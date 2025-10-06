<?php
/**
 * Admin Liquidity Providers Page
 */

if (!defined('ABSPATH')) {
    exit;
}

$liquidity_providers = new Crypto_Exchange_Liquidity_Providers();
$providers = $liquidity_providers->get_all_providers();
$stats = $liquidity_providers->get_provider_stats();
?>

<div class="wrap">
    <h1>Liquidity Providers Management</h1>
    
    <!-- Statistics -->
    <div class="crypto-stats">
        <div class="stat-box">
            <h3>Total Providers</h3>
            <p><?php echo count($providers); ?></p>
        </div>
        <div class="stat-box">
            <h3>Active Providers</h3>
            <p><?php echo count(array_filter($providers, function($p) { return $p->status === 'active'; })); ?></p>
        </div>
        <div class="stat-box">
            <h3>Total Orders (24h)</h3>
            <p><?php echo array_sum(array_column($stats, 'total_orders')); ?></p>
        </div>
        <div class="stat-box">
            <h3>Success Rate</h3>
            <p><?php 
                $total_orders = array_sum(array_column($stats, 'total_orders'));
                $successful_orders = array_sum(array_column($stats, 'successful_orders'));
                echo $total_orders > 0 ? number_format(($successful_orders / $total_orders) * 100, 2) . '%' : '0%';
            ?></p>
        </div>
    </div>
    
    <!-- Add Provider Button -->
    <div class="add-provider-section">
        <button id="add-provider-btn" class="button button-primary">Add New Provider</button>
    </div>
    
    <!-- Providers Table -->
    <div class="providers-table-section">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Exchange</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Trading Fee</th>
                    <th>Max Order Size</th>
                    <th>Supported Pairs</th>
                    <th>Orders (24h)</th>
                    <th>Success Rate</th>
                    <th>Avg Latency</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($providers as $provider): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($provider->name); ?></strong>
                            <?php if ($provider->sandbox): ?>
                                <span class="sandbox-badge">Sandbox</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="exchange-badge <?php echo esc_attr($provider->exchange); ?>">
                                <?php echo esc_html(ucfirst($provider->exchange)); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo esc_attr($provider->status); ?>">
                                <?php echo esc_html(ucfirst($provider->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($provider->priority); ?></td>
                        <td><?php echo number_format($provider->trading_fee, 4); ?>%</td>
                        <td><?php echo number_format($provider->max_order_size, 2); ?></td>
                        <td>
                            <?php 
                            $pairs = explode(',', $provider->supported_pairs);
                            echo count($pairs) > 3 ? count($pairs) . ' pairs' : esc_html($provider->supported_pairs);
                            ?>
                        </td>
                        <td>
                            <?php 
                            $provider_stats = array_filter($stats, function($s) use ($provider) { 
                                return $s->id == $provider->id; 
                            });
                            $provider_stat = reset($provider_stats);
                            echo $provider_stat ? $provider_stat->total_orders : '0';
                            ?>
                        </td>
                        <td>
                            <?php 
                            if ($provider_stat && $provider_stat->total_orders > 0) {
                                $success_rate = ($provider_stat->successful_orders / $provider_stat->total_orders) * 100;
                                echo number_format($success_rate, 1) . '%';
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            echo $provider_stat && $provider_stat->avg_latency ? 
                                number_format($provider_stat->avg_latency, 0) . 'ms' : 'N/A';
                            ?>
                        </td>
                        <td>
                            <button class="button button-small edit-provider-btn" data-provider-id="<?php echo esc_attr($provider->id); ?>">Edit</button>
                            <button class="button button-small test-provider-btn" data-provider-id="<?php echo esc_attr($provider->id); ?>">Test</button>
                            <button class="button button-small sync-provider-btn" data-provider-id="<?php echo esc_attr($provider->id); ?>">Sync</button>
                            <button class="button button-small button-link-delete delete-provider-btn" data-provider-id="<?php echo esc_attr($provider->id); ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Provider Modal -->
<div id="provider-modal" class="provider-modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modal-title">Add New Provider</h2>
        
        <form id="provider-form">
            <input type="hidden" id="provider-id" name="provider_id" value="">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="name">Provider Name *</label>
                    </th>
                    <td>
                        <input type="text" id="name" name="name" class="regular-text" required>
                        <p class="description">Display name for this provider</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="exchange">Exchange *</label>
                    </th>
                    <td>
                        <select id="exchange" name="exchange" required>
                            <option value="">Select Exchange</option>
                            <option value="binance">Binance</option>
                            <option value="coinbase">Coinbase Pro</option>
                            <option value="kraken">Kraken</option>
                            <option value="huobi">Huobi</option>
                        </select>
                        <p class="description">Exchange platform</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="api_key">API Key *</label>
                    </th>
                    <td>
                        <input type="text" id="api_key" name="api_key" class="regular-text" required>
                        <p class="description">API key from the exchange</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="api_secret">API Secret *</label>
                    </th>
                    <td>
                        <input type="password" id="api_secret" name="api_secret" class="regular-text" required>
                        <p class="description">API secret from the exchange</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="api_passphrase">API Passphrase</label>
                    </th>
                    <td>
                        <input type="password" id="api_passphrase" name="api_passphrase" class="regular-text">
                        <p class="description">API passphrase (required for Coinbase Pro)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sandbox">Sandbox Mode</label>
                    </th>
                    <td>
                        <input type="checkbox" id="sandbox" name="sandbox" value="1">
                        <label for="sandbox">Use sandbox/testnet</label>
                        <p class="description">Enable for testing with sandbox APIs</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="priority">Priority</label>
                    </th>
                    <td>
                        <input type="number" id="priority" name="priority" class="small-text" value="1" min="1" max="100">
                        <p class="description">Priority for order routing (1 = highest priority)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="max_daily_volume">Max Daily Volume</label>
                    </th>
                    <td>
                        <input type="number" id="max_daily_volume" name="max_daily_volume" class="regular-text" step="0.00000001" value="1000000">
                        <p class="description">Maximum daily trading volume</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="max_order_size">Max Order Size</label>
                    </th>
                    <td>
                        <input type="number" id="max_order_size" name="max_order_size" class="regular-text" step="0.00000001" value="10000">
                        <p class="description">Maximum single order size</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="min_order_size">Min Order Size</label>
                    </th>
                    <td>
                        <input type="number" id="min_order_size" name="min_order_size" class="regular-text" step="0.00000001" value="0.00000001">
                        <p class="description">Minimum single order size</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="trading_fee">Trading Fee (%)</label>
                    </th>
                    <td>
                        <input type="number" id="trading_fee" name="trading_fee" class="regular-text" step="0.0001" value="0.1">
                        <p class="description">Trading fee percentage</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="withdrawal_fee">Withdrawal Fee</label>
                    </th>
                    <td>
                        <input type="number" id="withdrawal_fee" name="withdrawal_fee" class="regular-text" step="0.00000001" value="0.001">
                        <p class="description">Withdrawal fee amount</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="supported_pairs">Supported Pairs</label>
                    </th>
                    <td>
                        <textarea id="supported_pairs" name="supported_pairs" class="large-text" rows="3" placeholder="BTC/USD,ETH/USD,LTC/USD or ALL for all pairs"></textarea>
                        <p class="description">Comma-separated list of supported trading pairs</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="endpoints">Custom Endpoints</label>
                    </th>
                    <td>
                        <textarea id="endpoints" name="endpoints" class="large-text" rows="3" placeholder='{"base_url": "https://api.example.com"}'></textarea>
                        <p class="description">JSON configuration for custom endpoints</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="rate_limit">Rate Limit</label>
                    </th>
                    <td>
                        <input type="number" id="rate_limit" name="rate_limit" class="small-text" value="1000">
                        <p class="description">API requests per minute</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="timeout">Timeout (seconds)</label>
                    </th>
                    <td>
                        <input type="number" id="timeout" name="timeout" class="small-text" value="30">
                        <p class="description">API request timeout</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="retry_attempts">Retry Attempts</label>
                    </th>
                    <td>
                        <input type="number" id="retry_attempts" name="retry_attempts" class="small-text" value="3">
                        <p class="description">Number of retry attempts for failed requests</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="status">Status</label>
                    </th>
                    <td>
                        <select id="status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <p class="description">Provider status</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">Save Provider</button>
                <button type="button" class="button cancel-btn">Cancel</button>
            </p>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Add provider button
    $('#add-provider-btn').click(function() {
        $('#modal-title').text('Add New Provider');
        $('#provider-form')[0].reset();
        $('#provider-id').val('');
        $('#provider-modal').show();
    });
    
    // Edit provider button
    $('.edit-provider-btn').click(function() {
        var providerId = $(this).data('provider-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_liquidity_provider_data',
                provider_id: providerId,
                nonce: '<?php echo wp_create_nonce('crypto_exchange_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var provider = response.data;
                    $('#modal-title').text('Edit Provider');
                    $('#provider-id').val(provider.id);
                    $('#name').val(provider.name);
                    $('#exchange').val(provider.exchange);
                    $('#api_key').val(provider.api_key);
                    $('#api_secret').val(provider.api_secret);
                    $('#api_passphrase').val(provider.api_passphrase);
                    $('#sandbox').prop('checked', provider.sandbox == 1);
                    $('#priority').val(provider.priority);
                    $('#max_daily_volume').val(provider.max_daily_volume);
                    $('#max_order_size').val(provider.max_order_size);
                    $('#min_order_size').val(provider.min_order_size);
                    $('#trading_fee').val(provider.trading_fee);
                    $('#withdrawal_fee').val(provider.withdrawal_fee);
                    $('#supported_pairs').val(provider.supported_pairs);
                    $('#endpoints').val(provider.endpoints);
                    $('#rate_limit').val(provider.rate_limit);
                    $('#timeout').val(provider.timeout);
                    $('#retry_attempts').val(provider.retry_attempts);
                    $('#status').val(provider.status);
                    $('#provider-modal').show();
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });
    
    // Test provider button
    $('.test-provider-btn').click(function() {
        var providerId = $(this).data('provider-id');
        var $btn = $(this);
        
        $btn.prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_test_liquidity_provider',
                provider_id: providerId,
                nonce: '<?php echo wp_create_nonce('crypto_exchange_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Test successful: ' + response.data);
                } else {
                    alert('Test failed: ' + response.data);
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text('Test');
            }
        });
    });
    
    // Sync provider button
    $('.sync-provider-btn').click(function() {
        var providerId = $(this).data('provider-id');
        var $btn = $(this);
        
        $btn.prop('disabled', true).text('Syncing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_sync_liquidity_provider',
                provider_id: providerId,
                nonce: '<?php echo wp_create_nonce('crypto_exchange_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Sync successful: ' + response.data);
                } else {
                    alert('Sync failed: ' + response.data);
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text('Sync');
            }
        });
    });
    
    // Delete provider button
    $('.delete-provider-btn').click(function() {
        var providerId = $(this).data('provider-id');
        
        if (confirm('Are you sure you want to delete this provider? This action cannot be undone.')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'crypto_exchange_delete_liquidity_provider',
                    provider_id: providerId,
                    nonce: '<?php echo wp_create_nonce('crypto_exchange_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Provider deleted successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        }
    });
    
    // Form submission
    $('#provider-form').submit(function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var isEdit = $('#provider-id').val() !== '';
        var action = isEdit ? 'crypto_exchange_update_liquidity_provider' : 'crypto_exchange_add_liquidity_provider';
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: action,
                ...formData,
                nonce: '<?php echo wp_create_nonce('crypto_exchange_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    $('#provider-modal').hide();
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });
    
    // Close modal
    $('.close, .cancel-btn').click(function() {
        $('#provider-modal').hide();
    });
    
    // Close modal when clicking outside
    $(window).click(function(e) {
        if (e.target.id === 'provider-modal') {
            $('#provider-modal').hide();
        }
    });
});
</script>

<style>
.crypto-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-box {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stat-box h3 {
    margin: 0 0 10px 0;
    color: #23282d;
    font-size: 14px;
    font-weight: 600;
}

.stat-box p {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
}

.add-provider-section {
    margin: 20px 0;
}

.providers-table-section {
    margin-top: 20px;
}

.sandbox-badge {
    background: #ffc107;
    color: #212529;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
    font-weight: bold;
    text-transform: uppercase;
    margin-left: 5px;
}

.exchange-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.exchange-badge.binance {
    background: #f0b90b;
    color: #000;
}

.exchange-badge.coinbase {
    background: #0052ff;
    color: #fff;
}

.exchange-badge.kraken {
    background: #5914cc;
    color: #fff;
}

.exchange-badge.huobi {
    background: #00d4aa;
    color: #000;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.inactive {
    background: #f8d7da;
    color: #721c24;
}

.provider-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: #fff;
    border-radius: 4px;
    padding: 20px;
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.close {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.close:hover {
    color: #000;
}

#modal-title {
    margin-top: 0;
    margin-bottom: 20px;
}

.form-table th {
    width: 200px;
}

.form-table td {
    padding: 10px 0;
}

.submit {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.submit .button {
    margin-right: 10px;
}

@media (max-width: 768px) {
    .crypto-stats {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .modal-content {
        width: 95%;
        padding: 15px;
    }
    
    .form-table th {
        width: auto;
        display: block;
        margin-bottom: 5px;
    }
    
    .form-table td {
        display: block;
        padding: 0 0 15px 0;
    }
}
</style>
