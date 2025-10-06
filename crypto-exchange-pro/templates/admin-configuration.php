<?php
/**
 * Advanced Admin Configuration Interface
 */

if (!defined('ABSPATH')) {
    exit;
}

$admin_config = new Crypto_Exchange_Admin_Config();
$settings = $admin_config->get_settings();
$modules = $admin_config->get_modules();
$backups = $admin_config->get_backups();
?>

<div class="wrap crypto-admin-config">
    <h1>Crypto Exchange Pro - Advanced Configuration</h1>
    
    <div class="crypto-config-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#modules" class="nav-tab nav-tab-active">Modules</a>
            <a href="#general" class="nav-tab">General</a>
            <a href="#trading" class="nav-tab">Trading</a>
            <a href="#wallet" class="nav-tab">Wallet</a>
            <a href="#security" class="nav-tab">Security</a>
            <a href="#kyc" class="nav-tab">KYC</a>
            <a href="#notifications" class="nav-tab">Notifications</a>
            <a href="#api" class="nav-tab">API</a>
            <a href="#liquidity" class="nav-tab">Liquidity</a>
            <a href="#backup" class="nav-tab">Backup/Restore</a>
        </nav>
        
        <!-- Modules Tab -->
        <div id="modules" class="tab-content">
            <div class="crypto-modules-grid">
                <?php foreach ($modules as $module_id => $module_data): ?>
                <div class="module-card" data-module="<?php echo esc_attr($module_id); ?>">
                    <div class="module-header">
                        <h3><?php echo esc_html($module_data['name']); ?></h3>
                        <div class="module-toggle">
                            <label class="switch">
                                <input type="checkbox" 
                                       class="module-toggle-input" 
                                       data-module="<?php echo esc_attr($module_id); ?>"
                                       <?php checked($settings['modules'][$module_id] ?? false); ?>
                                       <?php disabled($module_data['required']); ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    <div class="module-body">
                        <p class="module-description"><?php echo esc_html($module_data['description']); ?></p>
                        <div class="module-status">
                            <span class="status-indicator <?php echo ($settings['modules'][$module_id] ?? false) ? 'active' : 'inactive'; ?>"></span>
                            <span class="status-text">
                                <?php echo ($settings['modules'][$module_id] ?? false) ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                        <?php if ($module_data['required']): ?>
                        <div class="module-required">Required Module</div>
                        <?php endif; ?>
                        <div class="module-actions">
                            <button class="button button-small test-module" data-module="<?php echo esc_attr($module_id); ?>">Test</button>
                            <button class="button button-small module-status-btn" data-module="<?php echo esc_attr($module_id); ?>">Status</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- General Tab -->
        <div id="general" class="tab-content" style="display: none;">
            <form class="config-form" data-section="general">
                <table class="form-table">
                    <tr>
                        <th scope="row">Exchange Name</th>
                        <td>
                            <input type="text" name="exchange_name" 
                                   value="<?php echo esc_attr($settings['general']['exchange_name']); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Maintenance Mode</th>
                        <td>
                            <label>
                                <input type="checkbox" name="maintenance_mode" 
                                       <?php checked($settings['general']['maintenance_mode']); ?>>
                                Enable maintenance mode
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Debug Mode</th>
                        <td>
                            <label>
                                <input type="checkbox" name="debug_mode" 
                                       <?php checked($settings['general']['debug_mode']); ?>>
                                Enable debug mode
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Log Level</th>
                        <td>
                            <select name="log_level">
                                <option value="error" <?php selected($settings['general']['log_level'], 'error'); ?>>Error</option>
                                <option value="warning" <?php selected($settings['general']['log_level'], 'warning'); ?>>Warning</option>
                                <option value="info" <?php selected($settings['general']['log_level'], 'info'); ?>>Info</option>
                                <option value="debug" <?php selected($settings['general']['log_level'], 'debug'); ?>>Debug</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Timezone</th>
                        <td>
                            <select name="timezone">
                                <?php
                                $timezones = timezone_identifiers_list();
                                foreach ($timezones as $timezone) {
                                    echo '<option value="' . esc_attr($timezone) . '" ' . selected($settings['general']['timezone'], $timezone, false) . '>' . esc_html($timezone) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Default Currency</th>
                        <td>
                            <select name="currency">
                                <option value="USD" <?php selected($settings['general']['currency'], 'USD'); ?>>USD</option>
                                <option value="EUR" <?php selected($settings['general']['currency'], 'EUR'); ?>>EUR</option>
                                <option value="GBP" <?php selected($settings['general']['currency'], 'GBP'); ?>>GBP</option>
                                <option value="JPY" <?php selected($settings['general']['currency'], 'JPY'); ?>>JPY</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Save General Settings</button>
                    <button type="button" class="button reset-config" data-section="general">Reset to Default</button>
                </p>
            </form>
        </div>
        
        <!-- Trading Tab -->
        <div id="trading" class="tab-content" style="display: none;">
            <form class="config-form" data-section="trading">
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Trading</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" 
                                       <?php checked($settings['trading']['enabled']); ?>>
                                Enable trading functionality
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Minimum Trade Amount</th>
                        <td>
                            <input type="number" name="min_trade_amount" 
                                   value="<?php echo esc_attr($settings['trading']['min_trade_amount']); ?>" 
                                   step="0.01" min="0" class="small-text">
                            <p class="description">Minimum trade amount in USD</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Maximum Trade Amount</th>
                        <td>
                            <input type="number" name="max_trade_amount" 
                                   value="<?php echo esc_attr($settings['trading']['max_trade_amount']); ?>" 
                                   step="1000" min="0" class="small-text">
                            <p class="description">Maximum trade amount in USD</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Trading Fees (%)</th>
                        <td>
                            <input type="number" name="trading_fees" 
                                   value="<?php echo esc_attr($settings['trading']['trading_fees']); ?>" 
                                   step="0.001" min="0" max="10" class="small-text">
                            <p class="description">Default trading fee percentage</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Maker Fee (%)</th>
                        <td>
                            <input type="number" name="maker_fee" 
                                   value="<?php echo esc_attr($settings['trading']['maker_fee']); ?>" 
                                   step="0.001" min="0" max="10" class="small-text">
                            <p class="description">Maker fee percentage</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Taker Fee (%)</th>
                        <td>
                            <input type="number" name="taker_fee" 
                                   value="<?php echo esc_attr($settings['trading']['taker_fee']); ?>" 
                                   step="0.001" min="0" max="10" class="small-text">
                            <p class="description">Taker fee percentage</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Order Timeout (seconds)</th>
                        <td>
                            <input type="number" name="order_timeout" 
                                   value="<?php echo esc_attr($settings['trading']['order_timeout']); ?>" 
                                   min="60" max="3600" class="small-text">
                            <p class="description">Order timeout in seconds</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Max Open Orders</th>
                        <td>
                            <input type="number" name="max_open_orders" 
                                   value="<?php echo esc_attr($settings['trading']['max_open_orders']); ?>" 
                                   min="1" max="1000" class="small-text">
                            <p class="description">Maximum open orders per user</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Trading Settings</button>
                    <button type="button" class="button reset-config" data-section="trading">Reset to Default</button>
                </p>
            </form>
        </div>
        
        <!-- Wallet Tab -->
        <div id="wallet" class="tab-content" style="display: none;">
            <form class="config-form" data-section="wallet">
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Wallet System</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" 
                                       <?php checked($settings['wallet']['enabled']); ?>>
                                Enable wallet functionality
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Minimum Deposit</th>
                        <td>
                            <input type="number" name="min_deposit" 
                                   value="<?php echo esc_attr($settings['wallet']['min_deposit']); ?>" 
                                   step="0.00000001" min="0" class="small-text">
                            <p class="description">Minimum deposit amount</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Minimum Withdrawal</th>
                        <td>
                            <input type="number" name="min_withdrawal" 
                                   value="<?php echo esc_attr($settings['wallet']['min_withdrawal']); ?>" 
                                   step="0.00000001" min="0" class="small-text">
                            <p class="description">Minimum withdrawal amount</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Withdrawal Fee</th>
                        <td>
                            <input type="number" name="withdrawal_fee" 
                                   value="<?php echo esc_attr($settings['wallet']['withdrawal_fee']); ?>" 
                                   step="0.00000001" min="0" class="small-text">
                            <p class="description">Withdrawal fee percentage</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Max Daily Withdrawal</th>
                        <td>
                            <input type="number" name="max_daily_withdrawal" 
                                   value="<?php echo esc_attr($settings['wallet']['max_daily_withdrawal']); ?>" 
                                   step="0.01" min="0" class="small-text">
                            <p class="description">Maximum daily withdrawal in USD</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Require KYC for Withdrawal</th>
                        <td>
                            <label>
                                <input type="checkbox" name="require_kyc_for_withdrawal" 
                                       <?php checked($settings['wallet']['require_kyc_for_withdrawal']); ?>>
                                Require KYC verification for withdrawals
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Cold Storage Percentage</th>
                        <td>
                            <input type="number" name="cold_storage_percentage" 
                                   value="<?php echo esc_attr($settings['wallet']['cold_storage_percentage']); ?>" 
                                   min="0" max="100" class="small-text">
                            <p class="description">Percentage of funds kept in cold storage</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Wallet Settings</button>
                    <button type="button" class="button reset-config" data-section="wallet">Reset to Default</button>
                </p>
            </form>
        </div>
        
        <!-- Security Tab -->
        <div id="security" class="tab-content" style="display: none;">
            <form class="config-form" data-section="security">
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Security System</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" 
                                       <?php checked($settings['security']['enabled']); ?>>
                                Enable security features
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Require Two-Factor Authentication</th>
                        <td>
                            <label>
                                <input type="checkbox" name="two_factor_required" 
                                       <?php checked($settings['security']['two_factor_required']); ?>>
                                Require 2FA for all users
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">IP Whitelist</th>
                        <td>
                            <textarea name="ip_whitelist" rows="5" cols="50" 
                                      placeholder="Enter IP addresses, one per line"><?php echo esc_textarea(implode("\n", $settings['security']['ip_whitelist'])); ?></textarea>
                            <p class="description">IP addresses allowed to access the system (leave empty to allow all)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Max Login Attempts</th>
                        <td>
                            <input type="number" name="max_login_attempts" 
                                   value="<?php echo esc_attr($settings['security']['max_login_attempts']); ?>" 
                                   min="1" max="20" class="small-text">
                            <p class="description">Maximum login attempts before lockout</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Lockout Duration (seconds)</th>
                        <td>
                            <input type="number" name="lockout_duration" 
                                   value="<?php echo esc_attr($settings['security']['lockout_duration']); ?>" 
                                   min="60" max="86400" class="small-text">
                            <p class="description">Account lockout duration in seconds</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Session Timeout (seconds)</th>
                        <td>
                            <input type="number" name="session_timeout" 
                                   value="<?php echo esc_attr($settings['security']['session_timeout']); ?>" 
                                   min="300" max="86400" class="small-text">
                            <p class="description">Session timeout in seconds</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Minimum Password Length</th>
                        <td>
                            <input type="number" name="password_min_length" 
                                   value="<?php echo esc_attr($settings['security']['password_min_length']); ?>" 
                                   min="6" max="50" class="small-text">
                            <p class="description">Minimum password length</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Require Strong Password</th>
                        <td>
                            <label>
                                <input type="checkbox" name="require_strong_password" 
                                       <?php checked($settings['security']['require_strong_password']); ?>>
                                Require strong passwords (uppercase, lowercase, numbers, symbols)
                            </label>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Security Settings</button>
                    <button type="button" class="button reset-config" data-section="security">Reset to Default</button>
                </p>
            </form>
        </div>
        
        <!-- KYC Tab -->
        <div id="kyc" class="tab-content" style="display: none;">
            <form class="config-form" data-section="kyc">
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable KYC System</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" 
                                       <?php checked($settings['kyc']['enabled']); ?>>
                                Enable KYC verification
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Required for Trading</th>
                        <td>
                            <label>
                                <input type="checkbox" name="required_for_trading" 
                                       <?php checked($settings['kyc']['required_for_trading']); ?>>
                                Require KYC verification for trading
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Required for Withdrawal</th>
                        <td>
                            <label>
                                <input type="checkbox" name="required_for_withdrawal" 
                                       <?php checked($settings['kyc']['required_for_withdrawal']); ?>>
                                Require KYC verification for withdrawals
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Document Types</th>
                        <td>
                            <fieldset>
                                <?php
                                $document_types = array('passport', 'drivers_license', 'national_id', 'utility_bill', 'bank_statement');
                                foreach ($document_types as $type):
                                ?>
                                <label>
                                    <input type="checkbox" name="document_types[]" value="<?php echo esc_attr($type); ?>" 
                                           <?php checked(in_array($type, $settings['kyc']['document_types'])); ?>>
                                    <?php echo esc_html(ucwords(str_replace('_', ' ', $type))); ?>
                                </label><br>
                                <?php endforeach; ?>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Verification Timeout (seconds)</th>
                        <td>
                            <input type="number" name="verification_timeout" 
                                   value="<?php echo esc_attr($settings['kyc']['verification_timeout']); ?>" 
                                   min="3600" max="604800" class="small-text">
                            <p class="description">Time to wait for verification before timeout</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Auto Approve</th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_approve" 
                                       <?php checked($settings['kyc']['auto_approve']); ?>>
                                Automatically approve KYC documents (not recommended)
                            </label>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Save KYC Settings</button>
                    <button type="button" class="button reset-config" data-section="kyc">Reset to Default</button>
                </p>
            </form>
        </div>
        
        <!-- Notifications Tab -->
        <div id="notifications" class="tab-content" style="display: none;">
            <form class="config-form" data-section="notifications">
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Notifications</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" 
                                       <?php checked($settings['notifications']['enabled']); ?>>
                                Enable notification system
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Email Notifications</th>
                        <td>
                            <label>
                                <input type="checkbox" name="email_notifications" 
                                       <?php checked($settings['notifications']['email_notifications']); ?>>
                                Enable email notifications
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">SMS Notifications</th>
                        <td>
                            <label>
                                <input type="checkbox" name="sms_notifications" 
                                       <?php checked($settings['notifications']['sms_notifications']); ?>>
                                Enable SMS notifications
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Push Notifications</th>
                        <td>
                            <label>
                                <input type="checkbox" name="push_notifications" 
                                       <?php checked($settings['notifications']['push_notifications']); ?>>
                                Enable push notifications
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Trading Alerts</th>
                        <td>
                            <label>
                                <input type="checkbox" name="trading_alerts" 
                                       <?php checked($settings['notifications']['trading_alerts']); ?>>
                                Enable trading alerts
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Price Alerts</th>
                        <td>
                            <label>
                                <input type="checkbox" name="price_alerts" 
                                       <?php checked($settings['notifications']['price_alerts']); ?>>
                                Enable price alerts
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Security Alerts</th>
                        <td>
                            <label>
                                <input type="checkbox" name="security_alerts" 
                                       <?php checked($settings['notifications']['security_alerts']); ?>>
                                Enable security alerts
                            </label>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Notification Settings</button>
                    <button type="button" class="button reset-config" data-section="notifications">Reset to Default</button>
                </p>
            </form>
        </div>
        
        <!-- API Tab -->
        <div id="api" class="tab-content" style="display: none;">
            <form class="config-form" data-section="api">
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable API</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" 
                                       <?php checked($settings['api']['enabled']); ?>>
                                Enable REST API
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Rate Limit</th>
                        <td>
                            <input type="number" name="rate_limit" 
                                   value="<?php echo esc_attr($settings['api']['rate_limit']); ?>" 
                                   min="100" max="10000" class="small-text">
                            <p class="description">Maximum requests per rate limit window</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Rate Limit Window (seconds)</th>
                        <td>
                            <input type="number" name="rate_limit_window" 
                                   value="<?php echo esc_attr($settings['api']['rate_limit_window']); ?>" 
                                   min="60" max="3600" class="small-text">
                            <p class="description">Rate limit window in seconds</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Require API Key</th>
                        <td>
                            <label>
                                <input type="checkbox" name="require_api_key" 
                                       <?php checked($settings['api']['require_api_key']); ?>>
                                Require API key for all requests
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Allowed IPs</th>
                        <td>
                            <textarea name="allowed_ips" rows="5" cols="50" 
                                      placeholder="Enter IP addresses, one per line"><?php echo esc_textarea(implode("\n", $settings['api']['allowed_ips'])); ?></textarea>
                            <p class="description">IP addresses allowed to access the API (leave empty to allow all)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Max Requests per Minute</th>
                        <td>
                            <input type="number" name="max_requests_per_minute" 
                                   value="<?php echo esc_attr($settings['api']['max_requests_per_minute']); ?>" 
                                   min="10" max="1000" class="small-text">
                            <p class="description">Maximum requests per minute per IP</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Save API Settings</button>
                    <button type="button" class="button reset-config" data-section="api">Reset to Default</button>
                </p>
            </form>
        </div>
        
        <!-- Liquidity Tab -->
        <div id="liquidity" class="tab-content" style="display: none;">
            <form class="config-form" data-section="liquidity">
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Liquidity System</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" 
                                       <?php checked($settings['liquidity']['enabled']); ?>>
                                Enable liquidity aggregation
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Auto Route Orders</th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_route_orders" 
                                       <?php checked($settings['liquidity']['auto_route_orders']); ?>>
                                Automatically route orders to best liquidity provider
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Minimum Liquidity Providers</th>
                        <td>
                            <input type="number" name="min_liquidity_providers" 
                                   value="<?php echo esc_attr($settings['liquidity']['min_liquidity_providers']); ?>" 
                                   min="1" max="10" class="small-text">
                            <p class="description">Minimum number of active liquidity providers required</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Maximum Slippage (%)</th>
                        <td>
                            <input type="number" name="max_slippage" 
                                   value="<?php echo esc_attr($settings['liquidity']['max_slippage']); ?>" 
                                   step="0.001" min="0" max="10" class="small-text">
                            <p class="description">Maximum acceptable slippage percentage</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Prefer Lowest Fee</th>
                        <td>
                            <label>
                                <input type="checkbox" name="prefer_lowest_fee" 
                                       <?php checked($settings['liquidity']['prefer_lowest_fee']); ?>>
                                Prefer liquidity providers with lowest fees
                            </label>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Liquidity Settings</button>
                    <button type="button" class="button reset-config" data-section="liquidity">Reset to Default</button>
                </p>
            </form>
        </div>
        
        <!-- Backup/Restore Tab -->
        <div id="backup" class="tab-content" style="display: none;">
            <div class="backup-section">
                <h3>Configuration Backup</h3>
                <p>Create a backup of your current configuration settings.</p>
                <button type="button" class="button button-primary backup-config">Create Backup</button>
            </div>
            
            <div class="restore-section">
                <h3>Restore from Backup</h3>
                <p>Restore configuration from a previous backup.</p>
                <select id="backup-select">
                    <option value="">Select a backup to restore</option>
                    <?php foreach ($backups as $backup): ?>
                    <option value="<?php echo esc_attr($backup['id']); ?>">
                        <?php echo esc_html($backup['timestamp'] . ' (v' . $backup['version'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button restore-config">Restore Selected Backup</button>
            </div>
            
            <div class="import-export-section">
                <h3>Import/Export Settings</h3>
                <p>Import or export configuration settings as JSON files.</p>
                
                <div class="export-section">
                    <h4>Export Settings</h4>
                    <button type="button" class="button export-settings">Export Settings</button>
                </div>
                
                <div class="import-section">
                    <h4>Import Settings</h4>
                    <input type="file" id="config-file" accept=".json">
                    <button type="button" class="button import-settings">Import Settings</button>
                </div>
            </div>
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
    
    // Module toggle
    $('.module-toggle-input').on('change', function() {
        var module = $(this).data('module');
        var enabled = $(this).is(':checked');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_toggle_module',
                module: module,
                enabled: enabled,
                nonce: cryptoExchangeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                    location.reload();
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                location.reload();
            }
        });
    });
    
    // Test module
    $('.test-module').on('click', function() {
        var module = $(this).data('module');
        var button = $(this);
        
        button.prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_test_module',
                module: module,
                nonce: cryptoExchangeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Module test successful: ' + response.data);
                } else {
                    alert('Module test failed: ' + response.data);
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
    
    // Save configuration
    $('.config-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var section = form.data('section');
        var formData = form.serializeArray();
        var config = {};
        
        $.each(formData, function(i, field) {
            if (field.name.endsWith('[]')) {
                var key = field.name.slice(0, -2);
                if (!config[key]) config[key] = [];
                config[key].push(field.value);
            } else {
                config[field.name] = field.value;
            }
        });
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_save_config',
                section: section,
                config: config,
                nonce: cryptoExchangeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Configuration saved successfully!');
                } else {
                    alert('Error saving configuration: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Reset configuration
    $('.reset-config').on('click', function() {
        if (!confirm('Are you sure you want to reset this configuration to defaults?')) {
            return;
        }
        
        var section = $(this).data('section');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_reset_config',
                section: section,
                nonce: cryptoExchangeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Configuration reset successfully!');
                    location.reload();
                } else {
                    alert('Error resetting configuration: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Backup configuration
    $('.backup-config').on('click', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_backup_config',
                nonce: cryptoExchangeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Backup created successfully!');
                    location.reload();
                } else {
                    alert('Error creating backup: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Restore configuration
    $('.restore-config').on('click', function() {
        var backupId = $('#backup-select').val();
        if (!backupId) {
            alert('Please select a backup to restore.');
            return;
        }
        
        if (!confirm('Are you sure you want to restore this backup? This will overwrite your current configuration.')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_restore_config',
                backup_id: backupId,
                nonce: cryptoExchangeAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Configuration restored successfully!');
                    location.reload();
                } else {
                    alert('Error restoring configuration: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Export settings
    $('.export-settings').on('click', function() {
        window.location.href = ajaxurl + '?action=crypto_exchange_export_settings&nonce=' + cryptoExchangeAdmin.nonce;
    });
    
    // Import settings
    $('.import-settings').on('click', function() {
        var fileInput = $('#config-file')[0];
        if (!fileInput.files.length) {
            alert('Please select a configuration file.');
            return;
        }
        
        var formData = new FormData();
        formData.append('action', 'crypto_exchange_import_settings');
        formData.append('config_file', fileInput.files[0]);
        formData.append('nonce', cryptoExchangeAdmin.nonce);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Settings imported successfully!');
                    location.reload();
                } else {
                    alert('Error importing settings: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
});
</script>

<style>
.crypto-admin-config {
    max-width: 1200px;
}

.crypto-config-tabs .tab-content {
    margin-top: 20px;
}

.crypto-modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.module-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.module-header h3 {
    margin: 0;
    color: #333;
}

.module-toggle .switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.module-toggle .switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.module-toggle .slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.module-toggle .slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

.module-toggle input:checked + .slider {
    background-color: #2196F3;
}

.module-toggle input:checked + .slider:before {
    transform: translateX(26px);
}

.module-toggle input:disabled + .slider {
    opacity: 0.5;
    cursor: not-allowed;
}

.module-body {
    font-size: 14px;
}

.module-description {
    color: #666;
    margin-bottom: 15px;
}

.module-status {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
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

.module-required {
    background: #ff9800;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    display: inline-block;
    margin-bottom: 15px;
}

.module-actions {
    display: flex;
    gap: 10px;
}

.backup-section, .restore-section, .import-export-section {
    margin-bottom: 30px;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
}

.backup-section h3, .restore-section h3, .import-export-section h3 {
    margin-top: 0;
}

.export-section, .import-section {
    margin-bottom: 20px;
}

.export-section h4, .import-section h4 {
    margin-bottom: 10px;
}

#backup-select {
    width: 100%;
    max-width: 400px;
    margin-bottom: 10px;
}

#config-file {
    margin-bottom: 10px;
}

.form-table th {
    width: 200px;
}

.form-table td {
    padding: 15px 10px;
}

.form-table input[type="text"], 
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

.submit {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.submit .button {
    margin-right: 10px;
}
</style>