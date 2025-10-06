<?php
/**
 * Real-time price feed integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Price_Feed {
    
    private $wpdb;
    private $exchanges = array();
    private $api_keys = array();
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->init_exchanges();
        add_action('crypto_exchange_update_real_prices', array($this, 'update_real_prices'));
        add_filter('cron_schedules', array($this, 'add_cron_intervals'));
        
        if (!wp_next_scheduled('crypto_exchange_update_real_prices')) {
            wp_schedule_event(time(), 'every_5_seconds', 'crypto_exchange_update_real_prices');
        }
    }
    
    /**
     * Initialize exchange connections
     */
    private function init_exchanges() {
        $this->exchanges = array(
            'binance' => array(
                'name' => 'Binance',
                'api_url' => 'https://api.binance.com/api/v3/ticker/24hr',
                'weight' => 0.4,
                'enabled' => true
            ),
            'coinbase' => array(
                'name' => 'Coinbase Pro',
                'api_url' => 'https://api.exchange.coinbase.com/products',
                'weight' => 0.3,
                'enabled' => true
            ),
            'kraken' => array(
                'name' => 'Kraken',
                'api_url' => 'https://api.kraken.com/0/public/Ticker',
                'weight' => 0.2,
                'enabled' => true
            ),
            'huobi' => array(
                'name' => 'Huobi',
                'api_url' => 'https://api.huobi.pro/market/tickers',
                'weight' => 0.1,
                'enabled' => true
            )
        );
    }
    
    /**
     * Update real prices from exchanges
     */
    public function update_real_prices() {
        $prices = array();
        
        foreach ($this->exchanges as $exchange_id => $exchange) {
            if (!$exchange['enabled']) continue;
            
            try {
                $exchange_prices = $this->fetch_prices_from_exchange($exchange_id, $exchange);
                if ($exchange_prices) {
                    $prices[$exchange_id] = $exchange_prices;
                }
            } catch (Exception $e) {
                error_log('Price feed error for ' . $exchange_id . ': ' . $e->getMessage());
            }
        }
        
        if (!empty($prices)) {
            $aggregated_prices = $this->aggregate_prices($prices);
            $this->save_prices($aggregated_prices);
            $this->broadcast_prices($aggregated_prices);
        }
    }
    
    /**
     * Fetch prices from specific exchange
     */
    private function fetch_prices_from_exchange($exchange_id, $exchange) {
        $response = wp_remote_get($exchange['api_url'], array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'CryptoExchange/1.0'
            )
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('Failed to fetch from ' . $exchange_id);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data) {
            throw new Exception('Invalid response from ' . $exchange_id);
        }
        
        return $this->parse_exchange_data($exchange_id, $data);
    }
    
    /**
     * Parse exchange-specific data format
     */
    private function parse_exchange_data($exchange_id, $data) {
        switch ($exchange_id) {
            case 'binance':
                return $this->parse_binance_data($data);
            case 'coinbase':
                return $this->parse_coinbase_data($data);
            case 'kraken':
                return $this->parse_kraken_data($data);
            case 'huobi':
                return $this->parse_huobi_data($data);
            default:
                return array();
        }
    }
    
    /**
     * Parse Binance data
     */
    private function parse_binance_data($data) {
        $prices = array();
        
        foreach ($data as $ticker) {
            $symbol = $ticker['symbol'];
            if (strpos($symbol, 'USDT') !== false) {
                $pair = str_replace('USDT', '/USD', $symbol);
                $prices[$pair] = array(
                    'price' => floatval($ticker['lastPrice']),
                    'volume' => floatval($ticker['volume']),
                    'change_24h' => floatval($ticker['priceChangePercent']),
                    'high_24h' => floatval($ticker['highPrice']),
                    'low_24h' => floatval($ticker['lowPrice']),
                    'timestamp' => time()
                );
            }
        }
        
        return $prices;
    }
    
    /**
     * Parse Coinbase data
     */
    private function parse_coinbase_data($data) {
        $prices = array();
        
        foreach ($data as $product) {
            $id = $product['id'];
            if (strpos($id, '-USD') !== false) {
                $pair = str_replace('-', '/', $id);
                $prices[$pair] = array(
                    'price' => floatval($product['price']),
                    'volume' => floatval($product['volume_24h']),
                    'change_24h' => 0, // Coinbase doesn't provide this in products endpoint
                    'high_24h' => 0,
                    'low_24h' => 0,
                    'timestamp' => time()
                );
            }
        }
        
        return $prices;
    }
    
    /**
     * Parse Kraken data
     */
    private function parse_kraken_data($data) {
        $prices = array();
        
        if (isset($data['result'])) {
            foreach ($data['result'] as $pair => $ticker) {
                if (strpos($pair, 'USD') !== false) {
                    $formatted_pair = str_replace('X', '', $pair);
                    $formatted_pair = str_replace('Z', '', $formatted_pair);
                    $formatted_pair = substr($formatted_pair, 0, 3) . '/USD';
                    
                    $prices[$formatted_pair] = array(
                        'price' => floatval($ticker['c'][0]),
                        'volume' => floatval($ticker['v'][1]),
                        'change_24h' => 0,
                        'high_24h' => floatval($ticker['h'][0]),
                        'low_24h' => floatval($ticker['l'][0]),
                        'timestamp' => time()
                    );
                }
            }
        }
        
        return $prices;
    }
    
    /**
     * Parse Huobi data
     */
    private function parse_huobi_data($data) {
        $prices = array();
        
        if (isset($data['data'])) {
            foreach ($data['data'] as $ticker) {
                $symbol = $ticker['symbol'];
                if (strpos($symbol, 'usdt') !== false) {
                    $pair = strtoupper(str_replace('usdt', '/USD', $symbol));
                    $prices[$pair] = array(
                        'price' => floatval($ticker['close']),
                        'volume' => floatval($ticker['vol']),
                        'change_24h' => floatval($ticker['change']),
                        'high_24h' => floatval($ticker['high']),
                        'low_24h' => floatval($ticker['low']),
                        'timestamp' => time()
                    );
                }
            }
        }
        
        return $prices;
    }
    
    /**
     * Aggregate prices from multiple exchanges
     */
    private function aggregate_prices($all_prices) {
        $aggregated = array();
        $supported_pairs = array('BTC/USD', 'ETH/USD', 'BNB/USD', 'ADA/USD', 'SOL/USD', 'DOT/USD', 'MATIC/USD', 'AVAX/USD');
        
        foreach ($supported_pairs as $pair) {
            $pair_prices = array();
            $total_weight = 0;
            
            foreach ($all_prices as $exchange_id => $prices) {
                if (isset($prices[$pair])) {
                    $weight = $this->exchanges[$exchange_id]['weight'];
                    $pair_prices[] = array(
                        'price' => $prices[$pair]['price'],
                        'volume' => $prices[$pair]['volume'],
                        'weight' => $weight
                    );
                    $total_weight += $weight;
                }
            }
            
            if (!empty($pair_prices)) {
                $aggregated[$pair] = $this->calculate_weighted_average($pair_prices, $total_weight);
            }
        }
        
        return $aggregated;
    }
    
    /**
     * Calculate weighted average price
     */
    private function calculate_weighted_average($prices, $total_weight) {
        $weighted_price = 0;
        $weighted_volume = 0;
        $weighted_change = 0;
        $weighted_high = 0;
        $weighted_low = 0;
        
        foreach ($prices as $price_data) {
            $weight = $price_data['weight'];
            $weighted_price += $price_data['price'] * $weight;
            $weighted_volume += $price_data['volume'] * $weight;
        }
        
        return array(
            'price' => $weighted_price,
            'volume' => $weighted_volume,
            'change_24h' => $weighted_change,
            'high_24h' => $weighted_high,
            'low_24h' => $weighted_low,
            'timestamp' => time(),
            'sources' => count($prices)
        );
    }
    
    /**
     * Save prices to database
     */
    private function save_prices($prices) {
        foreach ($prices as $pair => $data) {
            $existing = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->wpdb->prefix}crypto_market_data WHERE pair = %s",
                    $pair
                )
            );
            
            if ($existing) {
                $this->wpdb->update(
                    $this->wpdb->prefix . 'crypto_market_data',
                    array(
                        'price' => $data['price'],
                        'volume_24h' => $data['volume'],
                        'change_24h' => $data['change_24h'],
                        'high_24h' => $data['high_24h'],
                        'low_24h' => $data['low_24h'],
                        'last_updated' => current_time('mysql')
                    ),
                    array('pair' => $pair),
                    array('%f', '%f', '%f', '%f', '%f', '%s'),
                    array('%s')
                );
            } else {
                $this->wpdb->insert(
                    $this->wpdb->prefix . 'crypto_market_data',
                    array(
                        'pair' => $pair,
                        'price' => $data['price'],
                        'volume_24h' => $data['volume'],
                        'change_24h' => $data['change_24h'],
                        'high_24h' => $data['high_24h'],
                        'low_24h' => $data['low_24h'],
                        'market_cap' => $data['price'] * 1000000 // Mock market cap
                    ),
                    array('%s', '%f', '%f', '%f', '%f', '%f', '%f')
                );
            }
        }
    }
    
    /**
     * Broadcast prices via WebSocket
     */
    private function broadcast_prices($prices) {
        if (class_exists('Crypto_Exchange_WebSocket')) {
            $websocket = new Crypto_Exchange_WebSocket();
            $websocket->broadcast_to_room('market_data', array(
                'action' => 'price_update',
                'data' => $prices,
                'timestamp' => time()
            ));
        }
    }
    
    /**
     * Get price history
     */
    public function get_price_history($pair, $interval = '1h', $limit = 100) {
        $table_name = $this->wpdb->prefix . 'crypto_price_history';
        
        // Create price history table if it doesn't exist
        $this->create_price_history_table();
        
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM $table_name 
                 WHERE pair = %s AND interval_type = %s 
                 ORDER BY timestamp DESC 
                 LIMIT %d",
                $pair,
                $interval,
                $limit
            )
        );
        
        return array_reverse($results);
    }
    
    /**
     * Create price history table
     */
    private function create_price_history_table() {
        $table_name = $this->wpdb->prefix . 'crypto_price_history';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            pair varchar(20) NOT NULL,
            price decimal(20,8) NOT NULL,
            volume decimal(20,8) DEFAULT 0,
            high decimal(20,8) DEFAULT 0,
            low decimal(20,8) DEFAULT 0,
            open decimal(20,8) DEFAULT 0,
            close decimal(20,8) DEFAULT 0,
            interval_type varchar(10) DEFAULT '1h',
            timestamp bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY pair_interval (pair, interval_type),
            KEY timestamp (timestamp)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Add custom cron intervals
     */
    public function add_cron_intervals($schedules) {
        $schedules['every_5_seconds'] = array(
            'interval' => 5,
            'display' => __('Every 5 Seconds')
        );
        $schedules['every_10_seconds'] = array(
            'interval' => 10,
            'display' => __('Every 10 Seconds')
        );
        return $schedules;
    }
    
    /**
     * Get exchange status
     */
    public function get_exchange_status() {
        $status = array();
        
        foreach ($this->exchanges as $exchange_id => $exchange) {
            $status[$exchange_id] = array(
                'name' => $exchange['name'],
                'enabled' => $exchange['enabled'],
                'weight' => $exchange['weight'],
                'last_update' => get_option('crypto_exchange_' . $exchange_id . '_last_update', 0),
                'status' => $this->check_exchange_health($exchange_id)
            );
        }
        
        return $status;
    }
    
    /**
     * Check exchange health
     */
    private function check_exchange_health($exchange_id) {
        $last_update = get_option('crypto_exchange_' . $exchange_id . '_last_update', 0);
        $time_diff = time() - $last_update;
        
        if ($time_diff < 60) {
            return 'healthy';
        } elseif ($time_diff < 300) {
            return 'warning';
        } else {
            return 'error';
        }
    }
    
    /**
     * Update exchange status
     */
    public function update_exchange_status($exchange_id, $status) {
        update_option('crypto_exchange_' . $exchange_id . '_last_update', time());
        update_option('crypto_exchange_' . $exchange_id . '_status', $status);
    }
}
