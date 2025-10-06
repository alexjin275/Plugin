<?php
/**
 * User Dashboard with Action Buttons
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

$user_actions = new Crypto_Exchange_User_Actions();
?>

<div class="crypto-dashboard-actions">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="welcome-section">
            <h1>Welcome back, <?php echo esc_html(wp_get_current_user()->display_name); ?>!</h1>
            <p>Manage your crypto portfolio and trading activities</p>
        </div>
        <div class="user-stats">
            <div class="stat-item">
                <span class="stat-label">Portfolio Value</span>
                <span class="stat-value">$0.00</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">24h Change</span>
                <span class="stat-value positive">+0.00%</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Total Trades</span>
                <span class="stat-value">0</span>
            </div>
        </div>
    </div>

    <!-- Quick Actions Bar -->
    <div class="quick-actions-bar">
        <div class="action-buttons">
            <button class="action-btn primary deposit-btn" data-action="deposit">
                <i class="icon-deposit">💰</i>
                <span>Deposit</span>
                <small>Add funds</small>
            </button>
            <button class="action-btn primary withdraw-btn" data-action="withdraw">
                <i class="icon-withdraw">💸</i>
                <span>Withdraw</span>
                <small>Cash out</small>
            </button>
            <button class="action-btn primary trade-btn" data-action="trade">
                <i class="icon-trade">📈</i>
                <span>Trade</span>
                <small>Buy/Sell</small>
            </button>
            <button class="action-btn secondary kyc-btn" data-action="kyc_verify">
                <i class="icon-kyc">🆔</i>
                <span>Verify Identity</span>
                <small>KYC Process</small>
            </button>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Portfolio Overview -->
        <div class="dashboard-card portfolio-card">
            <div class="card-header">
                <h3>Portfolio Overview</h3>
                <button class="refresh-btn" data-refresh="portfolio">🔄</button>
            </div>
            <div class="card-content">
                <div class="portfolio-summary">
                    <div class="total-value">
                        <span class="label">Total Value</span>
                        <span class="value">$0.00</span>
                    </div>
                    <div class="change-24h">
                        <span class="label">24h Change</span>
                        <span class="value positive">+$0.00 (+0.00%)</span>
                    </div>
                </div>
                <div class="portfolio-actions">
                    <button class="btn btn-sm btn-primary" data-action="deposit">Add Funds</button>
                    <button class="btn btn-sm btn-secondary" data-action="withdraw">Withdraw</button>
                </div>
            </div>
        </div>

        <!-- Quick Trade -->
        <div class="dashboard-card trade-card">
            <div class="card-header">
                <h3>Quick Trade</h3>
                <button class="refresh-btn" data-refresh="prices">🔄</button>
            </div>
            <div class="card-content">
                <div class="trading-pairs">
                    <div class="pair-item" data-pair="BTC/USDT">
                        <div class="pair-info">
                            <span class="pair-symbol">BTC/USDT</span>
                            <span class="pair-price">$45,000.00</span>
                        </div>
                        <div class="pair-change positive">+2.5%</div>
                    </div>
                    <div class="pair-item" data-pair="ETH/USDT">
                        <div class="pair-info">
                            <span class="pair-symbol">ETH/USDT</span>
                            <span class="pair-price">$3,200.00</span>
                        </div>
                        <div class="pair-change negative">-1.2%</div>
                    </div>
                </div>
                <div class="trade-actions">
                    <button class="btn btn-sm btn-success" data-action="trade" data-side="buy">Buy</button>
                    <button class="btn btn-sm btn-danger" data-action="trade" data-side="sell">Sell</button>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="dashboard-card activity-card">
            <div class="card-header">
                <h3>Recent Activity</h3>
                <button class="view-all-btn" data-view="activity">View All</button>
            </div>
            <div class="card-content">
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon">💰</div>
                        <div class="activity-details">
                            <span class="activity-type">Deposit</span>
                            <span class="activity-amount">+0.5 BTC</span>
                        </div>
                        <div class="activity-time">2 hours ago</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">📈</div>
                        <div class="activity-details">
                            <span class="activity-type">Trade</span>
                            <span class="activity-amount">Buy 0.1 ETH</span>
                        </div>
                        <div class="activity-time">5 hours ago</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wallet Balances -->
        <div class="dashboard-card wallet-card">
            <div class="card-header">
                <h3>Wallet Balances</h3>
                <button class="refresh-btn" data-refresh="balances">🔄</button>
            </div>
            <div class="card-content">
                <div class="balance-list">
                    <div class="balance-item">
                        <div class="coin-info">
                            <span class="coin-symbol">BTC</span>
                            <span class="coin-name">Bitcoin</span>
                        </div>
                        <div class="balance-amount">0.00000000</div>
                        <div class="balance-value">$0.00</div>
                    </div>
                    <div class="balance-item">
                        <div class="coin-info">
                            <span class="coin-symbol">ETH</span>
                            <span class="coin-name">Ethereum</span>
                        </div>
                        <div class="balance-amount">0.00000000</div>
                        <div class="balance-value">$0.00</div>
                    </div>
                </div>
                <div class="wallet-actions">
                    <button class="btn btn-sm btn-primary" data-action="deposit">Deposit</button>
                    <button class="btn btn-sm btn-secondary" data-action="withdraw">Withdraw</button>
                </div>
            </div>
        </div>

        <!-- Open Orders -->
        <div class="dashboard-card orders-card">
            <div class="card-header">
                <h3>Open Orders</h3>
                <button class="view-all-btn" data-view="orders">View All</button>
            </div>
            <div class="card-content">
                <div class="orders-list">
                    <div class="no-orders">
                        <p>No open orders</p>
                        <button class="btn btn-sm btn-primary" data-action="trade">Start Trading</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Market Overview -->
        <div class="dashboard-card market-card">
            <div class="card-header">
                <h3>Market Overview</h3>
                <button class="refresh-btn" data-refresh="market">🔄</button>
            </div>
            <div class="card-content">
                <div class="market-tabs">
                    <button class="tab-btn active" data-tab="top-gainers">Top Gainers</button>
                    <button class="tab-btn" data-tab="top-losers">Top Losers</button>
                    <button class="tab-btn" data-tab="volume">Volume</button>
                </div>
                <div class="market-content">
                    <div class="market-list">
                        <div class="market-item">
                            <span class="coin-symbol">BTC</span>
                            <span class="coin-price">$45,000.00</span>
                            <span class="coin-change positive">+2.5%</span>
                        </div>
                        <div class="market-item">
                            <span class="coin-symbol">ETH</span>
                            <span class="coin-price">$3,200.00</span>
                            <span class="coin-change negative">-1.2%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Panels (Hidden by default) -->
    <div class="action-panels">
        <!-- Include the action interface template -->
        <?php include CRYPTO_EXCHANGE_PLUGIN_DIR . 'templates/user-action-interface.php'; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize dashboard
    const dashboard = new CryptoDashboard();
    dashboard.init();
});

class CryptoDashboard {
    constructor() {
        this.userActions = new CryptoUserActions();
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadDashboardData();
        this.startAutoRefresh();
    }

    bindEvents() {
        // Action button clicks
        jQuery(document).on('click', '.action-btn, [data-action]', (e) => {
            const action = jQuery(e.currentTarget).data('action');
            if (action) {
                this.userActions.openActionPanel(action);
            }
        });

        // Refresh buttons
        jQuery(document).on('click', '.refresh-btn', (e) => {
            const type = jQuery(e.currentTarget).data('refresh');
            this.refreshData(type);
        });

        // View all buttons
        jQuery(document).on('click', '.view-all-btn', (e) => {
            const view = jQuery(e.currentTarget).data('view');
            this.viewAll(view);
        });

        // Market tabs
        jQuery(document).on('click', '.tab-btn', (e) => {
            const tab = jQuery(e.currentTarget).data('tab');
            this.switchMarketTab(tab);
        });

        // Trading pair clicks
        jQuery(document).on('click', '.pair-item', (e) => {
            const pair = jQuery(e.currentTarget).data('pair');
            this.userActions.openActionPanel('trade', { pair: pair });
        });
    }

    loadDashboardData() {
        // Load portfolio data
        this.loadPortfolioData();
        
        // Load market data
        this.loadMarketData();
        
        // Load recent activity
        this.loadRecentActivity();
        
        // Load wallet balances
        this.loadWalletBalances();
        
        // Load open orders
        this.loadOpenOrders();
    }

    loadPortfolioData() {
        jQuery.ajax({
            url: crypto_actions.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_action_data',
                action_type: 'dashboard_stats',
                nonce: crypto_actions.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updatePortfolioDisplay(response.data);
                }
            }
        });
    }

    loadMarketData() {
        jQuery.ajax({
            url: crypto_actions.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_market_data',
                nonce: crypto_actions.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updateMarketDisplay(response.data);
                }
            }
        });
    }

    loadRecentActivity() {
        jQuery.ajax({
            url: crypto_actions.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_action_data',
                action_type: 'recent_activities',
                nonce: crypto_actions.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updateActivityDisplay(response.data);
                }
            }
        });
    }

    loadWalletBalances() {
        jQuery.ajax({
            url: crypto_actions.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_action_data',
                action_type: 'wallet_balances',
                nonce: crypto_actions.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updateWalletDisplay(response.data);
                }
            }
        });
    }

    loadOpenOrders() {
        jQuery.ajax({
            url: crypto_actions.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_user_orders',
                nonce: crypto_actions.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updateOrdersDisplay(response.data);
                }
            }
        });
    }

    updatePortfolioDisplay(data) {
        if (data.total_value) {
            jQuery('.total-value .value').text('$' + data.total_value.toFixed(2));
        }
        if (data.change_24h) {
            const changeElement = jQuery('.change-24h .value');
            changeElement.text('$' + data.change_24h.toFixed(2) + ' (' + data.change_24h_percent.toFixed(2) + '%)');
            changeElement.removeClass('positive negative').addClass(data.change_24h >= 0 ? 'positive' : 'negative');
        }
    }

    updateMarketDisplay(data) {
        // Update market data display
        if (data.pairs) {
            data.pairs.forEach(pair => {
                const pairElement = jQuery(`[data-pair="${pair.symbol}"]`);
                if (pairElement.length) {
                    pairElement.find('.pair-price').text('$' + pair.price.toFixed(2));
                    const changeElement = pairElement.find('.pair-change');
                    changeElement.text((pair.change >= 0 ? '+' : '') + pair.change.toFixed(2) + '%');
                    changeElement.removeClass('positive negative').addClass(pair.change >= 0 ? 'positive' : 'negative');
                }
            });
        }
    }

    updateActivityDisplay(data) {
        // Update recent activity display
        if (data.activities) {
            const activityList = jQuery('.activity-list');
            activityList.empty();
            
            data.activities.forEach(activity => {
                const activityItem = jQuery(`
                    <div class="activity-item">
                        <div class="activity-icon">${activity.icon}</div>
                        <div class="activity-details">
                            <span class="activity-type">${activity.type}</span>
                            <span class="activity-amount">${activity.amount}</span>
                        </div>
                        <div class="activity-time">${activity.time}</div>
                    </div>
                `);
                activityList.append(activityItem);
            });
        }
    }

    updateWalletDisplay(data) {
        // Update wallet balances display
        if (data.balances) {
            const balanceList = jQuery('.balance-list');
            balanceList.empty();
            
            Object.keys(data.balances).forEach(coin => {
                const balance = data.balances[coin];
                const balanceItem = jQuery(`
                    <div class="balance-item">
                        <div class="coin-info">
                            <span class="coin-symbol">${coin}</span>
                            <span class="coin-name">${balance.name || coin}</span>
                        </div>
                        <div class="balance-amount">${balance.amount.toFixed(8)}</div>
                        <div class="balance-value">$${balance.value.toFixed(2)}</div>
                    </div>
                `);
                balanceList.append(balanceItem);
            });
        }
    }

    updateOrdersDisplay(data) {
        // Update open orders display
        if (data.orders && data.orders.length > 0) {
            const ordersList = jQuery('.orders-list');
            ordersList.empty();
            
            data.orders.forEach(order => {
                const orderItem = jQuery(`
                    <div class="order-item">
                        <div class="order-pair">${order.pair}</div>
                        <div class="order-side ${order.side}">${order.side.toUpperCase()}</div>
                        <div class="order-amount">${order.amount}</div>
                        <div class="order-price">$${order.price}</div>
                        <div class="order-status">${order.status}</div>
                    </div>
                `);
                ordersList.append(orderItem);
            });
        }
    }

    refreshData(type) {
        switch (type) {
            case 'portfolio':
                this.loadPortfolioData();
                break;
            case 'prices':
                this.loadMarketData();
                break;
            case 'balances':
                this.loadWalletBalances();
                break;
            case 'market':
                this.loadMarketData();
                break;
        }
    }

    viewAll(view) {
        // Navigate to full view
        switch (view) {
            case 'activity':
                window.location.href = '?page=activity';
                break;
            case 'orders':
                window.location.href = '?page=orders';
                break;
        }
    }

    switchMarketTab(tab) {
        // Switch market tab
        jQuery('.tab-btn').removeClass('active');
        jQuery(`[data-tab="${tab}"]`).addClass('active');
        
        // Load tab data
        this.loadMarketTabData(tab);
    }

    loadMarketTabData(tab) {
        // Load specific market tab data
        jQuery.ajax({
            url: crypto_actions.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_market_data',
                tab: tab,
                nonce: crypto_actions.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updateMarketTabDisplay(tab, response.data);
                }
            }
        });
    }

    updateMarketTabDisplay(tab, data) {
        // Update specific market tab display
        const marketList = jQuery('.market-list');
        marketList.empty();
        
        if (data.coins) {
            data.coins.forEach(coin => {
                const marketItem = jQuery(`
                    <div class="market-item">
                        <span class="coin-symbol">${coin.symbol}</span>
                        <span class="coin-price">$${coin.price.toFixed(2)}</span>
                        <span class="coin-change ${coin.change >= 0 ? 'positive' : 'negative'}">${coin.change >= 0 ? '+' : ''}${coin.change.toFixed(2)}%</span>
                    </div>
                `);
                marketList.append(marketItem);
            });
        }
    }

    startAutoRefresh() {
        // Auto refresh every 30 seconds
        setInterval(() => {
            this.loadDashboardData();
        }, 30000);
    }
}
</script>

<style>
/* Dashboard specific styles */
.crypto-dashboard-actions {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.welcome-section h1 {
    margin: 0 0 10px 0;
    font-size: 28px;
}

.welcome-section p {
    margin: 0;
    opacity: 0.9;
}

.user-stats {
    display: flex;
    gap: 30px;
}

.stat-item {
    text-align: center;
}

.stat-label {
    display: block;
    font-size: 14px;
    opacity: 0.8;
    margin-bottom: 5px;
}

.stat-value {
    display: block;
    font-size: 20px;
    font-weight: 600;
}

.stat-value.positive {
    color: #4caf50;
}

.stat-value.negative {
    color: #f44336;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.dashboard-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    background: #f8f9fa;
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    margin: 0;
    font-size: 18px;
    color: #333;
}

.refresh-btn, .view-all-btn {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    font-size: 14px;
    padding: 5px 10px;
    border-radius: 4px;
    transition: background 0.3s ease;
}

.refresh-btn:hover, .view-all-btn:hover {
    background: #e9ecef;
}

.card-content {
    padding: 20px;
}

.portfolio-summary {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.total-value, .change-24h {
    text-align: center;
}

.total-value .label, .change-24h .label {
    display: block;
    font-size: 14px;
    color: #666;
    margin-bottom: 5px;
}

.total-value .value, .change-24h .value {
    display: block;
    font-size: 24px;
    font-weight: 600;
    color: #333;
}

.change-24h .value.positive {
    color: #4caf50;
}

.change-24h .value.negative {
    color: #f44336;
}

.portfolio-actions, .trade-actions, .wallet-actions {
    display: flex;
    gap: 10px;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 14px;
}

.btn-success {
    background: #4caf50;
    color: white;
}

.btn-danger {
    background: #f44336;
    color: white;
}

.trading-pairs {
    margin-bottom: 20px;
}

.pair-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.3s ease;
}

.pair-item:hover {
    background: #f8f9fa;
}

.pair-item:last-child {
    border-bottom: none;
}

.pair-info {
    display: flex;
    flex-direction: column;
}

.pair-symbol {
    font-weight: 600;
    color: #333;
}

.pair-price {
    font-size: 14px;
    color: #666;
}

.pair-change {
    font-weight: 600;
    font-size: 14px;
}

.pair-change.positive {
    color: #4caf50;
}

.pair-change.negative {
    color: #f44336;
}

.activity-list {
    max-height: 200px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    font-size: 20px;
    margin-right: 15px;
}

.activity-details {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.activity-type {
    font-weight: 600;
    color: #333;
}

.activity-amount {
    font-size: 14px;
    color: #666;
}

.activity-time {
    font-size: 12px;
    color: #999;
}

.balance-list {
    margin-bottom: 20px;
}

.balance-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.balance-item:last-child {
    border-bottom: none;
}

.coin-info {
    display: flex;
    flex-direction: column;
}

.coin-symbol {
    font-weight: 600;
    color: #333;
}

.coin-name {
    font-size: 12px;
    color: #666;
}

.balance-amount {
    font-weight: 600;
    color: #333;
}

.balance-value {
    font-size: 14px;
    color: #666;
}

.no-orders {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.market-tabs {
    display: flex;
    margin-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.tab-btn {
    background: none;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    color: #666;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}

.tab-btn.active {
    color: #4facfe;
    border-bottom-color: #4facfe;
}

.market-list {
    max-height: 200px;
    overflow-y: auto;
}

.market-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.market-item:last-child {
    border-bottom: none;
}

.coin-symbol {
    font-weight: 600;
    color: #333;
}

.coin-price {
    color: #333;
}

.coin-change {
    font-weight: 600;
}

.coin-change.positive {
    color: #4caf50;
}

.coin-change.negative {
    color: #f44336;
}

/* Responsive Design */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .user-stats {
        justify-content: center;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .portfolio-summary {
        flex-direction: column;
        gap: 20px;
    }
    
    .portfolio-actions, .trade-actions, .wallet-actions {
        flex-direction: column;
    }
}
</style>
