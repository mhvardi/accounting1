<?php
/** @var array $leads */
/** @var array $employees */
/** @var array $statusLabels */
/** @var array $templates */
use App\Core\Date;
use App\Core\Str;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">🧲</span>
    <span>مدیریت لیدها</span>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">افزودن لید جدید</div>
    </div>
    <form method="post" action="/leads/create">
        <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;">
            <div class="form-field">
                <label class="form-label">نام لید</label>
                <input type="text" name="name" class="form-input" required>
            </div>
            <div class="form-field">
                <label class="form-label">موبایل</label>
                <input type="text" name="phone" class="form-input">
            </div>
            <div class="form-field">
                <label class="form-label">ایمیل</label>
                <input type="email" name="email" class="form-input">
            </div>
            <div class="form-field">
                <label class="form-label">منبع/کمپین</label>
                <input type="text" name="source" class="form-input" placeholder="مثلا تبلیغات گوگل">
            </div>
        </div>
        <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:10px;">
            <div class="form-field">
                <label class="form-label">وضعیت اولیه</label>
                <select name="status" class="form-input">
                    <?php foreach ($statusLabels as $key => $label): ?>
                        <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">مالک</label>
                <select name="owner_employee_id" class="form-input">
                    <option value="">—</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo (int)$emp['id']; ?>"><?php echo htmlspecialchars($emp['full_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">قالب چک‌لیست</label>
                <select name="template_key" class="form-input">
                    <?php foreach ($templates as $key => $template): ?>
                        <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($template['title'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">یادداشت کوتاه</label>
                <input type="text" name="note" class="form-input" placeholder="چالش یا درخواست مشتری">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">ثبت لید</button>
    </form>
</div>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">لیست لیدها</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>نام</th>
                <th>راه ارتباطی</th>
                <th>وضعیت</th>
                <th>مالک</th>
                <th>منبع</th>
                <th>تاریخ ایجاد</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($leads)): ?>
                <tr><td colspan="8">لیدی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td><?php echo (int)$lead['id']; ?></td>
                        <td><?php echo htmlspecialchars(Str::beautifyLabel($lead['name']), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <div><?php echo htmlspecialchars($lead['phone'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div style="color:#6b7280;font-size:12px;"> <?php echo htmlspecialchars($lead['email'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></div>
                        </td>
                        <td><span class="badge"><?php echo htmlspecialchars($statusLabels[$lead['status']] ?? $lead['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><?php echo htmlspecialchars($lead['owner_name'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($lead['source'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo !empty($lead['created_at']) ? Date::jDate($lead['created_at']) : ''; ?></td>
                        <td>
                            <a href="/leads/show?id=<?php echo (int)$lead['id']; ?>" class="btn btn-primary" style="padding-inline:8px;">نمایش</a>
                            <?php if (!empty($lead['converted_customer_id'])): ?>
                                <a href="/customers/profile?id=<?php echo (int)$lead['converted_customer_id']; ?>" class="btn btn-outline" style="padding-inline:8px;">نمایه مشتری</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
