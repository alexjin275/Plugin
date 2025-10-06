<?php
/**
 * Exchange Theme Footer
 */

?>
    </div><!-- #content -->

    <footer id="colophon" class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Exchange</h3>
                    <ul>
                        <li><a href="<?php echo home_url('/trading'); ?>">Trading</a></li>
                        <li><a href="<?php echo home_url('/markets'); ?>">Markets</a></li>
                        <li><a href="<?php echo home_url('/fees'); ?>">Fees</a></li>
                        <li><a href="<?php echo home_url('/api'); ?>">API</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Support</h3>
                    <ul>
                        <li><a href="<?php echo home_url('/help'); ?>">Help Center</a></li>
                        <li><a href="<?php echo home_url('/contact'); ?>">Contact Us</a></li>
                        <li><a href="<?php echo home_url('/status'); ?>">System Status</a></li>
                        <li><a href="<?php echo home_url('/security'); ?>">Security</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Company</h3>
                    <ul>
                        <li><a href="<?php echo home_url('/about'); ?>">About Us</a></li>
                        <li><a href="<?php echo home_url('/careers'); ?>">Careers</a></li>
                        <li><a href="<?php echo home_url('/press'); ?>">Press</a></li>
                        <li><a href="<?php echo home_url('/blog'); ?>">Blog</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Legal</h3>
                    <ul>
                        <li><a href="<?php echo home_url('/terms'); ?>">Terms of Service</a></li>
                        <li><a href="<?php echo home_url('/privacy'); ?>">Privacy Policy</a></li>
                        <li><a href="<?php echo home_url('/cookies'); ?>">Cookie Policy</a></li>
                        <li><a href="<?php echo home_url('/compliance'); ?>">Compliance</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Connect</h3>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-telegram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-discord"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                    </div>
                    <div class="newsletter-signup">
                        <h4>Stay Updated</h4>
                        <form class="newsletter-form">
                            <input type="email" placeholder="Enter your email" required>
                            <button type="submit" class="btn btn-primary">Subscribe</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-info">
                    <p>&copy; <?php echo date('Y'); ?> CryptoExchange Pro. All rights reserved.</p>
                    <p class="disclaimer">
                        Trading cryptocurrencies involves substantial risk of loss and is not suitable for all investors. 
                        Past performance is not indicative of future results.
                    </p>
                </div>
                <div class="footer-badges">
                    <div class="badge">
                        <i class="fas fa-shield-alt"></i>
                        <span>Bank-Grade Security</span>
                    </div>
                    <div class="badge">
                        <i class="fas fa-lock"></i>
                        <span>SSL Encrypted</span>
                    </div>
                    <div class="badge">
                        <i class="fas fa-certificate"></i>
                        <span>Licensed & Regulated</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</div><!-- #page -->

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobile-menu">
    <div class="mobile-menu-header">
        <div class="mobile-logo">
            <span class="logo-icon">₿</span>
            <span class="logo-text">CryptoExchange Pro</span>
        </div>
        <button class="mobile-menu-close" id="mobile-menu-close">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <nav class="mobile-nav">
        <ul class="mobile-nav-menu">
            <li><a href="<?php echo home_url('/'); ?>">Home</a></li>
            <li><a href="<?php echo home_url('/trading'); ?>">Trading</a></li>
            <li><a href="<?php echo home_url('/markets'); ?>">Markets</a></li>
            <li><a href="<?php echo home_url('/dashboard'); ?>">Dashboard</a></li>
            <li><a href="<?php echo home_url('/about'); ?>">About</a></li>
        </ul>
        <div class="mobile-user-menu">
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo home_url('/dashboard'); ?>" class="btn btn-primary">Dashboard</a>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-secondary">Logout</a>
            <?php else: ?>
                <a href="<?php echo wp_login_url(); ?>" class="btn btn-secondary">Login</a>
                <a href="<?php echo wp_registration_url(); ?>" class="btn btn-primary">Register</a>
            <?php endif; ?>
        </div>
    </nav>
</div>

<!-- Back to Top Button -->
<button class="back-to-top" id="back-to-top">
    <i class="fas fa-chevron-up"></i>
</button>

<?php wp_footer(); ?>

<script>
// Mobile menu functionality
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuClose = document.getElementById('mobile-menu-close');
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.add('active');
            document.body.classList.add('menu-open');
        });
    }
    
    if (mobileMenuClose && mobileMenu) {
        mobileMenuClose.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
            document.body.classList.remove('menu-open');
        });
    }
    
    // Close mobile menu when clicking outside
    mobileMenu.addEventListener('click', function(e) {
        if (e.target === mobileMenu) {
            mobileMenu.classList.remove('active');
            document.body.classList.remove('menu-open');
        }
    });
    
    // User dropdown functionality
    const userDropdownToggle = document.getElementById('user-dropdown-toggle');
    const userDropdownMenu = document.getElementById('user-dropdown-menu');
    
    if (userDropdownToggle && userDropdownMenu) {
        userDropdownToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdownMenu.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            userDropdownMenu.classList.remove('active');
        });
    }
    
    // Back to top button
    const backToTop = document.getElementById('back-to-top');
    if (backToTop) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });
        
        backToTop.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // Load user balance
    if (typeof crypto_theme !== 'undefined') {
        jQuery.ajax({
            url: crypto_theme.ajax_url,
            type: 'POST',
            data: {
                action: 'crypto_exchange_get_user_balance',
                nonce: crypto_theme.nonce
            },
            success: function(response) {
                if (response.success) {
                    const balanceElement = document.getElementById('header-balance');
                    if (balanceElement) {
                        balanceElement.textContent = '$' + response.data.total_balance.toFixed(2);
                    }
                }
            }
        });
    }
});
</script>

</body>
</html>
