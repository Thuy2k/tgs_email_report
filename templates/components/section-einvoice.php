<?php
/**
 * Component: Báo cáo xuất hóa đơn điện tử theo shop
 * Biến: $einvoice
 */
if (!defined('ABSPATH')) exit;

$einvoice = is_array($einvoice ?? null) ? $einvoice : [];
$summary = $einvoice['summary'] ?? [];
$shops = $einvoice['by_shop'] ?? [];
$fmt = function($v) { return number_format((float) $v, 0, ',', '.'); };
$shell = 'margin-bottom:20px; background:#ffffff; border:1px solid #e3ebf3; border-radius:26px; padding:18px; box-shadow:0 14px 32px rgba(20, 46, 79, 0.07);';

$report_df = !empty($date_from ?? '') ? (string) $date_from : current_time('Y-m-d');
$report_dt = !empty($date_to ?? '') ? (string) $date_to : $report_df;
$build_einv_url = static function ($blog_id, array $extra = []) use ($report_df, $report_dt) {
    // Ưu tiên route tích hợp trong tgs-shop-management; fallback về page standalone tgs-einvoice.
    $default_page = class_exists('TGS_Shop_Management') ? 'tgs-shop-management' : 'tgs-einvoice';
    $page = apply_filters('tgs_email_report_einvoice_detail_page', $default_page, (int) $blog_id);
    $view = apply_filters('tgs_email_report_einvoice_detail_view', 'einvoice-list', (int) $blog_id, $page);

    $args = array_merge([
        'page' => $page,
        'df'   => $report_df,
        'dt'   => $report_dt,
    ], $extra);

    if ($page === 'tgs-shop-management' && !empty($view)) {
        $args['view'] = $view;
    }

    return add_query_arg($args, get_admin_url((int) $blog_id, 'admin.php'));
};
?>
<div class="section" style="<?php echo $shell; ?>">
    <div style="font-size:24px; font-weight:700; color:#13273e; line-height:1.2; margin-bottom:16px;">Báo cáo xuất hóa đơn điện tử</div>

    <?php if (empty($shops)): ?>
        <div style="padding:14px 16px; border-radius:18px; background:#eef5ff; color:#284968; border:1px solid #dbe8f7;">
            Chưa có dữ liệu hóa đơn điện tử trong khoảng thời gian này.
        </div>
    <?php else: ?>
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom:16px;">
            <tr>
                <td width="33.33%" style="padding:0 6px 10px 0; vertical-align:top;">
                    <div style="background:#fbfcfe; border:1px solid #e5edf5; border-radius:18px; padding:14px 15px;">
                        <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Tổng đơn hàng</div>
                        <div style="font-size:21px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo $fmt($summary['total_orders'] ?? 0); ?></div>
                    </div>
                </td>
                <td width="33.33%" style="padding:0 6px 10px; vertical-align:top;">
                    <div style="background:#f3fbf6; border:1px solid #dcefe3; border-radius:18px; padding:14px 15px;">
                        <div style="font-size:11px; color:#6b8a76; text-transform:uppercase; letter-spacing:0.8px;">Đã xuất HĐĐT</div>
                        <div style="font-size:21px; font-weight:700; color:#1f8f4d; margin-top:4px;"><?php echo $fmt($summary['invoiced_orders'] ?? 0); ?></div>
                    </div>
                </td>
                <td width="33.33%" style="padding:0 0 10px 6px; vertical-align:top;">
                    <div style="background:#fff8f7; border:1px solid #f4dfdc; border-radius:18px; padding:14px 15px;">
                        <div style="font-size:11px; color:#9b6d68; text-transform:uppercase; letter-spacing:0.8px;">Chưa xuất HĐĐT</div>
                        <div style="font-size:21px; font-weight:700; color:#cf3d32; margin-top:4px;"><?php echo $fmt($summary['not_invoiced_orders'] ?? 0); ?></div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="33.33%" style="padding:0 6px 0 0; vertical-align:top;">
                    <div style="background:#fbfcfe; border:1px solid #e5edf5; border-radius:18px; padding:14px 15px;">
                        <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Có SP &lt;24 tháng</div>
                        <div style="font-size:21px; font-weight:700; color:#d97706; margin-top:4px;"><?php echo $fmt($summary['under24_count'] ?? 0); ?></div>
                    </div>
                </td>
                <td width="33.33%" style="padding:0 6px; vertical-align:top;">
                    <div style="background:#fbfcfe; border:1px solid #e5edf5; border-radius:18px; padding:14px 15px;">
                        <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Đã tách quá &lt;24 tháng</div>
                        <div style="font-size:21px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo $fmt($summary['split_under24_count'] ?? 0); ?></div>
                    </div>
                </td>
                <td width="33.33%" style="padding:0 0 0 6px; vertical-align:top;">
                    <div style="background:#fbfcfe; border:1px solid #e5edf5; border-radius:18px; padding:14px 15px;">
                        <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Không có SP &lt;24 tháng</div>
                        <div style="font-size:21px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo $fmt($summary['no_under24_count'] ?? 0); ?></div>
                    </div>
                </td>
            </tr>
        </table>

        <div style="margin-bottom:12px; padding:10px 12px; border-radius:12px; background:#f6f9fc; border:1px solid #e5edf5; color:#4b637a; font-size:12px;">
            Tỷ lệ xuất HĐĐT: <strong style="color:#13273e;"><?php echo number_format((float) ($summary['coverage_pct'] ?? 0), 1, ',', '.'); ?>%</strong>
            · Đã gửi: <strong><?php echo $fmt($summary['sent_orders'] ?? 0); ?></strong>
            · Ký demo: <strong><?php echo $fmt($summary['demo_signed_orders'] ?? 0); ?></strong>
            · Lỗi: <strong><?php echo $fmt($summary['failed_orders'] ?? 0); ?></strong>
            · Yêu cầu hủy: <strong><?php echo $fmt($summary['cancel_requested_orders'] ?? 0); ?></strong>
            · Đã hủy: <strong><?php echo $fmt($summary['canceled_orders'] ?? 0); ?></strong>
            · Đã tách quà &lt;24m: <strong><?php echo $fmt($summary['split_under24_count'] ?? 0); ?></strong>
        </div>

        <?php foreach ($shops as $bid => $s):
            $coverage = (float) ($s['coverage_pct'] ?? 0);
            $bar_width = $coverage > 0 ? max(8, min(100, $coverage)) : 0;
            $shop_url_all      = $build_einv_url($bid);
        ?>
        <div data-shop="<?php echo (int) $bid; ?>" style="margin-bottom:12px; border:1px solid #e6edf4; border-radius:22px; padding:14px 15px; background:#fcfdff;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="vertical-align:top; padding-right:8px;">
                        <div style="font-size:16px; font-weight:700; color:#13273e;"><?php echo esc_html($s['shop_name'] ?? ('Shop #' . $bid)); ?></div>
                        <div style="font-size:11px; color:#77889a; margin-top:4px;">
                            <?php echo esc_html($s['shop_code'] ?? ''); ?>
                            <?php if (!empty($s['shop_code'])): ?> | <?php endif; ?>
                            Tổng đơn: <?php echo (int) ($s['total_orders'] ?? 0); ?>
                        </div>
                    </td>
                    <td align="right" style="vertical-align:top; white-space:nowrap;">
                        <div style="font-size:11px; color:#77889a; text-transform:uppercase; letter-spacing:0.8px;">Đã xuất HĐĐT</div>
                        <div style="font-size:21px; font-weight:700; color:#1f8f4d; margin-top:4px;"><?php echo $fmt($s['invoiced_orders'] ?? 0); ?></div>
                    </td>
                </tr>
            </table>

            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:10px; table-layout:fixed; border-collapse:separate;">
                <tr>
                    <td width="<?php echo $bar_width; ?>%" style="height:8px; background:#2d5f8a; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                    <td width="<?php echo max(0, 100 - $bar_width); ?>%" style="height:8px; background:#e8eef5; border-radius:999px; font-size:0; line-height:0;">&nbsp;</td>
                </tr>
            </table>

            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:10px;">
                <tr>
                    <td width="20%" style="padding-right:6px; vertical-align:top;">
                        <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.8px;">Tỷ lệ xuất</div>
                        <div style="font-size:14px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo number_format($coverage, 1, ',', '.'); ?>%</div>
                    </td>
                    <td width="16%" style="padding:0 6px; vertical-align:top;">
                        <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.8px;">Chưa xuất</div>
                        <div style="font-size:14px; font-weight:700; color:#cf3d32; margin-top:4px;"><?php echo $fmt($s['not_invoiced_orders'] ?? 0); ?></div>
                    </td>
                    <td width="16%" style="padding:0 6px; vertical-align:top;">
                        <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.8px;">Chờ xử lý</div>
                        <div style="font-size:14px; font-weight:700; color:#d97706; margin-top:4px;"><?php echo $fmt($s['pending_orders'] ?? 0); ?></div>
                    </td>
                    <td width="16%" style="padding:0 6px; vertical-align:top;">
                        <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.8px;">Có quà</div>
                        <div style="font-size:14px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo $fmt($s['include_gifts_count'] ?? 0); ?></div>
                    </td>
                    <td width="16%" style="padding:0 6px; vertical-align:top;">
                        <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.8px;">SP <24m</div>
                        <div style="font-size:14px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo $fmt($s['under24_count'] ?? 0); ?></div>
                    </td>
                    <td width="16%" style="padding-left:6px; vertical-align:top;">
                        <div style="font-size:10px; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.8px;">Trạng thái lỗi</div>
                        <div style="font-size:14px; font-weight:700; color:#cf3d32; margin-top:4px;">
                            <?php echo $fmt(($s['failed_orders'] ?? 0) + ($s['cancel_requested_orders'] ?? 0)); ?>
                        </div>
                    </td>
                </tr>
            </table>

            <div style="margin-top:10px; padding:12px; border-radius:16px; background:#f7fafc; border:1px solid #e5edf5;">
                <div style="font-size:13px; font-weight:700; color:#37526d; margin-bottom:10px;">Trạng thái chi tiết</div>
                <?php
                    $status_rows = [
                        ['label' => 'Đã gửi', 'prefix' => 'sent'],
                        ['label' => 'Ký demo', 'prefix' => 'demo_signed'],
                        ['label' => 'Chờ xử lý', 'prefix' => 'pending'],
                        ['label' => 'Lỗi', 'prefix' => 'failed'],
                        ['label' => 'Yêu cầu hủy', 'prefix' => 'cancel_requested'],
                        ['label' => 'Đã hủy', 'prefix' => 'canceled'],
                    ];
                ?>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse:collapse;">
                    <tr style="background:#f0f4f8;">
                        <td style="padding:6px 8px; font-size:10px; font-weight:700; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.5px;">Trạng thái</td>
                        <td align="right" style="padding:6px 8px; font-size:10px; font-weight:700; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.5px;">Số đơn</td>
                        <td align="right" style="padding:6px 8px; font-size:10px; font-weight:700; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.5px;">Tổng giá trị</td>
                        <td align="right" style="padding:6px 8px; font-size:10px; font-weight:700; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.5px;">Có &lt;24M</td>
                        <td align="right" style="padding:6px 8px; font-size:10px; font-weight:700; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.5px;">Tách &lt;24M</td>
                        <td align="right" style="padding:6px 8px; font-size:10px; font-weight:700; color:#7a8d9f; text-transform:uppercase; letter-spacing:0.5px;">Không &lt;24M</td>
                    </tr>
                    <?php $si = 0; foreach ($status_rows as $row): ?>
                    <?php
                        $bg = ($si % 2 === 0) ? '#ffffff' : '#f9fbfd';
                        $si++;
                        $p = $row['prefix'];
                    ?>
                    <tr style="background:<?php echo $bg; ?>; border-top:1px solid #eef2f6;">
                        <td style="padding:8px 8px; vertical-align:top;">
                            <div style="font-size:12px; font-weight:600; color:#13273e;"><?php echo esc_html($row['label']); ?></div>
                        </td>
                        <td align="right" style="padding:8px 8px; vertical-align:top; white-space:nowrap; font-size:12px; font-weight:700; color:#13273e;">
                            <?php echo $fmt($s[$p . '_orders'] ?? 0); ?>
                        </td>
                        <td align="right" style="padding:8px 8px; vertical-align:top; white-space:nowrap; font-size:12px; font-weight:700; color:#13273e;">
                            <?php echo $fmt($s[$p . '_value'] ?? 0); ?>đ
                        </td>
                        <td align="right" style="padding:8px 8px; vertical-align:top; white-space:nowrap; font-size:12px; font-weight:700; color:#13273e;">
                            <?php echo $fmt($s[$p . '_under24_count'] ?? 0); ?>
                        </td>
                        <td align="right" style="padding:8px 8px; vertical-align:top; white-space:nowrap; font-size:12px; font-weight:700; color:#13273e;">
                            <?php echo $fmt($s[$p . '_split_under24_count'] ?? 0); ?>
                        </td>
                        <td align="right" style="padding:8px 8px; vertical-align:top; white-space:nowrap; font-size:12px; font-weight:700; color:#13273e;">
                            <?php echo $fmt($s[$p . '_no_under24_count'] ?? 0); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div style="margin-top:10px; text-align:right;">
                <a href="<?php echo esc_url($shop_url_all); ?>" target="_blank" style="display:inline-block; padding:8px 12px; border-radius:10px; border:1px solid #d7e4f1; background:#f2f7fd; color:#2d5f8a; text-decoration:none; font-size:12px; font-weight:700;">Xem chi tiết</a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
