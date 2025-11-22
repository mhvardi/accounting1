<?php
namespace App\Service;

use App\Core\Database;
use App\Core\Date;
use DateTime;

class ReportService
{
    public function getMonthlySummary(int $year, int $month): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("
            SELECT SUM(amount) AS s FROM payments
            WHERE status = 'paid'
              AND YEAR(paid_at) = :y
              AND MONTH(paid_at) = :m
        ");
        $stmt->execute([':y' => $year, ':m' => $month]);
        $income = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT SUM(total_amount) AS s FROM external_monthly_income
            WHERE year = :y AND month = :m
        ");
        $stmt->execute([':y' => $year, ':m' => $month]);
        $externalIncome = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT SUM(amount) AS s FROM expenses
            WHERE YEAR(occurred_at) = :y
              AND MONTH(occurred_at) = :m
        ");
        $stmt->execute([':y' => $year, ':m' => $month]);
        $expenses = (int)$stmt->fetchColumn();

        $totalIncome = $income + $externalIncome;
        $profit      = $totalIncome - $expenses;

        return [
            'income'          => $income,
            'external_income' => $externalIncome,
            'total_income'    => $totalIncome,
            'expenses'        => $expenses,
            'profit'          => $profit,
        ];
    }

    public function getLast12MonthsSummary(): array
    {
        $result = [];
        $now = new DateTime('first day of this month');
        for ($i = 11; $i >= 0; $i--) {
            $dt  = (clone $now)->modify("-{$i} month");
            $y   = (int)$dt->format('Y');
            $m   = (int)$dt->format('n');
            $key = $dt->format('Y-m');
            $summary = $this->getMonthlySummary($y, $m);
            $summary['label'] = Date::jMonthLabel($y, $m);
            $result[$key] = $summary;
        }
        return $result;
    }
}
