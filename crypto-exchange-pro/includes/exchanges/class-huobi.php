<?php
/**
 * Huobi Exchange Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Huobi {
    
    private $provider;
    private $api_key;
    private $api_secret;
    private $base_url;
    private $sandbox_url = 'https://api.huobi.pro';
    private $mainnet_url = 'https://api.huobi.pro';
    
    public function __construct($provider) {
        $this->provider = $provider;
        $this->api_key = $provider->api_key;
        $this->api_secret = $provider->api_secret;
        $this->base_url = $provider->sandbox ? $this->sandbox_url : $this->mainnet_url;
    }
    
    /**
     * Test connection to Huobi API
     */
    public function test_connection() {
        try {
            $response = $this->make_request('GET', '/v1/common/symbols');
            
            if ($response && isset($response['status']) && $response['status'] === 'ok') {
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
        return $this->make_signed_request('GET', '/v1/account/accounts');
    }
    
    /**
     * Get trading symbols
     */
    public function get_symbols() {
        $response = $this->make_request('GET', '/v1/common/symbols');
        
        if ($response && isset($response['data'])) {
            $symbols = array();
            foreach ($response['data'] as $symbol) {
                if ($symbol['state'] === 'online') {
                    $symbols[] = array(
                        'symbol' => $symbol['symbol'],
                        'base_currency' => $symbol['base-currency'],
                        'quote_currency' => $symbol['quote-currency'],
                        'price_precision' => $symbol['price-precision'],
                        'amount_precision' => $symbol['amount-precision'],
                        'symbol_partition' => $symbol['symbol-partition'],
                        'state' => $symbol['state'],
                        'value_precision' => $symbol['value-precision'],
                        'min_order_amt' => floatval($symbol['min-order-amt']),
                        'max_order_amt' => floatval($symbol['max-order-amt']),
                        'min_order_value' => floatval($symbol['min-order-value']),
                        'limit_order_min_amt' => floatval($symbol['limit-order-min-amt']),
                        'limit_order_max_amt' => floatval($symbol['limit-order-max-amt']),
                        'sell_market_min_amt' => floatval($symbol['sell-market-min-amt']),
                        'sell_market_max_amt' => floatval($symbol['sell-market-max-amt']),
                        'buy_market_max_amt' => floatval($symbol['buy-market-max-amt']),
                        'api_trading' => $symbol['api-trading']
                    );
                }
            }
            return $symbols;
        }
        
        return array();
    }
    
    /**
     * Get order book
     */
    public function get_depth($symbol, $type = 'step0') {
        $params = array(
            'symbol' => $symbol,
            'type' => $type
        );
        
        $response = $this->make_request('GET', '/market/depth', $params);
        
        if ($response && isset($response['tick'])) {
            return array(
                'bids' => $response['tick']['bids'],
                'asks' => $response['tick']['asks'],
                'ts' => $response['ts']
            );
        }
        
        return null;
    }
    
    /**
     * Get ticker
     */
    public function get_ticker($symbol) {
        $params = array('symbol' => $symbol);
        $response = $this->make_request('GET', '/market/detail/merged', $params);
        
        if ($response && isset($response['tick'])) {
            $tick = $response['tick'];
            return array(
                'symbol' => $symbol,
                'open' => floatval($tick['open']),
                'high' => floatval($tick['high']),
                'low' => floatval($tick['low']),
                'close' => floatval($tick['close']),
                'amount' => floatval($tick['amount']),
                'vol' => floatval($tick['vol']),
                'count' => intval($tick['count']),
                'bid' => floatval($tick['bid']),
                'bid_size' => floatval($tick['bidSize']),
                'ask' => floatval($tick['ask']),
                'ask_size' => floatval($tick['askSize'])
            );
        }
        
        return null;
    }
    
    /**
     * Get 24hr ticker
     */
    public function get_24hr_ticker($symbol) {
        $params = array('symbol' => $symbol);
        $response = $this->make_request('GET', '/market/detail', $params);
        
        if ($response && isset($response['tick'])) {
            $tick = $response['tick'];
            return array(
                'symbol' => $symbol,
                'open' => floatval($tick['open']),
                'high' => floatval($tick['high']),
                'low' => floatval($tick['low']),
                'close' => floatval($tick['close']),
                'amount' => floatval($tick['amount']),
                'vol' => floatval($tick['vol']),
                'count' => intval($tick['count'])
            );
        }
        
        return null;
    }
    
    /**
     * Place order
     */
    public function place_order($account_id, $symbol, $type, $amount, $price = null, $source = 'api') {
        $params = array(
            'account-id' => $account_id,
            'symbol' => $symbol,
            'type' => $type,
            'amount' => $amount,
            'source' => $source
        );
        
        if ($price !== null) {
            $params['price'] = $price;
        }
        
        $response = $this->make_signed_request('POST', '/v1/order/orders/place', $params);
        
        if ($response && isset($response['data'])) {
            return array(
                'success' => true,
                'order_id' => $response['data']
            );
        }
        
        return array(
            'success' => false,
            'error' => $response['err-msg'] ?? 'Unknown error'
        );
    }
    
    /**
     * Cancel order
     */
    public function cancel_order($order_id) {
        $response = $this->make_signed_request('POST', '/v1/order/orders/' . $order_id . '/submitcancel');
        
        if ($response && isset($response['data'])) {
            return array(
                'success' => true,
                'order_id' => $response['data']
            );
        }
        
        return array(
            'success' => false,
            'error' => $response['err-msg'] ?? 'Unknown error'
        );
    }
    
    /**
     * Get order status
     */
    public function get_order($order_id) {
        $response = $this->make_signed_request('GET', '/v1/order/orders/' . $order_id);
        
        if ($response && isset($response['data'])) {
            $order = $response['data'];
            return array(
                'success' => true,
                'id' => $order['id'],
                'symbol' => $order['symbol'],
                'account_id' => $order['account-id'],
                'amount' => floatval($order['amount']),
                'price' => floatval($order['price']),
                'created_at' => $order['created-at'],
                'type' => $order['type'],
                'side' => $order['side'],
                'filled_amount' => floatval($order['filled-amount']),
                'filled_cash_amount' => floatval($order['filled-cash-amount']),
                'filled_fees' => floatval($order['filled-fees']),
                'state' => $order['state'],
                'source' => $order['source']
            );
        }
        
        return array(
            'success' => false,
            'error' => $response['err-msg'] ?? 'Unknown error'
        );
    }
    
    /**
     * Get open orders
     */
    public function get_open_orders($account_id, $symbol = null, $size = 100) {
        $params = array(
            'account-id' => $account_id,
            'size' => $size
        );
        
        if ($symbol) {
            $params['symbol'] = $symbol;
        }
        
        $response = $this->make_signed_request('GET', '/v1/order/openOrders', $params);
        
        if ($response && isset($response['data'])) {
            $orders = array();
            foreach ($response['data'] as $order) {
                $orders[] = array(
                    'id' => $order['id'],
                    'symbol' => $order['symbol'],
                    'account_id' => $order['account-id'],
                    'amount' => floatval($order['amount']),
                    'price' => floatval($order['price']),
                    'created_at' => $order['created-at'],
                    'type' => $order['type'],
                    'side' => $order['side'],
                    'filled_amount' => floatval($order['filled-amount']),
                    'filled_cash_amount' => floatval($order['filled-cash-amount']),
                    'filled_fees' => floatval($order['filled-fees']),
                    'state' => $order['state'],
                    'source' => $order['source']
                );
            }
            return $orders;
        }
        
        return array();
    }
    
    /**
     * Get order history
     */
    public function get_order_history($account_id, $symbol = null, $states = null, $types = null, $start_date = null, $end_date = null, $from = null, $direct = null, $size = null) {
        $params = array('account-id' => $account_id);
        
        if ($symbol) $params['symbol'] = $symbol;
        if ($states) $params['states'] = $states;
        if ($types) $params['types'] = $types;
        if ($start_date) $params['start-date'] = $start_date;
        if ($end_date) $params['end-date'] = $end_date;
        if ($from) $params['from'] = $from;
        if ($direct) $params['direct'] = $direct;
        if ($size) $params['size'] = $size;
        
        $response = $this->make_signed_request('GET', '/v1/order/orders', $params);
        
        if ($response && isset($response['data'])) {
            $orders = array();
            foreach ($response['data'] as $order) {
                $orders[] = array(
                    'id' => $order['id'],
                    'symbol' => $order['symbol'],
                    'account_id' => $order['account-id'],
                    'amount' => floatval($order['amount']),
                    'price' => floatval($order['price']),
                    'created_at' => $order['created-at'],
                    'type' => $order['type'],
                    'side' => $order['side'],
                    'filled_amount' => floatval($order['filled-amount']),
                    'filled_cash_amount' => floatval($order['filled-cash-amount']),
                    'filled_fees' => floatval($order['filled-fees']),
                    'state' => $order['state'],
                    'source' => $order['source']
                );
            }
            return $orders;
        }
        
        return array();
    }
    
    /**
     * Get account balance
     */
    public function get_balance($account_id) {
        $response = $this->make_signed_request('GET', '/v1/account/accounts/' . $account_id . '/balance');
        
        if ($response && isset($response['data'])) {
            $balances = array();
            foreach ($response['data']['list'] as $balance) {
                if (floatval($balance['balance']) > 0) {
                    $balances[$balance['currency']] = array(
                        'currency' => $balance['currency'],
                        'type' => $balance['type'],
                        'balance' => floatval($balance['balance'])
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
    public function get_klines($symbol, $period, $size = 150) {
        $params = array(
            'symbol' => $symbol,
            'period' => $period,
            'size' => $size
        );
        
        $response = $this->make_request('GET', '/market/history/kline', $params);
        
        if ($response && isset($response['data'])) {
            $klines = array();
            foreach ($response['data'] as $kline) {
                $klines[] = array(
                    'id' => $kline['id'],
                    'open' => floatval($kline['open']),
                    'close' => floatval($kline['close']),
                    'low' => floatval($kline['low']),
                    'high' => floatval($kline['high']),
                    'amount' => floatval($kline['amount']),
                    'vol' => floatval($kline['vol']),
                    'count' => intval($kline['count'])
                );
            }
            return $klines;
        }
        
        return array();
    }
    
    /**
     * Sync data from Huobi
     */
    public function sync_data() {
        try {
            // Sync symbols
            $symbols = $this->get_symbols();
            $this->sync_symbols($symbols);
            
            // Sync account balances
            $accounts = $this->get_accounts();
            if ($accounts && isset($accounts['data'])) {
                foreach ($accounts['data'] as $account) {
                    $balances = $this->get_balance($account['id']);
                    $this->sync_balances($balances, $account['id']);
                }
            }
            
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
     * Sync symbols
     */
    private function sync_symbols($symbols) {
        global $wpdb;
        
        foreach ($symbols as $symbol) {
            $wpdb->replace(
                $wpdb->prefix . 'crypto_exchange_pairs',
                array(
                    'symbol' => $symbol['symbol'],
                    'base' => $symbol['base_currency'],
                    'quote' => $symbol['quote_currency'],
                    'min_qty' => $symbol['min_order_amt'],
                    'max_qty' => $symbol['max_order_amt'],
                    'step_size' => pow(10, -$symbol['amount_precision']),
                    'min_price' => 0,
                    'max_price' => 0,
                    'tick_size' => pow(10, -$symbol['price_precision']),
                    'exchange' => 'huobi',
                    'status' => $symbol['state'] === 'online' ? 'active' : 'inactive',
                    'updated_at' => current_time('mysql')
                )
            );
        }
    }
    
    /**
     * Sync balances
     */
    private function sync_balances($balances, $account_id) {
        global $wpdb;
        
        foreach ($balances as $currency => $balance) {
            $wpdb->replace(
                $wpdb->prefix . 'crypto_exchange_balances',
                array(
                    'exchange' => 'huobi',
                    'asset' => $currency,
                    'free' => $balance['type'] === 'trade' ? $balance['balance'] : 0,
                    'locked' => $balance['type'] === 'frozen' ? $balance['balance'] : 0,
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
        
        $symbols = $this->get_symbols();
        
        foreach ($symbols as $symbol) {
            $ticker = $this->get_24hr_ticker($symbol['symbol']);
            
            if ($ticker) {
                $change_24h = (($ticker['close'] - $ticker['open']) / $ticker['open']) * 100;
                
                $wpdb->replace(
                    $wpdb->prefix . 'crypto_market_data',
                    array(
                        'symbol' => $symbol['symbol'],
                        'price' => $ticker['close'],
                        'change_24h' => $change_24h,
                        'volume_24h' => $ticker['vol'],
                        'high_24h' => $ticker['high'],
                        'low_24h' => $ticker['low'],
                        'exchange' => 'huobi',
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
        
        if (isset($data['status']) && $data['status'] === 'error') {
            throw new Exception($data['err-msg'] ?? 'API Error');
        }
        
        return $data;
    }
    
    /**
     * Make signed request
     */
    private function make_signed_request($method, $endpoint, $params = array()) {
        $timestamp = gmdate('Y-m-d\TH:i:s');
        $params['AccessKeyId'] = $this->api_key;
        $params['SignatureMethod'] = 'HmacSHA256';
        $params['SignatureVersion'] = '2';
        $params['Timestamp'] = $timestamp;
        
        $query_string = http_build_query($params);
        $signature_string = $method . "\n" . parse_url($this->base_url, PHP_URL_HOST) . "\n" . $endpoint . "\n" . $query_string;
        $signature = base64_encode(hash_hmac('sha256', $signature_string, $this->api_secret, true));
        
        $params['Signature'] = $signature;
        
        $url = $this->base_url . $endpoint;
        
        if ($method === 'GET') {
            $url .= '?' . http_build_query($params);
        }
        
        $headers = array(
            'Content-Type' => 'application/json'
        );
        
        $args = array(
            'method' => $method,
            'timeout' => $this->provider->timeout,
            'headers' => $headers
        );
        
        if ($method === 'POST') {
            $args['body'] = json_encode($params);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['status']) && $data['status'] === 'error') {
            throw new Exception($data['err-msg'] ?? 'API Error');
        }
        
        return $data;
    }
}
