<?php
/**
 * @var array $categories
 * @var array $customers
 * @var array $contracts
 * @var array $logs
 * @var array|null $balance
 * @var array|null $tariff
 * @var array|null $authStatus
 * @var array $flash
 */
use App\Core\Str;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">📲</span>
    <span>مدیریت پیامک لیمو</span>
</div>

<?php if (!empty($flash['message'])): ?>
    <div class="alert <?php echo !empty($flash['ok']) ? '' : 'alert-error'; ?>">
        <?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;">
    <div class="card-soft">
        <div class="form-label">وضعیت احراز</div>
        <div class="kpi-value" style="font-size:18px;">
            <?php echo htmlspecialchars((string)($authStatus['data']['status'] ?? 'نامشخص'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>
    <div class="card-soft">
        <div class="form-label">موجودی</div>
        <div class="kpi-value" style="font-size:18px;">
            <?php echo htmlspecialchars((string)($balance['data']['balance'] ?? '---'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>
    <div class="card-soft">
        <div class="form-label">تعرفه پایه</div>
        <div class="kpi-value" style="font-size:18px;">
            <?php echo htmlspecialchars((string)($tariff['data']['price'] ?? '---'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>
</div>

<div class="grid" style="grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:12px;">
    <div class="card-soft">
        <div class="card-header">
            <div class="card-title">ارسال تکی / دستی</div>
            <div class="micro-copy">ارسال متن دلخواه، زمان‌بندی و برچسب</div>
        </div>
        <form action="/sms/send" method="post" class="form-grid">
            <label class="form-label">گیرندگان (جدا شده با کاما یا خط جدید)</label>
            <textarea name="recipients" rows="3" class="form-control" placeholder="0912...&#10;0935..."></textarea>
            <label class="form-label">متن پیام</label>
            <textarea name="text" rows="3" class="form-control"></textarea>
            <div class="grid" style="grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
                <div>
                    <label class="form-label">برچسب / دسته پیام</label>
                    <input type="text" name="category" class="form-control" placeholder="marketing | support">
                </div>
                <div>
                    <label class="form-label">زمان‌بندی (YYYY-mm-dd HH:ii)</label>
                    <input type="text" name="schedule_at" class="form-control" placeholder="2025-01-01 10:00">
                </div>
            </div>
            <div style="text-align:left;">
                <button type="submit" class="btn">ارسال</button>
            </div>
        </form>
    </div>

    <div class="card-soft">
        <div class="card-header">
            <div class="card-title">ارسال گروهی بر اساس دسته‌بندی خدمت</div>
            <div class="micro-copy">انتخاب مشتریان دارای قرارداد در دسته‌بندی‌های مشخص</div>
        </div>
        <form action="/sms/bulk" method="post" class="form-grid">
            <label class="form-label">دسته‌بندی‌ها</label>
            <select name="category_ids[]" multiple size="6" class="form-control">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="form-label">متن پیام</label>
            <textarea name="text" rows="3" class="form-control"></textarea>
            <label class="form-label">برچسب پیام</label>
            <input type="text" name="category" class="form-control" placeholder="campaign code">
            <div style="text-align:left;">
                <button type="submit" class="btn">ارسال گروهی</button>
            </div>
        </form>
    </div>

    <div class="card-soft">
        <div class="card-header">
            <div class="card-title">ارسال همبسته (Correlated)</div>
            <div class="micro-copy">هر خط: شماره|متن|شناسه دلخواه</div>
        </div>
        <form action="/sms/correlated" method="post" class="form-grid">
            <label class="form-label">لیست پیام‌ها</label>
            <textarea name="batch" rows="5" class="form-control" placeholder="0912...|سلام|welcome-1&#10;0935...|یادآوری|inv-77"></textarea>
            <div class="grid" style="grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
                <div>
                    <label class="form-label">برچسب پیام</label>
                    <input type="text" name="category" class="form-control" placeholder="bulk_tag">
                </div>
                <div>
                    <label class="form-label">زمان‌بندی</label>
                    <input type="text" name="schedule_at" class="form-control" placeholder="2025-01-02 09:00">
                </div>
            </div>
            <div style="text-align:left;">
                <button type="submit" class="btn">ارسال همبسته</button>
            </div>
        </form>
    </div>
</div>

<div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:12px;">
    <div class="card-soft">
        <div class="card-header">
            <div class="card-title">ارسال الگو (Pattern)</div>
            <div class="micro-copy">مقادیر را به شکل key:value در هر خط وارد کنید.</div>
        </div>
        <form action="/sms/pattern" method="post" class="form-grid">
            <label class="form-label">کد پترن</label>
            <input type="text" name="pattern_code" class="form-control" placeholder="PT-10001">
            <label class="form-label">گیرنده</label>
            <input type="text" name="receptor" class="form-control" placeholder="0912...">
            <label class="form-label">مقادیر</label>
            <textarea name="values" rows="4" class="form-control" placeholder="name: علی&#10;code: 1234"></textarea>
            <label class="form-label">برچسب پیام</label>
            <input type="text" name="category" class="form-control" placeholder="otp">
            <div style="text-align:left;">
                <button type="submit" class="btn">ارسال الگو</button>
            </div>
        </form>
    </div>

    <div class="card-soft">
        <div class="card-header">
            <div class="card-title">ارسال صوتی / OTP</div>
            <div class="micro-copy">ارسال کد تایید از طریق تماس صوتی</div>
        </div>
        <form action="/sms/voice" method="post" class="form-grid">
            <label class="form-label">گیرنده</label>
            <input type="text" name="receptor" class="form-control" placeholder="0912...">
            <label class="form-label">کد</label>
            <input type="text" name="code" class="form-control" placeholder="12345">
            <label class="form-label">برچسب پیام</label>
            <input type="text" name="category" class="form-control" placeholder="voice_otp">
            <div style="text-align:left;">
                <button type="submit" class="btn">ارسال صوتی</button>
            </div>
        </form>
    </div>

    <div class="card-soft">
        <div class="card-header">
            <div class="card-title">پیگیری وضعیت / لغو</div>
            <div class="micro-copy">بررسی تحویل یا لغو پیام زمان‌بندی شده</div>
        </div>
        <form action="/sms/status" method="post" class="form-grid">
            <label class="form-label">شناسه پیام</label>
            <input type="text" name="message_id" class="form-control" placeholder="msg-...">
            <div style="text-align:left;display:flex;gap:8px;flex-wrap:wrap;">
                <button type="submit" class="btn">استعلام تحویل</button>
                <button type="submit" formaction="/sms/cancel" class="btn btn-outline">لغو زمان‌بندی</button>
            </div>
        </form>
        <div style="margin-top:12px;">
            <form action="/sms/fetch-inbound" method="post" class="form-grid">
                <label class="form-label">دریافت پیام‌های ورودی از تاریخ</label>
                <input type="text" name="since" class="form-control" placeholder="2025-01-01">
                <div style="text-align:left;">
                    <button type="submit" class="btn btn-outline">دریافت ورودی‌ها</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="grid" style="grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:12px;">
    <div class="card-soft">
        <div class="card-header">
            <div class="card-title">یادآوری فاکتور</div>
            <div class="micro-copy">از قالب {amount} و {due_date} استفاده کنید</div>
        </div>
        <form action="/sms/invoice-reminder" method="post" class="form-grid">
            <label class="form-label">مشتری</label>
            <select name="customer_id" class="form-control">
                <?php foreach ($customers as $cus): ?>
                    <option value="<?php echo (int)$cus['id']; ?>"><?php echo htmlspecialchars($cus['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars(Str::digitsOnly($cus['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>)</option>
                <?php endforeach; ?>
            </select>
            <div class="grid" style="grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
                <div>
                    <label class="form-label">مبلغ</label>
                    <input type="text" name="amount" class="form-control money-input" placeholder="2500000">
                </div>
                <div>
                    <label class="form-label">تاریخ سررسید</label>
                    <input type="text" name="due_date" class="form-control" placeholder="1403/10/01">
                </div>
            </div>
            <label class="form-label">متن پیام</label>
            <textarea name="text" rows="3" class="form-control" placeholder="مشتری گرامی، مبلغ {amount} تا تاریخ {due_date} تسویه شود."></textarea>
            <div style="text-align:left;">
                <button type="submit" class="btn">ارسال یادآوری</button>
            </div>
        </form>
    </div>

    <div class="card-soft">
        <div class="card-header">
            <div class="card-title">خوش‌آمد / قرارداد</div>
            <div class="micro-copy">از متغیر {contract} در متن استفاده کنید</div>
        </div>
        <form action="/sms/welcome-trigger" method="post" class="form-grid">
            <label class="form-label">مشتری</label>
            <select name="customer_id" class="form-control">
                <?php foreach ($customers as $cus): ?>
                    <option value="<?php echo (int)$cus['id']; ?>"><?php echo htmlspecialchars($cus['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <label class="form-label">قرارداد مرتبط</label>
            <select name="contract_id" class="form-control">
                <option value="0">بدون قرارداد</option>
                <?php foreach ($contracts as $ct): ?>
                    <option value="<?php echo (int)$ct['id']; ?>"><?php echo htmlspecialchars($ct['title'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo (int)$ct['customer_id']; ?>)</option>
                <?php endforeach; ?>
            </select>
            <label class="form-label">متن پیام</label>
            <textarea name="text" rows="3" class="form-control" placeholder="{contract} فعال شد؛ به جمع وردی خوش آمدید."></textarea>
            <div style="text-align:left;">
                <button type="submit" class="btn">ارسال پیام خوش‌آمد</button>
            </div>
        </form>
    </div>
</div>

<div class="card-soft" style="margin-top:12px;">
    <div class="card-header">
        <div class="card-title">آخرین پیامک‌ها</div>
        <div class="micro-copy">نمایش ۵۰ رکورد اخیر</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>جهت</th>
                <th>نوع</th>
                <th>گیرنده/فرستنده</th>
                <th>متن/خلاصه</th>
                <th>وضعیت</th>
                <th>شناسه</th>
                <th>زمان</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="8">رکوردی ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo (int)$log['id']; ?></td>
                        <td><?php echo htmlspecialchars($log['direction'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($log['sms_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($log['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="micro-copy" style="white-space:normal;max-width:260px;">
                            <?php echo htmlspecialchars(mb_substr($log['message'] ?? '', 0, 120), ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td><?php echo htmlspecialchars($log['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="micro-copy"><?php echo htmlspecialchars($log['provider_message_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($log['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
