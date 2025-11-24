<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\View;
use App\Core\Database;
use App\Core\Date;
use App\Core\Str;
use PDOException;

class ContractsController
{
    private array $productCache = [];

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
            $customers = $pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
            $employees = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name")->fetchAll();
            $categories = $pdo->query("SELECT id, name FROM product_categories ORDER BY name")->fetchAll();
            $products = $pdo->query("SELECT id, name, type, billing_cycle, price FROM products ORDER BY type, name")->fetchAll();

            $sql = "SELECT c.*,
                           cust.name AS customer_name,
                           COALESCE(e.full_name, 'مدیریت') AS employee_name,
                           cat.name AS category_name,
                           COALESCE(SUM(li.sale_amount), 0) AS sale_total,
                           COALESCE(SUM(li.cost_amount), 0) AS cost_total
                    FROM contracts c
                    LEFT JOIN customers cust ON cust.id = c.customer_id
                    LEFT JOIN employees e ON e.id = c.sales_employee_id
                    LEFT JOIN product_categories cat ON cat.id = c.category_id
                    LEFT JOIN contract_line_items li ON li.contract_id = c.id
                    GROUP BY c.id, cust.name, e.full_name, cat.name
                    ORDER BY c.id DESC";
            $rows = $pdo->query($sql)->fetchAll();

            View::render('contracts/index', [
                'user'       => Auth::user(),
                'customers'  => $customers,
                'employees'  => $employees,
                'categories' => $categories,
                'products'   => $products,
                'contracts'  => $rows,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در بخش قراردادها: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }
    }

    public function create(): void
    {
        $this->ensureAuth();
        $title       = Str::beautifyLabel($_POST['title'] ?? '');
        $customer_id = (int)Str::normalizeDigits($_POST['customer_id'] ?? '0');
        $employee_id = (int)Str::normalizeDigits($_POST['employee_id'] ?? '0');
        $category_id = (int)Str::normalizeDigits($_POST['category_id'] ?? '0');
        $start_date  = Str::normalizeDigits(trim($_POST['start_date'] ?? ''));
        $status      = trim($_POST['status'] ?? 'active');
        $note        = trim($_POST['note'] ?? '');
        $items       = $this->parseLineItems($_POST);

        if ($title === '' || empty($items)) {
            header('Location: /contracts');
            return;
        }

        $saleTotal = array_sum(array_column($items, 'sale_amount'));
        $costTotal = array_sum(array_column($items, 'cost_amount'));

        try {
            $pdo = Database::connection();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO contracts
                (title, customer_id, sales_employee_id, category_id, total_amount, total_cost_amount, start_date, status, note, created_at, updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $now = date('Y-m-d H:i:s');
            $stmt->execute([
                $title,
                $customer_id ?: null,
                $employee_id ?: null,
                $category_id ?: null,
                $saleTotal,
                $costTotal,
                Date::fromJalaliInput($start_date),
                $status,
                $note,
                $now,
                $now,
            ]);
            $contractId = (int)$pdo->lastInsertId();

            foreach ($items as $item) {
                $serviceId = $this->createServiceInstance($pdo, $customer_id, $item, $status, $contractId);
                $this->createLineItem($pdo, $contractId, $serviceId, $item);
            }

            $pdo->commit();
        } catch (PDOException $e) {
            try { $pdo?->rollBack(); } catch (\Throwable $t) {}
            View::renderError('خطا در ثبت قرارداد: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /contracts');
    }

    public function edit(): void
    {
        $this->ensureAuth();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $id = (int)Str::normalizeDigits($_GET['id'] ?? '0');
            if (!$id) {
                header('Location: /contracts');
                return;
            }

            try {
                $pdo = Database::connection();
                $contractStmt = $pdo->prepare("SELECT * FROM contracts WHERE id = ?");
                $contractStmt->execute([$id]);
                $contract = $contractStmt->fetch();

                if (!$contract) {
                    header('Location: /contracts');
                    return;
                }

                $customers  = $pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
                $employees  = $pdo->query("SELECT id, full_name FROM employees ORDER BY full_name")->fetchAll();
                $categories = $pdo->query("SELECT id, name FROM product_categories ORDER BY name")->fetchAll();
                $products   = $pdo->query("SELECT id, name, type, billing_cycle, price FROM products ORDER BY type, name")->fetchAll();
                $itemsStmt  = $pdo->prepare("SELECT li.*, si.meta_json, si.status AS service_status
                                             FROM contract_line_items li
                                             LEFT JOIN service_instances si ON si.id = li.service_instance_id
                                             WHERE li.contract_id = ?
                                             ORDER BY li.id ASC");
                $itemsStmt->execute([$id]);
                $lineItems = [];
                foreach ($itemsStmt->fetchAll() as $row) {
                    $meta = json_decode($row['meta_json'] ?? '', true) ?: [];
                    $row['domain'] = $meta['domain'] ?? '';
                    $row['billing_notes'] = $meta['billing_notes'] ?? ($meta['notes'] ?? '');
                    $lineItems[] = $row;
                }

                View::render('contracts/edit', [
                    'user'       => Auth::user(),
                    'contract'   => $contract,
                    'customers'  => $customers,
                    'employees'  => $employees,
                    'categories' => $categories,
                    'products'   => $products,
                    'lineItems'  => $lineItems,
                ]);
                return;
            } catch (PDOException $e) {
                View::renderError('خطا در بارگذاری فرم ویرایش قرارداد: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
                return;
            }
        }

        $id          = (int)Str::normalizeDigits($_POST['id'] ?? '0');
        $title       = Str::beautifyLabel($_POST['title'] ?? '');
        $customer_id = (int)Str::normalizeDigits($_POST['customer_id'] ?? '0');
        $employee_id = (int)Str::normalizeDigits($_POST['employee_id'] ?? '0');
        $category_id = (int)Str::normalizeDigits($_POST['category_id'] ?? '0');
        $start_date  = Str::normalizeDigits(trim($_POST['start_date'] ?? ''));
        $status      = trim($_POST['status'] ?? 'active');
        $note        = trim($_POST['note'] ?? '');
        $items       = $this->parseLineItems($_POST);

        if (!$id || $title === '' || empty($items)) {
            header('Location: /contracts');
            return;
        }

        try {
            $pdo = Database::connection();
            $pdo->beginTransaction();
            $saleTotal = array_sum(array_column($items, 'sale_amount'));
            $costTotal = array_sum(array_column($items, 'cost_amount'));

            $stmt = $pdo->prepare("UPDATE contracts SET
                title = ?, customer_id = ?, sales_employee_id = ?, category_id = ?, total_amount = ?, total_cost_amount = ?, start_date = ?, status = ?, note = ?, updated_at = ?
                WHERE id = ?");
            $now = date('Y-m-d H:i:s');
            $stmt->execute([
                $title,
                $customer_id ?: null,
                $employee_id ?: null,
                $category_id ?: null,
                $saleTotal,
                $costTotal,
                Date::fromJalaliInput($start_date),
                $status,
                $note,
                $now,
                $id,
            ]);

            $existingStmt = $pdo->prepare("SELECT id, service_instance_id FROM contract_line_items WHERE contract_id = ?");
            $existingStmt->execute([$id]);
            $existing = [];
            foreach ($existingStmt->fetchAll() as $row) {
                $existing[(int)$row['id']] = (int)($row['service_instance_id'] ?? 0);
            }

            $kept = [];
            foreach ($items as $item) {
                $lineId = (int)($item['line_id'] ?? 0);
                $serviceId = (int)($item['service_id'] ?? 0);

                if ($lineId && isset($existing[$lineId])) {
                    $serviceId = $serviceId ?: $existing[$lineId];
                    if ($serviceId) {
                        $this->updateServiceInstance($pdo, $serviceId, $customer_id, $item, $status, $id);
                    } else {
                        $serviceId = $this->createServiceInstance($pdo, $customer_id, $item, $status, $id);
                    }
                    $this->updateLineItem($pdo, $lineId, $id, $serviceId, $item);
                    $kept[] = $lineId;
                    unset($existing[$lineId]);
                } else {
                    $serviceId = $this->createServiceInstance($pdo, $customer_id, $item, $status, $id);
                    $newLineId = $this->createLineItem($pdo, $id, $serviceId, $item);
                    $kept[] = $newLineId;
                }
            }

            if (!empty($existing)) {
                $idsToDelete = array_keys($existing);
                $in = implode(',', array_fill(0, count($idsToDelete), '?'));
                $deleteItems = $pdo->prepare("DELETE FROM contract_line_items WHERE contract_id = ? AND id IN ($in)");
                $deleteItems->execute(array_merge([$id], $idsToDelete));

                $serviceIds = array_values(array_filter($existing));
                if (!empty($serviceIds)) {
                    $inSrv = implode(',', array_fill(0, count($serviceIds), '?'));
                    $deleteSrv = $pdo->prepare("DELETE FROM service_instances WHERE contract_id = ? AND id IN ($inSrv)");
                    $deleteSrv->execute(array_merge([$id], $serviceIds));
                }
            }

            $pdo->commit();
        } catch (PDOException $e) {
            try { $pdo?->rollBack(); } catch (\Throwable $t) {}
            View::renderError('خطا در بروزرسانی قرارداد: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        header('Location: /contracts');
    }

    public function delete(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_GET['id'] ?? '0');
        if ($id) {
            try {
                $pdo = Database::connection();
                $pdo->beginTransaction();

                $svcStmt = $pdo->prepare("SELECT service_instance_id FROM contract_line_items WHERE contract_id = ? AND service_instance_id IS NOT NULL");
                $svcStmt->execute([$id]);
                $svcIds = array_map('intval', array_column($svcStmt->fetchAll(), 'service_instance_id'));

                $pdo->prepare("DELETE FROM contract_line_items WHERE contract_id = ?")
                    ->execute([$id]);

                if (!empty($svcIds)) {
                    $in = implode(',', array_fill(0, count($svcIds), '?'));
                    $pdo->prepare("DELETE FROM service_instances WHERE id IN ($in) OR contract_id = ?")
                        ->execute(array_merge($svcIds, [$id]));
                } else {
                    $pdo->prepare("DELETE FROM service_instances WHERE contract_id = ?")
                        ->execute([$id]);
                }

                $stmt = $pdo->prepare("DELETE FROM contracts WHERE id = ?");
                $stmt->execute([$id]);
                $pdo->commit();
            } catch (PDOException $e) {
                try { $pdo?->rollBack(); } catch (\Throwable $t) {}
                View::renderError('خطا در حذف قرارداد: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            }
        }
        header('Location: /contracts');
    }

    private function parseLineItems(array $input): array
    {
        $items = [];
        $products    = $input['item_product_id'] ?? [];
        $titles      = $input['item_title'] ?? [];
        $categories  = $input['item_category_id'] ?? [];
        $sales       = $input['item_sale_amount'] ?? [];
        $costs       = $input['item_cost_amount'] ?? [];
        $cycles      = $input['item_billing_cycle'] ?? [];
        $startDates  = $input['item_start_date'] ?? [];
        $dueDates    = $input['item_next_due_date'] ?? [];
        $domains     = $input['item_domain'] ?? [];
        $notes       = $input['item_notes'] ?? [];
        $ids         = $input['item_id'] ?? [];
        $serviceIds  = $input['item_service_id'] ?? [];

        foreach ($products as $idx => $pid) {
            $productId  = (int)Str::normalizeDigits((string)$pid);
            $title      = Str::beautifyLabel($titles[$idx] ?? '');
            $sale       = (int)Str::normalizeDigits((string)($sales[$idx] ?? '0'));
            $cost       = (int)Str::normalizeDigits((string)($costs[$idx] ?? '0'));
            $cycle      = trim($cycles[$idx] ?? '');
            $startDate  = Str::normalizeDigits(trim($startDates[$idx] ?? ''));
            $dueDate    = Str::normalizeDigits(trim($dueDates[$idx] ?? ''));
            $domain     = trim($domains[$idx] ?? '');
            $itemNote   = trim($notes[$idx] ?? '');

            if (!$productId && $title === '' && $sale === 0 && $cost === 0 && $domain === '') {
                continue;
            }

            $items[] = [
                'line_id'     => (int)Str::normalizeDigits((string)($ids[$idx] ?? '0')),
                'service_id'  => (int)Str::normalizeDigits((string)($serviceIds[$idx] ?? '0')),
                'product_id'  => $productId ?: null,
                'category_id' => (int)Str::normalizeDigits((string)($categories[$idx] ?? '0')) ?: null,
                'title'       => $title,
                'billing_cycle' => $cycle ?: null,
                'sale_amount' => $sale,
                'cost_amount' => $cost,
                'start_date'  => Date::fromJalaliInput($startDate),
                'next_due_date' => Date::fromJalaliInput($dueDate),
                'meta'        => [
                    'domain'        => $domain,
                    'billing_notes' => $itemNote,
                ],
            ];
        }

        return $items;
    }

    private function createServiceInstance($pdo, int $customerId, array $item, string $status, int $contractId): int
    {
        $now = date('Y-m-d H:i:s');
        $meta = [
            'domain'        => $item['meta']['domain'] ?? '',
            'billing_notes' => $item['meta']['billing_notes'] ?? '',
        ];

        $stmt = $pdo->prepare("INSERT INTO service_instances
            (customer_id, product_id, category_id, contract_id, status, start_date, next_due_date, access_granted, billing_cycle, sale_amount, cost_amount, meta_json, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $customerId,
            $item['product_id'] ?: 0,
            $item['category_id'] ?: null,
            $contractId,
            $status,
            $item['start_date'] ?: null,
            $item['next_due_date'] ?: null,
            0,
            $item['billing_cycle'] ?: null,
            $item['sale_amount'],
            $item['cost_amount'],
            json_encode($meta, JSON_UNESCAPED_UNICODE),
            $now,
            $now,
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function updateServiceInstance($pdo, int $serviceId, int $customerId, array $item, string $status, int $contractId): void
    {
        $meta = [
            'domain'        => $item['meta']['domain'] ?? '',
            'billing_notes' => $item['meta']['billing_notes'] ?? '',
        ];
        $now = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("UPDATE service_instances SET
                customer_id=?, product_id=?, category_id=?, contract_id=?, status=?, start_date=?, next_due_date=?, billing_cycle=?, sale_amount=?, cost_amount=?, meta_json=?, updated_at=?
                WHERE id=?");
        $stmt->execute([
            $customerId,
            $item['product_id'] ?: 0,
            $item['category_id'] ?: null,
            $contractId,
            $status,
            $item['start_date'] ?: null,
            $item['next_due_date'] ?: null,
            $item['billing_cycle'] ?: null,
            $item['sale_amount'],
            $item['cost_amount'],
            json_encode($meta, JSON_UNESCAPED_UNICODE),
            $now,
            $serviceId,
        ]);
    }

    private function createLineItem($pdo, int $contractId, int $serviceId, array $item): int
    {
        $now = date('Y-m-d H:i:s');
        $title = $this->resolveTitle($pdo, $item);

        $stmt = $pdo->prepare("INSERT INTO contract_line_items
            (contract_id, service_instance_id, product_id, category_id, title, billing_cycle, sale_amount, cost_amount, start_date, next_due_date, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $contractId,
            $serviceId ?: null,
            $item['product_id'] ?: null,
            $item['category_id'] ?: null,
            $title,
            $item['billing_cycle'] ?: null,
            $item['sale_amount'],
            $item['cost_amount'],
            $item['start_date'] ?: null,
            $item['next_due_date'] ?: null,
            $now,
            $now,
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function updateLineItem($pdo, int $lineId, int $contractId, int $serviceId, array $item): void
    {
        $title = $this->resolveTitle($pdo, $item);
        $now = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("UPDATE contract_line_items SET
                service_instance_id = ?, product_id = ?, category_id = ?, title = ?, billing_cycle = ?, sale_amount = ?, cost_amount = ?, start_date = ?, next_due_date = ?, updated_at = ?
                WHERE id = ? AND contract_id = ?");
        $stmt->execute([
            $serviceId ?: null,
            $item['product_id'] ?: null,
            $item['category_id'] ?: null,
            $title,
            $item['billing_cycle'] ?: null,
            $item['sale_amount'],
            $item['cost_amount'],
            $item['start_date'] ?: null,
            $item['next_due_date'] ?: null,
            $now,
            $lineId,
            $contractId,
        ]);
    }

    private function resolveTitle($pdo, array $item): string
    {
        if (!empty($item['title'])) {
            return $item['title'];
        }

        if (!empty($item['product_id'])) {
            $name = $this->loadProductName($pdo, (int)$item['product_id']);
            if ($name !== '') {
                return $name;
            }
        }

        return 'سرویس';
    }

    private function loadProductName($pdo, int $productId): string
    {
        if (!$productId) {
            return '';
        }

        if (!array_key_exists($productId, $this->productCache)) {
            $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $this->productCache[$productId] = (string)($stmt->fetchColumn() ?: '');
        }

        return $this->productCache[$productId];
    }
}
