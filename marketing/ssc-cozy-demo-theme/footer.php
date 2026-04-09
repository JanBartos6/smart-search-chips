<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
    <footer class="ssc-site-footer">
        <div class="ssc-shell ssc-site-footer__inner">
            <div class="ssc-site-footer__cta">
                <h2><?php esc_html_e( 'Try it yourself.', 'ssc-cozy-demo' ); ?></h2>
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
