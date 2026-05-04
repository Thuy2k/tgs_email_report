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

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

class TGS_Email_Settings
{
    const OPTION_KEY = 'tgs_email_smtp_settings';

    /** Cấu hình mặc định */
    private static $defaults = [
        'mode'        => 'php',       // php | smtp | resend_api | dev | fluent_smtp
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
        'einvoice_report_include_blogs'  => [],
    ];

    /** Kiểm tra Fluent SMTP có active không */
    public static function is_fluent_smtp_active()
    {
        if (defined('FLUENTMAIL')) {
            return true;
        }
        if (class_exists('FluentSmtpLib\\App\\Services\\Mailer\\BaseHandler') || class_exists('FluentMail\\App\\Services\\Mailer\\BaseHandler')) {
            return true;
        }
        return false;
    }

    /** Hook vào WordPress */
    public static function init()
    {
        $settings = self::get();

        // Nếu không dùng Fluent SMTP, chặn wp_mail sớm để bypass hoàn toàn Fluent.
        if ($settings['mode'] !== 'fluent_smtp') {
            add_filter('pre_wp_mail', [__CLASS__, 'intercept_wp_mail'], 1, 2);
        }

        // Dev mode: chặn wp_mail, ghi file thay thế
        if ($settings['mode'] === 'dev') {
            return;
        }

        // Resend API mode: gửi qua HTTP API thay vì SMTP
        if ($settings['mode'] === 'resend_api') {
            return;
        }

        // Fluent SMTP mode: nhường toàn bộ việc gửi mail cho Fluent SMTP
        if ($settings['mode'] === 'fluent_smtp') {
            return;
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

    /**
     * Chặn wp_mail trước khi Fluent SMTP hoặc transport khác can thiệp.
     * Trả non-null để short-circuit hoàn toàn wp_mail.
     */
    public static function intercept_wp_mail($null, $atts)
    {
        $settings = self::get();

        if ($settings['mode'] === 'fluent_smtp') {
            return $null;
        }

        if ($settings['mode'] === 'dev') {
            return self::dev_mode_intercept($null, $atts);
        }

        if ($settings['mode'] === 'resend_api') {
            return self::resend_api_intercept($null, $atts);
        }

        return self::direct_phpmailer_send($atts, $settings);
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
            $GLOBALS['tgs_resend_last_error'] = 'Chưa cấu hình Resend API key.';
            return false;
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
        $attachments = $atts['attachments'] ?? [];

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

        // Convert wp_mail attachments (file paths) to Resend attachments.
        if (!is_array($attachments)) {
            $attachments = empty($attachments) ? [] : [$attachments];
        }
        $resend_attachments = [];
        foreach ($attachments as $file_path) {
            $file_path = (string) $file_path;
            if ($file_path === '' || !file_exists($file_path) || !is_readable($file_path)) {
                continue;
            }

            $content = @file_get_contents($file_path);
            if ($content === false) {
                continue;
            }

            $item = [
                'filename' => basename($file_path),
                'content'  => base64_encode($content),
            ];
            if (function_exists('mime_content_type')) {
                $mime = @mime_content_type($file_path);
                if (!empty($mime)) {
                    $item['type'] = $mime;
                }
            }
            $resend_attachments[] = $item;
        }
        if (!empty($resend_attachments)) {
            $body['attachments'] = $resend_attachments;
        }

        if (!empty($cc)) {
            $body['cc'] = $cc;
        }

        $raw_attachment_bytes = 0;
        if (!empty($resend_attachments)) {
            foreach ($resend_attachments as $att) {
                // base64 length x 3/4 ~= bytes gốc (xấp xỉ)
                $raw_attachment_bytes += (int) floor((strlen($att['content'] ?? '') * 3) / 4);
            }
        }

        // Timeout mặc định cao hơn để upload payload lớn (attachment) ổn định hơn.
        // Có thể override qua filter tgs_email_resend_timeout.
        $timeout_seconds = 60;
        if ($raw_attachment_bytes > 0) {
            // Ước lượng tốc độ upload tối thiểu 250KB/s để tránh timeout giả.
            $estimated = (int) ceil(($raw_attachment_bytes * 1.35) / (250 * 1024));
            $timeout_seconds = max(60, min(300, $estimated + 20));
        }
        $timeout_seconds = (int) apply_filters('tgs_email_resend_timeout', $timeout_seconds, $raw_attachment_bytes, $atts);
        if ($timeout_seconds < 30) {
            $timeout_seconds = 30;
        }

        $response = wp_remote_post('https://api.resend.com/emails', [
            'timeout' => $timeout_seconds,
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

    /**
     * Gửi mail trực tiếp bằng PHPMailer để bypass hoàn toàn Fluent SMTP.
     */
    private static function direct_phpmailer_send($atts, $settings)
    {
        $GLOBALS['tgs_resend_last_error'] = null;

        if (!class_exists(PHPMailer::class)) {
            require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
            require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
            require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
        }

        $mailer = new PHPMailer(true);

        try {
            self::prepare_phpmailer_message($mailer, $atts, $settings);

            if ($settings['mode'] === 'smtp') {
                if (empty($settings['smtp_host'])) {
                    throw new PHPMailerException('Chưa cấu hình SMTP host.');
                }
                self::configure_phpmailer_instance($mailer, $settings);
            } else {
                $mailer->isMail();
            }

            return $mailer->send();
        } catch (\Throwable $e) {
            $GLOBALS['tgs_resend_last_error'] = $e->getMessage();
            return false;
        }
    }

    private static function configure_phpmailer_instance($mailer, $settings)
    {
        $mailer->isSMTP();
        $mailer->Host       = $settings['smtp_host'];
        $mailer->Port       = (int) $settings['smtp_port'];
        $mailer->SMTPAuth   = (bool) $settings['smtp_auth'];
        $mailer->Timeout    = 15;

        if (!empty($settings['smtp_secure'])) {
            $mailer->SMTPSecure = $settings['smtp_secure'];
        }

        if ($settings['smtp_auth'] && $settings['smtp_user']) {
            $mailer->Username = $settings['smtp_user'];
            $mailer->Password = self::decrypt_password($settings['smtp_pass']);
        }

        if (!empty($settings['smtp_no_verify_ssl'])) {
            $mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];
        }
    }

    private static function prepare_phpmailer_message($mailer, $atts, $settings)
    {
        $to = $atts['to'] ?? [];
        if (!is_array($to)) {
            $to = explode(',', (string) $to);
        }

        $subject = (string) ($atts['subject'] ?? '');
        $message = (string) ($atts['message'] ?? '');
        $headers = $atts['headers'] ?? [];
        $attachments = $atts['attachments'] ?? [];

        if (!is_array($headers)) {
            $headers = explode("\n", str_replace("\r\n", "\n", (string) $headers));
        }
        if (!is_array($attachments)) {
            $attachments = empty($attachments) ? [] : [$attachments];
        }

        $content_type = 'text/plain';
        $charset = get_bloginfo('charset');
        $from_email = $settings['from_email'] ?: get_option('admin_email');
        $from_name = $settings['from_name'] ?: 'TGS System';

        foreach ($headers as $header) {
            $header = trim((string) $header);
            if ($header === '') {
                continue;
            }

            if (stripos($header, 'Content-Type:') === 0) {
                $content_type = trim(substr($header, strlen('Content-Type:')));
                if (stripos($content_type, ';') !== false) {
                    [$content_type_only, $rest] = array_map('trim', explode(';', $content_type, 2));
                    $content_type = $content_type_only;
                    if (preg_match('/charset\s*=\s*([\w\-]+)/i', $rest, $m)) {
                        $charset = $m[1];
                    }
                }
                continue;
            }

            if (stripos($header, 'Cc:') === 0) {
                foreach (explode(',', substr($header, 3)) as $cc) {
                    $cc = sanitize_email(trim($cc));
                    if ($cc) {
                        $mailer->addCC($cc);
                    }
                }
                continue;
            }

            if (stripos($header, 'Bcc:') === 0) {
                foreach (explode(',', substr($header, 4)) as $bcc) {
                    $bcc = sanitize_email(trim($bcc));
                    if ($bcc) {
                        $mailer->addBCC($bcc);
                    }
                }
                continue;
            }

            if (stripos($header, 'Reply-To:') === 0) {
                foreach (explode(',', substr($header, 9)) as $reply_to) {
                    $reply_to = sanitize_email(trim($reply_to));
                    if ($reply_to) {
                        $mailer->addReplyTo($reply_to);
                    }
                }
                continue;
            }

            if (stripos($header, 'From:') === 0) {
                $parsed = self::parse_email_header_address(substr($header, 5));
                if (!empty($parsed['email'])) {
                    $from_email = $parsed['email'];
                    $from_name = $parsed['name'] !== '' ? $parsed['name'] : $from_name;
                }
                continue;
            }

            if (strpos($header, ':') !== false) {
                [$name, $value] = array_map('trim', explode(':', $header, 2));
                if ($name !== '' && $value !== '') {
                    $mailer->addCustomHeader($name, $value);
                }
            }
        }

        $mailer->CharSet = $charset;
        $mailer->Subject = $subject;
        $mailer->Body    = $message;

        if (stripos($content_type, 'text/html') === 0) {
            $mailer->isHTML(true);
            $mailer->AltBody = wp_strip_all_tags($message);
        } else {
            $mailer->isHTML(false);
        }

        $mailer->setFrom($from_email, $from_name, false);

        foreach ($to as $address) {
            $parsed = self::parse_email_header_address($address);
            if (!empty($parsed['email'])) {
                $mailer->addAddress($parsed['email'], $parsed['name']);
            }
        }

        foreach ($attachments as $attachment) {
            $attachment = (string) $attachment;
            if ($attachment !== '' && file_exists($attachment) && is_readable($attachment)) {
                $mailer->addAttachment($attachment);
            }
        }
    }

    private static function parse_email_header_address($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return ['email' => '', 'name' => ''];
        }

        if (preg_match('/^(.*)<([^>]+)>$/', $value, $m)) {
            return [
                'name'  => trim(str_replace(['"', "'"], '', $m[1])),
                'email' => sanitize_email(trim($m[2])),
            ];
        }

        return [
            'name'  => '',
            'email' => sanitize_email($value),
        ];
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
        $clean['mode']        = in_array($data['mode'] ?? '', ['php', 'smtp', 'resend_api', 'dev', 'fluent_smtp']) ? $data['mode'] : 'php';
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
        $clean['einvoice_report_include_blogs']  = array_values(array_map('intval', array_filter((array) ($data['einvoice_report_include_blogs'] ?? []))));

        update_site_option(self::OPTION_KEY, $clean);
        return $clean;
    }

    /** Test gửi email qua SMTP config hiện tại */
    public static function send_test($to_email)
    {
        if (!is_email($to_email)) {
            return ['success' => false, 'message' => 'Email không hợp lệ'];
        }

        // Re-apply hooks theo settings mới nhất trong cùng request hiện tại.
        remove_filter('pre_wp_mail', [__CLASS__, 'intercept_wp_mail'], 1);
        add_filter('pre_wp_mail', [__CLASS__, 'intercept_wp_mail'], 1, 2);

        $settings = self::get();

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
            } elseif ($settings['mode'] === 'fluent_smtp') {
                $msg .= ' (qua Fluent SMTP)';
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
