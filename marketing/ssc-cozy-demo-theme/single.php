<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<main class="ssc-page ssc-single-page">
    <section class="ssc-page-hero" data-reveal>
        <div class="ssc-shell ssc-page-hero__grid">
            <?php
            while ( have_posts() ) :
                the_post();
                $tags = sschp_demo_get_product_tags( get_the_ID(), 0 );
                ?>
                <div>
                    <span class="ssc-kicker"><?php echo esc_html( sschp_demo_get_collection_label( get_the_ID() ) ); ?></span>
                    <h1><?php the_title(); ?></h1>
                    <p class="ssc-page-hero__lede"><?php echo esc_html( wp_trim_words( get_the_content(), 36 ) ); ?></p>
                    <?php if ( ! empty( $tags ) ) : ?>
                        <div class="ssc-product-card__tags ssc-product-card__tags--full">
                            <?php foreach ( $tags as $tag ) : ?>
                                <span class="ssc-soft-chip"><?php echo esc_html( $tag ); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <aside class="ssc-card ssc-side-note">
                    <span class="ssc-card-label"><?php esc_html_e( 'Demo pathways', 'ssc-cozy-demo' ); ?></span>
                    <div class="ssc-button-column">
                        <a class="ssc-button ssc-button--solid" href="<?php echo esc_url( home_url( '/search/' ) ); ?>"><?php esc_html_e( 'Open live demo', 'ssc-cozy-demo' ); ?></a>
                        <a class="ssc-button ssc-button--ghost" href="<?php echo esc_url( add_query_arg( 's', get_the_title(), home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Search for this piece', 'ssc-cozy-demo' ); ?></a>
                    </div>
                </aside>
                <?php
            endwhile;
            rewind_posts();
            ?>
        </div>
    </section>

    <section class="ssc-section" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-card ssc-single-body">
                <?php
                while ( have_posts() ) :
                    the_post();
                    the_content();
                endwhile;
                ?>
            </div>
        </div>
    </section>
</main>
<?php
get_footer();
