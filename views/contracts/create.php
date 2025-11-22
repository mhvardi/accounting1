<?php
/** @var string|null $error */
/** @var array $employees */
/** @var array $categories */
$error = $error ?? null;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">➕</span>
    <span>ثبت قرارداد جدید</span>
</div>

<div class="card-soft" style="max-width:720px;">
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
    <form method="post" action="/contracts/create">
        <div class="grid" style="grid-template-columns: repeat(2,minmax(0,1fr));gap:10px;">
            <div class="form-field">
                <label class="form-label">نام مشتری</label>
                <input type="text" name="customer_name" class="form-input" required>
            </div>
            <div class="form-field">
                <label class="form-label">عنوان قرارداد</label>
                <input type="text" name="title" class="form-input" required>
            </div>
            <div class="form-field">
                <label class="form-label">دسته‌بندی</label>
                <select name="category_id" class="form-select" required>
                    <option value="">انتخاب کنید...</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">فروشنده (پرسنل مسئول فروش)</label>
                <select name="sales_employee_id" class="form-select">
                    <option value="">بدون فروشنده مشخص</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo (int)$emp['id']; ?>">
                            <?php echo htmlspecialchars($emp['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">مبلغ کل قرارداد (تومان)</label>
                <input type="number" name="total_amount" class="form-input" required min="0" step="10000">
            </div>
            <div class="form-field">
                <label class="form-label">تاریخ شروع (شمسی)</label>
                <input type="text" name="start_date" class="form-input jalali-picker" required placeholder="مثلاً 1404/08/01">
            </div>
        </div>
        <div class="form-field" style="margin-top:8px;">
            <label class="form-label">توضیحات</label>
            <textarea name="notes" class="form-textarea" rows="3"></textarea>
        </div>
        <div style="margin-top:10px;">
            <button type="submit" class="btn btn-primary">ثبت قرارداد</button>
        </div>
    </form>
</div>
