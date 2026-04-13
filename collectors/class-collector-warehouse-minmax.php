<?php
/**
 * Collector: Warehouse MIN/MAX — Cảnh báo tồn kho dưới MIN hoặc vượt MAX
 *
 * Data source:
 *   - tgs_fact_inventory_daily (closing_qty — snapshot mới nhất as-of date_to)
 *   - global_lot_item_shop_config (min_qty, max_qty)
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
            'near_expiry'   => [], // SP sắp hết hạn (30 ngày)
            'reorder_suggestions' => [], // Đề xuất mua
            'summary'       => [
                'total_below_min' => 0,
                'total_above_max' => 0,
                'total_near_expiry' => 0,
                'total_reorder' => 0,
            ],
        ];

        $shop_names   = self::get_shop_names();
        $config_table = self::config_table();
        $inv_table    = $wpdb->base_prefix . 'tgs_fact_inventory_daily';
        $reorder_tbl  = self::reorder_table();

        $has_config  = ($wpdb->get_var("SHOW TABLES LIKE '{$config_table}'") === $config_table);
        $has_inv     = ($wpdb->get_var("SHOW TABLES LIKE '{$inv_table}'") === $inv_table);
        $has_reorder = ($wpdb->get_var("SHOW TABLES LIKE '{$reorder_tbl}'") === $reorder_tbl);

        $latest_join = $has_inv ? self::inventory_latest_join($inv_table, $date_to) : '';

        // ── 1) Dưới MIN ──
        if ($has_config && $has_inv) {
            $below = $wpdb->get_results(
                "SELECT
                    inv.blog_id, inv.sku,
                    SUM(inv.closing_qty) as closing_qty,
                    cfg.min_qty, cfg.max_qty,
                    (cfg.min_qty - SUM(inv.closing_qty)) as shortage
                 FROM {$inv_table} inv
                 {$latest_join}
                 INNER JOIN {$config_table} cfg
                    ON inv.blog_id = cfg.blog_id AND inv.sku = cfg.product_sku
                 WHERE cfg.is_active = 1
                   AND cfg.min_qty > 0
                 GROUP BY inv.blog_id, inv.sku, cfg.min_qty, cfg.max_qty
                 HAVING closing_qty < cfg.min_qty
                 ORDER BY shortage DESC
                 LIMIT 300"
            );

            foreach ($below as $r) {
                $info = $shop_names[$r->blog_id] ?? ['code' => '', 'name' => 'Shop #' . $r->blog_id];
                $result['below_min'][] = [
                    'blog_id'     => $r->blog_id,
                    'shop_name'   => $info['name'],
                    'sku'         => $r->sku,
                    'closing_qty' => (float) $r->closing_qty,
                    'min_qty'     => (float) $r->min_qty,
                    'shortage'    => (float) $r->shortage,
                ];
            }
            $result['summary']['total_below_min'] = count($below);
        }

        // ── 2) Vượt MAX ──
        if ($has_config && $has_inv) {
            $above = $wpdb->get_results(
                "SELECT
                    inv.blog_id, inv.sku,
                    SUM(inv.closing_qty) as closing_qty,
                    cfg.max_qty,
                    (SUM(inv.closing_qty) - cfg.max_qty) as surplus
                 FROM {$inv_table} inv
                 {$latest_join}
                 INNER JOIN {$config_table} cfg
                    ON inv.blog_id = cfg.blog_id AND inv.sku = cfg.product_sku
                 WHERE cfg.is_active = 1
                   AND cfg.max_qty > 0
                 GROUP BY inv.blog_id, inv.sku, cfg.max_qty
                 HAVING closing_qty > cfg.max_qty
                 ORDER BY surplus DESC
                 LIMIT 200"
            );

            foreach ($above as $r) {
                $info = $shop_names[$r->blog_id] ?? ['code' => '', 'name' => 'Shop #' . $r->blog_id];
                $result['above_max'][] = [
                    'blog_id'     => $r->blog_id,
                    'shop_name'   => $info['name'],
                    'sku'         => $r->sku,
                    'closing_qty' => (float) $r->closing_qty,
                    'max_qty'     => (float) $r->max_qty,
                    'surplus'     => (float) $r->surplus,
                ];
            }
            $result['summary']['total_above_max'] = count($above);
        }

        // ── 3) Sắp hết hạn (30 ngày) ──
        if ($has_inv) {
            $near = $wpdb->get_results(
                "SELECT inv.blog_id, inv.sku, inv.exp_date,
                        inv.near_expiry_qty, inv.closing_qty
                 FROM {$inv_table} inv
                 {$latest_join}
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
                    'exp_date'        => $r->exp_date,
                    'near_expiry_qty' => (float) $r->near_expiry_qty,
                    'closing_qty'     => (float) $r->closing_qty,
                ];
            }
            $result['summary']['total_near_expiry'] = count($near);
        }

        // ── 4) Đề xuất mua ──
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
