<?php
/**
 * Component: Tổng quan hệ thống ngày (Summary Daily)
 * Biến: $summary
 */
if (!defined('ABSPATH')) exit;
$daily = $summary['daily_total'] ?? [];
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };

$card_shell = 'margin-bottom:20px; background:#ffffff; border:1px solid #e3ebf3; border-radius:26px; padding:18px; box-shadow:0 14px 32px rgba(20, 46, 79, 0.07);';
$total_net = (float) ($daily['total_net'] ?? 0);
$avg_order = ($daily['total_orders'] ?? 0) > 0
    ? round(($daily['total_net'] ?? 0) / $daily['total_orders'])
    : 0;
$top_share_base = $total_net > 0 ? $total_net : 1;
?>
<div class="section" style="<?php echo $card_shell; ?>">
    <div style="font-size:24px; font-weight:700; color:#13273e; margin-bottom:16px; line-height:1.2;">Tổng quan doanh thu</div>

    <div style="background:linear-gradient(180deg, #f7fbff 0%, #edf5ff 100%); border:1px solid #dbe8f7; border-radius:22px; padding:16px 16px 14px; margin-bottom:16px;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td style="vertical-align:top; padding-right:8px;">
                    <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#6480a0; margin-bottom:6px;">Đã thu bán hàng</div>
                    <div style="font-size:30px; font-weight:700; color:#0f2740; line-height:1.1;"><?php echo $fmt($daily['total_net'] ?? 0); ?>đ</div>
                </td>
                <td align="right" style="vertical-align:top;">
                    <span style="display:inline-block; padding:7px 11px; border-radius:999px; background:#ffffff; border:1px solid #dbe8f7; color:#284968; font-size:11px; font-weight:700;">
                        <?php echo $fmt($daily['active_shops'] ?? 0); ?> shop hoạt động
                    </span>
                </td>
            </tr>
        </table>

        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:14px;">
            <tr>
                <td width="33.33%" style="padding:0 8px 0 0; vertical-align:top;">
                    <div style="font-size:11px; color:#6d8198; text-transform:uppercase; letter-spacing:0.8px;">Tiền trước CK</div>
                    <div style="font-size:16px; font-weight:700; color:#17324f; margin-top:4px;"><?php echo $fmt($daily['total_gross'] ?? 0); ?>đ</div>
                </td>
                <td width="33.33%" style="padding:0 4px; vertical-align:top;">
                    <div style="font-size:11px; color:#6d8198; text-transform:uppercase; letter-spacing:0.8px;">Chiết khấu</div>
                    <div style="font-size:16px; font-weight:700; color:#cf3d32; margin-top:4px;"><?php echo $fmt($daily['total_discount'] ?? 0); ?>đ</div>
                </td>
                <td width="33.33%" style="padding:0 0 0 8px; vertical-align:top;">
                    <div style="font-size:11px; color:#6d8198; text-transform:uppercase; letter-spacing:0.8px;">Trả hàng</div>
                    <div style="font-size:16px; font-weight:700; color:#cf3d32; margin-top:4px;"><?php echo $fmt($daily['total_return'] ?? 0); ?>đ</div>
                </td>
            </tr>
        </table>
    </div>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:16px;">
        <tr>
            <td width="50%" style="padding:0 6px 10px 0; vertical-align:top;">
                <div style="background:#fbfcfe; border:1px solid #e6edf4; border-radius:18px; padding:14px 15px; min-height:82px;">
                    <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Tổng đơn hàng</div>
                    <div style="font-size:22px; font-weight:700; color:#13273e; margin-top:5px;"><?php echo $fmt($daily['total_orders'] ?? 0); ?></div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 10px 6px; vertical-align:top;">
                <div style="background:#fbfcfe; border:1px solid #e6edf4; border-radius:18px; padding:14px 15px; min-height:82px;">
                    <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">TB mỗi đơn</div>
                    <div style="font-size:22px; font-weight:700; color:#13273e; margin-top:5px;"><?php echo $fmt($avg_order); ?>đ</div>
                </div>
            </td>
        </tr>
        <tr>
            <td width="50%" style="padding:0 6px 0 0; vertical-align:top;">
                <div style="background:#fbfcfe; border:1px solid #e6edf4; border-radius:18px; padding:14px 15px; min-height:82px;">
                    <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Khách hàng</div>
                    <div style="font-size:22px; font-weight:700; color:#13273e; margin-top:5px;"><?php echo $fmt($daily['total_customers'] ?? 0); ?></div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 0 6px; vertical-align:top;">
                <div style="background:#fbfcfe; border:1px solid #e6edf4; border-radius:18px; padding:14px 15px; min-height:82px;">
                    <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Shop hoạt động</div>
                    <div style="font-size:22px; font-weight:700; color:#13273e; margin-top:5px;"><?php echo $fmt($daily['active_shops'] ?? 0); ?></div>
                </div>
            </td>
        </tr>
    </table>

    <?php if (!empty($summary['top_shops'])): ?>
    <div style="border-top:1px solid #edf2f7; padding-top:14px;">
        <div style="font-size:13px; font-weight:700; color:#13273e; margin-bottom:10px;">Top shop theo thực thu</div>
        <?php foreach ($summary['top_shops'] as $i => $t):
            $share = $top_share_base > 0 ? round(((float) ($t['net'] ?? 0) / $top_share_base) * 100, 1) : 0;
            $bar_width = $share > 0 ? max(8, min(100, $share)) : 0;
        ?>
        <div style="padding:12px 0; border-top:<?php echo $i === 0 ? '0' : '1px'; ?> solid #edf2f7;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="vertical-align:middle; padding-right:10px;">
                        <span style="display:inline-block; min-width:24px; padding:4px 0; border-radius:999px; background:#f0f4f8; color:#3b556f; font-size:11px; font-weight:700; text-align:center;"><?php echo $i + 1; ?></span>
                    </td>
                    <td style="vertical-align:middle;">
                        <div style="font-size:14px; font-weight:700; color:#13273e;"><?php echo esc_html($t['shop_name']); ?></div>
                        <div style="font-size:11px; color:#748597; margin-top:3px;"><?php echo (int) ($t['order_count'] ?? 0); ?> đơn</div>
                    </td>
                    <td align="right" style="vertical-align:middle; white-space:nowrap;">
                        <div style="font-size:12px; color:#75879a;"><?php echo number_format($share, 1, ',', '.'); ?>%</div>
                        <div style="font-size:16px; font-weight:700; color:#13273e; margin-top:3px;"><?php echo $fmt($t['net'] ?? 0); ?>đ</div>
                    </td>
                </tr>
            </table>
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:10px; table-layout:fixed; border-collapse:separate;">
                <tr>
                    <td width="<?php echo $bar_width; ?>%" style="height:8px; background:#2d5f8a; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                    <td width="<?php echo max(0, 100 - $bar_width); ?>%" style="height:8px; background:#e8eef5; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                </tr>
            </table>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
