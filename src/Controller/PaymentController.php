<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\View;
use App\Core\Database;

class PaymentController
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
        $sql = "SELECT p.*, c.title AS contract_title, c.customer_name
                FROM payments p
                LEFT JOIN contracts c ON c.id = p.contract_id
                ORDER BY p.paid_at DESC";
        $rows = $pdo->query($sql)->fetchAll();

        View::render('payments/index', [
            'user'     => Auth::user(),
            'payments' => $rows,
        ]);
    }

    public function createForm(): void
    {
        $this->ensureAuth();
        $pdo = Database::connection();
        $contracts = $pdo->query("SELECT id, title, customer_name FROM contracts ORDER BY id DESC")->fetchAll();

        View::render('payments/create', [
            'user'      => Auth::user(),
            'contracts' => $contracts,
            'error'     => null,
        ]);
    }

    public function create(): void
    {
        $this->ensureAuth();
        $pdo = Database::connection();

        $contract_id   = (int)($_POST['contract_id'] ?? 0);
        $amount        = (int)($_POST['amount'] ?? 0);
        $paid_at       = trim($_POST['paid_at'] ?? '');
        $method        = trim($_POST['method'] ?? '');
        $status        = $_POST['status'] ?? 'paid';
        $external_src  = $_POST['external_source'] ?? 'manual';
        $external_ref  = trim($_POST['external_ref'] ?? '');

        if ($amount <= 0 || $paid_at === '') {
            $contracts = $pdo->query("SELECT id, title, customer_name FROM contracts ORDER BY id DESC")->fetchAll();
            View::render('payments/create', [
                'user'      => Auth::user(),
                'contracts' => $contracts,
                'error'     => 'مبلغ و تاریخ پرداخت الزامی است.',
            ]);
            return;
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO payments 
            (contract_id, amount, paid_at, method, status, external_source, external_ref, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $contract_id ?: null,
            $amount,
            $paid_at,
            $method ?: null,
            $status,
            $external_src,
            $external_ref ?: null,
            $now,
            $now,
        ]);

        header('Location: /payments');
    }
}
