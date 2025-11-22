<?php
/** @var array $customers */
use App\Core\Date;
use App\Core\Str;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">🧑‍💼</span>
    <span>مدیریت مشتریان</span>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">افزودن مشتری جدید</div>
    </div>
    <form method="post" action="/customers/create">
        <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;">
            <div class="form-field">
                <label class="form-label">نام مشتری</label>
                <input type="text" name="name" class="form-input" required>
            </div>
            <div class="form-field">
                <label class="form-label">موبایل</label>
                <input type="text" name="phone" class="form-input">
            </div>
            <div class="form-field">
                <label class="form-label">ایمیل</label>
                <input type="email" name="email" class="form-input">
            </div>
            <div class="form-field">
                <label class="form-label">توضیحات</label>
                <input type="text" name="note" class="form-input">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">ثبت مشتری</button>
    </form>
</div>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">لیست مشتریان</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>نام</th>
                <th>موبایل</th>
                <th>ایمیل</th>
                <th>توضیحات</th>
                <th>تاریخ ایجاد</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($customers)): ?>
                <tr><td colspan="7">هنوز مشتری ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><?php echo (int)$c['id']; ?></td>
                        <td><?php echo htmlspecialchars(Str::beautifyLabel($c['name']), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($c['phone'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($c['note'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo !empty($c['created_at']) ? Date::jDate($c['created_at']) : ''; ?></td>
                        <td>
                            <a href="/customers/profile?id=<?php echo (int)$c['id']; ?>" class="btn btn-primary" style="padding-inline:8px;">پروفایل</a>
                            <button class="btn btn-outline" data-inline-edit-toggle="customer-<?php echo (int)$c['id']; ?>" style="padding-inline:8px;">ویرایش</button>
                            <a href="/customers/delete?id=<?php echo (int)$c['id']; ?>" class="btn btn-outline" style="padding-inline:8px;color:#b91c1c;margin-top:4px;"
                               onclick="return confirm('این مشتری حذف شود؟');">حذف</a>
                            <div class="inline-edit" data-inline-edit-box="customer-<?php echo (int)$c['id']; ?>">
                                <form method="post" action="/customers/edit">
                                    <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                                    <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:6px;">
                                        <div class="form-field">
                                            <label class="form-label">نام</label>
                                            <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">موبایل</label>
                                            <input type="text" name="phone" class="form-input" value="<?php echo htmlspecialchars($c['phone'], ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">ایمیل</label>
                                            <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">توضیحات</label>
                                            <input type="text" name="note" class="form-input" value="<?php echo htmlspecialchars($c['note'], ENT_QUOTES, 'UTF-8'); ?>">
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
