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
    abstract public static function collect($date_from, $date_to);

    /** Blog ID filter — empty = tất cả shop */
    protected static $blog_id_filter = [];

    public static function set_blog_filter(array $blog_ids)
    {
        // Lưu vào global để tất cả child class dùng chung (tránh lỗi static inheritance)
        $GLOBALS['_tgs_blog_id_filter'] = array_values(array_unique(array_map('intval', array_filter($blog_ids))));
        self::$blog_id_filter = $GLOBALS['_tgs_blog_id_filter'];
    }

    public static function get_blog_filter()
    {
        return $GLOBALS['_tgs_blog_id_filter'] ?? [];
    }

    /**
     * Trả về SQL snippet " AND {col} IN (1,2,3)" khi có filter, chuỗi rỗng nếu không.
     * @param string $alias Alias bảng (vd: 'inv'). Để trống nếu không cần prefix.
     */
    public static function blog_filter_sql(string $alias = ''): string
    {
        $filter = $GLOBALS['_tgs_blog_id_filter'] ?? [];
        if (empty($filter)) return '';
        $col = $alias ? "{$alias}.blog_id" : 'blog_id';
        $ids = implode(',', $filter);
        return " AND {$col} IN ({$ids})";
    }

    /* ── Helper: lấy danh sách blog_id active (đã áp dụng filter) ── */
    protected static function get_active_blog_ids()
    {
        global $wpdb;

        $blogs = $wpdb->get_results(
            "SELECT blog_id, domain, path
             FROM {$wpdb->blogs}
             WHERE archived = 0 AND deleted = 0 AND spam = 0
             ORDER BY blog_id ASC"
        );

        $blogs = $blogs ?: [];

        $filter = $GLOBALS['_tgs_blog_id_filter'] ?? [];
        if (!empty($filter)) {
            $blogs = array_values(array_filter($blogs, fn($b) => in_array((int) $b->blog_id, $filter, true)));
        }

        return $blogs;
    }

    /* ── Helper: lấy tên shop theo tiêu đề site WP + mã shop từ tgs_dim_shop ── */
    protected static function get_shop_names()
    {
        global $wpdb;
        $dim = $wpdb->base_prefix . 'tgs_dim_shop';
        $blogs = self::get_active_blog_ids();
        $dim_map = [];

        if ($wpdb->get_var("SHOW TABLES LIKE '{$dim}'") !== $dim) {
            foreach ($blogs as $blog) {
                $dim_map[$blog->blog_id] = [
                    'code' => 'SHOP-' . $blog->blog_id,
                    'name' => '',
                ];
            }
        } else {
            $rows = $wpdb->get_results("SELECT blog_id, shop_code, shop_name FROM {$dim} WHERE status = 1");
            foreach ($rows as $r) {
                $dim_map[$r->blog_id] = [
                    'code' => $r->shop_code,
                    'name' => $r->shop_name,
                ];
            }
        }

        $map = [];
        foreach ($blogs as $blog) {
            $blog_id = (int) $blog->blog_id;
            $site_title = self::get_site_title($blog_id);
            $dim_info = $dim_map[$blog_id] ?? [
                'code' => 'SHOP-' . $blog_id,
                'name' => '',
            ];

            $map[$blog_id] = [
                'code' => $dim_info['code'] ?: 'SHOP-' . $blog_id,
                'name' => $site_title ?: ($dim_info['name'] ?: 'Shop #' . $blog_id),
            ];
        }

        return $map;
    }

    protected static function get_site_title($blog_id)
    {
        global $wpdb;

        $title = '';
        if (function_exists('get_blog_option')) {
            $title = (string) get_blog_option($blog_id, 'blogname', '');
        }

        if ($title === '') {
            $options_table = $wpdb->get_blog_prefix($blog_id) . 'options';
            if ($wpdb->get_var("SHOW TABLES LIKE '{$options_table}'") === $options_table) {
                $title = (string) $wpdb->get_var($wpdb->prepare(
                    "SELECT option_value FROM {$options_table} WHERE option_name = %s LIMIT 1",
                    'blogname'
                ));
            }
        }

        return self::clean_text($title);
    }

    protected static function clean_text($value)
    {
        $value = is_scalar($value) ? (string) $value : '';
        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim((string) $value);
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

    /* ── Helper: bảng config MIN/MAX (dùng base_prefix, không hardcode) ── */
    protected static function config_table()
    {
        global $wpdb;
        return $wpdb->base_prefix . 'global_sku_stock_config';
    }

    /* ── Helper: bảng reorder suggestion ── */
    protected static function reorder_table()
    {
        global $wpdb;
        return $wpdb->base_prefix . 'global_reorder_suggestion';
    }

    /**
     * Tạo INNER JOIN subquery lấy bản ghi inventory mới nhất (as-of $date_to)
     * cho mỗi (blog_id, sku, exp_date).
     *
     * Cùng logic với DashboardQueryService::inventory_latest_join() trong rollup analytics.
     * Alias bảng fact phải là "inv", subquery alias là "latest".
     *
     * @param string $inv_table  Tên bảng fact_inventory_daily (đã escape)
     * @param string $date_to    Y-m-d — ngày snapshot
     * @return string  SQL INNER JOIN clause (chưa prepare, date_to được nhúng trực tiếp)
     */
    protected static function inventory_latest_join($inv_table, $date_to)
    {
        global $wpdb;
        $safe_date = esc_sql($date_to);
        return "INNER JOIN (
                SELECT blog_id, sku, exp_date, MAX(rollup_date) AS latest_date
                FROM {$inv_table}
                WHERE rollup_date <= '{$safe_date}'
                GROUP BY blog_id, sku, exp_date
            ) latest
                ON latest.blog_id = inv.blog_id
                AND latest.sku = inv.sku
                AND latest.exp_date <=> inv.exp_date
                AND latest.latest_date = inv.rollup_date";
    }
}
