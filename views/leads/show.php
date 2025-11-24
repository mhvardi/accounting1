<?php
/** @var array $lead */
/** @var array $checklists */
/** @var array $notes */
/** @var array $employees */
/** @var array $statusLabels */
use App\Core\Date;
use App\Core\Str;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">🧲</span>
    <span>جزییات لید: <?php echo htmlspecialchars($lead['name'], ENT_QUOTES, 'UTF-8'); ?></span>
</div>

<div class="grid" style="grid-template-columns:2fr 1fr;gap:12px;">
    <div class="card-soft">
        <div class="card-header" style="justify-content: space-between; align-items: center;">
            <div class="card-title">اطلاعات لید</div>
            <span class="badge"><?php echo htmlspecialchars($statusLabels[$lead['status']] ?? $lead['status'], ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="grid" style="grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;">
            <div>
                <div class="form-label">موبایل</div>
                <div><?php echo htmlspecialchars($lead['phone'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div>
                <div class="form-label">ایمیل</div>
                <div><?php echo htmlspecialchars($lead['email'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div>
                <div class="form-label">منبع/کمپین</div>
                <div><?php echo htmlspecialchars($lead['source'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div>
                <div class="form-label">مالک فعلی</div>
                <div><?php echo htmlspecialchars($lead['owner_name'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div>
                <div class="form-label">تاریخ ایجاد</div>
                <div><?php echo !empty($lead['created_at']) ? Date::jDate($lead['created_at']) : '—'; ?></div>
            </div>
            <div>
                <div class="form-label">آخرین بروزرسانی</div>
                <div><?php echo !empty($lead['updated_at']) ? Date::jDate($lead['updated_at']) : '—'; ?></div>
            </div>
        </div>
        <?php if (!empty($lead['note'])): ?>
            <div class="alert" style="margin-top:12px;">
                <div class="form-label">یادداشت</div>
                <div><?php echo nl2br(htmlspecialchars($lead['note'], ENT_QUOTES, 'UTF-8')); ?></div>
            </div>
        <?php endif; ?>
        <div class="grid" style="grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:12px;">
            <form method="post" action="/leads/update-status" class="card-soft" style="padding:12px;">
                <div class="form-field">
                    <label class="form-label">تغییر وضعیت</label>
                    <select name="status" class="form-input">
                        <?php foreach ($statusLabels as $key => $label): ?>
                            <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $lead['status'] === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="lead_id" value="<?php echo (int)$lead['id']; ?>">
                <button type="submit" class="btn btn-primary" style="margin-top:8px;">ثبت وضعیت</button>
            </form>
            <form method="post" action="/leads/assign" class="card-soft" style="padding:12px;">
                <div class="form-field">
                    <label class="form-label">تغییر مالک</label>
                    <select name="owner_employee_id" class="form-input">
                        <option value="">—</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo (int)$emp['id']; ?>" <?php echo ((int)$lead['owner_employee_id'] === (int)$emp['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($emp['full_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="lead_id" value="<?php echo (int)$lead['id']; ?>">
                <button type="submit" class="btn btn-outline" style="margin-top:8px;">انتصاب</button>
            </form>
        </div>
        <div style="margin-top:12px;display:flex;gap:10px;align-items:center;">
            <?php if (!empty($lead['converted_customer_id'])): ?>
                <a href="/customers/profile?id=<?php echo (int)$lead['converted_customer_id']; ?>" class="btn btn-primary">مشاهده مشتری</a>
            <?php else: ?>
                <form method="post" action="/leads/convert">
                    <input type="hidden" name="lead_id" value="<?php echo (int)$lead['id']; ?>">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('این لید به مشتری تبدیل شود؟');">تبدیل به مشتری</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-soft">
        <div class="card-header">
            <div class="card-title">یادداشت‌ها</div>
        </div>
        <form method="post" action="/leads/notes/add">
            <input type="hidden" name="lead_id" value="<?php echo (int)$lead['id']; ?>">
            <div class="form-field">
                <label class="form-label">یادداشت جدید</label>
                <textarea name="body" class="form-input" rows="3" placeholder="پیگیری یا مکاتبات مهم"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:6px;">ثبت یادداشت</button>
        </form>
        <div style="margin-top:10px;display:flex;flex-direction:column;gap:8px;">
            <?php if (empty($notes)): ?>
                <div style="color:#6b7280;">یادداشتی ثبت نشده است.</div>
            <?php else: ?>
                <?php foreach ($notes as $note): ?>
                    <div class="card-soft" style="border:1px solid #e5e7eb;padding:8px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <div style="color:#6b7280;font-size:12px;">کاربر: <?php echo htmlspecialchars($note['user_email'] ?: 'ناشناس', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div style="color:#6b7280;font-size:12px;"><?php echo !empty($note['created_at']) ? Date::jDate($note['created_at']) : ''; ?></div>
                        </div>
                        <div style="margin-top:4px;"><?php echo nl2br(htmlspecialchars($note['body'], ENT_QUOTES, 'UTF-8')); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card-soft" style="margin-top:12px;">
    <div class="card-header">
        <div class="card-title">چک‌لیست‌ها</div>
        <span class="badge">قالب: <?php echo htmlspecialchars($lead['template_key'], ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:10px;">
        <?php foreach ($checklists as $checklist): ?>
            <div class="card-soft" style="border:1px solid #e5e7eb;padding:10px;">
                <div class="card-title" style="margin-bottom:6px;"><?php echo htmlspecialchars($checklist['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                <ul class="checklist" style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:6px;">
                    <?php foreach ($checklist['items'] as $item): ?>
                        <li style="display:flex;align-items:center;gap:8px;">
                            <form method="post" action="/leads/checklist/toggle">
                                <input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
                                <button type="submit" class="btn btn-outline" style="padding:4px 8px;">
                                    <?php echo $item['status'] === 'done' ? '✅' : '⬜️'; ?>
                                </button>
                            </form>
                            <div>
                                <div><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div style="color:#6b7280;font-size:12px;">
                                    وضعیت: <?php echo htmlspecialchars($item['status'] === 'done' ? 'انجام شد' : 'در انتظار', ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (!empty($item['completed_at'])): ?> | <?php echo Date::jDate($item['completed_at']); ?><?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
</div>
