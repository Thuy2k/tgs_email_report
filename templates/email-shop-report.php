<?php
/**
 * Template: Email Shop Report
 *
 * Biến có sẵn: $sales, $bank, $max, $summary, $date_from, $date_to
 *
 * Gộp tất cả shop vào 1 email duy nhất
 */
if (!defined('ABSPATH')) exit;

// Build body
ob_start();
?>

<!-- ════════════════════════════════════════════
     SECTION 1: TỔNG QUAN HỆ THỐNG
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-summary-daily.php'; ?>

<!-- ════════════════════════════════════════════
     SECTION 2: DOANH THU BÁN HÀNG TỪNG SHOP
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-sales.php'; ?>

<!-- ════════════════════════════════════════════
     SECTION 3: ĐỐI CHIẾU THU NGÂN HÀNG
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-bank.php'; ?>

<!-- ════════════════════════════════════════════
     SECTION 4: MAX TỒN KHO TẠI SHOP
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-shop-max.php'; ?>

<!-- ════════════════════════════════════════════
     SECTION 5: SO SÁNH TUẦN
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-summary-weekly.php'; ?>

<!-- ════════════════════════════════════════════
     SECTION 6: CẢNH BÁO
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-alerts.php'; ?>

<?php
$body_content = ob_get_clean();
$subject = sprintf('Báo cáo bán hàng Chi nhánh Phú Thọ',
    ($date_from === $date_to) ? date('d/m/Y', strtotime($date_from))
        : date('d/m', strtotime($date_from)) . ' → ' . date('d/m/Y', strtotime($date_to))
);

// Render qua master layout
include __DIR__ . '/email-master-layout.php';
