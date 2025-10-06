<?php
/**
 * Advanced Admin Configuration System
 * Provides comprehensive control over all plugin features
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Admin_Config {
    
    private $wpdb;
    private $modules = array();
    private $settings = array();
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->init_modules();
        $this->init_hooks();
        $this->load_settings();
    }
    
    /**
     * Initialize all available modules
     */
    private function init_modules() {
        $this->modules = array(
            'trading' => array(
                'name' => 'Trading Engine',
                'description' => 'Core trading functionality',
                'class' => 'Crypto_Exchange_Trading',
                'required' => true,
                'dependencies' => array('database', 'wallet')
            ),
            'wallet' => array(
                'name' => 'Wallet System',
                'description' => 'Cryptocurrency wallet management',
                'class' => 'Crypto_Exchange_Wallet',
                'required' => true,
                'dependencies' => array('database')
            ),
            'kyc' => array(
                'name' => 'KYC System',
                'description' => 'Know Your Customer verification',
                'class' => 'Crypto_Exchange_KYC',
                'required' => false,
                'dependencies' => array('database')
            ),
            'advanced_kyc' => array(
                'name' => 'Advanced KYC',
                'description' => 'AI-powered KYC verification',
                'class' => 'Crypto_Exchange_Advanced_KYC',
                'required' => false,
                'dependencies' => array('kyc', 'database')
            ),
            'security' => array(
                'name' => 'Security System',
                'description' => 'Basic security features',
                'class' => 'Crypto_Exchange_Security',
                'required' => true,
                'dependencies' => array('database')
            ),
            'advanced_security' => array(
                'name' => 'Advanced Security',
                'description' => 'Advanced security monitoring',
                'class' => 'Crypto_Exchange_Advanced_Security',
                'required' => false,
                'dependencies' => array('security', 'database')
            ),
            'risk_management' => array(
                'name' => 'Risk Management',
                'description' => 'Trading risk assessment',
                'class' => 'Crypto_Exchange_Risk_Management',
                'required' => false,
                'dependencies' => array('trading', 'database')
            ),
            'payment_processing' => array(
                'name' => 'Payment Processing',
                'description' => 'Fiat payment processing',
                'class' => 'Crypto_Exchange_Payment_Processing',
                'required' => false,
                'dependencies' => array('wallet', 'database')
            ),
            'notifications' => array(
                'name' => 'Notification System',
                'description' => 'User notifications',
                'class' => 'Crypto_Exchange_Notifications',
                'required' => false,
                'dependencies' => array('database')
            ),
            'websocket' => array(
                'name' => 'WebSocket Server',
                'description' => 'Real-time data streaming',
                'class' => 'Crypto_Exchange_WebSocket',
                'required' => false,
                'dependencies' => array('trading')
            ),
            'price_feed' => array(
                'name' => 'Price Feed',
                'description' => 'Real-time price updates',
                'class' => 'Crypto_Exchange_Price_Feed',
                'required' => false,
                'dependencies' => array('market_data')
            ),
            'market_data' => array(
                'name' => 'Market Data',
                'description' => 'Market data management',
                'class' => 'Crypto_Exchange_Market_Data',
                'required' => true,
                'dependencies' => array('database')
            ),
            'matching_engine' => array(
                'name' => 'Matching Engine',
                'description' => 'Order matching system',
                'class' => 'Crypto_Exchange_Matching_Engine',
                'required' => true,
                'dependencies' => array('trading', 'database')
            ),
            'blockchain_wallet' => array(
                'name' => 'Blockchain Wallet',
                'description' => 'Blockchain integration',
                'class' => 'Crypto_Exchange_Blockchain_Wallet',
                'required' => false,
                'dependencies' => array('wallet')
            ),
            'charting' => array(
                'name' => 'Charting System',
                'description' => 'Trading charts',
                'class' => 'Crypto_Exchange_Charting',
                'required' => false,
                'dependencies' => array('market_data')
            ),
            'liquidity_providers' => array(
                'name' => 'Liquidity Providers',
                'description' => 'External liquidity integration',
                'class' => 'Crypto_Exchange_Liquidity_Providers',
                'required' => false,
                'dependencies' => array('trading')
            ),
            'liquidity_aggregator' => array(
                'name' => 'Liquidity Aggregator',
                'description' => 'Liquidity aggregation system',
                'class' => 'Crypto_Exchange_Liquidity_Aggregator',
                'required' => false,
                'dependencies' => array('liquidity_providers')
            ),
            'coin_management' => array(
                'name' => 'Coin Management',
                'description' => 'Cryptocurrency management',
                'class' => 'Crypto_Exchange_Coin_Management',
                'required' => true,
                'dependencies' => array('database')
            ),
            'api' => array(
                'name' => 'REST API',
                'description' => 'REST API endpoints',
                'class' => 'Crypto_Exchange_API',
                'required' => false,
                'dependencies' => array('trading', 'wallet')
            ),
            'theme_manager' => array(
                'name' => 'Theme Manager',
                'description' => 'Theme management system',
                'class' => 'Crypto_Exchange_Theme_Manager',
                'required' => false,
                'dependencies' => array()
            )
        );
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_crypto_exchange_toggle_module', array($this, 'toggle_module'));
        add_action('wp_ajax_crypto_exchange_save_config', array($this, 'save_config'));
        add_action('wp_ajax_crypto_exchange_reset_config', array($this, 'reset_config'));
        add_action('wp_ajax_crypto_exchange_backup_config', array($this, 'backup_config'));
        add_action('wp_ajax_crypto_exchange_restore_config', array($this, 'restore_config'));
        add_action('wp_ajax_crypto_exchange_test_module', array($this, 'test_module'));
        add_action('wp_ajax_crypto_exchange_get_module_status', array($this, 'get_module_status'));
        add_action('wp_ajax_crypto_exchange_export_settings', array($this, 'export_settings'));
        add_action('wp_ajax_crypto_exchange_import_settings', array($this, 'import_settings'));
    }
    
    /**
     * Load current settings
     */
    private function load_settings() {
        $this->settings = get_option('crypto_exchange_config', array());
        
        // Set default settings if not exists
        if (empty($this->settings)) {
            $this->settings = $this->get_default_settings();
            update_option('crypto_exchange_config', $this->settings);
        }
    }
    
    /**
     * Get default settings
     */
    private function get_default_settings() {
        return array(
            'modules' => array_fill_keys(array_keys($this->modules), true),
            'general' => array(
                'exchange_name' => 'Crypto Exchange Pro',
                'maintenance_mode' => false,
                'debug_mode' => false,
                'log_level' => 'info',
                'timezone' => 'UTC',
                'currency' => 'USD',
                'language' => 'en'
            ),
            'trading' => array(
                'enabled' => true,
                'min_trade_amount' => 10,
                'max_trade_amount' => 1000000,
                'trading_fees' => 0.001,
                'maker_fee' => 0.0005,
                'taker_fee' => 0.001,
                'order_timeout' => 300,
                'max_open_orders' => 100
            ),
            'wallet' => array(
                'enabled' => true,
                'min_deposit' => 0.001,
                'min_withdrawal' => 0.01,
                'withdrawal_fee' => 0.001,
                'max_daily_withdrawal' => 10000,
                'require_kyc_for_withdrawal' => true,
                'cold_storage_percentage' => 80
            ),
            'security' => array(
                'enabled' => true,
                'two_factor_required' => true,
                'ip_whitelist' => array(),
                'max_login_attempts' => 5,
                'lockout_duration' => 900,
                'session_timeout' => 3600,
                'password_min_length' => 8,
                'require_strong_password' => true
            ),
            'kyc' => array(
                'enabled' => true,
                'required_for_trading' => true,
                'required_for_withdrawal' => true,
                'document_types' => array('passport', 'drivers_license', 'national_id'),
                'verification_timeout' => 86400,
                'auto_approve' => false
            ),
            'notifications' => array(
                'enabled' => true,
                'email_notifications' => true,
                'sms_notifications' => false,
                'push_notifications' => true,
                'trading_alerts' => true,
                'price_alerts' => true,
                'security_alerts' => true
            ),
            'api' => array(
                'enabled' => true,
                'rate_limit' => 1000,
                'rate_limit_window' => 3600,
                'require_api_key' => true,
                'allowed_ips' => array(),
                'max_requests_per_minute' => 60
            ),
            'liquidity' => array(
                'enabled' => true,
                'auto_route_orders' => true,
                'min_liquidity_providers' => 2,
                'max_slippage' => 0.01,
                'prefer_lowest_fee' => true
            )
        );
    }
    
    /**
     * Toggle module status
     */
    public function toggle_module() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $module = sanitize_text_field($_POST['module']);
        $enabled = $_POST['enabled'] === 'true';
        
        if (!isset($this->modules[$module])) {
            wp_send_json_error('Invalid module');
        }
        
        // Check dependencies
        if ($enabled) {
            $dependencies = $this->modules[$module]['dependencies'];
            foreach ($dependencies as $dep) {
                if (!$this->is_module_enabled($dep)) {
                    wp_send_json_error('Module dependencies not met: ' . implode(', ', $dependencies));
                }
            }
        } else {
            // Check if other modules depend on this one
            $dependent_modules = $this->get_dependent_modules($module);
            if (!empty($dependent_modules)) {
                wp_send_json_error('Cannot disable module. Other modules depend on it: ' . implode(', ', $dependent_modules));
            }
        }
        
        $this->settings['modules'][$module] = $enabled;
        update_option('crypto_exchange_config', $this->settings);
        
        // Initialize or deinitialize module
        if ($enabled) {
            $this->initialize_module($module);
        } else {
            $this->deinitialize_module($module);
        }
        
        wp_send_json_success('Module status updated');
    }
    
    /**
     * Save configuration
     */
    public function save_config() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $section = sanitize_text_field($_POST['section']);
        $config = $_POST['config'];
        
        // Sanitize configuration based on section
        $sanitized_config = $this->sanitize_config($section, $config);
        
        $this->settings[$section] = $sanitized_config;
        update_option('crypto_exchange_config', $this->settings);
        
        // Apply configuration changes
        $this->apply_configuration($section, $sanitized_config);
        
        wp_send_json_success('Configuration saved');
    }
    
    /**
     * Reset configuration to defaults
     */
    public function reset_config() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $section = sanitize_text_field($_POST['section']);
        
        if ($section === 'all') {
            $this->settings = $this->get_default_settings();
        } else {
            $defaults = $this->get_default_settings();
            if (isset($defaults[$section])) {
                $this->settings[$section] = $defaults[$section];
            }
        }
        
        update_option('crypto_exchange_config', $this->settings);
        
        wp_send_json_success('Configuration reset');
    }
    
    /**
     * Backup configuration
     */
    public function backup_config() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $backup = array(
            'timestamp' => current_time('mysql'),
            'version' => CRYPTO_EXCHANGE_VERSION,
            'settings' => $this->settings
        );
        
        $backup_id = 'backup_' . time();
        update_option('crypto_exchange_backup_' . $backup_id, $backup);
        
        wp_send_json_success(array('backup_id' => $backup_id));
    }
    
    /**
     * Restore configuration from backup
     */
    public function restore_config() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $backup_id = sanitize_text_field($_POST['backup_id']);
        $backup = get_option('crypto_exchange_backup_' . $backup_id);
        
        if (!$backup) {
            wp_send_json_error('Backup not found');
        }
        
        $this->settings = $backup['settings'];
        update_option('crypto_exchange_config', $this->settings);
        
        wp_send_json_success('Configuration restored');
    }
    
    /**
     * Test module functionality
     */
    public function test_module() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $module = sanitize_text_field($_POST['module']);
        
        if (!isset($this->modules[$module])) {
            wp_send_json_error('Invalid module');
        }
        
        $class_name = $this->modules[$module]['class'];
        
        if (!class_exists($class_name)) {
            wp_send_json_error('Module class not found');
        }
        
        try {
            $instance = new $class_name();
            
            if (method_exists($instance, 'test_connection')) {
                $result = $instance->test_connection();
                if ($result['success']) {
                    wp_send_json_success($result['message']);
                } else {
                    wp_send_json_error($result['message']);
                }
            } else {
                wp_send_json_success('Module loaded successfully');
            }
        } catch (Exception $e) {
            wp_send_json_error('Module test failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get module status
     */
    public function get_module_status() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $module = sanitize_text_field($_POST['module']);
        $status = $this->get_module_status_info($module);
        
        wp_send_json_success($status);
    }
    
    /**
     * Export settings
     */
    public function export_settings() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $export_data = array(
            'version' => CRYPTO_EXCHANGE_VERSION,
            'timestamp' => current_time('mysql'),
            'settings' => $this->settings
        );
        
        $filename = 'crypto_exchange_config_' . date('Y-m-d_H-i-s') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Import settings
     */
    public function import_settings() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if (!isset($_FILES['config_file'])) {
            wp_send_json_error('No file uploaded');
        }
        
        $file = $_FILES['config_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('File upload error');
        }
        
        $content = file_get_contents($file['tmp_name']);
        $import_data = json_decode($content, true);
        
        if (!$import_data || !isset($import_data['settings'])) {
            wp_send_json_error('Invalid configuration file');
        }
        
        $this->settings = $import_data['settings'];
        update_option('crypto_exchange_config', $this->settings);
        
        wp_send_json_success('Settings imported successfully');
    }
    
    /**
     * Check if module is enabled
     */
    public function is_module_enabled($module) {
        return isset($this->settings['modules'][$module]) && $this->settings['modules'][$module];
    }
    
    /**
     * Get dependent modules
     */
    private function get_dependent_modules($module) {
        $dependent = array();
        
        foreach ($this->modules as $mod_name => $mod_data) {
            if (in_array($module, $mod_data['dependencies'])) {
                $dependent[] = $mod_name;
            }
        }
        
        return $dependent;
    }
    
    /**
     * Initialize module
     */
    private function initialize_module($module) {
        $class_name = $this->modules[$module]['class'];
        
        if (class_exists($class_name)) {
            try {
                $instance = new $class_name();
                if (method_exists($instance, 'init')) {
                    $instance->init();
                }
            } catch (Exception $e) {
                error_log('Failed to initialize module ' . $module . ': ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Deinitialize module
     */
    private function deinitialize_module($module) {
        // Module-specific cleanup can be added here
        do_action('crypto_exchange_deinitialize_module', $module);
    }
    
    /**
     * Sanitize configuration
     */
    private function sanitize_config($section, $config) {
        switch ($section) {
            case 'general':
                return array(
                    'exchange_name' => sanitize_text_field($config['exchange_name']),
                    'maintenance_mode' => (bool) $config['maintenance_mode'],
                    'debug_mode' => (bool) $config['debug_mode'],
                    'log_level' => sanitize_text_field($config['log_level']),
                    'timezone' => sanitize_text_field($config['timezone']),
                    'currency' => sanitize_text_field($config['currency']),
                    'language' => sanitize_text_field($config['language'])
                );
                
            case 'trading':
                return array(
                    'enabled' => (bool) $config['enabled'],
                    'min_trade_amount' => floatval($config['min_trade_amount']),
                    'max_trade_amount' => floatval($config['max_trade_amount']),
                    'trading_fees' => floatval($config['trading_fees']),
                    'maker_fee' => floatval($config['maker_fee']),
                    'taker_fee' => floatval($config['taker_fee']),
                    'order_timeout' => intval($config['order_timeout']),
                    'max_open_orders' => intval($config['max_open_orders'])
                );
                
            case 'wallet':
                return array(
                    'enabled' => (bool) $config['enabled'],
                    'min_deposit' => floatval($config['min_deposit']),
                    'min_withdrawal' => floatval($config['min_withdrawal']),
                    'withdrawal_fee' => floatval($config['withdrawal_fee']),
                    'max_daily_withdrawal' => floatval($config['max_daily_withdrawal']),
                    'require_kyc_for_withdrawal' => (bool) $config['require_kyc_for_withdrawal'],
                    'cold_storage_percentage' => intval($config['cold_storage_percentage'])
                );
                
            case 'security':
                return array(
                    'enabled' => (bool) $config['enabled'],
                    'two_factor_required' => (bool) $config['two_factor_required'],
                    'ip_whitelist' => array_map('sanitize_text_field', $config['ip_whitelist']),
                    'max_login_attempts' => intval($config['max_login_attempts']),
                    'lockout_duration' => intval($config['lockout_duration']),
                    'session_timeout' => intval($config['session_timeout']),
                    'password_min_length' => intval($config['password_min_length']),
                    'require_strong_password' => (bool) $config['require_strong_password']
                );
                
            case 'kyc':
                return array(
                    'enabled' => (bool) $config['enabled'],
                    'required_for_trading' => (bool) $config['required_for_trading'],
                    'required_for_withdrawal' => (bool) $config['required_for_withdrawal'],
                    'document_types' => array_map('sanitize_text_field', $config['document_types']),
                    'verification_timeout' => intval($config['verification_timeout']),
                    'auto_approve' => (bool) $config['auto_approve']
                );
                
            case 'notifications':
                return array(
                    'enabled' => (bool) $config['enabled'],
                    'email_notifications' => (bool) $config['email_notifications'],
                    'sms_notifications' => (bool) $config['sms_notifications'],
                    'push_notifications' => (bool) $config['push_notifications'],
                    'trading_alerts' => (bool) $config['trading_alerts'],
                    'price_alerts' => (bool) $config['price_alerts'],
                    'security_alerts' => (bool) $config['security_alerts']
                );
                
            case 'api':
                return array(
                    'enabled' => (bool) $config['enabled'],
                    'rate_limit' => intval($config['rate_limit']),
                    'rate_limit_window' => intval($config['rate_limit_window']),
                    'require_api_key' => (bool) $config['require_api_key'],
                    'allowed_ips' => array_map('sanitize_text_field', $config['allowed_ips']),
                    'max_requests_per_minute' => intval($config['max_requests_per_minute'])
                );
                
            case 'liquidity':
                return array(
                    'enabled' => (bool) $config['enabled'],
                    'auto_route_orders' => (bool) $config['auto_route_orders'],
                    'min_liquidity_providers' => intval($config['min_liquidity_providers']),
                    'max_slippage' => floatval($config['max_slippage']),
                    'prefer_lowest_fee' => (bool) $config['prefer_lowest_fee']
                );
                
            default:
                return $config;
        }
    }
    
    /**
     * Apply configuration changes
     */
    private function apply_configuration($section, $config) {
        switch ($section) {
            case 'general':
                if (isset($config['maintenance_mode'])) {
                    update_option('crypto_exchange_maintenance_mode', $config['maintenance_mode']);
                }
                if (isset($config['debug_mode'])) {
                    update_option('crypto_exchange_debug_mode', $config['debug_mode']);
                }
                break;
                
            case 'trading':
                update_option('crypto_exchange_trading_settings', $config);
                break;
                
            case 'wallet':
                update_option('crypto_exchange_wallet_settings', $config);
                break;
                
            case 'security':
                update_option('crypto_exchange_security_settings', $config);
                break;
                
            case 'kyc':
                update_option('crypto_exchange_kyc_settings', $config);
                break;
                
            case 'notifications':
                update_option('crypto_exchange_notification_settings', $config);
                break;
                
            case 'api':
                update_option('crypto_exchange_api_settings', $config);
                break;
                
            case 'liquidity':
                update_option('crypto_exchange_liquidity_settings', $config);
                break;
        }
    }
    
    /**
     * Get module status information
     */
    private function get_module_status_info($module) {
        $status = array(
            'enabled' => $this->is_module_enabled($module),
            'class_exists' => class_exists($this->modules[$module]['class']),
            'dependencies_met' => true,
            'last_error' => null,
            'performance' => null
        );
        
        // Check dependencies
        foreach ($this->modules[$module]['dependencies'] as $dep) {
            if (!$this->is_module_enabled($dep)) {
                $status['dependencies_met'] = false;
                break;
            }
        }
        
        // Get performance metrics if available
        if ($status['enabled'] && $status['class_exists']) {
            $class_name = $this->modules[$module]['class'];
            try {
                $instance = new $class_name();
                if (method_exists($instance, 'get_performance_metrics')) {
                    $status['performance'] = $instance->get_performance_metrics();
                }
            } catch (Exception $e) {
                $status['last_error'] = $e->getMessage();
            }
        }
        
        return $status;
    }
    
    /**
     * Get all settings
     */
    public function get_settings() {
        return $this->settings;
    }
    
    /**
     * Get module information
     */
    public function get_modules() {
        return $this->modules;
    }
    
    /**
     * Get available backups
     */
    public function get_backups() {
        global $wpdb;
        
        $backups = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE 'crypto_exchange_backup_%' 
             ORDER BY option_name DESC"
        );
        
        $formatted_backups = array();
        foreach ($backups as $backup) {
            $data = maybe_unserialize($backup->option_value);
            $formatted_backups[] = array(
                'id' => str_replace('crypto_exchange_backup_', '', $backup->option_name),
                'timestamp' => $data['timestamp'],
                'version' => $data['version']
            );
        }
        
        return $formatted_backups;
    }
}