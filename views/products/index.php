<?php
/** @var array $products */
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">🛒</span>
    <span>محصولات و سرویس‌ها</span>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">افزودن محصول جدید</div>
    </div>
    <form method="post" action="/products/store" class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;">
        <div class="form-field">
            <label class="form-label">نام محصول</label>
            <input type="text" name="name" class="form-input" required>
        </div>
        <div class="form-field">
            <label class="form-label">نوع</label>
            <select name="type" class="form-select">
                <option value="hosting">هاست</option>
                <option value="domain">دامنه</option>
                <option value="seo">سئو</option>
                <option value="service">سرویس/پشتیبانی</option>
                <option value="other">سایر</option>
            </select>
        </div>
        <div class="form-field">
            <label class="form-label">دوره پرداخت</label>
            <select name="billing_cycle" class="form-select">
                <option value="monthly">ماهانه</option>
                <option value="quarterly">سه‌ماهه</option>
                <option value="semiannual">شش‌ماهه</option>
                <option value="annual">سالانه</option>
                <option value="lifetime">نامحدود</option>
                <option value="free">رایگان</option>
            </select>
        </div>
        <div class="form-field">
            <label class="form-label">مبلغ پایه (تومان)</label>
            <input type="text" name="price" class="form-input money-input" value="0">
        </div>
        <div class="form-field" style="grid-column:span 2;">
            <label class="form-label">توضیحات</label>
            <input type="text" name="description" class="form-input" placeholder="ویژگی‌ها و پیکربندی">
        </div>
        <div class="form-field">
            <label class="form-label">سینک DirectAdmin</label>
            <label class="chip-toggle"><input type="checkbox" name="da_sync"> فعال</label>
        </div>
        <div class="form-field">
            <label class="form-label">DNS دامنه</label>
            <label class="chip-toggle"><input type="checkbox" name="domain_dns"> شامل مدیریت DNS</label>
        </div>
        <div>
            <button type="submit" class="btn btn-primary">ثبت محصول</button>
        </div>
    </form>
</div>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">لیست محصولات</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>نام</th>
                <th>نوع</th>
                <th>دوره</th>
                <th>قیمت (تومان)</th>
                <th>سینک</th>
                <th>ویرایش</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="7">محصولی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($products as $p): $meta = json_decode($p['meta_json'] ?? '', true) ?: []; ?>
                    <tr>
                        <td><?php echo (int)$p['id']; ?></td>
                        <td><?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($p['type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($p['billing_cycle'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo number_format((int)$p['price']); ?></td>
                        <td><?php echo !empty($meta['directadmin']['sync']) ? 'فعال' : '—'; ?></td>
                        <td>
                            <form method="post" action="/products/update" style="display:flex;gap:4px;align-items:center;flex-wrap:wrap;">
                                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                <input type="text" name="name" value="<?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?>" class="form-input" style="width:140px;">
                                <select name="type" class="form-select">
                                    <?php foreach (['hosting','domain','seo','service','other'] as $opt): ?>
                                        <option value="<?php echo $opt; ?>" <?php echo $p['type']===$opt?'selected':''; ?>><?php echo $opt; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="billing_cycle" class="form-select">
                                    <?php foreach (['monthly'=>'ماهانه','quarterly'=>'سه‌ماهه','semiannual'=>'شش‌ماهه','annual'=>'سالانه','lifetime'=>'نامحدود','free'=>'رایگان'] as $key=>$label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $p['billing_cycle']===$key?'selected':''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="price" value="<?php echo (int)$p['price']; ?>" class="form-input" style="width:90px;">
                                <input type="text" name="description" value="<?php echo htmlspecialchars($meta['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-input" style="width:160px;">
                                <label class="chip-toggle"><input type="checkbox" name="da_sync" <?php echo !empty($meta['directadmin']['sync'])?'checked':''; ?>> DA</label>
                                <label class="chip-toggle"><input type="checkbox" name="domain_dns" <?php echo !empty($meta['domain']['includes_dns'])?'checked':''; ?>> DNS</label>
                                <button class="btn btn-outline" type="submit">ذخیره</button>
                                <a class="btn btn-outline btn-danger" href="/products/delete?id=<?php echo (int)$p['id']; ?>" onclick="return confirm('حذف محصول؟');">حذف</a>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
