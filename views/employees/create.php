<?php
/** @var array $categories */
/** @var array $user */
use App\Core\Date;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">👤</span>
    <span>افزودن پرسنل جدید</span>
</div>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">مشخصات پرسنل</div>
    </div>
    <form method="post" action="/employees/create" id="employee-form">
        <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;">
            <div class="form-field">
                <label class="form-label">نام و نام خانوادگی</label>
                <input type="text" name="full_name" class="form-input" required>
            </div>
            <div class="form-field">
                <label class="form-label">حقوق ثابت ماهانه (تومان)</label>
                <input type="text" name="base_salary" class="form-input money-input" value="0">
            </div>
            <div class="form-field">
                <label class="form-label">نوع همکاری</label>
                <select name="cooperation_type" id="cooperation_type" class="form-select">
                    <option value="fixed">حقوق ثابت</option>
                    <option value="commission">پورسانتی</option>
                    <option value="mixed">ترکیبی (حقوق + پورسانت)</option>
                </select>
            </div>

            <div class="form-field">
                <label class="form-label">تاریخ شروع همکاری (شمسی)</label>
                <input type="text" name="start_date" class="form-input jalali-picker"
                       placeholder="مثلاً 1404/08/01">
            </div>
        </div>

        <hr style="margin:12px 0;opacity:0.2;">

        <div id="commission-block" style="display:none;">
            <div class="card-header" style="padding-left:0;padding-right:0;">
                <div class="card-title">تنظیمات پورسانت</div>
            </div>
            <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:6px;">
                <div class="form-field">
                    <label class="form-label">مدل پورسانت</label>
                    <select name="commission_model" id="commission_model" class="form-select">
                        <option value="none">بدون پورسانت</option>
                        <option value="percent">درصد ثابت</option>
                        <option value="tiered">پلکانی</option>
                    </select>
                </div>

                <div class="form-field">
                    <label class="form-label">حجم فروش مبنا</label>
                    <select name="commission_basis" id="commission_basis" class="form-select">
                        <option value="contract_received">مبلغ دریافتی از قراردادهای خودش</option>
                        <option value="contract_total">مبلغ کل قراردادهای خودش</option>
                        <option value="company_total">حجم کل فروش شرکت</option>
                        <option value="categories">دسته‌های خاص خدمات</option>
                    </select>
                </div>

                <div class="form-field commission-percent-block" style="display:none;">
                    <label class="form-label">درصد پورسانت (در مدل درصدی)</label>
                    <input type="text" name="commission_percent" class="form-input" value="0">
                </div>
            </div>

            <div id="commission-categories-block" style="display:none;margin-top:8px;">
                <label class="form-label">دسته‌های مشمول پورسانت (در صورت مبنای دسته)</label>
                <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:6px;">
                    <?php foreach ($categories as $cat): ?>
                        <label class="form-checkbox">
                            <input type="checkbox"
                                   name="commission_categories[]"
                                   value="<?php echo (int)$cat['id']; ?>">
                            <span><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </label>
                    <?php endforeach; ?>
                    <?php if (empty($categories)): ?>
                        <div style="font-size:11px;color:#f97316;">
                            هنوز دسته خدماتی تعریف نشده است.
                        </div>
                    <?php endif; ?>
                </div>
                <div style="margin-top:6px;">
                    <label class="form-checkbox">
                        <input type="checkbox" name="category_company_wide" value="1">
                        <span>محاسبه دسته‌بندی به‌صورت سراسری (بدون وابستگی به فروشنده قرارداد)</span>
                    </label>
                    <div style="font-size:11px;color:#6b7280;margin-top:2px;">
                        در حالت فعال، قراردادها و دریافتی‌های دسته انتخابی حتی اگر فروشنده مشخص نشده باشد در پورسانت لحاظ می‌شوند.
                    </div>
                </div>
            </div>

            <div id="commission-tiers-block" style="display:none;margin-top:8px;">
                <label class="form-label">پلکان‌های پورسانت (در صورت مدل پلکانی)</label>
                <div id="tier-rows">
                    <div class="grid tier-row" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:6px;margin-bottom:4px;">
                        <input type="text" name="tier_min[]" class="form-input money-input" placeholder="حداقل فروش (تومان)">
                        <input type="text" name="tier_max[]" class="form-input money-input" placeholder="حداکثر فروش (تومان)">
                        <input type="text" name="tier_percent[]" class="form-input" placeholder="درصد پورسانت">
                    </div>
                </div>
                <button type="button" class="btn btn-xs" id="add-tier-row" style="margin-top:4px;">+ افزودن پلکان</button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:10px;">ثبت پرسنل</button>
    </form>
</div>

<script src="/assets/js/employees.js"></script>