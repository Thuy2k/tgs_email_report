<?php
/**
 * Collector: Summary — Tổng hợp toàn hệ thống + so sánh tuần
 *
 * Gom data từ collector doanh thu shop để phần tổng quan, top shop
 * và so sánh tuần cùng dùng chung một nguồn số liệu.
 */

if (!defined('ABSPATH')) exit;

class TGS_Collector_Summary extends TGS_Collector_Base
{
    public static function collect($date_from, $date_to)
    {
        $shop_names = self::get_all_shop_names();
        $daily_sales = TGS_Collector_Shop_Sales::collect($date_from, $date_to);
        $weekly_compare = self::build_weekly_compare($date_to);
        $result = [
            'shops'        => [],  // tên shops (for reference)
            'daily_total'  => [],  // tổng hệ thống ngày
            'weekly_compare' => [], // so sánh tuần này vs tuần trước
            'top_shops'    => [],  // top 5 shop doanh thu
            'alerts'       => [],  // các cảnh báo quan trọng
        ];

        $result['shops'] = $shop_names;
        $result['daily_total'] = self::aggregate_sales_totals($daily_sales);
        $result['weekly_compare'] = $weekly_compare;
        $result['top_shops'] = self::build_top_shops($daily_sales, $shop_names);
        $result['alerts'] = self::build_alerts($date_from, $date_to, $daily_sales, $weekly_compare, $shop_names);

        return $result;
    }

    protected static function get_all_shop_names()
    {
        $shop_names = self::get_shop_names();
        if (!empty($shop_names)) {
            return $shop_names;
        }

        $fallback = [];
        foreach (self::get_active_blog_ids() as $blog) {
            $fallback[$blog->blog_id] = [
                'code' => 'SHOP-' . $blog->blog_id,
                'name' => 'Shop #' . $blog->blog_id,
            ];
        }

        return $fallback;
    }

    protected static function aggregate_sales_totals($sales_rows)
    {
        $totals = [
            'total_orders' => 0,
            'total_gross' => 0.0,
            'total_net' => 0.0,
            'total_discount' => 0.0,
            'total_return' => 0.0,
            'total_cogs' => 0.0,
            'total_profit' => 0.0,
            'total_customers' => 0,
            'active_shops' => 0,
        ];

        foreach ($sales_rows as $row) {
            $orders = (int) ($row['order_count'] ?? 0);
            $totals['total_orders'] += $orders;
            $totals['total_gross'] += (float) ($row['gross_revenue'] ?? 0);
            $totals['total_net'] += (float) ($row['net_revenue'] ?? 0);
            $totals['total_discount'] += (float) ($row['discount_value'] ?? 0);
            $totals['total_return'] += (float) ($row['return_value'] ?? 0);
            $totals['total_cogs'] += (float) ($row['cogs_value'] ?? 0);
            $totals['total_profit'] += (float) ($row['gross_profit'] ?? 0);
            $totals['total_customers'] += (int) ($row['customer_count'] ?? 0);

            if ($orders > 0) {
                $totals['active_shops']++;
            }
        }

        return $totals;
    }

    protected static function build_weekly_compare($date_to)
    {
        $week_start = self::start_of_week($date_to);
        $prev_week_start = date('Y-m-d', strtotime('-7 days', strtotime($week_start)));
        $prev_week_end = date('Y-m-d', strtotime('-1 day', strtotime($week_start)));

        $tw_sales = TGS_Collector_Shop_Sales::collect($week_start, $date_to);
        $pw_sales = TGS_Collector_Shop_Sales::collect($prev_week_start, $prev_week_end);
        $this_week_totals = self::aggregate_sales_totals($tw_sales);
        $prev_week_totals = self::aggregate_sales_totals($pw_sales);

        $tw_gifts = TGS_Collector_Shop_Gifts::collect($week_start, $date_to);
        $pw_gifts = TGS_Collector_Shop_Gifts::collect($prev_week_start, $prev_week_end);

        // Per-shop merge
        $all_blog_ids = array_unique(array_merge(array_keys($tw_sales), array_keys($pw_sales)));
        $shop_names   = self::get_shop_names();
        $by_shop = [];
        foreach ($all_blog_ids as $bid) {
            $tw = $tw_sales[$bid] ?? [];
            $pw = $pw_sales[$bid] ?? [];
            $info = $shop_names[$bid] ?? ['name' => 'Shop #' . $bid, 'code' => 'SHOP-' . $bid];
            $by_shop[$bid] = [
                'shop_name'          => $info['name'],
                'this_week_net'      => (float) ($tw['net_revenue'] ?? 0),
                'this_week_orders'   => (int)   ($tw['order_count'] ?? 0),
                'this_week_gift_qty' => (float) ($tw_gifts['by_shop'][$bid]['gift_qty'] ?? 0),
                'this_week_gift_value' => (float) ($tw_gifts['by_shop'][$bid]['gift_value'] ?? 0),
                'prev_week_net'      => (float) ($pw['net_revenue'] ?? 0),
                'prev_week_orders'   => (int)   ($pw['order_count'] ?? 0),
                'prev_week_gift_qty' => (float) ($pw_gifts['by_shop'][$bid]['gift_qty'] ?? 0),
                'prev_week_gift_value' => (float) ($pw_gifts['by_shop'][$bid]['gift_value'] ?? 0),
            ];
        }
        // Sort by this_week_net desc
        uasort($by_shop, fn($a, $b) => $b['this_week_net'] <=> $a['this_week_net']);

        $tw_net = (float) $this_week_totals['total_net'];
        $pw_net = (float) $prev_week_totals['total_net'];
        $change_pct = $pw_net > 0 ? round((($tw_net - $pw_net) / $pw_net) * 100, 1) : 0;

        return [
            'this_week_net'        => $tw_net,
            'this_week_orders'     => (int) $this_week_totals['total_orders'],
            'this_week_gift_qty'   => (float) ($tw_gifts['summary']['total_qty'] ?? 0),
            'this_week_gift_value' => (float) ($tw_gifts['summary']['total_value'] ?? 0),
            'prev_week_net'        => $pw_net,
            'prev_week_orders'     => (int) $prev_week_totals['total_orders'],
            'prev_week_gift_qty'   => (float) ($pw_gifts['summary']['total_qty'] ?? 0),
            'prev_week_gift_value' => (float) ($pw_gifts['summary']['total_value'] ?? 0),
            'change_pct'           => $change_pct,
            'week_start'           => $week_start,
            'prev_week_start'      => $prev_week_start,
            'prev_week_end'        => $prev_week_end,
            'by_shop'              => $by_shop,
        ];
    }

    protected static function build_top_shops($sales_rows, $shop_names)
    {
        $top_shops = [];

        foreach ($sales_rows as $blog_id => $row) {
            $info = $shop_names[$blog_id] ?? [
                'code' => $row['shop_code'] ?? 'SHOP-' . $blog_id,
                'name' => $row['shop_name'] ?? 'Shop #' . $blog_id,
            ];

            $top_shops[] = [
                'blog_id' => $blog_id,
                'shop_name' => $info['name'],
                'order_count' => (int) ($row['order_count'] ?? 0),
                'net' => (float) ($row['net_revenue'] ?? 0),
            ];
        }

        usort($top_shops, function ($left, $right) {
            return $right['net'] <=> $left['net'];
        });

        return array_slice($top_shops, 0, 5);
    }

    protected static function build_alerts($date_from, $date_to, $daily_sales, $weekly_compare, $shop_names)
    {
        $alerts = [];

        if ($date_from === $date_to) {
            $active_today = array_fill_keys(array_keys($daily_sales), true);

            foreach ($shop_names as $blog_id => $info) {
                if (isset($active_today[$blog_id])) {
                    continue;
                }

                if (self::has_confirmed_sales($blog_id, $date_from, $date_to)) {
                    continue;
                }

                $alerts[] = [
                    'type' => 'no_sale',
                    'level' => 'warning',
                    'message' => $info['name'] . ' — không có doanh thu ngày ' . date('d/m', strtotime($date_to)),
                    'blog_id' => $blog_id,
                ];
            }
        }

        if (($weekly_compare['change_pct'] ?? 0) < -20) {
            $alerts[] = [
                'type' => 'revenue_drop',
                'level' => 'danger',
                'message' => sprintf('Doanh thu tuần giảm %.1f%% so với tuần trước!', abs($weekly_compare['change_pct'])),
            ];
        }

        return $alerts;
    }

    protected static function has_confirmed_sales($blog_id, $date_from, $date_to)
    {
        global $wpdb;

        $ledger = self::get_blog_prefix($blog_id) . 'local_ledger';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$ledger}'") !== $ledger) {
            return false;
        }

        $sales_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(1)
             FROM {$ledger}
             WHERE local_ledger_type = 10
               AND local_ledger_status IN (2, 4)
               AND is_deleted = 0
               AND DATE(created_at) BETWEEN %s AND %s",
            $date_from, $date_to
        ));

        return (int) $sales_count > 0;
    }
}
