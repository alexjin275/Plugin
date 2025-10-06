<?php
/**
 * Liquidity Providers Management System
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Liquidity_Providers {
    
    private $wpdb;
    private $providers = array();
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        add_action('wp_ajax_crypto_exchange_add_liquidity_provider', array($this, 'add_liquidity_provider'));
        add_action('wp_ajax_crypto_exchange_update_liquidity_provider', array($this, 'update_liquidity_provider'));
        add_action('wp_ajax_crypto_exchange_delete_liquidity_provider', array($this, 'delete_liquidity_provider'));
        add_action('wp_ajax_crypto_exchange_test_liquidity_provider', array($this, 'test_liquidity_provider'));
        add_action('wp_ajax_crypto_exchange_get_liquidity_provider_data', array($this, 'get_liquidity_provider_data'));
        add_action('wp_ajax_crypto_exchange_sync_liquidity_provider', array($this, 'sync_liquidity_provider'));
        
        $this->load_providers();
    }
    
    /**
     * Load all liquidity providers
     */
    private function load_providers() {
        $providers = $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_liquidity_providers WHERE status = 'active'"
        );
        
        foreach ($providers as $provider) {
            $this->providers[$provider->id] = $provider;
        }
    }
    
    /**
     * Add new liquidity provider
     */
    public function add_liquidity_provider() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $provider_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'exchange' => sanitize_text_field($_POST['exchange']),
            'api_key' => sanitize_text_field($_POST['api_key']),
            'api_secret' => sanitize_text_field($_POST['api_secret']),
            'api_passphrase' => sanitize_text_field($_POST['api_passphrase']),
            'sandbox' => intval($_POST['sandbox']),
            'priority' => intval($_POST['priority']),
            'max_daily_volume' => floatval($_POST['max_daily_volume']),
            'max_order_size' => floatval($_POST['max_order_size']),
            'min_order_size' => floatval($_POST['min_order_size']),
            'trading_fee' => floatval($_POST['trading_fee']),
            'withdrawal_fee' => floatval($_POST['withdrawal_fee']),
            'supported_pairs' => sanitize_textarea_field($_POST['supported_pairs']),
            'endpoints' => sanitize_textarea_field($_POST['endpoints']),
            'rate_limit' => intval($_POST['rate_limit']),
            'timeout' => intval($_POST['timeout']),
            'retry_attempts' => intval($_POST['retry_attempts']),
            'status' => sanitize_text_field($_POST['status']),
            'created_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_liquidity_providers',
            $provider_data
        );
        
        if ($result) {
            wp_send_json_success('Liquidity provider added successfully');
        } else {
            wp_send_json_error('Failed to add liquidity provider');
        }
    }
    
    /**
     * Update liquidity provider
     */
    public function update_liquidity_provider() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $provider_id = intval($_POST['provider_id']);
        
        $provider_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'exchange' => sanitize_text_field($_POST['exchange']),
            'api_key' => sanitize_text_field($_POST['api_key']),
            'api_secret' => sanitize_text_field($_POST['api_secret']),
            'api_passphrase' => sanitize_text_field($_POST['api_passphrase']),
            'sandbox' => intval($_POST['sandbox']),
            'priority' => intval($_POST['priority']),
            'max_daily_volume' => floatval($_POST['max_daily_volume']),
            'max_order_size' => floatval($_POST['max_order_size']),
            'min_order_size' => floatval($_POST['min_order_size']),
            'trading_fee' => floatval($_POST['trading_fee']),
            'withdrawal_fee' => floatval($_POST['withdrawal_fee']),
            'supported_pairs' => sanitize_textarea_field($_POST['supported_pairs']),
            'endpoints' => sanitize_textarea_field($_POST['endpoints']),
            'rate_limit' => intval($_POST['rate_limit']),
            'timeout' => intval($_POST['timeout']),
            'retry_attempts' => intval($_POST['retry_attempts']),
            'status' => sanitize_text_field($_POST['status']),
            'updated_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_liquidity_providers',
            $provider_data,
            array('id' => $provider_id)
        );
        
        if ($result !== false) {
            wp_send_json_success('Liquidity provider updated successfully');
        } else {
            wp_send_json_error('Failed to update liquidity provider');
        }
    }
    
    /**
     * Delete liquidity provider
     */
    public function delete_liquidity_provider() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $provider_id = intval($_POST['provider_id']);
        
        $result = $this->wpdb->delete(
            $this->wpdb->prefix . 'crypto_liquidity_providers',
            array('id' => $provider_id)
        );
        
        if ($result) {
            wp_send_json_success('Liquidity provider deleted successfully');
        } else {
            wp_send_json_error('Failed to delete liquidity provider');
        }
    }
    
    /**
     * Test liquidity provider connection
     */
    public function test_liquidity_provider() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $provider_id = intval($_POST['provider_id']);
        $provider = $this->get_provider($provider_id);
        
        if (!$provider) {
            wp_send_json_error('Provider not found');
        }
        
        $exchange_class = 'Crypto_Exchange_' . ucfirst($provider->exchange);
        
        if (!class_exists($exchange_class)) {
            wp_send_json_error('Exchange class not found');
        }
        
        $exchange = new $exchange_class($provider);
        $test_result = $exchange->test_connection();
        
        if ($test_result['success']) {
            wp_send_json_success($test_result['message']);
        } else {
            wp_send_json_error($test_result['message']);
        }
    }
    
    /**
     * Get liquidity provider data
     */
    public function get_liquidity_provider_data() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $provider_id = intval($_POST['provider_id']);
        $provider = $this->get_provider($provider_id);
        
        if ($provider) {
            wp_send_json_success($provider);
        } else {
            wp_send_json_error('Provider not found');
        }
    }
    
    /**
     * Sync liquidity provider data
     */
    public function sync_liquidity_provider() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $provider_id = intval($_POST['provider_id']);
        $provider = $this->get_provider($provider_id);
        
        if (!$provider) {
            wp_send_json_error('Provider not found');
        }
        
        $exchange_class = 'Crypto_Exchange_' . ucfirst($provider->exchange);
        
        if (!class_exists($exchange_class)) {
            wp_send_json_error('Exchange class not found');
        }
        
        $exchange = new $exchange_class($provider);
        $sync_result = $exchange->sync_data();
        
        if ($sync_result['success']) {
            wp_send_json_success($sync_result['message']);
        } else {
            wp_send_json_error($sync_result['message']);
        }
    }
    
    /**
     * Get provider by ID
     */
    public function get_provider($provider_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_liquidity_providers WHERE id = %d",
                $provider_id
            )
        );
    }
    
    /**
     * Get all providers
     */
    public function get_all_providers() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_liquidity_providers ORDER BY priority ASC, name ASC"
        );
    }
    
    /**
     * Get active providers
     */
    public function get_active_providers() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_liquidity_providers WHERE status = 'active' ORDER BY priority ASC"
        );
    }
    
    /**
     * Get providers for trading pair
     */
    public function get_providers_for_pair($trading_pair) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_liquidity_providers 
                 WHERE status = 'active' 
                 AND (supported_pairs LIKE %s OR supported_pairs = 'ALL')
                 ORDER BY priority ASC",
                '%' . $trading_pair . '%'
            )
        );
    }
    
    /**
     * Get best provider for order
     */
    public function get_best_provider($trading_pair, $order_type, $amount, $side) {
        $providers = $this->get_providers_for_pair($trading_pair);
        
        if (empty($providers)) {
            return null;
        }
        
        $best_provider = null;
        $best_score = -1;
        
        foreach ($providers as $provider) {
            $score = $this->calculate_provider_score($provider, $trading_pair, $order_type, $amount, $side);
            
            if ($score > $best_score) {
                $best_score = $score;
                $best_provider = $provider;
            }
        }
        
        return $best_provider;
    }
    
    /**
     * Calculate provider score
     */
    private function calculate_provider_score($provider, $trading_pair, $order_type, $amount, $side) {
        $score = 0;
        
        // Priority score (higher priority = higher score)
        $score += (100 - $provider->priority) * 10;
        
        // Volume capacity score
        if ($amount <= $provider->max_order_size) {
            $score += 50;
        }
        
        // Fee score (lower fee = higher score)
        $score += (1 - $provider->trading_fee) * 100;
        
        // Reliability score (based on historical performance)
        $reliability = $this->get_provider_reliability($provider->id);
        $score += $reliability * 30;
        
        // Latency score (lower latency = higher score)
        $latency = $this->get_provider_latency($provider->id);
        if ($latency < 100) {
            $score += 40;
        } elseif ($latency < 500) {
            $score += 20;
        }
        
        return $score;
    }
    
    /**
     * Get provider reliability score
     */
    private function get_provider_reliability($provider_id) {
        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'filled' THEN 1 ELSE 0 END) as successful_orders
                 FROM {$this->wpdb->prefix}crypto_liquidity_orders 
                 WHERE provider_id = %d 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                $provider_id
            )
        );
        
        if ($stats->total_orders > 0) {
            return $stats->successful_orders / $stats->total_orders;
        }
        
        return 0.5; // Default reliability
    }
    
    /**
     * Get provider latency
     */
    private function get_provider_latency($provider_id) {
        $latency = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT AVG(latency) FROM {$this->wpdb->prefix}crypto_liquidity_stats 
                 WHERE provider_id = %d 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                $provider_id
            )
        );
        
        return $latency ?: 1000; // Default latency
    }
    
    /**
     * Create liquidity providers table
     */
    public function create_liquidity_providers_table() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_liquidity_providers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            exchange varchar(50) NOT NULL,
            api_key varchar(255) NOT NULL,
            api_secret varchar(255) NOT NULL,
            api_passphrase varchar(255) DEFAULT NULL,
            sandbox tinyint(1) DEFAULT 0,
            priority int(11) DEFAULT 1,
            max_daily_volume decimal(20,8) DEFAULT 1000000.00000000,
            max_order_size decimal(20,8) DEFAULT 10000.00000000,
            min_order_size decimal(20,8) DEFAULT 0.00000001,
            trading_fee decimal(8,4) DEFAULT 0.1000,
            withdrawal_fee decimal(20,8) DEFAULT 0.00100000,
            supported_pairs text,
            endpoints text,
            rate_limit int(11) DEFAULT 1000,
            timeout int(11) DEFAULT 30,
            retry_attempts int(11) DEFAULT 3,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY exchange (exchange),
            KEY status (status),
            KEY priority (priority)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create liquidity orders table
        $this->create_liquidity_orders_table();
        
        // Create liquidity stats table
        $this->create_liquidity_stats_table();
    }
    
    /**
     * Create liquidity orders table
     */
    private function create_liquidity_orders_table() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_liquidity_orders (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            provider_id bigint(20) NOT NULL,
            internal_order_id bigint(20) NOT NULL,
            external_order_id varchar(100) DEFAULT NULL,
            trading_pair varchar(20) NOT NULL,
            side varchar(10) NOT NULL,
            order_type varchar(20) NOT NULL,
            amount decimal(20,8) NOT NULL,
            price decimal(20,8) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            filled_amount decimal(20,8) DEFAULT 0.00000000,
            remaining_amount decimal(20,8) NOT NULL,
            fee decimal(20,8) DEFAULT 0.00000000,
            latency int(11) DEFAULT NULL,
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY provider_id (provider_id),
            KEY internal_order_id (internal_order_id),
            KEY external_order_id (external_order_id),
            KEY trading_pair (trading_pair),
            KEY status (status),
            KEY created_at (created_at)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create liquidity stats table
     */
    private function create_liquidity_stats_table() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_liquidity_stats (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            provider_id bigint(20) NOT NULL,
            trading_pair varchar(20) NOT NULL,
            total_orders int(11) DEFAULT 0,
            successful_orders int(11) DEFAULT 0,
            failed_orders int(11) DEFAULT 0,
            total_volume decimal(20,8) DEFAULT 0.00000000,
            total_fees decimal(20,8) DEFAULT 0.00000000,
            avg_latency int(11) DEFAULT 0,
            success_rate decimal(5,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY provider_id (provider_id),
            KEY trading_pair (trading_pair),
            KEY created_at (created_at)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get provider statistics
     */
    public function get_provider_stats() {
        $stats = $this->wpdb->get_results(
            "SELECT 
                p.id,
                p.name,
                p.exchange,
                p.status,
                COUNT(lo.id) as total_orders,
                SUM(CASE WHEN lo.status = 'filled' THEN 1 ELSE 0 END) as successful_orders,
                SUM(CASE WHEN lo.status = 'failed' THEN 1 ELSE 0 END) as failed_orders,
                SUM(lo.amount) as total_volume,
                SUM(lo.fee) as total_fees,
                AVG(lo.latency) as avg_latency
             FROM {$this->wpdb->prefix}crypto_liquidity_providers p
             LEFT JOIN {$this->wpdb->prefix}crypto_liquidity_orders lo ON p.id = lo.provider_id
             WHERE lo.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY p.id, p.name, p.exchange, p.status
             ORDER BY p.priority ASC"
        );
        
        return $stats;
    }
}
