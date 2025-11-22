<?php
/** @var array $employee */
/** @var array $categories */
/** @var array $commissionSteps */
/** @var array $commissionCats */
use App\Core\Date;
use App\Core\Str;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">👤</span>
    <span>ویرایش پرسنل: <?php echo htmlspecialchars($employee['full_name'], ENT_QUOTES, 'UTF-8'); ?></span>
</div>


<?php
// نوع همکاری از ستون compensation_type
$cooperationType = $employee['compensation_type'] ?? 'fixed';

// mode و scope از ستون‌های واقعی DB
$dbMode  = $employee['commission_mode']  ?? 'none';   // none | flat | tiered | category
$dbScope = $employee['commission_scope'] ?? 'self';   // self | company

// مدل پورسانت برای فرم: none | percent | tiered
if ($dbMode === 'tiered') {
    $commissionModel = 'tiered';
} elseif ($dbMode === 'none') {
    $commissionModel = 'none';
} else {
    // flat یا category را در فرم به صورت درصد ثابت نشان می‌دهیم
    $commissionModel = 'percent';
}

// مبنای پورسانت برای فرم
if ($dbMode === 'category') {
    // پورسانت بر اساس دسته‌های خاص خدمات
    $commissionBasis = 'categories';
} else {
    if ($dbScope === 'company') {
        // مبنا: حجم کل فروش شرکت
        $commissionBasis = 'company_total';
    } else {
        // مبنا: مبلغ دریافتی از قراردادهای خودش
        $commissionBasis = 'contract_received';
    }
}

// درصد پورسانت از ستون commission_percent
$commissionPercent = $employee['commission_percent'] ?? 0;

// تاریخ شروع همکاری از effective_from (شمسی‌سازی)
$startDate = Date::jDate($employee['effective_from'] ?? '');
?>
<div class="card-soft">
    <div class="card-header">
        <div class="card-title">اطلاعات پرسنل</div>
    </div>
    <form method="post" action="/employees/edit?id=<?php echo (int)$employee['id']; ?>" id="employee-form">
        <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;">
            <div class="form-field">
                <label class="form-label">نام و نام خانوادگی</label>
                <input type="text" name="full_name" class="form-input"
                       value="<?php echo htmlspecialchars($employee['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="form-field">
                <label class="form-label">حقوق ثابت ماهانه (تومان)</label>
                <input type="text" name="base_salary" class="form-input money-input"
                       value="<?php echo number_format((int)$employee['base_salary']); ?>">
            </div>
            <div class="form-field">
                <label class="form-label">نوع همکاری</label>
                <select name="cooperation_type" id="cooperation_type" class="form-select">
                    <option value="fixed"     <?php echo $cooperationType === 'fixed' ? 'selected' : ''; ?>>حقوق ثابت</option>
                    <option value="commission"<?php echo $cooperationType === 'commission' ? 'selected' : ''; ?>>پورسانتی</option>
                    <option value="mixed"     <?php echo $cooperationType === 'mixed' ? 'selected' : ''; ?>>ترکیبی (حقوق + پورسانت)</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">تاریخ شروع همکاری (شمسی)</label>
                <input type="text" name="start_date" class="form-input jalali-picker"
                       value="<?php echo htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <hr style="margin:12px 0;opacity:0.2;">

        <div id="commission-block" style="<?php echo ($cooperationType === 'commission' || $cooperationType === 'mixed') ? '' : 'display:none;'; ?>">
            <div class="card-header" style="padding-left:0;padding-right:0;">
                <div class="card-title">تنظیمات پورسانت</div>
            </div>
            <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:6px;">
                <div class="form-field">
                    <label class="form-label">مدل پورسانت</label>
                    <select name="commission_model" id="commission_model" class="form-select">
                        <option value="none"    <?php echo $commissionModel === 'none' ? 'selected' : ''; ?>>بدون پورسانت</option>
                        <option value="percent" <?php echo $commissionModel === 'percent' ? 'selected' : ''; ?>>درصد ثابت</option>
                        <option value="tiered"  <?php echo $commissionModel === 'tiered' ? 'selected' : ''; ?>>پلکانی</option>
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label">حجم فروش مبنا</label>
                    <select name="commission_basis" id="commission_basis" class="form-select">
                        <option value="contract_received" <?php echo $commissionBasis === 'contract_received' ? 'selected' : ''; ?>>
                            مبلغ دریافتی از قراردادهای خودش
                        </option>
                        <option value="contract_total" <?php echo $commissionBasis === 'contract_total' ? 'selected' : ''; ?>>
                            مبلغ کل قراردادهای خودش
                        </option>
                        <option value="company_total" <?php echo $commissionBasis === 'company_total' ? 'selected' : ''; ?>>
                            حجم کل فروش شرکت
                        </option>
                        <option value="categories" <?php echo $commissionBasis === 'categories' ? 'selected' : ''; ?>>
                            دسته‌های خاص خدمات
                        </option>
                    </select>
                </div>

                <div class="form-field commission-percent-block"
                     style="<?php echo ($commissionModel === 'percent') ? '' : 'display:none;'; ?>">
                    <label class="form-label">درصد پورسانت (در مدل درصدی)</label>
                    <input type="text" name="commission_percent" class="form-input"
                           value="<?php echo htmlspecialchars($commissionPercent, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div id="commission-categories-block"
                 style="<?php echo ($commissionBasis === 'categories') ? 'margin-top:8px;' : 'display:none;margin-top:8px;'; ?>">
                <label class="form-label">دسته‌های مشمول پورسانت (در صورت مبنای دسته)</label>
                <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:6px;">
                    <?php foreach ($categories as $cat): ?>
                        <label class="form-checkbox">
                            <input type="checkbox"
                                   name="commission_categories[]"
                                   value="<?php echo (int)$cat['id']; ?>"
                                   <?php echo in_array((int)$cat['id'], $commissionCats, true) ? 'checked' : ''; ?>>
                            <span><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </label>
                    <?php endforeach; ?>
                    <?php if (empty($categories)): ?>
                        <div style="font-size:11px;color:#f97316;">
                            هنوز دسته خدماتی تعریف نشده است.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="commission-tiers-block"
                 style="<?php echo ($commissionModel === 'tiered') ? 'margin-top:8px;' : 'display:none;margin-top:8px;'; ?>">
                <label class="form-label">پلکان‌های پورسانت (در صورت مدل پلکانی)</label>
                <div id="tier-rows">
                    <?php if (!empty($commissionSteps)): ?>
                        <?php foreach ($commissionSteps as $row): ?>
                            <div class="grid tier-row" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:6px;margin-bottom:4px;">
                                <input type="text" name="tier_min[]" class="form-input money-input"
                                       placeholder="حداقل فروش (تومان)"
                                       value="<?php echo number_format((int)($row['min'] ?? 0)); ?>">
                                <input type="text" name="tier_max[]" class="form-input money-input"
                                       placeholder="حداکثر فروش (تومان)"
                                       value="<?php echo number_format((int)($row['max'] ?? 0)); ?>">
                                <input type="text" name="tier_percent[]" class="form-input"
                                       placeholder="درصد پورسانت"
                                       value="<?php echo htmlspecialchars($row['percent'] ?? 0, ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="grid tier-row" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:6px;margin-bottom:4px;">
                            <input type="text" name="tier_min[]" class="form-input money-input" placeholder="حداقل فروش (تومان)">
                            <input type="text" name="tier_max[]" class="form-input money-input" placeholder="حداکثر فروش (تومان)">
                            <input type="text" name="tier_percent[]" class="form-input" placeholder="درصد پورسانت">
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-xs" id="add-tier-row" style="margin-top:4px;">+ افزودن پلکان</button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:10px;">ذخیره تغییرات</button>
        <a href="/employees" class="btn btn-xs" style="margin-top:10px;">بازگشت</a>
    </form>
</div>

<script src="/assets/js/employees.js"></script>
