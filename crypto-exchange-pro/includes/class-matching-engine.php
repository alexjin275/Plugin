<?php
/**
 * Advanced Order Matching Engine
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Matching_Engine {
    
    private $wpdb;
    private $order_books = array();
    private $trade_queue = array();
    private $price_levels = array();
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        add_action('crypto_exchange_process_orders', array($this, 'process_order_queue'));
        add_action('crypto_exchange_update_order_books', array($this, 'update_order_books'));
        
        if (!wp_next_scheduled('crypto_exchange_process_orders')) {
            wp_schedule_event(time(), 'every_second', 'crypto_exchange_process_orders');
        }
        
        if (!wp_next_scheduled('crypto_exchange_update_order_books')) {
            wp_schedule_event(time(), 'every_5_seconds', 'crypto_exchange_update_order_books');
        }
    }
    
    /**
     * Process order queue
     */
    public function process_order_queue() {
        $pending_orders = $this->get_pending_orders();
        
        foreach ($pending_orders as $order) {
            $this->process_order($order);
        }
    }
    
    /**
     * Get pending orders
     */
    private function get_pending_orders() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_orders 
             WHERE status = 'pending' 
             ORDER BY created_at ASC 
             LIMIT 100"
        );
    }
    
    /**
     * Process individual order
     */
    public function process_order($order) {
        $pair = $order->pair;
        
        // Initialize order book for pair if not exists
        if (!isset($this->order_books[$pair])) {
            $this->initialize_order_book($pair);
        }
        
        // Add order to order book
        $this->add_order_to_book($order);
        
        // Try to match the order
        $this->match_order($order);
    }
    
    /**
     * Initialize order book for pair
     */
    private function initialize_order_book($pair) {
        $this->order_books[$pair] = array(
            'bids' => new SplPriorityQueue(), // Highest price first
            'asks' => new SplPriorityQueue(), // Lowest price first
            'last_price' => 0,
            'volume_24h' => 0
        );
        
        // Load existing orders for this pair
        $this->load_existing_orders($pair);
    }
    
    /**
     * Load existing orders for pair
     */
    private function load_existing_orders($pair) {
        $orders = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_orders 
                 WHERE pair = %s AND status = 'pending' 
                 ORDER BY created_at ASC",
                $pair
            )
        );
        
        foreach ($orders as $order) {
            $this->add_order_to_book($order);
        }
    }
    
    /**
     * Add order to order book
     */
    private function add_order_to_book($order) {
        $pair = $order->pair;
        $price = floatval($order->price);
        $amount = floatval($order->remaining_amount);
        
        if ($order->side === 'buy') {
            // For bids, higher price = higher priority
            $this->order_books[$pair]['bids']->insert($order, $price);
        } else {
            // For asks, lower price = higher priority
            $this->order_books[$pair]['asks']->insert($order, -$price);
        }
    }
    
    /**
     * Match order against order book
     */
    private function match_order($order) {
        $pair = $order->pair;
        $matches = array();
        
        if ($order->side === 'buy') {
            $matches = $this->find_sell_matches($order);
        } else {
            $matches = $this->find_buy_matches($order);
        }
        
        // Execute matches
        foreach ($matches as $match) {
            $this->execute_trade($order, $match);
        }
        
        // Update order status
        $this->update_order_status($order);
    }
    
    /**
     * Find sell matches for buy order
     */
    private function find_sell_matches($buy_order) {
        $pair = $buy_order->pair;
        $matches = array();
        $remaining_amount = floatval($buy_order->remaining_amount);
        
        $asks = clone $this->order_books[$pair]['asks'];
        
        while (!$asks->isEmpty() && $remaining_amount > 0) {
            $sell_order = $asks->extract();
            
            // Check if prices match
            if ($buy_order->order_type === 'market' || 
                floatval($buy_order->price) >= floatval($sell_order->price)) {
                
                $match_amount = min($remaining_amount, floatval($sell_order->remaining_amount));
                
                $matches[] = array(
                    'order' => $sell_order,
                    'amount' => $match_amount,
                    'price' => floatval($sell_order->price)
                );
                
                $remaining_amount -= $match_amount;
            }
        }
        
        return $matches;
    }
    
    /**
     * Find buy matches for sell order
     */
    private function find_buy_matches($sell_order) {
        $pair = $sell_order->pair;
        $matches = array();
        $remaining_amount = floatval($sell_order->remaining_amount);
        
        $bids = clone $this->order_books[$pair]['bids'];
        
        while (!$bids->isEmpty() && $remaining_amount > 0) {
            $buy_order = $bids->extract();
            
            // Check if prices match
            if ($sell_order->order_type === 'market' || 
                floatval($sell_order->price) <= floatval($buy_order->price)) {
                
                $match_amount = min($remaining_amount, floatval($buy_order->remaining_amount));
                
                $matches[] = array(
                    'order' => $buy_order,
                    'amount' => $match_amount,
                    'price' => floatval($buy_order->price)
                );
                
                $remaining_amount -= $match_amount;
            }
        }
        
        return $matches;
    }
    
    /**
     * Execute trade between two orders
     */
    private function execute_trade($order1, $match) {
        $order2 = $match['order'];
        $amount = $match['amount'];
        $price = $match['price'];
        
        // Determine buyer and seller
        $buyer = $order1->side === 'buy' ? $order1 : $order2;
        $seller = $order1->side === 'sell' ? $order1 : $order2;
        
        // Calculate fees
        $fees = $this->calculate_fees($buyer, $seller, $amount, $price);
        
        // Create trade record
        $trade_id = $this->create_trade_record($buyer, $seller, $amount, $price, $fees);
        
        if ($trade_id) {
            // Update order amounts
            $this->update_order_amounts($order1->id, $amount);
            $this->update_order_amounts($order2->id, $amount);
            
            // Update user balances
            $this->update_user_balances($buyer, $seller, $amount, $price, $fees);
            
            // Update order book
            $this->update_order_book($order1->pair, $price, $amount);
            
            // Broadcast trade
            $this->broadcast_trade($trade_id);
        }
    }
    
    /**
     * Calculate trading fees
     */
    private function calculate_fees($buyer, $seller, $amount, $price) {
        $pair_data = $this->get_trading_pair($buyer->pair);
        $total_value = $amount * $price;
        
        $buyer_fee = $total_value * $pair_data->taker_fee;
        $seller_fee = $total_value * $pair_data->maker_fee;
        
        return array(
            'buyer_fee' => $buyer_fee,
            'seller_fee' => $seller_fee,
            'total_fee' => $buyer_fee + $seller_fee
        );
    }
    
    /**
     * Create trade record
     */
    private function create_trade_record($buyer, $seller, $amount, $price, $fees) {
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_trades',
            array(
                'buy_order_id' => $buyer->id,
                'sell_order_id' => $seller->id,
                'buyer_id' => $buyer->user_id,
                'seller_id' => $seller->user_id,
                'pair' => $buyer->pair,
                'amount' => $amount,
                'price' => $price,
                'total' => $amount * $price,
                'buyer_fee' => $fees['buyer_fee'],
                'seller_fee' => $fees['seller_fee']
            ),
            array('%d', '%d', '%d', '%d', '%s', '%f', '%f', '%f', '%f', '%f')
        );
        
        return $result ? $this->wpdb->insert_id : false;
    }
    
    /**
     * Update order amounts
     */
    private function update_order_amounts($order_id, $amount) {
        $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->wpdb->prefix}crypto_orders 
                 SET filled_amount = filled_amount + %f, 
                     remaining_amount = remaining_amount - %f 
                 WHERE id = %d",
                $amount,
                $amount,
                $order_id
            )
        );
    }
    
    /**
     * Update user balances
     */
    private function update_user_balances($buyer, $seller, $amount, $price, $fees) {
        $pair_data = $this->get_trading_pair($buyer->pair);
        $base_currency = $pair_data->base_currency;
        $quote_currency = $pair_data->quote_currency;
        
        // Update buyer's balance
        $this->update_wallet_balance($buyer->user_id, $base_currency, $amount);
        $this->update_wallet_balance($buyer->user_id, $quote_currency, -($amount * $price + $fees['buyer_fee']));
        
        // Update seller's balance
        $this->update_wallet_balance($seller->user_id, $base_currency, -$amount);
        $this->update_wallet_balance($seller->user_id, $quote_currency, $amount * $price - $fees['seller_fee']);
    }
    
    /**
     * Update wallet balance
     */
    private function update_wallet_balance($user_id, $currency, $amount) {
        $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->wpdb->prefix}crypto_wallets 
                 SET balance = balance + %f 
                 WHERE user_id = %d AND currency = %s",
                $amount,
                $user_id,
                $currency
            )
        );
    }
    
    /**
     * Update order book
     */
    private function update_order_book($pair, $price, $amount) {
        $this->order_books[$pair]['last_price'] = $price;
        $this->order_books[$pair]['volume_24h'] += $amount;
    }
    
    /**
     * Update order status
     */
    private function update_order_status($order) {
        $remaining = floatval($order->remaining_amount);
        
        if ($remaining <= 0) {
            $this->wpdb->update(
                $this->wpdb->prefix . 'crypto_orders',
                array('status' => 'filled'),
                array('id' => $order->id),
                array('%s'),
                array('%d')
            );
        }
    }
    
    /**
     * Get trading pair data
     */
    private function get_trading_pair($pair) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_trading_pairs WHERE pair = %s",
                $pair
            )
        );
    }
    
    /**
     * Broadcast trade
     */
    private function broadcast_trade($trade_id) {
        $trade = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_trades WHERE id = %d",
                $trade_id
            )
        );
        
        if (class_exists('Crypto_Exchange_WebSocket')) {
            $websocket = new Crypto_Exchange_WebSocket();
            $websocket->broadcast_to_room('trades', array(
                'action' => 'new_trade',
                'data' => $trade
            ));
        }
    }
    
    /**
     * Get order book for pair
     */
    public function get_order_book($pair, $depth = 20) {
        if (!isset($this->order_books[$pair])) {
            $this->initialize_order_book($pair);
        }
        
        $bids = array();
        $asks = array();
        
        // Get top bids
        $bids_queue = clone $this->order_books[$pair]['bids'];
        $count = 0;
        while (!$bids_queue->isEmpty() && $count < $depth) {
            $order = $bids_queue->extract();
            $bids[] = array(
                'price' => floatval($order->price),
                'amount' => floatval($order->remaining_amount),
                'total' => floatval($order->price) * floatval($order->remaining_amount)
            );
            $count++;
        }
        
        // Get top asks
        $asks_queue = clone $this->order_books[$pair]['asks'];
        $count = 0;
        while (!$asks_queue->isEmpty() && $count < $depth) {
            $order = $asks_queue->extract();
            $asks[] = array(
                'price' => floatval($order->price),
                'amount' => floatval($order->remaining_amount),
                'total' => floatval($order->price) * floatval($order->remaining_amount)
            );
            $count++;
        }
        
        return array(
            'bids' => $bids,
            'asks' => $asks,
            'last_price' => $this->order_books[$pair]['last_price'],
            'volume_24h' => $this->order_books[$pair]['volume_24h']
        );
    }
    
    /**
     * Get market depth
     */
    public function get_market_depth($pair, $levels = 10) {
        $order_book = $this->get_order_book($pair, $levels);
        
        $depth = array(
            'bids' => array(),
            'asks' => array()
        );
        
        // Aggregate bids by price level
        foreach ($order_book['bids'] as $bid) {
            $price = $bid['price'];
            if (!isset($depth['bids'][$price])) {
                $depth['bids'][$price] = 0;
            }
            $depth['bids'][$price] += $bid['amount'];
        }
        
        // Aggregate asks by price level
        foreach ($order_book['asks'] as $ask) {
            $price = $ask['price'];
            if (!isset($depth['asks'][$price])) {
                $depth['asks'][$price] = 0;
            }
            $depth['asks'][$price] += $ask['amount'];
        }
        
        return $depth;
    }
    
    /**
     * Get recent trades
     */
    public function get_recent_trades($pair, $limit = 50) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_trades 
                 WHERE pair = %s 
                 ORDER BY created_at DESC 
                 LIMIT %d",
                $pair,
                $limit
            )
        );
    }
    
    /**
     * Get 24h statistics
     */
    public function get_24h_stats($pair) {
        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT 
                    COUNT(*) as trade_count,
                    SUM(amount) as volume,
                    AVG(price) as avg_price,
                    MIN(price) as low_price,
                    MAX(price) as high_price,
                    (SELECT price FROM {$this->wpdb->prefix}crypto_trades 
                     WHERE pair = %s ORDER BY created_at DESC LIMIT 1) as last_price
                 FROM {$this->wpdb->prefix}crypto_trades 
                 WHERE pair = %s AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                $pair,
                $pair
            )
        );
        
        return $stats;
    }
    
    /**
     * Update order books
     */
    public function update_order_books() {
        // This would typically update order books from database
        // and clean up filled orders
        $this->cleanup_filled_orders();
    }
    
    /**
     * Cleanup filled orders
     */
    private function cleanup_filled_orders() {
        $this->wpdb->query(
            "DELETE FROM {$this->wpdb->prefix}crypto_orders 
             WHERE status = 'filled' AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
    }
}
