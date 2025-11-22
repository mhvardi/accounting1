<?php
/** @var array $customer */
/** @var array $contracts */
/** @var array $payments */
/** @var array $expenses */
/** @var int $profitByContracts */
/** @var int $profitByCash */
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">📌</span>
    <span>پروفایل مشتری: <?php echo htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8'); ?></span>
</div>

<div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:10px;">
    <div class="kpi-card">
        <div class="kpi-label">مجموع مبلغ قراردادها</div>
        <div class="kpi-value">
            <?php
            $sumContracts = 0;
            foreach ($contracts as $c) $sumContracts += (int)$c['total_amount'];
            echo number_format($sumContracts);
            ?>
            تومان
        </div>
    </div>
    <div class="kpi-card kpi-income">
        <div class="kpi-label">مجموع دریافتی از این مشتری</div>
        <div class="kpi-value">
            <?php
            $sumPayments = 0;
            foreach ($payments as $p) if ($p['status']==='paid') $sumPayments += (int)$p['amount'];
            echo number_format($sumPayments);
            ?>
            تومان
        </div>
    </div>
    <div class="kpi-card kpi-expense">
        <div class="kpi-label">مجموع هزینه‌های مربوط به این مشتری</div>
        <div class="kpi-value">
            <?php
            $sumExp = 0;
            foreach ($expenses as $e) $sumExp += (int)$e['amount'];
            echo number_format($sumExp);
            ?>
            تومان
        </div>
    </div>
</div>

<div class="grid" style="grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-bottom:10px;">
    <div class="kpi-card kpi-profit">
        <div class="kpi-label">سود/زیان بر اساس مبلغ قراردادها</div>
        <div class="kpi-value"><?php echo number_format($profitByContracts); ?> تومان</div>
    </div>
    <div class="kpi-card kpi-profit">
        <div class="kpi-label">سود/زیان بر اساس جریان نقدی</div>
        <div class="kpi-value"><?php echo number_format($profitByCash); ?> تومان</div>
    </div>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">قراردادها</div>
    </div>
    <div class="card-body" style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>عنوان</th>
                <th>مبلغ</th>
                <th>وضعیت</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($contracts)): ?>
                <tr><td colspan="4">برای این مشتری هنوز قراردادی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($contracts as $c): ?>
                    <tr>
                        <td><?php echo (int)$c['id']; ?></td>
                        <td><?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo number_format((int)$c['total_amount']); ?></td>
                        <td><?php echo htmlspecialchars($c['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">پرداخت‌ها</div>
    </div>
    <div class="card-body" style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>قرارداد</th>
                <th>مبلغ</th>
                <th>تاریخ</th>
                <th>وضعیت</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="4">پرداختی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?php echo (int)$p['id']; ?></td>
                        <td><?php echo htmlspecialchars($p['contract_title'] ?? 'بدون قرارداد', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo number_format((int)$p['amount']); ?></td>
                        <td><?php echo $p['pay_date'] ? $p['pay_date'] : ($p['paid_at'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($p['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">هزینه‌ها</div>
    </div>
    <div class="card-body" style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>عنوان</th>
                <th>مبلغ</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($expenses)): ?>
                <tr><td colspan="3">هزینه‌ای برای این مشتری ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($expenses as $e): ?>
                    <tr>
                        <td><?php echo (int)$e['id']; ?></td>
                        <td><?php echo htmlspecialchars($e['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo number_format((int)$e['amount']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
