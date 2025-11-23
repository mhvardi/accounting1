<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Str;
use PDO;
use PDOException;

class DomainController
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

    private function loadService(PDO $pdo, int $id)
    {
        $stmt = $pdo->prepare('SELECT s.*, p.type AS product_type FROM service_instances s LEFT JOIN products p ON p.id = s.product_id WHERE s.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    private function log(PDO $pdo, int $serviceId, string $action, bool $success, string $message): void
    {
        try {
            $stmt = $pdo->prepare('INSERT INTO directadmin_logs (service_id, server_id, action, status, message, response, created_at) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([
                $serviceId,
                0,
                $action,
                $success ? 'success' : 'error',
                $message,
                null,
                date('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            // ignore log errors
        }
    }

    private function runAction(string $action): void
    {
        $this->ensureAuth();
        $serviceId = (int)Str::normalizeDigits($_POST['service_id'] ?? $_GET['service_id'] ?? '0');

        if ($serviceId <= 0) {
            $this->respond(['success' => false, 'message' => 'شناسه سرویس معتبر نیست'], 422);
            return;
        }

        try {
            $pdo = Database::connection();
            $service = $this->loadService($pdo, $serviceId);
            if (!$service) {
                $this->respond(['success' => false, 'message' => 'سرویس یافت نشد'], 404);
                return;
            }
            if (($service['product_type'] ?? '') !== 'domain') {
                $this->respond(['success' => false, 'message' => 'این عملیات فقط برای دامنه‌ها فعال است'], 422);
                return;
            }

            $meta = json_decode($service['meta_json'] ?? '', true) ?: [];
            $now  = date('Y-m-d H:i:s');
            $message = 'عملیات ثبت شد';
            $status  = 'ok';
            $serviceStatus = $service['status'];

            switch ($action) {
                case 'suspend':
                    $status  = 'suspended';
                    $message = 'دامنه معلق شد';
                    $serviceStatus = 'suspended';
                    break;
                case 'renew':
                    $status  = 'renewed';
                    $message = 'تمدید دامنه ثبت شد';
                    break;
                default:
                    $message = 'دامنه سینک شد';
                    $status  = 'ok';
            }

            $meta['domain_sync_status']  = $status;
            $meta['domain_sync_message'] = $message;
            $meta['domain_sync_at']      = $now;

            $updateStmt = $pdo->prepare('UPDATE service_instances SET meta_json = ?, status = ?, updated_at = ? WHERE id = ?');
            $updateStmt->execute([
                json_encode($meta, JSON_UNESCAPED_UNICODE),
                $serviceStatus,
                $now,
                $serviceId,
            ]);

            $this->log($pdo, $serviceId, $action, true, $message);
            $this->respond(['success' => true, 'message' => $message, 'meta' => $meta]);
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

    public function renew(): void
    {
        $this->runAction('renew');
    }
}
