<?php
/**
 * TGS Email Sender — Core gửi email & lưu log
 *
 * 2 hàm chính:
 *   - send_shop_report($date_from, $date_to)
 *   - send_warehouse_report($date_from, $date_to)
 *
 * Mỗi hàm:  collect data → render HTML → wp_mail → log
 */

if (!defined('ABSPATH')) exit;

class TGS_Email_Sender
{
    /* ================================================================
     *  1) GỬI BÁO CÁO SHOP  (bán hàng + thu ngân hàng + MAX tại shop)
     * ================================================================ */
    public static function send_shop_report($date_from, $date_to, $triggered_by = 'manual', $user_id = 0, $override_recipients = null)
    {
        // ── Collect data từ các collector ──
        $sales_data     = TGS_Collector_Shop_Sales::collect($date_from, $date_to);
        $bank_data      = TGS_Collector_Shop_Bank::collect($date_from, $date_to);
        $max_data       = TGS_Collector_Shop_Max::collect($date_from, $date_to);
        $summary_data   = TGS_Collector_Summary::collect($date_from, $date_to);

        $all_data = [
            'sales'   => $sales_data,
            'bank'    => $bank_data,
            'max'     => $max_data,
            'summary' => $summary_data,
            'date_from' => $date_from,
            'date_to'   => $date_to,
        ];

        // ── Render HTML email ──
        $html = self::render_template('email-shop-report.php', $all_data);

        // ── Subject ──
        $subject = sprintf(
            '[TGS] Báo cáo bán hàng Shop — %s',
            ($date_from === $date_to)
                ? date('d/m/Y', strtotime($date_from))
                : date('d/m', strtotime($date_from)) . ' → ' . date('d/m/Y', strtotime($date_to))
        );

        // ── Recipients ──
        $recipients = $override_recipients ?: self::get_recipients(TGS_EMAIL_TYPE_SHOP);

        // ── Send ──
        return self::do_send($subject, $html, $recipients, TGS_EMAIL_TYPE_SHOP, $date_from, $date_to, $all_data, $triggered_by, $user_id);
    }

    /* ================================================================
     *  2) GỬI BÁO CÁO KHO  (MIN/MAX, tồn, cần mua, cảnh báo)
     * ================================================================ */
    public static function send_warehouse_report($date_from, $date_to, $triggered_by = 'manual', $user_id = 0, $override_recipients = null)
    {
        // ── Collect data từ các collector ──
        $minmax_data = TGS_Collector_Warehouse_MinMax::collect($date_from, $date_to);
        $stock_data  = TGS_Collector_Warehouse_Stock::collect($date_from, $date_to);
        $summary_data = TGS_Collector_Summary::collect($date_from, $date_to);

        $all_data = [
            'minmax'  => $minmax_data,
            'stock'   => $stock_data,
            'summary' => $summary_data,
            'date_from' => $date_from,
            'date_to'   => $date_to,
        ];

        // ── Render HTML email ──
        $html = self::render_template('email-warehouse-report.php', $all_data);

        // ── Subject ──
        $subject = sprintf(
            '[TGS] Báo cáo Kho — MIN/MAX & Tồn — %s',
            ($date_from === $date_to)
                ? date('d/m/Y', strtotime($date_from))
                : date('d/m', strtotime($date_from)) . ' → ' . date('d/m/Y', strtotime($date_to))
        );

        // ── Recipients ──
        $recipients = $override_recipients ?: self::get_recipients(TGS_EMAIL_TYPE_WAREHOUSE);

        // ── Send ──
        return self::do_send($subject, $html, $recipients, TGS_EMAIL_TYPE_WAREHOUSE, $date_from, $date_to, $all_data, $triggered_by, $user_id);
    }

    /* ================================================================
     *  CORE: Gửi email + lưu log
     * ================================================================ */
    private static function do_send($subject, $html, $recipients, $email_type, $date_from, $date_to, $data, $triggered_by, $user_id)
    {
        global $wpdb;

        $to_list = $recipients['to'] ?? [];
        $cc_list = $recipients['cc'] ?? [];

        // Không có người nhận → ghi log lỗi
        if (empty($to_list)) {
            $to_list = $cc_list;
            $cc_list = [];
        }
        if (empty($to_list)) {
            return ['success' => false, 'message' => 'Không có người nhận email. Vui lòng cấu hình danh sách recipients.'];
        }

        // Headers
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        foreach ($cc_list as $cc) {
            $headers[] = 'Cc: ' . sanitize_email($cc);
        }

        // Tạo log record trước (status = 0 = đang gửi)
        $now = current_time('mysql');
        $wpdb->insert(TGS_EMAIL_TABLE_LOG, [
            'email_type'        => $email_type,
            'subject'           => $subject,
            'recipients_to'     => wp_json_encode($to_list),
            'recipients_cc'     => wp_json_encode($cc_list),
            'date_from'         => $date_from,
            'date_to'           => $date_to,
            'blog_ids_json'     => wp_json_encode(array_keys($data['summary']['shops'] ?? [])),
            'data_json'         => wp_json_encode($data, JSON_UNESCAPED_UNICODE),
            'html_content'      => $html,
            'send_status'       => 0,
            'send_attempts'     => 1,
            'triggered_by'      => $triggered_by,
            'triggered_user_id' => $user_id,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
        $log_id = $wpdb->insert_id;

        // Gửi email bằng wp_mail
        $sent = wp_mail(
            array_map('sanitize_email', $to_list),
            $subject,
            $html,
            $headers
        );

        // Cập nhật status
        $error_msg = null;
        if (!$sent) {
            // Lấy lỗi chi tiết từ Resend API hoặc PHPMailer
            if (!empty($GLOBALS['tgs_resend_last_error'])) {
                $error_msg = $GLOBALS['tgs_resend_last_error'];
            } else {
                global $phpmailer;
                $error_msg = (isset($phpmailer) && is_object($phpmailer) && !empty($phpmailer->ErrorInfo))
                    ? $phpmailer->ErrorInfo
                    : 'wp_mail returned false';
            }
        }

        $wpdb->update(TGS_EMAIL_TABLE_LOG, [
            'send_status'   => $sent ? 1 : 2, // 1 = success, 2 = failed
            'error_message' => $error_msg,
            'updated_at'    => current_time('mysql'),
        ], ['log_id' => $log_id]);

        return [
            'success' => $sent,
            'log_id'  => $log_id,
            'message' => $sent
                ? 'Email đã gửi thành công!'
                : 'Gửi email thất bại. ' . ($error_msg ? 'Lỗi: ' . $error_msg : 'Kiểm tra cấu hình email.'),
        ];
    }

    /* ================================================================
     *  GỬI LẠI — Resend từ log cũ hoặc gửi lại khoảng ngày
     * ================================================================ */
    public static function resend_by_log_id($log_id)
    {
        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . TGS_EMAIL_TABLE_LOG . " WHERE log_id = %d",
            $log_id
        ));
        if (!$log) {
            return ['success' => false, 'message' => 'Không tìm thấy log #' . $log_id];
        }

        // Gửi lại cùng type & date range
        if ($log->email_type === TGS_EMAIL_TYPE_SHOP) {
            return self::send_shop_report($log->date_from, $log->date_to, 'resend', get_current_user_id());
        }
        return self::send_warehouse_report($log->date_from, $log->date_to, 'resend', get_current_user_id());
    }

    /* ================================================================
     *  Lấy danh sách recipients
     * ================================================================ */
    public static function get_recipients($email_type)
    {
        global $wpdb;
        $table = TGS_EMAIL_TABLE_RECIPIENTS;

        // Kiểm tra có recipient nào được cấu hình không (kể cả inactive)
        $total_configured = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table}"
        );

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT email, display_name, role_label FROM {$table}
             WHERE is_active = 1
             AND (email_types IS NULL OR JSON_CONTAINS(email_types, %s))",
            '"' . $email_type . '"'
        ));

        $to = [];
        $cc = [];
        foreach ($rows as $r) {
            // Người đầu tiên → to, còn lại → cc
            if (empty($to)) {
                $to[] = $r->email;
            } else {
                $cc[] = $r->email;
            }
        }

        // Fallback: chỉ dùng admin email khi CHƯA cấu hình recipient nào
        // Nếu đã cấu hình nhưng tất cả đều tắt → không gửi
        if (empty($to) && $total_configured === 0) {
            $to[] = get_option('admin_email');
        }

        return ['to' => $to, 'cc' => $cc];
    }

    /* ================================================================
     *  Render template
     * ================================================================ */
    public static function render_template($template_file, $data = [])
    {
        $path = TGS_EMAIL_REPORT_PLUGIN_DIR . 'templates/' . $template_file;
        if (!file_exists($path)) {
            return '<p>Template not found: ' . esc_html($template_file) . '</p>';
        }

        ob_start();
        extract($data, EXTR_SKIP);
        include $path;
        return ob_get_clean();
    }

    /* ================================================================
     *  Lấy lịch sử gửi email
     * ================================================================ */
    public static function get_logs($page = 1, $per_page = 20, $email_type = '')
    {
        global $wpdb;
        $table = TGS_EMAIL_TABLE_LOG;

        $where = '1=1';
        $params = [];
        if ($email_type) {
            $where .= ' AND email_type = %s';
            $params[] = $email_type;
        }

        $offset = ($page - 1) * $per_page;

        $total = $wpdb->get_var(
            $params
                ? $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}", ...$params)
                : "SELECT COUNT(*) FROM {$table} WHERE {$where}"
        );

        $sql = "SELECT log_id, email_type, subject, recipients_to, recipients_cc,
                       date_from, date_to, send_status, send_attempts, error_message,
                       triggered_by, created_at
                FROM {$table}
                WHERE {$where}
                ORDER BY created_at DESC
                LIMIT %d OFFSET %d";

        $params[] = $per_page;
        $params[] = $offset;

        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        return [
            'rows'     => $rows,
            'total'    => (int) $total,
            'page'     => $page,
            'per_page' => $per_page,
        ];
    }

    /**
     * Lấy chi tiết 1 log (bao gồm HTML content)
     */
    public static function get_log_detail($log_id)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . TGS_EMAIL_TABLE_LOG . " WHERE log_id = %d",
            $log_id
        ));
    }
}
