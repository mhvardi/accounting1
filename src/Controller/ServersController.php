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

    private function store($pdo): array
    {
        $id = (int)Str::normalizeDigits($_POST['id'] ?? '0');
        $data = [
            'name'         => trim($_POST['name'] ?? ''),
            'hostname'     => trim($_POST['hostname'] ?? ''),
            'ip'           => trim($_POST['ip'] ?? ''),
            'allocated_ips'=> trim($_POST['allocated_ips'] ?? ''),
            'monthly_cost' => (float)($_POST['monthly_cost'] ?? 0),
            'datacenter'   => trim($_POST['datacenter'] ?? ''),
            'account_limit'=> (int)Str::normalizeDigits($_POST['account_limit'] ?? '0'),
            'status_url'   => trim($_POST['status_url'] ?? ''),
            'disabled'     => isset($_POST['disabled']) ? 1 : 0,
            'ns1'          => trim($_POST['ns1'] ?? ''),
            'ns1_ip'       => trim($_POST['ns1_ip'] ?? ''),
            'ns2'          => trim($_POST['ns2'] ?? ''),
            'ns2_ip'       => trim($_POST['ns2_ip'] ?? ''),
            'ns3'          => trim($_POST['ns3'] ?? ''),
            'ns3_ip'       => trim($_POST['ns3_ip'] ?? ''),
            'ns4'          => trim($_POST['ns4'] ?? ''),
            'ns4_ip'       => trim($_POST['ns4_ip'] ?? ''),
            'ns5'          => trim($_POST['ns5'] ?? ''),
            'ns5_ip'       => trim($_POST['ns5_ip'] ?? ''),
            'module'       => strtolower(trim($_POST['module'] ?? 'directadmin')),
            'username'     => trim($_POST['username'] ?? ''),
            'password'     => trim($_POST['password'] ?? ''),
            'ssl'          => isset($_POST['ssl']) ? 1 : 0,
            'port'         => (int)Str::normalizeDigits($_POST['port'] ?? '2222'),
        ];

        if ($data['name'] === '' || $data['hostname'] === '' || $data['ip'] === '' || $data['username'] === '' || $data['password'] === '') {
            return [false, 'تمام فیلدهای اصلی الزامی هستند'];
        }

        if ($data['module'] !== 'directadmin') {
            return [false, 'ماژول انتخاب‌شده پشتیبانی نمی‌شود'];
        }

        if ($data['port'] <= 0 || $data['port'] > 65535) {
            return [false, 'پورت نامعتبر است'];
        }

        $now = date('Y-m-d H:i:s');
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE servers SET name=?, hostname=?, ip=?, allocated_ips=?, monthly_cost=?, datacenter=?, account_limit=?, status_url=?, disabled=?, ns1=?, ns1_ip=?, ns2=?, ns2_ip=?, ns3=?, ns3_ip=?, ns4=?, ns4_ip=?, ns5=?, ns5_ip=?, module=?, username=?, password=?, ssl=?, port=?, updated_at=? WHERE id=?");
                $stmt->execute([
                    $data['name'],
                    $data['hostname'],
                    $data['ip'],
                    $data['allocated_ips'],
                    $data['monthly_cost'],
                    $data['datacenter'],
                    $data['account_limit'],
                    $data['status_url'],
                    $data['disabled'],
                    $data['ns1'],
                    $data['ns1_ip'],
                    $data['ns2'],
                    $data['ns2_ip'],
                    $data['ns3'],
                    $data['ns3_ip'],
                    $data['ns4'],
                    $data['ns4_ip'],
                    $data['ns5'],
                    $data['ns5_ip'],
                    $data['module'],
                    $data['username'],
                    $data['password'],
                    $data['ssl'],
                    $data['port'],
                    $now,
                    $id
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO servers (name, hostname, ip, allocated_ips, monthly_cost, datacenter, account_limit, status_url, disabled, ns1, ns1_ip, ns2, ns2_ip, ns3, ns3_ip, ns4, ns4_ip, ns5, ns5_ip, module, username, password, ssl, port, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $data['name'],
                    $data['hostname'],
                    $data['ip'],
                    $data['allocated_ips'],
                    $data['monthly_cost'],
                    $data['datacenter'],
                    $data['account_limit'],
                    $data['status_url'],
                    $data['disabled'],
                    $data['ns1'],
                    $data['ns1_ip'],
                    $data['ns2'],
                    $data['ns2_ip'],
                    $data['ns3'],
                    $data['ns3_ip'],
                    $data['ns4'],
                    $data['ns4_ip'],
                    $data['ns5'],
                    $data['ns5_ip'],
                    $data['module'],
                    $data['username'],
                    $data['password'],
                    $data['ssl'],
                    $data['port'],
                    $now,
                    $now
                ]);
                $id = (int)$pdo->lastInsertId();
            }
        } catch (PDOException $e) {
            return [false, 'ذخیره سرور ناموفق بود: ' . $e->getMessage()];
        }

        $stmt = $pdo->prepare("SELECT * FROM servers WHERE id=?");
        $stmt->execute([$id]);
        $serverRow = $stmt->fetch();
        $healthMsg = 'ثبت شد';
        if ($serverRow) {
            $result = ServerHealthService::checkAndPersist($serverRow);
            $healthMsg = $result['status'] ? 'ثبت شد و اتصال برقرار است' : 'ثبت شد اما اتصال برقرار نشد: ' . $result['message'];
        }

        return [true, $healthMsg];
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

