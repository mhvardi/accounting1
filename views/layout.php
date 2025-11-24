<?php
use App\Core\Date;
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$currentJDate = Date::j('Y/m/d');
$isAuthPage = isset($viewName) && strpos($viewName, 'auth/') === 0;

function nav_active(string $path, string $current): string {
    return $path === $current ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حسابداری وردی</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=11">
</head>
<body>
<?php if ($isAuthPage): ?>
    <div class="auth-shell">
        <div class="auth-hero">
            <div class="logo">وردی کلود</div>
            <div class="hero-title">حسابداری هوشمند و یکپارچه</div>
            <div class="hero-sub">ورود امن، رابط مدرن و داشبورد آنی برای مدیریت مالی</div>
            <div class="hero-points">
                <span>گزارش زنده ماه جاری</span>
                <span>پشتیبانی از سال/ماه شمسی</span>
                <span>کنترل هزینه و حقوق</span>
            </div>
        </div>
        <div class="auth-card">
            <?php include $viewFile; ?>
        </div>
    </div>
<?php else: ?>
    <div class="app-shell">
        <aside class="sidebar" aria-label="منوی اصلی" aria-hidden="false">
            <div class="sidebar-logo">
                <span>داشبورد حسابداری وردی</span>
                <span class="badge">Pro</span>
            </div>
            <div class="nav-section" data-section-key="overview">
                <button class="nav-section-toggle" type="button">
                    <span class="nav-section-title">نمای کلی و تحلیل</span>
                    <span class="nav-toggle-icon">▾</span>
                </button>
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="/" class="nav-link <?php echo nav_active('/', $currentPath); ?>">
                            <span class="icon">🏠</span><span class="text">پیشخوان</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/reports" class="nav-link <?php echo nav_active('/reports', $currentPath); ?>">
                            <span class="icon">📈</span><span class="text">گزارش‌ها</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="nav-section" data-section-key="sales">
                <button class="nav-section-toggle" type="button">
                    <span class="nav-section-title">مشتری و فروش</span>
                    <span class="nav-toggle-icon">▾</span>
                </button>
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="/customers" class="nav-link <?php echo nav_active('/customers', $currentPath); ?>">
                            <span class="icon">🧑‍💼</span><span class="text">مشتریان</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/leads" class="nav-link <?php echo nav_active('/leads', $currentPath); ?>">
                            <span class="icon">🧲</span><span class="text">لیدها</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/hosting" class="nav-link <?php echo nav_active('/hosting', $currentPath); ?>">
                            <span class="icon">🗄️</span><span class="text">هاستینگ</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/domains" class="nav-link <?php echo nav_active('/domains', $currentPath); ?>">
                            <span class="icon">🌐</span><span class="text">دامنه‌ها</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/contracts" class="nav-link <?php echo nav_active('/contracts', $currentPath); ?>">
                            <span class="icon">📄</span><span class="text">قراردادها</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/payments" class="nav-link <?php echo nav_active('/payments', $currentPath); ?>">
                            <span class="icon">💳</span><span class="text">پرداخت‌ها</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/services" class="nav-link <?php echo nav_active('/services', $currentPath); ?>">
                            <span class="icon">🛰️</span><span class="text">سرویس‌ها</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/sms" class="nav-link <?php echo nav_active('/sms', $currentPath); ?>">
                            <span class="icon">📲</span><span class="text">پیامک</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/categories" class="nav-link <?php echo nav_active('/categories', $currentPath); ?>">
                            <span class="icon">📂</span><span class="text">دسته‌بندی خدمات</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="nav-section" data-section-key="finance">
                <button class="nav-section-toggle" type="button">
                    <span class="nav-section-title">مالی و هزینه</span>
                    <span class="nav-toggle-icon">▾</span>
                </button>
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="/expenses" class="nav-link <?php echo nav_active('/expenses', $currentPath); ?>">
                            <span class="icon">💸</span><span class="text">هزینه‌ها</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/expense-categories" class="nav-link <?php echo nav_active('/expense-categories', $currentPath); ?>">
                            <span class="icon">🏷️</span><span class="text">دسته‌بندی هزینه</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/misc-sites" class="nav-link <?php echo nav_active('/misc-sites', $currentPath); ?>">
                            <span class="icon">🌐</span><span class="text">سایت‌های متفرقه</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/payroll" class="nav-link <?php echo nav_active('/payroll', $currentPath); ?>">
                            <span class="icon">🧾</span><span class="text">حقوق و پورسانت</span>
                        </a>
                        <ul class="nav-sublist">
                            <li>
                                <a href="/employees/create" class="nav-sub-link <?php echo nav_active('/employees/create', $currentPath); ?>">➕ افزودن پرسنل</a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            <div class="nav-section" data-section-key="settings">
                <button class="nav-section-toggle" type="button">
                    <span class="nav-section-title">زیرساخت و تنظیمات</span>
                    <span class="nav-toggle-icon">▾</span>
                </button>
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="/servers" class="nav-link <?php echo nav_active('/servers', $currentPath); ?>">
                            <span class="icon">🖥️</span><span class="text">سرورها</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/audit-logs" class="nav-link <?php echo nav_active('/audit-logs', $currentPath); ?>">
                            <span class="icon">🧾</span><span class="text">گزارش ممیزی</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/notifications" class="nav-link <?php echo nav_active('/notifications', $currentPath); ?>">
                            <span class="icon">🔔</span><span class="text">اعلان‌ها</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/rbac" class="nav-link <?php echo nav_active('/rbac', $currentPath); ?>">
                            <span class="icon">🛡️</span><span class="text">نقش‌ها و دسترسی</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div style="margin-top:auto;font-size:11px;color:#6b7280;">
                <?php if (!empty($user)): ?>
                    <div>کاربر: <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div style="margin-top:4px;">
                        <a href="/logout" style="color:#f97316;text-decoration:none;">خروج</a>
                    </div>
                <?php endif; ?>
            </div>
        </aside>
        <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
        <main class="main">
            <div class="topbar">
                <div class="topbar-left">
                    <button type="button" class="btn btn-outline sidebar-toggle" id="sidebarToggle" aria-label="باز کردن منو">
                        ☰
                    </button>
                    <div class="topbar-title">داشبورد</div>
                </div>
                <div class="topbar-actions">
                    <div id="clockLabel" data-date="<?php echo htmlspecialchars($currentJDate, ENT_QUOTES, 'UTF-8'); ?>" style="font-size:12px;min-width:150px;text-align:left;"></div>
                    <button type="button" class="btn btn-outline" id="themeToggle" title="تغییر تم">
                        <span id="themeIcon">☀️</span>
                    </button>
                </div>
            </div>
            <?php include $viewFile; ?>
        </main>
    </div>
<?php endif; ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css">
<script src="https://cdn.jsdelivr.net/npm/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js"></script>

<script>
// تم روشن / تیره با آیکن خورشید / ماه
(function() {
    const saved = localStorage.getItem('vardi_theme');
    if (saved === 'dark') document.body.classList.add('theme-dark');
    const icon = document.getElementById('themeIcon');
    const btn   = document.getElementById('themeToggle');
    function sync() {
        if (!icon) return;
        icon.textContent = document.body.classList.contains('theme-dark') ? '🌙' : '☀️';
    }
    sync();
    if (btn) {
        btn.addEventListener('click', function(){
            document.body.classList.toggle('theme-dark');
            localStorage.setItem('vardi_theme', document.body.classList.contains('theme-dark') ? 'dark' : 'light');
            sync();
        });
    }
})();

// ساعت زنده با تاریخ شمسی ثابت از سرور
(function(){
    const clock = document.getElementById('clockLabel');
    if (!clock) return;
    const baseDate = clock.getAttribute('data-date') || '';
    function tick() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('fa-IR', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
        clock.textContent = baseDate + ' - ' + timeStr;
    }
    tick();
    setInterval(tick, 1000);
})();

// نرمال کردن اعداد فارسی به انگلیسی و فرمت سه‌رقمی مبالغ
(function(){
    function normalizeDigits(str){
        const fa = '۰۱۲۳۴۵۶۷۸۹';
        const ar = '٠١٢٣٤٥٦٧٨٩';
        let out = '';
        for (let ch of String(str)) {
            const iFa = fa.indexOf(ch);
            const iAr = ar.indexOf(ch);
            if (iFa !== -1) out += String(iFa);
            else if (iAr !== -1) out += String(iAr);
            else out += ch;
        }
        return out;
    }
    function formatMoney(val){
        const digits = String(val).replace(/[^0-9]/g,'');
        if (!digits) return '';
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    document.addEventListener('input', function(e){
        const el = e.target;
        if (!el.closest('input,textarea')) return;
        if (el.type === 'text' || el.type === 'number' || el.tagName === 'TEXTAREA') {
            const pos = el.selectionStart;
            let v = normalizeDigits(el.value);
            if (el.classList.contains('money-input')) {
                v = formatMoney(v);
            }
            el.value = v;
            if (pos !== null) {
                el.selectionStart = el.selectionEnd = el.value.length;
            }
        }
    });
})();

// تoggler ساده برای فرم‌های ویرایش سطر (inline-edit)
(function(){
    document.addEventListener('click', function(e){
        const btn = e.target.closest('[data-inline-edit-toggle]');
        if (!btn) return;
        e.preventDefault();
        const id = btn.getAttribute('data-inline-edit-toggle');
        if (!id) return;
        const box = document.querySelector('[data-inline-edit-box="' + id + '"]');
        if (!box) return;
        const isShown = box.style.display === 'block';
        box.style.display = isShown ? 'none' : 'block';
    });
})();

// فعال‌سازی datepicker شمسی اگر کتابخانه لود شده باشد
(function(){
    function initPicker(){
        if (window.jalaliDatepicker && typeof window.jalaliDatepicker.startWatch === 'function') {
            window.jalaliDatepicker.startWatch({ selector: '.jalali-picker' });
        }
    }
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initPicker();
    } else {
        document.addEventListener('DOMContentLoaded', initPicker);
    }
})();

// کنترل سایدبار موبایل
(function(){
    const sidebar = document.querySelector('.sidebar');
    const main = document.querySelector('.main');
    const toggleBtn = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');
    const navLinks = Array.from(document.querySelectorAll('.sidebar .nav-link'));
    const mobileQuery = window.matchMedia('(max-width: 1024px)');

    function applyState(open) {
        const isMobile = mobileQuery.matches;
        document.body.classList.toggle('sidebar-open', isMobile && open);
        if (isMobile) {
            sidebar?.setAttribute('aria-hidden', open ? 'false' : 'true');
            if (open) {
                main?.setAttribute('aria-hidden', 'true');
            } else {
                main?.removeAttribute('aria-hidden');
            }
        } else {
            sidebar?.setAttribute('aria-hidden', 'false');
            main?.removeAttribute('aria-hidden');
        }
    }

    function setOpen(open) {
        applyState(open);
    }

    toggleBtn?.addEventListener('click', function(){
        const nowOpen = !document.body.classList.contains('sidebar-open');
        setOpen(nowOpen);
    });

    backdrop?.addEventListener('click', function(){
        setOpen(false);
    });

    navLinks.forEach(function(link){
        link.addEventListener('click', function(){
            if (window.matchMedia('(max-width: 1024px)').matches) {
                setOpen(false);
            }
        });
    });

    applyState(false);
    mobileQuery.addEventListener('change', function(){
        applyState(false);
    });
})();

// ناوبری آکاردئونی با ذخیره وضعیت در localStorage
(function(){
    const sections = Array.from(document.querySelectorAll('.nav-section'));
    let savedState = {};
    try {
        savedState = JSON.parse(localStorage.getItem('vardi_nav_sections') || '{}') || {};
    } catch (e) {
        savedState = {};
    }

    function setOpen(section, open, key) {
        section.classList.toggle('collapsed', !open);
        savedState[key] = open;
        localStorage.setItem('vardi_nav_sections', JSON.stringify(savedState));
    }

    sections.forEach(function(section, index){
        const key = section.getAttribute('data-section-key') || ('section-' + index);
        const toggle = section.querySelector('.nav-section-toggle');
        const isOpen = savedState[key] !== false;
        setOpen(section, isOpen, key);

        if (toggle) {
            toggle.addEventListener('click', function(){
                const nowOpen = section.classList.contains('collapsed');
                setOpen(section, nowOpen, key);
            });
        }
    });
})();
</script>
</body>
</html>