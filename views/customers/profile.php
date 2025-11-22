<?php
/**
 * @var array $customer
 * @var array $contracts
 * @var int $contractTotal
 * @var int $paidTotal
 * @var int $dueTotal
 * @var array $payments
 */
use App\Core\Date;
use App\Core\Str;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">📇</span>
    <span>پروفایل مشتری: <?php echo htmlspecialchars(Str::beautifyLabel($customer['name']), ENT_QUOTES, 'UTF-8'); ?></span>
</div>

<div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;">
    <div class="card-soft">
        <div class="form-label">جمع قراردادها</div>
        <div class="kpi-value"><?php echo number_format($contractTotal); ?></div>
    </div>
    <div class="card-soft">
        <div class="form-label">مبالغ پرداخت‌شده</div>
        <div class="kpi-value" style="color:#16a34a;">
            <?php echo number_format($paidTotal); ?>
        </div>
    </div>
    <div class="card-soft">
        <div class="form-label">مانده قابل دریافت</div>
        <div class="kpi-value" style="color:<?php echo $dueTotal >= 0 ? '#b45309' : '#16a34a'; ?>;">
            <?php echo number_format($dueTotal); ?>
        </div>
    </div>
</div>

<div class="card-soft" style="margin-top:10px;">
    <div class="card-header">
        <div class="card-title">قراردادها</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>عنوان</th>
                    <th>دسته</th>
                    <th>کارشناس فروش</th>
                    <th>مبلغ</th>
                    <th>تاریخ شروع</th>
                    <th>وضعیت</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($contracts)): ?>
                <tr><td colspan="7">قراردادی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($contracts as $c): ?>
                    <tr>
                        <td><?php echo (int)$c['id']; ?></td>
                        <td><?php echo htmlspecialchars(Str::beautifyLabel($c['title']), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($c['category_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($c['employee_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo number_format((int)$c['total_amount']); ?></td>
                        <td><?php echo Date::jDate($c['start_date']); ?></td>
                        <td><?php echo htmlspecialchars($c['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-soft" style="margin-top:10px;">
    <div class="card-header">
        <div class="card-title">سرویس‌ها / محصولات فعال</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>محصول</th>
                <th>دامنه/سایت</th>
                <th>وضعیت</th>
                <th>شروع</th>
                <th>سررسید</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($services ?? [])): ?>
                <tr><td colspan="6">سرویسی برای این مشتری ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($services as $s): $meta = json_decode($s['meta_json'] ?? '', true) ?: []; ?>
                    <tr>
                        <td><?php echo (int)$s['id']; ?></td>
                        <td><?php echo htmlspecialchars(($s['product_name'] ?? '—') . ' / ' . ($s['product_type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($meta['domain'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($s['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo Date::jDate($s['start_date']); ?></td>
                        <td><?php echo Date::jDate($s['next_due_date']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-soft" style="margin-top:10px;">
    <div class="card-header">
        <div class="card-title">تراکنش‌ها</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>قرارداد</th>
                    <th>مبلغ</th>
                    <th>تاریخ پرداخت</th>
                    <th>وضعیت</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="5">پرداختی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?php echo (int)$p['id']; ?></td>
                        <td><?php echo htmlspecialchars(Str::beautifyLabel($p['contract_title']), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo number_format((int)$p['amount']); ?></td>
                        <td><?php echo Date::jDate($p['paid_at'] ?: $p['pay_date']); ?></td>
                        <td><?php echo htmlspecialchars($p['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
