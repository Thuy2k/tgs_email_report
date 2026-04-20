<?php
/**
 * TGS Email Settings — Cấu hình SMTP & chế độ gửi email
 *
 * Tích hợp trực tiếp trong plugin, không cần cài thêm plugin SMTP nào.
 * - Hook vào phpmailer_init để cấu hình SMTP
 * - Hỗ trợ dev mode: ghi email ra file thay vì gửi thật
 * - Mã hóa mật khẩu SMTP khi lưu
 */

if (!defined('ABSPATH')) exit;

class TGS_Email_Settings
{
    const OPTION_KEY = 'tgs_email_smtp_settings';

    /** Cấu hình mặc định */
    private static $defaults = [
        'mode'        => 'php',       // php | smtp | resend_api | dev
        'smtp_host'   => '',
        'smtp_port'   => 587,
        'smtp_secure' => 'tls',       // '' | tls | ssl
        'smtp_auth'   => 1,
        'smtp_user'   => '',
        'smtp_pass'   => '',          // encrypted khi lưu
        'smtp_no_verify_ssl' => 0,    // tắt verify SSL (cho cPanel proxy)
        'resend_api_key' => '',       // encrypted khi lưu
        'from_email'  => '',
        'from_name'   => 'TGS System',
        'shop_report_include_blogs'      => [],
        'warehouse_report_include_blogs' => [],
    ];

    /** Hook vào WordPress */
    public static function init()
    {
        $settings = self::get();

        // Dev mode: chặn wp_mail, ghi file thay thế
        if ($settings['mode'] === 'dev') {
            add_filter('pre_wp_mail', [__CLASS__, 'dev_mode_intercept'], 10, 2);
            return;
        }

        // Resend API mode: gửi qua HTTP API thay vì SMTP
        if ($settings['mode'] === 'resend_api') {
            add_filter('pre_wp_mail', [__CLASS__, 'resend_api_intercept'], 10, 2);
            return;
        }

        // SMTP mode: cấu hình PHPMailer
        if ($settings['mode'] === 'smtp' && !empty($settings['smtp_host'])) {
            add_action('phpmailer_init', [__CLASS__, 'configure_phpmailer'], 999);
        }

        // From email/name override
        if (!empty($settings['from_email'])) {
            add_filter('wp_mail_from', function () use ($settings) {
                return $settings['from_email'];
            }, 999);
        }
        if (!empty($settings['from_name'])) {
            add_filter('wp_mail_from_name', function () use ($settings) {
                return $settings['from_name'];
            }, 999);
        }
    }

    /** Cấu hình PHPMailer cho SMTP */
    public static function configure_phpmailer($phpmailer)
    {
        $s = self::get();

        $phpmailer->isSMTP();
        $phpmailer->Host       = $s['smtp_host'];
        $phpmailer->Port       = (int) $s['smtp_port'];
        $phpmailer->SMTPSecure = $s['smtp_secure'];
        $phpmailer->SMTPAuth   = (bool) $s['smtp_auth'];
        $phpmailer->Timeout    = 15; // 15 giây timeout — tránh treo AJAX

        if ($s['smtp_auth'] && $s['smtp_user']) {
            $phpmailer->Username = $s['smtp_user'];
            $phpmailer->Password = self::decrypt_password($s['smtp_pass']);
        }

        // Tắt kiểm tra SSL certificate (giữ nguyên TLS/STARTTLS)
        // Chỉ bypass verify — KHÔNG tắt mã hóa, vì Resend/Gmail bắt buộc TLS
        if (!empty($s['smtp_no_verify_ssl'])) {
            $phpmailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        if (!empty($s['from_email'])) {
            $phpmailer->From     = $s['from_email'];
            $phpmailer->FromName = $s['from_name'] ?: 'TGS System';
        }
    }

    /**
     * Dev mode: ghi email ra file thay vì gửi thật
     * @return true Trả true → wp_mail() sẽ return true mà không gửi
     */
    public static function dev_mode_intercept($null, $atts)
    {
        $to      = is_array($atts['to']) ? implode(', ', $atts['to']) : ($atts['to'] ?? '');
        $subject = $atts['subject'] ?? '';
        $message = $atts['message'] ?? '';
        $headers = $atts['headers'] ?? '';

        // Lưu vào thư mục uploads
        $upload_dir = wp_upload_dir();
        $log_dir    = $upload_dir['basedir'] . '/tgs-email-logs';
        if (!is_dir($log_dir)) {
            wp_mkdir_p($log_dir);
            // Chặn truy cập trực tiếp
            file_put_contents($log_dir . '/.htaccess', 'Deny from all');
            file_put_contents($log_dir . '/index.php', '<?php // Silence');
        }

        $filename = date('Y-m-d_His') . '_' . sanitize_file_name(substr($subject, 0, 40)) . '.html';
        $filepath = $log_dir . '/' . $filename;

        $log_content  = "<!-- TGS Email Dev Log -->\n";
        $log_content .= "<!-- To: {$to} -->\n";
        $log_content .= "<!-- Subject: {$subject} -->\n";
        $log_content .= "<!-- Date: " . current_time('Y-m-d H:i:s') . " -->\n";
        $log_content .= "<!-- Headers: " . (is_array($headers) ? implode(' | ', $headers) : $headers) . " -->\n";
        $log_content .= "<!-- ═══════════════════════════════════════ -->\n\n";
        $log_content .= $message;

        file_put_contents($filepath, $log_content);

        // Return true = wp_mail sẽ coi là gửi thành công
        return true;
    }

    /**
     * Resend API mode: gửi email qua Resend HTTP API (port 443 — không bị hosting chặn)
     * @return true|null  true = wp_mail coi là thành công, null = fallback wp_mail
     */
    public static function resend_api_intercept($null, $atts)
    {
        $s = self::get();
        $api_key = self::decrypt_password($s['resend_api_key']);
        if (empty($api_key)) {
            return null; // Fallback wp_mail nếu chưa có API key
        }

        $to = $atts['to'] ?? '';
        if (is_array($to)) {
            $to = array_map('sanitize_email', $to);
        } else {
            $to = [sanitize_email($to)];
        }

        $subject = $atts['subject'] ?? '';
        $message = $atts['message'] ?? '';
        $headers = $atts['headers'] ?? [];

        // Parse CC từ headers
        $cc = [];
        if (!is_array($headers)) {
            $headers = explode("\n", str_replace("\r\n", "\n", $headers));
        }
        foreach ($headers as $header) {
            if (preg_match('/^Cc:\s*(.+)$/i', trim($header), $m)) {
                $cc[] = sanitize_email(trim($m[1]));
            }
        }

        $from_email = $s['from_email'] ?: get_option('admin_email');
        $from_name  = $s['from_name'] ?: 'TGS System';
        $from       = $from_name ? "{$from_name} <{$from_email}>" : $from_email;

        $body = [
            'from'    => $from,
            'to'      => $to,
            'subject' => $subject,
            'html'    => $message,
        ];
        if (!empty($cc)) {
            $body['cc'] = $cc;
        }

        $response = wp_remote_post('https://api.resend.com/emails', [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            // Lưu lỗi vào global để sender có thể đọc
            $GLOBALS['tgs_resend_last_error'] = $response->get_error_message();
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        $resp_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 200 && !empty($resp_body['id'])) {
            return true; // Gửi thành công
        }

        $error_msg = $resp_body['message'] ?? ('HTTP ' . $code);
        $GLOBALS['tgs_resend_last_error'] = $error_msg;
        return false;
    }

    /* ════════════════════════════════════════
     * CRUD settings
     * ════════════════════════════════════════ */

    /** Lấy settings hiện tại */
    public static function get()
    {
        $saved = get_site_option(self::OPTION_KEY, []);
        return wp_parse_args($saved, self::$defaults);
    }

    /** Lưu settings (password sẽ được encrypt) */
    public static function save($data)
    {
        $clean = [];
        $clean['mode']        = in_array($data['mode'] ?? '', ['php', 'smtp', 'resend_api', 'dev']) ? $data['mode'] : 'php';
        $clean['smtp_host']   = sanitize_text_field($data['smtp_host'] ?? '');
        $clean['smtp_port']   = (int) ($data['smtp_port'] ?? 587);
        $clean['smtp_secure'] = in_array($data['smtp_secure'] ?? '', ['', 'tls', 'ssl']) ? $data['smtp_secure'] : 'tls';
        $clean['smtp_auth']   = (int) !empty($data['smtp_auth']);
        $clean['smtp_user']   = sanitize_text_field($data['smtp_user'] ?? '');
        $clean['smtp_no_verify_ssl'] = (int) !empty($data['smtp_no_verify_ssl']);
        $clean['from_email']  = sanitize_email($data['from_email'] ?? '');
        $clean['from_name']   = sanitize_text_field($data['from_name'] ?? 'TGS System');

        // SMTP Password
        $password_raw = $data['smtp_pass'] ?? '';
        if (!empty($password_raw) && $password_raw !== '••••••••') {
            $clean['smtp_pass'] = self::encrypt_password($password_raw);
        } else {
            $old = get_site_option(self::OPTION_KEY, []);
            $clean['smtp_pass'] = $old['smtp_pass'] ?? '';
        }

        // Resend API Key
        $api_key_raw = $data['resend_api_key'] ?? '';
        if (!empty($api_key_raw) && $api_key_raw !== '••••••••') {
            $clean['resend_api_key'] = self::encrypt_password($api_key_raw);
        } else {
            $old = $old ?? get_site_option(self::OPTION_KEY, []);
            $clean['resend_api_key'] = $old['resend_api_key'] ?? '';
        }

        $clean['shop_report_include_blogs']      = array_values(array_map('intval', array_filter((array) ($data['shop_report_include_blogs'] ?? []))));
        $clean['warehouse_report_include_blogs'] = array_values(array_map('intval', array_filter((array) ($data['warehouse_report_include_blogs'] ?? []))));

        update_site_option(self::OPTION_KEY, $clean);
        return $clean;
    }

    /** Test gửi email qua SMTP config hiện tại */
    public static function send_test($to_email)
    {
        if (!is_email($to_email)) {
            return ['success' => false, 'message' => 'Email không hợp lệ'];
        }

        // Re-apply hooks theo settings mới nhất (fix bug: save rồi test ngay trong cùng request)
        $settings = self::get();
        remove_all_actions('phpmailer_init');
        remove_all_filters('pre_wp_mail');
        remove_all_filters('wp_mail_from');
        remove_all_filters('wp_mail_from_name');

        if ($settings['mode'] === 'dev') {
            add_filter('pre_wp_mail', [__CLASS__, 'dev_mode_intercept'], 10, 2);
        } elseif ($settings['mode'] === 'resend_api') {
            add_filter('pre_wp_mail', [__CLASS__, 'resend_api_intercept'], 10, 2);
        } elseif ($settings['mode'] === 'smtp' && !empty($settings['smtp_host'])) {
            add_action('phpmailer_init', [__CLASS__, 'configure_phpmailer'], 999);
        }
        // Re-apply from email/name
        if (!empty($settings['from_email'])) {
            add_filter('wp_mail_from', function () use ($settings) { return $settings['from_email']; }, 999);
        }
        if (!empty($settings['from_name'])) {
            add_filter('wp_mail_from_name', function () use ($settings) { return $settings['from_name']; }, 999);
        }

        $subject = '[TGS Test] Email test SMTP — ' . current_time('H:i d/m/Y');
        $body    = '<div style="font-family:Arial,sans-serif; padding:20px;">';
        $body   .= '<h2 style="color:#28a745;">✓ Email test thành công!</h2>';
        $body   .= '<p>Cấu hình SMTP của bạn hoạt động tốt.</p>';
        $body   .= '<p style="color:#888; font-size:12px;">Gửi lúc: ' . current_time('H:i:s d/m/Y') . '</p>';

        $settings = self::get();
        $body .= '<hr style="border:none; border-top:1px solid #eee; margin:16px 0;">';
        $body .= '<p style="font-size:12px; color:#888;">';
        $body .= 'Mode: <strong>' . esc_html($settings['mode']) . '</strong>';
        if ($settings['mode'] === 'smtp') {
            $body .= ' | Host: <strong>' . esc_html($settings['smtp_host']) . ':' . $settings['smtp_port'] . '</strong>';
        }
        $body .= '</p></div>';

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $sent = wp_mail($to_email, $subject, $body, $headers);

        if ($sent) {
            $msg = 'Email test đã gửi thành công tới ' . $to_email;
            if ($settings['mode'] === 'dev') {
                $upload_dir = wp_upload_dir();
                $msg .= ' (Dev mode — file lưu tại wp-content/uploads/tgs-email-logs/)';
            } elseif ($settings['mode'] === 'resend_api') {
                $msg .= ' (qua Resend API)';
            }
            return ['success' => true, 'message' => $msg];
        }

        // Lấy lỗi PHPMailer hoặc Resend API nếu có
        $error = '';
        if (!empty($GLOBALS['tgs_resend_last_error'])) {
            $error = $GLOBALS['tgs_resend_last_error'];
        } else {
            global $phpmailer;
            if (isset($phpmailer) && is_object($phpmailer)) {
                $error = $phpmailer->ErrorInfo ?? '';
            }
        }

        return [
            'success' => false,
            'message' => 'Gửi thất bại. ' . ($error ? 'Lỗi: ' . $error : 'Kiểm tra lại cấu hình SMTP.'),
        ];
    }

    /* ════════════════════════════════════════
     * Encrypt / Decrypt password
     * ════════════════════════════════════════ */

    private static function encrypt_password($plain)
    {
        if (empty($plain)) return '';

        $key = self::get_encryption_key();
        if (function_exists('openssl_encrypt')) {
            $iv     = openssl_random_pseudo_bytes(16);
            $cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, 0, $iv);
            return base64_encode($iv . '::' . $cipher);
        }

        // Fallback đơn giản
        return base64_encode($plain);
    }

    private static function decrypt_password($encrypted)
    {
        if (empty($encrypted)) return '';

        $key     = self::get_encryption_key();
        $decoded = base64_decode($encrypted);

        if (function_exists('openssl_decrypt') && strpos($decoded, '::') !== false) {
            list($iv, $cipher) = explode('::', $decoded, 2);
            $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, 0, $iv);
            return $plain !== false ? $plain : '';
        }

        // Fallback
        return $decoded;
    }

    private static function get_encryption_key()
    {
        // Dùng AUTH_KEY của WordPress làm encryption key
        return defined('AUTH_KEY') ? AUTH_KEY : 'tgs-email-default-key-change-me';
    }

    /** Lấy settings cho hiển thị (ẩn password & API key) */
    public static function get_for_display()
    {
        $s = self::get();
        $s['smtp_pass'] = !empty($s['smtp_pass']) ? '••••••••' : '';
        $s['resend_api_key'] = !empty($s['resend_api_key']) ? '••••••••' : '';
        return $s;
    }
}
