<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Str;
use App\Service\DomainService;
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

    private function loadService(int $id)
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT s.*, p.type AS product_type, c.email AS customer_email FROM service_instances s LEFT JOIN products p ON p.id = s.product_id LEFT JOIN customers c ON c.id = s.customer_id WHERE s.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    private function buildPayload(string $action): array
    {
        $payload = [];
        $nameservers = isset($_POST['nameservers']) ? (array)$_POST['nameservers'] : [];
        if (empty($nameservers) && !empty($_POST['nameservers_csv'])) {
            $nameservers = array_filter(array_map('trim', explode(',', (string)$_POST['nameservers_csv'])));
        }

        switch ($action) {
            case 'register':
            case 'renew':
                $payload['years'] = (int)($_POST['years'] ?? 1);
                // no break
            case 'transfer':
                if ($action === 'transfer') {
                    $payload['auth_code'] = trim($_POST['auth_code'] ?? '');
                }
                $payload['nameservers'] = $nameservers;
                if (!empty($_POST['contact_json'])) {
                    $contact = json_decode((string)$_POST['contact_json'], true);
                    if (is_array($contact)) {
                        $payload['contact'] = $contact;
                    }
                }
                break;
            case 'dns_set':
                if (!empty($_POST['record_json'])) {
                    $record = json_decode((string)$_POST['record_json'], true);
                    if (is_array($record)) {
                        $payload['record'] = $record;
                    }
                }
                break;
            case 'dns_delete':
                $payload['record_id'] = $_POST['record_id'] ?? '';
                break;
        }

        return $payload;
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
            $service = $this->loadService($serviceId);
            if (!$service) {
                $this->respond(['success' => false, 'message' => 'سرویس یافت نشد'], 404);
                return;
            }
            if (($service['product_type'] ?? '') !== 'domain') {
                $this->respond(['success' => false, 'message' => 'این عملیات فقط برای دامنه‌ها فعال است'], 422);
                return;
            }

            $domainService = new DomainService(Auth::user()['id'] ?? null);
            $payload = $this->buildPayload($action);
            $result = $domainService->perform($service, $action, $payload);

            $code = $result['success'] ? 200 : 500;
            $this->respond([
                'success' => $result['success'] ?? false,
                'message' => $result['message'] ?? '',
                'response' => $result['data'] ?? null,
                'persisted' => $result['persisted'] ?? null,
            ], $code);
        } catch (PDOException $e) {
            $this->respond(['success' => false, 'message' => 'خطای پایگاه داده: ' . $e->getMessage()], 500);
        }
    }

    public function register(): void
    {
        $this->runAction('register');
    }

    public function renew(): void
    {
        $this->runAction('renew');
    }

    public function transfer(): void
    {
        $this->runAction('transfer');
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

    public function dns(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->runAction('dns_set');
        } else {
            $this->runAction('dns_get');
        }
    }

    public function dnsDelete(): void
    {
        $this->runAction('dns_delete');
    }

    public function whois(): void
    {
        $this->runAction('whois');
    }

    public function reconcile(): void
    {
        $this->ensureAuth();
        $svc = new DomainService(Auth::user()['id'] ?? null);
        $result = $svc->reconcile();
        $this->respond($result, $result['success'] ? 200 : 500);
    }
}
