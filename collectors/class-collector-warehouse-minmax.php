<?php
/**
 * Collector: Warehouse MIN/MAX — Cảnh báo tồn kho dưới MIN hoặc vượt MAX
 *
 * Data source:
 *   - tgs_fact_inventory_daily (closing_qty — snapshot mới nhất as-of date_to)
 *   - global_sku_stock_config (min_qty, max_qty)
 *   - global_reorder_suggestion (đề xuất mua)
 *
 * Dùng inventory_latest_join() để lấy snapshot mới nhất cho mỗi
 * (blog_id, sku, exp_date) — không bỏ sót SKU không có movement ngày cuối.
 */

if (!defined('ABSPATH')) exit;

class TGS_Collector_Warehouse_MinMax extends TGS_Collector_Base
{
    public static function collect($date_from, $date_to)
    {
        global $wpdb;

        $result = [
            'below_min'     => [], // SP dưới MIN
            'above_max'     => [], // SP vượt MAX
            'stockout'      => [], // SP hết hàng (closing_qty = 0, có cấu hình)
            'near_expiry'   => [], // SP sắp hết hạn (30 ngày)
            'reorder_suggestions' => [], // Đề xuất mua
            'by_shop'       => [], // Gom theo shop: blog_id => {shop_name, below_min[], above_max[], stockout[]}
            'summary'       => [
                'total_below_min'  => 0,
                'total_above_max'  => 0,
                'total_stockout'   => 0,
                'total_near_expiry' => 0,
                'total_reorder'    => 0,
            ],
        ];

        $shop_names   = self::get_shop_names();
        $config_table = self::config_table();
        $inv_table    = $wpdb->base_prefix . 'tgs_fact_inventory_daily';
        $prod_table   = $wpdb->base_prefix . 'tgs_dim_product';
        $reorder_tbl  = self::reorder_table();

        $has_config  = ($wpdb->get_var("SHOW TABLES LIKE '{$config_table}'") === $config_table);
        $has_inv     = ($wpdb->get_var("SHOW TABLES LIKE '{$inv_table}'") === $inv_table);
        $has_prod    = ($wpdb->get_var("SHOW TABLES LIKE '{$prod_table}'") === $prod_table);
        $has_reorder = ($wpdb->get_var("SHOW TABLES LIKE '{$reorder_tbl}'") === $reorder_tbl);

        $latest_join = $has_inv ? self::inventory_latest_join($inv_table, $date_to) : '';
        $prod_join   = $has_prod ? "LEFT JOIN {$prod_table} p ON p.sku = inv.sku" : '';

        // Helper: khởi tạo mảng shop trong by_shop
        $ensure_shop = function ($blog_id) use (&$result, $shop_names) {
            if (!isset($result['by_shop'][$blog_id])) {
                $info = $shop_names[$blog_id] ?? ['code' => '', 'name' => 'Shop #' . $blog_id];
                $result['by_shop'][$blog_id] = [
                    'shop_name'       => $info['name'],
                    'below_min'       => [],
                    'above_max'       => [],
                    'stockout'        => [],
                    'total_below_min' => 0,
                    'total_above_max' => 0,
                    'total_stockout'  => 0,
                ];
            }
        };

        // ── 1) Dưới MIN (closing_qty > 0 nhưng < min_qty) ──
        if ($has_config && $has_inv) {
            $date_from_speed = date('Y-m-d', strtotime($date_to . ' -30 days'));
            $safe_from_speed = esc_sql($date_from_speed);
            $safe_to         = esc_sql($date_to);
            $blog_sql        = self::blog_filter_sql('inv');

            $below = $wpdb->get_results(
                "SELECT
                    inv.blog_id, inv.sku,
                    COALESCE(p.product_name, inv.sku) as product_name,
                    SUM(inv.closing_qty) as closing_qty,
                    cfg.min_qty, cfg.max_qty,
                    (cfg.min_qty - SUM(inv.closing_qty)) as shortage,
                    COALESCE(spd.total_out, 0) as total_out,
                    COALESCE(spd.period_days, 0) as period_days
                 FROM {$inv_table} inv
                 {$latest_join}
                 INNER JOIN {$config_table} cfg
                    ON inv.blog_id = cfg.blog_id AND inv.sku = cfg.product_sku
                 {$prod_join}
                 LEFT JOIN (
                    SELECT blog_id, sku,
                           SUM(out_qty) as total_out,
                           COUNT(DISTINCT rollup_date) as period_days
                    FROM {$inv_table}
                    WHERE rollup_date BETWEEN '{$safe_from_speed}' AND '{$safe_to}'
                      AND out_qty > 0
                    GROUP BY blog_id, sku
                 ) spd ON spd.blog_id = inv.blog_id AND spd.sku = inv.sku
                 WHERE cfg.is_active = 1
                   AND cfg.site_type = 1
                   AND cfg.min_qty > 0
                   {$blog_sql}
                 GROUP BY inv.blog_id, inv.sku, p.product_name, cfg.min_qty, cfg.max_qty, spd.total_out, spd.period_days
                 HAVING closing_qty < cfg.min_qty AND closing_qty > 0
                 ORDER BY shortage DESC
                 LIMIT 300"
            );

            foreach ($below as $r) {
                $info      = $shop_names[$r->blog_id] ?? ['code' => '', 'name' => 'Shop #' . $r->blog_id];
                $days      = (int) $r->period_days;
                $sell_speed = $days > 0 ? round((float) $r->total_out / $days, 2) : 0.0;
                $item = [
                    'blog_id'      => $r->blog_id,
                    'shop_name'    => $info['name'],
                    'sku'          => $r->sku,
                    'product_name' => $r->product_name,
                    'closing_qty'  => (float) $r->closing_qty,
                    'min_qty'      => (float) $r->min_qty,
                    'max_qty'      => (float) $r->max_qty,
                    'shortage'     => (float) $r->shortage,
                    'sell_speed'   => $sell_speed,
                    'suggest_buy'  => max(0, (float) $r->max_qty - (float) $r->closing_qty),
                ];
                $result['below_min'][] = $item;

                $ensure_shop($r->blog_id);
                $result['by_shop'][$r->blog_id]['below_min'][] = $item;
            }
            $result['summary']['total_below_min'] = count($below);
        }

        // ── 2) Vượt MAX ──
        if ($has_config && $has_inv) {
            $blog_sql_above = self::blog_filter_sql('inv');
            $above = $wpdb->get_results(
                "SELECT
                    inv.blog_id, inv.sku,
                    COALESCE(p.product_name, inv.sku) as product_name,
                    SUM(inv.closing_qty) as closing_qty,
                    cfg.max_qty,
                    (SUM(inv.closing_qty) - cfg.max_qty) as surplus
                 FROM {$inv_table} inv
                 {$latest_join}
                 INNER JOIN {$config_table} cfg
                    ON inv.blog_id = cfg.blog_id AND inv.sku = cfg.product_sku
                 {$prod_join}
                 WHERE cfg.is_active = 1
                   AND cfg.max_qty > 0
                   {$blog_sql_above}
                 GROUP BY inv.blog_id, inv.sku, p.product_name, cfg.max_qty
                 HAVING closing_qty > cfg.max_qty
                 ORDER BY surplus DESC
                 LIMIT 200"
            );

            foreach ($above as $r) {
                $info = $shop_names[$r->blog_id] ?? ['code' => '', 'name' => 'Shop #' . $r->blog_id];
                $item = [
                    'blog_id'      => $r->blog_id,
                    'shop_name'    => $info['name'],
                    'sku'          => $r->sku,
                    'product_name' => $r->product_name,
                    'closing_qty'  => (float) $r->closing_qty,
                    'max_qty'      => (float) $r->max_qty,
                    'surplus'      => (float) $r->surplus,
                ];
                $result['above_max'][] = $item;

                $ensure_shop($r->blog_id);
                $result['by_shop'][$r->blog_id]['above_max'][] = $item;
            }
            $result['summary']['total_above_max'] = count($above);
        }

        // ── 3) Hết hàng (stockout_flag = 1 — không phụ thuộc config) ──
        if ($has_inv) {
            $blog_sql_oos = self::blog_filter_sql('inv');
            $oos = $wpdb->get_results(
                "SELECT
                    inv.blog_id, inv.sku,
                    COALESCE(p.product_name, inv.sku) as product_name,
                    SUM(inv.closing_qty) as closing_qty,
                    COALESCE(cfg.max_qty, 0) as max_qty
                 FROM {$inv_table} inv
                 {$latest_join}
                 LEFT JOIN {$config_table} cfg
                    ON inv.blog_id = cfg.blog_id AND inv.sku = cfg.product_sku AND cfg.is_active = 1 AND cfg.site_type = 1
                 {$prod_join}
                 WHERE inv.stockout_flag = 1
                   {$blog_sql_oos}
                 GROUP BY inv.blog_id, inv.sku, p.product_name, cfg.max_qty
                 ORDER BY inv.blog_id, inv.sku
                 LIMIT 500"
            );

            foreach ($oos as $r) {
                $info = $shop_names[$r->blog_id] ?? ['code' => '', 'name' => 'Shop #' . $r->blog_id];
                $item = [
                    'blog_id'      => $r->blog_id,
                    'shop_name'    => $info['name'],
                    'sku'          => $r->sku,
                    'product_name' => $r->product_name,
                    'closing_qty'  => (float) $r->closing_qty,
                    'max_qty'      => (float) $r->max_qty,
                ];
                $result['stockout'][] = $item;

                $ensure_shop($r->blog_id);
                $result['by_shop'][$r->blog_id]['stockout'][] = $item;
            }
            $result['summary']['total_stockout'] = count($oos);
        }

        // Cập nhật count per shop
        foreach ($result['by_shop'] as $bid => &$shop_data) {
            $shop_data['total_below_min'] = count($shop_data['below_min']);
            $shop_data['total_above_max'] = count($shop_data['above_max']);
            $shop_data['total_stockout']  = count($shop_data['stockout']);
        }
        unset($shop_data);

        // Sắp xếp shop: shop có nhiều vấn đề nhất lên trước
        uasort($result['by_shop'], function ($a, $b) {
            $total_a = $a['total_stockout'] + $a['total_below_min'] + $a['total_above_max'];
            $total_b = $b['total_stockout'] + $b['total_below_min'] + $b['total_above_max'];
            return $total_b <=> $total_a;
        });

        // ── 4) Sắp hết hạn (30 ngày) ──
        if ($has_inv) {
            $near = $wpdb->get_results(
                "SELECT inv.blog_id, inv.sku,
                        COALESCE(p.product_name, inv.sku) as product_name,
                        inv.exp_date,
                        inv.near_expiry_qty, inv.closing_qty
                 FROM {$inv_table} inv
                 {$latest_join}
                 {$prod_join}
                 WHERE inv.near_expiry_qty > 0
                 ORDER BY inv.near_expiry_qty DESC
                 LIMIT 200"
            );

            foreach ($near as $r) {
                $info = $shop_names[$r->blog_id] ?? ['code' => '', 'name' => 'Shop #' . $r->blog_id];
                $result['near_expiry'][] = [
                    'blog_id'         => $r->blog_id,
                    'shop_name'       => $info['name'],
                    'sku'             => $r->sku,
                    'product_name'    => $r->product_name,
                    'exp_date'        => $r->exp_date,
                    'near_expiry_qty' => (float) $r->near_expiry_qty,
                    'closing_qty'     => (float) $r->closing_qty,
                ];
            }
            $result['summary']['total_near_expiry'] = count($near);
        }

        // ── 5) Đề xuất mua ──
        if ($has_reorder) {
            $reorders = $wpdb->get_results(
                "SELECT * FROM {$reorder_tbl}
                 WHERE status IN (0, 1)
                   AND is_deleted = 0
                 ORDER BY created_at DESC
                 LIMIT 100"
            );
            $result['reorder_suggestions'] = $reorders;
            $result['summary']['total_reorder'] = count($reorders);
        }

        return $result;
    }
}
