<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\View;
use App\Core\Database;
use App\Core\Date;
use App\Core\Str;
use PDO;
use PDOException;

class ReportsController
{
    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            return;
        }

        $financialYears = Date::financialYears();
        [$currentJYear, $currentJMonth] = Date::currentJalali();
        $currentFY      = in_array($currentJYear, $financialYears, true) ? $currentJYear : max($financialYears);
        $yearInput      = Str::normalizeDigits($_GET['year'] ?? '');
        $monthInput     = Str::normalizeDigits($_GET['month'] ?? '');
        $range          = $_GET['range'] ?? 'current';
        $customStart    = Str::normalizeDigits(trim($_GET['start'] ?? ''));
        $customEnd      = Str::normalizeDigits(trim($_GET['end'] ?? ''));

        $year  = (int)$yearInput;
        if (!in_array($year, $financialYears, true)) {
            $year = $currentFY;
        }

        $month = (int)$monthInput;
        if ($month < 1 || $month > 12) {
            $month = $currentJMonth;
        }

        $pdo = Database::connection();

        $summary = [
            'contracts_total'        => 0,
            'contracts_cost_total'   => 0,
            'payments_total'         => 0,
            'external_total'         => 0,
            'payroll_total'          => 0,
            'expenses_total'         => 0,
            'profit_contract_based'  => 0,
            'profit_cash_based'      => 0,
            'profit_with_external'   => 0,
        ];
        $prevSummary = $summary;
        $periodLabel = '';
        $monthlySeries = [];
        $payrollDetail = [];
        $expensesByCategory = [];
        $contractsByCategory = [];
        $paymentsByCategory = [];

        try {
            [$startTs, $endTs, $startJy, $startJm, $endJy, $endJm, $rangeMonths, $periodLabel, $year, $month] =
                $this->resolveRange($year, $month, $range, $customStart, $customEnd, $currentJYear, $currentJMonth);

            $startDt   = date('Y-m-d H:i:s', $startTs);
            $endDt     = date('Y-m-d H:i:s', $endTs);
            $startDate = date('Y-m-d', $startTs);
            $endDate   = date('Y-m-d', $endTs);
            $startYm   = $startJy * 100 + $startJm;
            $endYm     = $endJy * 100 + $endJm;

            // مقادیر اصلی بازه فعلی
            $currentContracts = $this->sumContracts($pdo, $startDate, $endDate);
            $summary['contracts_total'] = $currentContracts['sale'];
            $summary['contracts_cost_total'] = $currentContracts['cost'];
            $summary['payments_total']  = $this->sumPayments($pdo, $startDt, $endDt);
            $summary['payroll_total']   = $this->sumPayroll($pdo, $startYm, $endYm);
            $summary['expenses_total']  = $this->sumExpenses($pdo, $startDate, $endDate);
            $expensesByCategory         = $this->expensesByCategory($pdo, $startDate, $endDate);
            $summary['external_total']  = $this->externalRevenue($startDt, $endDt);
            $contractsByCategory        = $this->contractsByCategory($pdo, $startDate, $endDate);
            $paymentsByCategory         = $this->paymentsByCategory($pdo, $startDt, $endDt);

            $summary['profit_contract_based'] = ($summary['contracts_total'] - $summary['contracts_cost_total']) - $summary['payroll_total'] - $summary['expenses_total'];
            $summary['profit_cash_based']     = $summary['payments_total']  - $summary['payroll_total'] - $summary['expenses_total'];
            $summary['profit_with_external']  = ($summary['payments_total'] + $summary['external_total']) - $summary['payroll_total'] - $summary['expenses_total'];

            // بازه قبلی هم‌اندازه برای مقایسه
            [$pStartTs, $pEndTs, $pStartJy, $pStartJm, $pEndJy, $pEndJm] = $this->previousRange($startTs, $rangeMonths);
            $pStartDt   = date('Y-m-d H:i:s', $pStartTs);
            $pEndDt     = date('Y-m-d H:i:s', $pEndTs);
            $pStartDate = date('Y-m-d', $pStartTs);
            $pEndDate   = date('Y-m-d', $pEndTs);
            $pStartYm   = $pStartJy * 100 + $pStartJm;
            $pEndYm     = $pEndJy * 100 + $pEndJm;

            $prevContracts = $this->sumContracts($pdo, $pStartDate, $pEndDate);
            $prevSummary['contracts_total'] = $prevContracts['sale'];
            $prevSummary['contracts_cost_total'] = $prevContracts['cost'];
            $prevSummary['payments_total']  = $this->sumPayments($pdo, $pStartDt, $pEndDt);
            $prevSummary['payroll_total']   = $this->sumPayroll($pdo, $pStartYm, $pEndYm);
            $prevSummary['expenses_total']  = $this->sumExpenses($pdo, $pStartDate, $pEndDate);
            $prevSummary['external_total']  = $this->externalRevenue($pStartDt, $pEndDt);
            $prevSummary['profit_contract_based'] = ($prevSummary['contracts_total'] - ($prevSummary['contracts_cost_total'] ?? 0)) - $prevSummary['payroll_total'] - $prevSummary['expenses_total'];
            $prevSummary['profit_cash_based']     = $prevSummary['payments_total']  - $prevSummary['payroll_total'] - $prevSummary['expenses_total'];
            $prevSummary['profit_with_external']  = ($prevSummary['payments_total'] + $prevSummary['external_total']) - $prevSummary['payroll_total'] - $prevSummary['expenses_total'];

            // سری ۱۲ ماه اخیر برای نمودار - انتهای بازه جاری مبناست
            $yearMonthList = [];
            $y = $endJy;
            $m = $endJm;
            for ($i = 0; $i < 12; $i++) {
                $yearMonthList[] = [$y, $m];
                $m--;
                if ($m === 0) {
                    $m = 12;
                    $y--;
                }
            }
            $yearMonthList = array_reverse($yearMonthList);

            foreach ($yearMonthList as [$yy, $mm]) {
                [$ms, $me] = Date::jalaliMonthRangeTs($yy, $mm);
                $msDt = date('Y-m-d H:i:s', $ms);
                $meDt = date('Y-m-d H:i:s', $me);
                $rev = $this->sumPayments($pdo, $msDt, $meDt);

                $stmt = $pdo->prepare("SELECT SUM(total_payable) FROM employee_payrolls WHERE year = :y AND month = :m");
                $stmt->execute([':y'=>$yy, ':m'=>$mm]);
                $payroll = (int)($stmt->fetchColumn() ?: 0);

                $exp = $this->sumExpenses($pdo, date('Y-m-d', $ms), date('Y-m-d', $me));

                $ext = $this->externalRevenue($msDt, $meDt);

                $profit = $rev - $payroll - $exp;
                $profitWithExt = ($rev + $ext) - $payroll - $exp;

                $monthlySeries[] = [
                    'label'        => $yy . '/' . str_pad($mm,2,'0',STR_PAD_LEFT),
                    'revenue'      => $rev,
                    'external'     => $ext,
                    'payroll'      => $payroll,
                    'expense'      => $exp,
                    'profit'       => $profit,
                    'profit_ext'   => $profitWithExt,
                ];
            }

            // جزئیات حقوق در بازه انتخابی
            $stmt = $pdo->prepare("SELECT p.*, e.full_name
                                   FROM employee_payrolls p
                                   JOIN employees e ON e.id = p.employee_id
                                   WHERE (p.year*100 + p.month) BETWEEN :s AND :e
                                   ORDER BY p.year DESC, p.month DESC, p.id DESC");
            $stmt->execute([':s'=>$startYm, ':e'=>$endYm]);
            $payrollDetail = $stmt->fetchAll();

        } catch (PDOException $e) {
            View::renderError('خطا در گزارش‌ها: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        $analysis = $this->buildNarrative($periodLabel, $summary, $prevSummary);

        View::render('reports/index', [
            'user'              => Auth::user(),
            'year'              => $year,
            'month'             => $month,
            'range'             => $range,
            'periodLabel'       => $periodLabel,
            'customStart'       => $customStart,
            'customEnd'         => $customEnd,
            'financialYears'    => $financialYears,
            'summary'           => $summary,
            'prevSummary'       => $prevSummary,
            'monthlySeries'     => $monthlySeries,
            'payrollDetail'     => $payrollDetail,
            'analysis'          => $analysis,
            'expensesByCategory'=> $expensesByCategory,
            'contractsByCategory'=> $contractsByCategory,
            'paymentsByCategory'=> $paymentsByCategory,
        ]);
    }

    private function sumContracts(PDO $pdo, string $startDate, string $endDate): array
    {
        $stmt = $pdo->prepare("SELECT SUM(total_amount) AS sale, SUM(total_cost_amount) AS cost FROM contracts WHERE start_date IS NOT NULL AND start_date >= :s AND start_date <= :e");
        $stmt->execute([':s'=>$startDate, ':e'=>$endDate]);
        $row = $stmt->fetch();
        return [
            'sale' => (int)($row['sale'] ?? 0),
            'cost' => (int)($row['cost'] ?? 0),
        ];
    }

    private function sumPayments(PDO $pdo, string $startDt, string $endDt): int
    {
        $stmt = $pdo->prepare("SELECT SUM(amount) AS s FROM payments WHERE status = 'paid' AND paid_at >= :s AND paid_at <= :e");
        $stmt->execute([':s'=>$startDt, ':e'=>$endDt]);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    private function sumPayroll(PDO $pdo, int $startYm, int $endYm): int
    {
        $stmt = $pdo->prepare("SELECT SUM(total_payable) AS s FROM employee_payrolls WHERE (year*100 + month) BETWEEN :s AND :e");
        $stmt->execute([':s'=>$startYm, ':e'=>$endYm]);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    private function sumExpenses(PDO $pdo, string $startDate, string $endDate): int
    {
        $stmt = $pdo->prepare("SELECT SUM(amount) AS s FROM expenses WHERE expense_date IS NOT NULL AND expense_date >= :s AND expense_date <= :e");
        $stmt->execute([':s'=>$startDate, ':e'=>$endDate]);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    private function expensesByCategory(PDO $pdo, string $startDate, string $endDate): array
    {
        $stmt = $pdo->prepare("SELECT category, SUM(amount) AS total
                               FROM expenses
                               WHERE expense_date IS NOT NULL AND expense_date >= :s AND expense_date <= :e
                               GROUP BY category
                               ORDER BY total DESC");
        $stmt->execute([':s'=>$startDate, ':e'=>$endDate]);
        return $stmt->fetchAll();
    }

    private function contractsByCategory(PDO $pdo, string $startDate, string $endDate): array
    {
        $sql = "SELECT c.category_id, COALESCE(pc.name, 'نامشخص') AS category_name, SUM(c.total_amount) AS total
                FROM contracts c
                LEFT JOIN product_categories pc ON pc.id = c.category_id
                WHERE c.start_date IS NOT NULL AND c.start_date >= :s AND c.start_date <= :e
                GROUP BY c.category_id, pc.name
                ORDER BY total DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':s'=>$startDate, ':e'=>$endDate]);
        return $stmt->fetchAll();
    }

    private function paymentsByCategory(PDO $pdo, string $startDt, string $endDt): array
    {
        $sql = "SELECT c.category_id, COALESCE(pc.name, 'نامشخص') AS category_name, SUM(p.amount) AS total
                FROM payments p
                LEFT JOIN contracts c ON c.id = p.contract_id
                LEFT JOIN product_categories pc ON pc.id = c.category_id
                WHERE p.status = 'paid' AND p.paid_at >= :s AND p.paid_at <= :e
                GROUP BY c.category_id, pc.name
                ORDER BY total DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':s'=>$startDt, ':e'=>$endDt]);
        return $stmt->fetchAll();
    }

    private function externalRevenue(string $startDt, string $endDt): int
    {
        try {
            $config = require __DIR__ . '/../../config/config.php';
            $host = $config['gateway_db_host'] ?? null;
            $db   = $config['gateway_db_name'] ?? null;
            $user = $config['gateway_db_user'] ?? null;
            $pass = $config['gateway_db_pass'] ?? null;
            if (!$db || !$user) {
                return 0;
            }
            $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $sql = "SELECT SUM(amount) AS total FROM central_payments
                    WHERE status = 'paid'
                      AND created_at >= :s
                      AND created_at <= :e";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':s'=>$startDt, ':e'=>$endDt]);
            $rial = (int)($stmt->fetchColumn() ?: 0);
            return (int)round($rial / 10);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function changePercent(int $current, int $prev): ?float
    {
        if ($prev === 0) return null;
        return round((($current - $prev) / $prev) * 100, 1);
    }

    private function buildNarrative(string $periodLabel, array $s, array $p): array
    {
        $cp = $this->changePercent($s['payments_total'], $p['payments_total']);
        $ce = $this->changePercent($s['expenses_total'], $p['expenses_total']);
        $cr = $this->changePercent($s['contracts_total'], $p['contracts_total']);
        $cProfit = $this->changePercent($s['profit_with_external'], $p['profit_with_external']);

        $lines = [];

        $lines[] = $periodLabel . " را می‌توان این‌طور خلاصه کرد:";

        if ($cp !== null) {
            if ($cp > 0) $lines[] = "• دریافتی واقعی (بدون سایت‌های متفرقه) نسبت به بازه قبل حدود " . $cp . "٪ رشد داشته است.";
            elseif ($cp < 0) $lines[] = "• دریافتی واقعی نسبت به بازه قبل حدود " . abs($cp) . "٪ کاهش داشته است.";
            else $lines[] = "• دریافتی واقعی تقریباً برابر با بازه قبل بوده است.";
        }

        if ($s['external_total'] > 0) {
            $lines[] = "• درآمد سایت‌های متفرقه در این بازه حدود " . number_format($s['external_total']) . " تومان بوده است.";
        }

        if ($cr !== null && $s['contracts_total'] > 0) {
            if ($cr > 0) $lines[] = "• مجموع مبلغ قراردادهای ثبت‌شده حدود " . $cr . "٪ بیشتر از بازه قبل است.";
            elseif ($cr < 0) $lines[] = "• مجموع مبلغ قراردادها حدود " . abs($cr) . "٪ کمتر از بازه قبل است.";
        }

        if ($ce !== null && $s['expenses_total'] > 0) {
            if ($ce > 0) $lines[] = "• هزینه‌ها نسبت به بازه قبل حدود " . $ce . "٪ افزایش داشته‌اند؛ خوب است دسته‌بندی هزینه‌ها را بررسی کنید.";
            elseif ($ce < 0) $lines[] = "• هزینه‌های شما نسبت به بازه قبل کاهش یافته که نشانه‌ی بهینه‌سازی است.";
        }

        if ($cProfit !== null) {
            if ($cProfit > 0) $lines[] = "• سود خالص با احتساب سایت‌های متفرقه حدود " . $cProfit . "٪ بیشتر از بازه قبل است.";
            elseif ($cProfit < 0) $lines[] = "• سود خالص با احتساب سایت‌های متفرقه نسبت به بازه قبل حدود " . abs($cProfit) . "٪ کمتر شده است.";
        }

        $targetRevenue = (int)round(($s['payments_total'] + $s['external_total']) * 1.2);
        if ($targetRevenue > 0) {
            $lines[] = "• با توجه به ارقام فعلی، یک تارگت منطقی برای بازه بعدی می‌تواند حدود " . number_format($targetRevenue) . " تومان دریافتی (با احتساب سایت‌های متفرقه) باشد؛ یعنی رشد حدود 20٪.";
        }

        return $lines;
    }

    private function subtractMonths(int $jy, int $jm, int $count): array
    {
        for ($i = 0; $i < $count; $i++) {
            $jm--;
            if ($jm === 0) {
                $jm = 12;
                $jy--;
            }
        }
        return [$jy, $jm];
    }

    private function resolveRange(int $year, int $month, string $range, string $customStart, string $customEnd, int $currentJYear, int $currentJMonth): array
    {
        $rangeMonths = 1;
        $periodLabel = '';

        switch ($range) {
            case 'prev':
                [$year, $month] = $this->subtractMonths($year, $month, 1);
                break;
            case '3m':
                $rangeMonths = 3;
                break;
            case '6m':
                $rangeMonths = 6;
                break;
            case '12m':
                $rangeMonths = 12;
                break;
            case 'custom':
                $customRange = ($customStart && $customEnd) ? Date::jalaliRange($customStart, $customEnd) : null;
                if ($customRange) {
                    [$startTs, $endTs] = $customRange;
                    $startJy = (int)jdate('Y', $startTs);
                    $startJm = (int)jdate('n', $startTs);
                    $endJy   = (int)jdate('Y', $endTs);
                    $endJm   = (int)jdate('n', $endTs);
                    $periodLabel = 'از ' . Date::jFromTimestamp($startTs, 'Y/m/d') . ' تا ' . Date::jFromTimestamp($endTs, 'Y/m/d');
                    return [$startTs, $endTs, $startJy, $startJm, $endJy, $endJm, max(1, $this->monthDistance($startJy, $startJm, $endJy, $endJm)), $periodLabel, $endJy, $endJm];
                }
                $year = $currentJYear;
                $month = $currentJMonth;
                break;
            default:
                // current month (do nothing)
                break;
        }

        [$startJy, $startJm] = $this->subtractMonths($year, $month, $rangeMonths - 1);
        [$startTs] = Date::jalaliMonthRangeTs($startJy, $startJm);
        [, $endTs] = Date::jalaliMonthRangeTs($year, $month);
        $periodLabel = 'ماه ' . $year . '/' . str_pad($month, 2, '0', STR_PAD_LEFT);
        if ($rangeMonths > 1) {
            $periodLabel = 'از ' . $startJy . '/' . str_pad($startJm, 2, '0', STR_PAD_LEFT) . ' تا ' . $year . '/' . str_pad($month, 2, '0', STR_PAD_LEFT);
        }

        return [$startTs, $endTs, $startJy, $startJm, $year, $month, $rangeMonths, $periodLabel, $year, $month];
    }

    private function previousRange(int $currentStartTs, int $rangeMonths): array
    {
        $prevEndTs = $currentStartTs - 1;
        $prevEndJy = (int)jdate('Y', $prevEndTs);
        $prevEndJm = (int)jdate('n', $prevEndTs);
        [$prevStartJy, $prevStartJm] = $this->subtractMonths($prevEndJy, $prevEndJm, $rangeMonths - 1);
        [$prevStartTs] = Date::jalaliMonthRangeTs($prevStartJy, $prevStartJm);
        return [$prevStartTs, $prevEndTs, $prevStartJy, $prevStartJm, $prevEndJy, $prevEndJm];
    }

    private function monthDistance(int $sy, int $sm, int $ey, int $em): int
    {
        return (($ey - $sy) * 12) + ($em - $sm) + 1;
    }
}
