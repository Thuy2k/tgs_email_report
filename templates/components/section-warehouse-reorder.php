<?php
/**
 * Component: Đề xuất mua hàng
 * Biến: $minmax
 */
if (!defined('ABSPATH')) exit;
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };
$reorders = $minmax['reorder_suggestions'] ?? [];
$shell = 'margin-bottom:20px; background:#ffffff; border:1px solid #e3ebf3; border-radius:26px; padding:18px; box-shadow:0 14px 32px rgba(20, 46, 79, 0.07);';
$th_style = 'padding:8px 10px; font-size:11px; color:#6480a0; text-transform:uppercase; letter-spacing:0.5px; border-bottom:2px solid #e6edf4;';
$td_style = 'padding:8px 10px; border-bottom:1px solid #f0f4f8; font-size:12px;';
?>
<?php if (!empty($reorders)): ?>
<div class="section" style="<?php echo $shell; ?>">
    <div style="font-size:24px; font-weight:700; color:#13273e; line-height:1.2; margin-bottom:4px;">Đề xuất mua hàng</div>
    <div style="font-size:12px; color:#77889a; margin-bottom:16px;"><?php echo count($reorders); ?> sản phẩm cần bổ sung</div>

    <div style="border:1px solid #dbe8f7; border-radius:22px; padding:14px 15px; background:#fafcff;">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <th style="<?php echo $th_style; ?> text-align:left;">Shop</th>
                <th style="<?php echo $th_style; ?> text-align:left;">SKU</th>
                <th style="<?php echo $th_style; ?> text-align:right;">Tồn</th>
                <th style="<?php echo $th_style; ?> text-align:right;">MIN</th>
                <th style="<?php echo $th_style; ?> text-align:right;">Thiếu</th>
                <th style="<?php echo $th_style; ?> text-align:center;">Loại</th>
                <th style="<?php echo $th_style; ?> text-align:center;">TT</th>
            </tr>
            <?php foreach (array_slice($reorders, 0, 15) as $i => $r):
                $st = (int) ($r->status ?? 0);
                $status_label = $st === 1 ? 'Duyệt' : ($st === 0 ? 'Chờ' : ($st === 3 ? 'Xong' : 'Từ chối'));
                $status_bg = $st === 1 ? '#f3fbf6' : ($st === 0 ? '#fffcf5' : '#f5f5f5');
                $status_cl = $st === 1 ? '#1f8f4d' : ($st === 0 ? '#b8860b' : '#77889a');
                $action_label = ((int)($r->suggested_action ?? 1)) === 2 ? 'Chuyển kho' : 'Mua thêm';
                $action_bg = ((int)($r->suggested_action ?? 1)) === 2 ? '#eef5ff' : '#fff1f0';
                $action_cl = ((int)($r->suggested_action ?? 1)) === 2 ? '#2d5f8a' : '#cf3d32';
            ?>
            <tr>
                <td style="<?php echo $td_style; ?>"><?php echo esc_html($r->blog_name_cache ?? 'Shop #' . ($r->blog_id ?? '')); ?></td>
                <td style="<?php echo $td_style; ?> font-weight:600; color:#13273e;"><?php echo esc_html($r->product_sku ?? ''); ?></td>
                <td style="<?php echo $td_style; ?> text-align:right;"><?php echo $fmt($r->current_stock ?? 0); ?></td>
                <td style="<?php echo $td_style; ?> text-align:right; color:#77889a;"><?php echo $fmt($r->min_qty ?? 0); ?></td>
                <td style="<?php echo $td_style; ?> text-align:right;">
                    <span style="display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; background:#fff1f0; color:#cf3d32;"><?php echo $fmt($r->deficit_qty ?? 0); ?></span>
                </td>
                <td style="<?php echo $td_style; ?> text-align:center;">
                    <span style="display:inline-block; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:600; background:<?php echo $action_bg; ?>; color:<?php echo $action_cl; ?>;"><?php echo $action_label; ?></span>
                </td>
                <td style="<?php echo $td_style; ?> text-align:center;">
                    <span style="display:inline-block; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:600; background:<?php echo $status_bg; ?>; color:<?php echo $status_cl; ?>;"><?php echo $status_label; ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($reorders) > 15): ?>
            <tr><td colspan="7" style="padding:8px 10px; color:#77889a; font-size:11px;">...và <?php echo count($reorders) - 15; ?> mục khác</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php endif; ?>
