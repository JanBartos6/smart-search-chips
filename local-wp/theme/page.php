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
            ?>
            <article class="tf-card tf-search-card">
                <h1><?php the_title(); ?></h1>
                <?php the_content(); ?>
            </article>
            <?php
        endwhile;
        ?>
    </div>
</main>
<?php
get_footer();
