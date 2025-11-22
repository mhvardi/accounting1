<?php
/** @var array $contracts */
/** @var string|null $error */
$error = $error ?? null;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">➕</span>
    <span>ثبت پرداخت جدید</span>
</div>

<div class="card-soft" style="max-width:720px;">
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
    <form method="post" action="/payments/create">
        <div class="grid" style="grid-template-columns: repeat(2,minmax(0,1fr));gap:10px;">
            <div class="form-field">
                <label class="form-label">قرارداد (اختیاری)</label>
                <select name="contract_id" class="form-select">
                    <option value="">بدون اتصال به قرارداد</option>
                    <?php foreach ($contracts as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>">
                            <?php echo htmlspecialchars($c['customer_name'] . ' - ' . $c['title'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">مبلغ پرداخت (تومان)</label>
                <input type="number" name="amount" class="form-input" required min="0" step="10000">
            </div>
            <div class="form-field">
                <label class="form-label">تاریخ پرداخت (شمسی)</label>
                <input type="text" name="pay_date" class="form-input jalali-picker" placeholder="مثلاً 1404/08/28" required>
            </div>
            <div class="form-field">
                <label class="form-label">روش پرداخت</label>
                <input type="text" name="method" class="form-input" placeholder="مثلاً کارت به کارت، درگاه، نقدی">
            </div>
            <div class="form-field">
                <label class="form-label">وضعیت</label>
                <select name="status" class="form-select">
                    <option value="paid">پرداخت شده</option>
                    <option value="pending">در انتظار</option>
                    <option value="refunded">عودت شده</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">منبع</label>
                <select name="external_source" class="form-select">
                    <option value="manual">داخلی / دستی</option>
                    <option value="whmcs">WHMCS</option>
                    <option value="bankshomareh">بانک شماره</option>
                    <option value="starplan">استارپلن</option>
                </select>
            </div>
        </div>
        <div class="form-field" style="margin-top:8px;">
            <label class="form-label">کد پیگیری یا توضیحات</label>
            <input type="text" name="external_ref" class="form-input">
        </div>
        <div style="margin-top:10px;">
            <button type="submit" class="btn btn-primary">ثبت پرداخت</button>
        </div>
    </form>
</div>
