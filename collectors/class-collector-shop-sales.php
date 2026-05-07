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
        $rollup_blog_ids = [];
        $blogs = self::get_active_blog_ids();

        // Ưu tiên đọc từ bảng rollup (nhanh hơn nhiều)
        $fact_table = $wpdb->base_prefix . 'tgs_fact_sales_daily';
        $has_rollup = ($wpdb->get_var("SHOW TABLES LIKE '{$fact_table}'") === $fact_table);

        if ($has_rollup) {
            $blog_sql = self::blog_filter_sql();
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
                   {$blog_sql}
                 GROUP BY blog_id
                 ORDER BY gross_revenue DESC",
                $date_from, $date_to
            ));

            foreach ($rows as $r) {
                $info = $shop_names[$r->blog_id] ?? ['code' => 'SHOP-' . $r->blog_id, 'name' => 'Shop #' . $r->blog_id];
                $sales_after_discount = (float) $r->gross_revenue;
                $discount_value = (float) $r->discount_value;
                $return_value = (float) $r->return_value;
                $net_revenue = (float) ($r->net_revenue ?? 0);

                if ($sales_after_discount > 0 && $net_revenue <= 0) {
                    $net_revenue = $sales_after_discount - $return_value;
                }

                // Hiển thị tiền hàng trước chiết khấu để công thức cột rõ ràng:
                // Tiền hàng trước CK - Chiết khấu - Trả hàng = Thực thu.
                $gross_revenue = $net_revenue + $discount_value + $return_value;
                if ($gross_revenue <= 0 && $sales_after_discount > 0) {
                    $gross_revenue = $sales_after_discount + $discount_value;
                }

                $result[$r->blog_id] = [
                    'shop_name'          => $info['name'],
                    'shop_code'          => $info['code'],
                    'order_count'        => (int) $r->order_count,
                    'gross_revenue'      => $gross_revenue,
                    'net_revenue'        => $net_revenue,
                    'discount_value'     => $discount_value,
                    'gift_value'         => (float) $r->gift_value,
                    'return_value'       => $return_value,
                    'cogs_value'         => (float) $r->cogs_value,
                    'gross_profit'       => (float) $r->gross_profit,
                    'gross_margin_pct'   => round((float) $r->gross_margin_pct, 2),
                    'customer_count'     => (int) $r->customer_count,
                    'new_customer_count' => (int) $r->new_customer_count,
                    'avg_order_value'    => (int) $r->order_count > 0 ? round($net_revenue / (int) $r->order_count) : 0,
                    'avg_items_per_order' => round((float) $r->avg_items_per_order, 1),
                    'hcl_breakdown'      => self::build_hcl_breakdown($r->blog_id, $date_from, $date_to, $gross_revenue),
                ];
                $rollup_blog_ids[$r->blog_id] = true;
            }
        }

        // ── Fallback / bù dữ liệu: query trực tiếp local_ledger cho shop chưa có rollup ──
        foreach ($blogs as $blog) {
            if (isset($rollup_blog_ids[$blog->blog_id])) {
                continue;
            }

            $prefix = self::get_blog_prefix($blog->blog_id);
            $ledger = $prefix . 'local_ledger';

            // Kiểm tra bảng tồn tại
            if ($wpdb->get_var("SHOW TABLES LIKE '{$ledger}'") !== $ledger) {
                continue;
            }

            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT
                    COUNT(DISTINCT CASE WHEN local_ledger_type = 10 THEN local_ledger_id END) as order_count,
                    COALESCE(SUM(CASE WHEN local_ledger_type = 10 THEN local_ledger_total_amount ELSE 0 END), 0) as sales_after_discount,
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
            $sales_after_discount = (float) $row->sales_after_discount;
            $discount_value = (float) $row->discount_value;
            $return_value = (float) $row->return_value;
            $gross_revenue = $sales_after_discount + $discount_value;
            $net = $sales_after_discount - $return_value;

            $result[$blog->blog_id] = [
                'shop_name'          => $info['name'],
                'shop_code'          => $info['code'],
                'order_count'        => (int) $row->order_count,
                'gross_revenue'      => $gross_revenue,
                'net_revenue'        => $net,
                'discount_value'     => $discount_value,
                'gift_value'         => 0,
                'return_value'       => $return_value,
                'cogs_value'         => 0,
                'gross_profit'       => $net,
                'gross_margin_pct'   => 0,
                'customer_count'     => (int) $row->customer_count,
                'new_customer_count' => 0,
                'avg_order_value'    => $row->order_count > 0 ? round($net / $row->order_count) : 0,
                'avg_items_per_order' => 0,
                'hcl_breakdown'      => self::build_hcl_breakdown($blog->blog_id, $date_from, $date_to, $gross_revenue),
            ];
        }

        // Sắp xếp theo doanh thu giảm dần
        uasort($result, function ($a, $b) {
            return $b['gross_revenue'] <=> $a['gross_revenue'];
        });

        return $result;
    }

    private static function build_hcl_breakdown($blog_id, $date_from, $date_to, $shop_sales_basis)
    {
        global $wpdb;

        $bid = (int) $blog_id;
        if ($bid <= 0) {
            return ['tree_rows' => [], 'other_revenue' => 0.0, 'strategic_total' => 0.0];
        }

        $inventory_table = $wpdb->base_prefix . 'tgs_fact_inventory_daily';
        $dim_product_table = $wpdb->base_prefix . 'tgs_dim_product';
        $sci_table = $wpdb->base_prefix . 'global_sci';

        if (
            $wpdb->get_var("SHOW TABLES LIKE '{$inventory_table}'") !== $inventory_table ||
            $wpdb->get_var("SHOW TABLES LIKE '{$dim_product_table}'") !== $dim_product_table ||
            $wpdb->get_var("SHOW TABLES LIKE '{$sci_table}'") !== $sci_table
        ) {
            return self::build_hcl_breakdown_from_ledger($bid, $date_from, $date_to, $shop_sales_basis);
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT
                COALESCE(p.global_sci_id, 0) AS global_sci_id,
                COALESCE(s.sci_code, '')     AS sci_code,
                COALESCE(s.name, '')         AS sci_name,
                COALESCE(s.path, '')         AS sci_path,
                COALESCE(SUM(i.sale_value), 0) AS revenue
             FROM {$inventory_table} i
             LEFT JOIN {$dim_product_table} p
                ON p.sku = i.sku
             LEFT JOIN {$sci_table} s
                ON s.id = p.global_sci_id
             WHERE i.blog_id = %d
               AND i.rollup_date BETWEEN %s AND %s
               AND COALESCE(i.sale_value, 0) > 0
             GROUP BY COALESCE(p.global_sci_id, 0), COALESCE(s.sci_code, ''), COALESCE(s.name, ''), COALESCE(s.path, '')
             ORDER BY sci_path ASC, revenue DESC",
            $bid,
            $date_from,
            $date_to
        )) ?: [];

        $direct_groups = [];
        $strategic_total = 0.0;
        $other_revenue = 0.0;

        foreach ($rows as $row) {
            $group_id = (int) ($row->global_sci_id ?? 0);
            $revenue = (float) ($row->revenue ?? 0);
            if ($revenue <= 0) {
                continue;
            }

            if ($group_id > 0) {
                $strategic_total += $revenue;
                $direct_groups[$group_id] = [
                    'global_sci_id' => $group_id,
                    'sci_code' => trim((string) ($row->sci_code ?? '')),
                    'sci_name' => trim((string) ($row->sci_name ?? '')),
                    'path' => trim((string) ($row->sci_path ?? '')),
                    'revenue' => $revenue,
                ];
            } else {
                $other_revenue += $revenue;
            }
        }

        $tree_rows = [];
        if (!empty($direct_groups)) {
            $all_hcl_rows = $wpdb->get_results(
                "SELECT id, sci_code, name, path
                 FROM {$sci_table}
                 WHERE is_deleted = 0 AND status = 1 AND path IS NOT NULL AND path <> ''
                 ORDER BY path ASC, id ASC",
                ARRAY_A
            ) ?: [];

            $tree_rows = self::build_hcl_tree_rows($direct_groups, $all_hcl_rows);
        }

        if ((float) $shop_sales_basis > 0 && $strategic_total > 0) {
            $other_revenue = max($other_revenue, max(0.0, (float) $shop_sales_basis - $strategic_total));
        }

        return [
            'tree_rows' => $tree_rows,
            'other_revenue' => $other_revenue,
            'strategic_total' => $strategic_total,
        ];
    }

    private static function build_hcl_breakdown_from_ledger($blog_id, $date_from, $date_to, $shop_sales_basis)
    {
        global $wpdb;

        $prefix = self::get_blog_prefix($blog_id);
        $ledger_table = $prefix . 'local_ledger';
        $item_table = $prefix . 'local_ledger_item';
        $product_table = $prefix . 'local_product_name';
        $mapping_table = $wpdb->base_prefix . 'global_product_sci_mapping';
        $sci_table = $wpdb->base_prefix . 'global_sci';

        if (
            $wpdb->get_var("SHOW TABLES LIKE '{$ledger_table}'") !== $ledger_table ||
            $wpdb->get_var("SHOW TABLES LIKE '{$item_table}'") !== $item_table ||
            $wpdb->get_var("SHOW TABLES LIKE '{$product_table}'") !== $product_table ||
            $wpdb->get_var("SHOW TABLES LIKE '{$mapping_table}'") !== $mapping_table ||
            $wpdb->get_var("SHOW TABLES LIKE '{$sci_table}'") !== $sci_table
        ) {
            return ['tree_rows' => [], 'other_revenue' => 0.0, 'strategic_total' => 0.0];
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT
                COALESCE(m.global_sci_id, 0) AS global_sci_id,
                COALESCE(s.sci_code, '')     AS sci_code,
                COALESCE(s.name, '')         AS sci_name,
                COALESCE(s.path, '')         AS sci_path,
                COALESCE(SUM(
                    (COALESCE(li.quantity, 0) * COALESCE(li.price, 0))
                    + COALESCE(li.local_ledger_item_tax_amount, 0)
                    - COALESCE(li.local_ledger_item_discount_amount, 0)
                ), 0) AS revenue
             FROM {$item_table} li
             INNER JOIN {$ledger_table} l
                ON l.local_ledger_id = li.local_ledger_id
             LEFT JOIN {$product_table} pn
                ON pn.local_product_name_id = li.local_product_name_id
             LEFT JOIN {$mapping_table} m
                ON m.sku = TRIM(pn.local_product_sku)
             LEFT JOIN {$sci_table} s
                ON s.id = m.global_sci_id
             WHERE l.local_ledger_type = 10
               AND l.local_ledger_status IN (2, 4)
               AND (l.is_deleted = 0 OR l.is_deleted IS NULL)
               AND (li.is_deleted = 0 OR li.is_deleted IS NULL)
               AND DATE(l.created_at) BETWEEN %s AND %s
             GROUP BY COALESCE(m.global_sci_id, 0), COALESCE(s.sci_code, ''), COALESCE(s.name, ''), COALESCE(s.path, '')
             ORDER BY sci_path ASC, revenue DESC",
            $date_from,
            $date_to
        )) ?: [];

        $direct_groups = [];
        $strategic_total = 0.0;
        $other_revenue = 0.0;

        foreach ($rows as $row) {
            $group_id = (int) ($row->global_sci_id ?? 0);
            $revenue = (float) ($row->revenue ?? 0);
            if ($revenue <= 0) {
                continue;
            }

            if ($group_id > 0) {
                $strategic_total += $revenue;
                $direct_groups[$group_id] = [
                    'global_sci_id' => $group_id,
                    'sci_code' => trim((string) ($row->sci_code ?? '')),
                    'sci_name' => trim((string) ($row->sci_name ?? '')),
                    'path' => trim((string) ($row->sci_path ?? '')),
                    'revenue' => $revenue,
                ];
            } else {
                $other_revenue += $revenue;
            }
        }

        $tree_rows = [];
        if (!empty($direct_groups)) {
            $all_hcl_rows = $wpdb->get_results(
                "SELECT id, sci_code, name, path
                 FROM {$sci_table}
                 WHERE is_deleted = 0 AND status = 1 AND path IS NOT NULL AND path <> ''
                 ORDER BY path ASC, id ASC",
                ARRAY_A
            ) ?: [];
            $tree_rows = self::build_hcl_tree_rows($direct_groups, $all_hcl_rows);
        }

        if ((float) $shop_sales_basis > 0 && $strategic_total > 0) {
            $other_revenue = max($other_revenue, max(0.0, (float) $shop_sales_basis - $strategic_total));
        }

        return [
            'tree_rows' => $tree_rows,
            'other_revenue' => $other_revenue,
            'strategic_total' => $strategic_total,
        ];
    }

    private static function build_hcl_tree_rows(array $direct_groups, array $all_hcl_rows): array
    {
        $nodes_by_path = [];
        $path_by_id = [];

        foreach ($all_hcl_rows as $row) {
            $path = self::normalize_hcl_path((string) ($row['path'] ?? ''));
            if ($path === '') {
                continue;
            }

            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $nodes_by_path[$path] = [
                'id' => $id,
                'path' => $path,
                'sci_code' => trim((string) ($row['sci_code'] ?? '')),
                'name' => trim((string) ($row['name'] ?? '')),
                'revenue' => 0.0,
                'children' => [],
            ];
            $path_by_id[$id] = $path;
        }

        foreach ($direct_groups as $group_id => $group) {
            $path = $path_by_id[(int) $group_id] ?? self::normalize_hcl_path((string) ($group['path'] ?? ''));
            $revenue = (float) ($group['revenue'] ?? 0);
            if ($path === '' || $revenue <= 0) {
                continue;
            }

            $current_path = $path;
            while ($current_path !== '') {
                if (isset($nodes_by_path[$current_path])) {
                    $nodes_by_path[$current_path]['revenue'] += $revenue;
                }

                $last_slash = strrpos($current_path, '/');
                if ($last_slash === false) {
                    break;
                }
                $current_path = substr($current_path, 0, $last_slash);
            }
        }

        foreach ($nodes_by_path as $path => &$node) {
            if ($node['revenue'] <= 0) {
                continue;
            }

            $last_slash = strrpos($path, '/');
            if ($last_slash === false) {
                continue;
            }

            $parent_path = substr($path, 0, $last_slash);
            if (isset($nodes_by_path[$parent_path]) && $nodes_by_path[$parent_path]['revenue'] > 0) {
                $nodes_by_path[$parent_path]['children'][] = $path;
            }
        }
        unset($node);

        $root_paths = [];
        foreach ($nodes_by_path as $path => $node) {
            if ($node['revenue'] <= 0) {
                continue;
            }

            $last_slash = strrpos($path, '/');
            $parent_path = $last_slash === false ? '' : substr($path, 0, $last_slash);
            if ($parent_path === '' || !isset($nodes_by_path[$parent_path]) || $nodes_by_path[$parent_path]['revenue'] <= 0) {
                $root_paths[] = $path;
            }
        }

        $tree_rows = [];
        foreach ($root_paths as $root_path) {
            self::append_hcl_tree_row($root_path, $nodes_by_path, $tree_rows, 0);
        }

        return $tree_rows;
    }

    private static function append_hcl_tree_row(string $path, array $nodes_by_path, array &$tree_rows, int $depth): void
    {
        if (!isset($nodes_by_path[$path])) {
            return;
        }

        $node = $nodes_by_path[$path];
        $label = $node['sci_code'] !== '' ? $node['sci_code'] : ($node['name'] !== '' ? $node['name'] : ('HCL #' . $node['id']));

        $tree_rows[] = [
            'global_sci_id' => (int) $node['id'],
            'label' => $label,
            'revenue' => (float) $node['revenue'],
            'depth' => $depth,
            'has_children' => !empty($node['children']),
        ];

        foreach ($node['children'] as $child_path) {
            self::append_hcl_tree_row($child_path, $nodes_by_path, $tree_rows, $depth + 1);
        }
    }

    private static function normalize_hcl_path(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        return trim($path, '/');
    }
}
