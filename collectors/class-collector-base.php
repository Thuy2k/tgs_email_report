<?php
/**
 * Collector Base — Abstract class cho tất cả data collector
 *
 * Mỗi collector con chỉ cần implement:
 *   static function collect($date_from, $date_to): array
 */

if (!defined('ABSPATH')) exit;

abstract class TGS_Collector_Base
{
    /**
     * Thu thập dữ liệu — return mảng kết quả
     *
     * @param string $date_from  Y-m-d
     * @param string $date_to    Y-m-d
     * @return array
     */
    abstract public static function collect($date_from, $date_to);

    /* ── Helper: lấy danh sách blog_id active ── */
    protected static function get_active_blog_ids()
    {
        global $wpdb;

        // Chỉ lấy các site public & not archived & not deleted
        $blogs = $wpdb->get_results(
            "SELECT blog_id, domain, path
             FROM {$wpdb->blogs}
             WHERE archived = 0 AND deleted = 0 AND spam = 0
             ORDER BY blog_id ASC"
        );

        return $blogs ?: [];
    }

    /* ── Helper: lấy tên shop từ wp_blogs + blogname (WordPress native) ── */
    protected static function get_shop_names()
    {
        global $wpdb;

        $blogs = $wpdb->get_results(
            "SELECT blog_id, domain, path
             FROM {$wpdb->blogs}
             WHERE archived = 0 AND deleted = 0 AND spam = 0
             ORDER BY blog_id ASC"
        );

        if (!$blogs) return [];

        $map = [];
        foreach ($blogs as $b) {
            $prefix = $wpdb->get_blog_prefix($b->blog_id);
            $name = $wpdb->get_var(
                "SELECT option_value FROM {$prefix}options WHERE option_name = 'blogname' LIMIT 1"
            );
            $map[$b->blog_id] = [
                'code' => 'SHOP-' . $b->blog_id,
                'name' => $name ?: $b->domain,
            ];
        }
        return $map;
    }

    /* ── Helper: switch blog & lấy prefix ── */
    protected static function get_blog_prefix($blog_id)
    {
        global $wpdb;
        return $wpdb->get_blog_prefix($blog_id);
    }

    /* ── Helper: format tiền VND ── */
    protected static function format_money($amount)
    {
        return number_format((float) $amount, 0, ',', '.');
    }

    /* ── Helper: ngày hôm nay ── */
    protected static function today()
    {
        return current_time('Y-m-d');
    }

    /* ── Helper: đầu tuần (thứ 2) ── */
    protected static function start_of_week($date = null)
    {
        $d = $date ? strtotime($date) : current_time('timestamp');
        $day_of_week = date('N', $d); // 1=Mon, 7=Sun
        return date('Y-m-d', strtotime('-' . ($day_of_week - 1) . ' days', $d));
    }
}
