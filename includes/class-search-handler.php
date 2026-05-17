<?php
/**
 * Search Handler - AJAX endpoint for search with chips
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCHP_Search_Handler {

    /** @var SSCHP_Chip_Engine */
    private $engine;

    /** @var SSCHP_Settings */
    private $settings;

    public function __construct( SSCHP_Chip_Engine $engine, SSCHP_Settings $settings ) {
        $this->engine   = $engine;
        $this->settings = $settings;
    }

    /**
     * Register AJAX hooks.
     */
    public function init() {
        $ajax_action = sanitize_key( $this->settings->get( 'override_ajax', '' ) );
        if ( '' !== $ajax_action && 'sschp_search' !== $ajax_action ) {
            add_action( 'wp_ajax_' . $ajax_action, array( $this, 'handle_search' ), 0 );
            add_action( 'wp_ajax_nopriv_' . $ajax_action, array( $this, 'handle_search' ), 0 );
        }

        // Always register our own action
        add_action( 'wp_ajax_sschp_search', array( $this, 'handle_search' ) );
        add_action( 'wp_ajax_nopriv_sschp_search', array( $this, 'handle_search' ) );
    }

    /**
     * Handle the AJAX search request.
     */
    public function handle_search() {
        if ( ! $this->is_sschp_managed_request() ) {
            return;
        }

        $this->verify_search_nonce_or_fail();

        $keyword          = $this->get_search_keyword();
        $active_tags      = $this->normalize_active_tags( $this->get_active_tags_request_input() );
        $language_request = array(
            'sschp_lang' => isset( $_POST['sschp_lang'] ) ? sanitize_key( wp_unslash( $_POST['sschp_lang'] ) ) : '',
            'lang'       => isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( $_POST['lang'] ) ) : '',
        );

        if ( empty( $keyword ) || strlen( $keyword ) < 2 ) {
            wp_die();
        }

        $language = SSCHP_Language::request_language( $language_request );
        SSCHP_Language::switch_ajax_language( $language );

        $post_types     = $this->normalize_post_types( $this->settings->get( 'post_types', array( 'post' ) ) );
        $max_results    = (int) $this->settings->get( 'search_results_count', 50 );
        $threshold_pct  = (float) $this->settings->get( 'relevance_threshold', 0.3 );
        $max_tag_length = (int) $this->settings->get( 'max_tag_length', 40 );
        $display_count  = (int) $this->settings->get( 'max_results', 10 );

        $max_chips = min( (int) $this->settings->get( 'max_chips', 8 ), SSCHP_License::max_chips() );

        // Run search query. If Relevanssi is active, explicitly hand the query
        // to Relevanssi so theme AJAX overrides keep the same relevance order.
        $query_args = array(
            's'              => $keyword,
            'post_type'      => $post_types,
            'posts_per_page' => $max_results,
            'post_status'    => 'publish',
            'relevanssi'     => true,
            'suppress_filters' => false,
        );

        $query_args = apply_filters( 'sschp_search_query_args', $query_args, $keyword, $active_tags, $language );
        $query      = new WP_Query( SSCHP_Language::apply_query_language( $query_args, $language ) );

        if ( function_exists( 'relevanssi_do_query' ) ) {
            relevanssi_do_query( $query );
        }

        if ( ! $query->have_posts() ) {
            header( 'X-SSC-Results-Count: 0' );
            echo '<p class="ssc-empty">' . esc_html( apply_filters( 'sschp_no_results_text', __( 'No results found.', 'smart-search-chips' ) ) ) . '</p>';
            wp_die();
        }

        // Collect posts with relevance scores
        $top_score    = 0;
        $scored_posts = array();

        while ( $query->have_posts() ) {
            $query->the_post();
            $score = isset( $GLOBALS['post']->relevance_score ) ? floatval( $GLOBALS['post']->relevance_score ) : 0;
            if ( $score > $top_score ) {
                $top_score = $score;
            }
            $scored_posts[] = array(
                'post'  => $GLOBALS['post'],
                'score' => $score,
            );
        }
        wp_reset_postdata();

        $use_relevance = $top_score > 0;
        if ( $use_relevance ) {
            $threshold      = $top_score * $threshold_pct;
            $relevant_posts = array_filter( $scored_posts, function( $p ) use ( $threshold ) {
                return $p['score'] >= $threshold;
            } );
            $relevant_posts = array_values( $relevant_posts );
        } else {
            $relevant_posts = $scored_posts;
        }

        // Build posts data with tags.
        $posts_data = $this->collect_posts_data( $relevant_posts );

        // Filter by active tags
        if ( ! empty( $active_tags ) ) {
            $posts_data = $this->engine->filter_by_tags( $posts_data, $active_tags );
        }

        $total = count( $posts_data );
        header( 'X-SSC-Results-Count: ' . (int) $total );

        // Generate chips
        $chips = $this->engine->generate_chips( $posts_data, $active_tags, $max_chips, $max_tag_length );

        // Allow themes/plugins to modify chips
        $chips = apply_filters( 'sschp_chips', $chips, $posts_data, $active_tags );
        header( 'X-SSC-Tagged-Posts: ' . (int) $this->count_tagged_posts( $posts_data ) );
        header( 'X-SSC-Chips-Count: ' . (int) count( $chips ) );

        // Render
        $this->render_chips( $chips, $active_tags );
        $this->render_results( $posts_data, $display_count );

        wp_die();
    }

    /**
     * Determine whether the current AJAX request should be handled by SSC.
     *
     * The built-in endpoint is always SSC-managed and must fail closed. For
     * third-party override actions, only take over when our JS marked the
     * request, or when an SSC nonce is present from an older intercepted request.
     *
     * @return bool
     */
    private function is_sschp_managed_request() {
        if ( 'sschp_search' === $this->get_ajax_action_name_from_request() ) {
            return true;
        }

        return $this->has_sschp_request_field();
    }

    /**
     * Verify the AJAX nonce or stop the SSC-managed request.
     */
    private function verify_search_nonce_or_fail() {
        $nonce = $this->get_scalar_request_value( 'sschp_nonce' );

        if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'sschp_search_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'smart-search-chips' ) );
        }
    }

    /**
     * Read the current admin-ajax action from the request or WP hook context.
     *
     * @return string
     */
    private function get_ajax_action_name_from_request() {
        $action = sanitize_key( $this->get_scalar_request_value( 'action' ) );

        if ( '' !== $action ) {
            return $action;
        }

        if ( function_exists( 'current_filter' ) ) {
            $hook = (string) current_filter();

            if ( 0 === strpos( $hook, 'wp_ajax_nopriv_' ) ) {
                return sanitize_key( substr( $hook, strlen( 'wp_ajax_nopriv_' ) ) );
            }

            if ( 0 === strpos( $hook, 'wp_ajax_' ) ) {
                return sanitize_key( substr( $hook, strlen( 'wp_ajax_' ) ) );
            }
        }

        return 'sschp_search';
    }

    /**
     * Check for known SSC request fields without treating generic theme payload
     * fields as ours.
     *
     * @return bool
     */
    private function has_sschp_request_field() {
        return '' !== $this->get_scalar_request_value( 'sschp_managed' )
            || '' !== $this->get_scalar_request_value( 'sschp_nonce' )
            || '' !== $this->get_scalar_request_value( 'sschp_lang' );
    }

    /**
     * Read a scalar request value from POST or GET with early sanitization.
     *
     * @param string $key Request key.
     * @return string
     */
    private function get_scalar_request_value( $key ) {
        if ( isset( $_POST[ $key ] ) && ! is_array( $_POST[ $key ] ) ) {
            return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
        }

        $value = filter_input( INPUT_GET, $key, FILTER_UNSAFE_RAW );

        if ( null === $value || false === $value || is_array( $value ) ) {
            return '';
        }

        return sanitize_text_field( wp_unslash( $value ) );
    }

    /**
     * Normalize requested active tags into a clean array.
     *
     * Accepts the native array shape from FormData as well as defensive
     * fallbacks for malformed scalar or JSON-encoded payloads.
     *
     * @param mixed $raw_tags Raw request value.
     * @return array
     */
    private function normalize_active_tags( $raw_tags ) {
        if ( is_string( $raw_tags ) ) {
            $decoded = json_decode( $raw_tags, true );
            if ( is_array( $decoded ) ) {
                $raw_tags = $decoded;
            } elseif ( '' === trim( $raw_tags ) ) {
                $raw_tags = array();
            } else {
                $raw_tags = array( $raw_tags );
            }
        }

        if ( ! is_array( $raw_tags ) ) {
            return array();
        }

        $tags = array();
        array_walk_recursive( $raw_tags, function( $tag ) use ( &$tags ) {
            $tag = sanitize_text_field( (string) $tag );
            if ( '' !== $tag ) {
                $tags[] = $tag;
            }
        } );

        $normalized = array();
        $seen       = array();

        foreach ( $tags as $tag ) {
            $key = mb_strtolower( $tag );
            if ( isset( $seen[ $key ] ) ) {
                continue;
            }

            $seen[ $key ]  = true;
            $normalized[] = $tag;
        }

        return apply_filters( 'sschp_active_tags', array_values( $normalized ) );
    }

    /**
     * Read and sanitize the active_tags AJAX field.
     *
     * @return mixed Sanitized scalar or array.
     */
    private function get_active_tags_request_input() {
        $raw_tags = filter_input( INPUT_POST, 'active_tags', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

        if ( null === $raw_tags || false === $raw_tags ) {
            $raw_tags = filter_input( INPUT_POST, 'active_tags', FILTER_UNSAFE_RAW );
        }

        return $this->sanitize_active_tags_input( $raw_tags );
    }

    /**
     * Sanitize the active_tags request field before normalization.
     *
     * @param mixed $raw_tags Raw request field.
     * @return mixed Sanitized scalar or array.
     */
    private function sanitize_active_tags_input( $raw_tags ) {
        $unslashed = wp_unslash( $raw_tags );

        if ( is_array( $unslashed ) ) {
            return map_deep( $unslashed, 'sanitize_text_field' );
        }

        return sanitize_text_field( (string) $unslashed );
    }

    /**
     * Read the search keyword from the configured AJAX field.
     *
     * @return string
     */
    private function get_search_keyword() {
        $fields = array( 'keyword', 's', 'query', 'search' );

        $configured = sanitize_key( $this->settings->get( 'ajax_keyword_field', 'keyword' ) );
        if ( '' !== $configured ) {
            array_unshift( $fields, $configured );
        }

        $fields = apply_filters( 'sschp_ajax_keyword_fields', array_values( array_unique( array_filter( $fields ) ) ) );

        foreach ( (array) $fields as $field ) {
            $field = sanitize_key( $field );
            if ( '' === $field ) {
                continue;
            }

            $value = filter_input( INPUT_POST, $field, FILTER_UNSAFE_RAW );
            if ( null === $value || false === $value ) {
                $value = filter_input( INPUT_GET, $field, FILTER_UNSAFE_RAW );
            }

            $value = sanitize_text_field( wp_unslash( (string) $value ) );
            if ( '' !== $value ) {
                return $value;
            }
        }

        return '';
    }

    /**
     * Normalize stored post type settings before building a query.
     *
     * @param mixed $post_types Stored post type option.
     * @return array
     */
    private function normalize_post_types( $post_types ) {
        $post_types = is_array( $post_types ) ? $post_types : array( $post_types );
        $post_types = array_filter( array_map( 'sanitize_key', $post_types ) );
        $post_types = array_values( array_filter( $post_types, 'post_type_exists' ) );

        return ! empty( $post_types ) ? $post_types : array( 'post' );
    }

    /**
     * Collect post data with tags from relevant posts.
     *
     * @param array $relevant_posts Scored posts.
     * @return array Posts data with tags.
     */
    private function collect_posts_data( $relevant_posts ) {
        $posts_data = array();

        foreach ( $relevant_posts as $rp ) {
            $pid = $rp['post']->ID;

            // Default: use built-in sschp_tags field only
            $primary = get_post_meta( $pid, SSCHP_Meta_Box::META_KEY, true ) ?: '';
            $extra   = '';

            if ( SSCHP_License::is_pro() ) {
                // Pro: use configured custom fields when present, otherwise keep
                // the built-in fallback so sites do not lose chips just because
                // Pro is active before field mapping is configured.
                $tag_field   = $this->settings->get( 'tag_fields', '' );
                $extra_field = $this->settings->get( 'extra_fields', '' );

                if ( ! empty( $tag_field ) || ! empty( $extra_field ) ) {
                    $primary = $this->get_field_value( $pid, $tag_field );
                    $extra   = $this->get_field_value( $pid, $extra_field );

                    if ( '' === $primary && '' === $extra && empty( $tag_field ) ) {
                        $primary = get_post_meta( $pid, SSCHP_Meta_Box::META_KEY, true ) ?: '';
                    }
                }
            }

            $tag_source = apply_filters(
                'sschp_post_tag_source',
                array(
                    'primary' => $primary,
                    'extra'   => $extra,
                ),
                $pid,
                array(
                    'context' => 'ajax',
                )
            );

            if ( is_array( $tag_source ) ) {
                $primary = isset( $tag_source['primary'] ) ? (string) $tag_source['primary'] : '';
                $extra   = isset( $tag_source['extra'] ) ? (string) $tag_source['extra'] : '';
            }

            $tag_data = $this->engine->extract_tags( $primary, $extra );

            $posts_data[] = array(
                'id'         => $pid,
                'url'        => get_permalink( $pid ),
                'title'      => get_the_title( $pid ),
                'thumbnail'  => $this->get_result_thumbnail( $pid ),
                'score'      => isset( $rp['score'] ) ? (float) $rp['score'] : 0.0,
                'tags'       => $tag_data['tags'],
                'tags_lower' => $tag_data['tags_lower'],
            );
        }

        return $posts_data;
    }

    /**
     * Count posts that have at least one available chip tag.
     *
     * @param array $posts_data Posts data.
     * @return int
     */
    private function count_tagged_posts( $posts_data ) {
        $count = 0;

        foreach ( $posts_data as $post_data ) {
            if ( ! empty( $post_data['tags'] ) ) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get a field value from a post, supporting ACF and regular post meta.
     *
     * @param int    $post_id    Post ID.
     * @param string $field_name Field name.
     * @return string Field value.
     */
    private function get_field_value( $post_id, $field_name ) {
        if ( empty( $field_name ) ) {
            return '';
        }

        // Try ACF first
        if ( function_exists( 'get_field' ) ) {
            $value = get_field( $field_name, $post_id );
            if ( $value ) {
                return (string) $value;
            }
        }

        // Fall back to regular post meta
        $value = get_post_meta( $post_id, $field_name, true );
        return $value ? (string) $value : '';
    }

    /**
     * Render the result thumbnail with filterable image size.
     *
     * @param int $post_id Post ID.
     * @return string Thumbnail markup.
     */
    private function get_result_thumbnail( $post_id ) {
        $configured_size = sanitize_key( $this->settings->get( 'result_image_size', 'medium' ) );
        $allowed_sizes   = array_merge( array( 'thumbnail', 'medium', 'medium_large', 'large', 'full' ), get_intermediate_image_sizes() );

        if ( ! in_array( $configured_size, array_unique( $allowed_sizes ), true ) ) {
            $configured_size = 'medium';
        }

        $image_size = apply_filters(
            'sschp_result_image_size',
            $configured_size,
            $post_id,
            array(
                'context' => 'ajax',
            )
        );

        return get_the_post_thumbnail(
            $post_id,
            $image_size,
            array(
                'class'   => 'ssc-result__img',
                'loading' => 'lazy',
            )
        );
    }

    /**
     * Render chips HTML.
     *
     * @param array $chips       Chips to render.
     * @param array $active_tags Currently active tags.
     */
    private function render_chips( $chips, $active_tags ) {
        if ( empty( $chips ) ) {
            return;
        }

        $color          = $this->get_color_setting( 'chip_color', '#e74c3c' );
        $text_color     = $this->get_color_setting( 'chip_text_color', '#ffffff' );
        $inactive_color = $this->get_color_setting( 'chip_inactive_color', '#ffffff' );
        $inactive_text  = $this->get_color_setting( 'chip_inactive_text_color', '#333333' );

        $radius    = $this->get_numeric_setting( 'chip_radius', 16, 0 );
        $font_size = $this->get_numeric_setting( 'chip_font_size', 13, 8 );
        $pad_x     = $this->get_numeric_setting( 'chip_padding_x', 12, 0 );
        $pad_y     = $this->get_numeric_setting( 'chip_padding_y', 4, 0 );

        echo '<div class="ssc-chips">';

        foreach ( $chips as $key => $chip ) {
            $value     = isset( $chip['value'] ) ? $chip['value'] : $chip['label'];
            $is_active = $this->engine->is_tag_active( $value, $active_tags );
            $bg        = $is_active ? esc_attr( $color ) : esc_attr( $inactive_color );
            $fg        = $is_active ? esc_attr( $text_color ) : esc_attr( $inactive_text );
            $border    = esc_attr( $color );

            $custom_html = apply_filters(
                'sschp_render_chip_html',
                '',
                $chip,
                $active_tags,
                array(
                    'context' => 'ajax',
                    'styles'  => array(
                        'background' => $is_active ? $color : $inactive_color,
                        'color'      => $is_active ? $text_color : $inactive_text,
                        'border'     => $color,
                        'radius'     => $radius,
                        'font_size'  => $font_size,
                        'pad_x'      => $pad_x,
                        'pad_y'      => $pad_y,
                    ),
                )
            );

            if ( is_string( $custom_html ) && '' !== $custom_html ) {
                echo wp_kses( $custom_html, $this->get_chip_markup_allowed_html() );
                continue;
            }

            printf(
                '<button type="button" class="ssc-chip%s" data-tag="%s" style="background:%s;color:%s;border:1px solid %s;border-radius:%spx;font-size:%spx;padding:%spx %spx;">%s (%d)</button>',
                $is_active ? ' ssc-chip--active' : '',
                esc_attr( $value ),
                esc_attr( $bg ), esc_attr( $fg ), esc_attr( $border ),
                esc_attr( $radius ), esc_attr( $font_size ),
                esc_attr( $pad_y ), esc_attr( $pad_x ),
                esc_html( $chip['label'] ),
                (int) $chip['count']
            );
        }

        echo '</div>';
    }

    /**
     * Allowed markup for internally generated chip controls.
     *
     * @return array
     */
    private function get_chip_markup_allowed_html() {
        return apply_filters(
            'sschp_chip_markup_allowed_html',
            array(
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
                'span'   => array(
                    'class'           => true,
                    'data-range-role' => true,
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
                    'class'    => true,
                    'type'     => true,
                    'data-tag' => true,
                    'style'    => true,
                ),
            )
        );
    }

    /**
     * Read a numeric setting with a hard fallback for empty/invalid values.
     *
     * @param string $key     Setting key without prefix.
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
     * Read a color setting with a hard fallback.
     *
     * @param string $key     Setting key without prefix.
     * @param string $default Default color.
     * @return string
     */
    private function get_color_setting( $key, $default ) {
        $value = $this->settings->get( $key, '' );
        $value = sanitize_hex_color( $value );

        return $value ? $value : $default;
    }

    /**
     * Render search results HTML.
     *
     * @param array $posts_data Posts to display.
     * @param int   $max        Maximum number to show.
     */
    private function render_results( $posts_data, $max = 10 ) {
        if ( empty( $posts_data ) ) {
            echo '<p class="ssc-empty">' . esc_html( apply_filters( 'sschp_no_filter_results_text', __( 'No results match the selected filters.', 'smart-search-chips' ) ) ) . '</p>';
            return;
        }

        // Allow themes to override the result template
        $template = locate_template( 'smart-search-chips/results.php' );

        if ( $template ) {
            $sschp_posts = array_slice( $posts_data, 0, $max );
            include $template;
            return;
        }

        // Default rendering
        echo '<ul class="ssc-results">';

        $shown = 0;
        foreach ( $posts_data as $post ) {
            if ( $shown >= $max ) {
                break;
            }

            echo '<li class="ssc-result">';
            echo '<a href="' . esc_url( $post['url'] ) . '">';

            if ( $post['thumbnail'] ) {
                echo '<figure class="ssc-result__image">' . wp_kses_post( $post['thumbnail'] ) . '</figure>';
            }

            echo '<span class="ssc-result__title">' . esc_html( $post['title'] ) . '</span>';
            echo '</a>';
            echo '</li>';

            $shown++;
        }

        echo '</ul>';
    }
}
