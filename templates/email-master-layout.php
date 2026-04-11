<?php
/**
 * Email Master Layout — Responsive wrapper cho tất cả email
 *
 * Biến có sẵn: $subject, $body_content, $date_from, $date_to
 * Hỗ trợ: PC, Tablet, Mobile
 */
if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html lang="vi" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo esc_html($subject ?? 'TGS Email Report'); ?></title>
    <style type="text/css">
        /* Reset */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; background-color: #f4f6f9; }

        /* Main */
        .email-wrapper { width: 100%; max-width: 800px; margin: 0 auto; background: #ffffff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .email-header { background: linear-gradient(135deg, #1e3a5f 0%, #2d5f8a 100%); color: #ffffff; padding: 24px 30px; }
        .email-header h1 { margin: 0; font-size: 22px; font-weight: 600; }
        .email-header .sub { color: #b0d0f0; font-size: 13px; margin-top: 6px; }
        .email-body { padding: 24px 30px; color: #333333; font-size: 14px; line-height: 1.6; }
        .email-footer { background: #f8f9fa; padding: 20px 30px; text-align: center; color: #888; font-size: 12px; border-top: 1px solid #e9ecef; }

        /* Section */
        .section { margin-bottom: 28px; }
        .section-title { font-size: 16px; font-weight: 700; color: #1e3a5f; margin: 0 0 12px 0; padding-bottom: 8px; border-bottom: 2px solid #2d5f8a; display: flex; align-items: center; gap: 8px; }
        .section-title .icon { font-size: 18px; }

        /* Table */
        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 12px; }
        .data-table th { background: #f0f4f8; color: #1e3a5f; padding: 10px 8px; text-align: left; font-weight: 600; border-bottom: 2px solid #dee2e6; white-space: nowrap; }
        .data-table td { padding: 8px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
        .data-table tr:hover td { background: #f8fafc; }
        .data-table .text-right { text-align: right; }
        .data-table .text-center { text-align: center; }
        .data-table .font-bold { font-weight: 700; }
        .data-table .total-row td { background: #f0f4f8; font-weight: 700; border-top: 2px solid #dee2e6; }

        /* Cards */
        .stats-row { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
        .stat-card { flex: 1; min-width: 140px; background: #f8fafc; border-radius: 8px; padding: 14px 16px; border-left: 4px solid #2d5f8a; }
        .stat-card .label { font-size: 11px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .stat-card .value { font-size: 20px; font-weight: 700; color: #1e3a5f; }
        .stat-card .sub { font-size: 11px; color: #888; margin-top: 2px; }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.success .value { color: #28a745; }
        .stat-card.danger { border-left-color: #dc3545; }
        .stat-card.danger .value { color: #dc3545; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.warning .value { color: #856404; }
        .stat-card.info { border-left-color: #17a2b8; }

        /* Badges */
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }

        /* Alert */
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 12px; font-size: 13px; }
        .alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .alert-warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        .alert-info { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }

        /* Responsive */
        @media screen and (max-width: 600px) {
            .email-wrapper { width: 100% !important; }
            .email-header, .email-body, .email-footer { padding: 16px !important; }
            .email-header h1 { font-size: 18px !important; }
            .data-table { font-size: 11px !important; }
            .data-table th, .data-table td { padding: 6px 4px !important; }
            .section-title { font-size: 14px !important; }
        }

        @media screen and (max-width: 480px) {
            .data-table .hide-mobile { display: none !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f9;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f4f6f9;">
        <tr>
            <td align="center" style="padding: 20px 10px;">
                <div class="email-wrapper" style="max-width:800px; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">

                    <!-- Header -->
                    <div class="email-header">
                        <h1><?php echo esc_html($subject ?? 'Báo Cáo TGS'); ?></h1>
                        <div class="sub">
                            <?php
                            $df = $date_from ?? '';
                            $dt = $date_to ?? '';
                            if ($df && $dt) {
                                if ($df === $dt) {
                                    echo 'Ngày ' . date('d/m/Y', strtotime($df));
                                } else {
                                    echo 'Từ ' . date('d/m/Y', strtotime($df)) . ' → ' . date('d/m/Y', strtotime($dt));
                                }
                            }
                            ?>
                            &nbsp;|&nbsp; Gửi lúc <?php echo current_time('H:i d/m/Y'); ?>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="email-body">
                        <?php echo $body_content ?? ''; ?>
                    </div>

                    <!-- Footer -->
                    <div class="email-footer">
                        <p style="margin:0;">TGS Shop Management System — Email tự động, vui lòng không reply.</p>
                        <p style="margin:4px 0 0; color:#aaa;">© <?php echo date('Y'); ?> TGS. Powered by TGS Email Report Plugin.</p>
                    </div>

                </div>
            </td>
        </tr>
    </table>
</body>
</html>
