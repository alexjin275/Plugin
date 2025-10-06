<?php
/**
 * Crypto Wallet Service Providers Management System
 * Integrates with major wallet service providers
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Wallet_Providers {
    
    private $wpdb;
    private $providers = array();
    private $active_providers = array();
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->init_providers();
        $this->init_hooks();
        $this->load_active_providers();
    }
    
    /**
     * Initialize available wallet service providers
     */
    private function init_providers() {
        $this->providers = array(
            'coinbase_wallet' => array(
                'name' => 'Coinbase Wallet',
                'description' => 'Coinbase Wallet SDK integration',
                'class' => 'Crypto_Exchange_Coinbase_Wallet',
                'supported_coins' => array('BTC', 'ETH', 'LTC', 'BCH', 'XRP', 'ADA', 'DOT', 'LINK'),
                'features' => array('hot_wallet', 'cold_storage', 'multi_sig', 'api_access'),
                'fees' => array('deposit' => 0, 'withdrawal' => 0.001, 'transaction' => 0.0005),
                'limits' => array('min_deposit' => 0.001, 'max_deposit' => 1000000, 'min_withdrawal' => 0.01),
                'api_required' => true,
                'webhook_support' => true,
                'status' => 'active'
            ),
            'metamask' => array(
                'name' => 'MetaMask',
                'description' => 'MetaMask wallet integration',
                'class' => 'Crypto_Exchange_MetaMask_Wallet',
                'supported_coins' => array('ETH', 'USDT', 'USDC', 'DAI', 'LINK', 'UNI', 'AAVE'),
                'features' => array('hot_wallet', 'browser_extension', 'mobile_app', 'dapp_integration'),
                'fees' => array('deposit' => 0, 'withdrawal' => 0.001, 'transaction' => 0.0005),
                'limits' => array('min_deposit' => 0.001, 'max_deposit' => 1000000, 'min_withdrawal' => 0.01),
                'api_required' => false,
                'webhook_support' => false,
                'status' => 'active'
            ),
            'trust_wallet' => array(
                'name' => 'Trust Wallet',
                'description' => 'Trust Wallet integration',
                'class' => 'Crypto_Exchange_Trust_Wallet',
                'supported_coins' => array('BTC', 'ETH', 'BNB', 'ADA', 'DOT', 'LINK', 'UNI', 'CAKE'),
                'features' => array('hot_wallet', 'mobile_app', 'multi_chain', 'staking'),
                'fees' => array('deposit' => 0, 'withdrawal' => 0.001, 'transaction' => 0.0005),
                'limits' => array('min_deposit' => 0.001, 'max_deposit' => 1000000, 'min_withdrawal' => 0.01),
                'api_required' => false,
                'webhook_support' => false,
                'status' => 'active'
            ),
            'ledger' => array(
                'name' => 'Ledger Hardware Wallet',
                'description' => 'Ledger hardware wallet integration',
                'class' => 'Crypto_Exchange_Ledger_Wallet',
                'supported_coins' => array('BTC', 'ETH', 'LTC', 'BCH', 'XRP', 'ADA', 'DOT', 'LINK'),
                'features' => array('hardware_wallet', 'cold_storage', 'multi_sig', 'offline_signing'),
                'fees' => array('deposit' => 0, 'withdrawal' => 0.001, 'transaction' => 0.0005),
                'limits' => array('min_deposit' => 0.001, 'max_deposit' => 1000000, 'min_withdrawal' => 0.01),
                'api_required' => false,
                'webhook_support' => false,
                'status' => 'active'
            ),
            'trezor' => array(
                'name' => 'Trezor Hardware Wallet',
                'description' => 'Trezor hardware wallet integration',
                'class' => 'Crypto_Exchange_Trezor_Wallet',
                'supported_coins' => array('BTC', 'ETH', 'LTC', 'BCH', 'XRP', 'ADA', 'DOT', 'LINK'),
                'features' => array('hardware_wallet', 'cold_storage', 'multi_sig', 'offline_signing'),
                'fees' => array('deposit' => 0, 'withdrawal' => 0.001, 'transaction' => 0.0005),
                'limits' => array('min_deposit' => 0.001, 'max_deposit' => 1000000, 'min_withdrawal' => 0.01),
                'api_required' => false,
                'webhook_support' => false,
                'status' => 'active'
            ),
            'phantom' => array(
                'name' => 'Phantom Wallet',
                'description' => 'Phantom Solana wallet integration',
                'class' => 'Crypto_Exchange_Phantom_Wallet',
                'supported_coins' => array('SOL', 'USDC', 'USDT', 'RAY', 'SRM', 'ORCA'),
                'features' => array('hot_wallet', 'browser_extension', 'mobile_app', 'dapp_integration', 'staking'),
                'fees' => array('deposit' => 0, 'withdrawal' => 0.001, 'transaction' => 0.0005),
                'limits' => array('min_deposit' => 0.001, 'max_deposit' => 1000000, 'min_withdrawal' => 0.01),
                'api_required' => false,
                'webhook_support' => false,
                'status' => 'active'
            ),
            'keplr' => array(
                'name' => 'Keplr Wallet',
                'description' => 'Keplr Cosmos ecosystem wallet',
                'class' => 'Crypto_Exchange_Keplr_Wallet',
                'supported_coins' => array('ATOM', 'OSMO', 'JUNO', 'SCRT', 'AKASH', 'REGEN'),
                'features' => array('hot_wallet', 'browser_extension', 'mobile_app', 'staking', 'governance'),
                'fees' => array('deposit' => 0, 'withdrawal' => 0.001, 'transaction' => 0.0005),
                'limits' => array('min_deposit' => 0.001, 'max_deposit' => 1000000, 'min_withdrawal' => 0.01),
                'api_required' => false,
                'webhook_support' => false,
                'status' => 'active'
            ),
            'polkadot_js' => array(
                'name' => 'Polkadot.js Wallet',
                'description' => 'Polkadot.js wallet integration',
                'class' => 'Crypto_Exchange_Polkadot_Wallet',
                'supported_coins' => array('DOT', 'KSM', 'ASTR', 'MOVR', 'GLMR', 'PHA'),
                'features' => array('hot_wallet', 'browser_extension', 'staking', 'governance', 'parachains'),
                'fees' => array('deposit' => 0, 'withdrawal' => 0.001, 'transaction' => 0.0005),
                'limits' => array('min_deposit' => 0.001, 'max_deposit' => 1000000, 'min_withdrawal' => 0.01),
                'api_required' => false,
                'webhook_support' => false,
                'status' => 'active'
            ),
            'algorand_wallet' => array(
                'name' => 'Algorand Wallet',
                'description' => 'Algorand official wallet integration',
                'class' => 'Crypto_Exchange_Algorand_Wallet',
                'supported_coins' => array('ALGO', 'USDC', 'USDT', 'YLDY', 'OPUL', 'SMILE'),
                'features' => array('hot_wallet', 'mobile_app', 'staking', 'governance', 'defi'),
                'fees' => array('deposit' => 0, 'withdrawal' => 0.001, 'transaction' => 0.0005),
                'limits' => array('min_deposit' => 0.001, 'max_deposit' => 1000000, 'min_withdrawal' => 0.01),
                'api_required' => false,
                'webhook_support' => false,
                'status' => 'active'
            ),
            'near_wallet' => array(
                'name' => 'NEAR Wallet',
                'description' => 'NEAR Protocol wallet integration',
                'class' => 'Crypto_Exchange_Near_Wallet',
                'supported_coins' => array('NEAR', 'USDC', 'USDT', 'AURORA', 'REF', 'SKYWARD'),
                'features' => array('hot_wallet', 'browser_extension', 'mobile_app', 'staking', 'defi'),
                'fees' => array('deposit' => 0, 'withdrawal' => 0.001, 'transaction' => 0.0005),
                'limits' => array('min_deposit' => 0.001, 'max_deposit' => 1000000, 'min_withdrawal' => 0.01),
                'api_required' => false,
                'webhook_support' => false,
                'status' => 'active'
            ),
            'avalanche_wallet' => array(
                'name' => 'Avalanche Wallet',
                'description' => 'Avalanche wallet integration',
                'class' => 'Crypto_Exchange_Avalanche_Wallet',
                'supported_coins' => array('AVAX', 'USDC', 'USDT', 'JOE', 'PNG', 'QI'),
                'features' => array('hot_wallet', 'browser_extension', 'mobile_app', 'staking', 'defi'),
                'fees' => array('deposit' => 0, 'withdrawal' => 0.001, 'transaction' => 0.0005),
                'limits' => array('min_deposit' => 0.001, 'max_deposit' => 1000000, 'min_withdrawal' => 0.01),
                'api_required' => false,
                'webhook_support' => false,
                'status' => 'active'
            ),
            'fantom_wallet' => array(
                'name' => 'Fantom Wallet',
                'description' => 'Fantom wallet integration',
                'class' => 'Crypto_Exchange_Fantom_Wallet',
                'supported_coins' => array('FTM', 'USDC', 'USDT', 'BOO', 'SPIRIT', 'TOMB'),
                'features' => array('hot_wallet', 'browser_extension', 'mobile_app', 'staking', 'defi'),
                'fees' => array('deposit' => 0, 'withdrawal' => 0.001, 'transaction' => 0.0005),
                'limits' => array('min_deposit' => 0.001, 'max_deposit' => 1000000, 'min_withdrawal' => 0.01),
                'api_required' => false,
                'webhook_support' => false,
                'status' => 'active'
            ),
            'polygon_wallet' => array(
                'name' => 'Polygon Wallet',
                'description' => 'Polygon wallet integration',
                'class' => 'Crypto_Exchange_Polygon_Wallet',
                'supported_coins' => array('MATIC', 'USDC', 'USDT', 'QUICK', 'SUSHI', 'AAVE'),
                'features' => array('hot_wallet', 'browser_extension', 'mobile_app', 'staking', 'defi'),
                'fees' => array('deposit' => 0, 'withdrawal' => 0.001, 'transaction' => 0.0005),
                'limits' => array('min_deposit' => 0.001, 'max_deposit' => 1000000, 'min_withdrawal' => 0.01),
                'api_required' => false,
                'webhook_support' => false,
                'status' => 'active'
            ),
            'binance_chain_wallet' => array(
                'name' => 'Binance Chain Wallet',
                'description' => 'Binance Chain wallet integration',
                'class' => 'Crypto_Exchange_Binance_Chain_Wallet',
                'supported_coins' => array('BNB', 'BUSD', 'USDT', 'CAKE', 'AUTO', 'BUNNY'),
                'features' => array('hot_wallet', 'browser_extension', 'mobile_app', 'staking', 'defi'),
                'fees' => array('deposit' => 0, 'withdrawal' => 0.001, 'transaction' => 0.0005),
                'limits' => array('min_deposit' => 0.001, 'max_deposit' => 1000000, 'min_withdrawal' => 0.01),
                'api_required' => false,
                'webhook_support' => false,
                'status' => 'active'
            ),
            'harmony_wallet' => array(
                'name' => 'Harmony Wallet',
                'description' => 'Harmony wallet integration',
                'class' => 'Crypto_Exchange_Harmony_Wallet',
                'supported_coins' => array('ONE', 'USDC', 'USDT', 'VIPER', 'JEWEL', 'FARM'),
                'features' => array('hot_wallet', 'browser_extension', 'mobile_app', 'staking', 'defi'),
                'fees' => array('deposit' => 0, 'withdrawal' => 0.001, 'transaction' => 0.0005),
                'limits' => array('min_deposit' => 0.001, 'max_deposit' => 1000000, 'min_withdrawal' => 0.01),
                'api_required' => false,
                'webhook_support' => false,
                'status' => 'active'
            ),
            'cronos_wallet' => array(
                'name' => 'Cronos Wallet',
                'description' => 'Cronos wallet integration',
                'class' => 'Crypto_Exchange_Cronos_Wallet',
                'supported_coins' => array('CRO', 'USDC', 'USDT', 'VVS', 'TECTONIC', 'MMF'),
                'features' => array('hot_wallet', 'browser_extension', 'mobile_app', 'staking', 'defi'),
                'fees' => array('deposit' => 0, 'withdrawal' => 0.001, 'transaction' => 0.0005),
                'limits' => array('min_deposit' => 0.001, 'max_deposit' => 1000000, 'min_withdrawal' => 0.01),
                'api_required' => false,
                'webhook_support' => false,
                'status' => 'active'
            )
        );
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_crypto_exchange_add_wallet_provider', array($this, 'add_wallet_provider'));
        add_action('wp_ajax_crypto_exchange_update_wallet_provider', array($this, 'update_wallet_provider'));
        add_action('wp_ajax_crypto_exchange_delete_wallet_provider', array($this, 'delete_wallet_provider'));
        add_action('wp_ajax_crypto_exchange_toggle_wallet_provider', array($this, 'toggle_wallet_provider'));
        add_action('wp_ajax_crypto_exchange_test_wallet_provider', array($this, 'test_wallet_provider'));
        add_action('wp_ajax_crypto_exchange_get_wallet_provider_data', array($this, 'get_wallet_provider_data'));
        add_action('wp_ajax_crypto_exchange_sync_wallet_provider', array($this, 'sync_wallet_provider'));
        add_action('wp_ajax_crypto_exchange_get_wallet_balances', array($this, 'get_wallet_balances'));
        add_action('wp_ajax_crypto_exchange_create_wallet_address', array($this, 'create_wallet_address'));
        add_action('wp_ajax_crypto_exchange_send_transaction', array($this, 'send_transaction'));
        add_action('wp_ajax_crypto_exchange_get_transaction_history', array($this, 'get_transaction_history'));
    }
    
    /**
     * Load active providers
     */
    private function load_active_providers() {
        $providers = $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_wallet_providers WHERE status = 'active'"
        );
        
        foreach ($providers as $provider) {
            $this->active_providers[$provider->provider_type] = $provider;
        }
    }
    
    /**
     * Add wallet provider
     */
    public function add_wallet_provider() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $provider_type = sanitize_text_field($_POST['provider_type']);
        
        if (!isset($this->providers[$provider_type])) {
            wp_send_json_error('Invalid provider type');
        }
        
        $provider_data = array(
            'provider_type' => $provider_type,
            'name' => sanitize_text_field($_POST['name']),
            'api_key' => sanitize_text_field($_POST['api_key']),
            'api_secret' => sanitize_text_field($_POST['api_secret']),
            'api_passphrase' => sanitize_text_field($_POST['api_passphrase']),
            'webhook_url' => esc_url_raw($_POST['webhook_url']),
            'rpc_url' => esc_url_raw($_POST['rpc_url']),
            'network' => sanitize_text_field($_POST['network']),
            'priority' => intval($_POST['priority']),
            'fees' => json_encode(array(
                'deposit' => floatval($_POST['deposit_fee']),
                'withdrawal' => floatval($_POST['withdrawal_fee']),
                'transaction' => floatval($_POST['transaction_fee'])
            )),
            'limits' => json_encode(array(
                'min_deposit' => floatval($_POST['min_deposit']),
                'max_deposit' => floatval($_POST['max_deposit']),
                'min_withdrawal' => floatval($_POST['min_withdrawal']),
                'max_withdrawal' => floatval($_POST['max_withdrawal'])
            )),
            'supported_coins' => json_encode(array_map('sanitize_text_field', $_POST['supported_coins'])),
            'features' => json_encode(array_map('sanitize_text_field', $_POST['features'])),
            'config' => json_encode(array(
                'timeout' => intval($_POST['timeout']),
                'retry_attempts' => intval($_POST['retry_attempts']),
                'rate_limit' => intval($_POST['rate_limit']),
                'auto_sync' => (bool) $_POST['auto_sync'],
                'cold_storage' => (bool) $_POST['cold_storage'],
                'multi_sig' => (bool) $_POST['multi_sig']
            )),
            'status' => sanitize_text_field($_POST['status']),
            'created_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_wallet_providers',
            $provider_data
        );
        
        if ($result) {
            wp_send_json_success('Wallet provider added successfully');
        } else {
            wp_send_json_error('Failed to add wallet provider');
        }
    }
    
    /**
     * Update wallet provider
     */
    public function update_wallet_provider() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $provider_id = intval($_POST['provider_id']);
        
        $provider_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'api_key' => sanitize_text_field($_POST['api_key']),
            'api_secret' => sanitize_text_field($_POST['api_secret']),
            'api_passphrase' => sanitize_text_field($_POST['api_passphrase']),
            'webhook_url' => esc_url_raw($_POST['webhook_url']),
            'rpc_url' => esc_url_raw($_POST['rpc_url']),
            'network' => sanitize_text_field($_POST['network']),
            'priority' => intval($_POST['priority']),
            'fees' => json_encode(array(
                'deposit' => floatval($_POST['deposit_fee']),
                'withdrawal' => floatval($_POST['withdrawal_fee']),
                'transaction' => floatval($_POST['transaction_fee'])
            )),
            'limits' => json_encode(array(
                'min_deposit' => floatval($_POST['min_deposit']),
                'max_deposit' => floatval($_POST['max_deposit']),
                'min_withdrawal' => floatval($_POST['min_withdrawal']),
                'max_withdrawal' => floatval($_POST['max_withdrawal'])
            )),
            'supported_coins' => json_encode(array_map('sanitize_text_field', $_POST['supported_coins'])),
            'features' => json_encode(array_map('sanitize_text_field', $_POST['features'])),
            'config' => json_encode(array(
                'timeout' => intval($_POST['timeout']),
                'retry_attempts' => intval($_POST['retry_attempts']),
                'rate_limit' => intval($_POST['rate_limit']),
                'auto_sync' => (bool) $_POST['auto_sync'],
                'cold_storage' => (bool) $_POST['cold_storage'],
                'multi_sig' => (bool) $_POST['multi_sig']
            )),
            'status' => sanitize_text_field($_POST['status']),
            'updated_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_wallet_providers',
            $provider_data,
            array('id' => $provider_id)
        );
        
        if ($result !== false) {
            wp_send_json_success('Wallet provider updated successfully');
        } else {
            wp_send_json_error('Failed to update wallet provider');
        }
    }
    
    /**
     * Delete wallet provider
     */
    public function delete_wallet_provider() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $provider_id = intval($_POST['provider_id']);
        
        // Check if provider has active wallets
        $active_wallets = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_wallets WHERE provider_id = %d AND status = 'active'",
            $provider_id
        ));
        
        if ($active_wallets > 0) {
            wp_send_json_error('Cannot delete provider with active wallets');
        }
        
        $result = $this->wpdb->delete(
            $this->wpdb->prefix . 'crypto_wallet_providers',
            array('id' => $provider_id)
        );
        
        if ($result) {
            wp_send_json_success('Wallet provider deleted successfully');
        } else {
            wp_send_json_error('Failed to delete wallet provider');
        }
    }
    
    /**
     * Toggle wallet provider status
     */
    public function toggle_wallet_provider() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $provider_id = intval($_POST['provider_id']);
        $current_status = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT status FROM {$this->wpdb->prefix}crypto_wallet_providers WHERE id = %d",
            $provider_id
        ));
        
        $new_status = $current_status === 'active' ? 'inactive' : 'active';
        
        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_wallet_providers',
            array('status' => $new_status),
            array('id' => $provider_id)
        );
        
        if ($result !== false) {
            wp_send_json_success('Wallet provider status updated');
        } else {
            wp_send_json_error('Failed to update wallet provider status');
        }
    }
    
    /**
     * Test wallet provider connection
     */
    public function test_wallet_provider() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $provider_id = intval($_POST['provider_id']);
        $provider = $this->get_provider($provider_id);
        
        if (!$provider) {
            wp_send_json_error('Provider not found');
        }
        
        $class_name = $this->providers[$provider->provider_type]['class'];
        
        if (!class_exists($class_name)) {
            wp_send_json_error('Provider class not found');
        }
        
        try {
            $instance = new $class_name($provider);
            $test_result = $instance->test_connection();
            
            if ($test_result['success']) {
                wp_send_json_success($test_result['message']);
            } else {
                wp_send_json_error($test_result['message']);
            }
        } catch (Exception $e) {
            wp_send_json_error('Provider test failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get wallet provider data
     */
    public function get_wallet_provider_data() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $provider_id = intval($_POST['provider_id']);
        $provider = $this->get_provider($provider_id);
        
        if ($provider) {
            // Decode JSON fields
            $provider->fees = json_decode($provider->fees, true);
            $provider->limits = json_decode($provider->limits, true);
            $provider->supported_coins = json_decode($provider->supported_coins, true);
            $provider->features = json_decode($provider->features, true);
            $provider->config = json_decode($provider->config, true);
            
            wp_send_json_success($provider);
        } else {
            wp_send_json_error('Provider not found');
        }
    }
    
    /**
     * Sync wallet provider data
     */
    public function sync_wallet_provider() {
        check_ajax_referer('crypto_exchange_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $provider_id = intval($_POST['provider_id']);
        $provider = $this->get_provider($provider_id);
        
        if (!$provider) {
            wp_send_json_error('Provider not found');
        }
        
        $class_name = $this->providers[$provider->provider_type]['class'];
        
        if (!class_exists($class_name)) {
            wp_send_json_error('Provider class not found');
        }
        
        try {
            $instance = new $class_name($provider);
            $sync_result = $instance->sync_data();
            
            if ($sync_result['success']) {
                wp_send_json_success($sync_result['message']);
            } else {
                wp_send_json_error($sync_result['message']);
            }
        } catch (Exception $e) {
            wp_send_json_error('Provider sync failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get wallet balances
     */
    public function get_wallet_balances() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in');
        }
        
        $user_id = get_current_user_id();
        $provider_id = intval($_POST['provider_id']);
        
        $provider = $this->get_provider($provider_id);
        if (!$provider) {
            wp_send_json_error('Provider not found');
        }
        
        $class_name = $this->providers[$provider->provider_type]['class'];
        
        if (!class_exists($class_name)) {
            wp_send_json_error('Provider class not found');
        }
        
        try {
            $instance = new $class_name($provider);
            $balances = $instance->get_balances($user_id);
            
            wp_send_json_success($balances);
        } catch (Exception $e) {
            wp_send_json_error('Failed to get balances: ' . $e->getMessage());
        }
    }
    
    /**
     * Create wallet address
     */
    public function create_wallet_address() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in');
        }
        
        $user_id = get_current_user_id();
        $provider_id = intval($_POST['provider_id']);
        $coin = sanitize_text_field($_POST['coin']);
        
        $provider = $this->get_provider($provider_id);
        if (!$provider) {
            wp_send_json_error('Provider not found');
        }
        
        $class_name = $this->providers[$provider->provider_type]['class'];
        
        if (!class_exists($class_name)) {
            wp_send_json_error('Provider class not found');
        }
        
        try {
            $instance = new $class_name($provider);
            $address = $instance->create_address($user_id, $coin);
            
            wp_send_json_success($address);
        } catch (Exception $e) {
            wp_send_json_error('Failed to create address: ' . $e->getMessage());
        }
    }
    
    /**
     * Send transaction
     */
    public function send_transaction() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in');
        }
        
        $user_id = get_current_user_id();
        $provider_id = intval($_POST['provider_id']);
        $to_address = sanitize_text_field($_POST['to_address']);
        $amount = floatval($_POST['amount']);
        $coin = sanitize_text_field($_POST['coin']);
        
        $provider = $this->get_provider($provider_id);
        if (!$provider) {
            wp_send_json_error('Provider not found');
        }
        
        $class_name = $this->providers[$provider->provider_type]['class'];
        
        if (!class_exists($class_name)) {
            wp_send_json_error('Provider class not found');
        }
        
        try {
            $instance = new $class_name($provider);
            $transaction = $instance->send_transaction($user_id, $to_address, $amount, $coin);
            
            wp_send_json_success($transaction);
        } catch (Exception $e) {
            wp_send_json_error('Failed to send transaction: ' . $e->getMessage());
        }
    }
    
    /**
     * Get transaction history
     */
    public function get_transaction_history() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in');
        }
        
        $user_id = get_current_user_id();
        $provider_id = intval($_POST['provider_id']);
        $coin = sanitize_text_field($_POST['coin']);
        $limit = intval($_POST['limit']) ?: 50;
        
        $provider = $this->get_provider($provider_id);
        if (!$provider) {
            wp_send_json_error('Provider not found');
        }
        
        $class_name = $this->providers[$provider->provider_type]['class'];
        
        if (!class_exists($class_name)) {
            wp_send_json_error('Provider class not found');
        }
        
        try {
            $instance = new $class_name($provider);
            $transactions = $instance->get_transaction_history($user_id, $coin, $limit);
            
            wp_send_json_success($transactions);
        } catch (Exception $e) {
            wp_send_json_error('Failed to get transaction history: ' . $e->getMessage());
        }
    }
    
    /**
     * Get provider by ID
     */
    public function get_provider($provider_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_wallet_providers WHERE id = %d",
                $provider_id
            )
        );
    }
    
    /**
     * Get all providers
     */
    public function get_all_providers() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_wallet_providers ORDER BY priority ASC, name ASC"
        );
    }
    
    /**
     * Get active providers
     */
    public function get_active_providers() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_wallet_providers WHERE status = 'active' ORDER BY priority ASC"
        );
    }
    
    /**
     * Get providers for coin
     */
    public function get_providers_for_coin($coin) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_wallet_providers 
                 WHERE status = 'active' 
                 AND JSON_CONTAINS(supported_coins, %s)
                 ORDER BY priority ASC",
                '"' . $coin . '"'
            )
        );
    }
    
    /**
     * Get best provider for coin
     */
    public function get_best_provider_for_coin($coin, $operation = 'deposit') {
        $providers = $this->get_providers_for_coin($coin);
        
        if (empty($providers)) {
            return null;
        }
        
        $best_provider = null;
        $best_score = -1;
        
        foreach ($providers as $provider) {
            $score = $this->calculate_provider_score($provider, $coin, $operation);
            
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
    private function calculate_provider_score($provider, $coin, $operation) {
        $score = 0;
        
        // Priority score (higher priority = higher score)
        $score += (100 - $provider->priority) * 10;
        
        // Fee score (lower fee = higher score)
        $fees = json_decode($provider->fees, true);
        $operation_fee = $fees[$operation] ?? 0;
        $score += (1 - $operation_fee) * 100;
        
        // Reliability score
        $reliability = $this->get_provider_reliability($provider->id);
        $score += $reliability * 30;
        
        // Uptime score
        $uptime = $this->get_provider_uptime($provider->id);
        $score += $uptime * 20;
        
        return $score;
    }
    
    /**
     * Get provider reliability score
     */
    private function get_provider_reliability($provider_id) {
        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_transactions
                 FROM {$this->wpdb->prefix}crypto_wallet_transactions 
                 WHERE provider_id = %d 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                $provider_id
            )
        );
        
        if ($stats->total_transactions > 0) {
            return $stats->successful_transactions / $stats->total_transactions;
        }
        
        return 0.5; // Default reliability
    }
    
    /**
     * Get provider uptime
     */
    private function get_provider_uptime($provider_id) {
        $uptime = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT AVG(uptime) FROM {$this->wpdb->prefix}crypto_wallet_provider_stats 
                 WHERE provider_id = %d 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                $provider_id
            )
        );
        
        return $uptime ?: 0.95; // Default uptime
    }
    
    /**
     * Create wallet providers table
     */
    public function create_wallet_providers_table() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_wallet_providers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            provider_type varchar(50) NOT NULL,
            name varchar(100) NOT NULL,
            api_key varchar(255) DEFAULT NULL,
            api_secret varchar(255) DEFAULT NULL,
            api_passphrase varchar(255) DEFAULT NULL,
            webhook_url varchar(255) DEFAULT NULL,
            rpc_url varchar(255) DEFAULT NULL,
            network varchar(50) DEFAULT 'mainnet',
            priority int(11) DEFAULT 1,
            fees text,
            limits text,
            supported_coins text,
            features text,
            config text,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY provider_type (provider_type),
            KEY status (status),
            KEY priority (priority)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create wallet transactions table
        $this->create_wallet_transactions_table();
        
        // Create wallet provider stats table
        $this->create_wallet_provider_stats_table();
    }
    
    /**
     * Create wallet transactions table
     */
    private function create_wallet_transactions_table() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_wallet_transactions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            provider_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            transaction_id varchar(100) NOT NULL,
            coin varchar(20) NOT NULL,
            from_address varchar(255) DEFAULT NULL,
            to_address varchar(255) NOT NULL,
            amount decimal(20,8) NOT NULL,
            fee decimal(20,8) DEFAULT 0.00000000,
            status varchar(20) DEFAULT 'pending',
            block_height bigint(20) DEFAULT NULL,
            confirmations int(11) DEFAULT 0,
            required_confirmations int(11) DEFAULT 6,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY provider_id (provider_id),
            KEY user_id (user_id),
            KEY transaction_id (transaction_id),
            KEY coin (coin),
            KEY status (status),
            KEY created_at (created_at)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create wallet provider stats table
     */
    private function create_wallet_provider_stats_table() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_wallet_provider_stats (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            provider_id bigint(20) NOT NULL,
            total_transactions int(11) DEFAULT 0,
            successful_transactions int(11) DEFAULT 0,
            failed_transactions int(11) DEFAULT 0,
            total_volume decimal(20,8) DEFAULT 0.00000000,
            total_fees decimal(20,8) DEFAULT 0.00000000,
            avg_processing_time int(11) DEFAULT 0,
            uptime decimal(5,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY provider_id (provider_id),
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
                p.provider_type,
                p.status,
                COUNT(wt.id) as total_transactions,
                SUM(CASE WHEN wt.status = 'completed' THEN 1 ELSE 0 END) as successful_transactions,
                SUM(CASE WHEN wt.status = 'failed' THEN 1 ELSE 0 END) as failed_transactions,
                SUM(wt.amount) as total_volume,
                SUM(wt.fee) as total_fees,
                AVG(wps.avg_processing_time) as avg_processing_time,
                AVG(wps.uptime) as uptime
             FROM {$this->wpdb->prefix}crypto_wallet_providers p
             LEFT JOIN {$this->wpdb->prefix}crypto_wallet_transactions wt ON p.id = wt.provider_id
             LEFT JOIN {$this->wpdb->prefix}crypto_wallet_provider_stats wps ON p.id = wps.provider_id
             WHERE wt.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY p.id, p.name, p.provider_type, p.status
             ORDER BY p.priority ASC"
        );
        
        return $stats;
    }
    
    /**
     * Get available providers
     */
    public function get_available_providers() {
        return $this->providers;
    }
    
    /**
     * Get provider by type
     */
    public function get_provider_by_type($provider_type) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_wallet_providers WHERE provider_type = %s AND status = 'active'",
                $provider_type
            )
        );
    }
}