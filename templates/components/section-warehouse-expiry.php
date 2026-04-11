<?php
/**
 * Component: Cảnh báo hạn sử dụng
 * Biến: $minmax
 */
if (!defined('ABSPATH')) exit;
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };
$near = $minmax['near_expiry'] ?? [];
?>
<?php if (!empty($near)): ?>
<div class="section">
    <div class="section-title" style="font-size:16px; font-weight:700; color:#1e3a5f; margin:0 0 12px 0; padding-bottom:8px; border-bottom:2px solid #ffc107;">
        ⏰ Sắp Hết Hạn Sử Dụng (<?php echo count($near); ?> mục)
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table" style="width:100%; border-collapse:collapse; font-size:12px;">
            <tr>
                <th style="background:#fff3cd; padding:8px; text-align:left; border-bottom:1px solid #dee2e6;">Shop</th>
                <th style="background:#fff3cd; padding:8px; text-align:left; border-bottom:1px solid #dee2e6;">SKU</th>
                <th style="background:#fff3cd; padding:8px; text-align:center; border-bottom:1px solid #dee2e6;">HSD</th>
                <th style="background:#fff3cd; padding:8px; text-align:right; border-bottom:1px solid #dee2e6;">SL Sắp Hết Hạn</th>
                <th style="background:#fff3cd; padding:8px; text-align:right; border-bottom:1px solid #dee2e6;">Tồn Tổng</th>
            </tr>
            <?php foreach (array_slice($near, 0, 20) as $n): ?>
            <tr>
                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;"><?php echo esc_html($n['shop_name']); ?></td>
                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; font-weight:600;"><?php echo esc_html($n['sku']); ?></td>
                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:center; color:#856404;">
                    <?php echo $n['exp_date'] ? date('d/m/Y', strtotime($n['exp_date'])) : '—'; ?>
                </td>
                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right; color:#dc3545; font-weight:700;"><?php echo $fmt($n['near_expiry_qty']); ?></td>
                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo $fmt($n['closing_qty']); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($near) > 20): ?>
            <tr><td colspan="5" style="padding:6px 8px; color:#888; font-size:11px;">...và <?php echo count($near) - 20; ?> mục khác</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php endif; ?>
