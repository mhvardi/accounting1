<?php
/** @var array $invoice */
/** @var array $payments */
use App\Core\Date;
?>
<div class="card-soft" style="max-width:900px;margin:0 auto;">
    <div class="card-header">
        <div class="card-title">فاکتور <?php echo htmlspecialchars($invoice['indicator_code'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="hint">تاریخ: <?php echo $invoice['issue_date'] ? Date::jDate($invoice['issue_date']) : Date::j('Y/m/d'); ?></div>
    </div>
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:8px;">
        <div class="chip">مشتری: <?php echo htmlspecialchars($invoice['customer_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="chip">شماره شاخص: <?php echo htmlspecialchars($invoice['indicator_code'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="chip">وضعیت: <?php echo htmlspecialchars($invoice['status'], ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="chip">سررسید: <?php echo $invoice['due_date'] ? Date::jDate($invoice['due_date']) : '—'; ?></div>
    </div>

    <div style="overflow-x:auto;margin-top:10px;">
        <table class="table">
            <thead><tr><th>#</th><th>شرح</th><th>مبلغ (ریال)</th></tr></thead>
            <tbody>
            <?php $i=1; $total=0; foreach ($invoice['items'] as $item): $total += (int)($item['amount'] ?? 0); ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo number_format((int)($item['amount'] ?? 0)); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><th colspan="2">جمع کل</th><th><?php echo number_format((int)$invoice['gross_amount']); ?></th></tr>
                <tr><th colspan="2">تخفیف</th><th><?php echo number_format((int)$invoice['discount_amount']); ?></th></tr>
                <tr><th colspan="2">مبلغ قابل پرداخت</th><th><?php echo number_format((int)$invoice['payable_amount']); ?></th></tr>
                <tr><th colspan="2">پرداخت شده</th><th><?php echo number_format((int)$invoice['paid_amount']); ?></th></tr>
            </tfoot>
        </table>
    </div>

    <div class="hint" style="margin-top:8px;">مانده: <?php echo number_format(max(0, ((int)$invoice['payable_amount']) - ((int)$invoice['paid_amount']))); ?> ریال</div>

    <?php if (!empty($payments)): ?>
        <div style="margin-top:12px;">
            <div class="card-title" style="font-size:14px;margin-bottom:6px;">پرداخت‌های ثبت‌شده</div>
            <table class="table">
                <thead><tr><th>#</th><th>مبلغ</th><th>تاریخ</th><th>وضعیت</th></tr></thead>
                <tbody>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?php echo (int)$p['id']; ?></td>
                        <td><?php echo number_format((int)$p['amount']); ?></td>
                        <td><?php echo $p['pay_date'] ? Date::jDate($p['pay_date']) : ($p['paid_at'] ? Date::jDate($p['paid_at']) : ''); ?></td>
                        <td><?php echo htmlspecialchars($p['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
