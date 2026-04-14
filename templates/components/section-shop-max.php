<?php
/**
 * Component: MAX tồn kho tại shop
 * Biến: $max
 */
if (!defined('ABSPATH')) exit;
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };
$total_over = 0;
foreach ($max as $m) { $total_over += $m['total_over_max_items'] ?? 0; }
?>
<div class="section">
    <div class="section-title" style="font-size:16px; font-weight:700; color:#1e3a5f; margin:0 0 12px 0; padding-bottom:8px; border-bottom:2px solid #2d5f8a;">
        MAX Tồn Kho Tại Shop
    </div>

    <?php if (empty($max)): ?>
        <div class="alert alert-success" style="padding:12px 16px; border-radius:6px; background:#d4edda; color:#155724; border-left:4px solid #28a745;">
            Không có sản phẩm nào vượt MAX tồn kho.
        </div>
    <?php else: ?>
        <div class="alert alert-warning" style="padding:12px 16px; border-radius:6px; background:#fff3cd; color:#856404; border-left:4px solid #ffc107; margin-bottom:12px;">
            Có <strong><?php echo $total_over; ?></strong> sản phẩm vượt MAX tại <strong><?php echo count($max); ?></strong> shop.
        </div>

        <?php foreach ($max as $blog_id => $shop): ?>
        <div style="margin-bottom:16px;">
            <strong style="font-size:13px; color:#1e3a5f;"><?php echo esc_html($shop['shop_name']); ?> — <?php echo $shop['total_over_max_items']; ?> SP vượt MAX</strong>
            <?php if (!empty($shop['note'])): ?>
                <div style="font-size:11px; color:#888; margin-top:2px;"><?php echo esc_html($shop['note']); ?></div>
            <?php endif; ?>

            <table class="data-table" style="width:100%; border-collapse:collapse; font-size:12px; margin-top:6px;">
                <tr>
                    <th style="background:#f0f4f8; padding:6px 8px; text-align:left; border-bottom:1px solid #dee2e6;">SKU</th>
                    <th style="background:#f0f4f8; padding:6px 8px; text-align:right; border-bottom:1px solid #dee2e6;">Tồn Hiện Tại</th>
                    <th style="background:#f0f4f8; padding:6px 8px; text-align:right; border-bottom:1px solid #dee2e6;">MAX</th>
                    <th style="background:#f0f4f8; padding:6px 8px; text-align:right; border-bottom:1px solid #dee2e6;">Vượt</th>
                </tr>
                <?php foreach (array_slice($shop['items'], 0, 10) as $item): ?>
                <tr>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;"><?php echo esc_html($item['sku']); ?></td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo $item['closing_qty'] !== null ? $fmt($item['closing_qty']) : '—'; ?></td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo $fmt($item['max_qty']); ?></td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right; color:#dc3545; font-weight:600;">
                        <?php echo $item['over_max'] !== null ? '+' . $fmt($item['over_max']) : '—'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($shop['items']) > 10): ?>
                <tr>
                    <td colspan="4" style="padding:6px 8px; color:#888; font-size:11px;">...và <?php echo count($shop['items']) - 10; ?> SP khác</td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
