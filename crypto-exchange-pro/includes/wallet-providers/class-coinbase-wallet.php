<?php
/**
 * Coinbase Wallet Provider Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Coinbase_Wallet {
    
    private $provider;
    private $api_key;
    private $api_secret;
    private $api_passphrase;
    private $sandbox;
    
    public function __construct($provider = null) {
        if ($provider) {
            $this->provider = $provider;
            $this->api_key = $provider->api_key;
            $this->api_secret = $provider->api_secret;
            $this->api_passphrase = $provider->api_passphrase;
            $this->sandbox = $provider->network === 'testnet';
        }
    }
    
    /**
     * Test connection to Coinbase API
     */
    public function test_connection() {
        try {
            $endpoint = $this->sandbox ? 'https://api-public.sandbox.pro.coinbase.com' : 'https://api.pro.coinbase.com';
            $response = $this->make_request('GET', '/accounts');
            
            if ($response && isset($response[0])) {
                return array(
                    'success' => true,
                    'message' => 'Coinbase connection successful'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Invalid response from Coinbase API'
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
            $response = $this->make_request('GET', '/accounts');
            $balances = array();
            
            foreach ($response as $account) {
                if (floatval($account['balance']) > 0) {
                    $balances[] = array(
                        'currency' => $account['currency'],
                        'balance' => $account['balance'],
                        'available' => $account['available'],
                        'hold' => $account['hold']
                    );
                }
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
            $response = $this->make_request('POST', '/deposits/coinbase-account', array(
                'amount' => '0',
                'currency' => $coin,
                'coinbase_account_id' => $this->get_coinbase_account_id($coin)
            ));
            
            return array(
                'address' => $response['payout_at'],
                'coin' => $coin,
                'provider' => 'coinbase'
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
            $response = $this->make_request('POST', '/withdrawals/crypto', array(
                'amount' => $amount,
                'currency' => $coin,
                'crypto_address' => $to_address
            ));
            
            return array(
                'transaction_id' => $response['id'],
                'status' => 'pending',
                'amount' => $amount,
                'coin' => $coin,
                'to_address' => $to_address
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
            $endpoint = '/fills';
            $params = array('limit' => $limit);
            
            if ($coin) {
                $params['product_id'] = $coin . '-USD';
            }
            
            $response = $this->make_request('GET', $endpoint, $params);
            
            $transactions = array();
            foreach ($response as $tx) {
                $transactions[] = array(
                    'id' => $tx['trade_id'],
                    'coin' => explode('-', $tx['product_id'])[0],
                    'amount' => $tx['size'],
                    'price' => $tx['price'],
                    'fee' => $tx['fee'],
                    'side' => $tx['side'],
                    'created_at' => $tx['created_at']
                );
            }
            
            return $transactions;
        } catch (Exception $e) {
            throw new Exception('Failed to get transaction history: ' . $e->getMessage());
        }
    }
    
    /**
     * Sync data with Coinbase
     */
    public function sync_data() {
        try {
            // Sync account balances
            $balances = $this->get_balances(0);
            
            // Sync transaction history
            $transactions = $this->get_transaction_history(0, null, 100);
            
            return array(
                'success' => true,
                'message' => 'Data synced successfully. ' . count($balances) . ' accounts, ' . count($transactions) . ' transactions.'
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Make authenticated request to Coinbase API
     */
    private function make_request($method, $endpoint, $data = null) {
        $url = ($this->sandbox ? 'https://api-public.sandbox.pro.coinbase.com' : 'https://api.pro.coinbase.com') . $endpoint;
        
        $timestamp = time();
        $message = $timestamp . $method . $endpoint . ($data ? json_encode($data) : '');
        $signature = base64_encode(hash_hmac('sha256', $message, base64_decode($this->api_secret), true));
        
        $headers = array(
            'CB-ACCESS-KEY: ' . $this->api_key,
            'CB-ACCESS-SIGN: ' . $signature,
            'CB-ACCESS-TIMESTAMP: ' . $timestamp,
            'CB-ACCESS-PASSPHRASE: ' . $this->api_passphrase,
            'Content-Type: application/json'
        );
        
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
     * Get Coinbase account ID for a specific coin
     */
    private function get_coinbase_account_id($coin) {
        try {
            $response = $this->make_request('GET', '/coinbase-accounts');
            
            foreach ($response as $account) {
                if ($account['currency'] === $coin) {
                    return $account['id'];
                }
            }
            
            throw new Exception('Coinbase account not found for ' . $coin);
        } catch (Exception $e) {
            throw new Exception('Failed to get Coinbase account ID: ' . $e->getMessage());
        }
    }
    
    /**
     * Get performance metrics
     */
    public function get_performance_metrics() {
        try {
            $response = $this->make_request('GET', '/accounts');
            $total_balance = 0;
            $account_count = 0;
            
            foreach ($response as $account) {
                $balance = floatval($account['balance']);
                if ($balance > 0) {
                    $total_balance += $balance;
                    $account_count++;
                }
            }
            
            return array(
                'total_balance' => $total_balance,
                'account_count' => $account_count,
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