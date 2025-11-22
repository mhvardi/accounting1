<?php
/** @var int $year */
/** @var int $month */
/** @var array $summary */
/** @var array $prevSummary */
/** @var array $monthlySeries */
/** @var array $payrollDetail */
/** @var array $analysis */
/** @var array $expensesByCategory */
/** @var array $financialYears */

use App\Core\Date;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">📈</span>
    <span>گزارش‌ها و تحلیل</span>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">فیلتر و بازه گزارش (پیش‌فرض: ماه فعلی)</div>
    </div>
    <form method="get" action="/reports">
        <?php
        $monthNames = Date::monthNames();
        ?>
        <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;">
            <div class="form-field">
                <label class="form-label">سال (شمسی)</label>
                <select name="year" class="form-select">
                    <?php foreach ($financialYears as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y == $year) ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">ماه</label>
                <select name="month" class="form-select">
                    <?php foreach ($monthNames as $num => $label): ?>
                        <option value="<?php echo $num; ?>" <?php echo ($num == $month) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">نوع بازه</label>
                <select name="range" class="form-select">
                    <option value="current" <?php echo $range==='current'?'selected':''; ?>>ماه انتخابی</option>
                    <option value="prev" <?php echo $range==='prev'?'selected':''; ?>>ماه قبل نسبت به انتخاب</option>
                    <option value="3m" <?php echo $range==='3m'?'selected':''; ?>>سه ماه گذشته</option>
                    <option value="6m" <?php echo $range==='6m'?'selected':''; ?>>شش ماه گذشته</option>
                    <option value="12m" <?php echo $range==='12m'?'selected':''; ?>>یک‌سال گذشته</option>
                    <option value="custom" <?php echo $range==='custom'?'selected':''; ?>>بازه دلخواه</option>
                </select>
            </div>
        </div>
        <div class="grid" style="grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:8px;">
            <div class="form-field">
                <label class="form-label">تاریخ شروع دلخواه (شمسی)</label>
                <input type="text" name="start" class="form-input jalali-picker" value="<?php echo htmlspecialchars($customStart, ENT_QUOTES, 'UTF-8'); ?>" placeholder="مثلاً 1403/01/01">
            </div>
            <div class="form-field">
                <label class="form-label">تاریخ پایان دلخواه (شمسی)</label>
                <input type="text" name="end" class="form-input jalali-picker" value="<?php echo htmlspecialchars($customEnd, ENT_QUOTES, 'UTF-8'); ?>" placeholder="مثلاً 1403/03/31">
            </div>
        </div>
        <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:6px; font-size:11px;">
            <button type="submit" class="btn btn-outline">مشاهده گزارش</button>
            <a href="/reports" class="btn btn-xs">ماه فعلی</a>
            <a href="/reports?range=prev" class="btn btn-xs">ماه قبل</a>
            <a href="/reports?range=3m" class="btn btn-xs">۳ ماه اخیر</a>
            <a href="/reports?range=6m" class="btn btn-xs">۶ ماه اخیر</a>
            <a href="/reports?range=12m" class="btn btn-xs">۱۲ ماه اخیر</a>
        </div>
        <div style="margin-top:6px;font-size:12px;color:#374151;">بازه انتخاب‌شده: <strong><?php echo htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8'); ?></strong></div>
    </form>
</div>

<div class="grid grid-4">
    <div class="card">
        <div class="card-header"><div class="card-title">درآمد قراردادها (بازه انتخابی)</div></div>
        <div class="kpi-value">
            <?php echo number_format($summary['contracts_total']); ?>
            <span style="font-size:11px;">تومان</span>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">دریافتی واقعی (بازه انتخابی)</div></div>
        <div class="kpi-value">
            <?php echo number_format($summary['payments_total']); ?>
            <span style="font-size:11px;">تومان</span>
        </div>
        <div style="font-size:11px;color:#6b7280;margin-top:4px;">بدون سایت‌های متفرقه</div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">درآمد سایت‌های متفرقه</div></div>
        <div class="kpi-value">
            <?php echo number_format($summary['external_total']); ?>
            <span style="font-size:11px;">تومان</span>
        </div>
        <div style="font-size:11px;color:#6b7280;margin-top:4px;">بانک شماره، استارپلن و ...</div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">هزینه‌ها + حقوق (بازه انتخابی)</div></div>
        <div class="kpi-value">
            <?php echo number_format($summary['payroll_total'] + $summary['expenses_total']); ?>
            <span style="font-size:11px;">تومان</span>
        </div>
    </div>
</div>

<div class="grid grid-3" style="margin-top:10px;">
    <div class="card">
        <div class="card-header"><div class="card-title">سود/زیان بر اساس قراردادها</div></div>
        <div class="kpi-value">
            <?php echo number_format($summary['profit_contract_based']); ?>
            <span style="font-size:11px;">تومان</span>
        </div>
        <div style="font-size:11px;color:#6b7280;margin-top:4px;">
            درآمد قراردادها - حقوق - هزینه‌ها
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">سود/زیان بر اساس جریان نقدی</div></div>
        <div class="kpi-value">
            <?php echo number_format($summary['profit_cash_based']); ?>
            <span style="font-size:11px;">تومان</span>
        </div>
        <div style="font-size:11px;color:#6b7280;margin-top:4px;">
            دریافتی‌های واقعی (بدون سایت‌های متفرقه) - حقوق - هزینه‌ها
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">سود/زیان با سایت‌های متفرقه</div></div>
        <div class="kpi-value">
            <?php echo number_format($summary['profit_with_external']); ?>
            <span style="font-size:11px;">تومان</span>
        </div>
        <div style="font-size:11px;color:#6b7280;margin-top:4px;">
            دریافتی واقعی + درآمد سایت‌های متفرقه - حقوق - هزینه‌ها
        </div>
    </div>
</div>

<div class="card-soft" style="margin-top:10px;">
    <div class="card-header">
        <div class="card-title">نمودار ۱۲ ماه اخیر (دریافتی / سایت‌های متفرقه / هزینه / سود)</div>
    </div>
    <canvas id="chartMonthly" style="width:100%;max-height:260px;"></canvas>
</div>

<div class="card-soft" style="margin-top:10px;">
    <div class="card-header">
        <div class="card-title">تحلیل هوشمند و تارگت ماه بعد</div>
    </div>
    <div style="font-size:13px;line-height:1.9;">
        <?php foreach ($analysis as $line): ?>
            <div><?php echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card-soft" style="margin-top:10px;">
    <div class="card-header">
        <div class="card-title">جزئیات حقوق ماه انتخابی</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>پرسنل</th>
                <th>سال/ماه</th>
                <th>حقوق ثابت</th>
                <th>پورسانت</th>
                <th>پاداش</th>
                <th>مساعده</th>
                <th>سایر کسورات</th>
                <th>خالص پرداختی</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($payrollDetail)): ?>
                <tr><td colspan="9">برای این ماه هنوز حقوقی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($payrollDetail as $p): ?>
                    <tr>
                        <td><?php echo (int)$p['id']; ?></td>
                        <td><?php echo htmlspecialchars($p['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $p['year'] . '/' . str_pad($p['month'],2,'0',STR_PAD_LEFT); ?></td>
                        <td><?php echo number_format((int)$p['base_salary']); ?></td>
                        <td><?php echo number_format((int)$p['commission_amount']); ?></td>
                        <td><?php echo number_format((int)$p['bonus_amount']); ?></td>
                        <td><?php echo number_format((int)$p['advance_amount']); ?></td>
                        <td><?php echo number_format((int)$p['other_deductions']); ?></td>
                        <td><?php echo number_format((int)$p['total_payable']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-soft" style="margin-top:10px;">
    <div class="card-header">
        <div class="card-title">هزینه‌ها بر اساس دسته‌بندی (ماه انتخابی)</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>دسته</th>
                <th>مبلغ (تومان)</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($expensesByCategory)): ?>
                <tr><td colspan="2">برای این ماه هنوز هزینه‌ای ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($expensesByCategory as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo number_format((int)$row['total']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
    const ctx = document.getElementById('chartMonthly');
    if (!ctx || typeof Chart === 'undefined') return;
    const data = <?php echo json_encode($monthlySeries, JSON_UNESCAPED_UNICODE); ?>;
    const labels      = data.map(r => r.label);
    const revenue     = data.map(r => r.revenue);
    const external    = data.map(r => r.external);
    const payroll     = data.map(r => r.payroll);
    const expense     = data.map(r => r.expense);
    const profit      = data.map(r => r.profit);
    const profitExt   = data.map(r => r.profit_ext);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'دریافتی', data: revenue, borderWidth: 2, tension: 0.3 },
                { label: 'سایت‌های متفرقه', data: external, borderWidth: 2, tension: 0.3 },
                { label: 'هزینه', data: expense, borderWidth: 2, tension: 0.3 },
                { label: 'حقوق', data: payroll, borderWidth: 2, tension: 0.3 },
                { label: 'سود (بدون متفرقه)', data: profit, borderWidth: 2, tension: 0.3 },
                { label: 'سود (با متفرقه)', data: profitExt, borderWidth: 2, tension: 0.3 },
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
})();
</script>
