<?php
/**
 * Exchange Theme Header
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- TradingView Charting Library -->
    <script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <header id="masthead" class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="site-branding">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo" rel="home">
                        <span class="logo-icon">₿</span>
                        <span class="logo-text">CryptoExchange Pro</span>
                    </a>
                </div>

                <nav id="site-navigation" class="main-navigation">
                    <ul class="nav-menu">
                        <li class="menu-item">
                            <a href="<?php echo home_url('/'); ?>">Home</a>
                        </li>
                        <li class="menu-item">
                            <a href="<?php echo home_url('/trading'); ?>">Trading</a>
                        </li>
                        <li class="menu-item">
                            <a href="<?php echo home_url('/markets'); ?>">Markets</a>
                        </li>
                        <li class="menu-item">
                            <a href="<?php echo home_url('/dashboard'); ?>">Dashboard</a>
                        </li>
                        <li class="menu-item">
                            <a href="<?php echo home_url('/about'); ?>">About</a>
                        </li>
                    </ul>
                </nav>

                <div class="user-menu">
                    <?php if (is_user_logged_in()): ?>
                        <div class="user-balance">
                            <span class="balance-label">Balance:</span>
                            <span class="balance-amount" id="header-balance">$0.00</span>
                        </div>
                        <div class="user-dropdown">
                            <button class="user-avatar" id="user-dropdown-toggle">
                                <i class="fas fa-user"></i>
                            </button>
                            <div class="dropdown-menu" id="user-dropdown-menu">
                                <a href="<?php echo home_url('/dashboard'); ?>" class="dropdown-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    Dashboard
                                </a>
                                <a href="<?php echo home_url('/profile'); ?>" class="dropdown-item">
                                    <i class="fas fa-user-cog"></i>
                                    Profile
                                </a>
                                <a href="<?php echo home_url('/settings'); ?>" class="dropdown-item">
                                    <i class="fas fa-cog"></i>
                                    Settings
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="<?php echo wp_logout_url(home_url()); ?>" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo wp_login_url(); ?>" class="btn btn-secondary">Login</a>
                        <a href="<?php echo wp_registration_url(); ?>" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                </div>

                <button class="mobile-menu-toggle" id="mobile-menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <div id="content" class="site-content">
