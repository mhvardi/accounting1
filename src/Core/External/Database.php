<?php
namespace App\Core;

use PDO;
use PDOException;

class ExternalDatabase
{
    public static function connection(): PDO
    {
        static $pdo = null;
        if ($pdo !== null) {
            return $pdo;
        }

        $config = require __DIR__ . '/../../config/config.php';

        $host = $config['gateway_db_host'] ?? 'localhost';
        $db   = $config['gateway_db_name'] ?? '';
        $user = $config['gateway_db_user'] ?? '';
        $pass = $config['gateway_db_pass'] ?? '';
        $dsn  = "mysql:host={$host};dbname={$db};charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new PDOException('اتصال به دیتابیس سایت‌های متفرقه ناموفق بود: ' . $e->getMessage(), (int)$e->getCode());
        }

        return $pdo;
    }
}