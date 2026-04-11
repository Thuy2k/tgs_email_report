<?php
/**
 * Collector: Shop Sales — Doanh thu bán hàng từng shop
 *
 * Data source:
 *   - tgs_fact_sales_daily (from tgs_rollup_analytics)
 *   - Fallback: local_ledger trực tiếp nếu rollup chưa chạy
 *
 * Trả về mảng:
 *   [blog_id => [
 *       'shop_name', 'shop_code',
 *       'order_count', 'gross_revenue', 'net_revenue', 'discount_value',
 *       'return_value', 'cogs_value', 'gross_profit', 'gross_margin_pct',
 *       'customer_count', 'new_customer_count', 'avg_order_value',
 *   ]]
 */

if (!defined('ABSPATH')) exit;

class TGS_Collector_Shop_Sales extends TGS_Collector_Base
{
    public static function collect($date_from, $date_to)
    {
        global $wpdb;

        $result = [];
        $shop_names = self::get_shop_names();

        // Ưu tiên đọc từ bảng rollup (nhanh hơn nhiều)
        $fact_table = $wpdb->base_prefix . 'tgs_fact_sales_daily';
        $has_rollup = ($wpdb->get_var("SHOW TABLES LIKE '{$fact_table}'") === $fact_table);

        if ($has_rollup) {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT blog_id,
                        SUM(order_count) as order_count,
                        SUM(gross_revenue) as gross_revenue,
                        SUM(net_revenue) as net_revenue,
                        SUM(discount_value) as discount_value,
                        SUM(gift_value) as gift_value,
                        SUM(returned_value) as return_value,
                        SUM(cogs_value) as cogs_value,
                        SUM(gross_profit) as gross_profit,
                        AVG(gross_margin_pct) as gross_margin_pct,
                        SUM(customer_count) as customer_count,
                        SUM(new_customer_count) as new_customer_count,
                        AVG(avg_order_value) as avg_order_value,
                        AVG(avg_items_per_order) as avg_items_per_order
                 FROM {$fact_table}
                 WHERE rollup_date BETWEEN %s AND %s
                 GROUP BY blog_id
                 ORDER BY gross_revenue DESC",
                $date_from, $date_to
            ));

            foreach ($rows as $r) {
                $info = $shop_names[$r->blog_id] ?? ['code' => 'SHOP-' . $r->blog_id, 'name' => 'Shop #' . $r->blog_id];
                $result[$r->blog_id] = [
                    'shop_name'          => $info['name'],
                    'shop_code'          => $info['code'],
                    'order_count'        => (int) $r->order_count,
                    'gross_revenue'      => (float) $r->gross_revenue,
                    'net_revenue'        => (float) $r->net_revenue,
                    'discount_value'     => (float) $r->discount_value,
                    'gift_value'         => (float) $r->gift_value,
                    'return_value'       => (float) $r->return_value,
                    'cogs_value'         => (float) $r->cogs_value,
                    'gross_profit'       => (float) $r->gross_profit,
                    'gross_margin_pct'   => round((float) $r->gross_margin_pct, 2),
                    'customer_count'     => (int) $r->customer_count,
                    'new_customer_count' => (int) $r->new_customer_count,
                    'avg_order_value'    => (float) $r->avg_order_value,
                    'avg_items_per_order' => round((float) $r->avg_items_per_order, 1),
                ];
            }

            return $result;
        }

        // ── Fallback: query trực tiếp local_ledger của từng site ──
        $blogs = self::get_active_blog_ids();
        foreach ($blogs as $blog) {
            $prefix = self::get_blog_prefix($blog->blog_id);
            $ledger = $prefix . 'local_ledger';

            // Kiểm tra bảng tồn tại
            if ($wpdb->get_var("SHOW TABLES LIKE '{$ledger}'") !== $ledger) {
                continue;
            }

            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT
                    COUNT(DISTINCT CASE WHEN local_ledger_type = 10 THEN local_ledger_id END) as order_count,
                    COALESCE(SUM(CASE WHEN local_ledger_type = 10 THEN local_ledger_total_amount ELSE 0 END), 0) as gross_revenue,
                    COALESCE(SUM(CASE WHEN local_ledger_type = 10 THEN local_ledger_discount ELSE 0 END), 0) as discount_value,
                    COALESCE(SUM(CASE WHEN local_ledger_type = 11 THEN local_ledger_total_amount ELSE 0 END), 0) as return_value,
                    COUNT(DISTINCT CASE WHEN local_ledger_type = 10 THEN local_ledger_person_id END) as customer_count
                 FROM {$ledger}
                 WHERE local_ledger_type IN (10, 11)
                   AND local_ledger_status IN (2, 4)
                   AND is_deleted = 0
                   AND DATE(created_at) BETWEEN %s AND %s",
                $date_from, $date_to
            ));

            if (!$row || (int) $row->order_count === 0) {
                continue;
            }

            $info = $shop_names[$blog->blog_id] ?? ['code' => 'SHOP-' . $blog->blog_id, 'name' => 'Shop #' . $blog->blog_id];
            $net = (float) $row->gross_revenue - (float) $row->discount_value - (float) $row->return_value;

            $result[$blog->blog_id] = [
                'shop_name'          => $info['name'],
                'shop_code'          => $info['code'],
                'order_count'        => (int) $row->order_count,
                'gross_revenue'      => (float) $row->gross_revenue,
                'net_revenue'        => $net,
                'discount_value'     => (float) $row->discount_value,
                'gift_value'         => 0,
                'return_value'       => (float) $row->return_value,
                'cogs_value'         => 0,
                'gross_profit'       => $net,
                'gross_margin_pct'   => 0,
                'customer_count'     => (int) $row->customer_count,
                'new_customer_count' => 0,
                'avg_order_value'    => $row->order_count > 0 ? round($net / $row->order_count) : 0,
                'avg_items_per_order' => 0,
            ];
        }

        // Sắp xếp theo doanh thu giảm dần
        uasort($result, function ($a, $b) {
            return $b['gross_revenue'] <=> $a['gross_revenue'];
        });

        return $result;
    }
}
