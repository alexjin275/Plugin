<?php
/**
 * Database management class for Crypto Exchange Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Database {
    
    private $wpdb;
    private $charset_collate;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->charset_collate = $wpdb->get_charset_collate();
    }
    
    /**
     * Create all database tables
     */
    public function create_tables() {
        $this->create_users_table();
        $this->create_wallets_table();
        $this->create_orders_table();
        $this->create_trades_table();
        $this->create_market_data_table();
        $this->create_kyc_documents_table();
        $this->create_transactions_table();
        $this->create_api_keys_table();
        $this->create_audit_logs_table();
        $this->create_trading_pairs_table();
        $this->create_fees_table();
        $this->create_notifications_table();
    }
    
    /**
     * Create users table for crypto exchange specific data
     */
    private function create_users_table() {
        $table_name = $this->wpdb->prefix . 'crypto_users';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            kyc_status varchar(20) DEFAULT 'pending',
            kyc_level int(1) DEFAULT 0,
            trading_limits decimal(20,8) DEFAULT 0,
            daily_trading_volume decimal(20,8) DEFAULT 0,
            monthly_trading_volume decimal(20,8) DEFAULT 0,
            two_fa_enabled tinyint(1) DEFAULT 0,
            two_fa_secret varchar(255) DEFAULT '',
            account_status varchar(20) DEFAULT 'active',
            last_login datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            KEY kyc_status (kyc_status),
            KEY account_status (account_status)
        ) $this->charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create wallets table
     */
    private function create_wallets_table() {
        $table_name = $this->wpdb->prefix . 'crypto_wallets';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            currency varchar(10) NOT NULL,
            balance decimal(20,8) DEFAULT 0,
            locked_balance decimal(20,8) DEFAULT 0,
            address varchar(255) DEFAULT '',
            private_key_encrypted text DEFAULT '',
            wallet_type varchar(20) DEFAULT 'hot',
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY currency (currency),
            KEY is_active (is_active)
        ) $this->charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create orders table
     */
    private function create_orders_table() {
        $table_name = $this->wpdb->prefix . 'crypto_orders';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            pair varchar(20) NOT NULL,
            order_type varchar(20) NOT NULL,
            side varchar(10) NOT NULL,
            amount decimal(20,8) NOT NULL,
            price decimal(20,8) DEFAULT NULL,
            stop_price decimal(20,8) DEFAULT NULL,
            filled_amount decimal(20,8) DEFAULT 0,
            remaining_amount decimal(20,8) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            fee decimal(20,8) DEFAULT 0,
            fee_currency varchar(10) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY pair (pair),
            KEY status (status),
            KEY order_type (order_type),
            KEY side (side)
        ) $this->charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create trades table
     */
    private function create_trades_table() {
        $table_name = $this->wpdb->prefix . 'crypto_trades';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            buy_order_id bigint(20) NOT NULL,
            sell_order_id bigint(20) NOT NULL,
            buyer_id bigint(20) NOT NULL,
            seller_id bigint(20) NOT NULL,
            pair varchar(20) NOT NULL,
            amount decimal(20,8) NOT NULL,
            price decimal(20,8) NOT NULL,
            total decimal(20,8) NOT NULL,
            buyer_fee decimal(20,8) DEFAULT 0,
            seller_fee decimal(20,8) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY buy_order_id (buy_order_id),
            KEY sell_order_id (sell_order_id),
            KEY buyer_id (buyer_id),
            KEY seller_id (seller_id),
            KEY pair (pair)
        ) $this->charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create market data table
     */
    private function create_market_data_table() {
        $table_name = $this->wpdb->prefix . 'crypto_market_data';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            pair varchar(20) NOT NULL,
            price decimal(20,8) NOT NULL,
            volume_24h decimal(20,8) DEFAULT 0,
            change_24h decimal(10,4) DEFAULT 0,
            high_24h decimal(20,8) DEFAULT 0,
            low_24h decimal(20,8) DEFAULT 0,
            market_cap decimal(20,2) DEFAULT 0,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY pair (pair),
            KEY last_updated (last_updated)
        ) $this->charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create KYC documents table
     */
    private function create_kyc_documents_table() {
        $table_name = $this->wpdb->prefix . 'crypto_kyc_documents';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            document_type varchar(50) NOT NULL,
            document_path varchar(500) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            verified_by bigint(20) DEFAULT NULL,
            verified_at datetime DEFAULT NULL,
            rejection_reason text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY document_type (document_type)
        ) $this->charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create transactions table
     */
    private function create_transactions_table() {
        $table_name = $this->wpdb->prefix . 'crypto_transactions';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            transaction_type varchar(20) NOT NULL,
            currency varchar(10) NOT NULL,
            amount decimal(20,8) NOT NULL,
            fee decimal(20,8) DEFAULT 0,
            status varchar(20) DEFAULT 'pending',
            tx_hash varchar(255) DEFAULT '',
            from_address varchar(255) DEFAULT '',
            to_address varchar(255) DEFAULT '',
            block_height bigint(20) DEFAULT NULL,
            confirmations int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY transaction_type (transaction_type),
            KEY status (status),
            KEY currency (currency)
        ) $this->charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create API keys table
     */
    private function create_api_keys_table() {
        $table_name = $this->wpdb->prefix . 'crypto_api_keys';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            api_key varchar(255) NOT NULL,
            api_secret varchar(255) NOT NULL,
            permissions text DEFAULT '',
            is_active tinyint(1) DEFAULT 1,
            last_used datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY api_key (api_key),
            KEY user_id (user_id),
            KEY is_active (is_active)
        ) $this->charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create audit logs table
     */
    private function create_audit_logs_table() {
        $table_name = $this->wpdb->prefix . 'crypto_audit_logs';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            action varchar(100) NOT NULL,
            details text DEFAULT '',
            ip_address varchar(45) DEFAULT '',
            user_agent text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $this->charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create trading pairs table
     */
    private function create_trading_pairs_table() {
        $table_name = $this->wpdb->prefix . 'crypto_trading_pairs';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            pair varchar(20) NOT NULL,
            base_currency varchar(10) NOT NULL,
            quote_currency varchar(10) NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            min_trade_amount decimal(20,8) DEFAULT 0,
            max_trade_amount decimal(20,8) DEFAULT 0,
            price_precision int(2) DEFAULT 8,
            amount_precision int(2) DEFAULT 8,
            maker_fee decimal(10,6) DEFAULT 0,
            taker_fee decimal(10,6) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY pair (pair),
            KEY is_active (is_active)
        ) $this->charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create fees table
     */
    private function create_fees_table() {
        $table_name = $this->wpdb->prefix . 'crypto_fees';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            fee_type varchar(50) NOT NULL,
            currency varchar(10) NOT NULL,
            amount decimal(20,8) NOT NULL,
            percentage decimal(5,4) DEFAULT 0,
            min_amount decimal(20,8) DEFAULT 0,
            max_amount decimal(20,8) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY fee_type (fee_type),
            KEY currency (currency),
            KEY is_active (is_active)
        ) $this->charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create notifications table
     */
    private function create_notifications_table() {
        $table_name = $this->wpdb->prefix . 'crypto_notifications';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type),
            KEY is_read (is_read)
        ) $this->charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Drop all tables
     */
    public function drop_tables() {
        $tables = array(
            $this->wpdb->prefix . 'crypto_notifications',
            $this->wpdb->prefix . 'crypto_fees',
            $this->wpdb->prefix . 'crypto_trading_pairs',
            $this->wpdb->prefix . 'crypto_audit_logs',
            $this->wpdb->prefix . 'crypto_api_keys',
            $this->wpdb->prefix . 'crypto_transactions',
            $this->wpdb->prefix . 'crypto_kyc_documents',
            $this->wpdb->prefix . 'crypto_market_data',
            $this->wpdb->prefix . 'crypto_trades',
            $this->wpdb->prefix . 'crypto_orders',
            $this->wpdb->prefix . 'crypto_wallets',
            $this->wpdb->prefix . 'crypto_users'
        );
        
        foreach ($tables as $table) {
            $this->wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Insert default trading pairs
     */
    public function insert_default_trading_pairs() {
        $pairs = array(
            array('BTC/USD', 'BTC', 'USD', 0.0001, 1000000, 8, 8, 0.001, 0.001),
            array('ETH/USD', 'ETH', 'USD', 0.01, 1000000, 8, 8, 0.001, 0.001),
            array('BNB/USD', 'BNB', 'USD', 0.1, 1000000, 8, 8, 0.001, 0.001),
            array('ADA/USD', 'ADA', 'USD', 1, 1000000, 8, 8, 0.001, 0.001),
            array('SOL/USD', 'SOL', 'USD', 0.01, 1000000, 8, 8, 0.001, 0.001),
            array('DOT/USD', 'DOT', 'USD', 0.1, 1000000, 8, 8, 0.001, 0.001),
            array('MATIC/USD', 'MATIC', 'USD', 1, 1000000, 8, 8, 0.001, 0.001),
            array('AVAX/USD', 'AVAX', 'USD', 0.1, 1000000, 8, 8, 0.001, 0.001)
        );
        
        foreach ($pairs as $pair) {
            $this->wpdb->insert(
                $this->wpdb->prefix . 'crypto_trading_pairs',
                array(
                    'pair' => $pair[0],
                    'base_currency' => $pair[1],
                    'quote_currency' => $pair[2],
                    'min_trade_amount' => $pair[3],
                    'max_trade_amount' => $pair[4],
                    'price_precision' => $pair[5],
                    'amount_precision' => $pair[6],
                    'maker_fee' => $pair[7],
                    'taker_fee' => $pair[8]
                ),
                array('%s', '%s', '%s', '%f', '%f', '%d', '%d', '%f', '%f')
            );
        }
    }
    
    /**
     * Insert default fees
     */
    public function insert_default_fees() {
        $fees = array(
            array('trading', 'BTC', 0, 0.001, 0, 0),
            array('trading', 'ETH', 0, 0.001, 0, 0),
            array('withdrawal', 'BTC', 0.0005, 0, 0, 0),
            array('withdrawal', 'ETH', 0.01, 0, 0, 0),
            array('withdrawal', 'BNB', 0.1, 0, 0, 0),
            array('deposit', 'BTC', 0, 0, 0, 0),
            array('deposit', 'ETH', 0, 0, 0, 0),
            array('deposit', 'BNB', 0, 0, 0, 0)
        );
        
        foreach ($fees as $fee) {
            $this->wpdb->insert(
                $this->wpdb->prefix . 'crypto_fees',
                array(
                    'fee_type' => $fee[0],
                    'currency' => $fee[1],
                    'amount' => $fee[2],
                    'percentage' => $fee[3],
                    'min_amount' => $fee[4],
                    'max_amount' => $fee[5]
                ),
                array('%s', '%s', '%f', '%f', '%f', '%f')
            );
        }
    }
}
