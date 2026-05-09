<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<main class="tf-section">
    <div class="tf-shell">
        <div class="tf-grid">
            <?php
            while ( have_posts() ) :
                the_post();
                $chips = trailforge_split_chips( get_post_meta( get_the_ID(), 'sschp_tags', true ) );
                ?>
                <article class="tf-card tf-product">
                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
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
    </div>
</main>
<?php
get_footer();
