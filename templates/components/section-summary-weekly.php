<?php
/**
 * Component: So sánh tuần
 * Biến: $summary
 */
if (!defined('ABSPATH')) exit;
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };
$wc = $summary['weekly_compare'] ?? [];
$change = $wc['change_pct'] ?? 0;
$arrow = $change > 0 ? '↑' : ($change < 0 ? '↓' : '→');
$color = $change > 0 ? '#28a745' : ($change < 0 ? '#dc3545' : '#6c757d');
$shell_style = 'margin-bottom:20px; background:#ffffff; border:1px solid #e3ebf3; border-radius:26px; padding:18px; box-shadow:0 14px 32px rgba(20, 46, 79, 0.07);';

$this_week_net = (float) ($wc['this_week_net'] ?? 0);
$prev_week_net = (float) ($wc['prev_week_net'] ?? 0);
$this_week_orders = (float) ($wc['this_week_orders'] ?? 0);
$prev_week_orders = (float) ($wc['prev_week_orders'] ?? 0);
$revenue_bar_base = max($this_week_net, $prev_week_net, 1);
$order_bar_base = max($this_week_orders, $prev_week_orders, 1);
$this_week_net_width = min(100, max(10, round(($this_week_net / $revenue_bar_base) * 100, 1)));
$prev_week_net_width = min(100, max(10, round(($prev_week_net / $revenue_bar_base) * 100, 1)));
$this_week_order_width = min(100, max(10, round(($this_week_orders / $order_bar_base) * 100, 1)));
$prev_week_order_width = min(100, max(10, round(($prev_week_orders / $order_bar_base) * 100, 1)));
?>
<div class="section" style="<?php echo $shell_style; ?>">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:12px;">
        <tr>
            <td style="vertical-align:top; padding-right:10px;">
                <div style="font-size:24px; font-weight:700; color:#13273e; line-height:1.2;">So sánh tuần</div>
            </td>
            <td align="right" style="vertical-align:top; white-space:nowrap;">
                <span style="display:inline-block; padding:8px 12px; border-radius:999px; background:<?php echo $change > 0 ? '#e9f9ef' : ($change < 0 ? '#fff1f0' : '#eff4f8'); ?>; color:<?php echo $color; ?>; font-size:12px; font-weight:700;">
                    <?php echo $arrow; ?> <?php echo number_format(abs($change), 1, ',', '.'); ?>%
                </span>
            </td>
        </tr>
    </table>

    <?php if (empty($wc) || ($wc['this_week_net'] == 0 && $wc['prev_week_net'] == 0)): ?>
        <div class="alert alert-info" style="padding:12px 16px; border-radius:6px; background:#d1ecf1; color:#0c5460; border-left:4px solid #17a2b8;">
            Chưa đủ dữ liệu để so sánh tuần.
        </div>
    <?php else: ?>
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:14px;">
            <tr>
                <td width="50%" style="padding:0 6px 12px 0; vertical-align:top;">
                    <div style="background:#f9fbfd; border:1px solid #e5edf5; border-radius:20px; padding:14px 15px; min-height:96px;">
                        <div style="font-size:11px; color:#6f8094; text-transform:uppercase; letter-spacing:0.8px;">Tuần này</div>
                        <div style="font-size:21px; font-weight:700; color:#13273e; margin-top:5px;"><?php echo $fmt($wc['this_week_net']); ?>đ</div>
                        <div style="font-size:12px; color:#718397; margin-top:5px;">Từ <?php echo date('d/m', strtotime($wc['week_start'])); ?> | <?php echo $fmt($wc['this_week_orders']); ?> đơn | Lãi <?php echo $fmt($wc['this_week_profit']); ?>đ</div>
                    </div>
                </td>
                <td width="50%" style="padding:0 0 12px 6px; vertical-align:top;">
                    <div style="background:#f9fbfd; border:1px solid #e5edf5; border-radius:20px; padding:14px 15px; min-height:96px;">
                        <div style="font-size:11px; color:#6f8094; text-transform:uppercase; letter-spacing:0.8px;">Tuần trước</div>
                        <div style="font-size:21px; font-weight:700; color:#60748a; margin-top:5px;"><?php echo $fmt($wc['prev_week_net']); ?>đ</div>
                        <div style="font-size:12px; color:#718397; margin-top:5px;"><?php echo date('d/m', strtotime($wc['prev_week_start'])); ?> - <?php echo date('d/m', strtotime($wc['prev_week_end'])); ?> | <?php echo $fmt($wc['prev_week_orders']); ?> đơn | Lãi <?php echo $fmt($wc['prev_week_profit']); ?>đ</div>
                    </div>
                </td>
            </tr>
        </table>

        <div style="background:#fcfdff; border:1px solid #e5edf5; border-radius:20px; padding:16px;">
            <div style="font-size:13px; font-weight:700; color:#13273e; margin-bottom:10px;">Chỉ số so sánh</div>

            <div style="margin-bottom:12px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:5px;">
                    <tr>
                        <td style="font-size:13px; font-weight:700; color:#13273e;">Doanh thu tuần</td>
                        <td align="right" style="font-size:12px; color:#718397;">Tuần này <?php echo $fmt($wc['this_week_net']); ?>đ</td>
                    </tr>
                </table>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:6px; table-layout:fixed; border-collapse:separate;">
                    <tr>
                        <td width="<?php echo $this_week_net_width; ?>%" style="height:8px; background:#1f8f4d; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                        <td width="<?php echo max(0, 100 - $this_week_net_width); ?>%" style="height:8px; background:#e7edf5; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                    </tr>
                </table>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="font-size:12px; color:#718397;">Tuần trước <?php echo $fmt($wc['prev_week_net']); ?>đ</td>
                        <td align="right" style="font-size:12px; color:#718397;"><?php echo number_format(abs($change), 1, ',', '.'); ?>%</td>
                    </tr>
                </table>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:6px; table-layout:fixed; border-collapse:separate;">
                    <tr>
                        <td width="<?php echo $prev_week_net_width; ?>%" style="height:8px; background:#c7d3df; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                        <td width="<?php echo max(0, 100 - $prev_week_net_width); ?>%" style="height:8px; background:#eef2f6; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                    </tr>
                </table>
            </div>

            <div>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:5px;">
                    <tr>
                        <td style="font-size:13px; font-weight:700; color:#13273e;">Số đơn</td>
                        <td align="right" style="font-size:12px; color:#718397;">Tuần này <?php echo $fmt($wc['this_week_orders']); ?> đơn</td>
                    </tr>
                </table>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:6px; table-layout:fixed; border-collapse:separate;">
                    <tr>
                        <td width="<?php echo $this_week_order_width; ?>%" style="height:8px; background:#2d5f8a; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                        <td width="<?php echo max(0, 100 - $this_week_order_width); ?>%" style="height:8px; background:#e7edf5; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                    </tr>
                </table>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="font-size:12px; color:#718397;">Tuần trước <?php echo $fmt($wc['prev_week_orders']); ?> đơn</td>
                        <td align="right" style="font-size:12px; color:#718397;">Lãi tuần này <?php echo $fmt($wc['this_week_profit']); ?>đ</td>
                    </tr>
                </table>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:6px; table-layout:fixed; border-collapse:separate;">
                    <tr>
                        <td width="<?php echo $prev_week_order_width; ?>%" style="height:8px; background:#c7d3df; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                        <td width="<?php echo max(0, 100 - $prev_week_order_width); ?>%" style="height:8px; background:#eef2f6; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                    </tr>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
