<?php
/**
 * Main Crypto Exchange class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange {
    
    private $version;
    private $plugin_dir;
    private $plugin_url;
    
    public function __construct() {
        $this->version = CRYPTO_EXCHANGE_VERSION;
        $this->plugin_dir = CRYPTO_EXCHANGE_PLUGIN_DIR;
        $this->plugin_url = CRYPTO_EXCHANGE_PLUGIN_URL;
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        add_action('init', array($this, 'init_hooks'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Initialize components
        $this->init_components();
    }
    
    /**
     * Initialize hooks
     */
    public function init_hooks() {
        // Add custom rewrite rules
        add_rewrite_rule('^crypto-exchange/([^/]+)/?$', 'index.php?crypto_page=$matches[1]', 'top');
        add_rewrite_rule('^crypto-exchange/([^/]+)/([^/]+)/?$', 'index.php?crypto_page=$matches[1]&crypto_action=$matches[2]', 'top');
        
        // Add custom query vars
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Handle custom pages
        add_action('template_redirect', array($this, 'template_redirect'));
        
        // Add AJAX handlers
        add_action('wp_ajax_crypto_exchange_login', array($this, 'ajax_login'));
        add_action('wp_ajax_crypto_exchange_register', array($this, 'ajax_register'));
        add_action('wp_ajax_crypto_exchange_logout', array($this, 'ajax_logout'));
        add_action('wp_ajax_crypto_exchange_place_order', array($this, 'ajax_place_order'));
        add_action('wp_ajax_crypto_exchange_cancel_order', array($this, 'ajax_cancel_order'));
        add_action('wp_ajax_crypto_exchange_get_orders', array($this, 'ajax_get_orders'));
        add_action('wp_ajax_crypto_exchange_get_trades', array($this, 'ajax_get_trades'));
        add_action('wp_ajax_crypto_exchange_get_wallets', array($this, 'ajax_get_wallets'));
        add_action('wp_ajax_crypto_exchange_deposit', array($this, 'ajax_deposit'));
        add_action('wp_ajax_crypto_exchange_withdraw', array($this, 'ajax_withdraw'));
        add_action('wp_ajax_crypto_exchange_get_market_data', array($this, 'ajax_get_market_data'));
        add_action('wp_ajax_crypto_exchange_upload_kyc', array($this, 'ajax_upload_kyc'));
        
        // Add non-privileged AJAX handlers
        add_action('wp_ajax_nopriv_crypto_exchange_login', array($this, 'ajax_login'));
        add_action('wp_ajax_nopriv_crypto_exchange_register', array($this, 'ajax_register'));
        add_action('wp_ajax_nopriv_crypto_exchange_get_market_data', array($this, 'ajax_get_market_data'));
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize database
        $database = new Crypto_Exchange_Database();
        
        // Initialize market data
        $market_data = new Crypto_Exchange_Market_Data();
        
        // Initialize security
        $security = new Crypto_Exchange_Security();
        
        // Initialize theme
        $theme = new Crypto_Exchange_Theme();
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('crypto-exchange-js', $this->plugin_url . 'assets/js/crypto-exchange.js', array('jquery'), $this->version, true);
        wp_enqueue_style('crypto-exchange-css', $this->plugin_url . 'assets/css/crypto-exchange.css', array(), $this->version);
        
        // Localize script
        wp_localize_script('crypto-exchange-js', 'cryptoExchange', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crypto_exchange_nonce'),
            'apiUrl' => home_url('/wp-json/crypto-exchange/v1/'),
            'isLoggedIn' => is_user_logged_in(),
            'currentUserId' => get_current_user_id()
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'crypto-exchange') !== false) {
            wp_enqueue_script('crypto-exchange-admin-js', $this->plugin_url . 'assets/js/admin.js', array('jquery'), $this->version, true);
            wp_enqueue_style('crypto-exchange-admin-css', $this->plugin_url . 'assets/css/admin.css', array(), $this->version);
            
            wp_localize_script('crypto-exchange-admin-js', 'cryptoExchangeAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('crypto_exchange_admin_nonce')
            ));
        }
    }
    
    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'crypto_page';
        $vars[] = 'crypto_action';
        return $vars;
    }
    
    /**
     * Handle custom pages
     */
    public function template_redirect() {
        $crypto_page = get_query_var('crypto_page');
        if ($crypto_page) {
            $crypto_action = get_query_var('crypto_action');
            
            switch ($crypto_page) {
                case 'login':
                    $this->handle_login_page();
                    break;
                case 'register':
                    $this->handle_register_page();
                    break;
                case 'dashboard':
                    $this->handle_dashboard_page();
                    break;
                case 'trading':
                    $this->handle_trading_page();
                    break;
                case 'wallets':
                    $this->handle_wallets_page();
                    break;
                case 'profile':
                    $this->handle_profile_page();
                    break;
                case 'kyc':
                    $this->handle_kyc_page();
                    break;
            }
        }
    }
    
    /**
     * Handle login page
     */
    private function handle_login_page() {
        if (is_user_logged_in()) {
            wp_redirect(home_url('/crypto-exchange/dashboard'));
            exit;
        }
        
        $auth = new Crypto_Exchange_Auth();
        $auth->render_login_page();
    }
    
    /**
     * Handle register page
     */
    private function handle_register_page() {
        if (is_user_logged_in()) {
            wp_redirect(home_url('/crypto-exchange/dashboard'));
            exit;
        }
        
        $auth = new Crypto_Exchange_Auth();
        $auth->render_register_page();
    }
    
    /**
     * Handle dashboard page
     */
    private function handle_dashboard_page() {
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/crypto-exchange/login'));
            exit;
        }
        
        $dashboard = new Crypto_Exchange_Dashboard();
        $dashboard->render();
    }
    
    /**
     * Handle trading page
     */
    private function handle_trading_page() {
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/crypto-exchange/login'));
            exit;
        }
        
        $trading = new Crypto_Exchange_Trading();
        $trading->render();
    }
    
    /**
     * Handle wallets page
     */
    private function handle_wallets_page() {
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/crypto-exchange/login'));
            exit;
        }
        
        $wallet = new Crypto_Exchange_Wallet();
        $wallet->render();
    }
    
    /**
     * Handle profile page
     */
    private function handle_profile_page() {
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/crypto-exchange/login'));
            exit;
        }
        
        $profile = new Crypto_Exchange_Profile();
        $profile->render();
    }
    
    /**
     * Handle KYC page
     */
    private function handle_kyc_page() {
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/crypto-exchange/login'));
            exit;
        }
        
        $kyc = new Crypto_Exchange_KYC();
        $kyc->render();
    }
    
    /**
     * AJAX login handler
     */
    public function ajax_login() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        $auth = new Crypto_Exchange_Auth();
        $result = $auth->login($_POST['email'], $_POST['password']);
        
        wp_send_json($result);
    }
    
    /**
     * AJAX register handler
     */
    public function ajax_register() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        $auth = new Crypto_Exchange_Auth();
        $result = $auth->register($_POST['email'], $_POST['password'], $_POST['first_name'], $_POST['last_name']);
        
        wp_send_json($result);
    }
    
    /**
     * AJAX logout handler
     */
    public function ajax_logout() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        wp_logout();
        wp_send_json_success('Logged out successfully');
    }
    
    /**
     * AJAX place order handler
     */
    public function ajax_place_order() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to place orders');
        }
        
        $trading = new Crypto_Exchange_Trading();
        $result = $trading->place_order($_POST);
        
        wp_send_json($result);
    }
    
    /**
     * AJAX cancel order handler
     */
    public function ajax_cancel_order() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to cancel orders');
        }
        
        $trading = new Crypto_Exchange_Trading();
        $result = $trading->cancel_order($_POST['order_id']);
        
        wp_send_json($result);
    }
    
    /**
     * AJAX get orders handler
     */
    public function ajax_get_orders() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to view orders');
        }
        
        $trading = new Crypto_Exchange_Trading();
        $result = $trading->get_user_orders(get_current_user_id());
        
        wp_send_json($result);
    }
    
    /**
     * AJAX get trades handler
     */
    public function ajax_get_trades() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to view trades');
        }
        
        $trading = new Crypto_Exchange_Trading();
        $result = $trading->get_user_trades(get_current_user_id());
        
        wp_send_json($result);
    }
    
    /**
     * AJAX get wallets handler
     */
    public function ajax_get_wallets() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to view wallets');
        }
        
        $wallet = new Crypto_Exchange_Wallet();
        $result = $wallet->get_user_wallets(get_current_user_id());
        
        wp_send_json($result);
    }
    
    /**
     * AJAX deposit handler
     */
    public function ajax_deposit() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to make deposits');
        }
        
        $wallet = new Crypto_Exchange_Wallet();
        $result = $wallet->create_deposit($_POST);
        
        wp_send_json($result);
    }
    
    /**
     * AJAX withdraw handler
     */
    public function ajax_withdraw() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to make withdrawals');
        }
        
        $wallet = new Crypto_Exchange_Wallet();
        $result = $wallet->create_withdrawal($_POST);
        
        wp_send_json($result);
    }
    
    /**
     * AJAX get market data handler
     */
    public function ajax_get_market_data() {
        $market_data = new Crypto_Exchange_Market_Data();
        $result = $market_data->get_all_market_data();
        
        wp_send_json($result);
    }
    
    /**
     * AJAX upload KYC handler
     */
    public function ajax_upload_kyc() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to upload documents');
        }
        
        $kyc = new Crypto_Exchange_KYC();
        $result = $kyc->upload_document($_FILES['document'], $_POST['document_type']);
        
        wp_send_json($result);
    }
}
