<?php
/**
 * AJAX Handlers for Crypto Exchange Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Ajax_Handlers {
    
    public function __construct() {
        add_action('wp_ajax_crypto_exchange_place_order', array($this, 'place_order'));
        add_action('wp_ajax_crypto_exchange_cancel_order', array($this, 'cancel_order'));
        add_action('wp_ajax_crypto_exchange_get_order_book', array($this, 'get_order_book'));
        add_action('wp_ajax_crypto_exchange_get_market_data', array($this, 'get_market_data'));
        add_action('wp_ajax_crypto_exchange_get_user_orders', array($this, 'get_user_orders'));
        add_action('wp_ajax_crypto_exchange_get_user_wallets', array($this, 'get_user_wallets'));
        add_action('wp_ajax_crypto_exchange_deposit', array($this, 'deposit'));
        add_action('wp_ajax_crypto_exchange_withdraw', array($this, 'withdraw'));
        add_action('wp_ajax_crypto_exchange_get_notifications', array($this, 'get_notifications'));
        add_action('wp_ajax_crypto_exchange_mark_notification_read', array($this, 'mark_notification_read'));
    }
    
    /**
     * Place order
     */
    public function place_order() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $user_id = get_current_user_id();
        $trading = new Crypto_Exchange_Trading();
        
        $order_data = array(
            'user_id' => $user_id,
            'trading_pair' => sanitize_text_field($_POST['trading_pair']),
            'order_type' => sanitize_text_field($_POST['order_type']),
            'side' => sanitize_text_field($_POST['side']),
            'amount' => floatval($_POST['amount']),
            'price' => floatval($_POST['price']),
            'stop_price' => floatval($_POST['stop_price'])
        );
        
        $result = $trading->place_order($order_data);
        
        if ($result) {
            wp_send_json_success('Order placed successfully');
        } else {
            wp_send_json_error('Failed to place order');
        }
    }
    
    /**
     * Cancel order
     */
    public function cancel_order() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $order_id = intval($_POST['order_id']);
        $trading = new Crypto_Exchange_Trading();
        
        $result = $trading->cancel_order($order_id);
        
        if ($result) {
            wp_send_json_success('Order cancelled successfully');
        } else {
            wp_send_json_error('Failed to cancel order');
        }
    }
    
    /**
     * Get order book
     */
    public function get_order_book() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        $trading_pair = sanitize_text_field($_POST['trading_pair']);
        $matching_engine = new Crypto_Exchange_Matching_Engine();
        
        $order_book = $matching_engine->get_order_book($trading_pair);
        
        wp_send_json_success($order_book);
    }
    
    /**
     * Get market data
     */
    public function get_market_data() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        $market_data = new Crypto_Exchange_Market_Data();
        $prices = $market_data->get_all_prices();
        
        // Convert to array format for frontend
        $formatted_prices = array();
        foreach ($prices as $pair => $data) {
            $formatted_prices[] = array(
                'symbol' => $pair,
                'price' => $data['price'],
                'change_24h' => $data['change_24h']
            );
        }
        
        wp_send_json_success($formatted_prices);
    }
    
    /**
     * Get user orders
     */
    public function get_user_orders() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $user_id = get_current_user_id();
        $trading = new Crypto_Exchange_Trading();
        $orders = $trading->get_user_orders($user_id);
        
        wp_send_json_success($orders);
    }
    
    /**
     * Get user wallets
     */
    public function get_user_wallets() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $user_id = get_current_user_id();
        $wallet = new Crypto_Exchange_Wallet();
        $wallets = $wallet->get_user_wallets($user_id);
        
        wp_send_json_success($wallets);
    }
    
    /**
     * Deposit
     */
    public function deposit() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $user_id = get_current_user_id();
        $currency = sanitize_text_field($_POST['currency']);
        $amount = floatval($_POST['amount']);
        
        $wallet = new Crypto_Exchange_Wallet();
        $result = $wallet->deposit($user_id, $currency, $amount);
        
        if ($result) {
            wp_send_json_success('Deposit request submitted successfully');
        } else {
            wp_send_json_error('Failed to submit deposit request');
        }
    }
    
    /**
     * Withdraw
     */
    public function withdraw() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $user_id = get_current_user_id();
        $currency = sanitize_text_field($_POST['currency']);
        $amount = floatval($_POST['amount']);
        $address = sanitize_text_field($_POST['address']);
        
        $wallet = new Crypto_Exchange_Wallet();
        $result = $wallet->withdraw($user_id, $currency, $amount, $address);
        
        if ($result) {
            wp_send_json_success('Withdrawal request submitted successfully');
        } else {
            wp_send_json_error('Failed to submit withdrawal request');
        }
    }
    
    /**
     * Get notifications
     */
    public function get_notifications() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $user_id = get_current_user_id();
        $notifications = new Crypto_Exchange_Notifications();
        $user_notifications = $notifications->get_user_notifications($user_id);
        
        wp_send_json_success($user_notifications);
    }
    
    /**
     * Mark notification as read
     */
    public function mark_notification_read() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $notification_id = intval($_POST['notification_id']);
        $notifications = new Crypto_Exchange_Notifications();
        $result = $notifications->mark_notification_read($notification_id);
        
        if ($result) {
            wp_send_json_success('Notification marked as read');
        } else {
            wp_send_json_error('Failed to mark notification as read');
        }
    }
}
