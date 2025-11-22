<?php
declare(strict_types=1);

session_start();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$baseDir = __DIR__;
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
</head>
<body>
<div>
    <h1>نصب سیستم حسابداری وردی</h1>
    <?php if ($error): ?>
        <div><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div>
            <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?><br>
            <a href="/index.php">ورود به سیستم</a>
        </div>
    <?php else: ?>
    <form method="post">
        <label>هاست دیتابیس</label>
        <input type="text" name="db_host" value="localhost" required><br>

        <label>نام دیتابیس</label>
        <input type="text" name="db_name" required><br>

        <label>نام کاربری دیتابیس</label>
        <input type="text" name="db_user" required><br>

        <label>رمز دیتابیس</label>
        <input type="password" name="db_pass"><br>

        <label>ایمیل مدیر (ورود)</label>
        <input type="email" name="admin_email" required><br>

        <label>رمز عبور مدیر</label>
        <input type="password" name="admin_password" required><br>

        <button type="submit">شروع نصب</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
