<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Date;
use App\Core\Str;
use App\Core\View;
use App\Service\SmsService;
use PDO;
use PDOException;

class ProformaController
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
            $sql = "SELECT pf.*, c.name AS customer_name, ct.title AS contract_title
                    FROM proformas pf
                    LEFT JOIN customers c ON c.id = pf.customer_id
                    LEFT JOIN contracts ct ON ct.id = pf.contract_id
                    ORDER BY pf.id DESC";
            $proformas = $pdo->query($sql)->fetchAll();

            View::render('proformas/index', [
                'user'      => Auth::user(),
                'customers' => $customers,
                'contracts' => $contracts,
                'proformas' => $proformas,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در لیست پیش‌فاکتور: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
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
        $issueDateInp = Str::normalizeDigits(trim($_POST['issue_date'] ?? ''));
        $dueDateInp   = Str::normalizeDigits(trim($_POST['due_date'] ?? ''));
        $status       = trim($_POST['status'] ?? 'unpaid');
        $note         = trim($_POST['note'] ?? '');
        $items        = $this->parseItems($_POST['items'] ?? '');

        if ($title === '') {
            header('Location: /proformas');
            return;
        }

        if ($amount <= 0 && !empty($items)) {
            $amount = array_sum(array_column($items, 'amount'));
        }
        if ($amount < 0) $amount = 0;
        if ($discount < 0) $discount = 0;
        $payable = max(0, $amount - $discount);

        if (!in_array($status, ['unpaid', 'paid', 'cancelled'], true)) {
            $status = 'unpaid';
        }

        $issueDate = Date::fromJalaliInput($issueDateInp);
        $dueDate   = Date::fromJalaliInput($dueDateInp);

        try {
            $pdo = Database::connection();
            [$jy, $seq, $code] = $this->nextIndicator($pdo);
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO proformas (indicator_year, indicator_seq, indicator_code, customer_id, contract_id, title, items_json, gross_amount, discount_amount, payable_amount, status, issue_date, due_date, note, created_at, updated_at)
                                   VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
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
                $status,
                $issueDate,
                $dueDate,
                $note,
                $now,
                $now,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در ثبت پیش‌فاکتور: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /proformas');
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
        $issueDateInp = Str::normalizeDigits(trim($_POST['issue_date'] ?? ''));
        $dueDateInp   = Str::normalizeDigits(trim($_POST['due_date'] ?? ''));
        $status       = trim($_POST['status'] ?? 'unpaid');
        $note         = trim($_POST['note'] ?? '');
        $items        = $this->parseItems($_POST['items'] ?? '');

        if (!$id || $title === '') {
            header('Location: /proformas');
            return;
        }

        if ($amount <= 0 && !empty($items)) {
            $amount = array_sum(array_column($items, 'amount'));
        }
        if ($amount < 0) $amount = 0;
        if ($discount < 0) $discount = 0;
        $payable = max(0, $amount - $discount);
        if (!in_array($status, ['unpaid', 'paid', 'cancelled'], true)) {
            $status = 'unpaid';
        }

        $issueDate = Date::fromJalaliInput($issueDateInp);
        $dueDate   = Date::fromJalaliInput($dueDateInp);

        try {
            $pdo = Database::connection();
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("UPDATE proformas SET customer_id=?, contract_id=?, title=?, items_json=?, gross_amount=?, discount_amount=?, payable_amount=?, status=?, issue_date=?, due_date=?, note=?, updated_at=? WHERE id = ?");
            $stmt->execute([
                $customer_id ?: null,
                $contract_id ?: null,
                $title,
                json_encode($items, JSON_UNESCAPED_UNICODE),
                $amount,
                $discount,
                $payable,
                $status,
                $issueDate,
                $dueDate,
                $note,
                $now,
                $id,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در بروزرسانی پیش‌فاکتور: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /proformas');
    }

    public function delete(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_GET['id'] ?? '0');
        if (!$id) {
            header('Location: /proformas');
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare('DELETE FROM proformas WHERE id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            View::renderError('خطا در حذف پیش‌فاکتور: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /proformas');
    }

    public function convert(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_POST['id'] ?? '0');
        $sendSms = !empty($_POST['send_sms']);
        if (!$id) {
            header('Location: /proformas');
            return;
        }

        try {
            $pdo = Database::connection();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM proformas WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $proforma = $stmt->fetch();
            if (!$proforma) {
                $pdo->rollBack();
                header('Location: /proformas');
                return;
            }

            $items = json_decode($proforma['items_json'] ?? '', true) ?: [];
            $amount = (int)($proforma['gross_amount'] ?? 0);
            $discount = (int)($proforma['discount_amount'] ?? 0);
            $payable = max(0, $amount - $discount);

            [$jy, $seq, $code] = $this->nextIndicator($pdo);
            $now = date('Y-m-d H:i:s');
            $issueDate = $proforma['issue_date'] ?: date('Y-m-d');
            $dueDate   = $proforma['due_date'] ?: $issueDate;

            $stmtInv = $pdo->prepare("INSERT INTO invoices (indicator_year, indicator_seq, indicator_code, customer_id, contract_id, title, items_json, gross_amount, discount_amount, payable_amount, paid_amount, status, issue_date, due_date, note, created_at, updated_at)
                                      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmtInv->execute([
                $jy,
                $seq,
                $code,
                $proforma['customer_id'] ?: null,
                $proforma['contract_id'] ?: null,
                $proforma['title'],
                json_encode($items, JSON_UNESCAPED_UNICODE),
                $amount,
                $discount,
                $payable,
                0,
                $proforma['status'] === 'paid' ? 'paid' : 'unpaid',
                $issueDate,
                $dueDate,
                $proforma['note'],
                $now,
                $now,
            ]);
            $newInvoiceId = (int)$pdo->lastInsertId();

            $stmtUp = $pdo->prepare("UPDATE proformas SET converted_invoice_id=?, converted_at=?, updated_at=? WHERE id = ?");
            $stmtUp->execute([$newInvoiceId, $now, $now, $id]);
            $pdo->commit();

            if ($sendSms && (int)$proforma['customer_id'] > 0) {
                $custStmt = $pdo->prepare("SELECT phone, name FROM customers WHERE id = ?");
                $custStmt->execute([(int)$proforma['customer_id']]);
                $customer = $custStmt->fetch();
                if ($customer && !empty($customer['phone'])) {
                    $text = 'فاکتور شماره ' . $code . ' به مبلغ ' . number_format($payable) . ' ریال ثبت شد.';
                    try {
                        $sms = new SmsService();
                        $sms->sendStandard([(string)$customer['phone']], $text, 'invoice', null, (int)$proforma['customer_id']);
                    } catch (\Throwable $e) {
                        // ignore sms errors to avoid failing conversion
                    }
                }
            }
        } catch (PDOException $e) {
            try { $pdo?->rollBack(); } catch (\Throwable $t) {}
            View::renderError('خطا در تبدیل پیش‌فاکتور: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /invoices');
    }

    private function nextIndicator(PDO $pdo): array
    {
        [$jy] = Date::currentJalali();
        $stmt = $pdo->prepare('SELECT MAX(indicator_seq) FROM proformas WHERE indicator_year = ?');
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
}
