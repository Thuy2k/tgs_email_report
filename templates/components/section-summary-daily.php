<?php
/**
 * Component: Tổng quan hệ thống ngày (Summary Daily)
 * Biến: $summary
 */
if (!defined('ABSPATH')) exit;
$daily = $summary['daily_total'] ?? [];
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };
?>
<div class="section">
    <div class="section-title">📊 Tổng Quan Hệ Thống</div>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:16px;">
        <tr>
            <td width="50%" style="padding:0 6px 12px 0; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #2d5f8a;">
                    <div style="font-size:11px; color:#6c757d; text-transform:uppercase;">Tổng Đơn Hàng</div>
                    <div style="font-size:20px; font-weight:700; color:#1e3a5f;"><?php echo $fmt($daily['total_orders'] ?? 0); ?></div>
                    <div style="font-size:11px; color:#888;"><?php echo (int)($daily['active_shops'] ?? 0); ?> shop hoạt động</div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 12px 6px; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #28a745;">
                    <div style="font-size:11px; color:#6c757d; text-transform:uppercase;">Thực Thu Bán Hàng</div>
                    <div style="font-size:20px; font-weight:700; color:#28a745;"><?php echo $fmt($daily['total_net'] ?? 0); ?>đ</div>
                    <div style="font-size:11px; color:#888;">Tổng bán: <?php echo $fmt($daily['total_gross'] ?? 0); ?>đ</div>
                </div>
            </td>
        </tr>
        <tr>
            <td width="50%" style="padding:0 6px 0 0; vertical-align:top;">
                <?php
                    $avg_order = ($daily['total_orders'] ?? 0) > 0
                        ? round(($daily['total_net'] ?? 0) / $daily['total_orders'])
                        : 0;
                ?>
                <div style="background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #17a2b8;">
                    <div style="font-size:11px; color:#6c757d; text-transform:uppercase;">TB Mỗi Đơn</div>
                    <div style="font-size:20px; font-weight:700; color:#1e3a5f;"><?php echo $fmt($avg_order); ?>đ</div>
                    <div style="font-size:11px; color:#888;">Chiết khấu: <?php echo $fmt($daily['total_discount'] ?? 0); ?>đ</div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 0 6px; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #ffc107;">
                    <div style="font-size:11px; color:#6c757d; text-transform:uppercase;">Khách Hàng</div>
                    <div style="font-size:20px; font-weight:700; color:#856404;"><?php echo $fmt($daily['total_customers'] ?? 0); ?></div>
                    <div style="font-size:11px; color:#888;">Trả hàng: <?php echo $fmt($daily['total_return'] ?? 0); ?>đ</div>
                </div>
            </td>
        </tr>
    </table>

    <?php if (!empty($summary['top_shops'])): ?>
    <div style="margin-top:8px;">
        <strong style="font-size:13px; color:#1e3a5f;">🏆 Top 5 Shop Doanh Thu:</strong>
        <table class="data-table" style="width:100%; border-collapse:collapse; font-size:13px; margin-top:8px;">
            <tr>
                <th style="background:#f0f4f8; color:#1e3a5f; padding:8px; text-align:left; border-bottom:2px solid #dee2e6;">#</th>
                <th style="background:#f0f4f8; color:#1e3a5f; padding:8px; text-align:left; border-bottom:2px solid #dee2e6;">Shop</th>
                <th style="background:#f0f4f8; color:#1e3a5f; padding:8px; text-align:right; border-bottom:2px solid #dee2e6;">Thực Thu</th>
            </tr>
            <?php foreach ($summary['top_shops'] as $i => $t): ?>
            <tr>
                <td style="padding:8px; border-bottom:1px solid #f0f0f0;"><?php echo $i + 1; ?></td>
                <td style="padding:8px; border-bottom:1px solid #f0f0f0; font-weight:600;"><?php echo esc_html($t['shop_name']); ?></td>
                <td style="padding:8px; border-bottom:1px solid #f0f0f0; text-align:right; color:#28a745; font-weight:600;"><?php echo $fmt($t['net']); ?>đ</td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>
</div>
