<?php
namespace App\Core;

class Str
{
    public static function normalizeDigits(string $value, bool $stripSeparators = true): string
    {
        // تبدیل اعداد فارسی و عربی به اعداد انگلیسی
        $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        $ar = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];

        $value = str_replace($fa, $en, $value);
        $value = str_replace($ar, $en, $value);

        if ($stripSeparators) {
            $value = str_replace([',', '٬', ' '], '', $value);
        }

        return $value;
    }

    public static function digitsOnly(string $value): string
    {
        return self::normalizeDigits($value, true);
    }

    public static function beautifyLabel(?string $value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        $value = str_replace(['-', '_', 'ـ', '–', '—'], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim($value);
    }
}
