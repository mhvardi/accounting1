<?php
/** @var array $unsyncedDomains */
?>
<div class="topbar-title" style="margin-bottom:8px;">
    <span class="emoji">🌐</span>
    <span>دامنه‌های سینک‌نشده</span>
    <div style="font-size:11px;color:#6b7280;">لیست دامنه‌های بدون مالک برای آشتی و اتصال به مشتری</div>
</div>

<div class="card-soft">
    <div class="card-header">
        <div class="card-title">لیست دامنه‌ها</div>
        <div class="card-actions" style="font-size:12px;color:#6b7280;">
            <?php echo count($unsyncedDomains); ?> مورد بدون مشتری
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>دامنه</th>
                <th>وضعیت</th>
                <th>آخرین پیام</th>
                <th>ثبت</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($unsyncedDomains)): ?>
                <tr><td colspan="5">دامنه سینک‌نشده‌ای یافت نشد.</td></tr>
            <?php else: ?>
                <?php foreach ($unsyncedDomains as $dom): ?>
                    <tr>
                        <td><?php echo (int)$dom['id']; ?></td>
                        <td>
                            <div><?php echo htmlspecialchars($dom['domain_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="micro-copy">رجیسترار: <?php echo htmlspecialchars($dom['registrar'] ?? 'نامشخص', ENT_QUOTES, 'UTF-8'); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($dom['status'] ?? 'نامشخص', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="micro-copy" style="white-space:normal;max-width:320px;">
                            <?php echo htmlspecialchars($dom['meta_json'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </td>
                        <td><?php echo htmlspecialchars($dom['created_at'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
