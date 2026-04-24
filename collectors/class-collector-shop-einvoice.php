<?php
/**
 * Collector: Shop E-Invoice — Thong ke hoa don dien tu theo tung shop
 *
 * Chi tieu:
 * - total_orders: Tong don POS da chot (type=10)
 * - invoiced_orders: So don da xuat HDDT (sent + demo_signed)
 * - pending_orders: So don dang pending
 * - not_invoiced_orders: So don chua xuat HDDT
 * - status: pending/sent/demo_signed/failed/cancel_requested/canceled
 * - compliance flags: include_gifts, has_under24m_item, is_split_for_under24m
 */

if (!defined('ABSPATH')) exit;

class TGS_Collector_Shop_EInvoice extends TGS_Collector_Base
{
    public static function collect($date_from, $date_to)
    {
        global $wpdb;

        $blogs = self::get_active_blog_ids();
        $shop_names = self::get_shop_names();

        $by_shop = [];
        $summary = [
            'total_orders'          => 0,
            'invoiced_orders'       => 0,
            'pending_orders'        => 0,
            'not_invoiced_orders'   => 0,
            'invoiced_value'        => 0.0,
            'sent_orders'           => 0,
            'sent_value'            => 0.0,
            'demo_signed_orders'    => 0,
            'demo_signed_value'     => 0.0,
            'failed_orders'         => 0,
            'failed_value'          => 0.0,
            'cancel_requested_orders' => 0,
            'cancel_requested_value'  => 0.0,
            'canceled_orders'       => 0,
            'canceled_value'        => 0.0,
            'include_gifts_count'   => 0,
            'under24_count'         => 0,
            'split_under24_count'   => 0,
            'no_under24_count'      => 0,
            'active_shops'          => 0,
            'coverage_pct'          => 0.0,
        ];

        foreach ($blogs as $blog) {
            $blog_id = (int) $blog->blog_id;
            $prefix = self::get_blog_prefix($blog_id);
            $ledger_table = $prefix . 'local_ledger';
            $invoice_table = $prefix . 'tgs_einvoice_invoices';

            $shop_info = $shop_names[$blog_id] ?? [
                'code' => 'SHOP-' . $blog_id,
                'name' => 'Shop #' . $blog_id,
            ];

            $total_orders = 0;
            if ($wpdb->get_var("SHOW TABLES LIKE '{$ledger_table}'") === $ledger_table) {
                $total_orders = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT local_ledger_id)
                     FROM {$ledger_table}
                     WHERE local_ledger_type = 10
                       AND local_ledger_status IN (2, 4)
                       AND (is_deleted = 0 OR is_deleted IS NULL)
                       AND DATE(created_at) BETWEEN %s AND %s",
                    $date_from,
                    $date_to
                ));
            }

            $metrics = [
                'invoiced_orders'         => 0,
                'invoiced_value'          => 0.0,
                'pending_orders'          => 0,
                'pending_value'           => 0.0,
                'sent_orders'             => 0,
                'sent_value'              => 0.0,
                'sent_under24_count'      => 0,
                'sent_split_under24_count'=> 0,
                'sent_no_under24_count'   => 0,
                'demo_signed_orders'      => 0,
                'demo_signed_value'       => 0.0,
                'demo_signed_under24_count'       => 0,
                'demo_signed_split_under24_count' => 0,
                'demo_signed_no_under24_count'    => 0,
                'failed_orders'           => 0,
                'failed_value'            => 0.0,
                'failed_under24_count'    => 0,
                'failed_split_under24_count' => 0,
                'failed_no_under24_count' => 0,
                'cancel_requested_orders' => 0,
                'cancel_requested_value'  => 0.0,
                'cancel_requested_under24_count' => 0,
                'cancel_requested_split_under24_count' => 0,
                'cancel_requested_no_under24_count' => 0,
                'canceled_orders'         => 0,
                'canceled_value'          => 0.0,
                'canceled_under24_count'  => 0,
                'canceled_split_under24_count' => 0,
                'canceled_no_under24_count' => 0,
                'pending_under24_count'   => 0,
                'pending_split_under24_count' => 0,
                'pending_no_under24_count' => 0,
                'include_gifts_count'     => 0,
                'under24_count'           => 0,
                'split_under24_count'     => 0,
                'no_under24_count'        => 0,
            ];

            if ($wpdb->get_var("SHOW TABLES LIKE '{$invoice_table}'") === $invoice_table) {
                $row = $wpdb->get_row($wpdb->prepare(
                    "SELECT
                        COUNT(DISTINCT CASE WHEN status IN ('sent', 'demo_signed') THEN sale_ledger_id END) AS invoiced_orders,
                        COALESCE(SUM(CASE WHEN status IN ('sent', 'demo_signed') THEN total_after_tax ELSE 0 END), 0) AS invoiced_value,
                        COUNT(DISTINCT CASE WHEN status = 'pending' THEN sale_ledger_id END) AS pending_orders,
                        COALESCE(SUM(CASE WHEN status = 'pending' THEN total_after_tax ELSE 0 END), 0) AS pending_value,
                        COUNT(DISTINCT CASE WHEN status = 'sent' THEN sale_ledger_id END) AS sent_orders,
                        COALESCE(SUM(CASE WHEN status = 'sent' THEN total_after_tax ELSE 0 END), 0) AS sent_value,
                        COALESCE(SUM(CASE WHEN status = 'sent' AND has_under24m_item = 1 THEN 1 ELSE 0 END), 0) AS sent_under24_count,
                        COALESCE(SUM(CASE WHEN status = 'sent' AND is_split_for_under24m = 1 THEN 1 ELSE 0 END), 0) AS sent_split_under24_count,
                        COALESCE(SUM(CASE WHEN status = 'sent' AND has_under24m_item = 0 THEN 1 ELSE 0 END), 0) AS sent_no_under24_count,
                        COUNT(DISTINCT CASE WHEN status = 'demo_signed' THEN sale_ledger_id END) AS demo_signed_orders,
                        COALESCE(SUM(CASE WHEN status = 'demo_signed' THEN total_after_tax ELSE 0 END), 0) AS demo_signed_value,
                        COALESCE(SUM(CASE WHEN status = 'demo_signed' AND has_under24m_item = 1 THEN 1 ELSE 0 END), 0) AS demo_signed_under24_count,
                        COALESCE(SUM(CASE WHEN status = 'demo_signed' AND is_split_for_under24m = 1 THEN 1 ELSE 0 END), 0) AS demo_signed_split_under24_count,
                        COALESCE(SUM(CASE WHEN status = 'demo_signed' AND has_under24m_item = 0 THEN 1 ELSE 0 END), 0) AS demo_signed_no_under24_count,
                        COUNT(DISTINCT CASE WHEN status = 'failed' THEN sale_ledger_id END) AS failed_orders,
                        COALESCE(SUM(CASE WHEN status = 'failed' THEN total_after_tax ELSE 0 END), 0) AS failed_value,
                        COALESCE(SUM(CASE WHEN status = 'failed' AND has_under24m_item = 1 THEN 1 ELSE 0 END), 0) AS failed_under24_count,
                        COALESCE(SUM(CASE WHEN status = 'failed' AND is_split_for_under24m = 1 THEN 1 ELSE 0 END), 0) AS failed_split_under24_count,
                        COALESCE(SUM(CASE WHEN status = 'failed' AND has_under24m_item = 0 THEN 1 ELSE 0 END), 0) AS failed_no_under24_count,
                        COUNT(DISTINCT CASE WHEN status = 'cancel_requested' THEN sale_ledger_id END) AS cancel_requested_orders,
                        COALESCE(SUM(CASE WHEN status = 'cancel_requested' THEN total_after_tax ELSE 0 END), 0) AS cancel_requested_value,
                        COALESCE(SUM(CASE WHEN status = 'cancel_requested' AND has_under24m_item = 1 THEN 1 ELSE 0 END), 0) AS cancel_requested_under24_count,
                        COALESCE(SUM(CASE WHEN status = 'cancel_requested' AND is_split_for_under24m = 1 THEN 1 ELSE 0 END), 0) AS cancel_requested_split_under24_count,
                        COALESCE(SUM(CASE WHEN status = 'cancel_requested' AND has_under24m_item = 0 THEN 1 ELSE 0 END), 0) AS cancel_requested_no_under24_count,
                        COUNT(DISTINCT CASE WHEN status = 'canceled' THEN sale_ledger_id END) AS canceled_orders,
                        COALESCE(SUM(CASE WHEN status = 'canceled' THEN total_after_tax ELSE 0 END), 0) AS canceled_value,
                        COALESCE(SUM(CASE WHEN status = 'canceled' AND has_under24m_item = 1 THEN 1 ELSE 0 END), 0) AS canceled_under24_count,
                        COALESCE(SUM(CASE WHEN status = 'canceled' AND is_split_for_under24m = 1 THEN 1 ELSE 0 END), 0) AS canceled_split_under24_count,
                        COALESCE(SUM(CASE WHEN status = 'canceled' AND has_under24m_item = 0 THEN 1 ELSE 0 END), 0) AS canceled_no_under24_count,
                        COALESCE(SUM(CASE WHEN status = 'pending' AND has_under24m_item = 1 THEN 1 ELSE 0 END), 0) AS pending_under24_count,
                        COALESCE(SUM(CASE WHEN status = 'pending' AND is_split_for_under24m = 1 THEN 1 ELSE 0 END), 0) AS pending_split_under24_count,
                        COALESCE(SUM(CASE WHEN status = 'pending' AND has_under24m_item = 0 THEN 1 ELSE 0 END), 0) AS pending_no_under24_count,
                        COALESCE(SUM(CASE WHEN include_gifts = 1 THEN 1 ELSE 0 END), 0) AS include_gifts_count,
                        COALESCE(SUM(CASE WHEN has_under24m_item = 1 THEN 1 ELSE 0 END), 0) AS under24_count,
                                COALESCE(SUM(CASE WHEN is_split_for_under24m = 1 THEN 1 ELSE 0 END), 0) AS split_under24_count,
                                COALESCE(SUM(CASE WHEN has_under24m_item = 0 THEN 1 ELSE 0 END), 0) AS no_under24_count
                     FROM {$invoice_table}
                     WHERE DATE(created_at) BETWEEN %s AND %s",
                    $date_from,
                    $date_to
                ));

                if ($row) {
                    foreach ($metrics as $k => $v) {
                        $is_value_field = substr($k, -6) === '_value';
                        $metrics[$k] = $is_value_field ? (float) ($row->$k ?? 0) : (int) ($row->$k ?? 0);
                    }
                }
            }

            $not_invoiced_orders = max(0, $total_orders - $metrics['invoiced_orders'] - $metrics['pending_orders']);
            $coverage_pct = $total_orders > 0 ? round(($metrics['invoiced_orders'] / $total_orders) * 100, 1) : 0.0;

            $by_shop[$blog_id] = [
                'shop_name'                => $shop_info['name'],
                'shop_code'                => $shop_info['code'],
                'total_orders'             => $total_orders,
                'invoiced_orders'          => $metrics['invoiced_orders'],
                'invoiced_value'           => $metrics['invoiced_value'],
                'pending_orders'           => $metrics['pending_orders'],
                'pending_value'            => $metrics['pending_value'],
                'not_invoiced_orders'      => $not_invoiced_orders,
                'sent_orders'              => $metrics['sent_orders'],
                'sent_value'               => $metrics['sent_value'],
                'sent_under24_count'       => $metrics['sent_under24_count'],
                'sent_split_under24_count' => $metrics['sent_split_under24_count'],
                'sent_no_under24_count'    => $metrics['sent_no_under24_count'],
                'demo_signed_orders'       => $metrics['demo_signed_orders'],
                'demo_signed_value'        => $metrics['demo_signed_value'],
                'demo_signed_under24_count'       => $metrics['demo_signed_under24_count'],
                'demo_signed_split_under24_count' => $metrics['demo_signed_split_under24_count'],
                'demo_signed_no_under24_count'    => $metrics['demo_signed_no_under24_count'],
                'failed_orders'            => $metrics['failed_orders'],
                'failed_value'             => $metrics['failed_value'],
                'failed_under24_count'     => $metrics['failed_under24_count'],
                'failed_split_under24_count' => $metrics['failed_split_under24_count'],
                'failed_no_under24_count'  => $metrics['failed_no_under24_count'],
                'cancel_requested_orders'  => $metrics['cancel_requested_orders'],
                'cancel_requested_value'   => $metrics['cancel_requested_value'],
                'cancel_requested_under24_count' => $metrics['cancel_requested_under24_count'],
                'cancel_requested_split_under24_count' => $metrics['cancel_requested_split_under24_count'],
                'cancel_requested_no_under24_count' => $metrics['cancel_requested_no_under24_count'],
                'canceled_orders'          => $metrics['canceled_orders'],
                'canceled_value'           => $metrics['canceled_value'],
                'canceled_under24_count'   => $metrics['canceled_under24_count'],
                'canceled_split_under24_count' => $metrics['canceled_split_under24_count'],
                'canceled_no_under24_count' => $metrics['canceled_no_under24_count'],
                'pending_under24_count'    => $metrics['pending_under24_count'],
                'pending_split_under24_count' => $metrics['pending_split_under24_count'],
                'pending_no_under24_count' => $metrics['pending_no_under24_count'],
                'include_gifts_count'      => $metrics['include_gifts_count'],
                'under24_count'            => $metrics['under24_count'],
                'split_under24_count'      => $metrics['split_under24_count'],
                'no_under24_count'         => $metrics['no_under24_count'],
                'coverage_pct'             => $coverage_pct,
            ];

            $summary['total_orders'] += $total_orders;
            $summary['invoiced_orders'] += $metrics['invoiced_orders'];
            $summary['invoiced_value'] += $metrics['invoiced_value'];
            $summary['pending_orders'] += $metrics['pending_orders'];
            $summary['not_invoiced_orders'] += $not_invoiced_orders;
            $summary['sent_orders'] += $metrics['sent_orders'];
            $summary['sent_value'] += $metrics['sent_value'];
            $summary['demo_signed_orders'] += $metrics['demo_signed_orders'];
            $summary['demo_signed_value'] += $metrics['demo_signed_value'];
            $summary['failed_orders'] += $metrics['failed_orders'];
            $summary['failed_value'] += $metrics['failed_value'];
            $summary['cancel_requested_orders'] += $metrics['cancel_requested_orders'];
            $summary['cancel_requested_value'] += $metrics['cancel_requested_value'];
            $summary['canceled_orders'] += $metrics['canceled_orders'];
            $summary['canceled_value'] += $metrics['canceled_value'];
            $summary['include_gifts_count'] += $metrics['include_gifts_count'];
            $summary['under24_count'] += $metrics['under24_count'];
            $summary['split_under24_count'] += $metrics['split_under24_count'];
            $summary['no_under24_count'] += $metrics['no_under24_count'];

            if ($total_orders > 0) {
                $summary['active_shops']++;
            }
        }

        if ($summary['total_orders'] > 0) {
            $summary['coverage_pct'] = round(($summary['invoiced_orders'] / $summary['total_orders']) * 100, 1);
        }

        uasort($by_shop, function ($a, $b) {
            return $b['total_orders'] <=> $a['total_orders'];
        });

        return [
            'summary' => $summary,
            'by_shop' => $by_shop,
        ];
    }
}
