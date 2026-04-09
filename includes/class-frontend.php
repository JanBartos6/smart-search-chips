<?php
/**
 * Frontend - Enqueue scripts/styles and provide search form shortcode
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSC_Frontend {

    /** @var SSC_Settings */
    private $settings;

    /** @var SSC_Chip_Engine */
    private $engine;

    /** @var bool */
    private $archive_chips_rendered = false;

    /** @var array */
    private $loop_source_posts = array();

    public function __construct( SSC_Settings $settings, SSC_Chip_Engine $engine ) {
        $this->settings = $settings;
        $this->engine   = $engine;
    }

    /**
     * Initialize frontend hooks.
     */
    public function init() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_shortcode( 'ssc_search', array( $this, 'render_search_form' ) );
        add_filter( 'the_posts', array( $this, 'filter_archive_posts' ), 10, 2 );

        if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
            add_filter( 'render_block', array( $this, 'inject_archive_chips_into_query_block' ), 10, 2 );
        } else {
            add_action( 'loop_start', array( $this, 'render_archive_chips_before_loop' ) );
        }

        if ( class_exists( 'WooCommerce' ) ) {
            add_action( 'woocommerce_before_shop_loop', array( $this, 'render_woocommerce_loop_chips' ), 5 );
        }
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueue_assets() {
        $style_path  = SSC_PLUGIN_DIR . 'assets/css/chips.css';
        $script_path = SSC_PLUGIN_DIR . 'assets/js/chips.js';

        wp_enqueue_style(
            'smart-search-chips',
            SSC_PLUGIN_URL . 'assets/css/chips.css',
            array(),
            file_exists( $style_path ) ? filemtime( $style_path ) : SSC_VERSION
        );

        wp_enqueue_script(
            'smart-search-chips',
            SSC_PLUGIN_URL . 'assets/js/chips.js',
            array(),
            file_exists( $script_path ) ? filemtime( $script_path ) : SSC_VERSION,
            true
        );

        // Pass settings to JS
        wp_localize_script( 'smart-search-chips', 'sscConfig', array(
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'action'     => $this->get_ajax_action_name(),
            'minChars'   => 2,
            'postType'   => implode( ',', (array) $this->settings->get( 'post_types', array( 'post' ) ) ),
            'nonce'      => wp_create_nonce( 'ssc_search_nonce' ),
            'lang'       => SSC_Language::current_language(),
            'analytics'  => array(
                'enabled' => SSC_Analytics::is_enabled(),
                'nonce'   => wp_create_nonce( 'ssc_analytics_nonce' ),
            ),
        ) );
    }

    /**
     * Resolve the AJAX action name that the frontend should call.
     *
     * Free sites should always use the built-in handler even if an old Pro-only
     * override setting is still stored after a downgrade.
     *
     * @return string
     */
    private function get_ajax_action_name() {
        if ( SSC_License::can_use_ajax_override() ) {
            $ajax_action = $this->settings->get( 'override_ajax' );
            if ( ! empty( $ajax_action ) ) {
                return $ajax_action;
            }
        }

        return 'ssc_search';
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
        <div class="ssc-search <?php echo esc_attr( $atts['class'] ); ?>">
            <form class="ssc-search__form js-ssc-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <input type="search"
                       class="ssc-search__input"
                       name="s"
                       placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
                       autocomplete="off"
                       required />
                <button type="submit" class="ssc-search__button">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                </button>
            </form>
            <div class="ssc-search__results js-ssc-results"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Filter supported archive/search loops by active chip tags.
     *
     * @param array    $posts Posts for the current query.
     * @param WP_Query $query Query object.
     * @return array
     */
    public function filter_archive_posts( $posts, $query ) {
        if ( ! $this->is_supported_loop_query( $query ) ) {
            return $posts;
        }

        $query_hash                       = spl_object_hash( $query );
        $this->loop_source_posts[ $query_hash ] = $posts;

        $active_tags = $this->get_active_archive_tags();
        if ( empty( $active_tags ) ) {
            return $posts;
        }

        $posts_data   = $this->collect_posts_data( $posts );
        $filtered     = $this->engine->filter_by_tags( $posts_data, $active_tags );
        $filtered_ids = wp_list_pluck( $filtered, 'id' );

        if ( empty( $filtered_ids ) ) {
            $query->post_count    = 0;
            $query->found_posts   = 0;
            $query->max_num_pages = 0;
            $query->posts         = array();
            return array();
        }

        $filtered_posts = array_values( array_filter( $posts, function( $post ) use ( $filtered_ids ) {
            return in_array( (int) $post->ID, $filtered_ids, true );
        } ) );

        $query->post_count    = count( $filtered_posts );
        $query->found_posts   = count( $filtered_posts );
        $query->max_num_pages = 1;
        $query->posts         = $filtered_posts;

        return $filtered_posts;
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

        echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

        echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
     * Build the archive chips markup for a supported query.
     *
     * @param WP_Query $query Query object.
     * @return string
     */
    private function get_archive_chips_markup( $query ) {
        if ( $this->archive_chips_rendered || ! $this->is_supported_loop_query( $query ) ) {
            return '';
        }

        $query_hash   = spl_object_hash( $query );
        $source_posts = isset( $this->loop_source_posts[ $query_hash ] ) ? $this->loop_source_posts[ $query_hash ] : $query->posts;
        $posts_data   = $this->collect_posts_data( $query->posts );
        $active_tags  = $this->get_active_archive_tags();
        $max_chips    = min(
            (int) $this->settings->get( 'max_chips', 8 ),
            SSC_License::max_chips()
        );
        $max_length   = (int) $this->settings->get( 'max_tag_length', 40 );
        $chips        = $this->engine->generate_chips( $posts_data, $active_tags, $max_chips, $max_length );

        if ( empty( $chips ) && ! empty( $active_tags ) && ! empty( $source_posts ) ) {
            foreach ( $active_tags as $tag ) {
                $chips[ mb_strtolower( $tag ) ] = array(
                    'label' => $tag,
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
        echo '<div class="ssc-chips">';
        foreach ( $chips as $chip ) {
            $label     = $chip['label'];
            $is_active = in_array( $label, $active_tags, true );
            $styles    = $this->get_chip_style_values( $is_active );

            printf(
                '<a class="ssc-chip%s" href="%s" data-tag="%s" style="background:%s;color:%s;border:1px solid %s;border-radius:%spx;font-size:%spx;padding:%spx %spx;text-decoration:none;">%s (%d)</a>',
                $is_active ? ' ssc-chip--active' : '',
                esc_url( $this->build_archive_filter_url( $label, $active_tags ) ),
                esc_attr( $label ),
                esc_attr( $styles['background'] ),
                esc_attr( $styles['color'] ),
                esc_attr( $styles['border'] ),
                esc_attr( $styles['radius'] ),
                esc_attr( $styles['font_size'] ),
                esc_attr( $styles['pad_y'] ),
                esc_attr( $styles['pad_x'] ),
                esc_html( $label ),
                (int) $chip['count']
            );
        }
        echo '</div>';
        echo '</div>';

        return ob_get_clean();
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

            if ( SSC_License::can_use_custom_fields() ) {
                $tag_field   = $this->settings->get( 'tag_fields', '' );
                $extra_field = $this->settings->get( 'extra_fields', '' );

                if ( ! empty( $tag_field ) || ! empty( $extra_field ) ) {
                    $primary = $this->get_field_value( $post_id, $tag_field );
                    $extra   = $this->get_field_value( $post_id, $extra_field );

                    if ( '' === $primary && '' === $extra && empty( $tag_field ) ) {
                        $primary = get_post_meta( $post_id, SSC_Meta_Box::META_KEY, true ) ?: '';
                    }
                } else {
                    $primary = get_post_meta( $post_id, SSC_Meta_Box::META_KEY, true ) ?: '';
                    $extra   = '';
                }
            } else {
                $primary = get_post_meta( $post_id, SSC_Meta_Box::META_KEY, true ) ?: '';
                $extra   = '';
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

        if ( function_exists( 'get_field' ) ) {
            $value = get_field( $field_name, $post_id );
            if ( $value ) {
                return (string) $value;
            }
        }

        $value = get_post_meta( $post_id, $field_name, true );
        return $value ? (string) $value : '';
    }

    /**
     * Parse active archive chip filters from the URL.
     *
     * @return array
     */
    private function get_active_archive_tags() {
        $raw = isset( $_GET['ssc_tags'] ) ? wp_unslash( $_GET['ssc_tags'] ) : '';

        if ( is_array( $raw ) ) {
            $values = $raw;
        } else {
            $values = explode( ',', (string) $raw );
        }

        $tags = array();
        foreach ( $values as $value ) {
            $value = sanitize_text_field( trim( (string) $value ) );
            if ( '' !== $value && ! in_array( $value, $tags, true ) ) {
                $tags[] = $value;
            }
        }

        return $tags;
    }

    /**
     * Build a URL that toggles one chip on the current archive page.
     *
     * @param string|null $tag         Tag to toggle.
     * @param array       $active_tags Current active tags.
     * @return string
     */
    private function build_archive_filter_url( $tag, $active_tags ) {
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
        $url         = home_url( $request_uri );
        $next_tags   = array_values( $active_tags );

        if ( null !== $tag ) {
            if ( in_array( $tag, $next_tags, true ) ) {
                $next_tags = array_values( array_filter( $next_tags, function( $active_tag ) use ( $tag ) {
                    return $active_tag !== $tag;
                } ) );
            } else {
                $next_tags[] = $tag;
            }
        }

        $url = remove_query_arg( array( 'paged', 'product-page' ), $url );

        if ( empty( $next_tags ) ) {
            return remove_query_arg( 'ssc_tags', $url );
        }

        return add_query_arg( 'ssc_tags', implode( ',', $next_tags ), $url );
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

        return array(
            'background' => $is_active ? $color : $inactive_color,
            'color'      => $is_active ? $text_color : $inactive_text,
            'border'     => $color,
            'radius'     => SSC_License::can_use_advanced_design() ? $this->get_numeric_setting( 'chip_radius', 16, 0 ) : 16,
            'font_size'  => SSC_License::can_use_advanced_design() ? $this->get_numeric_setting( 'chip_font_size', 13, 8 ) : 13,
            'pad_x'      => SSC_License::can_use_advanced_design() ? $this->get_numeric_setting( 'chip_padding_x', 12, 0 ) : 12,
            'pad_y'      => SSC_License::can_use_advanced_design() ? $this->get_numeric_setting( 'chip_padding_y', 4, 0 ) : 4,
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
