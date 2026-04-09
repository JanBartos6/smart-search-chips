<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
    <footer class="ssc-site-footer">
        <div class="ssc-shell ssc-site-footer__legal">
            <nav class="ssc-legal-links" aria-label="<?php esc_attr_e( 'Legal', 'ssc-cozy-demo' ); ?>">
                <a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>"><?php esc_html_e( 'Terms', 'ssc-cozy-demo' ); ?></a>
                <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy', 'ssc-cozy-demo' ); ?></a>
                <a href="<?php echo esc_url( home_url( '/refund-policy/' ) ); ?>"><?php esc_html_e( 'Refunds', 'ssc-cozy-demo' ); ?></a>
                <a href="<?php echo esc_url( home_url( '/support-policy/' ) ); ?>"><?php esc_html_e( 'Support', 'ssc-cozy-demo' ); ?></a>
            </nav>
        </div>
    </footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
