<?php
/**
 * Theme Manager - Handle theme activation and template overrides
 */

if (!defined('ABSPATH')) {
    exit;
}

class Crypto_Exchange_Theme_Manager {
    
    private $theme_name = 'Crypto Exchange Pro';
    private $theme_slug = 'crypto-exchange-pro';
    private $theme_path;
    private $theme_url;
    
    public function __construct() {
        $this->theme_path = CRYPTO_EXCHANGE_PLUGIN_DIR . 'themes/crypto-exchange-theme/';
        $this->theme_url = CRYPTO_EXCHANGE_PLUGIN_URL . 'themes/crypto-exchange-theme/';
        
        add_action('admin_menu', array($this, 'add_theme_menu'));
        add_action('wp_ajax_activate_crypto_theme', array($this, 'activate_theme'));
        add_action('wp_ajax_deactivate_crypto_theme', array($this, 'deactivate_theme'));
        add_action('wp_ajax_reset_crypto_theme', array($this, 'reset_theme'));
        
        // Template override hooks
        add_filter('template_include', array($this, 'override_template'));
        add_filter('theme_page_templates', array($this, 'add_custom_templates'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_theme_assets'));
        
        // Custom post types for theme
        add_action('init', array($this, 'register_theme_post_types'));
        
        // Customizer integration
        add_action('customize_register', array($this, 'customize_register'));
    }
    
    /**
     * Add theme management menu
     */
    public function add_theme_menu() {
        add_submenu_page(
            'crypto-exchange-pro',
            'Theme Management',
            'Theme Management',
            'manage_options',
            'crypto-exchange-theme',
            array($this, 'theme_management_page')
        );
    }
    
    /**
     * Theme management page
     */
    public function theme_management_page() {
        $current_theme = get_option('crypto_exchange_active_theme', false);
        $theme_status = $this->get_theme_status();
        
        ?>
        <div class="wrap">
            <h1>Crypto Exchange Theme Management</h1>
            
            <div class="theme-management-dashboard">
                <div class="theme-status-card">
                    <h2>Theme Status</h2>
                    <div class="status-indicator <?php echo $theme_status['active'] ? 'active' : 'inactive'; ?>">
                        <span class="status-dot"></span>
                        <span class="status-text">
                            <?php echo $theme_status['active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    <p class="status-description">
                        <?php echo $theme_status['active'] ? 
                            'Crypto Exchange theme is currently active and overriding WordPress templates.' : 
                            'Crypto Exchange theme is inactive. WordPress default theme is being used.'; ?>
                    </p>
                </div>
                
                <div class="theme-actions">
                    <?php if (!$theme_status['active']): ?>
                        <button class="button button-primary button-large activate-theme-btn">
                            <span class="dashicons dashicons-admin-appearance"></span>
                            Activate Crypto Exchange Theme
                        </button>
                    <?php else: ?>
                        <button class="button button-secondary button-large deactivate-theme-btn">
                            <span class="dashicons dashicons-admin-appearance"></span>
                            Deactivate Theme
                        </button>
                        <button class="button button-secondary reset-theme-btn">
                            <span class="dashicons dashicons-update"></span>
                            Reset Theme Settings
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="theme-preview">
                    <h3>Theme Preview</h3>
                    <div class="preview-container">
                        <iframe id="theme-preview" src="<?php echo home_url(); ?>" width="100%" height="600" frameborder="0"></iframe>
                    </div>
                </div>
                
                <div class="theme-features">
                    <h3>Theme Features</h3>
                    <div class="features-grid">
                        <div class="feature-item">
                            <span class="feature-icon">🏠</span>
                            <h4>Homepage Showcase</h4>
                            <p>Professional exchange homepage with live market data</p>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">📈</span>
                            <h4>Trading Interface</h4>
                            <p>Advanced trading platform with real-time charts</p>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">💰</span>
                            <h4>Wallet Management</h4>
                            <p>Secure wallet interface for deposits and withdrawals</p>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">📊</span>
                            <h4>Market Data</h4>
                            <p>Live market data, charts, and trading pairs</p>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">👤</span>
                            <h4>User Dashboard</h4>
                            <p>Personal dashboard with portfolio and activity</p>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon">📱</span>
                            <h4>Mobile Responsive</h4>
                            <p>Fully responsive design for all devices</p>
                        </div>
                    </div>
                </div>
                
                <div class="theme-customization">
                    <h3>Theme Customization</h3>
                    <div class="customization-options">
                        <div class="option-group">
                            <label for="theme_color_scheme">Color Scheme</label>
                            <select id="theme_color_scheme">
                                <option value="default">Default (Blue)</option>
                                <option value="dark">Dark Mode</option>
                                <option value="light">Light Mode</option>
                                <option value="green">Green (Trading)</option>
                            </select>
                        </div>
                        <div class="option-group">
                            <label for="theme_layout">Layout Style</label>
                            <select id="theme_layout">
                                <option value="modern">Modern</option>
                                <option value="classic">Classic</option>
                                <option value="minimal">Minimal</option>
                            </select>
                        </div>
                        <div class="option-group">
                            <label for="theme_sidebar">Sidebar Position</label>
                            <select id="theme_sidebar">
                                <option value="right">Right</option>
                                <option value="left">Left</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                        <button class="button button-primary save-theme-settings">Save Settings</button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .theme-management-dashboard {
            max-width: 1200px;
        }
        
        .theme-status-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .status-indicator.active .status-dot {
            background: #4caf50;
        }
        
        .status-indicator.inactive .status-dot {
            background: #f44336;
        }
        
        .theme-actions {
            text-align: center;
            margin: 20px 0;
        }
        
        .theme-actions .button {
            margin: 0 10px;
        }
        
        .theme-preview {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .preview-container {
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .theme-features {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .feature-item {
            text-align: center;
            padding: 20px;
            border: 1px solid #f0f0f0;
            border-radius: 8px;
        }
        
        .feature-icon {
            font-size: 32px;
            display: block;
            margin-bottom: 10px;
        }
        
        .theme-customization {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
        }
        
        .customization-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .option-group {
            display: flex;
            flex-direction: column;
        }
        
        .option-group label {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .option-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Activate theme
            $('.activate-theme-btn').click(function() {
                if (confirm('Are you sure you want to activate the Crypto Exchange theme? This will override your current theme.')) {
                    activateTheme();
                }
            });
            
            // Deactivate theme
            $('.deactivate-theme-btn').click(function() {
                if (confirm('Are you sure you want to deactivate the Crypto Exchange theme?')) {
                    deactivateTheme();
                }
            });
            
            // Reset theme
            $('.reset-theme-btn').click(function() {
                if (confirm('Are you sure you want to reset all theme settings?')) {
                    resetTheme();
                }
            });
            
            // Save settings
            $('.save-theme-settings').click(function() {
                saveThemeSettings();
            });
            
            function activateTheme() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'activate_crypto_theme',
                        nonce: '<?php echo wp_create_nonce('crypto_theme_nonce'); ?>'
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
            
            function deactivateTheme() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'deactivate_crypto_theme',
                        nonce: '<?php echo wp_create_nonce('crypto_theme_nonce'); ?>'
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
            
            function resetTheme() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'reset_crypto_theme',
                        nonce: '<?php echo wp_create_nonce('crypto_theme_nonce'); ?>'
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
            
            function saveThemeSettings() {
                const settings = {
                    color_scheme: $('#theme_color_scheme').val(),
                    layout: $('#theme_layout').val(),
                    sidebar: $('#theme_sidebar').val()
                };
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'save_theme_settings',
                        settings: settings,
                        nonce: '<?php echo wp_create_nonce('crypto_theme_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Settings saved successfully!');
                        } else {
                            alert('Error: ' + response.data);
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Get theme status
     */
    public function get_theme_status() {
        $active = get_option('crypto_exchange_active_theme', false);
        $theme_data = get_option('crypto_exchange_theme_data', array());
        
        return array(
            'active' => $active,
            'data' => $theme_data,
            'path' => $this->theme_path,
            'url' => $this->theme_url
        );
    }
    
    /**
     * Activate theme
     */
    public function activate_theme() {
        check_ajax_referer('crypto_theme_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Set theme as active
        update_option('crypto_exchange_active_theme', true);
        update_option('crypto_exchange_theme_data', array(
            'activated_at' => current_time('mysql'),
            'activated_by' => get_current_user_id(),
            'version' => CRYPTO_EXCHANGE_VERSION
        ));
        
        // Create necessary pages
        $this->create_theme_pages();
        
        // Set up theme options
        $this->setup_theme_options();
        
        wp_send_json_success('Theme activated successfully!');
    }
    
    /**
     * Deactivate theme
     */
    public function deactivate_theme() {
        check_ajax_referer('crypto_theme_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Deactivate theme
        update_option('crypto_exchange_active_theme', false);
        
        wp_send_json_success('Theme deactivated successfully!');
    }
    
    /**
     * Reset theme
     */
    public function reset_theme() {
        check_ajax_referer('crypto_theme_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Reset theme options
        delete_option('crypto_exchange_theme_data');
        delete_option('crypto_exchange_theme_settings');
        
        wp_send_json_success('Theme settings reset successfully!');
    }
    
    /**
     * Create theme pages
     */
    private function create_theme_pages() {
        $pages = array(
            'home' => array(
                'title' => 'Crypto Exchange',
                'content' => '[crypto_exchange_homepage]',
                'template' => 'homepage'
            ),
            'trading' => array(
                'title' => 'Trading',
                'content' => '[crypto_trading]',
                'template' => 'trading'
            ),
            'markets' => array(
                'title' => 'Markets',
                'content' => '[crypto_market_data]',
                'template' => 'markets'
            ),
            'dashboard' => array(
                'title' => 'Dashboard',
                'content' => '[crypto_dashboard]',
                'template' => 'dashboard'
            )
        );
        
        foreach ($pages as $slug => $page_data) {
            $existing_page = get_page_by_path($slug);
            if (!$existing_page) {
                wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_name' => $slug,
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'page_template' => $page_data['template']
                ));
            }
        }
    }
    
    /**
     * Setup theme options
     */
    private function setup_theme_options() {
        $default_settings = array(
            'color_scheme' => 'default',
            'layout' => 'modern',
            'sidebar' => 'right',
            'show_header' => true,
            'show_footer' => true,
            'custom_logo' => '',
            'custom_css' => ''
        );
        
        update_option('crypto_exchange_theme_settings', $default_settings);
    }
    
    /**
     * Override template
     */
    public function override_template($template) {
        if (!get_option('crypto_exchange_active_theme', false)) {
            return $template;
        }
        
        global $post;
        
        // Get custom template
        $custom_template = $this->get_custom_template();
        
        if ($custom_template && file_exists($custom_template)) {
            return $custom_template;
        }
        
        return $template;
    }
    
    /**
     * Get custom template
     */
    private function get_custom_template() {
        global $post;
        
        if (is_home() || is_front_page()) {
            return $this->theme_path . 'homepage.php';
        }
        
        if (is_page()) {
            $page_template = get_page_template_slug();
            if ($page_template) {
                return $this->theme_path . $page_template . '.php';
            }
        }
        
        if (is_single() && get_post_type() == 'crypto_coin') {
            return $this->theme_path . 'single-coin.php';
        }
        
        return $this->theme_path . 'index.php';
    }
    
    /**
     * Add custom templates
     */
    public function add_custom_templates($templates) {
        $custom_templates = array(
            'homepage.php' => 'Exchange Homepage',
            'trading.php' => 'Trading Interface',
            'markets.php' => 'Markets Page',
            'dashboard.php' => 'User Dashboard'
        );
        
        return array_merge($templates, $custom_templates);
    }
    
    /**
     * Enqueue theme assets
     */
    public function enqueue_theme_assets() {
        if (!get_option('crypto_exchange_active_theme', false)) {
            return;
        }
        
        // Theme styles
        wp_enqueue_style('crypto-exchange-theme', $this->theme_url . 'style.css', array(), CRYPTO_EXCHANGE_VERSION);
        
        // Theme scripts
        wp_enqueue_script('crypto-exchange-theme', $this->theme_url . 'js/theme.js', array('jquery'), CRYPTO_EXCHANGE_VERSION, true);
        
        // Localize script
        wp_localize_script('crypto-exchange-theme', 'crypto_theme', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crypto_theme_nonce'),
            'theme_url' => $this->theme_url,
            'settings' => get_option('crypto_exchange_theme_settings', array())
        ));
    }
    
    /**
     * Register theme post types
     */
    public function register_theme_post_types() {
        // Crypto News
        register_post_type('crypto_news', array(
            'labels' => array(
                'name' => 'Crypto News',
                'singular_name' => 'News Article'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'menu_icon' => 'dashicons-megaphone'
        ));
        
        // Trading Pairs
        register_post_type('trading_pair', array(
            'labels' => array(
                'name' => 'Trading Pairs',
                'singular_name' => 'Trading Pair'
            ),
            'public' => true,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-chart-line'
        ));
    }
    
    /**
     * Customizer integration
     */
    public function customize_register($wp_customize) {
        if (!get_option('crypto_exchange_active_theme', false)) {
            return;
        }
        
        // Add theme section
        $wp_customize->add_section('crypto_exchange_theme', array(
            'title' => 'Crypto Exchange Theme',
            'priority' => 30
        ));
        
        // Color scheme
        $wp_customize->add_setting('crypto_theme_color_scheme', array(
            'default' => 'default',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        $wp_customize->add_control('crypto_theme_color_scheme', array(
            'label' => 'Color Scheme',
            'section' => 'crypto_exchange_theme',
            'type' => 'select',
            'choices' => array(
                'default' => 'Default (Blue)',
                'dark' => 'Dark Mode',
                'light' => 'Light Mode',
                'green' => 'Green (Trading)'
            )
        ));
        
        // Layout style
        $wp_customize->add_setting('crypto_theme_layout', array(
            'default' => 'modern',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        $wp_customize->add_control('crypto_theme_layout', array(
            'label' => 'Layout Style',
            'section' => 'crypto_exchange_theme',
            'type' => 'select',
            'choices' => array(
                'modern' => 'Modern',
                'classic' => 'Classic',
                'minimal' => 'Minimal'
            )
        ));
    }
}
