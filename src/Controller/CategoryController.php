<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\View;
use App\Core\Database;
use App\Core\Str;
use PDOException;

class CategoryController
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
            $rows = $pdo->query("SELECT * FROM product_categories ORDER BY id DESC")->fetchAll();
            View::render('categories/index', [
                'user'       => Auth::user(),
                'categories' => $rows,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در بارگذاری دسته‌بندی‌ها: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }
    }

    public function create(): void
    {
        $this->ensureAuth();
        $name = Str::beautifyLabel($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $is_primary = isset($_POST['is_primary']) ? 1 : 0;

        if ($name === '') {
            $this->index();
            return;
        }

        if ($slug === '') {
            $slug = preg_replace('/\s+/', '-', $name);
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare("INSERT INTO product_categories (name, slug, is_primary, created_at, updated_at) VALUES (?,?,?,?,?)");
            $now = date('Y-m-d H:i:s');
            $stmt->execute([$name, $slug, $is_primary, $now, $now]);
        } catch (PDOException $e) {
            View::renderError('خطا در ثبت دسته‌بندی: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /categories');
    }

    public function edit(): void
    {
        $this->ensureAuth();
        $id   = (int)($_POST['id'] ?? 0);
        $name = Str::beautifyLabel($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $is_primary = isset($_POST['is_primary']) ? 1 : 0;

        if (!$id || $name === '') {
            header('Location: /categories');
            return;
        }
        if ($slug === '') {
            $slug = preg_replace('/\s+/', '-', $name);
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare("UPDATE product_categories SET name=?, slug=?, is_primary=?, updated_at=? WHERE id = ?");
            $now = date('Y-m-d H:i:s');
            $stmt->execute([$name, $slug, $is_primary, $now, $id]);
        } catch (PDOException $e) {
            View::renderError('خطا در بروزرسانی دسته‌بندی: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /categories');
    }

    public function delete(): void
    {
        $this->ensureAuth();
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            try {
                $pdo = Database::connection();
                $stmt = $pdo->prepare("DELETE FROM product_categories WHERE id = ?");
                $stmt->execute([$id]);
            } catch (PDOException $e) {
                View::renderError('خطا در حذف دسته‌بندی: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            }
        }
        header('Location: /categories');
    }
}
