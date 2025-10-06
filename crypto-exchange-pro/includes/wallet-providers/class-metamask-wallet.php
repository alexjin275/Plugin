<?php
/**
 * MetaMask Wallet Provider Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_MetaMask_Wallet {
    
    private $provider;
    private $rpc_url;
    private $network_id;
    
    public function __construct($provider = null) {
        if ($provider) {
            $this->provider = $provider;
            $this->rpc_url = $provider->rpc_url ?: 'https://mainnet.infura.io/v3/YOUR_PROJECT_ID';
            $this->network_id = $this->get_network_id($provider->network);
        }
    }
    
    /**
     * Test connection to Ethereum network
     */
    public function test_connection() {
        try {
            $response = $this->make_rpc_request('eth_blockNumber', array());
            
            if ($response && isset($response['result'])) {
                return array(
                    'success' => true,
                    'message' => 'MetaMask connection successful. Block: ' . hexdec($response['result'])
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Invalid response from Ethereum network'
                );
            }
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get account balances
     */
    public function get_balances($user_id) {
        try {
            // Get user's Ethereum addresses
            $addresses = $this->get_user_addresses($user_id);
            $balances = array();
            
            foreach ($addresses as $address) {
                // Get ETH balance
                $eth_balance = $this->get_eth_balance($address);
                if ($eth_balance > 0) {
                    $balances[] = array(
                        'currency' => 'ETH',
                        'balance' => $eth_balance,
                        'address' => $address
                    );
                }
                
                // Get ERC-20 token balances
                $token_balances = $this->get_token_balances($address);
                $balances = array_merge($balances, $token_balances);
            }
            
            return $balances;
        } catch (Exception $e) {
            throw new Exception('Failed to get balances: ' . $e->getMessage());
        }
    }
    
    /**
     * Create deposit address
     */
    public function create_address($user_id, $coin) {
        try {
            // Generate new Ethereum address
            $address = $this->generate_ethereum_address();
            
            // Store address for user
            $this->store_user_address($user_id, $address, $coin);
            
            return array(
                'address' => $address,
                'coin' => $coin,
                'provider' => 'metamask'
            );
        } catch (Exception $e) {
            throw new Exception('Failed to create address: ' . $e->getMessage());
        }
    }
    
    /**
     * Send transaction
     */
    public function send_transaction($user_id, $to_address, $amount, $coin) {
        try {
            $from_address = $this->get_user_primary_address($user_id);
            
            if ($coin === 'ETH') {
                $transaction = $this->send_eth_transaction($from_address, $to_address, $amount);
            } else {
                $transaction = $this->send_token_transaction($from_address, $to_address, $amount, $coin);
            }
            
            return array(
                'transaction_id' => $transaction['hash'],
                'status' => 'pending',
                'amount' => $amount,
                'coin' => $coin,
                'to_address' => $to_address,
                'from_address' => $from_address
            );
        } catch (Exception $e) {
            throw new Exception('Failed to send transaction: ' . $e->getMessage());
        }
    }
    
    /**
     * Get transaction history
     */
    public function get_transaction_history($user_id, $coin = null, $limit = 50) {
        try {
            $addresses = $this->get_user_addresses($user_id);
            $transactions = array();
            
            foreach ($addresses as $address) {
                $txs = $this->get_address_transactions($address, $coin, $limit);
                $transactions = array_merge($transactions, $txs);
            }
            
            // Sort by timestamp and limit
            usort($transactions, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });
            
            return array_slice($transactions, 0, $limit);
        } catch (Exception $e) {
            throw new Exception('Failed to get transaction history: ' . $e->getMessage());
        }
    }
    
    /**
     * Sync data with blockchain
     */
    public function sync_data() {
        try {
            // Sync all user balances
            $users = get_users();
            $total_balances = 0;
            $total_transactions = 0;
            
            foreach ($users as $user) {
                $balances = $this->get_balances($user->ID);
                $total_balances += count($balances);
                
                $transactions = $this->get_transaction_history($user->ID, null, 10);
                $total_transactions += count($transactions);
            }
            
            return array(
                'success' => true,
                'message' => 'Data synced successfully. ' . $total_balances . ' balances, ' . $total_transactions . ' transactions.'
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Make RPC request to Ethereum network
     */
    private function make_rpc_request($method, $params = array()) {
        $data = array(
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => 1
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->rpc_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }
        
        if ($http_code >= 400) {
            throw new Exception('RPC error: HTTP ' . $http_code);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get ETH balance for address
     */
    private function get_eth_balance($address) {
        $response = $this->make_rpc_request('eth_getBalance', array($address, 'latest'));
        
        if ($response && isset($response['result'])) {
            return hexdec($response['result']) / pow(10, 18); // Convert wei to ETH
        }
        
        return 0;
    }
    
    /**
     * Get ERC-20 token balances
     */
    private function get_token_balances($address) {
        $balances = array();
        $tokens = array('USDT', 'USDC', 'DAI', 'LINK', 'UNI', 'AAVE');
        
        foreach ($tokens as $token) {
            $balance = $this->get_token_balance($address, $token);
            if ($balance > 0) {
                $balances[] = array(
                    'currency' => $token,
                    'balance' => $balance,
                    'address' => $address
                );
            }
        }
        
        return $balances;
    }
    
    /**
     * Get specific token balance
     */
    private function get_token_balance($address, $token) {
        $token_address = $this->get_token_address($token);
        if (!$token_address) return 0;
        
        $data = '0x70a08231' . str_pad(substr($address, 2), 64, '0', STR_PAD_LEFT);
        
        $response = $this->make_rpc_request('eth_call', array(
            array(
                'to' => $token_address,
                'data' => $data
            ),
            'latest'
        ));
        
        if ($response && isset($response['result'])) {
            $decimals = $this->get_token_decimals($token);
            return hexdec($response['result']) / pow(10, $decimals);
        }
        
        return 0;
    }
    
    /**
     * Send ETH transaction
     */
    private function send_eth_transaction($from_address, $to_address, $amount) {
        // This would typically require user's private key or MetaMask integration
        // For now, we'll simulate the transaction
        return array(
            'hash' => '0x' . bin2hex(random_bytes(32)),
            'status' => 'pending'
        );
    }
    
    /**
     * Send ERC-20 token transaction
     */
    private function send_token_transaction($from_address, $to_address, $amount, $token) {
        // This would typically require user's private key or MetaMask integration
        // For now, we'll simulate the transaction
        return array(
            'hash' => '0x' . bin2hex(random_bytes(32)),
            'status' => 'pending'
        );
    }
    
    /**
     * Get user's Ethereum addresses
     */
    private function get_user_addresses($user_id) {
        global $wpdb;
        
        $addresses = $wpdb->get_col($wpdb->prepare(
            "SELECT address FROM {$wpdb->prefix}crypto_wallet_addresses 
             WHERE user_id = %d AND provider = 'metamask'",
            $user_id
        ));
        
        return $addresses ?: array();
    }
    
    /**
     * Store user address
     */
    private function store_user_address($user_id, $address, $coin) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'crypto_wallet_addresses',
            array(
                'user_id' => $user_id,
                'address' => $address,
                'coin' => $coin,
                'provider' => 'metamask',
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Generate Ethereum address
     */
    private function generate_ethereum_address() {
        // This is a simplified version - in production, use proper key generation
        return '0x' . bin2hex(random_bytes(20));
    }
    
    /**
     * Get user's primary address
     */
    private function get_user_primary_address($user_id) {
        global $wpdb;
        
        $address = $wpdb->get_var($wpdb->prepare(
            "SELECT address FROM {$wpdb->prefix}crypto_wallet_addresses 
             WHERE user_id = %d AND provider = 'metamask' 
             ORDER BY created_at ASC LIMIT 1",
            $user_id
        ));
        
        if (!$address) {
            $address = $this->generate_ethereum_address();
            $this->store_user_address($user_id, $address, 'ETH');
        }
        
        return $address;
    }
    
    /**
     * Get address transactions
     */
    private function get_address_transactions($address, $coin = null, $limit = 50) {
        // This would typically use a blockchain explorer API
        // For now, return empty array
        return array();
    }
    
    /**
     * Get token address
     */
    private function get_token_address($token) {
        $addresses = array(
            'USDT' => '0xdAC17F958D2ee523a2206206994597C13D831ec7',
            'USDC' => '0xA0b86a33E6441b8C4C8C0C4C0C4C0C4C0C4C0C4C',
            'DAI' => '0x6B175474E89094C44Da98b954EedeAC495271d0F',
            'LINK' => '0x514910771AF9Ca656af840dff83E8264EcF986CA',
            'UNI' => '0x1f9840a85d5aF5bf1D1762F925BDADdC4201F984',
            'AAVE' => '0x7Fc66500c84A76Ad7e9c93437bFc5Ac33E2DDaE9'
        );
        
        return $addresses[$token] ?? null;
    }
    
    /**
     * Get token decimals
     */
    private function get_token_decimals($token) {
        $decimals = array(
            'USDT' => 6,
            'USDC' => 6,
            'DAI' => 18,
            'LINK' => 18,
            'UNI' => 18,
            'AAVE' => 18
        );
        
        return $decimals[$token] ?? 18;
    }
    
    /**
     * Get network ID
     */
    private function get_network_id($network) {
        $networks = array(
            'mainnet' => 1,
            'testnet' => 3,
            'devnet' => 1337
        );
        
        return $networks[$network] ?? 1;
    }
    
    /**
     * Get performance metrics
     */
    public function get_performance_metrics() {
        try {
            $response = $this->make_rpc_request('eth_blockNumber', array());
            $block_number = $response && isset($response['result']) ? hexdec($response['result']) : 0;
            
            return array(
                'current_block' => $block_number,
                'network_id' => $this->network_id,
                'last_sync' => current_time('mysql'),
                'status' => 'active'
            );
        } catch (Exception $e) {
            return array(
                'error' => $e->getMessage(),
                'status' => 'error'
            );
        }
    }
}