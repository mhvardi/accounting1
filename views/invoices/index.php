<?php
/** @var array $invoices */
/** @var array $customers */
/** @var array $contracts */
use App\Core\Date;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">🧾</span>
    <span>فاکتورها</span>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">فاکتور جدید</div>
        <div class="hint">خطوط آیتم را به شکل «عنوان | مبلغ» وارد کنید. شماره شاخص بر اساس سال شمسی به صورت خودکار ساخته می‌شود.</div>
    </div>
    <form method="post" action="/invoices/create">
        <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;">
            <div class="form-field">
                <label class="form-label">عنوان</label>
                <input type="text" name="title" class="form-input" placeholder="مثلاً فاکتور خدمات پشتیبانی" required>
            </div>
            <div class="form-field">
                <label class="form-label">مشتری</label>
                <select name="customer_id" class="form-select">
                    <option value="">انتخاب مشتری</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">قرارداد (اختیاری)</label>
                <select name="contract_id" class="form-select">
                    <option value="">بدون قرارداد</option>
                    <?php foreach ($contracts as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">مبلغ کل (ریال)</label>
                <input type="text" name="amount" class="form-input money-input" placeholder="مثلاً 25000000">
            </div>
            <div class="form-field">
                <label class="form-label">تخفیف (ریال)</label>
                <input type="text" name="discount_amount" class="form-input money-input" value="0">
            </div>
            <div class="form-field">
                <label class="form-label">پرداخت شده (ریال)</label>
                <input type="text" name="paid_amount" class="form-input money-input" value="0">
            </div>
            <div class="form-field">
                <label class="form-label">تاریخ صدور</label>
                <input type="text" name="issue_date" class="form-input jalali-picker" value="<?php echo Date::j('Y/m/d'); ?>">
            </div>
            <div class="form-field">
                <label class="form-label">سررسید</label>
                <input type="text" name="due_date" class="form-input jalali-picker" placeholder="مثلاً 1404/01/15">
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
                <label class="form-label">آیتم‌ها (عنوان | مبلغ)</label>
                <textarea name="items" class="form-input" rows="3" placeholder="هاست یک‌ساله | 12000000&#10;دامنه دات آی‌آر | 3000000"></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">ثبت فاکتور</button>
    </form>
</div>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">لیست فاکتورها</div>
        <div class="hint">نمایش جمع تخفیف و ارتباط با پرداخت‌ها برای هر فاکتور.</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>کد</th>
                <th>عنوان</th>
                <th>مشتری</th>
                <th>مبلغ کل</th>
                <th>تخفیف</th>
                <th>قابل پرداخت</th>
                <th>پرداخت‌شده</th>
                <th>وضعیت</th>
                <th>سررسید</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($invoices)): ?>
                <tr><td colspan="10">فاکتوری ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($invoices as $inv): ?>
                    <?php
                    $paid = max((int)$inv['paid_amount'], (int)($inv['paid_from_payments'] ?? 0));
                    $balance = max(0, ((int)$inv['payable_amount']) - $paid);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($inv['indicator_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($inv['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($inv['customer_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo number_format((int)$inv['gross_amount']); ?></td>
                        <td><?php echo number_format((int)$inv['discount_amount']); ?></td>
                        <td><?php echo number_format((int)$inv['payable_amount']); ?></td>
                        <td><?php echo number_format($paid); ?></td>
                        <td><?php echo htmlspecialchars($inv['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $inv['due_date'] ? Date::jDate($inv['due_date']) : '—'; ?></td>
                        <td style="min-width:200px;">
                            <a class="btn btn-outline" href="/invoices/show?id=<?php echo (int)$inv['id']; ?>" target="_blank">نمایش</a>
                            <a class="btn btn-outline" href="/invoices/print?id=<?php echo (int)$inv['id']; ?>" target="_blank">چاپ</a>
                            <a class="btn btn-outline" href="/invoices/print?id=<?php echo (int)$inv['id']; ?>&download=1">دانلود</a>
                            <button class="btn btn-outline" data-inline-edit-toggle="inv-<?php echo (int)$inv['id']; ?>">ویرایش</button>
                            <a class="btn btn-outline" style="color:#b91c1c;" href="/invoices/delete?id=<?php echo (int)$inv['id']; ?>" onclick="return confirm('حذف فاکتور؟');">حذف</a>
                            <div class="inline-edit" data-inline-edit-box="inv-<?php echo (int)$inv['id']; ?>">
                                <form method="post" action="/invoices/edit">
                                    <input type="hidden" name="id" value="<?php echo (int)$inv['id']; ?>">
                                    <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;">
                                        <div class="form-field">
                                            <label class="form-label">عنوان</label>
                                            <input type="text" name="title" class="form-input" value="<?php echo htmlspecialchars($inv['title'], ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">مشتری</label>
                                            <select name="customer_id" class="form-select">
                                                <option value="">انتخاب</option>
                                                <?php foreach ($customers as $c): ?>
                                                    <option value="<?php echo (int)$c['id']; ?>" <?php echo ($inv['customer_id']==$c['id'])?'selected':''; ?>><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">قرارداد</label>
                                            <select name="contract_id" class="form-select">
                                                <option value="">بدون قرارداد</option>
                                                <?php foreach ($contracts as $c): ?>
                                                    <option value="<?php echo (int)$c['id']; ?>" <?php echo ($inv['contract_id']==$c['id'])?'selected':''; ?>><?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">مبلغ کل</label>
                                            <input type="text" name="amount" class="form-input money-input" value="<?php echo number_format((int)$inv['gross_amount']); ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">تخفیف</label>
                                            <input type="text" name="discount_amount" class="form-input money-input" value="<?php echo number_format((int)$inv['discount_amount']); ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">پرداخت شده</label>
                                            <input type="text" name="paid_amount" class="form-input money-input" value="<?php echo number_format((int)$inv['paid_amount']); ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">وضعیت</label>
                                            <select name="status" class="form-select">
                                                <option value="unpaid" <?php echo $inv['status']==='unpaid'?'selected':''; ?>>پرداخت نشده</option>
                                                <option value="paid" <?php echo $inv['status']==='paid'?'selected':''; ?>>پرداخت شده</option>
                                                <option value="cancelled" <?php echo $inv['status']==='cancelled'?'selected':''; ?>>لغو</option>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">تاریخ صدور</label>
                                            <input type="text" name="issue_date" class="form-input jalali-picker" value="<?php echo $inv['issue_date'] ? Date::jDate($inv['issue_date']) : ''; ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">سررسید</label>
                                            <input type="text" name="due_date" class="form-input jalali-picker" value="<?php echo $inv['due_date'] ? Date::jDate($inv['due_date']) : ''; ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">یادداشت</label>
                                            <input type="text" name="note" class="form-input" value="<?php echo htmlspecialchars($inv['note'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="form-field" style="grid-column:1/-1;">
                                            <label class="form-label">آیتم‌ها</label>
                                            <textarea name="items" class="form-input" rows="2"><?php
                                                $items = json_decode($inv['items_json'] ?? '', true) ?: [];
                                                foreach ($items as $itm) {
                                                    echo htmlspecialchars(($itm['title'] ?? '') . ' | ' . ($itm['amount'] ?? 0), ENT_QUOTES, 'UTF-8') . "\n";
                                                }
                                            ?></textarea>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="margin-top:6px;">ثبت تغییرات</button>
                                    <div class="hint" style="margin-top:4px;">باقیمانده: <?php echo number_format($balance); ?> ریال</div>
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
