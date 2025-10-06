<?php
/**
 * REST API class for Crypto Exchange Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_API {
    
    private $namespace = 'crypto-exchange/v1';
    
    public function __construct() {
        // Constructor
    }
    
    /**
     * Initialize REST API
     */
    public function init() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Market data endpoints
        register_rest_route($this->namespace, '/market-data', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_market_data'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route($this->namespace, '/market-data/(?P<pair>[a-zA-Z0-9/]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_pair_data'),
            'permission_callback' => '__return_true'
        ));
        
        // Trading endpoints
        register_rest_route($this->namespace, '/orders', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_orders'),
            'permission_callback' => array($this, 'check_user_permission')
        ));
        
        register_rest_route($this->namespace, '/orders', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_order'),
            'permission_callback' => array($this, 'check_user_permission')
        ));
        
        register_rest_route($this->namespace, '/orders/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'cancel_order'),
            'permission_callback' => array($this, 'check_user_permission')
        ));
        
        // Wallet endpoints
        register_rest_route($this->namespace, '/wallets', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_wallets'),
            'permission_callback' => array($this, 'check_user_permission')
        ));
        
        register_rest_route($this->namespace, '/wallets/balance/(?P<currency>[a-zA-Z]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_balance'),
            'permission_callback' => array($this, 'check_user_permission')
        ));
        
        // User endpoints
        register_rest_route($this->namespace, '/user/profile', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_profile'),
            'permission_callback' => array($this, 'check_user_permission')
        ));
        
        register_rest_route($this->namespace, '/user/kyc', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_kyc_status'),
            'permission_callback' => array($this, 'check_user_permission')
        ));
    }
    
    /**
     * Check user permission
     */
    public function check_user_permission() {
        return is_user_logged_in();
    }
    
    /**
     * Get market data
     */
    public function get_market_data($request) {
        $market_data = new Crypto_Exchange_Market_Data();
        $data = $market_data->get_all_market_data();
        
        return rest_ensure_response($data);
    }
    
    /**
     * Get pair data
     */
    public function get_pair_data($request) {
        $pair = $request['pair'];
        $market_data = new Crypto_Exchange_Market_Data();
        $data = $market_data->get_pair_data($pair);
        
        if (!$data) {
            return new WP_Error('pair_not_found', 'Trading pair not found', array('status' => 404));
        }
        
        return rest_ensure_response($data);
    }
    
    /**
     * Get user orders
     */
    public function get_orders($request) {
        $user_id = get_current_user_id();
        $trading = new Crypto_Exchange_Trading();
        $orders = $trading->get_user_orders($user_id);
        
        return rest_ensure_response($orders);
    }
    
    /**
     * Create order
     */
    public function create_order($request) {
        $user_id = get_current_user_id();
        $trading = new Crypto_Exchange_Trading();
        
        $data = $request->get_json_params();
        $result = $trading->place_order($data);
        
        if ($result['success']) {
            return rest_ensure_response($result);
        } else {
            return new WP_Error('order_failed', $result['message'], array('status' => 400));
        }
    }
    
    /**
     * Cancel order
     */
    public function cancel_order($request) {
        $order_id = $request['id'];
        $trading = new Crypto_Exchange_Trading();
        
        $result = $trading->cancel_order($order_id);
        
        if ($result['success']) {
            return rest_ensure_response($result);
        } else {
            return new WP_Error('cancel_failed', $result['message'], array('status' => 400));
        }
    }
    
    /**
     * Get user wallets
     */
    public function get_wallets($request) {
        $user_id = get_current_user_id();
        $wallet = new Crypto_Exchange_Wallet();
        $wallets = $wallet->get_user_wallets($user_id);
        
        return rest_ensure_response($wallets);
    }
    
    /**
     * Get wallet balance
     */
    public function get_balance($request) {
        $user_id = get_current_user_id();
        $currency = $request['currency'];
        $wallet = new Crypto_Exchange_Wallet();
        $balance = $wallet->get_balance($user_id, $currency);
        
        return rest_ensure_response(array('balance' => $balance));
    }
    
    /**
     * Get user profile
     */
    public function get_user_profile($request) {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        $profile = array(
            'id' => $user->ID,
            'email' => $user->user_email,
            'first_name' => get_user_meta($user_id, 'first_name', true),
            'last_name' => get_user_meta($user_id, 'last_name', true),
            'display_name' => $user->display_name,
            'registered' => $user->user_registered
        );
        
        return rest_ensure_response($profile);
    }
    
    /**
     * Get KYC status
     */
    public function get_kyc_status($request) {
        $user_id = get_current_user_id();
        $kyc = new Crypto_Exchange_KYC();
        $status = $kyc->get_kyc_status($user_id);
        
        return rest_ensure_response($status);
    }
}
