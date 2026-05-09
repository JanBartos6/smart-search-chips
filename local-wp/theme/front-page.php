<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<main>
    <section class="tf-hero">
        <div class="tf-shell tf-hero__grid">
            <div>
                <span class="tf-kicker"><?php esc_html_e( 'Local plugin lab', 'trailforge' ); ?></span>
                <h1><?php esc_html_e( 'Find the right trail kit.', 'trailforge' ); ?></h1>
                <p><?php esc_html_e( 'This fake outdoor catalog is seeded with Search Chips Tags so you can test live filtering, chip counts, settings, and result rendering locally.', 'trailforge' ); ?></p>
            </div>
            <div class="tf-card tf-search-card">
                <h2><?php esc_html_e( 'Search the catalog', 'trailforge' ); ?></h2>
                <p><?php esc_html_e( 'Try tent, stove, waterproof, ultralight, coffee, or sleep.', 'trailforge' ); ?></p>
                <?php echo do_shortcode( '[sschp_search placeholder="Try tent, stove, waterproof, coffee, ultralight..."]' ); ?>
            </div>
        </div>
    </section>

    <section class="tf-section">
        <div class="tf-shell">
            <span class="tf-kicker"><?php esc_html_e( 'Seeded products', 'trailforge' ); ?></span>
            <h2><?php esc_html_e( 'A random catalog to test against.', 'trailforge' ); ?></h2>
            <div class="tf-grid">
                <?php
                $products = new WP_Query(
                    array(
                        'post_type'      => 'post',
                        'posts_per_page' => 12,
                    )
                );

                while ( $products->have_posts() ) :
                    $products->the_post();
                    $chips = trailforge_split_chips( get_post_meta( get_the_ID(), 'sschp_tags', true ) );
                    ?>
                    <article class="tf-card tf-product">
                        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        <p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
                        <?php if ( $chips ) : ?>
                            <div class="tf-tags">
                                <?php foreach ( $chips as $chip ) : ?>
                                    <span><?php echo esc_html( $chip ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                    <?php
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        </div>
    </section>
</main>
<?php
get_footer();
