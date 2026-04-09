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
    <section class="ssc-hero" data-reveal>
        <div class="ssc-shell ssc-hero__grid">
            <div class="ssc-hero__copy">
                <span class="ssc-kicker"><?php esc_html_e( 'Clickable filters for WordPress search', 'ssc-cozy-demo' ); ?></span>
                <h1><?php esc_html_e( 'Add smart search chips to search, category pages, and catalogs.', 'ssc-cozy-demo' ); ?></h1>
                <p class="ssc-hero__lede">
                    <?php esc_html_e( 'People search once, then narrow from the results they already have. No heavy filter wall, no starting over.', 'ssc-cozy-demo' ); ?>
                </p>
                <div class="ssc-button-row">
                    <a class="ssc-button ssc-button--solid" href="<?php echo esc_url( home_url( '/search/' ) ); ?>"><?php esc_html_e( 'Try the live demo', 'ssc-cozy-demo' ); ?></a>
                    <a class="ssc-button ssc-button--ghost" href="<?php echo esc_url( home_url( '/?s=tea' ) ); ?>"><?php esc_html_e( 'See results-page filtering', 'ssc-cozy-demo' ); ?></a>
                </div>
                <div class="ssc-proof-pills">
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Live search', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Search pages', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Category filtering', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Free + Pro', 'ssc-cozy-demo' ); ?></span>
                </div>
                <ul class="ssc-stat-row">
                    <li><strong><?php esc_html_e( 'Search once', 'ssc-cozy-demo' ); ?></strong><span><?php esc_html_e( 'The next step appears as chips.', 'ssc-cozy-demo' ); ?></span></li>
                    <li><strong><?php esc_html_e( 'Click to narrow', 'ssc-cozy-demo' ); ?></strong><span><?php esc_html_e( 'Visitors refine without friction.', 'ssc-cozy-demo' ); ?></span></li>
                    <li><strong><?php esc_html_e( 'Keep browsing', 'ssc-cozy-demo' ); ?></strong><span><?php esc_html_e( 'Works on results and archive pages too.', 'ssc-cozy-demo' ); ?></span></li>
                </ul>
            </div>
            <aside class="ssc-hero-card" data-reveal>
                <div class="ssc-hero-card__header">
                    <span class="ssc-card-label"><?php esc_html_e( 'Try it right here', 'ssc-cozy-demo' ); ?></span>
                    <strong><?php esc_html_e( 'Start broad. Let the chips do the narrowing.', 'ssc-cozy-demo' ); ?></strong>
                </div>
                <p class="ssc-hero-card__hint">
                    <?php esc_html_e( 'The demo starts with a chip-rich query so the feature is visible immediately.', 'ssc-cozy-demo' ); ?>
                </p>
                <div class="ssc-hero-card__search" data-demo-autoload="tea">
                    <?php echo do_shortcode( '[ssc_search placeholder="Search tea, stoneware, serving, neutral, or giftable"]' ); ?>
                </div>
                <div class="ssc-demo-pill-row">
                    <button class="ssc-demo-pill" type="button" data-demo-query="tea"><?php esc_html_e( 'tea', 'ssc-cozy-demo' ); ?></button>
                    <button class="ssc-demo-pill" type="button" data-demo-query="stoneware"><?php esc_html_e( 'stoneware', 'ssc-cozy-demo' ); ?></button>
                    <button class="ssc-demo-pill" type="button" data-demo-query="hosting"><?php esc_html_e( 'hosting', 'ssc-cozy-demo' ); ?></button>
                </div>
            </aside>
        </div>
    </section>

    <section class="ssc-section" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-section-heading">
                <span class="ssc-kicker"><?php esc_html_e( 'See the whole plugin', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'Five quick views. No essay required.', 'ssc-cozy-demo' ); ?></h2>
            </div>
            <div class="ssc-experience-shell">
                <div class="ssc-feature-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Feature showcase', 'ssc-cozy-demo' ); ?>">
                    <button class="ssc-feature-tab is-active" type="button" data-feature-target="live" aria-pressed="true"><?php esc_html_e( 'Live search', 'ssc-cozy-demo' ); ?></button>
                    <button class="ssc-feature-tab" type="button" data-feature-target="results" aria-pressed="false"><?php esc_html_e( 'Search page', 'ssc-cozy-demo' ); ?></button>
                    <button class="ssc-feature-tab" type="button" data-feature-target="archive" aria-pressed="false"><?php esc_html_e( 'Category page', 'ssc-cozy-demo' ); ?></button>
                    <button class="ssc-feature-tab" type="button" data-feature-target="tags" aria-pressed="false"><?php esc_html_e( 'Add chips', 'ssc-cozy-demo' ); ?></button>
                    <button class="ssc-feature-tab" type="button" data-feature-target="pro" aria-pressed="false"><?php esc_html_e( 'Pro tools', 'ssc-cozy-demo' ); ?></button>
                </div>

                <div class="ssc-showcase-panel is-active" data-feature-panel="live">
                    <div class="ssc-showcase-copy">
                        <span class="ssc-card-label"><?php esc_html_e( 'Free', 'ssc-cozy-demo' ); ?></span>
                        <h3><?php esc_html_e( 'Type once. Then let people click their way closer.', 'ssc-cozy-demo' ); ?></h3>
                        <p><?php esc_html_e( 'Live search reveals useful chips from the current results, so visitors can refine without leaving the page.', 'ssc-cozy-demo' ); ?></p>
                        <div class="ssc-panel-chip-list">
                            <span class="ssc-soft-chip"><?php esc_html_e( 'Updates as you type', 'ssc-cozy-demo' ); ?></span>
                            <span class="ssc-soft-chip"><?php esc_html_e( 'AND filtering', 'ssc-cozy-demo' ); ?></span>
                            <span class="ssc-soft-chip"><?php esc_html_e( 'Shortcode ready', 'ssc-cozy-demo' ); ?></span>
                        </div>
                        <div class="ssc-panel-actions">
                            <a class="ssc-button ssc-button--solid" href="<?php echo esc_url( home_url( '/search/' ) ); ?>"><?php esc_html_e( 'Open live search', 'ssc-cozy-demo' ); ?></a>
                        </div>
                    </div>
                    <div class="ssc-showcase-visual">
                        <div class="ssc-ui-panel">
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

                <div class="ssc-showcase-panel" data-feature-panel="results" hidden>
                    <div class="ssc-showcase-copy">
                        <span class="ssc-card-label"><?php esc_html_e( 'Free', 'ssc-cozy-demo' ); ?></span>
                        <h3><?php esc_html_e( 'Keep filters visible on the search results page.', 'ssc-cozy-demo' ); ?></h3>
                        <p><?php esc_html_e( 'Chips stay active, the URL stays shareable, and visitors can move back and forward without losing the filter state.', 'ssc-cozy-demo' ); ?></p>
                        <div class="ssc-panel-chip-list">
                            <span class="ssc-soft-chip"><?php esc_html_e( 'Active chips stay visible', 'ssc-cozy-demo' ); ?></span>
                            <span class="ssc-soft-chip"><?php esc_html_e( 'Shareable URLs', 'ssc-cozy-demo' ); ?></span>
                            <span class="ssc-soft-chip"><?php esc_html_e( 'Browser history friendly', 'ssc-cozy-demo' ); ?></span>
                        </div>
                        <div class="ssc-panel-actions">
                            <a class="ssc-button ssc-button--solid" href="<?php echo esc_url( home_url( '/?s=tea' ) ); ?>"><?php esc_html_e( 'Open search page demo', 'ssc-cozy-demo' ); ?></a>
                        </div>
                    </div>
                    <div class="ssc-showcase-visual">
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

                <div class="ssc-showcase-panel" data-feature-panel="archive" hidden>
                    <div class="ssc-showcase-copy">
                        <span class="ssc-card-label"><?php esc_html_e( 'Free', 'ssc-cozy-demo' ); ?></span>
                        <h3><?php esc_html_e( 'Category pages stop being flat lists.', 'ssc-cozy-demo' ); ?></h3>
                        <p><?php esc_html_e( 'The same chip logic can guide browsing inside a collection, so people can keep narrowing after they land on a category page.', 'ssc-cozy-demo' ); ?></p>
                        <div class="ssc-panel-chip-list">
                            <span class="ssc-soft-chip"><?php esc_html_e( 'Works on archives', 'ssc-cozy-demo' ); ?></span>
                            <span class="ssc-soft-chip"><?php esc_html_e( 'Collection-aware browse', 'ssc-cozy-demo' ); ?></span>
                            <span class="ssc-soft-chip"><?php esc_html_e( 'Better category pages', 'ssc-cozy-demo' ); ?></span>
                        </div>
                        <div class="ssc-panel-actions">
                            <a class="ssc-button ssc-button--solid" href="<?php echo esc_url( $mugs_url ); ?>"><?php esc_html_e( 'Open category demo', 'ssc-cozy-demo' ); ?></a>
                        </div>
                    </div>
                    <div class="ssc-showcase-visual">
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

                <div class="ssc-showcase-panel" data-feature-panel="tags" hidden>
                    <div class="ssc-showcase-copy">
                        <span class="ssc-card-label"><?php esc_html_e( 'Free first, Pro expands it', 'ssc-cozy-demo' ); ?></span>
                        <h3><?php esc_html_e( 'Add your own chips, or pull them from the data you already have.', 'ssc-cozy-demo' ); ?></h3>
                        <p><?php esc_html_e( 'You can use the built-in tag field, WooCommerce product data, and in Pro, custom fields too.', 'ssc-cozy-demo' ); ?></p>
                        <div class="ssc-panel-chip-list">
                            <span class="ssc-soft-chip"><?php esc_html_e( 'Built-in chip editor', 'ssc-cozy-demo' ); ?></span>
                            <span class="ssc-soft-chip"><?php esc_html_e( 'WooCommerce aware', 'ssc-cozy-demo' ); ?></span>
                            <span class="ssc-soft-chip"><?php esc_html_e( 'Custom fields in Pro', 'ssc-cozy-demo' ); ?></span>
                        </div>
                    </div>
                    <div class="ssc-showcase-visual">
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

                <div class="ssc-showcase-panel" data-feature-panel="pro" hidden>
                    <div class="ssc-showcase-copy">
                        <span class="ssc-card-label"><?php esc_html_e( 'Pro', 'ssc-cozy-demo' ); ?></span>
                        <h3><?php esc_html_e( 'Learn from search behavior. Style it. Scale it.', 'ssc-cozy-demo' ); ?></h3>
                        <p><?php esc_html_e( 'Pro adds analytics, advanced design control, custom field mapping, Relevanssi integration, AI prompt building, and higher chip limits.', 'ssc-cozy-demo' ); ?></p>
                        <div class="ssc-panel-chip-list">
                            <span class="ssc-soft-chip"><?php esc_html_e( 'Analytics dashboard', 'ssc-cozy-demo' ); ?></span>
                            <span class="ssc-soft-chip"><?php esc_html_e( 'Advanced chip styling', 'ssc-cozy-demo' ); ?></span>
                            <span class="ssc-soft-chip"><?php esc_html_e( 'AI prompt builder', 'ssc-cozy-demo' ); ?></span>
                            <span class="ssc-soft-chip"><?php esc_html_e( 'Relevanssi integration', 'ssc-cozy-demo' ); ?></span>
                        </div>
                    </div>
                    <div class="ssc-showcase-visual">
                        <div class="ssc-ui-panel ssc-ui-panel--pro">
                            <div class="ssc-ui-kpi-grid">
                                <div class="ssc-mini-stat"><strong>62%</strong><span><?php esc_html_e( 'Search to click', 'ssc-cozy-demo' ); ?></span></div>
                                <div class="ssc-mini-stat"><strong>tea</strong><span><?php esc_html_e( 'Top query', 'ssc-cozy-demo' ); ?></span></div>
                                <div class="ssc-mini-stat"><strong>3</strong><span><?php esc_html_e( 'Zero-result trends', 'ssc-cozy-demo' ); ?></span></div>
                            </div>
                            <div class="ssc-ui-analytics-list">
                                <span><?php esc_html_e( 'Chip CTR: stoneware 28%', 'ssc-cozy-demo' ); ?></span>
                                <span><?php esc_html_e( 'Design: radius, size, spacing', 'ssc-cozy-demo' ); ?></span>
                                <span><?php esc_html_e( 'Prompt builder for faster tagging', 'ssc-cozy-demo' ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ssc-section" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-section-heading">
                <span class="ssc-kicker"><?php esc_html_e( 'Why Pro exists', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'Free gets the experience live. Pro helps you improve it.', 'ssc-cozy-demo' ); ?></h2>
            </div>
            <div class="ssc-pro-grid">
                <article class="ssc-card ssc-pro-card">
                    <span class="ssc-card-label"><?php esc_html_e( 'Understand behavior', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'See what people search, click, and miss.', 'ssc-cozy-demo' ); ?></h3>
                    <div class="ssc-panel-chip-list">
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Search to click rate', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Top zero-result queries', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Chip CTR', 'ssc-cozy-demo' ); ?></span>
                    </div>
                </article>
                <article class="ssc-card ssc-pro-card">
                    <span class="ssc-card-label"><?php esc_html_e( 'Fit your content', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'Use custom fields, more chips, and better search data.', 'ssc-cozy-demo' ); ?></h3>
                    <div class="ssc-panel-chip-list">
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Custom field mapping', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Relevanssi support', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Higher chip limits', 'ssc-cozy-demo' ); ?></span>
                    </div>
                </article>
                <article class="ssc-card ssc-pro-card">
                    <span class="ssc-card-label"><?php esc_html_e( 'Move faster', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'Style the chips and speed up tagging work.', 'ssc-cozy-demo' ); ?></h3>
                    <div class="ssc-panel-chip-list">
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Advanced design controls', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'AI prompt builder', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'CSV export', 'ssc-cozy-demo' ); ?></span>
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
                    <h2><?php esc_html_e( 'Moss & Clay exists to prove the plugin in a catalog where overlap actually matters.', 'ssc-cozy-demo' ); ?></h2>
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
