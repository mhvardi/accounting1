<?php
namespace App\Core;

require_once __DIR__ . '/../../jdf.php';

class Date
{
    private static bool $tzSynced = false;

    private static function ensureTimezone(): void
    {
        if (!self::$tzSynced) {
            date_default_timezone_set('Asia/Tehran');
            self::$tzSynced = true;
        }
    }

    public static function financialYears(): array
    {
        return [1403, 1404, 1405, 1406];
    }

    public static function monthNames(): array
    {
        return [
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
            5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
            9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
        ];
    }

    public static function currentJalali(): array
    {
        self::ensureTimezone();
        $y = (int)Str::normalizeDigits(self::j('Y'));
        $m = (int)Str::normalizeDigits(self::j('n'));
        $d = (int)Str::normalizeDigits(self::j('j'));

        return [$y, $m, $d];
    }
    /**
     * تاریخ/زمان فعلی به صورت شمسی
     */
    public static function j(string $format = 'Y/m/d'): string
    {
        self::ensureTimezone();
        return jdate($format);
    }

    /**
     * نمایش تاریخ ذخیره‌شده در دیتابیس به صورت شمسی.
     *
     * - اگر رشته خودش شمسی باشد (۱۳xx/.. یا ۱۴xx/.. با / یا - و ماه/روز یک یا دو رقمی)،
     *   همان را فقط نرمال‌سازی کرده و به صورت YYYY/MM/DD برمی‌گرداند.
     * - اگر میلادی باشد، با strtotime + jdate به شمسی تبدیل می‌شود.
     */
    public static function jDate(?string $value): string
    {
        self::ensureTimezone();
        if ($value === null) {
            return '';
        }

        $value = trim($value);
        if ($value === '') {
            return '';
        }

        // نرمال‌سازی اعداد فارسی → انگلیسی
        $norm = Str::normalizeDigits($value);

        // اگر شبیه تاریخ شمسی است: 13xx/.. یا 14xx/.. با / یا -
        if (preg_match('/^(13|14)(\d{2})[\/-](\d{1,2})[\/-](\d{1,2})$/', $norm, $m)) {
            $year  = $m[1] . $m[2];
            $month = str_pad($m[3], 2, '0', STR_PAD_LEFT);
            $day   = str_pad($m[4], 2, '0', STR_PAD_LEFT);
            return $year . '/' . $month . '/' . $day;
        }

        // در غیر این صورت فرض می‌کنیم میلادی است و تبدیل می‌کنیم
        $ts = strtotime($norm);
        if ($ts === false || $ts < 0) {
            return '';
        }

        $jalali = jdate('Y/m/d', $ts);
        if (preg_match('/^(\-?\d{1,4})/', $jalali, $ym)) {
            $jy = (int)$ym[1];
            if ($jy < 1200) {
                return '';
            }
        }

        return $jalali;
    }

    /**
     * تبدیل ورودی تاریخ شمسی فرم به فرمت ذخیره در دیتابیس (همیشه شمسی YYYY/MM/DD).
     */
    public static function fromJalaliInput(?string $value): ?string
    {
        self::ensureTimezone();

        $value = trim(Str::normalizeDigits((string)$value));
        if ($value === '') {
            [$jy, $jm, $jd] = self::currentJalali();
            [$gy, $gm, $gd] = jalali_to_gregorian($jy, $jm, $jd);
            return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
        }

        // جداکننده‌ها را به / تبدیل می‌کنیم
        $value = str_replace(['-', '٫', '،', ' '], '/', $value);

        // اگر تاریخ شمسی باشد، به میلادی (YYYY-MM-DD) تبدیل می‌کنیم
        if (preg_match('/^(\d{3,4})[\/](\d{1,2})[\/](\d{1,2})$/', $value, $m)) {
            [$gy, $gm, $gd] = jalali_to_gregorian((int)$m[1], (int)$m[2], (int)$m[3]);
            return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
        }

        // در غیر این صورت اگر ورودی میلادی باشد
        $ts = strtotime($value);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }

        return null;
    }

    /**
     * تبدیل timestamp یونیکس به تاریخ شمسی
     */
    public static function jFromTimestamp(int $timestamp, string $format = 'Y/m/d H:i'): string
    {
        self::ensureTimezone();
        return jdate($format, $timestamp);
    }

    /**
     * بازه‌ی شروع و پایان یک ماه شمسی به صورت timestamp یونیکس
     */
    public static function jalaliMonthRangeTs(int $jy, int $jm): array
    {
        self::ensureTimezone();
        if ($jm < 1) $jm = 1;
        if ($jm > 12) $jm = 12;
        $jd = 1;

        [$gy, $gm, $gd] = jalali_to_gregorian($jy, $jm, $jd);
        $start = mktime(0, 0, 0, $gm, $gd, $gy);

        $nextJYear  = $jy;
        $nextJMonth = $jm + 1;
        if ($nextJMonth === 13) {
            $nextJMonth = 1;
            $nextJYear++;
        }
        [$gy2, $gm2, $gd2] = jalali_to_gregorian($nextJYear, $nextJMonth, 1);
        $end = mktime(0, 0, 0, $gm2, $gd2, $gy2) - 1;

        return [$start, $end];
    }

    /**
     * تبدیل تاریخ شمسی (YYYY/MM/DD یا YYYY-MM-DD) به timestamp یونیکس.
     * اگر ورودی نامعتبر باشد، null برمی‌گرداند.
     */
    public static function jalaliToTimestamp(string $value, bool $endOfDay = false): ?int
    {
        self::ensureTimezone();
        $norm = Str::normalizeDigits(trim($value));
        if ($norm === '') {
            return null;
        }

        $norm = str_replace(['-', '٫', '،', ' '], '/', $norm);
        if (!preg_match('/^(\d{3,4})\/(\d{1,2})\/(\d{1,2})$/', $norm, $m)) {
            return null;
        }

        [$gy, $gm, $gd] = jalali_to_gregorian((int)$m[1], (int)$m[2], (int)$m[3]);
        $h  = $endOfDay ? 23 : 0;
        $mi = $endOfDay ? 59 : 0;
        $s  = $endOfDay ? 59 : 0;
        return mktime($h, $mi, $s, $gm, $gd, $gy);
    }

    /**
     * محاسبه بازه زمانی بر اساس تاریخ شمسی شروع و پایان.
     * اگر یکی از تاریخ‌ها نامعتبر باشد، null برمی‌گرداند.
     */
    public static function jalaliRange(string $start, string $end): ?array
    {
        $startTs = self::jalaliToTimestamp($start, false);
        $endTs   = self::jalaliToTimestamp($end, true);

        if ($startTs === null || $endTs === null) {
            return null;
        }

        if ($endTs < $startTs) {
            return null;
        }

        return [$startTs, $endTs];
    }
}
