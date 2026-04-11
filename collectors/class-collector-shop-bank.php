<?php
/**
 * Collector: Shop Bank — Tiền thu ngân hàng / tiền mặt từng shop
 *
 * Đối chiếu:
 *   - Tiền THỰC TẾ cần thu (net_revenue từ sale)
 *   - Tiền THỰC TẾ đã thu (receipts — local_ledger_type = 7)
 *   - Chênh lệch → cảnh báo
 *
 * Data source:
 *   - local_ledger type=7 (Phiếu thu)
 *   - local_ledger type=10 (Sale) — để đối chiếu
 */

if (!defined('ABSPATH')) exit;

class TGS_Collector_Shop_Bank extends TGS_Collector_Base
{
    public static function collect($date_from, $date_to)
    {
        global $wpdb;

        $result = [];
        $shop_names = self::get_shop_names();
        $blogs = self::get_active_blog_ids();

        foreach ($blogs as $blog) {
            $prefix = self::get_blog_prefix($blog->blog_id);
            $ledger = $prefix . 'local_ledger';

            if ($wpdb->get_var("SHOW TABLES LIKE '{$ledger}'") !== $ledger) {
                continue;
            }

            // Query tổng hợp: doanh thu cần thu + thực thu
            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT
                    /* Tiền cần thu (từ sale orders) */
                    COALESCE(SUM(CASE WHEN local_ledger_type = 10 THEN local_ledger_total_amount ELSE 0 END), 0)
                        - COALESCE(SUM(CASE WHEN local_ledger_type = 10 THEN COALESCE(local_ledger_discount, 0) ELSE 0 END), 0)
                        - COALESCE(SUM(CASE WHEN local_ledger_type = 11 THEN local_ledger_total_amount ELSE 0 END), 0)
                        as expected_revenue,

                    /* Tiền thực thu (receipts) */
                    COALESCE(SUM(CASE WHEN local_ledger_type = 7 THEN local_ledger_total_amount ELSE 0 END), 0)
                        as actual_collected,

                    /* Số phiếu thu */
                    COUNT(DISTINCT CASE WHEN local_ledger_type = 7 THEN local_ledger_id END) as receipt_count,

                    /* Số đơn bán */
                    COUNT(DISTINCT CASE WHEN local_ledger_type = 10 THEN local_ledger_id END) as sale_count

                 FROM {$ledger}
                 WHERE local_ledger_type IN (7, 10, 11)
                   AND local_ledger_status IN (2, 4)
                   AND is_deleted = 0
                   AND DATE(created_at) BETWEEN %s AND %s",
                $date_from, $date_to
            ));

            if (!$row || ((float) $row->expected_revenue == 0 && (float) $row->actual_collected == 0)) {
                continue;
            }

            $expected  = (float) $row->expected_revenue;
            $collected = (float) $row->actual_collected;
            $diff      = $collected - $expected;
            $diff_pct  = $expected > 0 ? round(($diff / $expected) * 100, 2) : 0;

            // Xác định trạng thái cảnh báo
            $status = 'ok';
            if (abs($diff) > 1000) { // chênh > 1,000đ
                $status = $diff > 0 ? 'surplus' : 'deficit'; // thừa / thiếu
            }

            // Chi tiết theo payment method (nếu có meta)
            $payment_breakdown = self::get_payment_breakdown($prefix, $date_from, $date_to);

            $info = $shop_names[$blog->blog_id] ?? ['code' => 'SHOP-' . $blog->blog_id, 'name' => 'Shop #' . $blog->blog_id];

            $result[$blog->blog_id] = [
                'shop_name'         => $info['name'],
                'shop_code'         => $info['code'],
                'expected_revenue'  => $expected,
                'actual_collected'  => $collected,
                'difference'        => $diff,
                'difference_pct'    => $diff_pct,
                'status'            => $status, // ok | surplus | deficit
                'receipt_count'     => (int) $row->receipt_count,
                'sale_count'        => (int) $row->sale_count,
                'payment_breakdown' => $payment_breakdown,
            ];
        }

        // Sắp xếp: deficit trước, rồi surplus, rồi ok
        uasort($result, function ($a, $b) {
            $priority = ['deficit' => 0, 'surplus' => 1, 'ok' => 2];
            $pa = $priority[$a['status']] ?? 2;
            $pb = $priority[$b['status']] ?? 2;
            if ($pa !== $pb) return $pa <=> $pb;
            return abs($b['difference']) <=> abs($a['difference']);
        });

        return $result;
    }

    /**
     * Lấy chi tiết thanh toán theo phương thức (cash, bank_transfer, card)
     */
    private static function get_payment_breakdown($prefix, $date_from, $date_to)
    {
        global $wpdb;
        $ledger = $prefix . 'local_ledger';

        // Tổng phiếu thu theo debit_account (tài khoản nợ)
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT
                COALESCE(local_ledger_debit_account, 'unknown') as method,
                SUM(local_ledger_total_amount) as total,
                COUNT(*) as count
             FROM {$ledger}
             WHERE local_ledger_type = 7
               AND local_ledger_status IN (2, 4)
               AND is_deleted = 0
               AND DATE(created_at) BETWEEN %s AND %s
             GROUP BY local_ledger_debit_account",
            $date_from, $date_to
        ));

        $breakdown = [];
        foreach ($rows as $r) {
            $label = self::map_payment_label($r->method);
            $breakdown[] = [
                'method' => $r->method,
                'label'  => $label,
                'total'  => (float) $r->total,
                'count'  => (int) $r->count,
            ];
        }
        return $breakdown;
    }

    private static function map_payment_label($method)
    {
        $labels = [
            'cash'          => 'Tiền mặt',
            'bank_transfer' => 'Chuyển khoản NH',
            'card'          => 'Thẻ',
            'momo'          => 'MoMo',
            'zalopay'       => 'ZaloPay',
            'vnpay'         => 'VNPay',
            'unknown'       => 'Khác',
        ];
        return $labels[$method] ?? ucfirst(str_replace('_', ' ', $method));
    }
}
