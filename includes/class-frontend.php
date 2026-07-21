<?php
/**
 * Frontend - Enqueue scripts/styles and provide search form shortcode
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCHP_Frontend {

    /** @var SSCHP_Settings */
    private $settings;

    /** @var SSCHP_Chip_Engine */
    private $engine;

    /** @var bool */
    private $archive_chips_rendered = false;

    /** @var array */
    private $current_page_unfiltered_posts = array();

    /** @var array */
    private $archive_filtered_posts = array();

    public function __construct( SSCHP_Settings $settings, SSCHP_Chip_Engine $engine ) {
        $this->settings = $settings;
        $this->engine   = $engine;
    }

    /**
     * Initialize frontend hooks.
     */
    public function init() {
        add_action( 'init', array( $this, 'register_search_block' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_shortcode( 'sschp_search', array( $this, 'render_search_form' ) );
        add_shortcode( 'ssc_search', array( $this, 'render_search_form' ) );
        add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
        add_filter( 'the_posts', array( $this, 'filter_archive_posts' ), 10, 2 );
        add_action( 'loop_start', array( $this, 'render_archive_chips_before_loop' ) );
        add_action( 'wp_footer', array( $this, 'render_archive_chips_footer_fallback' ), 5 );

        if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
            add_filter( 'render_block', array( $this, 'inject_archive_chips_into_query_block' ), 10, 2 );
        }

        if ( class_exists( 'WooCommerce' ) ) {
            add_action( 'woocommerce_before_shop_loop', array( $this, 'render_woocommerce_loop_chips' ), 5 );
        }
    }

    /**
     * Register the guaranteed-search Gutenberg block.
     */
    public function register_search_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        $block_dir = SSCHP_PLUGIN_DIR . 'blocks/search';
        if ( ! file_exists( $block_dir . '/block.json' ) ) {
            return;
        }

        register_block_type(
            $block_dir,
            array(
                'render_callback' => array( $this, 'render_search_block' ),
            )
        );
    }

    /**
     * Render the guaranteed-search Gutenberg block.
     *
     * @param array $attributes Block attributes.
     * @return string HTML output.
     */
    public function render_search_block( $attributes = array() ) {
        $attributes  = is_array( $attributes ) ? $attributes : array();
        $placeholder = isset( $attributes['placeholder'] ) ? sanitize_text_field( (string) $attributes['placeholder'] ) : __( 'Search...', 'smart-search-chips' );
        $class       = isset( $attributes['className'] ) ? sanitize_text_field( (string) $attributes['className'] ) : '';

        return $this->render_search_form(
            array(
                'placeholder' => '' !== $placeholder ? $placeholder : __( 'Search...', 'smart-search-chips' ),
                'class'       => $class,
            )
        );
    }

    /**
     * Register public query vars used by archive chip links.
     *
     * @param array $vars Existing query vars.
     * @return array
     */
    public function register_query_vars( $vars ) {
        $vars[] = 'sschp_tags';

        return $vars;
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueue_assets() {
        $style_path  = SSCHP_PLUGIN_DIR . 'assets/css/chips.css';
        $script_path = SSCHP_PLUGIN_DIR . 'assets/js/chips.js';

        wp_enqueue_style(
            'smart-search-chips',
            SSCHP_PLUGIN_URL . 'assets/css/chips.css',
            array(),
            file_exists( $style_path ) ? filemtime( $style_path ) : SSCHP_VERSION
        );

        $image_fit         = $this->settings->get( 'result_image_fit', '' );
        $image_display_px  = absint( $this->settings->get( 'result_image_display_size', 0 ) );
        $image_declarations = array();

        if ( in_array( $image_fit, array( 'contain', 'cover' ), true ) ) {
            $image_declarations[] = '--ssc-result-image-fit:' . $image_fit;
        }

        if ( $image_display_px > 0 ) {
            $image_declarations[] = '--ssc-result-image-size:' . $image_display_px . 'px';
            $image_declarations[] = '--ssc-result-image-padding:4px';
        }

        if ( ! empty( $image_declarations ) ) {
            wp_add_inline_style(
                'smart-search-chips',
                ':root{' . esc_html( implode( ';', $image_declarations ) ) . ';}'
            );
        }

        $analytics_enabled = false;
        $analytics_nonce   = '';
        if ( SSCHP_License::is_pro() && class_exists( 'SSCHP_Analytics' ) ) {
            $analytics_enabled = SSCHP_Analytics::is_enabled();
            $analytics_nonce   = wp_create_nonce( 'sschp_analytics_nonce' );
        }

        $dependencies = array();
        $analytics_script_path = SSCHP_PLUGIN_DIR . 'assets/js/analytics__premium_only.js';
        if ( $analytics_enabled && file_exists( $analytics_script_path ) ) {
            wp_enqueue_script(
                'smart-search-chips-analytics',
                SSCHP_PLUGIN_URL . 'assets/js/analytics__premium_only.js',
                array(),
                filemtime( $analytics_script_path ),
                true
            );
            $dependencies[] = 'smart-search-chips-analytics';
        }

        wp_enqueue_script(
            'smart-search-chips',
            SSCHP_PLUGIN_URL . 'assets/js/chips.js',
            $dependencies,
            file_exists( $script_path ) ? filemtime( $script_path ) : SSCHP_VERSION,
            true
        );

        // Pass settings to JS
        wp_localize_script( 'smart-search-chips', 'sschpConfig', array(
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'action'        => $this->get_ajax_action_name(),
            'themeAction'   => $this->get_theme_ajax_action_name(),
            'keywordField'  => $this->get_ajax_keyword_field_name(),
            'inputSelector' => $this->get_search_input_selector(),
            'resultSelector' => $this->get_result_container_selector(),
            'minChars'      => 2,
            'debounceMs'    => $this->get_debounce_ms(),
            'postType'      => implode( ',', (array) $this->settings->get( 'post_types', array( 'post' ) ) ),
            'productCategory' => $this->get_current_product_category_slug(),
            'nonce'         => wp_create_nonce( 'sschp_search_nonce' ),
            'lang'          => SSCHP_Language::current_language(),
            'isAdmin'       => current_user_can( 'manage_options' ),
            'analytics'     => array(
                'enabled' => $analytics_enabled,
                'nonce'   => $analytics_nonce,
            ),
        ) );
    }

    /**
     * Resolve the guaranteed AJAX action used by the shortcode.
     *
     * @return string
     */
    private function get_ajax_action_name() {
        return 'sschp_search';
    }

    /**
     * Resolve the optional theme AJAX action used for enhancement mode.
     *
     * @return string
     */
    private function get_theme_ajax_action_name() {
        $ajax_action = sanitize_key( $this->settings->get( 'override_ajax', '' ) );

        return 'sschp_search' !== $ajax_action ? $ajax_action : '';
    }

    /**
     * Resolve the request field that contains the search term.
     *
     * @return string
     */
    private function get_ajax_keyword_field_name() {
        $field = sanitize_key( $this->settings->get( 'ajax_keyword_field', 'keyword' ) );
        if ( '' !== $field ) {
            return $field;
        }

        return 'keyword';
    }

    /**
     * Resolve the frontend selector used to find a search input.
     *
     * @return string
     */
    private function get_search_input_selector() {
        $default  = '.ssc-search__input, input[type="search"], input[name="keyword"], input[name="s"], input[name="query"], input[name="search"]';
        $selector = sanitize_text_field( $this->settings->get( 'search_input_selector', '' ) );

        return '' !== $selector ? $selector : $default;
    }

    /**
     * Resolve the frontend selector that receives AJAX results.
     *
     * @return string
     */
    private function get_result_container_selector() {
        $default = '.js-ssc-results, .js-search__results, .ssc-search__results';
        $selector = sanitize_text_field( $this->settings->get( 'result_container_selector', '' ) );

        if ( '' !== $selector ) {
            return $selector . ', ' . $default;
        }

        return $default;
    }

    /**
     * Return the configured live-search debounce delay.
     *
     * @return int
     */
    private function get_debounce_ms() {
        $value = (int) $this->settings->get( 'debounce_ms', 300 );

        return max( 50, min( 2000, $value ) );
    }

    /**
     * Return the current product category slug when available.
     *
     * @return string
     */
    private function get_current_product_category_slug() {
        if ( ! function_exists( 'get_queried_object' ) ) {
            return '';
        }

        $queried = get_queried_object();
        if (
            ! $queried
            || ! isset( $queried->taxonomy, $queried->slug )
            || ! $this->is_product_category_taxonomy( $queried->taxonomy )
        ) {
            return '';
        }

        return sanitize_title( $queried->slug );
    }

    /**
     * Render search form shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_search_form( $atts = array() ) {
        $atts = shortcode_atts( array(
            'placeholder' => __( 'Search...', 'smart-search-chips' ),
            'class'       => '',
        ), $atts );

        ob_start();
        ?>
        <div class="ssc-search <?php echo esc_attr( $atts['class'] ); ?>" data-sschp-mode="guaranteed">
            <form class="ssc-search__form js-ssc-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <input type="search"
                       class="ssc-search__input"
                       name="s"
                       placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
                       autocomplete="off"
                       required />
                <button type="submit" class="ssc-search__button" aria-label="<?php echo esc_attr__( 'Search', 'smart-search-chips' ); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                </button>
            </form>
            <div class="ssc-search__results js-ssc-results" aria-live="polite" aria-busy="false"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Filter supported archive/search loops by active chip tags.
     *
     * Product category chips compare the complete category. Other archive types
     * retain current-page filtering to avoid unexpectedly expensive site-wide
     * queries.
     *
     * @param array    $posts Posts for the current query.
     * @param WP_Query $query Query object.
     * @return array
     */
    public function filter_archive_posts( $posts, $query ) {
        if ( ! $this->is_supported_loop_query( $query ) ) {
            return $posts;
        }

        $query_hash  = spl_object_hash( $query );
        $scope_posts = $this->is_product_category_query( $query )
            ? $this->get_full_product_category_posts( $query, $posts )
            : $posts;

        $this->current_page_unfiltered_posts[ $query_hash ] = $scope_posts;

        $active_tags = $this->get_active_archive_tags();
        if ( empty( $active_tags ) ) {
            $this->archive_filtered_posts[ $query_hash ] = $scope_posts;
            return $posts;
        }

        $posts_data   = $this->collect_posts_data( $scope_posts );
        $filtered     = $this->engine->filter_by_tags( $posts_data, $active_tags );
        $filtered_ids = wp_list_pluck( $filtered, 'id' );

        if ( empty( $filtered_ids ) ) {
            // Current-page filtering means pagination from the original query
            // no longer applies once a chip filter is active.
            $query->post_count    = 0;
            $query->found_posts   = 0;
            $query->max_num_pages = 0;
            $query->posts         = array();
            $this->archive_filtered_posts[ $query_hash ] = array();
            return array();
        }

        $filtered_posts = array_values( array_filter( $scope_posts, function( $post ) use ( $filtered_ids ) {
            return in_array( (int) $post->ID, $filtered_ids, true );
        } ) );

        // Expose the filtered current-page count and prevent stale pagination
        // links from implying that the full result set was filtered.
        $query->post_count    = count( $filtered_posts );
        $query->found_posts   = count( $filtered_posts );
        $query->max_num_pages = 1;
        $query->posts         = $filtered_posts;
        $this->archive_filtered_posts[ $query_hash ] = $filtered_posts;

        return $filtered_posts;
    }

    /**
     * Load every product in the current category while preserving its taxonomy
     * and language constraints.
     *
     * @param WP_Query $query          Main archive query.
     * @param array    $fallback_posts Current page posts.
     * @return array
     */
    private function get_full_product_category_posts( $query, $fallback_posts ) {
        if ( ! isset( $query->query_vars ) || ! is_array( $query->query_vars ) ) {
            return $fallback_posts;
        }

        $query_args = $query->query_vars;
        unset(
            $query_args['paged'],
            $query_args['page'],
            $query_args['offset'],
            $query_args['posts_per_archive_page'],
            $query_args['sschp_tags'],
            $query_args['fields']
        );

        $query_args['posts_per_page']  = -1;
        $query_args['numberposts']      = -1;
        $query_args['nopaging']         = true;
        $query_args['no_found_rows']    = true;
        $query_args['suppress_filters'] = false;

        $query_args = apply_filters( 'sschp_product_category_scope_query_args', $query_args, $query );
        $all_posts  = get_posts( $query_args );

        if ( ! is_array( $all_posts ) || ( empty( $all_posts ) && ! empty( $fallback_posts ) ) ) {
            return $fallback_posts;
        }

        return array_values( $all_posts );
    }

    /**
     * Render chips above the supported loop.
     *
     * @param WP_Query $query Query object.
     */
    public function render_archive_chips_before_loop( $query ) {
        $markup = $this->get_archive_chips_markup( $query );
        if ( '' === $markup ) {
            return;
        }

        echo wp_kses( $markup, $this->get_archive_chips_allowed_html() );
    }

    /**
     * Render chips above WooCommerce shop loops when available.
     */
    public function render_woocommerce_loop_chips() {
        global $wp_query;

        if ( ! $wp_query instanceof WP_Query ) {
            return;
        }

        $markup = $this->get_archive_chips_markup( $wp_query );
        if ( '' === $markup ) {
            return;
        }

        echo wp_kses( $markup, $this->get_archive_chips_allowed_html() );
    }

    /**
     * Provide archive chips for custom themes that render products without the
     * standard WordPress or WooCommerce loop hooks. The frontend script moves
     * this hidden fallback directly above the product list.
     */
    public function render_archive_chips_footer_fallback() {
        global $wp_query;

        if ( $this->archive_chips_rendered || ! $wp_query instanceof WP_Query ) {
            return;
        }

        $markup = $this->get_archive_chips_markup( $wp_query );
        if ( '' === $markup ) {
            return;
        }

        echo '<div class="ssc-loop-filters-fallback" data-sschp-archive-fallback hidden>';
        echo wp_kses( $markup, $this->get_archive_chips_allowed_html() );
        echo '</div>';
    }

    /**
     * Inject chips into block theme query output.
     *
     * @param string $block_content Rendered block HTML.
     * @param array  $block         Parsed block data.
     * @return string
     */
    public function inject_archive_chips_into_query_block( $block_content, $block ) {
        if ( $this->archive_chips_rendered || is_admin() || empty( $block['blockName'] ) || 'core/query' !== $block['blockName'] ) {
            return $block_content;
        }

        if ( empty( $block['attrs']['query']['inherit'] ) ) {
            return $block_content;
        }

        global $wp_query;
        if ( ! $wp_query instanceof WP_Query ) {
            return $block_content;
        }

        $markup = $this->get_archive_chips_markup( $wp_query );
        if ( '' === $markup ) {
            return $block_content;
        }

        return $markup . $block_content;
    }

    /**
     * Check whether this query is a supported context for loop chips.
     *
     * @param WP_Query $query Query object.
     * @return bool
     */
    private function is_supported_loop_query( $query ) {
        if ( is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() ) {
            return false;
        }

        if ( $query->is_feed() || $query->is_singular() ) {
            return false;
        }

        return $query->is_search() || $query->is_post_type_archive() || $query->is_tax() || $query->is_category() || $query->is_tag();
    }

    /**
     * Check whether a query is for a hierarchical product taxonomy.
     *
     * @param WP_Query $query Query object.
     * @return bool
     */
    private function is_product_category_query( $query ) {
        if ( ! $query instanceof WP_Query || ! $query->is_tax() ) {
            return false;
        }

        $taxonomy = method_exists( $query, 'get' ) ? sanitize_key( (string) $query->get( 'taxonomy' ) ) : '';
        if ( '' === $taxonomy && function_exists( 'get_queried_object' ) ) {
            $queried = get_queried_object();
            $taxonomy = $queried && isset( $queried->taxonomy ) ? sanitize_key( (string) $queried->taxonomy ) : '';
        }

        return $this->is_product_category_taxonomy( $taxonomy );
    }

    /**
     * Recognize WooCommerce and custom hierarchical product taxonomies.
     *
     * @param string $taxonomy Taxonomy name.
     * @return bool
     */
    private function is_product_category_taxonomy( $taxonomy ) {
        $taxonomy = sanitize_key( (string) $taxonomy );
        if ( 'product_cat' === $taxonomy ) {
            return true;
        }

        if ( '' === $taxonomy || ! function_exists( 'get_taxonomy' ) ) {
            return false;
        }

        $taxonomy_object = get_taxonomy( $taxonomy );
        return $taxonomy_object
            && ! empty( $taxonomy_object->hierarchical )
            && in_array( 'product', (array) $taxonomy_object->object_type, true );
    }

    /**
     * Build the archive chips markup for a supported query.
     *
     * @param WP_Query $query Query object.
     * @return string
     */
    private function get_archive_chips_markup( $query ) {
        if ( $this->archive_chips_rendered || ! $this->is_supported_loop_query( $query ) ) {
            return '';
        }

        $query_hash = spl_object_hash( $query );
        $unfiltered_scope_posts = isset( $this->current_page_unfiltered_posts[ $query_hash ] )
            ? $this->current_page_unfiltered_posts[ $query_hash ]
            : $query->posts;
        $filtered_scope_posts = isset( $this->archive_filtered_posts[ $query_hash ] )
            ? $this->archive_filtered_posts[ $query_hash ]
            : $query->posts;
        $posts_data  = $this->collect_posts_data( $filtered_scope_posts );
        $active_tags = $this->get_active_archive_tags();
        $max_chips   = min( (int) $this->settings->get( 'max_chips', 8 ), SSCHP_License::max_chips() );
        $max_length  = (int) $this->settings->get( 'max_tag_length', 40 );
        $chips       = $this->engine->generate_chips( $posts_data, $active_tags, $max_chips, $max_length );

        if ( empty( $chips ) && ! empty( $active_tags ) && ! empty( $unfiltered_scope_posts ) ) {
            foreach ( $active_tags as $tag ) {
                $chips[ mb_strtolower( $tag ) ] = array(
                    'label' => $tag,
                    'value' => $tag,
                    'count' => 0,
                );
            }
        }

        if ( empty( $chips ) && empty( $active_tags ) ) {
            return '';
        }

        $this->archive_chips_rendered = true;

        $title = __( 'Filter These Results', 'smart-search-chips' );
        if ( function_exists( 'is_search' ) && is_search() ) {
            $title = __( 'Refine This Search', 'smart-search-chips' );
        }

        ob_start();
        echo '<div class="ssc-loop-filters">';
        echo '<div class="ssc-loop-filters__header">';
        echo '<strong class="ssc-loop-filters__title">' . esc_html( $title ) . '</strong>';
        if ( ! empty( $active_tags ) ) {
            echo '<a class="ssc-loop-filters__clear" href="' . esc_url( $this->build_archive_filter_url( null, array() ) ) . '">' . esc_html__( 'Clear filters', 'smart-search-chips' ) . '</a>';
        }
        echo '</div>';
        if ( $this->is_product_category_query( $query ) ) {
            echo '<p class="ssc-loop-filters__description">' . esc_html__( 'These chips compare and filter all products in this category.', 'smart-search-chips' ) . '</p>';
        } else {
            echo '<p class="ssc-loop-filters__description">' . esc_html__( 'These chips refine the results currently loaded on this page. Changing filters resets pagination.', 'smart-search-chips' ) . '</p>';
        }
        echo '<div class="ssc-chips">';
        foreach ( $chips as $chip ) {
            $label     = $chip['label'];
            $value     = isset( $chip['value'] ) ? $chip['value'] : $label;
            $is_active = $this->engine->is_tag_active( $value, $active_tags );
            $styles    = $this->get_chip_style_values( $is_active );
            $url       = $this->build_archive_filter_url( $value, $active_tags );
            $count     = (int) $chip['count'];

            if ( $is_active ) {
                /* translators: 1: chip label, 2: matching result count. */
                $aria_label = sprintf(
                    _n( 'Remove filter %1$s, %2$d result', 'Remove filter %1$s, %2$d results', $count, 'smart-search-chips' ),
                    $label,
                    $count
                );
            } else {
                /* translators: 1: chip label, 2: matching result count. */
                $aria_label = sprintf(
                    _n( 'Filter by %1$s, %2$d result', 'Filter by %1$s, %2$d results', $count, 'smart-search-chips' ),
                    $label,
                    $count
                );
            }

            $custom_html = apply_filters(
                'sschp_render_chip_html',
                '',
                $chip,
                $active_tags,
                array(
                    'context' => 'archive',
                    'styles'  => $styles,
                    'url'     => $url,
                )
            );

            if ( is_string( $custom_html ) && '' !== $custom_html ) {
                echo wp_kses( $custom_html, $this->get_archive_chips_allowed_html() );
                continue;
            }

            printf(
                '<a class="ssc-chip%s" href="%s" data-tag="%s"%s aria-label="%s" style="background:%s;color:%s;border:1px solid %s;border-radius:%spx;font-size:%spx;padding:%spx %spx;text-decoration:none;"><span class="ssc-chip__label">%s</span><span class="ssc-chip__count" data-ssc-count-to="%d">%d</span></a>',
                $is_active ? ' ssc-chip--active' : '',
                esc_url( $url ),
                esc_attr( $value ),
                $is_active ? ' aria-current="true"' : '',
                esc_attr( $aria_label ),
                esc_attr( $styles['background'] ),
                esc_attr( $styles['color'] ),
                esc_attr( $styles['border'] ),
                esc_attr( $styles['radius'] ),
                esc_attr( $styles['font_size'] ),
                esc_attr( $styles['pad_y'] ),
                esc_attr( $styles['pad_x'] ),
                esc_html( $label ),
                $count,
                $count
            );
        }
        echo '</div>';
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Allowed markup for internally generated archive chips.
     *
     * @return array
     */
    private function get_archive_chips_allowed_html() {
        $allowed = array(
            'div'    => array(
                'class'                 => true,
                'style'                 => true,
                'data-range-context'    => true,
                'data-range-group-key'  => true,
                'data-range-unit-key'   => true,
                'data-range-prefix'     => true,
                'data-range-bounds-min' => true,
                'data-range-bounds-max' => true,
                'data-range-value'      => true,
                'data-range-label'      => true,
                'aria-live'             => true,
            ),
            'strong' => array(
                'class' => true,
            ),
            'p'      => array(
                'class' => true,
            ),
            'a'      => array(
                'class'        => true,
                'href'         => true,
                'data-tag'     => true,
                'aria-current' => true,
                'aria-label'   => true,
                'style'        => true,
            ),
            'span'   => array(
                'class'             => true,
                'data-range-role'   => true,
                'data-ssc-count-to' => true,
                'aria-live'         => true,
            ),
            'input'  => array(
                'class'           => true,
                'type'            => true,
                'min'             => true,
                'max'             => true,
                'step'            => true,
                'value'           => true,
                'data-range-role' => true,
                'aria-label'      => true,
            ),
            'button' => array(
                'class' => true,
                'type'  => true,
            ),
        );

        return apply_filters( 'sschp_archive_chips_allowed_html', $allowed );
    }

    /**
     * Collect tags for a set of posts.
     *
     * @param WP_Post[] $posts Posts to analyze.
     * @return array
     */
    private function collect_posts_data( $posts ) {
        $posts_data = array();

        foreach ( (array) $posts as $post ) {
            if ( empty( $post ) || empty( $post->ID ) ) {
                continue;
            }

            $post_id = (int) $post->ID;

            $primary = get_post_meta( $post_id, SSCHP_Meta_Box::META_KEY, true ) ?: '';
            $extra   = '';

            if ( SSCHP_License::is_pro() ) {
                $tag_field   = $this->settings->get( 'tag_fields', '' );
                $extra_field = $this->settings->get( 'extra_fields', '' );

                if ( ! empty( $tag_field ) || ! empty( $extra_field ) ) {
                    $primary = $this->get_field_value( $post_id, $tag_field );
                    $extra   = $this->get_field_value( $post_id, $extra_field );

                    if ( '' === $primary && '' === $extra && empty( $tag_field ) ) {
                        $primary = get_post_meta( $post_id, SSCHP_Meta_Box::META_KEY, true ) ?: '';
                    }
                }
            }

            $tag_source = apply_filters(
                'sschp_post_tag_source',
                array(
                    'primary' => $primary,
                    'extra'   => $extra,
                ),
                $post_id,
                array(
                    'context' => 'archive',
                )
            );

            if ( is_array( $tag_source ) ) {
                $primary = isset( $tag_source['primary'] ) ? (string) $tag_source['primary'] : '';
                $extra   = isset( $tag_source['extra'] ) ? (string) $tag_source['extra'] : '';
            }

            $tag_data = $this->engine->extract_tags( $primary, $extra );

            $posts_data[] = array(
                'id'         => $post_id,
                'tags'       => $tag_data['tags'],
                'tags_lower' => $tag_data['tags_lower'],
            );
        }

        return $posts_data;
    }

    /**
     * Read a field value using ACF when available.
     *
     * @param int    $post_id    Post ID.
     * @param string $field_name Meta key.
     * @return string
     */
    private function get_field_value( $post_id, $field_name ) {
        if ( empty( $field_name ) ) {
            return '';
        }

        $context = array(
            'post_id'    => (int) $post_id,
            'field_name' => (string) $field_name,
            'context'    => 'archive',
            'source'     => 'post_meta',
        );

        if ( function_exists( 'get_field' ) ) {
            $value = get_field( $field_name, $post_id );
            $normalized = SSCHP_Field_Normalizer::normalize(
                $value,
                array_merge(
                    $context,
                    array(
                        'source' => 'acf',
                    )
                )
            );

            if ( '' !== $normalized ) {
                return $normalized;
            }
        }

        $value = get_post_meta( $post_id, $field_name, true );
        return SSCHP_Field_Normalizer::normalize( $value, $context );
    }

    /**
     * Parse active archive chip filters from the URL.
     *
     * @return array
     */
    private function get_active_archive_tags() {
        $raw = get_query_var( 'sschp_tags', '' );

        if ( is_array( $raw ) ) {
            $values = map_deep( $raw, 'sanitize_text_field' );
        } else {
            $values = explode( ',', sanitize_text_field( (string) $raw ) );
        }

        $tags = array();
        $seen = array();
        foreach ( $values as $value ) {
            $value = sanitize_text_field( trim( (string) $value ) );
            $key   = mb_strtolower( $value );
            if ( '' !== $value && ! isset( $seen[ $key ] ) ) {
                $seen[ $key ] = true;
                $tags[]       = $value;
            }
        }

        return apply_filters( 'sschp_active_tags', $tags );
    }

    /**
     * Build a URL that toggles one chip on the current archive page.
     *
     * @param string|null $tag         Tag to toggle.
     * @param array       $active_tags Current active tags.
     * @return string
     */
    private function build_archive_filter_url( $tag, $active_tags ) {
        $url       = home_url( add_query_arg( array() ) );
        $next_tags = array_values( $active_tags );

        if ( null !== $tag ) {
            if ( $this->engine->is_tag_active( $tag, $next_tags ) ) {
                $next_tags = array_values( array_filter( $next_tags, function( $active_tag ) use ( $tag ) {
                    return ! $this->engine->is_tag_active( $tag, array( $active_tag ) );
                } ) );
            } else {
                $next_tags[] = $tag;
            }
        }

        $url = remove_query_arg( array( 'paged', 'product-page' ), $url );

        if ( empty( $next_tags ) ) {
            return remove_query_arg( 'sschp_tags', $url );
        }

        return add_query_arg( 'sschp_tags', implode( ',', $next_tags ), $url );
    }

    /**
     * Resolve the current chip design settings.
     *
     * @param bool $is_active Whether the chip is active.
     * @return array
     */
    private function get_chip_style_values( $is_active ) {
        $color          = $this->get_color_setting( 'chip_color', '#e74c3c' );
        $text_color     = $this->get_color_setting( 'chip_text_color', '#ffffff' );
        $inactive_color = $this->get_color_setting( 'chip_inactive_color', '#ffffff' );
        $inactive_text  = $this->get_color_setting( 'chip_inactive_text_color', '#333333' );

        $radius    = $this->get_numeric_setting( 'chip_radius', 16, 0 );
        $font_size = $this->get_numeric_setting( 'chip_font_size', 13, 8 );
        $pad_x     = $this->get_numeric_setting( 'chip_padding_x', 12, 0 );
        $pad_y     = $this->get_numeric_setting( 'chip_padding_y', 4, 0 );

        return array(
            'background'        => $is_active ? $color : $inactive_color,
            'color'             => $is_active ? $text_color : $inactive_text,
            'border'            => $color,
            'active_background' => $color,
            'active_color'      => $text_color,
            'radius'            => $radius,
            'font_size'         => $font_size,
            'pad_x'             => $pad_x,
            'pad_y'             => $pad_y,
        );
    }

    /**
     * Read a numeric design setting with a fallback.
     *
     * @param string $key     Setting key.
     * @param int    $default Default value.
     * @param int    $min     Minimum value.
     * @return int
     */
    private function get_numeric_setting( $key, $default, $min = 0 ) {
        $value = $this->settings->get( $key, '' );

        if ( '' === $value || ! is_numeric( $value ) ) {
            return (int) $default;
        }

        return max( (int) $min, (int) $value );
    }

    /**
     * Read a color setting with a fallback.
     *
     * @param string $key     Setting key.
     * @param string $default Default color.
     * @return string
     */
    private function get_color_setting( $key, $default ) {
        $value = sanitize_hex_color( $this->settings->get( $key, '' ) );
        return $value ? $value : $default;
    }
}
