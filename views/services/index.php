<?php
/** @var array $services */
/** @var array $customers */
/** @var array $products */
/** @var array $categories */
/** @var array $servers */
/** @var array $serversMap */
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">🛰️</span>
    <span>سرویس‌ها / خدمات فعال</span>
    <div style="font-size:11px;color:#6b7280;">لیست خدمات ایجادشده از طریق قراردادها + ویرایش سریع</div>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">ثبت سرویس جدید</div>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <div style="font-size:12px;color:#374151;max-width:580px;">
            ثبت سرویس و دریافت مبالغ فروش/خرید اکنون از طریق فرم قرارداد انجام می‌شود. برای افزودن سرویس جدید، یک قرارداد بسازید و آیتم مربوط را اضافه کنید.
        </div>
        <a href="/contracts" class="btn btn-primary">ثبت قرارداد جدید</a>
    </div>
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
                <th>دسته/نوع</th>
                <th>دامنه/سایت</th>
                <th>فروش</th>
                <th>خرید</th>
                <th>دوره</th>
                <th>قرارداد</th>
                <th>سرور</th>
                <th>سینک DA</th>
                <th>پیام سینک</th>
                <th>وضعیت</th>
                <th>شروع</th>
                <th>سررسید</th>
                <th>دسترسی</th>
                <th>ویرایش</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($services)): ?>
                <tr><td colspan="16">سرویسی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($services as $s): $meta = json_decode($s['meta_json'] ?? '', true) ?: []; ?>
                    <tr>
                        <td><?php echo (int)$s['id']; ?></td>
                        <td>
                            <a href="/customers/profile?id=<?php echo (int)($s['customer_id'] ?? 0); ?>" class="link-soft">
                                <?php echo htmlspecialchars($s['customer_name'] ?? '---', ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($s['category_name'] ?? ($s['product_name'] ?? '---'), ENT_QUOTES, 'UTF-8'); ?>
                            <div class="micro-copy" style="margin-top:2px;">نوع: <?php echo htmlspecialchars($s['category_slug'] ?? $s['product_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($meta['domain'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo number_format((int)($s['sale_amount'] ?? 0)); ?></td>
                        <td><?php echo number_format((int)($s['cost_amount'] ?? 0)); ?></td>
                        <td><?php echo htmlspecialchars($s['billing_cycle'] ?? ($s['product_billing_cycle'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $s['contract_id'] ? '#'.$s['contract_id'] : '—'; ?></td>
                        <td>
                            <?php $srvId = (int)($meta['panel']['server_id'] ?? 0); ?>
                            <?php echo $srvId ? htmlspecialchars($serversMap[$srvId]['hostname'] ?? 'نامشخص', ENT_QUOTES, 'UTF-8') : '—'; ?>
                            <div class="micro-copy" style="direction:ltr;">
                                <?php echo $srvId ? htmlspecialchars($serversMap[$srvId]['hostname'] ?? '', ENT_QUOTES, 'UTF-8') : ''; ?>
                            </div>
                        </td>
                        <td>
                            <?php $panel = $meta['panel'] ?? []; ?>
                            <div><?php echo htmlspecialchars($panel['sync_status'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="micro-copy"><?php echo !empty($panel['sync_at']) ? htmlspecialchars($panel['sync_at'], ENT_QUOTES, 'UTF-8') : ''; ?></div>
                        </td>
                        <td>
                            <div class="micro-copy" style="max-width:200px;white-space:normal;">
                                <?php echo htmlspecialchars($panel['sync_message'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($s['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo \App\Core\Date::jDate($s['start_date']); ?></td>
                        <td><?php echo \App\Core\Date::jDate($s['next_due_date']); ?></td>
                        <td><?php echo !empty($s['access_granted']) ? 'بله' : '—'; ?></td>
                        <td>
                            <form method="post" action="/services/update" style="display:flex;gap:4px;flex-wrap:wrap;align-items:center;">
                                <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
                                <input type="hidden" name="product_type" value="<?php echo htmlspecialchars($s['product_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="contract_id" value="<?php echo (int)($s['contract_id'] ?? 0); ?>">
                                <select name="status" class="form-select">
                                    <?php foreach (['active'=>'فعال','pending'=>'در انتظار','suspended'=>'معلق','cancelled'=>'لغو'] as $key=>$label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $s['status']===$key?'selected':''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="category_id" class="form-select">
                                    <option value="0">دسته خدمت</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo (int)$cat['id']; ?>" <?php echo ($s['category_id'] ?? 0)==$cat['id']?'selected':''; ?>><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="billing_cycle" value="<?php echo htmlspecialchars($s['billing_cycle'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-input" style="width:110px;" placeholder="monthly">
                                <input type="text" name="sale_amount" value="<?php echo number_format((int)($s['sale_amount'] ?? 0)); ?>" class="form-input money-input" style="width:120px;" placeholder="فروش">
                                <input type="text" name="cost_amount" value="<?php echo number_format((int)($s['cost_amount'] ?? 0)); ?>" class="form-input money-input" style="width:120px;" placeholder="خرید">
                                <label class="chip-toggle"><input type="checkbox" name="access_granted" <?php echo !empty($s['access_granted'])?'checked':''; ?>> دسترسی</label>
                                <input type="text" name="domain" value="<?php echo htmlspecialchars($meta['domain'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-input" style="width:150px;">
                                <input type="text" name="host_user" value="<?php echo htmlspecialchars($meta['host_user'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-input" style="width:120px;">
                                <input type="text" name="keywords" value="<?php echo htmlspecialchars(implode(',', $meta['keywords'] ?? []), ENT_QUOTES, 'UTF-8'); ?>" class="form-input" style="width:160px;">
                                <input type="text" name="da_username" value="<?php echo htmlspecialchars($meta['panel']['directadmin_username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-input" style="width:120px;">
                                <label class="chip-toggle"><input type="checkbox" name="da_sync" <?php echo !empty($meta['panel']['sync'])?'checked':''; ?>> DA</label>
                                <select name="server_id" class="form-select" style="width:140px;">
                                    <option value="0">سرور DirectAdmin</option>
                                    <?php foreach ($servers as $srv): ?>
                                        <option value="<?php echo (int)$srv['id']; ?>" <?php echo ($meta['panel']['server_id'] ?? 0)==$srv['id']?'selected':''; ?>><?php echo htmlspecialchars($srv['hostname'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="da_action" class="form-select" style="width:140px;">
                                    <option value="">عملیات DA</option>
                                    <option value="sync">Sync</option>
                                    <option value="create">Create</option>
                                    <option value="suspend">Suspend</option>
                                    <option value="unsuspend">Unsuspend</option>
                                    <option value="delete">Delete</option>
                                </select>
                                <label class="chip-toggle"><input type="checkbox" name="da_log_only"> فقط لاگ</label>
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
