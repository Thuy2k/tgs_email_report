<?php
/**
 * Constants cho TGS Email Report
 */

if (!defined('ABSPATH')) exit;

/* ── Table names (global — base_prefix) ── */
define('TGS_EMAIL_TABLE_LOG',        'wp_global_email_report_log');
define('TGS_EMAIL_TABLE_RECIPIENTS', 'wp_global_email_report_recipients');

/* ── Email types ── */
define('TGS_EMAIL_TYPE_SHOP',      'shop_report');      // Báo cáo shop bán hàng
define('TGS_EMAIL_TYPE_WAREHOUSE', 'warehouse_report'); // Báo cáo kho MIN/MAX
define('TGS_EMAIL_TYPE_BACKUP',    'backup_report');    // Báo cáo backup DB tự động
define('TGS_EMAIL_TYPE_EINVOICE',  'einvoice_report');  // Báo cáo hóa đơn điện tử

/* ── Ledger types (from tgs_shop_management) ── */
define('TGS_EMAIL_LEDGER_TYPE_IMPORT',    1);
define('TGS_EMAIL_LEDGER_TYPE_EXPORT',    2);
define('TGS_EMAIL_LEDGER_TYPE_DAMAGE',    6);
define('TGS_EMAIL_LEDGER_TYPE_RECEIPT',   7);  // Thu tiền
define('TGS_EMAIL_LEDGER_TYPE_PAYMENT',   8);  // Chi tiền
define('TGS_EMAIL_LEDGER_TYPE_SALE',      10); // Bán hàng
define('TGS_EMAIL_LEDGER_TYPE_RETURN',    11); // Trả hàng

/* ── Ledger statuses ── */
define('TGS_EMAIL_LEDGER_STATUS_APPROVED',  2);
define('TGS_EMAIL_LEDGER_STATUS_COMPLETED', 4);
