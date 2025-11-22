<?php
namespace App\Core;

class ErrorHandler
{
    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleException(\Throwable $e): void
    {
        http_response_code(500);
        echo "<h2>Internal error</h2>";
        echo "<p><strong>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</strong></p>";
        echo "<p>File: " . htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8') .
             " (line " . (int)$e->getLine() . ")</p>";
        echo "<h3>Trace:</h3>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . "</pre>";
    }
}
