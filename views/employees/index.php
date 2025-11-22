<?php
/** @var array $employees */
/** @var array $user */
use App\Core\Date;
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">👥</span>
    <span>لیست پرسنل</span>
</div>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">پرسنل ثبت‌شده</div>
        <div class="card-actions">
            <a href="/employees/create" class="btn btn-xs">+ افزودن پرسنل جدید</a>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>نام</th>
                <th>وضعیت</th>
                <th>حقوق ثابت (تومان)</th>
                <th>نوع همکاری</th>
                <th>مدل پورسانت</th>
                <th>حجم فروش مبنا</th>
                <th>درصد پایه</th>
                <th>از تاریخ</th>
                <th>اقدامات</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($employees)): ?>
                <tr>
                    <td colspan="10">هنوز هیچ پرسنلی ثبت نشده است.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($employees as $i => $e): ?>
                    <?php
                    $rowId   = (int)$e['id'];
                    $active  = (int)($e['active'] ?? 1);

                    $statusLabel = $active ? 'فعال' : 'غیرفعال';

                    // نوع همکاری از compensation_type
                    $compType = $e['compensation_type'] ?? 'fixed';
                    if ($compType === 'mixed') {
                        $compLabel = 'ترکیبی (حقوق + پورسانت)';
                    } elseif ($compType === 'commission') {
                        $compLabel = 'پورسانتی';
                    } else {
                        $compLabel = 'حقوق ثابت';
                    }

                    // مدل پورسانت از commission_mode
                    $mode  = $e['commission_mode']  ?? 'none';   // none | flat | tiered
                    $scope = $e['commission_scope'] ?? 'self';   // self | company | category

                    $modeLabel = 'بدون پورسانت';
                    if ($mode === 'tiered') {
                        $modeLabel = 'پلکانی';
                    } elseif ($mode === 'flat') {
                        $modeLabel = 'درصد ثابت';
                    }

                    $config = json_decode($e['commission_config_json'] ?? '', true) ?: [];

                    // 🔑 مبنای نمایش "حجم فروش مبنا"
                    if ($scope === 'category') {
                        // ✅ وقتی پورسانت روی دسته‌های خاص است
                        $basisLabel = 'دسته‌های خاص خدمات';
                        if (!empty($config['category_company_wide'])) {
                            $basisLabel .= ' (شرکتی)';
                        }
                    } elseif ($scope === 'company') {
                        $basisLabel = 'حجم کل فروش شرکت';
                    } else {
                        $basisLabel = 'حجم فروش خودش';
                    }

                    $baseSalary = (int)($e['base_salary'] ?? 0);
                    $percent    = (float)($e['commission_percent'] ?? 0);

                    $percentLabel = '';
                    $tiers  = $config['tiers'] ?? [];
                    if ($mode === 'none' || ($e['compensation_type'] ?? 'fixed') === 'fixed') {
                        $percentLabel = 'بدون پورسانت';
                    } elseif ($mode === 'flat') {
                        $percentLabel = ($percent ?: 0) . '٪';
                    } else {
                        if (!empty($tiers)) {
                            $minPc = null;
                            $maxPc = null;
                            foreach ($tiers as $t) {
                                $pc = isset($t['percent']) ? (float)$t['percent'] : null;
                                if ($pc === null) { continue; }
                                $minPc = $minPc === null ? $pc : min($minPc, $pc);
                                $maxPc = $maxPc === null ? $pc : max($maxPc, $pc);
                            }
                            if ($minPc !== null && $maxPc !== null) {
                                $percentLabel = ($minPc == $maxPc)
                                    ? ($maxPc . '٪')
                                    : ('از ' . $minPc . '٪ تا ' . $maxPc . '٪');
                            }
                        }
                        if ($percentLabel === '') {
                            $percentLabel = 'پلکانی';
                        }
                    }

                    $effective  = $e['effective_from'] ?? null;
                    $effectiveJ = $effective ? Date::jDate($effective) : '-';
                    ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo htmlspecialchars($e['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $statusLabel; ?></td>
                        <td><?php echo number_format($baseSalary); ?></td>
                        <td><?php echo $compLabel; ?></td>
                        <td><?php echo $modeLabel; ?></td>
                        <td><?php echo $basisLabel; ?></td>
                        <td><?php echo htmlspecialchars($percentLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($effectiveJ, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <a href="/employees/edit?id=<?php echo $rowId; ?>" class="btn btn-xxs">ویرایش</a>
                            <a href="/employees/delete?id=<?php echo $rowId; ?>"
                               class="btn btn-xxs btn-danger"
                               onclick="return confirm('آیا از حذف این پرسنل مطمئن هستید؟');">
                                حذف
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>