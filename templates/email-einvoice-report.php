<?php
/**
 * Template: Email E-Invoice Report
 *
 * Biến có sẵn: $einvoice, $date_from, $date_to
 */
if (!defined('ABSPATH')) exit;

ob_start();
?>
<div style="font-family:'Inter', 'Segoe UI', Tahoma, sans-serif; color:#1c2d40;">
    <?php include __DIR__ . '/components/section-einvoice.php'; ?>
</div>

<?php
$body_content = ob_get_clean();
$subject = sprintf('[TGS] Báo cáo HĐĐT theo shop - %s',
    ($date_from === $date_to)
        ? date('d/m/Y', strtotime($date_from))
        : date('d/m', strtotime($date_from)) . ' → ' . date('d/m/Y', strtotime($date_to))
);

include __DIR__ . '/email-master-layout.php';
