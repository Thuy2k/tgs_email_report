<?php
/**
 * Component: Tổng hợp thu tiền
 * Biến: $bank
 */
if (!defined('ABSPATH')) exit;
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };

$bank_shops = $bank['shops'] ?? (is_array($bank) ? $bank : []);
$payment_methods = $bank['payment_methods'] ?? [];
$totals = $bank['totals'] ?? [];

$total_expected = (float) ($totals['expected_revenue'] ?? 0);
$total_collected = (float) ($totals['actual_collected'] ?? 0);
$total_receipts = (int) ($totals['receipt_count'] ?? 0);
$total_sales = (int) ($totals['sale_count'] ?? 0);
$payment_methods_count = count($payment_methods);
$active_shop_count = count($bank_shops);

$payment_total_amount = 0;
$payment_total_count = 0;
foreach ($payment_methods as $payment_method_row) {
    $payment_total_amount += (float) ($payment_method_row['total'] ?? 0);
    $payment_total_count += (int) ($payment_method_row['count'] ?? 0);
}
?>
<div class="section">
    <div class="section-title" style="font-size:16px; font-weight:700; color:#1e3a5f; margin:0 0 12px 0; padding-bottom:8px; border-bottom:2px solid #2d5f8a;">
        Tổng Hợp Thu Tiền & Phương Thức Thanh Toán
    </div>

    <?php if (empty($bank_shops) && empty($payment_methods)): ?>
        <div class="alert alert-info" style="padding:12px 16px; border-radius:6px; background:#d1ecf1; color:#0c5460; border-left:4px solid #17a2b8;">
            Không có dữ liệu thu tiền trong khoảng thời gian này.
        </div>
    <?php else: ?>

        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:16px;">
            <tr>
                <td width="33%" style="padding:0 6px 0 0; vertical-align:top;">
                    <div style="background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #2d5f8a;">
                        <div style="font-size:11px; color:#6c757d; text-transform:uppercase;">Số Đơn Hàng</div>
                        <div style="font-size:18px; font-weight:700; color:#1e3a5f;"><?php echo $fmt($total_sales); ?></div>
                        <div style="font-size:11px; color:#888;"><?php echo $active_shop_count; ?> shop có đơn</div>
                    </div>
                </td>
                <td width="33%" style="padding:0 3px; vertical-align:top;">
                    <div style="background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #28a745;">
                        <div style="font-size:11px; color:#6c757d; text-transform:uppercase;">Đã Thu</div>
                        <div style="font-size:18px; font-weight:700; color:#28a745;"><?php echo $fmt($total_collected); ?>đ</div>
                        <div style="font-size:11px; color:#888;"><?php echo $total_receipts; ?> phiếu thu</div>
                    </div>
                </td>
                <td width="33%" style="padding:0 0 0 6px; vertical-align:top;">
                    <div style="background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #2d5f8a;">
                        <div style="font-size:11px; color:#6c757d; text-transform:uppercase;">Phương Thức Có Phát Sinh</div>
                        <div style="font-size:18px; font-weight:700; color:#1e3a5f;"><?php echo $payment_methods_count; ?></div>
                        <div style="font-size:11px; color:#888;"><?php echo $payment_total_count; ?> giao dịch</div>
                    </div>
                </td>
            </tr>
        </table>

        <?php if (!empty($payment_methods)): ?>
        <div style="margin-bottom:16px; overflow-x:auto;">
            <div style="font-size:13px; font-weight:700; color:#1e3a5f; margin:0 0 8px 0;">Tổng tiền theo phương thức thanh toán</div>
            <table class="data-table" style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:left; border-bottom:2px solid #dee2e6;">Phương thức</th>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:right; border-bottom:2px solid #dee2e6;">Số giao dịch</th>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:right; border-bottom:2px solid #dee2e6;">Tổng tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payment_methods as $payment_method_row): ?>
                    <tr>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; font-weight:600;"><?php echo esc_html($payment_method_row['label']); ?></td>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo (int) ($payment_method_row['count'] ?? 0); ?></td>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right; color:#28a745; font-weight:700;"><?php echo $fmt($payment_method_row['total'] ?? 0); ?>đ</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6;">TỔNG</td>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6; text-align:right;"><?php echo $payment_total_count; ?></td>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6; text-align:right; color:#28a745;"><?php echo $fmt($payment_total_amount); ?>đ</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div style="overflow-x:auto;">
            <table class="data-table" style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:left; border-bottom:2px solid #dee2e6;">Shop</th>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:right; border-bottom:2px solid #dee2e6;">Hóa đơn</th>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:right; border-bottom:2px solid #dee2e6;">Tổng Bán</th>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:right; border-bottom:2px solid #dee2e6;">Đã Thu</th>
                        <th class="hide-mobile" style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:right; border-bottom:2px solid #dee2e6;">Phiếu Thu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bank_shops as $blog_id => $b): ?>
                    <tr>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; font-weight:600;"><?php echo esc_html($b['shop_name']); ?></td>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo (int) $b['sale_count']; ?></td>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo $fmt($b['expected_revenue']); ?></td>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right; font-weight:600;"><?php echo $fmt($b['actual_collected']); ?></td>
                        <td class="hide-mobile" style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo $b['receipt_count']; ?></td>
                    </tr>

                    <?php if (!empty($b['payment_breakdown'])): ?>
                    <tr>
                        <td colspan="5" style="padding:4px 8px 8px 24px; border-bottom:1px solid #f0f0f0; font-size:12px; color:#666;">
                            <?php foreach ($b['payment_breakdown'] as $pm): ?>
                                <span style="display:inline-block; margin-right:12px;">
                                    <?php echo esc_html($pm['label']); ?>: <strong><?php echo $fmt($pm['total']); ?>đ</strong>
                                    (<?php echo $pm['count']; ?>)
                                </span>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php endforeach; ?>

                    <tr>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6;">TỔNG</td>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6; text-align:right;"><?php echo $total_sales; ?></td>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6; text-align:right;"><?php echo $fmt($total_expected); ?></td>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6; text-align:right;"><?php echo $fmt($total_collected); ?></td>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6; text-align:right;"><?php echo $total_receipts; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
