<?php
/**
 * Component: MAX tồn kho tại shop
 * Biến: $max
 */
if (!defined('ABSPATH')) exit;
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };
$total_over = 0;
foreach ($max as $m) { $total_over += $m['total_over_max_items'] ?? 0; }
$shell_style = 'margin-bottom:20px; background:#ffffff; border:1px solid #e3ebf3; border-radius:26px; padding:18px; box-shadow:0 14px 32px rgba(20, 46, 79, 0.07);';
?>
<div class="section" style="<?php echo $shell_style; ?>">
    <div style="font-size:24px; font-weight:700; color:#13273e; line-height:1.2; margin-bottom:16px;">Tồn vượt MAX</div>

    <?php if (empty($max)): ?>
        <div class="alert alert-success" style="padding:12px 16px; border-radius:6px; background:#d4edda; color:#155724; border-left:4px solid #28a745;">
            Không có sản phẩm nào vượt MAX tồn kho.
        </div>
    <?php else: ?>
        <div class="alert alert-warning" style="padding:14px 16px; border-radius:18px; background:#fff7dd; color:#856404; border:1px solid #f0ddb0; margin-bottom:14px;">
            Có <strong><?php echo $total_over; ?></strong> sản phẩm vượt MAX tại <strong><?php echo count($max); ?></strong> shop.
        </div>

        <?php foreach ($max as $blog_id => $shop): ?>
        <div data-shop="<?php echo (int)$blog_id; ?>" style="margin-bottom:14px; border:1px solid #e6edf4; border-radius:22px; padding:14px 15px; background:#fcfdff;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:8px;">
                <tr>
                    <td style="vertical-align:top; padding-right:8px;">
                        <div style="font-size:16px; font-weight:700; color:#13273e;"><?php echo esc_html($shop['shop_name']); ?></div>
                        <div style="font-size:11px; color:#77889a; margin-top:4px;"><?php echo $shop['total_over_max_items']; ?> sản phẩm vượt MAX</div>
                    </td>
                </tr>
            </table>
            <?php if (!empty($shop['note'])): ?>
                <div style="font-size:11px; color:#7a8d9f; margin-bottom:8px;"><?php echo esc_html($shop['note']); ?></div>
            <?php endif; ?>

            <table class="data-table" style="width:100%; border-collapse:collapse; font-size:12px; margin-top:4px;">
                <tr>
                    <th style="background:#f0f4f8; padding:6px 8px; text-align:left; border-bottom:1px solid #dee2e6;">Tên sản phẩm</th>
                    <th style="background:#f0f4f8; padding:6px 8px; text-align:left; border-bottom:1px solid #dee2e6;">SKU</th>
                    <th style="background:#f0f4f8; padding:6px 8px; text-align:right; border-bottom:1px solid #dee2e6;">Tồn Hiện Tại</th>
                    <th style="background:#f0f4f8; padding:6px 8px; text-align:right; border-bottom:1px solid #dee2e6;">MAX</th>
                    <th style="background:#f0f4f8; padding:6px 8px; text-align:right; border-bottom:1px solid #dee2e6;">Vượt</th>
                </tr>
                <?php foreach (array_slice($shop['items'], 0, 15) as $item): ?>
                <tr>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;"><?php echo esc_html($item['product_name'] ?: $item['sku']); ?></td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; color:#77889a; font-size:11px;"><?php echo esc_html($item['sku']); ?></td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo $item['closing_qty'] !== null ? $fmt($item['closing_qty']) : '—'; ?></td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo $fmt($item['max_qty']); ?></td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right; color:#dc3545; font-weight:600;">
                        <?php echo $item['over_max'] !== null ? '+' . $fmt($item['over_max']) : '—'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($shop['items']) > 15): ?>
                <tr>
                    <td colspan="5" style="padding:6px 8px; color:#888; font-size:11px; text-align:center;">... và <?php echo count($shop['items']) - 15; ?> sản phẩm khác</td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
