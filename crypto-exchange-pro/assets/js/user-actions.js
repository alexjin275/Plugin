/**
 * User Actions JavaScript - Comprehensive Action Buttons and Flows
 */

class CryptoUserActions {
    constructor() {
        this.currentAction = null;
        this.currentStep = 1;
        this.actionData = {};
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadUserData();
    }

    bindEvents() {
        // Action button clicks
        jQuery(document).on('click', '.action-btn', (e) => {
            const action = jQuery(e.currentTarget).data('action');
            this.openActionPanel(action);
        });

        // Panel close
        jQuery(document).on('click', '.close-panel', (e) => {
            this.closeActionPanel();
        });

        // Step navigation
        jQuery(document).on('click', '.next-step', (e) => {
            this.nextStep();
        });

        jQuery(document).on('click', '.prev-step', (e) => {
            this.prevStep();
        });

        // Currency selection
        jQuery(document).on('click', '.currency-card', (e) => {
            this.selectCurrency(e.currentTarget);
        });

        // Method selection
        jQuery(document).on('click', '.method-card', (e) => {
            this.selectMethod(e.currentTarget);
        });

        // Order type selection
        jQuery(document).on('click', '.order-type-card', (e) => {
            this.selectOrderType(e.currentTarget);
        });

        // Trading pair selection
        jQuery(document).on('click', '.pair-card', (e) => {
            this.selectTradingPair(e.currentTarget);
        });

        // Side selection
        jQuery(document).on('click', '.side-btn', (e) => {
            this.selectSide(e.currentTarget);
        });

        // Amount buttons
        jQuery(document).on('click', '.amount-btn', (e) => {
            this.setAmountPercentage(e.currentTarget);
        });

        // Form inputs
        jQuery(document).on('input', '.amount-input', (e) => {
            this.handleAmountInput(e.currentTarget);
        });

        jQuery(document).on('input', '#trade-amount, #trade-price', (e) => {
            this.calculateTradeTotal();
        });

        // Checkbox changes
        jQuery(document).on('change', '#accept-terms', (e) => {
            this.handleTermsAcceptance(e.currentTarget);
        });

        // File uploads
        jQuery(document).on('change', 'input[type="file"]', (e) => {
            this.handleFileUpload(e.currentTarget);
        });

        // Confirmation actions
        jQuery(document).on('click', '.confirm-action', (e) => {
            this.confirmAction();
        });

        // Copy buttons
        jQuery(document).on('click', '.copy-btn', (e) => {
            this.copyToClipboard(e.currentTarget);
        });

        // Send SMS
        jQuery(document).on('click', '.send-sms', (e) => {
            this.sendSMS(e.currentTarget);
        });

        // Start verification
        jQuery(document).on('click', '.start-verification', (e) => {
            this.startVerification(e.currentTarget);
        });
    }

    openActionPanel(action) {
        this.currentAction = action;
        this.currentStep = 1;
        this.actionData = {};

        // Hide all panels
        jQuery('.action-panel').hide();

        // Show selected panel
        jQuery(`#${action}-panel`).show();

        // Reset step indicators
        this.resetStepIndicators();

        // Load action data
        this.loadActionData(action);
    }

    closeActionPanel() {
        jQuery('.action-panel').hide();
        this.currentAction = null;
        this.currentStep = 1;
        this.actionData = {};
    }

    resetStepIndicators() {
        jQuery('.step').removeClass('active completed');
        jQuery('.step-panel').removeClass('active');
        jQuery('.step:first').addClass('active');
        jQuery('.step-panel:first').addClass('active');
    }

    nextStep() {
        if (this.validateCurrentStep()) {
            this.currentStep++;
            this.updateStepDisplay();
            this.loadStepData();
        }
    }

    prevStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.updateStepDisplay();
        }
    }

    updateStepDisplay() {
        // Update step indicators
        jQuery('.step').removeClass('active completed');
        jQuery('.step-panel').removeClass('active');

        // Mark previous steps as completed
        for (let i = 1; i < this.currentStep; i++) {
            jQuery(`.step[data-step="${i}"]`).addClass('completed');
        }

        // Mark current step as active
        jQuery(`.step[data-step="${this.currentStep}"]`).addClass('active');
        jQuery(`.step-panel[data-step="${this.currentStep}"]`).addClass('active');

        // Update next button state
        this.updateNextButtonState();
    }

    updateNextButtonState() {
        const nextBtn = jQuery('.next-step');
        const isValid = this.validateCurrentStep();
        nextBtn.prop('disabled', !isValid);
    }

    validateCurrentStep() {
        switch (this.currentAction) {
            case 'deposit':
                return this.validateDepositStep();
            case 'withdraw':
                return this.validateWithdrawStep();
            case 'trade':
                return this.validateTradeStep();
            case 'kyc_verify':
                return this.validateKYCStep();
            default:
                return true;
        }
    }

    validateDepositStep() {
        switch (this.currentStep) {
            case 1:
                return this.actionData.currency !== undefined;
            case 2:
                return this.actionData.method !== undefined;
            case 3:
                return this.actionData.amount > 0;
            case 4:
                return true;
            default:
                return false;
        }
    }

    validateWithdrawStep() {
        switch (this.currentStep) {
            case 1:
                return this.actionData.currency !== undefined;
            case 2:
                return this.actionData.method !== undefined;
            case 3:
                return this.actionData.amount > 0 && this.actionData.address !== '';
            case 4:
                return this.validateVerificationCodes();
            case 5:
                return true;
            default:
                return false;
        }
    }

    validateTradeStep() {
        switch (this.currentStep) {
            case 1:
                return this.actionData.pair !== undefined;
            case 2:
                return this.actionData.orderType !== undefined;
            case 3:
                return this.actionData.amount > 0 && this.actionData.price > 0;
            case 4:
                return this.actionData.termsAccepted === true;
            default:
                return false;
        }
    }

    validateKYCStep() {
        switch (this.currentStep) {
            case 1:
                return this.actionData.documents && this.actionData.documents.length >= 2;
            case 2:
                return this.actionData.verificationCompleted === true;
            case 3:
                return true;
            default:
                return false;
        }
    }

    validateVerificationCodes() {
        const twofaCode = jQuery('#2fa-code').val();
        const smsCode = jQuery('#sms-code').val();
        
        // Basic validation - in real implementation, validate against server
        return twofaCode.length === 6 && smsCode.length === 6;
    }

    selectCurrency(element) {
        const currency = jQuery(element).data('currency');
        this.actionData.currency = currency;

        // Update UI
        jQuery('.currency-card').removeClass('selected');
        jQuery(element).addClass('selected');

        // Update currency display
        jQuery('.currency-display').text(currency);

        // Enable next button
        this.updateNextButtonState();
    }

    selectMethod(element) {
        const method = jQuery(element).data('method');
        this.actionData.method = method;

        // Update UI
        jQuery('.method-card').removeClass('selected');
        jQuery(element).addClass('selected');

        // Enable next button
        this.updateNextButtonState();
    }

    selectOrderType(element) {
        const orderType = jQuery(element).data('type');
        this.actionData.orderType = orderType;

        // Update UI
        jQuery('.order-type-card').removeClass('selected');
        jQuery(element).addClass('selected');

        // Enable next button
        this.updateNextButtonState();
    }

    selectTradingPair(element) {
        const pair = jQuery(element).data('pair');
        this.actionData.pair = pair;

        // Update UI
        jQuery('.pair-card').removeClass('selected');
        jQuery(element).addClass('selected');

        // Enable next button
        this.updateNextButtonState();
    }

    selectSide(element) {
        const side = jQuery(element).data('side');
        this.actionData.side = side;

        // Update UI
        jQuery('.side-btn').removeClass('active');
        jQuery(element).addClass('active');

        // Update side display
        jQuery('.value.buy, .value.sell').removeClass('buy sell').addClass(side);
    }

    setAmountPercentage(element) {
        const percent = jQuery(element).data('percent');
        const balance = this.getCurrentBalance();
        const amount = (balance * percent) / 100;
        
        jQuery('.amount-input').val(amount.toFixed(8));
        this.actionData.amount = amount;
        
        this.updateNextButtonState();
    }

    handleAmountInput(element) {
        const amount = parseFloat(jQuery(element).val()) || 0;
        this.actionData.amount = amount;
        
        this.updateNextButtonState();
    }

    calculateTradeTotal() {
        const amount = parseFloat(jQuery('#trade-amount').val()) || 0;
        const price = parseFloat(jQuery('#trade-price').val()) || 0;
        const total = amount * price;
        
        jQuery('#trade-total').val(total.toFixed(2));
        this.actionData.total = total;
    }

    handleTermsAcceptance(element) {
        this.actionData.termsAccepted = jQuery(element).is(':checked');
        this.updateNextButtonState();
    }

    handleFileUpload(element) {
        const file = element.files[0];
        const documentType = jQuery(element).closest('.upload-area').data('document');
        
        if (file) {
            // Validate file
            if (this.validateFile(file)) {
                // Upload file
                this.uploadFile(file, documentType);
            } else {
                this.showError('Invalid file format or size');
            }
        }
    }

    validateFile(file) {
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        
        return file.size <= maxSize && allowedTypes.includes(file.type);
    }

    uploadFile(file, documentType) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('document_type', documentType);
        formData.append('action', 'crypto_exchange_upload_document');
        formData.append('nonce', crypto_actions.nonce);

        jQuery.ajax({
            url: crypto_actions.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                if (response.success) {
                    this.showUploadSuccess(documentType);
                    this.actionData.documents = this.actionData.documents || [];
                    this.actionData.documents.push({
                        type: documentType,
                        file: response.data
                    });
                    this.updateNextButtonState();
                } else {
                    this.showError(response.data);
                }
            },
            error: () => {
                this.showError('Upload failed. Please try again.');
            }
        });
    }

    showUploadSuccess(documentType) {
        const statusElement = jQuery(`[data-document="${documentType}"]`).siblings('.upload-status');
        statusElement.html('<span class="success">✓ Uploaded successfully</span>');
    }

    confirmAction() {
        if (this.validateCurrentStep()) {
            this.processAction();
        }
    }

    processAction() {
        const actionData = {
            action_type: this.currentAction,
            step: 'process',
            action_data: this.actionData
        };

        jQuery.ajax({
            url: crypto_actions.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_user_action',
                nonce: crypto_actions.nonce,
                ...actionData
            },
            success: (response) => {
                if (response.success) {
                    this.showSuccess(response.data);
                    this.closeActionPanel();
                } else {
                    this.showError(response.data);
                }
            },
            error: () => {
                this.showError('Action failed. Please try again.');
            }
        });
    }

    copyToClipboard(element) {
        const text = jQuery(element).siblings('input').val();
        navigator.clipboard.writeText(text).then(() => {
            this.showSuccess('Copied to clipboard!');
        });
    }

    sendSMS(element) {
        jQuery.ajax({
            url: crypto_actions.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_send_sms',
                nonce: crypto_actions.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.showSuccess('SMS sent successfully');
                } else {
                    this.showError(response.data);
                }
            }
        });
    }

    startVerification(element) {
        const method = jQuery(element).closest('.verification-method').data('method');
        
        if (method === 'selfie') {
            this.startSelfieVerification();
        } else if (method === 'video') {
            this.startVideoVerification();
        }
    }

    startSelfieVerification() {
        // In real implementation, use camera API
        this.showSuccess('Selfie verification started');
        this.actionData.verificationCompleted = true;
        this.updateNextButtonState();
    }

    startVideoVerification() {
        // In real implementation, start video call
        this.showSuccess('Video verification started');
        this.actionData.verificationCompleted = true;
        this.updateNextButtonState();
    }

    loadActionData(action) {
        jQuery.ajax({
            url: crypto_actions.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_action_data',
                action_type: action,
                nonce: crypto_actions.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updateActionData(response.data);
                }
            }
        });
    }

    loadStepData() {
        // Load data specific to current step
        switch (this.currentAction) {
            case 'deposit':
                this.loadDepositStepData();
                break;
            case 'withdraw':
                this.loadWithdrawStepData();
                break;
            case 'trade':
                this.loadTradeStepData();
                break;
            case 'kyc_verify':
                this.loadKYCStepData();
                break;
        }
    }

    loadDepositStepData() {
        switch (this.currentStep) {
            case 2:
                this.loadDepositMethods();
                break;
            case 3:
                this.loadDepositAmountInfo();
                break;
            case 4:
                this.loadDepositConfirmation();
                break;
        }
    }

    loadWithdrawStepData() {
        switch (this.currentStep) {
            case 2:
                this.loadWithdrawMethods();
                break;
            case 3:
                this.loadWithdrawAmountInfo();
                break;
            case 4:
                this.loadVerificationMethods();
                break;
            case 5:
                this.loadWithdrawConfirmation();
                break;
        }
    }

    loadTradeStepData() {
        switch (this.currentStep) {
            case 2:
                this.loadOrderTypes();
                break;
            case 3:
                this.loadTradingForm();
                break;
            case 4:
                this.loadTradeReview();
                break;
        }
    }

    loadKYCStepData() {
        switch (this.currentStep) {
            case 2:
                this.loadVerificationMethods();
                break;
            case 3:
                this.loadKYCSubmission();
                break;
        }
    }

    loadDepositMethods() {
        // Load available deposit methods for selected currency
        const currency = this.actionData.currency;
        // Implementation would fetch from server
    }

    loadWithdrawMethods() {
        // Load available withdrawal methods for selected currency
        const currency = this.actionData.currency;
        // Implementation would fetch from server
    }

    loadOrderTypes() {
        // Load available order types for selected pair
        const pair = this.actionData.pair;
        // Implementation would fetch from server
    }

    loadTradingForm() {
        // Load trading form data
        const pair = this.actionData.pair;
        const orderType = this.actionData.orderType;
        // Implementation would fetch from server
    }

    loadTradeReview() {
        // Load trade review data
        // Implementation would calculate fees, totals, etc.
    }

    loadDepositAmountInfo() {
        // Load amount limits and fees
        const currency = this.actionData.currency;
        const method = this.actionData.method;
        // Implementation would fetch from server
    }

    loadWithdrawAmountInfo() {
        // Load amount limits and fees
        const currency = this.actionData.currency;
        const method = this.actionData.method;
        // Implementation would fetch from server
    }

    loadVerificationMethods() {
        // Load available verification methods
        // Implementation would fetch from server
    }

    loadDepositConfirmation() {
        // Load deposit confirmation data
        // Implementation would generate wallet address, QR code, etc.
    }

    loadWithdrawConfirmation() {
        // Load withdrawal confirmation data
        // Implementation would show final details
    }

    loadKYCSubmission() {
        // Load KYC submission summary
        // Implementation would show uploaded documents and verification status
    }

    updateActionData(data) {
        this.actionData = { ...this.actionData, ...data };
    }

    getCurrentBalance() {
        // Get current balance for selected currency
        return 1.5; // Mock data
    }

    loadUserData() {
        // Load user's current data (balances, KYC status, etc.)
        jQuery.ajax({
            url: crypto_actions.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_action_data',
                action_type: 'dashboard_stats',
                nonce: crypto_actions.nonce
            },
            success: (response) => {
                if (response.success) {
                    this.updateUserInterface(response.data);
                }
            }
        });
    }

    updateUserInterface(data) {
        // Update UI with user data
        if (data.balances) {
            this.updateBalances(data.balances);
        }
        if (data.kyc_status) {
            this.updateKYCStatus(data.kyc_status);
        }
    }

    updateBalances(balances) {
        // Update balance displays
        Object.keys(balances).forEach(currency => {
            jQuery(`[data-currency="${currency}"] .currency-balance`).text(`Balance: ${balances[currency]}`);
        });
    }

    updateKYCStatus(kycStatus) {
        // Update KYC status display
        if (kycStatus.level >= 1) {
            jQuery('.kyc-btn').text('KYC Verified');
        }
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type) {
        // Create notification element
        const notification = jQuery(`
            <div class="crypto-notification ${type}">
                <span class="message">${message}</span>
                <button class="close-notification">&times;</button>
            </div>
        `);

        // Add to page
        jQuery('body').append(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.fadeOut(() => {
                notification.remove();
            });
        }, 5000);

        // Close button
        notification.find('.close-notification').click(() => {
            notification.fadeOut(() => {
                notification.remove();
            });
        });
    }
}

// Initialize when document is ready
jQuery(document).ready(function() {
    new CryptoUserActions();
});
