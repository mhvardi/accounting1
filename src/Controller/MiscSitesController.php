<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\View;
use App\Core\Date;
use App\Core\Database;
use App\Core\Str;
use PDO;
use PDOException;

class MiscSitesController
{
    /**
     * اتصال مستقیم به دیتابیس vardi_pay بر اساس config.php
     */
    private function externalPdo(): PDO
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

    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            return;
        }

        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                $this->storeLedgerEntry();
            }

            $pdo = $this->externalPdo();

            $todayStart = strtotime('today 00:00:00');
            $todayEnd   = strtotime('today 23:59:59');

            $yesterdayStart = $todayStart - 86400;
            $yesterdayEnd   = $todayStart - 1;

            $thisWeekStart  = $todayStart - 6 * 86400;
            $thisWeekEnd    = $todayEnd;

            $lastWeekStart  = $thisWeekStart - 7 * 86400;
            $lastWeekEnd    = $thisWeekStart - 1;

            $currentJYear  = (int)Date::j('Y');
            $currentJMonth = (int)Date::j('n');

            [$thisMonthStart, $thisMonthEnd] = $this->jalaliMonthRangeTs($currentJYear, $currentJMonth);

            $prevMonth = $currentJMonth - 1;
            $prevYear  = $currentJYear;
            if ($prevMonth === 0) {
                $prevMonth = 12;
                $prevYear--;
            }
            [$lastMonthStart, $lastMonthEnd] = $this->jalaliMonthRangeTs($prevYear, $prevMonth);

            $stats = [
                'today'      => $this->rangeStats($pdo, $todayStart,     $todayEnd,     'امروز'),
                'yesterday'  => $this->rangeStats($pdo, $yesterdayStart, $yesterdayEnd, 'دیروز'),
                'this_week'  => $this->rangeStats($pdo, $thisWeekStart,  $thisWeekEnd,  '۷ روز اخیر'),
                'last_week'  => $this->rangeStats($pdo, $lastWeekStart,  $lastWeekEnd,  'هفته قبل'),
                'this_month' => $this->rangeStats($pdo, $thisMonthStart, $thisMonthEnd, 'ماه جاری (شمسی)'),
                'last_month' => $this->rangeStats($pdo, $lastMonthStart, $lastMonthEnd, 'ماه قبل (شمسی)'),
            ];

            $internal = Database::connection();
            $ledgerSummary = $internal->query("SELECT site_name,
                       SUM(CASE WHEN kind='income' THEN amount_rial ELSE 0 END) AS income_rial,
                       SUM(CASE WHEN kind='expense' THEN amount_rial ELSE 0 END) AS expense_rial
                       FROM external_site_ledger
                       GROUP BY site_name
                       ORDER BY site_name")->fetchAll();

            $recentLedger = $internal->query("SELECT * FROM external_site_ledger ORDER BY occurred_at DESC, id DESC LIMIT 20")->fetchAll();

            $siteNames = $pdo->query("SELECT DISTINCT site_name FROM central_payments WHERE site_name IS NOT NULL AND site_name != '' ORDER BY site_name")->fetchAll();
            foreach ($ledgerSummary as $row) {
                $siteNames[] = ['site_name' => $row['site_name']];
            }
            $siteOptions = [];
            foreach ($siteNames as $row) {
                $siteOptions[$row['site_name']] = true;
            }
            ksort($siteOptions);

        } catch (PDOException $e) {
            View::renderError('خطا در سایت‌های متفرقه: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        View::render('misc_sites/index', [
            'user'          => Auth::user(),
            'stats'         => $stats,
            'ledgerSummary' => $ledgerSummary ?? [],
            'recentLedger'  => $recentLedger ?? [],
            'siteOptions'   => array_keys($siteOptions ?? []),
        ]);
    }

    private function storeLedgerEntry(): void
    {
        $site   = Str::beautifyLabel($_POST['site_name'] ?? 'استارپلن');
        $kind   = ($_POST['kind'] ?? 'expense') === 'income' ? 'income' : 'expense';
        $amount = (int)Str::normalizeDigits($_POST['amount'] ?? '0');
        $amountRial = $amount * 10; // ورودی به تومان، ذخیره به ریال برای هم‌خوانی با سایر داده‌ها
        $note   = trim($_POST['note'] ?? '');
        $occ    = Str::normalizeDigits(trim($_POST['occurred_at'] ?? ''));
        $occDate = Date::fromJalaliInput($occ);
        if ($occDate === null) {
            $occDate = date('Y-m-d');
        }

        if ($site === '' || $amountRial <= 0) {
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("INSERT INTO external_site_ledger (site_name, kind, amount_rial, note, occurred_at, created_at, updated_at)
                               VALUES (?,?,?,?,?,?,?)");
        $now = date('Y-m-d H:i:s');
        $stmt->execute([$site, $kind, $amountRial, $note, $occDate, $now, $now]);
    }

    private function rangeStats(PDO $pdo, int $startTs, int $endTs, string $label): array
    {
        $sql = "SELECT site_name, SUM(amount) AS total 
                FROM central_payments 
                WHERE status = 'paid' 
                  AND created_at >= :start 
                  AND created_at <= :end
                GROUP BY site_name
                ORDER BY total DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':start' => date('Y-m-d H:i:s', $startTs),
            ':end'   => date('Y-m-d H:i:s', $endTs),
        ]);
        $rows = $stmt->fetchAll();

        $total = 0;
        foreach ($rows as $r) {
            $total += (int)$r['total'];
        }

        return [
            'label'   => $label,
            'from_ts' => $startTs,
            'to_ts'   => $endTs,
            'rows'    => $rows,
            'total'   => $total,
        ];
    }

    private function jalaliMonthRangeTs(int $jy, int $jm): array
    {
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
}