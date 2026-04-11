<?php
/**
 * Component: Cảnh báo MIN/MAX kho
 * Biến: $minmax
 */
if (!defined('ABSPATH')) exit;
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };
$sm = $minmax['summary'] ?? [];
?>
<div class="section">
    <div class="section-title" style="font-size:16px; font-weight:700; color:#1e3a5f; margin:0 0 12px 0; padding-bottom:8px; border-bottom:2px solid #dc3545;">
        ⚠️ Cảnh Báo MIN / MAX Tồn Kho
    </div>

    <!-- Summary -->
    <div class="stats-row" style="display:flex; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
        <div class="stat-card danger" style="flex:1; min-width:140px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #dc3545;">
            <div class="label" style="font-size:11px; color:#6c757d;">Dưới MIN</div>
            <div class="value" style="font-size:20px; font-weight:700; color:#dc3545;"><?php echo $sm['total_below_min'] ?? 0; ?></div>
        </div>
        <div class="stat-card warning" style="flex:1; min-width:140px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #ffc107;">
            <div class="label" style="font-size:11px; color:#6c757d;">Vượt MAX</div>
            <div class="value" style="font-size:20px; font-weight:700; color:#856404;"><?php echo $sm['total_above_max'] ?? 0; ?></div>
        </div>
        <div class="stat-card" style="flex:1; min-width:140px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #ffc107;">
            <div class="label" style="font-size:11px; color:#6c757d;">Sắp Hết Hạn</div>
            <div class="value" style="font-size:20px; font-weight:700; color:#856404;"><?php echo $sm['total_near_expiry'] ?? 0; ?></div>
        </div>
        <div class="stat-card info" style="flex:1; min-width:140px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #17a2b8;">
            <div class="label" style="font-size:11px; color:#6c757d;">Đề Xuất Mua</div>
            <div class="value" style="font-size:20px; font-weight:700; color:#0c5460;"><?php echo $sm['total_reorder'] ?? 0; ?></div>
        </div>
    </div>

    <!-- Dưới MIN -->
    <?php if (!empty($minmax['below_min'])): ?>
    <div style="margin-bottom:16px;">
        <strong style="color:#dc3545; font-size:14px;">🔻 Sản Phẩm Dưới MIN (<?php echo count($minmax['below_min']); ?>)</strong>
        <div style="overflow-x:auto;">
            <table class="data-table" style="width:100%; border-collapse:collapse; font-size:12px; margin-top:8px;">
                <tr>
                    <th style="background:#f8d7da; padding:8px; text-align:left; border-bottom:1px solid #dee2e6;">Shop</th>
                    <th style="background:#f8d7da; padding:8px; text-align:left; border-bottom:1px solid #dee2e6;">SKU</th>
                    <th style="background:#f8d7da; padding:8px; text-align:right; border-bottom:1px solid #dee2e6;">Tồn</th>
                    <th style="background:#f8d7da; padding:8px; text-align:right; border-bottom:1px solid #dee2e6;">MIN</th>
                    <th style="background:#f8d7da; padding:8px; text-align:right; border-bottom:1px solid #dee2e6;">Thiếu</th>
                </tr>
                <?php foreach (array_slice($minmax['below_min'], 0, 20) as $item): ?>
                <tr>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;"><?php echo esc_html($item['shop_name']); ?></td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; font-weight:600;"><?php echo esc_html($item['sku']); ?></td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right; color:#dc3545; font-weight:600;"><?php echo $fmt($item['closing_qty']); ?></td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo $fmt($item['min_qty']); ?></td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right; color:#dc3545; font-weight:700;"><?php echo $fmt($item['shortage']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($minmax['below_min']) > 20): ?>
                <tr><td colspan="5" style="padding:6px 8px; color:#888; font-size:11px;">...và <?php echo count($minmax['below_min']) - 20; ?> mục khác</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Vượt MAX -->
    <?php if (!empty($minmax['above_max'])): ?>
    <div style="margin-bottom:16px;">
        <strong style="color:#856404; font-size:14px;">🔺 Sản Phẩm Vượt MAX (<?php echo count($minmax['above_max']); ?>)</strong>
        <div style="overflow-x:auto;">
            <table class="data-table" style="width:100%; border-collapse:collapse; font-size:12px; margin-top:8px;">
                <tr>
                    <th style="background:#fff3cd; padding:8px; text-align:left; border-bottom:1px solid #dee2e6;">Shop</th>
                    <th style="background:#fff3cd; padding:8px; text-align:left; border-bottom:1px solid #dee2e6;">SKU</th>
                    <th style="background:#fff3cd; padding:8px; text-align:right; border-bottom:1px solid #dee2e6;">Tồn</th>
                    <th style="background:#fff3cd; padding:8px; text-align:right; border-bottom:1px solid #dee2e6;">MAX</th>
                    <th style="background:#fff3cd; padding:8px; text-align:right; border-bottom:1px solid #dee2e6;">Dư</th>
                </tr>
                <?php foreach (array_slice($minmax['above_max'], 0, 20) as $item): ?>
                <tr>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;"><?php echo esc_html($item['shop_name']); ?></td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; font-weight:600;"><?php echo esc_html($item['sku']); ?></td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right; font-weight:600;"><?php echo $fmt($item['closing_qty']); ?></td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo $fmt($item['max_qty']); ?></td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right; color:#856404; font-weight:700;">+<?php echo $fmt($item['surplus']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($minmax['above_max']) > 20): ?>
                <tr><td colspan="5" style="padding:6px 8px; color:#888; font-size:11px;">...và <?php echo count($minmax['above_max']) - 20; ?> mục khác</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
