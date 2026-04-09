<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<main class="ssc-page">
    <section class="ssc-page-hero" data-reveal>
        <div class="ssc-shell">
            <?php while ( have_posts() ) : the_post(); ?>
                <span class="ssc-kicker"><?php esc_html_e( 'Page', 'ssc-cozy-demo' ); ?></span>
                <h1><?php the_title(); ?></h1>
            <?php endwhile; rewind_posts(); ?>
        </div>
    </section>
    <section class="ssc-section" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-card ssc-single-body">
                <?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>
            </div>
        </div>
    </section>
</main>
<?php
get_footer();

