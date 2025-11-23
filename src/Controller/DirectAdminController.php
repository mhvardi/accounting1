<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Str;
use App\Service\DirectAdminService;
use PDOException;

class DirectAdminController
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

    private function loadService(int $id)
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT s.*, c.email AS customer_email FROM service_instances s LEFT JOIN customers c ON c.id = s.customer_id WHERE s.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    private function runAction(string $action): void
    {
        $this->ensureAuth();
        $serviceId = (int)Str::normalizeDigits($_POST['service_id'] ?? $_GET['service_id'] ?? '0');
        $logOnly = isset($_POST['log_only']);

        if ($serviceId <= 0) {
            $this->respond(['success' => false, 'message' => 'شناسه سرویس معتبر نیست'], 422);
            return;
        }

        try {
            $pdo = Database::connection();
            $service = $this->loadService($serviceId);
            if (!$service) {
                $this->respond(['success' => false, 'message' => 'سرویس یافت نشد'], 404);
                return;
            }

            $result = DirectAdminService::perform($pdo, $service, $action, $logOnly);
            $this->respond(['success' => $result['success'], 'message' => $result['message'], 'response' => $result['response'] ?? null]);
        } catch (PDOException $e) {
            $this->respond(['success' => false, 'message' => 'خطای پایگاه‌داده: ' . $e->getMessage()], 500);
        }
    }

    public function create(): void
    {
        $this->runAction('create');
    }

    public function suspend(): void
    {
        $this->runAction('suspend');
    }

    public function unsuspend(): void
    {
        $this->runAction('unsuspend');
    }

    public function delete(): void
    {
        $this->runAction('delete');
    }

    public function sync(): void
    {
        $this->runAction('sync');
    }
}
