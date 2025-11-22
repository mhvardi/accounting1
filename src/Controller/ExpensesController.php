<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\View;
use App\Core\Database;
use App\Core\Date;
use App\Core\Str;
use PDOException;

class ExpensesController
{
    private function ensureCategoryTable(): void
    {
        $pdo = Database::connection();
        $pdo->exec("CREATE TABLE IF NOT EXISTS expense_categories (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(190) NOT NULL UNIQUE,
                        created_at DATETIME NULL,
                        updated_at DATETIME NULL
                    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

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
            $this->ensureCategoryTable();
            $customers = $pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
            $categories = $pdo->query("SELECT * FROM expense_categories ORDER BY name")->fetchAll();
            $sql = "SELECT e.*, c.name AS customer_name
                    FROM expenses e
                    LEFT JOIN customers c ON c.id = e.customer_id
                    ORDER BY e.id DESC";
            $rows = $pdo->query($sql)->fetchAll();

            View::render('expenses/index', [
                'user'      => Auth::user(),
                'expenses'  => $rows,
                'customers' => $customers,
                'categories'=> $categories,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در بخش هزینه‌ها: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }
    }

    public function create(): void
    {
        $this->ensureAuth();
        $categoryId = (int)Str::normalizeDigits($_POST['category_id'] ?? '0');
        $category   = trim($_POST['category'] ?? '');
        $amount     = (int)Str::normalizeDigits($_POST['amount'] ?? '0');
        $expenseDate= Str::normalizeDigits(trim($_POST['expense_date'] ?? ''));
        $customerId = (int)Str::normalizeDigits($_POST['customer_id'] ?? '0');
        $note       = trim($_POST['note'] ?? '');

        if ($amount <= 0 || ($category === '' && $categoryId === 0)) {
            header('Location: /expenses');
            return;
        }

        try {
            $pdo = Database::connection();
            $this->ensureCategoryTable();
            if ($categoryId) {
                $stmt = $pdo->prepare("SELECT name FROM expense_categories WHERE id = ?");
                $stmt->execute([$categoryId]);
                $found = $stmt->fetchColumn();
                if ($found) {
                    $category = $found;
                }
            }
            $stmt = $pdo->prepare("INSERT INTO expenses (category, amount, expense_date, customer_id, note, created_at, updated_at)
                                   VALUES (?,?,?,?,?,?,?)");
            $now = date('Y-m-d H:i:s');
            $stmt->execute([
                $category,
                $amount,
                Date::fromJalaliInput($expenseDate),
                $customerId ?: null,
                $note,
                $now,
                $now,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در ثبت هزینه: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /expenses');
    }

    public function edit(): void
    {
        $this->ensureAuth();
        $id         = (int)Str::normalizeDigits($_POST['id'] ?? '0');
        $categoryId = (int)Str::normalizeDigits($_POST['category_id'] ?? '0');
        $category   = trim($_POST['category'] ?? '');
        $amount     = (int)Str::normalizeDigits($_POST['amount'] ?? '0');
        $expenseDate= Str::normalizeDigits(trim($_POST['expense_date'] ?? ''));
        $customerId = (int)Str::normalizeDigits($_POST['customer_id'] ?? '0');
        $note       = trim($_POST['note'] ?? '');

        if (!$id) {
            header('Location: /expenses');
            return;
        }

        try {
            $pdo = Database::connection();
            $this->ensureCategoryTable();
            if ($categoryId) {
                $stmt = $pdo->prepare("SELECT name FROM expense_categories WHERE id = ?");
                $stmt->execute([$categoryId]);
                $found = $stmt->fetchColumn();
                if ($found) {
                    $category = $found;
                }
            }
            $stmt = $pdo->prepare("UPDATE expenses SET category=?, amount=?, expense_date=?, customer_id=?, note=?, updated_at=? WHERE id=?");
            $now = date('Y-m-d H:i:s');
            $stmt->execute([
                $category,
                $amount,
                Date::fromJalaliInput($expenseDate),
                $customerId ?: null,
                $note,
                $now,
                $id,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در بروزرسانی هزینه: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /expenses');
    }

    public function delete(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_GET['id'] ?? '0');
        if ($id) {
            try {
                $pdo = Database::connection();
                $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
                $stmt->execute([$id]);
            } catch (PDOException $e) {
                View::renderError('خطا در حذف هزینه: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            }
        }
        header('Location: /expenses');
    }
}
