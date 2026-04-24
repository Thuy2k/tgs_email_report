<?php
/**
 * Template: Email Shop Report
 *
 * Biến có sẵn: $sales, $bank, $max, $summary, $gifts, $date_from, $date_to
 *
 * Gộp tất cả shop vào 1 email duy nhất
 */
if (!defined('ABSPATH')) exit;

$daily = $summary['daily_total'] ?? [];
$weekly = $summary['weekly_compare'] ?? [];
$fmt = function($v) { return number_format((float)$v, 0, ',', '.'); };

$date_label = '';
if (!empty($date_from) && !empty($date_to)) {
     if ($date_from === $date_to) {
          $date_label = 'Ngày ' . date('d/m/Y', strtotime($date_from));
     } else {
          $date_label = date('d/m/Y', strtotime($date_from)) . ' - ' . date('d/m/Y', strtotime($date_to));
     }
}

$change_pct = (float) ($weekly['change_pct'] ?? 0);
$change_bg = '#eef5ff';
$change_color = '#2d5f8a';
$change_text = 'Ổn định ' . number_format(abs($change_pct), 1, ',', '.') . '%';
if ($change_pct > 0) {
     $change_bg = '#e9f9ef';
     $change_color = '#1f8f4d';
     $change_text = 'Tăng ' . number_format(abs($change_pct), 1, ',', '.') . '%';
} elseif ($change_pct < 0) {
     $change_bg = '#fff1f0';
     $change_color = '#cf3d32';
     $change_text = 'Giảm ' . number_format(abs($change_pct), 1, ',', '.') . '%';
}

// Build body
ob_start();
?>

<div style="font-family:'Inter', 'Segoe UI', Tahoma, sans-serif; color:#1c2d40;">
     <div style="margin-bottom:20px; background:linear-gradient(180deg, #ffffff 0%, #f5f9fc 100%); border:1px solid #dbe6f1; border-radius:28px; padding:18px 18px 20px; box-shadow:0 18px 34px rgba(18, 43, 74, 0.08);">
          <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
               <tr>
                    <td style="vertical-align:top; padding-right:8px;">
                         <div style="font-size:22px; line-height:1.25; font-weight:700; color:#13273e;">Báo cáo bán hàng shop</div>
                    </td>
                    <td align="right" style="vertical-align:top; white-space:nowrap;">
                         <?php if ($date_label !== ''): ?>
                         <span style="display:inline-block; padding:7px 12px; border-radius:999px; background:#eff4f8; color:#35506c; font-size:11px; font-weight:700; letter-spacing:0.3px;">
                              <?php echo esc_html($date_label); ?>
                         </span>
                         <?php endif; ?>
                    </td>
               </tr>
          </table>

          <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:16px 0 14px;">
               <tr>
                    <td width="36%" style="height:4px; background:#20466f; border-radius:999px;"></td>
                    <td width="4%"></td>
                    <td width="30%" style="height:4px; background:#59a3ff; border-radius:999px;"></td>
                    <td width="4%"></td>
                    <td width="26%" style="height:4px; background:#c6d7ea; border-radius:999px;"></td>
               </tr>
          </table>

          <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
               <tr>
                    <td width="50%" style="padding:0 6px 10px 0; vertical-align:top;">
                         <div style="background:#ffffff; border:1px solid #e5edf5; border-radius:18px; padding:12px 14px;">
                              <div style="font-size:11px; text-transform:uppercase; color:#7b8a9a; letter-spacing:0.8px;">Đã thu bán hàng</div>
                              <div style="font-size:20px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo $fmt($daily['total_net'] ?? 0); ?>đ</div>
                         </div>
                    </td>
                    <td width="50%" style="padding:0 0 10px 6px; vertical-align:top;">
                         <div style="background:<?php echo $change_bg; ?>; border:1px solid rgba(0, 0, 0, 0.04); border-radius:18px; padding:12px 14px; text-align:right;">
                              <div style="font-size:11px; text-transform:uppercase; color:#7b8a9a; letter-spacing:0.8px;">Biến động tuần</div>
                              <div style="font-size:20px; font-weight:700; color:<?php echo $change_color; ?>; margin-top:4px;"><?php echo esc_html($change_text); ?></div>
                         </div>
                    </td>
               </tr>
               <tr>
                    <td width="50%" style="padding:0 6px 0 0; vertical-align:top;">
                         <div style="background:#ffffff; border:1px solid #e5edf5; border-radius:18px; padding:12px 14px;">
                              <div style="font-size:11px; text-transform:uppercase; color:#7b8a9a; letter-spacing:0.8px;">Tổng đơn hàng</div>
                              <div style="font-size:18px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo $fmt($daily['total_orders'] ?? 0); ?></div>
                         </div>
                    </td>
                    <td width="50%" style="padding:0 0 0 6px; vertical-align:top;">
                         <div style="background:#ffffff; border:1px solid #e5edf5; border-radius:18px; padding:12px 14px;">
                              <div style="font-size:11px; text-transform:uppercase; color:#7b8a9a; letter-spacing:0.8px;">Shop hoạt động</div>
                              <div style="font-size:18px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo $fmt($daily['active_shops'] ?? 0); ?></div>
                         </div>
                    </td>
               </tr>
          </table>
     </div>

<!-- ════════════════════════════════════════════
     SECTION 1: TỔNG QUAN HỆ THỐNG
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-summary-daily.php'; ?>

<!-- ════════════════════════════════════════════
     SECTION 2: DOANH THU BÁN HÀNG TỪNG SHOP
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-sales.php'; ?>

<!-- ════════════════════════════════════════════
     SECTION 3: TẶNG KÈM
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-gifts.php'; ?>

<!-- ════════════════════════════════════════════
     SECTION 4: ĐỐI CHIẾU THU NGÂN HÀNG
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-bank.php'; ?>

<!-- ════════════════════════════════════════════
     SECTION 5: MAX TỒN KHO TẠI SHOP
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-shop-max.php'; ?>

<!-- ════════════════════════════════════════════
     SECTION 6: SO SANH TUAN
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-summary-weekly.php'; ?>

<!-- ════════════════════════════════════════════
     SECTION 7: CANH BAO
     ════════════════════════════════════════════ -->
<?php include __DIR__ . '/components/section-alerts.php'; ?>

</div>

<?php
$body_content = ob_get_clean();
$subject = sprintf('Báo cáo bán hàng Chi nhánh Phú Thọ',
    ($date_from === $date_to) ? date('d/m/Y', strtotime($date_from))
        : date('d/m', strtotime($date_from)) . ' → ' . date('d/m/Y', strtotime($date_to))
);

// Render qua master layout
include __DIR__ . '/email-master-layout.php';
