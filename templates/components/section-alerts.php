<?php
/**
 * Component: Cảnh báo tự động
 * Biến: $summary
 */
if (!defined('ABSPATH')) exit;
$alerts = $summary['alerts'] ?? [];
?>
<?php if (!empty($alerts)): ?>
<div class="section" style="margin-bottom:20px; background:#ffffff; border:1px solid #e3ebf3; border-radius:26px; padding:18px; box-shadow:0 14px 32px rgba(20, 46, 79, 0.07);">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:12px;">
        <tr>
            <td style="vertical-align:top; padding-right:8px;">
                <div style="font-size:24px; font-weight:700; color:#13273e; line-height:1.2;">Cảnh báo</div>
            </td>
            <td align="right" style="vertical-align:top; white-space:nowrap;">
                <span style="display:inline-block; padding:7px 11px; border-radius:999px; background:#fff1f0; color:#cf3d32; font-size:11px; font-weight:700;">
                    <?php echo count($alerts); ?> cảnh báo
                </span>
            </td>
        </tr>
    </table>

    <?php foreach ($alerts as $a):
        $bg = $a['level'] === 'danger' ? '#fff1f0' : ($a['level'] === 'warning' ? '#fff7dd' : '#eef6ff');
        $cl = $a['level'] === 'danger' ? '#a53329' : ($a['level'] === 'warning' ? '#8a6a11' : '#28537d');
        $bd = $a['level'] === 'danger' ? '#dc3545' : ($a['level'] === 'warning' ? '#ffc107' : '#5ba2ff');
        $badge = $a['level'] === 'danger' ? 'Mức cao' : ($a['level'] === 'warning' ? 'Theo dõi' : 'Thông tin');
    ?>
    <div style="padding:14px 15px; border-radius:18px; margin-bottom:10px; font-size:13px; background:<?php echo $bg; ?>; color:<?php echo $cl; ?>; border:1px solid rgba(0,0,0,0.04);">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td style="vertical-align:top; padding-right:10px;">
                    <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:<?php echo $bd; ?>; margin-top:5px;"></span>
                </td>
                <td style="vertical-align:top;">
                    <div style="font-size:11px; text-transform:uppercase; letter-spacing:0.9px; color:<?php echo $cl; ?>; margin-bottom:5px;"><?php echo esc_html($badge); ?></div>
                    <div style="font-size:14px; line-height:1.5; font-weight:700; color:<?php echo $cl; ?>;"><?php echo esc_html($a['message']); ?></div>
                </td>
            </tr>
        </table>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
