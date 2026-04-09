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
<main class="ssc-home ssc-home--landing">
    <section class="ssc-landing-hero" data-reveal>
        <div class="ssc-shell ssc-landing-hero__grid">
            <div class="ssc-landing-copy">
                <span class="ssc-kicker"><?php esc_html_e( 'Search that feels guided', 'ssc-cozy-demo' ); ?></span>
                <h1><?php esc_html_e( 'Give WordPress search a clear next step.', 'ssc-cozy-demo' ); ?></h1>
                <p class="ssc-landing-copy__lede">
                    <?php esc_html_e( 'Smart Search Chips turns search results into clickable paths. Visitors search once, then narrow with chips instead of starting over.', 'ssc-cozy-demo' ); ?>
                </p>
                <div class="ssc-button-row">
                    <a class="ssc-button ssc-button--solid" href="<?php echo esc_url( home_url( '/search/' ) ); ?>"><?php esc_html_e( 'Try the live demo', 'ssc-cozy-demo' ); ?></a>
                    <a class="ssc-button ssc-button--ghost" href="#walkthrough"><?php esc_html_e( 'See how it works', 'ssc-cozy-demo' ); ?></a>
                </div>
                <div class="ssc-proof-pills">
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Live search', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Search results pages', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Category filtering', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Free + Pro', 'ssc-cozy-demo' ); ?></span>
                </div>
            </div>

            <div class="ssc-landing-stage" data-demo-autoload="tea">
                <div class="ssc-stage-glow ssc-stage-glow--one"></div>
                <div class="ssc-stage-glow ssc-stage-glow--two"></div>
                <div class="ssc-stage-chip ssc-stage-chip--one"><?php esc_html_e( 'stoneware', 'ssc-cozy-demo' ); ?></div>
                <div class="ssc-stage-chip ssc-stage-chip--two"><?php esc_html_e( 'tea ritual', 'ssc-cozy-demo' ); ?></div>
                <div class="ssc-stage-chip ssc-stage-chip--three"><?php esc_html_e( 'hosting', 'ssc-cozy-demo' ); ?></div>
                <div class="ssc-stage-chip ssc-stage-chip--four"><?php esc_html_e( 'giftable', 'ssc-cozy-demo' ); ?></div>

                <div class="ssc-stage-window">
                    <div class="ssc-stage-window__top">
                        <span class="ssc-card-label"><?php esc_html_e( 'Live demo', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-stage-window__hint"><?php esc_html_e( 'Search the Moss & Clay catalog', 'ssc-cozy-demo' ); ?></span>
                    </div>
                    <h2><?php esc_html_e( 'Search the site and the filters appear.', 'ssc-cozy-demo' ); ?></h2>
                    <div class="ssc-hero-card__search">
                        <?php echo do_shortcode( '[ssc_search placeholder="Search tea, stoneware, serving, neutral, or giftable"]' ); ?>
                    </div>
                    <div class="ssc-demo-pill-row">
                        <button class="ssc-demo-pill" type="button" data-demo-query="tea"><?php esc_html_e( 'tea', 'ssc-cozy-demo' ); ?></button>
                        <button class="ssc-demo-pill" type="button" data-demo-query="stoneware"><?php esc_html_e( 'stoneware', 'ssc-cozy-demo' ); ?></button>
                        <button class="ssc-demo-pill" type="button" data-demo-query="hosting"><?php esc_html_e( 'hosting', 'ssc-cozy-demo' ); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="walkthrough" class="ssc-scene ssc-scene--soft" data-reveal>
        <div class="ssc-shell ssc-scene__grid">
            <div class="ssc-scene__copy">
                <span class="ssc-kicker"><?php esc_html_e( 'Step 1', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'A visitor searches once.', 'ssc-cozy-demo' ); ?></h2>
                <p><?php esc_html_e( 'Instead of hitting a plain list, they get useful filter chips pulled from the current results.', 'ssc-cozy-demo' ); ?></p>
                <div class="ssc-panel-chip-list">
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Broad search in', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Relevant chips out', 'ssc-cozy-demo' ); ?></span>
                </div>
            </div>
            <div class="ssc-scene__visual">
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
    </section>

    <section class="ssc-scene ssc-scene--warm" data-reveal>
        <div class="ssc-shell ssc-scene__grid ssc-scene__grid--reverse">
            <div class="ssc-scene__copy">
                <span class="ssc-kicker"><?php esc_html_e( 'Step 2', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'The same idea works on the results page.', 'ssc-cozy-demo' ); ?></h2>
                <p><?php esc_html_e( 'Active chips stay visible, the URL stays shareable, and visitors can keep narrowing without losing their place.', 'ssc-cozy-demo' ); ?></p>
                <div class="ssc-panel-chip-list">
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Active chips first', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Back-button friendly', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Shareable filter state', 'ssc-cozy-demo' ); ?></span>
                </div>
                <div class="ssc-panel-actions">
                    <a class="ssc-button ssc-button--solid" href="<?php echo esc_url( home_url( '/?s=tea' ) ); ?>"><?php esc_html_e( 'Open results-page demo', 'ssc-cozy-demo' ); ?></a>
                </div>
            </div>
            <div class="ssc-scene__visual">
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
    </section>

    <section class="ssc-scene ssc-scene--soft" data-reveal>
        <div class="ssc-shell ssc-scene__grid">
            <div class="ssc-scene__copy">
                <span class="ssc-kicker"><?php esc_html_e( 'Step 3', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'Category pages become easier to browse.', 'ssc-cozy-demo' ); ?></h2>
                <p><?php esc_html_e( 'When products overlap by material, style, function, or use case, chips turn flat category pages into guided browsing.', 'ssc-cozy-demo' ); ?></p>
                <div class="ssc-panel-chip-list">
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Category filtering', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Archive support', 'ssc-cozy-demo' ); ?></span>
                </div>
                <div class="ssc-panel-actions">
                    <a class="ssc-button ssc-button--solid" href="<?php echo esc_url( $mugs_url ); ?>"><?php esc_html_e( 'Open category demo', 'ssc-cozy-demo' ); ?></a>
                </div>
            </div>
            <div class="ssc-scene__visual">
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
    </section>

    <section class="ssc-scene ssc-scene--warm" data-reveal>
        <div class="ssc-shell ssc-scene__grid ssc-scene__grid--reverse">
            <div class="ssc-scene__copy">
                <span class="ssc-kicker"><?php esc_html_e( 'Step 4', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'You decide where the chips come from.', 'ssc-cozy-demo' ); ?></h2>
                <p><?php esc_html_e( 'Use the built-in chip field, WooCommerce product data, and in Pro, custom fields too.', 'ssc-cozy-demo' ); ?></p>
                <div class="ssc-panel-chip-list">
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Built-in editor', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'WooCommerce aware', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Custom fields in Pro', 'ssc-cozy-demo' ); ?></span>
                </div>
            </div>
            <div class="ssc-scene__visual">
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
    </section>

    <section class="ssc-scene ssc-scene--pro" data-reveal>
        <div class="ssc-shell ssc-scene__grid">
            <div class="ssc-scene__copy">
                <span class="ssc-kicker"><?php esc_html_e( 'Step 5', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'Pro helps you improve what visitors actually do.', 'ssc-cozy-demo' ); ?></h2>
                <p><?php esc_html_e( 'Track searches, chip clicks, and zero-result trends. Style the chips. Use custom fields. Speed up tagging work.', 'ssc-cozy-demo' ); ?></p>
                <div class="ssc-panel-chip-list">
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Analytics dashboard', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Advanced design controls', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'AI prompt builder', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Relevanssi integration', 'ssc-cozy-demo' ); ?></span>
                </div>
            </div>
            <div class="ssc-scene__visual">
                <div class="ssc-ui-panel ssc-ui-panel--pro">
                    <div class="ssc-ui-kpi-grid">
                        <div class="ssc-mini-stat"><strong>62%</strong><span><?php esc_html_e( 'Search to click', 'ssc-cozy-demo' ); ?></span></div>
                        <div class="ssc-mini-stat"><strong>tea</strong><span><?php esc_html_e( 'Top query', 'ssc-cozy-demo' ); ?></span></div>
                        <div class="ssc-mini-stat"><strong>3</strong><span><?php esc_html_e( 'Zero-result trends', 'ssc-cozy-demo' ); ?></span></div>
                    </div>
                    <div class="ssc-ui-analytics-list">
                        <span><?php esc_html_e( 'Chip CTR: stoneware 28%', 'ssc-cozy-demo' ); ?></span>
                        <span><?php esc_html_e( 'Design controls: radius, size, spacing', 'ssc-cozy-demo' ); ?></span>
                        <span><?php esc_html_e( 'AI prompt builder + CSV export', 'ssc-cozy-demo' ); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ssc-section ssc-tier-band" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-section-heading">
                <span class="ssc-kicker"><?php esc_html_e( 'Free and Pro', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'Start with the experience. Upgrade for insight and control.', 'ssc-cozy-demo' ); ?></h2>
            </div>
            <div class="ssc-tier-grid">
                <article class="ssc-card ssc-tier-card">
                    <span class="ssc-card-label"><?php esc_html_e( 'Free', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'Get the product live on your site.', 'ssc-cozy-demo' ); ?></h3>
                    <div class="ssc-panel-chip-list">
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Live search chips', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Results-page filtering', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Category filtering', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Built-in chip field', 'ssc-cozy-demo' ); ?></span>
                    </div>
                </article>
                <article class="ssc-card ssc-tier-card ssc-tier-card--pro">
                    <span class="ssc-card-label"><?php esc_html_e( 'Pro', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'Learn what works and shape it to your content.', 'ssc-cozy-demo' ); ?></h3>
                    <div class="ssc-panel-chip-list">
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Analytics dashboard', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Custom field mapping', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'Advanced design', 'ssc-cozy-demo' ); ?></span>
                        <span class="ssc-soft-chip"><?php esc_html_e( 'AI prompt builder', 'ssc-cozy-demo' ); ?></span>
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
                    <h2><?php esc_html_e( 'Moss & Clay is here so the plugin proves itself in a catalog where overlap really matters.', 'ssc-cozy-demo' ); ?></h2>
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
