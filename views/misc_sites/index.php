<?php
/** @var array $stats */
use App\Core\Date;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">🌐</span>
    <span>سایت‌های متفرقه (بانک شماره، استارپلن و ...)</span>
</div>

<div class="card-soft" style="margin-bottom:10px;">
    <div class="card-header">
        <div class="card-title">دفتر هزینه/درآمد سایت‌های متفرقه</div>
    </div>
    <form method="post" action="/misc-sites">
        <div class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;align-items:end;">
            <div class="form-field">
                <label class="form-label">نام سایت</label>
                <select name="site_name" class="form-select" required>
                    <option value="">انتخاب کنید</option>
                    <?php foreach ($siteOptions as $name): ?>
                        <option value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">نوع</label>
                <select name="kind" class="form-select">
                    <option value="expense">هزینه</option>
                    <option value="income">دریافتی</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">مبلغ (تومان)</label>
                <input type="text" name="amount" class="form-input money-input" required>
            </div>
            <div class="form-field">
                <label class="form-label">تاریخ رخداد (شمسی)</label>
                <input type="text" name="occurred_at" class="form-input jalali-picker" placeholder="مثلاً 1404/08/28">
            </div>
            <div class="form-field" style="grid-column:1 / span 4;">
                <label class="form-label">توضیحات</label>
                <textarea name="note" class="form-textarea" rows="2" placeholder="توضیح یا شماره فاکتور"></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">ثبت در دفتر</button>
    </form>

    <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:12px;">
        <?php if (empty($ledgerSummary)): ?>
            <div>هنوز داده‌ای در دفتر ثبت نشده است.</div>
        <?php else: ?>
            <?php foreach ($ledgerSummary as $row):
                $incomeT = (int)round(($row['income_rial'] ?? 0) / 10);
                $expenseT = (int)round(($row['expense_rial'] ?? 0) / 10);
                $profitT = $incomeT - $expenseT;
            ?>
                <div class="card-soft" style="box-shadow:none;border:1px solid #e5e7eb;">
                    <div class="card-title" style="margin-bottom:6px;"><?php echo htmlspecialchars($row['site_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="form-label">دریافتی کل</div>
                    <div class="kpi-value"><?php echo number_format($incomeT); ?></div>
                    <div class="form-label">هزینه کل</div>
                    <div class="kpi-value"><?php echo number_format($expenseT); ?></div>
                    <div class="form-label">سود/زیان</div>
                    <div class="kpi-value" style="color:<?php echo $profitT>=0?'#16a34a':'#b91c1c'; ?>;">
                        <?php echo number_format($profitT); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($recentLedger)): ?>
        <div style="overflow-x:auto;margin-top:12px;">
            <table class="table">
                <thead>
                    <tr>
                        <th>تاریخ</th>
                        <th>سایت</th>
                        <th>نوع</th>
                        <th>مبلغ (تومان)</th>
                        <th>توضیحات</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentLedger as $row): ?>
                    <tr>
                        <td><?php echo Date::jDate($row['occurred_at']); ?></td>
                        <td><?php echo htmlspecialchars($row['site_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $row['kind']==='income' ? 'دریافتی' : 'هزینه'; ?></td>
                        <td><?php echo number_format((int)round(($row['amount_rial'] ?? 0)/10)); ?></td>
                        <td><?php echo htmlspecialchars($row['note'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php foreach ($stats as $key => $item): ?>
    <div class="card-soft" style="margin-bottom:10px;">
        <div class="card-header">
            <div class="card-title"><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div style="font-size:11px;color:#6b7280;">
                از <?php echo Date::jFromTimestamp($item['from_ts'], 'Y/m/d H:i'); ?>
                تا <?php echo Date::jFromTimestamp($item['to_ts'], 'Y/m/d H:i'); ?>
            </div>
        </div>
        <?php
        // در دیتابیس مبلغ‌ها به ریال است، اینجا به تومان تبدیل می‌کنیم
        $totalToman = (int)round(($item['total'] ?? 0) / 10);
        ?>
        <div class="kpi-value">
            <?php echo number_format($totalToman); ?>
            <span style="font-size:11px;">تومان</span>
        </div>

        <div style="overflow-x:auto;margin-top:8px;">
            <table class="table">
                <thead>
                <tr>
                    <th>سایت</th>
                    <th>مبلغ موفق (تومان)</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($item['rows'])): ?>
                    <tr><td colspan="2">هیچ پرداخت موفقی در این بازه ثبت نشده است.</td></tr>
                <?php else: ?>
                    <?php foreach ($item['rows'] as $row): ?>
                        <?php $rowAmountToman = (int)round(($row['total'] ?? 0) / 10); ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['site_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo number_format($rowAmountToman); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>
