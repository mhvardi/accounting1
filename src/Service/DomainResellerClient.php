<?php
namespace App\Service;

use App\Core\Database;
use PDO;

class DomainResellerClient
{
    private PDO $pdo;
    private array $openApi;
    private CacheService $cache;
    private string $baseUrl;
    private ?string $apiKey;

    public function __construct(string $baseUrl, ?string $apiKey = null)
    {
        $this->pdo = Database::connection();
        $this->cache = new CacheService();
        $this->openApi = $this->loadJson(__DIR__ . '/../../json/domin/openapi (1).json');
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
    }

    private function loadJson(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }
        $decoded = json_decode((string)file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        $headers = ['Accept: application/json'];
        if ($this->apiKey) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        $ch = curl_init();
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if (strtoupper($method) === 'GET') {
            if (!empty($payload)) {
                $url .= '?' . http_build_query($payload);
            }
        } else {
            $opts[CURLOPT_CUSTOMREQUEST] = $method;
            $opts[CURLOPT_POSTFIELDS] = json_encode($payload);
            $headers[] = 'Content-Type: application/json';
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }

        $opts[CURLOPT_URL] = $url;
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string)$body, true);
        $success = ($err === '' && $code >= 200 && $code < 300);
        $message = $err ?: 'HTTP ' . $code;
        $this->log('domain', $path, $payload, $data ?? $body, $success, $message);

        return [
            'success' => $success,
            'code' => $code,
            'message' => $message,
            'data' => $data ?? $body,
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
                'domain',
                null,
                $action,
                json_encode($request),
                json_encode($response),
                $success ? 1 : 0,
                $message,
                date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // ignore logging errors
        }
    }

    public function getResellerBalance(): array
    {
        return $this->request('GET', '/balance');
    }

    public function listDomains(): array
    {
        return $this->cache->get('domains_all', function () {
            return $this->request('GET', '/domains');
        }, 120);
    }

    public function getDomainInfo(string $domain): array
    {
        return $this->request('GET', '/domains/' . $domain);
    }

    public function registerDomain(string $domain, int $years, array $contact, array $ns): array
    {
        return $this->request('POST', '/domains/register', [
            'domain' => $domain,
            'years' => $years,
            'contact' => $contact,
            'nameservers' => $ns,
        ]);
    }

    public function renewDomain(string $domain, int $years): array
    {
        return $this->request('POST', '/domains/renew', ['domain' => $domain, 'years' => $years]);
    }

    public function transferDomain(string $domain, string $authCode, array $ns = []): array
    {
        return $this->request('POST', '/domains/transfer', [
            'domain' => $domain,
            'authCode' => $authCode,
            'nameservers' => $ns,
        ]);
    }

    public function suspendDomain(string $domain): array
    {
        return $this->request('POST', '/domains/' . $domain . '/suspend');
    }

    public function unsuspendDomain(string $domain): array
    {
        return $this->request('POST', '/domains/' . $domain . '/unsuspend');
    }

    public function updateNameservers(string $domain, array $ns): array
    {
        return $this->request('PUT', '/domains/' . $domain . '/nameservers', ['nameservers' => $ns]);
    }

    public function getDNSRecords(string $domain): array
    {
        return $this->request('GET', '/domains/' . $domain . '/dns');
    }

    public function setDNSRecord(string $domain, array $record): array
    {
        return $this->request('POST', '/domains/' . $domain . '/dns', $record);
    }

    public function deleteDNSRecord(string $domain, string $recordId): array
    {
        return $this->request('DELETE', '/domains/' . $domain . '/dns/' . $recordId);
    }

    public function webhookVerifyAndParse(array $request): array
    {
        // Placeholder verification: ensure signature header exists
        $payload = $request['payload'] ?? '';
        $signature = $request['signature'] ?? '';
        $valid = $signature !== '';
        $this->log('domain', 'webhook', $request, ['valid' => $valid], $valid, $valid ? 'verified' : 'missing signature');
        return ['valid' => $valid, 'payload' => $payload];
    }
}
