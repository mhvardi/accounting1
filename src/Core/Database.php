<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $configFile = __DIR__ . '/../../config/config.php';
            if (!file_exists($configFile)) {
                View::renderError('فایل config/config.php یافت نشد. تنظیمات دیتابیس را بررسی کنید.');
            }

            // فایل کانفیگ شما (با کلیدهای db_host, db_name, db_user, db_pass)
            $cfg = include $configFile;
            if (!is_array($cfg)) {
                View::renderError('خروجی config/config.php معتبر نیست.');
            }

            // تنظیم timezone اگر در کانفیگ تعریف شده باشد
            if (!empty($cfg['timezone'])) {
                @date_default_timezone_set($cfg['timezone']);
            }

            $host = $cfg['db_host'] ?? 'localhost';
            $dbname = $cfg['db_name'] ?? '';
            $user = $cfg['db_user'] ?? '';
            $pass = $cfg['db_pass'] ?? '';

            if ($dbname === '' || $user === '') {
                View::renderError('نام دیتابیس یا نام کاربر دیتابیس در config/config.php تنظیم نشده است.');
            }

            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

            try {
                self::$pdo = new PDO(
                    $dsn,
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                // نمایش خطای واضح داخل پنل (همونی که الان دیدی ولی این بار با DSN درست)
                View::renderError(
                    'اتصال به دیتابیس ناموفق بود: ' . $e->getMessage(),
                    $e->getTraceAsString(),
                    null
                );
            }
        }

        return self::$pdo;
    }
}