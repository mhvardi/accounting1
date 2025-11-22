<?php

session_start();

$baseDir = dirname(__DIR__);
$configPath       = $baseDir . '/config/config.php';
$sampleConfigPath = $baseDir . '/config/config.sample.php';
$dbSchemaPath     = $baseDir . '/database.sql';

if (file_exists($configPath)) {
    echo "Config file already exists. If you want to reinstall, delete config/config.php first.";
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost     = trim($_POST['db_host'] ?? '');
    $dbName     = trim($_POST['db_name'] ?? '');
    $dbUser     = trim($_POST['db_user'] ?? '');
    $dbPass     = trim($_POST['db_pass'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass  = trim($_POST['admin_password'] ?? '');

    if ($dbHost && $dbName && $dbUser && $adminEmail && $adminPass) {
        try {
            $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $sql = file_get_contents($dbSchemaPath);
            $pdo->exec($sql);

            $config = require $sampleConfigPath;
            $config['db_host'] = $dbHost;
            $config['db_name'] = $dbName;
            $config['db_user'] = $dbUser;
            $config['db_pass'] = $dbPass;

            $configExport = '<?php' . PHP_EOL . 'return ' . var_export($config, true) . ';' . PHP_EOL;
            file_put_contents($configPath, $configExport);

            $hash = password_hash($adminPass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, created_at, updated_at) VALUES (?,?,?,?,?)");
            $now  = date('Y-m-d H:i:s');
            $stmt->execute([$adminEmail, $hash, 'admin', $now, $now]);

            $success = "نصب با موفقیت انجام شد. می‌توانید با ایمیل و رمز عبور وارد شوید.";
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = 'تمام فیلدهای ضروری را تکمیل کنید.';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نصب سیستم حسابداری وردی</title>
    <style>
        body { font-family: sans-serif; background: #f5f5f5; display:flex; align-items:center; justify-content:center; height:100vh; }
        .card { background:#fff; padding:20px 25px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); width:400px; }
        .card h1 { font-size:18px; margin-top:0; margin-bottom:15px; }
        label { display:block; margin-top:10px; font-size:13px; }
        input { width:100%; padding:7px 8px; margin-top:4px; border-radius:6px; border:1px solid #ccc; font-size:13px; }
        button { margin-top:15px; width:100%; padding:9px; border-radius:6px; border:none; background:#2563eb; color:#fff; font-size:14px; cursor:pointer; }
        .alert { margin-top:10px; padding:8px; border-radius:6px; font-size:13px; }
        .alert.error { background:#fee2e2; color:#b91c1c; }
        .alert.success { background:#dcfce7; color:#166534; }
    </style>
</head>
<body>
<div class="card">
    <h1>نصب سیستم حسابداری وردی</h1>

    <?php if ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert success">
            <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?><br>
            <a href="index.php">ورود به سیستم</a>
        </div>
    <?php else: ?>
    <form method="post">
        <label>هاست دیتابیس</label>
        <input type="text" name="db_host" value="localhost" required>

        <label>نام دیتابیس</label>
        <input type="text" name="db_name" required>

        <label>نام کاربری دیتابیس</label>
        <input type="text" name="db_user" required>

        <label>رمز دیتابیس</label>
        <input type="password" name="db_pass">

        <label>ایمیل مدیر (ورود)</label>
        <input type="email" name="admin_email" required>

        <label>رمز عبور مدیر</label>
        <input type="password" name="admin_password" required>

        <button type="submit">شروع نصب</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
