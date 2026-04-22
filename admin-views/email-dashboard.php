<?php
/**
 * Admin View: Email Report Dashboard
 *
 * Giao diện chính để:
 *   1. Gửi báo cáo Shop / Kho / Cả 2
 *   2. Chọn ngày / khoảng ngày
 *   3. Xem trước email trước khi gửi
 *   4. Quản lý danh sách người nhận
 *   5. URL trigger links
 *   6. Xem lịch sử gửi
 */
if (!defined('ABSPATH')) exit;

$today = current_time('Y-m-d');
$today_vn = date('d/m/Y', strtotime($today));
$trigger_url_all  = TGS_Email_Trigger::get_trigger_url('all');
$trigger_url_shop = TGS_Email_Trigger::get_trigger_url('shop');
$trigger_url_wh   = TGS_Email_Trigger::get_trigger_url('warehouse');
$trigger_url_backup = TGS_Email_Trigger::get_trigger_url('backup');
?>

<div class="tgs-email-dashboard" style="max-width:1200px; margin:0 auto;">

    <!-- ════════════════ HEADER ════════════════ -->
    <div class="tgs-er-header">
        <div>
            <h2 class="tgs-er-title">Email Báo Cáo Tự Động</h2>
            <p class="tgs-er-subtitle">Gửi báo cáo shop, kho và trạng thái backup DB tự động cho leaders & sếp.</p>
        </div>
        <div class="tgs-er-header-actions">
            <a href="<?php echo admin_url('admin.php?page=tgs-shop-management&view=email-report-settings'); ?>"
               class="tgs-er-btn tgs-er-btn-outline">Cài đặt SMTP</a>
            <a href="<?php echo admin_url('admin.php?page=tgs-shop-management&view=email-report-log'); ?>"
               class="tgs-er-btn tgs-er-btn-outline">Lịch sử gửi</a>
        </div>
    </div>

    <!-- ════════════════ GỬI BÁO CÁO ════════════════ -->
    <div class="tgs-er-card">
        <h3 class="tgs-er-card-title">Gửi Báo Cáo</h3>

        <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
            <div>
                <label class="tgs-er-label">Từ ngày</label>
                <input type="date" id="tgs-email-date-from" value="<?php echo esc_attr($today); ?>" class="tgs-er-input">
            </div>
            <div>
                <label class="tgs-er-label">Đến ngày</label>
                <input type="date" id="tgs-email-date-to" value="<?php echo esc_attr($today); ?>" class="tgs-er-input">
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button id="btn-send-shop" class="tgs-er-btn tgs-er-btn-primary">Gửi Báo Cáo Shop</button>
                <button id="btn-send-warehouse" class="tgs-er-btn tgs-er-btn-teal">Gửi Báo Cáo Kho</button>
                <button id="btn-send-backup" class="tgs-er-btn tgs-er-btn-outline">Gửi Báo Cáo Backup</button>
                <button id="btn-send-all" class="tgs-er-btn tgs-er-btn-success">Gửi Tất Cả</button>
            </div>
        </div>

        <div id="tgs-email-result" style="margin-top:12px; display:none;"></div>
    </div>

    <!-- ════════════════ XEM TRƯỚC ════════════════ -->
    <div class="tgs-er-card">
        <h3 class="tgs-er-card-title">Xem Trước Nội Dung Email</h3>

        <div style="display:flex; gap:8px; margin-bottom:12px; align-items:center;">
            <select id="tgs-email-preview-type" class="tgs-er-input" style="width:auto;">
                <option value="shop_report">Báo cáo Shop</option>
                <option value="warehouse_report">Báo cáo Kho</option>
                <option value="backup_report">Báo cáo Backup DB</option>
            </select>
            <button id="btn-preview" class="tgs-er-btn tgs-er-btn-outline">Xem Trước</button>
        </div>

        <div id="tgs-email-preview-frame" style="display:none; border:1px solid #dee2e6; border-radius:6px; overflow:hidden; background:#f4f6f9;">
            <iframe id="tgs-email-preview-iframe" style="width:100%; min-height:600px; border:none;"></iframe>
        </div>
    </div>

    <!-- ════════════════ TRIGGER LINKS ════════════════ -->
    <div class="tgs-er-card">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h3 class="tgs-er-card-title" style="margin:0; border:none; padding:0;">Cấu hình gửi tự động</h3>
            <button id="btn-toggle-trigger" class="tgs-er-btn tgs-er-btn-sm tgs-er-btn-outline" onclick="$('#tgs-trigger-content').slideToggle(200); var t=$(this); t.text(t.text()==='Hiển thị' ? 'Ẩn đi' : 'Hiển thị');">Hiển thị</button>
        </div>

        <div id="tgs-trigger-content" style="display:none; margin-top:16px;">
            <p style="font-size:12px; color:#888; margin:0 0 12px;">Dùng các link này để gửi email tự động qua cron job hoặc ấn vào để gửi ngay.</p>

            <div style="display:flex; flex-direction:column; gap:10px;">
                <div class="tgs-er-trigger-row">
                    <span class="tgs-er-trigger-badge tgs-er-trigger-badge--all">Tất cả</span>
                    <code class="tgs-er-trigger-url"><?php echo esc_html($trigger_url_all); ?></code>
                    <button class="tgs-er-btn tgs-er-btn-sm btn-copy-link" data-url="<?php echo esc_attr($trigger_url_all); ?>">Copy</button>
                    <a href="<?php echo esc_url($trigger_url_all); ?>" target="_blank" class="tgs-er-btn tgs-er-btn-sm tgs-er-btn-outline">Mở</a>
                </div>
                <div class="tgs-er-trigger-row">
                    <span class="tgs-er-trigger-badge tgs-er-trigger-badge--shop">Shop</span>
                    <code class="tgs-er-trigger-url"><?php echo esc_html($trigger_url_shop); ?></code>
                    <button class="tgs-er-btn tgs-er-btn-sm btn-copy-link" data-url="<?php echo esc_attr($trigger_url_shop); ?>">Copy</button>
                </div>
                <div class="tgs-er-trigger-row">
                    <span class="tgs-er-trigger-badge tgs-er-trigger-badge--wh">Kho</span>
                    <code class="tgs-er-trigger-url"><?php echo esc_html($trigger_url_wh); ?></code>
                    <button class="tgs-er-btn tgs-er-btn-sm btn-copy-link" data-url="<?php echo esc_attr($trigger_url_wh); ?>">Copy</button>
                </div>
                <div class="tgs-er-trigger-row">
                    <span class="tgs-er-trigger-badge tgs-er-trigger-badge--all">Backup</span>
                    <code class="tgs-er-trigger-url"><?php echo esc_html($trigger_url_backup); ?></code>
                    <button class="tgs-er-btn tgs-er-btn-sm btn-copy-link" data-url="<?php echo esc_attr($trigger_url_backup); ?>">Copy</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════ NGƯỜI NHẬN ════════════════ -->
    <div class="tgs-er-card">
        <div class="tgs-er-card-header">
            <h3 class="tgs-er-card-title" style="margin:0; border:none; padding:0;">Danh Sách Người Nhận</h3>
            <div class="tgs-er-date-badge" id="tgs-rcpt-date-info">
                Ngày báo cáo: <strong id="tgs-rcpt-date-label"><?php echo esc_html($today_vn); ?></strong>
            </div>
        </div>

        <!-- Form thêm -->
        <div class="tgs-er-add-form">
            <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                <div style="flex:1; min-width:200px;">
                    <label class="tgs-er-label">Email <span style="color:#dc3545;">*</span></label>
                    <input type="email" id="rcpt-email" placeholder="email@domain.com" class="tgs-er-input" style="width:100%;">
                </div>
                <div style="min-width:140px;">
                    <label class="tgs-er-label">Tên hiển thị</label>
                    <input type="text" id="rcpt-name" placeholder="Nguyễn Văn A" class="tgs-er-input" style="width:100%;">
                </div>
                <div style="min-width:110px;">
                    <label class="tgs-er-label">Vai trò</label>
                    <input type="text" id="rcpt-role" placeholder="Leader KD" class="tgs-er-input" style="width:100%;">
                </div>
                <div style="min-width:220px;">
                    <label class="tgs-er-label">Nhận báo cáo</label>
                    <div style="display:flex; gap:12px; padding-top:4px;">
                        <span id="rcpt-type-shop" class="tgs-er-toggle active" data-value="shop_report">
                            <i class="tgs-er-tick"></i> Báo cáo Shop
                        </span>
                        <span id="rcpt-type-wh" class="tgs-er-toggle active" data-value="warehouse_report">
                            <i class="tgs-er-tick"></i> Báo cáo Kho
                        </span>
                        <span id="rcpt-type-backup" class="tgs-er-toggle" data-value="backup_report">
                            <i class="tgs-er-tick"></i> Backup DB
                        </span>
                    </div>
                </div>
                <button id="btn-add-recipient" class="tgs-er-btn tgs-er-btn-primary" style="height:36px;">+ Thêm</button>
            </div>
        </div>

        <!-- Table -->
        <div id="tgs-recipients-list" style="overflow-x:auto;">
            <p style="color:#888; font-size:13px;">Đang tải...</p>
        </div>
    </div>

    <!-- ════════════════ LỊCH SỬ GẦN ĐÂY ════════════════ -->
    <div class="tgs-er-card">
        <div class="tgs-er-card-header">
            <h3 class="tgs-er-card-title" style="margin:0; border:none; padding:0;">Lịch Sử Gửi Gần Đây</h3>
            <a href="<?php echo admin_url('admin.php?page=tgs-shop-management&view=email-report-log'); ?>"
               class="tgs-er-link">Xem tất cả →</a>
        </div>
        <div id="tgs-email-recent-logs">
            <p style="color:#888; font-size:13px;">Đang tải...</p>
        </div>
    </div>

</div>

<!-- Modal xem email đã gửi -->
<div id="tgs-email-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:100000; justify-content:center; align-items:center;">
    <div style="background:#fff; border-radius:8px; width:95%; max-width:900px; max-height:90vh; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 20px; background:#1e3a5f; color:#fff;">
            <span id="tgs-email-modal-title" style="font-weight:600;">Xem nội dung email</span>
            <button id="tgs-email-modal-close" style="background:none; border:none; color:#fff; font-size:20px; cursor:pointer; padding:0 4px;">✕</button>
        </div>
        <div style="overflow:auto; max-height:calc(90vh - 60px);">
            <iframe id="tgs-email-modal-iframe" style="width:100%; min-height:600px; border:none;"></iframe>
        </div>
    </div>
</div>
