<?php
/**
 * Coinbase Pro Exchange Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Coinbase {
    
    private $provider;
    private $api_key;
    private $api_secret;
    private $api_passphrase;
    private $base_url;
    private $sandbox_url = 'https://api-public.sandbox.pro.coinbase.com';
    private $mainnet_url = 'https://api.pro.coinbase.com';
    
    public function __construct($provider) {
        $this->provider = $provider;
        $this->api_key = $provider->api_key;
        $this->api_secret = $provider->api_secret;
        $this->api_passphrase = $provider->api_passphrase;
        $this->base_url = $provider->sandbox ? $this->sandbox_url : $this->mainnet_url;
    }
    
    /**
     * Test connection to Coinbase Pro API
     */
    public function test_connection() {
        try {
            $response = $this->make_request('GET', '/time');
            
            if ($response && isset($response['iso'])) {
                return array(
                    'success' => true,
                    'message' => 'Connection successful'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Connection failed'
                );
            }
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get account information
     */
    public function get_accounts() {
        return $this->make_signed_request('GET', '/accounts');
    }
    
    /**
     * Get trading pairs
     */
    public function get_products() {
        $response = $this->make_request('GET', '/products');
        
        if ($response && is_array($response)) {
            $products = array();
            foreach ($response as $product) {
                if ($product['status'] === 'online') {
                    $products[] = array(
                        'id' => $product['id'],
                        'base_currency' => $product['base_currency'],
                        'quote_currency' => $product['quote_currency'],
                        'base_min_size' => floatval($product['base_min_size']),
                        'base_max_size' => floatval($product['base_max_size']),
                        'quote_increment' => floatval($product['quote_increment']),
                        'base_increment' => floatval($product['base_increment']),
                        'display_name' => $product['display_name'],
                        'status' => $product['status'],
                        'margin_enabled' => $product['margin_enabled'],
                        'post_only' => $product['post_only'],
                        'limit_only' => $product['limit_only'],
                        'cancel_only' => $product['cancel_only'],
                        'trading_disabled' => $product['trading_disabled']
                    );
                }
            }
            return $products;
        }
        
        return array();
    }
    
    /**
     * Get order book
     */
    public function get_product_order_book($product_id, $level = 2) {
        $params = array('level' => $level);
        $response = $this->make_request('GET', '/products/' . $product_id . '/book', $params);
        
        if ($response && isset($response['bids']) && isset($response['asks'])) {
            return array(
                'sequence' => $response['sequence'],
                'bids' => $response['bids'],
                'asks' => $response['asks']
            );
        }
        
        return null;
    }
    
    /**
     * Get ticker
     */
    public function get_product_ticker($product_id) {
        $response = $this->make_request('GET', '/products/' . $product_id . '/ticker');
        
        if ($response && isset($response['price'])) {
            return array(
                'trade_id' => $response['trade_id'],
                'price' => floatval($response['price']),
                'size' => floatval($response['size']),
                'time' => $response['time'],
                'bid' => floatval($response['bid']),
                'ask' => floatval($response['ask']),
                'volume' => floatval($response['volume'])
            );
        }
        
        return null;
    }
    
    /**
     * Get 24hr stats
     */
    public function get_product_24hr_stats($product_id) {
        $response = $this->make_request('GET', '/products/' . $product_id . '/stats');
        
        if ($response && isset($response['open'])) {
            return array(
                'open' => floatval($response['open']),
                'high' => floatval($response['high']),
                'low' => floatval($response['low']),
                'volume' => floatval($response['volume']),
                'last' => floatval($response['last']),
                'volume_30day' => floatval($response['volume_30day'])
            );
        }
        
        return null;
    }
    
    /**
     * Place order
     */
    public function place_order($product_id, $side, $order_type, $size, $price = null, $time_in_force = 'GTC') {
        $order_data = array(
            'product_id' => $product_id,
            'side' => $side,
            'type' => $order_type,
            'size' => $size,
            'time_in_force' => $time_in_force
        );
        
        if ($price !== null) {
            $order_data['price'] = $price;
        }
        
        $response = $this->make_signed_request('POST', '/orders', $order_data);
        
        if ($response && isset($response['id'])) {
            return array(
                'success' => true,
                'id' => $response['id'],
                'product_id' => $response['product_id'],
                'side' => $response['side'],
                'type' => $response['type'],
                'size' => floatval($response['size']),
                'price' => floatval($response['price']),
                'status' => $response['status'],
                'filled_size' => floatval($response['filled_size']),
                'fill_fees' => floatval($response['fill_fees']),
                'settled' => $response['settled'],
                'created_at' => $response['created_at']
            );
        }
        
        return array(
            'success' => false,
            'error' => $response['message'] ?? 'Unknown error'
        );
    }
    
    /**
     * Cancel order
     */
    public function cancel_order($order_id) {
        $response = $this->make_signed_request('DELETE', '/orders/' . $order_id);
        
        if ($response && isset($response['id'])) {
            return array(
                'success' => true,
                'id' => $response['id'],
                'status' => $response['status']
            );
        }
        
        return array(
            'success' => false,
            'error' => $response['message'] ?? 'Unknown error'
        );
    }
    
    /**
     * Get order status
     */
    public function get_order($order_id) {
        $response = $this->make_signed_request('GET', '/orders/' . $order_id);
        
        if ($response && isset($response['id'])) {
            return array(
                'success' => true,
                'id' => $response['id'],
                'product_id' => $response['product_id'],
                'side' => $response['side'],
                'type' => $response['type'],
                'size' => floatval($response['size']),
                'price' => floatval($response['price']),
                'status' => $response['status'],
                'filled_size' => floatval($response['filled_size']),
                'fill_fees' => floatval($response['fill_fees']),
                'settled' => $response['settled'],
                'created_at' => $response['created_at']
            );
        }
        
        return array(
            'success' => false,
            'error' => $response['message'] ?? 'Unknown error'
        );
    }
    
    /**
     * Get open orders
     */
    public function get_orders($product_id = null, $status = 'open') {
        $params = array('status' => $status);
        if ($product_id) {
            $params['product_id'] = $product_id;
        }
        
        $response = $this->make_signed_request('GET', '/orders', $params);
        
        if ($response && is_array($response)) {
            $orders = array();
            foreach ($response as $order) {
                $orders[] = array(
                    'id' => $order['id'],
                    'product_id' => $order['product_id'],
                    'side' => $order['side'],
                    'type' => $order['type'],
                    'size' => floatval($order['size']),
                    'price' => floatval($order['price']),
                    'status' => $order['status'],
                    'filled_size' => floatval($order['filled_size']),
                    'fill_fees' => floatval($order['fill_fees']),
                    'settled' => $order['settled'],
                    'created_at' => $order['created_at']
                );
            }
            return $orders;
        }
        
        return array();
    }
    
    /**
     * Get fills
     */
    public function get_fills($order_id = null, $product_id = null) {
        $params = array();
        if ($order_id) {
            $params['order_id'] = $order_id;
        }
        if ($product_id) {
            $params['product_id'] = $product_id;
        }
        
        $response = $this->make_signed_request('GET', '/fills', $params);
        
        if ($response && is_array($response)) {
            $fills = array();
            foreach ($response as $fill) {
                $fills[] = array(
                    'trade_id' => $fill['trade_id'],
                    'product_id' => $fill['product_id'],
                    'order_id' => $fill['order_id'],
                    'user_id' => $fill['user_id'],
                    'profile_id' => $fill['profile_id'],
                    'liquidity' => $fill['liquidity'],
                    'price' => floatval($fill['price']),
                    'size' => floatval($fill['size']),
                    'fee' => floatval($fill['fee']),
                    'created_at' => $fill['created_at'],
                    'side' => $fill['side']
                );
            }
            return $fills;
        }
        
        return array();
    }
    
    /**
     * Get account balances
     */
    public function get_balances() {
        $accounts = $this->get_accounts();
        
        if ($accounts && is_array($accounts)) {
            $balances = array();
            foreach ($accounts as $account) {
                if (floatval($account['balance']) > 0 || floatval($account['hold']) > 0) {
                    $balances[$account['currency']] = array(
                        'id' => $account['id'],
                        'currency' => $account['currency'],
                        'balance' => floatval($account['balance']),
                        'hold' => floatval($account['hold']),
                        'available' => floatval($account['available']),
                        'profile_id' => $account['profile_id']
                    );
                }
            }
            return $balances;
        }
        
        return array();
    }
    
    /**
     * Get historical data
     */
    public function get_historical_data($product_id, $start, $end, $granularity) {
        $params = array(
            'start' => $start,
            'end' => $end,
            'granularity' => $granularity
        );
        
        $response = $this->make_request('GET', '/products/' . $product_id . '/candles', $params);
        
        if ($response && is_array($response)) {
            $candles = array();
            foreach ($response as $candle) {
                $candles[] = array(
                    'time' => $candle[0],
                    'low' => floatval($candle[1]),
                    'high' => floatval($candle[2]),
                    'open' => floatval($candle[3]),
                    'close' => floatval($candle[4]),
                    'volume' => floatval($candle[5])
                );
            }
            return $candles;
        }
        
        return array();
    }
    
    /**
     * Sync data from Coinbase Pro
     */
    public function sync_data() {
        try {
            // Sync products
            $products = $this->get_products();
            $this->sync_products($products);
            
            // Sync account balances
            $balances = $this->get_balances();
            $this->sync_balances($balances);
            
            // Sync price data
            $this->sync_price_data();
            
            return array(
                'success' => true,
                'message' => 'Data synced successfully'
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Sync products
     */
    private function sync_products($products) {
        global $wpdb;
        
        foreach ($products as $product) {
            $wpdb->replace(
                $wpdb->prefix . 'crypto_exchange_pairs',
                array(
                    'symbol' => $product['id'],
                    'base' => $product['base_currency'],
                    'quote' => $product['quote_currency'],
                    'min_qty' => $product['base_min_size'],
                    'max_qty' => $product['base_max_size'],
                    'step_size' => $product['base_increment'],
                    'min_price' => 0,
                    'max_price' => 0,
                    'tick_size' => $product['quote_increment'],
                    'exchange' => 'coinbase',
                    'status' => $product['status'] === 'online' ? 'active' : 'inactive',
                    'updated_at' => current_time('mysql')
                )
            );
        }
    }
    
    /**
     * Sync balances
     */
    private function sync_balances($balances) {
        global $wpdb;
        
        foreach ($balances as $currency => $balance) {
            $wpdb->replace(
                $wpdb->prefix . 'crypto_exchange_balances',
                array(
                    'exchange' => 'coinbase',
                    'asset' => $currency,
                    'free' => $balance['available'],
                    'locked' => $balance['hold'],
                    'total' => $balance['balance'],
                    'updated_at' => current_time('mysql')
                )
            );
        }
    }
    
    /**
     * Sync price data
     */
    private function sync_price_data() {
        global $wpdb;
        
        $products = $this->get_products();
        
        foreach ($products as $product) {
            $ticker = $this->get_product_ticker($product['id']);
            $stats = $this->get_product_24hr_stats($product['id']);
            
            if ($ticker && $stats) {
                $wpdb->replace(
                    $wpdb->prefix . 'crypto_market_data',
                    array(
                        'symbol' => $product['id'],
                        'price' => $ticker['price'],
                        'change_24h' => (($ticker['price'] - $stats['open']) / $stats['open']) * 100,
                        'volume_24h' => $stats['volume'],
                        'high_24h' => $stats['high'],
                        'low_24h' => $stats['low'],
                        'exchange' => 'coinbase',
                        'updated_at' => current_time('mysql')
                    )
                );
            }
        }
    }
    
    /**
     * Make unsigned request
     */
    private function make_request($method, $endpoint, $params = array()) {
        $url = $this->base_url . $endpoint;
        
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $args = array(
            'method' => $method,
            'timeout' => $this->provider->timeout,
            'headers' => array(
                'Content-Type' => 'application/json'
            )
        );
        
        if ($method === 'POST' && !empty($params)) {
            $args['body'] = json_encode($params);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['message'])) {
            throw new Exception($data['message']);
        }
        
        return $data;
    }
    
    /**
     * Make signed request
     */
    private function make_signed_request($method, $endpoint, $params = array()) {
        $timestamp = time();
        $body = '';
        
        if ($method === 'POST' && !empty($params)) {
            $body = json_encode($params);
        }
        
        $message = $timestamp . $method . $endpoint . $body;
        $signature = base64_encode(hash_hmac('sha256', $message, base64_decode($this->api_secret), true));
        
        $headers = array(
            'Content-Type' => 'application/json',
            'CB-ACCESS-KEY' => $this->api_key,
            'CB-ACCESS-SIGN' => $signature,
            'CB-ACCESS-TIMESTAMP' => $timestamp,
            'CB-ACCESS-PASSPHRASE' => $this->api_passphrase
        );
        
        $url = $this->base_url . $endpoint;
        
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $args = array(
            'method' => $method,
            'timeout' => $this->provider->timeout,
            'headers' => $headers
        );
        
        if ($method === 'POST' && !empty($params)) {
            $args['body'] = $body;
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['message'])) {
            throw new Exception($data['message']);
        }
        
        return $data;
    }
}
