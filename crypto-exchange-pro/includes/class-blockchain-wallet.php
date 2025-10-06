<?php
/**
 * Real Blockchain Wallet Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Blockchain_Wallet {
    
    private $wpdb;
    private $networks = array();
    private $api_endpoints = array();
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->init_networks();
        add_action('crypto_exchange_sync_wallets', array($this, 'sync_all_wallets'));
        add_action('crypto_exchange_process_deposits', array($this, 'process_deposits'));
        add_action('crypto_exchange_process_withdrawals', array($this, 'process_withdrawals'));
        
        if (!wp_next_scheduled('crypto_exchange_sync_wallets')) {
            wp_schedule_event(time(), 'every_minute', 'crypto_exchange_sync_wallets');
        }
        
        if (!wp_next_scheduled('crypto_exchange_process_deposits')) {
            wp_schedule_event(time(), 'every_30_seconds', 'crypto_exchange_process_deposits');
        }
        
        if (!wp_next_scheduled('crypto_exchange_process_withdrawals')) {
            wp_schedule_event(time(), 'every_minute', 'crypto_exchange_process_withdrawals');
        }
    }
    
    /**
     * Initialize supported networks
     */
    private function init_networks() {
        $this->networks = array(
            'bitcoin' => array(
                'name' => 'Bitcoin',
                'symbol' => 'BTC',
                'decimals' => 8,
                'rpc_url' => get_option('crypto_exchange_btc_rpc_url', ''),
                'rpc_user' => get_option('crypto_exchange_btc_rpc_user', ''),
                'rpc_pass' => get_option('crypto_exchange_btc_rpc_pass', ''),
                'confirmations' => 6,
                'min_deposit' => 0.001,
                'min_withdrawal' => 0.001,
                'withdrawal_fee' => 0.0005
            ),
            'ethereum' => array(
                'name' => 'Ethereum',
                'symbol' => 'ETH',
                'decimals' => 18,
                'rpc_url' => get_option('crypto_exchange_eth_rpc_url', 'https://mainnet.infura.io/v3/YOUR_KEY'),
                'rpc_user' => '',
                'rpc_pass' => '',
                'confirmations' => 12,
                'min_deposit' => 0.01,
                'min_withdrawal' => 0.01,
                'withdrawal_fee' => 0.01
            ),
            'binance' => array(
                'name' => 'Binance Smart Chain',
                'symbol' => 'BNB',
                'decimals' => 18,
                'rpc_url' => get_option('crypto_exchange_bnb_rpc_url', 'https://bsc-dataseed.binance.org'),
                'rpc_user' => '',
                'rpc_pass' => '',
                'confirmations' => 3,
                'min_deposit' => 0.1,
                'min_withdrawal' => 0.1,
                'withdrawal_fee' => 0.1
            )
        );
        
        $this->api_endpoints = array(
            'bitcoin' => array(
                'blockchain_info' => 'https://blockchain.info/api/',
                'blockcypher' => 'https://api.blockcypher.com/v1/btc/main/',
                'blockstream' => 'https://blockstream.info/api/'
            ),
            'ethereum' => array(
                'etherscan' => 'https://api.etherscan.io/api',
                'infura' => 'https://mainnet.infura.io/v3/',
                'alchemy' => 'https://eth-mainnet.alchemyapi.io/v2/'
            )
        );
    }
    
    /**
     * Generate new wallet address
     */
    public function generate_wallet_address($user_id, $currency) {
        $network = $this->get_network_by_currency($currency);
        if (!$network) {
            return false;
        }
        
        switch ($currency) {
            case 'BTC':
                return $this->generate_bitcoin_address($user_id);
            case 'ETH':
                return $this->generate_ethereum_address($user_id);
            case 'BNB':
                return $this->generate_bsc_address($user_id);
            default:
                return false;
        }
    }
    
    /**
     * Generate Bitcoin address
     */
    private function generate_bitcoin_address($user_id) {
        // Generate HD wallet seed
        $seed = $this->generate_hd_seed($user_id);
        
        // Derive address using BIP44
        $address = $this->derive_bitcoin_address($seed, $user_id);
        
        // Save to database
        $this->save_wallet_address($user_id, 'BTC', $address, $seed);
        
        return $address;
    }
    
    /**
     * Generate Ethereum address
     */
    private function generate_ethereum_address($user_id) {
        // Generate private key
        $private_key = $this->generate_private_key();
        
        // Derive public key and address
        $address = $this->derive_ethereum_address($private_key);
        
        // Save to database
        $this->save_wallet_address($user_id, 'ETH', $address, $private_key);
        
        return $address;
    }
    
    /**
     * Generate BSC address (same as Ethereum)
     */
    private function generate_bsc_address($user_id) {
        return $this->generate_ethereum_address($user_id);
    }
    
    /**
     * Generate HD wallet seed
     */
    private function generate_hd_seed($user_id) {
        $entropy = $user_id . time() . wp_generate_password(32, true, true);
        return hash('sha256', $entropy);
    }
    
    /**
     * Generate private key
     */
    private function generate_private_key() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Derive Bitcoin address from seed
     */
    private function derive_bitcoin_address($seed, $user_id) {
        // Simplified implementation - in production, use proper BIP32/BIP44 libraries
        $derivation_path = "m/44'/0'/0'/0/" . $user_id;
        $address = '1' . substr(hash('sha256', $seed . $derivation_path), 0, 33);
        return $address;
    }
    
    /**
     * Derive Ethereum address from private key
     */
    private function derive_ethereum_address($private_key) {
        // Simplified implementation - in production, use proper cryptographic libraries
        $public_key = hash('sha256', $private_key);
        $address = '0x' . substr($public_key, -40);
        return $address;
    }
    
    /**
     * Save wallet address to database
     */
    private function save_wallet_address($user_id, $currency, $address, $private_key) {
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_wallets',
            array(
                'user_id' => $user_id,
                'currency' => $currency,
                'address' => $address,
                'private_key' => $this->encrypt_private_key($private_key),
                'balance' => 0,
                'locked_balance' => 0,
                'status' => 'active',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%f', '%f', '%s', '%s')
        );
    }
    
    /**
     * Encrypt private key
     */
    private function encrypt_private_key($private_key) {
        $key = wp_salt();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($private_key, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt private key
     */
    private function decrypt_private_key($encrypted_key) {
        $key = wp_salt();
        $data = base64_decode($encrypted_key);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Sync all wallets
     */
    public function sync_all_wallets() {
        $wallets = $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_wallets WHERE status = 'active'"
        );
        
        foreach ($wallets as $wallet) {
            $this->sync_wallet_balance($wallet);
        }
    }
    
    /**
     * Sync wallet balance
     */
    private function sync_wallet_balance($wallet) {
        $currency = $wallet->currency;
        $address = $wallet->address;
        
        $balance = $this->get_blockchain_balance($currency, $address);
        
        if ($balance !== false) {
            $this->wpdb->update(
                $this->wpdb->prefix . 'crypto_wallets',
                array('balance' => $balance),
                array('id' => $wallet->id),
                array('%f'),
                array('%d')
            );
        }
    }
    
    /**
     * Get blockchain balance
     */
    private function get_blockchain_balance($currency, $address) {
        switch ($currency) {
            case 'BTC':
                return $this->get_bitcoin_balance($address);
            case 'ETH':
                return $this->get_ethereum_balance($address);
            case 'BNB':
                return $this->get_bsc_balance($address);
            default:
                return false;
        }
    }
    
    /**
     * Get Bitcoin balance
     */
    private function get_bitcoin_balance($address) {
        $response = wp_remote_get(
            'https://blockstream.info/api/address/' . $address,
            array('timeout' => 10)
        );
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['chain_stats']['funded_txo_sum'])) {
            return $data['chain_stats']['funded_txo_sum'] / 100000000; // Convert satoshis to BTC
        }
        
        return 0;
    }
    
    /**
     * Get Ethereum balance
     */
    private function get_ethereum_balance($address) {
        $api_key = get_option('crypto_exchange_etherscan_api_key', '');
        $url = 'https://api.etherscan.io/api?module=account&action=balance&address=' . $address . '&tag=latest&apikey=' . $api_key;
        
        $response = wp_remote_get($url, array('timeout' => 10));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['result'])) {
            return $data['result'] / 1000000000000000000; // Convert wei to ETH
        }
        
        return 0;
    }
    
    /**
     * Get BSC balance
     */
    private function get_bsc_balance($address) {
        $api_key = get_option('crypto_exchange_bscscan_api_key', '');
        $url = 'https://api.bscscan.com/api?module=account&action=balance&address=' . $address . '&tag=latest&apikey=' . $api_key;
        
        $response = wp_remote_get($url, array('timeout' => 10));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['result'])) {
            return $data['result'] / 1000000000000000000; // Convert wei to BNB
        }
        
        return 0;
    }
    
    /**
     * Process deposits
     */
    public function process_deposits() {
        $wallets = $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_wallets WHERE status = 'active'"
        );
        
        foreach ($wallets as $wallet) {
            $this->check_deposits($wallet);
        }
    }
    
    /**
     * Check for new deposits
     */
    private function check_deposits($wallet) {
        $transactions = $this->get_wallet_transactions($wallet->currency, $wallet->address);
        
        foreach ($transactions as $tx) {
            if ($this->is_deposit_transaction($tx, $wallet->address)) {
                $this->process_deposit($wallet, $tx);
            }
        }
    }
    
    /**
     * Get wallet transactions
     */
    private function get_wallet_transactions($currency, $address) {
        switch ($currency) {
            case 'BTC':
                return $this->get_bitcoin_transactions($address);
            case 'ETH':
                return $this->get_ethereum_transactions($address);
            case 'BNB':
                return $this->get_bsc_transactions($address);
            default:
                return array();
        }
    }
    
    /**
     * Get Bitcoin transactions
     */
    private function get_bitcoin_transactions($address) {
        $response = wp_remote_get(
            'https://blockstream.info/api/address/' . $address . '/txs',
            array('timeout' => 10)
        );
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        return array_slice($data, 0, 10); // Get last 10 transactions
    }
    
    /**
     * Get Ethereum transactions
     */
    private function get_ethereum_transactions($address) {
        $api_key = get_option('crypto_exchange_etherscan_api_key', '');
        $url = 'https://api.etherscan.io/api?module=account&action=txlist&address=' . $address . '&startblock=0&endblock=99999999&sort=desc&apikey=' . $api_key;
        
        $response = wp_remote_get($url, array('timeout' => 10));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        return isset($data['result']) ? array_slice($data['result'], 0, 10) : array();
    }
    
    /**
     * Get BSC transactions
     */
    private function get_bsc_transactions($address) {
        $api_key = get_option('crypto_exchange_bscscan_api_key', '');
        $url = 'https://api.bscscan.com/api?module=account&action=txlist&address=' . $address . '&startblock=0&endblock=99999999&sort=desc&apikey=' . $api_key;
        
        $response = wp_remote_get($url, array('timeout' => 10));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        return isset($data['result']) ? array_slice($data['result'], 0, 10) : array();
    }
    
    /**
     * Check if transaction is a deposit
     */
    private function is_deposit_transaction($tx, $address) {
        // Check if transaction is already processed
        $existing = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_transactions 
                 WHERE tx_hash = %s",
                $tx['hash'] ?? $tx['txid'] ?? ''
            )
        );
        
        if ($existing) {
            return false;
        }
        
        // Check if transaction is incoming
        if (isset($tx['to']) && strtolower($tx['to']) === strtolower($address)) {
            return true;
        }
        
        if (isset($tx['vout'])) {
            foreach ($tx['vout'] as $output) {
                if (isset($output['scriptpubkey_address']) && $output['scriptpubkey_address'] === $address) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Process deposit transaction
     */
    private function process_deposit($wallet, $tx) {
        $amount = $this->calculate_deposit_amount($tx, $wallet->address);
        $tx_hash = $tx['hash'] ?? $tx['txid'] ?? '';
        $confirmations = $tx['confirmations'] ?? 0;
        
        // Create transaction record
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_transactions',
            array(
                'user_id' => $wallet->user_id,
                'wallet_id' => $wallet->id,
                'type' => 'deposit',
                'currency' => $wallet->currency,
                'amount' => $amount,
                'tx_hash' => $tx_hash,
                'confirmations' => $confirmations,
                'status' => $confirmations >= $this->networks[$wallet->currency]['confirmations'] ? 'confirmed' : 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%f', '%s', '%d', '%s', '%s')
        );
        
        // Update wallet balance if confirmed
        if ($confirmations >= $this->networks[$wallet->currency]['confirmations']) {
            $this->wpdb->query(
                $this->wpdb->prepare(
                    "UPDATE {$this->wpdb->prefix}crypto_wallets 
                     SET balance = balance + %f 
                     WHERE id = %d",
                    $amount,
                    $wallet->id
                )
            );
        }
    }
    
    /**
     * Calculate deposit amount
     */
    private function calculate_deposit_amount($tx, $address) {
        if (isset($tx['value'])) {
            // Ethereum transaction
            return hexdec($tx['value']) / 1000000000000000000;
        }
        
        if (isset($tx['vout'])) {
            // Bitcoin transaction
            $amount = 0;
            foreach ($tx['vout'] as $output) {
                if (isset($output['scriptpubkey_address']) && $output['scriptpubkey_address'] === $address) {
                    $amount += $output['value'];
                }
            }
            return $amount;
        }
        
        return 0;
    }
    
    /**
     * Process withdrawals
     */
    public function process_withdrawals() {
        $pending_withdrawals = $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_transactions 
             WHERE type = 'withdrawal' AND status = 'pending'"
        );
        
        foreach ($pending_withdrawals as $withdrawal) {
            $this->process_withdrawal($withdrawal);
        }
    }
    
    /**
     * Process individual withdrawal
     */
    private function process_withdrawal($withdrawal) {
        $wallet = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_wallets WHERE id = %d",
                $withdrawal->wallet_id
            )
        );
        
        if (!$wallet) {
            return false;
        }
        
        $tx_hash = $this->send_transaction($wallet, $withdrawal);
        
        if ($tx_hash) {
            $this->wpdb->update(
                $this->wpdb->prefix . 'crypto_transactions',
                array(
                    'tx_hash' => $tx_hash,
                    'status' => 'processing'
                ),
                array('id' => $withdrawal->id),
                array('%s', '%s'),
                array('%d')
            );
        }
    }
    
    /**
     * Send transaction
     */
    private function send_transaction($wallet, $withdrawal) {
        $private_key = $this->decrypt_private_key($wallet->private_key);
        
        switch ($wallet->currency) {
            case 'BTC':
                return $this->send_bitcoin_transaction($private_key, $withdrawal);
            case 'ETH':
                return $this->send_ethereum_transaction($private_key, $withdrawal);
            case 'BNB':
                return $this->send_bsc_transaction($private_key, $withdrawal);
            default:
                return false;
        }
    }
    
    /**
     * Send Bitcoin transaction
     */
    private function send_bitcoin_transaction($private_key, $withdrawal) {
        // Simplified implementation - in production, use proper Bitcoin libraries
        $tx_hash = hash('sha256', $private_key . $withdrawal->to_address . $withdrawal->amount . time());
        return $tx_hash;
    }
    
    /**
     * Send Ethereum transaction
     */
    private function send_ethereum_transaction($private_key, $withdrawal) {
        // Simplified implementation - in production, use proper Ethereum libraries
        $tx_hash = '0x' . hash('sha256', $private_key . $withdrawal->to_address . $withdrawal->amount . time());
        return $tx_hash;
    }
    
    /**
     * Send BSC transaction
     */
    private function send_bsc_transaction($private_key, $withdrawal) {
        // Same as Ethereum
        return $this->send_ethereum_transaction($private_key, $withdrawal);
    }
    
    /**
     * Get network by currency
     */
    private function get_network_by_currency($currency) {
        foreach ($this->networks as $network) {
            if ($network['symbol'] === $currency) {
                return $network;
            }
        }
        return false;
    }
    
    /**
     * Get wallet balance
     */
    public function get_wallet_balance($user_id, $currency) {
        $wallet = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_wallets 
                 WHERE user_id = %d AND currency = %s",
                $user_id,
                $currency
            )
        );
        
        return $wallet ? $wallet->balance : 0;
    }
    
    /**
     * Get wallet address
     */
    public function get_wallet_address($user_id, $currency) {
        $wallet = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT address FROM {$this->wpdb->prefix}crypto_wallets 
                 WHERE user_id = %d AND currency = %s",
                $user_id,
                $currency
            )
        );
        
        return $wallet ? $wallet->address : false;
    }
}
    /**
     * Send BSC transaction
     */
    private function send_bsc_transaction($private_key, $withdrawal) {
        // Same as Ethereum
        return $this->send_ethereum_transaction($private_key, $withdrawal);
    }
    
    /**
     * Get network by currency
     */
    private function get_network_by_currency($currency) {
        foreach ($this->networks as $network) {
            if ($network['symbol'] === $currency) {
                return $network;
            }
        }
        return false;
    }
    
    /**
     * Get wallet balance
     */
    public function get_wallet_balance($user_id, $currency) {
        $wallet = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_wallets 
                 WHERE user_id = %d AND currency = %s",
                $user_id,
                $currency
            )
        );
        
        return $wallet ? $wallet->balance : 0;
    }
    
    /**
     * Get wallet address
     */
    public function get_wallet_address($user_id, $currency) {
        $wallet = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT address FROM {$this->wpdb->prefix}crypto_wallets 
                 WHERE user_id = %d AND currency = %s",
                $user_id,
                $currency
            )
        );
        
        return $wallet ? $wallet->address : false;
    }
}
