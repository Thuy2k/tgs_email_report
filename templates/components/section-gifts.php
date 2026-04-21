<?php
/**
 * Component: Tặng kèm theo shop
 * Biến: $gifts  — output của TGS_Collector_Shop_Gifts::collect()
 *        ['by_shop' => [...], 'summary' => [...]]
 */
if (!defined('ABSPATH')) exit;

$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };
$by_shop = $gifts['by_shop'] ?? [];
$sm      = $gifts['summary'] ?? [];

$shell_style = 'margin-bottom:20px; background:#ffffff; border:1px solid #e3ebf3; border-radius:26px; padding:18px; box-shadow:0 14px 32px rgba(20,46,79,0.07);';
$th_style = 'padding:5px 8px; font-size:10px; font-weight:700; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.6px; border-bottom:1px solid #e6edf4; background:#f7fafd;';
$td_style = 'padding:6px 8px; border-bottom:1px solid #f0f4f8; font-size:12px; color:#13273e;';
?>
<div style="<?php echo $shell_style; ?>">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:14px;">
        <tr>
            <td width="33%" style="vertical-align:middle;"></td>
            <td width="34%" style="vertical-align:middle; text-align:center;">
                <div style="font-size:24px; font-weight:700; color:#13273e; line-height:1.2;">Quà tặng</div>
            </td>
            <td width="33%" style="vertical-align:middle; text-align:right;">
                <div style="font-size:11px; color:#77889a;">(Sản phẩm tặng kèm trong đơn hàng)</div>
            </td>
        </tr>
    </table>
    <div style="text-align:right; margin-bottom:14px;">
        <span style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Tổng giá trị tặng: </span>
        <span style="font-size:18px; font-weight:700; color:#5a2d82;"><?php echo $fmt($sm['total_value'] ?? 0); ?>đ</span>
    </div>

    <!-- Summary row -->
    <?php if (!empty($by_shop)): ?>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:14px;">
        <tr>
            <td width="33%" style="padding-right:8px; vertical-align:top;">
                <div style="background:#f9f5ff; border:1px solid #e4d9f7; border-radius:14px; padding:10px 12px; text-align:center;">
                    <div style="font-size:10px; color:#7a5faa; text-transform:uppercase; letter-spacing:0.8px;">Đơn có tặng kèm</div>
                    <div style="font-size:22px; font-weight:700; color:#5a2d82; margin-top:4px;"><?php echo $fmt($sm['total_orders']); ?></div>
                </div>
            </td>
            <td width="33%" style="padding:0 4px; vertical-align:top;">
                <div style="background:#f9f5ff; border:1px solid #e4d9f7; border-radius:14px; padding:10px 12px; text-align:center;">
                    <div style="font-size:10px; color:#7a5faa; text-transform:uppercase; letter-spacing:0.8px;">Số lượng quà</div>
                    <div style="font-size:22px; font-weight:700; color:#5a2d82; margin-top:4px;"><?php echo $fmt($sm['total_qty']); ?></div>
                </div>
            </td>
            <td width="33%" style="padding-left:8px; vertical-align:top;">
                <div style="background:#f9f5ff; border:1px solid #e4d9f7; border-radius:14px; padding:10px 12px; text-align:center;">
                    <div style="font-size:10px; color:#7a5faa; text-transform:uppercase; letter-spacing:0.8px;">Shop có tặng kèm</div>
                    <div style="font-size:22px; font-weight:700; color:#5a2d82; margin-top:4px;"><?php echo $sm['shop_count']; ?></div>
                </div>
            </td>
        </tr>
    </table>
    <?php endif; ?>

    <!-- Per shop -->
    <?php if (empty($by_shop)): ?>
        <div style="padding:12px 16px; border-radius:6px; background:#d1ecf1; color:#0c5460; border-left:4px solid #17a2b8;">
            Không có đơn nào áp dụng tặng kèm trong khoảng thời gian này.
        </div>
    <?php else: ?>
    <?php foreach ($by_shop as $blog_id => $shop): ?>
    <div data-shop="gift-<?php echo (int)$blog_id; ?>" style="margin-bottom:12px; border:1px solid #e4d9f7; border-radius:18px; padding:12px 14px; background:#fdf9ff;">
        <span style="display:none">gift-<?php echo (int)$blog_id; ?></span>
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:10px;">
            <tr>
                <td style="vertical-align:top;">
                    <div style="font-size:15px; font-weight:700; color:#13273e;"><?php echo esc_html($shop['shop_name']); ?></div>
                    <div style="font-size:11px; color:#77889a; margin-top:3px;">
                        <?php echo $shop['order_count']; ?> đơn có tặng &nbsp;·&nbsp;
                        <?php echo $fmt($shop['gift_qty']); ?> sp tặng &nbsp;·&nbsp;
                        <strong style="color:#5a2d82;"><?php echo $fmt($shop['gift_value']); ?>đ</strong>
                    </div>
                </td>
            </tr>
        </table>

        <?php if (!empty($shop['items'])): ?>
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <th style="<?php echo $th_style; ?> text-align:left;">Sản phẩm tặng kèm</th>
                <th style="<?php echo $th_style; ?> text-align:left;">SKU</th>
                <th style="<?php echo $th_style; ?> text-align:right;">Số đơn</th>
                <th style="<?php echo $th_style; ?> text-align:right;">Số lượng</th>
                <th style="<?php echo $th_style; ?> text-align:right;">Giá trị</th>
            </tr>
            <?php foreach ($shop['items'] as $rank => $item): ?>
            <tr style="<?php echo $rank % 2 === 0 ? 'background:#fdf9ff;' : 'background:#fff;'; ?>">
                <td style="<?php echo $td_style; ?>"><?php echo esc_html($item['name']); ?></td>
                <td style="<?php echo $td_style; ?> color:#77889a; font-size:11px;"><?php echo esc_html($item['sku']); ?></td>
                <td style="<?php echo $td_style; ?> text-align:right; color:#77889a;"><?php echo $fmt($item['order_count']); ?></td>
                <td style="<?php echo $td_style; ?> text-align:right; font-weight:600; color:#5a2d82;"><?php echo $fmt($item['qty']); ?></td>
                <td style="<?php echo $td_style; ?> text-align:right; color:#77889a;"><?php echo $fmt($item['value']); ?>đ</td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
