<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Date;
use App\Core\Str;
use App\Core\View;
use PDO;
use PDOException;

class InvoiceController
{
    private function ensureAuth(): void
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
            $customers = $pdo->query("SELECT id, name, phone FROM customers ORDER BY name")->fetchAll();
            $contracts = $pdo->query("SELECT id, title, customer_id FROM contracts ORDER BY id DESC")->fetchAll();
            $sql = "SELECT i.*, c.name AS customer_name, ct.title AS contract_title,
                           COALESCE(SUM(CASE WHEN p.status='paid' THEN p.amount ELSE 0 END),0) AS paid_from_payments
                    FROM invoices i
                    LEFT JOIN customers c ON c.id = i.customer_id
                    LEFT JOIN contracts ct ON ct.id = i.contract_id
                    LEFT JOIN payments p ON p.invoice_id = i.id
                    GROUP BY i.id, c.name, ct.title
                    ORDER BY i.id DESC";
            $invoices = $pdo->query($sql)->fetchAll();

            View::render('invoices/index', [
                'user'      => Auth::user(),
                'customers' => $customers,
                'contracts' => $contracts,
                'invoices'  => $invoices,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در لیست فاکتورها: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }
    }

    public function show(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_GET['id'] ?? '0');
        if (!$id) {
            header('Location: /invoices');
            return;
        }

        try {
            $pdo = Database::connection();
            $invoice = $this->loadInvoice($pdo, $id);
            if (!$invoice) {
                header('Location: /invoices');
                return;
            }
            $payments = $this->loadInvoicePayments($pdo, $id, (int)($invoice['contract_id'] ?? 0), (int)($invoice['customer_id'] ?? 0));

            View::render('invoices/show', [
                'user'     => Auth::user(),
                'invoice'  => $invoice,
                'payments' => $payments,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در نمایش فاکتور: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }
    }

    public function print(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_GET['id'] ?? '0');
        if (!$id) {
            header('Location: /invoices');
            return;
        }

        try {
            $pdo = Database::connection();
            $invoice = $this->loadInvoice($pdo, $id);
            if (!$invoice) {
                header('Location: /invoices');
                return;
            }
            $payments = $this->loadInvoicePayments($pdo, $id, (int)($invoice['contract_id'] ?? 0), (int)($invoice['customer_id'] ?? 0));

            if (isset($_GET['download'])) {
                header('Content-Disposition: attachment; filename="invoice-' . $invoice['indicator_code'] . '.html"');
            }

            View::render('invoices/print', [
                'invoice'  => $invoice,
                'payments' => $payments,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در چاپ فاکتور: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }
    }

    public function create(): void
    {
        $this->ensureAuth();
        $title        = Str::beautifyLabel($_POST['title'] ?? '');
        $customer_id  = (int)Str::normalizeDigits($_POST['customer_id'] ?? '0');
        $contract_id  = (int)Str::normalizeDigits($_POST['contract_id'] ?? '0');
        $amount       = (int)Str::normalizeDigits($_POST['amount'] ?? '0');
        $discount     = (int)Str::normalizeDigits($_POST['discount_amount'] ?? '0');
        $paidAmount   = (int)Str::normalizeDigits($_POST['paid_amount'] ?? '0');
        $issueDateInp = Str::normalizeDigits(trim($_POST['issue_date'] ?? ''));
        $dueDateInp   = Str::normalizeDigits(trim($_POST['due_date'] ?? ''));
        $status       = trim($_POST['status'] ?? 'unpaid');
        $note         = trim($_POST['note'] ?? '');
        $items        = $this->parseItems($_POST['items'] ?? '');

        if ($title === '') {
            header('Location: /invoices');
            return;
        }

        if ($amount <= 0 && !empty($items)) {
            $amount = array_sum(array_column($items, 'amount'));
        }
        if ($amount < 0) $amount = 0;
        if ($discount < 0) $discount = 0;
        $payable = max(0, $amount - $discount);
        $paidAmount = max(0, min($payable, $paidAmount));

        if (!in_array($status, ['unpaid', 'paid', 'cancelled'], true)) {
            $status = 'unpaid';
        }
        if ($status === 'paid' && $paidAmount === 0) {
            $paidAmount = $payable;
        }

        $issueDate = Date::fromJalaliInput($issueDateInp);
        $dueDate   = Date::fromJalaliInput($dueDateInp);

        try {
            $pdo = Database::connection();
            [$jy, $seq, $code] = $this->nextIndicator($pdo, 'invoices');
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO invoices (indicator_year, indicator_seq, indicator_code, customer_id, contract_id, title, items_json, gross_amount, discount_amount, payable_amount, paid_amount, status, issue_date, due_date, note, created_at, updated_at)
                                   VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $jy,
                $seq,
                $code,
                $customer_id ?: null,
                $contract_id ?: null,
                $title,
                json_encode($items, JSON_UNESCAPED_UNICODE),
                $amount,
                $discount,
                $payable,
                $paidAmount,
                $status,
                $issueDate,
                $dueDate,
                $note,
                $now,
                $now,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در ثبت فاکتور: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /invoices');
    }

    public function edit(): void
    {
        $this->ensureAuth();
        $id           = (int)Str::normalizeDigits($_POST['id'] ?? '0');
        $title        = Str::beautifyLabel($_POST['title'] ?? '');
        $customer_id  = (int)Str::normalizeDigits($_POST['customer_id'] ?? '0');
        $contract_id  = (int)Str::normalizeDigits($_POST['contract_id'] ?? '0');
        $amount       = (int)Str::normalizeDigits($_POST['amount'] ?? '0');
        $discount     = (int)Str::normalizeDigits($_POST['discount_amount'] ?? '0');
        $paidAmount   = (int)Str::normalizeDigits($_POST['paid_amount'] ?? '0');
        $issueDateInp = Str::normalizeDigits(trim($_POST['issue_date'] ?? ''));
        $dueDateInp   = Str::normalizeDigits(trim($_POST['due_date'] ?? ''));
        $status       = trim($_POST['status'] ?? 'unpaid');
        $note         = trim($_POST['note'] ?? '');
        $items        = $this->parseItems($_POST['items'] ?? '');

        if (!$id || $title === '') {
            header('Location: /invoices');
            return;
        }

        if ($amount <= 0 && !empty($items)) {
            $amount = array_sum(array_column($items, 'amount'));
        }
        if ($amount < 0) $amount = 0;
        if ($discount < 0) $discount = 0;
        $payable = max(0, $amount - $discount);
        $paidAmount = max(0, min($payable, $paidAmount));
        if (!in_array($status, ['unpaid', 'paid', 'cancelled'], true)) {
            $status = 'unpaid';
        }
        if ($status === 'paid' && $paidAmount === 0) {
            $paidAmount = $payable;
        }

        $issueDate = Date::fromJalaliInput($issueDateInp);
        $dueDate   = Date::fromJalaliInput($dueDateInp);

        try {
            $pdo = Database::connection();
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("UPDATE invoices SET customer_id=?, contract_id=?, title=?, items_json=?, gross_amount=?, discount_amount=?, payable_amount=?, paid_amount=?, status=?, issue_date=?, due_date=?, note=?, updated_at=? WHERE id = ?");
            $stmt->execute([
                $customer_id ?: null,
                $contract_id ?: null,
                $title,
                json_encode($items, JSON_UNESCAPED_UNICODE),
                $amount,
                $discount,
                $payable,
                $paidAmount,
                $status,
                $issueDate,
                $dueDate,
                $note,
                $now,
                $id,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در بروزرسانی فاکتور: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /invoices');
    }

    public function delete(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_GET['id'] ?? '0');
        if (!$id) {
            header('Location: /invoices');
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare('DELETE FROM invoices WHERE id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            View::renderError('خطا در حذف فاکتور: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /invoices');
    }

    private function nextIndicator(PDO $pdo, string $table): array
    {
        [$jy] = Date::currentJalali();
        $allowed = ['invoices', 'proformas'];
        if (!in_array($table, $allowed, true)) {
            throw new \InvalidArgumentException('جدول مجاز نیست');
        }
        $stmt = $pdo->prepare("SELECT MAX(indicator_seq) FROM {$table} WHERE indicator_year = ?");
        $stmt->execute([$jy]);
        $seq = (int)$stmt->fetchColumn();
        $seq++;
        $code = $jy . '/' . $seq;
        return [$jy, $seq, $code];
    }

    private function parseItems(string $raw): array
    {
        $lines = preg_split('/\r?\n/', trim($raw));
        $items = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $parts = array_map('trim', explode('|', $line));
            $label = Str::beautifyLabel($parts[0] ?? '');
            $amount = isset($parts[1]) ? (int)Str::normalizeDigits($parts[1]) : 0;
            if ($label === '' && $amount <= 0) continue;
            $items[] = [
                'title'  => $label ?: 'آیتم',
                'amount' => $amount,
            ];
        }
        return $items;
    }

    private function loadInvoice(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT i.*, c.name AS customer_name, c.phone AS customer_phone, ct.title AS contract_title
                                FROM invoices i
                                LEFT JOIN customers c ON c.id = i.customer_id
                                LEFT JOIN contracts ct ON ct.id = i.contract_id
                                WHERE i.id = ?");
        $stmt->execute([$id]);
        $invoice = $stmt->fetch();
        if ($invoice) {
            $invoice['items'] = json_decode($invoice['items_json'] ?? '', true) ?: [];
            $invoice['balance'] = max(0, ((int)$invoice['payable_amount']) - ((int)$invoice['paid_amount']));
        }
        return $invoice ?: null;
    }

    private function loadInvoicePayments(PDO $pdo, int $invoiceId, int $contractId, int $customerId): array
    {
        $stmt = $pdo->prepare("SELECT p.*
                               FROM payments p
                               WHERE (p.invoice_id = :iid)
                                  OR (:cid > 0 AND p.contract_id = :cid)
                                  OR (:cust > 0 AND p.customer_id = :cust)
                               ORDER BY p.id DESC");
        $stmt->execute([
            ':iid'  => $invoiceId,
            ':cid'  => $contractId,
            ':cust' => $customerId,
        ]);
        return $stmt->fetchAll();
    }
}
