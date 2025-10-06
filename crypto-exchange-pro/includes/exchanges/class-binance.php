<?php
/**
 * Binance Exchange Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Binance {
    
    private $provider;
    private $api_key;
    private $api_secret;
    private $base_url;
    private $testnet_url = 'https://testnet.binance.vision';
    private $mainnet_url = 'https://api.binance.com';
    
    public function __construct($provider) {
        $this->provider = $provider;
        $this->api_key = $provider->api_key;
        $this->api_secret = $provider->api_secret;
        $this->base_url = $provider->sandbox ? $this->testnet_url : $this->mainnet_url;
    }
    
    /**
     * Test connection to Binance API
     */
    public function test_connection() {
        try {
            $response = $this->make_request('GET', '/api/v3/ping');
            
            if ($response && !isset($response['code'])) {
                return array(
                    'success' => true,
                    'message' => 'Connection successful'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Connection failed: ' . ($response['msg'] ?? 'Unknown error')
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
    public function get_account_info() {
        return $this->make_signed_request('GET', '/api/v3/account');
    }
    
    /**
     * Get trading pairs
     */
    public function get_trading_pairs() {
        $response = $this->make_request('GET', '/api/v3/exchangeInfo');
        
        if ($response && isset($response['symbols'])) {
            $pairs = array();
            foreach ($response['symbols'] as $symbol) {
                if ($symbol['status'] === 'TRADING') {
                    $pairs[] = array(
                        'symbol' => $symbol['symbol'],
                        'base' => $symbol['baseAsset'],
                        'quote' => $symbol['quoteAsset'],
                        'min_qty' => $symbol['filters'][0]['minQty'] ?? 0,
                        'max_qty' => $symbol['filters'][0]['maxQty'] ?? 0,
                        'step_size' => $symbol['filters'][0]['stepSize'] ?? 0,
                        'min_price' => $symbol['filters'][1]['minPrice'] ?? 0,
                        'max_price' => $symbol['filters'][1]['maxPrice'] ?? 0,
                        'tick_size' => $symbol['filters'][1]['tickSize'] ?? 0
                    );
                }
            }
            return $pairs;
        }
        
        return array();
    }
    
    /**
     * Get order book
     */
    public function get_order_book($symbol, $limit = 100) {
        $params = array(
            'symbol' => $symbol,
            'limit' => $limit
        );
        
        $response = $this->make_request('GET', '/api/v3/depth', $params);
        
        if ($response && isset($response['bids']) && isset($response['asks'])) {
            return array(
                'bids' => $response['bids'],
                'asks' => $response['asks'],
                'last_update_id' => $response['lastUpdateId']
            );
        }
        
        return null;
    }
    
    /**
     * Get ticker price
     */
    public function get_ticker_price($symbol = null) {
        $params = array();
        if ($symbol) {
            $params['symbol'] = $symbol;
        }
        
        $response = $this->make_request('GET', '/api/v3/ticker/price', $params);
        
        if ($response) {
            if ($symbol) {
                return array(
                    'symbol' => $response['symbol'],
                    'price' => floatval($response['price'])
                );
            } else {
                $prices = array();
                foreach ($response as $ticker) {
                    $prices[$ticker['symbol']] = floatval($ticker['price']);
                }
                return $prices;
            }
        }
        
        return null;
    }
    
    /**
     * Get 24hr ticker statistics
     */
    public function get_24hr_ticker($symbol = null) {
        $params = array();
        if ($symbol) {
            $params['symbol'] = $symbol;
        }
        
        $response = $this->make_request('GET', '/api/v3/ticker/24hr', $params);
        
        if ($response) {
            if ($symbol) {
                return array(
                    'symbol' => $response['symbol'],
                    'price_change' => floatval($response['priceChange']),
                    'price_change_percent' => floatval($response['priceChangePercent']),
                    'weighted_avg_price' => floatval($response['weightedAvgPrice']),
                    'prev_close_price' => floatval($response['prevClosePrice']),
                    'last_price' => floatval($response['lastPrice']),
                    'last_qty' => floatval($response['lastQty']),
                    'bid_price' => floatval($response['bidPrice']),
                    'ask_price' => floatval($response['askPrice']),
                    'open_price' => floatval($response['openPrice']),
                    'high_price' => floatval($response['highPrice']),
                    'low_price' => floatval($response['lowPrice']),
                    'volume' => floatval($response['volume']),
                    'quote_volume' => floatval($response['quoteVolume']),
                    'open_time' => $response['openTime'],
                    'close_time' => $response['closeTime'],
                    'count' => intval($response['count'])
                );
            } else {
                $tickers = array();
                foreach ($response as $ticker) {
                    $tickers[$ticker['symbol']] = array(
                        'symbol' => $ticker['symbol'],
                        'price_change' => floatval($ticker['priceChange']),
                        'price_change_percent' => floatval($ticker['priceChangePercent']),
                        'weighted_avg_price' => floatval($ticker['weightedAvgPrice']),
                        'prev_close_price' => floatval($ticker['prevClosePrice']),
                        'last_price' => floatval($ticker['lastPrice']),
                        'last_qty' => floatval($ticker['lastQty']),
                        'bid_price' => floatval($ticker['bidPrice']),
                        'ask_price' => floatval($ticker['askPrice']),
                        'open_price' => floatval($ticker['openPrice']),
                        'high_price' => floatval($ticker['highPrice']),
                        'low_price' => floatval($ticker['lowPrice']),
                        'volume' => floatval($ticker['volume']),
                        'quote_volume' => floatval($ticker['quoteVolume']),
                        'open_time' => $ticker['openTime'],
                        'close_time' => $ticker['closeTime'],
                        'count' => intval($ticker['count'])
                    );
                }
                return $tickers;
            }
        }
        
        return null;
    }
    
    /**
     * Place order
     */
    public function place_order($symbol, $side, $type, $quantity, $price = null, $time_in_force = 'GTC') {
        $params = array(
            'symbol' => $symbol,
            'side' => strtoupper($side),
            'type' => strtoupper($type),
            'quantity' => $quantity,
            'timeInForce' => $time_in_force
        );
        
        if ($price !== null) {
            $params['price'] = $price;
        }
        
        $response = $this->make_signed_request('POST', '/api/v3/order', $params);
        
        if ($response && isset($response['orderId'])) {
            return array(
                'success' => true,
                'order_id' => $response['orderId'],
                'client_order_id' => $response['clientOrderId'],
                'symbol' => $response['symbol'],
                'side' => $response['side'],
                'type' => $response['type'],
                'status' => $response['status'],
                'quantity' => floatval($response['origQty']),
                'price' => floatval($response['price']),
                'executed_qty' => floatval($response['executedQty']),
                'cummulative_quote_qty' => floatval($response['cummulativeQuoteQty']),
                'time' => $response['transactTime']
            );
        }
        
        return array(
            'success' => false,
            'error' => $response['msg'] ?? 'Unknown error'
        );
    }
    
    /**
     * Cancel order
     */
    public function cancel_order($symbol, $order_id) {
        $params = array(
            'symbol' => $symbol,
            'orderId' => $order_id
        );
        
        $response = $this->make_signed_request('DELETE', '/api/v3/order', $params);
        
        if ($response && isset($response['orderId'])) {
            return array(
                'success' => true,
                'order_id' => $response['orderId'],
                'status' => $response['status']
            );
        }
        
        return array(
            'success' => false,
            'error' => $response['msg'] ?? 'Unknown error'
        );
    }
    
    /**
     * Get order status
     */
    public function get_order_status($symbol, $order_id) {
        $params = array(
            'symbol' => $symbol,
            'orderId' => $order_id
        );
        
        $response = $this->make_signed_request('GET', '/api/v3/order', $params);
        
        if ($response && isset($response['orderId'])) {
            return array(
                'success' => true,
                'order_id' => $response['orderId'],
                'client_order_id' => $response['clientOrderId'],
                'symbol' => $response['symbol'],
                'side' => $response['side'],
                'type' => $response['type'],
                'status' => $response['status'],
                'quantity' => floatval($response['origQty']),
                'price' => floatval($response['price']),
                'executed_qty' => floatval($response['executedQty']),
                'cummulative_quote_qty' => floatval($response['cummulativeQuoteQty']),
                'time' => $response['time']
            );
        }
        
        return array(
            'success' => false,
            'error' => $response['msg'] ?? 'Unknown error'
        );
    }
    
    /**
     * Get open orders
     */
    public function get_open_orders($symbol = null) {
        $params = array();
        if ($symbol) {
            $params['symbol'] = $symbol;
        }
        
        $response = $this->make_signed_request('GET', '/api/v3/openOrders', $params);
        
        if ($response && is_array($response)) {
            $orders = array();
            foreach ($response as $order) {
                $orders[] = array(
                    'order_id' => $order['orderId'],
                    'client_order_id' => $order['clientOrderId'],
                    'symbol' => $order['symbol'],
                    'side' => $order['side'],
                    'type' => $order['type'],
                    'status' => $order['status'],
                    'quantity' => floatval($order['origQty']),
                    'price' => floatval($order['price']),
                    'executed_qty' => floatval($order['executedQty']),
                    'cummulative_quote_qty' => floatval($order['cummulativeQuoteQty']),
                    'time' => $order['time']
                );
            }
            return $orders;
        }
        
        return array();
    }
    
    /**
     * Get account balances
     */
    public function get_balances() {
        $account = $this->get_account_info();
        
        if ($account && isset($account['balances'])) {
            $balances = array();
            foreach ($account['balances'] as $balance) {
                if (floatval($balance['free']) > 0 || floatval($balance['locked']) > 0) {
                    $balances[$balance['asset']] = array(
                        'free' => floatval($balance['free']),
                        'locked' => floatval($balance['locked']),
                        'total' => floatval($balance['free']) + floatval($balance['locked'])
                    );
                }
            }
            return $balances;
        }
        
        return array();
    }
    
    /**
     * Get klines (candlestick data)
     */
    public function get_klines($symbol, $interval, $limit = 500, $start_time = null, $end_time = null) {
        $params = array(
            'symbol' => $symbol,
            'interval' => $interval,
            'limit' => $limit
        );
        
        if ($start_time) {
            $params['startTime'] = $start_time;
        }
        
        if ($end_time) {
            $params['endTime'] = $end_time;
        }
        
        $response = $this->make_request('GET', '/api/v3/klines', $params);
        
        if ($response && is_array($response)) {
            $klines = array();
            foreach ($response as $kline) {
                $klines[] = array(
                    'open_time' => $kline[0],
                    'open' => floatval($kline[1]),
                    'high' => floatval($kline[2]),
                    'low' => floatval($kline[3]),
                    'close' => floatval($kline[4]),
                    'volume' => floatval($kline[5]),
                    'close_time' => $kline[6],
                    'quote_asset_volume' => floatval($kline[7]),
                    'number_of_trades' => intval($kline[8]),
                    'taker_buy_base_asset_volume' => floatval($kline[9]),
                    'taker_buy_quote_asset_volume' => floatval($kline[10])
                );
            }
            return $klines;
        }
        
        return array();
    }
    
    /**
     * Sync data from Binance
     */
    public function sync_data() {
        try {
            // Sync trading pairs
            $pairs = $this->get_trading_pairs();
            $this->sync_trading_pairs($pairs);
            
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
     * Sync trading pairs
     */
    private function sync_trading_pairs($pairs) {
        global $wpdb;
        
        foreach ($pairs as $pair) {
            $wpdb->replace(
                $wpdb->prefix . 'crypto_exchange_pairs',
                array(
                    'symbol' => $pair['symbol'],
                    'base' => $pair['base'],
                    'quote' => $pair['quote'],
                    'min_qty' => $pair['min_qty'],
                    'max_qty' => $pair['max_qty'],
                    'step_size' => $pair['step_size'],
                    'min_price' => $pair['min_price'],
                    'max_price' => $pair['max_price'],
                    'tick_size' => $pair['tick_size'],
                    'exchange' => 'binance',
                    'status' => 'active',
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
        
        foreach ($balances as $asset => $balance) {
            $wpdb->replace(
                $wpdb->prefix . 'crypto_exchange_balances',
                array(
                    'exchange' => 'binance',
                    'asset' => $asset,
                    'free' => $balance['free'],
                    'locked' => $balance['locked'],
                    'total' => $balance['total'],
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
        
        $tickers = $this->get_24hr_ticker();
        
        foreach ($tickers as $symbol => $ticker) {
            $wpdb->replace(
                $wpdb->prefix . 'crypto_market_data',
                array(
                    'symbol' => $symbol,
                    'price' => $ticker['last_price'],
                    'change_24h' => $ticker['price_change_percent'],
                    'volume_24h' => $ticker['volume'],
                    'high_24h' => $ticker['high_price'],
                    'low_24h' => $ticker['low_price'],
                    'exchange' => 'binance',
                    'updated_at' => current_time('mysql')
                )
            );
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
        
        if (isset($data['code'])) {
            throw new Exception($data['msg'] ?? 'API Error');
        }
        
        return $data;
    }
    
    /**
     * Make signed request
     */
    private function make_signed_request($method, $endpoint, $params = array()) {
        $params['timestamp'] = round(microtime(true) * 1000);
        $query_string = http_build_query($params);
        $signature = hash_hmac('sha256', $query_string, $this->api_secret);
        $params['signature'] = $signature;
        
        $url = $this->base_url . $endpoint;
        
        if ($method === 'GET') {
            $url .= '?' . http_build_query($params);
        }
        
        $args = array(
            'method' => $method,
            'timeout' => $this->provider->timeout,
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-MBX-APIKEY' => $this->api_key
            )
        );
        
        if ($method === 'POST' || $method === 'DELETE') {
            $args['body'] = json_encode($params);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['code'])) {
            throw new Exception($data['msg'] ?? 'API Error');
        }
        
        return $data;
    }
}
