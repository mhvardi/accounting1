<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Str;
use App\Core\View;
use App\Service\NotificationService;
use PDO;
use PDOException;

class LeadController
{
    private array $statusLabels = [
        'new'        => 'لید جدید',
        'contacted'  => 'تماس گرفته شد',
        'qualified'  => 'کیفیت‌سنجی شد',
        'proposal'   => 'ارسال پیشنهاد',
        'won'        => 'برنده شد',
        'lost'       => 'از دست رفت',
        'converted'  => 'تبدیل به مشتری',
    ];

    private function ensureAuth(): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
    }

    private function templates(): array
    {
        return [
            'default' => [
                'title' => 'چک‌لیست پایه فروش',
                'items' => [
                    'ثبت اطلاعات تماس و نیاز اولیه',
                    'پیگیری اولیه و زمان‌بندی جلسه',
                    'ارسال پیش‌نویس پیشنهاد',
                    'پیگیری جهت تایید پیشنهاد',
                    'هماهنگی پیش‌فاکتور یا قرارداد',
                ],
            ],
            'hosting' => [
                'title' => 'چک‌لیست هاستینگ',
                'items' => [
                    'جمع‌آوری دامنه یا زیردامنه',
                    'بررسی نیاز منبع و پلن',
                    'مستندسازی مشخصات DNS',
                    'ارسال دسترسی و شروع فعال‌سازی',
                ],
            ],
        ];
    }

    private function allowedStatus(string $status): string
    {
        return array_key_exists($status, $this->statusLabels) ? $status : 'new';
    }

    private function fetchEmployees(PDO $pdo): array
    {
        return $pdo->query("SELECT id, full_name FROM employees WHERE active = 1 ORDER BY full_name")?->fetchAll() ?: [];
    }

    private function createChecklist(PDO $pdo, int $leadId, string $templateKey): void
    {
        $templates = $this->templates();
        $template  = $templates[$templateKey] ?? $templates['default'];
        $now       = date('Y-m-d H:i:s');

        $stmtChecklist = $pdo->prepare('INSERT INTO lead_checklists (lead_id, template_key, title, created_at, updated_at) VALUES (?,?,?,?,?)');
        $stmtChecklist->execute([$leadId, $templateKey, $template['title'], $now, $now]);
        $checklistId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare('INSERT INTO lead_checklist_items (checklist_id, title, status, created_at, updated_at) VALUES (?,?,?,?,?)');
        foreach ($template['items'] as $itemTitle) {
            $itemStmt->execute([$checklistId, $itemTitle, 'pending', $now, $now]);
        }
    }

    private function logAudit(PDO $pdo, array $payload): void
    {
        $stmt = $pdo->prepare('INSERT INTO audit_logs (actor_user_id, customer_id, lead_id, entity_type, entity_id, action, before_json, after_json, request_json, response_json, success, message, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $payload['actor_user_id'] ?? null,
            $payload['customer_id'] ?? null,
            $payload['lead_id'] ?? null,
            $payload['entity_type'] ?? 'lead',
            $payload['entity_id'] ?? null,
            $payload['action'] ?? '',
            isset($payload['before_json']) ? json_encode($payload['before_json'], JSON_UNESCAPED_UNICODE) : null,
            isset($payload['after_json']) ? json_encode($payload['after_json'], JSON_UNESCAPED_UNICODE) : null,
            isset($payload['request_json']) ? json_encode($payload['request_json'], JSON_UNESCAPED_UNICODE) : null,
            isset($payload['response_json']) ? json_encode($payload['response_json'], JSON_UNESCAPED_UNICODE) : null,
            $payload['success'] ?? 1,
            $payload['message'] ?? null,
            date('Y-m-d H:i:s'),
        ]);
    }

    public function index(): void
    {
        $this->ensureAuth();

        try {
            $pdo   = Database::connection();
            $leads = $pdo->query("SELECT l.*, e.full_name AS owner_name FROM leads l LEFT JOIN employees e ON e.id = l.owner_employee_id ORDER BY l.id DESC")?->fetchAll() ?: [];
            $employees = $this->fetchEmployees($pdo);
        } catch (PDOException $e) {
            View::renderError('خطا در بارگذاری لیست لیدها: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        View::render('leads/index', [
            'user'         => Auth::user(),
            'leads'        => $leads,
            'employees'    => $employees,
            'statusLabels' => $this->statusLabels,
            'templates'    => $this->templates(),
        ]);
    }

    public function show(): void
    {
        $this->ensureAuth();
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: /leads');
            return;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare('SELECT l.*, e.full_name AS owner_name FROM leads l LEFT JOIN employees e ON e.id = l.owner_employee_id WHERE l.id = ?');
            $stmt->execute([$id]);
            $lead = $stmt->fetch();
            if (!$lead) {
                header('Location: /leads');
                return;
            }

            $checklistsStmt = $pdo->prepare('SELECT * FROM lead_checklists WHERE lead_id = ? ORDER BY id ASC');
            $checklistsStmt->execute([$id]);
            $checklists = $checklistsStmt->fetchAll();
            if (empty($checklists)) {
                $this->createChecklist($pdo, $id, $lead['template_key'] ?: 'default');
                $checklistsStmt->execute([$id]);
                $checklists = $checklistsStmt->fetchAll();
            }

            $itemsStmt = $pdo->prepare('SELECT * FROM lead_checklist_items WHERE checklist_id = ? ORDER BY id ASC');
            foreach ($checklists as &$cl) {
                $itemsStmt->execute([$cl['id']]);
                $cl['items'] = $itemsStmt->fetchAll();
            }
            unset($cl);

            $notesStmt = $pdo->prepare('SELECT n.*, u.email AS user_email FROM lead_notes n LEFT JOIN users u ON u.id = n.user_id WHERE n.lead_id = ? ORDER BY n.id DESC');
            $notesStmt->execute([$id]);
            $notes = $notesStmt->fetchAll();

            $employees = $this->fetchEmployees($pdo);
        } catch (PDOException $e) {
            View::renderError('خطا در بارگذاری جزئیات لید: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        View::render('leads/show', [
            'user'         => Auth::user(),
            'lead'         => $lead,
            'checklists'   => $checklists,
            'notes'        => $notes,
            'employees'    => $employees,
            'statusLabels' => $this->statusLabels,
        ]);
    }

    public function create(): void
    {
        $this->ensureAuth();
        $name        = Str::beautifyLabel($_POST['name'] ?? '');
        $phone       = Str::digitsOnly($_POST['phone'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $status      = $this->allowedStatus($_POST['status'] ?? 'new');
        $ownerId     = (int)($_POST['owner_employee_id'] ?? 0) ?: null;
        $source      = Str::beautifyLabel($_POST['source'] ?? '');
        $templateKey = trim($_POST['template_key'] ?? 'default');
        $note        = trim($_POST['note'] ?? '');

        if ($name === '') {
            header('Location: /leads');
            return;
        }

        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare('INSERT INTO leads (name, phone, email, status, owner_employee_id, source, template_key, note, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$name, $phone, $email, $status, $ownerId, $source, $templateKey, $note, $now, $now]);
            $leadId = (int)$pdo->lastInsertId();

            $this->createChecklist($pdo, $leadId, $templateKey);

            if ($note !== '') {
                $noteStmt = $pdo->prepare('INSERT INTO lead_notes (lead_id, user_id, body, created_at) VALUES (?,?,?,?)');
                $noteStmt->execute([$leadId, Auth::user()['id'] ?? null, $note, $now]);
            }

            $this->logAudit($pdo, [
                'actor_user_id' => Auth::user()['id'] ?? null,
                'lead_id'       => $leadId,
                'entity_id'     => $leadId,
                'action'        => 'create_lead',
                'after_json'    => ['status' => $status, 'owner_employee_id' => $ownerId],
                'success'       => 1,
                'message'       => 'لید جدید ثبت شد',
            ]);

            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            View::renderError('خطا در ثبت لید: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        header('Location: /leads/show?id=' . $leadId);
    }

    public function assign(): void
    {
        $this->ensureAuth();
        $leadId  = (int)($_POST['lead_id'] ?? 0);
        $ownerId = (int)($_POST['owner_employee_id'] ?? 0) ?: null;
        if (!$leadId) {
            header('Location: /leads');
            return;
        }

        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT * FROM leads WHERE id = ?');
            $stmt->execute([$leadId]);
            $lead = $stmt->fetch();
            if (!$lead) {
                $pdo->rollBack();
                header('Location: /leads');
                return;
            }

            $update = $pdo->prepare('UPDATE leads SET owner_employee_id = ?, updated_at = ? WHERE id = ?');
            $update->execute([$ownerId, date('Y-m-d H:i:s'), $leadId]);

            $after = $lead;
            $after['owner_employee_id'] = $ownerId;
            $this->logAudit($pdo, [
                'actor_user_id' => Auth::user()['id'] ?? null,
                'lead_id'       => $leadId,
                'entity_id'     => $leadId,
                'action'        => 'assign_lead_owner',
                'before_json'   => $lead,
                'after_json'    => $after,
                'success'       => 1,
                'message'       => 'مالک لید تغییر کرد',
            ]);

            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            View::renderError('خطا در انتساب لید: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        header('Location: /leads/show?id=' . $leadId);
    }

    public function updateStatus(): void
    {
        $this->ensureAuth();
        $leadId = (int)($_POST['lead_id'] ?? 0);
        $status = $this->allowedStatus($_POST['status'] ?? 'new');
        if (!$leadId) {
            header('Location: /leads');
            return;
        }

        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT * FROM leads WHERE id = ?');
            $stmt->execute([$leadId]);
            $lead = $stmt->fetch();
            if (!$lead) {
                $pdo->rollBack();
                header('Location: /leads');
                return;
            }

            $update = $pdo->prepare('UPDATE leads SET status = ?, updated_at = ? WHERE id = ?');
            $update->execute([$status, date('Y-m-d H:i:s'), $leadId]);

            $after        = $lead;
            $after['status'] = $status;
            $this->logAudit($pdo, [
                'actor_user_id' => Auth::user()['id'] ?? null,
                'lead_id'       => $leadId,
                'entity_id'     => $leadId,
                'action'        => 'update_lead_status',
                'before_json'   => $lead,
                'after_json'    => $after,
                'success'       => 1,
                'message'       => 'وضعیت لید بروزرسانی شد',
            ]);

            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            View::renderError('خطا در بروزرسانی وضعیت لید: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        header('Location: /leads/show?id=' . $leadId);
    }

    public function addNote(): void
    {
        $this->ensureAuth();
        $leadId = (int)($_POST['lead_id'] ?? 0);
        $body   = trim($_POST['body'] ?? '');
        if (!$leadId || $body === '') {
            header('Location: /leads/show?id=' . $leadId);
            return;
        }

        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $leadStmt = $pdo->prepare('SELECT * FROM leads WHERE id = ?');
            $leadStmt->execute([$leadId]);
            $lead = $leadStmt->fetch();
            if (!$lead) {
                $pdo->rollBack();
                header('Location: /leads');
                return;
            }

            $stmt = $pdo->prepare('INSERT INTO lead_notes (lead_id, user_id, body, created_at) VALUES (?,?,?,?)');
            $stmt->execute([$leadId, Auth::user()['id'] ?? null, $body, date('Y-m-d H:i:s')]);

            $this->logAudit($pdo, [
                'actor_user_id' => Auth::user()['id'] ?? null,
                'lead_id'       => $leadId,
                'entity_id'     => $leadId,
                'action'        => 'add_lead_note',
                'after_json'    => ['note' => $body],
                'success'       => 1,
                'message'       => 'یادداشت لید ثبت شد',
            ]);

            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            View::renderError('خطا در ثبت یادداشت لید: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        header('Location: /leads/show?id=' . $leadId);
    }

    public function toggleChecklistItem(): void
    {
        $this->ensureAuth();
        $itemId = (int)($_POST['item_id'] ?? 0);
        if (!$itemId) {
            header('Location: /leads');
            return;
        }

        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT i.*, c.lead_id, l.name AS lead_name, l.owner_employee_id FROM lead_checklist_items i INNER JOIN lead_checklists c ON c.id = i.checklist_id INNER JOIN leads l ON l.id = c.lead_id WHERE i.id = ?');
            $stmt->execute([$itemId]);
            $item = $stmt->fetch();
            if (!$item) {
                $pdo->rollBack();
                header('Location: /leads');
                return;
            }

            $newStatus    = $item['status'] === 'done' ? 'pending' : 'done';
            $completedAt  = $newStatus === 'done' ? date('Y-m-d H:i:s') : null;
            $updatedAt    = date('Y-m-d H:i:s');
            $update = $pdo->prepare('UPDATE lead_checklist_items SET status = ?, completed_at = ?, updated_at = ? WHERE id = ?');
            $update->execute([$newStatus, $completedAt, $updatedAt, $itemId]);

            $after = $item;
            $after['status'] = $newStatus;
            $after['completed_at'] = $completedAt;
            $this->logAudit($pdo, [
                'actor_user_id' => Auth::user()['id'] ?? null,
                'lead_id'       => $item['lead_id'],
                'entity_id'     => $itemId,
                'action'        => 'toggle_lead_checklist_item',
                'before_json'   => $item,
                'after_json'    => $after,
                'success'       => 1,
                'message'       => 'وضعیت چک‌لیست لید تغییر کرد',
            ]);

            $notificationUser = Auth::user()['id'] ?? null;
            if ($notificationUser) {
                $service = new NotificationService();
                $service->create(
                    $notificationUser,
                    null,
                    'lead.checklist',
                    'info',
                    'بروزرسانی چک‌لیست لید',
                    'وضعیت یک آیتم برای لید ' . ($item['lead_name'] ?? '') . ' تغییر کرد',
                    [
                        'lead_id'  => $item['lead_id'],
                        'item_id'  => $itemId,
                        'status'   => $newStatus,
                    ]
                );
            }

            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            View::renderError('خطا در بروزرسانی چک‌لیست لید: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        header('Location: /leads/show?id=' . ((int)$item['lead_id']));
    }

    public function convert(): void
    {
        $this->ensureAuth();
        $leadId = (int)($_POST['lead_id'] ?? 0);
        if (!$leadId) {
            header('Location: /leads');
            return;
        }

        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT * FROM leads WHERE id = ?');
            $stmt->execute([$leadId]);
            $lead = $stmt->fetch();
            if (!$lead) {
                $pdo->rollBack();
                header('Location: /leads');
                return;
            }

            if (!empty($lead['converted_customer_id'])) {
                $pdo->rollBack();
                header('Location: /customers/profile?id=' . (int)$lead['converted_customer_id']);
                return;
            }

            $now = date('Y-m-d H:i:s');
            $customerStmt = $pdo->prepare('INSERT INTO customers (name, phone, email, note, created_at, updated_at) VALUES (?,?,?,?,?,?)');
            $customerStmt->execute([$lead['name'], $lead['phone'], $lead['email'], $lead['note'], $now, $now]);
            $customerId = (int)$pdo->lastInsertId();

            $updateLead = $pdo->prepare('UPDATE leads SET converted_customer_id = ?, status = ?, converted_at = ?, updated_at = ? WHERE id = ?');
            $updateLead->execute([$customerId, 'converted', $now, $now, $leadId]);

            $pdo->prepare('UPDATE audit_logs SET customer_id = ? WHERE lead_id = ?')->execute([$customerId, $leadId]);

            $this->logAudit($pdo, [
                'actor_user_id' => Auth::user()['id'] ?? null,
                'customer_id'   => $customerId,
                'lead_id'       => $leadId,
                'entity_id'     => $leadId,
                'action'        => 'convert_to_customer',
                'before_json'   => $lead,
                'after_json'    => ['customer_id' => $customerId],
                'success'       => 1,
                'message'       => 'لید به مشتری تبدیل شد',
            ]);

            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            View::renderError('خطا در تبدیل لید به مشتری: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        header('Location: /customers/profile?id=' . $customerId);
    }
}
