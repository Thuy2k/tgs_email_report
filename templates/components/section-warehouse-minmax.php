<?php
/**
 * Component: Cảnh báo MIN/MAX kho — chia theo shop
 * Biến: $minmax  (có key by_shop, summary, below_min, above_max, stockout)
 */
if (!defined('ABSPATH')) exit;
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };
$sm       = $minmax['summary'] ?? [];
$by_shop  = $minmax['by_shop'] ?? [];
$shell    = 'margin-bottom:20px; background:#ffffff; border:1px solid #e3ebf3; border-radius:26px; padding:18px; box-shadow:0 14px 32px rgba(20, 46, 79, 0.07);';
$th_style = 'padding:8px 10px; font-size:11px; color:#6480a0; text-transform:uppercase; letter-spacing:0.5px; border-bottom:2px solid #e6edf4;';
$td_style = 'padding:8px 10px; border-bottom:1px solid #f0f4f8; font-size:12px;';
$total_issues = ($sm['total_stockout'] ?? 0) + ($sm['total_below_min'] ?? 0) + ($sm['total_above_max'] ?? 0);
?>
<div class="section" style="<?php echo $shell; ?>">
    <div style="font-size:24px; font-weight:700; color:#13273e; line-height:1.2; margin-bottom:16px;">Cảnh báo MIN / MAX</div>

    <!-- Tổng quan -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:16px;">
        <tr>
            <td width="33%" style="padding:0 4px 10px 0; vertical-align:top;">
                <div style="background:#fef2f1; border:1px solid #f4dfdc; border-radius:18px; padding:12px 10px; text-align:center;">
                    <div style="font-size:10px; color:#9b6d68; text-transform:uppercase; letter-spacing:0.8px;">Hết hàng</div>
                    <div style="font-size:24px; font-weight:700; color:#cf3d32; margin-top:3px;"><?php echo $sm['total_stockout'] ?? 0; ?></div>
                </div>
            </td>
            <td width="34%" style="padding:0 4px 10px 4px; vertical-align:top;">
                <div style="background:#fff8f0; border:1px solid #f0e5c9; border-radius:18px; padding:12px 10px; text-align:center;">
                    <div style="font-size:10px; color:#9b8968; text-transform:uppercase; letter-spacing:0.8px;">Dưới MIN</div>
                    <div style="font-size:24px; font-weight:700; color:#b8860b; margin-top:3px;"><?php echo $sm['total_below_min'] ?? 0; ?></div>
                </div>
            </td>
            <td width="33%" style="padding:0 0 10px 4px; vertical-align:top;">
                <div style="background:#eef5ff; border:1px solid #dbe8f7; border-radius:18px; padding:12px 10px; text-align:center;">
                    <div style="font-size:10px; color:#6480a0; text-transform:uppercase; letter-spacing:0.8px;">Vượt MAX</div>
                    <div style="font-size:24px; font-weight:700; color:#2d5f8a; margin-top:3px;"><?php echo $sm['total_above_max'] ?? 0; ?></div>
                </div>
            </td>
        </tr>
    </table>

    <?php if (empty($by_shop)): ?>
        <div style="padding:14px 16px; border-radius:18px; background:#f3fbf6; color:#1f8f4d; border:1px solid #cce8d6; text-align:center;">
            Không có cảnh báo tồn kho nào — tất cả đều trong ngưỡng.
        </div>
    <?php else: ?>
        <!-- Per-shop cards -->
        <?php foreach ($by_shop as $bid => $shop):
            $s_stockout  = $shop['total_stockout']  ?? 0;
            $s_below     = $shop['total_below_min']  ?? 0;
            $s_above     = $shop['total_above_max']  ?? 0;
            $s_total     = $s_stockout + $s_below + $s_above;
            if ($s_total === 0) continue;

            // Severity color for shop border
            $border_color = $s_stockout > 0 ? '#f4dfdc' : ($s_below > 0 ? '#f0e5c9' : '#dbe8f7');
            $bg_color     = $s_stockout > 0 ? '#fffbfa' : ($s_below > 0 ? '#fffdf8' : '#fafcff');
        ?>
        <div style="margin-bottom:14px; border:1px solid <?php echo $border_color; ?>; border-radius:22px; padding:14px 15px; background:<?php echo $bg_color; ?>;">
            <!-- Shop header -->
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="vertical-align:middle;">
                        <div style="font-size:16px; font-weight:700; color:#13273e;"><?php echo esc_html($shop['shop_name']); ?></div>
                    </td>
                    <td align="right" style="vertical-align:middle; white-space:nowrap;">
                        <?php if ($s_stockout > 0): ?>
                        <span style="display:inline-block; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; background:#fff1f0; color:#cf3d32; margin-left:4px;"><?php echo $s_stockout; ?> hết hàng</span>
                        <?php endif; ?>
                        <?php if ($s_below > 0): ?>
                        <span style="display:inline-block; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; background:#fffcf5; color:#b8860b; margin-left:4px;"><?php echo $s_below; ?> dưới MIN</span>
                        <?php endif; ?>
                        <?php if ($s_above > 0): ?>
                        <span style="display:inline-block; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; background:#eef5ff; color:#2d5f8a; margin-left:4px;"><?php echo $s_above; ?> vượt MAX</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <?php /* ── Hết hàng ── */ ?>
            <?php if ($s_stockout > 0): ?>
            <div style="margin-top:12px;">
                <div style="font-size:12px; font-weight:700; color:#cf3d32; margin-bottom:6px;">Hết hàng</div>
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <th style="<?php echo $th_style; ?> text-align:left;">Sản phẩm</th>
                        <th style="<?php echo $th_style; ?> text-align:right;">Tồn</th>
                        <th style="<?php echo $th_style; ?> text-align:right;">MAX</th>
                    </tr>
                    <?php foreach ($shop['stockout'] as $item): ?>
                    <tr>
                        <td style="<?php echo $td_style; ?>">
                            <div style="font-weight:600; color:#13273e;"><?php echo esc_html($item['product_name'] ?? $item['sku']); ?></div>
                            <div style="font-size:10px; color:#77889a;"><?php echo esc_html($item['sku']); ?></div>
                        </td>
                        <td style="<?php echo $td_style; ?> text-align:right;">
                            <span style="display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; background:#fff1f0; color:#cf3d32;">0</span>
                        </td>
                        <td style="<?php echo $td_style; ?> text-align:right; color:#77889a;"><?php echo $fmt($item['max_qty'] ?? 0); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>

            <?php /* ── Dưới MIN ── */ ?>
            <?php if ($s_below > 0): ?>
            <div style="margin-top:12px;">
                <div style="font-size:12px; font-weight:700; color:#b8860b; margin-bottom:6px;">Dưới MIN</div>
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <th style="<?php echo $th_style; ?> text-align:left;">Sản phẩm</th>
                        <th style="<?php echo $th_style; ?> text-align:right;">Tồn</th>
                        <th style="<?php echo $th_style; ?> text-align:right;">MIN</th>
                        <th style="<?php echo $th_style; ?> text-align:right;">Tốc độ bán</th>
                        <th style="<?php echo $th_style; ?> text-align:right;">Gợi ý mua</th>
                        <th style="<?php echo $th_style; ?> text-align:right;">Thiếu</th>
                    </tr>
                    <?php foreach ($shop['below_min'] as $item): ?>
                    <tr>
                        <td style="<?php echo $td_style; ?>">
                            <div style="font-weight:600; color:#13273e;"><?php echo esc_html($item['product_name'] ?? $item['sku']); ?></div>
                            <div style="font-size:10px; color:#77889a;"><?php echo esc_html($item['sku']); ?></div>
                        </td>
                        <td style="<?php echo $td_style; ?> text-align:right; color:#b8860b; font-weight:600;"><?php echo $fmt($item['closing_qty']); ?></td>
                        <td style="<?php echo $td_style; ?> text-align:right; color:#77889a;"><?php echo $fmt($item['min_qty']); ?></td>
                        <td style="<?php echo $td_style; ?> text-align:right; color:#555;">
                            <?php $spd = $item['sell_speed'] ?? 0; echo $spd > 0 ? number_format($spd, 2) . '/ngày' : '<span style="color:#bbb;">—</span>'; ?>
                        </td>
                        <td style="<?php echo $td_style; ?> text-align:right;">
                            <?php $sg = $item['suggest_buy'] ?? 0; ?>
                            <?php if ($sg > 0): ?>
                            <span style="display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; background:#e8f5e9; color:#1b5e20;">+<?php echo $fmt($sg); ?></span>
                            <?php else: echo '<span style="color:#bbb;">—</span>'; endif; ?>
                        </td>
                        <td style="<?php echo $td_style; ?> text-align:right;">
                            <span style="display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; background:#fffcf5; color:#b8860b;"><?php echo $fmt($item['shortage']); ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>

            <?php /* ── Vượt MAX ── */ ?>
            <?php if ($s_above > 0): ?>
            <div style="margin-top:12px;">
                <div style="font-size:12px; font-weight:700; color:#2d5f8a; margin-bottom:6px;">Vượt MAX</div>
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <th style="<?php echo $th_style; ?> text-align:left;">Sản phẩm</th>
                        <th style="<?php echo $th_style; ?> text-align:right;">Tồn</th>
                        <th style="<?php echo $th_style; ?> text-align:right;">MAX</th>
                        <th style="<?php echo $th_style; ?> text-align:right;">Dư</th>
                    </tr>
                    <?php foreach ($shop['above_max'] as $item): ?>
                    <tr>
                        <td style="<?php echo $td_style; ?>">
                            <div style="font-weight:600; color:#13273e;"><?php echo esc_html($item['product_name'] ?? $item['sku']); ?></div>
                            <div style="font-size:10px; color:#77889a;"><?php echo esc_html($item['sku']); ?></div>
                        </td>
                        <td style="<?php echo $td_style; ?> text-align:right; font-weight:600;"><?php echo $fmt($item['closing_qty']); ?></td>
                        <td style="<?php echo $td_style; ?> text-align:right; color:#77889a;"><?php echo $fmt($item['max_qty']); ?></td>
                        <td style="<?php echo $td_style; ?> text-align:right;">
                            <span style="display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; background:#eef5ff; color:#2d5f8a;">+<?php echo $fmt($item['surplus']); ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
