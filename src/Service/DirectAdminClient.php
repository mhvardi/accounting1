<?php
namespace App\Service;

use App\Core\Database;
use PDO;

class DirectAdminClient
{
    private array $server;
    private PDO $pdo;
    private array $apiMap;
    private array $swagger;
    private array $config;
    private CacheService $cache;

    public function __construct(array $server)
    {
        $this->server = $server;
        $this->pdo = Database::connection();
        $this->cache = new CacheService();
        $this->apiMap = $this->loadJson(__DIR__ . '/../../json/directadmin/directadmin_reseller_api_map.json');
        $this->swagger = $this->loadJson(__DIR__ . '/../../json/directadmin/swagger.json');
        $this->config = $this->loadJson(__DIR__ . '/../../json/directadmin/reseller-config.json');
    }

    private function loadJson(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }
        $decoded = json_decode((string)file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function baseUrl(): string
    {
        $scheme = !empty($this->server['ssl']) ? 'https' : 'http';
        $host = $this->server['hostname'] ?: ($this->server['ip'] ?? '');
        $port = (int)($this->server['port'] ?? ($this->config['default_port'] ?? 2222));
        return sprintf('%s://%s:%d', $scheme, $host, $port);
    }

    public function request(string $endpoint, array $params = [], string $method = 'GET', bool $appendJsonFlag = true, string $logType = 'hosting'): array
    {
        $url = rtrim($this->baseUrl(), '/') . '/' . ltrim($endpoint, '/');
        if ($appendJsonFlag && stripos($endpoint, 'CMD_API_') !== false) {
            $params['json'] = 'yes';
        }

        $retries = 2;
        $lastError = '';
        $responseBody = null;
        $httpCode = 0;

        for ($i = 0; $i <= $retries; $i++) {
            $ch = curl_init();
            $opts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ];

            if (strtoupper($method) === 'GET' && !empty($params)) {
                $urlWithQuery = $url . '?' . http_build_query($params);
                $opts[CURLOPT_URL] = $urlWithQuery;
            } else {
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_CUSTOMREQUEST] = $method;
                $opts[CURLOPT_POSTFIELDS] = http_build_query($params);
            }

            if (!empty($this->server['username'])) {
                $credential = null;
                if (!empty($this->server['login_key'])) {
                    $credential = $this->server['username'] . ':' . $this->server['login_key'];
                } elseif (!empty($this->server['password'])) {
                    $credential = $this->server['username'] . ':' . $this->server['password'];
                }

                if ($credential !== null) {
                    $opts[CURLOPT_USERPWD] = $credential;
                }
            }

            curl_setopt_array($ch, $opts);
            $responseBody = curl_exec($ch);
            $lastError = curl_error($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($lastError === '' && $httpCode >= 200 && $httpCode < 300) {
                break;
            }

            usleep(250000 * ($i + 1));
        }

        $decoded = json_decode((string)$responseBody, true);
        $success = ($lastError === '' && $httpCode >= 200 && $httpCode < 300);
        $message = $lastError ?: 'HTTP ' . $httpCode;

        $this->log($logType, $endpoint, $params, $decoded ?? $responseBody, $success, $message);

        return [
            'success' => $success,
            'code' => $httpCode,
            'message' => $message,
            'data' => $decoded ?? $responseBody,
        ];
    }

    private function log(string $type, string $action, array $request, $response, bool $success, string $message): void
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO sync_logs (type, action, request_json, response_json, success, message, created_at) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([
                $type,
                $action,
                json_encode($request),
                json_encode($response),
                $success ? 1 : 0,
                $message,
                date('Y-m-d H:i:s'),
            ]);

            $audit = $this->pdo->prepare('INSERT INTO audit_logs (actor_user_id, entity_type, entity_id, action, request_json, response_json, success, message, created_at) VALUES (?,?,?,?,?,?,?,?,?)');
            $audit->execute([
                null,
                'server',
                $this->server['id'] ?? null,
                $action,
                json_encode($request),
                json_encode($response),
                $success ? 1 : 0,
                $message,
                date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // silence logging failures
        }
    }

    public function listUsers(): array
    {
        return $this->cache->get('da_users_' . ($this->server['id'] ?? '0'), function () {
            return $this->request('/CMD_API_SHOW_USERS', [], 'GET');
        }, 120);
    }

    public function createUser(array $data): array
    {
        $payload = array_merge(['action' => 'create', 'add' => 'Submit'], $data);
        return $this->request('/CMD_API_ACCOUNT_USER', $payload, 'POST');
    }

    public function modifyUser(array $data): array
    {
        $payload = array_merge(['action' => 'modify', 'add' => 'Submit'], $data);
        return $this->request('/CMD_API_ACCOUNT_USER', $payload, 'POST');
    }

    public function suspendUser(string $username): array
    {
        return $this->request('/CMD_API_SELECT_USERS', ['location' => 'CMD_SELECT_USERS', 'suspend' => 'Suspend', 'select0' => $username], 'POST');
    }

    public function unsuspendUser(string $username): array
    {
        return $this->request('/CMD_API_SELECT_USERS', ['location' => 'CMD_SELECT_USERS', 'unsuspend' => 'Unsuspend', 'select0' => $username], 'POST');
    }

    public function deleteUser(string $username): array
    {
        return $this->request('/CMD_API_SELECT_USERS', ['confirmed' => 'Confirm', 'delete' => 'yes', 'select0' => $username], 'POST');
    }

    public function listPackages(): array
    {
        return $this->cache->get('da_packages_' . ($this->server['id'] ?? '0'), function () {
            return $this->request('/CMD_API_PACKAGES_USER', [], 'GET');
        }, 300);
    }

    public function listDomainsForUser(string $username): array
    {
        return $this->request('/CMD_API_SHOW_DOMAINS', ['user' => $username], 'GET');
    }

    public function userUsage(string $username): array
    {
        return $this->request('/CMD_API_SHOW_USER_USAGE', ['user' => $username], 'GET');
    }

    public function resellerUsage(): array
    {
        return $this->request('/CMD_API_SHOW_RESELLER_USAGE', [], 'GET', true, 'hosting');
    }

    public function getResellerConfig(): array
    {
        return $this->config;
    }

    public function testConnection(): array
    {
        return $this->request('/CMD_API_SHOW_RESELLER_USAGE', [], 'GET', true, 'hosting');
    }
}
