<?php
/**
 * Admin View: Email Log Viewer — Xem lịch sử email đã gửi
 */
if (!defined('ABSPATH')) exit;
?>
<div class="tgs-email-dashboard" style="max-width:1200px; margin:0 auto;">

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <div>
            <h2 style="margin:0; font-size:22px; color:#1e3a5f;">📋 Lịch Sử Email Đã Gửi</h2>
            <p style="margin:4px 0 0; color:#6c757d; font-size:13px;">Xem lại nội dung email, trạng thái, số lần gửi.</p>
        </div>
        <a href="<?php echo admin_url('admin.php?page=tgs-shop-management&view=email-report-dashboard'); ?>"
           class="button" style="background:#f0f4f8; border-color:#dee2e6; color:#1e3a5f;">
            ← Quay lại Dashboard
        </a>
    </div>

    <!-- Filter -->
    <div style="display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; align-items:center;">
        <select id="log-filter-type" style="padding:6px 10px; border:1px solid #ccc; border-radius:4px;">
            <option value="">Tất cả loại</option>
            <option value="shop_report">Shop Report</option>
            <option value="warehouse_report">Warehouse Report</option>
            <option value="backup_report">Backup Report</option>
        </select>
        <button id="btn-log-filter" class="button" style="padding:6px 14px;">🔍 Lọc</button>
    </div>

    <!-- Table -->
    <div class="tgs-email-card" style="background:#fff; border-radius:8px; padding:16px 20px; box-shadow:0 1px 4px rgba(0,0,0,.08);">
        <div id="tgs-email-log-table">
            <p style="color:#888;">Đang tải...</p>
        </div>

        <!-- Pagination -->
        <div id="tgs-email-log-pagination" style="margin-top:12px; display:flex; justify-content:center; gap:8px;"></div>
    </div>
</div>

<!-- Modal (reuse from dashboard) -->
<div id="tgs-email-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:100000; justify-content:center; align-items:center;">
    <div style="background:#fff; border-radius:8px; width:95%; max-width:900px; max-height:90vh; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 20px; background:#1e3a5f; color:#fff;">
            <span id="tgs-email-modal-title" style="font-weight:600;">Email Detail</span>
            <button id="tgs-email-modal-close" style="background:none; border:none; color:#fff; font-size:20px; cursor:pointer; padding:0 4px;">✕</button>
        </div>
        <div style="overflow:auto; max-height:calc(90vh - 60px);">
            <iframe id="tgs-email-modal-iframe" style="width:100%; min-height:600px; border:none;"></iframe>
        </div>
    </div>
</div>
