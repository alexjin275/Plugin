<?php
/**
 * Shortcodes for Crypto Exchange Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Shortcodes {
    
    public function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
    }
    
    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('crypto_dashboard', array($this, 'dashboard_shortcode'));
        add_shortcode('crypto_trading', array($this, 'trading_shortcode'));
        add_shortcode('crypto_wallet', array($this, 'wallet_shortcode'));
        add_shortcode('crypto_market_data', array($this, 'market_data_shortcode'));
        add_shortcode('crypto_price_ticker', array($this, 'price_ticker_shortcode'));
        add_shortcode('crypto_chart', array($this, 'chart_shortcode'));
    }
    
    /**
     * Dashboard shortcode
     */
    public function dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . wp_login_url() . '">login</a> to access the dashboard.</p>';
        }
        
        ob_start();
        include CRYPTO_EXCHANGE_PLUGIN_DIR . 'templates/user-dashboard.php';
        return ob_get_clean();
    }
    
    /**
     * Trading shortcode
     */
    public function trading_shortcode($atts) {
        $atts = shortcode_atts(array(
            'pair' => 'BTC/USD',
            'height' => '500px'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . wp_login_url() . '">login</a> to access trading.</p>';
        }
        
        ob_start();
        ?>
        <div class="crypto-trading-widget" style="height: <?php echo esc_attr($atts['height']); ?>;">
            <div class="trading-header">
                <h3>Trading Interface</h3>
                <div class="trading-pair-selector">
                    <select id="trading-pair-select">
                        <option value="BTC/USD">BTC/USD</option>
                        <option value="ETH/USD">ETH/USD</option>
                        <option value="LTC/USD">LTC/USD</option>
                    </select>
                </div>
            </div>
            <div class="trading-content">
                <div class="order-form">
                    <form id="trading-form">
                        <div class="form-group">
                            <label>Order Type</label>
                            <select name="order_type">
                                <option value="market">Market</option>
                                <option value="limit">Limit</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Side</label>
                            <select name="side">
                                <option value="buy">Buy</option>
                                <option value="sell">Sell</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="number" name="amount" step="0.00000001" required>
                        </div>
                        <div class="form-group">
                            <label>Price</label>
                            <input type="number" name="price" step="0.01">
                        </div>
                        <button type="submit" class="btn btn-primary">Place Order</button>
                    </form>
                </div>
                <div class="order-book">
                    <h4>Order Book</h4>
                    <div id="order-book-content"></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Wallet shortcode
     */
    public function wallet_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . wp_login_url() . '">login</a> to access your wallet.</p>';
        }
        
        $user_id = get_current_user_id();
        $wallet = new Crypto_Exchange_Wallet();
        $user_wallets = $wallet->get_user_wallets($user_id);
        
        ob_start();
        ?>
        <div class="crypto-wallet-widget">
            <h3>My Wallets</h3>
            <div class="wallets-grid">
                <?php foreach ($user_wallets as $wallet_data): ?>
                    <div class="wallet-card">
                        <div class="wallet-header">
                            <h4><?php echo esc_html($wallet_data['currency']); ?></h4>
                            <span class="wallet-type"><?php echo esc_html($wallet_data['type']); ?></span>
                        </div>
                        <div class="wallet-balance">
                            <span class="balance"><?php echo number_format($wallet_data['balance'], 8); ?></span>
                            <span class="currency"><?php echo esc_html($wallet_data['currency']); ?></span>
                        </div>
                        <div class="wallet-actions">
                            <button class="deposit-btn" data-currency="<?php echo esc_attr($wallet_data['currency']); ?>">Deposit</button>
                            <button class="withdraw-btn" data-currency="<?php echo esc_attr($wallet_data['currency']); ?>">Withdraw</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Market data shortcode
     */
    public function market_data_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => '10',
            'columns' => '3'
        ), $atts);
        
        $market_data = new Crypto_Exchange_Market_Data();
        $prices = $market_data->get_all_prices();
        
        // Limit results
        $prices = array_slice($prices, 0, intval($atts['limit']));
        
        ob_start();
        ?>
        <div class="crypto-market-data-widget" style="grid-template-columns: repeat(<?php echo esc_attr($atts['columns']); ?>, 1fr);">
            <?php foreach ($prices as $pair => $data): ?>
                <div class="price-card">
                    <div class="pair-name"><?php echo esc_html($pair); ?></div>
                    <div class="price">$<?php echo number_format($data['price'], 2); ?></div>
                    <div class="change <?php echo $data['change_24h'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo ($data['change_24h'] >= 0 ? '+' : '') . number_format($data['change_24h'], 2); ?>%
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Price ticker shortcode
     */
    public function price_ticker_shortcode($atts) {
        $atts = shortcode_atts(array(
            'pairs' => 'BTC/USD,ETH/USD,LTC/USD',
            'speed' => '50'
        ), $atts);
        
        $pairs = explode(',', $atts['pairs']);
        
        ob_start();
        ?>
        <div class="crypto-price-ticker" style="animation-duration: <?php echo esc_attr($atts['speed']); ?>s;">
            <div class="ticker-content">
                <?php foreach ($pairs as $pair): ?>
                    <div class="ticker-item">
                        <span class="pair"><?php echo esc_html(trim($pair)); ?></span>
                        <span class="price" data-pair="<?php echo esc_attr(trim($pair)); ?>">Loading...</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
        .crypto-price-ticker {
            overflow: hidden;
            white-space: nowrap;
            background: #f8f9fa;
            padding: 10px 0;
            border-radius: 4px;
        }
        
        .ticker-content {
            display: inline-block;
            animation: ticker <?php echo esc_attr($atts['speed']); ?>s linear infinite;
        }
        
        .ticker-item {
            display: inline-block;
            margin-right: 30px;
            padding: 5px 10px;
            background: white;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .ticker-item .pair {
            font-weight: bold;
            margin-right: 10px;
        }
        
        @keyframes ticker {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Load price data for ticker
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'crypto_exchange_get_market_data'
                },
                success: function(response) {
                    if (response.success) {
                        response.data.forEach(function(pair) {
                            $('.ticker-item .price[data-pair="' + pair.symbol + '"]').text('$' + pair.price.toFixed(2));
                        });
                    }
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Chart shortcode
     */
    public function chart_shortcode($atts) {
        $atts = shortcode_atts(array(
            'pair' => 'BTC/USD',
            'interval' => '1',
            'height' => '400px',
            'theme' => 'dark'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . wp_login_url() . '">login</a> to view charts.</p>';
        }
        
        $charting = new Crypto_Exchange_Charting();
        
        ob_start();
        ?>
        <div class="crypto-chart-widget" style="height: <?php echo esc_attr($atts['height']); ?>;">
            <div class="chart-header">
                <h4><?php echo esc_html($atts['pair']); ?> Chart</h4>
                <div class="chart-controls">
                    <select id="chart-interval">
                        <option value="1" <?php selected($atts['interval'], '1'); ?>>1m</option>
                        <option value="5" <?php selected($atts['interval'], '5'); ?>>5m</option>
                        <option value="15" <?php selected($atts['interval'], '15'); ?>>15m</option>
                        <option value="60" <?php selected($atts['interval'], '60'); ?>>1h</option>
                        <option value="240" <?php selected($atts['interval'], '240'); ?>>4h</option>
                        <option value="1440" <?php selected($atts['interval'], '1440'); ?>>1d</option>
                    </select>
                </div>
            </div>
            <div class="chart-container">
                <?php echo $charting->render_chart($atts['pair'], $atts['interval'], $atts['theme']); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
