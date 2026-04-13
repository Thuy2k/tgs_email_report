<?php
/**
 * Collector: Warehouse Stock — Tồn kho tổng hợp, nhập/xuất/hỏng
 *
 * Data source:
 *   - tgs_fact_inventory_daily (snapshot mới nhất as-of date_to)
 *
 * Logic: Dùng inventory_latest_join() lấy bản ghi mới nhất cho mỗi
 *        (blog_id, sku, exp_date) tính đến ngày date_to.
 *        Đây là snapshot tồn kho — KHÔNG cộng dồn nhiều ngày.
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
                'opening_qty'      => 0,
                'opening_value'    => 0,
                'in_qty'           => 0,
                'in_value'         => 0,
                'out_qty'          => 0,
                'out_value'        => 0,
                'damage_qty'       => 0,
                'damage_value'     => 0,
                'transfer_out_qty' => 0,
                'transfer_out_value' => 0,
                'closing_qty'      => 0,
                'closing_value'    => 0,
                'cogs_value'       => 0,
                'stockout_count'   => 0,
                'slow_moving_count' => 0,
                'total_skus'       => 0,
            ],
        ];

        $shop_names = self::get_shop_names();
        $inv_table  = $wpdb->base_prefix . 'tgs_fact_inventory_daily';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$inv_table}'") !== $inv_table) {
            return $result;
        }

        $latest_join = self::inventory_latest_join($inv_table, $date_to);

        // Snapshot tồn kho mới nhất, GROUP BY blog_id
        $rows = $wpdb->get_results(
            "SELECT
                inv.blog_id,
                SUM(inv.opening_qty) as opening_qty,
                SUM(inv.opening_value) as opening_value,
                SUM(inv.in_qty) as in_qty,
                SUM(inv.in_value) as in_value,
                SUM(inv.out_qty) as out_qty,
                SUM(inv.out_value) as out_value,
                SUM(inv.damage_qty) as damage_qty,
                SUM(inv.damage_value) as damage_value,
                SUM(inv.transfer_out_qty) as transfer_out_qty,
                SUM(inv.transfer_out_value) as transfer_out_value,
                SUM(inv.closing_qty) as closing_qty,
                SUM(inv.closing_value) as closing_value,
                SUM(inv.cogs_value) as cogs_value,
                SUM(CASE WHEN inv.stockout_flag = 1 THEN 1 ELSE 0 END) as stockout_count,
                SUM(CASE WHEN inv.slow_moving_qty > 0 THEN 1 ELSE 0 END) as slow_moving_count,
                COUNT(DISTINCT inv.sku) as total_skus
             FROM {$inv_table} inv
             {$latest_join}
             GROUP BY inv.blog_id
             ORDER BY closing_value DESC"
        );

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
            $result['totals']['opening_qty']      += (float) $r->opening_qty;
            $result['totals']['opening_value']     += (float) $r->opening_value;
            $result['totals']['in_qty']            += (float) $r->in_qty;
            $result['totals']['in_value']           += (float) $r->in_value;
            $result['totals']['out_qty']            += (float) $r->out_qty;
            $result['totals']['out_value']          += (float) $r->out_value;
            $result['totals']['damage_qty']         += (float) $r->damage_qty;
            $result['totals']['damage_value']       += (float) $r->damage_value;
            $result['totals']['transfer_out_qty']   += (float) $r->transfer_out_qty;
            $result['totals']['transfer_out_value'] += (float) $r->transfer_out_value;
            $result['totals']['closing_qty']        += (float) $r->closing_qty;
            $result['totals']['closing_value']      += (float) $r->closing_value;
            $result['totals']['cogs_value']         += (float) $r->cogs_value;
            $result['totals']['stockout_count']     += (int) $r->stockout_count;
            $result['totals']['slow_moving_count']  += (int) $r->slow_moving_count;
            $result['totals']['total_skus']         += (int) $r->total_skus;
        }

        return $result;
    }
}
