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
        if ( sschp_fs()->can_use_premium_code__premium_only() ) {
            // Override existing AJAX action (Pro only)
            $ajax_action = $this->settings->get( 'override_ajax' );
            if ( ! empty( $ajax_action ) ) {
                add_action( 'wp_ajax_' . $ajax_action, array( $this, 'handle_search' ), 0 );
                add_action( 'wp_ajax_nopriv_' . $ajax_action, array( $this, 'handle_search' ), 0 );
            }
        }

        // Always register our own action
        add_action( 'wp_ajax_sschp_search', array( $this, 'handle_search' ) );
        add_action( 'wp_ajax_nopriv_sschp_search', array( $this, 'handle_search' ) );
    }

    /**
     * Handle the AJAX search request.
     */
    public function handle_search() {
        // Nonce verification (optional — skip if overriding a theme handler that doesn't use nonces)
        if ( isset( $_POST['sschp_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sschp_nonce'] ) ), 'sschp_search_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'smart-search-chips' ) );
        }

        $keyword     = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
        $active_tags = $this->normalize_active_tags( isset( $_POST['active_tags'] ) ? wp_unslash( $_POST['active_tags'] ) : array() );

        if ( empty( $keyword ) || strlen( $keyword ) < 2 ) {
            wp_die();
        }

        $language = SSCHP_Language::request_language();
        SSCHP_Language::switch_ajax_language( $language );

        $post_types     = $this->settings->get( 'post_types', array( 'post' ) );
        $max_results    = (int) $this->settings->get( 'search_results_count', 50 );
        $threshold_pct  = (float) $this->settings->get( 'relevance_threshold', 0.3 );
        $max_tag_length = (int) $this->settings->get( 'max_tag_length', 40 );
        $display_count  = (int) $this->settings->get( 'max_results', 10 );

        // Enforce free version limit (5 chips)
        $max_chips = min( (int) $this->settings->get( 'max_chips', 8 ), 5 );

        if ( sschp_fs()->can_use_premium_code__premium_only() ) {
            // Pro: raise the limit
            $max_chips = min( (int) $this->settings->get( 'max_chips', 8 ), 99 );
        }

        // Run search query (Relevanssi hooks in automatically if active)
        $query_args = array(
            's'              => $keyword,
            'post_type'      => $post_types,
            'posts_per_page' => $max_results,
        );

        $query = new WP_Query( SSCHP_Language::apply_query_language( $query_args, $language ) );

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

        // Filter by relevance threshold (Pro + Relevanssi only)
        $use_relevance = false;
        if ( sschp_fs()->can_use_premium_code__premium_only() ) {
            $use_relevance = $top_score > 0;
        }
        if ( $use_relevance ) {
            $threshold      = $top_score * $threshold_pct;
            $relevant_posts = array_filter( $scored_posts, function( $p ) use ( $threshold ) {
                return $p['score'] >= $threshold;
            } );
            $relevant_posts = array_values( $relevant_posts );
        } else {
            $relevant_posts = $scored_posts;
        }

        // Build posts data with tags
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

        // Render
        $this->render_chips( $chips, $active_tags );
        $this->render_results( $posts_data, $display_count );

        wp_die();
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

        $tags = array_map( 'sanitize_text_field', $raw_tags );
        $tags = array_filter( $tags, function( $tag ) {
            return '' !== $tag;
        } );

        return array_values( array_unique( $tags ) );
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

            if ( sschp_fs()->can_use_premium_code__premium_only() ) {
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

            $tag_data = $this->engine->extract_tags( $primary, $extra );

            $posts_data[] = array(
                'id'         => $pid,
                'url'        => get_permalink( $pid ),
                'title'      => get_the_title( $pid ),
                'thumbnail'  => get_the_post_thumbnail( $pid, apply_filters( 'sschp_thumbnail_size', 'thumbnail' ) ),
                'tags'       => $tag_data['tags'],
                'tags_lower' => $tag_data['tags_lower'],
            );
        }

        return $posts_data;
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

        // Pro design options (defaults for free)
        $radius    = 16;
        $font_size = 13;
        $pad_x     = 12;
        $pad_y     = 4;

        if ( sschp_fs()->can_use_premium_code__premium_only() ) {
            $radius    = $this->get_numeric_setting( 'chip_radius', 16, 0 );
            $font_size = $this->get_numeric_setting( 'chip_font_size', 13, 8 );
            $pad_x     = $this->get_numeric_setting( 'chip_padding_x', 12, 0 );
            $pad_y     = $this->get_numeric_setting( 'chip_padding_y', 4, 0 );
        }

        echo '<div class="ssc-chips">';

        foreach ( $chips as $key => $chip ) {
            $is_active = in_array( $chip['label'], $active_tags, true );
            $bg        = $is_active ? esc_attr( $color ) : esc_attr( $inactive_color );
            $fg        = $is_active ? esc_attr( $text_color ) : esc_attr( $inactive_text );
            $border    = esc_attr( $color );

            printf(
                '<button type="button" class="ssc-chip%s" data-tag="%s" style="background:%s;color:%s;border:1px solid %s;border-radius:%spx;font-size:%spx;padding:%spx %spx;">%s (%d)</button>',
                $is_active ? ' ssc-chip--active' : '',
                esc_attr( $chip['label'] ),
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
