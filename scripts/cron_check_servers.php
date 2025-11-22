<?php
// Cron job: php scripts/cron_check_servers.php
ini_set('display_errors', '1');
error_reporting(E_ALL);

spl_autoload_register(function(string $class){
    if (strpos($class, 'App\\') === 0) {
        $path = __DIR__ . '/../src/' . str_replace('App\\', '', $class) . '.php';
        $path = str_replace('\\', '/', $path);
        if (file_exists($path)) require $path;
    }
});

if (file_exists(__DIR__ . '/../jdf.php')) {
    require_once __DIR__ . '/../jdf.php';
}

use App\Service\ServerHealthService;

$results = ServerHealthService::checkAll();

foreach ($results as $id => $res) {
    echo sprintf("Server #%d: %s (%s)\n", $id, $res['status'] ? 'OK' : 'FAIL', $res['message'] ?? '');
}
