<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\View;
use App\Core\Database;
use App\Core\Str;
use App\Core\Date;
use PDOException;

class CustomerController
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
            $rows = $pdo->query("SELECT * FROM customers ORDER BY id DESC")->fetchAll();
            View::render('customers/index', [
                'user'      => Auth::user(),
                'customers' => $rows,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در بارگذاری لیست مشتریان: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }
    }

    public function create(): void
    {
        $this->ensureAuth();
        $name  = Str::beautifyLabel($_POST['name'] ?? '');
        $phone = Str::digitsOnly(trim($_POST['phone'] ?? ''));
        $email = trim($_POST['email'] ?? '');
        $note  = trim($_POST['note'] ?? '');

        if ($name === '') {
            header('Location: /customers');
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, note, created_at, updated_at) VALUES (?,?,?,?,?,?)");
            $now = date('Y-m-d H:i:s');
            $stmt->execute([$name, $phone, $email, $note, $now, $now]);
        } catch (PDOException $e) {
            View::renderError('خطا در ثبت مشتری: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /customers');
    }

    public function edit(): void
    {
        $this->ensureAuth();
        $id    = (int)($_POST['id'] ?? 0);
        $name  = Str::beautifyLabel($_POST['name'] ?? '');
        $phone = Str::digitsOnly(trim($_POST['phone'] ?? ''));
        $email = trim($_POST['email'] ?? '');
        $note  = trim($_POST['note'] ?? '');

        if (!$id || $name === '') {
            header('Location: /customers');
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare("UPDATE customers SET name=?, phone=?, email=?, note=?, updated_at=? WHERE id=?");
            $now = date('Y-m-d H:i:s');
            $stmt->execute([$name, $phone, $email, $note, $now, $id]);
        } catch (PDOException $e) {
            View::renderError('خطا در بروزرسانی مشتری: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /customers');
    }

    public function delete(): void
    {
        $this->ensureAuth();
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            try {
                $pdo = Database::connection();
                $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
                $stmt->execute([$id]);
            } catch (PDOException $e) {
                View::renderError('خطا در حذف مشتری: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            }
        }
        header('Location: /customers');
    }

    public function profile(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_GET['id'] ?? '0');
        if (!$id) {
            header('Location: /customers');
            return;
        }

        try {
            $pdo = Database::connection();
            $services = [];
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$id]);
            $customer = $stmt->fetch();
            if (!$customer) {
                header('Location: /customers');
                return;
            }

            $contractsStmt = $pdo->prepare("SELECT c.*, e.full_name AS employee_name, cat.name AS category_name
                                            FROM contracts c
                                            LEFT JOIN employees e ON e.id = c.sales_employee_id
                                            LEFT JOIN product_categories cat ON cat.id = c.category_id
                                            WHERE c.customer_id = ?
                                            ORDER BY c.id DESC");
            $contractsStmt->execute([$id]);
            $contracts = $contractsStmt->fetchAll();

            $stmtTotal = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM contracts WHERE customer_id = ?");
            $stmtTotal->execute([$id]);
            $contractTotal = (int)$stmtTotal->fetchColumn();

            $stmtPaid = $pdo->prepare("SELECT COALESCE(SUM(p.amount),0)
                                        FROM payments p
                                        LEFT JOIN contracts c ON c.id = p.contract_id
                                        WHERE p.status = 'paid' AND (p.customer_id = ? OR c.customer_id = ?)");
            $stmtPaid->execute([$id, $id]);
            $paidTotal = (int)$stmtPaid->fetchColumn();

            $dueTotal = $contractTotal - $paidTotal;

            $paymentsStmt = $pdo->prepare("SELECT p.*, c.title AS contract_title
                                           FROM payments p
                                           LEFT JOIN contracts c ON c.id = p.contract_id
                                           WHERE p.customer_id = ? OR c.customer_id = ?
                                           ORDER BY p.id DESC
                                           LIMIT 20");
            $paymentsStmt->execute([$id, $id]);
            $payments = $paymentsStmt->fetchAll();

            $servicesStmt = $pdo->prepare("SELECT s.*, p.name AS product_name, p.type AS product_type
                                           FROM service_instances s
                                           LEFT JOIN products p ON p.id = s.product_id
                                           WHERE s.customer_id = ?
                                           ORDER BY s.id DESC");
            $servicesStmt->execute([$id]);
            $services = $servicesStmt->fetchAll();

            $servers = $pdo->query("SELECT id, name, hostname FROM servers")->fetchAll();
            $serversMap = [];
            foreach ($servers as $srv) {
                $serversMap[$srv['id']] = $srv;
            }

        } catch (PDOException $e) {
            View::renderError('خطا در بارگذاری پروفایل مشتری: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        View::render('customers/profile', [
            'user'          => Auth::user(),
            'customer'      => $customer,
            'contracts'     => $contracts,
            'contractTotal' => $contractTotal,
            'paidTotal'     => $paidTotal,
            'dueTotal'      => $dueTotal,
            'payments'      => $payments,
            'services'      => $services,
            'serversMap'    => $serversMap ?? [],
        ]);
    }
}
