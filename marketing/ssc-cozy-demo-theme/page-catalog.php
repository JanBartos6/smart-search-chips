<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$catalog_query = new WP_Query(
    array(
        'post_type'      => 'post',
        'posts_per_page' => -1,
        'category_name'  => 'catalog',
        'post_status'    => 'publish',
    )
);

$collections = get_categories(
    array(
        'hide_empty' => false,
        'slug'       => array( 'mugs', 'tea-tools', 'serving-pieces' ),
    )
);
?>
<main class="ssc-page ssc-catalog-page">
    <section class="ssc-page-hero" data-reveal>
        <div class="ssc-shell ssc-page-hero__grid">
            <div>
                <span class="ssc-kicker"><?php esc_html_e( 'Demo catalog', 'ssc-cozy-demo' ); ?></span>
                <h1><?php esc_html_e( 'A small shop with enough overlap to make search feel smart.', 'ssc-cozy-demo' ); ?></h1>
                <p class="ssc-page-hero__lede">
                    <?php esc_html_e( 'Moss & Clay is intentionally narrow: mugs, tea tools, and serving pieces for slow mornings. It gives Smart Search Chips room to show how dynamic filters can stay gentle and still be useful.', 'ssc-cozy-demo' ); ?>
                </p>
            </div>
            <aside class="ssc-card ssc-side-note">
                <span class="ssc-card-label"><?php esc_html_e( 'Shortcut', 'ssc-cozy-demo' ); ?></span>
                <p><?php esc_html_e( 'If you want the fully interactive plugin flow, jump to the live AJAX demo or open one of the full search-result examples below.', 'ssc-cozy-demo' ); ?></p>
                <div class="ssc-button-column">
                    <a class="ssc-button ssc-button--solid" href="<?php echo esc_url( home_url( '/search/' ) ); ?>"><?php esc_html_e( 'Open live demo search', 'ssc-cozy-demo' ); ?></a>
                    <a class="ssc-button ssc-button--ghost" href="<?php echo esc_url( home_url( '/?s=tea' ) ); ?>"><?php esc_html_e( 'Open search results for tea', 'ssc-cozy-demo' ); ?></a>
                </div>
            </aside>
        </div>
    </section>

    <?php if ( ! empty( $collections ) ) : ?>
        <section class="ssc-section ssc-collection-links" data-reveal>
            <div class="ssc-shell">
                <div class="ssc-feature-grid">
                    <?php foreach ( $collections as $collection ) : ?>
                        <a class="ssc-card ssc-collection-card" href="<?php echo esc_url( get_category_link( $collection->term_id ) ); ?>">
                            <span class="ssc-card-label"><?php esc_html_e( 'Collection archive', 'ssc-cozy-demo' ); ?></span>
                            <h3><?php echo esc_html( $collection->name ); ?></h3>
                            <p><?php echo esc_html( $collection->description ? $collection->description : __( 'Browse this slice of the catalog on its own archive page.', 'ssc-cozy-demo' ) ); ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="ssc-section" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-product-grid">
                <?php
                if ( $catalog_query->have_posts() ) :
                    while ( $catalog_query->have_posts() ) :
                        $catalog_query->the_post();
                        sschp_demo_render_product_card( get_the_ID() );
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
            </div>
        </div>
    </section>
</main>
<?php
get_footer();
