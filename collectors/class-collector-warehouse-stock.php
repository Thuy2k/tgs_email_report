<?php
/**
 * Collector: Warehouse Stock — Tồn kho tổng hợp, nhập/xuất/hỏng
 *
 * Data source:
 *   - tgs_fact_inventory_daily
 */

if (!defined('ABSPATH')) exit;

class TGS_Collector_Warehouse_Stock extends TGS_Collector_Base
{
    public static function collect($date_from, $date_to)
    {
        global $wpdb;

        $result = [
            'by_shop' => [],
            'totals'  => [
                'opening_qty'     => 0,
                'in_qty'          => 0,
                'out_qty'         => 0,
                'damage_qty'      => 0,
                'transfer_out_qty' => 0,
                'closing_qty'     => 0,
                'stockout_count'  => 0,
                'slow_moving_count' => 0,
            ],
        ];

        $shop_names = self::get_shop_names();
        $inv_table = $wpdb->base_prefix . 'tgs_fact_inventory_daily';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$inv_table}'") !== $inv_table) {
            return $result;
        }

        // Tổng hợp theo shop
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT
                blog_id,
                SUM(opening_qty) as opening_qty,
                SUM(opening_value) as opening_value,
                SUM(in_qty) as in_qty,
                SUM(in_value) as in_value,
                SUM(out_qty) as out_qty,
                SUM(out_value) as out_value,
                SUM(damage_qty) as damage_qty,
                SUM(damage_value) as damage_value,
                SUM(transfer_out_qty) as transfer_out_qty,
                SUM(transfer_out_value) as transfer_out_value,
                SUM(closing_qty) as closing_qty,
                SUM(closing_value) as closing_value,
                SUM(cogs_value) as cogs_value,
                SUM(CASE WHEN stockout_flag = 1 THEN 1 ELSE 0 END) as stockout_count,
                SUM(CASE WHEN slow_moving_qty > 0 THEN 1 ELSE 0 END) as slow_moving_count,
                COUNT(DISTINCT sku) as total_skus
             FROM {$inv_table}
             WHERE rollup_date BETWEEN %s AND %s
             GROUP BY blog_id
             ORDER BY closing_value DESC",
            $date_from, $date_to
        ));

        foreach ($rows as $r) {
            $info = $shop_names[$r->blog_id] ?? ['code' => 'SHOP-' . $r->blog_id, 'name' => 'Shop #' . $r->blog_id];

            $result['by_shop'][$r->blog_id] = [
                'shop_name'       => $info['name'],
                'shop_code'       => $info['code'],
                'opening_qty'     => (float) $r->opening_qty,
                'opening_value'   => (float) $r->opening_value,
                'in_qty'          => (float) $r->in_qty,
                'in_value'        => (float) $r->in_value,
                'out_qty'         => (float) $r->out_qty,
                'out_value'       => (float) $r->out_value,
                'damage_qty'      => (float) $r->damage_qty,
                'damage_value'    => (float) $r->damage_value,
                'transfer_out_qty'  => (float) $r->transfer_out_qty,
                'transfer_out_value' => (float) $r->transfer_out_value,
                'closing_qty'     => (float) $r->closing_qty,
                'closing_value'   => (float) $r->closing_value,
                'cogs_value'      => (float) $r->cogs_value,
                'stockout_count'  => (int) $r->stockout_count,
                'slow_moving_count' => (int) $r->slow_moving_count,
                'total_skus'      => (int) $r->total_skus,
            ];

            // Cộng dồn totals
            $result['totals']['opening_qty']     += (float) $r->opening_qty;
            $result['totals']['in_qty']          += (float) $r->in_qty;
            $result['totals']['out_qty']         += (float) $r->out_qty;
            $result['totals']['damage_qty']      += (float) $r->damage_qty;
            $result['totals']['transfer_out_qty'] += (float) $r->transfer_out_qty;
            $result['totals']['closing_qty']     += (float) $r->closing_qty;
            $result['totals']['stockout_count']  += (int) $r->stockout_count;
            $result['totals']['slow_moving_count'] += (int) $r->slow_moving_count;
        }

        return $result;
    }
}
