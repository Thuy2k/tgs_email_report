<?php
/**
 * Component: Cảnh báo tự động
 * Biến: $summary
 */
if (!defined('ABSPATH')) exit;
$alerts = $summary['alerts'] ?? [];
?>
<?php if (!empty($alerts)): ?>
<div class="section">
    <div class="section-title" style="font-size:16px; font-weight:700; color:#1e3a5f; margin:0 0 12px 0; padding-bottom:8px; border-bottom:2px solid #dc3545;">
        🚨 Cảnh Báo
    </div>

    <?php foreach ($alerts as $a):
        $bg = $a['level'] === 'danger' ? '#f8d7da' : ($a['level'] === 'warning' ? '#fff3cd' : '#d1ecf1');
        $cl = $a['level'] === 'danger' ? '#721c24' : ($a['level'] === 'warning' ? '#856404' : '#0c5460');
        $bd = $a['level'] === 'danger' ? '#dc3545' : ($a['level'] === 'warning' ? '#ffc107' : '#17a2b8');
    ?>
    <div style="padding:10px 14px; border-radius:6px; margin-bottom:8px; font-size:13px; background:<?php echo $bg; ?>; color:<?php echo $cl; ?>; border-left:4px solid <?php echo $bd; ?>;">
        <?php echo esc_html($a['message']); ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
