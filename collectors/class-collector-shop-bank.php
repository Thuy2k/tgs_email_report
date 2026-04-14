<?php
/**
 * Collector: Shop Bank — Tổng hợp tiền thu và phương thức thanh toán từng shop.
 *
 * Data source:
 *   - local_ledger type=7 (Phiếu thu thực tế)
 *   - local_ledger type=10 (Sale) và type=11 (Return) để lấy tổng bán ròng
 */

if (!defined('ABSPATH')) exit;

class TGS_Collector_Shop_Bank extends TGS_Collector_Base
{
    public static function collect($date_from, $date_to)
    {
        global $wpdb;

        $result = [
            'shops' => [],
            'payment_methods' => [],
            'totals' => [
                'expected_revenue' => 0.0,
                'actual_collected' => 0.0,
                'receipt_count' => 0,
                'sale_count' => 0,
            ],
        ];
        $shop_names = self::get_shop_names();
        $blogs = self::get_active_blog_ids();

        foreach ($blogs as $blog) {
            $prefix = self::get_blog_prefix($blog->blog_id);
            $ledger = $prefix . 'local_ledger';

            if ($wpdb->get_var("SHOW TABLES LIKE '{$ledger}'") !== $ledger) {
                continue;
            }

            // Query tổng hợp: tổng bán đã sau chiết khấu + trả hàng + số phiếu thu.
            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT
                    COALESCE(SUM(CASE WHEN local_ledger_type = 10 THEN local_ledger_total_amount ELSE 0 END), 0)
                        as sale_total,

                    COALESCE(SUM(CASE WHEN local_ledger_type = 11 THEN local_ledger_total_amount ELSE 0 END), 0)
                        as return_total,

                    COUNT(DISTINCT CASE WHEN local_ledger_type = 7 AND local_ledger_total_amount > 0 THEN local_ledger_id END) as receipt_count,

                    COUNT(DISTINCT CASE WHEN local_ledger_type = 10 THEN local_ledger_id END) as sale_count

                 FROM {$ledger}
                 WHERE local_ledger_type IN (7, 10, 11)
                   AND local_ledger_status IN (2, 4)
                   AND is_deleted = 0
                   AND DATE(created_at) BETWEEN %s AND %s",
                $date_from, $date_to
            ));

            if (!$row) {
                continue;
            }

            $expected  = (float) $row->sale_total - (float) $row->return_total;

            $payment_breakdown = self::get_payment_breakdown($prefix, $date_from, $date_to);
            $collected = self::sum_payment_breakdown($payment_breakdown);

            if ($expected == 0.0 && $collected == 0.0) {
                continue;
            }

            $info = $shop_names[$blog->blog_id] ?? ['code' => 'SHOP-' . $blog->blog_id, 'name' => 'Shop #' . $blog->blog_id];

            $result['shops'][$blog->blog_id] = [
                'shop_name'         => $info['name'],
                'shop_code'         => $info['code'],
                'expected_revenue'  => $expected,
                'actual_collected'  => $collected,
                'receipt_count'     => (int) $row->receipt_count,
                'sale_count'        => (int) $row->sale_count,
                'payment_breakdown' => $payment_breakdown,
            ];

            $result['totals']['expected_revenue'] += $expected;
            $result['totals']['actual_collected'] += $collected;
            $result['totals']['receipt_count'] += (int) $row->receipt_count;
            $result['totals']['sale_count'] += (int) $row->sale_count;
            self::merge_payment_method_totals($result['payment_methods'], $payment_breakdown);
        }

        uasort($result['shops'], function ($a, $b) {
            $revenue_compare = $b['actual_collected'] <=> $a['actual_collected'];
            if ($revenue_compare !== 0) {
                return $revenue_compare;
            }

            return $b['sale_count'] <=> $a['sale_count'];
        });

        uasort($result['payment_methods'], function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        $result['payment_methods'] = array_values($result['payment_methods']);

        return $result;
    }

    /**
     * Lấy chi tiết thanh toán theo phương thức từ phiếu thu.
     */
    private static function get_payment_breakdown($prefix, $date_from, $date_to)
    {
        global $wpdb;
        $ledger = $prefix . 'local_ledger';
        $meta_table = $prefix . 'local_ledger_meta';
        $has_meta_table = ($wpdb->get_var("SHOW TABLES LIKE '{$meta_table}'") === $meta_table);

        $sql = "SELECT l.local_ledger_total_amount, l.local_ledger_note";
        if ($has_meta_table) {
            $sql .= ", m.local_ledger_meta_value";
        }
        $sql .= "
             FROM {$ledger} l";
        if ($has_meta_table) {
            $sql .= " LEFT JOIN {$meta_table} m ON l.local_ledger_meta_id = m.local_ledger_meta_id";
        }
        $sql .= $wpdb->prepare(
            "
             WHERE l.local_ledger_type = 7
               AND l.local_ledger_status IN (2, 4)
               AND l.is_deleted = 0
               AND DATE(l.created_at) BETWEEN %s AND %s",
            $date_from, $date_to
        );

        $rows = $wpdb->get_results($sql);

        $breakdown = [];
        foreach ($rows as $r) {
            $amount = (float) $r->local_ledger_total_amount;
            if ($amount <= 0) {
                continue;
            }

            $meta = [];
            if ($has_meta_table && !empty($r->local_ledger_meta_value)) {
                $decoded = json_decode($r->local_ledger_meta_value, true);
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            }

            $method = self::clean_text($meta['receipt_method'] ?? 'unknown');
            $display = self::resolve_payment_display($method, $r->local_ledger_note ?? '');
            $key = self::build_payment_method_key($display['label'], $display['method']);

            if (!isset($breakdown[$key])) {
                $breakdown[$key] = [
                    'method' => $display['method'],
                    'label' => $display['label'],
                    'total' => 0.0,
                    'count' => 0,
                ];
            }

            $breakdown[$key]['total'] += $amount;
            $breakdown[$key]['count']++;
        }

        return array_values($breakdown);
    }

    private static function resolve_payment_display($method, $note)
    {
        $method = self::clean_text($method);
        $note = self::clean_text($note);

        if (!empty($note) && preg_match('/QR Payment \((.*?)\)/', $note, $matches)) {
            $gateway = strtoupper(trim($matches[1]));
            return [
                'method' => $method !== '' ? $method : 'qr',
                'label' => $gateway,
            ];
        }

        return [
            'method' => $method !== '' ? $method : 'unknown',
            'label' => self::map_payment_label($method !== '' ? $method : 'unknown'),
        ];
    }

    private static function build_payment_method_key($label, $method)
    {
        $label = (string) $label;
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
            if ($converted !== false) {
                $label = $converted;
            }
        }
        $label = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $label));
        $label = trim($label, '_');

        return $label !== '' ? $label : ($method ?: 'unknown');
    }

    private static function merge_payment_method_totals(&$totals, $payment_breakdown)
    {
        foreach ($payment_breakdown as $row) {
            $key = self::build_payment_method_key($row['label'] ?? '', $row['method'] ?? 'unknown');

            if (!isset($totals[$key])) {
                $totals[$key] = [
                    'method' => $row['method'] ?? 'unknown',
                    'label' => $row['label'] ?? self::map_payment_label($row['method'] ?? 'unknown'),
                    'total' => 0.0,
                    'count' => 0,
                ];
            }

            $totals[$key]['total'] += (float) ($row['total'] ?? 0);
            $totals[$key]['count'] += (int) ($row['count'] ?? 0);
        }
    }

    private static function sum_payment_breakdown($payment_breakdown)
    {
        $total = 0.0;
        foreach ($payment_breakdown as $row) {
            $total += (float) ($row['total'] ?? 0);
        }
        return $total;
    }

    private static function map_payment_label($method)
    {
        $labels = [
            'cash'          => 'Tiền mặt',
            'transfer'      => 'Chuyển khoản',
            'bank_transfer' => 'Chuyển khoản NH',
            'card'          => 'Thẻ',
            'qr'            => 'QR CODE',
            'momo'          => 'MoMo',
            'zalopay'       => 'ZaloPay',
            'vnpay'         => 'VNPay',
            'unknown'       => 'Khác',
        ];
        return $labels[$method] ?? ucfirst(str_replace('_', ' ', $method));
    }
}
