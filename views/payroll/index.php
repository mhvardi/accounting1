<?php
/** @var array $payrolls */
/** @var int $year */
/** @var int $month */
/** @var int $prevYear */
/** @var int $prevMonth */
/** @var int $nextYear */
/** @var int $nextMonth */
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">🧾</span>
    <span>حقوق و پورسانت ماهانه</span>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">
            لیست حقوق ماه
            <span style="font-weight:600;"><?php echo $year . '/' . str_pad($month,2,'0',STR_PAD_LEFT); ?></span>
        </div>
        <div style="display:flex;gap:6px;align-items:center;">
            <a href="/payroll?year=<?php echo $prevYear; ?>&month=<?php echo $prevMonth; ?>" class="btn btn-outline">ماه قبل</a>
            <a href="/payroll?year=<?php echo $nextYear; ?>&month=<?php echo $nextMonth; ?>" class="btn btn-outline">ماه بعد</a>
            <a href="/payroll/create?year=<?php echo $year; ?>&month=<?php echo $month; ?>" class="btn btn-primary">ثبت حقوق / پورسانت جدید</a>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>پرسنل</th>
                <th>سال/ماه</th>
                <th>مبنای محاسبه</th>
                <th>حجم فروش مبنا</th>
                <th>پورسانت</th>
                <th>حقوق ثابت</th>
                <th>پاداش</th>
                <th>مساعده</th>
                <th>سایر کسورات</th>
                <th>خالص پرداختی</th>
                <th>اقدامات</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($payrolls)): ?>
                <tr><td colspan="12">برای این ماه هنوز حقوقی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($payrolls as $p): ?>
                    <tr>
                        <td><?php echo (int)$p['id']; ?></td>
                        <td><?php echo htmlspecialchars($p['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $p['year'] . '/' . str_pad($p['month'],2,'0',STR_PAD_LEFT); ?></td>
                        <td>
                            <?php
                            echo $p['basis'] === 'cash_collected'
                                ? 'بر اساس دریافتی واقعی'
                                : 'بر اساس مبلغ قرارداد';
                            ?>
                        </td>
                        <td><?php echo number_format((int)$p['sales_amount']); ?></td>
                        <td><?php echo number_format((int)$p['commission_amount']); ?></td>
                        <td><?php echo number_format((int)$p['base_salary']); ?></td>
                        <td><?php echo number_format((int)$p['bonus_amount']); ?></td>
                        <td><?php echo number_format((int)$p['advance_amount']); ?></td>
                        <td><?php echo number_format((int)$p['other_deductions']); ?></td>
                        <td><?php echo number_format((int)$p['total_payable']); ?></td>
                        <td>
                            <a class="btn btn-outline btn-danger" href="/payroll/delete?id=<?php echo (int)$p['id']; ?>" onclick="return confirm('حذف رکورد حقوقی?');">حذف</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
