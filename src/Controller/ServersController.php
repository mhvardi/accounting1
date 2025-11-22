<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Core\Str;
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
        } catch (PDOException $e) {
            View::renderError('خطا در مدیریت سرور: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        View::render('servers/index', [
            'user' => Auth::user(),
            'servers' => $servers,
            'flash' => $flash,
        ]);
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

