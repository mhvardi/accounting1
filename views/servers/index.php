<?php
/** @var array $servers */
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">🖥️</span>
    <span>سرورها (DirectAdmin / NOC)</span>
    <div style="font-size:11px;color:#6b7280;margin-top:4px;">طبق ساختار WHMCS برای اتصال به API های DirectAdmin</div>
</div>

<div class="card-soft" style="margin-bottom:12px;">
    <div class="card-header">
        <div class="card-title">افزودن / ویرایش سرور</div>
        <div class="micro-copy">ثبت سریع با AJAX و بازخورد آنی</div>
    </div>
    <form method="post" class="grid server-form" id="serverForm" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;">
        <input type="hidden" name="id" value="">
        <div class="accordion">
            <button type="button" class="accordion-toggle" data-accordion-target="#coreFields">مشخصات اصلی</button>
            <div id="coreFields" class="accordion-body show">
                <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;">
                    <div class="form-field">
                        <label class="form-label">نام داخلی</label>
                        <input type="text" name="name" class="form-input" placeholder="iran-direct" required>
                    </div>
                    <div class="form-field">
                        <label class="form-label">نام سرور (hostname)</label>
                        <input type="text" name="hostname" class="form-input" placeholder="stardns.ir" required>
                    </div>
                    <div class="form-field">
                        <label class="form-label">IP اصلی</label>
                        <input type="text" name="ip" class="form-input" placeholder="80.249.115.114" required>
                    </div>
                    <div class="form-field">
                        <label class="form-label">IP های اختصاصی (در هر خط یکی)</label>
                        <textarea name="allocated_ips" class="form-textarea" rows="2"></textarea>
                    </div>
                    <div class="form-field">
                        <label class="form-label">هزینه ماهانه</label>
                        <input type="text" name="monthly_cost" class="form-input" placeholder="0.00">
                    </div>
                    <div class="form-field">
                        <label class="form-label">مرکز داده / NOC</label>
                        <input type="text" name="datacenter" class="form-input" placeholder="تهران">
                    </div>
                    <div class="form-field">
                        <label class="form-label">حداکثر تعداد حساب</label>
                        <input type="text" name="account_limit" class="form-input" placeholder="110">
                    </div>
                    <div class="form-field">
                        <label class="form-label">آدرس وضعیت</label>
                        <input type="text" name="status_url" class="form-input" placeholder="https://example.com/status/">
                    </div>
                    <div class="form-field">
                        <label class="form-label">غیرفعال باشد؟</label>
                        <label class="chip-toggle"><input type="checkbox" name="disabled"> غیرفعال</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="accordion">
            <button type="button" class="accordion-toggle" data-accordion-target="#connectionFields">اتصال و احراز هویت</button>
            <div id="connectionFields" class="accordion-body show">
                <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;">
                    <div class="form-field">
                        <label class="form-label">ماژول</label>
                        <input type="text" class="form-input" value="DirectAdmin" disabled>
                    </div>
                    <div class="form-field">
                        <label class="form-label">نام کاربری</label>
                        <input type="text" name="username" class="form-input" placeholder="stardnsi" required>
                    </div>
                    <div class="form-field">
                        <label class="form-label">رمز</label>
                        <input type="password" name="password" class="form-input" placeholder="••••••" required>
                    </div>
                    <div class="form-field">
                        <label class="form-label">SSL</label>
                        <label class="chip-toggle"><input type="checkbox" name="ssl" checked> اتصال امن</label>
                    </div>
                    <div class="form-field">
                        <label class="form-label">پورت</label>
                        <input type="text" name="port" class="form-input" value="2223">
                    </div>
                </div>
            </div>
        </div>

        <div class="accordion">
            <button type="button" class="accordion-toggle" data-accordion-target="#nsFields">DNS / NameServer</button>
            <div id="nsFields" class="accordion-body">
                <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;">
                    <div class="form-field">
                        <label class="form-label">NS1</label>
                        <input type="text" name="ns1" class="form-input" value="ns1.stardns.ir">
                        <input type="text" name="ns1_ip" class="form-input" placeholder="IP">
                    </div>
                    <div class="form-field">
                        <label class="form-label">NS2</label>
                        <input type="text" name="ns2" class="form-input" value="ns2.stardns.ir">
                        <input type="text" name="ns2_ip" class="form-input" placeholder="IP">
                    </div>
                    <div class="form-field">
                        <label class="form-label">NS3</label>
                        <input type="text" name="ns3" class="form-input" placeholder="ns3">
                        <input type="text" name="ns3_ip" class="form-input" placeholder="IP">
                    </div>
                    <div class="form-field">
                        <label class="form-label">NS4</label>
                        <input type="text" name="ns4" class="form-input" placeholder="ns4">
                        <input type="text" name="ns4_ip" class="form-input" placeholder="IP">
                    </div>
                    <div class="form-field">
                        <label class="form-label">NS5</label>
                        <input type="text" name="ns5" class="form-input" placeholder="ns5">
                        <input type="text" name="ns5_ip" class="form-input" placeholder="IP">
                    </div>
                </div>
            </div>
        </div>

        <div style="grid-column:1 / span 3; display:flex; gap:8px; align-items:center;">
            <button type="submit" class="btn btn-primary gradient">ثبت سرور</button>
            <span id="serverStatus" class="micro-copy"></span>
        </div>
    </form>
</div>

<?php if (!empty($flash)): ?>
    <div class="alert success" style="margin-bottom:10px;"> <?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?> </div>
<?php endif; ?>

<script>
    (function(){
        // accordion
        document.querySelectorAll('.accordion-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const target = document.querySelector(btn.dataset.accordionTarget);
                if (!target) return;
                target.classList.toggle('show');
            });
        });

        // ajax submit
        const form = document.getElementById('serverForm');
        const statusEl = document.getElementById('serverStatus');
        if (form) {
            form.addEventListener('submit', function(e){
                e.preventDefault();
                statusEl.textContent = 'در حال ذخیره...';
                statusEl.classList.add('pulse');
                fetch('/servers', {
                    method: 'POST',
                    headers: {'X-Requested-With':'XMLHttpRequest'},
                    body: new FormData(form)
                }).then(res => res.json()).then(res => {
                    statusEl.textContent = res.message || (res.success ? 'ثبت شد' : 'خطا');
                    statusEl.classList.remove('pulse');
                    if (res.success) {
                        setTimeout(() => window.location.reload(), 400);
                    }
                }).catch(() => {
                    statusEl.textContent = 'خطا در اتصال';
                    statusEl.classList.remove('pulse');
                });
            });
        }

        // health check buttons
        document.querySelectorAll('[data-check-id]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-check-id');
                btn.textContent = 'در حال بررسی...';
                fetch('/servers/check?id=' + encodeURIComponent(id), {headers:{'X-Requested-With':'XMLHttpRequest'}})
                    .then(res => res.json())
                    .then(res => {
                        btn.textContent = 'بررسی اتصال';
                        alert(res.message || (res.success ? 'موفق' : 'ناموفق'));
                        if (res.success) window.location.reload();
                    }).catch(() => {
                        btn.textContent = 'بررسی اتصال';
                        alert('خطا در بررسی اتصال');
                    });
            });
        });

        // hosting sync buttons
        document.querySelectorAll('[data-sync-hosting]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-sync-hosting');
                btn.textContent = 'در حال همگام‌سازی...';
                fetch('/servers/sync-hosting?id=' + encodeURIComponent(id), {headers:{'X-Requested-With':'XMLHttpRequest'}})
                    .then(res => res.json())
                    .then(res => {
                        btn.textContent = 'همگام‌سازی هاستینگ';
                        alert(res.message || (res.success ? 'موفق' : 'ناموفق'));
                        if (res.success) window.location.reload();
                    }).catch(() => {
                        btn.textContent = 'همگام‌سازی هاستینگ';
                        alert('خطا در همگام‌سازی');
                    });
            });
        });
    })();
</script>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">لیست سرورها</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>نام</th>
                <th>hostname</th>
                <th>IP</th>
                <th>پورت</th>
                <th>مصرف/ظرفیت</th>
                <th>آخرین بررسی</th>
                <th>وضعیت اتصال</th>
                <th>سرویس‌های متصل</th>
                <th>اقدامات</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($servers)): ?>
                <tr><td colspan="10">سروری ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($servers as $srv): ?>
                    <?php $healthRow = $health[$srv['id']] ?? []; $usage = $healthRow['usage'] ?? []; ?>
                    <tr>
                        <td><?php echo (int)$srv['id']; ?></td>
                        <td><?php echo htmlspecialchars($srv['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($srv['hostname'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($srv['ip'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($srv['port'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if (!empty($usage['success'])): ?>
                                <div class="micro-copy">پهنای‌باند: <?php echo htmlspecialchars($usage['bandwidth']['used'] ?? '؟', ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars($usage['bandwidth']['limit'] ?? '∞', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="micro-copy">دیسک: <?php echo htmlspecialchars($usage['disk']['used'] ?? '؟', ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars($usage['disk']['limit'] ?? '∞', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="micro-copy">کاربران: <?php echo htmlspecialchars($usage['accounts'] ?? '؟', ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php else: ?>
                                <span class="micro-copy">--</span>
                                <div class="micro-copy" style="direction:ltr;">&lrm;<?php echo htmlspecialchars($usage['message'] ?? 'نامشخص', ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $srv['last_checked_at'] ? htmlspecialchars($srv['last_checked_at'], ENT_QUOTES, 'UTF-8') : 'بررسی نشده'; ?>
                            <?php if (!empty($healthRow['checked_at'])): ?>
                                <div class="micro-copy">الان: <?php echo htmlspecialchars($healthRow['checked_at'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?php echo !empty($healthRow['status'] ?? $srv['last_check_status']) ? '✅ متصل' : '⚠️ ناموفق'; ?></div>
                            <div class="micro-copy" style="direction:ltr;">&lrm;<?php echo htmlspecialchars($healthRow['message'] ?? ($srv['last_check_message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        </td>
                        <td>
                            <?php $attached = $connections[$srv['id']] ?? []; ?>
                            <?php if (empty($attached)): ?>
                                <span class="micro-copy">بدون اتصال</span>
                            <?php else: ?>
                                <div class="micro-copy"><?php echo count($attached); ?> سرویس</div>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <?php foreach ($attached as $conn): ?>
                                        <span class="chip">#<?php echo (int)$conn['service_id']; ?> / <?php echo htmlspecialchars($conn['customer_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="display:flex;gap:6px;flex-wrap:wrap;">
                            <button type="button" class="btn btn-outline" data-check-id="<?php echo (int)$srv['id']; ?>">بررسی اتصال</button>
                            <button type="button" class="btn btn-outline" data-sync-hosting="<?php echo (int)$srv['id']; ?>">همگام‌سازی هاستینگ</button>
                            <a class="btn btn-outline btn-danger" onclick="return confirm('حذف سرور؟');" href="/servers/delete?id=<?php echo (int)$srv['id']; ?>">حذف</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

