<?php
/**
 * Component: Tổng quan kho
 * Biến: $stock
 */
if (!defined('ABSPATH')) exit;
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };
$t = $stock['totals'] ?? [];
?>
<div class="section">
    <div class="section-title" style="font-size:16px; font-weight:700; color:#1e3a5f; margin:0 0 12px 0; padding-bottom:8px; border-bottom:2px solid #2d5f8a;">
        📦 Tổng Quan Kho Hàng
    </div>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:16px;">
        <tr>
            <td width="50%" style="padding:0 6px 12px 0; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #2d5f8a;">
                    <div style="font-size:11px; color:#6c757d; text-transform:uppercase;">Tồn Đầu Kỳ</div>
                    <div style="font-size:20px; font-weight:700; color:#1e3a5f;"><?php echo $fmt($t['opening_qty'] ?? 0); ?></div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 12px 6px; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #28a745;">
                    <div style="font-size:11px; color:#6c757d; text-transform:uppercase;">Nhập</div>
                    <div style="font-size:20px; font-weight:700; color:#28a745;">+<?php echo $fmt($t['in_qty'] ?? 0); ?></div>
                </div>
            </td>
        </tr>
        <tr>
            <td width="50%" style="padding:0 6px 0 0; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #dc3545;">
                    <div style="font-size:11px; color:#6c757d; text-transform:uppercase;">Xuất</div>
                    <div style="font-size:20px; font-weight:700; color:#dc3545;">-<?php echo $fmt($t['out_qty'] ?? 0); ?></div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 0 6px; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #17a2b8;">
                    <div style="font-size:11px; color:#6c757d; text-transform:uppercase;">Tồn Cuối</div>
                    <div style="font-size:20px; font-weight:700; color:#1e3a5f;"><?php echo $fmt($t['closing_qty'] ?? 0); ?></div>
                </div>
            </td>
        </tr>
    </table>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td width="50%" style="padding:0 6px 12px 0; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #ffc107;">
                    <div style="font-size:11px; color:#6c757d;">Hư Hỏng</div>
                    <div style="font-size:18px; font-weight:700; color:#856404;"><?php echo $fmt($t['damage_qty'] ?? 0); ?></div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 12px 6px; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #6c757d;">
                    <div style="font-size:11px; color:#6c757d;">Chuyển Kho</div>
                    <div style="font-size:18px; font-weight:700; color:#6c757d;"><?php echo $fmt($t['transfer_out_qty'] ?? 0); ?></div>
                </div>
            </td>
        </tr>
        <tr>
            <td width="50%" style="padding:0 6px 0 0; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #dc3545;">
                    <div style="font-size:11px; color:#6c757d;">Hết Hàng</div>
                    <div style="font-size:18px; font-weight:700; color:#dc3545;"><?php echo $fmt($t['stockout_count'] ?? 0); ?> SKU</div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 0 6px; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #ffc107;">
                    <div style="font-size:11px; color:#6c757d;">Chậm Bán</div>
                    <div style="font-size:18px; font-weight:700; color:#856404;"><?php echo $fmt($t['slow_moving_count'] ?? 0); ?> SKU</div>
                </div>
            </td>
        </tr>
    </table>
</div>
