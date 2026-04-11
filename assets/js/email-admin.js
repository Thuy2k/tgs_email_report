/**
 * TGS Email Report — Admin JS
 *
 * Handles all AJAX interactions for:
 *   - Sending emails (shop, warehouse, all)
 *   - Preview
 *   - Recipients CRUD
 *   - Log viewing
 *   - URL copy
 */
(function ($) {
    'use strict';

    const API = tgsEmailReport.ajaxUrl;
    const NONCE = tgsEmailReport.nonce;

    /* ────────────────────────────────────────
     * Helpers
     * ──────────────────────────────────────── */
    function getDates() {
        return {
            date_from: $('#tgs-email-date-from').val(),
            date_to: $('#tgs-email-date-to').val()
        };
    }

    function showToast(msg, type) {
        type = type || 'info';
        var $t = $('<div class="tgs-email-toast ' + type + '">' + msg + '</div>');
        $('body').append($t);
        setTimeout(function () { $t.fadeOut(300, function () { $t.remove(); }); }, 4000);
    }

    function setLoading($btn, loading) {
        if (loading) {
            $btn.addClass('tgs-btn-loading').prop('disabled', true);
        } else {
            $btn.removeClass('tgs-btn-loading').prop('disabled', false);
        }
    }

    function showResult(html) {
        $('#tgs-email-result').html(html).show();
    }

    function ajaxPost(action, data, onSuccess, onError) {
        data = data || {};
        data.action = action;
        data.nonce = NONCE;
        $.post(API, data, function (res) {
            if (res.success) {
                onSuccess && onSuccess(res.data);
            } else {
                var msg = (res.data && res.data.message) || 'Có lỗi xảy ra';
                onError ? onError(msg) : showToast(msg, 'error');
            }
        }).fail(function () {
            showToast('Lỗi kết nối server', 'error');
        });
    }

    /* ────────────────────────────────────────
     * SEND EMAILS
     * ──────────────────────────────────────── */
    function sendEmail(action, $btn) {
        var dates = getDates();
        setLoading($btn, true);
        ajaxPost(action, dates, function (data) {
            setLoading($btn, false);
            showToast(data.message || 'Đã gửi thành công!', 'success');
            showResult('<div style="padding:8px 12px; background:#d4edda; color:#155724; border-radius:4px; font-size:13px;">✓ ' + (data.message || 'OK') + (data.log_id ? ' (Log #' + data.log_id + ')' : '') + '</div>');
            loadRecentLogs();
        }, function (msg) {
            setLoading($btn, false);
            showToast(msg, 'error');
            showResult('<div style="padding:8px 12px; background:#f8d7da; color:#721c24; border-radius:4px; font-size:13px;">✗ ' + msg + '</div>');
        });
    }

    $(document).on('click', '#btn-send-shop', function () { sendEmail('tgs_email_send_shop', $(this)); });
    $(document).on('click', '#btn-send-warehouse', function () { sendEmail('tgs_email_send_warehouse', $(this)); });
    $(document).on('click', '#btn-send-all', function () { sendEmail('tgs_email_send_all', $(this)); });

    /* ────────────────────────────────────────
     * PREVIEW
     * ──────────────────────────────────────── */
    $(document).on('click', '#btn-preview', function () {
        var $btn = $(this);
        var dates = getDates();
        dates.email_type = $('#tgs-email-preview-type').val();

        setLoading($btn, true);
        ajaxPost('tgs_email_preview', dates, function (data) {
            setLoading($btn, false);
            var $frame = $('#tgs-email-preview-iframe');
            var $container = $('#tgs-email-preview-frame');

            $container.show();
            var doc = $frame[0].contentDocument || $frame[0].contentWindow.document;
            doc.open();
            doc.write(data.html);
            doc.close();

            // Auto-resize iframe
            setTimeout(function () {
                try { $frame.height(doc.body.scrollHeight + 40); } catch (e) { }
            }, 300);
        }, function (msg) {
            setLoading($btn, false);
            showToast(msg, 'error');
        });
    });

    /* ────────────────────────────────────────
     * RECIPIENTS
     * ──────────────────────────────────────── */
    function loadRecipients() {
        ajaxPost('tgs_email_get_recipients', {}, function (data) {
            renderRecipients(data.recipients || []);
        });
    }

    function renderRecipients(list) {
        if (!list.length) {
            $('#tgs-recipients-list').html('<p style="color:#888; font-size:13px;">Chưa có người nhận nào. Thêm email ở trên để bắt đầu nhận báo cáo.</p>');
            return;
        }

        var html = '<table class="tgs-rcpt-table">';
        html += '<tr><th>Email</th><th>Tên</th><th>Vai trò</th><th style="text-align:center;">🛒 Shop</th><th style="text-align:center;">📦 Kho</th><th style="text-align:center;">Trạng thái</th><th></th></tr>';

        list.forEach(function (r) {
            var types = [];
            try { types = JSON.parse(r.email_types); } catch (e) { }
            var hasShop = types.indexOf('shop_report') >= 0;
            var hasWh = types.indexOf('warehouse_report') >= 0;
            var isActive = parseInt(r.is_active);

            var shopCell = hasShop
                ? '<span style="color:#28a745; font-weight:700;">✓</span>'
                : '<span style="color:#ccc;">—</span>';
            var whCell = hasWh
                ? '<span style="color:#28a745; font-weight:700;">✓</span>'
                : '<span style="color:#ccc;">—</span>';

            var activeBtn = isActive
                ? '<button class="btn-toggle-rcpt" data-id="' + r.recipient_id + '" data-active="0" style="background:#e8f5e9; color:#28a745; border:1px solid #c8e6c9; border-radius:4px; padding:3px 10px; font-size:11px; cursor:pointer; font-weight:600;">Đang bật</button>'
                : '<button class="btn-toggle-rcpt" data-id="' + r.recipient_id + '" data-active="1" style="background:#fff3e0; color:#e65100; border:1px solid #ffe0b2; border-radius:4px; padding:3px 10px; font-size:11px; cursor:pointer;">Đã tắt</button>';

            var rowStyle = isActive ? '' : ' style="opacity:0.5;"';

            // Encode data for edit
            var dataAttr = ' data-id="' + r.recipient_id + '"'
                + ' data-email="' + escHtml(r.email) + '"'
                + ' data-name="' + escHtml(r.display_name) + '"'
                + ' data-role="' + escHtml(r.role_label) + '"'
                + ' data-shop="' + (hasShop ? '1' : '0') + '"'
                + ' data-wh="' + (hasWh ? '1' : '0') + '"';

            html += '<tr' + rowStyle + '>';
            html += '<td><strong>' + escHtml(r.email) + '</strong></td>';
            html += '<td>' + escHtml(r.display_name) + '</td>';
            html += '<td>' + escHtml(r.role_label) + '</td>';
            html += '<td style="text-align:center;">' + shopCell + '</td>';
            html += '<td style="text-align:center;">' + whCell + '</td>';
            html += '<td style="text-align:center;">' + activeBtn + '</td>';
            html += '<td style="white-space:nowrap;">'
                + '<button class="button btn-edit-rcpt"' + dataAttr + ' style="font-size:11px; padding:2px 8px; color:#2d5f8a; margin-right:4px;">✎ Sửa</button>'
                + '<button class="button btn-delete-rcpt" data-id="' + r.recipient_id + '" style="font-size:11px; padding:2px 8px; color:#dc3545;">✕ Xóa</button>'
                + '</td>';
            html += '</tr>';
        });

        html += '</table>';
        $('#tgs-recipients-list').html(html);
    }

    // Toggle type buttons (Shop / Kho)
    $(document).on('click', '.rcpt-type-btn', function () {
        var $btn = $(this);
        $btn.toggleClass('active');
        setTypeBtn('#' + $btn.attr('id'), $btn.hasClass('active'));
    });

    var editingRecipientId = 0; // 0 = add mode, >0 = edit mode

    $(document).on('click', '#btn-add-recipient', function () {
        var types = [];
        if ($('#rcpt-type-shop').hasClass('active')) types.push('shop_report');
        if ($('#rcpt-type-wh').hasClass('active')) types.push('warehouse_report');

        var data = {
            email: $('#rcpt-email').val(),
            display_name: $('#rcpt-name').val(),
            role_label: $('#rcpt-role').val(),
            'email_types[]': types
        };

        if (!data.email) {
            showToast('Vui lòng nhập email', 'error');
            return;
        }
        if (!types.length) {
            showToast('Chọn ít nhất 1 loại báo cáo (Shop hoặc Kho)', 'error');
            return;
        }

        if (editingRecipientId > 0) {
            data.recipient_id = editingRecipientId;
        }

        ajaxPost('tgs_email_save_recipients', data, function (res) {
            showToast(editingRecipientId ? 'Đã cập nhật' : 'Đã thêm người nhận', 'success');
            resetRecipientForm();
            loadRecipients();
        });
    });

    function resetRecipientForm() {
        editingRecipientId = 0;
        $('#rcpt-email, #rcpt-name, #rcpt-role').val('');
        $('#rcpt-email').prop('disabled', false);
        $('#btn-add-recipient').html('+ Thêm');
        $('#btn-cancel-edit').remove();
        // Reset buttons to active
        setTypeBtn('#rcpt-type-shop', true);
        setTypeBtn('#rcpt-type-wh', true);
    }

    function setTypeBtn(sel, active) {
        var $b = $(sel);
        if (active) {
            $b.addClass('active');
            if ($b.data('value') === 'shop_report') {
                $b.css({ background: '#e3f0ff', border: '2px solid #2d5f8a', color: '#1e3a5f' });
            } else {
                $b.css({ background: '#e8f5e9', border: '2px solid #28a745', color: '#1b5e20' });
            }
        } else {
            $b.removeClass('active');
            $b.css({ background: '#f5f5f5', border: '2px solid #ccc', color: '#999' });
        }
    }

    // Edit recipient — fill form
    $(document).on('click', '.btn-edit-rcpt', function () {
        var $btn = $(this);
        editingRecipientId = $btn.data('id');
        $('#rcpt-email').val($btn.data('email')).prop('disabled', true);
        $('#rcpt-name').val($btn.data('name'));
        $('#rcpt-role').val($btn.data('role'));
        setTypeBtn('#rcpt-type-shop', $btn.data('shop') == 1);
        setTypeBtn('#rcpt-type-wh', $btn.data('wh') == 1);
        $('#btn-add-recipient').html('💾 Lưu');
        // Add cancel button if not exists
        if (!$('#btn-cancel-edit').length) {
            $('<button id="btn-cancel-edit" class="button" style="padding:8px 16px; height:36px; margin-left:6px;">Hủy</button>')
                .insertAfter('#btn-add-recipient');
        }
        // Scroll to form
        $('html, body').animate({ scrollTop: $('#rcpt-email').offset().top - 100 }, 300);
    });

    // Cancel edit
    $(document).on('click', '#btn-cancel-edit', function () {
        resetRecipientForm();
    });

    // Toggle active/inactive
    $(document).on('click', '.btn-toggle-rcpt', function () {
        var $btn = $(this);
        var id = $btn.data('id');
        var newActive = $btn.data('active');
        ajaxPost('tgs_email_save_recipients', {
            recipient_id: id,
            is_active: newActive
        }, function () {
            showToast(newActive ? 'Đã bật nhận email' : 'Đã tắt nhận email', 'success');
            loadRecipients();
        });
    });

    $(document).on('click', '.btn-delete-rcpt', function () {
        if (!confirm('Xóa người nhận này?')) return;
        var id = $(this).data('id');
        ajaxPost('tgs_email_delete_recipient', { recipient_id: id }, function () {
            showToast('Đã xóa', 'info');
            loadRecipients();
        });
    });

    /* ────────────────────────────────────────
     * LOGS
     * ──────────────────────────────────────── */
    var currentLogPage = 1;

    function loadRecentLogs() {
        ajaxPost('tgs_email_get_logs', { page: 1 }, function (data) {
            if ($('#tgs-email-recent-logs').length) {
                renderLogsTable(data.rows || [], '#tgs-email-recent-logs', 5);
            }
        });
    }

    function loadLogPage(page, type) {
        currentLogPage = page || 1;
        var filterType = type || ($('#log-filter-type').length ? $('#log-filter-type').val() : '');

        ajaxPost('tgs_email_get_logs', { page: currentLogPage, email_type: filterType }, function (data) {
            renderLogsTable(data.rows || [], '#tgs-email-log-table');
            renderPagination(data.total, data.page, data.per_page);
        });
    }

    function renderLogsTable(rows, container, limit) {
        if (!rows.length) {
            $(container).html('<p style="color:#888; font-size:13px;">Chưa có email nào được gửi.</p>');
            return;
        }

        var display = limit ? rows.slice(0, limit) : rows;

        var html = '<table class="tgs-log-table">';
        html += '<tr><th>#</th><th>Loại</th><th>Subject</th><th>Ngày</th><th>Trạng thái</th><th>Gửi</th><th></th></tr>';

        display.forEach(function (l) {
            var statusBadge = '';
            switch (parseInt(l.send_status)) {
                case 1: statusBadge = '<span class="tgs-badge tgs-badge-success">✓ OK</span>'; break;
                case 2: statusBadge = '<span class="tgs-badge tgs-badge-danger">✗ Lỗi</span>'; break;
                default: statusBadge = '<span class="tgs-badge tgs-badge-warning">⏳ Đang</span>';
            }

            var typeBadge = l.email_type === 'shop_report'
                ? '<span class="tgs-badge tgs-badge-info">Shop</span>'
                : '<span class="tgs-badge tgs-badge-success">Kho</span>';

            var dateRange = l.date_from === l.date_to ? l.date_from : l.date_from + ' → ' + l.date_to;

            html += '<tr>';
            html += '<td>#' + l.log_id + '</td>';
            html += '<td>' + typeBadge + '</td>';
            html += '<td style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' + escHtml(l.subject) + '</td>';
            html += '<td style="white-space:nowrap;">' + dateRange + '</td>';
            html += '<td>' + statusBadge + '</td>';
            html += '<td style="font-size:11px; white-space:nowrap;">' + escHtml(l.triggered_by || 'manual') + '<br>' + escHtml(l.created_at || '') + '</td>';
            html += '<td style="white-space:nowrap;">';
            html += '<button class="button btn-view-log" data-id="' + l.log_id + '" style="font-size:11px; padding:2px 8px;">👁️</button> ';
            html += '<button class="button btn-resend-log" data-id="' + l.log_id + '" style="font-size:11px; padding:2px 8px;">🔄</button>';
            html += '</td>';
            html += '</tr>';
        });

        html += '</table>';
        $(container).html(html);
    }

    function renderPagination(total, page, perPage) {
        var $pg = $('#tgs-email-log-pagination');
        if (!$pg.length) return;

        var totalPages = Math.ceil(total / perPage);
        if (totalPages <= 1) { $pg.html(''); return; }

        var html = '';
        for (var i = 1; i <= Math.min(totalPages, 10); i++) {
            html += '<button class="tgs-pagination-btn' + (i === page ? ' active' : '') + '" data-page="' + i + '">' + i + '</button>';
        }
        if (totalPages > 10) {
            html += '<span style="padding:4px;">...</span>';
            html += '<button class="tgs-pagination-btn" data-page="' + totalPages + '">' + totalPages + '</button>';
        }
        $pg.html(html);
    }

    $(document).on('click', '.tgs-pagination-btn', function () {
        loadLogPage($(this).data('page'));
    });

    $(document).on('click', '#btn-log-filter', function () {
        loadLogPage(1);
    });

    /* ────────────────────────────────────────
     * VIEW LOG DETAIL
     * ──────────────────────────────────────── */
    $(document).on('click', '.btn-view-log', function () {
        var logId = $(this).data('id');
        ajaxPost('tgs_email_get_log_detail', { log_id: logId }, function (data) {
            var log = data.log;
            showEmailModal(log.subject, log.html_content);
        });
    });

    /* ────────────────────────────────────────
     * RESEND
     * ──────────────────────────────────────── */
    $(document).on('click', '.btn-resend-log', function () {
        if (!confirm('Gửi lại email này?')) return;
        var $btn = $(this);
        var logId = $btn.data('id');
        setLoading($btn, true);
        ajaxPost('tgs_email_resend', { log_id: logId }, function (data) {
            setLoading($btn, false);
            showToast(data.message || 'Đã gửi lại!', 'success');
            loadRecentLogs();
            if ($('#tgs-email-log-table').length) loadLogPage(currentLogPage);
        }, function (msg) {
            setLoading($btn, false);
            showToast(msg, 'error');
        });
    });

    /* ────────────────────────────────────────
     * MODAL
     * ──────────────────────────────────────── */
    function showEmailModal(title, html) {
        var $modal = $('#tgs-email-modal');
        $('#tgs-email-modal-title').text(title || 'Email');
        $modal.css('display', 'flex');

        var iframe = document.getElementById('tgs-email-modal-iframe');
        var doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(html);
        doc.close();
    }

    $(document).on('click', '#tgs-email-modal-close', function () {
        $('#tgs-email-modal').hide();
    });
    $(document).on('click', '#tgs-email-modal', function (e) {
        if (e.target === this) $(this).hide();
    });

    /* ────────────────────────────────────────
     * COPY LINK
     * ──────────────────────────────────────── */
    $(document).on('click', '.btn-copy-link', function () {
        var url = $(this).data('url');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function () {
                showToast('Đã copy link!', 'success');
            });
        } else {
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(url).select();
            document.execCommand('copy');
            $temp.remove();
            showToast('Đã copy link!', 'success');
        }
    });

    /* ────────────────────────────────────────
     * SMTP SETTINGS
     * ──────────────────────────────────────── */

    // Toggle SMTP / Resend config visibility
    $(document).on('change', 'input[name="email_mode"]', function () {
        var mode = $(this).val();
        $('#tgs-smtp-config').toggle(mode === 'smtp');
        $('#tgs-resend-config').toggle(mode === 'resend_api');

        // Update card styling
        $('.tgs-mode-card').each(function () {
            var $card = $(this);
            var $input = $card.find('input[type="radio"]');
            var colorMap = { dev: '#28a745', resend_api: '#e91e63' };
            var bgMap = { dev: '#f0fff4', resend_api: '#fce4ec' };
            var defaultColor = '#2d5f8a';
            var defaultBg = '#f0f7ff';
            if ($input.is(':checked')) {
                $card.css({ 'border-color': colorMap[mode] || defaultColor, 'background': bgMap[mode] || defaultBg });
            } else {
                $card.css({ 'border-color': '#dee2e6', 'background': '#fff' });
            }
        });
    });

    // SMTP Presets
    $(document).on('click', '.btn-smtp-preset', function () {
        var $p = $(this);
        var host = $p.data('host');
        var port = $p.data('port');
        var secure = $p.data('secure');
        var auth = $p.data('auth');
        var user = $p.data('user');
        var fromEmail = $p.data('from-email');
        var fromName = $p.data('from-name');
        var noVerifySsl = $p.data('no-verify-ssl');

        $('#smtp_host').val(host);
        $('#smtp_port').val(port);
        $('#smtp_secure').val(secure !== undefined ? secure : 'tls');
        if (auth !== undefined) {
            $('#smtp_auth').val(auth ? '1' : '0');
        }
        if (user) {
            $('#smtp_user').val(user);
        }
        if (fromEmail) {
            $('#from_email').val(fromEmail);
        }
        if (fromName) {
            $('#from_name').val(fromName);
        }
        if (noVerifySsl !== undefined) {
            $('#smtp_no_verify_ssl').prop('checked', !!noVerifySsl);
        }
    });

    // Save SMTP settings
    $(document).on('click', '#btn-save-smtp', function () {
        var $btn = $(this);
        var data = {
            mode: $('input[name="email_mode"]:checked').val() || 'php',
            smtp_host: $('#smtp_host').val(),
            smtp_port: $('#smtp_port').val(),
            smtp_secure: $('#smtp_secure').val(),
            smtp_auth: $('#smtp_auth').val(),
            smtp_user: $('#smtp_user').val(),
            smtp_pass: $('#smtp_pass').val(),
            smtp_no_verify_ssl: $('#smtp_no_verify_ssl').is(':checked') ? 1 : 0,
            resend_api_key: $('#resend_api_key').val(),
            from_email: $('#from_email').val(),
            from_name: $('#from_name').val()
        };

        setLoading($btn, true);
        ajaxPost('tgs_email_save_settings', data, function (res) {
            setLoading($btn, false);
            showToast(res.message || 'Đã lưu!', 'success');
            showSmtpResult('<div style="padding:8px 12px; background:#d4edda; color:#155724; border-radius:4px; font-size:13px;">✓ ' + (res.message || 'OK') + '</div>');
        }, function (msg) {
            setLoading($btn, false);
            showToast(msg, 'error');
        });
    });

    // Test SMTP
    $(document).on('click', '#btn-test-smtp', function () {
        var $btn = $(this);
        var email = $('#test_email_to').val();
        if (!email) {
            showToast('Nhập email để test', 'error');
            return;
        }

        setLoading($btn, true);
        ajaxPost('tgs_email_test_smtp', { test_email: email }, function (res) {
            setLoading($btn, false);
            showToast(res.message, 'success');
            showSmtpResult('<div style="padding:8px 12px; background:#d4edda; color:#155724; border-radius:4px; font-size:13px;">✓ ' + escHtml(res.message) + '</div>');
            $('#tgs-cpanel-warning').hide();
        }, function (msg) {
            setLoading($btn, false);
            showToast(msg, 'error');
            showSmtpResult('<div style="padding:8px 12px; background:#f8d7da; color:#721c24; border-radius:4px; font-size:13px;">✗ ' + escHtml(msg) + '</div>');
            // Show cPanel warning if certificate mismatch error
            if (msg && (msg.indexOf('certificate') !== -1 || msg.indexOf('CN=') !== -1 || msg.indexOf('cprapid') !== -1 || msg.indexOf('Peer certificate') !== -1)) {
                $('#tgs-cpanel-warning').slideDown(200);
            }
        });
    });

    function showSmtpResult(html) {
        $('#tgs-smtp-result').html(html).show();
    }

    /* ────────────────────────────────────────
     * UTILS
     * ──────────────────────────────────────── */
    function escHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /* ────────────────────────────────────────
     * INIT
     * ──────────────────────────────────────── */
    $(document).ready(function () {
        // Dashboard page
        if ($('#tgs-recipients-list').length) {
            loadRecipients();
        }
        if ($('#tgs-email-recent-logs').length) {
            loadRecentLogs();
        }
        // Log viewer page
        if ($('#tgs-email-log-table').length && !$('#tgs-email-recent-logs').length) {
            loadLogPage(1);
        }
    });

})(jQuery);
