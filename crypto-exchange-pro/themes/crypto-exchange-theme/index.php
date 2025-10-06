<?php
/**
 * The main template file
 */

get_header(); ?>

<main id="main" class="site-main">
    <!-- Hero Section -->
    <section class="hero-section fade-in">
        <div class="hero-content">
            <h1>Trade Cryptocurrencies with Confidence</h1>
            <p>Advanced trading platform with real-time data, secure wallets, and professional-grade tools</p>
            <div class="hero-buttons">
                <a href="<?php echo wp_login_url(); ?>" class="btn btn-primary">Start Trading</a>
                <a href="#features" class="btn btn-secondary">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="text-center mb-5">
            <h2>Why Choose Our Exchange?</h2>
            <p>Professional-grade cryptocurrency trading platform with advanced features</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card slide-in-left">
                <div class="feature-icon">🔒</div>
                <h3>Bank-Level Security</h3>
                <p>Advanced encryption, multi-factor authentication, and cold storage for maximum security</p>
            </div>
            
            <div class="feature-card slide-in-left">
                <div class="feature-icon">⚡</div>
                <h3>Lightning Fast Trading</h3>
                <p>High-performance matching engine with sub-millisecond order execution</p>
            </div>
            
            <div class="feature-card slide-in-left">
                <div class="feature-icon">📊</div>
                <h3>Advanced Analytics</h3>
                <p>Real-time charts, technical indicators, and market analysis tools</p>
            </div>
            
            <div class="feature-card slide-in-right">
                <div class="feature-icon">💰</div>
                <h3>Low Trading Fees</h3>
                <p>Competitive trading fees starting from 0.1% with volume discounts</p>
            </div>
            
            <div class="feature-card slide-in-right">
                <div class="feature-icon">🌍</div>
                <h3>Global Access</h3>
                <p>Trade 24/7 with support for multiple fiat currencies and payment methods</p>
            </div>
            
            <div class="feature-card slide-in-right">
                <div class="feature-icon">📱</div>
                <h3>Mobile Trading</h3>
                <p>Full-featured mobile app for trading on the go</p>
            </div>
        </div>
    </section>

    <!-- Market Data Section -->
    <section class="market-data-section">
        <h2>Live Market Prices</h2>
        <div class="crypto-prices" id="crypto-prices">
            <!-- Prices will be loaded via JavaScript -->
            <div class="price-card loading">
                <div class="crypto-symbol">Loading...</div>
                <div class="crypto-price">-</div>
                <div class="crypto-change">-</div>
            </div>
        </div>
    </section>

    <!-- Trading Interface Preview -->
    <section class="trading-interface">
        <div class="trading-header">
            <h2>Professional Trading Interface</h2>
        </div>
        <div class="trading-content">
            <div class="order-panel">
                <div class="order-form">
                    <h3>Quick Trade</h3>
                    <div class="form-group">
                        <label for="preview-pair">Trading Pair</label>
                        <select id="preview-pair">
                            <option value="BTC/USD">BTC/USD</option>
                            <option value="ETH/USD">ETH/USD</option>
                            <option value="LTC/USD">LTC/USD</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="preview-amount">Amount</label>
                        <input type="number" id="preview-amount" placeholder="0.00">
                    </div>
                    <div class="order-buttons">
                        <button class="btn-buy">Buy</button>
                        <button class="btn-sell">Sell</button>
                    </div>
                </div>
            </div>
            <div class="chart-panel">
                <div class="chart-container">
                    <div class="text-center">
                        <h3>Live Price Chart</h3>
                        <p>Real-time trading charts with technical indicators</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="text-center mb-5">
        <h2>Ready to Start Trading?</h2>
        <p>Join thousands of traders using our advanced platform</p>
        <div class="hero-buttons">
            <a href="<?php echo wp_registration_url(); ?>" class="btn btn-primary">Create Account</a>
            <a href="<?php echo wp_login_url(); ?>" class="btn btn-secondary">Sign In</a>
        </div>
    </section>
</main>

<script>
jQuery(document).ready(function($) {
    // Load market data
    loadMarketData();
    
    // Update market data every 30 seconds
    setInterval(loadMarketData, 30000);
});

function loadMarketData() {
    jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'crypto_exchange_get_market_data'
        },
        success: function(response) {
            if (response.success) {
                updatePriceDisplay(response.data);
            }
        },
        error: function() {
            // Show demo data if API fails
            showDemoData();
        }
    });
}

function updatePriceDisplay(data) {
    var pricesHtml = '';
    
    data.forEach(function(pair) {
        var changeClass = pair.change_24h >= 0 ? 'positive' : 'negative';
        var changeSymbol = pair.change_24h >= 0 ? '+' : '';
        
        pricesHtml += '<div class="price-card">';
        pricesHtml += '<div class="crypto-symbol">' + pair.symbol + '</div>';
        pricesHtml += '<div class="crypto-price">$' + pair.price.toFixed(2) + '</div>';
        pricesHtml += '<div class="crypto-change ' + changeClass + '">' + changeSymbol + pair.change_24h.toFixed(2) + '%</div>';
        pricesHtml += '</div>';
    });
    
    $('#crypto-prices').html(pricesHtml);
}

function showDemoData() {
    var demoData = [
        { symbol: 'BTC/USD', price: 45000.00, change_24h: 2.5 },
        { symbol: 'ETH/USD', price: 3200.00, change_24h: -1.2 },
        { symbol: 'LTC/USD', price: 150.00, change_24h: 0.8 },
        { symbol: 'BNB/USD', price: 350.00, change_24h: 3.1 },
        { symbol: 'ADA/USD', price: 0.45, change_24h: -0.5 },
        { symbol: 'DOT/USD', price: 25.00, change_24h: 1.8 }
    ];
    
    updatePriceDisplay(demoData);
}
</script>

<?php get_footer(); ?>
