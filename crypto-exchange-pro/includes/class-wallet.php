<?php
/**
 * Wallet class for Crypto Exchange Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Wallet {
    
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Get user wallets
     */
    public function get_user_wallets($user_id) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_wallets 
                 WHERE user_id = %d AND is_active = 1 
                 ORDER BY currency ASC",
                $user_id
            )
        );
    }
    
    /**
     * Get wallet by currency
     */
    public function get_wallet($user_id, $currency) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_wallets 
                 WHERE user_id = %d AND currency = %s AND is_active = 1",
                $user_id,
                $currency
            )
        );
    }
    
    /**
     * Create deposit
     */
    public function create_deposit($data) {
        $user_id = get_current_user_id();
        $currency = sanitize_text_field($data['currency']);
        $amount = floatval($data['amount']);
        
        // Validate currency
        $wallet = $this->get_wallet($user_id, $currency);
        if (!$wallet) {
            return array(
                'success' => false,
                'message' => 'Invalid currency'
            );
        }
        
        // Create transaction record
        $transaction_id = $this->create_transaction($user_id, 'deposit', $currency, $amount);
        
        if ($transaction_id) {
            return array(
                'success' => true,
                'message' => 'Deposit request created',
                'transaction_id' => $transaction_id,
                'address' => $wallet->address
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to create deposit'
            );
        }
    }
    
    /**
     * Create withdrawal
     */
    public function create_withdrawal($data) {
        $user_id = get_current_user_id();
        $currency = sanitize_text_field($data['currency']);
        $amount = floatval($data['amount']);
        $address = sanitize_text_field($data['address']);
        
        // Validate currency
        $wallet = $this->get_wallet($user_id, $currency);
        if (!$wallet) {
            return array(
                'success' => false,
                'message' => 'Invalid currency'
            );
        }
        
        // Check balance
        if ($wallet->balance < $amount) {
            return array(
                'success' => false,
                'message' => 'Insufficient balance'
            );
        }
        
        // Calculate fee
        $fee = $this->calculate_withdrawal_fee($currency, $amount);
        
        // Check if user has enough balance including fee
        if ($wallet->balance < ($amount + $fee)) {
            return array(
                'success' => false,
                'message' => 'Insufficient balance to cover withdrawal fee'
            );
        }
        
        // Create transaction record
        $transaction_id = $this->create_transaction($user_id, 'withdrawal', $currency, $amount, $fee, $address);
        
        if ($transaction_id) {
            // Lock the amount
            $this->lock_balance($user_id, $currency, $amount + $fee);
            
            return array(
                'success' => true,
                'message' => 'Withdrawal request created',
                'transaction_id' => $transaction_id
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to create withdrawal'
            );
        }
    }
    
    /**
     * Create transaction
     */
    private function create_transaction($user_id, $type, $currency, $amount, $fee = 0, $address = '') {
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_transactions',
            array(
                'user_id' => $user_id,
                'transaction_type' => $type,
                'currency' => $currency,
                'amount' => $amount,
                'fee' => $fee,
                'status' => 'pending',
                'to_address' => $address
            ),
            array('%d', '%s', '%s', '%f', '%f', '%s', '%s')
        );
        
        return $result ? $this->wpdb->insert_id : false;
    }
    
    /**
     * Calculate withdrawal fee
     */
    private function calculate_withdrawal_fee($currency, $amount) {
        $fee_data = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_fees 
                 WHERE fee_type = 'withdrawal' AND currency = %s AND is_active = 1",
                $currency
            )
        );
        
        if ($fee_data) {
            return max($fee_data->amount, $amount * $fee_data->percentage);
        }
        
        // Default fees
        $default_fees = array(
            'BTC' => 0.0005,
            'ETH' => 0.01,
            'BNB' => 0.1
        );
        
        return $default_fees[$currency] ?? 0.01;
    }
    
    /**
     * Lock balance
     */
    private function lock_balance($user_id, $currency, $amount) {
        $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->wpdb->prefix}crypto_wallets 
                 SET balance = balance - %f, locked_balance = locked_balance + %f 
                 WHERE user_id = %d AND currency = %s",
                $amount,
                $amount,
                $user_id,
                $currency
            )
        );
    }
    
    /**
     * Unlock balance
     */
    private function unlock_balance($user_id, $currency, $amount) {
        $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->wpdb->prefix}crypto_wallets 
                 SET balance = balance + %f, locked_balance = locked_balance - %f 
                 WHERE user_id = %d AND currency = %s",
                $amount,
                $amount,
                $user_id,
                $currency
            )
        );
    }
    
    /**
     * Add balance
     */
    public function add_balance($user_id, $currency, $amount) {
        $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->wpdb->prefix}crypto_wallets 
                 SET balance = balance + %f 
                 WHERE user_id = %d AND currency = %s",
                $amount,
                $user_id,
                $currency
            )
        );
    }
    
    /**
     * Subtract balance
     */
    public function subtract_balance($user_id, $currency, $amount) {
        $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->wpdb->prefix}crypto_wallets 
                 SET balance = balance - %f 
                 WHERE user_id = %d AND currency = %s",
                $amount,
                $user_id,
                $currency
            )
        );
    }
    
    /**
     * Get transaction history
     */
    public function get_transaction_history($user_id, $limit = 50) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_transactions 
                 WHERE user_id = %d 
                 ORDER BY created_at DESC 
                 LIMIT %d",
                $user_id,
                $limit
            )
        );
    }
    
    /**
     * Generate new wallet address
     */
    public function generate_address($user_id, $currency) {
        // This is a simplified version - in production, use proper wallet generation
        $address = strtoupper($currency) . '_' . wp_generate_password(32, false);
        
        $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_wallets',
            array('address' => $address),
            array('user_id' => $user_id, 'currency' => $currency),
            array('%s'),
            array('%d', '%s')
        );
        
        return $address;
    }
    
    /**
     * Get wallet balance
     */
    public function get_balance($user_id, $currency) {
        $wallet = $this->get_wallet($user_id, $currency);
        return $wallet ? $wallet->balance : 0;
    }
    
    /**
     * Get total portfolio value
     */
    public function get_portfolio_value($user_id) {
        $wallets = $this->get_user_wallets($user_id);
        $total_value = 0;
        
        foreach ($wallets as $wallet) {
            // Get current price from market data
            $price = $this->get_currency_price($wallet->currency);
            $total_value += $wallet->balance * $price;
        }
        
        return $total_value;
    }
    
    /**
     * Get currency price
     */
    private function get_currency_price($currency) {
        $market_data = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT price FROM {$this->wpdb->prefix}crypto_market_data 
                 WHERE pair = %s",
                $currency . '/USD'
            )
        );
        
        return $market_data ? $market_data->price : 0;
    }
    
    /**
     * Render wallet page
     */
    public function render() {
        $user_id = get_current_user_id();
        $wallets = $this->get_user_wallets($user_id);
        $transactions = $this->get_transaction_history($user_id);
        ?>
        <div class="crypto-wallet-container">
            <div class="wallet-header">
                <h1>My Wallets</h1>
                <div class="portfolio-value">
                    <span class="label">Portfolio Value:</span>
                    <span class="value" id="portfolio-value">$0.00</span>
                </div>
            </div>
            
            <div class="wallet-content">
                <div class="wallets-grid">
                    <?php foreach ($wallets as $wallet): ?>
                    <div class="wallet-card">
                        <div class="wallet-header">
                            <h3><?php echo $wallet->currency; ?></h3>
                            <span class="wallet-status <?php echo $wallet->is_active ? 'active' : 'inactive'; ?>">
                                <?php echo $wallet->is_active ? 'Active' : 'Inactive'; ?>
                            </span>
                        </div>
                        
                        <div class="wallet-balance">
                            <div class="balance-amount">
                                <?php echo number_format($wallet->balance, 8); ?>
                            </div>
                            <div class="balance-label">Available</div>
                        </div>
                        
                        <div class="wallet-locked">
                            <div class="locked-amount">
                                <?php echo number_format($wallet->locked_balance, 8); ?>
                            </div>
                            <div class="locked-label">Locked</div>
                        </div>
                        
                        <div class="wallet-address">
                            <label>Address:</label>
                            <div class="address-container">
                                <input type="text" value="<?php echo $wallet->address; ?>" readonly class="address-input">
                                <button class="copy-btn" onclick="copyAddress('<?php echo $wallet->address; ?>')">Copy</button>
                            </div>
                        </div>
                        
                        <div class="wallet-actions">
                            <button class="btn btn-primary" onclick="showDepositModal('<?php echo $wallet->currency; ?>')">
                                Deposit
                            </button>
                            <button class="btn btn-secondary" onclick="showWithdrawModal('<?php echo $wallet->currency; ?>')">
                                Withdraw
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="transaction-history">
                    <h3>Transaction History</h3>
                    <div class="transaction-filters">
                        <select id="currency-filter">
                            <option value="">All Currencies</option>
                            <?php foreach ($wallets as $wallet): ?>
                            <option value="<?php echo $wallet->currency; ?>"><?php echo $wallet->currency; ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select id="type-filter">
                            <option value="">All Types</option>
                            <option value="deposit">Deposit</option>
                            <option value="withdrawal">Withdrawal</option>
                        </select>
                    </div>
                    
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Currency</th>
                                <th>Amount</th>
                                <th>Fee</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <span class="transaction-type <?php echo $transaction->transaction_type; ?>">
                                        <?php echo ucfirst($transaction->transaction_type); ?>
                                    </span>
                                </td>
                                <td><?php echo $transaction->currency; ?></td>
                                <td><?php echo number_format($transaction->amount, 8); ?></td>
                                <td><?php echo number_format($transaction->fee, 8); ?></td>
                                <td>
                                    <span class="transaction-status <?php echo $transaction->status; ?>">
                                        <?php echo ucfirst($transaction->status); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y H:i', strtotime($transaction->created_at)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Deposit Modal -->
        <div id="deposit-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Deposit <?php echo $wallet->currency; ?></h3>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="deposit-form">
                        <input type="hidden" name="currency" id="deposit-currency">
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="number" name="amount" step="0.00000001" required>
                        </div>
                        <div class="form-group">
                            <label>Deposit Address</label>
                            <div class="address-display">
                                <input type="text" id="deposit-address" readonly>
                                <button type="button" onclick="copyAddress(document.getElementById('deposit-address').value)">Copy</button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Create Deposit</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Withdraw Modal -->
        <div id="withdraw-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Withdraw <?php echo $wallet->currency; ?></h3>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="withdraw-form">
                        <input type="hidden" name="currency" id="withdraw-currency">
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="number" name="amount" step="0.00000001" required>
                            <small>Available: <span id="available-balance">0</span></small>
                        </div>
                        <div class="form-group">
                            <label>Withdrawal Address</label>
                            <input type="text" name="address" required>
                        </div>
                        <div class="form-group">
                            <label>Fee</label>
                            <input type="text" id="withdrawal-fee" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary">Create Withdrawal</button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}
