<?php
/** @var array $expenses */
/** @var array $customers */
/** @var array $categories */
use App\Core\Date;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">💸</span>
    <span>هزینه‌ها</span>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">ثبت هزینه جدید</div>
    </div>
    <form method="post" action="/expenses/create">
        <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;">
            <div class="form-field">
                <label class="form-label">دسته‌بندی هزینه</label>
                <select name="category_id" class="form-select">
                    <option value="">انتخاب کنید...</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-label" style="margin-top:4px;font-size:10px;">یا عنوان جدید را در کادر زیر وارد کنید</div>
                <input type="text" name="category" class="form-input" placeholder="مثلاً خرید دامنه، سرور، اجاره و ...">
                <a href="/expense-categories" class="btn btn-xs" style="margin-top:6px;">مدیریت دسته‌ها</a>
            </div>
            <div class="form-field">
                <label class="form-label">مبلغ (تومان)</label>
                <input type="text" name="amount" class="form-input money-input" value="0" required>
            </div>
            <div class="form-field">
                <label class="form-label">تاریخ هزینه (شمسی)</label>
                <input type="text" name="expense_date" class="form-input jalali-picker" placeholder="مثلاً 1403/08/15" value="<?php echo Date::j('Y/m/d'); ?>">
            </div>
            <div class="form-field">
                <label class="form-label">مشتری (در صورت ارتباط هزینه با مشتری)</label>
                <select name="customer_id" class="form-select">
                    <option value="">بدون مشتری</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field" style="grid-column:1/-1;">
                <label class="form-label">توضیحات</label>
                <input type="text" name="note" class="form-input">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">ثبت هزینه</button>
    </form>
</div>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">لیست هزینه‌ها</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>دسته‌بندی</th>
                <th>مبلغ (تومان)</th>
                <th>تاریخ</th>
                <th>مشتری</th>
                <th>توضیحات</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($expenses)): ?>
                <tr><td colspan="7">هنوز هزینه‌ای ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($expenses as $e): ?>
                    <tr>
                        <td><?php echo (int)$e['id']; ?></td>
                        <td><?php echo htmlspecialchars($e['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo number_format((int)$e['amount']); ?></td>
                        <td><?php echo $e['expense_date'] ? Date::jDate($e['expense_date']) : ''; ?></td>
                        <td><?php echo htmlspecialchars($e['customer_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($e['note'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <button class="btn btn-outline" data-inline-edit-toggle="expense-<?php echo (int)$e['id']; ?>" style="padding-inline:8px;">ویرایش</button>
                            <a href="/expenses/delete?id=<?php echo (int)$e['id']; ?>" class="btn btn-outline" style="padding-inline:8px;color:#b91c1c;margin-top:4px;"
                               onclick="return confirm('این هزینه حذف شود؟');">حذف</a>
                            <div class="inline-edit" data-inline-edit-box="expense-<?php echo (int)$e['id']; ?>">
                                <form method="post" action="/expenses/edit">
                                    <input type="hidden" name="id" value="<?php echo (int)$e['id']; ?>">
                                    <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:6px;">
                                        <?php
                                        $matchedCategoryId = '';
                                        foreach ($categories as $cat) {
                                            if ($cat['name'] === $e['category']) {
                                                $matchedCategoryId = (int)$cat['id'];
                                                break;
                                            }
                                        }
                                        ?>
                                        <div class="form-field">
                                            <label class="form-label">دسته‌بندی</label>
                                            <select name="category_id" class="form-select">
                                                <option value="">انتخاب کنید...</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo ($matchedCategoryId===$cat['id'])?'selected':''; ?>>
                                                        <?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="text" name="category" class="form-input" style="margin-top:4px;" placeholder="عنوان دلخواه"
                                                   value="<?php echo htmlspecialchars($e['category'], ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">مبلغ (تومان)</label>
                                            <input type="text" name="amount" class="form-input money-input"
                                                   value="<?php echo number_format((int)$e['amount']); ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">تاریخ هزینه (شمسی)</label>
                                            <input type="text" name="expense_date" class="form-input jalali-picker"
                                                   value="<?php echo $e['expense_date'] ? Date::jDate($e['expense_date']) : ''; ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">مشتری</label>
                                            <select name="customer_id" class="form-select">
                                                <option value="">بدون مشتری</option>
                                                <?php foreach ($customers as $c): ?>
                                                    <option value="<?php echo (int)$c['id']; ?>"
                                                        <?php echo ($e['customer_id']==$c['id'])?'selected':''; ?>>
                                                        <?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-field" style="grid-column:1/-1;">
                                            <label class="form-label">توضیحات</label>
                                            <input type="text" name="note" class="form-input"
                                                   value="<?php echo htmlspecialchars($e['note'], ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="margin-top:4px;">ثبت تغییرات</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
