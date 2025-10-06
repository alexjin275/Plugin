<?php
/**
 * Liquidity Aggregation System
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Liquidity_Aggregator {
    
    private $wpdb;
    private $providers;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        add_action('wp_ajax_crypto_exchange_aggregate_order_book', array($this, 'aggregate_order_book'));
        add_action('wp_ajax_crypto_exchange_aggregate_prices', array($this, 'aggregate_prices'));
        add_action('wp_ajax_crypto_exchange_route_order', array($this, 'route_order'));
        
        $this->load_providers();
    }
    
    /**
     * Load active liquidity providers
     */
    private function load_providers() {
        $this->providers = $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_liquidity_providers WHERE status = 'active' ORDER BY priority ASC"
        );
    }
    
    /**
     * Aggregate order book from multiple exchanges
     */
    public function aggregate_order_book($trading_pair, $limit = 100) {
        $aggregated_bids = array();
        $aggregated_asks = array();
        
        foreach ($this->providers as $provider) {
            if ($this->supports_pair($provider, $trading_pair)) {
                try {
                    $exchange_class = 'Crypto_Exchange_' . ucfirst($provider->exchange);
                    
                    if (class_exists($exchange_class)) {
                        $exchange = new $exchange_class($provider);
                        $order_book = $this->get_order_book_from_exchange($exchange, $provider, $trading_pair, $limit);
                        
                        if ($order_book) {
                            $this->merge_order_book($aggregated_bids, $aggregated_asks, $order_book, $provider);
                        }
                    }
                } catch (Exception $e) {
                    error_log('Liquidity aggregation error for ' . $provider->exchange . ': ' . $e->getMessage());
                }
            }
        }
        
        // Sort and limit results
        $aggregated_bids = $this->sort_and_limit_orders($aggregated_bids, 'desc', $limit);
        $aggregated_asks = $this->sort_and_limit_orders($aggregated_asks, 'asc', $limit);
        
        return array(
            'bids' => $aggregated_bids,
            'asks' => $aggregated_asks,
            'timestamp' => time()
        );
    }
    
    /**
     * Get order book from specific exchange
     */
    private function get_order_book_from_exchange($exchange, $provider, $trading_pair, $limit) {
        $method = $this->get_order_book_method($provider->exchange);
        
        if (method_exists($exchange, $method)) {
            return $exchange->$method($trading_pair, $limit);
        }
        
        return null;
    }
    
    /**
     * Get order book method name for exchange
     */
    private function get_order_book_method($exchange) {
        $methods = array(
            'binance' => 'get_order_book',
            'coinbase' => 'get_product_order_book',
            'kraken' => 'get_order_book',
            'huobi' => 'get_depth'
        );
        
        return $methods[$exchange] ?? 'get_order_book';
    }
    
    /**
     * Merge order book data
     */
    private function merge_order_book(&$bids, &$asks, $order_book, $provider) {
        if (isset($order_book['bids'])) {
            foreach ($order_book['bids'] as $bid) {
                $price = floatval($bid[0]);
                $amount = floatval($bid[1]);
                
                if (isset($bids[$price])) {
                    $bids[$price]['amount'] += $amount;
                    $bids[$price]['providers'][] = $provider->exchange;
                } else {
                    $bids[$price] = array(
                        'price' => $price,
                        'amount' => $amount,
                        'providers' => array($provider->exchange)
                    );
                }
            }
        }
        
        if (isset($order_book['asks'])) {
            foreach ($order_book['asks'] as $ask) {
                $price = floatval($ask[0]);
                $amount = floatval($ask[1]);
                
                if (isset($asks[$price])) {
                    $asks[$price]['amount'] += $amount;
                    $asks[$price]['providers'][] = $provider->exchange;
                } else {
                    $asks[$price] = array(
                        'price' => $price,
                        'amount' => $amount,
                        'providers' => array($provider->exchange)
                    );
                }
            }
        }
    }
    
    /**
     * Sort and limit orders
     */
    private function sort_and_limit_orders($orders, $direction, $limit) {
        if ($direction === 'desc') {
            krsort($orders);
        } else {
            ksort($orders);
        }
        
        $result = array();
        $count = 0;
        
        foreach ($orders as $order) {
            if ($count >= $limit) break;
            
            $result[] = array(
                $order['price'],
                $order['amount']
            );
            
            $count++;
        }
        
        return $result;
    }
    
    /**
     * Aggregate prices from multiple exchanges
     */
    public function aggregate_prices($trading_pair = null) {
        $prices = array();
        
        foreach ($this->providers as $provider) {
            if (!$trading_pair || $this->supports_pair($provider, $trading_pair)) {
                try {
                    $exchange_class = 'Crypto_Exchange_' . ucfirst($provider->exchange);
                    
                    if (class_exists($exchange_class)) {
                        $exchange = new $exchange_class($provider);
                        $price_data = $this->get_price_from_exchange($exchange, $provider, $trading_pair);
                        
                        if ($price_data) {
                            $this->merge_price_data($prices, $price_data, $provider);
                        }
                    }
                } catch (Exception $e) {
                    error_log('Price aggregation error for ' . $provider->exchange . ': ' . $e->getMessage());
                }
            }
        }
        
        return $this->calculate_weighted_prices($prices);
    }
    
    /**
     * Get price data from specific exchange
     */
    private function get_price_from_exchange($exchange, $provider, $trading_pair) {
        $method = $this->get_price_method($provider->exchange);
        
        if (method_exists($exchange, $method)) {
            if ($trading_pair) {
                return $exchange->$method($trading_pair);
            } else {
                return $exchange->$method();
            }
        }
        
        return null;
    }
    
    /**
     * Get price method name for exchange
     */
    private function get_price_method($exchange) {
        $methods = array(
            'binance' => 'get_24hr_ticker',
            'coinbase' => 'get_product_24hr_stats',
            'kraken' => 'get_ticker',
            'huobi' => 'get_24hr_ticker'
        );
        
        return $methods[$exchange] ?? 'get_ticker';
    }
    
    /**
     * Merge price data
     */
    private function merge_price_data(&$prices, $price_data, $provider) {
        if (is_array($price_data)) {
            foreach ($price_data as $symbol => $data) {
                if (!isset($prices[$symbol])) {
                    $prices[$symbol] = array();
                }
                
                $prices[$symbol][] = array(
                    'price' => $this->extract_price($data),
                    'volume' => $this->extract_volume($data),
                    'change_24h' => $this->extract_change_24h($data),
                    'provider' => $provider->exchange,
                    'weight' => $this->calculate_provider_weight($provider)
                );
            }
        }
    }
    
    /**
     * Extract price from exchange data
     */
    private function extract_price($data) {
        if (isset($data['last_price'])) return floatval($data['last_price']);
        if (isset($data['close'])) return floatval($data['close']);
        if (isset($data['price'])) return floatval($data['price']);
        if (isset($data['last'])) return floatval($data['last']);
        if (isset($data['c'][0])) return floatval($data['c'][0]);
        
        return 0;
    }
    
    /**
     * Extract volume from exchange data
     */
    private function extract_volume($data) {
        if (isset($data['volume'])) return floatval($data['volume']);
        if (isset($data['vol'])) return floatval($data['vol']);
        if (isset($data['quote_volume'])) return floatval($data['quote_volume']);
        if (isset($data['v'][1])) return floatval($data['v'][1]);
        
        return 0;
    }
    
    /**
     * Extract 24h change from exchange data
     */
    private function extract_change_24h($data) {
        if (isset($data['change_24h'])) return floatval($data['change_24h']);
        if (isset($data['price_change_percent'])) return floatval($data['price_change_percent']);
        if (isset($data['change'])) return floatval($data['change']);
        
        return 0;
    }
    
    /**
     * Calculate provider weight
     */
    private function calculate_provider_weight($provider) {
        $weight = 1.0;
        
        // Adjust weight based on provider priority
        $weight *= (100 - $provider->priority) / 100;
        
        // Adjust weight based on trading fee (lower fee = higher weight)
        $weight *= (1 - $provider->trading_fee);
        
        // Adjust weight based on reliability
        $reliability = $this->get_provider_reliability($provider->id);
        $weight *= $reliability;
        
        return $weight;
    }
    
    /**
     * Get provider reliability score
     */
    private function get_provider_reliability($provider_id) {
        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'filled' THEN 1 ELSE 0 END) as successful_orders
                 FROM {$this->wpdb->prefix}crypto_liquidity_orders 
                 WHERE provider_id = %d 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                $provider_id
            )
        );
        
        if ($stats->total_orders > 0) {
            return $stats->successful_orders / $stats->total_orders;
        }
        
        return 0.5; // Default reliability
    }
    
    /**
     * Calculate weighted prices
     */
    private function calculate_weighted_prices($prices) {
        $result = array();
        
        foreach ($prices as $symbol => $price_data) {
            if (empty($price_data)) continue;
            
            $total_weight = 0;
            $weighted_price = 0;
            $weighted_volume = 0;
            $weighted_change = 0;
            
            foreach ($price_data as $data) {
                $weight = $data['weight'];
                $total_weight += $weight;
                
                $weighted_price += $data['price'] * $weight;
                $weighted_volume += $data['volume'] * $weight;
                $weighted_change += $data['change_24h'] * $weight;
            }
            
            if ($total_weight > 0) {
                $result[$symbol] = array(
                    'price' => $weighted_price / $total_weight,
                    'volume' => $weighted_volume / $total_weight,
                    'change_24h' => $weighted_change / $total_weight,
                    'providers' => array_unique(array_column($price_data, 'provider'))
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Route order to best provider
     */
    public function route_order($trading_pair, $side, $amount, $price = null) {
        $best_provider = $this->find_best_provider($trading_pair, $side, $amount, $price);
        
        if (!$best_provider) {
            return array(
                'success' => false,
                'error' => 'No suitable provider found'
            );
        }
        
        try {
            $exchange_class = 'Crypto_Exchange_' . ucfirst($best_provider->exchange);
            $exchange = new $exchange_class($best_provider);
            
            $order_result = $this->place_order_on_exchange($exchange, $best_provider, $trading_pair, $side, $amount, $price);
            
            if ($order_result['success']) {
                $this->record_liquidity_order($best_provider->id, $order_result, $trading_pair, $side, $amount, $price);
            }
            
            return $order_result;
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => 'Order routing failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Find best provider for order
     */
    private function find_best_provider($trading_pair, $side, $amount, $price) {
        $suitable_providers = array();
        
        foreach ($this->providers as $provider) {
            if ($this->supports_pair($provider, $trading_pair) && 
                $this->meets_requirements($provider, $amount, $price)) {
                
                $score = $this->calculate_provider_score($provider, $trading_pair, $side, $amount);
                $suitable_providers[] = array(
                    'provider' => $provider,
                    'score' => $score
                );
            }
        }
        
        if (empty($suitable_providers)) {
            return null;
        }
        
        // Sort by score (highest first)
        usort($suitable_providers, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $suitable_providers[0]['provider'];
    }
    
    /**
     * Check if provider supports trading pair
     */
    private function supports_pair($provider, $trading_pair) {
        if ($provider->supported_pairs === 'ALL') {
            return true;
        }
        
        $supported_pairs = explode(',', $provider->supported_pairs);
        return in_array($trading_pair, $supported_pairs);
    }
    
    /**
     * Check if provider meets order requirements
     */
    private function meets_requirements($provider, $amount, $price) {
        // Check amount limits
        if ($amount < $provider->min_order_size || $amount > $provider->max_order_size) {
            return false;
        }
        
        // Check price limits if provided
        if ($price !== null) {
            $total_value = $amount * $price;
            if ($total_value > $provider->max_daily_volume) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Calculate provider score
     */
    private function calculate_provider_score($provider, $trading_pair, $side, $amount) {
        $score = 0;
        
        // Priority score (higher priority = higher score)
        $score += (100 - $provider->priority) * 10;
        
        // Fee score (lower fee = higher score)
        $score += (1 - $provider->trading_fee) * 100;
        
        // Reliability score
        $reliability = $this->get_provider_reliability($provider->id);
        $score += $reliability * 50;
        
        // Latency score
        $latency = $this->get_provider_latency($provider->id);
        if ($latency < 100) {
            $score += 40;
        } elseif ($latency < 500) {
            $score += 20;
        }
        
        return $score;
    }
    
    /**
     * Get provider latency
     */
    private function get_provider_latency($provider_id) {
        $latency = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT AVG(latency) FROM {$this->wpdb->prefix}crypto_liquidity_stats 
                 WHERE provider_id = %d 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                $provider_id
            )
        );
        
        return $latency ?: 1000; // Default latency
    }
    
    /**
     * Place order on exchange
     */
    private function place_order_on_exchange($exchange, $provider, $trading_pair, $side, $amount, $price) {
        $method = $this->get_place_order_method($provider->exchange);
        
        if (method_exists($exchange, $method)) {
            $start_time = microtime(true);
            
            $result = $exchange->$method($trading_pair, $side, 'limit', $amount, $price);
            
            $latency = round((microtime(true) - $start_time) * 1000);
            
            if ($result['success']) {
                $result['latency'] = $latency;
            }
            
            return $result;
        }
        
        return array(
            'success' => false,
            'error' => 'Order placement method not found'
        );
    }
    
    /**
     * Get place order method name for exchange
     */
    private function get_place_order_method($exchange) {
        $methods = array(
            'binance' => 'place_order',
            'coinbase' => 'place_order',
            'kraken' => 'add_order',
            'huobi' => 'place_order'
        );
        
        return $methods[$exchange] ?? 'place_order';
    }
    
    /**
     * Record liquidity order
     */
    private function record_liquidity_order($provider_id, $order_result, $trading_pair, $side, $amount, $price) {
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_liquidity_orders',
            array(
                'provider_id' => $provider_id,
                'internal_order_id' => 0, // Will be set by main trading system
                'external_order_id' => $order_result['order_id'] ?? null,
                'trading_pair' => $trading_pair,
                'side' => $side,
                'order_type' => 'limit',
                'amount' => $amount,
                'price' => $price,
                'status' => $order_result['success'] ? 'filled' : 'failed',
                'filled_amount' => $order_result['success'] ? $amount : 0,
                'remaining_amount' => $order_result['success'] ? 0 : $amount,
                'fee' => $this->calculate_fee($amount, $price),
                'latency' => $order_result['latency'] ?? null,
                'error_message' => $order_result['error'] ?? null,
                'created_at' => current_time('mysql')
            )
        );
    }
    
    /**
     * Calculate trading fee
     */
    private function calculate_fee($amount, $price) {
        $total_value = $amount * $price;
        return $total_value * 0.001; // 0.1% fee
    }
    
    /**
     * AJAX handler for aggregated order book
     */
    public function aggregate_order_book() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        $trading_pair = sanitize_text_field($_POST['trading_pair']);
        $limit = intval($_POST['limit']) ?: 100;
        
        $order_book = $this->aggregate_order_book($trading_pair, $limit);
        
        wp_send_json_success($order_book);
    }
    
    /**
     * AJAX handler for aggregated prices
     */
    public function aggregate_prices() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        $trading_pair = sanitize_text_field($_POST['trading_pair']);
        $prices = $this->aggregate_prices($trading_pair);
        
        wp_send_json_success($prices);
    }
    
    /**
     * AJAX handler for order routing
     */
    public function route_order() {
        check_ajax_referer('crypto_exchange_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $trading_pair = sanitize_text_field($_POST['trading_pair']);
        $side = sanitize_text_field($_POST['side']);
        $amount = floatval($_POST['amount']);
        $price = floatval($_POST['price']);
        
        $result = $this->route_order($trading_pair, $side, $amount, $price);
        
        wp_send_json($result);
    }
}
