<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Date;
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

    private function respond(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function appendLog(array $meta, string $action, string $message): array
    {
        $logs = $meta['logs'] ?? [];
        $logs[] = [
            'action' => $action,
            'message' => $message,
            'at' => date('Y-m-d H:i:s'),
        ];
        $meta['logs'] = $logs;

        return $meta;
    }

    private function baseMeta(array $input, array $existing = []): array
    {
        $meta = $existing;
        $meta['domain'] = trim($input['domain'] ?? ($existing['domain'] ?? ''));
        $meta['registrar_id'] = (int)Str::normalizeDigits($input['registrar_id'] ?? ($existing['registrar_id'] ?? 0));
        $panel = $meta['panel'] ?? [];
        $panel['server_id'] = (int)Str::normalizeDigits($input['server_id'] ?? ($panel['server_id'] ?? 0));
        $panel['sync'] = (int)($panel['sync'] ?? 0);
        $panel['directadmin_username'] = $panel['directadmin_username'] ?? '';
        $meta['panel'] = $panel;

        return $meta;
    }

    private function findDomainProduct(PDO $pdo, ?int $requestedId): ?int
    {
        if ($requestedId) {
            $stmt = $pdo->prepare('SELECT id FROM products WHERE id = ? LIMIT 1');
            $stmt->execute([$requestedId]);
            $row = $stmt->fetch();
            if ($row) {
                return (int)$row['id'];
            }
        }

        $fallback = $pdo->query("SELECT id FROM products WHERE type = 'domain' ORDER BY id ASC LIMIT 1")->fetch();
        return $fallback ? (int)$fallback['id'] : null;
    }

    private function fetchDomainService(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT s.*, p.type AS product_type FROM service_instances s LEFT JOIN products p ON p.id = s.product_id WHERE s.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $service = $stmt->fetch();

        if (!$service) {
            return null;
        }

        if (($service['product_type'] ?? '') !== 'domain') {
            return null;
        }

        return $service;
    }

    public function create(): void
    {
        $this->ensureAuth();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->respond(['error' => 'invalid_method'], 405);
            return;
        }

        $customerId = (int)Str::normalizeDigits($_POST['customer_id'] ?? '0');
        $domain = trim($_POST['domain'] ?? '');
        $billingCycle = trim($_POST['billing_cycle'] ?? 'annual');
        $startDate = Date::fromJalaliInput($_POST['start_date'] ?? '') ?: null;
        $nextDue = Date::fromJalaliInput($_POST['next_due_date'] ?? '') ?: null;
        $productId = (int)Str::normalizeDigits($_POST['product_id'] ?? '0');

        if ($customerId <= 0 || $domain === '') {
            $this->respond(['error' => 'customer_id_and_domain_required'], 422);
            return;
        }

        try {
            $pdo = Database::connection();
            $resolvedProductId = $this->findDomainProduct($pdo, $productId);
            if (!$resolvedProductId) {
                $this->respond(['error' => 'domain_product_not_found'], 404);
                return;
            }

            $meta = $this->baseMeta($_POST, ['domain_status' => 'active']);
            $meta = $this->appendLog($meta, 'create', 'ثبت دامنه جدید');
            $now = date('Y-m-d H:i:s');

            $stmt = $pdo->prepare("INSERT INTO service_instances (customer_id, product_id, status, start_date, next_due_date, access_granted, billing_cycle, sale_amount, cost_amount, meta_json, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $customerId,
                $resolvedProductId,
                'active',
                $startDate,
                $nextDue,
                0,
                $billingCycle ?: null,
                0,
                0,
                json_encode($meta, JSON_UNESCAPED_UNICODE),
                $now,
                $now,
            ]);

            $this->respond(['status' => 'ok', 'id' => (int)$pdo->lastInsertId()]);
        } catch (PDOException $e) {
            $this->respond(['error' => 'db_error', 'message' => $e->getMessage()], 500);
        }
    }

    public function renew(): void
    {
        $this->ensureAuth();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->respond(['error' => 'invalid_method'], 405);
            return;
        }

        $id = (int)Str::normalizeDigits($_POST['id'] ?? '0');
        $nextDue = Date::fromJalaliInput($_POST['next_due_date'] ?? '') ?: null;
        $note = trim($_POST['note'] ?? '');

        if ($id <= 0 || !$nextDue) {
            $this->respond(['error' => 'id_and_next_due_required'], 422);
            return;
        }

        try {
            $pdo = Database::connection();
            $service = $this->fetchDomainService($pdo, $id);
            if (!$service) {
                $this->respond(['error' => 'domain_service_not_found'], 404);
                return;
            }

            $meta = json_decode($service['meta_json'] ?? '', true) ?: [];
            $meta = $this->appendLog($meta, 'renew', $note !== '' ? $note : 'تمدید دامنه');
            $stmt = $pdo->prepare("UPDATE service_instances SET next_due_date = ?, meta_json = ?, updated_at = ? WHERE id = ?");
            $stmt->execute([$nextDue, json_encode($meta, JSON_UNESCAPED_UNICODE), date('Y-m-d H:i:s'), $id]);

            $this->respond(['status' => 'ok', 'next_due_date' => $nextDue]);
        } catch (PDOException $e) {
            $this->respond(['error' => 'db_error', 'message' => $e->getMessage()], 500);
        }
    }

    public function transfer(): void
    {
        $this->ensureAuth();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->respond(['error' => 'invalid_method'], 405);
            return;
        }

        $id = (int)Str::normalizeDigits($_POST['id'] ?? '0');
        $note = trim($_POST['note'] ?? '');
        if ($id <= 0) {
            $this->respond(['error' => 'id_required'], 422);
            return;
        }

        try {
            $pdo = Database::connection();
            $service = $this->fetchDomainService($pdo, $id);
            if (!$service) {
                $this->respond(['error' => 'domain_service_not_found'], 404);
                return;
            }

            $meta = json_decode($service['meta_json'] ?? '', true) ?: [];
            $meta = $this->baseMeta($_POST, $meta);
            $meta = $this->appendLog($meta, 'transfer', $note !== '' ? $note : 'انتقال دامنه');

            $stmt = $pdo->prepare("UPDATE service_instances SET meta_json = ?, updated_at = ? WHERE id = ?");
            $stmt->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), date('Y-m-d H:i:s'), $id]);

            $this->respond(['status' => 'ok']);
        } catch (PDOException $e) {
            $this->respond(['error' => 'db_error', 'message' => $e->getMessage()], 500);
        }
    }

    public function suspend(): void
    {
        $this->ensureAuth();
        $this->changeStatus('suspended', 'suspend');
    }

    public function unsuspend(): void
    {
        $this->ensureAuth();
        $this->changeStatus('active', 'unsuspend');
    }

    public function delete(): void
    {
        $this->ensureAuth();
        $this->changeStatus('cancelled', 'delete');
    }

    private function changeStatus(string $status, string $action): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->respond(['error' => 'invalid_method'], 405);
            return;
        }

        $id = (int)Str::normalizeDigits($_POST['id'] ?? '0');
        $note = trim($_POST['note'] ?? '');
        if ($id <= 0) {
            $this->respond(['error' => 'id_required'], 422);
            return;
        }

        try {
            $pdo = Database::connection();
            $service = $this->fetchDomainService($pdo, $id);
            if (!$service) {
                $this->respond(['error' => 'domain_service_not_found'], 404);
                return;
            }

            $meta = json_decode($service['meta_json'] ?? '', true) ?: [];
            $meta['domain_status'] = $status;
            $meta = $this->appendLog($meta, $action, $note !== '' ? $note : 'تغییر وضعیت دامنه');

            $stmt = $pdo->prepare("UPDATE service_instances SET status = ?, meta_json = ?, updated_at = ? WHERE id = ?");
            $stmt->execute([$status, json_encode($meta, JSON_UNESCAPED_UNICODE), date('Y-m-d H:i:s'), $id]);

            $this->respond(['status' => 'ok', 'service_status' => $status]);
        } catch (PDOException $e) {
            $this->respond(['error' => 'db_error', 'message' => $e->getMessage()], 500);
        }
    }

    public function listUnsynced(): void
    {
        $this->ensureAuth();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            $this->respond(['error' => 'invalid_method'], 405);
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->query("SELECT s.*, c.name AS customer_name FROM service_instances s LEFT JOIN products p ON p.id = s.product_id LEFT JOIN customers c ON c.id = s.customer_id WHERE p.type = 'domain' ORDER BY s.id DESC");
            $services = $stmt->fetchAll();

            $unsynced = [];
            foreach ($services as $service) {
                $meta = json_decode($service['meta_json'] ?? '', true) ?: [];
                $serverId = (int)($meta['panel']['server_id'] ?? 0);
                $registrarId = (int)($meta['registrar_id'] ?? 0);
                if ($serverId === 0 || $registrarId === 0) {
                    $unsynced[] = [
                        'id' => (int)$service['id'],
                        'customer_id' => (int)$service['customer_id'],
                        'customer_name' => $service['customer_name'] ?? '',
                        'domain' => $meta['domain'] ?? '',
                        'status' => $service['status'] ?? '',
                        'server_id' => $serverId,
                        'registrar_id' => $registrarId,
                    ];
                }
            }

            $this->respond(['status' => 'ok', 'unsynced' => $unsynced]);
        } catch (PDOException $e) {
            $this->respond(['error' => 'db_error', 'message' => $e->getMessage()], 500);
        }
    }
}
