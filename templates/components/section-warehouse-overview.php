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

    <div class="stats-row" style="display:flex; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
        <div class="stat-card" style="flex:1; min-width:140px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #2d5f8a;">
            <div class="label" style="font-size:11px; color:#6c757d; text-transform:uppercase;">Tồn Đầu Kỳ</div>
            <div class="value" style="font-size:20px; font-weight:700; color:#1e3a5f;"><?php echo $fmt($t['opening_qty'] ?? 0); ?></div>
        </div>
        <div class="stat-card success" style="flex:1; min-width:140px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #28a745;">
            <div class="label" style="font-size:11px; color:#6c757d; text-transform:uppercase;">Nhập</div>
            <div class="value" style="font-size:20px; font-weight:700; color:#28a745;">+<?php echo $fmt($t['in_qty'] ?? 0); ?></div>
        </div>
        <div class="stat-card danger" style="flex:1; min-width:140px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #dc3545;">
            <div class="label" style="font-size:11px; color:#6c757d; text-transform:uppercase;">Xuất</div>
            <div class="value" style="font-size:20px; font-weight:700; color:#dc3545;">-<?php echo $fmt($t['out_qty'] ?? 0); ?></div>
        </div>
        <div class="stat-card" style="flex:1; min-width:140px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #17a2b8;">
            <div class="label" style="font-size:11px; color:#6c757d; text-transform:uppercase;">Tồn Cuối</div>
            <div class="value" style="font-size:20px; font-weight:700; color:#1e3a5f;"><?php echo $fmt($t['closing_qty'] ?? 0); ?></div>
        </div>
    </div>

    <div class="stats-row" style="display:flex; flex-wrap:wrap; gap:12px;">
        <div class="stat-card warning" style="flex:1; min-width:160px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #ffc107;">
            <div class="label" style="font-size:11px; color:#6c757d;">Hư Hỏng</div>
            <div class="value" style="font-size:18px; font-weight:700; color:#856404;"><?php echo $fmt($t['damage_qty'] ?? 0); ?></div>
        </div>
        <div class="stat-card" style="flex:1; min-width:160px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #6c757d;">
            <div class="label" style="font-size:11px; color:#6c757d;">Chuyển Kho</div>
            <div class="value" style="font-size:18px; font-weight:700; color:#6c757d;"><?php echo $fmt($t['transfer_out_qty'] ?? 0); ?></div>
        </div>
        <div class="stat-card danger" style="flex:1; min-width:160px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #dc3545;">
            <div class="label" style="font-size:11px; color:#6c757d;">Hết Hàng (Stockout)</div>
            <div class="value" style="font-size:18px; font-weight:700; color:#dc3545;"><?php echo $fmt($t['stockout_count'] ?? 0); ?> SKU</div>
        </div>
        <div class="stat-card warning" style="flex:1; min-width:160px; background:#f8fafc; border-radius:8px; padding:14px 16px; border-left:4px solid #ffc107;">
            <div class="label" style="font-size:11px; color:#6c757d;">Chậm Bán</div>
            <div class="value" style="font-size:18px; font-weight:700; color:#856404;"><?php echo $fmt($t['slow_moving_count'] ?? 0); ?> SKU</div>
        </div>
    </div>
</div>
