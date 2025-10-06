<?php
/**
 * Advanced Security System with Hardware Wallet Support
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Advanced_Security {
    
    private $wpdb;
    private $security_config;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->init_security_config();
        add_action('crypto_exchange_security_scan', array($this, 'run_security_scan'));
        add_action('crypto_exchange_audit_logs', array($this, 'cleanup_audit_logs'));
        add_action('wp_ajax_crypto_exchange_verify_hardware_wallet', array($this, 'verify_hardware_wallet'));
        add_action('wp_ajax_crypto_exchange_generate_2fa', array($this, 'generate_2fa'));
        add_action('wp_ajax_crypto_exchange_verify_2fa', array($this, 'verify_2fa'));
        
        if (!wp_next_scheduled('crypto_exchange_security_scan')) {
            wp_schedule_event(time(), 'hourly', 'crypto_exchange_security_scan');
        }
        
        if (!wp_next_scheduled('crypto_exchange_audit_logs')) {
            wp_schedule_event(time(), 'daily', 'crypto_exchange_audit_logs');
        }
    }
    
    /**
     * Initialize security configuration
     */
    private function init_security_config() {
        $this->security_config = array(
            'max_login_attempts' => 5,
            'lockout_duration' => 900, // 15 minutes
            'session_timeout' => 3600, // 1 hour
            'password_min_length' => 12,
            'password_require_special' => true,
            'password_require_numbers' => true,
            'password_require_uppercase' => true,
            'password_require_lowercase' => true,
            'enable_2fa' => true,
            'enable_hardware_wallet' => true,
            'enable_biometric' => false,
            'enable_ip_whitelist' => false,
            'enable_geo_restrictions' => false,
            'enable_risk_scoring' => true,
            'enable_anomaly_detection' => true,
            'enable_ml_security' => false,
            'encryption_algorithm' => 'AES-256-GCM',
            'hash_algorithm' => 'argon2id',
            'jwt_secret' => wp_generate_password(64, true, true),
            'api_rate_limit' => 1000, // requests per hour
            'withdrawal_2fa_required' => true,
            'trading_2fa_required' => false,
            'deposit_2fa_required' => false
        );
    }
    
    /**
     * Run security scan
     */
    public function run_security_scan() {
        $this->scan_suspicious_activities();
        $this->check_failed_logins();
        $this->monitor_api_usage();
        $this->check_system_integrity();
        $this->scan_malicious_patterns();
    }
    
    /**
     * Scan for suspicious activities
     */
    private function scan_suspicious_activities() {
        // Check for unusual trading patterns
        $suspicious_trades = $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_trades 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
             AND amount > 10000
             ORDER BY created_at DESC"
        );
        
        foreach ($suspicious_trades as $trade) {
            $this->log_security_event('suspicious_trade', array(
                'trade_id' => $trade->id,
                'user_id' => $trade->buyer_id,
                'amount' => $trade->amount,
                'price' => $trade->price,
                'risk_score' => $this->calculate_trade_risk_score($trade)
            ));
        }
        
        // Check for rapid consecutive orders
        $rapid_orders = $this->wpdb->get_results(
            "SELECT user_id, COUNT(*) as order_count 
             FROM {$this->wpdb->prefix}crypto_orders 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
             GROUP BY user_id 
             HAVING order_count > 10"
        );
        
        foreach ($rapid_orders as $order) {
            $this->log_security_event('rapid_orders', array(
                'user_id' => $order->user_id,
                'order_count' => $order->order_count,
                'risk_score' => min($order->order_count * 10, 100)
            ));
        }
    }
    
    /**
     * Check failed login attempts
     */
    private function check_failed_logins() {
        $failed_logins = $this->wpdb->get_results(
            "SELECT ip_address, COUNT(*) as attempt_count 
             FROM {$this->wpdb->prefix}crypto_audit_logs 
             WHERE event_type = 'failed_login' 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
             GROUP BY ip_address 
             HAVING attempt_count >= 5"
        );
        
        foreach ($failed_logins as $login) {
            $this->block_ip_address($login->ip_address, 3600); // Block for 1 hour
            $this->log_security_event('ip_blocked', array(
                'ip_address' => $login->ip_address,
                'attempt_count' => $login->attempt_count,
                'block_duration' => 3600
            ));
        }
    }
    
    /**
     * Monitor API usage
     */
    private function monitor_api_usage() {
        $api_usage = $this->wpdb->get_results(
            "SELECT user_id, COUNT(*) as request_count 
             FROM {$this->wpdb->prefix}crypto_audit_logs 
             WHERE event_type = 'api_request' 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
             GROUP BY user_id 
             HAVING request_count > 500"
        );
        
        foreach ($api_usage as $usage) {
            $this->log_security_event('api_abuse', array(
                'user_id' => $usage->user_id,
                'request_count' => $usage->request_count,
                'action' => 'rate_limit_applied'
            ));
            
            // Apply rate limiting
            $this->apply_rate_limit($usage->user_id, 300); // 5 minutes
        }
    }
    
    /**
     * Check system integrity
     */
    private function check_system_integrity() {
        // Check for unauthorized file modifications
        $critical_files = array(
            ABSPATH . 'wp-config.php',
            CRYPTO_EXCHANGE_PLUGIN_DIR . 'crypto-exchange-plugin.php',
            CRYPTO_EXCHANGE_PLUGIN_DIR . 'includes/class-security.php'
        );
        
        foreach ($critical_files as $file) {
            if (file_exists($file)) {
                $current_hash = hash_file('sha256', $file);
                $stored_hash = get_option('crypto_exchange_file_hash_' . basename($file));
                
                if ($stored_hash && $current_hash !== $stored_hash) {
                    $this->log_security_event('file_tampering', array(
                        'file' => $file,
                        'stored_hash' => $stored_hash,
                        'current_hash' => $current_hash,
                        'severity' => 'critical'
                    ));
                } else {
                    update_option('crypto_exchange_file_hash_' . basename($file), $current_hash);
                }
            }
        }
    }
    
    /**
     * Scan for malicious patterns
     */
    private function scan_malicious_patterns() {
        // Check for SQL injection attempts
        $malicious_queries = $this->wpdb->get_results(
            "SELECT * FROM {$this->wpdb->prefix}crypto_audit_logs 
             WHERE event_type = 'api_request' 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
             AND (request_data LIKE '%UNION%' 
                  OR request_data LIKE '%DROP%' 
                  OR request_data LIKE '%DELETE%'
                  OR request_data LIKE '%INSERT%'
                  OR request_data LIKE '%UPDATE%')"
        );
        
        foreach ($malicious_queries as $query) {
            $this->log_security_event('sql_injection_attempt', array(
                'user_id' => $query->user_id,
                'ip_address' => $query->ip_address,
                'request_data' => $query->request_data,
                'severity' => 'high'
            ));
        }
    }
    
    /**
     * Calculate trade risk score
     */
    private function calculate_trade_risk_score($trade) {
        $risk_score = 0;
        
        // Amount-based risk
        if ($trade->amount > 50000) {
            $risk_score += 30;
        } elseif ($trade->amount > 10000) {
            $risk_score += 15;
        }
        
        // Time-based risk (trading outside normal hours)
        $hour = date('H', strtotime($trade->created_at));
        if ($hour < 6 || $hour > 22) {
            $risk_score += 10;
        }
        
        // User history risk
        $user_trades = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_trades 
                 WHERE buyer_id = %d OR seller_id = %d",
                $trade->buyer_id,
                $trade->buyer_id
            )
        );
        
        if ($user_trades < 5) {
            $risk_score += 20; // New user
        }
        
        // KYC status risk
        $kyc_status = get_user_meta($trade->buyer_id, 'crypto_kyc_status', true);
        if ($kyc_status !== 'verified') {
            $risk_score += 25;
        }
        
        return min($risk_score, 100);
    }
    
    /**
     * Block IP address
     */
    private function block_ip_address($ip_address, $duration) {
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_blocked_ips',
            array(
                'ip_address' => $ip_address,
                'reason' => 'security_violation',
                'blocked_until' => date('Y-m-d H:i:s', time() + $duration),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Apply rate limiting
     */
    private function apply_rate_limit($user_id, $duration) {
        update_user_meta($user_id, 'crypto_rate_limited_until', time() + $duration);
    }
    
    /**
     * Check if IP is blocked
     */
    public function is_ip_blocked($ip_address) {
        $blocked = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_blocked_ips 
                 WHERE ip_address = %s 
                 AND blocked_until > NOW()",
                $ip_address
            )
        );
        
        return $blocked ? true : false;
    }
    
    /**
     * Check if user is rate limited
     */
    public function is_user_rate_limited($user_id) {
        $rate_limited_until = get_user_meta($user_id, 'crypto_rate_limited_until', true);
        return $rate_limited_until && time() < $rate_limited_until;
    }
    
    /**
     * Generate 2FA secret
     */
    public function generate_2fa() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $secret = $this->generate_totp_secret();
        
        update_user_meta($user_id, 'crypto_2fa_secret', $secret);
        
        $qr_code = $this->generate_qr_code($user_id, $secret);
        
        wp_send_json_success(array(
            'secret' => $secret,
            'qr_code' => $qr_code
        ));
    }
    
    /**
     * Verify 2FA code
     */
    public function verify_2fa() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $code = sanitize_text_field($_POST['code']);
        $secret = get_user_meta($user_id, 'crypto_2fa_secret', true);
        
        if (!$secret) {
            wp_send_json_error('2FA not enabled');
        }
        
        if ($this->verify_totp_code($secret, $code)) {
            update_user_meta($user_id, 'crypto_2fa_enabled', true);
            wp_send_json_success('2FA verified successfully');
        } else {
            wp_send_json_error('Invalid 2FA code');
        }
    }
    
    /**
     * Generate TOTP secret
     */
    private function generate_totp_secret() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $secret;
    }
    
    /**
     * Verify TOTP code
     */
    private function verify_totp_code($secret, $code) {
        $time_window = 1; // Allow 1 time window tolerance
        
        for ($i = -$time_window; $i <= $time_window; $i++) {
            $timestamp = floor(time() / 30) + $i;
            $expected_code = $this->generate_totp_code($secret, $timestamp);
            
            if (hash_equals($expected_code, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate TOTP code
     */
    private function generate_totp_code($secret, $timestamp) {
        $key = $this->base32_decode($secret);
        $time = pack('N*', 0) . pack('N*', $timestamp);
        $hmac = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hmac[19]) & 0xf;
        $code = (
            ((ord($hmac[$offset]) & 0x7f) << 24) |
            ((ord($hmac[$offset + 1]) & 0xff) << 16) |
            ((ord($hmac[$offset + 2]) & 0xff) << 8) |
            (ord($hmac[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Base32 decode
     */
    private function base32_decode($input) {
        $map = array(
            'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7,
            'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15,
            'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23,
            'Y' => 24, 'Z' => 25, '2' => 26, '3' => 27, '4' => 28, '5' => 29, '6' => 30, '7' => 31
        );
        
        $input = strtoupper($input);
        $output = '';
        $v = 0;
        $vbits = 0;
        
        for ($i = 0; $i < strlen($input); $i++) {
            $v <<= 5;
            $v += $map[$input[$i]];
            $vbits += 5;
            
            if ($vbits >= 8) {
                $output .= chr(($v >> ($vbits - 8)) & 255);
                $vbits -= 8;
            }
        }
        
        return $output;
    }
    
    /**
     * Generate QR code for 2FA
     */
    private function generate_qr_code($user_id, $secret) {
        $user = get_user_by('id', $user_id);
        $issuer = get_bloginfo('name');
        $account = $user->user_email;
        
        $otpauth_url = "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}";
        
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($otpauth_url);
    }
    
    /**
     * Verify hardware wallet
     */
    public function verify_hardware_wallet() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $wallet_type = sanitize_text_field($_POST['wallet_type']);
        $public_key = sanitize_text_field($_POST['public_key']);
        $signature = sanitize_text_field($_POST['signature']);
        $message = sanitize_text_field($_POST['message']);
        
        // Verify signature
        if ($this->verify_hardware_signature($wallet_type, $public_key, $signature, $message)) {
            update_user_meta($user_id, 'crypto_hardware_wallet_enabled', true);
            update_user_meta($user_id, 'crypto_hardware_wallet_type', $wallet_type);
            update_user_meta($user_id, 'crypto_hardware_wallet_public_key', $public_key);
            
            wp_send_json_success('Hardware wallet verified successfully');
        } else {
            wp_send_json_error('Invalid hardware wallet signature');
        }
    }
    
    /**
     * Verify hardware wallet signature
     */
    private function verify_hardware_signature($wallet_type, $public_key, $signature, $message) {
        // Simplified implementation - in production, use proper cryptographic libraries
        switch ($wallet_type) {
            case 'ledger':
                return $this->verify_ledger_signature($public_key, $signature, $message);
            case 'trezor':
                return $this->verify_trezor_signature($public_key, $signature, $message);
            case 'keepkey':
                return $this->verify_keepkey_signature($public_key, $signature, $message);
            default:
                return false;
        }
    }
    
    /**
     * Verify Ledger signature
     */
    private function verify_ledger_signature($public_key, $signature, $message) {
        // Simplified implementation
        $expected_signature = hash('sha256', $public_key . $message . 'ledger');
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Verify Trezor signature
     */
    private function verify_trezor_signature($public_key, $signature, $message) {
        // Simplified implementation
        $expected_signature = hash('sha256', $public_key . $message . 'trezor');
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Verify KeepKey signature
     */
    private function verify_keepkey_signature($public_key, $signature, $message) {
        // Simplified implementation
        $expected_signature = hash('sha256', $public_key . $message . 'keepkey');
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Log security event
     */
    private function log_security_event($event_type, $data) {
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_audit_logs',
            array(
                'user_id' => get_current_user_id(),
                'event_type' => $event_type,
                'event_data' => json_encode($data),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
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
     * Cleanup audit logs
     */
    public function cleanup_audit_logs() {
        // Keep logs for 90 days
        $this->wpdb->query(
            "DELETE FROM {$this->wpdb->prefix}crypto_audit_logs 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );
        
        // Cleanup expired IP blocks
        $this->wpdb->query(
            "DELETE FROM {$this->wpdb->prefix}crypto_blocked_ips 
             WHERE blocked_until < NOW()"
        );
    }
    
    /**
     * Get security dashboard data
     */
    public function get_security_dashboard() {
        $stats = array(
            'total_events' => $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_audit_logs 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            ),
            'failed_logins' => $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_audit_logs 
                 WHERE event_type = 'failed_login' 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            ),
            'blocked_ips' => $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_blocked_ips 
                 WHERE blocked_until > NOW()"
            ),
            'suspicious_activities' => $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_audit_logs 
                 WHERE event_type IN ('suspicious_trade', 'rapid_orders', 'api_abuse') 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            )
        );
        
        return $stats;
    }
    
    /**
     * Create security tables
     */
    public function create_security_tables() {
        // Blocked IPs table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_blocked_ips (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            reason varchar(255) NOT NULL,
            blocked_until datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ip_address (ip_address),
            KEY blocked_until (blocked_until)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
    /**
     * Get security dashboard data
     */
    public function get_security_dashboard() {
        $stats = array(
            'total_events' => $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_audit_logs 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            ),
            'failed_logins' => $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_audit_logs 
                 WHERE event_type = 'failed_login' 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            ),
            'blocked_ips' => $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_blocked_ips 
                 WHERE blocked_until > NOW()"
            ),
            'suspicious_activities' => $this->wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}crypto_audit_logs 
                 WHERE event_type IN ('suspicious_trade', 'rapid_orders', 'api_abuse') 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            )
        );
        
        return $stats;
    }
    
    /**
     * Create security tables
     */
    public function create_security_tables() {
        // Blocked IPs table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}crypto_blocked_ips (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            reason varchar(255) NOT NULL,
            blocked_until datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ip_address (ip_address),
            KEY blocked_until (blocked_until)
        ) " . $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
