<div class="login-heading">
    <div class="eyebrow">ورود امن</div>
    <div class="title">ورود به پنل</div>
    <div class="subtitle">برای دسترسی به داشبورد از شناسه خود استفاده کنید.</div>
</div>
<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<form method="post" action="/login/submit" class="login-form">
    <div class="form-field">
        <label class="form-label">نام کاربری</label>
        <input type="text" name="identifier" class="form-input" placeholder="ایمیل / موبایل / نام کاربری" required>
    </div>
    <div class="form-field">
        <label class="form-label">رمز عبور</label>
        <input type="password" name="password" class="form-input" placeholder="••••••" required>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary" style="width:100%;">ورود به حساب</button>
        <div class="login-support">ورود با ایمیل، شماره موبایل یا نام کاربری + رمز عبور</div>
    </div>
</form>
