<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<main class="ssc-page ssc-results-page">
    <section class="ssc-page-hero" data-reveal>
        <div class="ssc-shell ssc-page-hero__grid">
            <div>
                <span class="ssc-kicker"><?php esc_html_e( 'Search results experience', 'ssc-cozy-demo' ); ?></span>
                <h1>
                    <?php
                    printf(
                        /* translators: %s: search query */
                        esc_html__( 'Results for "%s"', 'ssc-cozy-demo' ),
                        esc_html( get_search_query() )
                    );
                    ?>
                </h1>
                <p class="ssc-page-hero__lede">
                    <?php esc_html_e( 'This is the shareable archive-style flow. Here the chips live above the results, stay visible through navigation, and make it easy to refine or link someone directly to a filtered state.', 'ssc-cozy-demo' ); ?>
                </p>
            </div>
            <aside class="ssc-card ssc-side-note">
                <span class="ssc-card-label"><?php esc_html_e( 'Try another query', 'ssc-cozy-demo' ); ?></span>
                <?php sschp_demo_render_native_search_form( get_search_query() ); ?>
            </aside>
        </div>
    </section>

    <section class="ssc-section" data-reveal>
        <div class="ssc-shell">
            <?php if ( have_posts() ) : ?>
                <div class="ssc-product-grid">
                    <?php
                    while ( have_posts() ) :
                        the_post();
                        sschp_demo_render_product_card( get_the_ID() );
                    endwhile;
                    ?>
                </div>
            <?php else : ?>
                <div class="ssc-card ssc-empty-state">
                    <h2><?php esc_html_e( 'Nothing matched yet.', 'ssc-cozy-demo' ); ?></h2>
                    <p><?php esc_html_e( 'Try broader language like tea, mug, serving, glaze, or giftable. The demo catalog is intentionally small and overlapping so the filter chips stay relevant.', 'ssc-cozy-demo' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php
get_footer();
