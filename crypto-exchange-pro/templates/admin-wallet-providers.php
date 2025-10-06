<?php
/**
 * Wallet Service Providers Admin Interface
 */

if (!defined('ABSPATH')) {
    exit;
}

$wallet_providers = new Crypto_Exchange_Wallet_Providers();
$providers = $wallet_providers->get_all_providers();
$available_providers = $wallet_providers->get_available_providers();
$stats = $wallet_providers->get_provider_stats();
?>

<div class="wrap crypto-wallet-providers">
    <h1>Wallet Service Providers</h1>
    
    <div class="crypto-providers-header">
        <div class="providers-stats">
            <div class="stat-card">
                <h3>Total Providers</h3>
                <div class="stat-number"><?php echo count($providers); ?></div>
            </div>
            <div class="stat-card">
                <h3>Active Providers</h3>
                <div class="stat-number"><?php echo count(array_filter($providers, function($p) { return $p->status === 'active'; })); ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Transactions (24h)</h3>
                <div class="stat-number"><?php echo array_sum(array_column($stats, 'total_transactions')); ?></div>
            </div>
            <div class="stat-card">
                <h3>Success Rate</h3>
                <div class="stat-number">
                    <?php 
                    $total_tx = array_sum(array_column($stats, 'total_transactions'));
                    $success_tx = array_sum(array_column($stats, 'successful_transactions'));
                    echo $total_tx > 0 ? number_format(($success_tx / $total_tx) * 100, 1) . '%' : '0%';
                    ?>
                </div>
            </div>
        </div>
        
        <div class="providers-actions">
            <button class="button button-primary" id="add-provider-btn">Add New Provider</button>
            <button class="button" id="refresh-providers-btn">Refresh All</button>
            <button class="button" id="test-all-providers-btn">Test All Providers</button>
        </div>
    </div>
    
    <div class="crypto-providers-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#providers" class="nav-tab nav-tab-active">Providers</a>
            <a href="#statistics" class="nav-tab">Statistics</a>
            <a href="#transactions" class="nav-tab">Transactions</a>
            <a href="#settings" class="nav-tab">Settings</a>
        </nav>
        
        <!-- Providers Tab -->
        <div id="providers" class="tab-content">
            <div class="providers-grid">
                <?php foreach ($providers as $provider): ?>
                <div class="provider-card" data-provider-id="<?php echo $provider->id; ?>">
                    <div class="provider-header">
                        <div class="provider-info">
                            <h3><?php echo esc_html($provider->name); ?></h3>
                            <span class="provider-type"><?php echo esc_html(ucwords(str_replace('_', ' ', $provider->provider_type))); ?></span>
                        </div>
                        <div class="provider-status">
                            <span class="status-indicator <?php echo $provider->status; ?>"></span>
                            <span class="status-text"><?php echo ucfirst($provider->status); ?></span>
                        </div>
                    </div>
                    
                    <div class="provider-body">
                        <div class="provider-details">
                            <div class="detail-item">
                                <label>Priority:</label>
                                <span><?php echo $provider->priority; ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Network:</label>
                                <span><?php echo esc_html($provider->network); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Supported Coins:</label>
                                <span><?php echo count(json_decode($provider->supported_coins, true)); ?></span>
                            </div>
                        </div>
                        
                        <div class="provider-actions">
                            <button class="button button-small test-provider" data-provider-id="<?php echo $provider->id; ?>">Test</button>
                            <button class="button button-small edit-provider" data-provider-id="<?php echo $provider->id; ?>">Edit</button>
                            <button class="button button-small toggle-provider" data-provider-id="<?php echo $provider->id; ?>">
                                <?php echo $provider->status === 'active' ? 'Disable' : 'Enable'; ?>
                            </button>
                            <button class="button button-small delete-provider" data-provider-id="<?php echo $provider->id; ?>">Delete</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Statistics Tab -->
        <div id="statistics" class="tab-content" style="display: none;">
            <div class="stats-grid">
                <?php foreach ($stats as $stat): ?>
                <div class="stat-card">
                    <h3><?php echo esc_html($stat->name); ?></h3>
                    <div class="stat-details">
                        <div class="stat-item">
                            <label>Total Transactions:</label>
                            <span><?php echo number_format($stat->total_transactions); ?></span>
                        </div>
                        <div class="stat-item">
                            <label>Success Rate:</label>
                            <span>
                                <?php 
                                $success_rate = $stat->total_transactions > 0 ? ($stat->successful_transactions / $stat->total_transactions) * 100 : 0;
                                echo number_format($success_rate, 1) . '%';
                                ?>
                            </span>
                        </div>
                        <div class="stat-item">
                            <label>Total Volume:</label>
                            <span><?php echo number_format($stat->total_volume, 8); ?></span>
                        </div>
                        <div class="stat-item">
                            <label>Total Fees:</label>
                            <span><?php echo number_format($stat->total_fees, 8); ?></span>
                        </div>
                        <div class="stat-item">
                            <label>Avg Processing Time:</label>
                            <span><?php echo number_format($stat->avg_processing_time) . 's'; ?></span>
                        </div>
                        <div class="stat-item">
                            <label>Uptime:</label>
                            <span><?php echo number_format($stat->uptime, 1) . '%'; ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Transactions Tab -->
        <div id="transactions" class="tab-content" style="display: none;">
            <div class="transactions-filters">
                <select id="provider-filter">
                    <option value="">All Providers</option>
                    <?php foreach ($providers as $provider): ?>
                    <option value="<?php echo $provider->id; ?>"><?php echo esc_html($provider->name); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select id="status-filter">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                </select>
                
                <input type="date" id="date-from" placeholder="From Date">
                <input type="date" id="date-to" placeholder="To Date">
                
                <button class="button" id="filter-transactions">Filter</button>
            </div>
            
            <div class="transactions-table-container">
                <table class="wp-list-table widefat fixed striped" id="transactions-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Provider</th>
                            <th>User</th>
                            <th>Coin</th>
                            <th>Amount</th>
                            <th>Fee</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Transactions will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Settings Tab -->
        <div id="settings" class="tab-content" style="display: none;">
            <form id="wallet-providers-settings">
                <table class="form-table">
                    <tr>
                        <th scope="row">Default Provider Priority</th>
                        <td>
                            <input type="number" name="default_priority" value="1" min="1" max="100" class="small-text">
                            <p class="description">Default priority for new providers (1 = highest priority)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Auto Sync Interval</th>
                        <td>
                            <select name="auto_sync_interval">
                                <option value="300">5 minutes</option>
                                <option value="900">15 minutes</option>
                                <option value="1800">30 minutes</option>
                                <option value="3600">1 hour</option>
                            </select>
                            <p class="description">How often to sync provider data</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Transaction Timeout</th>
                        <td>
                            <input type="number" name="transaction_timeout" value="300" min="60" max="3600" class="small-text">
                            <p class="description">Transaction timeout in seconds</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Max Retry Attempts</th>
                        <td>
                            <input type="number" name="max_retry_attempts" value="3" min="1" max="10" class="small-text">
                            <p class="description">Maximum retry attempts for failed operations</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Webhooks</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_webhooks" value="1">
                                Enable webhook notifications
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Webhook URL</th>
                        <td>
                            <input type="url" name="webhook_url" class="regular-text" placeholder="https://your-site.com/webhook">
                            <p class="description">URL to receive webhook notifications</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Settings</button>
                </p>
            </form>
        </div>
    </div>
</div>

<!-- Add/Edit Provider Modal -->
<div id="provider-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title">Add New Provider</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="provider-form">
                <input type="hidden" id="provider-id" name="provider_id">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Provider Type</th>
                        <td>
                            <select id="provider-type" name="provider_type" required>
                                <option value="">Select Provider Type</option>
                                <?php foreach ($available_providers as $type => $provider): ?>
                                <option value="<?php echo esc_attr($type); ?>" data-features="<?php echo esc_attr(json_encode($provider['features'])); ?>" data-coins="<?php echo esc_attr(json_encode($provider['supported_coins'])); ?>">
                                    <?php echo esc_html($provider['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Provider Name</th>
                        <td>
                            <input type="text" id="provider-name" name="name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="text" id="api-key" name="api_key" class="regular-text">
                            <p class="description">API key for the provider (if required)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API Secret</th>
                        <td>
                            <input type="password" id="api-secret" name="api_secret" class="regular-text">
                            <p class="description">API secret for the provider (if required)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API Passphrase</th>
                        <td>
                            <input type="text" id="api-passphrase" name="api_passphrase" class="regular-text">
                            <p class="description">API passphrase for the provider (if required)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Webhook URL</th>
                        <td>
                            <input type="url" id="webhook-url" name="webhook_url" class="regular-text">
                            <p class="description">Webhook URL for notifications</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">RPC URL</th>
                        <td>
                            <input type="url" id="rpc-url" name="rpc_url" class="regular-text">
                            <p class="description">RPC URL for blockchain interaction</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Network</th>
                        <td>
                            <select id="network" name="network">
                                <option value="mainnet">Mainnet</option>
                                <option value="testnet">Testnet</option>
                                <option value="devnet">Devnet</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Priority</th>
                        <td>
                            <input type="number" id="priority" name="priority" value="1" min="1" max="100" class="small-text">
                            <p class="description">Provider priority (1 = highest priority)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Supported Coins</th>
                        <td>
                            <div id="supported-coins-container">
                                <!-- Coins will be populated based on provider type -->
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Features</th>
                        <td>
                            <div id="features-container">
                                <!-- Features will be populated based on provider type -->
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Fees</th>
                        <td>
                            <div class="fees-container">
                                <div class="fee-item">
                                    <label>Deposit Fee:</label>
                                    <input type="number" name="deposit_fee" step="0.00000001" min="0" class="small-text">
                                </div>
                                <div class="fee-item">
                                    <label>Withdrawal Fee:</label>
                                    <input type="number" name="withdrawal_fee" step="0.00000001" min="0" class="small-text">
                                </div>
                                <div class="fee-item">
                                    <label>Transaction Fee:</label>
                                    <input type="number" name="transaction_fee" step="0.00000001" min="0" class="small-text">
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Limits</th>
                        <td>
                            <div class="limits-container">
                                <div class="limit-item">
                                    <label>Min Deposit:</label>
                                    <input type="number" name="min_deposit" step="0.00000001" min="0" class="small-text">
                                </div>
                                <div class="limit-item">
                                    <label>Max Deposit:</label>
                                    <input type="number" name="max_deposit" step="0.01" min="0" class="small-text">
                                </div>
                                <div class="limit-item">
                                    <label>Min Withdrawal:</label>
                                    <input type="number" name="min_withdrawal" step="0.00000001" min="0" class="small-text">
                                </div>
                                <div class="limit-item">
                                    <label>Max Withdrawal:</label>
                                    <input type="number" name="max_withdrawal" step="0.01" min="0" class="small-text">
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Configuration</th>
                        <td>
                            <div class="config-container">
                                <div class="config-item">
                                    <label>Timeout (seconds):</label>
                                    <input type="number" name="timeout" value="30" min="5" max="300" class="small-text">
                                </div>
                                <div class="config-item">
                                    <label>Retry Attempts:</label>
                                    <input type="number" name="retry_attempts" value="3" min="1" max="10" class="small-text">
                                </div>
                                <div class="config-item">
                                    <label>Rate Limit:</label>
                                    <input type="number" name="rate_limit" value="1000" min="100" max="10000" class="small-text">
                                </div>
                                <div class="config-item">
                                    <label>
                                        <input type="checkbox" name="auto_sync" value="1"> Auto Sync
                                    </label>
                                </div>
                                <div class="config-item">
                                    <label>
                                        <input type="checkbox" name="cold_storage" value="1"> Cold Storage
                                    </label>
                                </div>
                                <div class="config-item">
                                    <label>
                                        <input type="checkbox" name="multi_sig" value="1"> Multi-Signature
                                    </label>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Status</th>
                        <td>
                            <select id="status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <div class="modal-footer">
                    <button type="submit" class="button button-primary">Save Provider</button>
                    <button type="button" class="button" id="cancel-provider">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').hide();
        $($(this).attr('href')).show();
    });
    
    // Add provider button
    $('#add-provider-btn').on('click', function() {
        $('#modal-title').text('Add New Provider');
        $('#provider-form')[0].reset();
        $('#provider-id').val('');
        $('#provider-modal').show();
    });
    
    // Edit provider button
    $('.edit-provider').on('click', function() {
        var providerId = $(this).data('provider-id');
        loadProviderData(providerId);
    });
    
    // Toggle provider status
    $('.toggle-provider').on('click', function() {
        var providerId = $(this).data('provider-id');
        var button = $(this);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_toggle_wallet_provider',
                provider_id: providerId,
                nonce: cryptoExchangeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Delete provider
    $('.delete-provider').on('click', function() {
        if (!confirm('Are you sure you want to delete this provider?')) {
            return;
        }
        
        var providerId = $(this).data('provider-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_delete_wallet_provider',
                provider_id: providerId,
                nonce: cryptoExchangeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Test provider
    $('.test-provider').on('click', function() {
        var providerId = $(this).data('provider-id');
        var button = $(this);
        
        button.prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_test_wallet_provider',
                provider_id: providerId,
                nonce: cryptoExchangeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Provider test successful: ' + response.data);
                } else {
                    alert('Provider test failed: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred during testing.');
            },
            complete: function() {
                button.prop('disabled', false).text('Test');
            }
        });
    });
    
    // Provider type change
    $('#provider-type').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var features = selectedOption.data('features') || [];
        var coins = selectedOption.data('coins') || [];
        
        // Update features
        var featuresContainer = $('#features-container');
        featuresContainer.empty();
        features.forEach(function(feature) {
                            featuresContainer.append('<label><input type="checkbox" name="features[]" value="' + feature + '" checked> ' + feature.replace('_', ' ').toUpperCase() + '</label><br>');
                        });
        
        // Update supported coins
        var coinsContainer = $('#supported-coins-container');
        coinsContainer.empty();
        coins.forEach(function(coin) {
                            coinsContainer.append('<label><input type="checkbox" name="supported_coins[]" value="' + coin + '" checked> ' + coin + '</label><br>');
                        });
    });
    
    // Save provider form
    $('#provider-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serializeArray();
        var data = {};
        
        $.each(formData, function(i, field) {
            if (field.name.endsWith('[]')) {
                var key = field.name.slice(0, -2);
                if (!data[key]) data[key] = [];
                data[key].push(field.value);
            } else {
                data[field.name] = field.value;
            }
        });
        
        var action = data.provider_id ? 'crypto_exchange_update_wallet_provider' : 'crypto_exchange_add_wallet_provider';
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: action,
                ...data,
                nonce: cryptoExchangeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Provider saved successfully!');
                    $('#provider-modal').hide();
                    location.reload();
                } else {
                    alert('Error saving provider: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Load provider data for editing
    function loadProviderData(providerId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_wallet_provider_data',
                provider_id: providerId,
                nonce: cryptoExchangeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var provider = response.data;
                    $('#modal-title').text('Edit Provider');
                    $('#provider-id').val(provider.id);
                    $('#provider-type').val(provider.provider_type);
                    $('#provider-name').val(provider.name);
                    $('#api-key').val(provider.api_key);
                    $('#api-secret').val(provider.api_secret);
                    $('#api-passphrase').val(provider.api_passphrase);
                    $('#webhook-url').val(provider.webhook_url);
                    $('#rpc-url').val(provider.rpc_url);
                    $('#network').val(provider.network);
                    $('#priority').val(provider.priority);
                    $('#status').val(provider.status);
                    
                    // Set fees
                    if (provider.fees) {
                        $('input[name="deposit_fee"]').val(provider.fees.deposit);
                        $('input[name="withdrawal_fee"]').val(provider.fees.withdrawal);
                        $('input[name="transaction_fee"]').val(provider.fees.transaction);
                    }
                    
                    // Set limits
                    if (provider.limits) {
                        $('input[name="min_deposit"]').val(provider.limits.min_deposit);
                        $('input[name="max_deposit"]').val(provider.limits.max_deposit);
                        $('input[name="min_withdrawal"]').val(provider.limits.min_withdrawal);
                        $('input[name="max_withdrawal"]').val(provider.limits.max_withdrawal);
                    }
                    
                    // Set config
                    if (provider.config) {
                        $('input[name="timeout"]').val(provider.config.timeout);
                        $('input[name="retry_attempts"]').val(provider.config.retry_attempts);
                        $('input[name="rate_limit"]').val(provider.config.rate_limit);
                        $('input[name="auto_sync"]').prop('checked', provider.config.auto_sync);
                        $('input[name="cold_storage"]').prop('checked', provider.config.cold_storage);
                        $('input[name="multi_sig"]').prop('checked', provider.config.multi_sig);
                    }
                    
                    // Trigger provider type change to load features and coins
                    $('#provider-type').trigger('change');
                    
                    // Set selected coins and features
                    if (provider.supported_coins) {
                        provider.supported_coins.forEach(function(coin) {
                            $('input[name="supported_coins[]"][value="' + coin + '"]').prop('checked', true);
                        });
                    }
                    
                    if (provider.features) {
                        provider.features.forEach(function(feature) {
                            $('input[name="features[]"][value="' + feature + '"]').prop('checked', true);
                        });
                    }
                    
                    $('#provider-modal').show();
                } else {
                    alert('Error loading provider data: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred loading provider data.');
            }
        });
    }
    
    // Close modal
    $('.close, #cancel-provider').on('click', function() {
        $('#provider-modal').hide();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if (e.target.id === 'provider-modal') {
            $('#provider-modal').hide();
        }
    });
    
    // Filter transactions
    $('#filter-transactions').on('click', function() {
        loadTransactions();
    });
    
    // Load transactions
    function loadTransactions() {
        var providerId = $('#provider-filter').val();
        var status = $('#status-filter').val();
        var dateFrom = $('#date-from').val();
        var dateTo = $('#date-to').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_transaction_history',
                provider_id: providerId,
                status: status,
                date_from: dateFrom,
                date_to: dateTo,
                nonce: cryptoExchangeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var tbody = $('#transactions-table tbody');
                    tbody.empty();
                    
                    if (response.data.length > 0) {
                        response.data.forEach(function(tx) {
                            tbody.append('<tr>' +
                                '<td>' + tx.id + '</td>' +
                                '<td>' + tx.provider_name + '</td>' +
                                '<td>' + tx.user_email + '</td>' +
                                '<td>' + tx.coin + '</td>' +
                                '<td>' + parseFloat(tx.amount).toFixed(8) + '</td>' +
                                '<td>' + parseFloat(tx.fee).toFixed(8) + '</td>' +
                                '<td><span class="status-' + tx.status + '">' + tx.status + '</span></td>' +
                                '<td>' + tx.created_at + '</td>' +
                                '<td><button class="button button-small">View</button></td>' +
                                '</tr>');
                        });
                    } else {
                        tbody.append('<tr><td colspan="9">No transactions found</td></tr>');
                    }
                } else {
                    alert('Error loading transactions: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred loading transactions.');
            }
        });
    }
    
    // Load transactions on tab show
    $('#transactions').on('click', function() {
        loadTransactions();
    });
});
</script>

<style>
.crypto-wallet-providers {
    max-width: 1200px;
}

.crypto-providers-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

.providers-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    flex: 1;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 14px;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.providers-actions {
    display: flex;
    gap: 10px;
}

.providers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.provider-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.provider-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.provider-info h3 {
    margin: 0;
    color: #333;
}

.provider-type {
    color: #666;
    font-size: 12px;
}

.provider-status {
    display: flex;
    align-items: center;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
}

.status-indicator.active {
    background-color: #4CAF50;
}

.status-indicator.inactive {
    background-color: #f44336;
}

.status-indicator.maintenance {
    background-color: #ff9800;
}

.provider-details {
    margin-bottom: 15px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.detail-item label {
    font-weight: bold;
    color: #666;
}

.provider-actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stat-details {
    margin-top: 15px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    padding: 5px 0;
    border-bottom: 1px solid #eee;
}

.stat-item label {
    font-weight: bold;
    color: #666;
}

.transactions-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.transactions-filters select,
.transactions-filters input {
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.transactions-table-container {
    overflow-x: auto;
}

.fees-container,
.limits-container,
.config-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.fee-item,
.limit-item,
.config-item {
    display: flex;
    flex-direction: column;
}

.fee-item label,
.limit-item label,
.config-item label {
    font-weight: bold;
    margin-bottom: 5px;
}

.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 8px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.close:hover {
    color: #000;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.modal-footer .button {
    margin-left: 10px;
}

.form-table th {
    width: 200px;
}

.form-table td {
    padding: 15px 10px;
}

.form-table input[type="text"], 
.form-table input[type="password"], 
.form-table input[type="url"], 
.form-table input[type="number"], 
.form-table select, 
.form-table textarea {
    width: 100%;
    max-width: 400px;
}

.form-table .small-text {
    width: 100px;
}

.form-table .description {
    font-style: italic;
    color: #666;
    margin-top: 5px;
}

.status-pending {
    color: #ff9800;
    font-weight: bold;
}

.status-completed {
    color: #4CAF50;
    font-weight: bold;
}

.status-failed {
    color: #f44336;
    font-weight: bold;
}
</style>