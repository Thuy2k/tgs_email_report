<?php
/**
 * Template: Email Backup Report
 *
 * Biến có sẵn: $folders, $orphan_folders, $stats, $date_from, $date_to, $generated_at, $auto_dir_exists
 */
if (!defined('ABSPATH')) exit;

$folders = is_array($folders ?? null) ? $folders : [];
$orphan_folders = is_array($orphan_folders ?? null) ? $orphan_folders : [];
$stats = is_array($stats ?? null) ? $stats : [];
$attachment_meta = is_array($attachment_meta ?? null) ? $attachment_meta : [];

ob_start();
?>

<div style="font-family:'Inter', 'Segoe UI', Tahoma, sans-serif; color:#1c2d40;">
    <div style="margin-bottom:20px; background:linear-gradient(180deg, #ffffff 0%, #f7fafc 100%); border:1px solid #dbe6f1; border-radius:28px; padding:18px 18px 20px; box-shadow:0 18px 34px rgba(18, 43, 74, 0.08);">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td style="vertical-align:top; padding-right:8px;">
                    <div style="font-size:22px; line-height:1.25; font-weight:700; color:#13273e;">Báo cáo backup DB tự động</div>
                    <div style="margin-top:6px; font-size:13px; color:#58708a;">Mỗi folder lấy file auto backup mới nhất để đối chiếu nhanh tình trạng sao lưu cuối ngày.</div>
                </td>
                <td align="right" style="vertical-align:top; white-space:nowrap;">
                    <span style="display:inline-block; padding:7px 12px; border-radius:999px; background:#eff4f8; color:#35506c; font-size:11px; font-weight:700; letter-spacing:0.3px;">
                        Tạo lúc <?php echo esc_html($generated_at ?? current_time('d/m/Y H:i:s')); ?>
                    </span>
                </td>
            </tr>
        </table>

        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:16px;">
            <tr>
                <td width="25%" style="padding:0 6px 10px 0; vertical-align:top;">
                    <div style="background:#ffffff; border:1px solid #e5edf5; border-radius:18px; padding:12px 14px;">
                        <div style="font-size:11px; text-transform:uppercase; color:#7b8a9a; letter-spacing:0.8px;">Folder cần kiểm tra</div>
                        <div style="font-size:20px; font-weight:700; color:#13273e; margin-top:4px;"><?php echo number_format((int) ($stats['total_folders'] ?? 0)); ?></div>
                    </div>
                </td>
                <td width="25%" style="padding:0 6px 10px 6px; vertical-align:top;">
                    <div style="background:#edf9f0; border:1px solid #d7efdd; border-radius:18px; padding:12px 14px;">
                        <div style="font-size:11px; text-transform:uppercase; color:#6a7f75; letter-spacing:0.8px;">Có backup mới nhất</div>
                        <div style="font-size:20px; font-weight:700; color:#1f8f4d; margin-top:4px;"><?php echo number_format((int) ($stats['available_backups'] ?? 0)); ?></div>
                    </div>
                </td>
                <td width="25%" style="padding:0 6px 10px 6px; vertical-align:top;">
                    <div style="background:#fff3e9; border:1px solid #ffe0bf; border-radius:18px; padding:12px 14px;">
                        <div style="font-size:11px; text-transform:uppercase; color:#8b6a33; letter-spacing:0.8px;">Thiếu backup</div>
                        <div style="font-size:20px; font-weight:700; color:#d9822b; margin-top:4px;"><?php echo number_format((int) ($stats['missing_backups'] ?? 0)); ?></div>
                    </div>
                </td>
                <td width="25%" style="padding:0 0 10px 6px; vertical-align:top;">
                    <div style="background:#eef5ff; border:1px solid #d9e7fb; border-radius:18px; padding:12px 14px;">
                        <div style="font-size:11px; text-transform:uppercase; color:#61758d; letter-spacing:0.8px;">Tổng dung lượng latest</div>
                        <div style="font-size:20px; font-weight:700; color:#20466f; margin-top:4px;"><?php echo esc_html($stats['total_latest_size_human'] ?? '0 B'); ?></div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <?php if (empty($auto_dir_exists)): ?>
        <div class="alert alert-danger" style="margin-bottom:16px;">
            Không tìm thấy thư mục auto backup. Kiểm tra lại plugin backup hoặc đường dẫn lưu trữ tại `wp-content/tgs-backups/auto/`.
        </div>
    <?php endif; ?>

    <?php if (!empty($stats['missing_backups'])): ?>
        <div class="alert alert-warning" style="margin-bottom:16px;">
            Có <?php echo number_format((int) $stats['missing_backups']); ?> folder chưa tìm thấy file backup mới nhất. Nên kiểm tra lịch cron hoặc quyền ghi thư mục.
        </div>
    <?php endif; ?>

    <?php if (!empty($stats['orphan_folders'])): ?>
        <div class="alert alert-info" style="margin-bottom:16px;">
            Phát hiện <?php echo number_format((int) $stats['orphan_folders']); ?> folder blog đã bị xóa khỏi multisite nhưng vẫn còn backup trên disk.
        </div>
    <?php endif; ?>

    <div class="alert alert-success" style="margin-bottom:16px;">
        Đính kèm thành công <?php echo number_format((int) ($attachment_meta['attached_count'] ?? 0)); ?> file
        (<?php echo esc_html($attachment_meta['attached_size_human'] ?? '0 B'); ?>).
        <?php if (!empty($attachment_meta['skipped_count'])): ?>
            Bỏ qua <?php echo number_format((int) $attachment_meta['skipped_count']); ?> file do thiếu file hoặc vượt giới hạn gửi.
        <?php endif; ?>
        Giới hạn hiện tại: tối đa <?php echo number_format((int) ($attachment_meta['max_attachments'] ?? 0)); ?> file,
        tổng <?php echo esc_html($attachment_meta['max_total_human'] ?? '0 B'); ?>.
    </div>

    <div class="section">
        <div class="section-title"><span class="icon">🗂️</span>Latest backup theo folder</div>
        <table class="data-table" cellpadding="0" cellspacing="0" border="0">
            <thead>
                <tr>
                    <th style="width:4%;">#</th>
                    <th style="width:26%;">Folder</th>
                    <th style="width:33%;">File mới nhất</th>
                    <th style="width:17%;">Thời gian</th>
                    <th style="width:10%;" class="text-right">Dung lượng</th>
                    <th style="width:10%;" class="text-center">Trạng thái</th>
                    <th style="width:10%;" class="text-center">Đính kèm</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($folders as $index => $folder): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <div style="font-weight:600; color:#17324d;"><?php echo esc_html($folder['label'] ?? ''); ?></div>
                            <div style="font-size:11px; color:#8392a5; margin-top:2px;">
                                <?php echo esc_html($folder['folder'] ?? ''); ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($folder['has_file'])): ?>
                                <div style="font-family:Consolas, monospace; color:#c72573; background:#f7f7f9; display:inline-block; padding:4px 7px; border-radius:6px; font-size:12px; word-break:break-all;">
                                    <?php echo esc_html($folder['filename'] ?? ''); ?>
                                </div>
                            <?php else: ?>
                                <span style="color:#8b6a33;">Chưa có file backup</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo !empty($folder['time_human']) ? esc_html($folder['time_human']) : '—'; ?></td>
                        <td class="text-right"><?php echo !empty($folder['has_file']) ? esc_html($folder['size_human'] ?? '0 B') : '—'; ?></td>
                        <td class="text-center">
                            <?php if (!empty($folder['has_file'])): ?>
                                <span class="badge badge-success">OK</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Thiếu</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if (!empty($folder['attached'])): ?>
                                <span class="badge badge-success">Đã đính kèm</span>
                            <?php elseif (!empty($folder['has_file'])): ?>
                                <span class="badge badge-warning">Bỏ qua</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Không có file</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($orphan_folders)): ?>
        <div class="section">
            <div class="section-title"><span class="icon">🧹</span>Folder blog đã xóa còn tồn tại</div>
            <table class="data-table" cellpadding="0" cellspacing="0" border="0">
                <thead>
                    <tr>
                        <th style="width:8%;">Blog ID</th>
                        <th style="width:28%;">Folder</th>
                        <th style="width:34%;">File mới nhất</th>
                        <th style="width:18%;">Thời gian</th>
                        <th style="width:12%;" class="text-right">Dung lượng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orphan_folders as $folder): ?>
                        <tr>
                            <td><?php echo (int) ($folder['blog_id'] ?? 0); ?></td>
                            <td>
                                <div style="font-weight:600; color:#17324d;"><?php echo esc_html($folder['label'] ?? ''); ?></div>
                                <div style="font-size:11px; color:#8392a5; margin-top:2px;">
                                    <?php echo esc_html($folder['folder'] ?? ''); ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($folder['has_file'])): ?>
                                    <div style="font-family:Consolas, monospace; color:#c72573; background:#f7f7f9; display:inline-block; padding:4px 7px; border-radius:6px; font-size:12px; word-break:break-all;">
                                        <?php echo esc_html($folder['filename'] ?? ''); ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#8b6a33;">Chưa có file backup</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo !empty($folder['time_human']) ? esc_html($folder['time_human']) : '—'; ?></td>
                            <td class="text-right"><?php echo !empty($folder['has_file']) ? esc_html($folder['size_human'] ?? '0 B') : '—'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$body_content = ob_get_clean();
$subject = 'Báo cáo Backup DB tự động';

include __DIR__ . '/email-master-layout.php';