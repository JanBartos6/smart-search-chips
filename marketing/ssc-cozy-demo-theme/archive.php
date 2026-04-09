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
                <span class="ssc-kicker"><?php esc_html_e( 'Catalog archive', 'ssc-cozy-demo' ); ?></span>
                <h1><?php the_archive_title(); ?></h1>
                <?php the_archive_description( '<p class="ssc-page-hero__lede">', '</p>' ); ?>
            </div>
            <aside class="ssc-card ssc-side-note">
                <span class="ssc-card-label"><?php esc_html_e( 'Use this view to test archive chips', 'ssc-cozy-demo' ); ?></span>
                <p><?php esc_html_e( 'Category and collection archives also support Smart Search Chips. When the content overlaps enough, the archive becomes a guided browse instead of a flat list.', 'ssc-cozy-demo' ); ?></p>
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
                        ssc_demo_render_product_card( get_the_ID() );
                    endwhile;
                    ?>
                </div>
            <?php else : ?>
                <div class="ssc-card ssc-empty-state">
                    <h2><?php esc_html_e( 'Nothing to show here.', 'ssc-cozy-demo' ); ?></h2>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php
get_footer();

