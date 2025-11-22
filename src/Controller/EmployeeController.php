<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\View;
use App\Core\Database;
use App\Core\Date;
use App\Core\Str;
use PDO;
use PDOException;

class EmployeeController
{
    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->query("SELECT * FROM employees ORDER BY id DESC");
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('employees/index', [
            'user'      => Auth::user(),
            'employees' => $employees,
        ]);
    }

    // برای Router
    public function createForm(): void
    {
        $this->create();
    }

    public function editForm(): void
    {
        $this->edit();
    }

    public function create(): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            return;
        }

        $pdo        = Database::connection();
        $categories = $this->loadServiceCategories($pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $fullName        = trim($_POST['full_name'] ?? '');
                $baseSalaryInput = $_POST['base_salary'] ?? '0';
                $cooperationType = $_POST['cooperation_type'] ?? 'fixed'; // fixed | commission | mixed

                $commissionModel      = $_POST['commission_model'] ?? 'none'; // none | percent | tiered
                $commissionBasis      = $_POST['commission_basis'] ?? 'contract_received'; // contract_received | contract_total | company_total | categories
                $commissionPercentInp = $_POST['commission_percent'] ?? '0';

                // پلکان‌ها
                $stepsMin  = $_POST['tier_min'] ?? [];
                $stepsMax  = $_POST['tier_max'] ?? [];
                $stepsPerc = $_POST['tier_percent'] ?? [];

                $tiers = [];
                for ($i = 0; $i < count($stepsMin); $i++) {
                    $min = Str::normalizeDigits($stepsMin[$i]);
                    $max = Str::normalizeDigits($stepsMax[$i]);
                    $pc  = Str::normalizeDigits($stepsPerc[$i]);
                    if ($min === '' && $max === '' && $pc === '') {
                        continue;
                    }
                    $tiers[] = [
                        'min'     => (int)str_replace(',', '', $min),
                        'max'     => (int)str_replace(',', '', $max),
                        'percent' => (float)$pc,
                    ];
                }

                // دسته‌های مشمول پورسانت
                $commissionCats = $_POST['commission_categories'] ?? [];
                $commissionCats = array_map('intval', $commissionCats);

                // نرمال‌سازی عددی
                $baseSalary        = (int)str_replace(',', '', Str::normalizeDigits($baseSalaryInput));
                $commissionPercent = (float)Str::normalizeDigits($commissionPercentInp);

                // تاریخ شروع همکاری (effective_from)
                $startDateInput = $_POST['start_date'] ?? null;
                $effectiveFrom  = Date::fromJalaliInput($startDateInput); // خروجی: YYYY-MM-DD میلادی که jdf به شمسی تبدیل می‌کند در نمایش

                // مپ کردن به ستون‌های واقعی جدول
                $compensationType = $cooperationType; // fixed | commission | mixed

                // scope: self / company
                // scope: self / company / category
                if ($commissionBasis === 'company_total') {
                    $commissionScope = 'company';
                } elseif ($commissionBasis === 'categories') {
                    $commissionScope = 'category';
                } else {
                    $commissionScope = 'self';
                }

                // mode: none | flat | tiered (مطابق enum جدول)
                switch ($commissionModel) {
                    case 'percent':
                        $commissionMode = 'flat';
                        break;
                    case 'tiered':
                        $commissionMode = 'tiered';
                        break;
                    case 'none':
                    default:
                        $commissionMode = 'none';
                        break;
                }

                // JSON تنظیمات پورسانت
                $config = [
                    'tiers' => $tiers,
                ];
                if ($commissionBasis === 'categories') {
                    $config['categories'] = $commissionCats;
                    $config['category_company_wide'] = isset($_POST['category_company_wide']) ? 1 : 0;
                }
                $configJson = json_encode($config, JSON_UNESCAPED_UNICODE);

                // ✅ ستون‌ها به‌صورت دقیق نام‌گذاری شده‌اند، ترتیب با جدول یکی است
                $sql = "INSERT INTO employees 
                        (full_name, active, base_salary, compensation_type, commission_mode, commission_scope, commission_percent, commission_config_json, effective_from)
                        VALUES
                        (:full_name, 1, :base_salary, :compensation_type, :commission_mode, :commission_scope, :commission_percent, :config_json, :effective_from)";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':full_name'          => $fullName,
                    ':base_salary'        => $baseSalary,
                    ':compensation_type'  => $compensationType,
                    ':commission_mode'    => $commissionMode,
                    ':commission_scope'   => $commissionScope,
                    ':commission_percent' => $commissionPercent,
                    ':config_json'        => $configJson,
                    ':effective_from'     => $effectiveFrom,
                ]);

                header('Location: /employees');
                return;

            } catch (PDOException $e) {
                View::renderError('خطا در ثبت پرسنل: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
                return;
            }
        }

        View::render('employees/create', [
            'user'       => Auth::user(),
            'categories' => $categories,
        ]);
    }

    public function edit(): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: /employees');
            return;
        }

        $pdo        = Database::connection();
        $categories = $this->loadServiceCategories($pdo);

        $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            header('Location: /employees');
            return;
        }

        // دیکد JSON پورسانت
        $configArr = [];
        if (!empty($employee['commission_config_json'])) {
            $tmp = json_decode($employee['commission_config_json'], true);
            if (is_array($tmp)) {
                $configArr = $tmp;
            }
        }
        $tiers          = $configArr['tiers']      ?? [];
        $commissionCats = $configArr['categories'] ?? [];
        $categoryCompanyWide = !empty($configArr['category_company_wide']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $fullName        = trim($_POST['full_name'] ?? '');
                $baseSalaryInput = $_POST['base_salary'] ?? '0';
                $cooperationType = $_POST['cooperation_type'] ?? 'fixed';

                $commissionModel      = $_POST['commission_model'] ?? 'none';
                $commissionBasis      = $_POST['commission_basis'] ?? 'contract_received';
                $commissionPercentInp = $_POST['commission_percent'] ?? '0';

                $stepsMin  = $_POST['tier_min'] ?? [];
                $stepsMax  = $_POST['tier_max'] ?? [];
                $stepsPerc = $_POST['tier_percent'] ?? [];

                $tiers = [];
                for ($i = 0; $i < count($stepsMin); $i++) {
                    $min = Str::normalizeDigits($stepsMin[$i]);
                    $max = Str::normalizeDigits($stepsMax[$i]);
                    $pc  = Str::normalizeDigits($stepsPerc[$i]);
                    if ($min === '' && $max === '' && $pc === '') {
                        continue;
                    }
                    $tiers[] = [
                        'min'     => (int)str_replace(',', '', $min),
                        'max'     => (int)str_replace(',', '', $max),
                        'percent' => (float)$pc,
                    ];
                }

                $commissionCats = $_POST['commission_categories'] ?? [];
                $commissionCats = array_map('intval', $commissionCats);
                $categoryCompanyWide = isset($_POST['category_company_wide']) ? 1 : 0;

                $baseSalary        = (int)str_replace(',', '', Str::normalizeDigits($baseSalaryInput));
                $commissionPercent = (float)Str::normalizeDigits($commissionPercentInp);

                $startDateInput = $_POST['start_date'] ?? null;
                $effectiveFrom  = Date::fromJalaliInput($startDateInput);

                $compensationType = $cooperationType;

                if ($commissionBasis === 'company_total') {
                    $commissionScope = 'company';
                } elseif ($commissionBasis === 'categories') {
                    $commissionScope = 'category';
                } else {
                    $commissionScope = 'self';
                }

                switch ($commissionModel) {
                    case 'percent':
                        $commissionMode = 'flat';
                        break;
                    case 'tiered':
                        $commissionMode = 'tiered';
                        break;
                    case 'none':
                    default:
                        $commissionMode = 'none';
                        break;
                }

                $config = [
                    'tiers' => $tiers,
                ];
                if ($commissionBasis === 'categories') {
                    $config['categories'] = $commissionCats;
                    $config['category_company_wide'] = $categoryCompanyWide;
                }
                $configJson = json_encode($config, JSON_UNESCAPED_UNICODE);

                $sql = "UPDATE employees SET
                            full_name             = :full_name,
                            base_salary           = :base_salary,
                            compensation_type     = :compensation_type,
                            commission_mode       = :commission_mode,
                            commission_scope      = :commission_scope,
                            commission_percent    = :commission_percent,
                            commission_config_json= :config_json,
                            effective_from        = :effective_from
                        WHERE id = :id";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':full_name'          => $fullName,
                    ':base_salary'        => $baseSalary,
                    ':compensation_type'  => $compensationType,
                    ':commission_mode'    => $commissionMode,
                    ':commission_scope'   => $commissionScope,
                    ':commission_percent' => $commissionPercent,
                    ':config_json'        => $configJson,
                    ':effective_from'     => $effectiveFrom,
                    ':id'                 => $id,
                ]);

                header('Location: /employees');
                return;

            } catch (PDOException $e) {
                View::renderError('خطا در ویرایش پرسنل: ' . $e->getMessage(), $e->getTraceAsString(), Auth::user());
                return;
            }
        }

        View::render('employees/edit', [
            'user'            => Auth::user(),
            'employee'        => $employee,
            'categories'      => $categories,
            'commissionSteps' => $tiers,
            'commissionCats'  => $commissionCats,
            'categoryCompanyWide' => $categoryCompanyWide,
        ]);
    }

    public function delete(): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            return;
        }
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: /employees');
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = :id");
        $stmt->execute([':id' => $id]);

        header('Location: /employees');
    }

    /**
     * لیست دسته‌های خدمات پورسانتی از product_categories
     */
    private function loadServiceCategories(PDO $pdo): array
    {
        try {
            $sql = "SELECT id, name 
                    FROM product_categories 
                    WHERE is_commissionable = 1
                    ORDER BY is_primary DESC, name ASC";
            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                return $rows;
            }
        } catch (\Throwable $e) {}

        return [];
    }
}