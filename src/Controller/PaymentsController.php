<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\View;
use App\Core\Database;
use App\Core\Date;
use App\Core\Str;
use PDOException;

class PaymentsController
{
    protected function ensureAuth(): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            return;
        }
    }

    public function index(): void
    {
        $this->ensureAuth();
        try {
            $pdo = Database::connection();
            $contracts = $pdo->query("SELECT id, title FROM contracts ORDER BY id DESC")->fetchAll();
            $sql = "SELECT p.*, c.title AS contract_title 
                    FROM payments p
                    LEFT JOIN contracts c ON c.id = p.contract_id
                    ORDER BY p.id DESC";
            $rows = $pdo->query($sql)->fetchAll();

            View::render('payments/index', [
                'user'      => Auth::user(),
                'payments'  => $rows,
                'contracts' => $contracts,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در بخش پرداخت‌ها: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }
    }

    public function create(): void
    {
        $this->ensureAuth();
        $contract_id = (int)Str::normalizeDigits($_POST['contract_id'] ?? '0');
        $amount      = (int)Str::normalizeDigits($_POST['amount'] ?? '0');
        $pay_date    = Str::normalizeDigits(trim($_POST['pay_date'] ?? ''));
        $method      = trim($_POST['method'] ?? '');
        $status      = trim($_POST['status'] ?? 'paid');
        $note        = trim($_POST['note'] ?? '');

        $payDate = Date::fromJalaliInput($pay_date);
        $paidAt  = $payDate ? ($payDate . ' 00:00:00') : date('Y-m-d H:i:s');

        $allowedStatuses = ['paid', 'pending', 'refunded'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'paid';
        }

        if ($amount <= 0) {
            header('Location: /payments');
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare("INSERT INTO payments (contract_id, amount, pay_date, paid_at, method, status, note, external_source, external_ref, created_at, updated_at)
                                   VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $now = date('Y-m-d H:i:s');
            $stmt->execute([
                $contract_id ?: null,
                $amount,
                $payDate,
                $paidAt,
                $method,
                $status,
                $note,
                'manual',
                null,
                $now,
                $now,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در ثبت پرداخت: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /payments');
    }

    public function edit(): void
    {
        $this->ensureAuth();
        $id          = (int)Str::normalizeDigits($_POST['id'] ?? '0');
        $contract_id = (int)Str::normalizeDigits($_POST['contract_id'] ?? '0');
        $amount      = (int)Str::normalizeDigits($_POST['amount'] ?? '0');
        $pay_date    = Str::normalizeDigits(trim($_POST['pay_date'] ?? ''));
        $method      = trim($_POST['method'] ?? '');
        $status      = trim($_POST['status'] ?? 'paid');
        $note        = trim($_POST['note'] ?? '');

        $payDate = Date::fromJalaliInput($pay_date);
        $paidAt  = $payDate ? ($payDate . ' 00:00:00') : date('Y-m-d H:i:s');

        $allowedStatuses = ['paid', 'pending', 'refunded'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'paid';
        }

        if (!$id) {
            header('Location: /payments');
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare("UPDATE payments SET contract_id=?, amount=?, pay_date=?, paid_at=?, method=?, status=?, note=?, updated_at=? WHERE id=?");
            $now = date('Y-m-d H:i:s');
            $stmt->execute([
                $contract_id ?: null,
                $amount,
                $payDate,
                $paidAt,
                $method,
                $status,
                $note,
                $now,
                $id,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در بروزرسانی پرداخت: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /payments');
    }

    public function delete(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_GET['id'] ?? '0');
        if ($id) {
            try {
                $pdo = Database::connection();
                $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
                $stmt->execute([$id]);
            } catch (PDOException $e) {
                View::renderError('خطا در حذف پرداخت: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            }
        }
        header('Location: /payments');
    }
}
