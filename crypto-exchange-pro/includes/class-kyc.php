<?php
/**
 * KYC class for Crypto Exchange Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_KYC {
    
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Upload KYC document
     */
    public function upload_document($file, $document_type) {
        $user_id = get_current_user_id();
        
        // Validate file
        if (!$this->validate_file($file)) {
            return array(
                'success' => false,
                'message' => 'Invalid file format or size'
            );
        }
        
        // Validate document type
        if (!$this->validate_document_type($document_type)) {
            return array(
                'success' => false,
                'message' => 'Invalid document type'
            );
        }
        
        // Upload file
        $upload_result = $this->upload_file($file, $user_id, $document_type);
        
        if ($upload_result['success']) {
            // Save document record
            $document_id = $this->save_document_record($user_id, $document_type, $upload_result['file_path']);
            
            if ($document_id) {
                return array(
                    'success' => true,
                    'message' => 'Document uploaded successfully',
                    'document_id' => $document_id
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Failed to save document record'
                );
            }
        } else {
            return $upload_result;
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validate_file($file) {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        
        // Check file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            return false;
        }
        
        // Check file type
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'application/pdf');
        if (!in_array($file['type'], $allowed_types)) {
            return false;
        }
        
        // Check file extension
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf');
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed_extensions)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate document type
     */
    private function validate_document_type($type) {
        $allowed_types = array(
            'passport',
            'drivers_license',
            'national_id',
            'utility_bill',
            'bank_statement',
            'selfie'
        );
        
        return in_array($type, $allowed_types);
    }
    
    /**
     * Upload file to server
     */
    private function upload_file($file, $user_id, $document_type) {
        $upload_dir = wp_upload_dir();
        $kyc_dir = $upload_dir['basedir'] . '/crypto-kyc/' . $user_id;
        
        // Create directory if it doesn't exist
        if (!file_exists($kyc_dir)) {
            wp_mkdir_p($kyc_dir);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $document_type . '_' . time() . '_' . wp_generate_password(8, false) . '.' . $extension;
        $file_path = $kyc_dir . '/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            return array(
                'success' => true,
                'file_path' => $file_path,
                'file_url' => $upload_dir['baseurl'] . '/crypto-kyc/' . $user_id . '/' . $filename
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to upload file'
            );
        }
    }
    
    /**
     * Save document record to database
     */
    private function save_document_record($user_id, $document_type, $file_path) {
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_kyc_documents',
            array(
                'user_id' => $user_id,
                'document_type' => $document_type,
                'document_path' => $file_path,
                'status' => 'pending'
            ),
            array('%d', '%s', '%s', '%s')
        );
        
        return $result ? $this->wpdb->insert_id : false;
    }
    
    /**
     * Get user KYC documents
     */
    public function get_user_documents($user_id) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_kyc_documents 
                 WHERE user_id = %d 
                 ORDER BY created_at DESC",
                $user_id
            )
        );
    }
    
    /**
     * Get KYC status
     */
    public function get_kyc_status($user_id) {
        $crypto_user = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT kyc_status, kyc_level FROM {$this->wpdb->prefix}crypto_users WHERE user_id = %d",
                $user_id
            )
        );
        
        return $crypto_user ? array(
            'status' => $crypto_user->kyc_status,
            'level' => $crypto_user->kyc_level
        ) : array(
            'status' => 'pending',
            'level' => 0
        );
    }
    
    /**
     * Update KYC status
     */
    public function update_kyc_status($user_id, $status, $level = 0) {
        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_users',
            array(
                'kyc_status' => $status,
                'kyc_level' => $level
            ),
            array('user_id' => $user_id),
            array('%s', '%d'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Verify document
     */
    public function verify_document($document_id, $status, $verified_by = null, $rejection_reason = '') {
        $verified_by = $verified_by ?: get_current_user_id();
        
        $result = $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_kyc_documents',
            array(
                'status' => $status,
                'verified_by' => $verified_by,
                'verified_at' => current_time('mysql'),
                'rejection_reason' => $rejection_reason
            ),
            array('id' => $document_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );
        
        if ($result) {
            // Update user KYC status if all documents are verified
            $this->update_user_kyc_status($document_id);
        }
        
        return $result !== false;
    }
    
    /**
     * Update user KYC status based on documents
     */
    private function update_user_kyc_status($document_id) {
        $document = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT user_id FROM {$this->wpdb->prefix}crypto_kyc_documents WHERE id = %d",
                $document_id
            )
        );
        
        if (!$document) {
            return;
        }
        
        $user_id = $document->user_id;
        
        // Check if all required documents are verified
        $required_documents = array('passport', 'drivers_license', 'national_id');
        $verified_documents = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT document_type FROM {$this->wpdb->prefix}crypto_kyc_documents 
                 WHERE user_id = %d AND status = 'verified'",
                $user_id
            )
        );
        
        $verified_types = array_column($verified_documents, 'document_type');
        $has_all_required = count(array_intersect($required_documents, $verified_types)) > 0;
        
        if ($has_all_required) {
            $this->update_kyc_status($user_id, 'verified', 1);
        }
    }
    
    /**
     * Get KYC requirements
     */
    public function get_kyc_requirements() {
        return array(
            'level_1' => array(
                'name' => 'Basic Verification',
                'description' => 'Verify your identity with a government-issued ID',
                'documents' => array('passport', 'drivers_license', 'national_id'),
                'trading_limit' => 10000
            ),
            'level_2' => array(
                'name' => 'Enhanced Verification',
                'description' => 'Provide proof of address and additional documents',
                'documents' => array('utility_bill', 'bank_statement'),
                'trading_limit' => 100000
            ),
            'level_3' => array(
                'name' => 'Premium Verification',
                'description' => 'Complete video verification and provide additional documentation',
                'documents' => array('selfie'),
                'trading_limit' => 1000000
            )
        );
    }
    
    /**
     * Check if user can trade
     */
    public function can_trade($user_id, $amount) {
        $kyc_status = $this->get_kyc_status($user_id);
        $requirements = $this->get_kyc_requirements();
        
        $current_level = $kyc_status['level'];
        $max_amount = $requirements['level_' . $current_level]['trading_limit'] ?? 0;
        
        return $amount <= $max_amount;
    }
    
    /**
     * Render KYC page
     */
    public function render() {
        $user_id = get_current_user_id();
        $kyc_status = $this->get_kyc_status($user_id);
        $documents = $this->get_user_documents($user_id);
        $requirements = $this->get_kyc_requirements();
        ?>
        <div class="crypto-kyc-container">
            <div class="kyc-header">
                <h1>Identity Verification (KYC)</h1>
                <div class="kyc-status">
                    <span class="status-label">Status:</span>
                    <span class="status-value status-<?php echo $kyc_status['status']; ?>">
                        <?php echo ucfirst($kyc_status['status']); ?>
                    </span>
                </div>
            </div>
            
            <div class="kyc-content">
                <div class="kyc-progress">
                    <h3>Verification Progress</h3>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo ($kyc_status['level'] / 3) * 100; ?>%"></div>
                    </div>
                    <p>Level <?php echo $kyc_status['level']; ?> of 3</p>
                </div>
                
                <div class="kyc-requirements">
                    <h3>Verification Requirements</h3>
                    <?php foreach ($requirements as $level => $requirement): ?>
                    <div class="requirement-level <?php echo $kyc_status['level'] >= substr($level, -1) ? 'completed' : ''; ?>">
                        <h4><?php echo $requirement['name']; ?></h4>
                        <p><?php echo $requirement['description']; ?></p>
                        <div class="trading-limit">
                            Trading Limit: $<?php echo number_format($requirement['trading_limit']); ?>
                        </div>
                        <div class="required-documents">
                            <strong>Required Documents:</strong>
                            <ul>
                                <?php foreach ($requirement['documents'] as $doc): ?>
                                <li><?php echo ucfirst(str_replace('_', ' ', $doc)); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="kyc-upload">
                    <h3>Upload Documents</h3>
                    <form id="kyc-upload-form" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="document-type">Document Type</label>
                            <select name="document_type" id="document-type" required>
                                <option value="">Select Document Type</option>
                                <option value="passport">Passport</option>
                                <option value="drivers_license">Driver's License</option>
                                <option value="national_id">National ID</option>
                                <option value="utility_bill">Utility Bill</option>
                                <option value="bank_statement">Bank Statement</option>
                                <option value="selfie">Selfie</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="document-file">Document File</label>
                            <input type="file" name="document" id="document-file" accept="image/*,application/pdf" required>
                            <small>Accepted formats: JPG, PNG, GIF, PDF (Max 10MB)</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Upload Document</button>
                    </form>
                </div>
                
                <div class="kyc-documents">
                    <h3>Uploaded Documents</h3>
                    <div class="documents-list">
                        <?php if (empty($documents)): ?>
                        <p>No documents uploaded yet.</p>
                        <?php else: ?>
                        <?php foreach ($documents as $document): ?>
                        <div class="document-item">
                            <div class="document-info">
                                <h4><?php echo ucfirst(str_replace('_', ' ', $document->document_type)); ?></h4>
                                <p>Uploaded: <?php echo date('M j, Y H:i', strtotime($document->created_at)); ?></p>
                            </div>
                            <div class="document-status">
                                <span class="status status-<?php echo $document->status; ?>">
                                    <?php echo ucfirst($document->status); ?>
                                </span>
                                <?php if ($document->status === 'rejected' && $document->rejection_reason): ?>
                                <p class="rejection-reason"><?php echo $document->rejection_reason; ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="document-actions">
                                <a href="<?php echo $document->document_path; ?>" target="_blank" class="btn btn-small">View</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
