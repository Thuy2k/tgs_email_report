<?php
/**
 * Component: Tồn kho theo shop
 * Biến: $stock
 */
if (!defined('ABSPATH')) exit;
$fmt  = function($v) { return number_format((float)$v, 0, ',', '.'); };
$shops = $stock['by_shop'] ?? [];
$t = $stock['totals'] ?? [];
$shell = 'margin-bottom:20px; background:#ffffff; border:1px solid #e3ebf3; border-radius:26px; padding:18px; box-shadow:0 14px 32px rgba(20, 46, 79, 0.07);';
$total_closing_value = max((float) ($t['closing_value'] ?? 1), 1);
?>
<div class="section" style="<?php echo $shell; ?>">
    <div style="font-size:24px; font-weight:700; color:#13273e; line-height:1.2; margin-bottom:16px;">Tồn kho theo shop</div>

    <?php if (empty($shops)): ?>
        <div style="padding:14px 16px; border-radius:18px; background:#eef5ff; color:#284968; border:1px solid #dbe8f7;">
            Chưa có dữ liệu tồn kho rollup.
        </div>
    <?php else: ?>
        <!-- Summary cards -->
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:16px;">
            <tr>
                <td width="50%" style="padding:0 6px 10px 0; vertical-align:top;">
                    <div style="background:#fbfcfe; border:1px solid #e5edf5; border-radius:18px; padding:14px 15px;">
                        <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Tổng SKU</div>
                        <div style="font-size:21px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo $fmt($t['total_skus'] ?? 0); ?></div>
                    </div>
                </td>
                <td width="50%" style="padding:0 0 10px 6px; vertical-align:top;">
                    <div style="background:#fbfcfe; border:1px solid #e5edf5; border-radius:18px; padding:14px 15px;">
                        <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Giá trị tồn cuối</div>
                        <div style="font-size:21px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo $fmt($t['closing_value'] ?? 0); ?>₫</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Per-shop cards -->
        <?php foreach ($shops as $bid => $s):
            $share = round(((float) ($s['closing_value'] ?? 0) / $total_closing_value) * 100, 1);
            $bar_width = $share > 0 ? max(8, min(100, $share)) : 0;
        ?>
        <div data-shop="<?php echo (int)$bid; ?>" style="margin-bottom:12px; border:1px solid #e6edf4; border-radius:22px; padding:14px 15px; background:#fcfdff;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="vertical-align:top; padding-right:8px;">
                        <div style="font-size:16px; font-weight:700; color:#13273e;"><?php echo esc_html($s['shop_name']); ?></div>
                        <div style="font-size:11px; color:#77889a; margin-top:4px;"><?php echo (int) $s['total_skus']; ?> SKU<?php if (($s['stockout_count'] ?? 0) > 0): ?> · <span style="color:#cf3d32; font-weight:600;"><?php echo (int) $s['stockout_count']; ?> hết hàng</span><?php endif; ?></div>
                    </td>
                    <td align="right" style="vertical-align:top; white-space:nowrap;">
                        <div style="font-size:12px; color:#75879a;"><?php echo number_format($share, 1, ',', '.'); ?>% tổng giá trị</div>
                        <div style="font-size:14px; font-weight:700; color:#13273e; margin-top:4px;"><span style="font-size:11px; color:#77889a; font-weight:400;">Tồn cuối: </span><?php echo $fmt($s['closing_value']); ?>₫</div>
                    </td>
                </tr>
            </table>

            <!-- Progress bar -->
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:10px; table-layout:fixed; border-collapse:separate;">
                <tr>
                    <td width="<?php echo $bar_width; ?>%" style="height:8px; background:#2d5f8a; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                    <td width="<?php echo max(0, 100 - $bar_width); ?>%" style="height:8px; background:#e8eef5; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                </tr>
            </table>

            <!-- Metrics row -->
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:10px;">
                <tr>
                    <td width="20%" style="padding-right:4px; vertical-align:top;">
                        <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.5px;">Tồn đầu</div>
                            <div style="font-size:13px; font-weight:700; color:#13273e; margin-top:3px;"><?php echo $fmt(max(0, (float) ($s['opening_qty'] ?? 0))); ?></div>
                    </td>
                    <td width="20%" style="padding:0 4px; vertical-align:top;">
                        <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.5px;">Nhập</div>
                        <div style="font-size:13px; font-weight:700; color:#1f8f4d; margin-top:3px;">+<?php echo $fmt(max(0, (float)($s['in_qty'] ?? 0) + (float)($s['adjustment_qty'] ?? 0))); ?></div>
                    </td>
                    <td width="20%" style="padding:0 4px; vertical-align:top;">
                        <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.5px;">Bán</div>
                        <div style="font-size:13px; font-weight:700; color:#cf3d32; margin-top:3px;"><?php echo $fmt($s['out_qty']); ?></div>
                    </td>
                    <td width="20%" style="padding:0 4px; vertical-align:top;">
                        <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.5px;">C.Kho</div>
                        <div style="font-size:13px; font-weight:700; color:#13273e; margin-top:3px;"><?php echo $fmt($s['transfer_out_qty']); ?></div>
                    </td>
                    <td width="20%" style="padding-left:4px; vertical-align:top;">
                        <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.5px;">Tồn cuối</div>
                        <div style="font-size:13px; font-weight:700; color:#13273e; margin-top:3px;"><?php echo $fmt($s['closing_qty']); ?></div>
                    </td>
                </tr>
            </table>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
