<?php

session_start();

$baseDir    = dirname(__DIR__);
$configFile = $baseDir . '/config/config.php';

// اگر کانفیگ نداریم یعنی هنوز نصب نشده
if (!file_exists($configFile)) {
    header('Location: install.php');
    exit;
}

// لود تنظیمات
$config = require $configFile;

// اتولودر
spl_autoload_register(function (string $class) use ($baseDir) {
    $prefix = 'App\\';
    $base   = $baseDir . '/src/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = $base . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// تنظیم timezone
date_default_timezone_set($config['timezone'] ?? 'Asia/Tehran');

use App\Core\Router;
use App\Core\Helpers;

$router = new Router();

// تعریف مسیرها
$router->get('/',      [\App\Controller\DashboardController::class, 'index']);
$router->get('/login', [\App\Controller\AuthController::class, 'showLogin']);
$router->post('/login',[\App\Controller\AuthController::class, 'login']);
$router->get('/logout',[\App\Controller\AuthController::class, 'logout']);

// نرمال‌سازی مسیر نسبی نسبت به public/
$uri    = $_SERVER['REQUEST_URI']    ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path   = parse_url($uri, PHP_URL_PATH);

$basePath = Helpers::basePath(); // مثلا '/public' یا ''

if ($basePath && strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

// حذف index.php از انتهای مسیر
if ($path === '' || $path === '/' || $path === '/index.php') {
    $path = '/';
}

try {
    $router->dispatch($path, $method);
} catch (\Throwable $e) {
    http_response_code(500);
    echo "Internal error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
