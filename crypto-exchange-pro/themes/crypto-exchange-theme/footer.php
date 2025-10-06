    </div><!-- #content -->

    <footer id="colophon" class="site-footer">
        <div class="footer-content">
            <div class="footer-links">
                <a href="<?php echo esc_url(home_url('/about')); ?>">About</a>
                <a href="<?php echo esc_url(home_url('/security')); ?>">Security</a>
                <a href="<?php echo esc_url(home_url('/fees')); ?>">Fees</a>
                <a href="<?php echo esc_url(home_url('/support')); ?>">Support</a>
                <a href="<?php echo esc_url(home_url('/api')); ?>">API</a>
                <a href="<?php echo esc_url(home_url('/legal')); ?>">Legal</a>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
