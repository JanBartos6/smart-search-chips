<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<main class="ssc-page">
    <section class="ssc-page-hero" data-reveal>
        <div class="ssc-shell ssc-page-hero__grid">
            <div>
                <span class="ssc-kicker"><?php esc_html_e( 'Live AJAX demo', 'ssc-cozy-demo' ); ?></span>
                <h1><?php esc_html_e( 'Search like a customer who only knows the feeling they are after.', 'ssc-cozy-demo' ); ?></h1>
                <p class="ssc-page-hero__lede">
                    <?php esc_html_e( 'This page shows the shortcode-based live search flow. Start with a broad term and then let the generated chips narrow the result set without a page reload.', 'ssc-cozy-demo' ); ?>
                </p>
                <div class="ssc-demo-pill-row ssc-demo-pill-row--links">
                    <button class="ssc-demo-pill" type="button" data-demo-query="tea"><?php esc_html_e( 'tea', 'ssc-cozy-demo' ); ?></button>
                    <button class="ssc-demo-pill" type="button" data-demo-query="stoneware"><?php esc_html_e( 'stoneware', 'ssc-cozy-demo' ); ?></button>
                    <button class="ssc-demo-pill" type="button" data-demo-query="hosting"><?php esc_html_e( 'hosting', 'ssc-cozy-demo' ); ?></button>
                    <button class="ssc-demo-pill" type="button" data-demo-query="coffee ritual"><?php esc_html_e( 'coffee ritual', 'ssc-cozy-demo' ); ?></button>
                </div>
            </div>
            <aside class="ssc-card ssc-side-note">
                <span class="ssc-card-label"><?php esc_html_e( 'Best viewed with intent-based search', 'ssc-cozy-demo' ); ?></span>
                <p><?php esc_html_e( 'The catalog is structured to overlap by ritual, material, and mood. That means the strongest searches are not necessarily product titles. Try tea, serving, glaze, neutral, or handmade.', 'ssc-cozy-demo' ); ?></p>
            </aside>
        </div>
    </section>

    <section class="ssc-section ssc-live-demo-section" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-live-demo-shell">
                <?php echo do_shortcode( '[ssc_search placeholder="Search mug, tea, glaze, handmade, or serving"]' ); ?>
            </div>
        </div>
    </section>

    <section class="ssc-section" data-reveal>
        <div class="ssc-shell">
            <div class="ssc-feature-grid">
                <article class="ssc-card ssc-feature-card">
                    <h3><?php esc_html_e( 'Live as you type', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'The result list and the chip set update together, so the interface feels conversational instead of mechanical.', 'ssc-cozy-demo' ); ?></p>
                </article>
                <article class="ssc-card ssc-feature-card">
                    <h3><?php esc_html_e( 'AND logic on chips', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'Choose one chip, then another, and the result set gets tighter without a heavy sidebar or a wall of checkboxes.', 'ssc-cozy-demo' ); ?></p>
                </article>
                <article class="ssc-card ssc-feature-card">
                    <h3><?php esc_html_e( 'Archive flow too', 'ssc-cozy-demo' ); ?></h3>
                    <p><?php esc_html_e( 'When you want shareable URLs and browser history support, the full search-results page keeps the same chip logic alive.', 'ssc-cozy-demo' ); ?></p>
                </article>
            </div>
        </div>
    </section>
</main>
<?php
get_footer();
