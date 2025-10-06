<?php
/**
 * User Actions System - Comprehensive Action Buttons and Flows
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_User_Actions {
    
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        add_action('wp_ajax_crypto_exchange_user_action', array($this, 'handle_user_action'));
        add_action('wp_ajax_crypto_exchange_get_action_data', array($this, 'get_action_data'));
        add_action('wp_ajax_crypto_exchange_confirm_action', array($this, 'confirm_action'));
        add_action('wp_ajax_crypto_exchange_cancel_action', array($this, 'cancel_action'));
        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script('crypto-user-actions', CRYPTO_EXCHANGE_PLUGIN_URL . 'assets/js/user-actions.js', array('jquery'), CRYPTO_EXCHANGE_VERSION, true);
        wp_enqueue_style('crypto-user-actions', CRYPTO_EXCHANGE_PLUGIN_URL . 'assets/css/user-actions.css', array(), CRYPTO_EXCHANGE_VERSION);
        
        wp_localize_script('crypto-user-actions', 'crypto_actions', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crypto_exchange_nonce'),
            'user_id' => get_current_user_id(),
            'is_logged_in' => is_user_logged_in()
        ));
    }
    
    /**
     * Handle user action requests
     */
    public function handle_user_action() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $action = sanitize_text_field($_POST['action_type']);
        $data = $_POST['action_data'] ?? array();
        
        switch ($action) {
            case 'deposit':
                return $this->handle_deposit_action($data);
            case 'withdraw':
                return $this->handle_withdraw_action($data);
            case 'trade':
                return $this->handle_trade_action($data);
            case 'kyc_verify':
                return $this->handle_kyc_verify_action($data);
            case 'wallet_manage':
                return $this->handle_wallet_manage_action($data);
            case 'security_settings':
                return $this->handle_security_settings_action($data);
            case 'order_manage':
                return $this->handle_order_manage_action($data);
            case 'account_settings':
                return $this->handle_account_settings_action($data);
            case 'notifications':
                return $this->handle_notifications_action($data);
            default:
                wp_send_json_error('Invalid action type');
        }
    }
    
    /**
     * Handle deposit action
     */
    private function handle_deposit_action($data) {
        $step = sanitize_text_field($data['step']);
        $user_id = get_current_user_id();
        
        switch ($step) {
            case 'initiate':
                return $this->initiate_deposit($data);
            case 'select_method':
                return $this->select_deposit_method($data);
            case 'enter_amount':
                return $this->enter_deposit_amount($data);
            case 'confirm':
                return $this->confirm_deposit($data);
            case 'process':
                return $this->process_deposit($data);
            default:
                wp_send_json_error('Invalid deposit step');
        }
    }
    
    /**
     * Initiate deposit process
     */
    private function initiate_deposit($data) {
        $currency = sanitize_text_field($data['currency']);
        
        // Get user's wallet for the currency
        $wallet = $this->get_user_wallet($currency);
        
        if (!$wallet) {
            wp_send_json_error('Wallet not found for ' . $currency);
        }
        
        // Get available deposit methods
        $methods = $this->get_deposit_methods($currency);
        
        $response = array(
            'step' => 'select_method',
            'currency' => $currency,
            'wallet_address' => $wallet['address'],
            'methods' => $methods,
            'min_amount' => $this->get_min_deposit_amount($currency),
            'max_amount' => $this->get_max_deposit_amount($currency),
            'fee' => $this->get_deposit_fee($currency),
            'estimated_time' => $this->get_deposit_estimated_time($currency)
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Select deposit method
     */
    private function select_deposit_method($data) {
        $method = sanitize_text_field($data['method']);
        $currency = sanitize_text_field($data['currency']);
        
        $method_info = $this->get_deposit_method_info($method, $currency);
        
        $response = array(
            'step' => 'enter_amount',
            'method' => $method,
            'currency' => $currency,
            'method_info' => $method_info,
            'min_amount' => $method_info['min_amount'],
            'max_amount' => $method_info['max_amount'],
            'fee' => $method_info['fee'],
            'estimated_time' => $method_info['estimated_time']
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Enter deposit amount
     */
    private function enter_deposit_amount($data) {
        $amount = floatval($data['amount']);
        $method = sanitize_text_field($data['method']);
        $currency = sanitize_text_field($data['currency']);
        
        // Validate amount
        $validation = $this->validate_deposit_amount($amount, $method, $currency);
        
        if (!$validation['valid']) {
            wp_send_json_error($validation['message']);
        }
        
        // Calculate fees and totals
        $fee = $this->calculate_deposit_fee($amount, $method, $currency);
        $total = $amount + $fee;
        
        $response = array(
            'step' => 'confirm',
            'amount' => $amount,
            'method' => $method,
            'currency' => $currency,
            'fee' => $fee,
            'total' => $total,
            'wallet_address' => $this->get_deposit_address($method, $currency),
            'qr_code' => $this->generate_qr_code($method, $currency, $amount),
            'instructions' => $this->get_deposit_instructions($method, $currency)
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Confirm deposit
     */
    private function confirm_deposit($data) {
        $amount = floatval($data['amount']);
        $method = sanitize_text_field($data['method']);
        $currency = sanitize_text_field($data['currency']);
        $user_id = get_current_user_id();
        
        // Create deposit record
        $deposit_id = $this->create_deposit_record($user_id, $currency, $amount, $method);
        
        $response = array(
            'step' => 'process',
            'deposit_id' => $deposit_id,
            'status' => 'pending',
            'tracking_url' => $this->get_deposit_tracking_url($deposit_id),
            'estimated_confirmation' => $this->get_deposit_confirmation_time($method, $currency)
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Process deposit
     */
    private function process_deposit($data) {
        $deposit_id = intval($data['deposit_id']);
        
        // Update deposit status
        $this->update_deposit_status($deposit_id, 'processing');
        
        $response = array(
            'step' => 'complete',
            'deposit_id' => $deposit_id,
            'status' => 'processing',
            'message' => 'Deposit is being processed. You will receive a notification when confirmed.'
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Handle withdraw action
     */
    private function handle_withdraw_action($data) {
        $step = sanitize_text_field($data['step']);
        
        switch ($step) {
            case 'initiate':
                return $this->initiate_withdraw($data);
            case 'select_method':
                return $this->select_withdraw_method($data);
            case 'enter_amount':
                return $this->enter_withdraw_amount($data);
            case 'verify_identity':
                return $this->verify_withdraw_identity($data);
            case 'confirm':
                return $this->confirm_withdraw($data);
            case 'process':
                return $this->process_withdraw($data);
            default:
                wp_send_json_error('Invalid withdraw step');
        }
    }
    
    /**
     * Initiate withdraw process
     */
    private function initiate_withdraw($data) {
        $currency = sanitize_text_field($data['currency']);
        $user_id = get_current_user_id();
        
        // Check KYC status
        $kyc_status = $this->get_user_kyc_status($user_id);
        if ($kyc_status['level'] < 1) {
            wp_send_json_error('KYC verification required for withdrawals');
        }
        
        // Get user balance
        $balance = $this->get_user_balance($user_id, $currency);
        
        // Get available withdraw methods
        $methods = $this->get_withdraw_methods($currency, $kyc_status['level']);
        
        $response = array(
            'step' => 'select_method',
            'currency' => $currency,
            'balance' => $balance,
            'methods' => $methods,
            'kyc_level' => $kyc_status['level'],
            'daily_limit' => $this->get_daily_withdraw_limit($user_id, $currency),
            'min_amount' => $this->get_min_withdraw_amount($currency),
            'max_amount' => $this->get_max_withdraw_amount($currency, $kyc_status['level'])
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Select withdraw method
     */
    private function select_withdraw_method($data) {
        $method = sanitize_text_field($data['method']);
        $currency = sanitize_text_field($data['currency']);
        
        $method_info = $this->get_withdraw_method_info($method, $currency);
        
        $response = array(
            'step' => 'enter_amount',
            'method' => $method,
            'currency' => $currency,
            'method_info' => $method_info,
            'min_amount' => $method_info['min_amount'],
            'max_amount' => $method_info['max_amount'],
            'fee' => $method_info['fee'],
            'estimated_time' => $method_info['estimated_time']
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Enter withdraw amount
     */
    private function enter_withdraw_amount($data) {
        $amount = floatval($data['amount']);
        $method = sanitize_text_field($data['method']);
        $currency = sanitize_text_field($data['currency']);
        $user_id = get_current_user_id();
        
        // Validate amount
        $validation = $this->validate_withdraw_amount($amount, $method, $currency, $user_id);
        
        if (!$validation['valid']) {
            wp_send_json_error($validation['message']);
        }
        
        // Calculate fees and totals
        $fee = $this->calculate_withdraw_fee($amount, $method, $currency);
        $total = $amount + $fee;
        
        $response = array(
            'step' => 'verify_identity',
            'amount' => $amount,
            'method' => $method,
            'currency' => $currency,
            'fee' => $fee,
            'total' => $total,
            'requires_2fa' => $this->requires_2fa_for_withdraw($user_id),
            'requires_sms' => $this->requires_sms_for_withdraw($user_id)
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Verify withdraw identity
     */
    private function verify_withdraw_identity($data) {
        $user_id = get_current_user_id();
        
        // Check 2FA
        if ($this->requires_2fa_for_withdraw($user_id)) {
            $code = sanitize_text_field($data['2fa_code']);
            if (!$this->verify_2fa_code($user_id, $code)) {
                wp_send_json_error('Invalid 2FA code');
            }
        }
        
        // Check SMS
        if ($this->requires_sms_for_withdraw($user_id)) {
            $code = sanitize_text_field($data['sms_code']);
            if (!$this->verify_sms_code($user_id, $code)) {
                wp_send_json_error('Invalid SMS code');
            }
        }
        
        $response = array(
            'step' => 'confirm',
            'verified' => true
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Confirm withdraw
     */
    private function confirm_withdraw($data) {
        $amount = floatval($data['amount']);
        $method = sanitize_text_field($data['method']);
        $currency = sanitize_text_field($data['currency']);
        $address = sanitize_text_field($data['address']);
        $user_id = get_current_user_id();
        
        // Create withdraw record
        $withdraw_id = $this->create_withdraw_record($user_id, $currency, $amount, $method, $address);
        
        $response = array(
            'step' => 'process',
            'withdraw_id' => $withdraw_id,
            'status' => 'pending',
            'tracking_url' => $this->get_withdraw_tracking_url($withdraw_id),
            'estimated_completion' => $this->get_withdraw_completion_time($method, $currency)
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Process withdraw
     */
    private function process_withdraw($data) {
        $withdraw_id = intval($data['withdraw_id']);
        
        // Update withdraw status
        $this->update_withdraw_status($withdraw_id, 'processing');
        
        $response = array(
            'step' => 'complete',
            'withdraw_id' => $withdraw_id,
            'status' => 'processing',
            'message' => 'Withdrawal is being processed. You will receive a notification when completed.'
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Handle trade action
     */
    private function handle_trade_action($data) {
        $step = sanitize_text_field($data['step']);
        
        switch ($step) {
            case 'initiate':
                return $this->initiate_trade($data);
            case 'select_pair':
                return $this->select_trading_pair($data);
            case 'choose_order_type':
                return $this->choose_order_type($data);
            case 'enter_details':
                return $this->enter_trade_details($data);
            case 'review':
                return $this->review_trade($data);
            case 'confirm':
                return $this->confirm_trade($data);
            case 'execute':
                return $this->execute_trade($data);
            default:
                wp_send_json_error('Invalid trade step');
        }
    }
    
    /**
     * Initiate trade
     */
    private function initiate_trade($data) {
        $user_id = get_current_user_id();
        
        // Check trading permissions
        $trading_status = $this->get_user_trading_status($user_id);
        if (!$trading_status['can_trade']) {
            wp_send_json_error($trading_status['message']);
        }
        
        // Get available trading pairs
        $pairs = $this->get_available_trading_pairs();
        
        $response = array(
            'step' => 'select_pair',
            'pairs' => $pairs,
            'trading_status' => $trading_status,
            'balance' => $this->get_user_trading_balance($user_id)
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Select trading pair
     */
    private function select_trading_pair($data) {
        $pair = sanitize_text_field($data['pair']);
        
        // Get pair information
        $pair_info = $this->get_trading_pair_info($pair);
        
        $response = array(
            'step' => 'choose_order_type',
            'pair' => $pair,
            'pair_info' => $pair_info,
            'current_price' => $this->get_current_price($pair),
            'order_types' => $this->get_available_order_types($pair)
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Choose order type
     */
    private function choose_order_type($data) {
        $pair = sanitize_text_field($data['pair']);
        $order_type = sanitize_text_field($data['order_type']);
        
        $response = array(
            'step' => 'enter_details',
            'pair' => $pair,
            'order_type' => $order_type,
            'order_type_info' => $this->get_order_type_info($order_type),
            'price_info' => $this->get_price_info($pair, $order_type)
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Enter trade details
     */
    private function enter_trade_details($data) {
        $pair = sanitize_text_field($data['pair']);
        $order_type = sanitize_text_field($data['order_type']);
        $side = sanitize_text_field($data['side']);
        $amount = floatval($data['amount']);
        $price = floatval($data['price'] ?? 0);
        
        // Validate trade details
        $validation = $this->validate_trade_details($pair, $order_type, $side, $amount, $price);
        
        if (!$validation['valid']) {
            wp_send_json_error($validation['message']);
        }
        
        // Calculate estimated values
        $estimated = $this->calculate_trade_estimated($pair, $side, $amount, $price);
        
        $response = array(
            'step' => 'review',
            'pair' => $pair,
            'order_type' => $order_type,
            'side' => $side,
            'amount' => $amount,
            'price' => $price,
            'estimated' => $estimated,
            'fee' => $this->calculate_trading_fee($amount, $price, $side)
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Review trade
     */
    private function review_trade($data) {
        $response = array(
            'step' => 'confirm',
            'trade_summary' => $data,
            'risk_warning' => $this->get_risk_warning($data),
            'terms_accepted' => false
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Confirm trade
     */
    private function confirm_trade($data) {
        $user_id = get_current_user_id();
        
        // Check terms acceptance
        if (!$data['terms_accepted']) {
            wp_send_json_error('You must accept the trading terms');
        }
        
        // Create order
        $order_id = $this->create_trade_order($user_id, $data);
        
        $response = array(
            'step' => 'execute',
            'order_id' => $order_id,
            'status' => 'pending'
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Execute trade
     */
    private function execute_trade($data) {
        $order_id = intval($data['order_id']);
        
        // Execute the order
        $result = $this->execute_order($order_id);
        
        $response = array(
            'step' => 'complete',
            'order_id' => $order_id,
            'result' => $result,
            'status' => $result['status']
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Handle KYC verification action
     */
    private function handle_kyc_verify_action($data) {
        $step = sanitize_text_field($data['step']);
        
        switch ($step) {
            case 'initiate':
                return $this->initiate_kyc_verify($data);
            case 'upload_documents':
                return $this->upload_kyc_documents($data);
            case 'verify_identity':
                return $this->verify_kyc_identity($data);
            case 'submit':
                return $this->submit_kyc_application($data);
            case 'review':
                return $this->review_kyc_status($data);
            default:
                wp_send_json_error('Invalid KYC step');
        }
    }
    
    /**
     * Initiate KYC verification
     */
    private function initiate_kyc_verify($data) {
        $user_id = get_current_user_id();
        
        // Check current KYC status
        $kyc_status = $this->get_user_kyc_status($user_id);
        
        $response = array(
            'step' => 'upload_documents',
            'current_level' => $kyc_status['level'],
            'required_documents' => $this->get_required_kyc_documents($kyc_status['level']),
            'benefits' => $this->get_kyc_benefits($kyc_status['level'])
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Upload KYC documents
     */
    private function upload_kyc_documents($data) {
        $user_id = get_current_user_id();
        $documents = $data['documents'];
        
        // Validate and upload documents
        $upload_result = $this->upload_kyc_document_files($user_id, $documents);
        
        if (!$upload_result['success']) {
            wp_send_json_error($upload_result['message']);
        }
        
        $response = array(
            'step' => 'verify_identity',
            'uploaded_documents' => $upload_result['documents'],
            'verification_methods' => $this->get_verification_methods()
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Verify KYC identity
     */
    private function verify_kyc_identity($data) {
        $user_id = get_current_user_id();
        $method = sanitize_text_field($data['method']);
        $verification_data = $data['verification_data'];
        
        // Process verification
        $verification_result = $this->process_kyc_verification($user_id, $method, $verification_data);
        
        if (!$verification_result['success']) {
            wp_send_json_error($verification_result['message']);
        }
        
        $response = array(
            'step' => 'submit',
            'verification_result' => $verification_result,
            'application_id' => $verification_result['application_id']
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Submit KYC application
     */
    private function submit_kyc_application($data) {
        $user_id = get_current_user_id();
        $application_id = intval($data['application_id']);
        
        // Submit application
        $submit_result = $this->submit_kyc_application_to_review($user_id, $application_id);
        
        $response = array(
            'step' => 'review',
            'application_id' => $application_id,
            'status' => 'submitted',
            'estimated_review_time' => $this->get_kyc_review_time(),
            'tracking_url' => $this->get_kyc_tracking_url($application_id)
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Review KYC status
     */
    private function review_kyc_status($data) {
        $user_id = get_current_user_id();
        
        $kyc_status = $this->get_user_kyc_status($user_id);
        
        $response = array(
            'step' => 'complete',
            'kyc_status' => $kyc_status,
            'next_steps' => $this->get_kyc_next_steps($kyc_status['level'])
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Get action data
     */
    public function get_action_data() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $action_type = sanitize_text_field($_POST['action_type']);
        $user_id = get_current_user_id();
        
        switch ($action_type) {
            case 'dashboard_stats':
                return $this->get_dashboard_stats($user_id);
            case 'wallet_balances':
                return $this->get_wallet_balances($user_id);
            case 'recent_activities':
                return $this->get_recent_activities($user_id);
            case 'pending_actions':
                return $this->get_pending_actions($user_id);
            default:
                wp_send_json_error('Invalid action type');
        }
    }
    
    /**
     * Confirm action
     */
    public function confirm_action() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $action_id = intval($_POST['action_id']);
        $confirmation_code = sanitize_text_field($_POST['confirmation_code']);
        
        $result = $this->process_action_confirmation($action_id, $confirmation_code);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Cancel action
     */
    public function cancel_action() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $action_id = intval($_POST['action_id']);
        
        $result = $this->process_action_cancellation($action_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    // Helper methods for data retrieval and validation
    private function get_user_wallet($currency) {
        // Implementation for getting user wallet
        return array('address' => 'sample_address_' . $currency);
    }
    
    private function get_deposit_methods($currency) {
        return array(
            array('id' => 'crypto', 'name' => 'Cryptocurrency', 'icon' => 'crypto-icon'),
            array('id' => 'bank', 'name' => 'Bank Transfer', 'icon' => 'bank-icon'),
            array('id' => 'card', 'name' => 'Credit/Debit Card', 'icon' => 'card-icon')
        );
    }
    
    private function get_min_deposit_amount($currency) {
        return 0.001;
    }
    
    private function get_max_deposit_amount($currency) {
        return 1000000;
    }
    
    private function get_deposit_fee($currency) {
        return 0.0005;
    }
    
    private function get_deposit_estimated_time($currency) {
        return '10-30 minutes';
    }
    
    // Add more helper methods as needed...
}
