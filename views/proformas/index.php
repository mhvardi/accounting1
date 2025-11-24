<?php
/** @var array $proformas */
/** @var array $customers */
/** @var array $contracts */
use App\Core\Date;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">📑</span>
    <span>پیش‌فاکتورها</span>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">ثبت پیش‌فاکتور</div>
        <div class="hint">شماره‌ها بر اساس سال شمسی تولید می‌شوند و بعداً قابل تبدیل به فاکتور هستند.</div>
    </div>
    <form method="post" action="/proformas/create">
        <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;">
            <div class="form-field">
                <label class="form-label">عنوان</label>
                <input type="text" name="title" class="form-input" required>
            </div>
            <div class="form-field">
                <label class="form-label">مشتری</label>
                <select name="customer_id" class="form-select">
                    <option value="">انتخاب</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">قرارداد</label>
                <select name="contract_id" class="form-select">
                    <option value="">بدون قرارداد</option>
                    <?php foreach ($contracts as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">مبلغ (ریال)</label>
                <input type="text" name="amount" class="form-input money-input" placeholder="مثلاً 18000000">
            </div>
            <div class="form-field">
                <label class="form-label">تخفیف (ریال)</label>
                <input type="text" name="discount_amount" class="form-input money-input" value="0">
            </div>
            <div class="form-field">
                <label class="form-label">تاریخ صدور</label>
                <input type="text" name="issue_date" class="form-input jalali-picker" value="<?php echo Date::j('Y/m/d'); ?>">
            </div>
            <div class="form-field">
                <label class="form-label">سررسید</label>
                <input type="text" name="due_date" class="form-input jalali-picker">
            </div>
            <div class="form-field">
                <label class="form-label">وضعیت</label>
                <select name="status" class="form-select">
                    <option value="unpaid">پرداخت نشده</option>
                    <option value="paid">پرداخت شده</option>
                    <option value="cancelled">لغو</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">یادداشت</label>
                <input type="text" name="note" class="form-input">
            </div>
            <div class="form-field" style="grid-column:1/-1;">
                <label class="form-label">آیتم‌ها</label>
                <textarea name="items" class="form-input" rows="3" placeholder="بکاپ ماهانه | 4000000"></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">ذخیره پیش‌فاکتور</button>
    </form>
</div>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">پیش‌فاکتورهای اخیر</div>
        <div class="hint">قابلیت ویرایش، حذف و تبدیل با ارسال پیامک.</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>کد</th>
                <th>عنوان</th>
                <th>مشتری</th>
                <th>مبلغ</th>
                <th>تخفیف</th>
                <th>قابل پرداخت</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($proformas)): ?>
                <tr><td colspan="8">پیش‌فاکتوری ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($proformas as $pf): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pf['indicator_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($pf['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($pf['customer_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo number_format((int)$pf['gross_amount']); ?></td>
                        <td><?php echo number_format((int)$pf['discount_amount']); ?></td>
                        <td><?php echo number_format((int)$pf['payable_amount']); ?></td>
                        <td><?php echo htmlspecialchars($pf['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td style="min-width:200px;">
                            <button class="btn btn-outline" data-inline-edit-toggle="pf-<?php echo (int)$pf['id']; ?>">ویرایش</button>
                            <a class="btn btn-outline" style="color:#b91c1c;" href="/proformas/delete?id=<?php echo (int)$pf['id']; ?>" onclick="return confirm('حذف پیش‌فاکتور؟');">حذف</a>
                            <form method="post" action="/proformas/convert" style="margin-top:6px;display:flex;gap:6px;align-items:center;">
                                <input type="hidden" name="id" value="<?php echo (int)$pf['id']; ?>">
                                <label style="display:flex;gap:4px;align-items:center;font-size:12px;">
                                    <input type="checkbox" name="send_sms" value="1"> ارسال پیامک پس از تبدیل
                                </label>
                                <button type="submit" class="btn btn-primary">تبدیل به فاکتور</button>
                            </form>
                            <div class="inline-edit" data-inline-edit-box="pf-<?php echo (int)$pf['id']; ?>">
                                <form method="post" action="/proformas/edit">
                                    <input type="hidden" name="id" value="<?php echo (int)$pf['id']; ?>">
                                    <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;">
                                        <div class="form-field">
                                            <label class="form-label">عنوان</label>
                                            <input type="text" name="title" class="form-input" value="<?php echo htmlspecialchars($pf['title'], ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">مشتری</label>
                                            <select name="customer_id" class="form-select">
                                                <option value="">انتخاب</option>
                                                <?php foreach ($customers as $c): ?>
                                                    <option value="<?php echo (int)$c['id']; ?>" <?php echo ($pf['customer_id']==$c['id'])?'selected':''; ?>><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">قرارداد</label>
                                            <select name="contract_id" class="form-select">
                                                <option value="">بدون قرارداد</option>
                                                <?php foreach ($contracts as $c): ?>
                                                    <option value="<?php echo (int)$c['id']; ?>" <?php echo ($pf['contract_id']==$c['id'])?'selected':''; ?>><?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">مبلغ</label>
                                            <input type="text" name="amount" class="form-input money-input" value="<?php echo number_format((int)$pf['gross_amount']); ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">تخفیف</label>
                                            <input type="text" name="discount_amount" class="form-input money-input" value="<?php echo number_format((int)$pf['discount_amount']); ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">وضعیت</label>
                                            <select name="status" class="form-select">
                                                <option value="unpaid" <?php echo $pf['status']==='unpaid'?'selected':''; ?>>پرداخت نشده</option>
                                                <option value="paid" <?php echo $pf['status']==='paid'?'selected':''; ?>>پرداخت شده</option>
                                                <option value="cancelled" <?php echo $pf['status']==='cancelled'?'selected':''; ?>>لغو</option>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">تاریخ صدور</label>
                                            <input type="text" name="issue_date" class="form-input jalali-picker" value="<?php echo $pf['issue_date'] ? Date::jDate($pf['issue_date']) : ''; ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">سررسید</label>
                                            <input type="text" name="due_date" class="form-input jalali-picker" value="<?php echo $pf['due_date'] ? Date::jDate($pf['due_date']) : ''; ?>">
                                        </div>
                                        <div class="form-field" style="grid-column:1/-1;">
                                            <label class="form-label">یادداشت</label>
                                            <input type="text" name="note" class="form-input" value="<?php echo htmlspecialchars($pf['note'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="form-field" style="grid-column:1/-1;">
                                            <label class="form-label">آیتم‌ها</label>
                                            <textarea name="items" class="form-input" rows="2"><?php
                                                $items = json_decode($pf['items_json'] ?? '', true) ?: [];
                                                foreach ($items as $itm) {
                                                    echo htmlspecialchars(($itm['title'] ?? '') . ' | ' . ($itm['amount'] ?? 0), ENT_QUOTES, 'UTF-8') . "\n";
                                                }
                                            ?></textarea>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="margin-top:6px;">ذخیره تغییرات</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
