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
        Tổng Quan Kho Hàng
    </div>

    <!-- Row 1: Giá trị tồn kho + Tổng SKU -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:16px;">
        <tr>
            <td width="50%" style="padding:0 6px 12px 0; vertical-align:top;">
                <div style="background:linear-gradient(135deg,#1e3a5f,#2d5f8a); border-radius:8px; padding:14px 16px;">
                    <div style="font-size:11px; color:rgba(255,255,255,.7); text-transform:uppercase;">Giá Trị Tồn Cuối</div>
                    <div style="font-size:20px; font-weight:700; color:#fff;"><?php echo $fmt($t['closing_value'] ?? 0); ?>₫</div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 12px 6px; vertical-align:top;">
                <div style="background:linear-gradient(135deg,#2d5f8a,#3a7bb8); border-radius:8px; padding:14px 16px;">
                    <div style="font-size:11px; color:rgba(255,255,255,.7); text-transform:uppercase;">Giá Vốn Bán</div>
                    <div style="font-size:20px; font-weight:700; color:#fff;"><?php echo $fmt($t['cogs_value'] ?? 0); ?>₫</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Row 2: Số lượng tồn + biến động -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:16px;">
        <tr>
            <td width="33%" style="padding:0 4px 12px 0; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:12px 14px; border-left:4px solid #2d5f8a;">
                    <div style="font-size:10px; color:#6c757d; text-transform:uppercase;">Tồn Đầu Kỳ</div>
                    <div style="font-size:18px; font-weight:700; color:#1e3a5f;"><?php echo $fmt($t['opening_qty'] ?? 0); ?></div>
                    <div style="font-size:10px; color:#888;"><?php echo $fmt($t['opening_value'] ?? 0); ?>₫</div>
                </div>
            </td>
            <td width="34%" style="padding:0 4px 12px 4px; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:12px 14px; border-left:4px solid #28a745;">
                    <div style="font-size:10px; color:#6c757d; text-transform:uppercase;">Nhập</div>
                    <div style="font-size:18px; font-weight:700; color:#28a745;">+<?php echo $fmt($t['in_qty'] ?? 0); ?></div>
                    <div style="font-size:10px; color:#888;"><?php echo $fmt($t['in_value'] ?? 0); ?>₫</div>
                </div>
            </td>
            <td width="33%" style="padding:0 0 12px 4px; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:12px 14px; border-left:4px solid #dc3545;">
                    <div style="font-size:10px; color:#6c757d; text-transform:uppercase;">Bán</div>
                    <div style="font-size:18px; font-weight:700; color:#dc3545;"><?php echo $fmt($t['out_qty'] ?? 0); ?></div>
                    <div style="font-size:10px; color:#888;"><?php echo $fmt($t['out_value'] ?? 0); ?>₫</div>
                </div>
            </td>
        </tr>
        <tr>
            <td width="33%" style="padding:0 4px 0 0; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:12px 14px; border-left:4px solid #17a2b8;">
                    <div style="font-size:10px; color:#6c757d; text-transform:uppercase;">Tồn Cuối</div>
                    <div style="font-size:18px; font-weight:700; color:#1e3a5f;"><?php echo $fmt($t['closing_qty'] ?? 0); ?></div>
                    <div style="font-size:10px; color:#888;"><?php echo $fmt($t['total_skus'] ?? 0); ?> SKU</div>
                </div>
            </td>
            <td width="34%" style="padding:0 4px 0 4px; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:12px 14px; border-left:4px solid #6c757d;">
                    <div style="font-size:10px; color:#6c757d; text-transform:uppercase;">Chuyển Kho</div>
                    <div style="font-size:18px; font-weight:700; color:#6c757d;"><?php echo $fmt($t['transfer_out_qty'] ?? 0); ?></div>
                    <div style="font-size:10px; color:#888;"><?php echo $fmt($t['transfer_out_value'] ?? 0); ?>₫</div>
                </div>
            </td>
            <td width="33%" style="padding:0 0 0 4px; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:12px 14px; border-left:4px solid #ffc107;">
                    <div style="font-size:10px; color:#6c757d; text-transform:uppercase;">Hư Hỏng</div>
                    <div style="font-size:18px; font-weight:700; color:#856404;"><?php echo $fmt($t['damage_qty'] ?? 0); ?></div>
                    <div style="font-size:10px; color:#888;"><?php echo $fmt($t['damage_value'] ?? 0); ?>₫</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Row 3: Cảnh báo -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td width="50%" style="padding:0 6px 0 0; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:12px 14px; border-left:4px solid #dc3545;">
                    <div style="font-size:10px; color:#6c757d;">Hết Hàng</div>
                    <div style="font-size:18px; font-weight:700; color:#dc3545;"><?php echo $fmt($t['stockout_count'] ?? 0); ?> <span style="font-size:12px;">SKU</span></div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 0 6px; vertical-align:top;">
                <div style="background:#f8fafc; border-radius:8px; padding:12px 14px; border-left:4px solid #ffc107;">
                    <div style="font-size:10px; color:#6c757d;">Chậm Bán</div>
                    <div style="font-size:18px; font-weight:700; color:#856404;"><?php echo $fmt($t['slow_moving_count'] ?? 0); ?> <span style="font-size:12px;">SKU</span></div>
                </div>
            </td>
        </tr>
    </table>
</div>
