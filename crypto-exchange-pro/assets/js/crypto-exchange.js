/**
 * Crypto Exchange Pro Frontend JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize components
    initTradingInterface();
    initWalletInterface();
    initMarketData();
    initCharts();
    initNotifications();
    
    /**
     * Initialize trading interface
     */
    function initTradingInterface() {
        // Order form submission
        $('#order-form, #trading-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            var $form = $(this);
            
            // Show loading state
            $form.find('button[type="submit"]').prop('disabled', true).text('Placing Order...');
            
            $.ajax({
                url: crypto_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crypto_exchange_place_order',
                    nonce: crypto_ajax.nonce,
                    ...formData
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Order placed successfully!', 'success');
                        $form[0].reset();
                        loadOrderBook();
                        loadUserOrders();
                    } else {
                        showNotification('Error: ' + response.data, 'error');
                    }
                },
                error: function() {
                    showNotification('An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    $form.find('button[type="submit"]').prop('disabled', false).text('Place Order');
                }
            });
        });
        
        // Order type change
        $('select[name="order_type"]').on('change', function() {
            var orderType = $(this).val();
            var $priceGroup = $('.form-group:has(input[name="price"])');
            var $stopGroup = $('.form-group:has(input[name="stop_price"])');
            
            if (orderType === 'limit') {
                $priceGroup.show();
                $stopGroup.hide();
            } else if (orderType === 'stop') {
                $priceGroup.hide();
                $stopGroup.show();
            } else {
                $priceGroup.hide();
                $stopGroup.hide();
            }
        });
        
        // Load order book
        loadOrderBook();
        
        // Update order book every 5 seconds
        setInterval(loadOrderBook, 5000);
    }
    
    /**
     * Initialize wallet interface
     */
    function initWalletInterface() {
        // Deposit/Withdraw buttons
        $('.deposit-btn, .withdraw-btn').on('click', function() {
            var action = $(this).hasClass('deposit-btn') ? 'deposit' : 'withdraw';
            var currency = $(this).data('currency');
            openWalletModal(action, currency);
        });
        
        // Load user wallets
        loadUserWallets();
    }
    
    /**
     * Initialize market data
     */
    function initMarketData() {
        loadMarketData();
        
        // Update market data every 30 seconds
        setInterval(loadMarketData, 30000);
    }
    
    /**
     * Initialize charts
     */
    function initCharts() {
        // Chart interval change
        $('#chart-interval').on('change', function() {
            var interval = $(this).val();
            var pair = $('#trading-pair-select').val() || 'BTC/USD';
            updateChart(pair, interval);
        });
        
        // Trading pair change
        $('#trading-pair-select').on('change', function() {
            var pair = $(this).val();
            var interval = $('#chart-interval').val() || '1';
            updateChart(pair, interval);
        });
    }
    
    /**
     * Initialize notifications
     */
    function initNotifications() {
        // Load notifications
        loadNotifications();
        
        // Mark notification as read
        $(document).on('click', '.notification-item', function() {
            var notificationId = $(this).data('id');
            markNotificationRead(notificationId);
        });
    }
    
    /**
     * Load order book
     */
    function loadOrderBook() {
        var tradingPair = $('#trading-pair-select').val() || 'BTC/USD';
        
        $.ajax({
            url: crypto_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_order_book',
                trading_pair: tradingPair,
                nonce: crypto_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateOrderBookDisplay(response.data);
                }
            }
        });
    }
    
    /**
     * Update order book display
     */
    function updateOrderBookDisplay(data) {
        var bidsHtml = '';
        var asksHtml = '';
        
        // Update bids
        if (data.bids && data.bids.length > 0) {
            data.bids.forEach(function(bid) {
                bidsHtml += '<div class="order-item">';
                bidsHtml += '<span class="price">' + parseFloat(bid.price).toFixed(2) + '</span>';
                bidsHtml += '<span class="amount">' + parseFloat(bid.amount).toFixed(8) + '</span>';
                bidsHtml += '</div>';
            });
        } else {
            bidsHtml = '<div class="no-orders">No bids</div>';
        }
        
        // Update asks
        if (data.asks && data.asks.length > 0) {
            data.asks.forEach(function(ask) {
                asksHtml += '<div class="order-item">';
                asksHtml += '<span class="price">' + parseFloat(ask.price).toFixed(2) + '</span>';
                asksHtml += '<span class="amount">' + parseFloat(ask.amount).toFixed(8) + '</span>';
                asksHtml += '</div>';
            });
        } else {
            asksHtml = '<div class="no-orders">No asks</div>';
        }
        
        $('#bids-list, .order-book .bids .order-list').html(bidsHtml);
        $('#asks-list, .order-book .asks .order-list').html(asksHtml);
    }
    
    /**
     * Load market data
     */
    function loadMarketData() {
        $.ajax({
            url: crypto_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_market_data',
                nonce: crypto_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateMarketDataDisplay(response.data);
                }
            },
            error: function() {
                // Show demo data if API fails
                showDemoMarketData();
            }
        });
    }
    
    /**
     * Update market data display
     */
    function updateMarketDataDisplay(data) {
        $('.price-card').each(function() {
            var $card = $(this);
            var symbol = $card.find('.pair-name, .crypto-symbol').text();
            
            var pairData = data.find(function(pair) {
                return pair.symbol === symbol;
            });
            
            if (pairData) {
                $card.find('.price, .crypto-price').text('$' + pairData.price.toFixed(2));
                
                var changeElement = $card.find('.change, .crypto-change');
                var changeText = (pairData.change_24h >= 0 ? '+' : '') + pairData.change_24h.toFixed(2) + '%';
                changeElement.text(changeText);
                changeElement.removeClass('positive negative').addClass(pairData.change_24h >= 0 ? 'positive' : 'negative');
            }
        });
    }
    
    /**
     * Show demo market data
     */
    function showDemoMarketData() {
        var demoData = [
            { symbol: 'BTC/USD', price: 45000.00, change_24h: 2.5 },
            { symbol: 'ETH/USD', price: 3200.00, change_24h: -1.2 },
            { symbol: 'LTC/USD', price: 150.00, change_24h: 0.8 },
            { symbol: 'BNB/USD', price: 350.00, change_24h: 3.1 },
            { symbol: 'ADA/USD', price: 0.45, change_24h: -0.5 },
            { symbol: 'DOT/USD', price: 25.00, change_24h: 1.8 }
        ];
        
        updateMarketDataDisplay(demoData);
    }
    
    /**
     * Load user wallets
     */
    function loadUserWallets() {
        $.ajax({
            url: crypto_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_user_wallets',
                nonce: crypto_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateWalletsDisplay(response.data);
                }
            }
        });
    }
    
    /**
     * Update wallets display
     */
    function updateWalletsDisplay(wallets) {
        var walletsHtml = '';
        
        wallets.forEach(function(wallet) {
            walletsHtml += '<div class="wallet-card">';
            walletsHtml += '<div class="wallet-header">';
            walletsHtml += '<h4>' + wallet.currency + '</h4>';
            walletsHtml += '<span class="wallet-type">' + wallet.type + '</span>';
            walletsHtml += '</div>';
            walletsHtml += '<div class="wallet-balance">';
            walletsHtml += '<span class="balance">' + parseFloat(wallet.balance).toFixed(8) + '</span>';
            walletsHtml += '<span class="currency">' + wallet.currency + '</span>';
            walletsHtml += '</div>';
            walletsHtml += '<div class="wallet-actions">';
            walletsHtml += '<button class="deposit-btn" data-currency="' + wallet.currency + '">Deposit</button>';
            walletsHtml += '<button class="withdraw-btn" data-currency="' + wallet.currency + '">Withdraw</button>';
            walletsHtml += '</div>';
            walletsHtml += '</div>';
        });
        
        $('.wallets-grid').html(walletsHtml);
        
        // Re-bind deposit/withdraw buttons
        $('.deposit-btn, .withdraw-btn').off('click').on('click', function() {
            var action = $(this).hasClass('deposit-btn') ? 'deposit' : 'withdraw';
            var currency = $(this).data('currency');
            openWalletModal(action, currency);
        });
    }
    
    /**
     * Load user orders
     */
    function loadUserOrders() {
        $.ajax({
            url: crypto_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_user_orders',
                nonce: crypto_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateOrdersDisplay(response.data);
                }
            }
        });
    }
    
    /**
     * Update orders display
     */
    function updateOrdersDisplay(orders) {
        var ordersHtml = '';
        
        orders.forEach(function(order) {
            ordersHtml += '<tr>';
            ordersHtml += '<td>' + order.trading_pair + '</td>';
            ordersHtml += '<td>' + order.order_type + '</td>';
            ordersHtml += '<td>' + order.side + '</td>';
            ordersHtml += '<td>' + parseFloat(order.amount).toFixed(8) + '</td>';
            ordersHtml += '<td>' + parseFloat(order.price).toFixed(2) + '</td>';
            ordersHtml += '<td>' + order.status + '</td>';
            ordersHtml += '<td>';
            if (order.status === 'pending' || order.status === 'open') {
                ordersHtml += '<button class="cancel-order-btn" data-order-id="' + order.id + '">Cancel</button>';
            }
            ordersHtml += '</td>';
            ordersHtml += '</tr>';
        });
        
        $('.orders-table tbody').html(ordersHtml);
        
        // Re-bind cancel order buttons
        $('.cancel-order-btn').off('click').on('click', function() {
            var orderId = $(this).data('order-id');
            cancelOrder(orderId);
        });
    }
    
    /**
     * Cancel order
     */
    function cancelOrder(orderId) {
        if (confirm('Are you sure you want to cancel this order?')) {
            $.ajax({
                url: crypto_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crypto_exchange_cancel_order',
                    order_id: orderId,
                    nonce: crypto_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Order cancelled successfully!', 'success');
                        loadUserOrders();
                    } else {
                        showNotification('Error: ' + response.data, 'error');
                    }
                }
            });
        }
    }
    
    /**
     * Open wallet modal
     */
    function openWalletModal(action, currency) {
        var modal = $('<div class="wallet-modal">');
        modal.html(`
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3>${action.charAt(0).toUpperCase() + action.slice(1)} ${currency}</h3>
                <form id="wallet-form">
                    <input type="hidden" name="action" value="crypto_exchange_${action}">
                    <input type="hidden" name="currency" value="${currency}">
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" name="amount" step="0.00000001" required>
                    </div>
                    ${action === 'withdraw' ? `
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" required>
                    </div>
                    ` : ''}
                    <button type="submit">${action.charAt(0).toUpperCase() + action.slice(1)}</button>
                </form>
            </div>
        `);
        
        $('body').append(modal);
        
        // Handle form submission
        $('#wallet-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            
            $.ajax({
                url: crypto_ajax.ajax_url,
                type: 'POST',
                data: {
                    ...formData,
                    nonce: crypto_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotification(action.charAt(0).toUpperCase() + action.slice(1) + ' request submitted successfully!', 'success');
                        modal.remove();
                        loadUserWallets();
                    } else {
                        showNotification('Error: ' + response.data, 'error');
                    }
                }
            });
        });
        
        // Close modal
        $('.close').on('click', function() {
            modal.remove();
        });
        
        // Close modal when clicking outside
        $(window).on('click', function(e) {
            if (e.target.id === 'wallet-modal') {
                modal.remove();
            }
        });
    }
    
    /**
     * Load notifications
     */
    function loadNotifications() {
        $.ajax({
            url: crypto_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_notifications',
                nonce: crypto_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateNotificationsDisplay(response.data);
                }
            }
        });
    }
    
    /**
     * Update notifications display
     */
    function updateNotificationsDisplay(notifications) {
        var notificationsHtml = '';
        
        notifications.forEach(function(notification) {
            notificationsHtml += '<div class="notification-item" data-id="' + notification.id + '">';
            notificationsHtml += '<div class="notification-title">' + notification.title + '</div>';
            notificationsHtml += '<div class="notification-message">' + notification.message + '</div>';
            notificationsHtml += '<div class="notification-time">' + notification.created_at + '</div>';
            notificationsHtml += '</div>';
        });
        
        $('.notifications-list').html(notificationsHtml);
    }
    
    /**
     * Mark notification as read
     */
    function markNotificationRead(notificationId) {
        $.ajax({
            url: crypto_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_mark_notification_read',
                notification_id: notificationId,
                nonce: crypto_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.notification-item[data-id="' + notificationId + '"]').addClass('read');
                }
            }
        });
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type) {
        var notification = $('<div class="crypto-notification ' + type + '">' + message + '</div>');
        
        $('body').append(notification);
        
        setTimeout(function() {
            notification.fadeOut(function() {
                notification.remove();
            });
        }, 5000);
    }
    
    /**
     * Update chart
     */
    function updateChart(pair, interval) {
        // This would integrate with TradingView or other charting library
        console.log('Updating chart for ' + pair + ' with interval ' + interval);
    }
    
    // Tab switching
    $('.tab-btn').on('click', function() {
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        
        var tabId = $(this).data('tab');
        $('.tab-pane').removeClass('active');
        $('#' + tabId).addClass('active');
    });
    
    // Initialize on page load
    if ($('.crypto-exchange-dashboard').length > 0) {
        loadUserOrders();
        loadUserWallets();
    }
});

// Add notification styles
jQuery(document).ready(function($) {
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .crypto-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 4px;
                color: white;
                font-weight: 500;
                z-index: 9999;
                animation: slideInRight 0.3s ease-out;
            }
            
            .crypto-notification.success {
                background: #28a745;
            }
            
            .crypto-notification.error {
                background: #dc3545;
            }
            
            .crypto-notification.info {
                background: #17a2b8;
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            .wallet-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
            }
            
            .modal-content {
                background: white;
                padding: 30px;
                border-radius: 8px;
                position: relative;
                min-width: 400px;
            }
            
            .close {
                position: absolute;
                top: 10px;
                right: 15px;
                font-size: 24px;
                cursor: pointer;
            }
        `)
        .appendTo('head');
});
