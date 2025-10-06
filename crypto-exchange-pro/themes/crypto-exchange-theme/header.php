<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <header id="masthead" class="site-header">
        <div class="header-container">
            <div class="site-branding">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo" rel="home">
                    <?php bloginfo('name'); ?>
                </a>
            </div>

            <nav id="site-navigation" class="main-navigation">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                    'fallback_cb'    => 'crypto_exchange_fallback_menu',
                ));
                ?>
            </nav>

            <div class="user-menu">
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo esc_url(home_url('/dashboard')); ?>" class="btn btn-primary">Dashboard</a>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-secondary">Logout</a>
                <?php else: ?>
                    <a href="<?php echo wp_login_url(); ?>" class="login-btn">Login</a>
                    <a href="<?php echo wp_registration_url(); ?>" class="register-btn">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div id="content" class="site-content">
