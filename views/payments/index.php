<?php
/** @var array $payments */
/** @var array $contracts */
/** @var array $customers */
/** @var array $invoices */
use App\Core\Date;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">💳</span>
    <span>پرداخت‌ها</span>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">ثبت پرداخت جدید</div>
    </div>
    <form method="post" action="/payments/create" data-payment-form>
        <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;">
            <div class="form-field">
                <label class="form-label">قرارداد (اختیاری)</label>
                <select name="contract_id" class="form-select" data-contract-select>
                    <option value="">بدون قرارداد</option>
                    <?php foreach ($contracts as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>">
                            <?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="hint" data-contract-summary style="margin-top:6px;font-size:12px;color:#6b7280;">
                    بدون قرارداد - مبلغ را دستی وارد کنید.
                </div>
            </div>
            <div class="form-field">
                <label class="form-label">فاکتور (اختیاری)</label>
                <select name="invoice_id" class="form-select">
                    <option value="">بدون فاکتور</option>
                    <?php foreach ($invoices as $inv): ?>
                        <option value="<?php echo (int)$inv['id']; ?>">
                            <?php echo htmlspecialchars(($inv['indicator_code'] ?? '') . ' - ' . ($inv['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="hint" style="margin-top:6px;font-size:12px;color:#6b7280;">در صورت انتخاب فاکتور، مشتری و قرارداد متناظر پر می‌شود.</div>
            </div>
            <div class="form-field">
                <label class="form-label">مشتری</label>
                <select name="customer_id" class="form-select" data-customer-select>
                    <option value="">انتخاب مشتری</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo (int)$customer['id']; ?>"><?php echo htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="hint" style="margin-top:6px;font-size:12px;color:#6b7280;">قابلیت جست‌وجو (Choices)</div>
            </div>
            <div class="form-field">
                <label class="form-label">مبلغ (تومان)</label>
                <input type="text" name="amount" class="form-input money-input" value="0" required data-amount-input>
            </div>
            <div class="form-field">
                <label class="form-label">تاریخ پرداخت (شمسی)</label>
                <input type="text" name="pay_date" class="form-input jalali-picker" placeholder="مثلاً 1403/08/15" value="<?php echo Date::j('Y/m/d'); ?>">
            </div>
            <div class="form-field">
                <label class="form-label">روش پرداخت</label>
                <input type="text" name="method" class="form-input" placeholder="کارت به کارت، درگاه، نقدی و ...">
            </div>
            <div class="form-field">
                <label class="form-label">وضعیت</label>
                <select name="status" class="form-select">
                    <option value="paid">پرداخت شده</option>
                    <option value="pending">در انتظار</option>
                    <option value="refunded">مرجوع/بازگشت</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">توضیحات</label>
                <input type="text" name="note" class="form-input">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">ثبت پرداخت</button>
    </form>
</div>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">لیست پرداخت‌ها</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>قرارداد</th>
                <th>فاکتور</th>
                <th>مشتری</th>
                <th>مبلغ قرارداد</th>
                <th>مبلغ (تومان)</th>
                <th>تاریخ پرداخت</th>
                <th>روش</th>
                <th>وضعیت</th>
                <th>توضیحات</th>
                <th>عملیات</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="10">هنوز پرداختی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?php echo (int)$p['id']; ?></td>
                        <td><?php echo htmlspecialchars($p['contract_title'] ?? 'بدون قرارداد', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $p['invoice_code'] ? htmlspecialchars($p['invoice_code'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                        <td><?php echo htmlspecialchars($p['customer_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $p['contract_amount'] ? number_format((int)$p['contract_amount']) : '—'; ?></td>
                        <td><?php echo number_format((int)$p['amount']); ?></td>
                        <td><?php echo $p['pay_date'] ? Date::jDate($p['pay_date']) : ($p['paid_at'] ? Date::jDate($p['paid_at']) : ''); ?></td>
                        <td><?php echo htmlspecialchars($p['method'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php
                            $map = ['paid'=>'پرداخت شده','pending'=>'در انتظار','refunded'=>'مرجوع شده'];
                            echo $map[$p['status']] ?? $p['status'];
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($p['note'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <button class="btn btn-outline" data-inline-edit-toggle="payment-<?php echo (int)$p['id']; ?>" style="padding-inline:8px;">ویرایش</button>
                            <a href="/payments/delete?id=<?php echo (int)$p['id']; ?>" class="btn btn-outline" style="padding-inline:8px;color:#b91c1c;margin-top:4px;"
                               onclick="return confirm('این پرداخت حذف شود؟');">حذف</a>
                            <div class="inline-edit" data-inline-edit-box="payment-<?php echo (int)$p['id']; ?>">
                                <form method="post" action="/payments/edit" data-payment-form>
                                    <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                    <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:6px;">
                                        <div class="form-field">
                                            <label class="form-label">قرارداد</label>
                                            <select name="contract_id" class="form-select" data-contract-select>
                                                <option value="">بدون قرارداد</option>
                                                <?php foreach ($contracts as $c): ?>
                                                    <option value="<?php echo (int)$c['id']; ?>"
                                                        <?php echo ($p['contract_id']==$c['id'])?'selected':''; ?>>
                                                        <?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="hint" data-contract-summary style="margin-top:4px;font-size:12px;color:#6b7280;">
                                                <?php if ($p['contract_amount']): ?>
                                                    مبلغ قرارداد: <?php echo number_format((int)$p['contract_amount']); ?> تومان
                                                <?php else: ?>
                                                    بدون قرارداد
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">فاکتور</label>
                                            <select name="invoice_id" class="form-select">
                                                <option value="">بدون فاکتور</option>
                                                <?php foreach ($invoices as $inv): ?>
                                                    <option value="<?php echo (int)$inv['id']; ?>" <?php echo ($p['invoice_id']==$inv['id'])?'selected':''; ?>>
                                                        <?php echo htmlspecialchars(($inv['indicator_code'] ?? '') . ' - ' . ($inv['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">مشتری</label>
                                            <select name="customer_id" class="form-select" data-customer-select>
                                                <option value="">انتخاب مشتری</option>
                                                <?php foreach ($customers as $customer): ?>
                                                    <option value="<?php echo (int)$customer['id']; ?>" <?php echo ($p['customer_id']==$customer['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">مبلغ (تومان)</label>
                                            <input type="text" name="amount" class="form-input money-input" value="<?php echo number_format((int)$p['amount']); ?>" data-amount-input>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">تاریخ پرداخت (شمسی)</label>
                                            <input type="text" name="pay_date" class="form-input jalali-picker"
                                                   value="<?php echo $p['pay_date'] ? Date::jDate($p['pay_date']) : ''; ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">روش</label>
                                            <input type="text" name="method" class="form-input" value="<?php echo htmlspecialchars($p['method'], ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">وضعیت</label>
                                            <select name="status" class="form-select">
                                                <option value="paid" <?php echo $p['status']==='paid'?'selected':''; ?>>پرداخت شده</option>
                                                <option value="pending" <?php echo $p['status']==='pending'?'selected':''; ?>>در انتظار</option>
                                                <option value="refunded" <?php echo $p['status']==='refunded'?'selected':''; ?>>مرجوع/بازگشت</option>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label class="form-label">توضیحات</label>
                                            <input type="text" name="note" class="form-input" value="<?php echo htmlspecialchars($p['note'], ENT_QUOTES, 'UTF-8'); ?>">
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

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    (function(){
        function formatMoney(val){
            const digits = String(val).replace(/[^0-9]/g,'');
            if (!digits) return '';
            return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function initPaymentForm(form){
            const contractSelect = form.querySelector('[data-contract-select]');
            const customerSelect = form.querySelector('[data-customer-select]');
            const amountInput    = form.querySelector('[data-amount-input]');
            const summaryBox     = form.querySelector('[data-contract-summary]');

            let customerChoices = null;
            if (customerSelect) {
                customerChoices = new Choices(customerSelect, {
                    shouldSort: false,
                    itemSelectText: '',
                    searchPlaceholderValue: 'جست‌وجوی مشتری...',
                });
            }

            async function fetchContractInfo(id){
                if (!id) {
                    if (summaryBox) summaryBox.textContent = 'بدون قرارداد - مبلغ را دستی وارد کنید.';
                    return;
                }
                if (summaryBox) summaryBox.textContent = 'در حال دریافت اطلاعات قرارداد...';
                try {
                    const res  = await fetch('/payments/contract-info?id=' + encodeURIComponent(id));
                    const data = await res.json();
                    if (!data.ok) {
                        if (summaryBox) summaryBox.textContent = data.message || 'خطا در واکشی اطلاعات قرارداد';
                        return;
                    }
                    if (summaryBox) {
                        summaryBox.innerHTML = 'مبلغ قرارداد: ' + formatMoney(data.contract_amount) + ' تومان' +
                            ' | مانده: ' + formatMoney(data.remaining) + ' تومان';
                    }
                    if (amountInput) {
                        const suggested = data.remaining || data.contract_amount || '';
                        amountInput.value = formatMoney(suggested);
                    }
                    if (customerChoices && data.customer_id) {
                        customerChoices.setChoiceByValue(String(data.customer_id));
                    }
                } catch (e) {
                    if (summaryBox) summaryBox.textContent = 'عدم برقراری ارتباط با سرور';
                }
            }

            if (contractSelect) {
                contractSelect.addEventListener('change', function(){
                    fetchContractInfo(contractSelect.value);
                });
                if (contractSelect.value) {
                    fetchContractInfo(contractSelect.value);
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function(){
            document.querySelectorAll('[data-payment-form]').forEach(initPaymentForm);
        });
    })();
</script>
