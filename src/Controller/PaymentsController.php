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
            $contracts = $pdo->query("SELECT id, title, total_amount, customer_id FROM contracts ORDER BY id DESC")->fetchAll();
            $customers = $pdo->query("SELECT id, name FROM customers ORDER BY id DESC")->fetchAll();
            $invoices  = $pdo->query("SELECT id, indicator_code, title, customer_id, contract_id, payable_amount FROM invoices ORDER BY id DESC")->fetchAll();
            $sql = "SELECT p.*, c.title AS contract_title, cust.name AS customer_name, inv.indicator_code AS invoice_code
                    FROM payments p
                    LEFT JOIN contracts c ON c.id = p.contract_id
                    LEFT JOIN customers cust ON cust.id = p.customer_id
                    LEFT JOIN invoices inv ON inv.id = p.invoice_id
                    ORDER BY p.id DESC";
            $rows = $pdo->query($sql)->fetchAll();

            View::render('payments/index', [
                'user'      => Auth::user(),
                'payments'  => $rows,
                'contracts' => $contracts,
                'customers' => $customers,
                'invoices'  => $invoices,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در بخش پرداخت‌ها: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }
    }

    public function create(): void
    {
        $this->ensureAuth();
        $contract_id = (int)Str::normalizeDigits($_POST['contract_id'] ?? '0');
        $invoice_id  = (int)Str::normalizeDigits($_POST['invoice_id'] ?? '0');
        $customer_id = (int)Str::normalizeDigits($_POST['customer_id'] ?? '0');
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
            $contractAmount = null;
            if ($invoice_id) {
                $stmtInvoice = $pdo->prepare("SELECT id, customer_id, contract_id, payable_amount FROM invoices WHERE id = ?");
                $stmtInvoice->execute([$invoice_id]);
                $invoiceRow = $stmtInvoice->fetch();
                if ($invoiceRow) {
                    $contract_id = (int)($invoiceRow['contract_id'] ?? 0);
                    $customer_id = (int)($invoiceRow['customer_id'] ?? 0);
                    $contractAmount = (int)($invoiceRow['payable_amount'] ?? 0);
                } else {
                    $invoice_id = 0;
                }
            }
            if ($contract_id) {
                $stmtContract = $pdo->prepare("SELECT id, customer_id, total_amount FROM contracts WHERE id = ?");
                $stmtContract->execute([$contract_id]);
                $contractRow = $stmtContract->fetch();
                if ($contractRow) {
                    $customer_id    = (int)($contractRow['customer_id'] ?? 0);
                    $contractAmount = (int)($contractRow['total_amount'] ?? 0);
                } else {
                    $contract_id = 0;
                }
            }

            $stmt = $pdo->prepare("INSERT INTO payments (contract_id, invoice_id, customer_id, contract_amount, amount, pay_date, paid_at, method, status, note, external_source, external_ref, created_at, updated_at)
                                   VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $now = date('Y-m-d H:i:s');
            $stmt->execute([
                $contract_id ?: null,
                $invoice_id ?: null,
                $customer_id ?: null,
                $contractAmount,
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

            if ($invoice_id) {
                $this->updateInvoicePaidAmount($pdo, $invoice_id);
            }
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
        $invoice_id  = (int)Str::normalizeDigits($_POST['invoice_id'] ?? '0');
        $customer_id = (int)Str::normalizeDigits($_POST['customer_id'] ?? '0');
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
            $contractAmount = null;
            if ($invoice_id) {
                $stmtInvoice = $pdo->prepare("SELECT id, customer_id, contract_id, payable_amount FROM invoices WHERE id = ?");
                $stmtInvoice->execute([$invoice_id]);
                $invoiceRow = $stmtInvoice->fetch();
                if ($invoiceRow) {
                    $contract_id = (int)($invoiceRow['contract_id'] ?? 0);
                    $customer_id = (int)($invoiceRow['customer_id'] ?? 0);
                    $contractAmount = (int)($invoiceRow['payable_amount'] ?? 0);
                } else {
                    $invoice_id = 0;
                }
            }
            if ($contract_id) {
                $stmtContract = $pdo->prepare("SELECT id, customer_id, total_amount FROM contracts WHERE id = ?");
                $stmtContract->execute([$contract_id]);
                $contractRow = $stmtContract->fetch();
                if ($contractRow) {
                    $customer_id    = (int)($contractRow['customer_id'] ?? 0);
                    $contractAmount = (int)($contractRow['total_amount'] ?? 0);
                } else {
                    $contract_id = 0;
                }
            }

            $stmt = $pdo->prepare("UPDATE payments SET contract_id=?, invoice_id=?, customer_id=?, contract_amount=?, amount=?, pay_date=?, paid_at=?, method=?, status=?, note=?, updated_at=? WHERE id=?");
            $now = date('Y-m-d H:i:s');
            $stmt->execute([
                $contract_id ?: null,
                $invoice_id ?: null,
                $customer_id ?: null,
                $contractAmount,
                $amount,
                $payDate,
                $paidAt,
                $method,
                $status,
                $note,
                $now,
                $id,
            ]);

            if ($invoice_id) {
                $this->updateInvoicePaidAmount($pdo, $invoice_id);
            }
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
                $invIdStmt = $pdo->prepare("SELECT invoice_id FROM payments WHERE id = ?");
                $invIdStmt->execute([$id]);
                $invoiceId = (int)($invIdStmt->fetchColumn() ?: 0);
                $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
                $stmt->execute([$id]);
                if ($invoiceId) {
                    $this->updateInvoicePaidAmount($pdo, $invoiceId);
                }
            } catch (PDOException $e) {
                View::renderError('خطا در حذف پرداخت: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            }
        }
        header('Location: /payments');
    }

    public function contractInfo(): void
    {
        $this->ensureAuth();
        header('Content-Type: application/json; charset=utf-8');

        $contractId = (int)Str::normalizeDigits($_GET['id'] ?? '0');
        if (!$contractId) {
            echo json_encode(['ok' => false, 'message' => 'شناسه قرارداد نامعتبر است.']);
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare("SELECT c.id, c.total_amount, c.customer_id, cust.name AS customer_name
                                    FROM contracts c
                                    LEFT JOIN customers cust ON cust.id = c.customer_id
                                    WHERE c.id = ?");
            $stmt->execute([$contractId]);
            $contract = $stmt->fetch();
            if (!$contract) {
                echo json_encode(['ok' => false, 'message' => 'قرارداد یافت نشد.']);
                return;
            }

            $stmtPaid = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='paid' AND contract_id = ?");
            $stmtPaid->execute([$contractId]);
            $paidTotal = (int)$stmtPaid->fetchColumn();

            $contractAmount = (int)($contract['total_amount'] ?? 0);
            $remaining       = max($contractAmount - $paidTotal, 0);

            echo json_encode([
                'ok'               => true,
                'contract_amount'  => $contractAmount,
                'paid_total'       => $paidTotal,
                'remaining'        => $remaining,
                'customer_id'      => (int)($contract['customer_id'] ?? 0),
                'customer_name'    => $contract['customer_name'] ?? null,
            ]);
        } catch (PDOException $e) {
            echo json_encode(['ok' => false, 'message' => 'خطا در واکشی قرارداد: ' . $e->getMessage()]);
        }
    }

    private function updateInvoicePaidAmount($pdo, int $invoiceId): void
    {
        if (!$invoiceId || !$pdo) {
            return;
        }

        try {
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id = ? AND status = 'paid'");
            $stmt->execute([$invoiceId]);
            $paid = (int)$stmt->fetchColumn();

            $update = $pdo->prepare("UPDATE invoices
                                     SET paid_amount = ?,
                                         status = CASE WHEN status='cancelled' THEN status WHEN payable_amount <= ? THEN 'paid' ELSE 'unpaid' END,
                                         updated_at = ?
                                     WHERE id = ?");
            $update->execute([$paid, $paid, date('Y-m-d H:i:s'), $invoiceId]);
        } catch (\Throwable $e) {
            // silent to avoid قطع جریان پرداخت
        }
    }
}
