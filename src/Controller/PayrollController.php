<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\View;
use App\Core\Database;
use App\Core\Date;
use App\Core\Str;
use App\Service\PayrollService;
use PDOException;

class PayrollController
{
    protected function ensureAuth(): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
    }

    public function index(): void
    {
        $this->ensureAuth();

        [$defaultYear, $defaultMonth] = Date::currentJalali();
        $years = Date::financialYears();
        if (!in_array($defaultYear, $years, true)) {
            $defaultYear = max($years);
        }

        $year  = (int)Str::normalizeDigits($_GET['year'] ?? (string)$defaultYear);
        $month = (int)Str::normalizeDigits($_GET['month'] ?? (string)$defaultMonth);
        if (!in_array($year, $years, true)) { $year = $defaultYear; }
        if ($month < 1) { $month = 1; }
        if ($month > 12) { $month = 12; }

        try {
            $service  = new PayrollService();
            $rows     = $service->listPayrollsByMonth($year, $month);
        } catch (PDOException $e) {
            View::renderError('خطا در بارگذاری لیست حقوق: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        $prevYear = $year;
        $prevMonth = $month - 1;
        if ($prevMonth === 0) {
            $prevMonth = 12;
            $prevYear--;
        }
        $nextYear = $year;
        $nextMonth = $month + 1;
        if ($nextMonth === 13) {
            $nextMonth = 1;
            $nextYear++;
        }

        View::render('payroll/index', [
            'user'      => Auth::user(),
            'payrolls'  => $rows,
            'year'      => $year,
            'month'     => $month,
            'prevYear'  => $prevYear,
            'prevMonth' => $prevMonth,
            'nextYear'  => $nextYear,
            'nextMonth' => $nextMonth,
        ]);
    }

    public function createForm(): void
    {
        $this->ensureAuth();
        try {
            $pdo = Database::connection();
            $employees = $pdo->query("SELECT id, full_name FROM employees WHERE active = 1 ORDER BY full_name")->fetchAll();
        } catch (PDOException $e) {
            View::renderError('خطا در بارگذاری لیست پرسنل برای حقوق: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        [$defaultYear, $defaultMonth] = Date::currentJalali();
        $years = Date::financialYears();
        if (!in_array($defaultYear, $years, true)) {
            $defaultYear = max($years);
        }

        $year  = (int)Str::normalizeDigits($_GET['year'] ?? (string)$defaultYear);
        $month = (int)Str::normalizeDigits($_GET['month'] ?? (string)$defaultMonth);
        if (!in_array($year, $years, true)) { $year = $defaultYear; }
        $basis = $_GET['basis'] ?? 'sales_total';
        $employeeId = (int)Str::normalizeDigits($_GET['employee_id'] ?? '0');

        $selectedEmployee = null;
        $contracts = [];
        $payments  = [];
        $salesAmount = 0;
        $commission = 0;
        $percent = 0;

        if ($employeeId) {
            try {
                $service = new PayrollService();
                $calc = $service->computeCommissionForMonth($employeeId, $year, $month, $basis);
                $selectedEmployee = $calc['employee'];
                $contracts       = $calc['contracts'];
                $payments        = $calc['payments'];
                $salesAmount     = $calc['salesAmount'];
                $commission      = $calc['commissionAmount'];
                $percent         = $calc['commissionPercent'];
            } catch (PDOException $e) {
                View::renderError('خطا در محاسبه پورسانت: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
                return;
            }
        }

        View::render('payroll/create', [
            'user'              => Auth::user(),
            'employees'         => $employees,
            'selectedEmployee'  => $selectedEmployee,
            'year'              => $year,
            'month'             => $month,
            'basis'             => $basis,
            'contracts'         => $contracts,
            'payments'          => $payments,
            'salesAmount'       => $salesAmount,
            'commission'        => $commission,
            'percent'           => $percent,
            'error'             => null,
        ]);
    }

    public function create(): void
    {
        $this->ensureAuth();

        $employeeId = (int)Str::normalizeDigits($_POST['employee_id'] ?? '0');
        $year       = (int)Str::normalizeDigits($_POST['year'] ?? '');
        $month      = (int)Str::normalizeDigits($_POST['month'] ?? '');
        $basis      = $_POST['basis'] ?? 'sales_total';

        $bonus          = (int)Str::normalizeDigits($_POST['bonus_amount'] ?? '0');
        $advance        = (int)Str::normalizeDigits($_POST['advance_amount'] ?? '0');
        $otherDeduction = (int)Str::normalizeDigits($_POST['other_deductions'] ?? '0');
        $note           = trim($_POST['note'] ?? '');
        $contractIdsRaw = $_POST['contract_ids'] ?? [];
        $contractIds    = [];
        foreach ($contractIdsRaw as $cid) {
            $contractIds[] = (int)Str::normalizeDigits((string)$cid);
        }

        if (!$employeeId) {
            header('Location: /payroll');
            return;
        }

        try {
            $service = new PayrollService();
            $service->createPayrollByEmployeeId(
                $employeeId,
                $year,
                $month,
                $basis,
                $contractIds,
                $bonus,
                $advance,
                $otherDeduction,
                $note
            );
        } catch (PDOException $e) {
            View::renderError('خطا در ثبت حقوق: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        header('Location: /payroll?year=' . $year . '&month=' . $month);
    }

    public function delete(): void
    {
        $this->ensureAuth();
        $id = (int)Str::normalizeDigits($_GET['id'] ?? '0');
        if (!$id) {
            header('Location: /payroll');
            return;
        }

        try {
            $service = new PayrollService();
            $service->deletePayroll($id);
        } catch (PDOException $e) {
            View::renderError('خطا در حذف رکورد حقوق: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
            return;
        }

        header('Location: /payroll');
    }
}
