<?php
/**
 * Theme class for Crypto Exchange Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Theme {
    
    public function __construct() {
        add_action('init', array($this, 'init_theme'));
    }
    
    /**
     * Initialize theme
     */
    public function init_theme() {
        // Register custom theme
        add_action('after_setup_theme', array($this, 'setup_theme'));
        
        // Add theme support
        add_action('init', array($this, 'add_theme_support'));
        
        // Enqueue theme styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_theme_styles'));
        
        // Add custom body classes
        add_filter('body_class', array($this, 'add_body_classes'));
    }
    
    /**
     * Setup theme
     */
    public function setup_theme() {
        // Add theme support
        add_theme_support('post-thumbnails');
        add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
        add_theme_support('custom-logo');
        add_theme_support('customize-selective-refresh-widgets');
    }
    
    /**
     * Add theme support
     */
    public function add_theme_support() {
        // Add support for custom post types
        add_theme_support('post-formats', array('aside', 'image', 'video', 'quote', 'link', 'gallery', 'audio'));
    }
    
    /**
     * Enqueue theme styles
     */
    public function enqueue_theme_styles() {
        // Only load on crypto exchange pages
        if ($this->is_crypto_exchange_page()) {
            wp_enqueue_style('crypto-exchange-theme', CRYPTO_EXCHANGE_PLUGIN_URL . 'themes/crypto-exchange-theme/style.css', array(), CRYPTO_EXCHANGE_VERSION);
            wp_enqueue_script('crypto-exchange-theme', CRYPTO_EXCHANGE_PLUGIN_URL . 'themes/crypto-exchange-theme/script.js', array('jquery'), CRYPTO_EXCHANGE_VERSION, true);
        }
    }
    
    /**
     * Add custom body classes
     */
    public function add_body_classes($classes) {
        if ($this->is_crypto_exchange_page()) {
            $classes[] = 'crypto-exchange-page';
        }
        
        return $classes;
    }
    
    /**
     * Check if current page is crypto exchange page
     */
    private function is_crypto_exchange_page() {
        return get_query_var('crypto_page') || 
               is_page_template('crypto-exchange') ||
               has_shortcode(get_post()->post_content ?? '', 'crypto_exchange');
    }
    
    /**
     * Get theme template
     */
    public function get_template($template_name, $args = array()) {
        $template_path = CRYPTO_EXCHANGE_PLUGIN_DIR . 'themes/crypto-exchange-theme/' . $template_name . '.php';
        
        if (file_exists($template_path)) {
            extract($args);
            include $template_path;
        }
    }
    
    /**
     * Render navigation
     */
    public function render_navigation() {
        ?>
        <nav class="crypto-nav">
            <div class="nav-container">
                <div class="nav-brand">
                    <a href="<?php echo home_url('/crypto-exchange/dashboard'); ?>">
                        <img src="<?php echo CRYPTO_EXCHANGE_PLUGIN_URL . 'themes/crypto-exchange-theme/assets/logo.png'; ?>" alt="Crypto Exchange">
                    </a>
                </div>
                
                <div class="nav-menu">
                    <ul>
                        <li><a href="<?php echo home_url('/crypto-exchange/dashboard'); ?>">Dashboard</a></li>
                        <li><a href="<?php echo home_url('/crypto-exchange/trading'); ?>">Trading</a></li>
                        <li><a href="<?php echo home_url('/crypto-exchange/wallets'); ?>">Wallets</a></li>
                        <li><a href="<?php echo home_url('/crypto-exchange/kyc'); ?>">KYC</a></li>
                    </ul>
                </div>
                
                <div class="nav-user">
                    <?php if (is_user_logged_in()): ?>
                        <div class="user-menu">
                            <span class="user-name"><?php echo wp_get_current_user()->display_name; ?></span>
                            <div class="user-dropdown">
                                <a href="<?php echo home_url('/crypto-exchange/profile'); ?>">Profile</a>
                                <a href="<?php echo wp_logout_url(); ?>">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo home_url('/crypto-exchange/login'); ?>" class="btn btn-primary">Login</a>
                        <a href="<?php echo home_url('/crypto-exchange/register'); ?>" class="btn btn-secondary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
        <?php
    }
    
    /**
     * Render footer
     */
    public function render_footer() {
        ?>
        <footer class="crypto-footer">
            <div class="footer-container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h4>Exchange</h4>
                        <ul>
                            <li><a href="<?php echo home_url('/crypto-exchange/trading'); ?>">Trading</a></li>
                            <li><a href="<?php echo home_url('/crypto-exchange/wallets'); ?>">Wallets</a></li>
                            <li><a href="<?php echo home_url('/crypto-exchange/kyc'); ?>">KYC</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h4>Support</h4>
                        <ul>
                            <li><a href="#">Help Center</a></li>
                            <li><a href="#">Contact Us</a></li>
                            <li><a href="#">API Documentation</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h4>Legal</h4>
                        <ul>
                            <li><a href="#">Terms of Service</a></li>
                            <li><a href="#">Privacy Policy</a></li>
                            <li><a href="#">Risk Disclosure</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <p>&copy; <?php echo date('Y'); ?> Crypto Exchange Pro. All rights reserved.</p>
                </div>
            </div>
        </footer>
        <?php
    }
    
    /**
     * Render sidebar
     */
    public function render_sidebar() {
        if (!is_user_logged_in()) {
            return;
        }
        ?>
        <aside class="crypto-sidebar">
            <div class="sidebar-content">
                <div class="sidebar-section">
                    <h3>Portfolio</h3>
                    <div class="portfolio-value" id="sidebar-portfolio-value">
                        $0.00
                    </div>
                </div>
                
                <div class="sidebar-section">
                    <h3>Quick Actions</h3>
                    <ul class="quick-actions">
                        <li><a href="<?php echo home_url('/crypto-exchange/trading'); ?>">Trade</a></li>
                        <li><a href="<?php echo home_url('/crypto-exchange/wallets'); ?>">Deposit</a></li>
                        <li><a href="<?php echo home_url('/crypto-exchange/wallets'); ?>">Withdraw</a></li>
                        <li><a href="<?php echo home_url('/crypto-exchange/kyc'); ?>">Verify Identity</a></li>
                    </ul>
                </div>
                
                <div class="sidebar-section">
                    <h3>Market Overview</h3>
                    <div class="market-overview" id="sidebar-market-overview">
                        <!-- Market data will be loaded here -->
                    </div>
                </div>
            </div>
        </aside>
        <?php
    }
    
    /**
     * Render page header
     */
    public function render_page_header($title, $subtitle = '') {
        ?>
        <div class="page-header">
            <div class="header-content">
                <h1><?php echo $title; ?></h1>
                <?php if ($subtitle): ?>
                    <p><?php echo $subtitle; ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render loading spinner
     */
    public function render_loading_spinner() {
        ?>
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Loading...</p>
        </div>
        <?php
    }
    
    /**
     * Render notification
     */
    public function render_notification($message, $type = 'info') {
        ?>
        <div class="notification notification-<?php echo $type; ?>">
            <span class="notification-message"><?php echo $message; ?></span>
            <button class="notification-close">&times;</button>
        </div>
        <?php
    }
    
    /**
     * Render modal
     */
    public function render_modal($id, $title, $content) {
        ?>
        <div id="<?php echo $id; ?>" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><?php echo $title; ?></h3>
                    <span class="modal-close">&times;</span>
                </div>
                <div class="modal-body">
                    <?php echo $content; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
