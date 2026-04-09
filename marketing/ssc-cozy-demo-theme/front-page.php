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
                <span class="ssc-kicker"><?php esc_html_e( 'Dynamic filter chips for WordPress', 'ssc-cozy-demo' ); ?></span>
                <h1><?php esc_html_e( 'Add smart, result-driven filter chips to search results, AJAX search, and archive pages.', 'ssc-cozy-demo' ); ?></h1>
                <p class="ssc-hero__lede">
                    <?php esc_html_e( 'Smart Search Chips turns flat WordPress search into a guided refinement flow. Instead of dumping people into a dead-end results page, it surfaces useful next-step filters from the results they are already looking at.', 'ssc-cozy-demo' ); ?>
                </p>
                <div class="ssc-button-row">
                    <a class="ssc-button ssc-button--solid" href="<?php echo esc_url( home_url( '/search/' ) ); ?>"><?php esc_html_e( 'Try the live demo', 'ssc-cozy-demo' ); ?></a>
                    <a class="ssc-button ssc-button--ghost" href="<?php echo esc_url( home_url( '/?s=tea' ) ); ?>"><?php esc_html_e( 'See search-results mode', 'ssc-cozy-demo' ); ?></a>
                </div>
                <ul class="ssc-hero-points">
                    <li><?php esc_html_e( 'Generates chips from the current result set, not from a generic static facet list.', 'ssc-cozy-demo' ); ?></li>
                    <li><?php esc_html_e( 'Works on live shortcode search, full search-results pages, and archive-style browsing.', 'ssc-cozy-demo' ); ?></li>
                    <li><?php esc_html_e( 'Supports built-in tags, custom fields, WooCommerce setups, and Relevanssi-powered search.', 'ssc-cozy-demo' ); ?></li>
                    <li><?php esc_html_e( 'Pro adds design controls, analytics, AI-assisted tagging workflows, and deeper integration options.', 'ssc-cozy-demo' ); ?></li>
                </ul>
                <ul class="ssc-stat-row">
                    <li><strong><?php esc_html_e( 'Contextual', 'ssc-cozy-demo' ); ?></strong><span><?php esc_html_e( 'Chips react to what actually matched, so the refinement options stay relevant.', 'ssc-cozy-demo' ); ?></span></li>
                    <li><strong><?php esc_html_e( 'Lightweight UX', 'ssc-cozy-demo' ); ?></strong><span><?php esc_html_e( 'You get a gentler discovery flow than a full sidebar of heavy faceted controls.', 'ssc-cozy-demo' ); ?></span></li>
                    <li><strong><?php esc_html_e( 'Commercial-ready', 'ssc-cozy-demo' ); ?></strong><span><?php esc_html_e( 'Free core flow, Pro gating, analytics, design tools, and hosted billing support are already in place.', 'ssc-cozy-demo' ); ?></span></li>
                </ul>
            </div>
            <aside class="ssc-hero-card" data-reveal>
                <div class="ssc-hero-card__header">
                    <span class="ssc-card-label"><?php esc_html_e( 'Live product proof', 'ssc-cozy-demo' ); ?></span>
                    <strong><?php esc_html_e( 'This site demonstrates the plugin, not just the mood.', 'ssc-cozy-demo' ); ?></strong>
                </div>
                <p class="ssc-hero-card__copy">
                    <?php esc_html_e( 'Moss & Clay is a deliberately coherent demo shop for teaware and serving pieces. It exists so you can see Smart Search Chips working in a believable catalog where overlap by material, ritual, and use actually matters.', 'ssc-cozy-demo' ); ?>
                </p>
                <p class="ssc-hero-card__hint">
                    <?php esc_html_e( 'The demo starts with a real tea search so the chips are visible immediately. Use the buttons below to swap the query.', 'ssc-cozy-demo' ); ?>
                </p>
                <div class="ssc-hero-card__search" data-demo-autoload="tea">
                    <?php echo do_shortcode( '[ssc_search placeholder="Search mug, matcha, stoneware, or serving"]' ); ?>
                </div>
                <div class="ssc-demo-pill-row">
                    <button class="ssc-demo-pill" type="button" data-demo-query="tea"><?php esc_html_e( 'Try "tea"', 'ssc-cozy-demo' ); ?></button>
                    <button class="ssc-demo-pill" type="button" data-demo-query="stoneware"><?php esc_html_e( 'Try "stoneware"', 'ssc-cozy-demo' ); ?></button>
                    <button class="ssc-demo-pill" type="button" data-demo-query="hosting"><?php esc_html_e( 'Try "hosting"', 'ssc-cozy-demo' ); ?></button>
                </div>
            </aside>
        </div>
    </section>

    <section class="ssc-section" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-section-heading">
                <span class="ssc-kicker"><?php esc_html_e( 'What the plugin actually adds', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'Smart Search Chips changes the shape of search, not just the styling around it.', 'ssc-cozy-demo' ); ?></h2>
            </div>
            <div class="ssc-feature-grid">
                <article class="ssc-card ssc-feature-card">
                    <h3><?php esc_html_e( 'Context-aware refinement', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'The chips are generated from the current result set, so the next filters feel relevant instead of generic.', 'ssc-cozy-demo' ); ?></p>
                </article>
                <article class="ssc-card ssc-feature-card">
                    <h3><?php esc_html_e( 'One plugin, multiple flows', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'Use it on live AJAX search, shortcode search pages, search-results templates, and archive browsing without rebuilding your site around a giant filter interface.', 'ssc-cozy-demo' ); ?></p>
                </article>
                <article class="ssc-card ssc-feature-card">
                    <h3><?php esc_html_e( 'Better discovery, less friction', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'Visitors get clickable next steps instead of a blank search dead-end, which makes discovery feel guided and noticeably calmer.', 'ssc-cozy-demo' ); ?></p>
                </article>
            </div>
        </div>
    </section>

    <section class="ssc-section ssc-showcase" data-reveal>
        <div class="ssc-shell ssc-showcase__grid">
            <div class="ssc-showcase__copy">
                <span class="ssc-kicker"><?php esc_html_e( 'Before and after', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'Default WordPress search gives you a list. Smart Search Chips gives you a next step.', 'ssc-cozy-demo' ); ?></h2>
                <p>
                    <?php esc_html_e( 'That added value is the whole point. Instead of forcing users to reformulate searches from scratch, the plugin exposes clickable refinements directly from what already matched.', 'ssc-cozy-demo' ); ?>
                </p>
                <div class="ssc-example-links">
                    <a href="<?php echo esc_url( home_url( '/search/' ) ); ?>"><?php esc_html_e( 'Test the live AJAX search flow', 'ssc-cozy-demo' ); ?></a>
                    <a href="<?php echo esc_url( home_url( '/?s=tea' ) ); ?>"><?php esc_html_e( 'Test the full results-page flow', 'ssc-cozy-demo' ); ?></a>
                    <a href="<?php echo esc_url( home_url( '/catalog/' ) ); ?>"><?php esc_html_e( 'See the full demo catalog behind both flows', 'ssc-cozy-demo' ); ?></a>
                </div>
            </div>
            <div class="ssc-stack">
                <article class="ssc-card ssc-stack-card ssc-comparison-card ssc-comparison-card--muted">
                    <span class="ssc-card-label"><?php esc_html_e( 'Without it', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'Static search results with no guided refinement.', 'ssc-cozy-demo' ); ?></h3>
                    <ul class="ssc-checklist ssc-checklist--muted">
                        <li><?php esc_html_e( 'Users hit a result list and must guess the next query themselves.', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'No lightweight way to narrow by the patterns inside the results they already see.', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'Search feels functional, but not especially helpful.', 'ssc-cozy-demo' ); ?></li>
                    </ul>
                </article>
                <article class="ssc-card ssc-stack-card ssc-comparison-card ssc-comparison-card--accent">
                    <span class="ssc-card-label"><?php esc_html_e( 'With Smart Search Chips', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'Search becomes a guided narrowing experience.', 'ssc-cozy-demo' ); ?></h3>
                    <ul class="ssc-checklist">
                        <li><?php esc_html_e( 'The plugin suggests useful filters from the current results automatically.', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'Visitors can click their way toward the right subset instead of starting over.', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'Archive pages, live AJAX search, and search-results templates all feel more intentional.', 'ssc-cozy-demo' ); ?></li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    <section class="ssc-section" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-section-heading">
                <span class="ssc-kicker"><?php esc_html_e( 'Where it fits best', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'Especially useful when content overlaps and the default search experience is too blunt.', 'ssc-cozy-demo' ); ?></h2>
            </div>
            <div class="ssc-feature-grid">
                <article class="ssc-card ssc-feature-card">
                    <h3><?php esc_html_e( 'Boutique ecommerce', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'Small and mid-sized shops with overlapping materials, styles, rituals, or use cases benefit immediately because visitors can narrow softly instead of drilling through menus.', 'ssc-cozy-demo' ); ?></p>
                </article>
                <article class="ssc-card ssc-feature-card">
                    <h3><?php esc_html_e( 'Technical catalogs', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'The same chip logic works on component-heavy or industrial catalogs too, where people search broadly and then need fast ways to narrow by function or compatibility.', 'ssc-cozy-demo' ); ?></p>
                </article>
                <article class="ssc-card ssc-feature-card">
                    <h3><?php esc_html_e( 'Content libraries and resource hubs', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'If your site has posts, case studies, or guides that cluster around recurring themes, Smart Search Chips can make search feel much more discoverable without a complex taxonomy UI.', 'ssc-cozy-demo' ); ?></p>
                </article>
            </div>
        </div>
    </section>

    <section class="ssc-section" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-section-heading ssc-section-heading--split">
                <div>
                    <span class="ssc-kicker"><?php esc_html_e( 'Proof through a coherent demo', 'ssc-cozy-demo' ); ?></span>
                    <h2><?php esc_html_e( 'The Moss & Clay catalog is here to demonstrate the plugin in a believable setting.', 'ssc-cozy-demo' ); ?></h2>
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
