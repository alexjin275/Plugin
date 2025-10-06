<?php
/**
 * Advanced KYC System with AI Document Verification
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Advanced_KYC {
    
    private $wpdb;
    private $kyc_config;
    private $ai_services;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->init_kyc_config();
        add_action('crypto_exchange_process_kyc_documents', array($this, 'process_kyc_documents'));
        add_action('wp_ajax_crypto_exchange_upload_kyc_document', array($this, 'upload_kyc_document'));
        add_action('wp_ajax_crypto_exchange_verify_kyc_document', array($this, 'verify_kyc_document'));
        add_action('wp_ajax_crypto_exchange_submit_kyc_application', array($this, 'submit_kyc_application'));
        
        if (!wp_next_scheduled('crypto_exchange_process_kyc_documents')) {
            wp_schedule_event(time(), 'every_5_minutes', 'crypto_exchange_process_kyc_documents');
        }
    }
    
    /**
     * Initialize KYC configuration
     */
    private function init_kyc_config() {
        $this->kyc_config = array(
            'enable_ai_verification' => get_option('crypto_exchange_ai_kyc_enabled', true),
            'enable_face_verification' => get_option('crypto_exchange_face_verification_enabled', true),
            'enable_document_ocr' => get_option('crypto_exchange_document_ocr_enabled', true),
            'enable_liveness_detection' => get_option('crypto_exchange_liveness_detection_enabled', true),
            'kyc_levels' => array(
                'basic' => array(
                    'name' => 'Basic Verification',
                    'requirements' => array('email', 'phone'),
                    'limits' => array('daily' => 1000, 'monthly' => 10000)
                ),
                'intermediate' => array(
                    'name' => 'Intermediate Verification',
                    'requirements' => array('email', 'phone', 'id_document', 'address_proof'),
                    'limits' => array('daily' => 10000, 'monthly' => 100000)
                ),
                'advanced' => array(
                    'name' => 'Advanced Verification',
                    'requirements' => array('email', 'phone', 'id_document', 'address_proof', 'selfie', 'face_verification'),
                    'limits' => array('daily' => 100000, 'monthly' => 1000000)
                )
            )
        );
    }
    
    /**
     * Process KYC documents
     */
    public function process_kyc_documents() {
        $pending_documents = $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_kyc_documents 
             WHERE status = 'pending' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        
        foreach ($pending_documents as $document) {
            $this->process_document($document);
        }
    }
    
    /**
     * Process individual document
     */
    private function process_document($document) {
        $document_type = $document->document_type;
        $file_path = $document->file_path;
        
        // Extract text using OCR
        $extracted_text = $this->extract_text_from_document($file_path);
        
        // Verify document authenticity
        $verification_result = $this->verify_document_authenticity($document_type, $file_path, $extracted_text);
        
        // Extract structured data
        $structured_data = $this->extract_structured_data($document_type, $extracted_text);
        
        // Update document status
        $this->update_document_status($document->id, $verification_result, $structured_data);
        
        // Check if user can be upgraded to next KYC level
        $this->check_kyc_level_upgrade($document->user_id);
    }
    
    /**
     * Upload KYC document
     */
    public function upload_kyc_document() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $document_type = sanitize_text_field($_POST['document_type']);
        $file = $_FILES['document_file'];
        
        // Validate file
        $validation_result = $this->validate_document_file($file, $document_type);
        if (!$validation_result['valid']) {
            wp_send_json_error($validation_result['message']);
        }
        
        // Upload file
        $upload_result = $this->upload_document_file($file, $user_id, $document_type);
        if (!$upload_result['success']) {
            wp_send_json_error($upload_result['message']);
        }
        
        // Create document record
        $document_id = $this->create_document_record($user_id, $document_type, $upload_result['file_path']);
        
        wp_send_json_success(array(
            'document_id' => $document_id,
            'message' => 'Document uploaded successfully'
        ));
    }
    
    /**
     * Verify KYC document
     */
    public function verify_kyc_document() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $document_id = intval($_POST['document_id']);
        $selfie_file = $_FILES['selfie_file'] ?? null;
        
        $document = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_kyc_documents 
                 WHERE id = %d AND user_id = %d",
                $document_id,
                $user_id
            )
        );
        
        if (!$document) {
            wp_send_json_error('Document not found');
        }
        
        // Process document if not already processed
        if ($document->status === 'pending') {
            $this->process_document($document);
        }
        
        // Perform face verification if selfie provided
        if ($selfie_file && $this->kyc_config['enable_face_verification']) {
            $face_verification_result = $this->perform_face_verification($document->file_path, $selfie_file);
            $this->update_face_verification($document_id, $face_verification_result);
        }
        
        wp_send_json_success('Document verification completed');
    }
    
    /**
     * Submit KYC application
     */
    public function submit_kyc_application() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $kyc_level = sanitize_text_field($_POST['kyc_level']);
        $personal_info = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'date_of_birth' => sanitize_text_field($_POST['date_of_birth']),
            'address' => sanitize_textarea_field($_POST['address']),
            'city' => sanitize_text_field($_POST['city']),
            'state' => sanitize_text_field($_POST['state']),
            'country' => sanitize_text_field($_POST['country']),
            'postal_code' => sanitize_text_field($_POST['postal_code']),
            'phone' => sanitize_text_field($_POST['phone'])
        );
        
        // Validate required fields
        $validation_result = $this->validate_kyc_application($kyc_level, $personal_info);
        if (!$validation_result['valid']) {
            wp_send_json_error($validation_result['message']);
        }
        
        // Create KYC application
        $application_id = $this->create_kyc_application($user_id, $kyc_level, $personal_info);
        
        // Update user KYC status
        update_user_meta($user_id, 'crypto_kyc_status', 'pending');
        update_user_meta($user_id, 'crypto_kyc_level', $kyc_level);
        update_user_meta($user_id, 'crypto_kyc_application_id', $application_id);
        
        wp_send_json_success(array(
            'application_id' => $application_id,
            'message' => 'KYC application submitted successfully'
        ));
    }
    
    /**
     * Extract text from document using OCR
     */
    private function extract_text_from_document($file_path) {
        if (!$this->kyc_config['enable_document_ocr']) {
            return '';
        }
        
        // Mock OCR implementation
        return 'Mock extracted text from document';
    }
    
    /**
     * Verify document authenticity
     */
    private function verify_document_authenticity($document_type, $file_path, $extracted_text) {
        if (!$this->kyc_config['enable_ai_verification']) {
            return array('verified' => true, 'confidence' => 1.0);
        }
        
        // Mock verification implementation
        return array('verified' => true, 'confidence' => 0.85);
    }
    
    /**
     * Extract structured data from document
     */
    private function extract_structured_data($document_type, $extracted_text) {
        $structured_data = array();
        
        switch ($document_type) {
            case 'passport':
                $structured_data = $this->extract_passport_data($extracted_text);
                break;
            case 'drivers_license':
                $structured_data = $this->extract_drivers_license_data($extracted_text);
                break;
            case 'national_id':
                $structured_data = $this->extract_national_id_data($extracted_text);
                break;
        }
        
        return $structured_data;
    }
    
    /**
     * Extract passport data
     */
    private function extract_passport_data($text) {
        $data = array();
        
        // Mock data extraction
        if (preg_match('/passport[:\s]*([A-Z0-9]{6,12})/i', $text, $matches)) {
            $data['document_number'] = $matches[1];
        }
        
        return $data;
    }
    
    /**
     * Extract driver's license data
     */
    private function extract_drivers_license_data($text) {
        $data = array();
        
        // Mock data extraction
        if (preg_match('/license[:\s]*([A-Z0-9]{6,12})/i', $text, $matches)) {
            $data['license_number'] = $matches[1];
        }
        
        return $data;
    }
    
    /**
     * Extract national ID data
     */
    private function extract_national_id_data($text) {
        $data = array();
        
        // Mock data extraction
        if (preg_match('/id[:\s]*([A-Z0-9]{6,12})/i', $text, $matches)) {
            $data['id_number'] = $matches[1];
        }
        
        return $data;
    }
    
    /**
     * Perform face verification
     */
    private function perform_face_verification($document_path, $selfie_file) {
        if (!$this->kyc_config['enable_face_verification']) {
            return array('verified' => true, 'confidence' => 1.0);
        }
        
        // Mock face verification
        return array('verified' => true, 'confidence' => 0.9);
    }
    
    /**
     * Validate document file
     */
    private function validate_document_file($file, $document_type) {
        $allowed_types = array('image/jpeg', 'image/png', 'image/jpg', 'application/pdf');
        $max_size = 10 * 1024 * 1024; // 10MB
        
        if (!in_array($file['type'], $allowed_types)) {
            return array('valid' => false, 'message' => 'Invalid file type. Please upload JPEG, PNG, or PDF.');
        }
        
        if ($file['size'] > $max_size) {
            return array('valid' => false, 'message' => 'File too large. Maximum size is 10MB.');
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array('valid' => false, 'message' => 'File upload error.');
        }
        
        return array('valid' => true);
    }
    
    /**
     * Upload document file
     */
    private function upload_document_file($file, $user_id, $document_type) {
        $upload_dir = wp_upload_dir();
        $kyc_dir = $upload_dir['basedir'] . '/crypto-exchange-kyc/' . $user_id;
        
        if (!file_exists($kyc_dir)) {
            wp_mkdir_p($kyc_dir);
        }
        
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_name = $document_type . '_' . time() . '.' . $file_extension;
        $file_path = $kyc_dir . '/' . $file_name;
        
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            return array('success' => true, 'file_path' => $file_path);
        } else {
            return array('success' => false, 'message' => 'Failed to upload file.');
        }
    }
    
    /**
     * Create document record
     */
    private function create_document_record($user_id, $document_type, $file_path) {
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_kyc_documents',
            array(
                'user_id' => $user_id,
                'document_type' => $document_type,
                'file_path' => $file_path,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Update document status
     */
    private function update_document_status($document_id, $verification_result, $structured_data) {
        $status = $verification_result['verified'] ? 'verified' : 'rejected';
        $confidence = $verification_result['confidence'];
        
        $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_kyc_documents',
            array(
                'status' => $status,
                'verification_data' => json_encode($verification_result),
                'extracted_data' => json_encode($structured_data),
                'confidence_score' => $confidence,
                'verified_at' => current_time('mysql')
            ),
            array('id' => $document_id),
            array('%s', '%s', '%s', '%f', '%s'),
            array('%d')
        );
    }
    
    /**
     * Update face verification
     */
    private function update_face_verification($document_id, $face_result) {
        $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_kyc_documents',
            array(
                'face_verification' => $face_result['verified'] ? 1 : 0,
                'face_confidence' => $face_result['confidence']
            ),
            array('id' => $document_id),
            array('%d', '%f'),
            array('%d')
        );
    }
    
    /**
     * Check KYC level upgrade
     */
    private function check_kyc_level_upgrade($user_id) {
        $current_level = get_user_meta($user_id, 'crypto_kyc_level', true);
        $documents = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_kyc_documents 
                 WHERE user_id = %d AND status = 'verified'",
                $user_id
            )
        );
        
        $verified_document_types = array_column($documents, 'document_type');
        
        foreach ($this->kyc_config['kyc_levels'] as $level => $config) {
            if ($this->has_required_documents($verified_document_types, $config['requirements'])) {
                if ($this->is_higher_level($level, $current_level)) {
                    update_user_meta($user_id, 'crypto_kyc_level', $level);
                    update_user_meta($user_id, 'crypto_kyc_status', 'verified');
                    $this->send_kyc_upgrade_notification($user_id, $level);
                }
                break;
            }
        }
    }
    
    /**
     * Check if user has required documents
     */
    private function has_required_documents($verified_document_types, $requirements) {
        foreach ($requirements as $requirement) {
            if (!in_array($requirement, $verified_document_types)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Check if level is higher than current
     */
    private function is_higher_level($new_level, $current_level) {
        $levels = array('basic', 'intermediate', 'advanced');
        $new_index = array_search($new_level, $levels);
        $current_index = array_search($current_level, $levels);
        
        return $new_index > $current_index;
    }
    
    /**
     * Send KYC upgrade notification
     */
    private function send_kyc_upgrade_notification($user_id, $level) {
        $user = get_user_by('id', $user_id);
        if ($user) {
            $subject = 'KYC Level Upgraded - ' . get_bloginfo('name');
            $message = sprintf(
                'Your KYC verification has been upgraded to %s level. You now have access to higher trading limits.',
                ucfirst($level)
            );
            wp_mail($user->user_email, $subject, $message);
        }
    }
    
    /**
     * Validate KYC application
     */
    private function validate_kyc_application($kyc_level, $personal_info) {
        $required_fields = array('first_name', 'last_name', 'date_of_birth', 'address', 'city', 'state', 'country', 'phone');
        
        foreach ($required_fields as $field) {
            if (empty($personal_info[$field])) {
                return array('valid' => false, 'message' => 'Missing required field: ' . $field);
            }
        }
        
        return array('valid' => true);
    }
    
    /**
     * Create KYC application
     */
    private function create_kyc_application($user_id, $kyc_level, $personal_info) {
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_kyc_applications',
            array(
                'user_id' => $user_id,
                'kyc_level' => $kyc_level,
                'personal_info' => json_encode($personal_info),
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Get KYC status
     */
    public function get_kyc_status($user_id) {
        $kyc_status = get_user_meta($user_id, 'crypto_kyc_status', true);
        $kyc_level = get_user_meta($user_id, 'crypto_kyc_level', true);
        
        $documents = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_kyc_documents 
                 WHERE user_id = %d ORDER BY created_at DESC",
                $user_id
            )
        );
        
        return array(
            'status' => $kyc_status ?: 'not_started',
            'level' => $kyc_level ?: 'basic',
            'documents' => $documents,
            'limits' => $this->get_kyc_limits($kyc_level ?: 'basic')
        );
    }
    
    /**
     * Get KYC limits
     */
    private function get_kyc_limits($kyc_level) {
        if (isset($this->kyc_config['kyc_levels'][$kyc_level])) {
            return $this->kyc_config['kyc_levels'][$kyc_level]['limits'];
        }
        
        return $this->kyc_config['kyc_levels']['basic']['limits'];
    }
    
    /**
     * Create KYC tables
     */
    public function create_kyc_tables() {
        // KYC applications table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_kyc_applications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            kyc_level varchar(50) NOT NULL,
            personal_info text,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
