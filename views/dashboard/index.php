<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">📊</span>
    <span>پیشخوان کلی حسابداری</span>
</div>

<div class="card-soft assistant-card">
    <div class="assistant-summary">
        <div style="font-size:12px;color:#6b7280;">دستیار هوشمند</div>
        <div style="font-size:16px;font-weight:700;">گزارش ماه <?php echo htmlspecialchars($assistant['month_label'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
        <div style="margin-top:6px;line-height:1.8;">
            جریان نقدی امروز: <strong><?php echo number_format($assistant['today_revenue'] ?? 0); ?></strong> تومان | هزینه امروز: <strong><?php echo number_format($assistant['today_expenses'] ?? 0); ?></strong> تومان.
            مجموع دریافتی ماه: <strong><?php echo number_format($assistant['month_revenue'] ?? 0); ?></strong> تومان و مجموع پرداختی/هزینه ماه: <strong><?php echo number_format($assistant['month_expenses'] ?? 0); ?></strong> تومان.
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;">
            <span class="assistant-chip">مشتریان: <?php echo (int)($assistant['customers'] ?? 0); ?></span>
            <span class="assistant-chip">قراردادها: <?php echo (int)($assistant['contracts'] ?? 0); ?></span>
        </div>
    </div>
    <div class="assistant-metrics">
        <div class="card">
            <div class="card-title">درآمد امروز</div>
            <div class="kpi-value"><?php echo number_format($assistant['today_revenue'] ?? 0); ?></div>
            <div class="card-meta">دریافتی + سایت‌های متفرقه</div>
        </div>
        <div class="card">
            <div class="card-title">هزینه امروز</div>
            <div class="kpi-value"><?php echo number_format($assistant['today_expenses'] ?? 0); ?></div>
            <div class="card-meta">هزینه‌ها و صورت‌حساب‌ها</div>
        </div>
    </div>
</div>

<div class="widget-grid">
    <div class="card">
        <div class="card-header">
            <div class="card-title">درآمد این ماه</div>
            <span class="badge-pill">ورودی نقد</span>
        </div>
        <div class="kpi-value"><?php echo number_format($kpis['revenue'] + $kpis['external']); ?></div>
        <div style="font-size:11px;color:#6b7280;margin-top:4px;">دریافتی‌ها + سایت‌های متفرقه</div>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="card-title">پرداخت به پرسنل این ماه</div>
            <span class="badge-pill">حقوق</span>
        </div>
        <div class="kpi-value"><?php echo number_format($kpis['payroll']); ?></div>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="card-title">هزینه‌ها</div>
            <span class="badge-pill">خرید/قبوض</span>
        </div>
        <div class="kpi-value"><?php echo number_format($kpis['expenses']); ?></div>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="card-title">سود تقریبی این ماه</div>
            <span class="badge-pill">نهایی</span>
        </div>
        <div class="kpi-value"><?php echo number_format($kpis['profit']); ?></div>
        <div style="font-size:11px;color:#6b7280;margin-top:4px;">بعد از کسر هزینه‌ها و حقوق</div>
    </div>
</div>
