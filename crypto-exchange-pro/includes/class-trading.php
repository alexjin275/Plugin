<?php
/**
 * Trading class for Crypto Exchange Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Trading {
    
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Place a new order
     */
    public function place_order($data) {
        $user_id = get_current_user_id();
        
        // Validate required fields
        if (empty($data['pair']) || empty($data['side']) || empty($data['amount'])) {
            return array(
                'success' => false,
                'message' => 'Missing required fields'
            );
        }
        
        // Validate trading pair
        $pair = $this->get_trading_pair($data['pair']);
        if (!$pair || !$pair->is_active) {
            return array(
                'success' => false,
                'message' => 'Invalid or inactive trading pair'
            );
        }
        
        // Validate amount
        $amount = floatval($data['amount']);
        if ($amount < $pair->min_trade_amount || $amount > $pair->max_trade_amount) {
            return array(
                'success' => false,
                'message' => 'Amount must be between ' . $pair->min_trade_amount . ' and ' . $pair->max_trade_amount
            );
        }
        
        // Validate price for limit orders
        $price = null;
        if ($data['order_type'] === 'limit') {
            if (empty($data['price'])) {
                return array(
                    'success' => false,
                    'message' => 'Price is required for limit orders'
                );
            }
            $price = floatval($data['price']);
        }
        
        // Check user balance
        if (!$this->check_user_balance($user_id, $data['side'], $data['pair'], $amount, $price)) {
            return array(
                'success' => false,
                'message' => 'Insufficient balance'
            );
        }
        
        // Create order
        $order_id = $this->create_order($user_id, $data, $pair);
        
        if ($order_id) {
            // Try to match the order
            $this->match_order($order_id);
            
            return array(
                'success' => true,
                'message' => 'Order placed successfully',
                'order_id' => $order_id
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to place order'
            );
        }
    }
    
    /**
     * Create order in database
     */
    private function create_order($user_id, $data, $pair) {
        $order_type = $data['order_type'] ?? 'market';
        $side = $data['side'];
        $amount = floatval($data['amount']);
        $price = isset($data['price']) ? floatval($data['price']) : null;
        
        // Calculate fee
        $fee_rate = $side === 'buy' ? $pair->taker_fee : $pair->maker_fee;
        $fee = $amount * $price * $fee_rate;
        
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_orders',
            array(
                'user_id' => $user_id,
                'pair' => $data['pair'],
                'order_type' => $order_type,
                'side' => $side,
                'amount' => $amount,
                'price' => $price,
                'remaining_amount' => $amount,
                'status' => 'pending',
                'fee' => $fee,
                'fee_currency' => $pair->quote_currency
            ),
            array('%d', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%f', '%s')
        );
        
        if ($result) {
            return $this->wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Match orders
     */
    private function match_order($order_id) {
        $order = $this->get_order($order_id);
        if (!$order) {
            return false;
        }
        
        // Find matching orders
        $matching_orders = $this->find_matching_orders($order);
        
        foreach ($matching_orders as $match_order) {
            if ($this->execute_trade($order, $match_order)) {
                // Update order status if fully filled
                if ($order->remaining_amount <= 0) {
                    $this->update_order_status($order->id, 'filled');
                }
                if ($match_order->remaining_amount <= 0) {
                    $this->update_order_status($match_order->id, 'filled');
                }
            }
        }
        
        return true;
    }
    
    /**
     * Find matching orders
     */
    private function find_matching_orders($order) {
        $opposite_side = $order->side === 'buy' ? 'sell' : 'buy';
        
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}crypto_orders 
             WHERE pair = %s AND side = %s AND status = 'pending' AND user_id != %d",
            $order->pair,
            $opposite_side,
            $order->user_id
        );
        
        if ($order->order_type === 'limit') {
            if ($order->side === 'buy') {
                $query .= $this->wpdb->prepare(" AND price <= %f", $order->price);
            } else {
                $query .= $this->wpdb->prepare(" AND price >= %f", $order->price);
            }
        }
        
        $query .= " ORDER BY price " . ($order->side === 'buy' ? 'ASC' : 'DESC') . ", created_at ASC";
        
        return $this->wpdb->get_results($query);
    }
    
    /**
     * Execute trade between two orders
     */
    private function execute_trade($order1, $order2) {
        $trade_amount = min($order1->remaining_amount, $order2->remaining_amount);
        $trade_price = $order1->order_type === 'market' ? $order2->price : $order1->price;
        $trade_total = $trade_amount * $trade_price;
        
        // Calculate fees
        $pair = $this->get_trading_pair($order1->pair);
        $fee1 = $trade_total * $pair->taker_fee;
        $fee2 = $trade_total * $pair->maker_fee;
        
        // Create trade record
        $trade_id = $this->create_trade($order1, $order2, $trade_amount, $trade_price, $trade_total, $fee1, $fee2);
        
        if ($trade_id) {
            // Update order amounts
            $this->update_order_amounts($order1->id, $trade_amount);
            $this->update_order_amounts($order2->id, $trade_amount);
            
            // Update user balances
            $this->update_user_balances($order1, $order2, $trade_amount, $trade_price, $fee1, $fee2);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Create trade record
     */
    private function create_trade($order1, $order2, $amount, $price, $total, $fee1, $fee2) {
        $buy_order = $order1->side === 'buy' ? $order1 : $order2;
        $sell_order = $order1->side === 'sell' ? $order1 : $order2;
        
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_trades',
            array(
                'buy_order_id' => $buy_order->id,
                'sell_order_id' => $sell_order->id,
                'buyer_id' => $buy_order->user_id,
                'seller_id' => $sell_order->user_id,
                'pair' => $order1->pair,
                'amount' => $amount,
                'price' => $price,
                'total' => $total,
                'buyer_fee' => $fee1,
                'seller_fee' => $fee2
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
                 SET filled_amount = filled_amount + %f, remaining_amount = remaining_amount - %f 
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
    private function update_user_balances($order1, $order2, $amount, $price, $fee1, $fee2) {
        $pair = $this->get_trading_pair($order1->pair);
        $base_currency = $pair->base_currency;
        $quote_currency = $pair->quote_currency;
        
        // Update buyer's balance
        $buyer = $order1->side === 'buy' ? $order1 : $order2;
        $this->update_wallet_balance($buyer->user_id, $base_currency, $amount);
        $this->update_wallet_balance($buyer->user_id, $quote_currency, -($amount * $price + $fee1));
        
        // Update seller's balance
        $seller = $order1->side === 'sell' ? $order1 : $order2;
        $this->update_wallet_balance($seller->user_id, $base_currency, -$amount);
        $this->update_wallet_balance($seller->user_id, $quote_currency, $amount * $price - $fee2);
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
     * Cancel order
     */
    public function cancel_order($order_id) {
        $order = $this->get_order($order_id);
        
        if (!$order) {
            return array(
                'success' => false,
                'message' => 'Order not found'
            );
        }
        
        if ($order->user_id != get_current_user_id()) {
            return array(
                'success' => false,
                'message' => 'Unauthorized'
            );
        }
        
        if ($order->status !== 'pending') {
            return array(
                'success' => false,
                'message' => 'Order cannot be cancelled'
            );
        }
        
        $result = $this->update_order_status($order_id, 'cancelled');
        
        if ($result) {
            return array(
                'success' => true,
                'message' => 'Order cancelled successfully'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to cancel order'
            );
        }
    }
    
    /**
     * Get order by ID
     */
    private function get_order($order_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_orders WHERE id = %d",
                $order_id
            )
        );
    }
    
    /**
     * Update order status
     */
    private function update_order_status($order_id, $status) {
        return $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_orders',
            array('status' => $status),
            array('id' => $order_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Get trading pair
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
     * Check user balance
     */
    private function check_user_balance($user_id, $side, $pair, $amount, $price) {
        $pair_data = $this->get_trading_pair($pair);
        $currency = $side === 'buy' ? $pair_data->quote_currency : $pair_data->base_currency;
        
        $wallet = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT balance FROM {$this->wpdb->prefix}crypto_wallets 
                 WHERE user_id = %d AND currency = %s",
                $user_id,
                $currency
            )
        );
        
        if (!$wallet) {
            return false;
        }
        
        $required_amount = $side === 'buy' ? $amount * $price : $amount;
        
        return $wallet->balance >= $required_amount;
    }
    
    /**
     * Get user orders
     */
    public function get_user_orders($user_id) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_orders 
                 WHERE user_id = %d 
                 ORDER BY created_at DESC",
                $user_id
            )
        );
    }
    
    /**
     * Get user trades
     */
    public function get_user_trades($user_id) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_trades 
                 WHERE buyer_id = %d OR seller_id = %d 
                 ORDER BY created_at DESC",
                $user_id,
                $user_id
            )
        );
    }
    
    /**
     * Get order book
     */
    public function get_order_book($pair, $limit = 50) {
        $buy_orders = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT price, SUM(remaining_amount) as amount 
                 FROM {$this->wpdb->prefix}crypto_orders 
                 WHERE pair = %s AND side = 'buy' AND status = 'pending' 
                 GROUP BY price 
                 ORDER BY price DESC 
                 LIMIT %d",
                $pair,
                $limit
            )
        );
        
        $sell_orders = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT price, SUM(remaining_amount) as amount 
                 FROM {$this->wpdb->prefix}crypto_orders 
                 WHERE pair = %s AND side = 'sell' AND status = 'pending' 
                 GROUP BY price 
                 ORDER BY price ASC 
                 LIMIT %d",
                $pair,
                $limit
            )
        );
        
        return array(
            'bids' => $buy_orders,
            'asks' => $sell_orders
        );
    }
    
    /**
     * Render trading page
     */
    public function render() {
        ?>
        <div class="crypto-trading-container">
            <div class="trading-header">
                <h1>Trading</h1>
                <div class="trading-pair-selector">
                    <select id="trading-pair">
                        <option value="BTC/USD">BTC/USD</option>
                        <option value="ETH/USD">ETH/USD</option>
                        <option value="BNB/USD">BNB/USD</option>
                    </select>
                </div>
            </div>
            
            <div class="trading-content">
                <div class="trading-left">
                    <div class="order-form">
                        <h3>Place Order</h3>
                        <form id="place-order-form">
                            <div class="form-group">
                                <label>Order Type</label>
                                <select name="order_type" id="order-type">
                                    <option value="market">Market</option>
                                    <option value="limit">Limit</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Side</label>
                                <div class="side-buttons">
                                    <button type="button" class="side-btn buy-btn active" data-side="buy">Buy</button>
                                    <button type="button" class="side-btn sell-btn" data-side="sell">Sell</button>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Amount</label>
                                <input type="number" name="amount" step="0.00000001" required>
                            </div>
                            
                            <div class="form-group" id="price-group" style="display: none;">
                                <label>Price</label>
                                <input type="number" name="price" step="0.01">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Place Order</button>
                        </form>
                    </div>
                    
                    <div class="order-history">
                        <h3>My Orders</h3>
                        <div id="orders-list">
                            <!-- Orders will be loaded here -->
                        </div>
                    </div>
                </div>
                
                <div class="trading-right">
                    <div class="market-data">
                        <h3>Market Data</h3>
                        <div id="market-data">
                            <!-- Market data will be loaded here -->
                        </div>
                    </div>
                    
                    <div class="order-book">
                        <h3>Order Book</h3>
                        <div id="order-book">
                            <!-- Order book will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
