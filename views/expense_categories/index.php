<?php
/** @var array $categories */
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">🏷️</span>
    <span>دسته‌بندی‌های هزینه</span>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">افزودن دسته جدید</div>
        <div class="card-meta">طبقه‌بندی هزینه‌ها برای گزارش دقیق‌تر</div>
    </div>
    <form method="post" action="/expense-categories/create" class="grid" style="grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;">
        <div class="form-field" style="grid-column:1/-1;">
            <label class="form-label">نام دسته‌بندی</label>
            <input type="text" name="name" class="form-input" placeholder="مثلاً اجاره، حقوق، تبلیغات" required>
        </div>
        <div>
            <button type="submit" class="btn btn-primary">ثبت دسته</button>
        </div>
    </form>
</div>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">فهرست دسته‌ها</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>نام</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($categories)): ?>
                <tr><td colspan="3">هنوز دسته‌ای ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?php echo (int)$cat['id']; ?></td>
                        <td><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <button class="btn btn-outline" data-inline-edit-toggle="cat-<?php echo (int)$cat['id']; ?>">ویرایش</button>
                            <a class="btn btn-outline" style="color:#b91c1c;margin-right:6px;" href="/expense-categories/delete?id=<?php echo (int)$cat['id']; ?>" onclick="return confirm('این دسته حذف شود؟');">حذف</a>
                            <div class="inline-edit" data-inline-edit-box="cat-<?php echo (int)$cat['id']; ?>">
                                <form method="post" action="/expense-categories/edit">
                                    <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
                                    <div class="form-field">
                                        <label class="form-label">نام دسته</label>
                                        <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary">ثبت تغییر</button>
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
