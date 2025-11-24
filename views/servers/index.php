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
        <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;">
            <div class="form-field">
                <label class="form-label">نام سرور (hostname)</label>
                <input type="text" name="hostname" class="form-input" placeholder="stardns.ir" required>
            </div>
            <div class="form-field">
                <label class="form-label">IP اصلی</label>
                <input type="text" name="ip" class="form-input" placeholder="80.249.115.114" required>
            </div>
            <div class="form-field">
                <label class="form-label">نام کاربری DirectAdmin</label>
                <input type="text" name="username" class="form-input" placeholder="admin" required>
            </div>
            <div class="form-field">
                <label class="form-label">رمز ورود (در صورت استفاده)</label>
                <input type="password" name="password" class="form-input" placeholder="••••••">
            </div>
            <div class="form-field">
                <label class="form-label">Login Key (اختیاری)</label>
                <input type="text" name="login_key" class="form-input" placeholder="da_login_key_...">
                <div class="micro-copy">در صورت استفاده از login key، وارد کردن رمز الزامی نیست.</div>
            </div>
            <div class="form-field">
                <label class="form-label">پورت</label>
                <input type="text" name="port" class="form-input" value="2222">
            </div>
            <div class="form-field">
                <label class="form-label">SSL</label>
                <label class="chip-toggle"><input type="checkbox" name="ssl" checked> اتصال امن (https)</label>
            </div>
        </div>

        <div style="grid-column:1 / span 3; display:flex; gap:8px; align-items:center;">
            <button type="button" class="btn btn-outline" id="testConnection">بررسی اتصال</button>
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
        const form = document.getElementById('serverForm');
        const statusEl = document.getElementById('serverStatus');
        const testBtn = document.getElementById('testConnection');

        const runTest = () => {
            statusEl.textContent = 'در حال بررسی اتصال...';
            statusEl.classList.add('pulse');
            return fetch('/servers/test', {
                method: 'POST',
                headers: {'X-Requested-With':'XMLHttpRequest'},
                body: new FormData(form)
            }).then(async res => {
                const data = await res.json();
                statusEl.textContent = data.message || (res.ok ? 'موفق' : 'ناموفق');
                statusEl.classList.remove('pulse');
                return res.ok;
            }).catch(() => {
                statusEl.textContent = 'خطا در بررسی اتصال';
                statusEl.classList.remove('pulse');
                return false;
            });
        };

        if (testBtn) {
            testBtn.addEventListener('click', function(e){
                e.preventDefault();
                if (!form) return;
                runTest();
            });
        }

        if (form) {
            form.addEventListener('submit', function(e){
                e.preventDefault();
                runTest().then(success => {
                    if (!success) {
                        return;
                    }
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
                        statusEl.textContent = 'خطا در ذخیره';
                        statusEl.classList.remove('pulse');
                    });
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
                <th>hostname</th>
                <th>IP</th>
                <th>نام کاربری</th>
                <th>SSL</th>
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
                <tr><td colspan="11">سروری ثبت نشده است.</td></tr>
            <?php else: ?>
                <?php foreach ($servers as $srv): ?>
                    <?php $healthRow = $health[$srv['id']] ?? []; $usage = $healthRow['usage'] ?? []; ?>
                    <tr>
                        <td><?php echo (int)$srv['id']; ?></td>
                        <td><?php echo htmlspecialchars($srv['hostname'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($srv['ip'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($srv['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo !empty($srv['ssl']) ? '✅' : '—'; ?></td>
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

