<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

// اتولودر ساده برای فضای نام App\*
spl_autoload_register(function(string $class) {
    if (strpos($class, 'App\\') === 0) {
        $path = __DIR__ . '/src/' . str_replace('App\\', '', $class) . '.php';
        $path = str_replace('\\', '/', $path);
        if (file_exists($path)) {
            require $path;
        }
    }
});

// jdf برای تاریخ شمسی (در کلاس Date هم بررسی می‌شود)
if (file_exists(__DIR__ . '/jdf.php')) {
    require_once __DIR__ . '/jdf.php';
}

$router = new \App\Core\Router();
$router->dispatch();
