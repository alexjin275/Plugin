<?php
/**
 * Wallet Addresses Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Wallet_Addresses {
    
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->create_wallet_addresses_table();
    }
    
    /**
     * Create wallet addresses table
     */
    public function create_wallet_addresses_table() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_wallet_addresses (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            address varchar(255) NOT NULL,
            coin varchar(20) NOT NULL,
            provider varchar(50) NOT NULL,
            label varchar(100) DEFAULT NULL,
            is_primary tinyint(1) DEFAULT 0,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY address (address),
            KEY coin (coin),
            KEY provider (provider),
            KEY status (status)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Add wallet address
     */
    public function add_address($user_id, $address, $coin, $provider, $label = null, $is_primary = false) {
        // If this is set as primary, unset other primary addresses for this user/coin
        if ($is_primary) {
            $this->wpdb->update(
                $this->wpdb->prefix . 'crypto_wallet_addresses',
                array('is_primary' => 0),
                array(
                    'user_id' => $user_id,
                    'coin' => $coin,
                    'provider' => $provider
                )
            );
        }
        
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_wallet_addresses',
            array(
                'user_id' => $user_id,
                'address' => $address,
                'coin' => $coin,
                'provider' => $provider,
                'label' => $label,
                'is_primary' => $is_primary ? 1 : 0,
                'created_at' => current_time('mysql')
            )
        );
        
        return $result ? $this->wpdb->insert_id : false;
    }
    
    /**
     * Get user addresses
     */
    public function get_user_addresses($user_id, $coin = null, $provider = null) {
        $where = array('user_id' => $user_id);
        
        if ($coin) {
            $where['coin'] = $coin;
        }
        
        if ($provider) {
            $where['provider'] = $provider;
        }
        
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_wallet_addresses 
                 WHERE user_id = %d" . 
                ($coin ? " AND coin = %s" : "") . 
                ($provider ? " AND provider = %s" : "") . 
                " ORDER BY is_primary DESC, created_at ASC",
                $user_id,
                $coin,
                $provider
            )
        );
    }
    
    /**
     * Get primary address for user/coin/provider
     */
    public function get_primary_address($user_id, $coin, $provider) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_wallet_addresses 
                 WHERE user_id = %d AND coin = %s AND provider = %s AND is_primary = 1",
                $user_id,
                $coin,
                $provider
            )
        );
    }
    
    /**
     * Set primary address
     */
    public function set_primary_address($address_id, $user_id) {
        // Get address details
        $address = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_wallet_addresses WHERE id = %d",
                $address_id
            )
        );
        
        if (!$address) {
            return false;
        }
        
        // Unset other primary addresses for this user/coin/provider
        $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_wallet_addresses',
            array('is_primary' => 0),
            array(
                'user_id' => $user_id,
                'coin' => $address->coin,
                'provider' => $address->provider
            )
        );
        
        // Set this address as primary
        return $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_wallet_addresses',
            array('is_primary' => 1),
            array('id' => $address_id)
        );
    }
    
    /**
     * Update address
     */
    public function update_address($address_id, $data) {
        $allowed_fields = array('label', 'status', 'is_primary');
        $update_data = array();
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $update_data['updated_at'] = current_time('mysql');
        
        return $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_wallet_addresses',
            $update_data,
            array('id' => $address_id)
        );
    }
    
    /**
     * Delete address
     */
    public function delete_address($address_id) {
        return $this->wpdb->delete(
            $this->wpdb->prefix . 'crypto_wallet_addresses',
            array('id' => $address_id)
        );
    }
    
    /**
     * Get address by ID
     */
    public function get_address($address_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_wallet_addresses WHERE id = %d",
                $address_id
            )
        );
    }
    
    /**
     * Get addresses by provider
     */
    public function get_addresses_by_provider($provider) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_wallet_addresses 
                 WHERE provider = %s AND status = 'active'
                 ORDER BY created_at DESC",
                $provider
            )
        );
    }
    
    /**
     * Get address statistics
     */
    public function get_address_stats() {
        $stats = array();
        
        // Total addresses
        $stats['total_addresses'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_wallet_addresses"
        );
        
        // Active addresses
        $stats['active_addresses'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_wallet_addresses WHERE status = 'active'"
        );
        
        // Addresses by provider
        $stats['by_provider'] = $this->wpdb->get_results(
            "SELECT provider, COUNT(*) as count 
             FROM {$this->wpdb->prefix}crypto_wallet_addresses 
             WHERE status = 'active'
             GROUP BY provider"
        );
        
        // Addresses by coin
        $stats['by_coin'] = $this->wpdb->get_results(
            "SELECT coin, COUNT(*) as count 
             FROM {$this->wpdb->prefix}crypto_wallet_addresses 
             WHERE status = 'active'
             GROUP BY coin"
        );
        
        return $stats;
    }
    
    /**
     * Validate address format
     */
    public function validate_address($address, $coin) {
        $validators = array(
            'BTC' => function($addr) {
                return preg_match('/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/', $addr) || 
                       preg_match('/^bc1[a-z0-9]{39,59}$/', $addr);
            },
            'ETH' => function($addr) {
                return preg_match('/^0x[a-fA-F0-9]{40}$/', $addr);
            },
            'SOL' => function($addr) {
                return preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $addr);
            },
            'BNB' => function($addr) {
                return preg_match('/^bnb[a-z0-9]{39}$/', $addr);
            },
            'ADA' => function($addr) {
                return preg_match('/^addr1[a-z0-9]{98}$/', $addr);
            },
            'DOT' => function($addr) {
                return preg_match('/^[1-9A-HJ-NP-Za-km-z]{47,48}$/', $addr);
            }
        );
        
        if (isset($validators[$coin])) {
            return $validators[$coin]($address);
        }
        
        // Default validation - basic length check
        return strlen($address) >= 20 && strlen($address) <= 100;
    }
    
    /**
     * Generate QR code for address
     */
    public function generate_qr_code($address, $coin) {
        $qr_data = array(
            'address' => $address,
            'coin' => $coin,
            'timestamp' => time()
        );
        
        $qr_string = json_encode($qr_data);
        
        // In a real implementation, you would use a QR code library
        // For now, return the data string
        return $qr_string;
    }
    
    /**
     * Get address usage statistics
     */
    public function get_address_usage($address_id) {
        global $wpdb;
        
        $usage = array();
        
        // Get transaction count
        $usage['transaction_count'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}crypto_wallet_transactions 
             WHERE from_address = %s OR to_address = %s",
            $address_id,
            $address_id
        ));
        
        // Get total volume
        $usage['total_volume'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$wpdb->prefix}crypto_wallet_transactions 
             WHERE from_address = %s OR to_address = %s",
            $address_id,
            $address_id
        ));
        
        // Get last transaction
        $usage['last_transaction'] = $wpdb->get_var($wpdb->prepare(
            "SELECT created_at FROM {$wpdb->prefix}crypto_wallet_transactions 
             WHERE from_address = %s OR to_address = %s
             ORDER BY created_at DESC LIMIT 1",
            $address_id,
            $address_id
        ));
        
        return $usage;
    }
}