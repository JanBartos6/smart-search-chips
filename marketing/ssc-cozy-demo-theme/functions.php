<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ssc_demo_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support(
        'html5',
        array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        )
    );
}
add_action( 'after_setup_theme', 'ssc_demo_theme_setup' );

function ssc_demo_theme_enqueue_assets() {
    wp_enqueue_style(
        'ssc-demo-fonts',
        'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap',
        array(),
        null
    );

    $style_path  = get_theme_file_path( 'assets/css/cozy-demo.css' );
    $script_path = get_theme_file_path( 'assets/js/cozy-demo.js' );
    $is_landing  = is_front_page() || is_home();

    wp_enqueue_style(
        'ssc-demo-theme',
        get_theme_file_uri( 'assets/css/cozy-demo.css' ),
        array( 'ssc-demo-fonts' ),
        file_exists( $style_path ) ? filemtime( $style_path ) : null
    );

    $script_dependencies = array();

    if ( $is_landing ) {
        wp_enqueue_script(
            'ssc-demo-freemius-checkout',
            'https://checkout.freemius.com/js/v1/',
            array(),
            null,
            true
        );

        $script_dependencies[] = 'ssc-demo-freemius-checkout';
    }

    wp_enqueue_script(
        'ssc-demo-theme',
        get_theme_file_uri( 'assets/js/cozy-demo.js' ),
        $script_dependencies,
        file_exists( $script_path ) ? filemtime( $script_path ) : null,
        true
    );

    if ( $is_landing ) {
        wp_localize_script(
            'ssc-demo-theme',
            'sscDemoCheckout',
            array(
                'productId'   => '27046',
                'planId'      => '45154',
                'publicKey'   => 'pk_69b2a1db30add56864a42e4ea9929',
                'image'       => 'https://smartsearchchips.app/wp-content/uploads/2026/04/ssc-favicon-300x300.png',
                'name'        => 'Smart Search Chips',
                'licenses'    => 1,
                'checkoutUrl' => ssc_demo_get_checkout_url(),
            )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'ssc_demo_theme_enqueue_assets' );

function ssc_demo_get_checkout_url() {
    return 'https://checkout.freemius.com/plugin/27046/plan/45154/';
}

function ssc_demo_get_free_plugin_url() {
    return 'https://wordpress.org/plugins/smart-search-chips/';
}

function ssc_demo_get_product_tags( $post_id, $limit = 4 ) {
    $raw = get_post_meta( $post_id, 'ssc_tags', true );
    if ( empty( $raw ) ) {
        return array();
    }

    $parts = preg_split( '/[;,]/', (string) $raw );
    $tags  = array();

    foreach ( $parts as $part ) {
        $tag = trim( wp_strip_all_tags( $part ) );
        if ( '' === $tag ) {
            continue;
        }

        $key = mb_strtolower( $tag );
        if ( isset( $tags[ $key ] ) ) {
            continue;
        }

        $tags[ $key ] = $tag;
    }

    if ( $limit > 0 ) {
        return array_slice( array_values( $tags ), 0, $limit );
    }

    return array_values( $tags );
}

function ssc_demo_get_collection_terms( $post_id ) {
    $terms = get_the_terms( $post_id, 'category' );
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return array();
    }

    return array_values(
        array_filter(
            $terms,
            function ( $term ) {
                return ! in_array( $term->slug, array( 'catalog', 'uncategorized' ), true );
            }
        )
    );
}

function ssc_demo_get_collection_label( $post_id ) {
    $terms = ssc_demo_get_collection_terms( $post_id );
    if ( empty( $terms ) ) {
        return __( 'Catalog Piece', 'ssc-cozy-demo' );
    }

    return $terms[0]->name;
}

function ssc_demo_render_product_card( $post_id ) {
    $tags       = ssc_demo_get_product_tags( $post_id, 4 );
    $collection = ssc_demo_get_collection_label( $post_id );
    ?>
    <article class="ssc-card ssc-product-card" data-reveal>
        <div class="ssc-product-card__meta">
            <span class="ssc-product-card__collection"><?php echo esc_html( $collection ); ?></span>
            <span class="ssc-product-card__dot"></span>
            <span class="ssc-product-card__id"><?php echo esc_html( get_the_date( 'M j', $post_id ) ); ?></span>
        </div>
        <h3 class="ssc-product-card__title">
            <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
                <?php echo esc_html( get_the_title( $post_id ) ); ?>
            </a>
        </h3>
        <p class="ssc-product-card__excerpt">
            <?php echo esc_html( wp_trim_words( get_post_field( 'post_content', $post_id ), 20 ) ); ?>
        </p>
        <?php if ( ! empty( $tags ) ) : ?>
            <div class="ssc-product-card__tags">
                <?php foreach ( $tags as $tag ) : ?>
                    <span class="ssc-soft-chip"><?php echo esc_html( $tag ); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
    <?php
}

function ssc_demo_render_native_search_form( $value = '' ) {
    ?>
    <form class="ssc-inline-search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <input
            class="ssc-inline-search__input"
            type="search"
            name="s"
            value="<?php echo esc_attr( $value ); ?>"
            placeholder="<?php esc_attr_e( 'Try tea, mug, glaze, serving, or giftable', 'ssc-cozy-demo' ); ?>"
            required
        />
        <button class="ssc-button ssc-button--solid" type="submit"><?php esc_html_e( 'Search', 'ssc-cozy-demo' ); ?></button>
    </form>
    <?php
}
