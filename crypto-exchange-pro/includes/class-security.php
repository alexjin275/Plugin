<?php
/**
 * Security class for Crypto Exchange Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Security {
    
    public function __construct() {
        add_action('init', array($this, 'init_security'));
    }
    
    /**
     * Initialize security measures
     */
    public function init_security() {
        // Add security headers
        add_action('send_headers', array($this, 'add_security_headers'));
        
        // Rate limiting
        add_action('wp_ajax_crypto_exchange_login', array($this, 'rate_limit_login'), 1);
        add_action('wp_ajax_nopriv_crypto_exchange_login', array($this, 'rate_limit_login'), 1);
        
        // Input sanitization
        add_action('init', array($this, 'sanitize_inputs'));
        
        // CSRF protection
        add_action('init', array($this, 'csrf_protection'));
    }
    
    /**
     * Add security headers
     */
    public function add_security_headers() {
        if (!is_admin()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data: https:; font-src \'self\' data:;');
        }
    }
    
    /**
     * Rate limiting for login attempts
     */
    public function rate_limit_login() {
        $ip = $this->get_client_ip();
        $key = 'crypto_login_attempts_' . md5($ip);
        
        $attempts = get_transient($key);
        if ($attempts === false) {
            $attempts = 0;
        }
        
        if ($attempts >= 5) {
            wp_die('Too many login attempts. Please try again later.');
        }
        
        set_transient($key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
    }
    
    /**
     * Sanitize inputs
     */
    public function sanitize_inputs() {
        if ($_POST) {
            $_POST = $this->recursive_sanitize($_POST);
        }
        
        if ($_GET) {
            $_GET = $this->recursive_sanitize($_GET);
        }
    }
    
    /**
     * Recursively sanitize array
     */
    private function recursive_sanitize($data) {
        if (is_array($data)) {
            return array_map(array($this, 'recursive_sanitize'), $data);
        } else {
            return sanitize_text_field($data);
        }
    }
    
    /**
     * CSRF protection
     */
    public function csrf_protection() {
        if ($_POST && !wp_verify_nonce($_POST['crypto_exchange_nonce'] ?? '', 'crypto_exchange_nonce')) {
            if (strpos($_SERVER['REQUEST_URI'], 'crypto-exchange') !== false) {
                wp_die('Security check failed. Please try again.');
            }
        }
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Encrypt sensitive data
     */
    public function encrypt($data) {
        $key = $this->get_encryption_key();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decrypt($data) {
        $key = $this->get_encryption_key();
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Get encryption key
     */
    private function get_encryption_key() {
        $key = get_option('crypto_exchange_encryption_key');
        if (!$key) {
            $key = wp_generate_password(32, true, true);
            update_option('crypto_exchange_encryption_key', $key);
        }
        return $key;
    }
    
    /**
     * Generate secure random string
     */
    public function generate_secure_token($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Hash password securely
     */
    public function hash_password($password) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
    
    /**
     * Verify password
     */
    public function verify_password($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Log security event
     */
    public function log_security_event($event, $details = '') {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'crypto_audit_logs',
            array(
                'user_id' => get_current_user_id(),
                'action' => 'security_' . $event,
                'details' => $details,
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Check for suspicious activity
     */
    public function check_suspicious_activity($user_id) {
        global $wpdb;
        
        $ip = $this->get_client_ip();
        $recent_attempts = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}crypto_audit_logs 
                 WHERE ip_address = %s AND action LIKE 'security_%' 
                 AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                $ip
            )
        );
        
        if ($recent_attempts > 10) {
            $this->log_security_event('suspicious_activity', 'Multiple security events from IP: ' . $ip);
            return true;
        }
        
        return false;
    }
    
    /**
     * Block suspicious IP
     */
    public function block_ip($ip, $reason = 'Suspicious activity') {
        $blocked_ips = get_option('crypto_exchange_blocked_ips', array());
        $blocked_ips[] = array(
            'ip' => $ip,
            'reason' => $reason,
            'blocked_at' => current_time('mysql')
        );
        update_option('crypto_exchange_blocked_ips', $blocked_ips);
    }
    
    /**
     * Check if IP is blocked
     */
    public function is_ip_blocked($ip = null) {
        if (!$ip) {
            $ip = $this->get_client_ip();
        }
        
        $blocked_ips = get_option('crypto_exchange_blocked_ips', array());
        foreach ($blocked_ips as $blocked) {
            if ($blocked['ip'] === $ip) {
                return true;
            }
        }
        
        return false;
    }
}
