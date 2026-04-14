<?php
/**
 * Component: Tồn kho theo shop
 * Biến: $stock
 */
if (!defined('ABSPATH')) exit;
$fmt  = function($v) { return number_format((float)$v, 0, ',', '.'); };
$fmtm = function($v) { return number_format((float)$v, 0, ',', '.'); };
$shops = $stock['by_shop'] ?? [];
$t = $stock['totals'] ?? [];
?>
<div class="section">
    <div class="section-title" style="font-size:16px; font-weight:700; color:#1e3a5f; margin:0 0 12px 0; padding-bottom:8px; border-bottom:2px solid #2d5f8a;">
        Tồn Kho Theo Shop
    </div>

    <?php if (empty($shops)): ?>
        <div class="alert alert-info" style="padding:12px 16px; border-radius:6px; background:#d1ecf1; color:#0c5460; border-left:4px solid #17a2b8;">
            Chưa có dữ liệu tồn kho rollup.
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="data-table" style="width:100%; border-collapse:collapse; font-size:12px;">
                <thead>
                    <tr>
                        <th style="background:#f0f4f8; padding:8px; text-align:left; border-bottom:2px solid #dee2e6;">Shop</th>
                        <th style="background:#f0f4f8; padding:8px; text-align:right; border-bottom:2px solid #dee2e6;">SKU</th>
                        <th style="background:#f0f4f8; padding:8px; text-align:right; border-bottom:2px solid #dee2e6;">Tồn Đầu</th>
                        <th style="background:#f0f4f8; padding:8px; text-align:right; border-bottom:2px solid #dee2e6;">Nhập</th>
                        <th style="background:#f0f4f8; padding:8px; text-align:right; border-bottom:2px solid #dee2e6;">Bán</th>
                        <th class="hide-mobile" style="background:#f0f4f8; padding:8px; text-align:right; border-bottom:2px solid #dee2e6;">Hỏng</th>
                        <th class="hide-mobile" style="background:#f0f4f8; padding:8px; text-align:right; border-bottom:2px solid #dee2e6;">C.Kho</th>
                        <th style="background:#f0f4f8; padding:8px; text-align:right; border-bottom:2px solid #dee2e6;">Tồn Cuối</th>
                        <th style="background:#f0f4f8; padding:8px; text-align:right; border-bottom:2px solid #dee2e6;">GT Tồn</th>
                        <th class="hide-mobile" style="background:#f0f4f8; padding:8px; text-align:right; border-bottom:2px solid #dee2e6;">Hết Hàng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shops as $bid => $s): ?>
                    <tr>
                        <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; font-weight:600;"><?php echo esc_html($s['shop_name']); ?></td>
                        <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo $s['total_skus']; ?></td>
                        <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo $fmt($s['opening_qty']); ?></td>
                        <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right; color:#28a745;">+<?php echo $fmt($s['in_qty']); ?></td>
                        <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right; color:#dc3545;"><?php echo $fmt($s['out_qty']); ?></td>
                        <td class="hide-mobile" style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right; color:#856404;"><?php echo $fmt($s['damage_qty']); ?></td>
                        <td class="hide-mobile" style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo $fmt($s['transfer_out_qty']); ?></td>
                        <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right; font-weight:700;"><?php echo $fmt($s['closing_qty']); ?></td>
                        <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right; font-weight:600; color:#1e3a5f;"><?php echo $fmtm($s['closing_value']); ?></td>
                        <td class="hide-mobile" style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right;">
                            <?php if ($s['stockout_count'] > 0): ?>
                                <span style="display:inline-block; padding:2px 6px; border-radius:4px; font-size:11px; font-weight:600; background:#f8d7da; color:#721c24;"><?php echo $s['stockout_count']; ?></span>
                            <?php else: ?>
                                <span style="color:#28a745;">0</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:#f0f4f8; font-weight:700;">
                        <td style="padding:8px; border-top:2px solid #dee2e6;">Tổng</td>
                        <td style="padding:8px; border-top:2px solid #dee2e6; text-align:right;"><?php echo $fmt($t['total_skus'] ?? 0); ?></td>
                        <td style="padding:8px; border-top:2px solid #dee2e6; text-align:right;"><?php echo $fmt($t['opening_qty'] ?? 0); ?></td>
                        <td style="padding:8px; border-top:2px solid #dee2e6; text-align:right; color:#28a745;">+<?php echo $fmt($t['in_qty'] ?? 0); ?></td>
                        <td style="padding:8px; border-top:2px solid #dee2e6; text-align:right; color:#dc3545;"><?php echo $fmt($t['out_qty'] ?? 0); ?></td>
                        <td class="hide-mobile" style="padding:8px; border-top:2px solid #dee2e6; text-align:right; color:#856404;"><?php echo $fmt($t['damage_qty'] ?? 0); ?></td>
                        <td class="hide-mobile" style="padding:8px; border-top:2px solid #dee2e6; text-align:right;"><?php echo $fmt($t['transfer_out_qty'] ?? 0); ?></td>
                        <td style="padding:8px; border-top:2px solid #dee2e6; text-align:right;"><?php echo $fmt($t['closing_qty'] ?? 0); ?></td>
                        <td style="padding:8px; border-top:2px solid #dee2e6; text-align:right; color:#1e3a5f;"><?php echo $fmtm($t['closing_value'] ?? 0); ?></td>
                        <td class="hide-mobile" style="padding:8px; border-top:2px solid #dee2e6; text-align:right;"><?php echo $fmt($t['stockout_count'] ?? 0); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>
