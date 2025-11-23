<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Str;
use App\Service\DirectAdminClient;
use PDO;
use PDOException;

class HostingController
{
    private function ensureAuth(): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
    }

    private function respond(array $payload, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function loadAccount(int $id)
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT h.*, s.id AS srv_id, s.name AS srv_name, s.hostname AS srv_hostname, s.ip AS srv_ip, s.port AS srv_port, s.username AS srv_username, s.password AS srv_password, s.ssl AS srv_ssl FROM hosting_accounts h LEFT JOIN servers s ON s.id = h.server_id WHERE h.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    private function logAction(PDO $pdo, array $account, string $action, array $request, $response, bool $success, string $message): void
    {
        try {
            $stmt = $pdo->prepare('INSERT INTO sync_logs (type, customer_id, service_id, action, request_json, response_json, success, message, created_at) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                'hosting',
                $account['customer_id'] ?? null,
                $account['id'] ?? null,
                $action,
                json_encode($request),
                json_encode($response),
                $success ? 1 : 0,
                $message,
                date('Y-m-d H:i:s'),
            ]);

            $audit = $pdo->prepare('INSERT INTO audit_logs (actor_user_id, customer_id, entity_type, entity_id, action, before_json, after_json, request_json, response_json, success, message, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
            $audit->execute([
                Auth::user()['id'] ?? null,
                $account['customer_id'] ?? null,
                'hosting_account',
                $account['id'] ?? null,
                $action,
                null,
                null,
                json_encode($request),
                json_encode($response),
                $success ? 1 : 0,
                $message,
                date('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            // swallow logging errors
        }
    }

    private function persistUsage(PDO $pdo, int $accountId, array $meta, array $usage, ?string $status = null): void
    {
        $disk = (int)($usage['disk_mb'] ?? $usage['quota'] ?? $usage['disk'] ?? 0);
        $bw = (int)($usage['bw_mb'] ?? $usage['bandwidth'] ?? $usage['bw'] ?? 0);

        $meta['last_usage'] = $usage;
        $meta['sync_at'] = date('Y-m-d H:i:s');
        if ($status !== null) {
            $meta['sync_status'] = $status;
        }

        $stmt = $pdo->prepare('UPDATE hosting_accounts SET usage_disk_mb=?, usage_bw_mb=?, last_sync_at=?, meta_json=?, updated_at=? WHERE id=?');
        $stmt->execute([
            $disk,
            $bw,
            date('Y-m-d H:i:s'),
            json_encode($meta, JSON_UNESCAPED_UNICODE),
            date('Y-m-d H:i:s'),
            $accountId,
        ]);
    }

    private function runAction(string $action): void
    {
        $this->ensureAuth();
        $accountId = (int)Str::normalizeDigits($_POST['id'] ?? $_GET['id'] ?? '0');
        if ($accountId <= 0) {
            $this->respond(['success' => false, 'message' => 'شناسه هاست نامعتبر است'], 422);
            return;
        }

        try {
            $pdo = Database::connection();
            $account = $this->loadAccount($accountId);
            if (!$account) {
                $this->respond(['success' => false, 'message' => 'هاست یافت نشد'], 404);
                return;
            }
            $server = [
                'id' => $account['srv_id'] ?? $account['server_id'] ?? null,
                'name' => $account['srv_name'] ?? '',
                'hostname' => $account['srv_hostname'] ?? '',
                'ip' => $account['srv_ip'] ?? '',
                'port' => $account['srv_port'] ?? null,
                'username' => $account['srv_username'] ?? '',
                'password' => $account['srv_password'] ?? '',
                'ssl' => $account['srv_ssl'] ?? 0,
            ];

            if (empty($server['id']) || ($server['hostname'] === '' && ($server['ip'] ?? '') === '')) {
                $this->respond(['success' => false, 'message' => 'سرور هاستینگ مشخص نشده است'], 422);
                return;
            }

            $client = new DirectAdminClient($server);
            $result = ['success' => false, 'message' => 'اقدامی انجام نشد'];
            $meta = json_decode($account['meta_json'] ?? '', true) ?: [];
            $usage = [];

            switch ($action) {
                case 'suspend':
                    $result = $client->suspendUser($account['da_username']);
                    if ($result['success']) {
                        $meta['status'] = 'suspended';
                        $stmt = $pdo->prepare('UPDATE hosting_accounts SET status=?, updated_at=? WHERE id=?');
                        $stmt->execute(['suspended', date('Y-m-d H:i:s'), $accountId]);
                    }
                    break;
                case 'unsuspend':
                    $result = $client->unsuspendUser($account['da_username']);
                    if ($result['success']) {
                        $meta['status'] = 'active';
                        $stmt = $pdo->prepare('UPDATE hosting_accounts SET status=?, updated_at=? WHERE id=?');
                        $stmt->execute(['active', date('Y-m-d H:i:s'), $accountId]);
                    }
                    break;
                case 'reconcile':
                case 'sync':
                default:
                    $result = $client->userUsage($account['da_username']);
                    $data = is_array($result['data'] ?? null) ? $result['data'] : [];
                    $usage = $data['usage'] ?? $data;
                    if ($result['success']) {
                        $this->persistUsage($pdo, $accountId, $meta, $usage, $result['success'] ? 'ok' : 'error');
                    }
                    break;
            }

            $this->logAction($pdo, $account, $action, ['account_id' => $accountId], $result, (bool)$result['success'], $result['message'] ?? '');
            $this->respond([
                'success' => $result['success'] ?? false,
                'message' => $result['message'] ?? '',
                'usage' => $usage,
            ], $result['success'] ? 200 : 500);
        } catch (PDOException $e) {
            $this->respond(['success' => false, 'message' => 'خطای پایگاه داده: ' . $e->getMessage()], 500);
        }
    }

    public function sync(): void
    {
        $this->runAction('sync');
    }

    public function suspend(): void
    {
        $this->runAction('suspend');
    }

    public function unsuspend(): void
    {
        $this->runAction('unsuspend');
    }

    public function reconcile(): void
    {
        $this->runAction('reconcile');
    }
}
