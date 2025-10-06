<?php
/**
 * Crypto Exchange Pro Theme functions and definitions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme setup
 */
function crypto_exchange_theme_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
    add_theme_support('custom-logo');
    add_theme_support('customize-selective-refresh-widgets');
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'crypto-exchange-pro'),
        'footer'  => __('Footer Menu', 'crypto-exchange-pro'),
    ));
    
    // Add image sizes
    add_image_size('crypto-feature', 400, 300, true);
    add_image_size('crypto-thumbnail', 150, 150, true);
}
add_action('after_setup_theme', 'crypto_exchange_theme_setup');

/**
 * Enqueue scripts and styles
 */
function crypto_exchange_theme_scripts() {
    // Theme stylesheet
    wp_enqueue_style('crypto-exchange-theme-style', get_stylesheet_uri(), array(), '1.0.0');
    
    // Enqueue jQuery
    wp_enqueue_script('jquery');
    
    // Theme JavaScript
    wp_enqueue_script('crypto-exchange-theme-js', get_template_directory_uri() . '/js/theme.js', array('jquery'), '1.0.0', true);
    
    // Localize script for AJAX
    wp_localize_script('crypto-exchange-theme-js', 'crypto_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('crypto_exchange_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'crypto_exchange_theme_scripts');

/**
 * Register widget areas
 */
function crypto_exchange_widgets_init() {
    register_sidebar(array(
        'name'          => __('Sidebar', 'crypto-exchange-pro'),
        'id'            => 'sidebar-1',
        'description'   => __('Add widgets here.', 'crypto-exchange-pro'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
    
    register_sidebar(array(
        'name'          => __('Footer Widgets', 'crypto-exchange-pro'),
        'id'            => 'footer-widgets',
        'description'   => __('Add widgets here.', 'crypto-exchange-pro'),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="footer-widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'crypto_exchange_widgets_init');

/**
 * Fallback menu
 */
function crypto_exchange_fallback_menu() {
    echo '<ul id="primary-menu" class="menu">';
    echo '<li><a href="' . home_url('/') . '">Home</a></li>';
    echo '<li><a href="' . home_url('/dashboard') . '">Dashboard</a></li>';
    echo '<li><a href="' . home_url('/markets') . '">Markets</a></li>';
    echo '<li><a href="' . home_url('/about') . '">About</a></li>';
    echo '<li><a href="' . home_url('/contact') . '">Contact</a></li>';
    echo '</ul>';
}

/**
 * Customize login page
 */
function crypto_exchange_login_styles() {
    ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url('<?php echo get_template_directory_uri(); ?>/images/logo.png');
            height: 65px;
            width: 320px;
            background-size: 320px 65px;
            background-repeat: no-repeat;
            padding-bottom: 30px;
        }
        
        .login form {
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .login #nav a,
        .login #backtoblog a {
            color: #007bff;
        }
        
        .login #nav a:hover,
        .login #backtoblog a:hover {
            color: #0056b3;
        }
        
        .wp-core-ui .button-primary {
            background: #007bff;
            border-color: #007bff;
        }
        
        .wp-core-ui .button-primary:hover {
            background: #0056b3;
            border-color: #0056b3;
        }
    </style>
    <?php
}
add_action('login_enqueue_scripts', 'crypto_exchange_login_styles');

/**
 * Change login logo URL
 */
function crypto_exchange_login_logo_url() {
    return home_url();
}
add_filter('login_headerurl', 'crypto_exchange_login_logo_url');

/**
 * Change login logo title
 */
function crypto_exchange_login_logo_title() {
    return get_bloginfo('name');
}
add_filter('login_headertitle', 'crypto_exchange_login_logo_title');

/**
 * Add custom body classes
 */
function crypto_exchange_body_classes($classes) {
    if (is_front_page()) {
        $classes[] = 'homepage';
    }
    
    if (is_user_logged_in()) {
        $classes[] = 'logged-in';
    }
    
    return $classes;
}
add_filter('body_class', 'crypto_exchange_body_classes');

/**
 * Custom excerpt length
 */
function crypto_exchange_excerpt_length($length) {
    return 20;
}
add_filter('excerpt_length', 'crypto_exchange_excerpt_length');

/**
 * Custom excerpt more
 */
function crypto_exchange_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'crypto_exchange_excerpt_more');

/**
 * Add custom post types support
 */
function crypto_exchange_add_theme_support() {
    add_theme_support('post-thumbnails');
    add_theme_support('custom-background');
    add_theme_support('custom-header');
}
add_action('after_setup_theme', 'crypto_exchange_add_theme_support');

/**
 * Enqueue admin styles
 */
function crypto_exchange_admin_styles() {
    wp_enqueue_style('crypto-exchange-admin-style', get_template_directory_uri() . '/css/admin.css', array(), '1.0.0');
}
add_action('admin_enqueue_scripts', 'crypto_exchange_admin_styles');

/**
 * Add custom CSS for admin
 */
function crypto_exchange_admin_css() {
    ?>
    <style type="text/css">
        .crypto-exchange-admin {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .crypto-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-box {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-box h3 {
            margin: 0 0 10px 0;
            color: #23282d;
            font-size: 14px;
            font-weight: 600;
        }
        
        .stat-box p {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
        }
    </style>
    <?php
}
add_action('admin_head', 'crypto_exchange_admin_css');

/**
 * Add custom dashboard widgets
 */
function crypto_exchange_dashboard_widgets() {
    wp_add_dashboard_widget(
        'crypto_exchange_stats',
        'Crypto Exchange Statistics',
        'crypto_exchange_dashboard_stats_widget'
    );
}
add_action('wp_dashboard_setup', 'crypto_exchange_dashboard_widgets');

/**
 * Dashboard stats widget
 */
function crypto_exchange_dashboard_stats_widget() {
    global $wpdb;
    
    $total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
    $total_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}crypto_orders");
    $total_trades = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}crypto_trades");
    $total_volume = $wpdb->get_var("SELECT SUM(amount * price) FROM {$wpdb->prefix}crypto_trades");
    
    ?>
    <div class="crypto-stats">
        <div class="stat-box">
            <h3>Total Users</h3>
            <p><?php echo number_format($total_users); ?></p>
        </div>
        <div class="stat-box">
            <h3>Total Orders</h3>
            <p><?php echo number_format($total_orders); ?></p>
        </div>
        <div class="stat-box">
            <h3>Total Trades</h3>
            <p><?php echo number_format($total_trades); ?></p>
        </div>
        <div class="stat-box">
            <h3>Total Volume</h3>
            <p>$<?php echo number_format($total_volume, 2); ?></p>
        </div>
    </div>
    <?php
}

/**
 * Add custom admin menu
 */
function crypto_exchange_admin_menu() {
    add_menu_page(
        'Crypto Exchange',
        'Crypto Exchange',
        'manage_options',
        'crypto-exchange',
        'crypto_exchange_admin_page',
        'dashicons-chart-line',
        30
    );
}
add_action('admin_menu', 'crypto_exchange_admin_menu');

/**
 * Admin page callback
 */
function crypto_exchange_admin_page() {
    ?>
    <div class="wrap">
        <h1>Crypto Exchange Dashboard</h1>
        <p>Welcome to the Crypto Exchange Pro admin panel.</p>
        
        <div class="crypto-stats">
            <div class="stat-box">
                <h3>Active Users</h3>
                <p><?php echo count(get_users()); ?></p>
            </div>
            <div class="stat-box">
                <h3>Total Coins</h3>
                <p><?php echo count(get_posts(array('post_type' => 'crypto_coin', 'post_status' => 'publish'))); ?></p>
            </div>
            <div class="stat-box">
                <h3>System Status</h3>
                <p style="color: #28a745;">Online</p>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Add custom post types
 */
function crypto_exchange_register_post_types() {
    // Crypto Coin post type
    register_post_type('crypto_coin', array(
        'labels' => array(
            'name' => 'Crypto Coins',
            'singular_name' => 'Crypto Coin',
            'add_new' => 'Add New Coin',
            'add_new_item' => 'Add New Crypto Coin',
            'edit_item' => 'Edit Crypto Coin',
            'new_item' => 'New Crypto Coin',
            'view_item' => 'View Crypto Coin',
            'search_items' => 'Search Crypto Coins',
            'not_found' => 'No crypto coins found',
            'not_found_in_trash' => 'No crypto coins found in trash',
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
        'menu_icon' => 'dashicons-money-alt',
    ));
}
add_action('init', 'crypto_exchange_register_post_types');

/**
 * Add custom taxonomies
 */
function crypto_exchange_register_taxonomies() {
    // Coin Category taxonomy
    register_taxonomy('coin_category', 'crypto_coin', array(
        'labels' => array(
            'name' => 'Coin Categories',
            'singular_name' => 'Coin Category',
            'search_items' => 'Search Categories',
            'all_items' => 'All Categories',
            'parent_item' => 'Parent Category',
            'parent_item_colon' => 'Parent Category:',
            'edit_item' => 'Edit Category',
            'update_item' => 'Update Category',
            'add_new_item' => 'Add New Category',
            'new_item_name' => 'New Category Name',
            'menu_name' => 'Categories',
        ),
        'hierarchical' => true,
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
    ));
}
add_action('init', 'crypto_exchange_register_taxonomies');

/**
 * Add custom fields to user profile
 */
function crypto_exchange_add_user_fields($user) {
    ?>
    <h3>Crypto Exchange Information</h3>
    <table class="form-table">
        <tr>
            <th><label for="kyc_status">KYC Status</label></th>
            <td>
                <select name="kyc_status" id="kyc_status">
                    <option value="pending" <?php selected(get_user_meta($user->ID, 'kyc_status', true), 'pending'); ?>>Pending</option>
                    <option value="verified" <?php selected(get_user_meta($user->ID, 'kyc_status', true), 'verified'); ?>>Verified</option>
                    <option value="rejected" <?php selected(get_user_meta($user->ID, 'kyc_status', true), 'rejected'); ?>>Rejected</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="trading_limits">Trading Limits</label></th>
            <td>
                <input type="text" name="trading_limits" id="trading_limits" value="<?php echo esc_attr(get_user_meta($user->ID, 'trading_limits', true)); ?>" class="regular-text" />
                <p class="description">Daily trading limits in USD</p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'crypto_exchange_add_user_fields');
add_action('edit_user_profile', 'crypto_exchange_add_user_fields');

/**
 * Save custom user fields
 */
function crypto_exchange_save_user_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    
    update_user_meta($user_id, 'kyc_status', sanitize_text_field($_POST['kyc_status']));
    update_user_meta($user_id, 'trading_limits', sanitize_text_field($_POST['trading_limits']));
}
add_action('personal_options_update', 'crypto_exchange_save_user_fields');
add_action('edit_user_profile_update', 'crypto_exchange_save_user_fields');

/**
 * Add custom columns to users table
 */
function crypto_exchange_add_user_columns($columns) {
    $columns['kyc_status'] = 'KYC Status';
    $columns['trading_limits'] = 'Trading Limits';
    return $columns;
}
add_filter('manage_users_columns', 'crypto_exchange_add_user_columns');

/**
 * Display custom user column data
 */
function crypto_exchange_show_user_column_data($value, $column_name, $user_id) {
    switch ($column_name) {
        case 'kyc_status':
            $status = get_user_meta($user_id, 'kyc_status', true);
            $status_colors = array(
                'pending' => '#ffc107',
                'verified' => '#28a745',
                'rejected' => '#dc3545'
            );
            $color = isset($status_colors[$status]) ? $status_colors[$status] : '#6c757d';
            return '<span style="color: ' . $color . '; font-weight: bold;">' . ucfirst($status) . '</span>';
            
        case 'trading_limits':
            $limits = get_user_meta($user_id, 'trading_limits', true);
            return $limits ? '$' . number_format($limits) : 'No limits';
    }
    return $value;
}
add_filter('manage_users_custom_column', 'crypto_exchange_show_user_column_data', 10, 3);
