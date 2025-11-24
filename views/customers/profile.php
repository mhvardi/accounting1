<?php
/**
 * @var array $customer
 * @var array $contracts
 * @var int $contractTotal
 * @var int $paidTotal
 * @var int $dueTotal
 * @var array $payments
 * @var array $serversMap
 * @var array $domains
 * @var array $hostingAccounts
 * @var array $syncLogs
 * @var array $auditLogs
 * @var array $notifications
 * @var array $smsLogs
 * @var string $registrarBalance
 * @var string $resellerBalance
 * @var bool $showRegistrarResellerBalances
 * @var array $walletAccount
 * @var array $walletTransactions
 */
use App\Core\Date;
use App\Core\Str;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">📇</span>
    <span>پروفایل مشتری: <?php echo htmlspecialchars(Str::beautifyLabel($customer['name']), ENT_QUOTES, 'UTF-8'); ?></span>
</div>

<div id="action-alert" class="alert" style="display:none;"></div>

<div style="margin-bottom:12px;">
    <div class="tab-controls" style="display:flex;gap:6px;flex-wrap:wrap;">
        <button class="btn btn-outline tab-btn active" data-tab="overview">نمای کلی</button>
        <button class="btn btn-outline tab-btn" data-tab="contracts">قراردادها / پرداخت‌ها</button>
        <button class="btn btn-outline tab-btn" data-tab="services">دامنه / هاست</button>
        <button class="btn btn-outline tab-btn" data-tab="notifications">اعلان و لاگ‌ها</button>
        <button class="btn btn-outline tab-btn" data-tab="sms">لاگ پیامک</button>
        <button class="btn btn-outline tab-btn" data-tab="wallet">کیف پول</button>
    </div>
</div>

<div class="tab-panel active" id="tab-overview" style="display:block;">
    <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;">
        <div class="card-soft">
            <div class="form-label">جمع قراردادها</div>
            <div class="kpi-value"><?php echo number_format($contractTotal); ?></div>
        </div>
        <div class="card-soft">
            <div class="form-label">مبالغ پرداخت‌شده</div>
            <div class="kpi-value" style="color:#16a34a;">
                <?php echo number_format($paidTotal); ?>
            </div>
        </div>
        <div class="card-soft">
            <div class="form-label">مانده قابل دریافت</div>
            <div class="kpi-value" style="color:<?php echo $dueTotal >= 0 ? '#b45309' : '#16a34a'; ?>;">
                <?php echo number_format($dueTotal); ?>
            </div>
        </div>
    </div>

    <?php if (!empty($showRegistrarResellerBalances)): ?>
        <div class="card-soft" style="margin-top:10px;">
            <div class="card-header">
                <div class="card-title">اعتبارات رجیسترار / ریسلر</div>
            </div>
            <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:8px;">
                <div class="chip">اعتبار رجیسترار: <?php echo htmlspecialchars($registrarBalance, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="chip">اعتبار ریسلر: <?php echo htmlspecialchars($resellerBalance, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="chip">کیف پول: <?php echo number_format((int)($walletAccount['balance'] ?? 0)); ?> ریال</div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="tab-panel" id="tab-contracts" style="display:none;">
    <div class="card-soft">
        <div class="card-header">
            <div class="card-title">قراردادها</div>
        </div>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>عنوان</th>
                    <th>دسته</th>
                    <th>کارشناس فروش</th>
                    <th>مبلغ</th>
                    <th>تاریخ شروع</th>
                    <th>وضعیت</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($contracts)): ?>
                    <tr><td colspan="7">قراردادی ثبت نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($contracts as $c): ?>
                        <tr>
                            <td><?php echo (int)$c['id']; ?></td>
                            <td><?php echo htmlspecialchars(Str::beautifyLabel($c['title']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($c['category_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($c['employee_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo number_format((int)$c['total_amount']); ?></td>
                            <td><?php echo Date::jDate($c['start_date']); ?></td>
                            <td><?php echo htmlspecialchars($c['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-soft" style="margin-top:10px;">
        <div class="card-header">
            <div class="card-title">پرداخت‌ها</div>
        </div>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>قرارداد</th>
                    <th>مبلغ</th>
                    <th>تاریخ پرداخت</th>
                    <th>وضعیت</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="5">پرداختی ثبت نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?php echo (int)$p['id']; ?></td>
                            <td><?php echo htmlspecialchars(Str::beautifyLabel($p['contract_title'] ?: 'بدون قرارداد'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo number_format((int)$p['amount']); ?></td>
                            <td><?php echo $p['pay_date'] ? Date::jDate($p['pay_date']) : ($p['paid_at'] ? Date::jDate($p['paid_at']) : ''); ?></td>
                            <td><?php echo htmlspecialchars($p['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="tab-panel" id="tab-services" style="display:none;">
    <div style="margin-bottom:10px;display:flex;justify-content:flex-end;">
        <a class="btn btn-outline" href="/domains">دامنه‌های سینک‌نشده</a>
    </div>
    <div class="card-soft">
        <div class="card-header">
            <div class="card-title">دامنه‌ها</div>
        </div>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>دامنه</th>
                    <th>وضعیت</th>
                    <th>انقضا</th>
                    <th>DNS / WHOIS</th>
                    <th>آخرین سینک</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($domains)): ?>
                    <tr><td colspan="7">دامنه‌ای برای این مشتری ثبت نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($domains as $dom): ?>
                        <tr>
                            <td><?php echo (int)$dom['id']; ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($dom['domain_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="micro-copy">قفل: <?php echo htmlspecialchars($dom['lock_status'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($dom['status'] ?? 'نامشخص', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($dom['expires_at'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="max-width:260px;">
                                <div class="micro-copy">NS: <?php echo htmlspecialchars(implode(' | ', $dom['nameservers'] ?? []), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="micro-copy">DNS رکوردها: <?php echo htmlspecialchars((string)count($dom['dns_records'] ?? []), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="micro-copy">WHOIS: <?php echo htmlspecialchars($dom['whois']['registrant'] ?? ($dom['whois']['registrar'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($dom['last_sync_at'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="micro-copy"><?php echo htmlspecialchars($dom['remote_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td style="display:flex;gap:6px;flex-wrap:wrap;">
                                <button class="btn btn-outline" onclick="handleDomainAction(<?php echo (int)$dom['id']; ?>,'sync')">سینک</button>
                                <button class="btn btn-outline" onclick="handleDomainAction(<?php echo (int)$dom['id']; ?>,'suspend')">ساسپند</button>
                                <button class="btn btn-outline" onclick="handleDomainAction(<?php echo (int)$dom['id']; ?>,'unsuspend')">آن‌ساسپند</button>
                                <button class="btn btn-outline" onclick="handleDomainAction(<?php echo (int)$dom['id']; ?>,'renew')">تمدید</button>
                                <button class="btn btn-outline" onclick="handleDomainAction(<?php echo (int)$dom['id']; ?>,'whois')">WHOIS</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-soft" style="margin-top:10px;">
        <div class="card-header">
            <div class="card-title">سرویس‌های هاستینگ</div>
        </div>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>دامنه اصلی</th>
                    <th>سرور</th>
                    <th>کاربر</th>
                    <th>مصرف</th>
                    <th>آخرین سینک</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($hostingAccounts)): ?>
                    <tr><td colspan="8">هاست فعالی ثبت نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($hostingAccounts as $acc): ?>
                        <tr>
                            <td><?php echo (int)$acc['id']; ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($acc['primary_domain'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="micro-copy">پکیج: <?php echo htmlspecialchars($acc['package_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($acc['server_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <div class="micro-copy">کاربر: <?php echo htmlspecialchars($acc['da_username'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="micro-copy">سرور: <?php echo htmlspecialchars($acc['hostname'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td>
                                <div class="micro-copy">دیسک: <?php echo number_format((int)$acc['usage_disk_mb']); ?> MB</div>
                                <div class="micro-copy">ترافیک: <?php echo number_format((int)$acc['usage_bw_mb']); ?> MB</div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($acc['last_sync_at'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="micro-copy"><?php echo htmlspecialchars($acc['remote_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($acc['status'] ?? 'pending', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="display:flex;gap:6px;flex-wrap:wrap;">
                                <button class="btn btn-outline" onclick="handleHostingAction(<?php echo (int)$acc['id']; ?>,'sync')">سینک</button>
                                <button class="btn btn-outline btn-danger" onclick="handleHostingAction(<?php echo (int)$acc['id']; ?>,'suspend')">ساسپند</button>
                                <button class="btn btn-outline" onclick="handleHostingAction(<?php echo (int)$acc['id']; ?>,'unsuspend')">آن‌ساسپند</button>
                                <button class="btn btn-outline" onclick="handleHostingAction(<?php echo (int)$acc['id']; ?>,'reconcile')">آشتی</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="tab-panel" id="tab-notifications" style="display:none;">
    <div class="card-soft">
        <div class="card-header">
            <div class="card-title">اعلان‌ها</div>
        </div>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>نوع</th>
                    <th>عنوان</th>
                    <th>تاریخ</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($notifications)): ?>
                    <tr><td colspan="4">اعلانی ثبت نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($notifications as $note): ?>
                        <tr>
                            <td><?php echo (int)$note['id']; ?></td>
                            <td><?php echo htmlspecialchars($note['type'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="micro-copy" style="white-space:normal;max-width:240px;">
                                <?php echo htmlspecialchars($note['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td><?php echo htmlspecialchars($note['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-soft" style="margin-top:10px;">
        <div class="card-header">
            <div class="card-title">لاگ سینک</div>
        </div>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>نوع</th>
                    <th>عملیات</th>
                    <th>موفق</th>
                    <th>پیام</th>
                    <th>تاریخ</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($syncLogs)): ?>
                    <tr><td colspan="6">لاگ سینک ثبت نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($syncLogs as $log): ?>
                        <tr>
                            <td><?php echo (int)$log['id']; ?></td>
                            <td><?php echo htmlspecialchars($log['type'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($log['action'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo !empty($log['success']) ? '✅' : '❌'; ?></td>
                            <td class="micro-copy" style="white-space:normal;max-width:220px;">&lrm;<?php echo htmlspecialchars($log['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($log['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-soft" style="margin-top:10px;">
        <div class="card-header">
            <div class="card-title">لاگ ممیزی</div>
        </div>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>نوع</th>
                    <th>عملیات</th>
                    <th>موفق</th>
                    <th>پیام</th>
                    <th>تاریخ</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($auditLogs)): ?>
                    <tr><td colspan="6">لاگ ممیزی ثبت نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($auditLogs as $log): ?>
                        <tr>
                            <td><?php echo (int)$log['id']; ?></td>
                            <td><?php echo htmlspecialchars($log['entity_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($log['action'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo !empty($log['success']) ? '✅' : '❌'; ?></td>
                            <td class="micro-copy" style="white-space:normal;max-width:220px;">&lrm;<?php echo htmlspecialchars($log['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($log['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="tab-panel" id="tab-sms" style="display:none;">
    <div class="card-soft">
        <div class="card-header">
            <div class="card-title">تاریخچه پیامک</div>
        </div>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>جهت</th>
                    <th>نوع</th>
                    <th>وضعیت</th>
                    <th>متن</th>
                    <th>زمان</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($smsLogs)): ?>
                    <tr><td colspan="6">پیامکی ثبت نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($smsLogs as $sms): ?>
                        <tr>
                            <td><?php echo (int)$sms['id']; ?></td>
                            <td><?php echo htmlspecialchars($sms['direction'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($sms['sms_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($sms['status'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="micro-copy" style="white-space:normal;max-width:240px;">
                                <?php echo htmlspecialchars(mb_substr($sms['message'] ?? '', 0, 120), ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td><?php echo htmlspecialchars($sms['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="tab-panel" id="tab-wallet" style="display:none;">
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:10px;">
        <div class="card-soft">
            <div class="card-header">
                <div class="card-title">موجودی کیف پول</div>
            </div>
            <div class="kpi-value" style="margin:10px 0;">
                <?php echo number_format((int)($walletAccount['balance'] ?? 0)); ?> ریال
            </div>
            <div class="micro-copy">شناسه کیف پول: <?php echo (int)($walletAccount['id'] ?? 0); ?></div>
        </div>
        <div class="card-soft">
            <div class="card-header"><div class="card-title">ثبت تراکنش کیف پول</div></div>
            <form id="wallet-adjust-form" class="grid" style="grid-template-columns:1fr;gap:8px;">
                <input type="hidden" name="customer_id" value="<?php echo (int)$customer['id']; ?>" />
                <select name="direction" required>
                    <option value="credit">افزایش اعتبار</option>
                    <option value="debit">کاهش / شارژ قرارداد</option>
                </select>
                <input type="number" name="amount" placeholder="مبلغ (ریال)" required />
                <input type="text" name="description" placeholder="توضیحات (اختیاری)" />
                <button type="submit" class="btn">ثبت تراکنش</button>
            </form>
        </div>
    </div>

    <div class="card-soft" style="margin-top:10px;">
        <div class="card-header">
            <div class="card-title">گردش حساب کیف پول</div>
        </div>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>نوع</th>
                    <th>مبلغ</th>
                    <th>شرح</th>
                    <th>ارجاع</th>
                    <th>تاریخ</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($walletTransactions)): ?>
                    <tr><td colspan="6">تراکنشی ثبت نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($walletTransactions as $txn): ?>
                        <tr>
                            <td><?php echo (int)$txn['id']; ?></td>
                            <td>
                                <?php $isDebit = $txn['direction'] === 'debit'; ?>
                                <span class="chip" style="display:inline-flex;align-items:center;gap:6px;color:<?php echo $isDebit ? '#b91c1c' : '#15803d'; ?>;">
                                    <?php echo $isDebit ? '⬇️ کاهش' : '⬆️ افزایش'; ?>
                                </span>
                            </td>
                            <td><?php echo ($isDebit ? '-' : '+') . number_format((int)$txn['amount']); ?></td>
                            <td class="micro-copy" style="white-space:normal;max-width:260px;">&lrm;<?php echo htmlspecialchars($txn['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($txn['reference_type'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($txn['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const alertBox = document.getElementById('action-alert');

    function showActionMessage(success, message) {
        if (!alertBox) return;
        alertBox.textContent = message || '';
        alertBox.className = 'alert ' + (success ? '' : 'alert-error');
        alertBox.style.display = 'block';
    }

    async function postAction(url, payload) {
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {'X-Requested-With':'XMLHttpRequest', 'Content-Type':'application/x-www-form-urlencoded'},
                body: new URLSearchParams(payload)
            });
            const data = await res.json();
            showActionMessage(!!data.success, data.message || '');
            return data;
        } catch (e) {
            showActionMessage(false, 'خطا در انجام عملیات');
        }
    }

    function handleDomainAction(domainId, action) {
        if (action === 'suspend' && !confirm('دامنه ساسپند شود؟')) return;
        postAction('/domains/' + action, {domain_id: domainId});
    }

    function handleHostingAction(id, action) {
        if (action === 'suspend' && !confirm('سرویس ساسپند شود؟')) return;
        postAction('/hosting/' + action, {id});
    }

    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;
            tabButtons.forEach(b => b.classList.remove('active'));
            tabPanels.forEach(panel => panel.style.display = panel.id === 'tab-' + target ? 'block' : 'none');
            btn.classList.add('active');
        });
    });

    function wireWalletForm(formId, endpoint) {
        const form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const payload = Object.fromEntries(formData.entries());
            const res = await postAction(endpoint, payload);
            if (res && res.success) {
                setTimeout(() => window.location.reload(), 500);
            }
        });
    }

    wireWalletForm('wallet-adjust-form', '/customers/wallet/adjust');
</script>
