<?php
/** @var array $categories */
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">📂</span>
    <span>دسته‌بندی خدمات</span>
</div>

<div class="card-soft" style="margin-bottom:10px;max-width:600px;">
    <div class="card-header">
        <div class="card-title">افزودن دسته‌بندی جدید</div>
    </div>
    <form method="post" action="/categories/create">
        <div class="grid" style="grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;">
            <div class="form-field">
                <label class="form-label">عنوان دسته‌بندی</label>
                <input type="text" name="name" class="form-input" required>
            </div>
            <div class="form-field">
                <label class="form-label">اسلاگ (انگلیسی، اختیاری)</label>
                <input type="text" name="slug" class="form-input" placeholder="مثلاً seo, website">
            </div>
            <div class="form-field" style="display:flex;align-items:center;gap:6px;margin-top:20px;">
                <input type="checkbox" name="is_primary" id="is_primary">
                <label for="is_primary" class="form-label" style="margin:0;">دسته‌بندی اصلی (سایت / سئو)</label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">ثبت دسته‌بندی</button>
    </form>
</div>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">لیست دسته‌بندی‌ها</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>عنوان</th>
                <th>اسلاگ</th>
                <th>اصلی؟</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($categories)): ?>
                <tr><td colspan="5">هنوز دسته‌بندی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?php echo (int)$cat['id']; ?></td>
                        <td><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo ((int)$cat['is_primary']===1)?'بله':'خیر'; ?></td>
                        <td>
                            <button class="btn btn-outline" data-inline-edit-toggle="category-<?php echo (int)$cat['id']; ?>" style="padding-inline:8px;">ویرایش</button>
                            <a href="/categories/delete?id=<?php echo (int)$cat['id']; ?>" class="btn btn-outline" style="padding-inline:8px;color:#b91c1c;margin-top:4px;"
                               onclick="return confirm('این دسته‌بندی حذف شود؟');">حذف</a>
                            <div class="inline-edit" data-inline-edit-box="category-<?php echo (int)$cat['id']; ?>">
                                <form method="post" action="/categories/edit">
                                    <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
                                    <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:6px;">
                                        <div class="form-field">
                                            <label class="form-label">عنوان</label>
                                            <input type="text" name="name" class="form-input"
                                                   value="<?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">اسلاگ</label>
                                            <input type="text" name="slug" class="form-input"
                                                   value="<?php echo htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="form-field" style="display:flex;align-items:center;gap:6px;margin-top:20px;">
                                            <input type="checkbox" name="is_primary" id="is_primary_<?php echo (int)$cat['id']; ?>" <?php echo ((int)$cat['is_primary']===1)?'checked':''; ?>>
                                            <label for="is_primary_<?php echo (int)$cat['id']; ?>" class="form-label" style="margin:0;">اصلی</label>
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
