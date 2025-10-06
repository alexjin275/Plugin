<?php
/**
 * Kraken Exchange Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Kraken {
    
    private $provider;
    private $api_key;
    private $api_secret;
    private $base_url;
    private $sandbox_url = 'https://api-sandbox.kraken.com';
    private $mainnet_url = 'https://api.kraken.com';
    
    public function __construct($provider) {
        $this->provider = $provider;
        $this->api_key = $provider->api_key;
        $this->api_secret = $provider->api_secret;
        $this->base_url = $provider->sandbox ? $this->sandbox_url : $this->mainnet_url;
    }
    
    /**
     * Test connection to Kraken API
     */
    public function test_connection() {
        try {
            $response = $this->make_request('GET', '/0/public/Time');
            
            if ($response && isset($response['result']['unixtime'])) {
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
     * Get account balance
     */
    public function get_balance() {
        return $this->make_private_request('/0/private/Balance');
    }
    
    /**
     * Get asset pairs
     */
    public function get_asset_pairs() {
        $response = $this->make_request('GET', '/0/public/AssetPairs');
        
        if ($response && isset($response['result'])) {
            $pairs = array();
            foreach ($response['result'] as $pair_name => $pair_data) {
                if (isset($pair_data['wsname'])) {
                    $pairs[] = array(
                        'altname' => $pair_data['altname'],
                        'wsname' => $pair_data['wsname'],
                        'aclass_base' => $pair_data['aclass_base'],
                        'base' => $pair_data['base'],
                        'aclass_quote' => $pair_data['aclass_quote'],
                        'quote' => $pair_data['quote'],
                        'lot' => $pair_data['lot'],
                        'pair_decimals' => $pair_data['pair_decimals'],
                        'lot_decimals' => $pair_data['lot_decimals'],
                        'lot_multiplier' => $pair_data['lot_multiplier'],
                        'leverage_buy' => $pair_data['leverage_buy'],
                        'leverage_sell' => $pair_data['leverage_sell'],
                        'fees' => $pair_data['fees'],
                        'fees_maker' => $pair_data['fees_maker'],
                        'fee_volume_currency' => $pair_data['fee_volume_currency'],
                        'margin_call' => $pair_data['margin_call'],
                        'margin_stop' => $pair_data['margin_stop']
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
    public function get_order_book($pair, $count = 100) {
        $params = array(
            'pair' => $pair,
            'count' => $count
        );
        
        $response = $this->make_request('GET', '/0/public/Depth', $params);
        
        if ($response && isset($response['result'])) {
            $pair_data = reset($response['result']);
            return array(
                'bids' => $pair_data['bids'],
                'asks' => $pair_data['asks']
            );
        }
        
        return null;
    }
    
    /**
     * Get ticker
     */
    public function get_ticker($pair = null) {
        $params = array();
        if ($pair) {
            $params['pair'] = $pair;
        }
        
        $response = $this->make_request('GET', '/0/public/Ticker', $params);
        
        if ($response && isset($response['result'])) {
            if ($pair) {
                $pair_data = reset($response['result']);
                return array(
                    'a' => $pair_data['a'], // Ask [price, whole_lot_volume, lot_volume]
                    'b' => $pair_data['b'], // Bid [price, whole_lot_volume, lot_volume]
                    'c' => $pair_data['c'], // Last trade closed [price, lot_volume]
                    'v' => $pair_data['v'], // Volume [today, last_24_hours]
                    'p' => $pair_data['p'], // Volume weighted average price [today, last_24_hours]
                    't' => $pair_data['t'], // Number of trades [today, last_24_hours]
                    'l' => $pair_data['l'], // Low [today, last_24_hours]
                    'h' => $pair_data['h'], // High [today, last_24_hours]
                    'o' => $pair_data['o']  // Today's opening price
                );
            } else {
                $tickers = array();
                foreach ($response['result'] as $pair_name => $pair_data) {
                    $tickers[$pair_name] = array(
                        'a' => $pair_data['a'],
                        'b' => $pair_data['b'],
                        'c' => $pair_data['c'],
                        'v' => $pair_data['v'],
                        'p' => $pair_data['p'],
                        't' => $pair_data['t'],
                        'l' => $pair_data['l'],
                        'h' => $pair_data['h'],
                        'o' => $pair_data['o']
                    );
                }
                return $tickers;
            }
        }
        
        return null;
    }
    
    /**
     * Add order
     */
    public function add_order($pair, $type, $ordertype, $volume, $price = null, $leverage = null) {
        $params = array(
            'pair' => $pair,
            'type' => $type,
            'ordertype' => $ordertype,
            'volume' => $volume
        );
        
        if ($price !== null) {
            $params['price'] = $price;
        }
        
        if ($leverage !== null) {
            $params['leverage'] = $leverage;
        }
        
        $response = $this->make_private_request('/0/private/AddOrder', $params);
        
        if ($response && isset($response['result'])) {
            return array(
                'success' => true,
                'txid' => $response['result']['txid'][0],
                'descr' => $response['result']['descr']
            );
        }
        
        return array(
            'success' => false,
            'error' => $response['error'][0] ?? 'Unknown error'
        );
    }
    
    /**
     * Cancel order
     */
    public function cancel_order($txid) {
        $params = array('txid' => $txid);
        $response = $this->make_private_request('/0/private/CancelOrder', $params);
        
        if ($response && isset($response['result'])) {
            return array(
                'success' => true,
                'count' => $response['result']['count']
            );
        }
        
        return array(
            'success' => false,
            'error' => $response['error'][0] ?? 'Unknown error'
        );
    }
    
    /**
     * Get open orders
     */
    public function get_open_orders($trades = false) {
        $params = array('trades' => $trades);
        $response = $this->make_private_request('/0/private/OpenOrders', $params);
        
        if ($response && isset($response['result'])) {
            return $response['result'];
        }
        
        return array();
    }
    
    /**
     * Get closed orders
     */
    public function get_closed_orders($trades = false, $start = null, $end = null) {
        $params = array('trades' => $trades);
        
        if ($start) {
            $params['start'] = $start;
        }
        
        if ($end) {
            $params['end'] = $end;
        }
        
        $response = $this->make_private_request('/0/private/ClosedOrders', $params);
        
        if ($response && isset($response['result'])) {
            return $response['result'];
        }
        
        return array();
    }
    
    /**
     * Query orders info
     */
    public function query_orders_info($txid, $trades = false) {
        $params = array(
            'txid' => $txid,
            'trades' => $trades
        );
        
        $response = $this->make_private_request('/0/private/QueryOrders', $params);
        
        if ($response && isset($response['result'])) {
            return $response['result'];
        }
        
        return array();
    }
    
    /**
     * Get trade history
     */
    public function get_trade_history($type = 'all', $trades = false, $start = null, $end = null) {
        $params = array(
            'type' => $type,
            'trades' => $trades
        );
        
        if ($start) {
            $params['start'] = $start;
        }
        
        if ($end) {
            $params['end'] = $end;
        }
        
        $response = $this->make_private_request('/0/private/TradesHistory', $params);
        
        if ($response && isset($response['result'])) {
            return $response['result'];
        }
        
        return array();
    }
    
    /**
     * Get OHLC data
     */
    public function get_ohlc($pair, $interval = 1, $since = null) {
        $params = array(
            'pair' => $pair,
            'interval' => $interval
        );
        
        if ($since) {
            $params['since'] = $since;
        }
        
        $response = $this->make_request('GET', '/0/public/OHLC', $params);
        
        if ($response && isset($response['result'])) {
            $pair_data = reset($response['result']);
            $ohlc = array();
            
            foreach ($pair_data as $candle) {
                $ohlc[] = array(
                    'time' => $candle[0],
                    'open' => floatval($candle[1]),
                    'high' => floatval($candle[2]),
                    'low' => floatval($candle[3]),
                    'close' => floatval($candle[4]),
                    'vwap' => floatval($candle[5]),
                    'volume' => floatval($candle[6]),
                    'count' => intval($candle[7])
                );
            }
            
            return array(
                'data' => $ohlc,
                'last' => $response['result']['last']
            );
        }
        
        return array();
    }
    
    /**
     * Get account balances
     */
    public function get_balances() {
        $balance = $this->get_balance();
        
        if ($balance && isset($balance['result'])) {
            $balances = array();
            foreach ($balance['result'] as $currency => $amount) {
                if (floatval($amount) > 0) {
                    $balances[$currency] = array(
                        'currency' => $currency,
                        'balance' => floatval($amount),
                        'free' => floatval($amount),
                        'locked' => 0
                    );
                }
            }
            return $balances;
        }
        
        return array();
    }
    
    /**
     * Sync data from Kraken
     */
    public function sync_data() {
        try {
            // Sync asset pairs
            $pairs = $this->get_asset_pairs();
            $this->sync_asset_pairs($pairs);
            
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
     * Sync asset pairs
     */
    private function sync_asset_pairs($pairs) {
        global $wpdb;
        
        foreach ($pairs as $pair) {
            $wpdb->replace(
                $wpdb->prefix . 'crypto_exchange_pairs',
                array(
                    'symbol' => $pair['altname'],
                    'base' => $pair['base'],
                    'quote' => $pair['quote'],
                    'min_qty' => $pair['lot'],
                    'max_qty' => 0,
                    'step_size' => pow(10, -$pair['lot_decimals']),
                    'min_price' => 0,
                    'max_price' => 0,
                    'tick_size' => pow(10, -$pair['pair_decimals']),
                    'exchange' => 'kraken',
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
        
        foreach ($balances as $currency => $balance) {
            $wpdb->replace(
                $wpdb->prefix . 'crypto_exchange_balances',
                array(
                    'exchange' => 'kraken',
                    'asset' => $currency,
                    'free' => $balance['free'],
                    'locked' => $balance['locked'],
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
        
        $tickers = $this->get_ticker();
        
        foreach ($tickers as $pair => $ticker) {
            $last_price = floatval($ticker['c'][0]);
            $open_price = floatval($ticker['o']);
            $change_24h = (($last_price - $open_price) / $open_price) * 100;
            
            $wpdb->replace(
                $wpdb->prefix . 'crypto_market_data',
                array(
                    'symbol' => $pair,
                    'price' => $last_price,
                    'change_24h' => $change_24h,
                    'volume_24h' => floatval($ticker['v'][1]),
                    'high_24h' => floatval($ticker['h'][1]),
                    'low_24h' => floatval($ticker['l'][1]),
                    'exchange' => 'kraken',
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
                'Content-Type' => 'application/x-www-form-urlencoded'
            )
        );
        
        if ($method === 'POST' && !empty($params)) {
            $args['body'] = http_build_query($params);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error']) && !empty($data['error'])) {
            throw new Exception(implode(', ', $data['error']));
        }
        
        return $data;
    }
    
    /**
     * Make private request
     */
    private function make_private_request($endpoint, $params = array()) {
        $nonce = round(microtime(true) * 1000);
        $postdata = http_build_query(array_merge($params, array('nonce' => $nonce)));
        
        $path = $endpoint;
        $secret = base64_decode($this->api_secret);
        $signature = hash_hmac('sha512', $path . hash('sha256', $nonce . $postdata, true), $secret, true);
        
        $headers = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'API-Key' => $this->api_key,
            'API-Sign' => base64_encode($signature)
        );
        
        $url = $this->base_url . $endpoint;
        
        $args = array(
            'method' => 'POST',
            'timeout' => $this->provider->timeout,
            'headers' => $headers,
            'body' => $postdata
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error']) && !empty($data['error'])) {
            throw new Exception(implode(', ', $data['error']));
        }
        
        return $data;
    }
}
