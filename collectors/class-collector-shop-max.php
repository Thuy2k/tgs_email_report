<?php
/**
 * Collector: Shop MAX — Tồn kho MAX tại từng shop
 *
 * Lấy sản phẩm đang vượt MAX config tại shop.
 *
 * Data source:
 *   - tgs_fact_inventory_daily (closing_qty)
 *   - wp_global_lot_item_shop_config (max_qty)
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
        $config_table = 'wp_global_lot_item_shop_config';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$config_table}'") !== $config_table) {
            return $result;
        }

        // Bảng inventory rollup
        $inv_table = $wpdb->base_prefix . 'tgs_fact_inventory_daily';
        $has_inventory = ($wpdb->get_var("SHOW TABLES LIKE '{$inv_table}'") === $inv_table);

        if ($has_inventory) {
            // Join inventory closing qty với config max_qty
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT
                    inv.blog_id,
                    inv.sku,
                    inv.closing_qty,
                    cfg.max_qty,
                    cfg.min_qty,
                    (inv.closing_qty - cfg.max_qty) as over_max
                 FROM {$inv_table} inv
                 INNER JOIN {$config_table} cfg
                    ON inv.blog_id = cfg.blog_id AND inv.sku = cfg.product_sku
                 WHERE inv.rollup_date = %s
                   AND cfg.is_active = 1
                   AND cfg.max_qty > 0
                   AND inv.closing_qty > cfg.max_qty
                 ORDER BY (inv.closing_qty - cfg.max_qty) DESC
                 LIMIT 200",
                $date_to
            ));

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
            $configs = $wpdb->get_results(
                "SELECT blog_id, product_sku, max_qty, min_qty
                 FROM {$config_table}
                 WHERE is_active = 1 AND max_qty > 0
                 ORDER BY blog_id, product_sku"
            );

            foreach ($configs as $c) {
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
