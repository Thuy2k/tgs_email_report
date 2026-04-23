<?php
/**
 * Component: Tổng quan kho
 * Biến: $stock
 */
if (!defined('ABSPATH')) exit;
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };
$t = $stock['totals'] ?? [];
$shell = 'margin-bottom:20px; background:#ffffff; border:1px solid #e3ebf3; border-radius:26px; padding:18px; box-shadow:0 14px 32px rgba(20, 46, 79, 0.07);';
?>
<div class="section" style="<?php echo $shell; ?>">
    <div style="font-size:24px; font-weight:700; color:#13273e; margin-bottom:16px; line-height:1.2;">Tổng quan biến động</div>

    <!-- Hero: Giá trị tồn + Giá vốn bán -->
    <div style="background:linear-gradient(180deg, #f7fbff 0%, #edf5ff 100%); border:1px solid #dbe8f7; border-radius:22px; padding:16px 16px 14px; margin-bottom:16px;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td style="vertical-align:top; padding-right:8px;">
                    <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#6480a0; margin-bottom:6px;">Giá trị tồn cuối</div>
                    <div style="font-size:30px; font-weight:700; color:#0f2740; line-height:1.1;"><?php echo $fmt($t['closing_value'] ?? 0); ?>₫</div>
                </td>
                <td align="right" style="vertical-align:top;">
                    <span style="display:inline-block; padding:7px 11px; border-radius:999px; background:#ffffff; border:1px solid #dbe8f7; color:#284968; font-size:11px; font-weight:700;">
                        <?php echo $fmt($t['total_skus'] ?? 0); ?> SKU
                    </span>
                </td>
            </tr>
        </table>
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:14px;">
            <tr>
                <td width="50%" style="padding-right:8px; vertical-align:top;">
                    <div style="font-size:11px; color:#6d8198; text-transform:uppercase; letter-spacing:0.8px;">Tồn đầu kỳ</div>
                    <div style="font-size:16px; font-weight:700; color:#17324f; margin-top:4px;"><?php echo $fmt($t['opening_qty'] ?? 0); ?></div>
                </td>
                <td width="50%" style="padding-left:8px; vertical-align:top;">
                    <div style="font-size:11px; color:#6d8198; text-transform:uppercase; letter-spacing:0.8px;">Tồn cuối kỳ</div>
                    <div style="font-size:16px; font-weight:700; color:#17324f; margin-top:4px;"><?php echo $fmt($t['closing_qty'] ?? 0); ?></div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Biến động: Nhập / Bán / Chuyển kho / Hư hỏng -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:16px;">
        <tr>
            <td width="50%" style="padding:0 6px 10px 0; vertical-align:top;">
                <div style="background:#f3fbf6; border:1px solid #dcefe3; border-radius:18px; padding:14px 15px;">
                    <div style="font-size:11px; color:#6d8198; text-transform:uppercase; letter-spacing:0.8px;">Nhập hàng</div>
                    <div style="font-size:21px; font-weight:700; color:#1f8f4d; margin-top:4px;">+<?php echo $fmt(max(0, (float)($t['in_qty'] ?? 0) + (float)($t['adjustment_qty'] ?? 0))); ?></div>
                    <div style="font-size:11px; color:#77889a; margin-top:2px;"><?php echo $fmt(max(0, (float)($t['in_value'] ?? 0) + (float)($t['adjustment_value'] ?? 0))); ?>₫</div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 10px 6px; vertical-align:top;">
                <div style="background:#fff8f7; border:1px solid #f4dfdc; border-radius:18px; padding:14px 15px;">
                    <div style="font-size:11px; color:#9b6d68; text-transform:uppercase; letter-spacing:0.8px;">Bán ra</div>
                    <div style="font-size:21px; font-weight:700; color:#cf3d32; margin-top:4px;"><?php echo $fmt($t['out_qty'] ?? 0); ?></div>
                    <div style="font-size:11px; color:#77889a; margin-top:2px;"><?php echo $fmt($t['out_value'] ?? 0); ?>₫</div>
                </div>
            </td>
        </tr>
        <tr>
            <td width="50%" style="padding:0 6px 0 0; vertical-align:top;">
                <div style="background:#fbfcfe; border:1px solid #e6edf4; border-radius:18px; padding:14px 15px;">
                    <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Chuyển kho</div>
                    <div style="font-size:21px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo $fmt($t['transfer_out_qty'] ?? 0); ?></div>
                    <div style="font-size:11px; color:#77889a; margin-top:2px;"><?php echo $fmt($t['transfer_out_value'] ?? 0); ?>₫</div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 0 6px; vertical-align:top;">
                <div style="background:#fffcf5; border:1px solid #f0e5c9; border-radius:18px; padding:14px 15px;">
                    <div style="font-size:11px; color:#9b8968; text-transform:uppercase; letter-spacing:0.8px;">Hư hỏng</div>
                    <div style="font-size:21px; font-weight:700; color:#b8860b; margin-top:4px;"><?php echo $fmt($t['damage_qty'] ?? 0); ?></div>
                    <div style="font-size:11px; color:#77889a; margin-top:2px;"><?php echo $fmt($t['damage_value'] ?? 0); ?>₫</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Cảnh báo: Hết hàng / Chậm bán -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td width="50%" style="padding:0 6px 0 0; vertical-align:top;">
                <div style="background:#fff1f0; border:1px solid #f4dfdc; border-radius:18px; padding:14px 15px; text-align:center;">
                    <div style="font-size:11px; color:#9b6d68; text-transform:uppercase; letter-spacing:0.8px;">Hết hàng</div>
                    <div style="font-size:26px; font-weight:700; color:#cf3d32; margin-top:4px;"><?php echo $fmt($t['stockout_count'] ?? 0); ?></div>
                    <div style="font-size:11px; color:#77889a;">SKU</div>
                </div>
            </td>
            <td width="50%" style="padding:0 0 0 6px; vertical-align:top;">
                <div style="background:#fffcf5; border:1px solid #f0e5c9; border-radius:18px; padding:14px 15px; text-align:center;">
                    <div style="font-size:11px; color:#9b8968; text-transform:uppercase; letter-spacing:0.8px;">Chậm bán</div>
                    <div style="font-size:26px; font-weight:700; color:#b8860b; margin-top:4px;"><?php echo $fmt($t['slow_moving_count'] ?? 0); ?></div>
                    <div style="font-size:11px; color:#77889a;">SKU</div>
                </div>
            </td>
        </tr>
    </table>
</div>
