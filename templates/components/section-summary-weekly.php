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
$this_week_gift_qty   = (float) ($wc['this_week_gift_qty'] ?? 0);
$prev_week_gift_qty   = (float) ($wc['prev_week_gift_qty'] ?? 0);
$this_week_gift_value = (float) ($wc['this_week_gift_value'] ?? 0);
$prev_week_gift_value = (float) ($wc['prev_week_gift_value'] ?? 0);
$gift_qty_base = max($this_week_gift_qty, $prev_week_gift_qty, 1);
$gift_value_base = max($this_week_gift_value, $prev_week_gift_value, 1);
$this_week_gift_qty_width   = min(100, max(10, round(($this_week_gift_qty / $gift_qty_base) * 100, 1)));
$prev_week_gift_qty_width   = min(100, max(10, round(($prev_week_gift_qty / $gift_qty_base) * 100, 1)));
$this_week_gift_value_width = min(100, max(10, round(($this_week_gift_value / $gift_value_base) * 100, 1)));
$prev_week_gift_value_width = min(100, max(10, round(($prev_week_gift_value / $gift_value_base) * 100, 1)));
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
            <div style="font-size:13px; font-weight:700; color:#13273e; margin-bottom:10px;">Chỉ số so sánh theo shop</div>
            <?php $wc_shops = $wc['by_shop'] ?? []; ?>
            <?php if (empty($wc_shops)): ?>
                <div style="font-size:12px; color:#718397;">Chưa có dữ liệu theo shop.</div>
            <?php else: ?>
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse:collapse;">
                <tr style="background:#f0f4f8;">
                    <td style="padding:6px 8px; font-size:10px; font-weight:700; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.5px;">Shop</td>
                    <td align="right" style="padding:6px 8px; font-size:10px; font-weight:700; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.5px;">Doanh thu</td>
                    <td align="right" style="padding:6px 8px; font-size:10px; font-weight:700; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.5px;">Số đơn</td>
                    <td align="right" style="padding:6px 8px; font-size:10px; font-weight:700; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.5px;">SL quà</td>
                    <td align="right" style="padding:6px 8px; font-size:10px; font-weight:700; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.5px;">Giá trị quà</td>
                </tr>
                <?php $i = 0; foreach ($wc_shops as $bid => $s): ?>
                <?php $row_bg = $i % 2 === 0 ? '#ffffff' : '#f9fbfd'; $i++; ?>
                <tr style="background:<?php echo $row_bg; ?>; border-top:1px solid #eef2f6;">
                    <td style="padding:8px 8px; vertical-align:top;">
                        <div style="font-size:12px; font-weight:600; color:#13273e;"><?php echo esc_html($s['shop_name']); ?></div>
                    </td>
                    <td align="right" style="padding:8px 8px; vertical-align:top; white-space:nowrap;">
                        <div style="font-size:13px; font-weight:700; color:#13273e;"><?php echo $fmt($s['this_week_net']); ?>đ</div>
                        <div style="font-size:10px; color:#5a6e82; font-weight:600; margin-top:2px;">Tuần trước: <?php echo $fmt($s['prev_week_net']); ?>đ</div>
                    </td>
                    <td align="right" style="padding:8px 8px; font-size:12px; color:#13273e; font-weight:600; vertical-align:top; white-space:nowrap;">
                        <?php echo $fmt($s['this_week_orders']); ?>
                        <div style="font-size:10px; color:#5a6e82; font-weight:600; margin-top:2px;">Tuần trước: <?php echo $fmt($s['prev_week_orders']); ?></div>
                    </td>
                    <td align="right" style="padding:8px 8px; font-size:12px; color:#7c3aed; font-weight:600; vertical-align:top; white-space:nowrap;">
                        <?php echo $fmt($s['this_week_gift_qty']); ?> sp
                        <div style="font-size:10px; color:#5a6e82; font-weight:600; margin-top:2px;">Tuần trước: <?php echo $fmt($s['prev_week_gift_qty']); ?></div>
                    </td>
                    <td align="right" style="padding:8px 8px; font-size:12px; color:#7c3aed; font-weight:600; vertical-align:top; white-space:nowrap;">
                        <?php echo $fmt($s['this_week_gift_value']); ?>đ
                        <div style="font-size:10px; color:#5a6e82; font-weight:600; margin-top:2px;">Tuần trước: <?php echo $fmt($s['prev_week_gift_value']); ?>đ</div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
