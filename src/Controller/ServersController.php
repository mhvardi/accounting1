<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Core\Str;
use App\Service\ServerHealthService;
use PDO;
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
            $this->ensureServerSchema($pdo);
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
        } catch (PDOException $e) {
            View::renderError('خطا در مدیریت سرور: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        View::render('servers/index', [
            'user' => Auth::user(),
            'servers' => $servers,
            'connections' => $connections,
            'flash' => $flash,
        ]);
    }

    private function mapConnections($pdo): array
    {
        $services = $pdo
            ->query("SELECT s.*, c.name AS customer_name FROM service_instances s LEFT JOIN customers c ON c.id = s.customer_id ORDER BY s.id DESC")
            ->fetchAll();
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

    private function ensureServerSchema(PDO $pdo): void
    {
        try {
            $columns = $pdo->query('SHOW COLUMNS FROM servers')->fetchAll(PDO::FETCH_COLUMN);
            $required = [
                'allocated_ips' => "ALTER TABLE servers ADD COLUMN allocated_ips TEXT NULL AFTER notes",
                'monthly_cost'  => "ALTER TABLE servers ADD COLUMN monthly_cost DECIMAL(10,2) DEFAULT 0.00 AFTER allocated_ips",
                'datacenter'    => "ALTER TABLE servers ADD COLUMN datacenter VARCHAR(190) NULL AFTER monthly_cost",
                'account_limit' => "ALTER TABLE servers ADD COLUMN account_limit INT(11) NULL AFTER datacenter",
                'status_url'    => "ALTER TABLE servers ADD COLUMN status_url VARCHAR(255) NULL AFTER account_limit",
                'disabled'      => "ALTER TABLE servers ADD COLUMN disabled TINYINT(1) DEFAULT 0 AFTER status_url",
                'ns1'           => "ALTER TABLE servers ADD COLUMN ns1 VARCHAR(190) NULL AFTER disabled",
                'ns1_ip'        => "ALTER TABLE servers ADD COLUMN ns1_ip VARCHAR(45) NULL AFTER ns1",
                'ns2'           => "ALTER TABLE servers ADD COLUMN ns2 VARCHAR(190) NULL AFTER ns1_ip",
                'ns2_ip'        => "ALTER TABLE servers ADD COLUMN ns2_ip VARCHAR(45) NULL AFTER ns2",
                'ns3'           => "ALTER TABLE servers ADD COLUMN ns3 VARCHAR(190) NULL AFTER ns2_ip",
                'ns3_ip'        => "ALTER TABLE servers ADD COLUMN ns3_ip VARCHAR(45) NULL AFTER ns3",
                'ns4'           => "ALTER TABLE servers ADD COLUMN ns4 VARCHAR(190) NULL AFTER ns3_ip",
                'ns4_ip'        => "ALTER TABLE servers ADD COLUMN ns4_ip VARCHAR(45) NULL AFTER ns4",
                'ns5'           => "ALTER TABLE servers ADD COLUMN ns5 VARCHAR(190) NULL AFTER ns4_ip",
                'ns5_ip'        => "ALTER TABLE servers ADD COLUMN ns5_ip VARCHAR(45) NULL AFTER ns5",
                'module'        => "ALTER TABLE servers ADD COLUMN module VARCHAR(50) DEFAULT 'directadmin' AFTER ns5_ip",
                'username'      => "ALTER TABLE servers ADD COLUMN username VARCHAR(190) NULL AFTER module",
                'password'      => "ALTER TABLE servers ADD COLUMN password VARCHAR(190) NULL AFTER username",
                'ssl'           => "ALTER TABLE servers ADD COLUMN ssl TINYINT(1) DEFAULT 0 AFTER password",
                'port'          => "ALTER TABLE servers ADD COLUMN port INT(11) DEFAULT 2222 AFTER ssl",
            ];

            foreach ($required as $column => $ddl) {
                if (!in_array($column, $columns, true)) {
                    $pdo->exec($ddl);
                }
            }
        } catch (PDOException $e) {
            // quietly continue; schema sync is best-effort
        }
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
            'module'       => 'directadmin',
            'username'     => trim($_POST['username'] ?? ''),
            'password'     => trim($_POST['password'] ?? ''),
            'ssl'          => isset($_POST['ssl']) ? 1 : 0,
            'port'         => (int)Str::normalizeDigits($_POST['port'] ?? '2222'),
        ];

        if ($data['name'] === '' || $data['hostname'] === '' || $data['ip'] === '' || $data['username'] === '' || $data['password'] === '') {
            return [false, 'تمام فیلدهای اصلی الزامی هستند'];
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
                $stmt = $pdo->prepare("INSERT INTO servers (name, hostname, ip, allocated_ips, monthly_cost, datacenter, account_limit, status_url, disabled, ns1, ns1_ip, ns2, ns2_ip, ns3, ns3_ip, ns4, ns4_ip, ns5, ns5_ip, module, username, password, ssl, port, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
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
            $result = ServerHealthService::checkAndPersist($server);
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => $result['status'],
                    'message' => $result['message'],
                    'checked_at' => $result['checked_at'] ?? null
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
}

