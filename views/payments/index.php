<?php
/** @var array $payments */
/** @var array $contracts */
use App\Core\Date;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">💳</span>
    <span>پرداخت‌ها</span>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">ثبت پرداخت جدید</div>
    </div>
    <form method="post" action="/payments/create">
        <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;">
            <div class="form-field">
                <label class="form-label">قرارداد (اختیاری)</label>
                <select name="contract_id" class="form-select select-search">
                    <option value="">بدون قرارداد</option>
                    <?php foreach ($contracts as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">مبلغ (تومان)</label>
                <input type="text" name="amount" class="form-input money-input" value="0" required>
            </div>
            <div class="form-field">
                <label class="form-label">تاریخ پرداخت (شمسی)</label>
                <input type="text" name="pay_date" class="form-input jalali-picker" placeholder="مثلاً 1403/08/15" value="<?php echo Date::j('Y/m/d'); ?>">
            </div>
            <div class="form-field">
                <label class="form-label">روش پرداخت</label>
                <input type="text" name="method" class="form-input" placeholder="کارت به کارت، درگاه، نقدی و ...">
            </div>
            <div class="form-field">
                <label class="form-label">وضعیت</label>
                <select name="status" class="form-select">
                    <option value="paid">پرداخت شده</option>
                    <option value="pending">در انتظار</option>
                    <option value="refunded">مرجوع/بازگشت</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">توضیحات</label>
                <input type="text" name="note" class="form-input">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">ثبت پرداخت</button>
    </form>
</div>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">لیست پرداخت‌ها</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>قرارداد</th>
                <th>مبلغ (تومان)</th>
                <th>تاریخ پرداخت</th>
                <th>روش</th>
                <th>وضعیت</th>
                <th>توضیحات</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="8">هنوز پرداختی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?php echo (int)$p['id']; ?></td>
                        <td><?php echo htmlspecialchars($p['contract_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo number_format((int)$p['amount']); ?></td>
                        <td><?php echo $p['pay_date'] ? Date::jDate($p['pay_date']) : ''; ?></td>
                        <td><?php echo htmlspecialchars($p['method'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php
                            $map = ['paid'=>'پرداخت شده','pending'=>'در انتظار','refunded'=>'مرجوع شده'];
                            echo $map[$p['status']] ?? $p['status'];
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($p['note'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <button class="btn btn-outline" data-inline-edit-toggle="payment-<?php echo (int)$p['id']; ?>" style="padding-inline:8px;">ویرایش</button>
                            <a href="/payments/delete?id=<?php echo (int)$p['id']; ?>" class="btn btn-outline" style="padding-inline:8px;color:#b91c1c;margin-top:4px;"
                               onclick="return confirm('این پرداخت حذف شود؟');">حذف</a>
                            <div class="inline-edit" data-inline-edit-box="payment-<?php echo (int)$p['id']; ?>">
                                <form method="post" action="/payments/edit">
                                    <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                    <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:6px;">
                                        <div class="form-field">
                                            <label class="form-label">قرارداد</label>
                                            <select name="contract_id" class="form-select select-search">
                                                <option value="">بدون قرارداد</option>
                                                <?php foreach ($contracts as $c): ?>
                                                    <option value="<?php echo (int)$c['id']; ?>"
                                                        <?php echo ($p['contract_id']==$c['id'])?'selected':''; ?>>
                                                        <?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">مبلغ (تومان)</label>
                                            <input type="text" name="amount" class="form-input money-input" value="<?php echo number_format((int)$p['amount']); ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">تاریخ پرداخت (شمسی)</label>
                                            <input type="text" name="pay_date" class="form-input jalali-picker"
                                                   value="<?php echo $p['pay_date'] ? Date::jDate($p['pay_date']) : ''; ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">روش</label>
                                            <input type="text" name="method" class="form-input" value="<?php echo htmlspecialchars($p['method'], ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">وضعیت</label>
                                            <select name="status" class="form-select">
                                                <option value="paid" <?php echo $p['status']==='paid'?'selected':''; ?>>پرداخت شده</option>
                                                <option value="pending" <?php echo $p['status']==='pending'?'selected':''; ?>>در انتظار</option>
                                                <option value="refunded" <?php echo $p['status']==='refunded'?'selected':''; ?>>مرجوع/بازگشت</option>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">توضیحات</label>
                                            <input type="text" name="note" class="form-input" value="<?php echo htmlspecialchars($p['note'], ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="margin-top:4px;">ثبت تغییرات</button>
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
