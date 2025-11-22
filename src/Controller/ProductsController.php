<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\View;
use App\Core\Database;
use App\Core\Date;
use App\Core\Str;
use PDOException;

class ProductsController
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
            $products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
        } catch (PDOException $e) {
            View::renderError('خطا در بارگذاری محصولات: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        View::render('products/index', [
            'user' => Auth::user(),
            'products' => $products,
            'yearOptions' => Date::financialYears(),
        ]);
    }

    public function store(): void
    {
        $this->ensureAuth();
        $name = Str::beautifyLabel($_POST['name'] ?? '');
        $type = $_POST['type'] ?? 'service';
        $cycle = $_POST['billing_cycle'] ?? 'monthly';
        $price = (int)Str::normalizeDigits($_POST['price'] ?? '0');
        $meta = [
            'description' => trim($_POST['description'] ?? ''),
            'directadmin' => [
                'sync' => isset($_POST['da_sync']) ? 1 : 0,
            ],
            'domain' => [
                'includes_dns' => isset($_POST['domain_dns']) ? 1 : 0,
            ],
        ];

        if ($name === '') {
            header('Location: /products');
            return;
        }

        try {
            $pdo = Database::connection();
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO products (name, type, billing_cycle, price, meta_json, created_at, updated_at) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$name, $type, $cycle, $price, json_encode($meta, JSON_UNESCAPED_UNICODE), $now, $now]);
        } catch (PDOException $e) {
            View::renderError('خطا در ایجاد محصول: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        header('Location: /products');
    }

    public function update(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_POST['id'] ?? '0');
        $name = Str::beautifyLabel($_POST['name'] ?? '');
        $type = $_POST['type'] ?? 'service';
        $cycle = $_POST['billing_cycle'] ?? 'monthly';
        $price = (int)Str::normalizeDigits($_POST['price'] ?? '0');
        $meta = [
            'description' => trim($_POST['description'] ?? ''),
            'directadmin' => [
                'sync' => isset($_POST['da_sync']) ? 1 : 0,
            ],
            'domain' => [
                'includes_dns' => isset($_POST['domain_dns']) ? 1 : 0,
            ],
        ];

        if (!$id || $name === '') {
            header('Location: /products');
            return;
        }

        try {
            $pdo = Database::connection();
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("UPDATE products SET name=?, type=?, billing_cycle=?, price=?, meta_json=?, updated_at=? WHERE id=?");
            $stmt->execute([$name, $type, $cycle, $price, json_encode($meta, JSON_UNESCAPED_UNICODE), $now, $id]);
        } catch (PDOException $e) {
            View::renderError('خطا در بروزرسانی محصول: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        header('Location: /products');
    }

    public function delete(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_GET['id'] ?? '0');
        if (!$id) {
            header('Location: /products');
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            View::renderError('خطا در حذف محصول: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        header('Location: /products');
    }
}
