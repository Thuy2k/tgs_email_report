<?php
/**
 * TGS Email Trigger — URL endpoint để gọi gửi email mà không cần đăng nhập admin
 *
 * URL dạng:  https://domain.com/?tgs_email_trigger=1&type=shop&secret=xxx
 *            https://domain.com/?tgs_email_trigger=1&type=warehouse&secret=xxx
 *            https://domain.com/?tgs_email_trigger=1&type=all&secret=xxx
 *
 * Secret được lưu trong option `tgs_email_trigger_secret`.
 * Dùng cho cron job server hoặc link tay (ấn vào = gửi).
 */

if (!defined('ABSPATH')) exit;

class TGS_Email_Trigger
{
    const OPTION_SECRET = 'tgs_email_trigger_secret';

    public static function register()
    {
        add_action('init', [__CLASS__, 'listen'], 1);
    }

    /**
     * Lắng nghe request `?tgs_email_trigger=1`
     */
    public static function listen()
    {
        if (!isset($_GET['tgs_email_trigger'])) {
            return;
        }

        // Verify secret
        $secret = sanitize_text_field($_GET['secret'] ?? '');
        $stored_secret = get_site_option(self::OPTION_SECRET, '');

        // Nếu chưa có secret → tạo mới
        if (empty($stored_secret)) {
            $stored_secret = wp_generate_password(32, false);
            update_site_option(self::OPTION_SECRET, $stored_secret);
        }

        // Nếu user đang login & là admin → cho phép không cần secret
        $is_admin = is_user_logged_in() && current_user_can('manage_options');

        if (!$is_admin && !hash_equals($stored_secret, $secret)) {
            wp_die('Unauthorized. Secret không đúng.', 'Forbidden', ['response' => 403]);
        }

        $type      = sanitize_text_field($_GET['type'] ?? 'all');
        $date_from = sanitize_text_field($_GET['date_from'] ?? current_time('Y-m-d'));
        $date_to   = sanitize_text_field($_GET['date_to'] ?? current_time('Y-m-d'));

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) $date_from = current_time('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) $date_to = current_time('Y-m-d');

        $uid     = get_current_user_id();
        $trigger = $is_admin ? 'url_admin' : 'url_cron';
        $results = [];

        if ($type === 'shop' || $type === 'all') {
            $results['shop'] = TGS_Email_Sender::send_shop_report($date_from, $date_to, $trigger, $uid);
        }
        if ($type === 'warehouse' || $type === 'all') {
            $results['warehouse'] = TGS_Email_Sender::send_warehouse_report($date_from, $date_to, $trigger, $uid);
        }

        // Trả JSON
        header('Content-Type: application/json; charset=utf-8');
        echo wp_json_encode([
            'success'   => true,
            'type'      => $type,
            'date_from' => $date_from,
            'date_to'   => $date_to,
            'results'   => $results,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Lấy URL trigger với secret
     */
    public static function get_trigger_url($type = 'all', $date_from = '', $date_to = '')
    {
        $secret = get_site_option(self::OPTION_SECRET, '');
        if (empty($secret)) {
            $secret = wp_generate_password(32, false);
            update_site_option(self::OPTION_SECRET, $secret);
        }

        $args = [
            'tgs_email_trigger' => 1,
            'type'   => $type,
            'secret' => $secret,
        ];
        if ($date_from) $args['date_from'] = $date_from;
        if ($date_to)   $args['date_to']   = $date_to;

        return add_query_arg($args, home_url('/'));
    }
}
