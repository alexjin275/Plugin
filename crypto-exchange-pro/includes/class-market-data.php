<?php
/**
 * Market Data class for Crypto Exchange Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Market_Data {
    
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Update all market prices
     */
    public function update_all_prices() {
        $pairs = $this->get_trading_pairs();
        
        foreach ($pairs as $pair) {
            $this->update_pair_price($pair->pair);
        }
    }
    
    /**
     * Update price for specific pair
     */
    public function update_pair_price($pair) {
        $price_data = $this->fetch_price_data($pair);
        
        if ($price_data) {
            $this->save_price_data($pair, $price_data);
        }
    }
    
    /**
     * Fetch price data from external API
     */
    private function fetch_price_data($pair) {
        // This is a simplified version - in production, use real APIs like CoinGecko, CoinMarketCap, etc.
        $mock_prices = array(
            'BTC/USD' => array('price' => 45000, 'change_24h' => 2.5, 'volume_24h' => 1000000000),
            'ETH/USD' => array('price' => 3000, 'change_24h' => 1.8, 'volume_24h' => 800000000),
            'BNB/USD' => array('price' => 300, 'change_24h' => -0.5, 'volume_24h' => 200000000),
            'ADA/USD' => array('price' => 0.5, 'change_24h' => 3.2, 'volume_24h' => 150000000),
            'SOL/USD' => array('price' => 100, 'change_24h' => 5.1, 'volume_24h' => 300000000),
            'DOT/USD' => array('price' => 7, 'change_24h' => -1.2, 'volume_24h' => 100000000),
            'MATIC/USD' => array('price' => 0.8, 'change_24h' => 4.3, 'volume_24h' => 120000000),
            'AVAX/USD' => array('price' => 25, 'change_24h' => 2.1, 'volume_24h' => 180000000)
        );
        
        return $mock_prices[$pair] ?? null;
    }
    
    /**
     * Save price data to database
     */
    private function save_price_data($pair, $data) {
        $existing = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_market_data WHERE pair = %s",
                $pair
            )
        );
        
        if ($existing) {
            // Update existing record
            $this->wpdb->update(
                $this->wpdb->prefix . 'crypto_market_data',
                array(
                    'price' => $data['price'],
                    'change_24h' => $data['change_24h'],
                    'volume_24h' => $data['volume_24h'],
                    'last_updated' => current_time('mysql')
                ),
                array('pair' => $pair),
                array('%f', '%f', '%f', '%s'),
                array('%s')
            );
        } else {
            // Insert new record
            $this->wpdb->insert(
                $this->wpdb->prefix . 'crypto_market_data',
                array(
                    'pair' => $pair,
                    'price' => $data['price'],
                    'change_24h' => $data['change_24h'],
                    'volume_24h' => $data['volume_24h'],
                    'high_24h' => $data['price'] * 1.05, // Mock high
                    'low_24h' => $data['price'] * 0.95,  // Mock low
                    'market_cap' => $data['price'] * 1000000 // Mock market cap
                ),
                array('%s', '%f', '%f', '%f', '%f', '%f', '%f')
            );
        }
    }
    
    /**
     * Get all market data
     */
    public function get_all_market_data() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_market_data ORDER BY pair ASC"
        );
    }
    
    /**
     * Get market data for specific pair
     */
    public function get_pair_data($pair) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_market_data WHERE pair = %s",
                $pair
            )
        );
    }
    
    /**
     * Get trading pairs
     */
    private function get_trading_pairs() {
        return $this->wpdb->get_results(
            "SELECT pair FROM {$this->wpdb->prefix}crypto_trading_pairs WHERE is_active = 1"
        );
    }
    
    /**
     * Get price history
     */
    public function get_price_history($pair, $period = '24h') {
        // This would typically fetch from a separate price history table
        // For now, we'll return mock data
        $current_price = $this->get_pair_data($pair);
        
        if (!$current_price) {
            return array();
        }
        
        $history = array();
        $price = $current_price->price;
        
        // Generate mock historical data
        for ($i = 23; $i >= 0; $i--) {
            $variation = (rand(-100, 100) / 100) * 0.02; // ±2% variation
            $historical_price = $price * (1 + $variation);
            
            $history[] = array(
                'timestamp' => time() - ($i * 3600), // Hourly data
                'price' => $historical_price,
                'volume' => rand(1000000, 10000000)
            );
        }
        
        return $history;
    }
    
    /**
     * Get market summary
     */
    public function get_market_summary() {
        $data = $this->get_all_market_data();
        $summary = array(
            'total_volume' => 0,
            'total_market_cap' => 0,
            'gainers' => 0,
            'losers' => 0,
            'pairs' => array()
        );
        
        foreach ($data as $pair) {
            $summary['total_volume'] += $pair->volume_24h;
            $summary['total_market_cap'] += $pair->market_cap;
            
            if ($pair->change_24h > 0) {
                $summary['gainers']++;
            } else {
                $summary['losers']++;
            }
            
            $summary['pairs'][] = array(
                'pair' => $pair->pair,
                'price' => $pair->price,
                'change_24h' => $pair->change_24h,
                'volume_24h' => $pair->volume_24h,
                'market_cap' => $pair->market_cap
            );
        }
        
        return $summary;
    }
    
    /**
     * Get top gainers
     */
    public function get_top_gainers($limit = 10) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_market_data 
                 WHERE change_24h > 0 
                 ORDER BY change_24h DESC 
                 LIMIT %d",
                $limit
            )
        );
    }
    
    /**
     * Get top losers
     */
    public function get_top_losers($limit = 10) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_market_data 
                 WHERE change_24h < 0 
                 ORDER BY change_24h ASC 
                 LIMIT %d",
                $limit
            )
        );
    }
    
    /**
     * Get most active pairs
     */
    public function get_most_active($limit = 10) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_market_data 
                 ORDER BY volume_24h DESC 
                 LIMIT %d",
                $limit
            )
        );
    }
    
    /**
     * Search pairs
     */
    public function search_pairs($query) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_market_data 
                 WHERE pair LIKE %s 
                 ORDER BY volume_24h DESC",
                '%' . $query . '%'
            )
        );
    }
    
    /**
     * Get market statistics
     */
    public function get_market_stats() {
        $stats = array();
        
        // Total pairs
        $stats['total_pairs'] = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_market_data"
        );
        
        // Total volume 24h
        $stats['total_volume_24h'] = $this->wpdb->get_var(
            "SELECT SUM(volume_24h) FROM {$this->wpdb->prefix}crypto_market_data"
        );
        
        // Average price change
        $stats['avg_change_24h'] = $this->wpdb->get_var(
            "SELECT AVG(change_24h) FROM {$this->wpdb->prefix}crypto_market_data"
        );
        
        // Best performer
        $best = $this->wpdb->get_row(
            "SELECT pair, change_24h FROM {$this->wpdb->prefix}crypto_market_data 
             ORDER BY change_24h DESC LIMIT 1"
        );
        $stats['best_performer'] = $best;
        
        // Worst performer
        $worst = $this->wpdb->get_row(
            "SELECT pair, change_24h FROM {$this->wpdb->prefix}crypto_market_data 
             ORDER BY change_24h ASC LIMIT 1"
        );
        $stats['worst_performer'] = $worst;
        
        return $stats;
    }
    
    /**
     * Initialize default market data
     */
    public function initialize_default_data() {
        $default_pairs = array(
            'BTC/USD', 'ETH/USD', 'BNB/USD', 'ADA/USD', 
            'SOL/USD', 'DOT/USD', 'MATIC/USD', 'AVAX/USD'
        );
        
        foreach ($default_pairs as $pair) {
            $price_data = $this->fetch_price_data($pair);
            if ($price_data) {
                $this->save_price_data($pair, $price_data);
            }
        }
    }
}
