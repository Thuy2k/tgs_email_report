<?php
/**
 * Component: Doanh thu bán hàng từng shop
 * Biến: $sales
 */
if (!defined('ABSPATH')) exit;
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };
$shell_style = 'margin-bottom:20px; background:#ffffff; border:1px solid #e3ebf3; border-radius:26px; padding:18px; box-shadow:0 14px 32px rgba(20, 46, 79, 0.07);';

// Tổng
$total_orders = 0; $total_gross = 0; $total_net = 0; $total_discount = 0; $total_return = 0;
foreach ($sales as $s) {
    $total_orders += $s['order_count'];
    $total_gross += $s['gross_revenue'];
    $total_net += $s['net_revenue'];
    $total_discount += $s['discount_value'];
    $total_return += $s['return_value'];
}

$net_share_base = $total_net > 0 ? $total_net : 1;
?>
<div class="section" style="<?php echo $shell_style; ?>">
    <div style="font-size:24px; font-weight:700; color:#13273e; line-height:1.2; margin-bottom:16px;">Doanh thu từng shop</div>

    <?php if (empty($sales)): ?>
        <div class="alert alert-info" style="padding:12px 16px; border-radius:6px; background:#d1ecf1; color:#0c5460; border-left:4px solid #17a2b8;">
            Không có dữ liệu bán hàng trong khoảng thời gian này.
        </div>
    <?php else: ?>
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:16px;">
            <tr>
                <td width="50%" style="padding:0 6px 10px 0; vertical-align:top;">
                    <div style="background:#fbfcfe; border:1px solid #e5edf5; border-radius:18px; padding:14px 15px;">
                        <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Tổng đơn</div>
                        <div style="font-size:21px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo $fmt($total_orders); ?></div>
                    </div>
                </td>
                <td width="50%" style="padding:0 0 10px 6px; vertical-align:top;">
                    <div style="background:#fbfcfe; border:1px solid #e5edf5; border-radius:18px; padding:14px 15px;">
                        <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Tiền trước CK</div>
                        <div style="font-size:21px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo $fmt($total_gross); ?>đ</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="50%" style="padding:0 6px 0 0; vertical-align:top;">
                    <div style="background:#fff8f7; border:1px solid #f4dfdc; border-radius:18px; padding:14px 15px;">
                        <div style="font-size:11px; color:#9b6d68; text-transform:uppercase; letter-spacing:0.8px;">Chiết khấu</div>
                        <div style="font-size:21px; font-weight:700; color:#cf3d32; margin-top:4px;"><?php echo $fmt($total_discount); ?>đ</div>
                    </div>
                </td>
                <td width="50%" style="padding:0 0 0 6px; vertical-align:top;">
                    <div style="background:#f3fbf6; border:1px solid #dcefe3; border-radius:18px; padding:14px 15px;">
                        <div style="font-size:11px; color:#6d8198; text-transform:uppercase; letter-spacing:0.8px;">Thực thu</div>
                        <div style="font-size:21px; font-weight:700; color:#1f8f4d; margin-top:4px;"><?php echo $fmt($total_net); ?>đ</div>
                    </div>
                </td>
            </tr>
        </table>

        <?php foreach ($sales as $blog_id => $s):
            $share = $net_share_base > 0 ? round(((float) ($s['net_revenue'] ?? 0) / $net_share_base) * 100, 1) : 0;
            $bar_width = $share > 0 ? max(8, min(100, $share)) : 0;
            $hcl_breakdown = $s['hcl_breakdown'] ?? [];
            $strategic_groups = $hcl_breakdown['strategic_groups'] ?? [];
            $other_revenue = (float) ($hcl_breakdown['other_revenue'] ?? 0);
        ?>
        <div style="margin-top:12px; border:1px solid #e6edf4; border-radius:22px; padding:14px 15px; background:#fcfdff;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="vertical-align:top; padding-right:8px;">
                        <div style="font-size:16px; font-weight:700; color:#13273e;"><?php echo esc_html($s['shop_name']); ?></div>
                        <div style="font-size:11px; color:#77889a; margin-top:4px;"><?php echo $s['shop_code'] ? esc_html($s['shop_code']) . ' | ' : ''; ?><?php echo (int) $s['order_count']; ?> đơn</div>
                    </td>
                    <td align="right" style="vertical-align:top; white-space:nowrap;">
                        <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Thực thu</div>
                        <div style="font-size:21px; font-weight:700; color:#1f8f4d; margin-top:4px;"><?php echo $fmt($s['net_revenue']); ?>đ</div>
                    </td>
                </tr>
            </table>

            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:10px; table-layout:fixed; border-collapse:separate;">
                <tr>
                    <td width="<?php echo $bar_width; ?>%" style="height:8px; background:#2d5f8a; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                    <td width="<?php echo max(0, 100 - $bar_width); ?>%" style="height:8px; background:#e8eef5; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                </tr>
            </table>

            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:10px;">
                <tr>
                    <td width="25%" style="padding-right:8px; vertical-align:top;">
                        <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.8px;">Tỷ trọng</div>
                        <div style="font-size:14px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo number_format($share, 1, ',', '.'); ?>%</div>
                    </td>
                    <td width="25%" style="padding:0 8px; vertical-align:top;">
                        <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.8px;">Tiền trước CK</div>
                        <div style="font-size:14px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo $fmt($s['gross_revenue']); ?>đ</div>
                    </td>
                    <td width="25%" style="padding:0 8px; vertical-align:top;">
                        <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.8px;">Chiết khấu</div>
                        <div style="font-size:14px; font-weight:700; color:#cf3d32; margin-top:4px;"><?php echo $s['discount_value'] > 0 ? '-' . $fmt($s['discount_value']) : '0'; ?></div>
                    </td>
                    <td width="25%" style="padding-left:8px; vertical-align:top;">
                        <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.8px;">Trả hàng</div>
                        <div style="font-size:14px; font-weight:700; color:#cf3d32; margin-top:4px;"><?php echo $s['return_value'] > 0 ? '-' . $fmt($s['return_value']) : '0'; ?></div>
                    </td>
                </tr>
            </table>

            <div style="margin-top:12px; border-top:1px dashed #dbe6f1; padding-top:10px;">
                <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.8px; margin-bottom:8px;">Chi tiết doanh số theo nhóm hàng chiến lược</div>
                <?php if (!empty($strategic_groups)): ?>
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse:collapse;">
                        <?php foreach ($strategic_groups as $group): ?>
                            <tr>
                                <td style="padding:4px 0; font-size:12px; color:#13273e;"><?php echo esc_html($group['label'] ?? 'HCL'); ?></td>
                                <td align="right" style="padding:4px 0; font-size:12px; font-weight:700; color:#1f8f4d;"><?php echo $fmt($group['revenue'] ?? 0); ?>đ</td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td style="padding:6px 0 0 0; font-size:12px; color:#5f7288; border-top:1px solid #edf2f7;">Nhóm hàng khác</td>
                            <td align="right" style="padding:6px 0 0 0; font-size:12px; font-weight:700; color:#2d5f8a; border-top:1px solid #edf2f7;"><?php echo $fmt($other_revenue); ?>đ</td>
                        </tr>
                    </table>
                <?php else: ?>
                    <div style="font-size:12px; color:#7a8d9f;">Không có phát sinh doanh số theo nhóm HCL trong kỳ này.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
