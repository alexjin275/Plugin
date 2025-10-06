<?php
/**
 * Exchange Homepage Template
 * This template showcases the exchange like major platforms
 */

get_header(); ?>

<div class="crypto-exchange-homepage">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-background">
            <div class="hero-particles"></div>
            <div class="hero-gradient"></div>
        </div>
        <div class="hero-content">
            <div class="container">
                <div class="hero-text">
                    <h1 class="hero-title">
                        Trade <span class="highlight">Cryptocurrency</span><br>
                        Like a <span class="highlight">Professional</span>
                    </h1>
                    <p class="hero-subtitle">
                        Join millions of traders on the world's most advanced cryptocurrency exchange platform. 
                        Buy, sell, and trade digital assets with institutional-grade security and lightning-fast execution.
                    </p>
                    <div class="hero-actions">
                        <a href="<?php echo home_url('/trading'); ?>" class="btn btn-primary btn-large">
                            <span class="btn-icon">📈</span>
                            Start Trading
                        </a>
                        <a href="<?php echo home_url('/dashboard'); ?>" class="btn btn-secondary btn-large">
                            <span class="btn-icon">👤</span>
                            Open Account
                        </a>
                    </div>
                    <div class="hero-stats">
                        <div class="stat-item">
                            <span class="stat-number">$2.5B+</span>
                            <span class="stat-label">24h Volume</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">500+</span>
                            <span class="stat-label">Trading Pairs</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">10M+</span>
                            <span class="stat-label">Users</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">99.9%</span>
                            <span class="stat-label">Uptime</span>
                        </div>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="trading-preview">
                        <div class="preview-header">
                            <div class="preview-tabs">
                                <button class="tab active">Spot</button>
                                <button class="tab">Futures</button>
                                <button class="tab">Options</button>
                            </div>
                            <div class="preview-pair">BTC/USDT</div>
                        </div>
                        <div class="preview-chart">
                            <div class="chart-container">
                                <canvas id="hero-chart"></canvas>
                            </div>
                        </div>
                        <div class="preview-orderbook">
                            <div class="orderbook-side">
                                <div class="orderbook-header">Sell Orders</div>
                                <div class="orderbook-rows">
                                    <div class="orderbook-row">
                                        <span class="price">$45,250.00</span>
                                        <span class="amount">0.125</span>
                                        <span class="total">$5,656.25</span>
                                    </div>
                                    <div class="orderbook-row">
                                        <span class="price">$45,200.00</span>
                                        <span class="amount">0.250</span>
                                        <span class="total">$11,300.00</span>
                                    </div>
                                    <div class="orderbook-row">
                                        <span class="price">$45,150.00</span>
                                        <span class="amount">0.500</span>
                                        <span class="total">$22,575.00</span>
                                    </div>
                                </div>
                            </div>
                            <div class="orderbook-spread">
                                <span class="spread-label">Spread</span>
                                <span class="spread-value">$12.50 (0.03%)</span>
                            </div>
                            <div class="orderbook-side">
                                <div class="orderbook-header">Buy Orders</div>
                                <div class="orderbook-rows">
                                    <div class="orderbook-row">
                                        <span class="price">$45,100.00</span>
                                        <span class="amount">0.750</span>
                                        <span class="total">$33,825.00</span>
                                    </div>
                                    <div class="orderbook-row">
                                        <span class="price">$45,050.00</span>
                                        <span class="amount">1.000</span>
                                        <span class="total">$45,050.00</span>
                                    </div>
                                    <div class="orderbook-row">
                                        <span class="price">$45,000.00</span>
                                        <span class="amount">2.500</span>
                                        <span class="total">$112,500.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Market Overview Section -->
    <section class="market-overview-section">
        <div class="container">
            <div class="section-header">
                <h2>Live Market Data</h2>
                <p>Real-time cryptocurrency prices and market information</p>
            </div>
            <div class="market-tabs">
                <button class="tab-btn active" data-tab="spot">Spot Markets</button>
                <button class="tab-btn" data-tab="futures">Futures</button>
                <button class="tab-btn" data-tab="new">New Listings</button>
            </div>
            <div class="market-table-container">
                <table class="market-table">
                    <thead>
                        <tr>
                            <th>Pair</th>
                            <th>Price</th>
                            <th>24h Change</th>
                            <th>24h Volume</th>
                            <th>Market Cap</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="market-data">
                        <!-- Market data will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="section-header">
                <h2>Why Choose Our Exchange?</h2>
                <p>Advanced features and tools for professional traders</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="icon">⚡</span>
                    </div>
                    <h3>Lightning Fast</h3>
                    <p>Execute trades in milliseconds with our high-performance matching engine and global CDN infrastructure.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="icon">🔒</span>
                    </div>
                    <h3>Bank-Grade Security</h3>
                    <p>Your funds are protected with multi-layer security, cold storage, and insurance coverage up to $250M.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="icon">📊</span>
                    </div>
                    <h3>Advanced Charts</h3>
                    <p>Professional trading tools with 100+ technical indicators, drawing tools, and real-time market data.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="icon">💰</span>
                    </div>
                    <h3>Low Fees</h3>
                    <p>Competitive trading fees starting from 0.1% with volume discounts and maker rebates up to 0.02%.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="icon">🌍</span>
                    </div>
                    <h3>Global Access</h3>
                    <p>Trade 24/7 from anywhere in the world with support for 25+ languages and 100+ payment methods.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="icon">📱</span>
                    </div>
                    <h3>Mobile Trading</h3>
                    <p>Full-featured mobile apps for iOS and Android with push notifications and biometric authentication.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Trading Tools Section -->
    <section class="trading-tools-section">
        <div class="container">
            <div class="section-header">
                <h2>Professional Trading Tools</h2>
                <p>Everything you need to succeed in cryptocurrency trading</p>
            </div>
            <div class="tools-grid">
                <div class="tool-card">
                    <div class="tool-preview">
                        <div class="preview-chart">
                            <canvas id="tool-chart-1"></canvas>
                        </div>
                    </div>
                    <div class="tool-content">
                        <h3>Advanced Charting</h3>
                        <p>Professional-grade charts with 100+ technical indicators, drawing tools, and customizable layouts.</p>
                        <ul class="tool-features">
                            <li>Real-time data feeds</li>
                            <li>Multiple timeframes</li>
                            <li>Custom indicators</li>
                            <li>Drawing tools</li>
                        </ul>
                    </div>
                </div>
                <div class="tool-card">
                    <div class="tool-preview">
                        <div class="preview-interface">
                            <div class="interface-header">Trading Interface</div>
                            <div class="interface-content">
                                <div class="order-form">
                                    <div class="form-row">
                                        <label>Order Type</label>
                                        <select>
                                            <option>Market</option>
                                            <option>Limit</option>
                                            <option>Stop</option>
                                        </select>
                                    </div>
                                    <div class="form-row">
                                        <label>Amount</label>
                                        <input type="number" placeholder="0.00000000">
                                    </div>
                                    <button class="btn btn-primary">Place Order</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tool-content">
                        <h3>Smart Order Types</h3>
                        <p>Advanced order types including stop-loss, take-profit, and algorithmic trading strategies.</p>
                        <ul class="tool-features">
                            <li>Market orders</li>
                            <li>Limit orders</li>
                            <li>Stop orders</li>
                            <li>Algorithmic trading</li>
                        </ul>
                    </div>
                </div>
                <div class="tool-card">
                    <div class="tool-preview">
                        <div class="preview-portfolio">
                            <div class="portfolio-header">Portfolio</div>
                            <div class="portfolio-content">
                                <div class="balance-item">
                                    <span class="coin">BTC</span>
                                    <span class="amount">1.25000000</span>
                                    <span class="value">$56,250.00</span>
                                </div>
                                <div class="balance-item">
                                    <span class="coin">ETH</span>
                                    <span class="amount">10.50000000</span>
                                    <span class="value">$33,600.00</span>
                                </div>
                                <div class="balance-item">
                                    <span class="coin">USDT</span>
                                    <span class="amount">5,000.00000000</span>
                                    <span class="value">$5,000.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tool-content">
                        <h3>Portfolio Management</h3>
                        <p>Track your portfolio performance with real-time P&L, asset allocation, and risk metrics.</p>
                        <ul class="tool-features">
                            <li>Real-time P&L</li>
                            <li>Asset allocation</li>
                            <li>Risk metrics</li>
                            <li>Performance tracking</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Security Section -->
    <section class="security-section">
        <div class="container">
            <div class="security-content">
                <div class="security-text">
                    <h2>Your Security is Our Priority</h2>
                    <p>We employ industry-leading security measures to protect your funds and personal information.</p>
                    <div class="security-features">
                        <div class="security-item">
                            <span class="security-icon">🔐</span>
                            <div class="security-details">
                                <h4>Cold Storage</h4>
                                <p>95% of funds stored in offline cold wallets</p>
                            </div>
                        </div>
                        <div class="security-item">
                            <span class="security-icon">🛡️</span>
                            <div class="security-details">
                                <h4>Insurance Coverage</h4>
                                <p>Up to $250M in insurance protection</p>
                            </div>
                        </div>
                        <div class="security-item">
                            <span class="security-icon">🔍</span>
                            <div class="security-details">
                                <h4>Regular Audits</h4>
                                <p>Third-party security audits and penetration testing</p>
                            </div>
                        </div>
                        <div class="security-item">
                            <span class="security-icon">🔑</span>
                            <div class="security-details">
                                <h4>2FA Authentication</h4>
                                <p>Multi-factor authentication for all accounts</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="security-visual">
                    <div class="security-shield">
                        <div class="shield-icon">🛡️</div>
                        <div class="shield-text">Bank-Grade Security</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Start Trading?</h2>
                <p>Join millions of traders and start your cryptocurrency journey today</p>
                <div class="cta-actions">
                    <a href="<?php echo home_url('/dashboard'); ?>" class="btn btn-primary btn-large">
                        <span class="btn-icon">🚀</span>
                        Get Started Now
                    </a>
                    <a href="<?php echo home_url('/trading'); ?>" class="btn btn-secondary btn-large">
                        <span class="btn-icon">👀</span>
                        View Live Markets
                    </a>
                </div>
                <div class="cta-features">
                    <div class="cta-feature">
                        <span class="feature-icon">✅</span>
                        <span>No setup fees</span>
                    </div>
                    <div class="cta-feature">
                        <span class="feature-icon">✅</span>
                        <span>Instant verification</span>
                    </div>
                    <div class="cta-feature">
                        <span class="feature-icon">✅</span>
                        <span>24/7 support</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize homepage
    const homepage = new CryptoExchangeHomepage();
    homepage.init();
});

class CryptoExchangeHomepage {
    constructor() {
        this.charts = {};
        this.marketData = [];
        this.init();
    }

    init() {
        this.loadMarketData();
        this.initializeCharts();
        this.bindEvents();
        this.startRealTimeUpdates();
    }

    loadMarketData() {
        $.ajax({
            url: crypto_theme.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_market_data',
                nonce: crypto_theme.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.marketData = response.data.pairs || [];
                    this.updateMarketTable();
                }
            }
        });
    }

    updateMarketTable() {
        const tbody = $('#market-data');
        tbody.empty();

        this.marketData.forEach(pair => {
            const row = $(`
                <tr>
                    <td class="pair-cell">
                        <div class="pair-info">
                            <span class="pair-symbol">${pair.symbol}</span>
                            <span class="pair-name">${pair.name}</span>
                        </div>
                    </td>
                    <td class="price-cell">
                        <span class="price">$${pair.price.toFixed(2)}</span>
                    </td>
                    <td class="change-cell">
                        <span class="change ${pair.change >= 0 ? 'positive' : 'negative'}">
                            ${pair.change >= 0 ? '+' : ''}${pair.change.toFixed(2)}%
                        </span>
                    </td>
                    <td class="volume-cell">
                        <span class="volume">$${this.formatNumber(pair.volume)}</span>
                    </td>
                    <td class="marketcap-cell">
                        <span class="marketcap">$${this.formatNumber(pair.market_cap)}</span>
                    </td>
                    <td class="action-cell">
                        <button class="btn btn-sm btn-primary" onclick="window.location.href='${crypto_theme.theme_url}../trading?pair=${pair.symbol}'">
                            Trade
                        </button>
                    </td>
                </tr>
            `);
            tbody.append(row);
        });
    }

    initializeCharts() {
        // Hero chart
        const heroCtx = document.getElementById('hero-chart');
        if (heroCtx) {
            this.charts.hero = new Chart(heroCtx, {
                type: 'line',
                data: {
                    labels: this.generateTimeLabels(24),
                    datasets: [{
                        label: 'BTC/USDT',
                        data: this.generatePriceData(24),
                        borderColor: '#4facfe',
                        backgroundColor: 'rgba(79, 172, 254, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            display: false
                        },
                        y: {
                            display: false
                        }
                    },
                    elements: {
                        point: {
                            radius: 0
                        }
                    }
                }
            });
        }

        // Tool charts
        const toolCtx1 = document.getElementById('tool-chart-1');
        if (toolCtx1) {
            this.charts.tool1 = new Chart(toolCtx1, {
                type: 'candlestick',
                data: {
                    labels: this.generateTimeLabels(50),
                    datasets: [{
                        label: 'BTC/USDT',
                        data: this.generateCandlestickData(50),
                        borderColor: '#4facfe',
                        backgroundColor: 'rgba(79, 172, 254, 0.1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            display: false
                        },
                        y: {
                            display: false
                        }
                    }
                }
            });
        }
    }

    generateTimeLabels(count) {
        const labels = [];
        const now = new Date();
        for (let i = count - 1; i >= 0; i--) {
            const time = new Date(now.getTime() - i * 60 * 60 * 1000);
            labels.push(time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }));
        }
        return labels;
    }

    generatePriceData(count) {
        const data = [];
        let price = 45000;
        for (let i = 0; i < count; i++) {
            price += (Math.random() - 0.5) * 1000;
            data.push(price);
        }
        return data;
    }

    generateCandlestickData(count) {
        const data = [];
        let price = 45000;
        for (let i = 0; i < count; i++) {
            const open = price;
            const close = price + (Math.random() - 0.5) * 1000;
            const high = Math.max(open, close) + Math.random() * 500;
            const low = Math.min(open, close) - Math.random() * 500;
            data.push({ open, high, low, close });
            price = close;
        }
        return data;
    }

    formatNumber(num) {
        if (num >= 1e9) return (num / 1e9).toFixed(1) + 'B';
        if (num >= 1e6) return (num / 1e6).toFixed(1) + 'M';
        if (num >= 1e3) return (num / 1e3).toFixed(1) + 'K';
        return num.toFixed(0);
    }

    bindEvents() {
        // Market tabs
        $('.tab-btn').click(function() {
            $('.tab-btn').removeClass('active');
            $(this).addClass('active');
            // Load different market data based on tab
        });

        // Preview tabs
        $('.preview-tabs .tab').click(function() {
            $('.preview-tabs .tab').removeClass('active');
            $(this).addClass('active');
        });
    }

    startRealTimeUpdates() {
        // Update market data every 5 seconds
        setInterval(() => {
            this.loadMarketData();
        }, 5000);

        // Update charts every 10 seconds
        setInterval(() => {
            this.updateCharts();
        }, 10000);
    }

    updateCharts() {
        if (this.charts.hero) {
            const newData = this.generatePriceData(24);
            this.charts.hero.data.datasets[0].data = newData;
            this.charts.hero.update('none');
        }
    }
}
</script>

<?php get_footer(); ?>
