<?php
/**
 * Component: Cảnh báo hạn sử dụng
 * Biến: $minmax
 */
if (!defined('ABSPATH')) exit;
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };
$near = $minmax['near_expiry'] ?? [];
$shell = 'margin-bottom:20px; background:#ffffff; border:1px solid #e3ebf3; border-radius:26px; padding:18px; box-shadow:0 14px 32px rgba(20, 46, 79, 0.07);';
$th_style = 'padding:8px 10px; font-size:11px; color:#6480a0; text-transform:uppercase; letter-spacing:0.5px; border-bottom:2px solid #e6edf4;';
$td_style = 'padding:8px 10px; border-bottom:1px solid #f0f4f8; font-size:12px;';
?>
<?php if (!empty($near)): ?>
<div class="section" style="<?php echo $shell; ?>">
    <div style="font-size:24px; font-weight:700; color:#13273e; line-height:1.2; margin-bottom:4px;">Sắp hết hạn sử dụng</div>
    <div style="font-size:12px; color:#77889a; margin-bottom:16px;"><?php echo count($near); ?> sản phẩm cần lưu ý</div>

    <div style="border:1px solid #f0e5c9; border-radius:22px; padding:14px 15px; background:#fffdf8;">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <th style="<?php echo $th_style; ?> text-align:left;">Shop</th>
                <th style="<?php echo $th_style; ?> text-align:left;">Sản phẩm</th>
                <th style="<?php echo $th_style; ?> text-align:center;">HSD</th>
                <th style="<?php echo $th_style; ?> text-align:right;">SL cận hạn</th>
                <th style="<?php echo $th_style; ?> text-align:right;">Tồn tổng</th>
            </tr>
            <?php foreach (array_slice($near, 0, 20) as $n): ?>
            <tr>
                <td style="<?php echo $td_style; ?>"><?php echo esc_html($n['shop_name']); ?></td>
                <td style="<?php echo $td_style; ?>">
                    <div style="font-weight:600; color:#13273e;"><?php echo esc_html($n['product_name'] ?? $n['sku']); ?></div>
                    <div style="font-size:10px; color:#77889a;"><?php echo esc_html($n['sku']); ?></div>
                </td>
                <td style="<?php echo $td_style; ?> text-align:center;">
                    <span style="display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:600; background:#fffcf5; color:#b8860b;">
                        <?php echo $n['exp_date'] ? date('d/m/Y', strtotime($n['exp_date'])) : '—'; ?>
                    </span>
                </td>
                <td style="<?php echo $td_style; ?> text-align:right;">
                    <span style="font-weight:700; color:#cf3d32;"><?php echo $fmt($n['near_expiry_qty']); ?></span>
                </td>
                <td style="<?php echo $td_style; ?> text-align:right; color:#13273e;"><?php echo $fmt($n['closing_qty']); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($near) > 20): ?>
            <tr><td colspan="5" style="padding:8px 10px; color:#77889a; font-size:11px;">...và <?php echo count($near) - 20; ?> mục khác</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php endif; ?>
