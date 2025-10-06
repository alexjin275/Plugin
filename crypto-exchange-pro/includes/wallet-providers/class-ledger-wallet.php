<?php
/**
 * Ledger Hardware Wallet Provider Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Ledger_Wallet {
    
    private $provider;
    private $api_url;
    private $api_key;
    
    public function __construct($provider = null) {
        if ($provider) {
            $this->provider = $provider;
            $this->api_url = $provider->rpc_url ?: 'https://api.ledger.com';
            $this->api_key = $provider->api_key;
        }
    }
    
    /**
     * Test connection to Ledger API
     */
    public function test_connection() {
        try {
            $response = $this->make_request('GET', '/v1/currencies');
            
            if ($response && isset($response['currencies'])) {
                return array(
                    'success' => true,
                    'message' => 'Ledger connection successful. ' . count($response['currencies']) . ' currencies supported.'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Invalid response from Ledger API'
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
            $addresses = $this->get_user_addresses($user_id);
            $balances = array();
            
            foreach ($addresses as $address) {
                $address_balances = $this->get_address_balances($address);
                $balances = array_merge($balances, $address_balances);
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
            $address = $this->generate_ledger_address($coin);
            $this->store_user_address($user_id, $address, $coin);
            
            return array(
                'address' => $address,
                'coin' => $coin,
                'provider' => 'ledger'
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
            $from_address = $this->get_user_primary_address($user_id, $coin);
            
            $transaction = $this->create_ledger_transaction($from_address, $to_address, $amount, $coin);
            
            return array(
                'transaction_id' => $transaction['id'],
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
                $address_txs = $this->get_address_transactions($address, $coin, $limit);
                $transactions = array_merge($transactions, $address_txs);
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
     * Sync data with Ledger
     */
    public function sync_data() {
        try {
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
     * Make request to Ledger API
     */
    private function make_request($method, $endpoint, $data = null) {
        $url = $this->api_url . $endpoint;
        
        $headers = array(
            'Content-Type: application/json',
            'User-Agent: Crypto-Exchange-Pro/1.0'
        );
        
        if ($this->api_key) {
            $headers[] = 'Authorization: Bearer ' . $this->api_key;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }
        
        if ($http_code >= 400) {
            $error_data = json_decode($response, true);
            throw new Exception('API error: ' . ($error_data['message'] ?? 'Unknown error'));
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get balances for an address
     */
    private function get_address_balances($address) {
        try {
            $response = $this->make_request('GET', '/v1/address/' . $address . '/balance');
            
            $balances = array();
            if ($response && isset($response['balances'])) {
                foreach ($response['balances'] as $balance) {
                    if (floatval($balance['amount']) > 0) {
                        $balances[] = array(
                            'currency' => $balance['currency'],
                            'balance' => $balance['amount'],
                            'address' => $address
                        );
                    }
                }
            }
            
            return $balances;
        } catch (Exception $e) {
            return array();
        }
    }
    
    /**
     * Generate Ledger address
     */
    private function generate_ledger_address($coin) {
        // This would typically use proper key generation with Ledger device
        // For now, generate a mock address
        $prefixes = array(
            'BTC' => '1',
            'ETH' => '0x',
            'LTC' => 'L',
            'BCH' => 'q',
            'XRP' => 'r',
            'ADA' => 'addr1',
            'DOT' => '1',
            'LINK' => '0x'
        );
        
        $prefix = $prefixes[$coin] ?? '0x';
        $random = bin2hex(random_bytes(20));
        
        return $prefix . $random;
    }
    
    /**
     * Create Ledger transaction
     */
    private function create_ledger_transaction($from_address, $to_address, $amount, $coin) {
        // This would typically create a real transaction with Ledger device
        // For now, simulate the transaction
        return array(
            'id' => 'ledger_' . bin2hex(random_bytes(16)),
            'status' => 'pending',
            'hash' => '0x' . bin2hex(random_bytes(32))
        );
    }
    
    /**
     * Get user's addresses
     */
    private function get_user_addresses($user_id) {
        global $wpdb;
        
        $addresses = $wpdb->get_col($wpdb->prepare(
            "SELECT address FROM {$wpdb->prefix}crypto_wallet_addresses 
             WHERE user_id = %d AND provider = 'ledger'",
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
                'provider' => 'ledger',
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Get user's primary address for coin
     */
    private function get_user_primary_address($user_id, $coin) {
        global $wpdb;
        
        $address = $wpdb->get_var($wpdb->prepare(
            "SELECT address FROM {$wpdb->prefix}crypto_wallet_addresses 
             WHERE user_id = %d AND provider = 'ledger' AND coin = %s
             ORDER BY created_at ASC LIMIT 1",
            $user_id,
            $coin
        ));
        
        if (!$address) {
            $address = $this->generate_ledger_address($coin);
            $this->store_user_address($user_id, $address, $coin);
        }
        
        return $address;
    }
    
    /**
     * Get address transactions
     */
    private function get_address_transactions($address, $coin = null, $limit = 50) {
        try {
            $endpoint = '/v1/address/' . $address . '/transactions';
            $params = array('limit' => $limit);
            
            if ($coin) {
                $params['currency'] = $coin;
            }
            
            $response = $this->make_request('GET', $endpoint . '?' . http_build_query($params));
            
            $transactions = array();
            if ($response && isset($response['transactions'])) {
                foreach ($response['transactions'] as $tx) {
                    $transactions[] = array(
                        'id' => $tx['id'],
                        'coin' => $tx['currency'],
                        'amount' => $tx['amount'],
                        'fee' => $tx['fee'],
                        'status' => $tx['status'],
                        'timestamp' => $tx['timestamp']
                    );
                }
            }
            
            return $transactions;
        } catch (Exception $e) {
            return array();
        }
    }
    
    /**
     * Get performance metrics
     */
    public function get_performance_metrics() {
        try {
            $response = $this->make_request('GET', '/v1/status');
            
            return array(
                'api_status' => $response['status'] ?? 'unknown',
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