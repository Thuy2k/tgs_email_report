<?php
/**
 * Admin View: Email Settings — Cấu hình SMTP & chế độ gửi
 */
if (!defined('ABSPATH')) exit;

$settings = TGS_Email_Settings::get_for_display();

// Lấy danh sách tất cả shops đang active
global $wpdb;
$all_blogs = $wpdb->get_results(
    "SELECT blog_id, domain, path FROM {$wpdb->blogs}
     WHERE archived = 0 AND deleted = 0 AND spam = 0
     ORDER BY blog_id ASC"
) ?: [];

// Tên shop (dùng get_blog_details nếu có)
$blog_names = [];
foreach ($all_blogs as $b) {
    $details = get_blog_details($b->blog_id);
    $blog_names[$b->blog_id] = $details ? $details->blogname : 'Blog #' . $b->blog_id;
}

$shop_filter_blogs = (array) ($settings['shop_report_include_blogs'] ?? []);
$wh_filter_blogs   = (array) ($settings['warehouse_report_include_blogs'] ?? []);
$einv_filter_blogs = (array) ($settings['einvoice_report_include_blogs'] ?? []);
?>

<div class="tgs-email-dashboard" style="max-width:800px; margin:0 auto;">

    <!-- Header -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <div>
            <h2 style="margin:0; font-size:22px; color:#1e3a5f;">⚙️ Cài Đặt Email SMTP</h2>
            <p style="margin:4px 0 0; color:#6c757d; font-size:13px;">Cấu hình cách gửi email — SMTP, PHP mail, hoặc Dev mode (ghi file).</p>
        </div>
        <a href="<?php echo admin_url('admin.php?page=tgs-shop-management&view=email-report-dashboard'); ?>"
           class="button" style="background:#f0f4f8; border-color:#dee2e6; color:#1e3a5f;">
            ← Quay lại Dashboard
        </a>
    </div>

    <!-- ════════════════════════ CHẾ ĐỘ GỬI ════════════════════════ -->
    <div class="tgs-email-card" style="background:#fff; border-radius:8px; padding:20px 24px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:20px;">
        <h3 style="margin:0 0 16px; font-size:16px; color:#1e3a5f; border-bottom:1px solid #eee; padding-bottom:8px;">📡 Chế Độ Gửi Email</h3>

        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <!-- PHP Mail -->
            <label class="tgs-mode-card <?php echo $settings['mode'] === 'php' ? 'active' : ''; ?>"
                   style="flex:1; min-width:180px; cursor:pointer; padding:16px; border:2px solid <?php echo $settings['mode'] === 'php' ? '#2d5f8a' : '#dee2e6'; ?>; border-radius:8px; background:<?php echo $settings['mode'] === 'php' ? '#f0f7ff' : '#fff'; ?>;">
                <input type="radio" name="email_mode" value="php" <?php echo $settings['mode'] === 'php' ? 'checked' : ''; ?> style="margin-right:8px;">
                <strong style="color:#1e3a5f;">📧 PHP Mail</strong>
                <p style="margin:8px 0 0; font-size:12px; color:#6c757d;">Dùng mail server mặc định của hosting. Đơn giản, không cần cấu hình thêm.</p>
            </label>

            <!-- SMTP -->
            <label class="tgs-mode-card <?php echo $settings['mode'] === 'smtp' ? 'active' : ''; ?>"
                   style="flex:1; min-width:180px; cursor:pointer; padding:16px; border:2px solid <?php echo $settings['mode'] === 'smtp' ? '#2d5f8a' : '#dee2e6'; ?>; border-radius:8px; background:<?php echo $settings['mode'] === 'smtp' ? '#f0f7ff' : '#fff'; ?>;">
                <input type="radio" name="email_mode" value="smtp" <?php echo $settings['mode'] === 'smtp' ? 'checked' : ''; ?> style="margin-right:8px;">
                <strong style="color:#1e3a5f;">🔐 SMTP</strong>
                <p style="margin:8px 0 0; font-size:12px; color:#6c757d;">Gmail, Outlook, hoặc SMTP server riêng. Ổn định & đáng tin cậy nhất.</p>
            </label>

            <!-- Dev Mode -->
            <label class="tgs-mode-card <?php echo $settings['mode'] === 'dev' ? 'active' : ''; ?>"
                   style="flex:1; min-width:180px; cursor:pointer; padding:16px; border:2px solid <?php echo $settings['mode'] === 'dev' ? '#28a745' : '#dee2e6'; ?>; border-radius:8px; background:<?php echo $settings['mode'] === 'dev' ? '#f0fff4' : '#fff'; ?>;">
                <input type="radio" name="email_mode" value="dev" <?php echo $settings['mode'] === 'dev' ? 'checked' : ''; ?> style="margin-right:8px;">
                <strong style="color:#28a745;">🧪 Dev Mode</strong>
                <p style="margin:8px 0 0; font-size:12px; color:#6c757d;">Không gửi thật — lưu email thành file HTML để xem. Dùng khi dev local.</p>
            </label>

            <!-- Resend API -->
            <label class="tgs-mode-card <?php echo $settings['mode'] === 'resend_api' ? 'active' : ''; ?>"
                   style="flex:1; min-width:180px; cursor:pointer; padding:16px; border:2px solid <?php echo $settings['mode'] === 'resend_api' ? '#e91e63' : '#dee2e6'; ?>; border-radius:8px; background:<?php echo $settings['mode'] === 'resend_api' ? '#fce4ec' : '#fff'; ?>;">
                <input type="radio" name="email_mode" value="resend_api" <?php echo $settings['mode'] === 'resend_api' ? 'checked' : ''; ?> style="margin-right:8px;">
                <strong style="color:#e91e63;">🚀 Resend API</strong>
                <p style="margin:8px 0 0; font-size:12px; color:#6c757d;">Gửi qua HTTPS — không cần mở port SMTP. Ổn định nhất cho mọi hosting.</p>
            </label>

            <!-- Fluent SMTP -->
            <label class="tgs-mode-card <?php echo $settings['mode'] === 'fluent_smtp' ? 'active' : ''; ?>"
                   style="flex:1; min-width:180px; cursor:pointer; padding:16px; border:2px solid <?php echo $settings['mode'] === 'fluent_smtp' ? '#7c3aed' : '#dee2e6'; ?>; border-radius:8px; background:<?php echo $settings['mode'] === 'fluent_smtp' ? '#f5f3ff' : '#fff'; ?>;">
                <input type="radio" name="email_mode" value="fluent_smtp" <?php echo $settings['mode'] === 'fluent_smtp' ? 'checked' : ''; ?> style="margin-right:8px;">
                <strong style="color:#7c3aed;">⚡ Fluent SMTP</strong>
                <p style="margin:8px 0 0; font-size:12px; color:#6c757d;">TGS không tự gửi nữa, nhường toàn bộ gửi mail cho Fluent SMTP.</p>
            </label>
        </div>
    </div>

    <!-- ════════════════════════ FLUENT SMTP INFO ════════════════════════ -->
    <div id="tgs-fluent-config" class="tgs-email-card" style="background:#fff; border-radius:8px; padding:20px 24px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:20px; <?php echo $settings['mode'] !== 'fluent_smtp' ? 'display:none;' : ''; ?>">
        <h3 style="margin:0 0 16px; font-size:16px; color:#7c3aed; border-bottom:1px solid #eee; padding-bottom:8px;">⚡ Fluent SMTP Mode</h3>
        <div style="background:#f5f3ff; border-radius:6px; padding:12px 16px; margin-bottom:16px; font-size:13px; color:#5b21b6; line-height:1.7;">
            Khi chọn chế độ này, TGS Email Report chỉ gọi <code>wp_mail()</code> và không tự override SMTP/API.
        </div>
        <div style="font-size:13px; color:#4b5563; margin-bottom:10px;">
            Trạng thái Fluent SMTP:
            <?php if (TGS_Email_Settings::is_fluent_smtp_active()): ?>
                <strong style="color:#059669;">Đã phát hiện</strong>
            <?php else: ?>
                <strong style="color:#dc2626;">Chưa phát hiện</strong>
            <?php endif; ?>
        </div>
        <a href="<?php echo esc_url(network_admin_url('options-general.php?page=fluent-mail')); ?>"
           class="button" style="border-color:#7c3aed; color:#7c3aed; background:#fff;">
            Mở cài đặt Fluent SMTP
        </a>
    </div>

    <!-- ════════════════════════ RESEND API CONFIG ════════════════════════ -->
    <div id="tgs-resend-config" class="tgs-email-card" style="background:#fff; border-radius:8px; padding:20px 24px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:20px; <?php echo $settings['mode'] !== 'resend_api' ? 'display:none;' : ''; ?>">
        <h3 style="margin:0 0 16px; font-size:16px; color:#e91e63; border-bottom:1px solid #eee; padding-bottom:8px;">🚀 Cấu Hình Resend API</h3>

        <div style="background:#fce4ec; border-radius:6px; padding:12px 16px; margin-bottom:16px; font-size:13px; color:#880e4f;">
            <strong>Hướng dẫn nhanh:</strong> Vào <a href="https://resend.com/api-keys" target="_blank" style="color:#e91e63; font-weight:600;">resend.com/api-keys</a> → Add API Key → Copy key (<code>re_...</code>) → dán vào ô bên dưới.
        </div>

        <div style="max-width:500px;">
            <label style="display:block; font-size:12px; color:#6c757d; margin-bottom:4px; font-weight:600;">API Key *</label>
            <input type="password" id="resend_api_key" value="<?php echo esc_attr($settings['resend_api_key']); ?>"
                   placeholder="re_xxxxxxxxxx..."
                   style="width:100%; padding:10px 12px; border:1px solid #e91e63; border-radius:4px; font-family:monospace; font-size:14px;">
            <p style="margin:6px 0 0; font-size:11px; color:#888;">Miễn phí 3,000 email/tháng. Key chỉ hiện 1 lần trên Resend — hãy copy trước khi đóng.</p>
        </div>
    </div>

    <!-- ════════════════════════ SMTP CONFIG ════════════════════════ -->
    <div id="tgs-smtp-config" class="tgs-email-card" style="background:#fff; border-radius:8px; padding:20px 24px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:20px; <?php echo $settings['mode'] !== 'smtp' ? 'display:none;' : ''; ?>">
        <h3 style="margin:0 0 16px; font-size:16px; color:#1e3a5f; border-bottom:1px solid #eee; padding-bottom:8px;">🔐 Cấu Hình SMTP</h3>

        <!-- Cảnh báo cPanel -->
        <div id="tgs-cpanel-warning" style="display:none; background:#fff3cd; border:1px solid #ffc107; border-radius:6px; padding:12px 16px; margin-bottom:16px; font-size:13px; color:#856404;">
            <strong>⚠️ Hosting cPanel:</strong> Nhiều cPanel chặn kết nối SMTP ra ngoài (Gmail port 587). Nếu bị lỗi "Peer certificate CN did not match":
            <ul style="margin:8px 0 0; padding-left:20px;">
                <li><strong>Cách 1:</strong> Thử <strong>Gmail (SSL/465)</strong> — port 465 thường không bị chặn</li>
                <li><strong>Cách 2:</strong> Dùng <strong>cPanel Mail</strong> — gửi qua mail server nội bộ (localhost)</li>
                <li><strong>Cách 3:</strong> Chuyển sang <strong>PHP Mail</strong> — đơn giản nhất, dùng Exim sẵn có</li>
            </ul>
        </div>

        <!-- Preset nhanh -->
        <div style="margin-bottom:16px;">
            <label style="display:block; font-size:12px; color:#6c757d; margin-bottom:4px; font-weight:600;">⚡ Cài nhanh</label>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button type="button" class="button btn-smtp-preset" data-host="smtp.gmail.com" data-port="587" data-secure="tls" data-auth="1" style="font-size:12px;">Gmail (TLS/587)</button>
                <button type="button" class="button btn-smtp-preset" data-host="smtp.gmail.com" data-port="465" data-secure="ssl" data-auth="1" style="font-size:12px; background:#fff3cd; border-color:#ffc107;">Gmail (SSL/465)</button>
                <button type="button" class="button btn-smtp-preset" data-host="smtp-mail.outlook.com" data-port="587" data-secure="tls" data-auth="1" style="font-size:12px;">Outlook</button>
                <button type="button" class="button btn-smtp-preset" data-host="localhost" data-port="25" data-secure="" data-auth="0" style="font-size:12px; background:#e8f5e9; border-color:#4caf50;">cPanel Mail</button>
                <button type="button" class="button btn-smtp-preset" data-host="smtp-relay.brevo.com" data-port="2525" data-secure="tls" data-auth="1" style="font-size:12px; background:#e3f2fd; border-color:#2196f3;">Brevo</button>
                <button type="button" class="button btn-smtp-preset" data-host="smtp.sendgrid.net" data-port="2525" data-secure="tls" data-auth="1" data-user="apikey" style="font-size:12px; background:#e8eaf6; border-color:#3f51b5;">SendGrid</button>
                <button type="button" class="button btn-smtp-preset" data-host="smtp.resend.com" data-port="2525" data-secure="tls" data-auth="1" data-user="resend" data-from-email="onboarding@resend.dev" data-from-name="TGS System" style="font-size:12px; background:#fce4ec; border-color:#e91e63;">Resend</button>
                <button type="button" class="button btn-smtp-preset" data-host="" data-port="587" data-secure="tls" data-auth="1" style="font-size:12px;">Tùy chỉnh</button>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <!-- Host -->
            <div>
                <label style="display:block; font-size:12px; color:#6c757d; margin-bottom:4px; font-weight:600;">SMTP Host *</label>
                <input type="text" id="smtp_host" value="<?php echo esc_attr($settings['smtp_host']); ?>"
                       placeholder="smtp.gmail.com"
                       style="width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:4px;">
            </div>
            <!-- Port -->
            <div>
                <label style="display:block; font-size:12px; color:#6c757d; margin-bottom:4px; font-weight:600;">Port *</label>
                <input type="number" id="smtp_port" value="<?php echo esc_attr($settings['smtp_port']); ?>"
                       placeholder="587"
                       style="width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:4px;">
            </div>
            <!-- Encryption -->
            <div>
                <label style="display:block; font-size:12px; color:#6c757d; margin-bottom:4px; font-weight:600;">Mã hóa</label>
                <select id="smtp_secure" style="width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:4px;">
                    <option value="tls" <?php echo $settings['smtp_secure'] === 'tls' ? 'selected' : ''; ?>>TLS (khuyên dùng)</option>
                    <option value="ssl" <?php echo $settings['smtp_secure'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                    <option value="" <?php echo $settings['smtp_secure'] === '' ? 'selected' : ''; ?>>Không mã hóa</option>
                </select>
            </div>
            <!-- Auth -->
            <div>
                <label style="display:block; font-size:12px; color:#6c757d; margin-bottom:4px; font-weight:600;">Xác thực</label>
                <select id="smtp_auth" style="width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:4px;">
                    <option value="1" <?php echo $settings['smtp_auth'] ? 'selected' : ''; ?>>Có (Username + Password)</option>
                    <option value="0" <?php echo !$settings['smtp_auth'] ? 'selected' : ''; ?>>Không</option>
                </select>
            </div>
            <!-- Username -->
            <div>
                <label style="display:block; font-size:12px; color:#6c757d; margin-bottom:4px; font-weight:600;">Username (Email)</label>
                <input type="text" id="smtp_user" value="<?php echo esc_attr($settings['smtp_user']); ?>"
                       placeholder="your-email@gmail.com"
                       style="width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:4px;">
            </div>
            <!-- Password -->
            <div>
                <label style="display:block; font-size:12px; color:#6c757d; margin-bottom:4px; font-weight:600;">Password / App Password</label>
                <input type="password" id="smtp_pass" value="<?php echo esc_attr($settings['smtp_pass']); ?>"
                       placeholder="App password hoặc mật khẩu"
                       style="width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:4px;">
                <p style="margin:4px 0 0; font-size:11px; color:#888;">Gmail: Dùng <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color:#2d5f8a;">App Password</a> (bật 2FA trước)</p>
            </div>
        </div>

        <!-- SSL Verify option -->
        <div style="margin-top:16px; padding:12px 16px; background:#fff8e1; border:1px solid #ffe082; border-radius:6px;">
            <label style="cursor:pointer; display:flex; align-items:center; gap:8px;">
                <input type="checkbox" id="smtp_no_verify_ssl" value="1" <?php echo !empty($settings['smtp_no_verify_ssl']) ? 'checked' : ''; ?>>
                <span style="font-size:13px; color:#856404;">
                    <strong>Tắt kiểm tra SSL certificate</strong> — Bật nếu hosting cPanel bị lỗi "Peer certificate CN did not match"
                </span>
            </label>
        </div>
    </div>

    <!-- ════════════════════════ FROM INFO ════════════════════════ -->
    <div class="tgs-email-card" style="background:#fff; border-radius:8px; padding:20px 24px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:20px;">
        <h3 style="margin:0 0 16px; font-size:16px; color:#1e3a5f; border-bottom:1px solid #eee; padding-bottom:8px;">✉️ Thông Tin Người Gửi</h3>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div>
                <label style="display:block; font-size:12px; color:#6c757d; margin-bottom:4px; font-weight:600;">From Email</label>
                <input type="email" id="from_email" value="<?php echo esc_attr($settings['from_email']); ?>"
                       placeholder="noreply@yourdomain.com"
                       style="width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:4px;">
                <p style="margin:4px 0 0; font-size:11px; color:#888;">Để trống = dùng email admin WordPress</p>
            </div>
            <div>
                <label style="display:block; font-size:12px; color:#6c757d; margin-bottom:4px; font-weight:600;">From Name</label>
                <input type="text" id="from_name" value="<?php echo esc_attr($settings['from_name']); ?>"
                       placeholder="TGS System"
                       style="width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:4px;">
            </div>
        </div>
    </div>

    <!-- ════════════════════════ LỌC SHOP ════════════════════════ -->
    <div class="tgs-email-card" style="background:#fff; border-radius:8px; padding:20px 24px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:20px;">
        <h3 style="margin:0 0 16px; font-size:16px; color:#1e3a5f;">🏪 Lọc Shop Thống Kê</h3>
        <p style="margin:0 0 16px; font-size:13px; color:#6c757d;">Chọn shop muốn đưa vào thống kê. Nếu không chọn = lấy <strong>tất cả</strong> shop.</p>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
            <!-- Shop Report -->
            <div>
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                    <strong style="font-size:13px; color:#2d5f8a;">📊 Báo cáo Shop (Doanh thu)</strong>
                    <button type="button" class="btn-toggle-all" data-group="shop" style="font-size:11px; padding:3px 8px; border:1px solid #2d5f8a; border-radius:4px; background:#f0f7ff; color:#2d5f8a; cursor:pointer;">Chọn tất cả</button>
                </div>
                <div style="display:flex; flex-direction:column; gap:6px; max-height:260px; overflow-y:auto; padding:8px; border:1px solid #e9ecef; border-radius:6px; background:#f8f9fa;">
                    <?php foreach ($all_blogs as $b): ?>
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer; padding:4px 6px; border-radius:4px; transition:background .15s;" onmouseover="this.style.background='#e9f4ff'" onmouseout="this.style.background='transparent'">
                        <input type="checkbox" class="shop-blog-cb" value="<?php echo (int)$b->blog_id; ?>"
                               <?php checked(in_array((int)$b->blog_id, $shop_filter_blogs, true)); ?>
                               style="width:16px; height:16px; appearance:checkbox !important; -webkit-appearance:checkbox !important; accent-color:#2d5f8a; cursor:pointer; flex-shrink:0;">
                        <span><?php echo esc_html($blog_names[$b->blog_id]); ?></span>
                        <span style="margin-left:auto; font-size:11px; color:#999;">#<?php echo (int)$b->blog_id; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Warehouse Report -->
            <div>
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                    <strong style="font-size:13px; color:#5a2d82;">📦 Báo cáo Kho (Min/Max & Tồn)</strong>
                    <button type="button" class="btn-toggle-all" data-group="wh" style="font-size:11px; padding:3px 8px; border:1px solid #5a2d82; border-radius:4px; background:#f8f0ff; color:#5a2d82; cursor:pointer;">Chọn tất cả</button>
                </div>
                <div style="display:flex; flex-direction:column; gap:6px; max-height:260px; overflow-y:auto; padding:8px; border:1px solid #e9ecef; border-radius:6px; background:#f8f9fa;">
                    <?php foreach ($all_blogs as $b): ?>
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer; padding:4px 6px; border-radius:4px; transition:background .15s;" onmouseover="this.style.background='#f3e8ff'" onmouseout="this.style.background='transparent'">
                        <input type="checkbox" class="wh-blog-cb" value="<?php echo (int)$b->blog_id; ?>"
                               <?php checked(in_array((int)$b->blog_id, $wh_filter_blogs, true)); ?>
                               style="width:16px; height:16px; appearance:checkbox !important; -webkit-appearance:checkbox !important; accent-color:#5a2d82; cursor:pointer; flex-shrink:0;">
                        <span><?php echo esc_html($blog_names[$b->blog_id]); ?></span>
                        <span style="margin-left:auto; font-size:11px; color:#999;">#<?php echo (int)$b->blog_id; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- E-Invoice Report -->
            <div>
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                    <strong style="font-size:13px; color:#0f766e;">🧾 Báo cáo HĐĐT</strong>
                    <button type="button" class="btn-toggle-all" data-group="einv" style="font-size:11px; padding:3px 8px; border:1px solid #0f766e; border-radius:4px; background:#ecfeff; color:#0f766e; cursor:pointer;">Chọn tất cả</button>
                </div>
                <div style="display:flex; flex-direction:column; gap:6px; max-height:260px; overflow-y:auto; padding:8px; border:1px solid #e9ecef; border-radius:6px; background:#f8f9fa;">
                    <?php foreach ($all_blogs as $b): ?>
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer; padding:4px 6px; border-radius:4px; transition:background .15s;" onmouseover="this.style.background='#ecfeff'" onmouseout="this.style.background='transparent'">
                        <input type="checkbox" class="einv-blog-cb" value="<?php echo (int)$b->blog_id; ?>"
                               <?php checked(in_array((int)$b->blog_id, $einv_filter_blogs, true)); ?>
                               style="width:16px; height:16px; appearance:checkbox !important; -webkit-appearance:checkbox !important; accent-color:#0f766e; cursor:pointer; flex-shrink:0;">
                        <span><?php echo esc_html($blog_names[$b->blog_id]); ?></span>
                        <span style="margin-left:auto; font-size:11px; color:#999;">#<?php echo (int)$b->blog_id; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════ BUTTONS ════════════════════════ -->
    <div class="tgs-email-card" style="background:#fff; border-radius:8px; padding:20px 24px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:20px;">
        <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
            <button id="btn-save-smtp" class="button button-primary" style="background:#2d5f8a; border-color:#1e3a5f; padding:8px 24px; font-weight:600; font-size:14px;">
                💾 Lưu Cài Đặt
            </button>

            <div style="flex:1;"></div>

            <!-- Test -->
            <input type="email" id="test_email_to" placeholder="test@email.com"
                   value="<?php echo esc_attr(get_option('admin_email')); ?>"
                   style="padding:8px 10px; border:1px solid #ccc; border-radius:4px; width:220px;">
            <button id="btn-test-smtp" class="button" style="background:#28a745; border-color:#218838; color:#fff; padding:8px 16px; font-weight:600;">
                🧪 Gửi Test
            </button>
        </div>

        <div id="tgs-smtp-result" style="margin-top:12px; display:none;"></div>
    </div>

    <!-- ════════════════════════ HƯỚNG DẪN ════════════════════════ -->
    <div class="tgs-email-card" style="background:#fff; border-radius:8px; padding:20px 24px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:20px;">
        <h3 style="margin:0 0 12px; font-size:15px; color:#1e3a5f; cursor:pointer;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none';">
            📖 Hướng Dẫn Cài Đặt <span style="font-size:12px; color:#888;">(nhấn để mở)</span>
        </h3>
        <div style="display:none; font-size:13px; color:#555; line-height:1.8;">
            <div style="background:#f8fafc; border-radius:6px; padding:16px; margin-bottom:12px;">
                <strong style="color:#1e3a5f;">🧪 Dev Mode (local XAMPP):</strong>
                <ol style="margin:8px 0 0; padding-left:20px;">
                    <li>Chọn <strong>Dev Mode</strong> → Lưu</li>
                    <li>Thử gửi email → file HTML được lưu tại <code>wp-content/uploads/tgs-email-logs/</code></li>
                    <li>Mở file HTML bằng trình duyệt để xem nội dung email</li>
                </ol>
            </div>

            <div style="background:#f0f7ff; border-radius:6px; padding:16px; margin-bottom:12px;">
                <strong style="color:#1e3a5f;">📧 Gmail SMTP:</strong>
                <ol style="margin:8px 0 0; padding-left:20px;">
                    <li>Bật <a href="https://myaccount.google.com/security" target="_blank" style="color:#2d5f8a;">Xác minh 2 bước</a></li>
                    <li>Tạo <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color:#2d5f8a;">App Password</a> (chọn "Mail" → "Windows Computer")</li>
                    <li>Nhấn <strong>Gmail</strong> preset → nhập email & app password → Lưu</li>
                </ol>
            </div>

            <div style="background:#f0fff4; border-radius:6px; padding:16px; margin-bottom:12px;">
                <strong style="color:#1e3a5f;">🏢 Server Production (cPanel):</strong>
                <ol style="margin:8px 0 0; padding-left:20px;">
                    <li>Cách đơn giản nhất: chọn <strong>PHP Mail</strong> — cPanel có Exim sẵn</li>
                    <li>Muốn gửi Gmail: thử <strong>Gmail (SSL/465)</strong> trước — nếu lỗi thì dùng <strong>cPanel Mail</strong></li>
                    <li>Nếu bị lỗi "Peer certificate CN did not match" → cPanel proxy chặn → dùng <strong>PHP Mail</strong> hoặc <strong>cPanel Mail (localhost:25)</strong></li>
                    <li>Luôn nhấn <strong>Gửi Test</strong> trước khi dùng thật</li>
                </ol>
            </div>

            <div style="background:#fce4ec; border-radius:6px; padding:16px; margin-bottom:12px;">
                <strong style="color:#880e4f;">📨 Resend (API Email — miễn phí 3,000 email/tháng):</strong>
                <ol style="margin:8px 0 0; padding-left:20px;">
                    <li>Đăng ký tại <a href="https://resend.com" target="_blank" style="color:#e91e63;">resend.com</a> → vào <strong>API Keys</strong> → <strong>Add API Key</strong> → đặt tên (vd: tgs-email) → <strong>Create</strong></li>
                    <li>Copy API Key (bắt đầu bằng <code>re_...</code>)</li>
                    <li>Nhấn preset <strong>Resend</strong> ở trên → dán API Key vào ô Password → Lưu</li>
                    <li>Ban đầu From Email phải dùng <code>onboarding@resend.dev</code> (email mặc định Resend cho phép)</li>
                    <li>Sau khi test OK → vào Resend → <strong>Domains</strong> → verify domain <code>quantri.thegioisua.com</code> → đổi From Email thành <code>report@quantri.thegioisua.com</code></li>
                </ol>
            </div>

            <div style="background:#fff3cd; border-radius:6px; padding:16px;">
                <strong style="color:#856404;">⚡ Giải quyết nhanh khi SMTP lỗi trên cPanel:</strong>
                <ol style="margin:8px 0 0; padding-left:20px;">
                    <li>Chuyển mode sang <strong>PHP Mail</strong> → Lưu → Test</li>
                    <li>Nếu PHP Mail vào spam → dùng SMTP preset <strong>cPanel Mail</strong> (localhost:25) → Lưu → Test</li>
                    <li>Nếu vẫn muốn Gmail → liên hệ hosting mở port SMTP outbound</li>
                </ol>
            </div>
        </div>
    </div>

</div>
