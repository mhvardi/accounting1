<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\View;
use App\Core\Database;

class ContractController
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
        $pdo = Database::connection();
        $sql = "SELECT c.*, e.full_name AS sales_name, pc.name AS category_name
                FROM contracts c
                LEFT JOIN employees e ON e.id = c.sales_employee_id
                LEFT JOIN product_categories pc ON pc.id = c.category_id
                ORDER BY c.id DESC";
        $rows = $pdo->query($sql)->fetchAll();

        $employees = $pdo->query("SELECT id, full_name FROM employees WHERE active = 1 ORDER BY full_name")->fetchAll();
        $categories = $pdo->query("SELECT id, name FROM product_categories ORDER BY name")->fetchAll();

        View::render('contracts/index', [
            'user'       => Auth::user(),
            'contracts'  => $rows,
            'employees'  => $employees,
            'categories' => $categories,
        ]);
    }

    public function createForm(): void
    {
        $this->ensureAuth();
        $pdo = Database::connection();
        $employees = $pdo->query("SELECT id, full_name FROM employees WHERE active = 1 ORDER BY full_name")->fetchAll();
        $categories = $pdo->query("SELECT id, name FROM product_categories ORDER BY name")->fetchAll();

        View::render('contracts/create', [
            'user'       => Auth::user(),
            'error'      => null,
            'employees'  => $employees,
            'categories' => $categories,
        ]);
    }

    public function create(): void
    {
        $this->ensureAuth();
        $pdo = Database::connection();

        $customer_name     = trim($_POST['customer_name'] ?? '');
        $category_id       = (int)($_POST['category_id'] ?? 0);
        $title             = trim($_POST['title'] ?? '');
        $total_amount      = (int)($_POST['total_amount'] ?? 0);
        $start_date        = trim($_POST['start_date'] ?? '');
        $sales_employee_id = (int)($_POST['sales_employee_id'] ?? 0);
        $notes             = trim($_POST['notes'] ?? '');

        if ($customer_name === '' || $category_id === 0 || $title === '' || $total_amount <= 0 || $start_date === '') {
            $employees  = $pdo->query("SELECT id, full_name FROM employees WHERE active = 1 ORDER BY full_name")->fetchAll();
            $categories = $pdo->query("SELECT id, name FROM product_categories ORDER BY name")->fetchAll();
            View::render('contracts/create', [
                'user'       => Auth::user(),
                'error'      => 'همه فیلدهای الزامی را تکمیل کنید.',
                'employees'  => $employees,
                'categories' => $categories,
            ]);
            return;
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO contracts 
            (customer_name, category_id, title, total_amount, start_date, sales_employee_id, notes, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $customer_name,
            $category_id,
            $title,
            $total_amount,
            $start_date,
            $sales_employee_id ?: null,
            $notes ?: null,
            $now,
            $now,
        ]);

        header('Location: /contracts');
    }
}
