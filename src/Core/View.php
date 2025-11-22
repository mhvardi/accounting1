<?php
namespace App\Core;

class View
{
    public static function render(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../../views/' . $view . '.php';
        $viewName = $view;
        include __DIR__ . '/../../views/layout.php';
    }

    public static function renderError(string $message, ?string $trace = null, ?array $user = null): void
    {
        $viewFile = __DIR__ . '/../../views/error/generic.php';
        $data = ['message' => $message, 'trace' => $trace, 'user' => $user];
        extract($data);
        include __DIR__ . '/../../views/layout.php';
        exit;
    }
}
