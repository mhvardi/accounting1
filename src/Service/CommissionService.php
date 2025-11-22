<?php
namespace App\Service;

class CommissionService
{
    public function computeRow(array $employeeRow, int $totalSales): array
    {
        if ($totalSales <= 0) {
            return [0, 0, 0.0];
        }

        $mode = $employeeRow['commission_mode'] ?? 'none';
        $pct  = 0.0;
        $commission = 0;

        if ($mode === 'flat') {
            $pct = max(0.0, (float)($employeeRow['commission_percent'] ?? 0));
            $commission = (int)round($totalSales * ($pct / 100.0));
        } elseif ($mode === 'tiered') {
            $configJson = $employeeRow['commission_config_json'] ?? null;
            $config = $configJson ? json_decode($configJson, true) : null;
            $tiers  = (is_array($config) && !empty($config['tiers'])) ? $config['tiers'] : [];

            if (empty($tiers)) {
                $tiers = [
                    ['min'=>0, 'max'=>50000000,  'percent'=>2],
                    ['min'=>50000001, 'max'=>100000000, 'percent'=>3],
                    ['min'=>100000001,'max'=>150000000, 'percent'=>4],
                    ['min'=>150000001,'max'=>null,       'percent'=>5],
                ];
            }
            foreach ($tiers as $t) {
                $min = (float)($t['min'] ?? 0);
                $max = isset($t['max']) && $t['max'] !== '' ? (float)$t['max'] : INF;
                $p   = (float)($t['percent'] ?? 0);
                if ($totalSales >= $min && $totalSales <= $max) {
                    $pct = $p;
                    break;
                }
            }
            $commission = (int)round($totalSales * ($pct / 100.0));
        }

        return [$totalSales, $commission, $pct];
    }
}
