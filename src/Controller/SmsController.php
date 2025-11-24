<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Str;
use App\Core\View;
use App\Service\SmsService;
use PDO;
use PDOException;

class SmsController
{
    private SmsService $sms;
    private PDO $pdo;

    public function __construct()
    {
        $this->sms = new SmsService();
        $this->pdo = Database::connection();
    }

    private function ensureAuth(): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
    }

    private function redirectWithMessage(string $message, bool $success = true): void
    {
        $query = http_build_query(['msg' => $message, 'ok' => $success ? 1 : 0]);
        header('Location: /sms?' . $query);
    }

    public function index(): void
    {
        $this->ensureAuth();

        try {
            $categories = $this->pdo->query('SELECT id, name FROM product_categories ORDER BY name')->fetchAll();
            $customers = $this->pdo->query('SELECT id, name, phone FROM customers ORDER BY name LIMIT 300')->fetchAll();
            $contracts = $this->pdo->query('SELECT id, title, customer_id FROM contracts ORDER BY id DESC LIMIT 200')->fetchAll();
            $logs = $this->pdo->query('SELECT * FROM sms_logs ORDER BY id DESC LIMIT 50')->fetchAll();
        } catch (PDOException $e) {
            View::renderError('خطا در بارگذاری صفحه پیامک: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        $balance = null;
        $tariff = null;
        $authStatus = null;
        try {
            $balance = $this->sms->balance();
            $tariff = $this->sms->tariff();
            $authStatus = $this->sms->authStatus();
        } catch (\Throwable $e) {
            // ignore connectivity issues, form actions still available
        }

        View::render('sms/index', [
            'user'       => Auth::user(),
            'categories' => $categories,
            'customers'  => $customers,
            'contracts'  => $contracts,
            'logs'       => $logs,
            'balance'    => $balance,
            'tariff'     => $tariff,
            'authStatus' => $authStatus,
            'flash'      => ['message' => $_GET['msg'] ?? null, 'ok' => ($_GET['ok'] ?? '1') === '1'],
        ]);
    }

    public function send(): void
    {
        $this->ensureAuth();
        $text = trim($_POST['text'] ?? '');
        $category = trim($_POST['category'] ?? '') ?: null;
        $schedule = trim($_POST['schedule_at'] ?? '') ?: null;
        $numbersRaw = preg_split('/[\s,\n\r]+/u', $_POST['recipients'] ?? '', -1, PREG_SPLIT_NO_EMPTY);
        $phones = [];
        foreach ($numbersRaw as $raw) {
            $digits = Str::digitsOnly($raw);
            if ($digits !== '') {
                $phones[] = $digits;
            }
        }

        if ($text === '' || empty($phones)) {
            $this->redirectWithMessage('متن پیام و گیرنده الزامی است', false);
            return;
        }

        $res = $this->sms->sendStandard($phones, $text, $category, $schedule);
        $this->redirectWithMessage($res['success'] ? 'پیام ارسال شد' : ('خطا در ارسال: ' . ($res['message'] ?? '')), $res['success']);
    }

    public function sendBulk(): void
    {
        $this->ensureAuth();
        $categoryIds = array_filter(array_map('intval', $_POST['category_ids'] ?? []));
        $text = trim($_POST['text'] ?? '');
        $tag = trim($_POST['category'] ?? '') ?: null;

        if (empty($categoryIds) || $text === '') {
            $this->redirectWithMessage('دسته‌بندی و متن پیام الزامی است', false);
            return;
        }

        try {
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $stmt = $this->pdo->prepare("SELECT DISTINCT c.phone FROM customers c JOIN contracts ct ON ct.customer_id = c.id WHERE ct.category_id IN ($placeholders) AND c.phone IS NOT NULL AND c.phone <> ''");
            $stmt->execute($categoryIds);
            $phones = [];
            while ($row = $stmt->fetch()) {
                $digits = Str::digitsOnly($row['phone'] ?? '');
                if ($digits !== '') {
                    $phones[] = $digits;
                }
            }
        } catch (PDOException $e) {
            View::renderError('خطا در یافتن مشتریان هدف: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        if (empty($phones)) {
            $this->redirectWithMessage('شماره معتبری برای دسته‌بندی انتخابی یافت نشد', false);
            return;
        }

        $res = $this->sms->sendStandard($phones, $text, $tag, null);
        $this->redirectWithMessage($res['success'] ? 'ارسال گروهی انجام شد' : ('خطا در ارسال: ' . ($res['message'] ?? '')), $res['success']);
    }

    public function sendCorrelated(): void
    {
        $this->ensureAuth();
        $category = trim($_POST['category'] ?? '') ?: null;
        $schedule = trim($_POST['schedule_at'] ?? '') ?: null;
        $lines = preg_split('/[\n\r]+/u', $_POST['batch'] ?? '', -1, PREG_SPLIT_NO_EMPTY);
        $messages = [];
        foreach ($lines as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) >= 2) {
                $phone = Str::digitsOnly($parts[0]);
                $text = $parts[1];
                if ($phone !== '' && $text !== '') {
                    $messages[] = [
                        'to' => $phone,
                        'text' => $text,
                        'correlation_id' => $parts[2] ?? null,
                    ];
                }
            }
        }

        if (empty($messages)) {
            $this->redirectWithMessage('لیست پیام‌های همبسته معتبر نیست', false);
            return;
        }

        $res = $this->sms->sendCorrelated($messages, $category, $schedule);
        $this->redirectWithMessage($res['success'] ? 'ارسال همبسته انجام شد' : ('خطا: ' . ($res['message'] ?? '')), $res['success']);
    }

    public function sendPattern(): void
    {
        $this->ensureAuth();
        $pattern = trim($_POST['pattern_code'] ?? '');
        $receptor = Str::digitsOnly($_POST['receptor'] ?? '');
        $category = trim($_POST['category'] ?? '') ?: null;
        $valuesRaw = $_POST['values'] ?? '';
        $values = [];
        foreach (explode("\n", $valuesRaw) as $line) {
            if (strpos($line, ':') !== false) {
                [$k, $v] = array_map('trim', explode(':', $line, 2));
                if ($k !== '') {
                    $values[$k] = $v;
                }
            }
        }

        if ($pattern === '' || $receptor === '' || empty($values)) {
            $this->redirectWithMessage('کد پترن، گیرنده و مقادیر الزامی است', false);
            return;
        }

        $res = $this->sms->sendPattern($pattern, $receptor, $values, $category);
        $this->redirectWithMessage($res['success'] ? 'پیام الگو ارسال شد' : ('خطا: ' . ($res['message'] ?? '')), $res['success']);
    }

    public function sendVoice(): void
    {
        $this->ensureAuth();
        $receptor = Str::digitsOnly($_POST['receptor'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $category = trim($_POST['category'] ?? '') ?: null;

        if ($receptor === '' || $code === '') {
            $this->redirectWithMessage('گیرنده و کد صوتی الزامی است', false);
            return;
        }

        $res = $this->sms->sendVoiceOtp($receptor, $code, $category);
        $this->redirectWithMessage($res['success'] ? 'ارسال صوتی انجام شد' : ('خطا: ' . ($res['message'] ?? '')), $res['success']);
    }

    public function invoiceReminder(): void
    {
        $this->ensureAuth();
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $amount = Str::digitsOnly($_POST['amount'] ?? '');
        $dueDate = trim($_POST['due_date'] ?? '');
        $message = trim($_POST['text'] ?? '');

        if ($customerId <= 0 || $message === '') {
            $this->redirectWithMessage('مشتری و پیام الزامی است', false);
            return;
        }

        $phone = $this->lookupCustomerPhone($customerId);
        if ($phone === null) {
            $this->redirectWithMessage('شماره‌ای برای مشتری یافت نشد', false);
            return;
        }

        $filledMessage = str_replace(['{amount}', '{due_date}'], [$amount, $dueDate], $message);
        $res = $this->sms->sendStandard([$phone], $filledMessage, 'invoice_reminder', null, $customerId);
        $this->redirectWithMessage($res['success'] ? 'یادآوری فاکتور ارسال شد' : ('خطا: ' . ($res['message'] ?? '')), $res['success']);
    }

    public function welcomeTrigger(): void
    {
        $this->ensureAuth();
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $contractId = (int)($_POST['contract_id'] ?? 0);
        $message = trim($_POST['text'] ?? '');

        if ($customerId <= 0 || $message === '') {
            $this->redirectWithMessage('مشتری و پیام الزامی است', false);
            return;
        }

        $phone = $this->lookupCustomerPhone($customerId);
        if ($phone === null) {
            $this->redirectWithMessage('شماره‌ای برای مشتری یافت نشد', false);
            return;
        }

        $contractTitle = $this->lookupContractTitle($contractId);
        $filledMessage = str_replace('{contract}', $contractTitle, $message);
        $res = $this->sms->sendStandard([$phone], $filledMessage, 'welcome', null, $customerId);
        $this->redirectWithMessage($res['success'] ? 'پیام خوش‌آمد ارسال شد' : ('خطا: ' . ($res['message'] ?? '')), $res['success']);
    }

    public function fetchInbound(): void
    {
        $this->ensureAuth();
        $since = trim($_POST['since'] ?? '') ?: null;
        $res = $this->sms->fetchInbound($since);
        $this->redirectWithMessage($res['success'] ? 'دریافت پیام‌های ورودی انجام شد' : ('خطا: ' . ($res['message'] ?? '')), $res['success']);
    }

    public function checkStatus(): void
    {
        $this->ensureAuth();
        $messageId = trim($_POST['message_id'] ?? '');
        if ($messageId === '') {
            $this->redirectWithMessage('شناسه پیام الزامی است', false);
            return;
        }

        $res = $this->sms->deliveryStatus($messageId);
        $this->redirectWithMessage($res['success'] ? 'وضعیت پیام بازیابی شد' : ('خطا: ' . ($res['message'] ?? '')), $res['success']);
    }

    public function cancelScheduled(): void
    {
        $this->ensureAuth();
        $messageId = trim($_POST['message_id'] ?? '');
        if ($messageId === '') {
            $this->redirectWithMessage('شناسه پیام الزامی است', false);
            return;
        }

        $res = $this->sms->cancelScheduled($messageId);
        $this->redirectWithMessage($res['success'] ? 'پیام زمان‌بندی شده لغو شد' : ('خطا: ' . ($res['message'] ?? '')), $res['success']);
    }

    private function lookupCustomerPhone(int $customerId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT phone FROM customers WHERE id = ?');
        $stmt->execute([$customerId]);
        $phone = $stmt->fetchColumn();
        $digits = Str::digitsOnly((string)$phone);
        return $digits !== '' ? $digits : null;
    }

    private function lookupContractTitle(int $contractId): string
    {
        if ($contractId <= 0) {
            return '';
        }
        $stmt = $this->pdo->prepare('SELECT title FROM contracts WHERE id = ?');
        $stmt->execute([$contractId]);
        return (string)$stmt->fetchColumn();
    }
}
