/**
 * Crypto Exchange Pro - Main JavaScript
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        CryptoExchange.init();
    });

    // Main CryptoExchange object
    window.CryptoExchange = {
        
        // Initialize the application
        init: function() {
            this.bindEvents();
            this.loadMarketData();
            this.loadUserData();
        },

        // Bind event handlers
        bindEvents: function() {
            // Auth forms
            $('#crypto-login-form').on('submit', this.handleLogin);
            $('#crypto-register-form').on('submit', this.handleRegister);
            
            // Trading forms
            $('#place-order-form').on('submit', this.handlePlaceOrder);
            $('.side-btn').on('click', this.handleSideToggle);
            $('#order-type').on('change', this.handleOrderTypeChange);
            
            // Wallet forms
            $('#deposit-form').on('submit', this.handleDeposit);
            $('#withdraw-form').on('submit', this.handleWithdraw);
            
            // KYC forms
            $('#kyc-upload-form').on('submit', this.handleKYCUpload);
            
            // Modal events
            $('.modal-close, .close').on('click', this.closeModal);
            $(window).on('click', this.handleModalClick);
            
            // Notification close
            $('.notification-close').on('click', this.closeNotification);
            
            // Copy address buttons
            $('.copy-btn').on('click', this.copyToClipboard);
        },

        // Handle login form submission
        handleLogin: function(e) {
            e.preventDefault();
            
            const form = $(this);
            const formData = form.serialize();
            
            CryptoExchange.showLoading();
            
            $.ajax({
                url: cryptoExchange.ajaxUrl,
                type: 'POST',
                data: formData + '&action=crypto_exchange_login',
                success: function(response) {
                    CryptoExchange.hideLoading();
                    
                    if (response.success) {
                        CryptoExchange.showNotification('Login successful!', 'success');
                        setTimeout(() => {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    } else {
                        CryptoExchange.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    CryptoExchange.hideLoading();
                    CryptoExchange.showNotification('An error occurred. Please try again.', 'error');
                }
            });
        },

        // Handle register form submission
        handleRegister: function(e) {
            e.preventDefault();
            
            const form = $(this);
            const formData = form.serialize();
            
            // Validate password confirmation
            const password = form.find('input[name="password"]').val();
            const confirmPassword = form.find('input[name="confirm_password"]').val();
            
            if (password !== confirmPassword) {
                CryptoExchange.showNotification('Passwords do not match.', 'error');
                return;
            }
            
            CryptoExchange.showLoading();
            
            $.ajax({
                url: cryptoExchange.ajaxUrl,
                type: 'POST',
                data: formData + '&action=crypto_exchange_register',
                success: function(response) {
                    CryptoExchange.hideLoading();
                    
                    if (response.success) {
                        CryptoExchange.showNotification('Registration successful!', 'success');
                        setTimeout(() => {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    } else {
                        CryptoExchange.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    CryptoExchange.hideLoading();
                    CryptoExchange.showNotification('An error occurred. Please try again.', 'error');
                }
            });
        },

        // Handle place order form submission
        handlePlaceOrder: function(e) {
            e.preventDefault();
            
            const form = $(this);
            const formData = form.serializeArray();
            const data = {};
            
            // Convert form data to object
            $.each(formData, function(i, field) {
                data[field.name] = field.value;
            });
            
            // Add trading pair
            data.pair = $('#trading-pair').val();
            
            CryptoExchange.showLoading();
            
            $.ajax({
                url: cryptoExchange.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'crypto_exchange_place_order',
                    ...data
                },
                success: function(response) {
                    CryptoExchange.hideLoading();
                    
                    if (response.success) {
                        CryptoExchange.showNotification('Order placed successfully!', 'success');
                        form[0].reset();
                        CryptoExchange.loadOrders();
                    } else {
                        CryptoExchange.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    CryptoExchange.hideLoading();
                    CryptoExchange.showNotification('An error occurred. Please try again.', 'error');
                }
            });
        },

        // Handle side toggle (buy/sell)
        handleSideToggle: function(e) {
            e.preventDefault();
            
            $('.side-btn').removeClass('active');
            $(this).addClass('active');
            
            const side = $(this).data('side');
            $('input[name="side"]').val(side);
        },

        // Handle order type change
        handleOrderTypeChange: function() {
            const orderType = $(this).val();
            const priceGroup = $('#price-group');
            
            if (orderType === 'limit') {
                priceGroup.show();
                priceGroup.find('input').prop('required', true);
            } else {
                priceGroup.hide();
                priceGroup.find('input').prop('required', false);
            }
        },

        // Handle deposit form submission
        handleDeposit: function(e) {
            e.preventDefault();
            
            const form = $(this);
            const formData = form.serialize();
            
            CryptoExchange.showLoading();
            
            $.ajax({
                url: cryptoExchange.ajaxUrl,
                type: 'POST',
                data: formData + '&action=crypto_exchange_deposit',
                success: function(response) {
                    CryptoExchange.hideLoading();
                    
                    if (response.success) {
                        CryptoExchange.showNotification('Deposit request created!', 'success');
                        CryptoExchange.closeModal();
                        CryptoExchange.loadWallets();
                    } else {
                        CryptoExchange.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    CryptoExchange.hideLoading();
                    CryptoExchange.showNotification('An error occurred. Please try again.', 'error');
                }
            });
        },

        // Handle withdraw form submission
        handleWithdraw: function(e) {
            e.preventDefault();
            
            const form = $(this);
            const formData = form.serialize();
            
            CryptoExchange.showLoading();
            
            $.ajax({
                url: cryptoExchange.ajaxUrl,
                type: 'POST',
                data: formData + '&action=crypto_exchange_withdraw',
                success: function(response) {
                    CryptoExchange.hideLoading();
                    
                    if (response.success) {
                        CryptoExchange.showNotification('Withdrawal request created!', 'success');
                        CryptoExchange.closeModal();
                        CryptoExchange.loadWallets();
                    } else {
                        CryptoExchange.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    CryptoExchange.hideLoading();
                    CryptoExchange.showNotification('An error occurred. Please try again.', 'error');
                }
            });
        },

        // Handle KYC upload form submission
        handleKYCUpload: function(e) {
            e.preventDefault();
            
            const form = $(this);
            const formData = new FormData(form[0]);
            formData.append('action', 'crypto_exchange_upload_kyc');
            
            CryptoExchange.showLoading();
            
            $.ajax({
                url: cryptoExchange.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    CryptoExchange.hideLoading();
                    
                    if (response.success) {
                        CryptoExchange.showNotification('Document uploaded successfully!', 'success');
                        form[0].reset();
                        CryptoExchange.loadKYCDocuments();
                    } else {
                        CryptoExchange.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    CryptoExchange.hideLoading();
                    CryptoExchange.showNotification('An error occurred. Please try again.', 'error');
                }
            });
        },

        // Load market data
        loadMarketData: function() {
            $.ajax({
                url: cryptoExchange.apiUrl + 'market-data',
                type: 'GET',
                success: function(response) {
                    CryptoExchange.updateMarketData(response);
                },
                error: function() {
                    console.error('Failed to load market data');
                }
            });
        },

        // Update market data display
        updateMarketData: function(data) {
            // Update market overview in sidebar
            const marketOverview = $('#sidebar-market-overview');
            if (marketOverview.length) {
                let html = '';
                data.forEach(function(pair) {
                    const changeClass = pair.change_24h >= 0 ? 'positive' : 'negative';
                    html += `
                        <div class="market-item">
                            <div class="pair-name">${pair.pair}</div>
                            <div class="pair-price">$${parseFloat(pair.price).toFixed(2)}</div>
                            <div class="pair-change ${changeClass}">${pair.change_24h >= 0 ? '+' : ''}${pair.change_24h.toFixed(2)}%</div>
                        </div>
                    `;
                });
                marketOverview.html(html);
            }
        },

        // Load user data
        loadUserData: function() {
            if (!cryptoExchange.isLoggedIn) {
                return;
            }
            
            // Load portfolio value
            this.loadPortfolioValue();
            
            // Load orders
            this.loadOrders();
            
            // Load wallets
            this.loadWallets();
        },

        // Load portfolio value
        loadPortfolioValue: function() {
            $.ajax({
                url: cryptoExchange.apiUrl + 'wallets',
                type: 'GET',
                success: function(response) {
                    let totalValue = 0;
                    response.forEach(function(wallet) {
                        // This would need to be calculated with current prices
                        totalValue += parseFloat(wallet.balance) * 1000; // Mock calculation
                    });
                    
                    $('#portfolio-value, #sidebar-portfolio-value').text('$' + totalValue.toFixed(2));
                }
            });
        },

        // Load orders
        loadOrders: function() {
            $.ajax({
                url: cryptoExchange.apiUrl + 'orders',
                type: 'GET',
                success: function(response) {
                    CryptoExchange.updateOrdersDisplay(response);
                }
            });
        },

        // Update orders display
        updateOrdersDisplay: function(orders) {
            const ordersList = $('#orders-list');
            if (!ordersList.length) return;
            
            let html = '';
            orders.forEach(function(order) {
                const statusClass = order.status.toLowerCase();
                html += `
                    <div class="order-item">
                        <div class="order-info">
                            <div class="order-pair">${order.pair}</div>
                            <div class="order-type">${order.order_type} ${order.side}</div>
                        </div>
                        <div class="order-details">
                            <div class="order-amount">${parseFloat(order.amount).toFixed(8)}</div>
                            <div class="order-price">$${parseFloat(order.price || 0).toFixed(2)}</div>
                        </div>
                        <div class="order-status">
                            <span class="status ${statusClass}">${order.status}</span>
                        </div>
                    </div>
                `;
            });
            
            ordersList.html(html || '<p>No orders found.</p>');
        },

        // Load wallets
        loadWallets: function() {
            $.ajax({
                url: cryptoExchange.apiUrl + 'wallets',
                type: 'GET',
                success: function(response) {
                    // Wallets are already rendered server-side
                    // This could be used for real-time updates
                }
            });
        },

        // Load KYC documents
        loadKYCDocuments: function() {
            // This would reload the KYC documents section
            location.reload();
        },

        // Show modal
        showModal: function(modalId) {
            $('#' + modalId).show();
        },

        // Close modal
        closeModal: function() {
            $('.modal').hide();
        },

        // Handle modal click outside
        handleModalClick: function(e) {
            if ($(e.target).hasClass('modal')) {
                CryptoExchange.closeModal();
            }
        },

        // Show notification
        showNotification: function(message, type) {
            const notification = $(`
                <div class="notification notification-${type}">
                    <span class="notification-message">${message}</span>
                    <button class="notification-close">&times;</button>
                </div>
            `);
            
            $('body').append(notification);
            
            // Auto remove after 5 seconds
            setTimeout(function() {
                notification.fadeOut(function() {
                    notification.remove();
                });
            }, 5000);
        },

        // Close notification
        closeNotification: function() {
            $(this).closest('.notification').fadeOut(function() {
                $(this).remove();
            });
        },

        // Show loading
        showLoading: function() {
            if ($('.loading-spinner').length === 0) {
                $('body').append(`
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p>Loading...</p>
                    </div>
                `);
            }
        },

        // Hide loading
        hideLoading: function() {
            $('.loading-spinner').remove();
        },

        // Copy to clipboard
        copyToClipboard: function() {
            const button = $(this);
            const input = button.siblings('input');
            const text = input.val();
            
            navigator.clipboard.writeText(text).then(function() {
                button.text('Copied!');
                setTimeout(function() {
                    button.text('Copy');
                }, 2000);
            });
        },

        // Show deposit modal
        showDepositModal: function(currency) {
            $('#deposit-currency').val(currency);
            $('#deposit-address').val(currency + '_' + Math.random().toString(36).substr(2, 9));
            $('#deposit-modal').show();
        },

        // Show withdraw modal
        showWithdrawModal: function(currency) {
            $('#withdraw-currency').val(currency);
            $('#available-balance').text('0.00000000'); // This would be loaded from API
            $('#withdraw-modal').show();
        },

        // Cancel order
        cancelOrder: function(orderId) {
            if (!confirm('Are you sure you want to cancel this order?')) {
                return;
            }
            
            $.ajax({
                url: cryptoExchange.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'crypto_exchange_cancel_order',
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        CryptoExchange.showNotification('Order cancelled successfully!', 'success');
                        CryptoExchange.loadOrders();
                    } else {
                        CryptoExchange.showNotification(response.data.message, 'error');
                    }
                }
            });
        }
    };

    // Global functions for inline event handlers
    window.showDepositModal = function(currency) {
        CryptoExchange.showDepositModal(currency);
    };

    window.showWithdrawModal = function(currency) {
        CryptoExchange.showWithdrawModal(currency);
    };

    window.copyAddress = function(address) {
        navigator.clipboard.writeText(address).then(function() {
            CryptoExchange.showNotification('Address copied to clipboard!', 'success');
        });
    };

    window.cancelOrder = function(orderId) {
        CryptoExchange.cancelOrder(orderId);
    };

})(jQuery);
