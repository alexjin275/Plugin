<?php
/**
 * Trading Interface Template
 * Professional trading platform interface
 */

get_header(); ?>

<div class="crypto-trading-interface">
    <!-- Trading Header -->
    <div class="trading-header">
        <div class="container">
            <div class="trading-nav">
                <div class="trading-tabs">
                    <button class="tab-btn active" data-tab="spot">Spot</button>
                    <button class="tab-btn" data-tab="futures">Futures</button>
                    <button class="tab-btn" data-tab="options">Options</button>
                    <button class="tab-btn" data-tab="margin">Margin</button>
                </div>
                <div class="trading-pair-selector">
                    <select id="trading-pair-select">
                        <option value="BTC/USDT">BTC/USDT</option>
                        <option value="ETH/USDT">ETH/USDT</option>
                        <option value="BNB/USDT">BNB/USDT</option>
                        <option value="ADA/USDT">ADA/USDT</option>
                    </select>
                </div>
                <div class="trading-actions">
                    <button class="btn btn-sm btn-primary" id="deposit-btn">Deposit</button>
                    <button class="btn btn-sm btn-secondary" id="withdraw-btn">Withdraw</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Trading Layout -->
    <div class="trading-layout">
        <div class="container">
            <div class="trading-grid">
                <!-- Left Sidebar - Market Data -->
                <div class="trading-sidebar-left">
                    <div class="market-data-panel">
                        <div class="panel-header">
                            <h3>Markets</h3>
                            <div class="panel-actions">
                                <button class="btn-icon" id="refresh-markets">🔄</button>
                                <button class="btn-icon" id="favorite-markets">⭐</button>
                            </div>
                        </div>
                        <div class="market-search">
                            <input type="text" placeholder="Search markets..." id="market-search">
                        </div>
                        <div class="market-tabs">
                            <button class="tab-btn active" data-market="spot">Spot</button>
                            <button class="tab-btn" data-market="futures">Futures</button>
                        </div>
                        <div class="market-list" id="market-list">
                            <!-- Market data will be loaded here -->
                        </div>
                    </div>
                </div>

                <!-- Center - Charts and Order Book -->
                <div class="trading-center">
                    <!-- Chart Section -->
                    <div class="chart-section">
                        <div class="chart-header">
                            <div class="chart-pair-info">
                                <h2 id="current-pair">BTC/USDT</h2>
                                <div class="pair-price">
                                    <span class="price" id="current-price">$45,000.00</span>
                                    <span class="change positive" id="price-change">+2.5%</span>
                                </div>
                            </div>
                            <div class="chart-controls">
                                <div class="timeframe-selector">
                                    <button class="timeframe-btn active" data-timeframe="1m">1m</button>
                                    <button class="timeframe-btn" data-timeframe="5m">5m</button>
                                    <button class="timeframe-btn" data-timeframe="15m">15m</button>
                                    <button class="timeframe-btn" data-timeframe="1h">1h</button>
                                    <button class="timeframe-btn" data-timeframe="4h">4h</button>
                                    <button class="timeframe-btn" data-timeframe="1d">1d</button>
                                </div>
                                <div class="chart-tools">
                                    <button class="tool-btn" id="fullscreen-chart">⛶</button>
                                    <button class="tool-btn" id="chart-settings">⚙️</button>
                                </div>
                            </div>
                        </div>
                        <div class="chart-container">
                            <div id="trading-chart" class="trading-chart"></div>
                        </div>
                        <div class="chart-indicators">
                            <div class="indicator-group">
                                <label>RSI</label>
                                <span class="indicator-value">65.4</span>
                            </div>
                            <div class="indicator-group">
                                <label>MACD</label>
                                <span class="indicator-value">+125.6</span>
                            </div>
                            <div class="indicator-group">
                                <label>Volume</label>
                                <span class="indicator-value">1.2M</span>
                            </div>
                        </div>
                    </div>

                    <!-- Order Book Section -->
                    <div class="orderbook-section">
                        <div class="orderbook-header">
                            <h3>Order Book</h3>
                            <div class="orderbook-controls">
                                <button class="btn-icon" id="refresh-orderbook">🔄</button>
                                <button class="btn-icon" id="orderbook-settings">⚙️</button>
                            </div>
                        </div>
                        <div class="orderbook-container">
                            <div class="orderbook-side">
                                <div class="orderbook-header-row">
                                    <span>Price (USDT)</span>
                                    <span>Amount (BTC)</span>
                                    <span>Total</span>
                                </div>
                                <div class="orderbook-rows" id="sell-orders">
                                    <!-- Sell orders will be loaded here -->
                                </div>
                            </div>
                            <div class="orderbook-spread">
                                <span class="spread-label">Spread</span>
                                <span class="spread-value" id="orderbook-spread">$12.50 (0.03%)</span>
                            </div>
                            <div class="orderbook-side">
                                <div class="orderbook-header-row">
                                    <span>Price (USDT)</span>
                                    <span>Amount (BTC)</span>
                                    <span>Total</span>
                                </div>
                                <div class="orderbook-rows" id="buy-orders">
                                    <!-- Buy orders will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar - Trading Panel -->
                <div class="trading-sidebar-right">
                    <!-- Trading Form -->
                    <div class="trading-panel">
                        <div class="panel-header">
                            <h3>Place Order</h3>
                        </div>
                        <div class="trading-form">
                            <div class="order-type-tabs">
                                <button class="tab-btn active" data-type="limit">Limit</button>
                                <button class="tab-btn" data-type="market">Market</button>
                                <button class="tab-btn" data-type="stop">Stop</button>
                            </div>
                            
                            <div class="order-side-selector">
                                <button class="side-btn buy active" data-side="buy">Buy</button>
                                <button class="side-btn sell" data-side="sell">Sell</button>
                            </div>

                            <div class="form-group">
                                <label>Price (USDT)</label>
                                <input type="number" id="order-price" placeholder="0.00000000" step="0.01">
                                <div class="price-suggestions">
                                    <button class="price-btn" data-action="last">Last</button>
                                    <button class="price-btn" data-action="bid">Bid</button>
                                    <button class="price-btn" data-action="ask">Ask</button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Amount (BTC)</label>
                                <input type="number" id="order-amount" placeholder="0.00000000" step="0.00000001">
                                <div class="amount-suggestions">
                                    <button class="amount-btn" data-percent="25">25%</button>
                                    <button class="amount-btn" data-percent="50">50%</button>
                                    <button class="amount-btn" data-percent="75">75%</button>
                                    <button class="amount-btn" data-percent="100">100%</button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Total (USDT)</label>
                                <input type="number" id="order-total" placeholder="0.00000000" readonly>
                            </div>

                            <div class="order-options">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="post-only">
                                    <span class="checkmark"></span>
                                    Post Only
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" id="reduce-only">
                                    <span class="checkmark"></span>
                                    Reduce Only
                                </label>
                            </div>

                            <div class="order-summary">
                                <div class="summary-row">
                                    <span>Fee</span>
                                    <span id="order-fee">0.00000000 BTC</span>
                                </div>
                                <div class="summary-row">
                                    <span>Total</span>
                                    <span id="order-total-display">0.00000000 USDT</span>
                                </div>
                            </div>

                            <button class="btn btn-primary btn-large" id="place-order-btn">
                                <span id="order-btn-text">Buy BTC</span>
                            </button>
                        </div>
                    </div>

                    <!-- Balance Panel -->
                    <div class="balance-panel">
                        <div class="panel-header">
                            <h3>Balance</h3>
                            <button class="btn-icon" id="refresh-balance">🔄</button>
                        </div>
                        <div class="balance-list" id="balance-list">
                            <!-- Balance data will be loaded here -->
                        </div>
                    </div>

                    <!-- Open Orders Panel -->
                    <div class="orders-panel">
                        <div class="panel-header">
                            <h3>Open Orders</h3>
                            <button class="btn-icon" id="refresh-orders">🔄</button>
                        </div>
                        <div class="orders-list" id="open-orders">
                            <!-- Open orders will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize trading interface
    const tradingInterface = new CryptoTradingInterface();
    tradingInterface.init();
});

class CryptoTradingInterface {
    constructor() {
        this.currentPair = 'BTC/USDT';
        this.currentSide = 'buy';
        this.currentType = 'limit';
        this.chart = null;
        this.orderBook = { buy: [], sell: [] };
        this.balances = {};
        this.openOrders = [];
        this.init();
    }

    init() {
        this.loadMarketData();
        this.loadOrderBook();
        this.loadBalances();
        this.loadOpenOrders();
        this.initializeChart();
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
                    this.updateMarketList(response.data.pairs || []);
                }
            }
        });
    }

    loadOrderBook() {
        $.ajax({
            url: crypto_theme.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_orderbook',
                pair: this.currentPair,
                nonce: crypto_theme.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.orderBook = response.data;
                    this.updateOrderBook();
                }
            }
        });
    }

    loadBalances() {
        $.ajax({
            url: crypto_theme.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_balances',
                nonce: crypto_theme.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.balances = response.data;
                    this.updateBalanceList();
                }
            }
        });
    }

    loadOpenOrders() {
        $.ajax({
            url: crypto_theme.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_open_orders',
                nonce: crypto_theme.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.openOrders = response.data;
                    this.updateOpenOrders();
                }
            }
        });
    }

    initializeChart() {
        // Initialize TradingView chart
        if (typeof TradingView !== 'undefined') {
            this.chart = new TradingView.widget({
                width: '100%',
                height: 400,
                symbol: 'BINANCE:BTCUSDT',
                interval: '1',
                timezone: 'Etc/UTC',
                theme: 'light',
                style: '1',
                locale: 'en',
                toolbar_bg: '#f1f3f6',
                enable_publishing: false,
                hide_side_toolbar: false,
                allow_symbol_change: true,
                container_id: 'trading-chart',
                studies: [
                    'RSI@tv-basicstudies',
                    'MACD@tv-basicstudies'
                ]
            });
        }
    }

    updateMarketList(pairs) {
        const container = $('#market-list');
        container.empty();

        pairs.forEach(pair => {
            const row = $(`
                <div class="market-item" data-pair="${pair.symbol}">
                    <div class="market-symbol">${pair.symbol}</div>
                    <div class="market-price">$${pair.price.toFixed(2)}</div>
                    <div class="market-change ${pair.change >= 0 ? 'positive' : 'negative'}">
                        ${pair.change >= 0 ? '+' : ''}${pair.change.toFixed(2)}%
                    </div>
                </div>
            `);
            container.append(row);
        });
    }

    updateOrderBook() {
        this.updateOrderBookSide('#sell-orders', this.orderBook.sell, 'sell');
        this.updateOrderBookSide('#buy-orders', this.orderBook.buy, 'buy');
        
        // Update spread
        if (this.orderBook.sell.length > 0 && this.orderBook.buy.length > 0) {
            const bestAsk = this.orderBook.sell[0].price;
            const bestBid = this.orderBook.buy[0].price;
            const spread = bestAsk - bestBid;
            const spreadPercent = (spread / bestBid) * 100;
            $('#orderbook-spread').text(`$${spread.toFixed(2)} (${spreadPercent.toFixed(2)}%)`);
        }
    }

    updateOrderBookSide(selector, orders, side) {
        const container = $(selector);
        container.empty();

        orders.slice(0, 10).forEach(order => {
            const row = $(`
                <div class="orderbook-row ${side}">
                    <span class="price">$${order.price.toFixed(2)}</span>
                    <span class="amount">${order.amount.toFixed(8)}</span>
                    <span class="total">$${order.total.toFixed(2)}</span>
                </div>
            `);
            container.append(row);
        });
    }

    updateBalanceList() {
        const container = $('#balance-list');
        container.empty();

        Object.keys(this.balances).forEach(coin => {
            const balance = this.balances[coin];
            if (balance.amount > 0) {
                const row = $(`
                    <div class="balance-item">
                        <div class="coin-info">
                            <span class="coin-symbol">${coin}</span>
                            <span class="coin-name">${balance.name || coin}</span>
                        </div>
                        <div class="balance-amount">${balance.amount.toFixed(8)}</div>
                        <div class="balance-value">$${balance.value.toFixed(2)}</div>
                    </div>
                `);
                container.append(row);
            }
        });
    }

    updateOpenOrders() {
        const container = $('#open-orders');
        container.empty();

        if (this.openOrders.length === 0) {
            container.html('<div class="no-orders">No open orders</div>');
            return;
        }

        this.openOrders.forEach(order => {
            const row = $(`
                <div class="order-item">
                    <div class="order-pair">${order.pair}</div>
                    <div class="order-side ${order.side}">${order.side.toUpperCase()}</div>
                    <div class="order-amount">${order.amount}</div>
                    <div class="order-price">$${order.price}</div>
                    <div class="order-status">${order.status}</div>
                    <button class="btn btn-sm btn-danger cancel-order" data-id="${order.id}">Cancel</button>
                </div>
            `);
            container.append(row);
        });
    }

    bindEvents() {
        // Trading pair selection
        $('#trading-pair-select').change((e) => {
            this.currentPair = e.target.value;
            this.loadOrderBook();
            this.updateCurrentPair();
        });

        // Order side selection
        $('.side-btn').click((e) => {
            $('.side-btn').removeClass('active');
            $(e.target).addClass('active');
            this.currentSide = $(e.target).data('side');
            this.updateOrderButton();
        });

        // Order type selection
        $('.tab-btn[data-type]').click((e) => {
            $('.tab-btn[data-type]').removeClass('active');
            $(e.target).addClass('active');
            this.currentType = $(e.target).data('type');
        });

        // Price and amount inputs
        $('#order-price, #order-amount').on('input', () => {
            this.calculateTotal();
        });

        // Price suggestions
        $('.price-btn').click((e) => {
            const action = $(e.target).data('action');
            this.setPriceSuggestion(action);
        });

        // Amount suggestions
        $('.amount-btn').click((e) => {
            const percent = $(e.target).data('percent');
            this.setAmountPercentage(percent);
        });

        // Place order
        $('#place-order-btn').click(() => {
            this.placeOrder();
        });

        // Market item clicks
        $(document).on('click', '.market-item', (e) => {
            const pair = $(e.currentTarget).data('pair');
            this.selectTradingPair(pair);
        });

        // Cancel order
        $(document).on('click', '.cancel-order', (e) => {
            const orderId = $(e.target).data('id');
            this.cancelOrder(orderId);
        });
    }

    calculateTotal() {
        const price = parseFloat($('#order-price').val()) || 0;
        const amount = parseFloat($('#order-amount').val()) || 0;
        const total = price * amount;
        
        $('#order-total').val(total.toFixed(8));
        $('#order-total-display').text(`${total.toFixed(8)} USDT`);
        
        // Calculate fee (0.1%)
        const fee = amount * 0.001;
        $('#order-fee').text(`${fee.toFixed(8)} BTC`);
    }

    setPriceSuggestion(action) {
        if (action === 'last') {
            $('#order-price').val('45000.00');
        } else if (action === 'bid') {
            $('#order-price').val('44950.00');
        } else if (action === 'ask') {
            $('#order-price').val('45050.00');
        }
        this.calculateTotal();
    }

    setAmountPercentage(percent) {
        const balance = this.getCurrentBalance();
        const amount = (balance * percent) / 100;
        $('#order-amount').val(amount.toFixed(8));
        this.calculateTotal();
    }

    getCurrentBalance() {
        const baseCoin = this.currentPair.split('/')[0];
        return this.balances[baseCoin]?.amount || 0;
    }

    updateCurrentPair() {
        $('#current-pair').text(this.currentPair);
    }

    updateOrderButton() {
        const text = this.currentSide === 'buy' ? 'Buy' : 'Sell';
        const baseCoin = this.currentPair.split('/')[0];
        $('#order-btn-text').text(`${text} ${baseCoin}`);
    }

    selectTradingPair(pair) {
        this.currentPair = pair;
        $('#trading-pair-select').val(pair);
        this.updateCurrentPair();
        this.loadOrderBook();
    }

    placeOrder() {
        const orderData = {
            pair: this.currentPair,
            side: this.currentSide,
            type: this.currentType,
            price: parseFloat($('#order-price').val()),
            amount: parseFloat($('#order-amount').val()),
            post_only: $('#post-only').is(':checked'),
            reduce_only: $('#reduce-only').is(':checked')
        };

        $.ajax({
            url: crypto_theme.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_place_order',
                order: orderData,
                nonce: crypto_theme.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.showNotification('Order placed successfully!', 'success');
                    this.loadOpenOrders();
                    this.loadBalances();
                } else {
                    this.showNotification('Error: ' + response.data, 'error');
                }
            }
        });
    }

    cancelOrder(orderId) {
        $.ajax({
            url: crypto_theme.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_cancel_order',
                order_id: orderId,
                nonce: crypto_theme.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.showNotification('Order cancelled successfully!', 'success');
                    this.loadOpenOrders();
                } else {
                    this.showNotification('Error: ' + response.data, 'error');
                }
            }
        });
    }

    startRealTimeUpdates() {
        // Update order book every 2 seconds
        setInterval(() => {
            this.loadOrderBook();
        }, 2000);

        // Update balances every 10 seconds
        setInterval(() => {
            this.loadBalances();
        }, 10000);

        // Update open orders every 5 seconds
        setInterval(() => {
            this.loadOpenOrders();
        }, 5000);
    }

    showNotification(message, type) {
        const notification = $(`
            <div class="crypto-notification ${type}">
                <span class="message">${message}</span>
                <button class="close-notification">&times;</button>
            </div>
        `);

        $('body').append(notification);

        setTimeout(() => {
            notification.fadeOut(() => {
                notification.remove();
            });
        }, 5000);

        notification.find('.close-notification').click(() => {
            notification.fadeOut(() => {
                notification.remove();
            });
        });
    }
}
</script>

<?php get_footer(); ?>
