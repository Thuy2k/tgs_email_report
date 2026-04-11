<?php
/**
 * Plugin Name: TGS Email Report
 * Description: Hệ thống gửi email báo cáo tự động cho Shop & Kho — tổng kết bán hàng, thu ngân hàng, MIN/MAX tồn kho, cảnh báo.
 * Version:     1.0.0
 * Author:      TGS Team
 * Requires at least: 6.0
 * Network:     true
 * Text Domain: tgs-email-report
 *
 * Hook vào header-mega-nav của tgs_shop_management (tgs_shop_report_menu)
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ──────────────────── Constants ──────────────────── */
define('TGS_EMAIL_REPORT_VERSION', '1.0.0');
define('TGS_EMAIL_REPORT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TGS_EMAIL_REPORT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TGS_EMAIL_REPORT_PLUGIN_FILE', __FILE__);

/* ──────────────────── Autoload ──────────────────── */
require_once TGS_EMAIL_REPORT_PLUGIN_DIR . 'includes/class-tgs-email-constants.php';
require_once TGS_EMAIL_REPORT_PLUGIN_DIR . 'includes/class-tgs-email-settings.php';
require_once TGS_EMAIL_REPORT_PLUGIN_DIR . 'includes/class-tgs-email-sender.php';

// Collectors
require_once TGS_EMAIL_REPORT_PLUGIN_DIR . 'collectors/class-collector-base.php';
require_once TGS_EMAIL_REPORT_PLUGIN_DIR . 'collectors/class-collector-shop-sales.php';
require_once TGS_EMAIL_REPORT_PLUGIN_DIR . 'collectors/class-collector-shop-bank.php';
require_once TGS_EMAIL_REPORT_PLUGIN_DIR . 'collectors/class-collector-shop-max.php';
require_once TGS_EMAIL_REPORT_PLUGIN_DIR . 'collectors/class-collector-warehouse-minmax.php';
require_once TGS_EMAIL_REPORT_PLUGIN_DIR . 'collectors/class-collector-warehouse-stock.php';
require_once TGS_EMAIL_REPORT_PLUGIN_DIR . 'collectors/class-collector-summary.php';

// Functions
require_once TGS_EMAIL_REPORT_PLUGIN_DIR . 'functions/ajax/class-tgs-email-ajax.php';
require_once TGS_EMAIL_REPORT_PLUGIN_DIR . 'functions/class-tgs-email-trigger.php';

/* ──────────────────── Main Class ──────────────────── */
class TGS_Email_Report
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Hook vào header mega-nav menu — mục "Báo cáo"
        add_action('tgs_shop_report_menu', [$this, 'render_menu_item'], 20, 1);

        // Dashboard routes
        add_filter('tgs_shop_dashboard_routes', [$this, 'register_routes']);

        // Admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX
        TGS_Email_Ajax::register();

        // URL trigger endpoint
        TGS_Email_Trigger::register();

        // SMTP settings hook
        TGS_Email_Settings::init();
    }

    /* ── Menu item ── */
    public function render_menu_item($current_view)
    {
        $slug     = 'email-report-dashboard';
        $url      = admin_url('admin.php?page=tgs-shop-management&view=' . $slug);
        $is_active = (strpos($current_view, 'email-report') === 0);
        ?>
        <li class="menu-item <?php echo $is_active ? 'active' : ''; ?>">
            <a href="<?php echo esc_url($url); ?>" class="menu-link">
                <i class="menu-icon tf-icons bx bx-envelope"></i>
                <div>Email Báo Cáo</div>
            </a>
        </li>
        <?php
    }

    /* ── Dashboard routes ── */
    public function register_routes($routes)
    {
        $routes['email-report-dashboard'] = [
            'Email Báo Cáo',
            TGS_EMAIL_REPORT_PLUGIN_DIR . 'admin-views/email-dashboard.php',
        ];
        $routes['email-report-log'] = [
            'Lịch sử Email',
            TGS_EMAIL_REPORT_PLUGIN_DIR . 'admin-views/email-log-viewer.php',
        ];
        $routes['email-report-settings'] = [
            'Cài đặt SMTP',
            TGS_EMAIL_REPORT_PLUGIN_DIR . 'admin-views/email-settings.php',
        ];
        return $routes;
    }

    /* ── Admin assets ── */
    public function enqueue_admin_assets($hook)
    {
        // Chỉ load trên trang tgs-shop-management
        if (strpos($hook, 'tgs-shop-management') === false) {
            return;
        }
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : '';
        if (strpos($view, 'email-report') !== 0) {
            return;
        }

        wp_enqueue_style(
            'tgs-email-report-admin',
            TGS_EMAIL_REPORT_PLUGIN_URL . 'assets/css/email-admin.css',
            [],
            TGS_EMAIL_REPORT_VERSION
        );

        wp_enqueue_script(
            'tgs-email-report-admin',
            TGS_EMAIL_REPORT_PLUGIN_URL . 'assets/js/email-admin.js',
            ['jquery'],
            TGS_EMAIL_REPORT_VERSION,
            true
        );

        wp_localize_script('tgs-email-report-admin', 'tgsEmailReport', [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('tgs_email_report_nonce'),
            'triggerUrl' => home_url('?tgs_email_trigger=1'),
        ]);
    }
}

/* ── Bootstrap ── */
add_action('plugins_loaded', function () {
    TGS_Email_Report::get_instance();
});
