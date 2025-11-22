<div class="login-heading">
    <div class="title">ورود به پنل</div>
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
    </div>
</form>
