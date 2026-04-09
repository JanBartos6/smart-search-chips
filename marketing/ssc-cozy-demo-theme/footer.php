<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
    <footer class="ssc-site-footer">
        <div class="ssc-shell ssc-site-footer__inner">
            <div>
                <p class="ssc-site-footer__eyebrow"><?php esc_html_e( 'Built to feel light, even when the catalog is not.', 'ssc-cozy-demo' ); ?></p>
                <h2><?php esc_html_e( 'Smart Search Chips for calmer WordPress search.', 'ssc-cozy-demo' ); ?></h2>
            </div>
            <div class="ssc-site-footer__actions">
                <a class="ssc-button ssc-button--solid" href="<?php echo esc_url( home_url( '/search/' ) ); ?>"><?php esc_html_e( 'Open the live demo', 'ssc-cozy-demo' ); ?></a>
                <a class="ssc-button ssc-button--ghost" href="<?php echo esc_url( home_url( '/catalog/' ) ); ?>"><?php esc_html_e( 'Browse the catalog', 'ssc-cozy-demo' ); ?></a>
            </div>
        </div>
    </footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
