<?php
/**
 * Collector: Warehouse Stock — Tồn kho tổng hợp, nhập/xuất/hỏng
 *
 * Data source:
 *   - tgs_fact_inventory_daily (daily rollup)
 *
 * Logic:
 *   - Tồn cuối / stockout / slow moving: lấy snapshot mới nhất as-of date_to.
 *   - Nhập / bán / chuyển kho / hư hỏng: cộng dồn theo khoảng date_from → date_to.
 *   - Tồn đầu kỳ: lấy tồn đầu ngày date_from; nếu chưa có row đúng ngày thì fallback
 *     sang closing của bản ghi gần nhất trước đó.
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

        $latest_join  = self::inventory_latest_join($inv_table, $date_to);
        $opening_join = self::inventory_latest_join($inv_table, $date_from);
        $blog_sql     = self::blog_filter_sql();

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT
                ids.blog_id,
                COALESCE(op.opening_qty, 0) as opening_qty,
                COALESCE(op.opening_value, 0) as opening_value,
                COALESCE(mv.in_qty, 0) as in_qty,
                COALESCE(mv.in_value, 0) as in_value,
                COALESCE(mv.out_qty, 0) as out_qty,
                COALESCE(mv.out_value, 0) as out_value,
                COALESCE(mv.damage_qty, 0) as damage_qty,
                COALESCE(mv.damage_value, 0) as damage_value,
                COALESCE(mv.transfer_out_qty, 0) as transfer_out_qty,
                COALESCE(mv.transfer_out_value, 0) as transfer_out_value,
                COALESCE(snap.closing_qty, 0) as closing_qty,
                COALESCE(snap.closing_value, 0) as closing_value,
                COALESCE(mv.cogs_value, 0) as cogs_value,
                COALESCE(snap.stockout_count, 0) as stockout_count,
                COALESCE(snap.slow_moving_count, 0) as slow_moving_count,
                COALESCE(snap.total_skus, 0) as total_skus
             FROM (
                SELECT DISTINCT blog_id
                FROM {$inv_table}
                WHERE 1=1 {$blog_sql}
             ) ids
             LEFT JOIN (
                SELECT
                    inv.blog_id,
                    SUM(CASE WHEN inv.rollup_date = %s THEN inv.opening_qty ELSE inv.closing_qty END) as opening_qty,
                    SUM(CASE WHEN inv.rollup_date = %s THEN inv.opening_value ELSE inv.closing_value END) as opening_value
                FROM {$inv_table} inv
                {$opening_join}
                GROUP BY inv.blog_id
             ) op ON op.blog_id = ids.blog_id
             LEFT JOIN (
                SELECT
                    blog_id,
                    SUM(in_qty) as in_qty,
                    SUM(in_value) as in_value,
                    SUM(out_qty) as out_qty,
                    SUM(out_value) as out_value,
                    SUM(damage_qty) as damage_qty,
                    SUM(damage_value) as damage_value,
                    SUM(transfer_out_qty) as transfer_out_qty,
                    SUM(transfer_out_value) as transfer_out_value,
                    SUM(cogs_value) as cogs_value
                FROM {$inv_table}
                WHERE rollup_date BETWEEN %s AND %s
                GROUP BY blog_id
             ) mv ON mv.blog_id = ids.blog_id
             LEFT JOIN (
                SELECT
                    inv.blog_id,
                    SUM(inv.closing_qty) as closing_qty,
                    SUM(inv.closing_value) as closing_value,
                    SUM(CASE WHEN inv.stockout_flag = 1 THEN 1 ELSE 0 END) as stockout_count,
                    SUM(CASE WHEN inv.slow_moving_qty > 0 THEN 1 ELSE 0 END) as slow_moving_count,
                    COUNT(DISTINCT inv.sku) as total_skus
                FROM {$inv_table} inv
                {$latest_join}
                GROUP BY inv.blog_id
             ) snap ON snap.blog_id = ids.blog_id
             ORDER BY COALESCE(snap.closing_value, 0) DESC",
            $date_from,
            $date_from,
            $date_from,
            $date_to
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
