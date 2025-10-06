/**
 * Crypto Exchange Pro - Admin JavaScript
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        CryptoExchangeAdmin.init();
    });

    // Main CryptoExchangeAdmin object
    window.CryptoExchangeAdmin = {
        
        // Initialize the admin interface
        init: function() {
            this.bindEvents();
            this.initCharts();
            this.initTabs();
            this.loadDashboardData();
        },

        // Bind event handlers
        bindEvents: function() {
            // Tab navigation
            $('.nav-tab').on('click', this.handleTabClick);
            
            // Bulk actions
            $('#doaction').on('click', this.handleBulkAction);
            $('#cb-select-all-1').on('change', this.handleSelectAll);
            
            // User actions
            $('.toggle-user-status').on('click', this.toggleUserStatus);
            $('.verify-document').on('click', this.verifyDocument);
            $('.cancel-order').on('click', this.cancelOrder);
            
            // Form submissions
            $('.crypto-exchange-settings form').on('submit', this.handleSettingsSave);
            
            // Export buttons
            $('.export-btn').on('click', this.handleExport);
            
            // Refresh buttons
            $('.refresh-btn').on('click', this.refreshData);
        },

        // Handle tab clicks
        handleTabClick: function(e) {
            e.preventDefault();
            
            const tab = $(this);
            const target = tab.attr('href');
            
            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            tab.addClass('nav-tab-active');
            
            // Show target content
            $('.tab-content').removeClass('active');
            $(target).addClass('active');
        },

        // Initialize tabs
        initTabs: function() {
            // Show first tab by default
            $('.nav-tab:first').addClass('nav-tab-active');
            $('.tab-content:first').addClass('active');
        },

        // Handle bulk actions
        handleBulkAction: function(e) {
            e.preventDefault();
            
            const action = $('#bulk-action-selector-top').val();
            const selectedUsers = $('input[name="users[]"]:checked');
            
            if (action === '-1' || selectedUsers.length === 0) {
                alert('Please select an action and at least one user.');
                return;
            }
            
            if (!confirm(`Are you sure you want to ${action} ${selectedUsers.length} user(s)?`)) {
                return;
            }
            
            const userIds = selectedUsers.map(function() {
                return $(this).val();
            }).get();
            
            CryptoExchangeAdmin.performBulkAction(action, userIds);
        },

        // Perform bulk action
        performBulkAction: function(action, userIds) {
            $.ajax({
                url: cryptoExchangeAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'crypto_exchange_bulk_action',
                    bulk_action: action,
                    user_ids: userIds,
                    nonce: cryptoExchangeAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CryptoExchangeAdmin.showNotice(response.data.message, 'success');
                        location.reload();
                    } else {
                        CryptoExchangeAdmin.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    CryptoExchangeAdmin.showNotice('An error occurred. Please try again.', 'error');
                }
            });
        },

        // Handle select all checkbox
        handleSelectAll: function() {
            const isChecked = $(this).is(':checked');
            $('input[name="users[]"]').prop('checked', isChecked);
        },

        // Toggle user status
        toggleUserStatus: function(e) {
            e.preventDefault();
            
            const button = $(this);
            const userId = button.data('user-id');
            const currentStatus = button.data('current-status');
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            if (!confirm(`Are you sure you want to ${newStatus} this user?`)) {
                return;
            }
            
            $.ajax({
                url: cryptoExchangeAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'crypto_exchange_toggle_user_status',
                    user_id: userId,
                    status: newStatus,
                    nonce: cryptoExchangeAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CryptoExchangeAdmin.showNotice('User status updated successfully!', 'success');
                        location.reload();
                    } else {
                        CryptoExchangeAdmin.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    CryptoExchangeAdmin.showNotice('An error occurred. Please try again.', 'error');
                }
            });
        },

        // Verify document
        verifyDocument: function(e) {
            e.preventDefault();
            
            const button = $(this);
            const documentId = button.data('document-id');
            const status = button.data('status');
            
            if (!confirm(`Are you sure you want to ${status} this document?`)) {
                return;
            }
            
            $.ajax({
                url: cryptoExchangeAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'crypto_exchange_verify_document',
                    document_id: documentId,
                    status: status,
                    nonce: cryptoExchangeAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CryptoExchangeAdmin.showNotice('Document status updated successfully!', 'success');
                        location.reload();
                    } else {
                        CryptoExchangeAdmin.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    CryptoExchangeAdmin.showNotice('An error occurred. Please try again.', 'error');
                }
            });
        },

        // Cancel order
        cancelOrder: function(e) {
            e.preventDefault();
            
            const button = $(this);
            const orderId = button.data('order-id');
            
            if (!confirm('Are you sure you want to cancel this order?')) {
                return;
            }
            
            $.ajax({
                url: cryptoExchangeAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'crypto_exchange_cancel_order',
                    order_id: orderId,
                    nonce: cryptoExchangeAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CryptoExchangeAdmin.showNotice('Order cancelled successfully!', 'success');
                        location.reload();
                    } else {
                        CryptoExchangeAdmin.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    CryptoExchangeAdmin.showNotice('An error occurred. Please try again.', 'error');
                }
            });
        },

        // Handle settings save
        handleSettingsSave: function(e) {
            e.preventDefault();
            
            const form = $(this);
            const formData = form.serialize();
            
            $.ajax({
                url: cryptoExchangeAdmin.ajaxUrl,
                type: 'POST',
                data: formData + '&action=crypto_exchange_save_settings',
                success: function(response) {
                    if (response.success) {
                        CryptoExchangeAdmin.showNotice('Settings saved successfully!', 'success');
                    } else {
                        CryptoExchangeAdmin.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    CryptoExchangeAdmin.showNotice('An error occurred. Please try again.', 'error');
                }
            });
        },

        // Handle export
        handleExport: function(e) {
            e.preventDefault();
            
            const button = $(this);
            const type = button.data('export-type');
            
            window.open(cryptoExchangeAdmin.ajaxUrl + '?action=crypto_exchange_export&type=' + type, '_blank');
        },

        // Refresh data
        refreshData: function(e) {
            e.preventDefault();
            
            const button = $(this);
            const type = button.data('refresh-type');
            
            button.addClass('loading');
            
            $.ajax({
                url: cryptoExchangeAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'crypto_exchange_refresh_data',
                    type: type,
                    nonce: cryptoExchangeAdmin.nonce
                },
                success: function(response) {
                    button.removeClass('loading');
                    
                    if (response.success) {
                        CryptoExchangeAdmin.showNotice('Data refreshed successfully!', 'success');
                        location.reload();
                    } else {
                        CryptoExchangeAdmin.showNotice(response.data.message, 'error');
                    }
                },
                error: function() {
                    button.removeClass('loading');
                    CryptoExchangeAdmin.showNotice('An error occurred. Please try again.', 'error');
                }
            });
        },

        // Load dashboard data
        loadDashboardData: function() {
            this.loadCharts();
            this.loadStats();
        },

        // Load charts
        loadCharts: function() {
            // Volume chart
            const volumeCtx = document.getElementById('volume-chart');
            if (volumeCtx) {
                this.initVolumeChart(volumeCtx);
            }
            
            // Users chart
            const usersCtx = document.getElementById('users-chart');
            if (usersCtx) {
                this.initUsersChart(usersCtx);
            }
        },

        // Initialize volume chart
        initVolumeChart: function(ctx) {
            // Mock data - in production, this would come from the server
            const data = {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Trading Volume (USD)',
                    data: [1200000, 1900000, 3000000, 5000000, 2000000, 3000000, 4500000],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                }]
            };
            
            new Chart(ctx, {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        },

        // Initialize users chart
        initUsersChart: function(ctx) {
            // Mock data - in production, this would come from the server
            const data = {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'New Users',
                    data: [12, 19, 3, 5],
                    backgroundColor: '#28a745'
                }]
            };
            
            new Chart(ctx, {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        // Initialize charts
        initCharts: function() {
            // Charts are initialized in loadCharts
        },

        // Load stats
        loadStats: function() {
            $.ajax({
                url: cryptoExchangeAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'crypto_exchange_get_stats',
                    nonce: cryptoExchangeAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CryptoExchangeAdmin.updateStats(response.data);
                    }
                }
            });
        },

        // Update stats display
        updateStats: function(stats) {
            // Update stat cards
            Object.keys(stats).forEach(function(key) {
                const element = $('[data-stat="' + key + '"]');
                if (element.length) {
                    element.text(stats[key]);
                }
            });
        },

        // Show notice
        showNotice: function(message, type) {
            const notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after(notice);
            
            // Auto remove after 5 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            }, 5000);
        },

        // Show loading
        showLoading: function(element) {
            element.addClass('loading');
        },

        // Hide loading
        hideLoading: function(element) {
            element.removeClass('loading');
        },

        // Format number
        formatNumber: function(num) {
            return num.toLocaleString();
        },

        // Format currency
        formatCurrency: function(amount, currency) {
            currency = currency || 'USD';
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(amount);
        },

        // Format percentage
        formatPercentage: function(value) {
            return (value * 100).toFixed(2) + '%';
        },

        // Format date
        formatDate: function(date) {
            return new Date(date).toLocaleDateString();
        },

        // Format datetime
        formatDateTime: function(date) {
            return new Date(date).toLocaleString();
        }
    };

    // Global functions for inline event handlers
    window.toggleUserStatus = function(userId) {
        $('[data-user-id="' + userId + '"]').click();
    };

    window.verifyDocument = function(documentId, status) {
        $('[data-document-id="' + documentId + '"][data-status="' + status + '"]').click();
    };

    window.cancelOrder = function(orderId) {
        $('[data-order-id="' + orderId + '"]').click();
    };

    window.togglePairStatus = function(pairId) {
        // Implementation for toggling pair status
        console.log('Toggle pair status:', pairId);
    };

})(jQuery);
