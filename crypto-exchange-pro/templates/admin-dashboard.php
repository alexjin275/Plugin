<?php
/**
 * Comprehensive Admin Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

$admin_config = new Crypto_Exchange_Admin_Config();
$wallet_providers = new Crypto_Exchange_Wallet_Providers();
$error_handler = new Crypto_Exchange_Error_Handler();

$settings = $admin_config->get_settings();
$modules = $admin_config->get_modules();
$system_health = $error_handler->get_system_health();
$provider_stats = $wallet_providers->get_provider_stats();
$conflicts = $error_handler->check_conflicts();

// Get recent activity
global $wpdb;
$recent_orders = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}crypto_orders ORDER BY created_at DESC LIMIT 5"
);
$recent_users = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}crypto_users ORDER BY created_at DESC LIMIT 5"
);
$recent_errors = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}crypto_errors ORDER BY timestamp DESC LIMIT 5"
);
?>

<div class="wrap crypto-admin-dashboard">
    <h1>Crypto Exchange Pro - Admin Dashboard</h1>
    
    <!-- System Health Status -->
    <div class="system-health <?php echo $system_health['overall']; ?>">
        <div class="health-header">
            <h2>System Health</h2>
            <span class="health-status status-<?php echo $system_health['overall']; ?>">
                <?php echo ucfirst($system_health['overall']); ?>
            </span>
        </div>
        <div class="health-metrics">
            <div class="metric">
                <span class="metric-label">Errors (24h):</span>
                <span class="metric-value"><?php echo $system_health['components']['errors_24h']; ?></span>
            </div>
            <div class="metric">
                <span class="metric-label">Unresolved Conflicts:</span>
                <span class="metric-value"><?php echo $system_health['components']['unresolved_conflicts']; ?></span>
            </div>
            <div class="metric">
                <span class="metric-label">Database:</span>
                <span class="metric-value status-<?php echo $system_health['components']['database_status']; ?>">
                    <?php echo ucfirst($system_health['components']['database_status']); ?>
                </span>
            </div>
            <div class="metric">
                <span class="metric-label">API:</span>
                <span class="metric-value status-<?php echo $system_health['components']['api_status']; ?>">
                    <?php echo ucfirst($system_health['components']['api_status']); ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <a href="<?php echo admin_url('admin.php?page=crypto-exchange-config'); ?>" class="button button-primary">
                <span class="dashicons dashicons-admin-settings"></span>
                System Configuration
            </a>
            <a href="<?php echo admin_url('admin.php?page=crypto-exchange-wallet-providers'); ?>" class="button button-primary">
                <span class="dashicons dashicons-wallet"></span>
                Wallet Providers
            </a>
            <a href="<?php echo admin_url('admin.php?page=crypto-exchange-coins'); ?>" class="button button-primary">
                <span class="dashicons dashicons-money-alt"></span>
                Coin Management
            </a>
            <a href="<?php echo admin_url('admin.php?page=crypto-exchange-liquidity'); ?>" class="button button-primary">
                <span class="dashicons dashicons-chart-area"></span>
                Liquidity Providers
            </a>
            <button class="button" id="test-system-btn">
                <span class="dashicons dashicons-admin-tools"></span>
                Test System
            </button>
            <button class="button" id="clear-errors-btn">
                <span class="dashicons dashicons-trash"></span>
                Clear Errors
            </button>
        </div>
    </div>
    
    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Module Status -->
        <div class="dashboard-card">
            <h3>Module Status</h3>
            <div class="module-list">
                <?php foreach ($modules as $module_id => $module_data): ?>
                <div class="module-item">
                    <div class="module-info">
                        <span class="module-name"><?php echo esc_html($module_data['name']); ?></span>
                        <span class="module-status status-<?php echo ($settings['modules'][$module_id] ?? false) ? 'active' : 'inactive'; ?>">
                            <?php echo ($settings['modules'][$module_id] ?? false) ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    <div class="module-actions">
                        <button class="button button-small toggle-module" data-module="<?php echo esc_attr($module_id); ?>">
                            <?php echo ($settings['modules'][$module_id] ?? false) ? 'Disable' : 'Enable'; ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Wallet Providers -->
        <div class="dashboard-card">
            <h3>Wallet Providers</h3>
            <div class="provider-stats">
                <?php foreach ($provider_stats as $stat): ?>
                <div class="provider-item">
                    <div class="provider-info">
                        <span class="provider-name"><?php echo esc_html($stat->name); ?></span>
                        <span class="provider-status status-<?php echo $stat->status; ?>">
                            <?php echo ucfirst($stat->status); ?>
                        </span>
                    </div>
                    <div class="provider-metrics">
                        <span class="metric">TX: <?php echo number_format($stat->total_transactions); ?></span>
                        <span class="metric">Success: <?php echo number_format($stat->successful_transactions); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="dashboard-card">
            <h3>Recent Orders</h3>
            <div class="activity-list">
                <?php foreach ($recent_orders as $order): ?>
                <div class="activity-item">
                    <div class="activity-info">
                        <span class="activity-type">Order #<?php echo $order->id; ?></span>
                        <span class="activity-details"><?php echo $order->pair; ?> - $<?php echo number_format($order->price, 2); ?></span>
                    </div>
                    <span class="activity-time"><?php echo human_time_diff(strtotime($order->created_at)); ?> ago</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Recent Users -->
        <div class="dashboard-card">
            <h3>Recent Users</h3>
            <div class="activity-list">
                <?php foreach ($recent_users as $user): ?>
                <div class="activity-item">
                    <div class="activity-info">
                        <span class="activity-type"><?php echo get_userdata($user->user_id)->user_email; ?></span>
                        <span class="activity-details">KYC: <?php echo ucfirst($user->kyc_status); ?></span>
                    </div>
                    <span class="activity-time"><?php echo human_time_diff(strtotime($user->created_at)); ?> ago</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Recent Errors -->
        <div class="dashboard-card">
            <h3>Recent Errors</h3>
            <div class="error-list">
                <?php foreach ($recent_errors as $error): ?>
                <div class="error-item level-<?php echo $error->level; ?>">
                    <div class="error-info">
                        <span class="error-level"><?php echo ucfirst($error->level); ?></span>
                        <span class="error-message"><?php echo esc_html(substr($error->message, 0, 50)); ?>...</span>
                    </div>
                    <span class="error-time"><?php echo human_time_diff(strtotime($error->timestamp)); ?> ago</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- System Statistics -->
        <div class="dashboard-card">
            <h3>System Statistics</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label">Total Users:</span>
                    <span class="stat-value"><?php echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}crypto_users"); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Active Orders:</span>
                    <span class="stat-value"><?php echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}crypto_orders WHERE status = 'active'"); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total Volume (24h):</span>
                    <span class="stat-value">$<?php echo number_format($wpdb->get_var("SELECT SUM(total) FROM {$wpdb->prefix}crypto_trades WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)") ?: 0, 2); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Wallet Addresses:</span>
                    <span class="stat-value"><?php echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}crypto_wallet_addresses"); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Conflicts -->
        <?php if (!empty($conflicts)): ?>
        <div class="dashboard-card conflicts-card">
            <h3>System Conflicts</h3>
            <div class="conflicts-list">
                <?php foreach ($conflicts as $conflict): ?>
                <div class="conflict-item severity-<?php echo $conflict['severity']; ?>">
                    <div class="conflict-info">
                        <span class="conflict-type"><?php echo ucfirst(str_replace('_', ' ', $conflict['type'])); ?></span>
                        <span class="conflict-message"><?php echo esc_html($conflict['message']); ?></span>
                    </div>
                    <div class="conflict-actions">
                        <button class="button button-small resolve-conflict" data-conflict-id="<?php echo $conflict['id'] ?? 0; ?>">
                            Resolve
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- System Test Modal -->
    <div id="system-test-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>System Test Results</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="test-results">
                    <div class="test-loading">
                        <span class="spinner is-active"></span>
                        Running system tests...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle module
    $('.toggle-module').on('click', function() {
        var module = $(this).data('module');
        var button = $(this);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_toggle_module',
                module: module,
                enabled: !$(this).text().includes('Enable'),
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
    
    // Test system
    $('#test-system-btn').on('click', function() {
        $('#system-test-modal').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_test_system',
                nonce: cryptoExchangeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var results = response.data;
                    var html = '<div class="test-results">';
                    
                    for (var component in results) {
                        var test = results[component];
                        html += '<div class="test-item status-' + test.status + '">';
                        html += '<h4>' + component.charAt(0).toUpperCase() + component.slice(1) + '</h4>';
                        html += '<p>' + test.message + '</p>';
                        if (test.results) {
                            html += '<ul>';
                            for (var i = 0; i < test.results.length; i++) {
                                var result = test.results[i];
                                html += '<li class="status-' + result.status + '">';
                                html += result.provider + ': ' + result.message;
                                html += '</li>';
                            }
                            html += '</ul>';
                        }
                        html += '</div>';
                    }
                    
                    html += '</div>';
                    $('#test-results').html(html);
                } else {
                    $('#test-results').html('<div class="test-error">Test failed: ' + response.data + '</div>');
                }
            },
            error: function() {
                $('#test-results').html('<div class="test-error">An error occurred during testing.</div>');
            }
        });
    });
    
    // Clear errors
    $('#clear-errors-btn').on('click', function() {
        if (!confirm('Are you sure you want to clear all errors?')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_clear_errors',
                nonce: cryptoExchangeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Errors cleared successfully!');
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
    
    // Resolve conflict
    $('.resolve-conflict').on('click', function() {
        var conflictId = $(this).data('conflict-id');
        var resolution = prompt('Please describe how you resolved this conflict:');
        
        if (resolution) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'crypto_exchange_resolve_conflict',
                    conflict_id: conflictId,
                    resolution: resolution,
                    nonce: cryptoExchangeAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Conflict resolved successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        }
    });
    
    // Close modal
    $('.close').on('click', function() {
        $('#system-test-modal').hide();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if (e.target.id === 'system-test-modal') {
            $('#system-test-modal').hide();
        }
    });
});
</script>

<style>
.crypto-admin-dashboard {
    max-width: 1400px;
}

.system-health {
    background: #f9f9f9;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    border-left: 4px solid #ddd;
}

.system-health.good {
    border-left-color: #4CAF50;
}

.system-health.warning {
    border-left-color: #ff9800;
}

.system-health.critical {
    border-left-color: #f44336;
}

.health-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.health-status {
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 12px;
}

.status-good {
    background: #4CAF50;
    color: white;
}

.status-warning {
    background: #ff9800;
    color: white;
}

.status-critical {
    background: #f44336;
    color: white;
}

.health-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.metric {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    background: white;
    border-radius: 4px;
}

.quick-actions {
    margin-bottom: 30px;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.action-buttons .button {
    display: flex;
    align-items: center;
    gap: 8px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.dashboard-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.dashboard-card h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 10px;
}

.module-list, .provider-stats, .activity-list, .error-list, .conflicts-list {
    max-height: 300px;
    overflow-y: auto;
}

.module-item, .provider-item, .activity-item, .error-item, .conflict-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.module-item:last-child, .provider-item:last-child, .activity-item:last-child, .error-item:last-child, .conflict-item:last-child {
    border-bottom: none;
}

.module-info, .provider-info, .activity-info, .error-info, .conflict-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.module-name, .provider-name, .activity-type, .error-level, .conflict-type {
    font-weight: bold;
    color: #333;
}

.module-status, .provider-status {
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 12px;
    text-transform: uppercase;
}

.status-active {
    background: #4CAF50;
    color: white;
}

.status-inactive {
    background: #f44336;
    color: white;
}

.activity-details, .error-message, .conflict-message {
    color: #666;
    font-size: 12px;
}

.activity-time, .error-time {
    color: #999;
    font-size: 11px;
}

.provider-metrics {
    display: flex;
    gap: 10px;
}

.metric {
    font-size: 11px;
    color: #666;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.stat-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
}

.stat-label {
    font-size: 12px;
    color: #666;
}

.stat-value {
    font-size: 18px;
    font-weight: bold;
    color: #333;
}

.conflicts-card {
    border-left: 4px solid #ff9800;
}

.conflict-item.severity-critical {
    border-left: 3px solid #f44336;
    padding-left: 10px;
}

.conflict-item.severity-warning {
    border-left: 3px solid #ff9800;
    padding-left: 10px;
}

.conflict-item.severity-info {
    border-left: 3px solid #2196F3;
    padding-left: 10px;
}

.error-item.level-critical {
    border-left: 3px solid #f44336;
    padding-left: 10px;
}

.error-item.level-error {
    border-left: 3px solid #ff5722;
    padding-left: 10px;
}

.error-item.level-warning {
    border-left: 3px solid #ff9800;
    padding-left: 10px;
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

.test-results {
    max-height: 400px;
    overflow-y: auto;
}

.test-item {
    margin-bottom: 20px;
    padding: 15px;
    border-radius: 4px;
    border-left: 4px solid #ddd;
}

.test-item.status-success {
    background: #f1f8e9;
    border-left-color: #4CAF50;
}

.test-item.status-error {
    background: #ffebee;
    border-left-color: #f44336;
}

.test-item h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.test-item p {
    margin: 0 0 10px 0;
    color: #666;
}

.test-item ul {
    margin: 0;
    padding-left: 20px;
}

.test-item li {
    margin-bottom: 5px;
}

.test-item li.status-success {
    color: #4CAF50;
}

.test-item li.status-error {
    color: #f44336;
}

.test-loading {
    text-align: center;
    padding: 20px;
}

.test-error {
    color: #f44336;
    text-align: center;
    padding: 20px;
}
</style>