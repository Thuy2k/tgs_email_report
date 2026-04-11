<?php
/**
 * Template: Email Warehouse Report
 *
 * Biến có sẵn: $minmax, $stock, $summary, $date_from, $date_to
 */
if (!defined('ABSPATH')) exit;

ob_start();
?>

<!-- ════════════════════════════════════════════
     SECTION 1: TỔNG QUAN KHO
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-warehouse-overview.php'; ?>

<!-- ════════════════════════════════════════════
     SECTION 2: CẢNH BÁO MIN/MAX
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-warehouse-minmax.php'; ?>

<!-- ════════════════════════════════════════════
     SECTION 3: TỒN KHO THEO SHOP
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-warehouse-stock.php'; ?>

<!-- ════════════════════════════════════════════
     SECTION 4: CẢNH BÁO HẠN SỬ DỤNG
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-warehouse-expiry.php'; ?>

<!-- ════════════════════════════════════════════
     SECTION 5: ĐỀ XUẤT MUA HÀNG
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-warehouse-reorder.php'; ?>

<?php
$body_content = ob_get_clean();
$subject = sprintf('[TGS] Báo cáo Kho — MIN/MAX & Tồn — %s',
    ($date_from === $date_to) ? date('d/m/Y', strtotime($date_from))
        : date('d/m', strtotime($date_from)) . ' → ' . date('d/m/Y', strtotime($date_to))
);

include __DIR__ . '/email-master-layout.php';
