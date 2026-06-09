<?php
/**
 * Collector: Shop Gifts — Thống kê tặng kèm theo shop
 *
 * Đọc trực tiếp từ local_ledger_item (gift_type = 1) per blog.
 *
 * Trả về mảng:
 *   [blog_id => [
 *       'shop_name', 'shop_code',
 *       'order_count'   — số đơn có ít nhất 1 sản phẩm tặng kèm
 *       'gift_qty'      — tổng số lượng sản phẩm tặng kèm
 *       'gift_value'    — tổng giá trị (giá niêm yết × số lượng)
 *       'items'         — top 5 sản phẩm tặng kèm nhiều nhất
 *   ]]
 *
 *   Kèm key 'summary' => ['total_orders', 'total_qty', 'total_value', 'shop_count']
 */

if (!defined('ABSPATH')) exit;

class TGS_Collector_Shop_Gifts extends TGS_Collector_Base
{
    public static function collect($date_from, $date_to)
    {
        global $wpdb;

        $result  = [];
        $summary = ['total_orders' => 0, 'total_qty' => 0, 'total_value' => 0.0, 'shop_count' => 0];

        $shop_names = self::get_shop_names();
        $blogs      = self::get_active_blog_ids();

        foreach ($blogs as $blog) {
            $bid    = (int) $blog->blog_id;
            $prefix = self::get_blog_prefix($bid);
            $ledger = $prefix . 'local_ledger';
            $items  = $prefix . 'local_ledger_item';

            if ($wpdb->get_var("SHOW TABLES LIKE '{$items}'") !== $items) {
                continue;
            }

            // Tổng hợp theo ledger item; catalog/giá lấy từ global product.
            $agg = $wpdb->get_row($wpdb->prepare(
                "SELECT
                    COUNT(DISTINCT li.local_ledger_id)                            AS order_count,
                    COALESCE(SUM(li.quantity), 0)                                 AS gift_qty
                 FROM {$items} li
                 WHERE li.local_ledger_item_gift_type = 1
                   AND li.is_deleted = 0
                   AND DATE(li.created_at) BETWEEN %s AND %s",
                $date_from, $date_to
            ));

            if (!$agg || (int) $agg->order_count === 0) {
                continue;
            }

            $gift_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT
                    TRIM(li.local_product_sku) AS sku,
                    COUNT(DISTINCT li.local_ledger_id)                            AS order_count,
                    SUM(li.quantity)                                               AS qty
                 FROM {$items} li
                 WHERE li.local_ledger_item_gift_type = 1
                   AND li.is_deleted = 0
                   AND DATE(li.created_at) BETWEEN %s AND %s
                   AND li.local_product_sku IS NOT NULL
                   AND TRIM(li.local_product_sku) <> ''
                 GROUP BY TRIM(li.local_product_sku)
                 ORDER BY qty DESC",
                $date_from, $date_to
            ));

            $skus = [];
            foreach ($gift_rows as $r) {
                $sku = trim((string) ($r->sku ?? ''));
                if ($sku !== '') {
                    $skus[] = $sku;
                }
            }

            $products_by_sku = TGS_Email_Global_Products::products_by_skus($skus);

            $top_items = [];
            $gift_value_total = 0.0;
            foreach ($gift_rows as $index => $r) {
                $sku = trim((string) ($r->sku ?? ''));
                $product = $sku !== '' ? ($products_by_sku[$sku] ?? ($products_by_sku[strtoupper($sku)] ?? null)) : null;
                $product = $product ? (array) $product : [];
                $qty = (float) $r->qty;
                $value = TGS_Email_Global_Products::price_after_tax($product) * $qty;
                $gift_value_total += $value;

                if ($index >= 5) {
                    continue;
                }

                $top_items[] = [
                    'name'        => TGS_Email_Global_Products::name($product, $sku),
                    'sku'         => $sku,
                    'order_count' => (int) $r->order_count,
                    'qty'         => $qty,
                    'value'       => $value,
                ];
            }

            $info = $shop_names[$bid] ?? ['code' => 'SHOP-' . $bid, 'name' => 'Shop #' . $bid];

            $result[$bid] = [
                'shop_name'   => $info['name'],
                'shop_code'   => $info['code'],
                'order_count' => (int) $agg->order_count,
                'gift_qty'    => (float) $agg->gift_qty,
                'gift_value'  => $gift_value_total,
                'items'       => $top_items,
            ];

            $summary['total_orders'] += (int) $agg->order_count;
            $summary['total_qty']    += (float) $agg->gift_qty;
            $summary['total_value']  += $gift_value_total;
            $summary['shop_count']++;
        }

        // Sắp xếp shop theo giá trị tặng kèm giảm dần
        uasort($result, fn($a, $b) => $b['gift_value'] <=> $a['gift_value']);

        return ['by_shop' => $result, 'summary' => $summary];
    }
}
