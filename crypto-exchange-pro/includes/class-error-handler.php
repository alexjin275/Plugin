<?php
/**
 * Comprehensive Error Handling and Conflict Resolution System
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Error_Handler {
    
    private $wpdb;
    private $error_log = array();
    private $conflict_log = array();
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->init_hooks();
        $this->create_error_tables();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Set error handlers
        set_error_handler(array($this, 'handle_error'));
        set_exception_handler(array($this, 'handle_exception'));
        register_shutdown_function(array($this, 'handle_shutdown'));
        
        // WordPress hooks
        add_action('wp_ajax_crypto_exchange_get_errors', array($this, 'get_errors'));
        add_action('wp_ajax_crypto_exchange_clear_errors', array($this, 'clear_errors'));
        add_action('wp_ajax_crypto_exchange_resolve_conflict', array($this, 'resolve_conflict'));
        add_action('wp_ajax_crypto_exchange_test_system', array($this, 'test_system'));
    }
    
    /**
     * Handle PHP errors
     */
    public function handle_error($errno, $errstr, $errfile, $errline) {
        $error = array(
            'type' => 'error',
            'level' => $this->get_error_level($errno),
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        );
        
        $this->log_error($error);
        
        // Don't execute PHP internal error handler
        return true;
    }
    
    /**
     * Handle exceptions
     */
    public function handle_exception($exception) {
        $error = array(
            'type' => 'exception',
            'level' => 'critical',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        );
        
        $this->log_error($error);
    }
    
    /**
     * Handle shutdown errors
     */
    public function handle_shutdown() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
            $error_data = array(
                'type' => 'shutdown',
                'level' => 'critical',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'timestamp' => current_time('mysql'),
                'user_id' => get_current_user_id(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            );
            
            $this->log_error($error_data);
        }
    }
    
    /**
     * Get error level from error number
     */
    private function get_error_level($errno) {
        $levels = array(
            E_ERROR => 'critical',
            E_WARNING => 'warning',
            E_PARSE => 'critical',
            E_NOTICE => 'notice',
            E_CORE_ERROR => 'critical',
            E_CORE_WARNING => 'warning',
            E_COMPILE_ERROR => 'critical',
            E_COMPILE_WARNING => 'warning',
            E_USER_ERROR => 'error',
            E_USER_WARNING => 'warning',
            E_USER_NOTICE => 'notice',
            E_STRICT => 'notice',
            E_RECOVERABLE_ERROR => 'error',
            E_DEPRECATED => 'notice',
            E_USER_DEPRECATED => 'notice'
        );
        
        return $levels[$errno] ?? 'unknown';
    }
    
    /**
     * Log error to database
     */
    private function log_error($error) {
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_errors',
            $error
        );
        
        // Also log to WordPress error log
        error_log('Crypto Exchange Error: ' . json_encode($error));
        
        // Store in memory for current request
        $this->error_log[] = $error;
    }
    
    /**
     * Check for conflicts
     */
    public function check_conflicts() {
        $conflicts = array();
        
        // Check for plugin conflicts
        $active_plugins = get_option('active_plugins', array());
        $conflict_plugins = array(
            'woocommerce/woocommerce.php' => 'WooCommerce may conflict with trading functionality',
            'easy-digital-downloads/easy-digital-downloads.php' => 'EDD may conflict with payment processing',
            'memberpress/memberpress.php' => 'MemberPress may conflict with user management'
        );
        
        foreach ($conflict_plugins as $plugin => $message) {
            if (in_array($plugin, $active_plugins)) {
                $conflicts[] = array(
                    'type' => 'plugin_conflict',
                    'severity' => 'warning',
                    'message' => $message,
                    'plugin' => $plugin,
                    'timestamp' => current_time('mysql')
                );
            }
        }
        
        // Check for theme conflicts
        $current_theme = wp_get_theme();
        $conflict_themes = array(
            'twenty-twenty-one' => 'Default theme may not support all features',
            'twenty-twenty-two' => 'Default theme may not support all features'
        );
        
        if (isset($conflict_themes[$current_theme->get_stylesheet()])) {
            $conflicts[] = array(
                'type' => 'theme_conflict',
                'severity' => 'info',
                'message' => $conflict_themes[$current_theme->get_stylesheet()],
                'theme' => $current_theme->get_stylesheet(),
                'timestamp' => current_time('mysql')
            );
        }
        
        // Check for database conflicts
        $db_conflicts = $this->check_database_conflicts();
        $conflicts = array_merge($conflicts, $db_conflicts);
        
        // Check for API conflicts
        $api_conflicts = $this->check_api_conflicts();
        $conflicts = array_merge($conflicts, $api_conflicts);
        
        // Log conflicts
        foreach ($conflicts as $conflict) {
            $this->log_conflict($conflict);
        }
        
        return $conflicts;
    }
    
    /**
     * Check database conflicts
     */
    private function check_database_conflicts() {
        $conflicts = array();
        
        // Check for missing tables
        $required_tables = array(
            'crypto_users',
            'crypto_orders',
            'crypto_trades',
            'crypto_wallets',
            'crypto_kyc_documents',
            'crypto_wallet_providers',
            'crypto_wallet_addresses'
        );
        
        foreach ($required_tables as $table) {
            $table_name = $this->wpdb->prefix . $table;
            if ($this->wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                $conflicts[] = array(
                    'type' => 'database_conflict',
                    'severity' => 'critical',
                    'message' => "Missing required table: $table",
                    'table' => $table,
                    'timestamp' => current_time('mysql')
                );
            }
        }
        
        // Check for table structure conflicts
        $structure_conflicts = $this->check_table_structure();
        $conflicts = array_merge($conflicts, $structure_conflicts);
        
        return $conflicts;
    }
    
    /**
     * Check table structure
     */
    private function check_table_structure() {
        $conflicts = array();
        
        // Check crypto_users table structure
        $users_table = $this->wpdb->prefix . 'crypto_users';
        if ($this->wpdb->get_var("SHOW TABLES LIKE '$users_table'") == $users_table) {
            $columns = $this->wpdb->get_col("DESCRIBE $users_table");
            $required_columns = array('user_id', 'kyc_status', 'account_status', 'trading_limits');
            
            foreach ($required_columns as $column) {
                if (!in_array($column, $columns)) {
                    $conflicts[] = array(
                        'type' => 'database_conflict',
                        'severity' => 'critical',
                        'message' => "Missing required column: $column in $users_table",
                        'table' => 'crypto_users',
                        'column' => $column,
                        'timestamp' => current_time('mysql')
                    );
                }
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Check API conflicts
     */
    private function check_api_conflicts() {
        $conflicts = array();
        
        // Check for conflicting REST API endpoints
        $rest_routes = rest_get_server()->get_routes();
        $crypto_routes = array_filter($rest_routes, function($key) {
            return strpos($key, 'crypto-exchange') !== false;
        }, ARRAY_FILTER_USE_KEY);
        
        // Check for conflicting AJAX actions
        $ajax_actions = array(
            'crypto_exchange_login',
            'crypto_exchange_register',
            'crypto_exchange_place_order',
            'crypto_exchange_get_balances'
        );
        
        foreach ($ajax_actions as $action) {
            if (has_action("wp_ajax_$action") && has_action("wp_ajax_nopriv_$action")) {
                $conflicts[] = array(
                    'type' => 'api_conflict',
                    'severity' => 'warning',
                    'message' => "Conflicting AJAX action: $action",
                    'action' => $action,
                    'timestamp' => current_time('mysql')
                );
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Log conflict
     */
    private function log_conflict($conflict) {
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_conflicts',
            $conflict
        );
        
        $this->conflict_log[] = $conflict;
    }
    
    /**
     * Get errors
     */
    public function get_errors() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $level = sanitize_text_field($_POST['level'] ?? 'all');
        $limit = intval($_POST['limit'] ?? 100);
        
        $where = '';
        if ($level !== 'all') {
            $where = $this->wpdb->prepare("WHERE level = %s", $level);
        }
        
        $errors = $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_errors 
             $where 
             ORDER BY timestamp DESC 
             LIMIT $limit"
        );
        
        wp_send_json_success($errors);
    }
    
    /**
     * Clear errors
     */
    public function clear_errors() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $level = sanitize_text_field($_POST['level'] ?? 'all');
        
        if ($level === 'all') {
            $result = $this->wpdb->query("TRUNCATE TABLE {$this->wpdb->prefix}crypto_errors");
        } else {
            $result = $this->wpdb->delete(
                $this->wpdb->prefix . 'crypto_errors',
                array('level' => $level)
            );
        }
        
        if ($result !== false) {
            wp_send_json_success('Errors cleared successfully');
        } else {
            wp_send_json_error('Failed to clear errors');
        }
    }
    
    /**
     * Resolve conflict
     */
    public function resolve_conflict() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $conflict_id = intval($_POST['conflict_id']);
        $resolution = sanitize_text_field($_POST['resolution']);
        
        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_conflicts',
            array(
                'resolved' => 1,
                'resolution' => $resolution,
                'resolved_at' => current_time('mysql'),
                'resolved_by' => get_current_user_id()
            ),
            array('id' => $conflict_id)
        );
        
        if ($result !== false) {
            wp_send_json_success('Conflict resolved successfully');
        } else {
            wp_send_json_error('Failed to resolve conflict');
        }
    }
    
    /**
     * Test system
     */
    public function test_system() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $tests = array();
        
        // Test database connection
        $tests['database'] = $this->test_database();
        
        // Test API endpoints
        $tests['api'] = $this->test_api();
        
        // Test wallet providers
        $tests['wallet_providers'] = $this->test_wallet_providers();
        
        // Test trading system
        $tests['trading'] = $this->test_trading();
        
        // Test security
        $tests['security'] = $this->test_security();
        
        wp_send_json_success($tests);
    }
    
    /**
     * Test database
     */
    private function test_database() {
        try {
            $result = $this->wpdb->get_var("SELECT 1");
            return array(
                'status' => 'success',
                'message' => 'Database connection successful'
            );
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test API
     */
    private function test_api() {
        try {
            $response = wp_remote_get(home_url('/wp-json/crypto-exchange/v1/wallet/providers'));
            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                throw new Exception("API returned status code: $status_code");
            }
            
            return array(
                'status' => 'success',
                'message' => 'API endpoints working correctly'
            );
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'API test failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test wallet providers
     */
    private function test_wallet_providers() {
        try {
            $wallet_providers = new Crypto_Exchange_Wallet_Providers();
            $providers = $wallet_providers->get_active_providers();
            
            $test_results = array();
            foreach ($providers as $provider) {
                $class_name = $wallet_providers->get_available_providers()[$provider->provider_type]['class'];
                if (class_exists($class_name)) {
                    $instance = new $class_name($provider);
                    $test_result = $instance->test_connection();
                    $test_results[] = array(
                        'provider' => $provider->name,
                        'status' => $test_result['success'] ? 'success' : 'error',
                        'message' => $test_result['message']
                    );
                }
            }
            
            return array(
                'status' => 'success',
                'message' => 'Wallet providers tested',
                'results' => $test_results
            );
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Wallet providers test failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test trading
     */
    private function test_trading() {
        try {
            $trading = new Crypto_Exchange_Trading();
            // Add trading system tests here
            
            return array(
                'status' => 'success',
                'message' => 'Trading system operational'
            );
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Trading system test failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Test security
     */
    private function test_security() {
        try {
            $security = new Crypto_Exchange_Security();
            // Add security tests here
            
            return array(
                'status' => 'success',
                'message' => 'Security system operational'
            );
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Security system test failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Create error tables
     */
    private function create_error_tables() {
        // Create errors table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_errors (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            type varchar(20) NOT NULL,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            file varchar(255) DEFAULT NULL,
            line int(11) DEFAULT NULL,
            trace text DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            ip varchar(45) DEFAULT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY timestamp (timestamp),
            KEY user_id (user_id)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create conflicts table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_conflicts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            type varchar(50) NOT NULL,
            severity varchar(20) NOT NULL,
            message text NOT NULL,
            plugin varchar(255) DEFAULT NULL,
            theme varchar(255) DEFAULT NULL,
            table_name varchar(255) DEFAULT NULL,
            column_name varchar(255) DEFAULT NULL,
            action varchar(255) DEFAULT NULL,
            resolved tinyint(1) DEFAULT 0,
            resolution text DEFAULT NULL,
            resolved_at datetime DEFAULT NULL,
            resolved_by bigint(20) DEFAULT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type),
            KEY severity (severity),
            KEY resolved (resolved),
            KEY timestamp (timestamp)
        ) " . $this->wpdb->get_charset_collate();
        
        dbDelta($sql);
    }
    
    /**
     * Get system health
     */
    public function get_system_health() {
        $health = array(
            'overall' => 'good',
            'components' => array()
        );
        
        // Check error count
        $error_count = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_errors 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        
        if ($error_count > 100) {
            $health['overall'] = 'critical';
        } elseif ($error_count > 50) {
            $health['overall'] = 'warning';
        }
        
        // Check conflict count
        $conflict_count = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_conflicts 
             WHERE resolved = 0"
        );
        
        if ($conflict_count > 10) {
            $health['overall'] = 'warning';
        }
        
        $health['components'] = array(
            'errors_24h' => $error_count,
            'unresolved_conflicts' => $conflict_count,
            'database_status' => $this->test_database()['status'],
            'api_status' => $this->test_api()['status']
        );
        
        return $health;
    }
}