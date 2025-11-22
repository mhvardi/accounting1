<?php
/**
 * @var array $employees
 * @var array|null $selectedEmployee
 * @var int $year
 * @var int $month
 * @var string $basis
 * @var array $contracts
 * @var int $salesAmount
 * @var int $commission
 * @var float $percent
 */
use App\Core\Date;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">➕</span>
    <span>ثبت حقوق و پورسانت</span>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">انتخاب پرسنل و پارامترهای محاسبه</div>
    </div>
    <form method="get" action="/payroll/create">
        <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;">
            <div class="form-field">
                <label class="form-label">پرسنل</label>
                <select name="employee_id" class="form-select" required>
                    <option value="">انتخاب کنید...</option>
                    <?php foreach ($employees as $e): ?>
                        <option value="<?php echo (int)$e['id']; ?>"
                            <?php echo ($selectedEmployee && $selectedEmployee['id'] == $e['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($e['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php
            $years = Date::financialYears();
            $monthNames = Date::monthNames();
            ?>
            <div class="form-field">
                <label class="form-label">سال (شمسی)</label>
                <select name="year" class="form-select">
                    <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y==$year)?'selected':''; ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">ماه (شمسی)</label>
                <select name="month" class="form-select">
                    <?php foreach ($monthNames as $num=>$label): ?>
                        <option value="<?php echo $num; ?>" <?php echo ($num==$month)?'selected':''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">مبنای محاسبه (فعلاً فقط جهت یادداشت)</label>
                <select name="basis" class="form-select">
                    <option value="sales_total" <?php echo $basis==='sales_total'?'selected':''; ?>>بر اساس مبلغ قراردادها</option>
                    <option value="cash_collected" <?php echo $basis==='cash_collected'?'selected':''; ?>>بر اساس دریافتی واقعی</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-outline" style="margin-top:8px;">محاسبه</button>
    </form>
</div>

<?php if ($selectedEmployee): ?>
    <?php
    $percentDisplay = $percent > 0 ? ($percent . '٪') : 'بدون پورسانت';
    ?>
    <div class="card-soft">
        <div class="card-header">
            <div class="card-title">
                خلاصه برای
                <strong><?php echo htmlspecialchars($selectedEmployee['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                (<?php echo $year . '/' . str_pad($month,2,'0',STR_PAD_LEFT); ?>)
            </div>
        </div>
        <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:10px;">
            <div>
                <div class="form-label">حجم فروش مبنا</div>
                <div class="kpi-value"><?php echo number_format($salesAmount); ?></div>
            </div>
            <div>
                <div class="form-label">درصد پورسانت</div>
                <div class="kpi-value"><?php echo htmlspecialchars($percentDisplay, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div>
                <div class="form-label">مبلغ پورسانت</div>
                <div class="kpi-value"><?php echo number_format($commission); ?></div>
            </div>
            <div>
                <div class="form-label">حقوق ثابت ماهانه</div>
                <div class="kpi-value"><?php echo number_format((int)$selectedEmployee['base_salary']); ?></div>
            </div>
        </div>

        <form method="post" action="/payroll/create">
            <input type="hidden" name="employee_id" value="<?php echo (int)$selectedEmployee['id']; ?>">
            <input type="hidden" name="year" value="<?php echo $year; ?>">
            <input type="hidden" name="month" value="<?php echo $month; ?>">
            <input type="hidden" name="basis" value="<?php echo htmlspecialchars($basis, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:10px;">
                <div class="form-field">
                    <label class="form-label">پاداش (تومان)</label>
                    <input type="text" name="bonus_amount" class="form-input money-input" value="0">
                </div>
                <div class="form-field">
                    <label class="form-label">مساعده (تومان)</label>
                    <input type="text" name="advance_amount" class="form-input money-input" value="0">
                </div>
                <div class="form-field">
                    <label class="form-label">سایر کسورات (تومان)</label>
                    <input type="text" name="other_deductions" class="form-input money-input" value="0">
                </div>
                <div class="form-field">
                    <label class="form-label">توضیحات</label>
                    <textarea name="note" class="form-textarea" rows="2" placeholder="توضیح در مورد مبنای محاسبه این ماه..."></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top:10px;">ثبت نهایی حقوق و پورسانت</button>
        </form>
    </div>
<?php endif; ?>
