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

            $sql = "SELECT c.*, 
                           cust.name AS customer_name,
                           e.full_name AS employee_name,
                           cat.name AS category_name
                    FROM contracts c
                    LEFT JOIN customers cust ON cust.id = c.customer_id
                    LEFT JOIN employees e ON e.id = c.sales_employee_id
                    LEFT JOIN product_categories cat ON cat.id = c.category_id
                    ORDER BY c.id DESC";
            $rows = $pdo->query($sql)->fetchAll();

            View::render('contracts/index', [
                'user'       => Auth::user(),
                'customers'  => $customers,
                'employees'  => $employees,
                'categories' => $categories,
                'contracts'  => $rows,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در بخش قراردادها: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }
    }

    public function create(): void
    {
        $this->ensureAuth();
        $title       = trim($_POST['title'] ?? '');
        $customer_id = (int)Str::normalizeDigits($_POST['customer_id'] ?? '0');
        $employee_id = (int)Str::normalizeDigits($_POST['employee_id'] ?? '0');
        $category_id = (int)Str::normalizeDigits($_POST['category_id'] ?? '0');
        $amount      = (int)Str::normalizeDigits($_POST['total_amount'] ?? '0');
        $start_date  = Str::normalizeDigits(trim($_POST['start_date'] ?? ''));
        $status      = trim($_POST['status'] ?? 'active');
        $note        = trim($_POST['note'] ?? '');

        if ($title === '') {
            header('Location: /contracts');
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare("INSERT INTO contracts 
                (title, customer_id, sales_employee_id, category_id, total_amount, start_date, status, note, created_at, updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,?)");
            $now = date('Y-m-d H:i:s');
            $stmt->execute([
                $title,
                $customer_id ?: null,
                $employee_id ?: null,
                $category_id ?: null,
                $amount,
                Date::fromJalaliInput($start_date),
                $status,
                $note,
                $now,
                $now,
            ]);
        } catch (PDOException $e) {
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

                View::render('contracts/edit', [
                    'user'       => Auth::user(),
                    'contract'   => $contract,
                    'customers'  => $customers,
                    'employees'  => $employees,
                    'categories' => $categories,
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
        $amount      = (int)Str::normalizeDigits($_POST['total_amount'] ?? '0');
        $start_date  = Str::normalizeDigits(trim($_POST['start_date'] ?? ''));
        $status      = trim($_POST['status'] ?? 'active');
        $note        = trim($_POST['note'] ?? '');

        if (!$id || $title === '') {
            header('Location: /contracts');
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare("UPDATE contracts SET
                title = ?, customer_id = ?, sales_employee_id = ?, category_id = ?, total_amount = ?, start_date = ?, status = ?, note = ?, updated_at = ?
                WHERE id = ?");
            $now = date('Y-m-d H:i:s');
            $stmt->execute([
                $title,
                $customer_id ?: null,
                $employee_id ?: null,
                $category_id ?: null,
                $amount,
                Date::fromJalaliInput($start_date),
                $status,
                $note,
                $now,
                $id,
            ]);
        } catch (PDOException $e) {
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
                $stmt = $pdo->prepare("DELETE FROM contracts WHERE id = ?");
                $stmt->execute([$id]);
            } catch (PDOException $e) {
                View::renderError('خطا در حذف قرارداد: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            }
        }
        header('Location: /contracts');
    }
}
