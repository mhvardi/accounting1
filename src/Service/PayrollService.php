<?php
namespace App\Service;

use App\Core\Database;
use App\Core\Date;
use App\Core\Str;

class PayrollService
{
    public function computeCommissionForMonth(int $employeeId, int $year, int $month, string $basis): array
    {
        $pdo = Database::connection();

        $stmtEmp = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
        $stmtEmp->execute([$employeeId]);
        $employee = $stmtEmp->fetch();

        if (!$employee) {
            return [
                'employee'          => null,
                'salesAmount'       => 0,
                'commissionAmount'  => 0,
                'commissionPercent' => 0,
                'contracts'         => [],
                'payments'          => [],
            ];
        }

        [$startTs, $endTs] = Date::jalaliMonthRangeTs($year, $month);
        $startDate = date('Y-m-d', $startTs);
        $endDate   = date('Y-m-d', $endTs);
        $startDt   = date('Y-m-d H:i:s', $startTs);
        $endDt     = date('Y-m-d H:i:s', $endTs);

        $config = json_decode($employee['commission_config_json'] ?? '', true) ?: [];
        $tiers  = $config['tiers'] ?? [];
        $categories = $config['categories'] ?? [];
        $categoryCompanyWide = !empty($config['category_company_wide']);

        $scope = $employee['commission_scope'] ?? 'self';
        $contracts = [];
        $payments  = [];
        $salesAmount = 0;

        if ($basis === 'cash_collected') {
            $sql = "SELECT p.*, c.title AS contract_title, c.category_id, c.sales_employee_id
                    FROM payments p
                    LEFT JOIN contracts c ON c.id = p.contract_id
                    WHERE p.status = 'paid'";
            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                $ts = $this->resolveTimestamp($row['paid_at'] ?? null, $row['pay_date'] ?? null, true);
                if ($ts === null || $ts < $startTs || $ts > $endTs) {
                    continue;
                }

                if ($scope === 'self' && (int)($row['sales_employee_id'] ?? 0) !== $employeeId) {
                    continue;
                }
                if ($scope === 'category') {
                    $rowCategoryId = (int)($row['category_id'] ?? 0);
                    $salesPersonId = (int)($row['sales_employee_id'] ?? 0);

                    if (!empty($categories) && !in_array($rowCategoryId, $categories, true)) {
                        continue;
                    }

                    if (!$categoryCompanyWide && $salesPersonId !== 0 && $salesPersonId !== $employeeId) {
                        continue;
                    }
                }

                $salesAmount += (int)($row['amount'] ?? 0);
                $payments[] = $row;
            }
        } else {
            $sql = "SELECT * FROM contracts WHERE start_date IS NOT NULL";
            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                $ts = $this->resolveTimestamp($row['start_date'] ?? null, $row['start_date'] ?? null, false);
                if ($ts === null || $ts < $startTs || $ts > $endTs) {
                    continue;
                }

                if ($scope === 'self' && (int)($row['sales_employee_id'] ?? 0) !== $employeeId) {
                    continue;
                }
                if ($scope === 'category') {
                    $rowCategoryId = (int)($row['category_id'] ?? 0);
                    $salesPersonId = (int)($row['sales_employee_id'] ?? 0);

                    if (!empty($categories) && !in_array($rowCategoryId, $categories, true)) {
                        continue;
                    }

                    if (!$categoryCompanyWide && $salesPersonId !== 0 && $salesPersonId !== $employeeId) {
                        continue;
                    }
                }

                $salesAmount += (int)($row['total_amount'] ?? 0);
                $contracts[] = $row;
            }
        }

        $commissionPercent = $this->resolvePercent(
            $employee['compensation_type'] ?? 'fixed',
            $employee['commission_mode'] ?? 'none',
            (float)($employee['commission_percent'] ?? 0),
            $tiers,
            $salesAmount
        );

        $commissionAmount = (int)round($salesAmount * $commissionPercent / 100);

        return [
            'employee'          => $employee,
            'salesAmount'       => $salesAmount,
            'commissionAmount'  => $commissionAmount,
            'commissionPercent' => $commissionPercent,
            'contracts'         => $contracts,
            'payments'          => $payments,
        ];
    }

    public function listPayrollsByMonth(int $year, int $month): array
    {
        $pdo = Database::connection();
        $sql = "SELECT p.*, e.full_name
                FROM employee_payrolls p
                JOIN employees e ON e.id = p.employee_id
                WHERE p.year = :y AND p.month = :m
                ORDER BY p.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':y'=>$year, ':m'=>$month]);
        return $stmt->fetchAll();
    }

    public function createPayrollByEmployeeId(int $employeeId, int $year, int $month, string $basis, array $contractIds, int $bonus, int $advance, int $otherDeductions, string $note): int
    {
        $pdo = Database::connection();

        $calc = $this->computeCommissionForMonth($employeeId, $year, $month, $basis);
        if (!$calc['employee']) {
            return 0;
        }

        $employee      = $calc['employee'];
        $salesAmount   = $calc['salesAmount'];
        $commission    = $calc['commissionAmount'];
        $percent       = $calc['commissionPercent'];

        if (($employee['compensation_type'] ?? 'fixed') === 'fixed' || ($employee['commission_mode'] ?? 'none') === 'none') {
            $percent    = 0;
            $commission = 0;
        }

        $baseSalary  = (int)$employee['base_salary'];
        $totalPayable = $baseSalary + $commission + $bonus - $advance - $otherDeductions;

        $pdo->beginTransaction();
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO employee_payrolls
            (employee_id, year, month, basis, sales_amount, commission_amount, base_salary, bonus_amount, advance_amount, other_deductions, total_payable, note, status, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $employeeId,
            $year,
            $month,
            $basis,
            $salesAmount,
            $commission,
            $baseSalary,
            $bonus,
            $advance,
            $otherDeductions,
            $totalPayable,
            $note ?: ('پورسانت با نرخ ' . $percent . '٪ محاسبه شد'),
            'paid',
            $now,
            $now,
        ]);
        $payrollId = (int)$pdo->lastInsertId();

        // فعلاً employee_commission_items هم استفاده نمی‌کنیم تا جدول لازم نشود

        $pdo->commit();
        return $payrollId;
    }

    public function deletePayroll(int $id): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare("DELETE FROM employee_payrolls WHERE id = ?");
        $stmt->execute([$id]);
    }

    private function resolveTimestamp(?string $primary, ?string $fallback, bool $endOfDay): ?int
    {
        $candidates = [$primary, $fallback];
        foreach ($candidates as $value) {
            if ($value === null) {
                continue;
            }
            $value = trim((string)$value);
            if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
                continue;
            }

            $norm = \App\Core\Str::normalizeDigits($value);
            if (preg_match('/^(\d{4})[\/-](\d{2})[\/-](\d{2})/', $norm, $m)) {
                $year = (int)$m[1];
                if ($year >= 1500) {
                    $candidate = str_replace('/', '-', $norm);
                    $ts = strtotime($candidate . ($endOfDay ? ' 23:59:59' : ''));
                    if ($ts !== false) {
                        return $ts;
                    }
                }
            }

            $ts = Date::jalaliToTimestamp($value, $endOfDay);
            if ($ts !== null) {
                return $ts;
            }

            $ts = strtotime($value);
            if ($ts !== false) {
                return $ts;
            }
        }

        return null;
    }

    private function resolvePercent(string $compType, string $mode, float $basePercent, array $tiers, int $salesAmount): float
    {
        if ($compType === 'fixed' || $mode === 'none') {
            return 0.0;
        }

        if ($mode === 'flat') {
            return max(0.0, $basePercent);
        }

        if ($mode === 'tiered') {
            if (!empty($tiers)) {
                usort($tiers, function ($a, $b) {
                    return ($a['min'] ?? 0) <=> ($b['min'] ?? 0);
                });

                $lastPercent = 0.0;
                foreach ($tiers as $tier) {
                    $min = (int)($tier['min'] ?? 0);
                    $max = (int)($tier['max'] ?? 0);
                    $pc  = (float)($tier['percent'] ?? 0);
                    $lastPercent = $pc;
                    if ($salesAmount >= $min && ($max === 0 || $salesAmount <= $max)) {
                        return $pc;
                    }
                }

                return $lastPercent;
            }

            return 0.0;
        }

        return 0.0;
    }
}
