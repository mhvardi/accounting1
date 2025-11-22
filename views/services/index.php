<?php
/** @var array $services */
/** @var array $customers */
/** @var array $products */
/** @var array $categories */
/** @var array $servers */
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">🛰️</span>
    <span>سرویس‌ها / خدمات فعال</span>
    <div style="font-size:11px;color:#6b7280;">انواع خدمات (هاست، دامنه، سئو، طراحی سایت) بدون ماژول محصول جداگانه</div>
</div>

<script>
    const select = document.getElementById('service-product');
    const typeInput = document.getElementById('service-product-type');
    const catSelect = document.getElementById('service-category');
    const catSlug = document.getElementById('service-category-slug');
    const toggleFields = () => {
        const catType = catSelect?.options[catSelect.selectedIndex]?.dataset.type || '';
        const type = catType || select?.options[select.selectedIndex]?.dataset.type || '';
        typeInput.value = type;
        if (catSlug) catSlug.value = catType;
        document.querySelectorAll('.service-fields').forEach(el => {
            el.style.display = 'none';
        });
        if (type === 'hosting') {
            document.querySelectorAll('.service-hosting').forEach(el => el.style.display = 'block');
            document.querySelectorAll('.service-domains').forEach(el => el.style.display = 'block');
            document.querySelectorAll('.service-web').forEach(el => el.style.display = 'block');
        } else if (type === 'domain') {
            document.querySelectorAll('.service-domains').forEach(el => el.style.display = 'block');
        } else if (type === 'seo') {
            document.querySelectorAll('.service-seo').forEach(el => el.style.display = 'block');
        } else {
            document.querySelectorAll('.service-web').forEach(el => el.style.display = 'block');
        }
    };
    select?.addEventListener('change', toggleFields);
    catSelect?.addEventListener('change', toggleFields);
    toggleFields();
</script>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">افزودن سرویس برای مشتری</div>
    </div>
    <form method="post" action="/services/store" class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;">
        <div class="form-field">
            <label class="form-label">مشتری</label>
            <select name="customer_id" class="form-select select-search" required>
                <option value="">انتخاب کنید</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label class="form-label">دسته‌بندی خدمت</label>
            <select name="category_id" class="form-select select-search" id="service-category" required>
                <option value="">انتخاب از دسته‌های خدمات</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo (int)$cat['id']; ?>" data-type="<?php echo htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="category_slug" id="service-category-slug" value="">
        </div>
        <div class="form-field">
            <label class="form-label">طرح/نوع دقیق</label>
            <select name="product_id" class="form-select select-search" id="service-product">
                <option value="">(اختیاری) انتخاب پلن</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?php echo (int)$p['id']; ?>" data-type="<?php echo htmlspecialchars($p['type'], ENT_QUOTES, 'UTF-8'); ?>" data-billing="<?php echo htmlspecialchars($p['billing_cycle'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($p['type'], ENT_QUOTES, 'UTF-8'); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="product_type" id="service-product-type" value="">
        </div>
        <div class="form-field">
            <label class="form-label">وضعیت</label>
            <select name="status" class="form-select">
                <option value="active">فعال</option>
                <option value="pending">در انتظار</option>
                <option value="suspended">معلق</option>
                <option value="cancelled">لغو</option>
            </select>
        </div>
        <div class="form-field">
            <label class="form-label">دسترسی به مشتری</label>
            <label class="chip-toggle"><input type="checkbox" name="access_granted"> دسترسی پنل</label>
        </div>
        <div class="form-field service-fields service-domains service-hosting">
            <label class="form-label">دامنه / سایت</label>
            <input type="text" name="domain" class="form-input" placeholder="example.com">
        </div>
        <div class="form-field service-fields service-hosting">
            <label class="form-label">سرور DirectAdmin</label>
            <select name="server_id" class="form-select select-search">
                <option value="0">انتخاب سرور</option>
                <?php foreach ($servers as $srv): ?>
                    <option value="<?php echo (int)$srv['id']; ?>"><?php echo htmlspecialchars($srv['name'] . ' (' . $srv['hostname'] . ')', ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field service-fields service-hosting">
            <label class="form-label">کاربر هاست/پنل</label>
            <input type="text" name="host_user" class="form-input" placeholder="directadmin user">
            <div style="display:flex;gap:6px;margin-top:4px;align-items:center;">
                <label class="chip-toggle"><input type="checkbox" name="da_sync"> سینک خودکار</label>
                <label class="chip-toggle"><input type="checkbox" name="da_ssl" checked> SSL</label>
                <input type="text" name="da_port" class="form-input" style="width:90px;" value="2223" placeholder="پورت">
            </div>
            <input type="text" name="da_username" class="form-input" placeholder="نام کاربری DA" style="margin-top:6px;">
        </div>
        <div class="form-field service-fields service-seo">
            <label class="form-label">کلمات کلیدی سئو</label>
            <input type="text" name="keywords" class="form-input" placeholder="کلمه۱, کلمه۲">
        </div>
        <div class="form-field service-fields service-seo">
            <label class="form-label">سرچ کنسول (property)</label>
            <input type="text" name="search_property" class="form-input" placeholder="https://example.com">
        </div>
        <div class="form-field service-fields service-domains">
            <label class="form-label">نیم‌سرورها</label>
            <div class="grid" style="grid-template-columns:repeat(2,minmax(0,1fr));gap:4px;">
                <input type="text" name="ns1" class="form-input" placeholder="NS1">
                <input type="text" name="ns2" class="form-input" placeholder="NS2">
                <input type="text" name="ns3" class="form-input" placeholder="NS3">
                <input type="text" name="ns4" class="form-input" placeholder="NS4">
                <input type="text" name="ns5" class="form-input" placeholder="NS5">
            </div>
        </div>
        <div class="form-field service-fields service-web">
            <label class="form-label">دسترسی سایت</label>
            <input type="text" name="site_username" class="form-input" placeholder="username">
            <input type="text" name="site_password" class="form-input" placeholder="password" style="margin-top:4px;">
        </div>
        <div class="form-field service-fields service-web">
            <label class="form-label">توضیح قرارداد / پشتیبانی</label>
            <textarea name="billing_notes" class="form-textarea" rows="2" placeholder="اقساط، پشتیبانی سالانه و ..."></textarea>
        </div>
        <div class="form-field">
            <label class="form-label">تاریخ شروع</label>
            <input type="text" name="start_date" class="form-input jalali-picker" placeholder="مثلاً 1404/08/01">
        </div>
        <div class="form-field">
            <label class="form-label">تاریخ تمدید / سررسید</label>
            <input type="text" name="next_due_date" class="form-input jalali-picker" placeholder="مثلاً 1404/09/01">
        </div>
        <div>
            <button type="submit" class="btn btn-primary">ثبت سرویس</button>
        </div>
    </form>
</div>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">سرویس‌های ثبت‌شده</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>مشتری</th>
                <th>دسته/نوع خدمت</th>
                <th>دامنه/سایت</th>
                <th>وضعیت</th>
                <th>شروع</th>
                <th>سررسید</th>
                <th>دسترسی</th>
                <th>ویرایش</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($services)): ?>
                <tr><td colspan="9">سرویسی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($services as $s): $meta = json_decode($s['meta_json'] ?? '', true) ?: []; ?>
                    <tr>
                        <td><?php echo (int)$s['id']; ?></td>
                        <td><?php echo htmlspecialchars($s['customer_name'] ?? '---', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php echo htmlspecialchars($s['category_name'] ?? ($s['product_name'] ?? '---'), ENT_QUOTES, 'UTF-8'); ?>
                            <div class="micro-copy" style="margin-top:2px;">نوع: <?php echo htmlspecialchars($s['category_slug'] ?? $s['product_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($meta['domain'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($s['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo \App\Core\Date::jDate($s['start_date']); ?></td>
                        <td><?php echo \App\Core\Date::jDate($s['next_due_date']); ?></td>
                        <td><?php echo !empty($s['access_granted']) ? 'بله' : '—'; ?></td>
                        <td>
                            <form method="post" action="/services/update" style="display:flex;gap:4px;flex-wrap:wrap;align-items:center;">
                                <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
                                <input type="hidden" name="product_type" value="<?php echo htmlspecialchars($s['product_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                  <select name="status" class="form-select">
                                      <?php foreach (['active'=>'فعال','pending'=>'در انتظار','suspended'=>'معلق','cancelled'=>'لغو'] as $key=>$label): ?>
                                          <option value="<?php echo $key; ?>" <?php echo $s['status']===$key?'selected':''; ?>><?php echo $label; ?></option>
                                      <?php endforeach; ?>
                                  </select>
                                  <select name="category_id" class="form-select select-search">
                                      <option value="0">دسته خدمت</option>
                                      <?php foreach ($categories as $cat): ?>
                                          <option value="<?php echo (int)$cat['id']; ?>" <?php echo ($s['category_id'] ?? 0)==$cat['id']?'selected':''; ?>><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                      <?php endforeach; ?>
                                  </select>
                                  <label class="chip-toggle"><input type="checkbox" name="access_granted" <?php echo !empty($s['access_granted'])?'checked':''; ?>> دسترسی</label>
                                <input type="text" name="domain" value="<?php echo htmlspecialchars($meta['domain'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-input" style="width:150px;">
                                <input type="text" name="host_user" value="<?php echo htmlspecialchars($meta['host_user'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-input" style="width:120px;">
                                <input type="text" name="keywords" value="<?php echo htmlspecialchars(implode(',', $meta['keywords'] ?? []), ENT_QUOTES, 'UTF-8'); ?>" class="form-input" style="width:160px;">
                                <input type="text" name="da_username" value="<?php echo htmlspecialchars($meta['panel']['directadmin_username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-input" style="width:120px;">
                                <label class="chip-toggle"><input type="checkbox" name="da_sync" <?php echo !empty($meta['panel']['sync'])?'checked':''; ?>> DA</label>
                                <input type="text" name="search_property" value="<?php echo htmlspecialchars($meta['search_console']['property'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-input" style="width:160px;">
                                  <input type="text" name="start_date" value="<?php echo \App\Core\Date::jDate($s['start_date']); ?>" class="form-input jalali-picker" style="width:110px;">
                                  <input type="text" name="next_due_date" value="<?php echo \App\Core\Date::jDate($s['next_due_date']); ?>" class="form-input jalali-picker" style="width:110px;">
                                <button class="btn btn-outline" type="submit">بروزرسانی</button>
                                <a class="btn btn-outline btn-danger" href="/services/delete?id=<?php echo (int)$s['id']; ?>" onclick="return confirm('حذف سرویس؟');">حذف</a>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
