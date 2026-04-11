<?php
/**
 * Component: So sánh tuần
 * Biến: $summary
 */
if (!defined('ABSPATH')) exit;
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };
$wc = $summary['weekly_compare'] ?? [];
$change = $wc['change_pct'] ?? 0;
$arrow = $change > 0 ? '↑' : ($change < 0 ? '↓' : '→');
$color = $change > 0 ? '#28a745' : ($change < 0 ? '#dc3545' : '#6c757d');
?>
<div class="section">
    <div class="section-title" style="font-size:16px; font-weight:700; color:#1e3a5f; margin:0 0 12px 0; padding-bottom:8px; border-bottom:2px solid #2d5f8a;">
        📈 So Sánh Tuần
    </div>

    <?php if (empty($wc) || ($wc['this_week_net'] == 0 && $wc['prev_week_net'] == 0)): ?>
        <div class="alert alert-info" style="padding:12px 16px; border-radius:6px; background:#d1ecf1; color:#0c5460; border-left:4px solid #17a2b8;">
            Chưa đủ dữ liệu để so sánh tuần.
        </div>
    <?php else: ?>
        <div class="stats-row" style="display:flex; flex-wrap:wrap; gap:12px; margin-bottom:12px;">
            <div class="stat-card" style="flex:1; min-width:200px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #2d5f8a;">
                <div class="label" style="font-size:11px; color:#6c757d; text-transform:uppercase;">Tuần Này (từ <?php echo date('d/m', strtotime($wc['week_start'])); ?>)</div>
                <div class="value" style="font-size:20px; font-weight:700; color:#1e3a5f;"><?php echo $fmt($wc['this_week_net']); ?>đ</div>
                <div class="sub" style="font-size:11px; color:#888;"><?php echo $fmt($wc['this_week_orders']); ?> đơn | LN: <?php echo $fmt($wc['this_week_profit']); ?>đ</div>
            </div>

            <div class="stat-card" style="flex:1; min-width:200px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #6c757d;">
                <div class="label" style="font-size:11px; color:#6c757d; text-transform:uppercase;">Tuần Trước</div>
                <div class="value" style="font-size:20px; font-weight:700; color:#6c757d;"><?php echo $fmt($wc['prev_week_net']); ?>đ</div>
                <div class="sub" style="font-size:11px; color:#888;"><?php echo $fmt($wc['prev_week_orders']); ?> đơn | LN: <?php echo $fmt($wc['prev_week_profit']); ?>đ</div>
            </div>

            <div class="stat-card" style="flex:1; min-width:140px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid <?php echo $color; ?>;">
                <div class="label" style="font-size:11px; color:#6c757d; text-transform:uppercase;">Thay Đổi</div>
                <div class="value" style="font-size:24px; font-weight:700; color:<?php echo $color; ?>;">
                    <?php echo $arrow; ?> <?php echo abs($change); ?>%
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
