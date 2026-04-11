<?php
/**
 * Component: Doanh thu bán hàng từng shop
 * Biến: $sales
 */
if (!defined('ABSPATH')) exit;
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };

// Tổng
$total_orders = 0; $total_gross = 0; $total_net = 0; $total_discount = 0; $total_return = 0;
foreach ($sales as $s) {
    $total_orders += $s['order_count'];
    $total_gross += $s['gross_revenue'];
    $total_net += $s['net_revenue'];
    $total_discount += $s['discount_value'];
    $total_return += $s['return_value'];
}
?>
<div class="section">
    <div class="section-title" style="font-size:16px; font-weight:700; color:#1e3a5f; margin:0 0 12px 0; padding-bottom:8px; border-bottom:2px solid #2d5f8a;">
        🛒 Doanh Thu Bán Hàng Từng Shop
    </div>

    <?php if (empty($sales)): ?>
        <div class="alert alert-info" style="padding:12px 16px; border-radius:6px; background:#d1ecf1; color:#0c5460; border-left:4px solid #17a2b8;">
            Không có dữ liệu bán hàng trong khoảng thời gian này.
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="data-table" style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:left; border-bottom:2px solid #dee2e6; white-space:nowrap;">Shop</th>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:right; border-bottom:2px solid #dee2e6; white-space:nowrap;">Đơn</th>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:right; border-bottom:2px solid #dee2e6; white-space:nowrap;">Tổng Bán</th>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:right; border-bottom:2px solid #dee2e6; white-space:nowrap;">Chiết Khấu</th>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:right; border-bottom:2px solid #dee2e6; white-space:nowrap;">Trả Hàng</th>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:right; border-bottom:2px solid #dee2e6; white-space:nowrap;">Thực Thu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $blog_id => $s): ?>
                    <tr>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; font-weight:600;"><?php echo esc_html($s['shop_name']); ?></td>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo $s['order_count']; ?></td>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo $fmt($s['gross_revenue']); ?></td>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right; color:#dc3545;"><?php echo $s['discount_value'] > 0 ? '-' . $fmt($s['discount_value']) : '0'; ?></td>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right; color:#dc3545;"><?php echo $s['return_value'] > 0 ? '-' . $fmt($s['return_value']) : '0'; ?></td>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right; font-weight:700; color:#28a745;"><?php echo $fmt($s['net_revenue']); ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- TỔNG -->
                    <tr>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6;">TỔNG (<?php echo count($sales); ?> shop)</td>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6; text-align:right;"><?php echo $fmt($total_orders); ?></td>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6; text-align:right;"><?php echo $fmt($total_gross); ?></td>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6; text-align:right; color:#dc3545;"><?php echo $total_discount > 0 ? '-' . $fmt($total_discount) : '0'; ?></td>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6; text-align:right; color:#dc3545;"><?php echo $total_return > 0 ? '-' . $fmt($total_return) : '0'; ?></td>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6; text-align:right; color:#28a745; font-size:14px;"><?php echo $fmt($total_net); ?>đ</td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
