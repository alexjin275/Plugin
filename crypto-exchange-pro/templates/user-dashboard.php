<?php
/**
 * User Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$crypto_exchange = new Crypto_Exchange();
$wallet = new Crypto_Exchange_Wallet();
$trading = new Crypto_Exchange_Trading();
$market_data = new Crypto_Exchange_Market_Data();

// Get user data
$user_wallets = $wallet->get_user_wallets($user_id);
$user_orders = $trading->get_user_orders($user_id);
$user_trades = $trading->get_user_trades($user_id);
$market_prices = $market_data->get_all_prices();
?>

<div class="crypto-exchange-dashboard">
    <div class="dashboard-header">
        <h1>Cryptocurrency Exchange Dashboard</h1>
        <div class="user-info">
            <span>Welcome, <?php echo esc_html(wp_get_current_user()->display_name); ?></span>
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="dashboard-content">
        <!-- Trading Interface -->
        <div class="trading-section">
            <div class="trading-panel">
                <div class="order-form">
                    <h3>Place Order</h3>
                    <form id="order-form">
                        <div class="form-group">
                            <label for="order-type">Order Type</label>
                            <select id="order-type" name="order_type">
                                <option value="market">Market Order</option>
                                <option value="limit">Limit Order</option>
                                <option value="stop">Stop Order</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="order-side">Side</label>
                            <select id="order-side" name="side">
                                <option value="buy">Buy</option>
                                <option value="sell">Sell</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="trading-pair">Trading Pair</label>
                            <select id="trading-pair" name="trading_pair">
                                <?php foreach ($market_prices as $pair => $data): ?>
                                    <option value="<?php echo esc_attr($pair); ?>"><?php echo esc_html($pair); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input type="number" id="amount" name="amount" step="0.00000001" required>
                        </div>
                        
                        <div class="form-group" id="price-group" style="display: none;">
                            <label for="price">Price</label>
                            <input type="number" id="price" name="price" step="0.01">
                        </div>
                        
                        <div class="form-group">
                            <label for="stop-price" id="stop-price-label" style="display: none;">Stop Price</label>
                            <input type="number" id="stop-price" name="stop_price" step="0.01" style="display: none;">
                        </div>
                        
                        <button type="submit" class="place-order-btn">Place Order</button>
                    </form>
                </div>
                
                <div class="order-book">
                    <h3>Order Book</h3>
                    <div class="order-book-content">
                        <div class="bids">
                            <h4>Bids</h4>
                            <div class="order-list" id="bids-list"></div>
                        </div>
                        <div class="asks">
                            <h4>Asks</h4>
                            <div class="order-list" id="asks-list"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="chart-section">
                <h3>Price Chart</h3>
                <div class="chart-container">
                    <?php
                    $charting = new Crypto_Exchange_Charting();
                    echo $charting->render_chart('BTC/USD', '1', 'dark');
                    ?>
                </div>
            </div>
        </div>

        <!-- Wallet Section -->
        <div class="wallet-section">
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

        <!-- Orders Section -->
        <div class="orders-section">
            <h3>My Orders</h3>
            <div class="orders-tabs">
                <button class="tab-btn active" data-tab="open-orders">Open Orders</button>
                <button class="tab-btn" data-tab="order-history">Order History</button>
                <button class="tab-btn" data-tab="trade-history">Trade History</button>
            </div>
            
            <div class="tab-content">
                <div id="open-orders" class="tab-pane active">
                    <div class="orders-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Pair</th>
                                    <th>Type</th>
                                    <th>Side</th>
                                    <th>Amount</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_orders as $order): ?>
                                    <tr>
                                        <td><?php echo esc_html($order['trading_pair']); ?></td>
                                        <td><?php echo esc_html($order['order_type']); ?></td>
                                        <td><?php echo esc_html($order['side']); ?></td>
                                        <td><?php echo number_format($order['amount'], 8); ?></td>
                                        <td><?php echo number_format($order['price'], 2); ?></td>
                                        <td><?php echo esc_html($order['status']); ?></td>
                                        <td>
                                            <button class="cancel-order-btn" data-order-id="<?php echo esc_attr($order['id']); ?>">Cancel</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div id="order-history" class="tab-pane">
                    <div class="orders-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Pair</th>
                                    <th>Type</th>
                                    <th>Side</th>
                                    <th>Amount</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Order history will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div id="trade-history" class="tab-pane">
                    <div class="trades-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Pair</th>
                                    <th>Side</th>
                                    <th>Amount</th>
                                    <th>Price</th>
                                    <th>Fee</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_trades as $trade): ?>
                                    <tr>
                                        <td><?php echo esc_html($trade['trading_pair']); ?></td>
                                        <td><?php echo esc_html($trade['side']); ?></td>
                                        <td><?php echo number_format($trade['amount'], 8); ?></td>
                                        <td><?php echo number_format($trade['price'], 2); ?></td>
                                        <td><?php echo number_format($trade['fee'], 8); ?></td>
                                        <td><?php echo esc_html($trade['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Market Data Section -->
        <div class="market-data-section">
            <h3>Market Data</h3>
            <div class="market-prices">
                <?php foreach ($market_prices as $pair => $data): ?>
                    <div class="price-card">
                        <div class="pair-name"><?php echo esc_html($pair); ?></div>
                        <div class="price">$<?php echo number_format($data['price'], 2); ?></div>
                        <div class="change <?php echo $data['change_24h'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo ($data['change_24h'] >= 0 ? '+' : '') . number_format($data['change_24h'], 2); ?>%
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Order form handling
    $('#order-type').change(function() {
        if ($(this).val() === 'limit') {
            $('#price-group').show();
            $('#stop-price').hide();
            $('#stop-price-label').hide();
        } else if ($(this).val() === 'stop') {
            $('#price-group').hide();
            $('#stop-price').show();
            $('#stop-price-label').show();
        } else {
            $('#price-group').hide();
            $('#stop-price').hide();
            $('#stop-price-label').hide();
        }
    });
    
    // Place order
    $('#order-form').submit(function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'crypto_exchange_place_order',
                ...formData
            },
            success: function(response) {
                if (response.success) {
                    alert('Order placed successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });
    
    // Cancel order
    $('.cancel-order-btn').click(function() {
        var orderId = $(this).data('order-id');
        
        if (confirm('Are you sure you want to cancel this order?')) {
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'crypto_exchange_cancel_order',
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Order cancelled successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        }
    });
    
    // Tab switching
    $('.tab-btn').click(function() {
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        
        var tabId = $(this).data('tab');
        $('.tab-pane').removeClass('active');
        $('#' + tabId).addClass('active');
    });
    
    // Deposit/Withdraw buttons
    $('.deposit-btn, .withdraw-btn').click(function() {
        var action = $(this).hasClass('deposit-btn') ? 'deposit' : 'withdraw';
        var currency = $(this).data('currency');
        
        // Open deposit/withdraw modal
        openWalletModal(action, currency);
    });
    
    // Load order book
    loadOrderBook();
    
    // Update market data every 5 seconds
    setInterval(updateMarketData, 5000);
});

function loadOrderBook() {
    $.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'crypto_exchange_get_order_book',
            trading_pair: 'BTC/USD'
        },
        success: function(response) {
            if (response.success) {
                updateOrderBookDisplay(response.data);
            }
        }
    });
}

function updateOrderBookDisplay(data) {
    // Update bids
    var bidsHtml = '';
    data.bids.forEach(function(bid) {
        bidsHtml += '<div class="order-item">';
        bidsHtml += '<span class="price">' + bid.price + '</span>';
        bidsHtml += '<span class="amount">' + bid.amount + '</span>';
        bidsHtml += '</div>';
    });
    $('#bids-list').html(bidsHtml);
    
    // Update asks
    var asksHtml = '';
    data.asks.forEach(function(ask) {
        asksHtml += '<div class="order-item">';
        asksHtml += '<span class="price">' + ask.price + '</span>';
        asksHtml += '<span class="amount">' + ask.amount + '</span>';
        asksHtml += '</div>';
    });
    $('#asks-list').html(asksHtml);
}

function updateMarketData() {
    $.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'crypto_exchange_get_market_data'
        },
        success: function(response) {
            if (response.success) {
                // Update price cards
                response.data.forEach(function(pair) {
                    $('.price-card').each(function() {
                        if ($(this).find('.pair-name').text() === pair.symbol) {
                            $(this).find('.price').text('$' + pair.price.toFixed(2));
                            $(this).find('.change').text((pair.change_24h >= 0 ? '+' : '') + pair.change_24h.toFixed(2) + '%');
                            $(this).find('.change').removeClass('positive negative').addClass(pair.change_24h >= 0 ? 'positive' : 'negative');
                        }
                    });
                });
            }
        }
    });
}

function openWalletModal(action, currency) {
    // Create modal for deposit/withdraw
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
    $('#wallet-form').submit(function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(action.charAt(0).toUpperCase() + action.slice(1) + ' request submitted successfully!');
                    modal.remove();
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });
    
    // Close modal
    $('.close').click(function() {
        modal.remove();
    });
}
</script>

<style>
.crypto-exchange-dashboard {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e1e5e9;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.logout-btn {
    background: #dc3545;
    color: white;
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    cursor: pointer;
}

.trading-section {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 20px;
    margin-bottom: 30px;
}

.trading-panel {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.order-form {
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.place-order-btn {
    width: 100%;
    background: #007bff;
    color: white;
    padding: 12px;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
}

.order-book {
    border-top: 1px solid #eee;
    padding-top: 20px;
}

.order-book-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.order-list {
    max-height: 200px;
    overflow-y: auto;
}

.order-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px solid #f0f0f0;
}

.chart-section {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.wallet-section,
.orders-section,
.market-data-section {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.wallets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.wallet-card {
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}

.wallet-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.wallet-type {
    background: #f8f9fa;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
}

.wallet-balance {
    margin-bottom: 15px;
}

.balance {
    font-size: 24px;
    font-weight: bold;
    color: #007bff;
}

.wallet-actions {
    display: flex;
    gap: 10px;
}

.deposit-btn,
.withdraw-btn {
    flex: 1;
    padding: 8px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.deposit-btn {
    background: #28a745;
    color: white;
}

.withdraw-btn {
    background: #ffc107;
    color: #212529;
}

.orders-tabs {
    display: flex;
    margin-bottom: 20px;
    border-bottom: 1px solid #e1e5e9;
}

.tab-btn {
    padding: 10px 20px;
    border: none;
    background: none;
    cursor: pointer;
    border-bottom: 2px solid transparent;
}

.tab-btn.active {
    border-bottom-color: #007bff;
    color: #007bff;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

.orders-table,
.trades-table {
    overflow-x: auto;
}

.orders-table table,
.trades-table table {
    width: 100%;
    border-collapse: collapse;
}

.orders-table th,
.orders-table td,
.trades-table th,
.trades-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e1e5e9;
}

.orders-table th,
.trades-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.cancel-order-btn {
    background: #dc3545;
    color: white;
    padding: 4px 8px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.market-prices {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}

.price-card {
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}

.pair-name {
    font-weight: 600;
    margin-bottom: 5px;
}

.price {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 5px;
}

.change {
    font-size: 14px;
    font-weight: 600;
}

.change.positive {
    color: #28a745;
}

.change.negative {
    color: #dc3545;
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

@media (max-width: 768px) {
    .trading-section {
        grid-template-columns: 1fr;
    }
    
    .wallets-grid {
        grid-template-columns: 1fr;
    }
    
    .market-prices {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
}
</style>
