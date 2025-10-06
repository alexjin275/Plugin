<?php
/**
 * Coin Management System
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Coin_Management {
    
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        add_action('wp_ajax_crypto_exchange_add_coin', array($this, 'add_coin'));
        add_action('wp_ajax_crypto_exchange_update_coin', array($this, 'update_coin'));
        add_action('wp_ajax_crypto_exchange_delete_coin', array($this, 'delete_coin'));
        add_action('wp_ajax_crypto_exchange_toggle_coin', array($this, 'toggle_coin'));
        add_action('wp_ajax_crypto_exchange_get_coin_data', array($this, 'get_coin_data'));
    }
    
    /**
     * Add new coin
     */
    public function add_coin() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $coin_data = array(
            'symbol' => sanitize_text_field($_POST['symbol']),
            'name' => sanitize_text_field($_POST['name']),
            'decimals' => intval($_POST['decimals']),
            'min_deposit' => floatval($_POST['min_deposit']),
            'min_withdrawal' => floatval($_POST['min_withdrawal']),
            'withdrawal_fee' => floatval($_POST['withdrawal_fee']),
            'trading_fee' => floatval($_POST['trading_fee']),
            'status' => sanitize_text_field($_POST['status']),
            'type' => sanitize_text_field($_POST['type']),
            'contract_address' => sanitize_text_field($_POST['contract_address']),
            'rpc_url' => esc_url_raw($_POST['rpc_url']),
            'explorer_url' => esc_url_raw($_POST['explorer_url']),
            'icon_url' => esc_url_raw($_POST['icon_url']),
            'description' => sanitize_textarea_field($_POST['description']),
            'created_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_coins',
            $coin_data
        );
        
        if ($result) {
            // Create wallet for all users
            $this->create_wallets_for_coin($coin_data['symbol']);
            
            wp_send_json_success('Coin added successfully');
        } else {
            wp_send_json_error('Failed to add coin');
        }
    }
    
    /**
     * Update coin
     */
    public function update_coin() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $coin_id = intval($_POST['coin_id']);
        $coin_data = array(
            'symbol' => sanitize_text_field($_POST['symbol']),
            'name' => sanitize_text_field($_POST['name']),
            'decimals' => intval($_POST['decimals']),
            'min_deposit' => floatval($_POST['min_deposit']),
            'min_withdrawal' => floatval($_POST['min_withdrawal']),
            'withdrawal_fee' => floatval($_POST['withdrawal_fee']),
            'trading_fee' => floatval($_POST['trading_fee']),
            'status' => sanitize_text_field($_POST['status']),
            'type' => sanitize_text_field($_POST['type']),
            'contract_address' => sanitize_text_field($_POST['contract_address']),
            'rpc_url' => esc_url_raw($_POST['rpc_url']),
            'explorer_url' => esc_url_raw($_POST['explorer_url']),
            'icon_url' => esc_url_raw($_POST['icon_url']),
            'description' => sanitize_textarea_field($_POST['description']),
            'updated_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_coins',
            $coin_data,
            array('id' => $coin_id)
        );
        
        if ($result !== false) {
            wp_send_json_success('Coin updated successfully');
        } else {
            wp_send_json_error('Failed to update coin');
        }
    }
    
    /**
     * Delete coin
     */
    public function delete_coin() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $coin_id = intval($_POST['coin_id']);
        
        // Check if coin has active trades
        $active_trades = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_trades WHERE trading_pair LIKE %s",
            '%' . $this->wpdb->esc_like($this->get_coin_symbol($coin_id)) . '%'
        ));
        
        if ($active_trades > 0) {
            wp_send_json_error('Cannot delete coin with active trades');
        }
        
        $result = $this->wpdb->delete(
            $this->wpdb->prefix . 'crypto_coins',
            array('id' => $coin_id)
        );
        
        if ($result) {
            wp_send_json_success('Coin deleted successfully');
        } else {
            wp_send_json_error('Failed to delete coin');
        }
    }
    
    /**
     * Toggle coin status
     */
    public function toggle_coin() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $coin_id = intval($_POST['coin_id']);
        $current_status = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT status FROM {$this->wpdb->prefix}crypto_coins WHERE id = %d",
            $coin_id
        ));
        
        $new_status = $current_status === 'active' ? 'inactive' : 'active';
        
        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_coins',
            array('status' => $new_status),
            array('id' => $coin_id)
        );
        
        if ($result !== false) {
            wp_send_json_success('Coin status updated');
        } else {
            wp_send_json_error('Failed to update coin status');
        }
    }
    
    /**
     * Get coin data
     */
    public function get_coin_data() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $coin_id = intval($_POST['coin_id']);
        $coin = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}crypto_coins WHERE id = %d",
            $coin_id
        ), ARRAY_A);
        
        if ($coin) {
            wp_send_json_success($coin);
        } else {
            wp_send_json_error('Coin not found');
        }
    }
    
    /**
     * Get all coins
     */
    public function get_all_coins() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_coins ORDER BY symbol ASC",
            ARRAY_A
        );
    }
    
    /**
     * Get active coins
     */
    public function get_active_coins() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_coins WHERE status = 'active' ORDER BY symbol ASC",
            ARRAY_A
        );
    }
    
    /**
     * Create wallets for new coin
     */
    private function create_wallets_for_coin($symbol) {
        $users = get_users();
        
        foreach ($users as $user) {
            $wallet = new Crypto_Exchange_Wallet();
            $wallet->create_user_wallet($user->ID, $symbol, 'hot');
        }
    }
    
    /**
     * Get coin symbol by ID
     */
    private function get_coin_symbol($coin_id) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT symbol FROM {$this->wpdb->prefix}crypto_coins WHERE id = %d",
            $coin_id
        ));
    }
    
    /**
     * Create coins table
     */
    public function create_coins_table() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_coins (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            symbol varchar(20) NOT NULL,
            name varchar(100) NOT NULL,
            decimals int(11) DEFAULT 8,
            min_deposit decimal(20,8) DEFAULT 0.00000001,
            min_withdrawal decimal(20,8) DEFAULT 0.00000001,
            withdrawal_fee decimal(20,8) DEFAULT 0.001,
            trading_fee decimal(8,4) DEFAULT 0.25,
            status varchar(20) DEFAULT 'active',
            type varchar(20) DEFAULT 'crypto',
            contract_address varchar(255) DEFAULT NULL,
            rpc_url varchar(255) DEFAULT NULL,
            explorer_url varchar(255) DEFAULT NULL,
            icon_url varchar(255) DEFAULT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY symbol (symbol),
            KEY status (status),
            KEY type (type)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Insert default coins
        $this->insert_default_coins();
    }
    
    /**
     * Insert default coins
     */
    private function insert_default_coins() {
        $default_coins = array(
            array(
                'symbol' => 'BTC',
                'name' => 'Bitcoin',
                'decimals' => 8,
                'min_deposit' => 0.0001,
                'min_withdrawal' => 0.001,
                'withdrawal_fee' => 0.0005,
                'trading_fee' => 0.1,
                'type' => 'crypto',
                'explorer_url' => 'https://blockstream.info',
                'description' => 'Bitcoin - Digital Gold'
            ),
            array(
                'symbol' => 'ETH',
                'name' => 'Ethereum',
                'decimals' => 18,
                'min_deposit' => 0.01,
                'min_withdrawal' => 0.01,
                'withdrawal_fee' => 0.005,
                'trading_fee' => 0.1,
                'type' => 'crypto',
                'explorer_url' => 'https://etherscan.io',
                'description' => 'Ethereum - Smart Contract Platform'
            ),
            array(
                'symbol' => 'USDT',
                'name' => 'Tether USD',
                'decimals' => 6,
                'min_deposit' => 10,
                'min_withdrawal' => 10,
                'withdrawal_fee' => 1,
                'trading_fee' => 0.1,
                'type' => 'crypto',
                'contract_address' => '0xdAC17F958D2ee523a2206206994597C13D831ec7',
                'explorer_url' => 'https://etherscan.io',
                'description' => 'Tether USD - Stablecoin'
            ),
            array(
                'symbol' => 'USDC',
                'name' => 'USD Coin',
                'decimals' => 6,
                'min_deposit' => 10,
                'min_withdrawal' => 10,
                'withdrawal_fee' => 1,
                'trading_fee' => 0.1,
                'type' => 'crypto',
                'contract_address' => '0xA0b86a33E6441b8C4C8C0C4C0C4C0C4C0C4C0C4C',
                'explorer_url' => 'https://etherscan.io',
                'description' => 'USD Coin - Stablecoin'
            ),
            array(
                'symbol' => 'BNB',
                'name' => 'Binance Coin',
                'decimals' => 18,
                'min_deposit' => 0.1,
                'min_withdrawal' => 0.1,
                'withdrawal_fee' => 0.0005,
                'trading_fee' => 0.1,
                'type' => 'crypto',
                'explorer_url' => 'https://bscscan.com',
                'description' => 'Binance Coin - BSC Native Token'
            )
        );
        
        foreach ($default_coins as $coin) {
            $this->wpdb->insert(
                $this->wpdb->prefix . 'crypto_coins',
                $coin
            );
        }
    }
    
    /**
     * Get coin statistics
     */
    public function get_coin_stats() {
        $stats = array();
        
        // Total coins
        $stats['total_coins'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_coins"
        );
        
        // Active coins
        $stats['active_coins'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_coins WHERE status = 'active'"
        );
        
        // Inactive coins
        $stats['inactive_coins'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_coins WHERE status = 'inactive'"
        );
        
        // Crypto coins
        $stats['crypto_coins'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_coins WHERE type = 'crypto'"
        );
        
        // Token coins
        $stats['token_coins'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_coins WHERE type = 'token'"
        );
        
        return $stats;
    }
}
