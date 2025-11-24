<?php
/** @var array $invoice */
/** @var array $payments */
use App\Core\Date;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">🧾</span>
    <span>جزئیات فاکتور <?php echo htmlspecialchars($invoice['indicator_code'], ENT_QUOTES, 'UTF-8'); ?></span>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">اطلاعات فاکتور</div>
        <div class="hint">تخفیف و پرداخت‌ها در محاسبه مانده لحاظ شده است.</div>
    </div>
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;">
        <div class="chip">مشتری: <?php echo htmlspecialchars($invoice['customer_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="chip">عنوان: <?php echo htmlspecialchars($invoice['title'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="chip">شماره: <?php echo htmlspecialchars($invoice['indicator_code'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="chip">مبلغ کل: <?php echo number_format((int)$invoice['gross_amount']); ?> ریال</div>
        <div class="chip">تخفیف: <?php echo number_format((int)$invoice['discount_amount']); ?> ریال</div>
        <div class="chip">قابل پرداخت: <?php echo number_format((int)$invoice['payable_amount']); ?> ریال</div>
        <div class="chip">پرداخت شده: <?php echo number_format((int)$invoice['paid_amount']); ?> ریال</div>
        <div class="chip">وضعیت: <?php echo htmlspecialchars($invoice['status'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="chip">سررسید: <?php echo $invoice['due_date'] ? Date::jDate($invoice['due_date']) : '—'; ?></div>
    </div>
    <?php if (!empty($invoice['note'])): ?>
        <div class="alert" style="margin-top:8px;">یادداشت: <?php echo htmlspecialchars($invoice['note'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">آیتم‌ها</div>
        <div class="hint">خطوط مربوط به این فاکتور</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead><tr><th>#</th><th>شرح</th><th>مبلغ</th></tr></thead>
            <tbody>
            <?php if (empty($invoice['items'])): ?>
                <tr><td colspan="3">آیتمی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php $i=1; foreach ($invoice['items'] as $item): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo number_format((int)($item['amount'] ?? 0)); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">پرداخت‌های مرتبط</div>
        <div class="hint">پرداخت‌های متصل به فاکتور یا قرارداد مرتبط نمایش داده می‌شوند.</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead><tr><th>#</th><th>مبلغ</th><th>تاریخ</th><th>روش</th><th>وضعیت</th></tr></thead>
            <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="5">پرداختی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?php echo (int)$p['id']; ?></td>
                        <td><?php echo number_format((int)$p['amount']); ?></td>
                        <td><?php echo $p['pay_date'] ? Date::jDate($p['pay_date']) : ($p['paid_at'] ? Date::jDate($p['paid_at']) : ''); ?></td>
                        <td><?php echo htmlspecialchars($p['method'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($p['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
