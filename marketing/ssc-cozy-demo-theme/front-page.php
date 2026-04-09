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
                    <?php esc_html_e( 'Search stays light, but becomes much more useful. Chips are generated from the current results so visitors can narrow instantly instead of starting over.', 'ssc-cozy-demo' ); ?>
                </p>
                <div class="ssc-button-row">
                    <a class="ssc-button ssc-button--solid" href="<?php echo esc_url( home_url( '/search/' ) ); ?>"><?php esc_html_e( 'Try the live demo', 'ssc-cozy-demo' ); ?></a>
                    <a class="ssc-button ssc-button--ghost" href="<?php echo esc_url( home_url( '/?s=tea' ) ); ?>"><?php esc_html_e( 'See search-results mode', 'ssc-cozy-demo' ); ?></a>
                </div>
                <div class="ssc-proof-pills">
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Live AJAX search', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Search results pages', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Archive refinement', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'WooCommerce + custom fields', 'ssc-cozy-demo' ); ?></span>
                </div>
                <ul class="ssc-stat-row">
                    <li><strong><?php esc_html_e( 'Generated from results', 'ssc-cozy-demo' ); ?></strong><span><?php esc_html_e( 'Not a static filter wall.', 'ssc-cozy-demo' ); ?></span></li>
                    <li><strong><?php esc_html_e( 'Click to narrow', 'ssc-cozy-demo' ); ?></strong><span><?php esc_html_e( 'Less reformulating, less dead-end search.', 'ssc-cozy-demo' ); ?></span></li>
                    <li><strong><?php esc_html_e( 'Free + Pro', 'ssc-cozy-demo' ); ?></strong><span><?php esc_html_e( 'Core flow now, deeper control later.', 'ssc-cozy-demo' ); ?></span></li>
                </ul>
            </div>
            <aside class="ssc-hero-card" data-reveal>
                <div class="ssc-hero-card__header">
                    <span class="ssc-card-label"><?php esc_html_e( 'Live product proof', 'ssc-cozy-demo' ); ?></span>
                    <strong><?php esc_html_e( 'Preloaded so the chips are visible immediately.', 'ssc-cozy-demo' ); ?></strong>
                </div>
                <p class="ssc-hero-card__hint">
                    <?php esc_html_e( 'Switch between chip-rich queries and watch the demo search recompose itself.', 'ssc-cozy-demo' ); ?>
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
                <span class="ssc-kicker"><?php esc_html_e( 'Proof by showing', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'Three product views that sell the plugin faster than a paragraph does.', 'ssc-cozy-demo' ); ?></h2>
            </div>
            <div class="ssc-proof-grid">
                <article class="ssc-card ssc-proof-card">
                    <span class="ssc-card-label"><?php esc_html_e( 'Live AJAX', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'Type, reveal chips, keep narrowing.', 'ssc-cozy-demo' ); ?></h3>
                    <div class="ssc-ui-panel">
                        <div class="ssc-ui-searchbar">tea</div>
                        <div class="ssc-ui-chiprow">
                            <span>stoneware</span>
                            <span>tea ritual</span>
                            <span>hosting</span>
                        </div>
                        <div class="ssc-ui-results">
                            <span><?php esc_html_e( 'Linen Tea Canister', 'ssc-cozy-demo' ); ?></span>
                            <span><?php esc_html_e( 'Cedar Teapot', 'ssc-cozy-demo' ); ?></span>
                            <span><?php esc_html_e( 'Moss Matcha Bowl', 'ssc-cozy-demo' ); ?></span>
                        </div>
                    </div>
                </article>
                <article class="ssc-card ssc-proof-card">
                    <span class="ssc-card-label"><?php esc_html_e( 'Results pages', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'Active chips stay visible and shareable.', 'ssc-cozy-demo' ); ?></h3>
                    <div class="ssc-ui-panel">
                        <div class="ssc-ui-chiprow ssc-ui-chiprow--active">
                            <span>stoneware</span>
                            <span>tea ritual</span>
                            <span class="is-active">hosting</span>
                        </div>
                        <div class="ssc-ui-meta"><?php esc_html_e( 'Filtered URL + browser history', 'ssc-cozy-demo' ); ?></div>
                        <div class="ssc-ui-results">
                            <span><?php esc_html_e( 'Ember Tasting Cups', 'ssc-cozy-demo' ); ?></span>
                            <span><?php esc_html_e( 'Cedar Teapot', 'ssc-cozy-demo' ); ?></span>
                        </div>
                    </div>
                </article>
                <article class="ssc-card ssc-proof-card">
                    <span class="ssc-card-label"><?php esc_html_e( 'Pro insights', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'See what people search, click, and miss.', 'ssc-cozy-demo' ); ?></h3>
                    <div class="ssc-ui-panel ssc-ui-panel--stats">
                        <div class="ssc-mini-stat"><strong>62%</strong><span><?php esc_html_e( 'Search to click', 'ssc-cozy-demo' ); ?></span></div>
                        <div class="ssc-mini-stat"><strong>tea</strong><span><?php esc_html_e( 'Top query', 'ssc-cozy-demo' ); ?></span></div>
                        <div class="ssc-mini-stat"><strong>0-result</strong><span><?php esc_html_e( 'Trend tracking', 'ssc-cozy-demo' ); ?></span></div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="ssc-section ssc-showcase" data-reveal>
        <div class="ssc-shell ssc-showcase__grid">
            <div class="ssc-showcase__copy">
                <span class="ssc-kicker"><?php esc_html_e( 'Before and after', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'Without the plugin: a list. With the plugin: a path.', 'ssc-cozy-demo' ); ?></h2>
            </div>
            <div class="ssc-stack">
                <article class="ssc-card ssc-stack-card ssc-comparison-card ssc-comparison-card--muted">
                    <span class="ssc-card-label"><?php esc_html_e( 'Without it', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'Static results, no next move.', 'ssc-cozy-demo' ); ?></h3>
                    <div class="ssc-ui-panel ssc-ui-panel--muted">
                        <div class="ssc-ui-results">
                            <span><?php esc_html_e( 'Tea result 1', 'ssc-cozy-demo' ); ?></span>
                            <span><?php esc_html_e( 'Tea result 2', 'ssc-cozy-demo' ); ?></span>
                            <span><?php esc_html_e( 'Tea result 3', 'ssc-cozy-demo' ); ?></span>
                        </div>
                    </div>
                </article>
                <article class="ssc-card ssc-stack-card ssc-comparison-card ssc-comparison-card--accent">
                    <span class="ssc-card-label"><?php esc_html_e( 'With Smart Search Chips', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'Guided narrowing from the same result set.', 'ssc-cozy-demo' ); ?></h3>
                    <div class="ssc-ui-panel">
                        <div class="ssc-ui-chiprow ssc-ui-chiprow--active">
                            <span>stoneware</span>
                            <span>hosting</span>
                            <span class="is-active">tea ritual</span>
                        </div>
                        <div class="ssc-ui-results">
                            <span><?php esc_html_e( 'Moss Matcha Bowl', 'ssc-cozy-demo' ); ?></span>
                            <span><?php esc_html_e( 'Cedar Teapot', 'ssc-cozy-demo' ); ?></span>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="ssc-section" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-section-heading">
                <span class="ssc-kicker"><?php esc_html_e( 'Where it fits best', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'Best on catalogs and content sets with meaningful overlap.', 'ssc-cozy-demo' ); ?></h2>
            </div>
            <div class="ssc-fit-grid">
                <article class="ssc-card ssc-fit-card">
                    <h3><?php esc_html_e( 'Boutique ecommerce', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'Materials, styles, rituals, use cases.', 'ssc-cozy-demo' ); ?></p>
                </article>
                <article class="ssc-card ssc-fit-card">
                    <h3><?php esc_html_e( 'Technical catalogs', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'Function, compatibility, component families.', 'ssc-cozy-demo' ); ?></p>
                </article>
                <article class="ssc-card ssc-fit-card">
                    <h3><?php esc_html_e( 'Content libraries and resource hubs', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'Themes, topics, recurring problem spaces.', 'ssc-cozy-demo' ); ?></p>
                </article>
                <article class="ssc-card ssc-fit-card">
                    <h3><?php esc_html_e( 'Existing AJAX search themes', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'A lighter upgrade without rebuilding the whole search UI.', 'ssc-cozy-demo' ); ?></p>
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
