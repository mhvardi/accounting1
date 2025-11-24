<?php
namespace App\Service;

use App\Core\Database;
use PDO;
use PDOException;

class ServerHealthService
{
    public static function check(array $server): array
    {
        $scheme = !empty($server['ssl']) ? 'https' : 'http';
        $host = $server['hostname'] ?: $server['ip'];
        $port = (int)($server['port'] ?? 0);
        if ($port <= 0) {
            $port = !empty($server['ssl']) ? 443 : 80;
        }
        $url = sprintf('%s://%s:%d/CMD_API_SHOW_SERVICES?json=yes', $scheme, $host, $port);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        if (!empty($server['username'])) {
            $credential = null;
            if (!empty($server['login_key'])) {
                $credential = $server['username'] . ':' . $server['login_key'];
            } elseif (!empty($server['password'])) {
                $credential = $server['username'] . ':' . $server['password'];
            }

            if ($credential !== null) {
                curl_setopt($ch, CURLOPT_USERPWD, $credential);
            }
        }

        $output = curl_exec($ch);
        $err    = curl_error($ch);
        $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($output === false) {
            return ['status' => false, 'message' => $err ?: 'اتصال برقرار نشد'];
        }

        if ($code >= 200 && $code < 500) {
            return ['status' => true, 'message' => 'پاسخ از سرور دریافت شد'];
        }

        return ['status' => false, 'message' => 'کد پاسخ نامعتبر: ' . $code];
    }

    public static function persistStatus(PDO $pdo, int $id, array $result): void
    {
        try {
            $stmt = $pdo->prepare('UPDATE servers SET last_check_status=?, last_check_message=?, last_checked_at=? WHERE id=?');
            $stmt->execute([
                $result['status'] ? 1 : 0,
                $result['message'] ?? null,
                date('Y-m-d H:i:s'),
                $id,
            ]);
        } catch (PDOException $e) {
            // swallow to avoid breaking UI
        }
    }

    public static function checkAndPersist(array $server): array
    {
        $pdo = Database::connection();
        $result = self::check($server);
        self::persistStatus($pdo, (int)$server['id'], $result);
        $result['checked_at'] = date('Y-m-d H:i:s');
        return $result;
    }

    public static function checkAll(): array
    {
        $pdo = Database::connection();
        $servers = $pdo->query('SELECT * FROM servers')->fetchAll();
        $out = [];
        foreach ($servers as $srv) {
            $out[$srv['id']] = self::checkAndPersist($srv);
        }
        return $out;
    }
}
