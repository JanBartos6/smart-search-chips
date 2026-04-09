<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
    <footer class="ssc-site-footer">
        <div class="ssc-shell ssc-site-footer__inner">
            <div>
                <p class="ssc-site-footer__eyebrow"><?php esc_html_e( 'Dynamic chips. Better discovery. Lighter WordPress search.', 'ssc-cozy-demo' ); ?></p>
                <h2><?php esc_html_e( 'Smart Search Chips helps people narrow results without making search feel heavier.', 'ssc-cozy-demo' ); ?></h2>
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
