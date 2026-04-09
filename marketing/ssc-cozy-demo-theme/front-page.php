<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$featured_query = new WP_Query(
    array(
        'post_type'      => 'post',
        'posts_per_page' => 4,
        'category_name'  => 'catalog',
        'post_status'    => 'publish',
    )
);

$mugs_term = get_category_by_slug( 'mugs' );
$mugs_url  = $mugs_term ? get_category_link( $mugs_term ) : home_url( '/category/mugs/' );
?>
<main class="ssc-home">
    <section class="ssc-hero ssc-hero--cinematic" data-reveal>
        <div class="ssc-shell ssc-hero__grid ssc-hero__grid--cinematic">
            <div class="ssc-hero__copy">
                <span class="ssc-kicker"><?php esc_html_e( 'Search that gently guides people forward', 'ssc-cozy-demo' ); ?></span>
                <h1><?php esc_html_e( 'Turn WordPress search into a visual path, not a dead end.', 'ssc-cozy-demo' ); ?></h1>
                <p class="ssc-hero__lede">
                    <?php esc_html_e( 'Smart Search Chips adds clickable next steps to search results, category pages, and catalogs, so visitors refine naturally instead of rewriting queries.', 'ssc-cozy-demo' ); ?>
                </p>
                <div class="ssc-button-row">
                    <a class="ssc-button ssc-button--solid" href="<?php echo esc_url( home_url( '/search/' ) ); ?>"><?php esc_html_e( 'Search the live catalog', 'ssc-cozy-demo' ); ?></a>
                    <a class="ssc-button ssc-button--ghost" href="#story"><?php esc_html_e( 'See the scroll walkthrough', 'ssc-cozy-demo' ); ?></a>
                </div>
                <div class="ssc-proof-pills">
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Search once', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Click to narrow', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Works on archives too', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Free + Pro', 'ssc-cozy-demo' ); ?></span>
                </div>
                <ul class="ssc-stat-row">
                    <li><strong><?php esc_html_e( 'Live discovery', 'ssc-cozy-demo' ); ?></strong><span><?php esc_html_e( 'Relevant chips appear from the current results.', 'ssc-cozy-demo' ); ?></span></li>
                    <li><strong><?php esc_html_e( 'Guided browsing', 'ssc-cozy-demo' ); ?></strong><span><?php esc_html_e( 'The same pattern continues on search and category pages.', 'ssc-cozy-demo' ); ?></span></li>
                    <li><strong><?php esc_html_e( 'Merchant control', 'ssc-cozy-demo' ); ?></strong><span><?php esc_html_e( 'Use built-in tags, Woo data, custom fields, analytics, and more.', 'ssc-cozy-demo' ); ?></span></li>
                </ul>
            </div>
            <aside class="ssc-hero-cinema" data-demo-autoload="tea">
                <div class="ssc-cinema-orb ssc-cinema-orb--sage"></div>
                <div class="ssc-cinema-orb ssc-cinema-orb--peach"></div>
                <div class="ssc-floating-chip ssc-floating-chip--1"><?php esc_html_e( 'stoneware', 'ssc-cozy-demo' ); ?></div>
                <div class="ssc-floating-chip ssc-floating-chip--2"><?php esc_html_e( 'hosting', 'ssc-cozy-demo' ); ?></div>
                <div class="ssc-floating-chip ssc-floating-chip--3"><?php esc_html_e( 'tea ritual', 'ssc-cozy-demo' ); ?></div>
                <div class="ssc-floating-chip ssc-floating-chip--4"><?php esc_html_e( 'giftable', 'ssc-cozy-demo' ); ?></div>
                <div class="ssc-cinema-card">
                    <div class="ssc-cinema-card__eyebrow">
                        <span class="ssc-card-label"><?php esc_html_e( 'Try it immediately', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-cinema-dot"></span>
                        <span><?php esc_html_e( 'Live catalog demo', 'ssc-cozy-demo' ); ?></span>
                    </div>
                    <h2><?php esc_html_e( 'Search the catalog and watch the next step appear.', 'ssc-cozy-demo' ); ?></h2>
                    <div class="ssc-hero-card__search">
                        <?php echo do_shortcode( '[ssc_search placeholder="Search tea, stoneware, serving, neutral, or giftable"]' ); ?>
                    </div>
                    <div class="ssc-demo-pill-row">
                        <button class="ssc-demo-pill" type="button" data-demo-query="tea"><?php esc_html_e( 'tea', 'ssc-cozy-demo' ); ?></button>
                        <button class="ssc-demo-pill" type="button" data-demo-query="stoneware"><?php esc_html_e( 'stoneware', 'ssc-cozy-demo' ); ?></button>
                        <button class="ssc-demo-pill" type="button" data-demo-query="hosting"><?php esc_html_e( 'hosting', 'ssc-cozy-demo' ); ?></button>
                    </div>
                </div>
            </aside>
        </div>
        <div class="ssc-scroll-cue">
            <span><?php esc_html_e( 'Scroll for the guided walkthrough', 'ssc-cozy-demo' ); ?></span>
        </div>
    </section>

    <section id="story" class="ssc-story" data-reveal>
        <div class="ssc-shell ssc-story__grid">
            <div class="ssc-story__stage-wrap">
                <div class="ssc-story__stage">
                    <div class="ssc-story__nav" aria-label="<?php esc_attr_e( 'Story navigation', 'ssc-cozy-demo' ); ?>">
                        <button class="ssc-story__jump is-active" type="button" data-story-jump="search"><?php esc_html_e( 'Search', 'ssc-cozy-demo' ); ?></button>
                        <button class="ssc-story__jump" type="button" data-story-jump="results"><?php esc_html_e( 'Results', 'ssc-cozy-demo' ); ?></button>
                        <button class="ssc-story__jump" type="button" data-story-jump="archive"><?php esc_html_e( 'Categories', 'ssc-cozy-demo' ); ?></button>
                        <button class="ssc-story__jump" type="button" data-story-jump="setup"><?php esc_html_e( 'Setup', 'ssc-cozy-demo' ); ?></button>
                        <button class="ssc-story__jump" type="button" data-story-jump="pro"><?php esc_html_e( 'Pro', 'ssc-cozy-demo' ); ?></button>
                    </div>

                    <div class="ssc-story__visual is-active" data-story-visual="search">
                        <div class="ssc-story-screen">
                            <div class="ssc-story-screen__bar">
                                <span class="ssc-story-screen__pill is-live"><?php esc_html_e( 'Live search', 'ssc-cozy-demo' ); ?></span>
                                <span class="ssc-story-screen__hint"><?php esc_html_e( 'Type broad. Narrow visually.', 'ssc-cozy-demo' ); ?></span>
                            </div>
                            <div class="ssc-ui-panel ssc-ui-panel--hero">
                                <div class="ssc-ui-searchbar">tea</div>
                                <div class="ssc-ui-chiprow">
                                    <span><?php esc_html_e( 'stoneware', 'ssc-cozy-demo' ); ?></span>
                                    <span><?php esc_html_e( 'tea ritual', 'ssc-cozy-demo' ); ?></span>
                                    <span><?php esc_html_e( 'hosting', 'ssc-cozy-demo' ); ?></span>
                                    <span><?php esc_html_e( 'giftable', 'ssc-cozy-demo' ); ?></span>
                                </div>
                                <div class="ssc-ui-results">
                                    <span><?php esc_html_e( 'Cedar Teapot', 'ssc-cozy-demo' ); ?></span>
                                    <span><?php esc_html_e( 'Moss Matcha Bowl', 'ssc-cozy-demo' ); ?></span>
                                    <span><?php esc_html_e( 'Linen Tea Canister', 'ssc-cozy-demo' ); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ssc-story__visual" data-story-visual="results">
                        <div class="ssc-story-screen">
                            <div class="ssc-story-screen__bar">
                                <span class="ssc-story-screen__pill"><?php esc_html_e( 'Search page', 'ssc-cozy-demo' ); ?></span>
                                <span class="ssc-story-screen__hint"><?php esc_html_e( 'Shareable state, visible active chips.', 'ssc-cozy-demo' ); ?></span>
                            </div>
                            <div class="ssc-ui-panel">
                                <div class="ssc-ui-browserbar">/ ?s=tea&amp;ssc_tags=stoneware</div>
                                <div class="ssc-ui-chiprow ssc-ui-chiprow--active">
                                    <span class="is-active"><?php esc_html_e( 'stoneware', 'ssc-cozy-demo' ); ?></span>
                                    <span><?php esc_html_e( 'tea ritual', 'ssc-cozy-demo' ); ?></span>
                                    <span><?php esc_html_e( 'hosting', 'ssc-cozy-demo' ); ?></span>
                                </div>
                                <div class="ssc-ui-count"><?php esc_html_e( '3 matching pieces', 'ssc-cozy-demo' ); ?></div>
                                <div class="ssc-ui-results">
                                    <span><?php esc_html_e( 'Cedar Teapot', 'ssc-cozy-demo' ); ?></span>
                                    <span><?php esc_html_e( 'Ember Tasting Cups', 'ssc-cozy-demo' ); ?></span>
                                    <span><?php esc_html_e( 'Hearth Speckle Mug', 'ssc-cozy-demo' ); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ssc-story__visual" data-story-visual="archive">
                        <div class="ssc-story-screen">
                            <div class="ssc-story-screen__bar">
                                <span class="ssc-story-screen__pill"><?php esc_html_e( 'Category page', 'ssc-cozy-demo' ); ?></span>
                                <span class="ssc-story-screen__hint"><?php esc_html_e( 'Collections become guided browse paths.', 'ssc-cozy-demo' ); ?></span>
                            </div>
                            <div class="ssc-ui-panel">
                                <div class="ssc-ui-section-title"><?php esc_html_e( 'Mugs', 'ssc-cozy-demo' ); ?></div>
                                <div class="ssc-ui-chiprow">
                                    <span><?php esc_html_e( 'stoneware', 'ssc-cozy-demo' ); ?></span>
                                    <span><?php esc_html_e( 'neutral glaze', 'ssc-cozy-demo' ); ?></span>
                                    <span><?php esc_html_e( 'giftable', 'ssc-cozy-demo' ); ?></span>
                                </div>
                                <div class="ssc-ui-results">
                                    <span><?php esc_html_e( 'Hearth Speckle Mug', 'ssc-cozy-demo' ); ?></span>
                                    <span><?php esc_html_e( 'Dawn Latte Cup', 'ssc-cozy-demo' ); ?></span>
                                    <span><?php esc_html_e( 'Ember Tasting Cups', 'ssc-cozy-demo' ); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ssc-story__visual" data-story-visual="setup">
                        <div class="ssc-story-screen">
                            <div class="ssc-story-screen__bar">
                                <span class="ssc-story-screen__pill"><?php esc_html_e( 'Setup', 'ssc-cozy-demo' ); ?></span>
                                <span class="ssc-story-screen__hint"><?php esc_html_e( 'Add chips directly or source them from your content.', 'ssc-cozy-demo' ); ?></span>
                            </div>
                            <div class="ssc-ui-panel ssc-ui-panel--admin">
                                <div class="ssc-ui-section-title"><?php esc_html_e( 'Post editor', 'ssc-cozy-demo' ); ?></div>
                                <div class="ssc-admin-row">
                                    <span class="ssc-admin-label"><?php esc_html_e( 'Title', 'ssc-cozy-demo' ); ?></span>
                                    <div class="ssc-admin-field"><?php esc_html_e( 'Cedar Teapot', 'ssc-cozy-demo' ); ?></div>
                                </div>
                                <div class="ssc-admin-row">
                                    <span class="ssc-admin-label"><?php esc_html_e( 'Search chips', 'ssc-cozy-demo' ); ?></span>
                                    <div class="ssc-admin-tag-input">
                                        <span><?php esc_html_e( 'stoneware', 'ssc-cozy-demo' ); ?></span>
                                        <span><?php esc_html_e( 'tea ritual', 'ssc-cozy-demo' ); ?></span>
                                        <span><?php esc_html_e( 'hosting', 'ssc-cozy-demo' ); ?></span>
                                        <em><?php esc_html_e( 'Add tag...', 'ssc-cozy-demo' ); ?></em>
                                    </div>
                                </div>
                                <div class="ssc-admin-row">
                                    <span class="ssc-admin-label"><?php esc_html_e( 'Data sources', 'ssc-cozy-demo' ); ?></span>
                                    <div class="ssc-admin-sidebar">
                                        <span><?php esc_html_e( 'Built-in tags', 'ssc-cozy-demo' ); ?></span>
                                        <span><?php esc_html_e( 'Woo attributes', 'ssc-cozy-demo' ); ?></span>
                                        <span><?php esc_html_e( 'Custom field mapping', 'ssc-cozy-demo' ); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ssc-story__visual" data-story-visual="pro">
                        <div class="ssc-story-screen">
                            <div class="ssc-story-screen__bar">
                                <span class="ssc-story-screen__pill is-pro"><?php esc_html_e( 'Pro', 'ssc-cozy-demo' ); ?></span>
                                <span class="ssc-story-screen__hint"><?php esc_html_e( 'Optimize what people search, click, and miss.', 'ssc-cozy-demo' ); ?></span>
                            </div>
                            <div class="ssc-ui-panel ssc-ui-panel--pro">
                                <div class="ssc-ui-kpi-grid">
                                    <div class="ssc-mini-stat"><strong>62%</strong><span><?php esc_html_e( 'Search to click', 'ssc-cozy-demo' ); ?></span></div>
                                    <div class="ssc-mini-stat"><strong>tea</strong><span><?php esc_html_e( 'Top query', 'ssc-cozy-demo' ); ?></span></div>
                                    <div class="ssc-mini-stat"><strong>3</strong><span><?php esc_html_e( 'Zero-result trends', 'ssc-cozy-demo' ); ?></span></div>
                                </div>
                                <div class="ssc-ui-analytics-list">
                                    <span><?php esc_html_e( 'Chip CTR: stoneware 28%', 'ssc-cozy-demo' ); ?></span>
                                    <span><?php esc_html_e( 'Design controls: radius, size, spacing', 'ssc-cozy-demo' ); ?></span>
                                    <span><?php esc_html_e( 'AI prompt builder + Relevanssi support', 'ssc-cozy-demo' ); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ssc-story__steps">
                <article id="story-search" class="ssc-story-step is-active" data-story-step="search">
                    <span class="ssc-card-label"><?php esc_html_e( 'Step 1', 'ssc-cozy-demo' ); ?></span>
                    <h2><?php esc_html_e( 'Start with a broad search and let the interface answer back.', 'ssc-cozy-demo' ); ?></h2>
                    <p><?php esc_html_e( 'Instead of forcing people to guess the perfect keyword, Smart Search Chips shows helpful filter ideas from the current result set.', 'ssc-cozy-demo' ); ?></p>
                    <div class="ssc-panel-chip-list">
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Live search', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Immediate narrowing', 'ssc-cozy-demo' ); ?></span>
                    </div>
                </article>

                <article id="story-results" class="ssc-story-step" data-story-step="results">
                    <span class="ssc-card-label"><?php esc_html_e( 'Step 2', 'ssc-cozy-demo' ); ?></span>
                    <h2><?php esc_html_e( 'Keep those chips visible on the search results page.', 'ssc-cozy-demo' ); ?></h2>
                    <p><?php esc_html_e( 'The state is shareable, back-button friendly, and easy to revisit later. People can keep narrowing without losing context.', 'ssc-cozy-demo' ); ?></p>
                    <div class="ssc-panel-chip-list">
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Active chips first', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Shareable URLs', 'ssc-cozy-demo' ); ?></span>
                    </div>
                </article>

                <article id="story-archive" class="ssc-story-step" data-story-step="archive">
                    <span class="ssc-card-label"><?php esc_html_e( 'Step 3', 'ssc-cozy-demo' ); ?></span>
                    <h2><?php esc_html_e( 'Turn category pages into guided browse paths.', 'ssc-cozy-demo' ); ?></h2>
                    <p><?php esc_html_e( 'This is especially useful when products overlap by material, function, mood, compatibility, or use case.', 'ssc-cozy-demo' ); ?></p>
                    <div class="ssc-panel-chip-list">
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Category filtering', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Catalog-friendly', 'ssc-cozy-demo' ); ?></span>
                    </div>
                </article>

                <article id="story-setup" class="ssc-story-step" data-story-step="setup">
                    <span class="ssc-card-label"><?php esc_html_e( 'Step 4', 'ssc-cozy-demo' ); ?></span>
                    <h2><?php esc_html_e( 'Add chips manually or source them from the data you already manage.', 'ssc-cozy-demo' ); ?></h2>
                    <p><?php esc_html_e( 'Use the built-in chip field, WooCommerce product data, and if you need more control, map custom fields in Pro.', 'ssc-cozy-demo' ); ?></p>
                    <div class="ssc-panel-chip-list">
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Built-in tags', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'WooCommerce aware', 'ssc-cozy-demo' ); ?></span>
                    </div>
                </article>

                <article id="story-pro" class="ssc-story-step" data-story-step="pro">
                    <span class="ssc-card-label"><?php esc_html_e( 'Step 5', 'ssc-cozy-demo' ); ?></span>
                    <h2><?php esc_html_e( 'Use Pro when you want insight, polish, and scale.', 'ssc-cozy-demo' ); ?></h2>
                    <p><?php esc_html_e( 'Pro adds analytics, advanced chip styling, custom field mapping, higher limits, AI prompt building, and Relevanssi integration.', 'ssc-cozy-demo' ); ?></p>
                    <div class="ssc-panel-chip-list">
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Analytics', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Design controls', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'AI + Relevanssi', 'ssc-cozy-demo' ); ?></span>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="ssc-section" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-section-heading">
                <span class="ssc-kicker"><?php esc_html_e( 'Free and Pro', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'Start with the experience. Upgrade for insight and control.', 'ssc-cozy-demo' ); ?></h2>
            </div>
            <div class="ssc-tier-grid">
                <article class="ssc-card ssc-tier-card">
                    <span class="ssc-card-label"><?php esc_html_e( 'Free', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'Get the search experience live.', 'ssc-cozy-demo' ); ?></h3>
                    <div class="ssc-panel-chip-list">
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Live search chips', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Results-page filtering', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Category/archive filtering', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Built-in chip field', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'WooCommerce support', 'ssc-cozy-demo' ); ?></span>
                    </div>
                </article>
                <article class="ssc-card ssc-tier-card ssc-tier-card--pro">
                    <span class="ssc-card-label"><?php esc_html_e( 'Pro', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'Learn what works and shape it to your catalog.', 'ssc-cozy-demo' ); ?></h3>
                    <div class="ssc-panel-chip-list">
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Analytics dashboard', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Custom field mapping', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Advanced chip design', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Relevanssi integration', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'AI prompt builder', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Higher chip limits', 'ssc-cozy-demo' ); ?></span>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="ssc-section" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-section-heading ssc-section-heading--split">
                <div>
                    <span class="ssc-kicker"><?php esc_html_e( 'Believable demo catalog', 'ssc-cozy-demo' ); ?></span>
                    <h2><?php esc_html_e( 'Moss & Clay is here so the plugin can prove itself in a catalog where overlap really matters.', 'ssc-cozy-demo' ); ?></h2>
                </div>
                <a class="ssc-button ssc-button--ghost" href="<?php echo esc_url( home_url( '/catalog/' ) ); ?>"><?php esc_html_e( 'Browse the catalog', 'ssc-cozy-demo' ); ?></a>
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
