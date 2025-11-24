<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Core\Str;
use App\Service\DirectAdminClient;
use App\Service\ServerHealthService;
use PDOException;

class ServersController
{
    private function ensureAuth(): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
    }

    public function index(): void
    {
        $this->ensureAuth();
        try {
            $pdo = Database::connection();
            $flash = null;
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                [$ok, $flash] = $this->store($pdo);
                if ($this->isAjax()) {
                    header('Content-Type: application/json');
                    http_response_code($ok ? 200 : 422);
                    echo json_encode(['success' => $ok, 'message' => $flash]);
                    return;
                }
            }

            $servers = $pdo->query("SELECT * FROM servers ORDER BY id DESC")->fetchAll();
            $connections = $this->mapConnections($pdo);
            $health = $this->hydrateHealth($pdo, $servers);
        } catch (PDOException $e) {
            View::renderError('خطا در مدیریت سرور: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        View::render('servers/index', [
            'user' => Auth::user(),
            'servers' => $servers,
            'connections' => $connections,
            'health' => $health,
            'flash' => $flash,
        ]);
    }

    private function hydrateHealth($pdo, array $servers): array
    {
        $out = [];
        foreach ($servers as $server) {
            $client = new DirectAdminClient($server);
            $connection = $client->testConnection();
            $usage = $client->resellerUsage();

            $healthResult = [
                'status' => $connection['success'] ?? false,
                'message' => $connection['message'] ?? 'نامشخص',
                'checked_at' => date('Y-m-d H:i:s'),
                'usage' => $this->summarizeResellerUsage($usage),
            ];

            ServerHealthService::persistStatus($pdo, (int)$server['id'], [
                'status' => $healthResult['status'],
                'message' => $healthResult['message'],
            ]);

            $out[$server['id']] = $healthResult;
        }

        return $out;
    }

    private function summarizeResellerUsage(array $usage): array
    {
        $success = $usage['success'] ?? false;
        $data = $usage['data'] ?? null;
        if (!$success || !is_array($data)) {
            return [
                'success' => false,
                'message' => $usage['message'] ?? 'اطلاعاتی دریافت نشد',
                'bandwidth' => null,
                'disk' => null,
                'accounts' => null,
            ];
        }

        return [
            'success' => true,
            'message' => 'به‌روزرسانی شد',
            'bandwidth' => $this->extractUsageBucket($data, ['bandwidth', 'bw']),
            'disk' => $this->extractUsageBucket($data, ['quota', 'disk']),
            'accounts' => $this->extractAccounts($data),
        ];
    }

    private function extractUsageBucket(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                continue;
            }
            $bucket = $data[$key];
            $used = $this->toNumeric($bucket['usage'] ?? $bucket['used'] ?? $bucket);
            $limit = $this->toNumeric($bucket['limit'] ?? $bucket['allocated'] ?? null);
            return ['used' => $used, 'limit' => $limit];
        }

        return ['used' => null, 'limit' => null];
    }

    private function extractAccounts(array $data): ?int
    {
        if (isset($data['users']) && is_array($data['users'])) {
            return count($data['users']);
        }
        if (isset($data['accounts']) && is_array($data['accounts'])) {
            return count($data['accounts']);
        }
        if (isset($data['users']) && is_numeric($data['users'])) {
            return (int)$data['users'];
        }
        return null;
    }

    private function toNumeric($value): ?float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        if (is_string($value) && preg_match('/([0-9]+\.?[0-9]*)/', $value, $m)) {
            return (float)$m[1];
        }
        return null;
    }

    private function mapConnections($pdo): array
    {
        $services = $pdo->query("SELECT s.*, c.name AS customer_name FROM service_instances s LEFT JOIN customers c ON c.id = s.customer_id ORDER BY s.id DESC")->fetchAll();
        $map = [];
        foreach ($services as $service) {
            $meta = json_decode($service['meta_json'] ?? '', true) ?: [];
            $serverId = (int)($meta['panel']['server_id'] ?? 0);
            if ($serverId <= 0) {
                continue;
            }
            if (!isset($map[$serverId])) {
                $map[$serverId] = [];
            }
            $map[$serverId][] = [
                'service_id' => (int)$service['id'],
                'customer_id' => (int)($service['customer_id'] ?? 0),
                'customer_name' => $service['customer_name'] ?? '',
                'domain' => $meta['domain'] ?? '',
                'status' => $service['status'] ?? '',
            ];
        }

        return $map;
    }

    private function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    private function normalizeServerInput(): array
    {
        $password = trim($_POST['password'] ?? '');
        $loginKey = trim($_POST['login_key'] ?? '');

        return [
            'id' => (int)Str::normalizeDigits($_POST['id'] ?? '0'),
            'hostname' => trim($_POST['hostname'] ?? ''),
            'ip' => trim($_POST['ip'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'password' => $password === '' ? null : $password,
            'login_key' => $loginKey === '' ? null : $loginKey,
            'ssl' => isset($_POST['ssl']) ? 1 : 0,
            'port' => (int)Str::normalizeDigits($_POST['port'] ?? '2222'),
        ];
    }

    private function validateServerInput(array $data): ?string
    {
        if ($data['hostname'] === '' || $data['ip'] === '' || $data['username'] === '') {
            return 'hostname، IP و نام کاربری الزامی هستند';
        }

        if (empty($data['password']) && empty($data['login_key'])) {
            return 'رمز عبور یا login key را وارد کنید';
        }

        if ($data['port'] <= 0 || $data['port'] > 65535) {
            return 'پورت نامعتبر است';
        }

        return null;
    }

    private function testDirectAdminConnection(array $data): array
    {
        $client = new DirectAdminClient($data);
        $result = $client->testConnection();
        return [
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? 'خطا در اتصال',
        ];
    }

    private function store($pdo): array
    {
        $data = $this->normalizeServerInput();
        $validationError = $this->validateServerInput($data);
        if ($validationError !== null) {
            return [false, $validationError];
        }

        $connection = $this->testDirectAdminConnection($data);
        if (!$connection['success']) {
            return [false, 'اتصال برقرار نشد: ' . $connection['message']];
        }

        $now = date('Y-m-d H:i:s');
        try {
            if ($data['id'] > 0) {
                $stmt = $pdo->prepare('UPDATE servers SET hostname = :hostname, ip = :ip, username = :username, password = :password, login_key = :login_key, ssl = :ssl, port = :port, last_check_status = :last_check_status, last_check_message = :last_check_message, last_checked_at = :last_checked_at, updated_at = :updated_at WHERE id = :id');
                $stmt->execute([
                    ':hostname' => $data['hostname'],
                    ':ip' => $data['ip'],
                    ':username' => $data['username'],
                    ':password' => $data['password'],
                    ':login_key' => $data['login_key'],
                    ':ssl' => $data['ssl'],
                    ':port' => $data['port'],
                    ':last_check_status' => 1,
                    ':last_check_message' => $connection['message'],
                    ':last_checked_at' => $now,
                    ':updated_at' => $now,
                    ':id' => $data['id'],
                ]);
                $id = $data['id'];
            } else {
                $stmt = $pdo->prepare('INSERT INTO servers (hostname, ip, username, password, login_key, ssl, port, last_check_status, last_check_message, last_checked_at, created_at, updated_at) VALUES (:hostname, :ip, :username, :password, :login_key, :ssl, :port, :last_check_status, :last_check_message, :last_checked_at, :created_at, :updated_at)');
                $stmt->execute([
                    ':hostname' => $data['hostname'],
                    ':ip' => $data['ip'],
                    ':username' => $data['username'],
                    ':password' => $data['password'],
                    ':login_key' => $data['login_key'],
                    ':ssl' => $data['ssl'],
                    ':port' => $data['port'],
                    ':last_check_status' => 1,
                    ':last_check_message' => $connection['message'],
                    ':last_checked_at' => $now,
                    ':created_at' => $now,
                    ':updated_at' => $now,
                ]);
                $id = (int)$pdo->lastInsertId();
            }
        } catch (PDOException $e) {
            return [false, 'ذخیره سرور ناموفق بود: ' . $e->getMessage()];
        }

        return [true, 'ثبت شد و اتصال برقرار است'];
    }

    public function test(): void
    {
        $this->ensureAuth();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            return;
        }

        $data = $this->normalizeServerInput();
        $validationError = $this->validateServerInput($data);
        if ($validationError !== null) {
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $validationError]);
            return;
        }

        $result = $this->testDirectAdminConnection($data);
        header('Content-Type: application/json');
        http_response_code($result['success'] ? 200 : 422);
        echo json_encode($result);
    }

    public function delete(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_GET['id'] ?? '0');
        if (!$id) {
            header('Location: /servers');
            return;
        }
        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare("DELETE FROM servers WHERE id=?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            View::renderError('حذف سرور ناموفق بود: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }
        header('Location: /servers');
    }

    public function check(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_GET['id'] ?? '0');
        if (!$id) {
            header('Location: /servers');
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare("SELECT * FROM servers WHERE id=?");
            $stmt->execute([$id]);
            $server = $stmt->fetch();
            if (!$server) {
                if ($this->isAjax()) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'سرور یافت نشد']);
                    return;
                }
                header('Location: /servers');
                return;
            }
            $client = new DirectAdminClient($server);
            $result = $client->testConnection();
            ServerHealthService::persistStatus($pdo, $id, [
                'status' => $result['success'] ?? false,
                'message' => $result['message'] ?? 'خطا در اتصال',
            ]);
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => $result['success'] ?? false,
                    'message' => $result['message'] ?? 'خطا در اتصال',
                    'checked_at' => date('Y-m-d H:i:s'),
                ]);
                return;
            }
        } catch (PDOException $e) {
            if ($this->isAjax()) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'خطا در بررسی اتصال: ' . $e->getMessage()]);
                return;
            }
        }

        header('Location: /servers');
    }

    public function syncHosting(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_GET['id'] ?? '0');
        if (!$id) {
            $this->respond(['success' => false, 'message' => 'شناسه سرور معتبر نیست'], 422);
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare('SELECT * FROM servers WHERE id=?');
            $stmt->execute([$id]);
            $server = $stmt->fetch();
            if (!$server) {
                $this->respond(['success' => false, 'message' => 'سرور یافت نشد'], 404);
                return;
            }

            $result = $this->syncHostingAccountsForServer($pdo, $server);
            $this->respond($result, $result['success'] ? 200 : 500);
        } catch (PDOException $e) {
            $this->respond(['success' => false, 'message' => 'خطای پایگاه داده: ' . $e->getMessage()], 500);
        }
    }

    private function syncHostingAccountsForServer($pdo, array $server): array
    {
        $stmt = $pdo->prepare('SELECT * FROM hosting_accounts WHERE server_id = ?');
        $stmt->execute([(int)$server['id']]);
        $accounts = $stmt->fetchAll();
        if (empty($accounts)) {
            return ['success' => true, 'message' => 'حساب میزبانی برای این سرور یافت نشد', 'synced' => 0, 'failed' => 0];
        }

        $client = new DirectAdminClient($server);
        $synced = 0;
        $failed = 0;
        $details = [];

        foreach ($accounts as $account) {
            $response = $client->userUsage($account['da_username']);
            $success = $response['success'] ?? false;
            $this->logHostingSync($pdo, $server, $account, $response);

            if ($success && is_array($response['data'] ?? null)) {
                $usage = $this->extractUsageBucket($response['data'], ['quota', 'disk']);
                $bandwidth = $this->extractUsageBucket($response['data'], ['bandwidth', 'bw']);
                $this->updateHostingUsage($pdo, $account['id'], $usage, $bandwidth);
                $synced++;
            } else {
                $failed++;
            }

            $details[] = [
                'account_id' => (int)$account['id'],
                'username' => $account['da_username'],
                'success' => $success,
                'message' => $response['message'] ?? '',
            ];
        }

        $message = sprintf('همگام‌سازی %d حساب انجام شد، %d مورد ناموفق بود', $synced, $failed);
        return ['success' => $failed === 0, 'message' => $message, 'synced' => $synced, 'failed' => $failed, 'details' => $details];
    }

    private function updateHostingUsage($pdo, int $id, array $disk, array $bandwidth): void
    {
        $stmt = $pdo->prepare('UPDATE hosting_accounts SET usage_disk_mb=?, usage_bw_mb=?, last_sync_at=?, updated_at=? WHERE id=?');
        $stmt->execute([
            $disk['used'] ?? 0,
            $bandwidth['used'] ?? 0,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
            $id,
        ]);
    }

    private function logHostingSync($pdo, array $server, array $account, array $response): void
    {
        try {
            $stmt = $pdo->prepare('INSERT INTO sync_logs (type, customer_id, service_id, action, request_json, response_json, success, message, created_at) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                'hosting',
                $account['customer_id'] ?? null,
                null,
                'server_hosting_sync',
                json_encode(['server_id' => $server['id'] ?? null, 'account_id' => $account['id'], 'username' => $account['da_username']]),
                json_encode($response),
                ($response['success'] ?? false) ? 1 : 0,
                $response['message'] ?? '',
                date('Y-m-d H:i:s'),
            ]);

            $audit = $pdo->prepare('INSERT INTO audit_logs (actor_user_id, customer_id, entity_type, entity_id, action, request_json, response_json, success, message, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $user = Auth::user();
            $audit->execute([
                $user['id'] ?? null,
                $account['customer_id'] ?? null,
                'hosting_account',
                $account['id'],
                'server_hosting_sync',
                json_encode(['server_id' => $server['id'] ?? null, 'account_id' => $account['id']]),
                json_encode($response),
                ($response['success'] ?? false) ? 1 : 0,
                $response['message'] ?? '',
                date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // swallow logging issues
        }
    }

    private function respond(array $payload, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}

