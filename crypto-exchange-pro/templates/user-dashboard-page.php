<?php
/**
 * User Dashboard Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

// Include the dashboard template
include CRYPTO_EXCHANGE_PLUGIN_DIR . 'templates/user-dashboard.php';
