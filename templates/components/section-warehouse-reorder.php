<?php
/**
 * Component: Đề xuất mua hàng
 * Biến: $minmax
 */
if (!defined('ABSPATH')) exit;
$reorders = $minmax['reorder_suggestions'] ?? [];
?>
<?php if (!empty($reorders)): ?>
<div class="section">
    <div class="section-title" style="font-size:16px; font-weight:700; color:#1e3a5f; margin:0 0 12px 0; padding-bottom:8px; border-bottom:2px solid #17a2b8;">
        Đề Xuất Mua Hàng (<?php echo count($reorders); ?>)
    </div>

    <div class="alert alert-info" style="padding:12px 16px; border-radius:6px; background:#d1ecf1; color:#0c5460; border-left:4px solid #17a2b8; margin-bottom:12px;">
        Các đề xuất mua hàng tự động dựa trên MIN tồn kho và tốc độ tiêu thụ.
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table" style="width:100%; border-collapse:collapse; font-size:12px;">
            <tr>
                <th style="background:#d1ecf1; padding:8px; text-align:left; border-bottom:1px solid #dee2e6;">Shop</th>
                <th style="background:#d1ecf1; padding:8px; text-align:left; border-bottom:1px solid #dee2e6;">SKU</th>
                <th style="background:#d1ecf1; padding:8px; text-align:right; border-bottom:1px solid #dee2e6;">Tồn</th>
                <th style="background:#d1ecf1; padding:8px; text-align:right; border-bottom:1px solid #dee2e6;">MIN</th>
                <th style="background:#d1ecf1; padding:8px; text-align:right; border-bottom:1px solid #dee2e6;">Thiếu</th>
                <th style="background:#d1ecf1; padding:8px; text-align:center; border-bottom:1px solid #dee2e6;">Loại</th>
                <th style="background:#d1ecf1; padding:8px; text-align:center; border-bottom:1px solid #dee2e6;">TT</th>
            </tr>
            <?php foreach (array_slice($reorders, 0, 15) as $i => $r):
                $st = (int) ($r->status ?? 0); // 0=pending, 1=approved, 2=rejected, 3=completed
                $status_label = $st === 1 ? '✓ Duyệt' : ($st === 0 ? '⏳ Chờ' : ($st === 3 ? '✔ Xong' : '✗ Từ chối'));
                $status_bg = $st === 1 ? '#d4edda' : ($st === 0 ? '#fff3cd' : '#f0f0f0');
                $status_cl = $st === 1 ? '#155724' : ($st === 0 ? '#856404' : '#666');
                $action_label = ((int)($r->suggested_action ?? 1)) === 2 ? 'Chuyển kho' : 'Mua thêm';
            ?>
            <tr>
                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;"><?php echo esc_html($r->blog_name_cache ?? 'Shop #' . ($r->blog_id ?? '')); ?></td>
                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; font-weight:600;"><?php echo esc_html($r->product_sku ?? ''); ?></td>
                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right; color:#dc3545;"><?php echo $fmt($r->current_stock ?? 0); ?></td>
                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right;"><?php echo $fmt($r->min_qty ?? 0); ?></td>
                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:right; color:#dc3545; font-weight:700;"><?php echo $fmt($r->deficit_qty ?? 0); ?></td>
                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:center; font-size:11px;"><?php echo $action_label; ?></td>
                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; text-align:center;">
                    <span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; background:<?php echo $status_bg; ?>; color:<?php echo $status_cl; ?>;"><?php echo $status_label; ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php endif; ?>
