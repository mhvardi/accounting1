<?php
/** @var array $contract */
/** @var array $customers */
/** @var array $employees */
/** @var array $categories */
use App\Core\Date;
use App\Core\Str;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">✏️</span>
    <span>ویرایش قرارداد</span>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">اطلاعات قرارداد #<?php echo (int)$contract['id']; ?></div>
    </div>
    <form method="post" action="/contracts/edit">
        <input type="hidden" name="id" value="<?php echo (int)$contract['id']; ?>">
        <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;">
            <div class="form-field">
                <label class="form-label">مشتری</label>
                <select name="customer_id" class="form-select select-search">
                    <?php foreach ($customers as $cust): ?>
                        <option value="<?php echo (int)$cust['id']; ?>" <?php echo $contract['customer_id']==$cust['id']?'selected':''; ?>>
                            <?php echo htmlspecialchars(Str::beautifyLabel($cust['name']), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">عنوان قرارداد</label>
                <input type="text" name="title" class="form-input" required value="<?php echo htmlspecialchars(Str::beautifyLabel($contract['title']), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-field">
                <label class="form-label">تاریخ شروع (شمسی)</label>
                <input type="text" name="start_date" class="form-input jalali-picker" value="<?php echo htmlspecialchars(Date::jDate($contract['start_date']), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-field">
                <label class="form-label">مبلغ کل (تومان)</label>
                <input type="text" name="total_amount" class="form-input money-input" value="<?php echo number_format((int)$contract['total_amount']); ?>">
            </div>
            <div class="form-field">
                <label class="form-label">دسته خدمات</label>
                <select name="category_id" class="form-select select-search">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat['id']; ?>" <?php echo $contract['category_id']==$cat['id']?'selected':''; ?>>
                            <?php echo htmlspecialchars(Str::beautifyLabel($cat['name']), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">کارشناس فروش</label>
                <select name="employee_id" class="form-select select-search">
                    <option value="">بدون کارشناس</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo (int)$emp['id']; ?>" <?php echo $contract['sales_employee_id']==$emp['id']?'selected':''; ?>>
                            <?php echo htmlspecialchars(Str::beautifyLabel($emp['full_name']), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">وضعیت</label>
                <select name="status" class="form-select">
                    <option value="active" <?php echo $contract['status']==='active'?'selected':''; ?>>فعال</option>
                    <option value="pending" <?php echo $contract['status']==='pending'?'selected':''; ?>>در انتظار</option>
                    <option value="closed" <?php echo $contract['status']==='closed'?'selected':''; ?>>بسته شده</option>
                    <option value="canceled" <?php echo $contract['status']==='canceled'?'selected':''; ?>>لغو شده</option>
                </select>
            </div>
            <div class="form-field" style="grid-column:1/-1;">
                <label class="form-label">توضیحات</label>
                <textarea name="note" class="form-input" rows="2"><?php echo htmlspecialchars($contract['note'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>
        <div style="display:flex;gap:8px;margin-top:10px;">
            <a href="/contracts" class="btn btn-outline">بازگشت</a>
            <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
        </div>
    </form>
</div>
