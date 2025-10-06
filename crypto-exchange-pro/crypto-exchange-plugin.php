<?php
/**
 * Plugin Name: Crypto Exchange Pro
 * Plugin URI: https://your-website.com/crypto-exchange-pro
 * Description: A comprehensive cryptocurrency exchange platform for WordPress with advanced trading features, wallet management, KYC system, and admin dashboard.
 * Version: 2.0.0
 * Author: Your Name
 * Author URI: https://your-website.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: crypto-exchange-pro
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CRYPTO_EXCHANGE_VERSION', '2.0.0');
define('CRYPTO_EXCHANGE_PLUGIN_FILE', __FILE__);
define('CRYPTO_EXCHANGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CRYPTO_EXCHANGE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CRYPTO_EXCHANGE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-crypto-exchange.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-database.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-auth.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-trading.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-wallet.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-market-data.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-kyc.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-admin.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-api.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-security.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-theme.php';

// Include advanced classes
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-websocket.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-price-feed.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-matching-engine.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-blockchain-wallet.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-charting.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-advanced-security.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-coin-management.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-risk-management.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-payment-processing.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-advanced-kyc.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-notifications.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-ajax-handlers.php';

// Initialize the plugin
function crypto_exchange_init() {
    $crypto_exchange = new Crypto_Exchange();
    $crypto_exchange->init();
    
    // Initialize shortcodes
    new Crypto_Exchange_Shortcodes();
    
    // Initialize AJAX handlers
    new Crypto_Exchange_Ajax_Handlers();
}
add_action('plugins_loaded', 'crypto_exchange_init');

// Activation hook
register_activation_hook(__FILE__, 'crypto_exchange_activate');
function crypto_exchange_activate() {
    // Create database tables
    $database = new Crypto_Exchange_Database();
    $database->create_tables();
    
    // Create additional tables for advanced features
    $advanced_security = new Crypto_Exchange_Advanced_Security();
    $advanced_security->create_security_tables();
    
    $risk_management = new Crypto_Exchange_Risk_Management();
    $risk_management->create_risk_tables();
    
    $payment_processing = new Crypto_Exchange_Payment_Processing();
    $payment_processing->create_payment_tables();
    
    $advanced_kyc = new Crypto_Exchange_Advanced_KYC();
    $advanced_kyc->create_kyc_tables();
    
    $notifications = new Crypto_Exchange_Notifications();
    $notifications->create_notification_tables();
    
    $coin_management = new Crypto_Exchange_Coin_Management();
    $coin_management->create_coins_table();
    
    // Set default options
    add_option('crypto_exchange_version', CRYPTO_EXCHANGE_VERSION);
    add_option('crypto_exchange_settings', array(
        'exchange_name' => 'Crypto Exchange Pro',
        'supported_currencies' => array('BTC', 'ETH', 'BNB', 'ADA', 'SOL', 'DOT', 'MATIC', 'AVAX'),
        'fiat_currencies' => array('USD', 'EUR', 'GBP'),
        'trading_fees' => 0.001,
        'withdrawal_fees' => array(
            'BTC' => 0.0005,
            'ETH' => 0.01,
            'BNB' => 0.1
        ),
        'min_trade_amount' => 10,
        'max_trade_amount' => 1000000,
        'kyc_required' => true,
        'two_factor_auth' => true,
        'api_enabled' => true,
        'websocket_enabled' => true,
        'real_time_prices' => true,
        'advanced_security' => true,
        'risk_management' => true,
        'payment_processing' => true,
        'ai_kyc' => true,
        'notifications' => true
    ));
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'crypto_exchange_deactivate');
function crypto_exchange_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Clear scheduled events
    wp_clear_scheduled_hook('crypto_exchange_update_market_data');
    wp_clear_scheduled_hook('crypto_exchange_update_real_prices');
    wp_clear_scheduled_hook('crypto_exchange_process_orders');
    wp_clear_scheduled_hook('crypto_exchange_update_order_books');
    wp_clear_scheduled_hook('crypto_exchange_sync_wallets');
    wp_clear_scheduled_hook('crypto_exchange_process_deposits');
    wp_clear_scheduled_hook('crypto_exchange_process_withdrawals');
    wp_clear_scheduled_hook('crypto_exchange_update_chart_data');
    wp_clear_scheduled_hook('crypto_exchange_security_scan');
    wp_clear_scheduled_hook('crypto_exchange_audit_logs');
    wp_clear_scheduled_hook('crypto_exchange_risk_scan');
    wp_clear_scheduled_hook('crypto_exchange_position_monitor');
    wp_clear_scheduled_hook('crypto_exchange_liquidation_check');
    wp_clear_scheduled_hook('crypto_exchange_process_payments');
    wp_clear_scheduled_hook('crypto_exchange_sync_bank_transactions');
    wp_clear_scheduled_hook('crypto_exchange_process_kyc_documents');
    wp_clear_scheduled_hook('crypto_exchange_send_notifications');
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'crypto_exchange_uninstall');
function crypto_exchange_uninstall() {
    // Remove database tables
    $database = new Crypto_Exchange_Database();
    $database->drop_tables();
    
    // Remove additional tables
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}crypto_blocked_ips");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}crypto_risk_alerts");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}crypto_payments");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}crypto_kyc_applications");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}crypto_notifications");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}crypto_price_history");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}crypto_coins");
    
    // Remove options
    delete_option('crypto_exchange_version');
    delete_option('crypto_exchange_settings');
    delete_option('crypto_exchange_websocket_enabled');
    delete_option('crypto_exchange_real_time_prices');
    delete_option('crypto_exchange_advanced_security');
    delete_option('crypto_exchange_risk_management');
    delete_option('crypto_exchange_payment_processing');
    delete_option('crypto_exchange_ai_kyc');
    delete_option('crypto_exchange_notifications');
}

// Add admin menu
add_action('admin_menu', 'crypto_exchange_admin_menu');
function crypto_exchange_admin_menu() {
    add_menu_page(
        'Crypto Exchange Pro',
        'Crypto Exchange Pro',
        'manage_options',
        'crypto-exchange-pro',
        'crypto_exchange_admin_page',
        'dashicons-chart-line',
        30
    );
    
    add_submenu_page(
        'crypto-exchange-pro',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'crypto-exchange-pro',
        'crypto_exchange_admin_page'
    );
    
    add_submenu_page(
        'crypto-exchange-pro',
        'Settings',
        'Settings',
        'manage_options',
        'crypto-exchange-settings',
        'crypto_exchange_settings_page'
    );
    
    add_submenu_page(
        'crypto-exchange-pro',
        'Users',
        'Users',
        'manage_options',
        'crypto-exchange-users',
        'crypto_exchange_users_page'
    );
    
    add_submenu_page(
        'crypto-exchange-pro',
        'Trading',
        'Trading',
        'manage_options',
        'crypto-exchange-trading',
        'crypto_exchange_trading_page'
    );
    
    add_submenu_page(
        'crypto-exchange-pro',
        'Wallets',
        'Wallets',
        'manage_options',
        'crypto-exchange-wallets',
        'crypto_exchange_wallets_page'
    );
    
    add_submenu_page(
        'crypto-exchange-pro',
        'KYC',
        'KYC',
        'manage_options',
        'crypto-exchange-kyc',
        'crypto_exchange_kyc_page'
    );
    
    add_submenu_page(
        'crypto-exchange-pro',
        'Security',
        'Security',
        'manage_options',
        'crypto-exchange-security',
        'crypto_exchange_security_page'
    );
    
    add_submenu_page(
        'crypto-exchange-pro',
        'Risk Management',
        'Risk Management',
        'manage_options',
        'crypto-exchange-risk',
        'crypto_exchange_risk_page'
    );
    
    add_submenu_page(
        'crypto-exchange-pro',
        'Payments',
        'Payments',
        'manage_options',
        'crypto-exchange-payments',
        'crypto_exchange_payments_page'
    );
    
    add_submenu_page(
        'crypto-exchange-pro',
        'Notifications',
        'Notifications',
        'manage_options',
        'crypto-exchange-notifications',
        'crypto_exchange_notifications_page'
    );
    
    add_submenu_page(
        'crypto-exchange-pro',
        'Coin Management',
        'Coin Management',
        'manage_options',
        'crypto-exchange-coins',
        'crypto_exchange_coins_page'
    );
}

// Admin page callbacks
function crypto_exchange_admin_page() {
    $admin = new Crypto_Exchange_Admin();
    $admin->dashboard_page();
}

function crypto_exchange_settings_page() {
    $admin = new Crypto_Exchange_Admin();
    $admin->settings_page();
}

function crypto_exchange_users_page() {
    $admin = new Crypto_Exchange_Admin();
    $admin->users_page();
}

function crypto_exchange_trading_page() {
    $admin = new Crypto_Exchange_Admin();
    $admin->trading_page();
}

function crypto_exchange_wallets_page() {
    $admin = new Crypto_Exchange_Admin();
    $admin->wallets_page();
}

function crypto_exchange_kyc_page() {
    $admin = new Crypto_Exchange_Admin();
    $admin->kyc_page();
}

function crypto_exchange_security_page() {
    $advanced_security = new Crypto_Exchange_Advanced_Security();
    $dashboard_data = $advanced_security->get_security_dashboard();
    
    echo '<div class="wrap">';
    echo '<h1>Security Dashboard</h1>';
    echo '<div class="crypto-security-dashboard">';
    echo '<div class="security-stats">';
    echo '<div class="stat-box"><h3>Total Events (24h)</h3><p>' . $dashboard_data['total_events'] . '</p></div>';
    echo '<div class="stat-box"><h3>Failed Logins</h3><p>' . $dashboard_data['failed_logins'] . '</p></div>';
    echo '<div class="stat-box"><h3>Blocked IPs</h3><p>' . $dashboard_data['blocked_ips'] . '</p></div>';
    echo '<div class="stat-box"><h3>Suspicious Activities</h3><p>' . $dashboard_data['suspicious_activities'] . '</p></div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

function crypto_exchange_risk_page() {
    $risk_management = new Crypto_Exchange_Risk_Management();
    
    echo '<div class="wrap">';
    echo '<h1>Risk Management</h1>';
    echo '<div class="crypto-risk-dashboard">';
    echo '<p>Risk management dashboard content goes here.</p>';
    echo '</div>';
    echo '</div>';
}

function crypto_exchange_payments_page() {
    $payment_processing = new Crypto_Exchange_Payment_Processing();
    
    echo '<div class="wrap">';
    echo '<h1>Payment Processing</h1>';
    echo '<div class="crypto-payments-dashboard">';
    echo '<p>Payment processing dashboard content goes here.</p>';
    echo '</div>';
    echo '</div>';
}

function crypto_exchange_notifications_page() {
    $notifications = new Crypto_Exchange_Notifications();
    
    echo '<div class="wrap">';
    echo '<h1>Notifications</h1>';
    echo '<div class="crypto-notifications-dashboard">';
    echo '<p>Notifications dashboard content goes here.</p>';
    echo '</div>';
    echo '</div>';
}

function crypto_exchange_coins_page() {
    include CRYPTO_EXCHANGE_PLUGIN_DIR . 'templates/admin-coin-management.php';
}

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'crypto_exchange_enqueue_scripts');
function crypto_exchange_enqueue_scripts() {
    wp_enqueue_script('crypto-exchange-js', CRYPTO_EXCHANGE_PLUGIN_URL . 'assets/js/crypto-exchange.js', array('jquery'), CRYPTO_EXCHANGE_VERSION, true);
    wp_enqueue_style('crypto-exchange-css', CRYPTO_EXCHANGE_PLUGIN_URL . 'assets/css/crypto-exchange.css', array(), CRYPTO_EXCHANGE_VERSION);
    
    // Enqueue WebSocket script
    wp_enqueue_script('crypto-exchange-websocket', CRYPTO_EXCHANGE_PLUGIN_URL . 'assets/js/websocket.js', array('jquery'), CRYPTO_EXCHANGE_VERSION, true);
    
    // Enqueue charting script
    wp_enqueue_script('crypto-exchange-charting', CRYPTO_EXCHANGE_PLUGIN_URL . 'assets/js/charting.js', array('jquery'), CRYPTO_EXCHANGE_VERSION, true);
    
    // Localize script for AJAX
    wp_localize_script('crypto-exchange-js', 'cryptoExchange', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('crypto_exchange_nonce'),
        'apiUrl' => home_url('/wp-json/crypto-exchange/v1/'),
        'isLoggedIn' => is_user_logged_in(),
        'currentUserId' => get_current_user_id(),
        'websocketUrl' => home_url('/?crypto_ws=1'),
        'version' => CRYPTO_EXCHANGE_VERSION
    ));
}

// Enqueue admin scripts
add_action('admin_enqueue_scripts', 'crypto_exchange_admin_enqueue_scripts');
function crypto_exchange_admin_enqueue_scripts($hook) {
    if (strpos($hook, 'crypto-exchange') !== false) {
        wp_enqueue_script('crypto-exchange-admin-js', CRYPTO_EXCHANGE_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), CRYPTO_EXCHANGE_VERSION, true);
        wp_enqueue_style('crypto-exchange-admin-css', CRYPTO_EXCHANGE_PLUGIN_URL . 'assets/css/admin.css', array(), CRYPTO_EXCHANGE_VERSION);
        
        wp_localize_script('crypto-exchange-admin-js', 'cryptoExchangeAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crypto_exchange_admin_nonce')
        ));
    }
}

// Add shortcodes
add_shortcode('crypto_exchange_login', 'crypto_exchange_login_shortcode');
function crypto_exchange_login_shortcode($atts) {
    $auth = new Crypto_Exchange_Auth();
    return $auth->login_form();
}

add_shortcode('crypto_exchange_register', 'crypto_exchange_register_shortcode');
function crypto_exchange_register_shortcode($atts) {
    $auth = new Crypto_Exchange_Auth();
    return $auth->register_form();
}

add_shortcode('crypto_exchange_dashboard', 'crypto_exchange_dashboard_shortcode');
function crypto_exchange_dashboard_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>Please log in to access your dashboard.</p>';
    }
    
    $dashboard = new Crypto_Exchange_Dashboard();
    return $dashboard->render();
}

add_shortcode('crypto_exchange_trading', 'crypto_exchange_trading_shortcode');
function crypto_exchange_trading_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>Please log in to access trading.</p>';
    }
    
    $trading = new Crypto_Exchange_Trading();
    return $trading->render();
}

add_shortcode('crypto_exchange_wallets', 'crypto_exchange_wallets_shortcode');
function crypto_exchange_wallets_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>Please log in to access your wallets.</p>';
    }
    
    $wallet = new Crypto_Exchange_Wallet();
    return $wallet->render();
}

add_shortcode('crypto_exchange_chart', 'crypto_exchange_chart_shortcode');
function crypto_exchange_chart_shortcode($atts) {
    $atts = shortcode_atts(array(
        'pair' => 'BTC/USD',
        'interval' => '1',
        'theme' => 'dark'
    ), $atts);
    
    $charting = new Crypto_Exchange_Charting();
    return $charting->render_chart($atts['pair'], $atts['interval'], $atts['theme']);
}

// Initialize REST API
add_action('rest_api_init', 'crypto_exchange_rest_api_init');
function crypto_exchange_rest_api_init() {
    $api = new Crypto_Exchange_API();
    $api->init();
}

// Add custom post types
add_action('init', 'crypto_exchange_custom_post_types');
function crypto_exchange_custom_post_types() {
    // Trading pairs
    register_post_type('crypto_pair', array(
        'labels' => array(
            'name' => 'Trading Pairs',
            'singular_name' => 'Trading Pair'
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'custom-fields'),
        'show_in_rest' => true
    ));
    
    // Market data
    register_post_type('crypto_market_data', array(
        'labels' => array(
            'name' => 'Market Data',
            'singular_name' => 'Market Data'
        ),
        'public' => false,
        'supports' => array('custom-fields'),
        'show_in_rest' => true
    ));
}

// Add custom taxonomies
add_action('init', 'crypto_exchange_custom_taxonomies');
function crypto_exchange_custom_taxonomies() {
    register_taxonomy('crypto_category', 'crypto_pair', array(
        'labels' => array(
            'name' => 'Crypto Categories',
            'singular_name' => 'Crypto Category'
        ),
        'hierarchical' => true,
        'show_in_rest' => true
    ));
}

// Add custom user meta fields
add_action('show_user_profile', 'crypto_exchange_user_profile_fields');
add_action('edit_user_profile', 'crypto_exchange_user_profile_fields');
function crypto_exchange_user_profile_fields($user) {
    $kyc_status = get_user_meta($user->ID, 'crypto_kyc_status', true);
    $two_fa_enabled = get_user_meta($user->ID, 'crypto_2fa_enabled', true);
    $trading_limits = get_user_meta($user->ID, 'crypto_trading_limits', true);
    $hardware_wallet_enabled = get_user_meta($user->ID, 'crypto_hardware_wallet_enabled', true);
    ?>
    <h3>Crypto Exchange Pro Information</h3>
    <table class="form-table">
        <tr>
            <th><label for="crypto_kyc_status">KYC Status</label></th>
            <td>
                <select name="crypto_kyc_status" id="crypto_kyc_status">
                    <option value="pending" <?php selected($kyc_status, 'pending'); ?>>Pending</option>
                    <option value="verified" <?php selected($kyc_status, 'verified'); ?>>Verified</option>
                    <option value="rejected" <?php selected($kyc_status, 'rejected'); ?>>Rejected</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="crypto_2fa_enabled">2FA Enabled</label></th>
            <td>
                <input type="checkbox" name="crypto_2fa_enabled" id="crypto_2fa_enabled" value="1" <?php checked($two_fa_enabled, 1); ?> />
            </td>
        </tr>
        <tr>
            <th><label for="crypto_hardware_wallet_enabled">Hardware Wallet Enabled</label></th>
            <td>
                <input type="checkbox" name="crypto_hardware_wallet_enabled" id="crypto_hardware_wallet_enabled" value="1" <?php checked($hardware_wallet_enabled, 1); ?> />
            </td>
        </tr>
        <tr>
            <th><label for="crypto_trading_limits">Trading Limits</label></th>
            <td>
                <input type="text" name="crypto_trading_limits" id="crypto_trading_limits" value="<?php echo esc_attr($trading_limits); ?>" class="regular-text" />
                <p class="description">Daily trading limit in USD</p>
            </td>
        </tr>
    </table>
    <?php
}

// Save custom user meta fields
add_action('personal_options_update', 'crypto_exchange_save_user_profile_fields');
add_action('edit_user_profile_update', 'crypto_exchange_save_user_profile_fields');
function crypto_exchange_save_user_profile_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    
    update_user_meta($user_id, 'crypto_kyc_status', sanitize_text_field($_POST['crypto_kyc_status']));
    update_user_meta($user_id, 'crypto_2fa_enabled', isset($_POST['crypto_2fa_enabled']) ? 1 : 0);
    update_user_meta($user_id, 'crypto_hardware_wallet_enabled', isset($_POST['crypto_hardware_wallet_enabled']) ? 1 : 0);
    update_user_meta($user_id, 'crypto_trading_limits', sanitize_text_field($_POST['crypto_trading_limits']));
}

// Add custom capabilities
add_action('init', 'crypto_exchange_add_custom_capabilities');
function crypto_exchange_add_custom_capabilities() {
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('manage_crypto_exchange');
        $role->add_cap('trade_crypto');
        $role->add_cap('withdraw_crypto');
        $role->add_cap('deposit_crypto');
        $role->add_cap('manage_crypto_security');
        $role->add_cap('manage_crypto_risk');
        $role->add_cap('manage_crypto_payments');
    }
    
    $role = get_role('subscriber');
    if ($role) {
        $role->add_cap('trade_crypto');
        $role->add_cap('withdraw_crypto');
        $role->add_cap('deposit_crypto');
    }
}

// Add custom rewrite rules
add_action('init', 'crypto_exchange_add_rewrite_rules');
function crypto_exchange_add_rewrite_rules() {
    add_rewrite_rule('^crypto-exchange/([^/]+)/?$', 'index.php?crypto_page=$matches[1]', 'top');
    add_rewrite_rule('^crypto-exchange/([^/]+)/([^/]+)/?$', 'index.php?crypto_page=$matches[1]&crypto_action=$matches[2]', 'top');
}

// Add custom query vars
add_filter('query_vars', 'crypto_exchange_add_query_vars');
function crypto_exchange_add_query_vars($vars) {
    $vars[] = 'crypto_page';
    $vars[] = 'crypto_action';
    return $vars;
}

// Handle custom pages
add_action('template_redirect', 'crypto_exchange_template_redirect');
function crypto_exchange_template_redirect() {
    $crypto_page = get_query_var('crypto_page');
    if ($crypto_page) {
        $crypto_action = get_query_var('crypto_action');
        
        switch ($crypto_page) {
            case 'login':
                $auth = new Crypto_Exchange_Auth();
                $auth->handle_login();
                break;
            case 'register':
                $auth = new Crypto_Exchange_Auth();
                $auth->handle_register();
                break;
            case 'dashboard':
                if (!is_user_logged_in()) {
                    wp_redirect(home_url('/crypto-exchange/login'));
                    exit;
                }
                $dashboard = new Crypto_Exchange_Dashboard();
                $dashboard->render();
                break;
            case 'trading':
                if (!is_user_logged_in()) {
                    wp_redirect(home_url('/crypto-exchange/login'));
                    exit;
                }
                $trading = new Crypto_Exchange_Trading();
                $trading->render();
                break;
            case 'wallets':
                if (!is_user_logged_in()) {
                    wp_redirect(home_url('/crypto-exchange/login'));
                    exit;
                }
                $wallet = new Crypto_Exchange_Wallet();
                $wallet->render();
                break;
            case 'kyc':
                if (!is_user_logged_in()) {
                    wp_redirect(home_url('/crypto-exchange/login'));
                    exit;
                }
                $kyc = new Crypto_Exchange_Advanced_KYC();
                $kyc->render();
                break;
        }
    }
}

// Add cron jobs
add_action('crypto_exchange_update_market_data', 'crypto_exchange_update_market_data_cron');
function crypto_exchange_update_market_data_cron() {
    $market_data = new Crypto_Exchange_Market_Data();
    $market_data->update_all_prices();
}

// Schedule cron jobs
if (!wp_next_scheduled('crypto_exchange_update_market_data')) {
    wp_schedule_event(time(), 'every_minute', 'crypto_exchange_update_market_data');
}

// Add custom cron intervals
add_filter('cron_schedules', 'crypto_exchange_cron_schedules');
function crypto_exchange_cron_schedules($schedules) {
    $schedules['every_minute'] = array(
        'interval' => 60,
        'display' => __('Every Minute')
    );
    $schedules['every_5_seconds'] = array(
        'interval' => 5,
        'display' => __('Every 5 Seconds')
    );
    $schedules['every_10_seconds'] = array(
        'interval' => 10,
        'display' => __('Every 10 Seconds')
    );
    $schedules['every_30_seconds'] = array(
        'interval' => 30,
        'display' => __('Every 30 Seconds')
    );
    return $schedules;
}

// Cleanup on deactivation
register_deactivation_hook(__FILE__, 'crypto_exchange_deactivate_cron');
function crypto_exchange_deactivate_cron() {
    wp_clear_scheduled_hook('crypto_exchange_update_market_data');
}
