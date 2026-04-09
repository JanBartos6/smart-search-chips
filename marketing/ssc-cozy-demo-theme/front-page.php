<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$featured_query = new WP_Query(
    array(
        'post_type'      => 'post',
        'posts_per_page' => 6,
        'category_name'  => 'catalog',
        'post_status'    => 'publish',
    )
);
?>
<main class="ssc-home">
    <section class="ssc-hero" data-reveal>
        <div class="ssc-shell ssc-hero__grid">
            <div class="ssc-hero__copy">
                <span class="ssc-kicker"><?php esc_html_e( 'WordPress plugin for calmer search experiences', 'ssc-cozy-demo' ); ?></span>
                <h1><?php esc_html_e( 'Turn search results into warm, useful filters people actually want to click.', 'ssc-cozy-demo' ); ?></h1>
                <p class="ssc-hero__lede">
                    <?php esc_html_e( 'This demo pairs Smart Search Chips with a fictional tea-and-tableware catalog called Moss & Clay. The point is not just filtering. It is helping the shop feel easier to browse, softer to use, and more intentional from the first search onward.', 'ssc-cozy-demo' ); ?>
                </p>
                <div class="ssc-button-row">
                    <a class="ssc-button ssc-button--solid" href="<?php echo esc_url( home_url( '/search/' ) ); ?>"><?php esc_html_e( 'Try the live demo', 'ssc-cozy-demo' ); ?></a>
                    <a class="ssc-button ssc-button--ghost" href="<?php echo esc_url( home_url( '/catalog/' ) ); ?>"><?php esc_html_e( 'Browse the catalog', 'ssc-cozy-demo' ); ?></a>
                </div>
                <ul class="ssc-stat-row">
                    <li><strong>AJAX</strong><span><?php esc_html_e( 'Live search chips update as you type', 'ssc-cozy-demo' ); ?></span></li>
                    <li><strong>Archive</strong><span><?php esc_html_e( 'Shareable result pages with visible active filters', 'ssc-cozy-demo' ); ?></span></li>
                    <li><strong>Pro</strong><span><?php esc_html_e( 'Design controls, analytics, AI workflow, Freemius gating', 'ssc-cozy-demo' ); ?></span></li>
                </ul>
            </div>
            <aside class="ssc-hero-card" data-reveal>
                <div class="ssc-hero-card__header">
                    <span class="ssc-card-label"><?php esc_html_e( 'Live demo panel', 'ssc-cozy-demo' ); ?></span>
                    <strong><?php esc_html_e( 'Search the Moss & Clay catalog', 'ssc-cozy-demo' ); ?></strong>
                </div>
                <p class="ssc-hero-card__copy">
                    <?php esc_html_e( 'Try everyday queries, not toy examples. This catalog is built around tea ritual, ceramics, hosting, and soft neutral materials so the chip suggestions feel consistent and believable.', 'ssc-cozy-demo' ); ?>
                </p>
                <div class="ssc-hero-card__search">
                    <?php echo do_shortcode( '[ssc_search placeholder="Search mug, matcha, stoneware, or serving"]' ); ?>
                </div>
                <div class="ssc-demo-pill-row">
                    <button class="ssc-demo-pill" type="button" data-demo-query="mug"><?php esc_html_e( 'Try "mug"', 'ssc-cozy-demo' ); ?></button>
                    <button class="ssc-demo-pill" type="button" data-demo-query="tea"><?php esc_html_e( 'Try "tea"', 'ssc-cozy-demo' ); ?></button>
                    <button class="ssc-demo-pill" type="button" data-demo-query="serving"><?php esc_html_e( 'Try "serving"', 'ssc-cozy-demo' ); ?></button>
                </div>
            </aside>
        </div>
    </section>

    <section class="ssc-section" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-section-heading">
                <span class="ssc-kicker"><?php esc_html_e( 'Why this demo feels coherent', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'One calm catalog, three strong plugin use cases.', 'ssc-cozy-demo' ); ?></h2>
            </div>
            <div class="ssc-feature-grid">
                <article class="ssc-card ssc-feature-card">
                    <h3><?php esc_html_e( 'Boutique catalogs', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'When products overlap by material, glaze, ritual, or use, chips help people narrow gently without a heavy filter sidebar.', 'ssc-cozy-demo' ); ?></p>
                </article>
                <article class="ssc-card ssc-feature-card">
                    <h3><?php esc_html_e( 'Editorial search pages', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'The search results page becomes more than a list. It turns into a guided refinement flow with shareable URLs and visible active tags.', 'ssc-cozy-demo' ); ?></p>
                </article>
                <article class="ssc-card ssc-feature-card">
                    <h3><?php esc_html_e( 'Lighter product discovery', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'Because the chips come from actual matching results, the interface feels contextual, not over-configured. That matters on small catalogs too.', 'ssc-cozy-demo' ); ?></p>
                </article>
            </div>
        </div>
    </section>

    <section class="ssc-section ssc-showcase" data-reveal>
        <div class="ssc-shell ssc-showcase__grid">
            <div class="ssc-showcase__copy">
                <span class="ssc-kicker"><?php esc_html_e( 'A thoughtful demo shop', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'Moss & Clay keeps the catalog narrow on purpose: teaware, serving pieces, and slow-morning essentials.', 'ssc-cozy-demo' ); ?></h2>
                <p>
                    <?php esc_html_e( 'That gives the search experience enough overlap to be interesting, but not so much noise that the filters feel fake. Search for a material, a ritual, or a product type and the chip set reacts with useful next steps.', 'ssc-cozy-demo' ); ?>
                </p>
                <div class="ssc-example-links">
                    <a href="<?php echo esc_url( home_url( '/?s=tea' ) ); ?>"><?php esc_html_e( 'Open a search-results demo for tea', 'ssc-cozy-demo' ); ?></a>
                    <a href="<?php echo esc_url( home_url( '/?s=mug' ) ); ?>"><?php esc_html_e( 'See how mugs narrow with chips', 'ssc-cozy-demo' ); ?></a>
                    <a href="<?php echo esc_url( home_url( '/?s=glaze' ) ); ?>"><?php esc_html_e( 'Check glaze-based refinement', 'ssc-cozy-demo' ); ?></a>
                </div>
            </div>
            <div class="ssc-stack">
                <article class="ssc-card ssc-stack-card">
                    <span class="ssc-card-label"><?php esc_html_e( 'Designed for this demo', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'Light palette, rounded edges, warm motion.', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'The visual language stays quiet so the search behavior gets to feel premium without turning the page into a control panel.', 'ssc-cozy-demo' ); ?></p>
                </article>
                <article class="ssc-card ssc-stack-card">
                    <span class="ssc-card-label"><?php esc_html_e( 'What to try', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'Search for product intent, not only exact titles.', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'Queries like tea, serving, giftable, glaze, or stoneware give a clearer picture of how Smart Search Chips guides discovery.', 'ssc-cozy-demo' ); ?></p>
                </article>
            </div>
        </div>
    </section>

    <section class="ssc-section" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-section-heading ssc-section-heading--split">
                <div>
                    <span class="ssc-kicker"><?php esc_html_e( 'Featured catalog pieces', 'ssc-cozy-demo' ); ?></span>
                    <h2><?php esc_html_e( 'A cozy demo catalog with enough overlap to make the chips useful.', 'ssc-cozy-demo' ); ?></h2>
                </div>
                <a class="ssc-button ssc-button--ghost" href="<?php echo esc_url( home_url( '/catalog/' ) ); ?>"><?php esc_html_e( 'View all catalog pieces', 'ssc-cozy-demo' ); ?></a>
            </div>
            <div class="ssc-product-grid">
                <?php
                if ( $featured_query->have_posts() ) :
                    while ( $featured_query->have_posts() ) :
                        $featured_query->the_post();
                        ssc_demo_render_product_card( get_the_ID() );
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

