<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">⚠️</span>
    <span>خطای سیستم</span>
</div>
<div class="card-soft">
    <div class="card-header">
        <div class="card-title">یک خطای داخلی رخ داده است</div>
    </div>
    <p style="font-size:13px;margin-bottom:6px;">
        برای این که دیگر صفحه‌ی 500 سفید نبینی، متن خطا را اینجا نمایش می‌دهم. این پیام را برای توسعه‌دهنده‌ات بفرست:
    </p>
    <pre style="direction:ltr;font-size:11px;background:#020617;color:#e5e7eb;padding:8px 10px;border-radius:12px;overflow:auto;max-height:260px;">
<?php echo htmlspecialchars($message ?? 'Unknown error', ENT_QUOTES, 'UTF-8'); ?>

<?php if (!empty($trace)): ?>
<?php echo htmlspecialchars($trace, ENT_QUOTES, 'UTF-8'); ?>
<?php endif; ?>
    </pre>
</div>
