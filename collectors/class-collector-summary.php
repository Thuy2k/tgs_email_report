<?php
/**
 * Collector: Summary — Tổng hợp toàn hệ thống + so sánh tuần
 *
 * Gom data từ tgs_fact_sales_daily + tgs_fact_inventory_daily
 * → tổng doanh thu, tổng tồn, so sánh với tuần trước
 */

if (!defined('ABSPATH')) exit;

class TGS_Collector_Summary extends TGS_Collector_Base
{
    public static function collect($date_from, $date_to)
    {
        global $wpdb;

        $result = [
            'shops'        => [],  // tên shops (for reference)
            'daily_total'  => [],  // tổng hệ thống ngày
            'weekly_compare' => [], // so sánh tuần này vs tuần trước
            'top_shops'    => [],  // top 5 shop doanh thu
            'alerts'       => [],  // các cảnh báo quan trọng
        ];

        $shop_names = self::get_shop_names();
        $result['shops'] = $shop_names;

        $fact_table = $wpdb->base_prefix . 'tgs_fact_sales_daily';
        $has_rollup = ($wpdb->get_var("SHOW TABLES LIKE '{$fact_table}'") === $fact_table);

        if (!$has_rollup) {
            return $result;
        }

        // ── 1) Tổng hệ thống trong khoảng ngày ──
        $total = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(order_count) as total_orders,
                SUM(gross_revenue) as total_gross,
                SUM(net_revenue) as total_net,
                SUM(discount_value) as total_discount,
                SUM(returned_value) as total_return,
                SUM(cogs_value) as total_cogs,
                SUM(gross_profit) as total_profit,
                SUM(customer_count) as total_customers,
                COUNT(DISTINCT blog_id) as active_shops
             FROM {$fact_table}
             WHERE rollup_date BETWEEN %s AND %s",
            $date_from, $date_to
        ));

        $result['daily_total'] = [
            'total_orders'    => (int) ($total->total_orders ?? 0),
            'total_gross'     => (float) ($total->total_gross ?? 0),
            'total_net'       => (float) ($total->total_net ?? 0),
            'total_discount'  => (float) ($total->total_discount ?? 0),
            'total_return'    => (float) ($total->total_return ?? 0),
            'total_cogs'      => (float) ($total->total_cogs ?? 0),
            'total_profit'    => (float) ($total->total_profit ?? 0),
            'total_customers' => (int) ($total->total_customers ?? 0),
            'active_shops'    => (int) ($total->active_shops ?? 0),
        ];

        // ── 2) So sánh tuần: tuần này vs tuần trước ──
        $week_start = self::start_of_week($date_to);
        $prev_week_start = date('Y-m-d', strtotime('-7 days', strtotime($week_start)));
        $prev_week_end   = date('Y-m-d', strtotime('-1 day', strtotime($week_start)));

        $this_week = $wpdb->get_row($wpdb->prepare(
            "SELECT SUM(net_revenue) as net, SUM(order_count) as orders, SUM(gross_profit) as profit
             FROM {$fact_table}
             WHERE rollup_date BETWEEN %s AND %s",
            $week_start, $date_to
        ));

        $prev_week = $wpdb->get_row($wpdb->prepare(
            "SELECT SUM(net_revenue) as net, SUM(order_count) as orders, SUM(gross_profit) as profit
             FROM {$fact_table}
             WHERE rollup_date BETWEEN %s AND %s",
            $prev_week_start, $prev_week_end
        ));

        $tw_net = (float) ($this_week->net ?? 0);
        $pw_net = (float) ($prev_week->net ?? 0);
        $change_pct = $pw_net > 0 ? round((($tw_net - $pw_net) / $pw_net) * 100, 1) : 0;

        $result['weekly_compare'] = [
            'this_week_net'     => $tw_net,
            'this_week_orders'  => (int) ($this_week->orders ?? 0),
            'this_week_profit'  => (float) ($this_week->profit ?? 0),
            'prev_week_net'     => $pw_net,
            'prev_week_orders'  => (int) ($prev_week->orders ?? 0),
            'prev_week_profit'  => (float) ($prev_week->profit ?? 0),
            'change_pct'        => $change_pct,
            'week_start'        => $week_start,
            'prev_week_start'   => $prev_week_start,
            'prev_week_end'     => $prev_week_end,
        ];

        // ── 3) Top 5 shop ──
        $tops = $wpdb->get_results($wpdb->prepare(
            "SELECT blog_id, SUM(net_revenue) as net
             FROM {$fact_table}
             WHERE rollup_date BETWEEN %s AND %s
             GROUP BY blog_id
             ORDER BY net DESC
             LIMIT 5",
            $date_from, $date_to
        ));

        foreach ($tops as $t) {
            $info = $shop_names[$t->blog_id] ?? ['code' => '', 'name' => 'Shop #' . $t->blog_id];
            $result['top_shops'][] = [
                'blog_id'   => $t->blog_id,
                'shop_name' => $info['name'],
                'net'       => (float) $t->net,
            ];
        }

        // ── 4) Cảnh báo tự động ──
        // Shop không có doanh thu hôm nay
        if ($date_from === $date_to) {
            $active_today = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT blog_id FROM {$fact_table} WHERE rollup_date = %s",
                $date_to
            ));
            $all_shops = array_keys($shop_names);
            $inactive = array_diff($all_shops, $active_today);

            foreach ($inactive as $bid) {
                $info = $shop_names[$bid] ?? ['code' => '', 'name' => 'Shop #' . $bid];
                $result['alerts'][] = [
                    'type'    => 'no_sale',
                    'level'   => 'warning',
                    'message' => $info['name'] . ' — không có doanh thu ngày ' . date('d/m', strtotime($date_to)),
                    'blog_id' => $bid,
                ];
            }
        }

        // Doanh thu giảm >20% so với tuần trước
        if ($change_pct < -20) {
            $result['alerts'][] = [
                'type'    => 'revenue_drop',
                'level'   => 'danger',
                'message' => sprintf('Doanh thu tuần giảm %.1f%% so với tuần trước!', abs($change_pct)),
            ];
        }

        return $result;
    }
}
