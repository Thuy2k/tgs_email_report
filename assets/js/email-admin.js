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
            $('#tgs-recipients-list').html('<p style="color:#888; font-size:13px;">Chưa có người nhận nào. Thêm email để bắt đầu.</p>');
            return;
        }

        var html = '<table class="tgs-rcpt-table">';
        html += '<tr><th>Email</th><th>Tên</th><th>Vai trò</th><th>Loại</th><th>Active</th><th></th></tr>';

        list.forEach(function (r) {
            var types = [];
            try { types = JSON.parse(r.email_types); } catch (e) { }
            var typeBadges = types.map(function (t) {
                var label = t === 'shop_report' ? 'Shop' : 'Kho';
                var cls = t === 'shop_report' ? 'tgs-badge-info' : 'tgs-badge-success';
                return '<span class="tgs-badge ' + cls + '">' + label + '</span>';
            }).join(' ');

            var activeBadge = parseInt(r.is_active)
                ? '<span class="tgs-badge tgs-badge-success">✓</span>'
                : '<span class="tgs-badge tgs-badge-warning">Off</span>';

            html += '<tr>';
            html += '<td>' + escHtml(r.email) + '</td>';
            html += '<td>' + escHtml(r.display_name) + '</td>';
            html += '<td>' + escHtml(r.role_label) + '</td>';
            html += '<td>' + typeBadges + '</td>';
            html += '<td>' + activeBadge + '</td>';
            html += '<td><button class="button btn-delete-rcpt" data-id="' + r.recipient_id + '" style="font-size:11px; padding:2px 8px; color:#dc3545;">✕ Xóa</button></td>';
            html += '</tr>';
        });

        html += '</table>';
        $('#tgs-recipients-list').html(html);
    }

    $(document).on('click', '#btn-add-recipient', function () {
        var data = {
            email: $('#rcpt-email').val(),
            display_name: $('#rcpt-name').val(),
            role_label: $('#rcpt-role').val(),
            'email_types[]': $('#rcpt-types').val()
        };

        if (!data.email) {
            showToast('Vui lòng nhập email', 'error');
            return;
        }

        ajaxPost('tgs_email_save_recipients', data, function (res) {
            showToast('Đã thêm người nhận', 'success');
            $('#rcpt-email, #rcpt-name, #rcpt-role').val('');
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
