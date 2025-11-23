<?php
namespace App\Service;

use App\Core\Str;
use PDO;
use PDOException;

class DirectAdminService
{
    public static function call(array $server, string $endpoint, array $params = [], string $method = 'POST'): array
    {
        $scheme = !empty($server['ssl']) ? 'https' : 'http';
        $host = $server['hostname'] ?: ($server['ip'] ?? '');
        $port = (int)($server['port'] ?? 0);
        if ($port <= 0) {
            $port = !empty($server['ssl']) ? 443 : 80;
        }

        $url = sprintf('%s://%s:%d%s%s', $scheme, $host, $port, $endpoint, $method === 'GET' && !empty($params) ? '?' . http_build_query($params) : '');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        if (!empty($server['username']) && !empty($server['password'])) {
            curl_setopt($ch, CURLOPT_USERPWD, $server['username'] . ':' . $server['password']);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $success = ($err === '' && $code >= 200 && $code < 300);
        $message = $err ?: 'کد پاسخ: ' . $code;

        return [
            'success' => $success,
            'code' => $code,
            'message' => $success ? 'پاسخ موفق از DirectAdmin دریافت شد' : $message,
            'response' => $body,
        ];
    }

    public static function createAccount(array $server, array $payload): array
    {
        $params = array_merge([
            'action' => 'create',
            'add' => 'Submit',
        ], $payload);
        return self::call($server, '/CMD_API_ACCOUNT_USER', $params, 'POST');
    }

    public static function suspendAccount(array $server, string $username): array
    {
        $params = [
            'location' => 'CMD_SELECT_USERS',
            'suspend' => 'Suspend',
            'select0' => $username,
        ];
        return self::call($server, '/CMD_API_SELECT_USERS', $params, 'POST');
    }

    public static function unsuspendAccount(array $server, string $username): array
    {
        $params = [
            'location' => 'CMD_SELECT_USERS',
            'unsuspend' => 'Unsuspend',
            'select0' => $username,
        ];
        return self::call($server, '/CMD_API_SELECT_USERS', $params, 'POST');
    }

    public static function deleteAccount(array $server, string $username): array
    {
        $params = [
            'confirmed' => 'Confirm',
            'delete' => 'yes',
            'select0' => $username,
        ];
        return self::call($server, '/CMD_API_SELECT_USERS', $params, 'POST');
    }

    public static function syncAccount(array $server, string $username): array
    {
        $params = [
            'user' => $username,
            'json' => 'yes',
        ];
        return self::call($server, '/CMD_API_SHOW_USER_CONFIG', $params, 'GET');
    }

    public static function log(PDO $pdo, int $serviceId, int $serverId, string $action, array $result): void
    {
        try {
            $stmt = $pdo->prepare('INSERT INTO directadmin_logs (service_id, server_id, action, status, message, response, created_at) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([
                $serviceId,
                $serverId,
                $action,
                $result['success'] ? 'success' : 'error',
                $result['message'] ?? '',
                $result['response'] ?? null,
                date('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            // ignore logging failures to avoid breaking main flow
        }
    }

    private static function randomPassword(): string
    {
        try {
            return substr(bin2hex(random_bytes(8)), 0, 12);
        } catch (\Exception $e) {
            return (string)rand(100000, 999999) . 'Aa';
        }
    }

    public static function perform(PDO $pdo, array $serviceRow, string $action, bool $logOnly = false): array
    {
        $meta = json_decode($serviceRow['meta_json'] ?? '', true) ?: [];
        $panel = $meta['panel'] ?? [];
        $serverId = (int)($panel['server_id'] ?? 0);
        $username = trim($panel['directadmin_username'] ?? ($meta['host_user'] ?? ''));
        $domain = trim($meta['domain'] ?? '');
        $password = trim($meta['credentials']['password'] ?? '');

        if ($serverId <= 0) {
            return ['success' => false, 'message' => 'سرور DirectAdmin برای این سرویس انتخاب نشده است'];
        }
        if ($username === '') {
            return ['success' => false, 'message' => 'نام کاربری DirectAdmin در متادیتا سرویس موجود نیست'];
        }

        $stmt = $pdo->prepare('SELECT * FROM servers WHERE id = ?');
        $stmt->execute([$serverId]);
        $server = $stmt->fetch();
        if (!$server) {
            return ['success' => false, 'message' => 'سرور انتخاب‌شده یافت نشد'];
        }

        $result = ['success' => false, 'message' => 'اقدامی ثبت نشد', 'response' => null];
        if ($logOnly) {
            $result = ['success' => true, 'message' => 'درخواست در صف/لاگ ثبت شد', 'response' => null];
        } else {
            switch ($action) {
                case 'create':
                    $result = self::createAccount($server, [
                        'username' => $username,
                        'passwd' => $password ?: self::randomPassword(),
                        'passwd2' => $password ?: self::randomPassword(),
                        'email' => $serviceRow['customer_email'] ?? '',
                        'domain' => $domain,
                        'package' => $meta['product_type'] ?? 'shared',
                        'notify' => 'yes',
                    ]);
                    break;
                case 'suspend':
                    $result = self::suspendAccount($server, $username);
                    break;
                case 'unsuspend':
                    $result = self::unsuspendAccount($server, $username);
                    break;
                case 'delete':
                    $result = self::deleteAccount($server, $username);
                    break;
                case 'sync':
                default:
                    $result = self::syncAccount($server, $username);
            }
        }

        self::log($pdo, (int)$serviceRow['id'], $serverId, $action, $result);
        self::updateMeta($pdo, $serviceRow, $action, $result, $serverId);

        return $result;
    }

    public static function updateMeta(PDO $pdo, array $serviceRow, string $action, array $result, int $serverId): void
    {
        $meta = json_decode($serviceRow['meta_json'] ?? '', true) ?: [];
        if (!isset($meta['panel'])) {
            $meta['panel'] = [];
        }
        $meta['panel']['last_action'] = $action;
        $meta['panel']['sync_status'] = $result['success'] ? 'ok' : 'error';
        $meta['panel']['sync_message'] = $result['message'] ?? '';
        $meta['panel']['sync_at'] = date('Y-m-d H:i:s');
        $meta['panel']['sync_server'] = $serverId;
        if (!empty($result['response'])) {
            $meta['panel']['last_response'] = $result['response'];
        }

        try {
            $stmt = $pdo->prepare('UPDATE service_instances SET meta_json=?, updated_at=? WHERE id=?');
            $stmt->execute([
                json_encode($meta, JSON_UNESCAPED_UNICODE),
                date('Y-m-d H:i:s'),
                (int)$serviceRow['id'],
            ]);
        } catch (PDOException $e) {
            // swallow update errors to avoid blocking request
        }
    }
}
