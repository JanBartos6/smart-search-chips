<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<main class="tf-section">
    <div class="tf-shell">
        <?php
        while ( have_posts() ) :
            the_post();
            $chips = trailforge_split_chips( get_post_meta( get_the_ID(), 'sschp_tags', true ) );
            ?>
            <article class="tf-card tf-search-card">
                <span class="tf-kicker"><?php echo wp_kses_post( get_the_category_list( ', ' ) ); ?></span>
                <h1><?php the_title(); ?></h1>
                <?php the_content(); ?>
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
        ?>
    </div>
</main>
<?php
get_footer();
