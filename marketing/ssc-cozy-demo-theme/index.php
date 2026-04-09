<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<main class="ssc-page">
    <section class="ssc-page-hero" data-reveal>
        <div class="ssc-shell">
            <span class="ssc-kicker"><?php esc_html_e( 'Latest entries', 'ssc-cozy-demo' ); ?></span>
            <h1><?php bloginfo( 'name' ); ?></h1>
        </div>
    </section>
    <section class="ssc-section" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-product-grid">
                <?php
                if ( have_posts() ) :
                    while ( have_posts() ) :
                        the_post();
                        ssc_demo_render_product_card( get_the_ID() );
                    endwhile;
                endif;
                ?>
            </div>
        </div>
    </section>
</main>
<?php
get_footer();
