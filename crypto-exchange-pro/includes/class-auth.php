<?php
/**
 * Authentication class for Crypto Exchange Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Auth {
    
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * User login
     */
    public function login($email, $password) {
        $user = wp_authenticate($email, $password);
        
        if (is_wp_error($user)) {
            return array(
                'success' => false,
                'message' => $user->get_error_message()
            );
        }
        
        // Check if user is active
        $crypto_user = $this->get_crypto_user($user->ID);
        if ($crypto_user && $crypto_user->account_status !== 'active') {
            return array(
                'success' => false,
                'message' => 'Your account is not active. Please contact support.'
            );
        }
        
        // Log the user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
        
        // Update last login
        $this->update_last_login($user->ID);
        
        // Log audit
        $this->log_audit($user->ID, 'login', 'User logged in');
        
        return array(
            'success' => true,
            'message' => 'Login successful',
            'redirect' => home_url('/crypto-exchange/dashboard')
        );
    }
    
    /**
     * User registration
     */
    public function register($email, $password, $first_name, $last_name) {
        // Validate email
        if (!is_email($email)) {
            return array(
                'success' => false,
                'message' => 'Please enter a valid email address'
            );
        }
        
        // Check if user already exists
        if (email_exists($email)) {
            return array(
                'success' => false,
                'message' => 'An account with this email already exists'
            );
        }
        
        // Validate password strength
        if (strlen($password) < 8) {
            return array(
                'success' => false,
                'message' => 'Password must be at least 8 characters long'
            );
        }
        
        // Create WordPress user
        $user_id = wp_create_user($email, $password, $email);
        
        if (is_wp_error($user_id)) {
            return array(
                'success' => false,
                'message' => $user_id->get_error_message()
            );
        }
        
        // Update user meta
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        
        // Create crypto user record
        $this->create_crypto_user($user_id);
        
        // Log the user in
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);
        
        // Log audit
        $this->log_audit($user_id, 'register', 'User registered');
        
        return array(
            'success' => true,
            'message' => 'Registration successful',
            'redirect' => home_url('/crypto-exchange/dashboard')
        );
    }
    
    /**
     * Create crypto user record
     */
    private function create_crypto_user($user_id) {
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_users',
            array(
                'user_id' => $user_id,
                'kyc_status' => 'pending',
                'kyc_level' => 0,
                'trading_limits' => 1000, // Default limit
                'account_status' => 'active'
            ),
            array('%d', '%s', '%d', '%f', '%s')
        );
        
        // Create default wallets
        $this->create_default_wallets($user_id);
    }
    
    /**
     * Create default wallets for user
     */
    private function create_default_wallets($user_id) {
        $currencies = array('BTC', 'ETH', 'BNB', 'ADA', 'SOL', 'DOT', 'MATIC', 'AVAX');
        
        foreach ($currencies as $currency) {
            $this->wpdb->insert(
                $this->wpdb->prefix . 'crypto_wallets',
                array(
                    'user_id' => $user_id,
                    'currency' => $currency,
                    'balance' => 0,
                    'locked_balance' => 0,
                    'address' => $this->generate_wallet_address($currency),
                    'wallet_type' => 'hot'
                ),
                array('%d', '%s', '%f', '%f', '%s', '%s')
            );
        }
    }
    
    /**
     * Generate wallet address
     */
    private function generate_wallet_address($currency) {
        // This is a simplified version - in production, use proper wallet generation
        return strtoupper($currency) . '_' . wp_generate_password(32, false);
    }
    
    /**
     * Get crypto user data
     */
    public function get_crypto_user($user_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}crypto_users WHERE user_id = %d",
                $user_id
            )
        );
    }
    
    /**
     * Update last login
     */
    private function update_last_login($user_id) {
        $this->wpdb->update(
            $this->wpdb->prefix . 'crypto_users',
            array('last_login' => current_time('mysql')),
            array('user_id' => $user_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Log audit trail
     */
    private function log_audit($user_id, $action, $details) {
        $this->wpdb->insert(
            $this->wpdb->prefix . 'crypto_audit_logs',
            array(
                'user_id' => $user_id,
                'action' => $action,
                'details' => $details,
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ),
            array('%d', '%s', '%s', '%s', '%s')
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
     * Render login page
     */
    public function render_login_page() {
        $this->render_page('login');
    }
    
    /**
     * Render register page
     */
    public function render_register_page() {
        $this->render_page('register');
    }
    
    /**
     * Render page template
     */
    private function render_page($template) {
        $template_path = CRYPTO_EXCHANGE_PLUGIN_DIR . 'templates/' . $template . '.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->render_default_page($template);
        }
        
        exit;
    }
    
    /**
     * Render default page
     */
    private function render_default_page($template) {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo ucfirst($template); ?> - <?php bloginfo('name'); ?></title>
            <?php wp_head(); ?>
        </head>
        <body <?php body_class(); ?>>
            <div class="crypto-exchange-container">
                <div class="crypto-exchange-auth">
                    <div class="auth-header">
                        <h1><?php echo ucfirst($template); ?></h1>
                        <p>Access your crypto exchange account</p>
                    </div>
                    
                    <?php if ($template === 'login'): ?>
                        <?php $this->render_login_form(); ?>
                    <?php else: ?>
                        <?php $this->render_register_form(); ?>
                    <?php endif; ?>
                    
                    <div class="auth-footer">
                        <?php if ($template === 'login'): ?>
                            <p>Don't have an account? <a href="<?php echo home_url('/crypto-exchange/register'); ?>">Register here</a></p>
                        <?php else: ?>
                            <p>Already have an account? <a href="<?php echo home_url('/crypto-exchange/login'); ?>">Login here</a></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
    
    /**
     * Render login form
     */
    public function render_login_form() {
        ?>
        <form id="crypto-login-form" class="crypto-auth-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="remember_me"> Remember me
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">Login</button>
            
            <div class="form-links">
                <a href="<?php echo wp_lostpassword_url(); ?>">Forgot Password?</a>
            </div>
        </form>
        <?php
    }
    
    /**
     * Render register form
     */
    public function render_register_form() {
        ?>
        <form id="crypto-register-form" class="crypto-auth-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="8">
                <small>Password must be at least 8 characters long</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="terms" required> I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a>
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>
        <?php
    }
    
    /**
     * Get login form (for shortcode)
     */
    public function login_form() {
        ob_start();
        $this->render_login_form();
        return ob_get_clean();
    }
    
    /**
     * Get register form (for shortcode)
     */
    public function register_form() {
        ob_start();
        $this->render_register_form();
        return ob_get_clean();
    }
    
    /**
     * Handle login form submission
     */
    public function handle_login() {
        if ($_POST && isset($_POST['crypto_login'])) {
            $result = $this->login($_POST['email'], $_POST['password']);
            
            if ($result['success']) {
                wp_redirect($result['redirect']);
                exit;
            } else {
                $this->error_message = $result['message'];
            }
        }
    }
    
    /**
     * Handle register form submission
     */
    public function handle_register() {
        if ($_POST && isset($_POST['crypto_register'])) {
            if ($_POST['password'] !== $_POST['confirm_password']) {
                $this->error_message = 'Passwords do not match';
                return;
            }
            
            $result = $this->register($_POST['email'], $_POST['password'], $_POST['first_name'], $_POST['last_name']);
            
            if ($result['success']) {
                wp_redirect($result['redirect']);
                exit;
            } else {
                $this->error_message = $result['message'];
            }
        }
    }
}
