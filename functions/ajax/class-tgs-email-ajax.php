<?php
/**
 * TGS Email AJAX — Xử lý tất cả AJAX actions cho email report
 *
 * Actions:
 *   tgs_email_send_shop       — Gửi báo cáo shop
 *   tgs_email_send_warehouse  — Gửi báo cáo kho
 *   tgs_email_send_all        — Gửi cả 2 loại
 *   tgs_email_get_logs        — Lấy lịch sử gửi
 *   tgs_email_get_log_detail  — Xem chi tiết 1 email đã gửi
 *   tgs_email_resend          — Gửi lại từ log
 *   tgs_email_save_recipients — Lưu danh sách recipients
 *   tgs_email_get_recipients  — Lấy danh sách recipients
 *   tgs_email_preview         — Preview email (không gửi)
 */

if (!defined('ABSPATH')) exit;

class TGS_Email_Ajax
{
    public static function register()
    {
        $actions = [
            'tgs_email_send_shop',
            'tgs_email_send_warehouse',
            'tgs_email_send_all',
            'tgs_email_get_logs',
            'tgs_email_get_log_detail',
            'tgs_email_resend',
            'tgs_email_save_recipients',
            'tgs_email_get_recipients',
            'tgs_email_delete_recipient',
            'tgs_email_preview',
        ];

        foreach ($actions as $action) {
            $method = str_replace('tgs_email_', 'handle_', $action);
            add_action('wp_ajax_' . $action, [__CLASS__, $method]);
        }
    }

    /* ── Security check ── */
    private static function check()
    {
        check_ajax_referer('tgs_email_report_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Không có quyền truy cập']);
        }
    }

    /* ── Parse date range ── */
    private static function parse_dates()
    {
        $date_from = sanitize_text_field($_POST['date_from'] ?? current_time('Y-m-d'));
        $date_to   = sanitize_text_field($_POST['date_to'] ?? current_time('Y-m-d'));

        // Validate format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
            wp_send_json_error(['message' => 'Định dạng ngày không hợp lệ (YYYY-MM-DD)']);
        }

        return [$date_from, $date_to];
    }

    /* ════════════════════════════════════════════
     *  GỬI BÁO CÁO SHOP
     * ════════════════════════════════════════════ */
    public static function handle_send_shop()
    {
        self::check();
        list($date_from, $date_to) = self::parse_dates();

        $result = TGS_Email_Sender::send_shop_report(
            $date_from,
            $date_to,
            'manual',
            get_current_user_id()
        );

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /* ════════════════════════════════════════════
     *  GỬI BÁO CÁO KHO
     * ════════════════════════════════════════════ */
    public static function handle_send_warehouse()
    {
        self::check();
        list($date_from, $date_to) = self::parse_dates();

        $result = TGS_Email_Sender::send_warehouse_report(
            $date_from,
            $date_to,
            'manual',
            get_current_user_id()
        );

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /* ════════════════════════════════════════════
     *  GỬI CẢ 2 BÁO CÁO
     * ════════════════════════════════════════════ */
    public static function handle_send_all()
    {
        self::check();
        list($date_from, $date_to) = self::parse_dates();

        $uid = get_current_user_id();
        $shop_result = TGS_Email_Sender::send_shop_report($date_from, $date_to, 'manual', $uid);
        $wh_result   = TGS_Email_Sender::send_warehouse_report($date_from, $date_to, 'manual', $uid);

        wp_send_json_success([
            'shop_report'      => $shop_result,
            'warehouse_report' => $wh_result,
            'message'          => sprintf(
                'Shop: %s | Kho: %s',
                $shop_result['success'] ? '✓ OK' : '✗ Lỗi',
                $wh_result['success'] ? '✓ OK' : '✗ Lỗi'
            ),
        ]);
    }

    /* ════════════════════════════════════════════
     *  PREVIEW — Render email HTML (không gửi)
     * ════════════════════════════════════════════ */
    public static function handle_preview()
    {
        self::check();
        list($date_from, $date_to) = self::parse_dates();

        $type = sanitize_text_field($_POST['email_type'] ?? 'shop_report');

        if ($type === TGS_EMAIL_TYPE_WAREHOUSE) {
            $minmax   = TGS_Collector_Warehouse_MinMax::collect($date_from, $date_to);
            $stock    = TGS_Collector_Warehouse_Stock::collect($date_from, $date_to);
            $summary  = TGS_Collector_Summary::collect($date_from, $date_to);
            $html = TGS_Email_Sender::render_template('email-warehouse-report.php', [
                'minmax' => $minmax, 'stock' => $stock, 'summary' => $summary,
                'date_from' => $date_from, 'date_to' => $date_to,
            ]);
        } else {
            $sales   = TGS_Collector_Shop_Sales::collect($date_from, $date_to);
            $bank    = TGS_Collector_Shop_Bank::collect($date_from, $date_to);
            $max     = TGS_Collector_Shop_Max::collect($date_from, $date_to);
            $summary = TGS_Collector_Summary::collect($date_from, $date_to);
            $html = TGS_Email_Sender::render_template('email-shop-report.php', [
                'sales' => $sales, 'bank' => $bank, 'max' => $max, 'summary' => $summary,
                'date_from' => $date_from, 'date_to' => $date_to,
            ]);
        }

        wp_send_json_success(['html' => $html]);
    }

    /* ════════════════════════════════════════════
     *  LỊCH SỬ GỬI
     * ════════════════════════════════════════════ */
    public static function handle_get_logs()
    {
        self::check();
        $page = max(1, (int) ($_POST['page'] ?? 1));
        $type = sanitize_text_field($_POST['email_type'] ?? '');
        $logs = TGS_Email_Sender::get_logs($page, 20, $type);
        wp_send_json_success($logs);
    }

    public static function handle_get_log_detail()
    {
        self::check();
        $log_id = (int) ($_POST['log_id'] ?? 0);
        $log = TGS_Email_Sender::get_log_detail($log_id);
        if (!$log) {
            wp_send_json_error(['message' => 'Không tìm thấy log #' . $log_id]);
        }
        wp_send_json_success(['log' => $log]);
    }

    /* ════════════════════════════════════════════
     *  GỬI LẠI
     * ════════════════════════════════════════════ */
    public static function handle_resend()
    {
        self::check();
        $log_id = (int) ($_POST['log_id'] ?? 0);

        if ($log_id > 0) {
            $result = TGS_Email_Sender::resend_by_log_id($log_id);
        } else {
            // Gửi lại theo date range
            list($date_from, $date_to) = self::parse_dates();
            $type = sanitize_text_field($_POST['email_type'] ?? 'shop_report');
            $uid = get_current_user_id();

            if ($type === TGS_EMAIL_TYPE_WAREHOUSE) {
                $result = TGS_Email_Sender::send_warehouse_report($date_from, $date_to, 'resend', $uid);
            } else {
                $result = TGS_Email_Sender::send_shop_report($date_from, $date_to, 'resend', $uid);
            }
        }

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /* ════════════════════════════════════════════
     *  QUẢN LÝ RECIPIENTS
     * ════════════════════════════════════════════ */
    public static function handle_save_recipients()
    {
        self::check();
        global $wpdb;

        $email       = sanitize_email($_POST['email'] ?? '');
        $name        = sanitize_text_field($_POST['display_name'] ?? '');
        $role        = sanitize_text_field($_POST['role_label'] ?? '');
        $types_raw   = $_POST['email_types'] ?? [];
        $is_active   = (int) ($_POST['is_active'] ?? 1);
        $recipient_id = (int) ($_POST['recipient_id'] ?? 0);

        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Email không hợp lệ']);
        }

        // Sanitize email_types
        $types = [];
        if (is_array($types_raw)) {
            foreach ($types_raw as $t) {
                $types[] = sanitize_text_field($t);
            }
        }

        $table = TGS_EMAIL_TABLE_RECIPIENTS;
        $now = current_time('mysql');

        $data = [
            'email'       => $email,
            'display_name' => $name,
            'role_label'  => $role,
            'email_types' => wp_json_encode($types),
            'is_active'   => $is_active,
            'updated_at'  => $now,
        ];

        if ($recipient_id > 0) {
            $wpdb->update($table, $data, ['recipient_id' => $recipient_id]);
        } else {
            $data['created_at'] = $now;
            $wpdb->insert($table, $data);
            $recipient_id = $wpdb->insert_id;
        }

        wp_send_json_success(['recipient_id' => $recipient_id, 'message' => 'Đã lưu']);
    }

    public static function handle_get_recipients()
    {
        self::check();
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM " . TGS_EMAIL_TABLE_RECIPIENTS . " ORDER BY is_active DESC, display_name ASC");
        wp_send_json_success(['recipients' => $rows]);
    }

    public static function handle_delete_recipient()
    {
        self::check();
        global $wpdb;
        $rid = (int) ($_POST['recipient_id'] ?? 0);
        if ($rid > 0) {
            $wpdb->delete(TGS_EMAIL_TABLE_RECIPIENTS, ['recipient_id' => $rid]);
        }
        wp_send_json_success(['message' => 'Đã xóa']);
    }
}
