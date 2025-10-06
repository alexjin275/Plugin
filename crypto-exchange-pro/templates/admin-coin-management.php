<?php
/**
 * Admin Coin Management Page
 */

if (!defined('ABSPATH')) {
    exit;
}

$coin_management = new Crypto_Exchange_Coin_Management();
$coins = $coin_management->get_all_coins();
$stats = $coin_management->get_coin_stats();
?>

<div class="wrap">
    <h1>Coin Management</h1>
    
    <!-- Statistics -->
    <div class="crypto-stats">
        <div class="stat-box">
            <h3>Total Coins</h3>
            <p><?php echo esc_html($stats['total_coins']); ?></p>
        </div>
        <div class="stat-box">
            <h3>Active Coins</h3>
            <p><?php echo esc_html($stats['active_coins']); ?></p>
        </div>
        <div class="stat-box">
            <h3>Inactive Coins</h3>
            <p><?php echo esc_html($stats['inactive_coins']); ?></p>
        </div>
        <div class="stat-box">
            <h3>Crypto Coins</h3>
            <p><?php echo esc_html($stats['crypto_coins']); ?></p>
        </div>
        <div class="stat-box">
            <h3>Token Coins</h3>
            <p><?php echo esc_html($stats['token_coins']); ?></p>
        </div>
    </div>
    
    <!-- Add Coin Button -->
    <div class="add-coin-section">
        <button id="add-coin-btn" class="button button-primary">Add New Coin</button>
    </div>
    
    <!-- Coins Table -->
    <div class="coins-table-section">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Symbol</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Decimals</th>
                    <th>Min Deposit</th>
                    <th>Min Withdrawal</th>
                    <th>Withdrawal Fee</th>
                    <th>Trading Fee</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coins as $coin): ?>
                    <tr>
                        <td>
                            <?php if (!empty($coin['icon_url'])): ?>
                                <img src="<?php echo esc_url($coin['icon_url']); ?>" alt="<?php echo esc_attr($coin['symbol']); ?>" width="20" height="20" style="vertical-align: middle; margin-right: 5px;">
                            <?php endif; ?>
                            <strong><?php echo esc_html($coin['symbol']); ?></strong>
                        </td>
                        <td><?php echo esc_html($coin['name']); ?></td>
                        <td>
                            <span class="coin-type <?php echo esc_attr($coin['type']); ?>">
                                <?php echo esc_html(ucfirst($coin['type'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="coin-status <?php echo esc_attr($coin['status']); ?>">
                                <?php echo esc_html(ucfirst($coin['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($coin['decimals']); ?></td>
                        <td><?php echo number_format($coin['min_deposit'], 8); ?></td>
                        <td><?php echo number_format($coin['min_withdrawal'], 8); ?></td>
                        <td><?php echo number_format($coin['withdrawal_fee'], 8); ?></td>
                        <td><?php echo number_format($coin['trading_fee'], 4); ?>%</td>
                        <td>
                            <button class="button button-small edit-coin-btn" data-coin-id="<?php echo esc_attr($coin['id']); ?>">Edit</button>
                            <button class="button button-small toggle-coin-btn" data-coin-id="<?php echo esc_attr($coin['id']); ?>" data-status="<?php echo esc_attr($coin['status']); ?>">
                                <?php echo $coin['status'] === 'active' ? 'Disable' : 'Enable'; ?>
                            </button>
                            <button class="button button-small button-link-delete delete-coin-btn" data-coin-id="<?php echo esc_attr($coin['id']); ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Coin Modal -->
<div id="coin-modal" class="coin-modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modal-title">Add New Coin</h2>
        
        <form id="coin-form">
            <input type="hidden" id="coin-id" name="coin_id" value="">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="symbol">Symbol *</label>
                    </th>
                    <td>
                        <input type="text" id="symbol" name="symbol" class="regular-text" required>
                        <p class="description">Coin symbol (e.g., BTC, ETH, USDT)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="name">Name *</label>
                    </th>
                    <td>
                        <input type="text" id="name" name="name" class="regular-text" required>
                        <p class="description">Full coin name (e.g., Bitcoin, Ethereum)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="type">Type *</label>
                    </th>
                    <td>
                        <select id="type" name="type" required>
                            <option value="crypto">Cryptocurrency</option>
                            <option value="token">Token</option>
                            <option value="fiat">Fiat</option>
                        </select>
                        <p class="description">Type of coin</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="decimals">Decimals</label>
                    </th>
                    <td>
                        <input type="number" id="decimals" name="decimals" class="small-text" value="8" min="0" max="18">
                        <p class="description">Number of decimal places (0-18)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="min_deposit">Min Deposit</label>
                    </th>
                    <td>
                        <input type="number" id="min_deposit" name="min_deposit" class="regular-text" step="0.00000001" value="0.00000001">
                        <p class="description">Minimum deposit amount</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="min_withdrawal">Min Withdrawal</label>
                    </th>
                    <td>
                        <input type="number" id="min_withdrawal" name="min_withdrawal" class="regular-text" step="0.00000001" value="0.00000001">
                        <p class="description">Minimum withdrawal amount</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="withdrawal_fee">Withdrawal Fee</label>
                    </th>
                    <td>
                        <input type="number" id="withdrawal_fee" name="withdrawal_fee" class="regular-text" step="0.00000001" value="0.001">
                        <p class="description">Fee charged for withdrawals</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="trading_fee">Trading Fee (%)</label>
                    </th>
                    <td>
                        <input type="number" id="trading_fee" name="trading_fee" class="regular-text" step="0.0001" value="0.25">
                        <p class="description">Trading fee percentage</p>
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
                        <p class="description">Coin status</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="contract_address">Contract Address</label>
                    </th>
                    <td>
                        <input type="text" id="contract_address" name="contract_address" class="regular-text">
                        <p class="description">Smart contract address (for tokens)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="rpc_url">RPC URL</label>
                    </th>
                    <td>
                        <input type="url" id="rpc_url" name="rpc_url" class="regular-text">
                        <p class="description">RPC endpoint URL</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="explorer_url">Explorer URL</label>
                    </th>
                    <td>
                        <input type="url" id="explorer_url" name="explorer_url" class="regular-text">
                        <p class="description">Block explorer URL</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="icon_url">Icon URL</label>
                    </th>
                    <td>
                        <input type="url" id="icon_url" name="icon_url" class="regular-text">
                        <p class="description">Coin icon image URL</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="description">Description</label>
                    </th>
                    <td>
                        <textarea id="description" name="description" class="large-text" rows="3"></textarea>
                        <p class="description">Coin description</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">Save Coin</button>
                <button type="button" class="button cancel-btn">Cancel</button>
            </p>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Add coin button
    $('#add-coin-btn').click(function() {
        $('#modal-title').text('Add New Coin');
        $('#coin-form')[0].reset();
        $('#coin-id').val('');
        $('#coin-modal').show();
    });
    
    // Edit coin button
    $('.edit-coin-btn').click(function() {
        var coinId = $(this).data('coin-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_coin_data',
                coin_id: coinId,
                nonce: '<?php echo wp_create_nonce('crypto_exchange_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var coin = response.data;
                    $('#modal-title').text('Edit Coin');
                    $('#coin-id').val(coin.id);
                    $('#symbol').val(coin.symbol);
                    $('#name').val(coin.name);
                    $('#type').val(coin.type);
                    $('#decimals').val(coin.decimals);
                    $('#min_deposit').val(coin.min_deposit);
                    $('#min_withdrawal').val(coin.min_withdrawal);
                    $('#withdrawal_fee').val(coin.withdrawal_fee);
                    $('#trading_fee').val(coin.trading_fee);
                    $('#status').val(coin.status);
                    $('#contract_address').val(coin.contract_address);
                    $('#rpc_url').val(coin.rpc_url);
                    $('#explorer_url').val(coin.explorer_url);
                    $('#icon_url').val(coin.icon_url);
                    $('#description').val(coin.description);
                    $('#coin-modal').show();
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });
    
    // Toggle coin status
    $('.toggle-coin-btn').click(function() {
        var coinId = $(this).data('coin-id');
        var currentStatus = $(this).data('status');
        
        if (confirm('Are you sure you want to ' + (currentStatus === 'active' ? 'disable' : 'enable') + ' this coin?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'crypto_exchange_toggle_coin',
                    coin_id: coinId,
                    nonce: '<?php echo wp_create_nonce('crypto_exchange_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        }
    });
    
    // Delete coin
    $('.delete-coin-btn').click(function() {
        var coinId = $(this).data('coin-id');
        
        if (confirm('Are you sure you want to delete this coin? This action cannot be undone.')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'crypto_exchange_delete_coin',
                    coin_id: coinId,
                    nonce: '<?php echo wp_create_nonce('crypto_exchange_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        }
    });
    
    // Form submission
    $('#coin-form').submit(function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var isEdit = $('#coin-id').val() !== '';
        var action = isEdit ? 'crypto_exchange_update_coin' : 'crypto_exchange_add_coin';
        
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
                    $('#coin-modal').hide();
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });
    
    // Close modal
    $('.close, .cancel-btn').click(function() {
        $('#coin-modal').hide();
    });
    
    // Close modal when clicking outside
    $(window).click(function(e) {
        if (e.target.id === 'coin-modal') {
            $('#coin-modal').hide();
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

.add-coin-section {
    margin: 20px 0;
}

.coins-table-section {
    margin-top: 20px;
}

.coin-type {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.coin-type.crypto {
    background: #e3f2fd;
    color: #1976d2;
}

.coin-type.token {
    background: #f3e5f5;
    color: #7b1fa2;
}

.coin-type.fiat {
    background: #e8f5e8;
    color: #388e3c;
}

.coin-status {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.coin-status.active {
    background: #e8f5e8;
    color: #388e3c;
}

.coin-status.inactive {
    background: #ffebee;
    color: #d32f2f;
}

.coin-modal {
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
    max-width: 600px;
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
