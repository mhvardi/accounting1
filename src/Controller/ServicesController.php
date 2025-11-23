<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\View;
use App\Core\Database;
use App\Core\Date;
use App\Core\Str;
use App\Service\DirectAdminService;
use PDOException;

class ServicesController
{
    protected function ensureAuth(): void
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
            $this->ensureCatalog($pdo);
            $services = $pdo->query("SELECT s.*, c.name AS customer_name, p.name AS product_name, p.type AS product_type, p.billing_cycle AS product_billing_cycle, pc.name AS category_name, pc.slug AS category_slug FROM service_instances s LEFT JOIN customers c ON c.id = s.customer_id LEFT JOIN products p ON p.id = s.product_id LEFT JOIN product_categories pc ON pc.id = s.category_id ORDER BY s.id DESC")->fetchAll();
            $customers = $pdo->query("SELECT id, name FROM customers ORDER BY id DESC")->fetchAll();
            $products  = $pdo->query("SELECT id, name, type, billing_cycle FROM products ORDER BY type, name")->fetchAll();
            $categories = $pdo->query("SELECT id, name, slug FROM product_categories ORDER BY is_primary DESC, id DESC")->fetchAll();
            $servers   = $pdo->query("SELECT id, name, hostname FROM servers ORDER BY id DESC")->fetchAll();
            $serversMap = [];
            foreach ($servers as $srv) {
                $serversMap[$srv['id']] = $srv;
            }
        } catch (PDOException $e) {
            View::renderError('خطا در بارگذاری سرویس‌ها: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        View::render('services/index', [
            'user' => Auth::user(),
            'services' => $services,
            'customers' => $customers,
            'products' => $products,
            'categories' => $categories,
            'servers' => $servers,
            'serversMap' => $serversMap,
            'months' => Date::monthNames(),
            'years' => Date::financialYears(),
        ]);
    }

    public function store(): void
    {
        $this->ensureAuth();
        $customerId = (int)Str::normalizeDigits($_POST['customer_id'] ?? '0');
        $productId  = (int)Str::normalizeDigits($_POST['product_id'] ?? '0');
        $categoryId = (int)Str::normalizeDigits($_POST['category_id'] ?? '0');
        $status     = $_POST['status'] ?? 'active';
        $access     = isset($_POST['access_granted']) ? 1 : 0;
        $start      = Date::fromJalaliInput($_POST['start_date'] ?? '');
        $nextDue    = Date::fromJalaliInput($_POST['next_due_date'] ?? '');
        $meta = $this->buildMeta($_POST);
        $saleAmount = (int)Str::normalizeDigits($_POST['sale_amount'] ?? '0');
        $costAmount = (int)Str::normalizeDigits($_POST['cost_amount'] ?? '0');
        $billingCycle = trim($_POST['billing_cycle'] ?? ($_POST['selected_billing_cycle'] ?? ''));
        $contractId = (int)Str::normalizeDigits($_POST['contract_id'] ?? '0');
        $daAction = trim($_POST['da_action'] ?? '');
        $daLogOnly = isset($_POST['da_log_only']);

        if (!$customerId || (!$productId && !$categoryId)) {
            header('Location: /services');
            return;
        }

        try {
            $pdo = Database::connection();
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO service_instances (customer_id, product_id, category_id, contract_id, status, start_date, next_due_date, access_granted, billing_cycle, sale_amount, cost_amount, meta_json, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$customerId, $productId ?: null, $categoryId ?: null, $contractId ?: null, $status, $start, $nextDue, $access, $billingCycle ?: null, $saleAmount, $costAmount, json_encode($meta, JSON_UNESCAPED_UNICODE), $now, $now]);

            if ($daAction !== '' && ($service = $this->loadService($pdo, (int)$pdo->lastInsertId()))) {
                DirectAdminService::perform($pdo, $service, $daAction, $daLogOnly);
            }
        } catch (PDOException $e) {
            View::renderError('خطا در ثبت سرویس: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        header('Location: /services');
    }

    public function update(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_POST['id'] ?? '0');
        $status     = $_POST['status'] ?? 'active';
        $access     = isset($_POST['access_granted']) ? 1 : 0;
        $start      = Date::fromJalaliInput($_POST['start_date'] ?? '');
        $nextDue    = Date::fromJalaliInput($_POST['next_due_date'] ?? '');
        $categoryId = (int)Str::normalizeDigits($_POST['category_id'] ?? '0');
        $saleAmount = (int)Str::normalizeDigits($_POST['sale_amount'] ?? '0');
        $costAmount = (int)Str::normalizeDigits($_POST['cost_amount'] ?? '0');
        $billingCycle = trim($_POST['billing_cycle'] ?? ($_POST['selected_billing_cycle'] ?? ''));
        $contractId = (int)Str::normalizeDigits($_POST['contract_id'] ?? '0');
        $daAction = trim($_POST['da_action'] ?? '');
        $daLogOnly = isset($_POST['da_log_only']);

        if (!$id) {
            header('Location: /services');
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare('SELECT * FROM service_instances WHERE id=?');
            $stmt->execute([$id]);
            $serviceRow = $stmt->fetch();
            if (!$serviceRow) {
                header('Location: /services');
                return;
            }
            $existingMeta = json_decode($serviceRow['meta_json'] ?? '', true) ?: [];
            $meta = $this->buildMeta($_POST, $existingMeta);
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("UPDATE service_instances SET status=?, start_date=?, next_due_date=?, access_granted=?, meta_json=?, category_id=?, billing_cycle=?, sale_amount=?, cost_amount=?, contract_id=?, updated_at=? WHERE id=?");
            $stmt->execute([$status, $start, $nextDue, $access, json_encode($meta, JSON_UNESCAPED_UNICODE), $categoryId ?: null, $billingCycle ?: null, $saleAmount, $costAmount, $contractId ?: null, $now, $id]);

            if ($daAction !== '' && ($service = $this->loadService($pdo, $id))) {
                $service['meta_json'] = json_encode($meta, JSON_UNESCAPED_UNICODE);
                DirectAdminService::perform($pdo, $service, $daAction, $daLogOnly);
            }
        } catch (PDOException $e) {
            View::renderError('خطا در بروزرسانی سرویس: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        header('Location: /services');
    }

    public function delete(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_GET['id'] ?? '0');
        if (!$id) {
            header('Location: /services');
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare("DELETE FROM service_instances WHERE id = ?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            View::renderError('خطا در حذف سرویس: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        header('Location: /services');
    }

    private function ensureCatalog($pdo): void
    {
        $existing = $pdo->query("SELECT COUNT(*) AS cnt FROM products")->fetch();
        if ((int)($existing['cnt'] ?? 0) > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $defaults = [
            ['هاست اشتراکی', 'hosting', 'monthly'],
            ['دامنه IR', 'domain', 'annual'],
            ['دامنه COM', 'domain', 'annual'],
            ['پشتیبانی سایت', 'service', 'annual'],
            ['سئو ماهانه', 'seo', 'monthly'],
            ['طراحی سایت', 'service', 'lifetime'],
        ];
        $stmt = $pdo->prepare("INSERT INTO products (name, type, billing_cycle, price, meta_json, created_at, updated_at) VALUES (?,?,?,?,?,?,?)");
        foreach ($defaults as $d) {
            $stmt->execute([$d[0], $d[1], $d[2], 0, json_encode([], JSON_UNESCAPED_UNICODE), $now, $now]);
        }
    }

    private function loadService($pdo, int $id)
    {
        $stmt = $pdo->prepare('SELECT s.*, c.email AS customer_email FROM service_instances s LEFT JOIN customers c ON c.id = s.customer_id WHERE s.id=?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    private function buildMeta(array $input, array $existingMeta = []): array
    {
        $domain    = trim($input['domain'] ?? '');
        $host_user = trim($input['host_user'] ?? '');
        $productType = trim($input['product_type'] ?? ($input['selected_product_type'] ?? 'service'));
        $categorySlug = trim($input['category_slug'] ?? '');
        $existingPanel = $existingMeta['panel'] ?? [];
        $existingCredentials = $existingMeta['credentials'] ?? [];
        $existingKeywords = $existingMeta['keywords'] ?? [];
        $existingSearch = $existingMeta['search_console'] ?? [];
        $existingDns = $existingMeta['domain_dns'] ?? [];
        $meta = $existingMeta;
        $meta['domain']    = $domain ?: ($existingMeta['domain'] ?? '');
        $meta['host_user'] = $host_user ?: ($existingMeta['host_user'] ?? '');
        $meta['keywords']  = array_filter(array_map('trim', explode(',', $input['keywords'] ?? implode(',', $existingKeywords))));
        $meta['panel']     = array_merge($existingPanel, [
            'directadmin_username' => trim($input['da_username'] ?? ($existingPanel['directadmin_username'] ?? '')),
            'sync' => isset($input['da_sync']) ? 1 : ($existingPanel['sync'] ?? 0),
            'server_id' => (int)Str::normalizeDigits($input['server_id'] ?? ($existingPanel['server_id'] ?? '0')),
            'port' => (int)Str::normalizeDigits($input['da_port'] ?? ($existingPanel['port'] ?? '2222')),
            'ssl' => isset($input['da_ssl']) ? 1 : ($existingPanel['ssl'] ?? 0),
        ]);
        $meta['search_console'] = [
            'property' => trim($input['search_property'] ?? ($existingSearch['property'] ?? '')),
        ];
        $meta['domain_dns'] = [
            'ns1' => trim($input['ns1'] ?? ($existingDns['ns1'] ?? '')),
            'ns2' => trim($input['ns2'] ?? ($existingDns['ns2'] ?? '')),
            'ns3' => trim($input['ns3'] ?? ($existingDns['ns3'] ?? '')),
            'ns4' => trim($input['ns4'] ?? ($existingDns['ns4'] ?? '')),
            'ns5' => trim($input['ns5'] ?? ($existingDns['ns5'] ?? '')),
        ];
        $meta['credentials'] = [
            'username' => trim($input['site_username'] ?? ($existingCredentials['username'] ?? '')),
            'password' => trim($input['site_password'] ?? ($existingCredentials['password'] ?? '')),
        ];
        $meta['billing_notes'] = trim($input['billing_notes'] ?? ($existingMeta['billing_notes'] ?? ''));
        $meta['product_type'] = $productType ?: ($existingMeta['product_type'] ?? $categorySlug);
        $meta['category_slug'] = $categorySlug ?: ($existingMeta['category_slug'] ?? '');

        return $meta;
    }
}
