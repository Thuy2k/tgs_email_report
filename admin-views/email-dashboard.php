<?php
/**
 * Admin View: Email Report Dashboard
 *
 * Giao diện chính để:
 *   1. Gửi báo cáo Shop / Kho / Cả 2 (nút "Gửi Luôn")
 *   2. Chọn ngày / khoảng ngày
 *   3. Preview email trước khi gửi
 *   4. Quản lý danh sách recipients (CC)
 *   5. URL trigger links
 *   6. Xem lịch sử gửi
 */
if (!defined('ABSPATH')) exit;

$today = current_time('Y-m-d');
$trigger_url_all  = TGS_Email_Trigger::get_trigger_url('all');
$trigger_url_shop = TGS_Email_Trigger::get_trigger_url('shop');
$trigger_url_wh   = TGS_Email_Trigger::get_trigger_url('warehouse');
?>

<div class="tgs-email-dashboard" style="max-width:1200px; margin:0 auto;">

    <!-- ════════════════════════════════════════
         HEADER
         ════════════════════════════════════════ -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <div>
            <h2 style="margin:0; font-size:22px; color:#1e3a5f;">📧 Email Báo Cáo Tự Động</h2>
            <p style="margin:4px 0 0; color:#6c757d; font-size:13px;">Gửi báo cáo bán hàng, thu ngân hàng, MIN/MAX tồn kho cho leaders & sếp.</p>
        </div>
        <a href="<?php echo admin_url('admin.php?page=tgs-shop-management&view=email-report-settings'); ?>"
           class="button" style="background:#f0f4f8; border-color:#dee2e6; color:#1e3a5f;">
            ⚙️ Cài Đặt SMTP
        </a>
        <a href="<?php echo admin_url('admin.php?page=tgs-shop-management&view=email-report-log'); ?>"
           class="button" style="background:#f0f4f8; border-color:#dee2e6; color:#1e3a5f;">
            📋 Lịch Sử Gửi
        </a>
    </div>

    <!-- ════════════════════════════════════════
         CONTROLS: Ngày & Nút gửi
         ════════════════════════════════════════ -->
    <div class="tgs-email-card" style="background:#fff; border-radius:8px; padding:20px 24px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:20px;">
        <h3 style="margin:0 0 16px; font-size:16px; color:#1e3a5f; border-bottom:1px solid #eee; padding-bottom:8px;">⚙️ Gửi Báo Cáo</h3>

        <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end;">
            <!-- Date range -->
            <div>
                <label style="display:block; font-size:12px; color:#6c757d; margin-bottom:4px; font-weight:600;">Từ ngày</label>
                <input type="date" id="tgs-email-date-from" value="<?php echo esc_attr($today); ?>"
                       class="regular-text" style="padding:6px 10px; border:1px solid #ccc; border-radius:4px;">
            </div>
            <div>
                <label style="display:block; font-size:12px; color:#6c757d; margin-bottom:4px; font-weight:600;">Đến ngày</label>
                <input type="date" id="tgs-email-date-to" value="<?php echo esc_attr($today); ?>"
                       class="regular-text" style="padding:6px 10px; border:1px solid #ccc; border-radius:4px;">
            </div>

            <!-- Buttons -->
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button id="btn-send-shop" class="button button-primary" style="background:#2d5f8a; border-color:#1e3a5f; padding:6px 16px; font-weight:600;">
                    🛒 Gửi Báo Cáo Shop
                </button>
                <button id="btn-send-warehouse" class="button" style="background:#17a2b8; border-color:#138496; color:#fff; padding:6px 16px; font-weight:600;">
                    📦 Gửi Báo Cáo Kho
                </button>
                <button id="btn-send-all" class="button" style="background:#28a745; border-color:#218838; color:#fff; padding:6px 16px; font-weight:600;">
                    🚀 Gửi Tất Cả
                </button>
            </div>
        </div>

        <!-- Result message -->
        <div id="tgs-email-result" style="margin-top:12px; display:none;"></div>
    </div>

    <!-- ════════════════════════════════════════
         PREVIEW
         ════════════════════════════════════════ -->
    <div class="tgs-email-card" style="background:#fff; border-radius:8px; padding:20px 24px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:20px;">
        <h3 style="margin:0 0 16px; font-size:16px; color:#1e3a5f; border-bottom:1px solid #eee; padding-bottom:8px;">👁️ Preview Email</h3>

        <div style="display:flex; gap:8px; margin-bottom:12px;">
            <select id="tgs-email-preview-type" style="padding:6px 10px; border:1px solid #ccc; border-radius:4px;">
                <option value="shop_report">Báo cáo Shop</option>
                <option value="warehouse_report">Báo cáo Kho</option>
            </select>
            <button id="btn-preview" class="button" style="padding:6px 16px;">
                👁️ Xem Trước
            </button>
        </div>

        <div id="tgs-email-preview-frame" style="display:none; border:1px solid #dee2e6; border-radius:6px; overflow:hidden; background:#f4f6f9;">
            <iframe id="tgs-email-preview-iframe" style="width:100%; min-height:600px; border:none;"></iframe>
        </div>
    </div>

    <!-- ════════════════════════════════════════
         URL TRIGGER LINKS
         ════════════════════════════════════════ -->
    <div class="tgs-email-card" style="background:#fff; border-radius:8px; padding:20px 24px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:20px;">
        <h3 style="margin:0 0 16px; font-size:16px; color:#1e3a5f; border-bottom:1px solid #eee; padding-bottom:8px;">🔗 Trigger Links (Ấn vào = Gửi)</h3>
        <p style="font-size:12px; color:#888; margin-bottom:12px;">Dùng các link này để gửi email bằng cách ấn vào, hoặc đặt trong cron job server.</p>

        <div style="display:flex; flex-direction:column; gap:10px;">
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; background:#d1ecf1; color:#0c5460;">ALL</span>
                <code style="flex:1; padding:6px 10px; background:#f8f9fa; border:1px solid #dee2e6; border-radius:4px; font-size:12px; word-break:break-all;"><?php echo esc_html($trigger_url_all); ?></code>
                <button class="button btn-copy-link" data-url="<?php echo esc_attr($trigger_url_all); ?>" style="font-size:12px; padding:4px 10px;">📋 Copy</button>
                <a href="<?php echo esc_url($trigger_url_all); ?>" target="_blank" class="button" style="font-size:12px; padding:4px 10px;">↗ Mở</a>
            </div>
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; background:#cce5ff; color:#004085;">SHOP</span>
                <code style="flex:1; padding:6px 10px; background:#f8f9fa; border:1px solid #dee2e6; border-radius:4px; font-size:12px; word-break:break-all;"><?php echo esc_html($trigger_url_shop); ?></code>
                <button class="button btn-copy-link" data-url="<?php echo esc_attr($trigger_url_shop); ?>" style="font-size:12px; padding:4px 10px;">📋 Copy</button>
            </div>
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; background:#d4edda; color:#155724;">KHO</span>
                <code style="flex:1; padding:6px 10px; background:#f8f9fa; border:1px solid #dee2e6; border-radius:4px; font-size:12px; word-break:break-all;"><?php echo esc_html($trigger_url_wh); ?></code>
                <button class="button btn-copy-link" data-url="<?php echo esc_attr($trigger_url_wh); ?>" style="font-size:12px; padding:4px 10px;">📋 Copy</button>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════
         RECIPIENTS (CC)
         ════════════════════════════════════════ -->
    <div class="tgs-email-card" style="background:#fff; border-radius:8px; padding:20px 24px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:20px;">
        <h3 style="margin:0 0 16px; font-size:16px; color:#1e3a5f; border-bottom:1px solid #eee; padding-bottom:8px;">👥 Danh Sách Người Nhận</h3>

        <!-- Form thêm -->
        <div style="background:#f8fafc; border-radius:8px; padding:16px; margin-bottom:16px; border:1px dashed #dee2e6;">
            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
                <div style="flex:1; min-width:200px;">
                    <label style="display:block; font-size:11px; color:#6c757d; margin-bottom:2px; font-weight:600;">Email *</label>
                    <input type="email" id="rcpt-email" placeholder="email@domain.com"
                           style="padding:8px 10px; border:1px solid #ccc; border-radius:4px; width:100%; box-sizing:border-box;">
                </div>
                <div style="min-width:140px;">
                    <label style="display:block; font-size:11px; color:#6c757d; margin-bottom:2px; font-weight:600;">Tên hiển thị</label>
                    <input type="text" id="rcpt-name" placeholder="Nguyễn Văn A"
                           style="padding:8px 10px; border:1px solid #ccc; border-radius:4px; width:100%; box-sizing:border-box;">
                </div>
                <div style="min-width:110px;">
                    <label style="display:block; font-size:11px; color:#6c757d; margin-bottom:2px; font-weight:600;">Vai trò</label>
                    <input type="text" id="rcpt-role" placeholder="Leader KD"
                           style="padding:8px 10px; border:1px solid #ccc; border-radius:4px; width:100%; box-sizing:border-box;">
                </div>
                <div style="min-width:180px;">
                    <label style="display:block; font-size:11px; color:#6c757d; margin-bottom:4px; font-weight:600;">Nhận báo cáo</label>
                    <div style="display:flex; gap:6px;">
                        <button type="button" id="rcpt-type-shop" class="rcpt-type-btn active"
                                style="padding:6px 14px; border-radius:4px; font-size:13px; cursor:pointer; border:2px solid #2d5f8a; background:#e3f0ff; color:#1e3a5f; font-weight:600;"
                                data-value="shop_report">🛒 Shop</button>
                        <button type="button" id="rcpt-type-wh" class="rcpt-type-btn active"
                                style="padding:6px 14px; border-radius:4px; font-size:13px; cursor:pointer; border:2px solid #28a745; background:#e8f5e9; color:#1b5e20; font-weight:600;"
                                data-value="warehouse_report">📦 Kho</button>
                    </div>
                </div>
                <button id="btn-add-recipient" class="button button-primary" style="padding:8px 20px; height:36px; font-weight:600;">
                    + Thêm
                </button>
            </div>
        </div>

        <!-- Table -->
        <div id="tgs-recipients-list" style="overflow-x:auto;">
            <p style="color:#888; font-size:13px;">Đang tải...</p>
        </div>
    </div>

    <!-- ════════════════════════════════════════
         LỊCH SỬ GẦN ĐÂY
         ════════════════════════════════════════ -->
    <div class="tgs-email-card" style="background:#fff; border-radius:8px; padding:20px 24px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid #eee; padding-bottom:8px;">
            <h3 style="margin:0; font-size:16px; color:#1e3a5f;">📋 Lịch Sử Gửi Gần Đây</h3>
            <a href="<?php echo admin_url('admin.php?page=tgs-shop-management&view=email-report-log'); ?>"
               style="font-size:12px; color:#2d5f8a;">Xem tất cả →</a>
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
            <span id="tgs-email-modal-title" style="font-weight:600;">Email Preview</span>
            <button id="tgs-email-modal-close" style="background:none; border:none; color:#fff; font-size:20px; cursor:pointer; padding:0 4px;">✕</button>
        </div>
        <div style="overflow:auto; max-height:calc(90vh - 60px);">
            <iframe id="tgs-email-modal-iframe" style="width:100%; min-height:600px; border:none;"></iframe>
        </div>
    </div>
</div>
