<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\View;
use App\Core\Database;
use App\Core\Str;
use App\Core\Date;
use PDOException;

class CustomerController
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
            $rows = $pdo->query("SELECT * FROM customers ORDER BY id DESC")->fetchAll();
            View::render('customers/index', [
                'user'      => Auth::user(),
                'customers' => $rows,
            ]);
        } catch (PDOException $e) {
            View::renderError('خطا در بارگذاری لیست مشتریان: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }
    }

    public function create(): void
    {
        $this->ensureAuth();
        $name  = Str::beautifyLabel($_POST['name'] ?? '');
        $phone = Str::digitsOnly(trim($_POST['phone'] ?? ''));
        $email = trim($_POST['email'] ?? '');
        $note  = trim($_POST['note'] ?? '');

        if ($name === '') {
            header('Location: /customers');
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, note, created_at, updated_at) VALUES (?,?,?,?,?,?)");
            $now = date('Y-m-d H:i:s');
            $stmt->execute([$name, $phone, $email, $note, $now, $now]);
        } catch (PDOException $e) {
            View::renderError('خطا در ثبت مشتری: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /customers');
    }

    public function edit(): void
    {
        $this->ensureAuth();
        $id    = (int)($_POST['id'] ?? 0);
        $name  = Str::beautifyLabel($_POST['name'] ?? '');
        $phone = Str::digitsOnly(trim($_POST['phone'] ?? ''));
        $email = trim($_POST['email'] ?? '');
        $note  = trim($_POST['note'] ?? '');

        if (!$id || $name === '') {
            header('Location: /customers');
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare("UPDATE customers SET name=?, phone=?, email=?, note=?, updated_at=? WHERE id=?");
            $now = date('Y-m-d H:i:s');
            $stmt->execute([$name, $phone, $email, $note, $now, $id]);
        } catch (PDOException $e) {
            View::renderError('خطا در بروزرسانی مشتری: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
        }

        header('Location: /customers');
    }

    public function delete(): void
    {
        $this->ensureAuth();
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            try {
                $pdo = Database::connection();
                $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
                $stmt->execute([$id]);
            } catch (PDOException $e) {
                View::renderError('خطا در حذف مشتری: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            }
        }
        header('Location: /customers');
    }

    public function profile(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_GET['id'] ?? '0');
        if (!$id) {
            header('Location: /customers');
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$id]);
            $customer = $stmt->fetch();
            if (!$customer) {
                header('Location: /customers');
                return;
            }

            $contractsStmt = $pdo->prepare("SELECT c.*, COALESCE(e.full_name, 'مدیریت') AS employee_name, cat.name AS category_name
                                            FROM contracts c
                                            LEFT JOIN employees e ON e.id = c.sales_employee_id
                                            LEFT JOIN product_categories cat ON cat.id = c.category_id
                                            WHERE c.customer_id = ?
                                            ORDER BY c.id DESC");
            $contractsStmt->execute([$id]);
            $contracts = $contractsStmt->fetchAll();

            $stmtTotal = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM contracts WHERE customer_id = ?");
            $stmtTotal->execute([$id]);
            $contractTotal = (int)$stmtTotal->fetchColumn();

            $stmtPaid = $pdo->prepare("SELECT COALESCE(SUM(p.amount),0)
                                        FROM payments p
                                        LEFT JOIN contracts c ON c.id = p.contract_id
                                        WHERE p.status = 'paid' AND (p.customer_id = ? OR c.customer_id = ?)");
            $stmtPaid->execute([$id, $id]);
            $paidTotal = (int)$stmtPaid->fetchColumn();

            $dueTotal = $contractTotal - $paidTotal;

            $paymentsStmt = $pdo->prepare("SELECT p.*, c.title AS contract_title
                                           FROM payments p
                                           LEFT JOIN contracts c ON c.id = p.contract_id
                                           WHERE p.customer_id = ? OR c.customer_id = ?
                                           ORDER BY p.id DESC
                                           LIMIT 20");
            $paymentsStmt->execute([$id, $id]);
            $payments = $paymentsStmt->fetchAll();

            $servers = $pdo->query("SELECT id, hostname, ip, last_check_message FROM servers")->fetchAll();
            $serversMap = [];
            foreach ($servers as $srv) {
                $serversMap[$srv['id']] = $srv;
            }

            $hostingAccountsStmt = $pdo->prepare("SELECT h.*, s.hostname AS server_name, s.hostname"
                                                  . " FROM hosting_accounts h"
                                                  . " LEFT JOIN servers s ON s.id = h.server_id"
                                                  . " WHERE h.customer_id = ?"
                                                  . " ORDER BY h.id DESC");
            $hostingAccountsStmt->execute([$id]);
            $hostingAccounts = $hostingAccountsStmt->fetchAll();
            foreach ($hostingAccounts as &$ha) {
                $ha['meta'] = json_decode($ha['meta_json'] ?? '', true) ?: [];
            }
            unset($ha);

            $domainsStmt = $pdo->prepare("SELECT * FROM domains WHERE customer_id = ? ORDER BY id DESC");
            $domainsStmt->execute([$id]);
            $domains = $domainsStmt->fetchAll();
            foreach ($domains as &$dom) {
                $dom['nameservers'] = !empty($dom['nameservers_json']) ? (json_decode($dom['nameservers_json'], true) ?: []) : [];
                $dom['dns_records'] = !empty($dom['dns_records_json']) ? (json_decode($dom['dns_records_json'], true) ?: []) : [];
                $dom['whois'] = !empty($dom['whois_json']) ? (json_decode($dom['whois_json'], true) ?: []) : [];
            }
            unset($dom);

            $unsyncedDomainsStmt = $pdo->prepare("SELECT * FROM domains WHERE customer_id IS NULL ORDER BY id DESC LIMIT 20");
            $unsyncedDomainsStmt->execute();
            $unsyncedDomains = $unsyncedDomainsStmt->fetchAll();

            $registrarBalance = '';
            $resellerBalance  = '';

            $syncLogsStmt = $pdo->prepare("SELECT * FROM sync_logs WHERE customer_id = ? ORDER BY id DESC LIMIT 50");
            $syncLogsStmt->execute([$id]);
            $syncLogs = $syncLogsStmt->fetchAll();

            $auditLogsStmt = $pdo->prepare("SELECT * FROM audit_logs WHERE customer_id = ? ORDER BY id DESC LIMIT 50");
            $auditLogsStmt->execute([$id]);
            $auditLogs = $auditLogsStmt->fetchAll();

            $notificationsStmt = $pdo->prepare("SELECT * FROM notifications WHERE customer_id = ? ORDER BY id DESC LIMIT 20");
            $notificationsStmt->execute([$id]);
            $notifications = $notificationsStmt->fetchAll();

            $smsLogsStmt = $pdo->prepare("SELECT * FROM sms_logs WHERE customer_id = ? ORDER BY id DESC LIMIT 30");
            $smsLogsStmt->execute([$id]);
            $smsLogs = $smsLogsStmt->fetchAll();

            [$walletAccount, $walletTransactions] = $this->loadWalletData($pdo, $id, $paidTotal, $contractTotal);

        } catch (PDOException $e) {
            View::renderError('خطا در بارگذاری پروفایل مشتری: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        View::render('customers/profile', [
            'user'          => Auth::user(),
            'customer'      => $customer,
            'contracts'     => $contracts,
            'contractTotal' => $contractTotal,
            'paidTotal'     => $paidTotal,
            'dueTotal'      => $dueTotal,
            'payments'      => $payments,
            'serversMap'    => $serversMap ?? [],
            'domains'       => $domains ?? [],
            'hostingAccounts' => $hostingAccounts ?? [],
            'unsyncedDomains' => $unsyncedDomains ?? [],
            'syncLogs'        => $syncLogs ?? [],
            'auditLogs'       => $auditLogs ?? [],
            'notifications'   => $notifications ?? [],
            'smsLogs'         => $smsLogs ?? [],
            'registrarBalance'=> $registrarBalance ?: '—',
            'resellerBalance' => $resellerBalance ?: '—',
            'walletAccount'   => $walletAccount,
            'walletTransactions' => $walletTransactions,
        ]);
    }

    public function walletAdjust(): void
    {
        $this->ensureAuth();

        $customerId = (int)($_POST['customer_id'] ?? 0);
        $amount     = (int)Str::normalizeDigits($_POST['amount'] ?? '0');
        $note       = trim($_POST['description'] ?? '');
        $direction  = ($_POST['direction'] ?? 'credit') === 'debit' ? 'debit' : 'credit';

        if ($customerId <= 0 || $amount <= 0) {
            $this->jsonResponse(false, 'مبلغ یا شناسه مشتری نامعتبر است.');
            return;
        }

        try {
            $pdo = Database::connection();
            $pdo->beginTransaction();
            $wallet = $this->getOrCreateWalletAccount($pdo, $customerId);
            $this->recordWalletTransaction($pdo, $wallet['id'], $direction, $amount, $note, 'manual', null);
            $balance = $this->refreshWalletBalance($pdo, $wallet['id']);
            $pdo->commit();
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->jsonResponse(false, 'خطا در به‌روزرسانی کیف پول: ' . $e->getMessage());
            return;
        }

        $this->jsonResponse(true, 'کیف پول با موفقیت بروزرسانی شد.', ['balance' => $balance]);
    }

    protected function loadWalletData($pdo, int $customerId, int $paidTotal, int $contractTotal): array
    {
        $wallet = $this->getOrCreateWalletAccount($pdo, $customerId);
        $overpayment = max(0, $paidTotal - $contractTotal);

        if ($overpayment > 0) {
            $existing = $this->getOverpaymentCredits($pdo, $wallet['id']);
            $pendingCredit = $overpayment - $existing;
            if ($pendingCredit > 0) {
                $this->recordWalletTransaction(
                    $pdo,
                    $wallet['id'],
                    'credit',
                    $pendingCredit,
                    'اعتبار مازاد پرداخت قرارداد',
                    'overpayment',
                    null
                );
            }
        }

        $wallet['balance'] = $this->refreshWalletBalance($pdo, $wallet['id']);

        $txnStmt = $pdo->prepare("SELECT * FROM wallet_transactions WHERE wallet_account_id = ? ORDER BY id DESC LIMIT 80");
        $txnStmt->execute([$wallet['id']]);
        $transactions = $txnStmt->fetchAll();

        return [$wallet, $transactions];
    }

    protected function getOrCreateWalletAccount($pdo, int $customerId): array
    {
        $stmt = $pdo->prepare("SELECT * FROM wallet_accounts WHERE customer_id = ? LIMIT 1");
        $stmt->execute([$customerId]);
        $wallet = $stmt->fetch();
        if ($wallet) {
            return $wallet;
        }

        $now = date('Y-m-d H:i:s');
        $insert = $pdo->prepare("INSERT INTO wallet_accounts (customer_id, balance, created_at, updated_at) VALUES (?,?,?,?)");
        $insert->execute([$customerId, 0, $now, $now]);

        return [
            'id' => (int)$pdo->lastInsertId(),
            'customer_id' => $customerId,
            'balance' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    protected function refreshWalletBalance($pdo, int $walletId): int
    {
        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(CASE WHEN direction='credit' THEN amount ELSE -amount END),0) AS balance"
            . " FROM wallet_transactions WHERE wallet_account_id = ?"
        );
        $stmt->execute([$walletId]);
        $balance = (int)$stmt->fetchColumn();

        $update = $pdo->prepare("UPDATE wallet_accounts SET balance = ?, updated_at = ? WHERE id = ?");
        $update->execute([$balance, date('Y-m-d H:i:s'), $walletId]);

        return $balance;
    }

    protected function getOverpaymentCredits($pdo, int $walletId): int
    {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE wallet_account_id = ? AND reference_type = 'overpayment' AND direction = 'credit'");
        $stmt->execute([$walletId]);
        return (int)$stmt->fetchColumn();
    }

    protected function recordWalletTransaction($pdo, int $walletId, string $direction, int $amount, string $description = '', ?string $referenceType = null, $referenceId = null): void
    {
        $stmt = $pdo->prepare(
            "INSERT INTO wallet_transactions (wallet_account_id, direction, amount, description, reference_type, reference_id, created_at)"
            . " VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $walletId,
            $direction,
            $amount,
            $description,
            $referenceType,
            $referenceId,
            date('Y-m-d H:i:s'),
        ]);
    }

    protected function jsonResponse(bool $success, string $message, array $data = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message] + $data);
        exit;
    }
}
