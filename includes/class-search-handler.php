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

    const CACHE_GROUP          = 'smart-search-chips';
    const CACHE_VERSION_OPTION = 'sschp_cache_version';
    const DEFAULT_CACHE_TTL    = 300;

    /** @var SSCHP_Chip_Engine */
    private $engine;

    /** @var SSCHP_Settings */
    private $settings;

    /** @var SSCHP_Search_Index|null */
    private $search_index;

    /** @var SSCHP_Relevance_Scorer */
    private $relevance_scorer;

    public function __construct( SSCHP_Chip_Engine $engine, SSCHP_Settings $settings, ?SSCHP_Search_Index $search_index = null ) {
        $this->engine           = $engine;
        $this->settings         = $settings;
        $this->search_index     = $search_index;
        $this->relevance_scorer = new SSCHP_Relevance_Scorer();
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

        add_action( 'save_post', array( $this, 'handle_cache_post_changed' ), 70, 3 );
        add_action( 'deleted_post', array( $this, 'handle_cache_post_deleted' ) );
        add_action( 'updated_option', array( $this, 'handle_cache_option_changed' ), 20, 3 );
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
        $max_results    = $this->limit_analyzed_results( (int) $this->settings->get( 'search_results_count', 50 ) );
        $threshold_pct  = (float) $this->settings->get( 'relevance_threshold', 0.3 );
        $max_tag_length = (int) $this->settings->get( 'max_tag_length', 40 );
        $display_count  = $this->limit_rendered_results( (int) $this->settings->get( 'max_results', 10 ) );

        $max_chips = min( (int) $this->settings->get( 'max_chips', 8 ), SSCHP_License::max_chips() );

        $posts_data = $this->get_posts_data_for_keyword( $keyword, $post_types, $active_tags, $language, $max_results, $threshold_pct );

        if ( empty( $posts_data ) ) {
            header( 'X-SSC-Results-Count: 0' );
            echo '<p class="ssc-empty">' . esc_html( apply_filters( 'sschp_no_results_text', __( 'No results found.', 'smart-search-chips' ) ) ) . '</p>';
            wp_die();
        }

        // Filter by active tags
        $posts_data = $this->get_cached_filtered_posts( $posts_data, $active_tags );

        $total = count( $posts_data );
        header( 'X-SSC-Results-Count: ' . (int) $total );

        // Generate chips
        $chips = $this->get_cached_chips( $posts_data, $active_tags, $max_chips, $max_tag_length );

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
     * Get post data from the local index when ready, otherwise use WP_Query.
     *
     * @param string $keyword       Search keyword.
     * @param array  $post_types    Post types.
     * @param array  $active_tags   Active tags.
     * @param string $language      Language code.
     * @param int    $max_results   Search result limit.
     * @param float  $threshold_pct Relevance threshold.
     * @return array
     */
    private function get_posts_data_for_keyword( $keyword, $post_types, $active_tags, $language, $max_results, $threshold_pct ) {
        $cache_key = $this->build_cache_key(
            'search',
            array(
                'keyword'       => $keyword,
                'post_types'    => array_values( (array) $post_types ),
                'active_tags'   => $this->normalize_cache_tags( $active_tags ),
                'language'      => $language,
                'max_results'   => $max_results,
                'threshold_pct' => $threshold_pct,
            )
        );
        $cached = $this->cache_get( $cache_key );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $posts_data = $this->query_posts_data_for_keyword( $keyword, $post_types, $active_tags, $language, $max_results, $threshold_pct );
        $this->cache_set( $cache_key, $posts_data );

        return $posts_data;
    }

    /**
     * Run a structured admin-only diagnostic search.
     *
     * This uses the same search/index/tag/chip pipeline as the frontend AJAX
     * handler, but returns data for the settings screen instead of rendered HTML.
     *
     * @param array $args Diagnostic search arguments.
     * @return array
     */
    public function get_admin_test_search_data( $args = array() ) {
        $keyword   = isset( $args['keyword'] ) ? sanitize_text_field( (string) $args['keyword'] ) : '';
        $explainer = new SSCHP_Search_Explainer( $this->engine, $this->settings );

        $post_types = isset( $args['post_types'] )
            ? $this->normalize_post_types( $args['post_types'] )
            : $this->normalize_post_types( $this->settings->get( 'post_types', array( 'post' ) ) );

        if ( '' === trim( $keyword ) || strlen( $keyword ) < 2 ) {
            $explanations = $explainer->explain(
                array(
                    'query'        => $keyword,
                    'post_types'   => $post_types,
                    'search_path'  => 'not_run',
                    'search_index' => $this->search_index,
                )
            );

            return array(
                'query'             => $keyword,
                'path'              => 'not_run',
                'pathLabel'         => __( 'Not run', 'smart-search-chips' ),
                'postTypes'         => $post_types,
                'resultCount'       => 0,
                'taggedResultCount' => 0,
                'chips'             => array(),
                'results'           => array(),
                'warnings'          => $this->get_warning_messages_from_explanations( $explanations ),
                'explanations'      => $explanations,
            );
        }

        $language        = isset( $args['language'] ) ? sanitize_key( (string) $args['language'] ) : SSCHP_Language::current_language();
        $max_results     = $this->limit_analyzed_results( (int) $this->settings->get( 'search_results_count', 50 ) );
        $threshold_pct   = (float) $this->settings->get( 'relevance_threshold', 0.3 );
        $max_tag_length  = (int) $this->settings->get( 'max_tag_length', 40 );
        $max_chips       = min( (int) $this->settings->get( 'max_chips', 8 ), $this->get_max_chips_limit() );
        $preview_results = max( 1, min( 5, (int) apply_filters( 'sschp_admin_test_search_preview_results', 5 ) ) );
        $diagnostics     = array(
            'path'     => 'wp_query_fallback',
            'warnings' => array(),
        );
        $search_data = $this->get_diagnostic_posts_data(
            array(
                'keyword'       => $keyword,
                'post_types'    => $post_types,
                'language'      => $language,
                'max_results'   => $max_results,
                'threshold_pct' => $threshold_pct,
            )
        );
        $posts_data  = $search_data['postsData'];
        $diagnostics = array(
            'path'     => $search_data['path'],
            'warnings' => $search_data['warnings'],
        );

        $chips = $this->engine->generate_chips( $posts_data, array(), $max_chips, $max_tag_length );
        $chips = apply_filters( 'sschp_chips', $chips, $posts_data, array() );

        $explanations = $explainer->explain(
            array(
                'query'          => $keyword,
                'post_types'     => $post_types,
                'language'       => $language,
                'search_path'    => $diagnostics['path'],
                'search_index'   => $this->search_index,
                'posts_data'     => $posts_data,
                'chips'          => $chips,
                'max_tag_length' => $max_tag_length,
            )
        );
        $warnings = array_merge(
            isset( $diagnostics['warnings'] ) ? (array) $diagnostics['warnings'] : array(),
            $this->get_warning_messages_from_explanations( $explanations )
        );

        if ( ! $this->is_relevanssi_available() && 'wp_query_fallback' === $diagnostics['path'] ) {
            $warnings[] = __( 'Relevanssi was not detected, so Smart Search Chips used the built-in WP_Query fallback scoring.', 'smart-search-chips' );
        }

        return array(
            'query'             => $keyword,
            'path'              => $diagnostics['path'],
            'pathLabel'         => $this->get_search_path_label( $diagnostics['path'] ),
            'postTypes'         => $post_types,
            'resultCount'       => count( $posts_data ),
            'taggedResultCount' => $this->count_tagged_posts( $posts_data ),
            'chips'             => $this->format_admin_test_chips( $chips ),
            'results'           => $this->format_admin_test_results( array_slice( $posts_data, 0, $preview_results ) ),
            'warnings'          => array_values( array_unique( array_filter( $warnings ) ) ),
            'explanations'      => $explanations,
        );
    }

    /**
     * Return raw post data from the same search/index/relevance pipeline used by
     * the frontend and admin Test Search diagnostics.
     *
     * @param array $args Diagnostic search arguments.
     * @return array
     */
    public function get_diagnostic_posts_data( $args = array() ) {
        $keyword = isset( $args['keyword'] ) ? sanitize_text_field( (string) $args['keyword'] ) : '';

        $post_types = isset( $args['post_types'] )
            ? $this->normalize_post_types( $args['post_types'] )
            : $this->normalize_post_types( $this->settings->get( 'post_types', array( 'post' ) ) );

        $active_tags = isset( $args['active_tags'] )
            ? $this->normalize_active_tags( $args['active_tags'] )
            : array();

        $language      = isset( $args['language'] ) ? sanitize_key( (string) $args['language'] ) : SSCHP_Language::current_language();
        $max_results   = isset( $args['max_results'] ) ? $this->limit_analyzed_results( absint( $args['max_results'] ) ) : $this->limit_analyzed_results( (int) $this->settings->get( 'search_results_count', 50 ) );
        $threshold_pct = isset( $args['threshold_pct'] ) ? (float) $args['threshold_pct'] : (float) $this->settings->get( 'relevance_threshold', 0.3 );
        $diagnostics   = array(
            'path'     => 'wp_query_fallback',
            'warnings' => array(),
        );

        $posts_data = $this->query_posts_data_for_keyword(
            $keyword,
            $post_types,
            $active_tags,
            $language,
            $max_results,
            $threshold_pct,
            $diagnostics
        );

        return array(
            'query'     => $keyword,
            'path'      => $diagnostics['path'],
            'pathLabel' => $this->get_search_path_label( $diagnostics['path'] ),
            'postTypes' => $post_types,
            'language'  => $language,
            'postsData' => $posts_data,
            'warnings'  => isset( $diagnostics['warnings'] ) ? array_values( array_filter( (array) $diagnostics['warnings'] ) ) : array(),
        );
    }

    /**
     * Query post data without cache.
     *
     * @param string $keyword       Search keyword.
     * @param array  $post_types    Post types.
     * @param array  $active_tags   Active tags.
     * @param string $language      Language code.
     * @param int    $max_results   Search result limit.
     * @param float  $threshold_pct Relevance threshold.
     * @return array
     */
    private function query_posts_data_for_keyword( $keyword, $post_types, $active_tags, $language, $max_results, $threshold_pct, &$diagnostics = null ) {
        if ( ! is_array( $diagnostics ) ) {
            $diagnostics = array();
        }

        if (
            $this->search_index
            && apply_filters( 'sschp_use_search_index', true, $keyword, $post_types, $language )
        ) {
            $indexed_posts = $this->search_index->search( $keyword, $post_types, $language, $max_results );
            if ( is_array( $indexed_posts ) ) {
                $diagnostics['path'] = 'local_index';
                return $indexed_posts;
            }

            if ( method_exists( $this->search_index, 'is_ready' ) ) {
                $diagnostics['index_ready'] = (bool) $this->search_index->is_ready( $post_types, $language );
            }
        }

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

        $relevanssi_available = $this->is_relevanssi_available();
        $relevanssi_ran       = function_exists( 'relevanssi_do_query' );
        $diagnostics['path']  = $relevanssi_ran ? 'relevanssi' : 'wp_query_fallback';

        if ( $relevanssi_ran ) {
            relevanssi_do_query( $query );
        }

        if ( ! $query->have_posts() ) {
            return array();
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
            $diagnostics['path'] = 'relevanssi';
            $threshold      = $top_score * $threshold_pct;
            $relevant_posts = array_filter( $scored_posts, function( $p ) use ( $threshold ) {
                return $p['score'] >= $threshold;
            } );
            $relevant_posts = array_values( $relevant_posts );
        } elseif ( ! $relevanssi_available || apply_filters( 'sschp_score_relevanssi_results', false, $keyword, $query ) ) {
            $diagnostics['path'] = 'wp_query_fallback';
            $relevant_posts = $this->score_fallback_posts( $scored_posts, $keyword );
        } else {
            $diagnostics['path'] = 'relevanssi';
            $relevant_posts = $scored_posts;
        }

        return $this->collect_posts_data( $relevant_posts );
    }

    /**
     * Format chips for admin diagnostics JSON.
     *
     * @param array $chips Generated chips.
     * @return array
     */
    private function format_admin_test_chips( $chips ) {
        $formatted = array();

        foreach ( (array) $chips as $chip ) {
            $type = isset( $chip['type'] ) ? sanitize_key( $chip['type'] ) : 'chip';
            $row  = array(
                'label' => isset( $chip['label'] ) ? (string) $chip['label'] : '',
                'value' => isset( $chip['value'] ) ? (string) $chip['value'] : ( isset( $chip['label'] ) ? (string) $chip['label'] : '' ),
                'count' => isset( $chip['count'] ) ? (int) $chip['count'] : 0,
                'type'  => $type,
            );

            if ( 'range' === $type && ! empty( $chip['range'] ) && is_array( $chip['range'] ) ) {
                $row['range'] = $this->format_admin_test_range_chip( $chip['range'] );
            }

            $formatted[] = $row;
        }

        return $formatted;
    }

    /**
     * Format range-chip metadata for admin diagnostics JSON.
     *
     * @param array $range Range chip metadata.
     * @return array
     */
    private function format_admin_test_range_chip( $range ) {
        return array(
            'groupKey'    => isset( $range['group_key'] ) ? sanitize_key( $range['group_key'] ) : '',
            'unitKey'     => isset( $range['unit_key'] ) ? sanitize_key( $range['unit_key'] ) : '',
            'unit'        => isset( $range['unit'] ) ? sanitize_text_field( (string) $range['unit'] ) : '',
            'min'         => isset( $range['min'] ) ? (float) $range['min'] : 0.0,
            'max'         => isset( $range['max'] ) ? (float) $range['max'] : 0.0,
            'selectedMin' => isset( $range['selected_min'] ) ? (float) $range['selected_min'] : ( isset( $range['min'] ) ? (float) $range['min'] : 0.0 ),
            'selectedMax' => isset( $range['selected_max'] ) ? (float) $range['selected_max'] : ( isset( $range['max'] ) ? (float) $range['max'] : 0.0 ),
            'step'        => isset( $range['step'] ) ? (float) $range['step'] : 1.0,
        );
    }

    /**
     * Format result previews for admin diagnostics JSON.
     *
     * @param array $posts_data Post data rows.
     * @return array
     */
    private function format_admin_test_results( $posts_data ) {
        $formatted = array();

        foreach ( (array) $posts_data as $post ) {
            $formatted[] = array(
                'id'       => isset( $post['id'] ) ? absint( $post['id'] ) : 0,
                'title'    => isset( $post['title'] ) ? (string) $post['title'] : '',
                'url'      => isset( $post['url'] ) ? esc_url_raw( $post['url'] ) : '',
                'postType' => isset( $post['post_type'] ) ? sanitize_key( $post['post_type'] ) : '',
                'score'    => isset( $post['score'] ) ? (float) $post['score'] : 0.0,
                'tags'     => isset( $post['tags'] ) ? array_values( array_map( 'strval', (array) $post['tags'] ) ) : array(),
            );
        }

        return $formatted;
    }

    /**
     * Convert structured explanations to legacy warning strings.
     *
     * @param array $explanations Explanation rows.
     * @return array
     */
    private function get_warning_messages_from_explanations( $explanations ) {
        $messages = array();

        foreach ( (array) $explanations as $explanation ) {
            if ( ! is_array( $explanation ) ) {
                continue;
            }

            $severity = isset( $explanation['severity'] ) ? sanitize_key( $explanation['severity'] ) : 'info';
            if ( ! in_array( $severity, array( 'warning', 'error' ), true ) ) {
                continue;
            }

            if ( ! empty( $explanation['message'] ) ) {
                $messages[] = (string) $explanation['message'];
            }
        }

        return array_values( array_unique( array_filter( $messages ) ) );
    }

    /**
     * Return a human-readable search path label.
     *
     * @param string $path Path key.
     * @return string
     */
    private function get_search_path_label( $path ) {
        $labels = array(
            'local_index'       => __( 'Local index', 'smart-search-chips' ),
            'relevanssi'        => __( 'Relevanssi', 'smart-search-chips' ),
            'wp_query_fallback' => __( 'WP_Query fallback', 'smart-search-chips' ),
            'not_run'           => __( 'Not run', 'smart-search-chips' ),
        );

        return isset( $labels[ $path ] ) ? $labels[ $path ] : $labels['wp_query_fallback'];
    }

    /**
     * Get the edition-specific chip limit.
     *
     * @return int
     */
    private function get_max_chips_limit() {
        if ( class_exists( 'SSCHP_License' ) ) {
            return SSCHP_License::max_chips();
        }

        if ( class_exists( 'SSCHP_Edition' ) ) {
            return SSCHP_Edition::max_chips();
        }

        return 8;
    }

    /**
     * Return filtered posts from cache when possible.
     *
     * @param array $posts_data  Base posts data.
     * @param array $active_tags Active tags.
     * @return array
     */
    private function get_cached_filtered_posts( $posts_data, $active_tags ) {
        if ( empty( $active_tags ) ) {
            return $posts_data;
        }

        $cache_key = $this->build_cache_key(
            'filtered',
            array(
                'posts'       => $this->posts_data_signature( $posts_data ),
                'active_tags' => $this->normalize_cache_tags( $active_tags ),
            )
        );
        $cached = $this->cache_get( $cache_key );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $filtered = $this->engine->filter_by_tags( $posts_data, $active_tags );
        $this->cache_set( $cache_key, $filtered );

        return $filtered;
    }

    /**
     * Return generated chips from cache when possible.
     *
     * @param array $posts_data    Posts data.
     * @param array $active_tags   Active tags.
     * @param int   $max_chips     Max chips.
     * @param int   $max_tag_length Max tag length.
     * @return array
     */
    private function get_cached_chips( $posts_data, $active_tags, $max_chips, $max_tag_length ) {
        $cache_key = $this->build_cache_key(
            'chips',
            array(
                'posts'          => $this->posts_data_signature( $posts_data ),
                'active_tags'    => $this->normalize_cache_tags( $active_tags ),
                'max_chips'      => (int) $max_chips,
                'max_tag_length' => (int) $max_tag_length,
            )
        );
        $cached = $this->cache_get( $cache_key );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $chips = $this->engine->generate_chips( $posts_data, $active_tags, $max_chips, $max_tag_length );
        $this->cache_set( $cache_key, $chips );

        return $chips;
    }

    /**
     * Create a stable signature for post data used by chip/filter caches.
     *
     * @param array $posts_data Posts data.
     * @return string
     */
    private function posts_data_signature( $posts_data ) {
        $signature = array();

        foreach ( (array) $posts_data as $post ) {
            $signature[] = array(
                'id'    => isset( $post['id'] ) ? absint( $post['id'] ) : 0,
                'tags'  => isset( $post['tags'] ) ? array_values( (array) $post['tags'] ) : array(),
                'keys'  => isset( $post['tags_lower'] ) ? array_values( (array) $post['tags_lower'] ) : array(),
                'score' => isset( $post['score'] ) ? (float) $post['score'] : 0,
            );
        }

        return md5( wp_json_encode( $signature ) );
    }

    /**
     * Normalize active tags for cache keys without changing display labels.
     *
     * @param array $active_tags Active tags.
     * @return array
     */
    private function normalize_cache_tags( $active_tags ) {
        $tags = array();

        foreach ( (array) $active_tags as $tag ) {
            $key = mb_strtolower( trim( (string) $tag ) );
            if ( '' !== $key ) {
                $tags[ $key ] = $key;
            }
        }

        ksort( $tags );

        return array_values( $tags );
    }

    /**
     * Build a versioned cache key.
     *
     * @param string $scope Cache scope.
     * @param array  $data  Cache key data.
     * @return string
     */
    private function build_cache_key( $scope, $data ) {
        $payload = array(
            'scope'   => sanitize_key( $scope ),
            'version' => $this->get_cache_version(),
            'data'    => $data,
        );

        return 'sschp_' . sanitize_key( $scope ) . '_' . md5( wp_json_encode( $payload ) );
    }

    /**
     * Read the current search cache version.
     *
     * @return string
     */
    private function get_cache_version() {
        $version = get_option( self::CACHE_VERSION_OPTION, '' );

        return '' !== (string) $version ? (string) $version : '1';
    }

    /**
     * Get a cached value from object cache or transient fallback.
     *
     * @param string $key Cache key.
     * @return mixed
     */
    private function cache_get( $key ) {
        if ( $this->use_object_cache() ) {
            return wp_cache_get( $key, self::CACHE_GROUP );
        }

        return get_transient( $key );
    }

    /**
     * Store a cached value.
     *
     * @param string $key   Cache key.
     * @param mixed  $value Value.
     * @return void
     */
    private function cache_set( $key, $value ) {
        $ttl = max( 30, (int) apply_filters( 'sschp_search_cache_ttl', self::DEFAULT_CACHE_TTL, $key, $value ) );

        if ( $this->use_object_cache() ) {
            wp_cache_set( $key, $value, self::CACHE_GROUP, $ttl );
            return;
        }

        set_transient( $key, $value, $ttl );
    }

    /**
     * Determine whether a persistent object cache should be used.
     *
     * @return bool
     */
    private function use_object_cache() {
        return function_exists( 'wp_using_ext_object_cache' )
            && wp_using_ext_object_cache()
            && function_exists( 'wp_cache_get' )
            && function_exists( 'wp_cache_set' );
    }

    /**
     * Invalidate all search/chip caches by bumping the version.
     *
     * @param string $reason Reason for diagnostics.
     * @return void
     */
    public function bump_cache_version( $reason = '' ) {
        $version = function_exists( 'wp_generate_uuid4' )
            ? microtime( true ) . '-' . wp_generate_uuid4()
            : (string) microtime( true );

        update_option( self::CACHE_VERSION_OPTION, $version, false );
        do_action( 'sschp_search_cache_invalidated', $reason, $version );
    }

    /**
     * Invalidate cache when content changes.
     *
     * @param int         $post_id Post ID.
     * @param WP_Post|null $post   Post object.
     * @param bool        $update  Whether this is an existing post update.
     */
    public function handle_cache_post_changed( $post_id, $post = null, $update = null ) {
        $post_id = absint( $post_id );
        if ( $post_id <= 0 ) {
            return;
        }

        if ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post_id ) ) {
            return;
        }

        if ( function_exists( 'wp_is_post_autosave' ) && wp_is_post_autosave( $post_id ) ) {
            return;
        }

        $this->bump_cache_version( 'post_changed' );
    }

    /**
     * Invalidate cache when content is deleted.
     *
     * @param int $post_id Post ID.
     */
    public function handle_cache_post_deleted( $post_id ) {
        if ( absint( $post_id ) > 0 ) {
            $this->bump_cache_version( 'post_deleted' );
        }
    }

    /**
     * Invalidate cache when relevant settings change.
     *
     * @param string $option    Option name.
     * @param mixed  $old_value Old value.
     * @param mixed  $value     New value.
     */
    public function handle_cache_option_changed( $option, $old_value, $value ) {
        if ( $old_value === $value || self::CACHE_VERSION_OPTION === $option ) {
            return;
        }

        if ( in_array( $option, $this->get_cache_sensitive_options(), true ) ) {
            $this->bump_cache_version( 'settings_changed' );
        }
    }

    /**
     * Options that affect search result or chip output.
     *
     * @return array
     */
    private function get_cache_sensitive_options() {
        return array(
            'sschp_post_types',
            'sschp_tag_fields',
            'sschp_extra_fields',
            'sschp_max_chips',
            'sschp_max_results',
            'sschp_relevance_threshold',
            'sschp_max_tag_length',
            'sschp_search_results_count',
            'sschp_woo_enabled',
            'sschp_woo_include_sku',
            'sschp_synonym_rules',
            'sschp_pinned_chips',
            'sschp_hidden_chips',
            'sschp_renamed_chips',
        );
    }

    /**
     * Clamp analyzed results to a hard performance cap.
     *
     * @param int $value Requested value.
     * @return int
     */
    private function limit_analyzed_results( $value ) {
        $cap = max( 5, (int) apply_filters( 'sschp_hard_analyzed_results_cap', 200 ) );

        return max( 1, min( $cap, (int) $value ) );
    }

    /**
     * Clamp rendered results to a hard performance cap.
     *
     * @param int $value Requested value.
     * @return int
     */
    private function limit_rendered_results( $value ) {
        $cap = max( 1, (int) apply_filters( 'sschp_hard_rendered_results_cap', 100 ) );

        return max( 1, min( $cap, (int) $value ) );
    }

    /**
     * Determine whether Relevanssi should own result ordering.
     *
     * @return bool
     */
    private function is_relevanssi_available() {
        return (bool) apply_filters( 'sschp_relevanssi_available', function_exists( 'relevanssi_do_query' ) );
    }

    /**
     * Score and sort default WP_Query results when Relevanssi is unavailable.
     *
     * @param array  $posts   Scored post rows.
     * @param string $keyword Search keyword.
     * @return array
     */
    private function score_fallback_posts( $posts, $keyword ) {
        $top_score = 0.0;

        foreach ( $posts as $index => $row ) {
            if ( empty( $row['post'] ) || ! is_object( $row['post'] ) ) {
                continue;
            }

            $post_id    = isset( $row['post']->ID ) ? absint( $row['post']->ID ) : 0;
            $tag_source = $this->get_post_tag_source( $post_id, 'score' );
            $tag_data   = $this->engine->extract_tags( $tag_source['primary'], $tag_source['extra'] );
            $score      = $this->relevance_scorer->score_post( $row['post'], $keyword, $tag_data['tags'] );

            $posts[ $index ]['score'] = $score;
            $top_score = max( $top_score, $score );
        }

        if ( $top_score <= 0 ) {
            return $posts;
        }

        $posts = array_values(
            array_filter(
                $posts,
                function( $row ) {
                    return isset( $row['score'] ) && (float) $row['score'] > 0;
                }
            )
        );

        usort(
            $posts,
            function( $a, $b ) {
                $score_a = isset( $a['score'] ) ? (float) $a['score'] : 0.0;
                $score_b = isset( $b['score'] ) ? (float) $b['score'] : 0.0;

                if ( $score_a === $score_b ) {
                    return 0;
                }

                return $score_a < $score_b ? 1 : -1;
            }
        );

        return $posts;
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
        if ( isset( $_POST[ $key ] ) ) {
            $posted_value = wp_unslash( $_POST[ $key ] );

            if ( ! is_array( $posted_value ) ) {
                return sanitize_text_field( $posted_value );
            }
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

            $tag_source = $this->get_post_tag_source( $pid, 'ajax' );
            $primary    = $tag_source['primary'];
            $extra      = $tag_source['extra'];
            $tag_data   = $this->engine->extract_tags( $primary, $extra );

            $posts_data[] = array(
                'id'         => $pid,
                'url'        => get_permalink( $pid ),
                'title'      => get_the_title( $pid ),
                'thumbnail'  => $this->get_result_thumbnail( $pid ),
                'post_type'  => get_post_type( $pid ),
                'score'      => isset( $rp['score'] ) ? (float) $rp['score'] : 0.0,
                'tags'       => $tag_data['tags'],
                'tags_lower' => $tag_data['tags_lower'],
            );
        }

        return $posts_data;
    }

    /**
     * Get chip tag source values for a post.
     *
     * @param int    $post_id Post ID.
     * @param string $context Read context.
     * @return array
     */
    private function get_post_tag_source( $post_id, $context = 'ajax' ) {
        // Default: use built-in sschp_tags field only
        $primary = get_post_meta( $post_id, SSCHP_Meta_Box::META_KEY, true ) ?: '';
        $extra   = '';

        if ( SSCHP_License::is_pro() ) {
            // Pro: use configured custom fields when present, otherwise keep
            // the built-in fallback so sites do not lose chips just because
            // Pro is active before field mapping is configured.
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
                'context' => $context,
            )
        );

        if ( is_array( $tag_source ) ) {
            $primary = isset( $tag_source['primary'] ) ? (string) $tag_source['primary'] : '';
            $extra   = isset( $tag_source['extra'] ) ? (string) $tag_source['extra'] : '';
        }

        return array(
            'primary' => $primary,
            'extra'   => $extra,
        );
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

        $context = array(
            'post_id'    => (int) $post_id,
            'field_name' => (string) $field_name,
            'context'    => 'ajax',
            'source'     => 'post_meta',
        );

        // Try ACF first
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

        // Fall back to regular post meta
        $value = get_post_meta( $post_id, $field_name, true );
        return SSCHP_Field_Normalizer::normalize( $value, $context );
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
            $count     = (int) $chip['count'];

            if ( $is_active ) {
                /* translators: 1: chip label, 2: matching result count. */
                $aria_label = sprintf(
                    _n( 'Remove filter %1$s, %2$d result', 'Remove filter %1$s, %2$d results', $count, 'smart-search-chips' ),
                    $chip['label'],
                    $count
                );
            } else {
                /* translators: 1: chip label, 2: matching result count. */
                $aria_label = sprintf(
                    _n( 'Filter by %1$s, %2$d result', 'Filter by %1$s, %2$d results', $count, 'smart-search-chips' ),
                    $chip['label'],
                    $count
                );
            }

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
                '<button type="button" class="ssc-chip%s" data-tag="%s" aria-pressed="%s" aria-label="%s" style="background:%s;color:%s;border:1px solid %s;border-radius:%spx;font-size:%spx;padding:%spx %spx;">%s (%d)</button>',
                $is_active ? ' ssc-chip--active' : '',
                esc_attr( $value ),
                $is_active ? 'true' : 'false',
                esc_attr( $aria_label ),
                esc_attr( $bg ), esc_attr( $fg ), esc_attr( $border ),
                esc_attr( $radius ), esc_attr( $font_size ),
                esc_attr( $pad_y ), esc_attr( $pad_x ),
                esc_html( $chip['label'] ),
                $count
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
                    'class'        => true,
                    'type'         => true,
                    'data-tag'     => true,
                    'aria-pressed' => true,
                    'aria-label'   => true,
                    'style'        => true,
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

            $position = $shown + 1;
            echo '<li class="ssc-result">';
            echo '<a href="' . esc_url( $post['url'] ) . '" data-sschp-result="1" data-sschp-post-id="' . esc_attr( (int) $post['id'] ) . '" data-sschp-post-type="' . esc_attr( isset( $post['post_type'] ) ? $post['post_type'] : '' ) . '" data-sschp-result-position="' . esc_attr( $position ) . '">';

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
