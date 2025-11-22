<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Str;
use App\Core\View;
use PDOException;

class ExpenseCategoriesController
{
    protected function ensureAuth(): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
    }

    private function ensureTable(): void
    {
        $pdo = Database::connection();
        $sql = "CREATE TABLE IF NOT EXISTS expense_categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(190) NOT NULL UNIQUE,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL
                ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $pdo->exec($sql);
    }

    public function index(): void
    {
        $this->ensureAuth();
        try {
            $this->ensureTable();
            $pdo = Database::connection();
            $rows = $pdo->query("SELECT * FROM expense_categories ORDER BY id DESC")->fetchAll();
            View::render('expense_categories/index', [
                'user'       => Auth::user(),
                'categories' => $rows,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در بارگذاری دسته‌بندی هزینه: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }
    }

    public function create(): void
    {
        $this->ensureAuth();
        $name = Str::beautifyLabel($_POST['name'] ?? '');
        if ($name === '') {
            header('Location: /expense-categories');
            return;
        }

        try {
            $this->ensureTable();
            $pdo = Database::connection();
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO expense_categories (name, created_at, updated_at) VALUES (?,?,?)");
            $stmt->execute([$name, $now, $now]);
        } catch (PDOException $e) {
            View::renderError('خطا در ثبت دسته‌بندی هزینه: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        header('Location: /expense-categories');
    }

    public function edit(): void
    {
        $this->ensureAuth();
        $id   = (int)Str::normalizeDigits($_POST['id'] ?? '0');
        $name = Str::beautifyLabel($_POST['name'] ?? '');
        if (!$id || $name === '') {
            header('Location: /expense-categories');
            return;
        }

        try {
            $this->ensureTable();
            $pdo = Database::connection();
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("UPDATE expense_categories SET name=?, updated_at=? WHERE id=?");
            $stmt->execute([$name, $now, $id]);
        } catch (PDOException $e) {
            View::renderError('خطا در بروزرسانی دسته‌بندی هزینه: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        header('Location: /expense-categories');
    }

    public function delete(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_GET['id'] ?? '0');
        if ($id) {
            try {
                $this->ensureTable();
                $pdo = Database::connection();
                $stmt = $pdo->prepare("DELETE FROM expense_categories WHERE id = ?");
                $stmt->execute([$id]);
            } catch (PDOException $e) {
                View::renderError('خطا در حذف دسته‌بندی هزینه: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
                return;
            }
        }
        header('Location: /expense-categories');
    }
}
