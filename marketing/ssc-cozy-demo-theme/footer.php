<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
    <footer class="ssc-site-footer">
        <div class="ssc-shell ssc-site-footer__inner">
            <div>
                <p class="ssc-site-footer__eyebrow"><?php esc_html_e( 'Start with the core flow. Upgrade when you want deeper control.', 'ssc-cozy-demo' ); ?></p>
                <h2><?php esc_html_e( 'Smart Search Chips makes WordPress search easier to use, then gives Pro users analytics, design control, and better data mapping.', 'ssc-cozy-demo' ); ?></h2>
            </div>
            <div class="ssc-site-footer__actions">
                <a class="ssc-button ssc-button--solid" href="<?php echo esc_url( home_url( '/search/' ) ); ?>"><?php esc_html_e( 'Open the live demo', 'ssc-cozy-demo' ); ?></a>
                <a class="ssc-button ssc-button--ghost" href="<?php echo esc_url( home_url( '/?s=tea' ) ); ?>"><?php esc_html_e( 'See filtered results', 'ssc-cozy-demo' ); ?></a>
            </div>
        </div>
    </footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
