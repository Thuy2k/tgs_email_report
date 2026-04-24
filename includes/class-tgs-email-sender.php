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
    const BACKUP_DEFAULT_MAX_ATTACHMENTS = 40;
    const BACKUP_DEFAULT_MAX_TOTAL_BYTES = 104857600; // 100MB

    /* ================================================================
     *  1) GỬI BÁO CÁO SHOP  (bán hàng + thu ngân hàng + MAX tại shop)
     * ================================================================ */
    public static function send_shop_report($date_from, $date_to, $triggered_by = 'manual', $user_id = 0, $override_recipients = null)
    {
        // ── Áp dụng filter shop ──
        $settings = TGS_Email_Settings::get();
        TGS_Collector_Base::set_blog_filter($settings['shop_report_include_blogs'] ?? []);

        // ── Collect data từ các collector ──
        $sales_data     = TGS_Collector_Shop_Sales::collect($date_from, $date_to);
        $bank_data      = TGS_Collector_Shop_Bank::collect($date_from, $date_to);
        $max_data       = TGS_Collector_Shop_Max::collect($date_from, $date_to);
        $summary_data   = TGS_Collector_Summary::collect($date_from, $date_to);
        $gifts_data     = TGS_Collector_Shop_Gifts::collect($date_from, $date_to);
        $einvoice_data  = TGS_Collector_Shop_EInvoice::collect($date_from, $date_to);

        $all_data = [
            'sales'   => $sales_data,
            'bank'    => $bank_data,
            'max'     => $max_data,
            'summary' => $summary_data,
            'gifts'   => $gifts_data,
            'einvoice' => $einvoice_data,
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
        // ── Áp dụng filter shop ──
        $settings = TGS_Email_Settings::get();
        TGS_Collector_Base::set_blog_filter($settings['warehouse_report_include_blogs'] ?? []);

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
     *  3) GỬI BÁO CÁO BACKUP DB TỰ ĐỘNG
     * ================================================================ */
    public static function send_backup_report($date_from, $date_to, $triggered_by = 'manual', $user_id = 0, $override_recipients = null)
    {
        $all_data = self::collect_backup_report_data();
        $all_data['date_from'] = $date_from;
        $all_data['date_to'] = $date_to;

        $prepared = self::prepare_backup_attachments($all_data['folders'] ?? []);
        $all_data['folders'] = $prepared['folders'];
        $all_data['attachment_meta'] = $prepared['meta'];

        $html = self::render_template('email-backup-report.php', $all_data);

        $subject = sprintf(
            '[TGS] Báo cáo Backup DB tự động — %s',
            ($date_from === $date_to)
                ? date('d/m/Y', strtotime($date_to))
                : date('d/m', strtotime($date_from)) . ' → ' . date('d/m/Y', strtotime($date_to))
        );

        $recipients = $override_recipients ?: self::get_recipients(TGS_EMAIL_TYPE_BACKUP);

        return self::do_send($subject, $html, $recipients, TGS_EMAIL_TYPE_BACKUP, $date_from, $date_to, $all_data, $triggered_by, $user_id, $prepared['attachments']);
     *  3) GỬI BÁO CÁO HÓA ĐƠN ĐIỆN TỬ
     * ================================================================ */
    public static function send_einvoice_report($date_from, $date_to, $triggered_by = 'manual', $user_id = 0, $override_recipients = null)
    {
        $settings = TGS_Email_Settings::get();
        TGS_Collector_Base::set_blog_filter($settings['einvoice_report_include_blogs'] ?? []);

        $einvoice_data = TGS_Collector_Shop_EInvoice::collect($date_from, $date_to);

        $all_data = [
            'einvoice' => $einvoice_data,
            'date_from' => $date_from,
            'date_to'   => $date_to,
        ];

        $html = self::render_template('email-einvoice-report.php', $all_data);

        $subject = sprintf(
            '[TGS] Báo cáo HĐĐT theo shop — %s',
            ($date_from === $date_to)
                ? date('d/m/Y', strtotime($date_from))
                : date('d/m', strtotime($date_from)) . ' → ' . date('d/m/Y', strtotime($date_to))
        );

        $recipients = $override_recipients ?: self::get_recipients(TGS_EMAIL_TYPE_EINVOICE);

        return self::do_send($subject, $html, $recipients, TGS_EMAIL_TYPE_EINVOICE, $date_from, $date_to, $all_data, $triggered_by, $user_id);
    }

    /* ================================================================
     *  CORE: Gửi email + lưu log
     * ================================================================ */
    private static function do_send($subject, $html, $recipients, $email_type, $date_from, $date_to, $data, $triggered_by, $user_id, $attachments = [])
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

        $blog_ids = array_keys($data['summary']['shops'] ?? []);
        if (empty($blog_ids) && !empty($data['folders']) && is_array($data['folders'])) {
            foreach ($data['folders'] as $folder) {
                if (!empty($folder['blog_id'])) {
                    $blog_ids[] = (int) $folder['blog_id'];
                }
            }
            $blog_ids = array_values(array_unique($blog_ids));
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
            'blog_ids_json'     => wp_json_encode($blog_ids),
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
            $headers,
            $attachments
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
        if ($log->email_type === TGS_EMAIL_TYPE_BACKUP) {
            return self::send_backup_report($log->date_from, $log->date_to, 'resend', get_current_user_id());
        if ($log->email_type === TGS_EMAIL_TYPE_EINVOICE) {
            return self::send_einvoice_report($log->date_from, $log->date_to, 'resend', get_current_user_id());
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

    public static function get_backup_preview_data()
    {
        $data = self::collect_backup_report_data();
        $prepared = self::prepare_backup_attachments($data['folders'] ?? []);
        $data['folders'] = $prepared['folders'];
        $data['attachment_meta'] = $prepared['meta'];
        return $data;
    }

    /* ================================================================
     *  BACKUP REPORT HELPERS
     * ================================================================ */
    private static function collect_backup_report_data()
    {
        $auto_dir = self::get_backup_auto_dir();
        $folders = [];
        $orphan_folders = [];
        $active_blog_ids = [];
        $stats = [
            'total_folders'      => 0,
            'available_backups'  => 0,
            'missing_backups'    => 0,
            'orphan_folders'     => 0,
            'total_latest_size'  => 0,
            'latest_generated_at'=> null,
        ];
        $latest_timestamp = 0;

        $global_row = self::build_backup_folder_row('global', 'Global (dùng chung)', 'global', 0, $auto_dir . 'global/');
        $folders[] = $global_row;

        if (is_multisite()) {
            $sites = get_sites(['number' => 0]);
            foreach ($sites as $site) {
                $blog_id = (int) $site->blog_id;
                $active_blog_ids[] = $blog_id;
                $details = get_blog_details($blog_id);
                $blog_name = ($details && !empty($details->blogname)) ? $details->blogname : ('Blog ' . $blog_id);
                $folders[] = self::build_backup_folder_row(
                    'blog_' . $blog_id,
                    $blog_name . ' (ID: ' . $blog_id . ')',
                    'local',
                    $blog_id,
                    $auto_dir . 'blog_' . $blog_id . '/'
                );
            }
        } else {
            $blog_id = get_current_blog_id();
            $active_blog_ids[] = $blog_id;
            $folders[] = self::build_backup_folder_row(
                'blog_' . $blog_id,
                get_bloginfo('name') . ' (ID: ' . $blog_id . ')',
                'local',
                $blog_id,
                $auto_dir . 'blog_' . $blog_id . '/'
            );
        }

        foreach ($folders as $folder) {
            $stats['total_folders']++;
            if (!empty($folder['has_file'])) {
                $stats['available_backups']++;
                $stats['total_latest_size'] += (int) $folder['size'];
                $latest_timestamp = max($latest_timestamp, (int) $folder['time']);
            } else {
                $stats['missing_backups']++;
            }
        }

        $orphan_dirs = glob($auto_dir . 'blog_*', GLOB_ONLYDIR);
        if ($orphan_dirs) {
            foreach ($orphan_dirs as $dir) {
                $name = basename($dir);
                if (!preg_match('/^blog_(\d+)$/', $name, $matches)) {
                    continue;
                }

                $blog_id = (int) $matches[1];
                if (in_array($blog_id, $active_blog_ids, true)) {
                    continue;
                }

                $row = self::build_backup_folder_row(
                    $name,
                    'Blog ' . $blog_id . ' (đã xóa)',
                    'orphan',
                    $blog_id,
                    trailingslashit($dir),
                    true
                );
                $orphan_folders[] = $row;
                $stats['orphan_folders']++;
                if (!empty($row['has_file'])) {
                    $latest_timestamp = max($latest_timestamp, (int) $row['time']);
                }
            }
        }

        if ($latest_timestamp > 0) {
            $stats['latest_generated_at'] = wp_date('d/m/Y H:i:s', $latest_timestamp);
        }

        $stats['total_latest_size_human'] = self::format_bytes($stats['total_latest_size']);

        return [
            'folders'         => $folders,
            'orphan_folders'  => $orphan_folders,
            'stats'           => $stats,
            'generated_at'    => current_time('d/m/Y H:i:s'),
            'auto_dir_exists' => is_dir($auto_dir),
        ];
    }

    private static function build_backup_folder_row($key, $label, $type, $blog_id, $dir, $is_orphan = false)
    {
        $latest = self::get_latest_backup_file($dir);

        return [
            'key'        => $key,
            'label'      => $label,
            'type'       => $type,
            'blog_id'    => $blog_id,
            'orphan'     => $is_orphan,
            'folder'     => basename(rtrim($dir, '/\\')),
            'has_file'   => !empty($latest),
            'status'     => $latest ? 'ok' : 'missing',
            'filename'   => $latest['name'] ?? '',
            'path'       => $latest['path'] ?? '',
            'size'       => $latest['size'] ?? 0,
            'size_human' => self::format_bytes($latest['size'] ?? 0),
            'time'       => $latest['time'] ?? 0,
            'time_human' => !empty($latest['time']) ? wp_date('d/m/Y H:i:s', $latest['time']) : '',
            'attached'   => false,
            'attach_reason' => '',
        ];
    }

    private static function get_latest_backup_file($dir)
    {
        if (!is_dir($dir)) {
            return null;
        }

        $files = glob(rtrim($dir, '/\\') . '/*.sql');
        if (!$files) {
            return null;
        }

        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $file = $files[0];
        return [
            'name' => basename($file),
            'path' => $file,
            'size' => (int) filesize($file),
            'time' => (int) filemtime($file),
        ];
    }

    private static function prepare_backup_attachments($folders)
    {
        $max_attachments = (int) apply_filters('tgs_email_backup_max_attachments', self::BACKUP_DEFAULT_MAX_ATTACHMENTS);
        $max_total_bytes = (int) apply_filters('tgs_email_backup_max_total_bytes', self::BACKUP_DEFAULT_MAX_TOTAL_BYTES);

        // Resend API giới hạn payload attachment thấp hơn SMTP thông thường.
        if (class_exists('TGS_Email_Settings')) {
            $settings = TGS_Email_Settings::get();
            if (($settings['mode'] ?? '') === 'resend_api') {
                $resend_safe_cap = (int) apply_filters('tgs_email_backup_resend_max_total_bytes', 20 * 1024 * 1024); // 20MB
                if ($resend_safe_cap <= 0) {
                    $resend_safe_cap = 20 * 1024 * 1024;
                }
                if ($max_total_bytes > $resend_safe_cap) {
                    $max_total_bytes = $resend_safe_cap;
                }
            }
        }

        if ($max_attachments <= 0) {
            $max_attachments = self::BACKUP_DEFAULT_MAX_ATTACHMENTS;
        }
        if ($max_total_bytes <= 0) {
            $max_total_bytes = self::BACKUP_DEFAULT_MAX_TOTAL_BYTES;
        }

        $attachments = [];
        $total_bytes = 0;
        $attached_count = 0;
        $skipped_count = 0;

        foreach ($folders as $idx => $folder) {
            $has_file = !empty($folder['has_file']);
            $path = (string) ($folder['path'] ?? '');
            $size = (int) ($folder['size'] ?? 0);

            $folders[$idx]['attached'] = false;
            $folders[$idx]['attach_reason'] = '';

            if (!$has_file) {
                $skipped_count++;
                $folders[$idx]['attach_reason'] = 'missing';
                continue;
            }

            if (empty($path) || !file_exists($path) || !is_readable($path)) {
                $skipped_count++;
                $folders[$idx]['attach_reason'] = 'not_readable';
                continue;
            }

            if ($attached_count >= $max_attachments) {
                $skipped_count++;
                $folders[$idx]['attach_reason'] = 'limit_count';
                continue;
            }

            if (($total_bytes + $size) > $max_total_bytes) {
                $skipped_count++;
                $folders[$idx]['attach_reason'] = 'limit_size';
                continue;
            }

            $attachments[] = $path;
            $total_bytes += $size;
            $attached_count++;
            $folders[$idx]['attached'] = true;
            $folders[$idx]['attach_reason'] = 'attached';
        }

        return [
            'attachments' => $attachments,
            'folders'     => $folders,
            'meta'        => [
                'attached_count'    => $attached_count,
                'skipped_count'     => $skipped_count,
                'attached_bytes'    => $total_bytes,
                'attached_size_human' => self::format_bytes($total_bytes),
                'max_attachments'   => $max_attachments,
                'max_total_bytes'   => $max_total_bytes,
                'max_total_human'   => self::format_bytes($max_total_bytes),
            ],
        ];
    }

    private static function get_backup_auto_dir()
    {
        if (class_exists('TGS_Backup_Manager') && method_exists('TGS_Backup_Manager', 'get_backup_dir')) {
            return trailingslashit(TGS_Backup_Manager::get_backup_dir()) . 'auto/';
        }

        return WP_CONTENT_DIR . '/tgs-backups/auto/';
    }

    private static function format_bytes($bytes)
    {
        $bytes = (int) $bytes;
        if ($bytes <= 0) {
            return '0 B';
        }

        if (class_exists('TGS_Backup_Manager') && method_exists('TGS_Backup_Manager', 'format_size')) {
            return TGS_Backup_Manager::format_size($bytes);
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        $value = $bytes / pow(1024, $power);

        return round($value, $power === 0 ? 0 : 1) . ' ' . $units[$power];
    }
}
