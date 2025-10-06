<?php
/**
 * Wallet API Management System
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Wallet_API {
    
    private $wpdb;
    private $wallet_providers;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->wallet_providers = new Crypto_Exchange_Wallet_Providers();
        
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('rest_api_init', array($this, 'register_api_routes'));
        add_action('wp_ajax_crypto_exchange_wallet_balance', array($this, 'get_wallet_balance'));
        add_action('wp_ajax_crypto_exchange_wallet_address', array($this, 'create_wallet_address'));
        add_action('wp_ajax_crypto_exchange_wallet_send', array($this, 'send_wallet_transaction'));
        add_action('wp_ajax_crypto_exchange_wallet_history', array($this, 'get_wallet_history'));
        add_action('wp_ajax_crypto_exchange_wallet_providers', array($this, 'get_wallet_providers'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_api_routes() {
        register_rest_route('crypto-exchange/v1', '/wallet/balance', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_balance'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route('crypto-exchange/v1', '/wallet/address', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_create_address'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route('crypto-exchange/v1', '/wallet/send', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_send_transaction'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route('crypto-exchange/v1', '/wallet/history', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_history'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route('crypto-exchange/v1', '/wallet/providers', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_providers'),
            'permission_callback' => array($this, 'check_permissions')
        ));
    }
    
    /**
     * Check API permissions
     */
    public function check_permissions($request) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return new WP_Error('unauthorized', 'Authentication required', array('status' => 401));
        }
        
        // Check API key if required
        $api_settings = get_option('crypto_exchange_api_settings', array());
        if ($api_settings['require_api_key'] ?? false) {
            $api_key = $request->get_header('X-API-Key');
            if (!$api_key || !$this->validate_api_key($api_key)) {
                return new WP_Error('invalid_api_key', 'Invalid API key', array('status' => 401));
            }
        }
        
        return true;
    }
    
    /**
     * Validate API key
     */
    private function validate_api_key($api_key) {
        $valid_keys = get_option('crypto_exchange_api_keys', array());
        return in_array($api_key, $valid_keys);
    }
    
    /**
     * REST API: Get wallet balance
     */
    public function rest_get_balance($request) {
        $user_id = get_current_user_id();
        $provider_id = $request->get_param('provider_id');
        $coin = $request->get_param('coin');
        
        try {
            if ($provider_id) {
                $provider = $this->wallet_providers->get_provider($provider_id);
                if (!$provider) {
                    return new WP_Error('provider_not_found', 'Provider not found', array('status' => 404));
                }
                
                $class_name = $this->wallet_providers->get_available_providers()[$provider->provider_type]['class'];
                $instance = new $class_name($provider);
                $balances = $instance->get_balances($user_id);
            } else {
                $balances = $this->get_all_balances($user_id);
            }
            
            if ($coin) {
                $balances = array_filter($balances, function($balance) use ($coin) {
                    return $balance['currency'] === $coin;
                });
            }
            
            return rest_ensure_response($balances);
        } catch (Exception $e) {
            return new WP_Error('balance_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * REST API: Create wallet address
     */
    public function rest_create_address($request) {
        $user_id = get_current_user_id();
        $provider_id = $request->get_param('provider_id');
        $coin = $request->get_param('coin');
        
        if (!$provider_id || !$coin) {
            return new WP_Error('missing_params', 'Provider ID and coin are required', array('status' => 400));
        }
        
        try {
            $provider = $this->wallet_providers->get_provider($provider_id);
            if (!$provider) {
                return new WP_Error('provider_not_found', 'Provider not found', array('status' => 404));
            }
            
            $class_name = $this->wallet_providers->get_available_providers()[$provider->provider_type]['class'];
            $instance = new $class_name($provider);
            $address = $instance->create_address($user_id, $coin);
            
            return rest_ensure_response($address);
        } catch (Exception $e) {
            return new WP_Error('address_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * REST API: Send transaction
     */
    public function rest_send_transaction($request) {
        $user_id = get_current_user_id();
        $provider_id = $request->get_param('provider_id');
        $to_address = $request->get_param('to_address');
        $amount = $request->get_param('amount');
        $coin = $request->get_param('coin');
        
        if (!$provider_id || !$to_address || !$amount || !$coin) {
            return new WP_Error('missing_params', 'All parameters are required', array('status' => 400));
        }
        
        try {
            $provider = $this->wallet_providers->get_provider($provider_id);
            if (!$provider) {
                return new WP_Error('provider_not_found', 'Provider not found', array('status' => 404));
            }
            
            $class_name = $this->wallet_providers->get_available_providers()[$provider->provider_type]['class'];
            $instance = new $class_name($provider);
            $transaction = $instance->send_transaction($user_id, $to_address, $amount, $coin);
            
            return rest_ensure_response($transaction);
        } catch (Exception $e) {
            return new WP_Error('transaction_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * REST API: Get transaction history
     */
    public function rest_get_history($request) {
        $user_id = get_current_user_id();
        $provider_id = $request->get_param('provider_id');
        $coin = $request->get_param('coin');
        $limit = $request->get_param('limit') ?: 50;
        
        try {
            if ($provider_id) {
                $provider = $this->wallet_providers->get_provider($provider_id);
                if (!$provider) {
                    return new WP_Error('provider_not_found', 'Provider not found', array('status' => 404));
                }
                
                $class_name = $this->wallet_providers->get_available_providers()[$provider->provider_type]['class'];
                $instance = new $class_name($provider);
                $transactions = $instance->get_transaction_history($user_id, $coin, $limit);
            } else {
                $transactions = $this->get_all_transactions($user_id, $coin, $limit);
            }
            
            return rest_ensure_response($transactions);
        } catch (Exception $e) {
            return new WP_Error('history_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * REST API: Get wallet providers
     */
    public function rest_get_providers($request) {
        $providers = $this->wallet_providers->get_active_providers();
        
        // Remove sensitive information
        foreach ($providers as $provider) {
            unset($provider->api_key);
            unset($provider->api_secret);
            unset($provider->api_passphrase);
        }
        
        return rest_ensure_response($providers);
    }
    
    /**
     * AJAX: Get wallet balance
     */
    public function get_wallet_balance() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in');
        }
        
        $user_id = get_current_user_id();
        $provider_id = intval($_POST['provider_id']);
        
        try {
            if ($provider_id) {
                $provider = $this->wallet_providers->get_provider($provider_id);
                if (!$provider) {
                    wp_send_json_error('Provider not found');
                }
                
                $class_name = $this->wallet_providers->get_available_providers()[$provider->provider_type]['class'];
                $instance = new $class_name($provider);
                $balances = $instance->get_balances($user_id);
            } else {
                $balances = $this->get_all_balances($user_id);
            }
            
            wp_send_json_success($balances);
        } catch (Exception $e) {
            wp_send_json_error('Failed to get balances: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Create wallet address
     */
    public function create_wallet_address() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in');
        }
        
        $user_id = get_current_user_id();
        $provider_id = intval($_POST['provider_id']);
        $coin = sanitize_text_field($_POST['coin']);
        
        try {
            $provider = $this->wallet_providers->get_provider($provider_id);
            if (!$provider) {
                wp_send_json_error('Provider not found');
            }
            
            $class_name = $this->wallet_providers->get_available_providers()[$provider->provider_type]['class'];
            $instance = new $class_name($provider);
            $address = $instance->create_address($user_id, $coin);
            
            wp_send_json_success($address);
        } catch (Exception $e) {
            wp_send_json_error('Failed to create address: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Send wallet transaction
     */
    public function send_wallet_transaction() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in');
        }
        
        $user_id = get_current_user_id();
        $provider_id = intval($_POST['provider_id']);
        $to_address = sanitize_text_field($_POST['to_address']);
        $amount = floatval($_POST['amount']);
        $coin = sanitize_text_field($_POST['coin']);
        
        try {
            $provider = $this->wallet_providers->get_provider($provider_id);
            if (!$provider) {
                wp_send_json_error('Provider not found');
            }
            
            $class_name = $this->wallet_providers->get_available_providers()[$provider->provider_type]['class'];
            $instance = new $class_name($provider);
            $transaction = $instance->send_transaction($user_id, $to_address, $amount, $coin);
            
            wp_send_json_success($transaction);
        } catch (Exception $e) {
            wp_send_json_error('Failed to send transaction: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Get wallet history
     */
    public function get_wallet_history() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in');
        }
        
        $user_id = get_current_user_id();
        $provider_id = intval($_POST['provider_id']);
        $coin = sanitize_text_field($_POST['coin']);
        $limit = intval($_POST['limit']) ?: 50;
        
        try {
            if ($provider_id) {
                $provider = $this->wallet_providers->get_provider($provider_id);
                if (!$provider) {
                    wp_send_json_error('Provider not found');
                }
                
                $class_name = $this->wallet_providers->get_available_providers()[$provider->provider_type]['class'];
                $instance = new $class_name($provider);
                $transactions = $instance->get_transaction_history($user_id, $coin, $limit);
            } else {
                $transactions = $this->get_all_transactions($user_id, $coin, $limit);
            }
            
            wp_send_json_success($transactions);
        } catch (Exception $e) {
            wp_send_json_error('Failed to get history: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Get wallet providers
     */
    public function get_wallet_providers() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        $providers = $this->wallet_providers->get_active_providers();
        
        // Remove sensitive information
        foreach ($providers as $provider) {
            unset($provider->api_key);
            unset($provider->api_secret);
            unset($provider->api_passphrase);
        }
        
        wp_send_json_success($providers);
    }
    
    /**
     * Get all balances from all providers
     */
    private function get_all_balances($user_id) {
        $providers = $this->wallet_providers->get_active_providers();
        $all_balances = array();
        
        foreach ($providers as $provider) {
            try {
                $class_name = $this->wallet_providers->get_available_providers()[$provider->provider_type]['class'];
                $instance = new $class_name($provider);
                $balances = $instance->get_balances($user_id);
                $all_balances = array_merge($all_balances, $balances);
            } catch (Exception $e) {
                error_log('Failed to get balances from provider ' . $provider->name . ': ' . $e->getMessage());
            }
        }
        
        return $all_balances;
    }
    
    /**
     * Get all transactions from all providers
     */
    private function get_all_transactions($user_id, $coin = null, $limit = 50) {
        $providers = $this->wallet_providers->get_active_providers();
        $all_transactions = array();
        
        foreach ($providers as $provider) {
            try {
                $class_name = $this->wallet_providers->get_available_providers()[$provider->provider_type]['class'];
                $instance = new $class_name($provider);
                $transactions = $instance->get_transaction_history($user_id, $coin, $limit);
                $all_transactions = array_merge($all_transactions, $transactions);
            } catch (Exception $e) {
                error_log('Failed to get transactions from provider ' . $provider->name . ': ' . $e->getMessage());
            }
        }
        
        // Sort by timestamp and limit
        usort($all_transactions, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return array_slice($all_transactions, 0, $limit);
    }
    
    /**
     * Get best provider for coin
     */
    public function get_best_provider_for_coin($coin, $operation = 'deposit') {
        return $this->wallet_providers->get_best_provider_for_coin($coin, $operation);
    }
    
    /**
     * Get provider statistics
     */
    public function get_provider_statistics() {
        return $this->wallet_providers->get_provider_stats();
    }
}