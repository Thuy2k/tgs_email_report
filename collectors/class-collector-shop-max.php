<?php
/**
 * Collector: Shop MAX — Tồn kho MAX tại từng shop
 *
 * Lấy sản phẩm đang vượt MAX config tại shop.
 *
 * Data source:
 *   - tgs_fact_inventory_daily (closing_qty — snapshot mới nhất as-of date_to)
 *   - global_sku_stock_config (max_qty)
 *
 * Dùng inventory_latest_join() để lấy snapshot mới nhất cho mỗi
 * (blog_id, sku, exp_date) — không bỏ sót SKU không có movement ngày cuối.
 */

if (!defined('ABSPATH')) exit;

class TGS_Collector_Shop_Max extends TGS_Collector_Base
{
    public static function collect($date_from, $date_to)
    {
        global $wpdb;

        $result = [];
        $shop_names = self::get_shop_names();

        // Bảng config MIN/MAX
        $config_table = self::config_table();
        if ($wpdb->get_var("SHOW TABLES LIKE '{$config_table}'") !== $config_table) {
            return $result;
        }

        // Bảng inventory rollup
        $inv_table = $wpdb->base_prefix . 'tgs_fact_inventory_daily';
        $has_inventory = ($wpdb->get_var("SHOW TABLES LIKE '{$inv_table}'") === $inv_table);

        if ($has_inventory) {
            $latest_join = self::inventory_latest_join($inv_table, $date_to);

            // Join inventory closing qty (latest snapshot) với config max_qty
            $rows = $wpdb->get_results(
                "SELECT
                    inv.blog_id,
                    inv.sku,
                    SUM(inv.closing_qty) as closing_qty,
                    cfg.max_qty,
                    cfg.min_qty,
                    (SUM(inv.closing_qty) - cfg.max_qty) as over_max
                 FROM {$inv_table} inv
                 {$latest_join}
                 INNER JOIN {$config_table} cfg
                    ON inv.blog_id = cfg.blog_id AND inv.sku = cfg.product_sku
                 WHERE cfg.is_active = 1
                   AND cfg.max_qty > 0
                 GROUP BY inv.blog_id, inv.sku, cfg.max_qty, cfg.min_qty
                 HAVING closing_qty > cfg.max_qty
                 ORDER BY over_max DESC
                 LIMIT 200"
            );

            foreach ($rows as $r) {
                $bid = $r->blog_id;
                if (!isset($result[$bid])) {
                    $info = $shop_names[$bid] ?? ['code' => 'SHOP-' . $bid, 'name' => 'Shop #' . $bid];
                    $result[$bid] = [
                        'shop_name' => $info['name'],
                        'shop_code' => $info['code'],
                        'items'     => [],
                        'total_over_max_items' => 0,
                    ];
                }
                $result[$bid]['items'][] = [
                    'sku'         => $r->sku,
                    'closing_qty' => (float) $r->closing_qty,
                    'max_qty'     => (float) $r->max_qty,
                    'over_max'    => (float) $r->over_max,
                ];
                $result[$bid]['total_over_max_items']++;
            }
        } else {
            // Fallback: chỉ lấy config có max_qty, không đối chiếu tồn
            $cfgs = $wpdb->get_results(
                "SELECT blog_id, product_sku, max_qty, min_qty
                 FROM {$config_table}
                 WHERE is_active = 1 AND max_qty > 0
                 ORDER BY blog_id, product_sku"
            );

            foreach ($cfgs as $c) {
                $bid = $c->blog_id;
                if (!isset($result[$bid])) {
                    $info = $shop_names[$bid] ?? ['code' => 'SHOP-' . $bid, 'name' => 'Shop #' . $bid];
                    $result[$bid] = [
                        'shop_name' => $info['name'],
                        'shop_code' => $info['code'],
                        'items'     => [],
                        'total_over_max_items' => 0,
                        'note'      => 'Chưa có data tồn kho rollup — chỉ hiện cấu hình MAX',
                    ];
                }
                $result[$bid]['items'][] = [
                    'sku'         => $c->product_sku,
                    'closing_qty' => null,
                    'max_qty'     => (float) $c->max_qty,
                    'over_max'    => null,
                ];
            }
        }

        return $result;
    }
}
