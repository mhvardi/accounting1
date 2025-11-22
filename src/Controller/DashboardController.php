<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Date;
use App\Core\View;
use PDO;
use PDOException;

class DashboardController
{
    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            return;
        }
        $kpis = [
            'revenue'   => 0,
            'external'  => 0,
            'expenses'  => 0,
            'payroll'   => 0,
            'profit'    => 0,
        ];
        $assistant = [];

        try {
            $pdo = Database::connection();
            [$jy, $jm] = Date::currentJalali();
            [$startTs, $endTs] = Date::jalaliMonthRangeTs($jy, $jm);
            $startDt = date('Y-m-d H:i:s', $startTs);
            $endDt   = date('Y-m-d H:i:s', $endTs);
            $startDate = date('Y-m-d', $startTs);
            $endDate   = date('Y-m-d', $endTs);
            $todayStart = date('Y-m-d 00:00:00');
            $todayEnd   = date('Y-m-d 23:59:59');

            $stmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE status='paid' AND paid_at >= :s AND paid_at <= :e");
            $stmt->execute([':s'=>$startDt, ':e'=>$endDt]);
            $kpis['revenue'] = (int)($stmt->fetchColumn() ?: 0);

            $stmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE status='paid' AND paid_at >= :s AND paid_at <= :e");
            $stmt->execute([':s'=>$todayStart, ':e'=>$todayEnd]);
            $todayRevenue = (int)($stmt->fetchColumn() ?: 0);

            $stmt = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE expense_date IS NOT NULL AND expense_date >= :s AND expense_date <= :e");
            $stmt->execute([':s'=>$startDate, ':e'=>$endDate]);
            $kpis['expenses'] = (int)($stmt->fetchColumn() ?: 0);

            $stmt = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE expense_date IS NOT NULL AND expense_date >= :s AND expense_date <= :e");
            $stmt->execute([':s'=>$todayStart, ':e'=>$todayEnd]);
            $todayExpenses = (int)($stmt->fetchColumn() ?: 0);

            $stmt = $pdo->prepare("SELECT SUM(total_payable) FROM employee_payrolls WHERE year = :y AND month = :m");
            $stmt->execute([':y'=>$jy, ':m'=>$jm]);
            $kpis['payroll'] = (int)($stmt->fetchColumn() ?: 0);

            $kpis['external'] = $this->externalRevenue($startDt, $endDt);
            $kpis['profit'] = ($kpis['revenue'] + $kpis['external']) - $kpis['expenses'] - $kpis['payroll'];

            $customersCount = (int)($pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn() ?: 0);
            $contractsCount = (int)($pdo->query("SELECT COUNT(*) FROM contracts")->fetchColumn() ?: 0);

            $monthNames = Date::monthNames();
            $assistant = [
                'month_label'     => $jy . ' / ' . ($monthNames[$jm] ?? $jm),
                'today_revenue'   => $todayRevenue + $this->externalRevenue($todayStart, $todayEnd),
                'today_expenses'  => $todayExpenses,
                'month_revenue'   => $kpis['revenue'] + $kpis['external'],
                'month_expenses'  => $kpis['expenses'] + $kpis['payroll'],
                'customers'       => $customersCount,
                'contracts'       => $contractsCount,
            ];
        } catch (PDOException $e) {
            View::renderError('خطا در پیشخوان: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        View::render('dashboard/index', [
            'user' => Auth::user(),
            'kpis' => $kpis,
            'assistant' => $assistant,
        ]);
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
}
