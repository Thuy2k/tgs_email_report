<?php
/**
 * Component: Đối chiếu thu ngân hàng
 * Biến: $bank
 */
if (!defined('ABSPATH')) exit;
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };

$total_expected = 0; $total_collected = 0;
foreach ($bank as $b) { $total_expected += $b['expected_revenue']; $total_collected += $b['actual_collected']; }
$total_diff = $total_collected - $total_expected;
?>
<div class="section">
    <div class="section-title" style="font-size:16px; font-weight:700; color:#1e3a5f; margin:0 0 12px 0; padding-bottom:8px; border-bottom:2px solid #2d5f8a;">
        🏦 Đối Chiếu Thu Ngân Hàng & Tiền Mặt
    </div>

    <?php if (empty($bank)): ?>
        <div class="alert alert-info" style="padding:12px 16px; border-radius:6px; background:#d1ecf1; color:#0c5460; border-left:4px solid #17a2b8;">
            Không có dữ liệu thu tiền trong khoảng thời gian này.
        </div>
    <?php else: ?>

        <!-- Tổng quan đối chiếu -->
        <div class="stats-row" style="display:flex; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
            <div class="stat-card" style="flex:1; min-width:140px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #2d5f8a;">
                <div class="label" style="font-size:11px; color:#6c757d; text-transform:uppercase;">Cần Thu</div>
                <div class="value" style="font-size:20px; font-weight:700; color:#1e3a5f;"><?php echo $fmt($total_expected); ?>đ</div>
            </div>
            <div class="stat-card <?php echo $total_diff >= 0 ? 'success' : 'danger'; ?>" style="flex:1; min-width:140px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid <?php echo $total_diff >= 0 ? '#28a745' : '#dc3545'; ?>;">
                <div class="label" style="font-size:11px; color:#6c757d; text-transform:uppercase;">Thực Thu</div>
                <div class="value" style="font-size:20px; font-weight:700; color:<?php echo $total_diff >= 0 ? '#28a745' : '#dc3545'; ?>;"><?php echo $fmt($total_collected); ?>đ</div>
            </div>
            <div class="stat-card <?php echo abs($total_diff) <= 1000 ? 'success' : ($total_diff > 0 ? 'warning' : 'danger'); ?>" style="flex:1; min-width:140px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid <?php echo abs($total_diff) <= 1000 ? '#28a745' : ($total_diff > 0 ? '#ffc107' : '#dc3545'); ?>;">
                <div class="label" style="font-size:11px; color:#6c757d; text-transform:uppercase;">Chênh Lệch</div>
                <div class="value" style="font-size:20px; font-weight:700; color:<?php echo abs($total_diff) <= 1000 ? '#28a745' : '#dc3545'; ?>;"><?php echo ($total_diff > 0 ? '+' : '') . $fmt($total_diff); ?>đ</div>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table" style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:left; border-bottom:2px solid #dee2e6;">Shop</th>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:right; border-bottom:2px solid #dee2e6;">Cần Thu</th>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:right; border-bottom:2px solid #dee2e6;">Thực Thu</th>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:right; border-bottom:2px solid #dee2e6;">Chênh Lệch</th>
                        <th style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:center; border-bottom:2px solid #dee2e6;">Trạng Thái</th>
                        <th class="hide-mobile" style="background:#f0f4f8; color:#1e3a5f; padding:10px 8px; text-align:right; border-bottom:2px solid #dee2e6;">Phiếu Thu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bank as $blog_id => $b):
                        $status_color = $b['status'] === 'ok' ? '#28a745' : ($b['status'] === 'surplus' ? '#ffc107' : '#dc3545');
                        $status_bg = $b['status'] === 'ok' ? '#d4edda' : ($b['status'] === 'surplus' ? '#fff3cd' : '#f8d7da');
                        $status_label = $b['status'] === 'ok' ? '✓ Khớp' : ($b['status'] === 'surplus' ? '↑ Thừa' : '↓ Thiếu');
                    ?>
                    <tr>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; font-weight:600;"><?php echo esc_html($b['shop_name']); ?></td>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo $fmt($b['expected_revenue']); ?></td>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right; font-weight:600;"><?php echo $fmt($b['actual_collected']); ?></td>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right; color:<?php echo $status_color; ?>; font-weight:600;">
                            <?php echo ($b['difference'] > 0 ? '+' : '') . $fmt($b['difference']); ?>
                            <?php if ($b['difference_pct'] != 0): ?><br><span style="font-size:11px;">(<?php echo $b['difference_pct']; ?>%)</span><?php endif; ?>
                        </td>
                        <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:center;">
                            <span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; background:<?php echo $status_bg; ?>; color:<?php echo $status_color; ?>;"><?php echo $status_label; ?></span>
                        </td>
                        <td class="hide-mobile" style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo $b['receipt_count']; ?></td>
                    </tr>

                    <?php if (!empty($b['payment_breakdown'])): ?>
                    <tr>
                        <td colspan="6" style="padding:4px 8px 8px 24px; border-bottom:1px solid #f0f0f0; font-size:12px; color:#666;">
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

                    <!-- TỔNG -->
                    <tr>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6;">TỔNG</td>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6; text-align:right;"><?php echo $fmt($total_expected); ?></td>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6; text-align:right;"><?php echo $fmt($total_collected); ?></td>
                        <td style="padding:8px; background:#f0f4f8; font-weight:700; border-top:2px solid #dee2e6; text-align:right; color:<?php echo abs($total_diff) <= 1000 ? '#28a745' : '#dc3545'; ?>;"><?php echo ($total_diff > 0 ? '+' : '') . $fmt($total_diff); ?>đ</td>
                        <td style="padding:8px; background:#f0f4f8; border-top:2px solid #dee2e6;" colspan="2"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
