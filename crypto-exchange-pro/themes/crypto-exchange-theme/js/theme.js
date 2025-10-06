/**
 * Crypto Exchange Pro Theme JavaScript
 * Handles theme functionality and interactions
 */

class CryptoExchangeTheme {
    constructor() {
        this.isThemeActive = false;
        this.settings = {};
        this.charts = {};
        this.init();
    }

    init() {
        this.checkThemeStatus();
        this.loadSettings();
        this.bindEvents();
        this.initializeComponents();
    }

    checkThemeStatus() {
        // Check if theme is active via AJAX
        jQuery.ajax({
            url: crypto_theme.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_check_theme_status',
                nonce: crypto_theme.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.isThemeActive = response.data.active;
                    this.applyThemeSettings();
                }
            }
        });
    }

    loadSettings() {
        this.settings = crypto_theme.settings || {};
        this.applyThemeSettings();
    }

    applyThemeSettings() {
        if (!this.isThemeActive) return;

        // Apply color scheme
        this.applyColorScheme(this.settings.color_scheme || 'default');
        
        // Apply layout
        this.applyLayout(this.settings.layout || 'modern');
        
        // Apply sidebar position
        this.applySidebarPosition(this.settings.sidebar || 'right');
    }

    applyColorScheme(scheme) {
        const body = document.body;
        body.classList.remove('color-scheme-default', 'color-scheme-dark', 'color-scheme-light', 'color-scheme-green');
        body.classList.add(`color-scheme-${scheme}`);
    }

    applyLayout(layout) {
        const body = document.body;
        body.classList.remove('layout-modern', 'layout-classic', 'layout-minimal');
        body.classList.add(`layout-${layout}`);
    }

    applySidebarPosition(position) {
        const body = document.body;
        body.classList.remove('sidebar-right', 'sidebar-left', 'sidebar-none');
        body.classList.add(`sidebar-${position}`);
    }

    bindEvents() {
        // Theme toggle events
        jQuery(document).on('click', '.theme-toggle', (e) => {
            this.toggleTheme();
        });

        // Color scheme change
        jQuery(document).on('change', '.color-scheme-selector', (e) => {
            this.changeColorScheme(e.target.value);
        });

        // Layout change
        jQuery(document).on('change', '.layout-selector', (e) => {
            this.changeLayout(e.target.value);
        });

        // Sidebar position change
        jQuery(document).on('change', '.sidebar-selector', (e) => {
            this.changeSidebarPosition(e.target.value);
        });

        // Mobile menu toggle
        jQuery(document).on('click', '.mobile-menu-toggle', (e) => {
            this.toggleMobileMenu();
        });

        // Chart fullscreen
        jQuery(document).on('click', '.fullscreen-chart', (e) => {
            this.toggleChartFullscreen();
        });

        // Market data refresh
        jQuery(document).on('click', '.refresh-markets', (e) => {
            this.refreshMarketData();
        });

        // Trading pair selection
        jQuery(document).on('click', '.market-item', (e) => {
            this.selectTradingPair(jQuery(e.currentTarget).data('pair'));
        });

        // Order book row click
        jQuery(document).on('click', '.orderbook-row', (e) => {
            this.setOrderPrice(jQuery(e.currentTarget).find('.price').text());
        });
    }

    initializeComponents() {
        if (!this.isThemeActive) return;

        this.initializeCharts();
        this.initializeMarketData();
        this.initializeTradingInterface();
        this.initializeNotifications();
        this.startRealTimeUpdates();
    }

    initializeCharts() {
        // Initialize TradingView charts if available
        if (typeof TradingView !== 'undefined') {
            this.initializeTradingViewCharts();
        } else {
            // Fallback to Chart.js
            this.initializeChartJSCharts();
        }
    }

    initializeTradingViewCharts() {
        // Main trading chart
        const tradingChart = document.getElementById('trading-chart');
        if (tradingChart) {
            this.charts.trading = new TradingView.widget({
                width: '100%',
                height: 400,
                symbol: 'BINANCE:BTCUSDT',
                interval: '1',
                timezone: 'Etc/UTC',
                theme: this.settings.color_scheme === 'dark' ? 'dark' : 'light',
                style: '1',
                locale: 'en',
                toolbar_bg: '#f1f3f6',
                enable_publishing: false,
                hide_side_toolbar: false,
                allow_symbol_change: true,
                container_id: 'trading-chart',
                studies: [
                    'RSI@tv-basicstudies',
                    'MACD@tv-basicstudies',
                    'Volume@tv-basicstudies'
                ]
            });
        }

        // Homepage hero chart
        const heroChart = document.getElementById('hero-chart');
        if (heroChart) {
            this.charts.hero = new TradingView.widget({
                width: '100%',
                height: 200,
                symbol: 'BINANCE:BTCUSDT',
                interval: '1',
                timezone: 'Etc/UTC',
                theme: 'light',
                style: '1',
                locale: 'en',
                toolbar_bg: '#f1f3f6',
                enable_publishing: false,
                hide_side_toolbar: true,
                allow_symbol_change: false,
                container_id: 'hero-chart'
            });
        }
    }

    initializeChartJSCharts() {
        // Fallback chart implementation using Chart.js
        const chartElements = document.querySelectorAll('.trading-chart, .hero-chart');
        chartElements.forEach(element => {
            this.createFallbackChart(element);
        });
    }

    createFallbackChart(element) {
        const ctx = element.getContext('2d');
        const data = this.generateSampleData();
        
        this.charts[element.id] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Price',
                    data: data.prices,
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

    generateSampleData() {
        const labels = [];
        const prices = [];
        let price = 45000;
        
        for (let i = 0; i < 50; i++) {
            labels.push(i);
            price += (Math.random() - 0.5) * 1000;
            prices.push(price);
        }
        
        return { labels, prices };
    }

    initializeMarketData() {
        this.loadMarketData();
        this.loadOrderBook();
        this.loadTradingPairs();
    }

    loadMarketData() {
        jQuery.ajax({
            url: crypto_theme.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_market_data',
                nonce: crypto_theme.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updateMarketDisplay(response.data);
                }
            }
        });
    }

    loadOrderBook() {
        jQuery.ajax({
            url: crypto_theme.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_orderbook',
                pair: 'BTC/USDT',
                nonce: crypto_theme.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updateOrderBookDisplay(response.data);
                }
            }
        });
    }

    loadTradingPairs() {
        jQuery.ajax({
            url: crypto_theme.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_trading_pairs',
                nonce: crypto_theme.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updateTradingPairsDisplay(response.data);
                }
            }
        });
    }

    updateMarketDisplay(data) {
        // Update market table
        const tbody = document.getElementById('market-data');
        if (tbody) {
            tbody.innerHTML = '';
            
            data.pairs.forEach(pair => {
                const row = document.createElement('tr');
                row.innerHTML = `
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
                `;
                tbody.appendChild(row);
            });
        }
    }

    updateOrderBookDisplay(data) {
        // Update order book
        this.updateOrderBookSide('#sell-orders', data.sell || []);
        this.updateOrderBookSide('#buy-orders', data.buy || []);
        
        // Update spread
        if (data.sell && data.buy && data.sell.length > 0 && data.buy.length > 0) {
            const bestAsk = data.sell[0].price;
            const bestBid = data.buy[0].price;
            const spread = bestAsk - bestBid;
            const spreadPercent = (spread / bestBid) * 100;
            
            const spreadElement = document.getElementById('orderbook-spread');
            if (spreadElement) {
                spreadElement.textContent = `$${spread.toFixed(2)} (${spreadPercent.toFixed(2)}%)`;
            }
        }
    }

    updateOrderBookSide(selector, orders) {
        const container = document.querySelector(selector);
        if (!container) return;
        
        container.innerHTML = '';
        
        orders.slice(0, 10).forEach(order => {
            const row = document.createElement('div');
            row.className = 'orderbook-row';
            row.innerHTML = `
                <span class="price">$${order.price.toFixed(2)}</span>
                <span class="amount">${order.amount.toFixed(8)}</span>
                <span class="total">$${order.total.toFixed(2)}</span>
            `;
            container.appendChild(row);
        });
    }

    updateTradingPairsDisplay(data) {
        // Update trading pairs in sidebar
        const container = document.getElementById('market-list');
        if (!container) return;
        
        container.innerHTML = '';
        
        data.pairs.forEach(pair => {
            const item = document.createElement('div');
            item.className = 'market-item';
            item.dataset.pair = pair.symbol;
            item.innerHTML = `
                <div class="market-symbol">${pair.symbol}</div>
                <div class="market-price">$${pair.price.toFixed(2)}</div>
                <div class="market-change ${pair.change >= 0 ? 'positive' : 'negative'}">
                    ${pair.change >= 0 ? '+' : ''}${pair.change.toFixed(2)}%
                </div>
            `;
            container.appendChild(item);
        });
    }

    initializeTradingInterface() {
        // Initialize trading form
        this.initializeTradingForm();
        
        // Initialize balance display
        this.loadUserBalances();
        
        // Initialize open orders
        this.loadOpenOrders();
    }

    initializeTradingForm() {
        // Bind form events
        jQuery(document).on('input', '#order-price, #order-amount', () => {
            this.calculateOrderTotal();
        });

        jQuery(document).on('click', '.side-btn', (e) => {
            jQuery('.side-btn').removeClass('active');
            jQuery(e.target).addClass('active');
        });

        jQuery(document).on('click', '.order-type-tabs .tab-btn', (e) => {
            jQuery('.order-type-tabs .tab-btn').removeClass('active');
            jQuery(e.target).addClass('active');
        });
    }

    calculateOrderTotal() {
        const price = parseFloat(jQuery('#order-price').val()) || 0;
        const amount = parseFloat(jQuery('#order-amount').val()) || 0;
        const total = price * amount;
        
        jQuery('#order-total').val(total.toFixed(8));
    }

    loadUserBalances() {
        jQuery.ajax({
            url: crypto_theme.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_balances',
                nonce: crypto_theme.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updateBalanceDisplay(response.data);
                }
            }
        });
    }

    updateBalanceDisplay(balances) {
        const container = document.getElementById('balance-list');
        if (!container) return;
        
        container.innerHTML = '';
        
        Object.keys(balances).forEach(coin => {
            const balance = balances[coin];
            if (balance.amount > 0) {
                const item = document.createElement('div');
                item.className = 'balance-item';
                item.innerHTML = `
                    <div class="coin-info">
                        <span class="coin-symbol">${coin}</span>
                        <span class="coin-name">${balance.name || coin}</span>
                    </div>
                    <div class="balance-amount">${balance.amount.toFixed(8)}</div>
                    <div class="balance-value">$${balance.value.toFixed(2)}</div>
                `;
                container.appendChild(item);
            }
        });
    }

    loadOpenOrders() {
        jQuery.ajax({
            url: crypto_theme.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_open_orders',
                nonce: crypto_theme.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updateOpenOrdersDisplay(response.data);
                }
            }
        });
    }

    updateOpenOrdersDisplay(orders) {
        const container = document.getElementById('open-orders');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (orders.length === 0) {
            container.innerHTML = '<div class="no-orders">No open orders</div>';
            return;
        }
        
        orders.forEach(order => {
            const item = document.createElement('div');
            item.className = 'order-item';
            item.innerHTML = `
                <div class="order-pair">${order.pair}</div>
                <div class="order-side ${order.side}">${order.side.toUpperCase()}</div>
                <div class="order-amount">${order.amount}</div>
                <div class="order-price">$${order.price}</div>
                <div class="order-status">${order.status}</div>
                <button class="btn btn-sm btn-danger cancel-order" data-id="${order.id}">Cancel</button>
            `;
            container.appendChild(item);
        });
    }

    initializeNotifications() {
        // Initialize notification system
        this.notificationContainer = document.createElement('div');
        this.notificationContainer.className = 'notification-container';
        document.body.appendChild(this.notificationContainer);
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `crypto-notification ${type}`;
        notification.innerHTML = `
            <span class="message">${message}</span>
            <button class="close-notification">&times;</button>
        `;
        
        this.notificationContainer.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
        
        // Close button
        notification.querySelector('.close-notification').addEventListener('click', () => {
            notification.remove();
        });
    }

    startRealTimeUpdates() {
        // Update market data every 5 seconds
        setInterval(() => {
            this.loadMarketData();
        }, 5000);

        // Update order book every 2 seconds
        setInterval(() => {
            this.loadOrderBook();
        }, 2000);

        // Update balances every 30 seconds
        setInterval(() => {
            this.loadUserBalances();
        }, 30000);

        // Update open orders every 10 seconds
        setInterval(() => {
            this.loadOpenOrders();
        }, 10000);
    }

    // Event handlers
    toggleTheme() {
        this.isThemeActive = !this.isThemeActive;
        this.applyThemeSettings();
    }

    changeColorScheme(scheme) {
        this.settings.color_scheme = scheme;
        this.applyColorScheme(scheme);
        this.saveSettings();
    }

    changeLayout(layout) {
        this.settings.layout = layout;
        this.applyLayout(layout);
        this.saveSettings();
    }

    changeSidebarPosition(position) {
        this.settings.sidebar = position;
        this.applySidebarPosition(position);
        this.saveSettings();
    }

    toggleMobileMenu() {
        const menu = document.querySelector('.mobile-menu');
        if (menu) {
            menu.classList.toggle('active');
        }
    }

    toggleChartFullscreen() {
        const chart = document.querySelector('.chart-container');
        if (chart) {
            chart.classList.toggle('fullscreen');
        }
    }

    refreshMarketData() {
        this.loadMarketData();
        this.showNotification('Market data refreshed', 'success');
    }

    selectTradingPair(pair) {
        // Update trading pair selection
        const selector = document.getElementById('trading-pair-select');
        if (selector) {
            selector.value = pair;
        }
        
        // Load new order book
        this.loadOrderBook();
        
        // Update chart symbol
        if (this.charts.trading) {
            this.charts.trading.setSymbol(`BINANCE:${pair.replace('/', '')}`);
        }
    }

    setOrderPrice(price) {
        const priceInput = document.getElementById('order-price');
        if (priceInput) {
            priceInput.value = price.replace('$', '');
            this.calculateOrderTotal();
        }
    }

    saveSettings() {
        jQuery.ajax({
            url: crypto_theme.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_save_theme_settings',
                settings: this.settings,
                nonce: crypto_theme.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.showNotification('Settings saved successfully', 'success');
                }
            }
        });
    }

    formatNumber(num) {
        if (num >= 1e9) return (num / 1e9).toFixed(1) + 'B';
        if (num >= 1e6) return (num / 1e6).toFixed(1) + 'M';
        if (num >= 1e3) return (num / 1e3).toFixed(1) + 'K';
        return num.toFixed(0);
    }
}

// Initialize theme when DOM is ready
jQuery(document).ready(function() {
    window.cryptoExchangeTheme = new CryptoExchangeTheme();
});

// Export for global access
window.CryptoExchangeTheme = CryptoExchangeTheme;
