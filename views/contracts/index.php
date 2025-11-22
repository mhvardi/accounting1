<?php
/** @var array $contracts */
use App\Core\Date;
use App\Core\Str;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">📄</span>
    <span>قراردادها</span>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">ثبت قرارداد جدید</div>
    </div>
    <form method="post" action="/contracts/create">
        <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;">
            <div class="form-field">
                <label class="form-label">مشتری</label>
                <select name="customer_id" class="form-select">
                    <?php foreach ($customers as $cust): ?>
                        <option value="<?php echo (int)$cust['id']; ?>">
                            <?php echo htmlspecialchars(Str::beautifyLabel($cust['name']), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">عنوان قرارداد</label>
                <input type="text" name="title" class="form-input" required>
            </div>
            <div class="form-field">
                <label class="form-label">تاریخ شروع (شمسی)</label>
                <input type="text" name="start_date" class="form-input jalali-picker" placeholder="مثلاً 1404/08/20">
            </div>
            <div class="form-field">
                <label class="form-label">مبلغ کل (تومان)</label>
                <input type="text" name="total_amount" class="form-input money-input" value="0">
            </div>
            <div class="form-field">
                <label class="form-label">دسته خدمات</label>
                <select name="category_id" class="form-select">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat['id']; ?>">
                            <?php echo htmlspecialchars(Str::beautifyLabel($cat['name']), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">کارشناس فروش</label>
                <select name="employee_id" class="form-select">
                    <option value="">بدون کارشناس</option>
                    <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo (int)$emp['id']; ?>">
                                <?php echo htmlspecialchars(Str::beautifyLabel($emp['full_name']), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">وضعیت</label>
                <select name="status" class="form-select">
                    <option value="active">فعال</option>
                    <option value="pending">در انتظار</option>
                    <option value="closed">بسته شده</option>
                    <option value="canceled">لغو شده</option>
                </select>
            </div>
            <div class="form-field" style="grid-column:1/-1;">
                <label class="form-label">توضیحات</label>
                <textarea name="note" class="form-input" rows="2"></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">ثبت قرارداد</button>
    </form>
</div>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">لیست قراردادها</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>مشتری</th>
                <th>عنوان</th>
                <th>تاریخ شروع</th>
                <th>مبلغ (تومان)</th>
                <th>دسته</th>
                <th>کارشناس فروش</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($contracts)): ?>
                <tr><td colspan="9">هنوز قراردادی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($contracts as $c): ?>
                    <tr>
                        <td><?php echo (int)$c['id']; ?></td>
                        <td><?php echo htmlspecialchars(Str::beautifyLabel($c['customer_name']), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(Str::beautifyLabel($c['title']), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo Date::jDate($c['start_date']); ?></td>
                        <td><?php echo number_format((int)$c['total_amount']); ?></td>
                        <td><?php echo htmlspecialchars($c['category_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($c['employee_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php
                            $statusLabel = [
                                'active'   => 'فعال',
                                'pending'  => 'در انتظار',
                                'closed'   => 'بسته شده',
                                'canceled' => 'لغو شده',
                            ][$c['status']] ?? $c['status'];
                            echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8');
                            ?>
                        </td>
                        <td>
                            <a href="/contracts/edit?id=<?php echo (int)$c['id']; ?>" class="btn btn-xs">ویرایش</a>
                            <a href="/contracts/delete?id=<?php echo (int)$c['id']; ?>"
                               class="btn btn-xs btn-danger"
                               onclick="return confirm('حذف این قرارداد؟');">حذف</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
