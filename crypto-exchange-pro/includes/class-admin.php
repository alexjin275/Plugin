<?php
/**
 * Admin class for Crypto Exchange Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Admin {
    
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        include CRYPTO_EXCHANGE_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        $settings = get_option('crypto_exchange_settings', array());
        ?>
        <div class="wrap">
            <h1>Crypto Exchange Settings</h1>
            
            <div class="notice notice-info">
                <p><strong>Advanced Configuration Available:</strong> <a href="<?php echo admin_url('admin.php?page=crypto-exchange-config'); ?>" class="button button-primary">Open Advanced Configuration</a></p>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('crypto_exchange_settings', 'crypto_exchange_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Exchange Name</th>
                        <td>
                            <input type="text" name="exchange_name" value="<?php echo esc_attr($settings['exchange_name'] ?? 'Crypto Exchange Pro'); ?>" class="regular-text" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Supported Currencies</th>
                        <td>
                            <fieldset>
                                <?php
                                $supported_currencies = $settings['supported_currencies'] ?? array('BTC', 'ETH', 'BNB', 'ADA', 'SOL', 'DOT', 'MATIC', 'AVAX');
                                $all_currencies = array('BTC', 'ETH', 'BNB', 'ADA', 'SOL', 'DOT', 'MATIC', 'AVAX', 'LTC', 'XRP', 'BCH', 'EOS');
                                foreach ($all_currencies as $currency):
                                ?>
                                <label>
                                    <input type="checkbox" name="supported_currencies[]" value="<?php echo $currency; ?>" <?php checked(in_array($currency, $supported_currencies)); ?> />
                                    <?php echo $currency; ?>
                                </label><br>
                                <?php endforeach; ?>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Fiat Currencies</th>
                        <td>
                            <fieldset>
                                <?php
                                $fiat_currencies = $settings['fiat_currencies'] ?? array('USD', 'EUR', 'GBP');
                                $all_fiat = array('USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD');
                                foreach ($all_fiat as $currency):
                                ?>
                                <label>
                                    <input type="checkbox" name="fiat_currencies[]" value="<?php echo $currency; ?>" <?php checked(in_array($currency, $fiat_currencies)); ?> />
                                    <?php echo $currency; ?>
                                </label><br>
                                <?php endforeach; ?>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Trading Fee (%)</th>
                        <td>
                            <input type="number" name="trading_fees" value="<?php echo esc_attr($settings['trading_fees'] ?? 0.1); ?>" step="0.001" min="0" max="10" class="small-text" />
                            <p class="description">Trading fee as a percentage (e.g., 0.1 for 0.1%)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Minimum Trade Amount</th>
                        <td>
                            <input type="number" name="min_trade_amount" value="<?php echo esc_attr($settings['min_trade_amount'] ?? 10); ?>" step="0.01" min="0" class="small-text" />
                            <p class="description">Minimum trade amount in USD</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Maximum Trade Amount</th>
                        <td>
                            <input type="number" name="max_trade_amount" value="<?php echo esc_attr($settings['max_trade_amount'] ?? 1000000); ?>" step="1000" min="0" class="small-text" />
                            <p class="description">Maximum trade amount in USD</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">KYC Required</th>
                        <td>
                            <label>
                                <input type="checkbox" name="kyc_required" value="1" <?php checked($settings['kyc_required'] ?? true); ?> />
                                Require KYC verification for trading
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Two-Factor Authentication</th>
                        <td>
                            <label>
                                <input type="checkbox" name="two_factor_auth" value="1" <?php checked($settings['two_factor_auth'] ?? true); ?> />
                                Enable two-factor authentication
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">API Enabled</th>
                        <td>
                            <label>
                                <input type="checkbox" name="api_enabled" value="1" <?php checked($settings['api_enabled'] ?? true); ?> />
                                Enable REST API
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Users page
     */
    public function users_page() {
        $users = $this->get_users();
        ?>
        <div class="wrap">
            <h1>Crypto Exchange Users</h1>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="action" id="bulk-action-selector-top">
                        <option value="-1">Bulk Actions</option>
                        <option value="activate">Activate</option>
                        <option value="deactivate">Deactivate</option>
                        <option value="verify_kyc">Verify KYC</option>
                    </select>
                    <input type="submit" id="doaction" class="button action" value="Apply">
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1">
                        </th>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Name</th>
                        <th>KYC Status</th>
                        <th>Account Status</th>
                        <th>Trading Limits</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <th class="check-column">
                            <input type="checkbox" name="users[]" value="<?php echo $user->user_id; ?>">
                        </th>
                        <td><?php echo $user->user_id; ?></td>
                        <td><?php echo get_userdata($user->user_id)->user_email; ?></td>
                        <td><?php echo get_user_meta($user->user_id, 'first_name', true) . ' ' . get_user_meta($user->user_id, 'last_name', true); ?></td>
                        <td>
                            <span class="kyc-status kyc-<?php echo $user->kyc_status; ?>">
                                <?php echo ucfirst($user->kyc_status); ?>
                            </span>
                        </td>
                        <td>
                            <span class="account-status account-<?php echo $user->account_status; ?>">
                                <?php echo ucfirst($user->account_status); ?>
                            </span>
                        </td>
                        <td>$<?php echo number_format($user->trading_limits, 2); ?></td>
                        <td><?php echo $user->last_login ? date('M j, Y H:i', strtotime($user->last_login)) : 'Never'; ?></td>
                        <td>
                            <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->user_id); ?>" class="button button-small">Edit</a>
                            <a href="#" class="button button-small" onclick="toggleUserStatus(<?php echo $user->user_id; ?>)">
                                <?php echo $user->account_status === 'active' ? 'Deactivate' : 'Activate'; ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Trading page
     */
    public function trading_page() {
        $orders = $this->get_recent_orders();
        $trades = $this->get_recent_trades();
        ?>
        <div class="wrap">
            <h1>Trading Management</h1>
            
            <div class="crypto-trading-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#orders" class="nav-tab nav-tab-active">Orders</a>
                    <a href="#trades" class="nav-tab">Trades</a>
                    <a href="#pairs" class="nav-tab">Trading Pairs</a>
                </nav>
                
                <div id="orders" class="tab-content">
                    <h3>Recent Orders</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Pair</th>
                                <th>Type</th>
                                <th>Side</th>
                                <th>Amount</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order->id; ?></td>
                                <td><?php echo get_userdata($order->user_id)->user_email; ?></td>
                                <td><?php echo $order->pair; ?></td>
                                <td><?php echo ucfirst($order->order_type); ?></td>
                                <td><?php echo ucfirst($order->side); ?></td>
                                <td><?php echo number_format($order->amount, 8); ?></td>
                                <td>$<?php echo number_format($order->price, 2); ?></td>
                                <td><?php echo ucfirst($order->status); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($order->created_at)); ?></td>
                                <td>
                                    <?php if ($order->status === 'pending'): ?>
                                    <a href="#" class="button button-small" onclick="cancelOrder(<?php echo $order->id; ?>)">Cancel</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div id="trades" class="tab-content" style="display: none;">
                    <h3>Recent Trades</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pair</th>
                                <th>Buyer</th>
                                <th>Seller</th>
                                <th>Amount</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trades as $trade): ?>
                            <tr>
                                <td><?php echo $trade->id; ?></td>
                                <td><?php echo $trade->pair; ?></td>
                                <td><?php echo get_userdata($trade->buyer_id)->user_email; ?></td>
                                <td><?php echo get_userdata($trade->seller_id)->user_email; ?></td>
                                <td><?php echo number_format($trade->amount, 8); ?></td>
                                <td>$<?php echo number_format($trade->price, 2); ?></td>
                                <td>$<?php echo number_format($trade->total, 2); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($trade->created_at)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div id="pairs" class="tab-content" style="display: none;">
                    <h3>Trading Pairs</h3>
                    <p><a href="<?php echo admin_url('post-new.php?post_type=crypto_pair'); ?>" class="button button-primary">Add New Pair</a></p>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Pair</th>
                                <th>Base Currency</th>
                                <th>Quote Currency</th>
                                <th>Status</th>
                                <th>Min Amount</th>
                                <th>Max Amount</th>
                                <th>Maker Fee</th>
                                <th>Taker Fee</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pairs = $this->get_trading_pairs();
                            foreach ($pairs as $pair):
                            ?>
                            <tr>
                                <td><?php echo $pair->pair; ?></td>
                                <td><?php echo $pair->base_currency; ?></td>
                                <td><?php echo $pair->quote_currency; ?></td>
                                <td><?php echo $pair->is_active ? 'Active' : 'Inactive'; ?></td>
                                <td><?php echo number_format($pair->min_trade_amount, 8); ?></td>
                                <td><?php echo number_format($pair->max_trade_amount, 2); ?></td>
                                <td><?php echo number_format($pair->maker_fee * 100, 2); ?>%</td>
                                <td><?php echo number_format($pair->taker_fee * 100, 2); ?>%</td>
                                <td>
                                    <a href="#" class="button button-small" onclick="togglePairStatus(<?php echo $pair->id; ?>)">
                                        <?php echo $pair->is_active ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Wallets page
     */
    public function wallets_page() {
        $wallets = $this->get_wallet_stats();
        ?>
        <div class="wrap">
            <h1>Wallet Management</h1>
            
            <div class="crypto-wallet-stats">
                <?php foreach ($wallets as $currency => $data): ?>
                <div class="wallet-stat-card">
                    <h3><?php echo $currency; ?></h3>
                    <div class="stat-number"><?php echo number_format($data['total_balance'], 8); ?></div>
                    <div class="stat-label">Total Balance</div>
                    <div class="stat-details">
                        <div>Users: <?php echo $data['user_count']; ?></div>
                        <div>Locked: <?php echo number_format($data['locked_balance'], 8); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * KYC page
     */
    public function kyc_page() {
        $kyc_documents = $this->get_kyc_documents();
        ?>
        <div class="wrap">
            <h1>KYC Management</h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Document Type</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Verified By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kyc_documents as $doc): ?>
                    <tr>
                        <td><?php echo $doc->id; ?></td>
                        <td><?php echo get_userdata($doc->user_id)->user_email; ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $doc->document_type)); ?></td>
                        <td>
                            <span class="kyc-status kyc-<?php echo $doc->status; ?>">
                                <?php echo ucfirst($doc->status); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y H:i', strtotime($doc->created_at)); ?></td>
                        <td><?php echo $doc->verified_by ? get_userdata($doc->verified_by)->display_name : '-'; ?></td>
                        <td>
                            <a href="<?php echo $doc->document_path; ?>" target="_blank" class="button button-small">View</a>
                            <?php if ($doc->status === 'pending'): ?>
                            <a href="#" class="button button-small" onclick="verifyDocument(<?php echo $doc->id; ?>, 'verified')">Verify</a>
                            <a href="#" class="button button-small" onclick="verifyDocument(<?php echo $doc->id; ?>, 'rejected')">Reject</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        $total_users = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_users");
        $active_users = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_users WHERE account_status = 'active'");
        $volume_24h = $this->wpdb->get_var("SELECT SUM(total) FROM {$this->wpdb->prefix}crypto_trades WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $total_orders = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_orders");
        $pending_kyc = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_kyc_documents WHERE status = 'pending'");
        $total_wallets = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_wallets");
        
        $recent_orders = $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_orders ORDER BY created_at DESC LIMIT 10"
        );
        
        return array(
            'total_users' => $total_users,
            'active_users' => $active_users,
            'volume_24h' => $volume_24h ?: 0,
            'total_orders' => $total_orders,
            'pending_kyc' => $pending_kyc,
            'total_wallets' => $total_wallets,
            'recent_orders' => $recent_orders
        );
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        if (!wp_verify_nonce($_POST['crypto_exchange_nonce'], 'crypto_exchange_settings')) {
            wp_die('Security check failed');
        }
        
        $settings = array(
            'exchange_name' => sanitize_text_field($_POST['exchange_name']),
            'supported_currencies' => array_map('sanitize_text_field', $_POST['supported_currencies']),
            'fiat_currencies' => array_map('sanitize_text_field', $_POST['fiat_currencies']),
            'trading_fees' => floatval($_POST['trading_fees']),
            'min_trade_amount' => floatval($_POST['min_trade_amount']),
            'max_trade_amount' => floatval($_POST['max_trade_amount']),
            'kyc_required' => isset($_POST['kyc_required']),
            'two_factor_auth' => isset($_POST['two_factor_auth']),
            'api_enabled' => isset($_POST['api_enabled'])
        );
        
        update_option('crypto_exchange_settings', $settings);
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
    
    /**
     * Get users
     */
    private function get_users() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_users ORDER BY created_at DESC"
        );
    }
    
    /**
     * Get recent orders
     */
    private function get_recent_orders() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_orders ORDER BY created_at DESC LIMIT 50"
        );
    }
    
    /**
     * Get recent trades
     */
    private function get_recent_trades() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_trades ORDER BY created_at DESC LIMIT 50"
        );
    }
    
    /**
     * Get trading pairs
     */
    private function get_trading_pairs() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_trading_pairs ORDER BY pair ASC"
        );
    }
    
    /**
     * Get wallet statistics
     */
    private function get_wallet_stats() {
        $results = $this->wpdb->get_results(
            "SELECT currency, SUM(balance) as total_balance, SUM(locked_balance) as locked_balance, COUNT(DISTINCT user_id) as user_count 
             FROM {$this->wpdb->prefix}crypto_wallets 
             GROUP BY currency"
        );
        
        $stats = array();
        foreach ($results as $result) {
            $stats[$result->currency] = array(
                'total_balance' => $result->total_balance,
                'locked_balance' => $result->locked_balance,
                'user_count' => $result->user_count
            );
        }
        
        return $stats;
    }
    
    /**
     * Get KYC documents
     */
    private function get_kyc_documents() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_kyc_documents ORDER BY created_at DESC"
        );
    }
}
