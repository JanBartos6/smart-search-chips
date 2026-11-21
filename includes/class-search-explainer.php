<?php
/**
 * Search explanation helper for admin/debug diagnostics.
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCHP_Search_Explainer {

    /** @var SSCHP_Chip_Engine */
    private $engine;

    /** @var SSCHP_Settings|null */
    private $settings;

    public function __construct( ?SSCHP_Chip_Engine $engine = null, $settings = null ) {
        $this->engine   = $engine ? $engine : new SSCHP_Chip_Engine();
        $this->settings = $settings;
    }

    /**
     * Explain why chips/results may be missing for a search context.
     *
     * @param array $context Search/debug context.
     * @return array
     */
    public function explain( $context = array() ) {
        $context      = is_array( $context ) ? $context : array();
        $explanations = array();
        $query        = isset( $context['query'] ) ? trim( sanitize_text_field( (string) $context['query'] ) ) : '';
        $min_length   = isset( $context['min_query_length'] ) ? max( 1, (int) $context['min_query_length'] ) : 2;

        if ( '' === $query ) {
            $explanations[] = $this->make_explanation(
                'no_search_query',
                'error',
                __( 'No search query was provided.', 'smart-search-chips' ),
                __( 'Enter a search term with at least two characters, then run the test again.', 'smart-search-chips' )
            );
        } elseif ( strlen( $query ) < $min_length ) {
            $explanations[] = $this->make_explanation(
                'query_too_short',
                'error',
                sprintf(
                    /* translators: %d: minimum query length. */
                    __( 'The search query is too short. Smart Search Chips starts searching at %d characters.', 'smart-search-chips' ),
                    $min_length
                ),
                __( 'Use a longer query so WordPress and the chip generator have enough text to match.', 'smart-search-chips' )
            );
        }

        $explanations = array_merge(
            $explanations,
            $this->get_index_explanations( $context ),
            $this->get_woocommerce_explanations( $context ),
            $this->get_theme_selector_explanations( $context )
        );

        if ( '' === $query || strlen( $query ) < $min_length ) {
            return $this->filter_explanations( $explanations, $context );
        }

        $posts_data     = isset( $context['posts_data'] ) && is_array( $context['posts_data'] ) ? $context['posts_data'] : array();
        $chips          = isset( $context['chips'] ) && is_array( $context['chips'] ) ? $context['chips'] : array();
        $max_tag_length = isset( $context['max_tag_length'] ) ? max( 1, (int) $context['max_tag_length'] ) : 40;

        if ( empty( $posts_data ) ) {
            $explanations[] = $this->make_explanation(
                'no_matching_results',
                'warning',
                __( 'The search ran, but no matching results were returned for this query and post type selection.', 'smart-search-chips' ),
                __( 'Try a broader query, confirm the selected post types, and make sure matching content is published.', 'smart-search-chips' )
            );

            return $this->filter_explanations( $explanations, $context );
        }

        $tag_counts = $this->get_tag_counts( $posts_data, isset( $context['active_tags'] ) ? $context['active_tags'] : array() );

        if ( empty( $tag_counts ) ) {
            $explanations[] = $this->make_explanation(
                'results_missing_tags',
                'warning',
                __( 'Matching results were found, but none of them have Search Chips Tags or generated helper tags available.', 'smart-search-chips' ),
                __( 'Add Search Chips Tags to the matched posts/products, or enable and generate WooCommerce helper tags for products.', 'smart-search-chips' )
            );

            return $this->filter_explanations( $explanations, $context );
        }

        if ( empty( $chips ) ) {
            $total_results      = count( $posts_data );
            $all_tags_redundant = true;
            $has_short_tag      = false;

            foreach ( $tag_counts as $tag ) {
                $count = isset( $tag['count'] ) ? (int) $tag['count'] : 0;
                $label = isset( $tag['label'] ) ? (string) $tag['label'] : '';

                if ( $count < $total_results ) {
                    $all_tags_redundant = false;
                }

                if ( mb_strlen( $label ) <= $max_tag_length ) {
                    $has_short_tag = true;
                }
            }

            if ( ! $has_short_tag ) {
                $explanations[] = $this->make_explanation(
                    'tags_exceed_max_length',
                    'warning',
                    __( 'Tags exist, but every available tag is longer than the current maximum tag length.', 'smart-search-chips' ),
                    __( 'Increase Max tag length in the settings or shorten the Search Chips Tags values.', 'smart-search-chips' )
                );
            }

            if ( $all_tags_redundant ) {
                $explanations[] = $this->make_explanation(
                    'all_tags_redundant',
                    'info',
                    __( 'Tags exist, but every tag is shared by every result, so no chip would narrow this result set.', 'smart-search-chips' ),
                    __( 'Add more specific tags that only apply to some of the matching results.', 'smart-search-chips' )
                );
            }
        }

        return $this->filter_explanations( $explanations, $context );
    }

    /**
     * Build explanations about the local index state.
     *
     * @param array $context Search/debug context.
     * @return array
     */
    private function get_index_explanations( $context ) {
        $search_index = isset( $context['search_index'] ) ? $context['search_index'] : null;
        $search_path  = isset( $context['search_path'] ) ? sanitize_key( (string) $context['search_path'] ) : '';

        if ( ! $search_index || '' === $search_path || 'not_run' === $search_path || 'local_index' === $search_path ) {
            return array();
        }

        $post_types = isset( $context['post_types'] ) ? $context['post_types'] : array();
        $language   = isset( $context['language'] ) ? sanitize_key( (string) $context['language'] ) : '';
        $ready      = method_exists( $search_index, 'is_ready' ) ? (bool) $search_index->is_ready( $post_types, $language ) : true;

        if ( $ready ) {
            return array();
        }

        $status = $this->get_index_status();

        if ( 'queued' === $status ) {
            return array(
                $this->make_explanation(
                    'local_index_queued',
                    'info',
                    __( 'The local search index is queued and has not finished building yet.', 'smart-search-chips' ),
                    __( 'Wait for the background index job to run, or use the settings screen to rebuild the index.', 'smart-search-chips' )
                ),
            );
        }

        if ( 'processing' === $status ) {
            return array(
                $this->make_explanation(
                    'local_index_processing',
                    'info',
                    __( 'The local search index is still processing, so this test used live WordPress search instead.', 'smart-search-chips' ),
                    __( 'Run the test again after indexing finishes, or keep using the fallback while the index catches up.', 'smart-search-chips' )
                ),
            );
        }

        return array(
            $this->make_explanation(
                'local_index_not_ready',
                'warning',
                __( 'The local search index is not ready for this post type/language yet.', 'smart-search-chips' ),
                __( 'Rebuild the local search index from the settings screen, then test the same query again.', 'smart-search-chips' )
            ),
        );
    }

    /**
     * Build WooCommerce-related explanations.
     *
     * @param array $context Search/debug context.
     * @return array
     */
    private function get_woocommerce_explanations( $context ) {
        if ( ! $this->is_woocommerce_active( $context ) ) {
            return array();
        }

        $explanations = array();
        $post_types   = $this->normalize_post_types( isset( $context['post_types'] ) ? $context['post_types'] : array() );

        if ( ! in_array( 'product', $post_types, true ) ) {
            $explanations[] = $this->make_explanation(
                'woocommerce_products_not_selected',
                'warning',
                __( 'WooCommerce is active, but the selected post types do not include products.', 'smart-search-chips' ),
                __( 'Include the product post type in the search settings or in this test search.', 'smart-search-chips' )
            );
        }

        if ( ! $this->woocommerce_helpers_enabled( $context ) ) {
            $explanations[] = $this->make_explanation(
                'woocommerce_helper_tags_disabled',
                'info',
                __( 'WooCommerce helper tags are disabled, so product attributes/categories are not added as generated chip tags.', 'smart-search-chips' ),
                __( 'Enable WooCommerce helper tags if products should receive chips from attributes, categories, and product tags.', 'smart-search-chips' )
            );
        }

        return $explanations;
    }

    /**
     * Build theme selector explanations when theme AJAX enhancement is relevant.
     *
     * @param array $context Search/debug context.
     * @return array
     */
    private function get_theme_selector_explanations( $context ) {
        $theme_context = ! empty( $context['theme_context'] ) || ! empty( $context['include_theme_context'] );

        if ( ! $theme_context && $this->settings && method_exists( $this->settings, 'get' ) ) {
            $theme_context = '' !== sanitize_key( (string) $this->settings->get( 'override_ajax', '' ) );
        }

        if ( ! $theme_context ) {
            return array();
        }

        $selector = isset( $context['result_container_selector'] )
            ? trim( sanitize_text_field( (string) $context['result_container_selector'] ) )
            : $this->get_setting( 'result_container_selector', '.js-ssc-results, .js-search__results, .ssc-search__results' );

        if ( '' === $selector ) {
            return array(
                $this->make_explanation(
                    'result_container_selector_missing',
                    'error',
                    __( 'Theme AJAX enhancement is configured, but no result container selector is set.', 'smart-search-chips' ),
                    __( 'Add the CSS selector for the theme element that receives search results.', 'smart-search-chips' )
                ),
            );
        }

        if ( ! $this->looks_like_valid_css_selector( $selector ) ) {
            return array(
                $this->make_explanation(
                    'result_container_selector_invalid',
                    'error',
                    __( 'The configured theme result container selector does not look valid.', 'smart-search-chips' ),
                    __( 'Correct the Result Container Selector before testing theme AJAX integration.', 'smart-search-chips' )
                ),
            );
        }

        return array();
    }

    /**
     * Count tags using the same extension point as chip generation.
     *
     * @param array $posts_data  Post rows.
     * @param array $active_tags Active tags.
     * @return array
     */
    private function get_tag_counts( $posts_data, $active_tags ) {
        $safe_posts = array();

        foreach ( (array) $posts_data as $post ) {
            $post = is_array( $post ) ? $post : array();
            if ( ! isset( $post['tags'] ) || ! is_array( $post['tags'] ) ) {
                $post['tags'] = array();
            }
            if ( ! isset( $post['tags_lower'] ) || ! is_array( $post['tags_lower'] ) ) {
                $post['tags_lower'] = array_map( 'mb_strtolower', $post['tags'] );
            }
            $safe_posts[] = $post;
        }

        $tag_counts = $this->engine->count_tags( $safe_posts );

        return apply_filters( 'sschp_chip_tag_counts', $tag_counts, $safe_posts, (array) $active_tags );
    }

    /**
     * Create a normalized explanation row.
     *
     * @param string $code          Explanation code.
     * @param string $severity      Severity.
     * @param string $message       Human message.
     * @param string $suggested_fix Suggested fix.
     * @return array
     */
    private function make_explanation( $code, $severity, $message, $suggested_fix ) {
        $severity = sanitize_key( $severity );
        if ( ! in_array( $severity, array( 'info', 'warning', 'error' ), true ) ) {
            $severity = 'info';
        }

        return array(
            'code'         => sanitize_key( $code ),
            'severity'     => $severity,
            'message'      => (string) $message,
            'suggestedFix' => (string) $suggested_fix,
        );
    }

    /**
     * Allow optional modules and site code to extend explanations.
     *
     * @param array $explanations Explanation rows.
     * @param array $context      Search/debug context.
     * @return array
     */
    private function filter_explanations( $explanations, $context ) {
        $explanations = apply_filters( 'sschp_search_explanations', array_values( $explanations ), $context, $this );

        return array_values( array_filter( (array) $explanations, 'is_array' ) );
    }

    /**
     * Determine whether WooCommerce is active.
     *
     * @param array $context Search/debug context.
     * @return bool
     */
    private function is_woocommerce_active( $context ) {
        if ( array_key_exists( 'woocommerce_active', $context ) ) {
            return (bool) $context['woocommerce_active'];
        }

        return class_exists( 'WooCommerce' );
    }

    /**
     * Determine whether WooCommerce helper tags are enabled.
     *
     * @param array $context Search/debug context.
     * @return bool
     */
    private function woocommerce_helpers_enabled( $context ) {
        if ( array_key_exists( 'woocommerce_helper_tags_enabled', $context ) ) {
            return (bool) $context['woocommerce_helper_tags_enabled'];
        }

        return '1' === (string) $this->get_setting( 'woo_enabled', '1' );
    }

    /**
     * Read a plugin setting when available.
     *
     * @param string $key     Setting key without prefix.
     * @param mixed  $default Default value.
     * @return mixed
     */
    private function get_setting( $key, $default = '' ) {
        if ( $this->settings && method_exists( $this->settings, 'get' ) ) {
            return $this->settings->get( $key, $default );
        }

        return get_option( 'sschp_' . $key, $default );
    }

    /**
     * Get local index status.
     *
     * @return string
     */
    private function get_index_status() {
        if ( class_exists( 'SSCHP_Search_Index' ) ) {
            return sanitize_key( get_option( SSCHP_Search_Index::STATUS_OPTION, 'not_started' ) );
        }

        return 'not_started';
    }

    /**
     * Normalize post types.
     *
     * @param mixed $post_types Post types.
     * @return array
     */
    private function normalize_post_types( $post_types ) {
        $post_types = is_array( $post_types ) ? $post_types : array( $post_types );
        $post_types = array_filter( array_map( 'sanitize_key', $post_types ) );

        return array_values( $post_types );
    }

    /**
     * Conservative CSS selector sanity check.
     *
     * @param string $selector Selector value.
     * @return bool
     */
    private function looks_like_valid_css_selector( $selector ) {
        if ( '' === trim( $selector ) ) {
            return false;
        }

        if ( preg_match( '/[\x00-\x1F\x7F]/', $selector ) ) {
            return false;
        }

        if ( preg_match( '/(^|,)\s*(,|$)/', $selector ) ) {
            return false;
        }

        $pairs = array(
            '(' => ')',
            '[' => ']',
            '{' => '}',
        );
        $stack = array();
        $quote = '';
        $len   = strlen( $selector );

        for ( $i = 0; $i < $len; $i++ ) {
            $char = $selector[ $i ];

            if ( '\\' === $char ) {
                $i++;
                continue;
            }

            if ( '' !== $quote ) {
                if ( $char === $quote ) {
                    $quote = '';
                }
                continue;
            }

            if ( '"' === $char || "'" === $char ) {
                $quote = $char;
                continue;
            }

            if ( isset( $pairs[ $char ] ) ) {
                $stack[] = $pairs[ $char ];
                continue;
            }

            if ( in_array( $char, $pairs, true ) ) {
                if ( empty( $stack ) || array_pop( $stack ) !== $char ) {
                    return false;
                }
            }
        }

        return '' === $quote && empty( $stack );
    }
}
