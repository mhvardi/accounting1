<?php
namespace App\Core;

class Helpers
{
    /**
     * بر اساس SCRIPT_NAME، مسیر پایه (base path) پروژه را حساب می‌کند.
     * اگر پروژه در /public باشد، خروجی /public است. اگر در روت باشد، خروجی رشته خالی است.
     */
    public static function basePath(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir        = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($dir === '/' || $dir === '\\') {
            return '';
        }
        return $dir;
    }
}
