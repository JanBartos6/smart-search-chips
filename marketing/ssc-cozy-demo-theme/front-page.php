<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$mugs_term = get_category_by_slug( 'mugs' );
$mugs_url  = $mugs_term ? get_category_link( $mugs_term ) : home_url( '/category/mugs/' );
$checkout_url    = sschp_demo_get_checkout_url();
$free_plugin_url = sschp_demo_get_free_plugin_url();
?>
<main class="ssc-home ssc-home--landing">

    <!-- ========== HERO: Full-viewport cinematic intro ========== -->
    <section class="ssc-hero-full" data-reveal>
        <div class="ssc-shell ssc-hero-full__inner">
            <div class="ssc-hero-full__copy">
                <span class="ssc-kicker"><?php esc_html_e( 'WordPress plugin', 'ssc-cozy-demo' ); ?></span>
                <h1><?php esc_html_e( 'Search once. Narrow with a click.', 'ssc-cozy-demo' ); ?></h1>
                <p class="ssc-hero-full__lede">
                    <?php esc_html_e( 'Smart Search Chips adds clickable filter chips to your search results. Visitors find what they want without rewriting their query.', 'ssc-cozy-demo' ); ?>
                </p>
                <div class="ssc-button-row">
                    <a class="ssc-button ssc-button--solid ssc-button--lg" href="<?php echo esc_url( home_url( '/search/' ) ); ?>"><?php esc_html_e( 'Try it live', 'ssc-cozy-demo' ); ?></a>
                    <a class="ssc-button ssc-button--ghost ssc-button--lg" href="<?php echo esc_url( $free_plugin_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get free plugin', 'ssc-cozy-demo' ); ?></a>
                    <a class="ssc-button ssc-button--ghost ssc-button--lg" href="#before-after"><?php esc_html_e( 'See the difference', 'ssc-cozy-demo' ); ?></a>
                    <a class="ssc-button ssc-button--solid ssc-button--lg" href="<?php echo esc_url( $checkout_url ); ?>" data-ssc-checkout><?php esc_html_e( 'Buy Pro', 'ssc-cozy-demo' ); ?></a>
                </div>
            </div>

            <div class="ssc-hero-full__demo" data-demo-autoload="tea">
                <div class="ssc-hero-orb ssc-hero-orb--one"></div>
                <div class="ssc-hero-orb ssc-hero-orb--two"></div>

                <div class="ssc-hero-card-live">
                    <div class="ssc-hero-card-live__top">
                        <span class="ssc-live-dot"></span>
                        <span><?php esc_html_e( 'Live demo', 'ssc-cozy-demo' ); ?></span>
                    </div>
                    <div class="ssc-hero-card__search">
                        <?php echo do_shortcode( '[sschp_search placeholder="Try searching: tea, stoneware, serving..."]' ); ?>
                    </div>
                    <div class="ssc-demo-pill-row">
                        <button class="ssc-demo-pill" type="button" data-demo-query="tea"><?php esc_html_e( 'tea', 'ssc-cozy-demo' ); ?></button>
                        <button class="ssc-demo-pill" type="button" data-demo-query="stoneware"><?php esc_html_e( 'stoneware', 'ssc-cozy-demo' ); ?></button>
                        <button class="ssc-demo-pill" type="button" data-demo-query="hosting"><?php esc_html_e( 'hosting', 'ssc-cozy-demo' ); ?></button>
                        <button class="ssc-demo-pill" type="button" data-demo-query="neutral"><?php esc_html_e( 'neutral', 'ssc-cozy-demo' ); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="ssc-hero-full__scroll" data-scroll-cue>
            <span><?php esc_html_e( 'Scroll to explore', 'ssc-cozy-demo' ); ?></span>
            <svg width="16" height="24" viewBox="0 0 16 24" fill="none"><rect x="1" y="1" width="14" height="22" rx="7" stroke="currentColor" stroke-width="1.5"/><circle class="ssc-scroll-dot" cx="8" cy="7" r="2" fill="currentColor"/></svg>
        </div>
    </section>

    <!-- ========== BEFORE / AFTER: Instant value prop ========== -->
    <section id="before-after" class="ssc-contrast" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-contrast__header">
                <h2><?php esc_html_e( 'The difference is immediate.', 'ssc-cozy-demo' ); ?></h2>
                <p><?php esc_html_e( 'Default WordPress search gives a flat list. Smart Search Chips gives visitors a way to narrow it.', 'ssc-cozy-demo' ); ?></p>
            </div>
            <div class="ssc-contrast__grid">
                <div class="ssc-contrast__card ssc-contrast__card--before">
                    <span class="ssc-contrast__label"><?php esc_html_e( 'Without the plugin', 'ssc-cozy-demo' ); ?></span>
                    <div class="ssc-ui-panel ssc-ui-panel--muted">
                        <div class="ssc-ui-searchbar"><?php esc_html_e( 'tea', 'ssc-cozy-demo' ); ?></div>
                        <div class="ssc-ui-results">
                            <span><?php esc_html_e( 'Cedar Teapot', 'ssc-cozy-demo' ); ?></span>
                            <span><?php esc_html_e( 'Moss Matcha Bowl', 'ssc-cozy-demo' ); ?></span>
                            <span><?php esc_html_e( 'Linen Tea Canister', 'ssc-cozy-demo' ); ?></span>
                            <span><?php esc_html_e( 'Ember Tasting Cups', 'ssc-cozy-demo' ); ?></span>
                            <span><?php esc_html_e( 'Hearth Speckle Mug', 'ssc-cozy-demo' ); ?></span>
                        </div>
                        <div class="ssc-contrast__verdict"><?php esc_html_e( '5 results. Now what?', 'ssc-cozy-demo' ); ?></div>
                    </div>
                </div>
                <div class="ssc-contrast__card ssc-contrast__card--after">
                    <span class="ssc-contrast__label ssc-contrast__label--accent"><?php esc_html_e( 'With Smart Search Chips', 'ssc-cozy-demo' ); ?></span>
                    <div class="ssc-ui-panel">
                        <div class="ssc-ui-searchbar"><?php esc_html_e( 'tea', 'ssc-cozy-demo' ); ?></div>
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
                            <span><?php esc_html_e( 'Ember Tasting Cups', 'ssc-cozy-demo' ); ?></span>
                            <span><?php esc_html_e( 'Hearth Speckle Mug', 'ssc-cozy-demo' ); ?></span>
                        </div>
                        <div class="ssc-contrast__verdict ssc-contrast__verdict--good"><?php esc_html_e( 'Click a chip to narrow instantly.', 'ssc-cozy-demo' ); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== WORKS EVERYWHERE: Three contexts ========== -->
    <section class="ssc-contexts" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-contexts__header">
                <span class="ssc-kicker"><?php esc_html_e( 'Works everywhere you need it', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'Three surfaces. One plugin.', 'ssc-cozy-demo' ); ?></h2>
            </div>
            <div class="ssc-contexts__grid">

                <article class="ssc-context-card" data-reveal>
                    <div class="ssc-context-card__visual">
                        <div class="ssc-ui-panel">
                            <div class="ssc-ui-searchbar"><?php esc_html_e( 'tea', 'ssc-cozy-demo' ); ?></div>
                            <div class="ssc-ui-chiprow">
                                <span><?php esc_html_e( 'stoneware', 'ssc-cozy-demo' ); ?></span>
                                <span><?php esc_html_e( 'tea ritual', 'ssc-cozy-demo' ); ?></span>
                                <span><?php esc_html_e( 'hosting', 'ssc-cozy-demo' ); ?></span>
                            </div>
                            <div class="ssc-ui-results">
                                <span><?php esc_html_e( 'Cedar Teapot', 'ssc-cozy-demo' ); ?></span>
                                <span><?php esc_html_e( 'Moss Matcha Bowl', 'ssc-cozy-demo' ); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="ssc-context-card__body">
                        <h3><?php esc_html_e( 'Live search', 'ssc-cozy-demo' ); ?></h3>
                        <p><?php esc_html_e( 'Chips appear as the visitor types. Results narrow with each click.', 'ssc-cozy-demo' ); ?></p>
                        <a class="ssc-context-link" href="<?php echo esc_url( home_url( '/search/' ) ); ?>"><?php esc_html_e( 'Try the demo', 'ssc-cozy-demo' ); ?> &rarr;</a>
                    </div>
                </article>

                <article class="ssc-context-card" data-reveal>
                    <div class="ssc-context-card__visual">
                        <div class="ssc-ui-panel">
                            <div class="ssc-ui-browserbar">/?s=tea&amp;sschp_tags=stoneware</div>
                            <div class="ssc-ui-chiprow ssc-ui-chiprow--active">
                                <span class="is-active"><?php esc_html_e( 'stoneware', 'ssc-cozy-demo' ); ?></span>
                                <span><?php esc_html_e( 'tea ritual', 'ssc-cozy-demo' ); ?></span>
                                <span><?php esc_html_e( 'hosting', 'ssc-cozy-demo' ); ?></span>
                            </div>
                            <div class="ssc-ui-count"><?php esc_html_e( '3 matching results', 'ssc-cozy-demo' ); ?></div>
                            <div class="ssc-ui-results">
                                <span><?php esc_html_e( 'Cedar Teapot', 'ssc-cozy-demo' ); ?></span>
                                <span><?php esc_html_e( 'Ember Tasting Cups', 'ssc-cozy-demo' ); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="ssc-context-card__body">
                        <h3><?php esc_html_e( 'Search results page', 'ssc-cozy-demo' ); ?></h3>
                        <p><?php esc_html_e( 'Active chips stay visible. URLs stay shareable. The back button works.', 'ssc-cozy-demo' ); ?></p>
                        <a class="ssc-context-link" href="<?php echo esc_url( home_url( '/?s=tea' ) ); ?>"><?php esc_html_e( 'See filtered results', 'ssc-cozy-demo' ); ?> &rarr;</a>
                    </div>
                </article>

                <article class="ssc-context-card" data-reveal>
                    <div class="ssc-context-card__visual">
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
                            </div>
                        </div>
                    </div>
                    <div class="ssc-context-card__body">
                        <h3><?php esc_html_e( 'Category pages', 'ssc-cozy-demo' ); ?></h3>
                        <p><?php esc_html_e( 'Turn flat archive pages into guided browsing. Great for shops and catalogs.', 'ssc-cozy-demo' ); ?></p>
                        <a class="ssc-context-link" href="<?php echo esc_url( $mugs_url ); ?>"><?php esc_html_e( 'Browse mugs', 'ssc-cozy-demo' ); ?> &rarr;</a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- ========== HOW IT WORKS: Simple 3-step ========== -->
    <section class="ssc-how" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-how__header">
                <span class="ssc-kicker"><?php esc_html_e( 'How it works', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'Install. Tag. Done.', 'ssc-cozy-demo' ); ?></h2>
            </div>
            <div class="ssc-how__grid">
                <div class="ssc-how__step" data-reveal>
                    <div class="ssc-how__number">1</div>
                    <h3><?php esc_html_e( 'Install the plugin', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'Activate it like any other WordPress plugin. No code needed.', 'ssc-cozy-demo' ); ?></p>
                </div>
                <div class="ssc-how__step" data-reveal>
                    <div class="ssc-how__number">2</div>
                    <h3><?php esc_html_e( 'Add chips to your content', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'Use the built-in chip editor on each post or page. Or pull from WooCommerce attributes.', 'ssc-cozy-demo' ); ?></p>
                </div>
                <div class="ssc-how__step" data-reveal>
                    <div class="ssc-how__number">3</div>
                    <h3><?php esc_html_e( 'Visitors search and narrow', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'Relevant chips appear automatically in search results. Visitors click to filter.', 'ssc-cozy-demo' ); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== PRO: Visual feature showcase ========== -->
    <section id="pro" class="ssc-pro-section" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-pro-section__header">
                <span class="ssc-kicker"><?php esc_html_e( 'Go further with Pro', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'See what visitors search. Shape what they find.', 'ssc-cozy-demo' ); ?></h2>
                <p><?php esc_html_e( 'Upgrade when you need analytics, custom data sources, numeric range chips, and team workflows.', 'ssc-cozy-demo' ); ?></p>
                <div class="ssc-button-row ssc-button-row--center">
                    <a class="ssc-button ssc-button--solid ssc-button--lg" href="<?php echo esc_url( $checkout_url ); ?>" data-ssc-checkout><?php esc_html_e( 'Buy Smart Search Chips Pro', 'ssc-cozy-demo' ); ?></a>
                </div>
            </div>

            <!-- Analytics visual -->
            <div class="ssc-pro-showcase" data-reveal>
                <div class="ssc-pro-showcase__copy">
                    <div class="ssc-pro-showcase__icon">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><path d="M4 22V10l5-3 5 3v12M14 22V14l5-3 5 3v8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <h3><?php esc_html_e( 'Analytics dashboard', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'See which searches lead to clicks, which chips get used most, and which queries return nothing.', 'ssc-cozy-demo' ); ?></p>
                </div>
                <div class="ssc-pro-showcase__visual">
                    <div class="ssc-ui-panel ssc-ui-panel--pro">
                        <div class="ssc-ui-kpi-grid">
                            <div class="ssc-mini-stat"><strong>62%</strong><span><?php esc_html_e( 'Search to click', 'ssc-cozy-demo' ); ?></span></div>
                            <div class="ssc-mini-stat"><strong>tea</strong><span><?php esc_html_e( 'Top query', 'ssc-cozy-demo' ); ?></span></div>
                            <div class="ssc-mini-stat"><strong>3</strong><span><?php esc_html_e( 'Zero-result trends', 'ssc-cozy-demo' ); ?></span></div>
                        </div>
                        <div class="ssc-ui-analytics-list">
                            <span><?php esc_html_e( 'Chip CTR: stoneware 28% | tea ritual 22% | hosting 18%', 'ssc-cozy-demo' ); ?></span>
                            <span><?php esc_html_e( 'Top zero-result: "ceramic diffuser" - consider adding', 'ssc-cozy-demo' ); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Catalog rules visual -->
            <div class="ssc-pro-showcase ssc-pro-showcase--reverse" data-reveal>
                <div class="ssc-pro-showcase__copy">
                    <div class="ssc-pro-showcase__icon">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><circle cx="14" cy="14" r="10" stroke="currentColor" stroke-width="1.8"/><path d="M14 8v6l4 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <h3><?php esc_html_e( 'Synonyms and manual curation', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'Normalize messy catalog language, pin important chips, hide noisy tags, and rename labels without editing every product.', 'ssc-cozy-demo' ); ?></p>
                </div>
                <div class="ssc-pro-showcase__visual">
                    <div class="ssc-ui-panel ssc-ui-panel--pro">
                        <div class="ssc-ui-section-title"><?php esc_html_e( 'Catalog rules', 'ssc-cozy-demo' ); ?></div>
                        <div class="ssc-design-demo">
                            <div class="ssc-design-demo__row">
                                <span class="ssc-design-chip ssc-design-chip--round"><?php esc_html_e( 'Sofa = couch', 'ssc-cozy-demo' ); ?></span>
                                <span class="ssc-design-chip ssc-design-chip--square"><?php esc_html_e( 'Pin: bestseller', 'ssc-cozy-demo' ); ?></span>
                                <span class="ssc-design-chip ssc-design-chip--outline"><?php esc_html_e( 'Hide: internal', 'ssc-cozy-demo' ); ?></span>
                                <span class="ssc-design-chip ssc-design-chip--filled"><?php esc_html_e( 'eco => Eco friendly', 'ssc-cozy-demo' ); ?></span>
                            </div>
                        </div>
                        <div class="ssc-admin-row">
                            <span class="ssc-admin-label"><?php esc_html_e( 'Rules', 'ssc-cozy-demo' ); ?></span>
                            <div class="ssc-admin-sidebar">
                                <span><?php esc_html_e( 'Synonyms', 'ssc-cozy-demo' ); ?></span>
                                <span><?php esc_html_e( 'Pinned chips', 'ssc-cozy-demo' ); ?></span>
                                <span><?php esc_html_e( 'Hidden chips', 'ssc-cozy-demo' ); ?></span>
                                <span><?php esc_html_e( 'Label overrides', 'ssc-cozy-demo' ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Remaining Pro features grid -->
            <div class="ssc-pro-extras" data-reveal>
                <div class="ssc-pro-feature" data-reveal>
                    <div class="ssc-pro-feature__icon">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><path d="M7 14h14M14 7v14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><rect x="4" y="4" width="20" height="20" rx="4" stroke="currentColor" stroke-width="1.8"/></svg>
                    </div>
                    <h3><?php esc_html_e( 'Custom field mapping', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'Pull chip data from any custom field, ACF group, or WooCommerce attribute.', 'ssc-cozy-demo' ); ?></p>
                </div>
                <div class="ssc-pro-feature" data-reveal>
                    <div class="ssc-pro-feature__icon">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><path d="M6 20l4-8 4 5 4-10 4 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <h3><?php esc_html_e( 'Numeric range chips', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'Group values like 100 W, 200 W, and 500 W into one range slider chip with min and max controls.', 'ssc-cozy-demo' ); ?></p>
                </div>
                <div class="ssc-pro-feature" data-reveal>
                    <div class="ssc-pro-feature__icon">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><path d="M8 6h12a2 2 0 012 2v12a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2z" stroke="currentColor" stroke-width="1.8"/><path d="M10 12l3 3 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <h3><?php esc_html_e( 'AI prompt workflows', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'Generate structured prompts for external AI tools so teams can review and fill chip fields faster.', 'ssc-cozy-demo' ); ?></p>
                </div>
                <div class="ssc-pro-feature" data-reveal>
                    <div class="ssc-pro-feature__icon">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><path d="M14 6v16M6 14h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </div>
                    <h3><?php esc_html_e( 'Commercial support', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'Priority help for stores and content teams that depend on guided search conversion.', 'ssc-cozy-demo' ); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== ADMIN: Visual of the editor ========== -->
    <section class="ssc-admin-preview" data-reveal>
        <div class="ssc-shell ssc-admin-preview__grid">
            <div class="ssc-admin-preview__copy">
                <span class="ssc-kicker"><?php esc_html_e( 'Simple to manage', 'ssc-cozy-demo' ); ?></span>
                <h2><?php esc_html_e( 'You control what chips appear.', 'ssc-cozy-demo' ); ?></h2>
                <p><?php esc_html_e( 'Add search chips right in the WordPress editor. Tag each post or product with the terms your visitors actually care about.', 'ssc-cozy-demo' ); ?></p>
                <div class="ssc-panel-chip-list">
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Built-in editor', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'WooCommerce aware', 'ssc-cozy-demo' ); ?></span>
                    <span class="ssc-soft-chip"><?php esc_html_e( 'Custom fields in Pro', 'ssc-cozy-demo' ); ?></span>
                </div>
            </div>
            <div class="ssc-admin-preview__visual">
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
                            <span class="ssc-admin-sidebar__pro"><?php esc_html_e( 'Custom field mapping', 'ssc-cozy-demo' ); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========== TIERS: Free vs Pro comparison ========== -->
    <section class="ssc-tiers" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-tiers__header">
                <h2><?php esc_html_e( 'Start free. Upgrade when you need more.', 'ssc-cozy-demo' ); ?></h2>
            </div>
            <div class="ssc-tier-grid">
                <article class="ssc-card ssc-tier-card">
                    <span class="ssc-card-label"><?php esc_html_e( 'Free', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'Everything you need to get started.', 'ssc-cozy-demo' ); ?></h3>
                    <ul class="ssc-tier-list">
                        <li><?php esc_html_e( 'Live search chips', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'Search results page filtering', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'Category and archive filtering', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'Built-in chip editor', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'WooCommerce support', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'Relevanssi-compatible search scoring', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'Chip color and style controls', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'No account or payment required', 'ssc-cozy-demo' ); ?></li>
                    </ul>
                    <a class="ssc-button ssc-button--ghost" href="<?php echo esc_url( $free_plugin_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get free on WordPress.org', 'ssc-cozy-demo' ); ?></a>
                </article>
                <article class="ssc-card ssc-tier-card ssc-tier-card--pro">
                    <span class="ssc-card-label"><?php esc_html_e( 'Pro', 'ssc-cozy-demo' ); ?></span>
                    <h3><?php esc_html_e( 'Deeper insight. More control.', 'ssc-cozy-demo' ); ?></h3>
                    <ul class="ssc-tier-list">
                        <li class="ssc-tier-list__highlight"><?php esc_html_e( 'Everything in Free, plus:', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'Analytics dashboard', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'ACF and custom field connectors', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'Numeric range slider chips', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'Synonym rules and manual chip curation', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'Authenticated REST access for chip rules', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'AI-assisted tag workflows', 'ssc-cozy-demo' ); ?></li>
                        <li><?php esc_html_e( 'Commercial support', 'ssc-cozy-demo' ); ?></li>
                    </ul>
                    <p class="ssc-tier-price"><?php esc_html_e( '$49 per year for one site.', 'ssc-cozy-demo' ); ?></p>
                    <a class="ssc-button ssc-button--solid" href="<?php echo esc_url( $checkout_url ); ?>" data-ssc-checkout><?php esc_html_e( 'Buy Pro', 'ssc-cozy-demo' ); ?></a>
                </article>
            </div>
        </div>
    </section>

</main>
<?php
get_footer();
