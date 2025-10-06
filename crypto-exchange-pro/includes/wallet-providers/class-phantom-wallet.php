<?php
/**
 * Phantom Wallet Provider Integration (Solana)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Phantom_Wallet {
    
    private $provider;
    private $rpc_url;
    private $network;
    
    public function __construct($provider = null) {
        if ($provider) {
            $this->provider = $provider;
            $this->rpc_url = $provider->rpc_url ?: 'https://api.mainnet-beta.solana.com';
            $this->network = $provider->network;
        }
    }
    
    /**
     * Test connection to Solana network
     */
    public function test_connection() {
        try {
            $response = $this->make_rpc_request('getHealth');
            
            if ($response && $response === 'ok') {
                return array(
                    'success' => true,
                    'message' => 'Phantom Wallet connection successful to Solana network'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Invalid response from Solana network'
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
            $address = $this->generate_solana_address();
            $this->store_user_address($user_id, $address, $coin);
            
            return array(
                'address' => $address,
                'coin' => $coin,
                'provider' => 'phantom_wallet'
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
            
            $transaction = $this->create_solana_transaction($from_address, $to_address, $amount, $coin);
            
            return array(
                'transaction_id' => $transaction['signature'],
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
     * Sync data with Solana
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
     * Make RPC request to Solana network
     */
    private function make_rpc_request($method, $params = array()) {
        $data = array(
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params
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
        
        $result = json_decode($response, true);
        
        if (isset($result['error'])) {
            throw new Exception('RPC error: ' . $result['error']['message']);
        }
        
        return $result['result'] ?? null;
    }
    
    /**
     * Get balances for an address
     */
    private function get_address_balances($address) {
        try {
            $response = $this->make_rpc_request('getAccountInfo', array(
                $address,
                array('encoding' => 'jsonParsed')
            ));
            
            $balances = array();
            
            if ($response && isset($response['value'])) {
                // SOL balance
                $sol_balance = $this->get_sol_balance($address);
                if ($sol_balance > 0) {
                    $balances[] = array(
                        'currency' => 'SOL',
                        'balance' => $sol_balance,
                        'address' => $address
                    );
                }
                
                // SPL token balances
                $token_balances = $this->get_spl_token_balances($address);
                $balances = array_merge($balances, $token_balances);
            }
            
            return $balances;
        } catch (Exception $e) {
            return array();
        }
    }
    
    /**
     * Get SOL balance
     */
    private function get_sol_balance($address) {
        try {
            $response = $this->make_rpc_request('getBalance', array($address));
            return $response ? $response / 1000000000 : 0; // Convert lamports to SOL
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get SPL token balances
     */
    private function get_spl_token_balances($address) {
        try {
            $response = $this->make_rpc_request('getTokenAccountsByOwner', array(
                $address,
                array('programId' => 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA'),
                array('encoding' => 'jsonParsed')
            ));
            
            $balances = array();
            $tokens = array('USDC', 'USDT', 'RAY', 'SRM', 'ORCA');
            
            if ($response && isset($response['value'])) {
                foreach ($response['value'] as $account) {
                    $token_info = $account['account']['data']['parsed']['info'];
                    $mint = $token_info['mint'];
                    $amount = $token_info['tokenAmount']['uiAmount'];
                    
                    if ($amount > 0) {
                        $token_symbol = $this->get_token_symbol($mint);
                        if ($token_symbol) {
                            $balances[] = array(
                                'currency' => $token_symbol,
                                'balance' => $amount,
                                'address' => $address
                            );
                        }
                    }
                }
            }
            
            return $balances;
        } catch (Exception $e) {
            return array();
        }
    }
    
    /**
     * Generate Solana address
     */
    private function generate_solana_address() {
        // This would typically use proper key generation
        // For now, generate a mock address
        return base58_encode(random_bytes(32));
    }
    
    /**
     * Create Solana transaction
     */
    private function create_solana_transaction($from_address, $to_address, $amount, $coin) {
        // This would typically create a real Solana transaction
        // For now, simulate the transaction
        return array(
            'signature' => base58_encode(random_bytes(64)),
            'status' => 'pending'
        );
    }
    
    /**
     * Get user's addresses
     */
    private function get_user_addresses($user_id) {
        global $wpdb;
        
        $addresses = $wpdb->get_col($wpdb->prepare(
            "SELECT address FROM {$wpdb->prefix}crypto_wallet_addresses 
             WHERE user_id = %d AND provider = 'phantom_wallet'",
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
                'provider' => 'phantom_wallet',
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
             WHERE user_id = %d AND provider = 'phantom_wallet' AND coin = %s
             ORDER BY created_at ASC LIMIT 1",
            $user_id,
            $coin
        ));
        
        if (!$address) {
            $address = $this->generate_solana_address();
            $this->store_user_address($user_id, $address, $coin);
        }
        
        return $address;
    }
    
    /**
     * Get address transactions
     */
    private function get_address_transactions($address, $coin = null, $limit = 50) {
        try {
            $response = $this->make_rpc_request('getSignaturesForAddress', array(
                $address,
                array('limit' => $limit)
            ));
            
            $transactions = array();
            if ($response) {
                foreach ($response as $tx) {
                    $transactions[] = array(
                        'id' => $tx['signature'],
                        'coin' => $coin ?: 'SOL',
                        'amount' => 0, // Would need to fetch transaction details
                        'fee' => $tx['fee'] ?? 0,
                        'status' => $tx['err'] ? 'failed' : 'completed',
                        'timestamp' => date('Y-m-d H:i:s', $tx['blockTime'])
                    );
                }
            }
            
            return $transactions;
        } catch (Exception $e) {
            return array();
        }
    }
    
    /**
     * Get token symbol from mint address
     */
    private function get_token_symbol($mint) {
        $tokens = array(
            'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v' => 'USDC',
            'Es9vMFrzaCERmJfrF4H2FYD4KCoNkY11McCe8BenwNYB' => 'USDT',
            '4k3Dyjzvzp8eMZWUXbBCjEvwSkkk59S5iCNLY3QrkX6R' => 'RAY',
            'SRMuApVNdxXokk5GT7XD5cUUgXMBCoAz2LHeuAoKWRt' => 'SRM',
            'orcaEKTdK7LKz57vaAYr9QeNsVEPfiu6QeMU1kektZE' => 'ORCA'
        );
        
        return $tokens[$mint] ?? null;
    }
    
    /**
     * Base58 encode
     */
    private function base58_encode($data) {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = strlen($alphabet);
        $encoded = '';
        
        $num = gmp_init('0x' . bin2hex($data));
        
        while (gmp_cmp($num, 0) > 0) {
            $remainder = gmp_mod($num, $base);
            $encoded = $alphabet[gmp_intval($remainder)] . $encoded;
            $num = gmp_div($num, $base);
        }
        
        return $encoded;
    }
    
    /**
     * Get performance metrics
     */
    public function get_performance_metrics() {
        try {
            $response = $this->make_rpc_request('getHealth');
            $block_height = $this->make_rpc_request('getBlockHeight');
            
            return array(
                'network_health' => $response,
                'current_slot' => $block_height,
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