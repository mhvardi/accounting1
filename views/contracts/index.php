<?php
/** @var array $contracts */
/** @var array $products */
use App\Core\Date;
use App\Core\Str;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">📄</span>
    <span>قراردادها</span>
    <div style="font-size:11px;color:#6b7280;">ثبت قرارداد همراه با آیتم‌های خدمت (هاست، دامنه، سئو و ...)</div>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">ثبت قرارداد جدید + سرویس‌های زیرمجموعه</div>
    </div>
    <form method="post" action="/contracts/create" id="contract-form">
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
                <label class="form-label">توضیحات قرارداد</label>
                <textarea name="note" class="form-input" rows="2"></textarea>
            </div>
        </div>

        <div class="card" style="margin:12px 0;">
            <div class="card-header">
                <div class="card-title">آیتم‌های سرویس و مبالغ</div>
                <div style="font-size:11px;color:#6b7280;">برای هر سرویس، محصول، دوره صورت‌حساب، مبلغ فروش و مبلغ خرید را وارد کنید.</div>
            </div>
            <div style="overflow-x:auto;">
                <table class="table">
                    <thead>
                    <tr>
                        <th style="min-width:140px;">محصول</th>
                        <th>عنوان/شرح</th>
                        <th>دوره</th>
                        <th>فروش (تومان)</th>
                        <th>خرید (تومان)</th>
                        <th>شروع</th>
                        <th>سررسید</th>
                        <th>دامنه / یادداشت</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody id="line-items-body">
                    <tr class="line-item-row">
                        <td>
                            <select name="item_product_id[]" class="form-select item-product">
                                <option value="">انتخاب محصول</option>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?php echo (int)$p['id']; ?>" data-cycle="<?php echo htmlspecialchars($p['billing_cycle'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-price="<?php echo (int)($p['price'] ?? 0); ?>">
                                        <?php echo htmlspecialchars($p['name'] . ' (' . $p['type'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="item_id[]" value="">
                            <input type="hidden" name="item_service_id[]" value="">
                            <select name="item_category_id[]" class="form-select" style="margin-top:6px;">
                                <option value="">دسته مرتبط</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="item_title[]" class="form-input" placeholder="شرح آیتم">
                            <textarea name="item_notes[]" class="form-textarea" rows="1" placeholder="یادداشت فاکتور" style="margin-top:4px;"></textarea>
                        </td>
                        <td><input type="text" name="item_billing_cycle[]" class="form-input item-billing-cycle" placeholder="monthly/annual"></td>
                        <td><input type="text" name="item_sale_amount[]" class="form-input money-input" value="0"></td>
                        <td><input type="text" name="item_cost_amount[]" class="form-input money-input" value="0"></td>
                        <td><input type="text" name="item_start_date[]" class="form-input jalali-picker" placeholder="1404/01/01"></td>
                        <td><input type="text" name="item_next_due_date[]" class="form-input jalali-picker" placeholder="1404/02/01"></td>
                        <td>
                            <input type="text" name="item_domain[]" class="form-input" placeholder="example.com">
                        </td>
                        <td style="text-align:center;">
                            <button type="button" class="btn btn-xs btn-outline remove-line">حذف</button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
                <button type="button" class="btn btn-outline" id="add-line-item">+ افزودن آیتم</button>
                <div style="font-size:12px;color:#6b7280;">پس از ذخیره، هر آیتم به‌عنوان سرویس مستقل ثبت می‌شود.</div>
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
                <th>جمع فروش</th>
                <th>جمع خرید</th>
                <th>دسته</th>
                <th>کارشناس فروش</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($contracts)): ?>
                <tr><td colspan="10">هنوز قراردادی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($contracts as $c): ?>
                    <tr>
                        <td><?php echo (int)$c['id']; ?></td>
                        <td><?php echo htmlspecialchars(Str::beautifyLabel($c['customer_name']), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(Str::beautifyLabel($c['title']), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo Date::jDate($c['start_date']); ?></td>
                        <td><?php echo number_format((int)($c['sale_total'] ?? $c['total_amount'] ?? 0)); ?></td>
                        <td><?php echo number_format((int)($c['cost_total'] ?? $c['total_cost_amount'] ?? 0)); ?></td>
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

<script>
    const tbody = document.getElementById('line-items-body');
    const addBtn = document.getElementById('add-line-item');

    function cloneRow() {
        const first = tbody.querySelector('.line-item-row');
        const clone = first.cloneNode(true);
        clone.querySelectorAll('input, textarea, select').forEach(el => {
            if (el.type === 'hidden') {
                el.value = '';
            } else if (el.tagName === 'SELECT') {
                el.selectedIndex = 0;
            } else {
                el.value = '';
            }
        });
        tbody.appendChild(clone);
    }

    addBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        cloneRow();
    });

    tbody?.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-line')) {
            const rows = tbody.querySelectorAll('.line-item-row');
            if (rows.length > 1) {
                e.target.closest('tr').remove();
            }
        }
    });

    tbody?.addEventListener('change', (e) => {
        if (e.target.classList.contains('item-product')) {
            const opt = e.target.selectedOptions[0];
            const row = e.target.closest('tr');
            const cycleInput = row.querySelector('.item-billing-cycle');
            const saleInput = row.querySelector('input[name="item_sale_amount[]"]');
            const cycle = opt?.dataset.cycle || '';
            const price = opt?.dataset.price || '';
            if (cycle && !cycleInput.value) cycleInput.value = cycle;
            if (price && (!saleInput.value || saleInput.value === '0')) saleInput.value = price;
        }
    });
</script>
