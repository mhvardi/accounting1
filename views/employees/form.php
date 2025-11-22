<?php
$employee = $employee ?? null;
$isEdit   = !empty($employee);
$action   = $isEdit ? '/employees/edit' : '/employees/create';
use App\Core\Date;
?>
<form method="post" action="<?php echo $action; ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?php echo (int)$employee['id']; ?>">
    <?php endif; ?>
    <div class="grid" style="grid-template-columns: repeat(2,minmax(0,1fr));gap:10px;">
        <div class="form-field">
            <label class="form-label">نام و نام خانوادگی</label>
            <input type="text" name="full_name" class="form-input"
                   value="<?php echo $isEdit ? htmlspecialchars($employee['full_name'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
        </div>
        <div class="form-field">
            <label class="form-label">حقوق ثابت ماهانه (تومان)</label>
            <input type="number" name="base_salary" class="form-input" min="0" step="10000"
                   value="<?php echo $isEdit ? (int)$employee['base_salary'] : 0; ?>">
        </div>
        <div class="form-field">
            <label class="form-label">نوع همکاری</label>
            <select name="compensation_type" class="form-select" id="compensation_type">
                <?php
                $ct = $isEdit ? $employee['compensation_type'] : 'fixed';
                ?>
                <option value="fixed" <?php echo $ct==='fixed'?'selected':''; ?>>حقوق ثابت</option>
                <option value="commission" <?php echo $ct==='commission'?'selected':''; ?>>پورسانتی</option>
                <option value="mixed" <?php echo $ct==='mixed'?'selected':''; ?>>ترکیبی (حقوق + پورسانت)</option>
            </select>
        </div>
        <div class="form-field commission-only">
            <label class="form-label">مدل پورسانت</label>
            <?php $cm = $isEdit ? $employee['commission_mode'] : 'none'; ?>
            <select name="commission_mode" class="form-select" id="commission_mode">
                <option value="none" <?php echo $cm==='none'?'selected':''; ?>>بدون پورسانت</option>
                <option value="flat" <?php echo $cm==='flat'?'selected':''; ?>>درصدی</option>
                <option value="tiered" <?php echo $cm==='tiered'?'selected':''; ?>>پلکانی</option>
            </select>
        </div>
        <div class="form-field commission-only">
            <label class="form-label">مبنای پورسانت</label>
            <?php $cs = $isEdit ? $employee['commission_scope'] : 'self'; ?>
            <select name="commission_scope" class="form-select">
                <option value="self" <?php echo $cs==='self'?'selected':''; ?>>حجم فروش خودش</option>
                <option value="company" <?php echo $cs==='company'?'selected':''; ?>>حجم فروش کل شرکت</option>
                <option value="category" <?php echo $cs==='category'?'selected':''; ?>>فروش دسته‌بندی خاص</option>
            </select>
        </div>
        <div class="form-field commission-flat">
            <label class="form-label">درصد ثابت (در مدل درصدی)</label>
            <input type="number" name="commission_percent" class="form-input" min="0" max="100"
                   value="<?php echo $isEdit ? (int)$employee['commission_percent'] : 0; ?>">
        </div>
        <div class="form-field">
            <label class="form-label">تاریخ شروع همکاری (شمسی YYYY/MM/DD)</label>
            <input type="text" name="effective_from" class="form-input"
                   placeholder="مثلاً 1403/08/01"
                   value="<?php echo $isEdit && $employee['effective_from'] ? Date::jDate($employee['effective_from']) : ''; ?>">
        </div>
        <div class="form-field" style="display:flex;align-items:center;gap:6px;margin-top:20px;">
            <?php $active = $isEdit ? (int)$employee['active'] : 1; ?>
            <input type="checkbox" name="active" id="emp_active" <?php echo $active===1?'checked':''; ?>>
            <label for="emp_active" class="form-label" style="margin:0;">فعال باشد</label>
        </div>
    </div>

    <div id="tierSection" style="margin-top:14px;display:none;">
        <div class="form-label" style="margin-bottom:4px;">پلکان‌های پورسانت (حداقل فروش و درصد مربوطه)</div>
        <table class="table">
            <thead>
            <tr>
                <th>حداقل فروش (تومان)</th>
                <th>حداکثر فروش (تومان) - خالی = بی‌نهایت</th>
                <th>درصد پورسانت</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $tiers = [];
            if ($isEdit && !empty($employee['commission_config_json'])) {
                $decoded = json_decode($employee['commission_config_json'], true);
                if (is_array($decoded) && !empty($decoded['tiers'])) {
                    $tiers = $decoded['tiers'];
                }
            }
            if (empty($tiers)) {
                $tiers = [
                    ['min'=>0,'max'=>50000000,'percent'=>2],
                    ['min'=>50000001,'max'=>100000000,'percent'=>3],
                    ['min'=>100000001,'max'=>150000000,'percent'=>4],
                    ['min'=>150000001,'max'=>null,'percent'=>5],
                ];
            }
            foreach ($tiers as $t):
            ?>
                <tr>
                    <td><input type="number" name="tier_min[]" class="form-input" value="<?php echo (int)$t['min']; ?>"></td>
                    <td><input type="number" name="tier_max[]" class="form-input" value="<?php echo isset($t['max'])?$t['max']:''; ?>"></td>
                    <td><input type="number" name="tier_percent[]" class="form-input" value="<?php echo (float)$t['percent']; ?>" step="0.1"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="display:flex;gap:8px;margin-top:12px;">
        <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'ذخیره تغییرات' : 'ذخیره پرسنل'; ?></button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var compSel  = document.getElementById('compensation_type');
    var modeSel  = document.getElementById('commission_mode');
    var tierSec  = document.getElementById('tierSection');
    var commOnly = document.querySelectorAll('.commission-only');
    var commFlat = document.querySelectorAll('.commission-flat');

    function refreshVisibility() {
        var compVal = compSel.value;
        var modeVal = modeSel.value;

        commOnly.forEach(function(el){ el.style.display = (compVal === 'fixed') ? 'none' : 'block'; });
        commFlat.forEach(function(el){ el.style.display = (compVal === 'fixed' || modeVal !== 'flat') ? 'none' : 'block'; });

        if (compVal === 'fixed' || modeVal !== 'tiered') {
            tierSec.style.display = 'none';
        } else {
            tierSec.style.display = 'block';
        }
    }

    compSel.addEventListener('change', refreshVisibility);
    modeSel.addEventListener('change', refreshVisibility);
    refreshVisibility();
});
</script>
