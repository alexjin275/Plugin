<?php
/**
 * Real-time Risk Management and Position Monitoring
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Risk_Management {
    
    private $wpdb;
    private $risk_config;
    private $position_monitor;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->init_risk_config();
        add_action('crypto_exchange_risk_scan', array($this, 'run_risk_scan'));
        add_action('crypto_exchange_position_monitor', array($this, 'monitor_positions'));
        add_action('crypto_exchange_liquidation_check', array($this, 'check_liquidations'));
        add_action('wp_ajax_crypto_exchange_get_risk_metrics', array($this, 'get_risk_metrics'));
        add_action('wp_ajax_crypto_exchange_set_risk_limits', array($this, 'set_risk_limits'));
        
        if (!wp_next_scheduled('crypto_exchange_risk_scan')) {
            wp_schedule_event(time(), 'every_30_seconds', 'crypto_exchange_risk_scan');
        }
        
        if (!wp_next_scheduled('crypto_exchange_position_monitor')) {
            wp_schedule_event(time(), 'every_10_seconds', 'crypto_exchange_position_monitor');
        }
        
        if (!wp_next_scheduled('crypto_exchange_liquidation_check')) {
            wp_schedule_event(time(), 'every_5_seconds', 'crypto_exchange_liquidation_check');
        }
    }
    
    /**
     * Initialize risk configuration
     */
    private function init_risk_config() {
        $this->risk_config = array(
            'max_position_size' => 1000000, // $1M
            'max_daily_loss' => 50000, // $50K
            'max_daily_volume' => 10000000, // $10M
            'max_leverage' => 100, // 100x
            'liquidation_threshold' => 0.8, // 80% of margin
            'margin_call_threshold' => 0.9, // 90% of margin
            'max_open_orders' => 50,
            'max_order_size' => 100000, // $100K
            'volatility_threshold' => 0.05, // 5%
            'correlation_threshold' => 0.8, // 80%
            'enable_stop_loss' => true,
            'enable_take_profit' => true,
            'enable_trailing_stop' => true,
            'enable_hedging' => true,
            'enable_portfolio_hedging' => true,
            'enable_volatility_adjustment' => true,
            'enable_correlation_monitoring' => true,
            'enable_market_impact_analysis' => true,
            'enable_liquidity_monitoring' => true,
            'enable_systemic_risk_monitoring' => true
        );
    }
    
    /**
     * Run risk scan
     */
    public function run_risk_scan() {
        $this->scan_portfolio_risk();
        $this->scan_market_risk();
        $this->scan_liquidity_risk();
        $this->scan_credit_risk();
        $this->scan_operational_risk();
        $this->scan_systemic_risk();
    }
    
    /**
     * Scan portfolio risk
     */
    private function scan_portfolio_risk() {
        $users = $this->wpdb->get_results(
            "SELECT DISTINCT user_id FROM {$this->wpdb->prefix}crypto_wallets WHERE balance > 0"
        );
        
        foreach ($users as $user) {
            $portfolio_risk = $this->calculate_portfolio_risk($user->user_id);
            
            if ($portfolio_risk['risk_score'] > 80) {
                $this->trigger_risk_alert('high_portfolio_risk', $user->user_id, $portfolio_risk);
            }
            
            if ($portfolio_risk['risk_score'] > 95) {
                $this->apply_risk_controls($user->user_id, 'portfolio_risk_limit');
            }
        }
    }
    
    /**
     * Calculate portfolio risk
     */
    private function calculate_portfolio_risk($user_id) {
        $wallets = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_wallets WHERE user_id = %d",
                $user_id
            )
        );
        
        $total_value = 0;
        $risk_score = 0;
        $concentration_risk = 0;
        $volatility_risk = 0;
        
        foreach ($wallets as $wallet) {
            $value = $wallet->balance * $this->get_current_price($wallet->currency);
            $total_value += $value;
            
            // Concentration risk
            $concentration = $value / max($total_value, 1);
            if ($concentration > 0.5) {
                $concentration_risk += $concentration * 50;
            }
            
            // Volatility risk
            $volatility = $this->get_volatility($wallet->currency);
            $volatility_risk += $volatility * $value / max($total_value, 1);
        }
        
        // Position size risk
        $position_risk = min($total_value / $this->risk_config['max_position_size'] * 100, 100);
        
        // Correlation risk
        $correlation_risk = $this->calculate_correlation_risk($wallets);
        
        $risk_score = ($concentration_risk + $volatility_risk + $position_risk + $correlation_risk) / 4;
        
        return array(
            'risk_score' => $risk_score,
            'total_value' => $total_value,
            'concentration_risk' => $concentration_risk,
            'volatility_risk' => $volatility_risk,
            'position_risk' => $position_risk,
            'correlation_risk' => $correlation_risk
        );
    }
    
    /**
     * Scan market risk
     */
    private function scan_market_risk() {
        $pairs = $this->wpdb->get_results(
            "SELECT DISTINCT pair FROM {$this->wpdb->prefix}crypto_market_data"
        );
        
        foreach ($pairs as $pair) {
            $market_risk = $this->calculate_market_risk($pair->pair);
            
            if ($market_risk['risk_score'] > 70) {
                $this->trigger_risk_alert('high_market_risk', null, $market_risk);
            }
        }
    }
    
    /**
     * Calculate market risk
     */
    private function calculate_market_risk($pair) {
        $price_data = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_market_data WHERE pair = %s",
                $pair
            )
        );
        
        if (!$price_data) {
            return array('risk_score' => 0);
        }
        
        $volatility = abs($price_data->change_24h) / 100;
        $volume_risk = $this->calculate_volume_risk($pair);
        $liquidity_risk = $this->calculate_liquidity_risk($pair);
        $trend_risk = $this->calculate_trend_risk($pair);
        
        $risk_score = ($volatility * 40 + $volume_risk * 30 + $liquidity_risk * 20 + $trend_risk * 10);
        
        return array(
            'risk_score' => $risk_score,
            'volatility' => $volatility,
            'volume_risk' => $volume_risk,
            'liquidity_risk' => $liquidity_risk,
            'trend_risk' => $trend_risk
        );
    }
    
    /**
     * Scan liquidity risk
     */
    private function scan_liquidity_risk() {
        $pairs = $this->wpdb->get_results(
            "SELECT DISTINCT pair FROM {$this->wpdb->prefix}crypto_trading_pairs"
        );
        
        foreach ($pairs as $pair) {
            $liquidity_risk = $this->calculate_liquidity_risk($pair->pair);
            
            if ($liquidity_risk > 80) {
                $this->trigger_risk_alert('high_liquidity_risk', null, array(
                    'pair' => $pair->pair,
                    'liquidity_risk' => $liquidity_risk
                ));
            }
        }
    }
    
    /**
     * Calculate liquidity risk
     */
    private function calculate_liquidity_risk($pair) {
        $order_book = $this->get_order_book_depth($pair);
        
        if (!$order_book) {
            return 100; // No liquidity
        }
        
        $bid_depth = $this->calculate_depth($order_book['bids']);
        $ask_depth = $this->calculate_depth($order_book['asks']);
        $spread = $this->calculate_spread($order_book);
        
        $liquidity_score = ($bid_depth + $ask_depth) / 2;
        $spread_penalty = min($spread * 100, 50);
        
        return max(0, 100 - $liquidity_score - $spread_penalty);
    }
    
    /**
     * Scan credit risk
     */
    private function scan_credit_risk() {
        $users = $this->wpdb->get_results(
            "SELECT DISTINCT user_id FROM {$this->wpdb->prefix}crypto_orders WHERE status = 'pending'"
        );
        
        foreach ($users as $user) {
            $credit_risk = $this->calculate_credit_risk($user->user_id);
            
            if ($credit_risk['risk_score'] > 75) {
                $this->trigger_risk_alert('high_credit_risk', $user->user_id, $credit_risk);
            }
        }
    }
    
    /**
     * Calculate credit risk
     */
    private function calculate_credit_risk($user_id) {
        $total_balance = $this->get_total_balance($user_id);
        $total_exposure = $this->get_total_exposure($user_id);
        $kyc_status = get_user_meta($user_id, 'crypto_kyc_status', true);
        $account_age = $this->get_account_age($user_id);
        $trading_history = $this->get_trading_history_score($user_id);
        
        $leverage_ratio = $total_exposure / max($total_balance, 1);
        $kyc_penalty = $kyc_status !== 'verified' ? 30 : 0;
        $age_penalty = $account_age < 30 ? 20 : 0;
        $history_penalty = max(0, 50 - $trading_history);
        
        $risk_score = min(100, $leverage_ratio * 40 + $kyc_penalty + $age_penalty + $history_penalty);
        
        return array(
            'risk_score' => $risk_score,
            'leverage_ratio' => $leverage_ratio,
            'total_balance' => $total_balance,
            'total_exposure' => $total_exposure,
            'kyc_status' => $kyc_status,
            'account_age' => $account_age,
            'trading_history' => $trading_history
        );
    }
    
    /**
     * Scan operational risk
     */
    private function scan_operational_risk() {
        $system_health = $this->check_system_health();
        $api_health = $this->check_api_health();
        $database_health = $this->check_database_health();
        $network_health = $this->check_network_health();
        
        $operational_risk = ($system_health + $api_health + $database_health + $network_health) / 4;
        
        if ($operational_risk > 80) {
            $this->trigger_risk_alert('high_operational_risk', null, array(
                'operational_risk' => $operational_risk,
                'system_health' => $system_health,
                'api_health' => $api_health,
                'database_health' => $database_health,
                'network_health' => $network_health
            ));
        }
    }
    
    /**
     * Scan systemic risk
     */
    private function scan_systemic_risk() {
        $market_correlation = $this->calculate_market_correlation();
        $volatility_index = $this->calculate_volatility_index();
        $liquidity_index = $this->calculate_liquidity_index();
        $sentiment_index = $this->calculate_sentiment_index();
        
        $systemic_risk = ($market_correlation + $volatility_index + $liquidity_index + $sentiment_index) / 4;
        
        if ($systemic_risk > 70) {
            $this->trigger_risk_alert('high_systemic_risk', null, array(
                'systemic_risk' => $systemic_risk,
                'market_correlation' => $market_correlation,
                'volatility_index' => $volatility_index,
                'liquidity_index' => $liquidity_index,
                'sentiment_index' => $sentiment_index
            ));
        }
    }
    
    /**
     * Monitor positions
     */
    public function monitor_positions() {
        $positions = $this->get_all_positions();
        
        foreach ($positions as $position) {
            $this->check_position_limits($position);
            $this->check_margin_requirements($position);
            $this->check_stop_loss($position);
            $this->check_take_profit($position);
        }
    }
    
    /**
     * Check liquidations
     */
    public function check_liquidations() {
        $positions = $this->get_leveraged_positions();
        
        foreach ($positions as $position) {
            $margin_ratio = $this->calculate_margin_ratio($position);
            
            if ($margin_ratio < $this->risk_config['liquidation_threshold']) {
                $this->liquidate_position($position);
            } elseif ($margin_ratio < $this->risk_config['margin_call_threshold']) {
                $this->send_margin_call($position);
            }
        }
    }
    
    /**
     * Get risk metrics
     */
    public function get_risk_metrics() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        
        $metrics = array(
            'portfolio_risk' => $this->calculate_portfolio_risk($user_id),
            'credit_risk' => $this->calculate_credit_risk($user_id),
            'market_risk' => $this->calculate_market_risk('BTC/USD'),
            'liquidity_risk' => $this->calculate_liquidity_risk('BTC/USD'),
            'operational_risk' => $this->check_system_health(),
            'systemic_risk' => $this->calculate_systemic_risk(),
            'risk_limits' => $this->get_user_risk_limits($user_id),
            'risk_alerts' => $this->get_user_risk_alerts($user_id)
        );
        
        wp_send_json_success($metrics);
    }
    
    /**
     * Set risk limits
     */
    public function set_risk_limits() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $limits = array(
            'max_position_size' => floatval($_POST['max_position_size']),
            'max_daily_loss' => floatval($_POST['max_daily_loss']),
            'max_leverage' => floatval($_POST['max_leverage']),
            'stop_loss_percentage' => floatval($_POST['stop_loss_percentage']),
            'take_profit_percentage' => floatval($_POST['take_profit_percentage'])
        );
        
        update_user_meta($user_id, 'crypto_risk_limits', $limits);
        
        wp_send_json_success('Risk limits updated successfully');
    }
    
    /**
     * Trigger risk alert
     */
    private function trigger_risk_alert($alert_type, $user_id, $data) {
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_risk_alerts',
            array(
                'user_id' => $user_id,
                'alert_type' => $alert_type,
                'alert_data' => json_encode($data),
                'severity' => $this->get_alert_severity($alert_type),
                'status' => 'active',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        // Send notification
        $this->send_risk_notification($user_id, $alert_type, $data);
    }
    
    /**
     * Apply risk controls
     */
    private function apply_risk_controls($user_id, $control_type) {
        switch ($control_type) {
            case 'portfolio_risk_limit':
                $this->limit_position_sizes($user_id);
                break;
            case 'credit_risk_limit':
                $this->suspend_trading($user_id);
                break;
            case 'market_risk_limit':
                $this->halt_trading();
                break;
        }
    }
    
    /**
     * Get current price
     */
    private function get_current_price($currency) {
        $price_data = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT price FROM {$this->wpdb->prefix}crypto_market_data WHERE pair = %s",
                $currency . '/USD'
            )
        );
        
        return $price_data ? floatval($price_data->price) : 0;
    }
    
    /**
     * Get volatility
     */
    private function get_volatility($currency) {
        $price_data = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT change_24h FROM {$this->wpdb->prefix}crypto_market_data WHERE pair = %s",
                $currency . '/USD'
            )
        );
        
        return $price_data ? abs($price_data->change_24h) / 100 : 0;
    }
    
    /**
     * Calculate correlation risk
     */
    private function calculate_correlation_risk($wallets) {
        if (count($wallets) < 2) {
            return 0;
        }
        
        $correlations = array();
        for ($i = 0; $i < count($wallets); $i++) {
            for ($j = $i + 1; $j < count($wallets); $j++) {
                $correlation = $this->calculate_correlation(
                    $wallets[$i]->currency,
                    $wallets[$j]->currency
                );
                $correlations[] = $correlation;
            }
        }
        
        return array_sum($correlations) / count($correlations) * 100;
    }
    
    /**
     * Calculate correlation between two currencies
     */
    private function calculate_correlation($currency1, $currency2) {
        // Simplified correlation calculation
        $price1 = $this->get_current_price($currency1);
        $price2 = $this->get_current_price($currency2);
        
        if ($price1 == 0 || $price2 == 0) {
            return 0;
        }
        
        // Mock correlation based on price movement
        $change1 = $this->get_volatility($currency1);
        $change2 = $this->get_volatility($currency2);
        
        return min(1, ($change1 + $change2) / 2);
    }
    
    /**
     * Get order book depth
     */
    private function get_order_book_depth($pair) {
        $matching_engine = new Crypto_Exchange_Matching_Engine();
        return $matching_engine->get_order_book($pair, 20);
    }
    
    /**
     * Calculate depth
     */
    private function calculate_depth($orders) {
        $depth = 0;
        foreach ($orders as $order) {
            $depth += $order['amount'] * $order['price'];
        }
        return $depth;
    }
    
    /**
     * Calculate spread
     */
    private function calculate_spread($order_book) {
        if (empty($order_book['bids']) || empty($order_book['asks'])) {
            return 1;
        }
        
        $best_bid = $order_book['bids'][0]['price'];
        $best_ask = $order_book['asks'][0]['price'];
        
        return ($best_ask - $best_bid) / $best_bid;
    }
    
    /**
     * Get total balance
     */
    private function get_total_balance($user_id) {
        $wallets = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_wallets WHERE user_id = %d",
                $user_id
            )
        );
        
        $total = 0;
        foreach ($wallets as $wallet) {
            $price = $this->get_current_price($wallet->currency);
            $total += $wallet->balance * $price;
        }
        
        return $total;
    }
    
    /**
     * Get total exposure
     */
    private function get_total_exposure($user_id) {
        $orders = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_orders 
                 WHERE user_id = %d AND status = 'pending'",
                $user_id
            )
        );
        
        $exposure = 0;
        foreach ($orders as $order) {
            $exposure += $order->amount * $order->price;
        }
        
        return $exposure;
    }
    
    /**
     * Get account age
     */
    private function get_account_age($user_id) {
        $user = get_user_by('id', $user_id);
        return (time() - strtotime($user->user_registered)) / 86400; // Days
    }
    
    /**
     * Get trading history score
     */
    private function get_trading_history_score($user_id) {
        $trades = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_trades 
                 WHERE buyer_id = %d OR seller_id = %d",
                $user_id,
                $user_id
            )
        );
        
        return min(100, $trades * 2); // 2 points per trade, max 100
    }
    
    /**
     * Check system health
     */
    private function check_system_health() {
        $memory_usage = memory_get_usage(true) / memory_get_peak_usage(true);
        $cpu_usage = sys_getloadavg()[0];
        $disk_usage = disk_free_space('/') / disk_total_space('/');
        
        return (1 - $memory_usage) * 40 + (1 - $cpu_usage) * 30 + $disk_usage * 30;
    }
    
    /**
     * Check API health
     */
    private function check_api_health() {
        // Check external API responses
        $price_feed = new Crypto_Exchange_Price_Feed();
        $status = $price_feed->get_exchange_status();
        
        $healthy_exchanges = 0;
        foreach ($status as $exchange) {
            if ($exchange['status'] === 'healthy') {
                $healthy_exchanges++;
            }
        }
        
        return ($healthy_exchanges / count($status)) * 100;
    }
    
    /**
     * Check database health
     */
    private function check_database_health() {
        $start_time = microtime(true);
        $this->wpdb->get_var("SELECT 1");
        $query_time = microtime(true) - $start_time;
        
        return max(0, 100 - $query_time * 1000); // Convert to milliseconds
    }
    
    /**
     * Check network health
     */
    private function check_network_health() {
        // Simplified network health check
        return 95; // Mock value
    }
    
    /**
     * Calculate market correlation
     */
    private function calculate_market_correlation() {
        // Simplified market correlation calculation
        return 60; // Mock value
    }
    
    /**
     * Calculate volatility index
     */
    private function calculate_volatility_index() {
        $pairs = $this->wpdb->get_results(
            "SELECT change_24h FROM {$this->wpdb->prefix}crypto_market_data"
        );
        
        $volatilities = array();
        foreach ($pairs as $pair) {
            $volatilities[] = abs($pair->change_24h);
        }
        
        return array_sum($volatilities) / count($volatilities);
    }
    
    /**
     * Calculate liquidity index
     */
    private function calculate_liquidity_index() {
        $pairs = $this->wpdb->get_results(
            "SELECT volume_24h FROM {$this->wpdb->prefix}crypto_market_data"
        );
        
        $volumes = array();
        foreach ($pairs as $pair) {
            $volumes[] = $pair->volume_24h;
        }
        
        $avg_volume = array_sum($volumes) / count($volumes);
        return min(100, $avg_volume / 1000000 * 100); // Normalize to 0-100
    }
    
    /**
     * Calculate sentiment index
     */
    private function calculate_sentiment_index() {
        $charting = new Crypto_Exchange_Charting();
        $sentiment = $charting->get_market_sentiment('BTC/USD');
        
        switch ($sentiment) {
            case 'Bullish':
                return 20;
            case 'Bearish':
                return 80;
            default:
                return 50;
        }
    }
    
    /**
     * Get all positions
     */
    private function get_all_positions() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_orders 
             WHERE status = 'pending' AND order_type = 'limit'"
        );
    }
    
    /**
     * Get leveraged positions
     */
    private function get_leveraged_positions() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_orders 
             WHERE status = 'pending' AND leverage > 1"
        );
    }
    
    /**
     * Calculate margin ratio
     */
    private function calculate_margin_ratio($position) {
        $margin = $position->margin;
        $unrealized_pnl = $this->calculate_unrealized_pnl($position);
        
        return ($margin + $unrealized_pnl) / $margin;
    }
    
    /**
     * Calculate unrealized P&L
     */
    private function calculate_unrealized_pnl($position) {
        $current_price = $this->get_current_price($position->pair);
        $entry_price = $position->price;
        $amount = $position->amount;
        
        if ($position->side === 'buy') {
            return ($current_price - $entry_price) * $amount;
        } else {
            return ($entry_price - $current_price) * $amount;
        }
    }
    
    /**
     * Liquidate position
     */
    private function liquidate_position($position) {
        // Create liquidation order
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_orders',
            array(
                'user_id' => $position->user_id,
                'pair' => $position->pair,
                'side' => $position->side === 'buy' ? 'sell' : 'buy',
                'order_type' => 'market',
                'amount' => $position->amount,
                'price' => $this->get_current_price($position->pair),
                'status' => 'pending',
                'is_liquidation' => true,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%f', '%f', '%s', '%d', '%s')
        );
        
        $this->trigger_risk_alert('position_liquidated', $position->user_id, array(
            'position_id' => $position->id,
            'pair' => $position->pair,
            'amount' => $position->amount
        ));
    }
    
    /**
     * Send margin call
     */
    private function send_margin_call($position) {
        $this->trigger_risk_alert('margin_call', $position->user_id, array(
            'position_id' => $position->id,
            'margin_ratio' => $this->calculate_margin_ratio($position)
        ));
    }
    
    /**
     * Get alert severity
     */
    private function get_alert_severity($alert_type) {
        $severities = array(
            'high_portfolio_risk' => 'high',
            'high_market_risk' => 'medium',
            'high_liquidity_risk' => 'medium',
            'high_credit_risk' => 'high',
            'high_operational_risk' => 'critical',
            'high_systemic_risk' => 'critical',
            'position_liquidated' => 'high',
            'margin_call' => 'medium'
        );
        
        return $severities[$alert_type] ?? 'low';
    }
    
    /**
     * Send risk notification
     */
    private function send_risk_notification($user_id, $alert_type, $data) {
        // Send email notification
        $user = get_user_by('id', $user_id);
        if ($user) {
            $subject = 'Risk Alert: ' . ucfirst(str_replace('_', ' ', $alert_type));
            $message = 'A risk alert has been triggered: ' . json_encode($data);
            wp_mail($user->user_email, $subject, $message);
        }
    }
    
    /**
     * Limit position sizes
     */
    private function limit_position_sizes($user_id) {
        update_user_meta($user_id, 'crypto_position_limit', 0.5); // 50% of normal limit
    }
    
    /**
     * Suspend trading
     */
    private function suspend_trading($user_id) {
        update_user_meta($user_id, 'crypto_trading_suspended', true);
    }
    
    /**
     * Halt trading
     */
    private function halt_trading() {
        update_option('crypto_exchange_trading_halted', true);
    }
    
    /**
     * Get user risk limits
     */
    private function get_user_risk_limits($user_id) {
        return get_user_meta($user_id, 'crypto_risk_limits', true) ?: $this->risk_config;
    }
    
    /**
     * Get user risk alerts
     */
    private function get_user_risk_alerts($user_id) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_risk_alerts 
                 WHERE user_id = %d AND status = 'active' 
                 ORDER BY created_at DESC LIMIT 10",
                $user_id
            )
        );
    }
    
    /**
     * Create risk management tables
     */
    public function create_risk_tables() {
        // Risk alerts table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_risk_alerts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            alert_type varchar(100) NOT NULL,
            alert_data text,
            severity varchar(20) DEFAULT 'medium',
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY alert_type (alert_type),
            KEY status (status)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
    /**
     * Get trading history score
     */
    private function get_trading_history_score($user_id) {
        $trades = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_trades 
                 WHERE buyer_id = %d OR seller_id = %d",
                $user_id,
                $user_id
            )
        );
        
        return min(100, $trades * 2); // 2 points per trade, max 100
    }
    
    /**
     * Check system health
     */
    private function check_system_health() {
        $memory_usage = memory_get_usage(true) / memory_get_peak_usage(true);
        $cpu_usage = sys_getloadavg()[0];
        $disk_usage = disk_free_space('/') / disk_total_space('/');
        
        return (1 - $memory_usage) * 40 + (1 - $cpu_usage) * 30 + $disk_usage * 30;
    }
    
    /**
     * Check API health
     */
    private function check_api_health() {
        // Check external API responses
        $price_feed = new Crypto_Exchange_Price_Feed();
        $status = $price_feed->get_exchange_status();
        
        $healthy_exchanges = 0;
        foreach ($status as $exchange) {
            if ($exchange['status'] === 'healthy') {
                $healthy_exchanges++;
            }
        }
        
        return ($healthy_exchanges / count($status)) * 100;
    }
    
    /**
     * Check database health
     */
    private function check_database_health() {
        $start_time = microtime(true);
        $this->wpdb->get_var("SELECT 1");
        $query_time = microtime(true) - $start_time;
        
        return max(0, 100 - $query_time * 1000); // Convert to milliseconds
    }
    
    /**
     * Check network health
     */
    private function check_network_health() {
        // Simplified network health check
        return 95; // Mock value
    }
    
    /**
     * Calculate market correlation
     */
    private function calculate_market_correlation() {
        // Simplified market correlation calculation
        return 60; // Mock value
    }
    
    /**
     * Calculate volatility index
     */
    private function calculate_volatility_index() {
        $pairs = $this->wpdb->get_results(
            "SELECT change_24h FROM {$this->wpdb->prefix}crypto_market_data"
        );
        
        $volatilities = array();
        foreach ($pairs as $pair) {
            $volatilities[] = abs($pair->change_24h);
        }
        
        return array_sum($volatilities) / count($volatilities);
    }
    
    /**
     * Calculate liquidity index
     */
    private function calculate_liquidity_index() {
        $pairs = $this->wpdb->get_results(
            "SELECT volume_24h FROM {$this->wpdb->prefix}crypto_market_data"
        );
        
        $volumes = array();
        foreach ($pairs as $pair) {
            $volumes[] = $pair->volume_24h;
        }
        
        $avg_volume = array_sum($volumes) / count($volumes);
        return min(100, $avg_volume / 1000000 * 100); // Normalize to 0-100
    }
    
    /**
     * Calculate sentiment index
     */
    private function calculate_sentiment_index() {
        $charting = new Crypto_Exchange_Charting();
        $sentiment = $charting->get_market_sentiment('BTC/USD');
        
        switch ($sentiment) {
            case 'Bullish':
                return 20;
            case 'Bearish':
                return 80;
            default:
                return 50;
        }
    }
    
    /**
     * Get all positions
     */
    private function get_all_positions() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_orders 
             WHERE status = 'pending' AND order_type = 'limit'"
        );
    }
    
    /**
     * Get leveraged positions
     */
    private function get_leveraged_positions() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_orders 
             WHERE status = 'pending' AND leverage > 1"
        );
    }
    
    /**
     * Calculate margin ratio
     */
    private function calculate_margin_ratio($position) {
        $margin = $position->margin;
        $unrealized_pnl = $this->calculate_unrealized_pnl($position);
        
        return ($margin + $unrealized_pnl) / $margin;
    }
    
    /**
     * Calculate unrealized P&L
     */
    private function calculate_unrealized_pnl($position) {
        $current_price = $this->get_current_price($position->pair);
        $entry_price = $position->price;
        $amount = $position->amount;
        
        if ($position->side === 'buy') {
            return ($current_price - $entry_price) * $amount;
        } else {
            return ($entry_price - $current_price) * $amount;
        }
    }
    
    /**
     * Liquidate position
     */
    private function liquidate_position($position) {
        // Create liquidation order
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_orders',
            array(
                'user_id' => $position->user_id,
                'pair' => $position->pair,
                'side' => $position->side === 'buy' ? 'sell' : 'buy',
                'order_type' => 'market',
                'amount' => $position->amount,
                'price' => $this->get_current_price($position->pair),
                'status' => 'pending',
                'is_liquidation' => true,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%f', '%f', '%s', '%d', '%s')
        );
        
        $this->trigger_risk_alert('position_liquidated', $position->user_id, array(
            'position_id' => $position->id,
            'pair' => $position->pair,
            'amount' => $position->amount
        ));
    }
    
    /**
     * Send margin call
     */
    private function send_margin_call($position) {
        $this->trigger_risk_alert('margin_call', $position->user_id, array(
            'position_id' => $position->id,
            'margin_ratio' => $this->calculate_margin_ratio($position)
        ));
    }
    
    /**
     * Get alert severity
     */
    private function get_alert_severity($alert_type) {
        $severities = array(
            'high_portfolio_risk' => 'high',
            'high_market_risk' => 'medium',
            'high_liquidity_risk' => 'medium',
            'high_credit_risk' => 'high',
            'high_operational_risk' => 'critical',
            'high_systemic_risk' => 'critical',
            'position_liquidated' => 'high',
            'margin_call' => 'medium'
        );
        
        return $severities[$alert_type] ?? 'low';
    }
    
    /**
     * Send risk notification
     */
    private function send_risk_notification($user_id, $alert_type, $data) {
        // Send email notification
        $user = get_user_by('id', $user_id);
        if ($user) {
            $subject = 'Risk Alert: ' . ucfirst(str_replace('_', ' ', $alert_type));
            $message = 'A risk alert has been triggered: ' . json_encode($data);
            wp_mail($user->user_email, $subject, $message);
        }
    }
    
    /**
     * Limit position sizes
     */
    private function limit_position_sizes($user_id) {
        update_user_meta($user_id, 'crypto_position_limit', 0.5); // 50% of normal limit
    }
    
    /**
     * Suspend trading
     */
    private function suspend_trading($user_id) {
        update_user_meta($user_id, 'crypto_trading_suspended', true);
    }
    
    /**
     * Halt trading
     */
    private function halt_trading() {
        update_option('crypto_exchange_trading_halted', true);
    }
    
    /**
     * Get user risk limits
     */
    private function get_user_risk_limits($user_id) {
        return get_user_meta($user_id, 'crypto_risk_limits', true) ?: $this->risk_config;
    }
    
    /**
     * Get user risk alerts
     */
    private function get_user_risk_alerts($user_id) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_risk_alerts 
                 WHERE user_id = %d AND status = 'active' 
                 ORDER BY created_at DESC LIMIT 10",
                $user_id
            )
        );
    }
    
    /**
     * Create risk management tables
     */
    public function create_risk_tables() {
        // Risk alerts table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_risk_alerts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            alert_type varchar(100) NOT NULL,
            alert_data text,
            severity varchar(20) DEFAULT 'medium',
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY alert_type (alert_type),
            KEY status (status)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
