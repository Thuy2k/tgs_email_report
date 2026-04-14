<?php
/**
 * Component: Tổng hợp thu tiền
 * Biến: $bank
 */
if (!defined('ABSPATH')) exit;
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };
$shell_style = 'margin-bottom:20px; background:#ffffff; border:1px solid #e3ebf3; border-radius:26px; padding:18px; box-shadow:0 14px 32px rgba(20, 46, 79, 0.07);';

$bank_shops = $bank['shops'] ?? (is_array($bank) ? $bank : []);
$payment_methods = $bank['payment_methods'] ?? [];
$totals = $bank['totals'] ?? [];

$total_collected = (float) ($totals['actual_collected'] ?? 0);
$total_receipts = (int) ($totals['receipt_count'] ?? 0);
$total_sales = (int) ($totals['sale_count'] ?? 0);
$active_shop_count = count($bank_shops);

$payment_total_amount = 0;
foreach ($payment_methods as $payment_method_row) {
    $payment_total_amount += (float) ($payment_method_row['total'] ?? 0);
}

$payment_share_base = $payment_total_amount > 0 ? $payment_total_amount : 1;
$shop_collected_base = $total_collected > 0 ? $total_collected : 1;
?>
<div class="section" style="<?php echo $shell_style; ?>">
    <div style="font-size:24px; font-weight:700; color:#13273e; line-height:1.2; margin-bottom:16px;">Thu tiền</div>

    <?php if (empty($bank_shops) && empty($payment_methods)): ?>
        <div class="alert alert-info" style="padding:12px 16px; border-radius:6px; background:#d1ecf1; color:#0c5460; border-left:4px solid #17a2b8;">
            Không có dữ liệu thu tiền trong khoảng thời gian này.
        </div>
    <?php else: ?>

        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:16px;">
            <tr>
                <td width="50%" style="padding:0 6px 0 0; vertical-align:top;">
                    <div style="background:#fbfcfe; border:1px solid #e6edf4; border-radius:18px; padding:14px 15px; min-height:88px;">
                        <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Số đơn hàng</div>
                        <div style="font-size:20px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo $fmt($total_sales); ?></div>
                        <div style="font-size:11px; color:#7a8d9f; margin-top:4px;"><?php echo $active_shop_count; ?> shop có đơn</div>
                    </div>
                </td>
                <td width="50%" style="padding:0 0 0 6px; vertical-align:top;">
                    <div style="background:#f3fbf6; border:1px solid #dcefe3; border-radius:18px; padding:14px 15px; min-height:88px;">
                        <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Đã thu</div>
                        <div style="font-size:20px; font-weight:700; color:#1f8f4d; margin-top:4px;"><?php echo $fmt($total_collected); ?>đ</div>
                        <div style="font-size:11px; color:#7a8d9f; margin-top:4px;"><?php echo $total_receipts; ?> phiếu thu</div>
                    </div>
                </td>
            </tr>
        </table>

        <?php if (!empty($payment_methods)): ?>
        <div style="margin-bottom:16px; border:1px solid #e6edf4; border-radius:22px; padding:14px 15px; background:#fcfdff;">
            <div style="font-size:13px; font-weight:700; color:#13273e; margin-bottom:10px;">Theo phương thức</div>
            <?php foreach ($payment_methods as $index => $payment_method_row):
                $share = $payment_share_base > 0 ? round(((float) ($payment_method_row['total'] ?? 0) / $payment_share_base) * 100, 1) : 0;
                $bar_width = $share > 0 ? max(10, min(100, $share)) : 0;
            ?>
            <div style="padding:<?php echo $index === 0 ? '0' : '12px'; ?> 0 0; border-top:<?php echo $index === 0 ? '0' : '1px'; ?> solid #edf2f7; margin-top:<?php echo $index === 0 ? '0' : '12px'; ?>;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="vertical-align:middle; padding-right:10px;">
                            <div style="font-size:14px; font-weight:700; color:#13273e;"><?php echo esc_html($payment_method_row['label']); ?></div>
                            <div style="font-size:11px; color:#7a8d9f; margin-top:3px;"><?php echo (int) ($payment_method_row['count'] ?? 0); ?> giao dịch</div>
                        </td>
                        <td align="right" style="vertical-align:middle; white-space:nowrap;">
                            <div style="font-size:12px; color:#718397;"><?php echo number_format($share, 1, ',', '.'); ?>%</div>
                            <div style="font-size:16px; font-weight:700; color:#13273e; margin-top:3px;"><?php echo $fmt($payment_method_row['total'] ?? 0); ?>đ</div>
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

        <div style="border-top:1px solid #edf2f7; padding-top:14px;">
            <div style="font-size:13px; font-weight:700; color:#13273e; margin-bottom:10px;">Theo shop</div>
            <?php foreach ($bank_shops as $blog_id => $b):
                $shop_share = $shop_collected_base > 0 ? round(((float) ($b['actual_collected'] ?? 0) / $shop_collected_base) * 100, 1) : 0;
                $shop_bar_width = $shop_share > 0 ? max(8, min(100, $shop_share)) : 0;
            ?>
            <div style="margin-top:12px; border:1px solid #e6edf4; border-radius:22px; padding:14px 15px; background:#fcfdff;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="vertical-align:top; padding-right:8px;">
                            <div style="font-size:16px; font-weight:700; color:#13273e;"><?php echo esc_html($b['shop_name']); ?></div>
                            <div style="font-size:11px; color:#7a8d9f; margin-top:4px;"><?php echo (int) ($b['sale_count'] ?? 0); ?> đơn | <?php echo (int) ($b['receipt_count'] ?? 0); ?> phiếu thu</div>
                        </td>
                        <td align="right" style="vertical-align:top; white-space:nowrap;">
                            <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Đã thu</div>
                            <div style="font-size:21px; font-weight:700; color:#1f8f4d; margin-top:4px;"><?php echo $fmt($b['actual_collected']); ?>đ</div>
                        </td>
                    </tr>
                </table>

                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:10px; table-layout:fixed; border-collapse:separate;">
                    <tr>
                        <td width="<?php echo $shop_bar_width; ?>%" style="height:8px; background:#1f8f4d; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                        <td width="<?php echo max(0, 100 - $shop_bar_width); ?>%" style="height:8px; background:#e8eef5; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                    </tr>
                </table>

                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:10px;">
                    <tr>
                        <td width="50%" style="padding-right:6px; vertical-align:top;">
                            <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.8px;">Tỷ trọng đã thu</div>
                            <div style="font-size:14px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo number_format($shop_share, 1, ',', '.'); ?>%</div>
                        </td>
                        <td width="50%" style="padding-left:6px; vertical-align:top;">
                            <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.8px;">Doanh số sau CK</div>
                            <div style="font-size:14px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo $fmt($b['expected_revenue']); ?>đ</div>
                        </td>
                    </tr>
                </table>

                <?php if (!empty($b['payment_breakdown'])): ?>
                <div style="margin-top:10px;">
                    <?php foreach ($b['payment_breakdown'] as $pm): ?>
                        <span style="display:inline-block; margin:0 8px 8px 0; padding:6px 10px; border-radius:999px; background:#f1f6fb; color:#35506c; font-size:11px; font-weight:700;">
                            <?php echo esc_html($pm['label']); ?> · <?php echo $fmt($pm['total']); ?>đ · <?php echo (int) ($pm['count'] ?? 0); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
