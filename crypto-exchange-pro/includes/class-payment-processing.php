<?php
/**
 * Real Payment Processing with Stripe and Bank Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Payment_Processing {
    
    private $wpdb;
    private $stripe_config;
    private $bank_config;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->init_payment_config();
        add_action('crypto_exchange_process_payments', array($this, 'process_pending_payments'));
        add_action('crypto_exchange_sync_bank_transactions', array($this, 'sync_bank_transactions'));
        add_action('wp_ajax_crypto_exchange_create_payment_intent', array($this, 'create_payment_intent'));
        add_action('wp_ajax_crypto_exchange_confirm_payment', array($this, 'confirm_payment'));
        add_action('wp_ajax_crypto_exchange_create_bank_transfer', array($this, 'create_bank_transfer'));
        add_action('wp_ajax_crypto_exchange_verify_bank_transfer', array($this, 'verify_bank_transfer'));
        
        if (!wp_next_scheduled('crypto_exchange_process_payments')) {
            wp_schedule_event(time(), 'every_minute', 'crypto_exchange_process_payments');
        }
        
        if (!wp_next_scheduled('crypto_exchange_sync_bank_transactions')) {
            wp_schedule_event(time(), 'every_5_minutes', 'crypto_exchange_sync_bank_transactions');
        }
    }
    
    /**
     * Initialize payment configuration
     */
    private function init_payment_config() {
        $this->stripe_config = array(
            'publishable_key' => get_option('crypto_exchange_stripe_publishable_key', ''),
            'secret_key' => get_option('crypto_exchange_stripe_secret_key', ''),
            'webhook_secret' => get_option('crypto_exchange_stripe_webhook_secret', ''),
            'currency' => 'usd',
            'enabled' => get_option('crypto_exchange_stripe_enabled', false)
        );
        
        $this->bank_config = array(
            'api_key' => get_option('crypto_exchange_bank_api_key', ''),
            'api_secret' => get_option('crypto_exchange_bank_api_secret', ''),
            'webhook_secret' => get_option('crypto_exchange_bank_webhook_secret', ''),
            'enabled' => get_option('crypto_exchange_bank_enabled', false)
        );
    }
    
    /**
     * Process pending payments
     */
    public function process_pending_payments() {
        $pending_payments = $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_payments 
             WHERE status = 'pending' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        
        foreach ($pending_payments as $payment) {
            $this->process_payment($payment);
        }
    }
    
    /**
     * Process individual payment
     */
    private function process_payment($payment) {
        switch ($payment->payment_method) {
            case 'stripe':
                $this->process_stripe_payment($payment);
                break;
            case 'bank_transfer':
                $this->process_bank_transfer($payment);
                break;
            case 'wire_transfer':
                $this->process_wire_transfer($payment);
                break;
            case 'ach':
                $this->process_ach_payment($payment);
                break;
        }
    }
    
    /**
     * Process Stripe payment
     */
    private function process_stripe_payment($payment) {
        if (!$this->stripe_config['enabled']) {
            return;
        }
        
        try {
            \Stripe\Stripe::setApiKey($this->stripe_config['secret_key']);
            
            $intent = \Stripe\PaymentIntent::retrieve($payment->external_id);
            
            if ($intent->status === 'succeeded') {
                $this->complete_payment($payment, $intent->id);
            } elseif ($intent->status === 'requires_payment_method') {
                $this->fail_payment($payment, 'Payment method required');
            } elseif ($intent->status === 'canceled') {
                $this->fail_payment($payment, 'Payment canceled');
            }
        } catch (Exception $e) {
            error_log('Stripe payment error: ' . $e->getMessage());
            $this->fail_payment($payment, $e->getMessage());
        }
    }
    
    /**
     * Process bank transfer
     */
    private function process_bank_transfer($payment) {
        if (!$this->bank_config['enabled']) {
            return;
        }
        
        // Check if transfer has been received
        $transfer_status = $this->check_bank_transfer_status($payment->external_id);
        
        if ($transfer_status === 'completed') {
            $this->complete_payment($payment, $payment->external_id);
        } elseif ($transfer_status === 'failed') {
            $this->fail_payment($payment, 'Bank transfer failed');
        }
    }
    
    /**
     * Process wire transfer
     */
    private function process_wire_transfer($payment) {
        // Wire transfers are typically manual and require admin approval
        $this->update_payment_status($payment->id, 'pending_approval');
    }
    
    /**
     * Process ACH payment
     */
    private function process_ach_payment($payment) {
        if (!$this->stripe_config['enabled']) {
            return;
        }
        
        try {
            \Stripe\Stripe::setApiKey($this->stripe_config['secret_key']);
            
            $payment_method = \Stripe\PaymentMethod::retrieve($payment->external_id);
            
            if ($payment_method->type === 'us_bank_account') {
                $this->complete_payment($payment, $payment->external_id);
            }
        } catch (Exception $e) {
            error_log('ACH payment error: ' . $e->getMessage());
            $this->fail_payment($payment, $e->getMessage());
        }
    }
    
    /**
     * Create payment intent
     */
    public function create_payment_intent() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $amount = floatval($_POST['amount']);
        $currency = sanitize_text_field($_POST['currency']);
        $payment_method = sanitize_text_field($_POST['payment_method']);
        
        if (!$this->stripe_config['enabled']) {
            wp_send_json_error('Stripe not enabled');
        }
        
        try {
            \Stripe\Stripe::setApiKey($this->stripe_config['secret_key']);
            
            $intent = \Stripe\PaymentIntent::create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => strtolower($currency),
                'payment_method_types' => ['card'],
                'metadata' => [
                    'user_id' => $user_id,
                    'type' => 'deposit'
                ]
            ]);
            
            // Create payment record
            $payment_id = $this->create_payment_record($user_id, $amount, $currency, $payment_method, $intent->id);
            
            wp_send_json_success(array(
                'client_secret' => $intent->client_secret,
                'payment_id' => $payment_id
            ));
        } catch (Exception $e) {
            wp_send_json_error('Payment intent creation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Confirm payment
     */
    public function confirm_payment() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $payment_id = intval($_POST['payment_id']);
        $payment_intent_id = sanitize_text_field($_POST['payment_intent_id']);
        
        $payment = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_payments WHERE id = %d",
                $payment_id
            )
        );
        
        if (!$payment) {
            wp_send_json_error('Payment not found');
        }
        
        try {
            \Stripe\Stripe::setApiKey($this->stripe_config['secret_key']);
            
            $intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            
            if ($intent->status === 'succeeded') {
                $this->complete_payment($payment, $payment_intent_id);
                wp_send_json_success('Payment confirmed successfully');
            } else {
                wp_send_json_error('Payment not completed');
            }
        } catch (Exception $e) {
            wp_send_json_error('Payment confirmation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Create bank transfer
     */
    public function create_bank_transfer() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $amount = floatval($_POST['amount']);
        $currency = sanitize_text_field($_POST['currency']);
        $bank_details = array(
            'account_holder' => sanitize_text_field($_POST['account_holder']),
            'account_number' => sanitize_text_field($_POST['account_number']),
            'routing_number' => sanitize_text_field($_POST['routing_number']),
            'bank_name' => sanitize_text_field($_POST['bank_name'])
        );
        
        if (!$this->bank_config['enabled']) {
            wp_send_json_error('Bank transfers not enabled');
        }
        
        // Create bank transfer request
        $transfer_id = $this->create_bank_transfer_request($user_id, $amount, $currency, $bank_details);
        
        if ($transfer_id) {
            wp_send_json_success(array(
                'transfer_id' => $transfer_id,
                'instructions' => $this->get_bank_transfer_instructions($transfer_id)
            ));
        } else {
            wp_send_json_error('Bank transfer creation failed');
        }
    }
    
    /**
     * Verify bank transfer
     */
    public function verify_bank_transfer() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $transfer_id = sanitize_text_field($_POST['transfer_id']);
        $verification_code = sanitize_text_field($_POST['verification_code']);
        
        $transfer = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_payments WHERE external_id = %s",
                $transfer_id
            )
        );
        
        if (!$transfer) {
            wp_send_json_error('Transfer not found');
        }
        
        if ($this->verify_bank_transfer_code($transfer_id, $verification_code)) {
            $this->complete_payment($transfer, $transfer_id);
            wp_send_json_success('Bank transfer verified successfully');
        } else {
            wp_send_json_error('Invalid verification code');
        }
    }
    
    /**
     * Create payment record
     */
    private function create_payment_record($user_id, $amount, $currency, $payment_method, $external_id) {
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_payments',
            array(
                'user_id' => $user_id,
                'amount' => $amount,
                'currency' => $currency,
                'payment_method' => $payment_method,
                'external_id' => $external_id,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%f', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Create bank transfer request
     */
    private function create_bank_transfer_request($user_id, $amount, $currency, $bank_details) {
        $transfer_id = 'BT' . time() . rand(1000, 9999);
        
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_payments',
            array(
                'user_id' => $user_id,
                'amount' => $amount,
                'currency' => $currency,
                'payment_method' => 'bank_transfer',
                'external_id' => $transfer_id,
                'bank_details' => json_encode($bank_details),
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $transfer_id;
    }
    
    /**
     * Complete payment
     */
    private function complete_payment($payment, $external_id) {
        $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_payments',
            array(
                'status' => 'completed',
                'external_id' => $external_id,
                'completed_at' => current_time('mysql')
            ),
            array('id' => $payment->id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        // Add funds to user's wallet
        $this->add_funds_to_wallet($payment->user_id, $payment->amount, $payment->currency);
        
        // Send confirmation email
        $this->send_payment_confirmation($payment);
    }
    
    /**
     * Fail payment
     */
    private function fail_payment($payment, $reason) {
        $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_payments',
            array(
                'status' => 'failed',
                'failure_reason' => $reason,
                'failed_at' => current_time('mysql')
            ),
            array('id' => $payment->id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        // Send failure notification
        $this->send_payment_failure_notification($payment, $reason);
    }
    
    /**
     * Update payment status
     */
    private function update_payment_status($payment_id, $status) {
        $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_payments',
            array('status' => $status),
            array('id' => $payment_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Add funds to wallet
     */
    private function add_funds_to_wallet($user_id, $amount, $currency) {
        // Check if wallet exists
        $wallet = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_wallets 
                 WHERE user_id = %d AND currency = %s",
                $user_id,
                $currency
            )
        );
        
        if ($wallet) {
            // Update existing wallet
            $this->wpdb->update(
                $this->wpdb->prefix . 'crypto_wallets',
                array('balance' => $wallet->balance + $amount),
                array('id' => $wallet->id),
                array('%f'),
                array('%d')
            );
        } else {
            // Create new wallet
            $this->wpdb->insert(
                $this->wpdb->prefix . 'crypto_wallets',
                array(
                    'user_id' => $user_id,
                    'currency' => $currency,
                    'balance' => $amount,
                    'status' => 'active',
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%f', '%s', '%s')
            );
        }
        
        // Create transaction record
        $this->create_transaction_record($user_id, $amount, $currency, 'deposit', 'payment');
    }
    
    /**
     * Create transaction record
     */
    private function create_transaction_record($user_id, $amount, $currency, $type, $source) {
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_transactions',
            array(
                'user_id' => $user_id,
                'type' => $type,
                'currency' => $currency,
                'amount' => $amount,
                'source' => $source,
                'status' => 'completed',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%f', '%s', '%s', '%s')
        );
    }
    
    /**
     * Check bank transfer status
     */
    private function check_bank_transfer_status($transfer_id) {
        // Mock implementation - in production, integrate with actual bank API
        $statuses = array('pending', 'completed', 'failed');
        return $statuses[array_rand($statuses)];
    }
    
    /**
     * Verify bank transfer code
     */
    private function verify_bank_transfer_code($transfer_id, $code) {
        // Mock implementation - in production, verify with actual bank system
        return $code === 'VERIFY123';
    }
    
    /**
     * Get bank transfer instructions
     */
    private function get_bank_transfer_instructions($transfer_id) {
        return array(
            'bank_name' => 'Crypto Exchange Bank',
            'account_number' => '1234567890',
            'routing_number' => '987654321',
            'reference' => $transfer_id,
            'amount' => 'As specified in your request',
            'note' => 'Include transfer ID in the reference field'
        );
    }
    
    /**
     * Send payment confirmation
     */
    private function send_payment_confirmation($payment) {
        $user = get_user_by('id', $payment->user_id);
        if ($user) {
            $subject = 'Payment Confirmed - ' . get_bloginfo('name');
            $message = sprintf(
                'Your payment of %s %s has been confirmed and added to your account.',
                $payment->amount,
                $payment->currency
            );
            wp_mail($user->user_email, $subject, $message);
        }
    }
    
    /**
     * Send payment failure notification
     */
    private function send_payment_failure_notification($payment, $reason) {
        $user = get_user_by('id', $payment->user_id);
        if ($user) {
            $subject = 'Payment Failed - ' . get_bloginfo('name');
            $message = sprintf(
                'Your payment of %s %s has failed. Reason: %s',
                $payment->amount,
                $payment->currency,
                $reason
            );
            wp_mail($user->user_email, $subject, $message);
        }
    }
    
    /**
     * Sync bank transactions
     */
    public function sync_bank_transactions() {
        if (!$this->bank_config['enabled']) {
            return;
        }
        
        // Mock implementation - in production, sync with actual bank API
        $transactions = $this->fetch_bank_transactions();
        
        foreach ($transactions as $transaction) {
            $this->process_bank_transaction($transaction);
        }
    }
    
    /**
     * Fetch bank transactions
     */
    private function fetch_bank_transactions() {
        // Mock implementation
        return array();
    }
    
    /**
     * Process bank transaction
     */
    private function process_bank_transaction($transaction) {
        // Process incoming bank transaction
        $this->create_transaction_record(
            $transaction['user_id'],
            $transaction['amount'],
            $transaction['currency'],
            'deposit',
            'bank_transfer'
        );
    }
    
    /**
     * Get payment methods
     */
    public function get_payment_methods() {
        $methods = array();
        
        if ($this->stripe_config['enabled']) {
            $methods[] = array(
                'id' => 'stripe',
                'name' => 'Credit/Debit Card',
                'type' => 'card',
                'fees' => '2.9% + $0.30',
                'processing_time' => 'Instant'
            );
            
            $methods[] = array(
                'id' => 'ach',
                'name' => 'ACH Transfer',
                'type' => 'bank',
                'fees' => '0.8%',
                'processing_time' => '1-3 business days'
            );
        }
        
        if ($this->bank_config['enabled']) {
            $methods[] = array(
                'id' => 'bank_transfer',
                'name' => 'Bank Transfer',
                'type' => 'bank',
                'fees' => 'Free',
                'processing_time' => '1-2 business days'
            );
            
            $methods[] = array(
                'id' => 'wire_transfer',
                'name' => 'Wire Transfer',
                'type' => 'bank',
                'fees' => '$25',
                'processing_time' => '1-2 business days'
            );
        }
        
        return $methods;
    }
    
    /**
     * Get payment limits
     */
    public function get_payment_limits($user_id) {
        $kyc_status = get_user_meta($user_id, 'crypto_kyc_status', true);
        
        $limits = array(
            'daily' => 1000,
            'monthly' => 10000,
            'yearly' => 100000
        );
        
        if ($kyc_status === 'verified') {
            $limits['daily'] = 10000;
            $limits['monthly'] = 100000;
            $limits['yearly'] = 1000000;
        }
        
        return $limits;
    }
    
    /**
     * Create payment tables
     */
    public function create_payment_tables() {
        // Payments table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_payments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            amount decimal(20,2) NOT NULL,
            currency varchar(10) NOT NULL,
            payment_method varchar(50) NOT NULL,
            external_id varchar(255) DEFAULT NULL,
            bank_details text DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            failure_reason text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            failed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY external_id (external_id)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
        if ($this->bank_config['enabled']) {
            $methods[] = array(
                'id' => 'bank_transfer',
                'name' => 'Bank Transfer',
                'type' => 'bank',
                'fees' => 'Free',
                'processing_time' => '1-2 business days'
            );
            
            $methods[] = array(
                'id' => 'wire_transfer',
                'name' => 'Wire Transfer',
                'type' => 'bank',
                'fees' => '$25',
                'processing_time' => '1-2 business days'
            );
        }
        
        return $methods;
    }
    
    /**
     * Get payment limits
     */
    public function get_payment_limits($user_id) {
        $kyc_status = get_user_meta($user_id, 'crypto_kyc_status', true);
        
        $limits = array(
            'daily' => 1000,
            'monthly' => 10000,
            'yearly' => 100000
        );
        
        if ($kyc_status === 'verified') {
            $limits['daily'] = 10000;
            $limits['monthly'] = 100000;
            $limits['yearly'] = 1000000;
        }
        
        return $limits;
    }
    
    /**
     * Create payment tables
     */
    public function create_payment_tables() {
        // Payments table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_payments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            amount decimal(20,2) NOT NULL,
            currency varchar(10) NOT NULL,
            payment_method varchar(50) NOT NULL,
            external_id varchar(255) DEFAULT NULL,
            bank_details text DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            failure_reason text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            failed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY external_id (external_id)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
