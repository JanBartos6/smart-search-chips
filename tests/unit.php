<?php

require_once __DIR__ . '/bootstrap.php';

$sschp_test_failures = 0;

function sschp_test_fail( $message ) {
    global $sschp_test_failures;
    $sschp_test_failures++;
    fwrite( STDERR, "FAIL: {$message}\n" );
}

function sschp_assert_true( $condition, $message ) {
    if ( ! $condition ) {
        sschp_test_fail( $message );
    }
}

function sschp_assert_same( $expected, $actual, $message ) {
    if ( $expected !== $actual ) {
        sschp_test_fail(
            $message . "\n  expected: " . var_export( $expected, true ) . "\n  actual:   " . var_export( $actual, true )
        );
    }
}

function sschp_assert_contains( $needle, $haystack, $message ) {
    if ( false === strpos( $haystack, $needle ) ) {
        sschp_test_fail( $message . "\n  missing: " . $needle );
    }
}

function sschp_run_if_class_exists( $class_name, $callback ) {
    if ( class_exists( $class_name ) ) {
        $callback();
    }
}

function sschp_make_chip_post( array $tags ) {
    return array(
        'tags'       => $tags,
        'tags_lower' => array_map( 'mb_strtolower', $tags ),
    );
}

function sschp_make_frontend_post( $post_id, array $tags ) {
    global $sschp_test_post_meta;

    $post_id = (int) $post_id;
    $sschp_test_post_meta[ $post_id ][ SSCHP_Meta_Box::META_KEY ] = implode( '; ', $tags );

    return (object) array(
        'ID'         => $post_id,
        'post_title' => 'Post ' . $post_id,
    );
}

function sschp_make_archive_query( array $posts, $args = array() ) {
    return new WP_Query(
        array_merge(
            array(
                'posts'         => $posts,
                'found_posts'   => 25,
                'max_num_pages' => 5,
                'is_search'     => true,
            ),
            $args
        )
    );
}

function sschp_register_test_product( $post_id, $attributes = array(), $sku = '', $terms = array(), $type = 'simple', $variation_prices = array() ) {
    global $sschp_test_products, $sschp_test_post_types, $sschp_test_post_terms;

    $post_id = (int) $post_id;
    $sschp_test_products[ $post_id ]   = new SSCHP_Test_Product( $attributes, $sku, $type, $variation_prices );
    $sschp_test_post_types[ $post_id ] = 'product';

    foreach ( $terms as $taxonomy => $values ) {
        $sschp_test_post_terms[ $post_id . '|' . $taxonomy ] = $values;
    }
}

function sschp_reset_test_state() {
    global $sschp_test_options, $sschp_test_settings_errors, $sschp_test_filters, $sschp_test_actions, $sschp_test_polylang_language, $sschp_test_freemius, $sschp_test_added_actions, $sschp_test_added_filters, $sschp_test_is_block_theme, $sschp_test_current_user_can, $sschp_test_current_user_calls, $sschp_test_transients, $sschp_test_nonce_valid, $sschp_test_query_vars, $sschp_test_post_meta, $sschp_test_current_url, $sschp_test_is_admin, $sschp_test_is_search, $sschp_test_products, $sschp_test_post_types, $sschp_test_post_terms, $sschp_test_post_revisions, $sschp_test_post_autosaves;

    $sschp_test_options         = array();
    $sschp_test_settings_errors = array();
    $sschp_test_filters         = array();
    $sschp_test_actions         = array();
    $sschp_test_added_actions   = array();
    $sschp_test_added_filters   = array();
    $sschp_test_polylang_language = '';
    $sschp_test_freemius        = null;
    $sschp_test_is_block_theme  = false;
    $sschp_test_current_user_can   = array();
    $sschp_test_current_user_calls = array();
    $sschp_test_transients         = array();
    $sschp_test_nonce_valid        = true;
    $sschp_test_query_vars         = array();
    $sschp_test_post_meta          = array();
    $sschp_test_current_url        = '/';
    $sschp_test_is_admin           = false;
    $sschp_test_is_search          = false;
    $sschp_test_products           = array();
    $sschp_test_post_types         = array();
    $sschp_test_post_terms         = array();
    $sschp_test_post_revisions     = array();
    $sschp_test_post_autosaves     = array();
    $_POST                    = array();
    $_REQUEST                 = array();
    $_SERVER                  = array();
}

function sschp_invoke_private( $object, $method_name, array $args = array() ) {
    $reflection = new ReflectionMethod( $object, $method_name );
    $reflection->setAccessible( true );

    return $reflection->invokeArgs( is_object( $object ) ? $object : null, $args );
}

function sschp_capture_wp_die( $callback ) {
    ob_start();

    try {
        $callback();
        $output = ob_get_clean();

        return array(
            'died'    => false,
            'message' => '',
            'output'  => $output,
        );
    } catch ( RuntimeException $e ) {
        $output = ob_get_clean();

        return array(
            'died'    => true,
            'message' => $e->getMessage(),
            'output'  => $output,
        );
    }
}

function sschp_test_chip_engine_extract_tags() {
    $engine = new SSCHP_Chip_Engine();
    $tags   = $engine->extract_tags( 'Steel; IP65; steel', 'Outdoor, CE, outdoor' );

    sschp_assert_same(
        array( 'Steel', 'IP65', 'Outdoor', 'CE' ),
        $tags['tags'],
        'extract_tags should merge primary and extra tags while deduplicating case-insensitively'
    );
}

function sschp_test_chip_engine_extract_tags_supports_comma_delimited_primary_field() {
    $engine = new SSCHP_Chip_Engine();
    $tags   = $engine->extract_tags( 'spring handle, stainless steel, spring handle', '' );

    sschp_assert_same(
        array( 'spring handle', 'stainless steel' ),
        $tags['tags'],
        'extract_tags should split comma-delimited primary field values used by the built-in sschp_tags field'
    );
}

function sschp_test_chip_engine_generate_chips() {
    $engine = new SSCHP_Chip_Engine();
    $posts  = array(
        array( 'tags' => array( 'Shared', 'Alpha', 'Outdoor' ), 'tags_lower' => array( 'shared', 'alpha', 'outdoor' ) ),
        array( 'tags' => array( 'Shared', 'Alpha', 'Indoor' ), 'tags_lower' => array( 'shared', 'alpha', 'indoor' ) ),
        array( 'tags' => array( 'Shared', 'Beta', 'Outdoor' ), 'tags_lower' => array( 'shared', 'beta', 'outdoor' ) ),
        array( 'tags' => array( 'Shared', 'Beta', 'Indoor' ), 'tags_lower' => array( 'shared', 'beta', 'indoor' ) ),
    );

    $chips = $engine->generate_chips( $posts, array(), 8, 40 );

    sschp_assert_true( ! isset( $chips['shared'] ), 'generate_chips should skip tags present in every result' );
    sschp_assert_true( isset( $chips['alpha'] ), 'generate_chips should keep useful differentiating tags' );
    sschp_assert_true( isset( $chips['beta'] ), 'generate_chips should keep useful differentiating tags from the other half of the set' );
}

function sschp_test_chip_engine_is_tag_active_case_insensitive() {
    $engine = new SSCHP_Chip_Engine();

    sschp_assert_true(
        $engine->is_tag_active( 'Spring Handle', array( 'spring handle' ) ),
        'lower-case active tag should match a mixed-case stored chip value'
    );
    sschp_assert_true(
        $engine->is_tag_active( 'spring handle', array( 'Spring Handle' ) ),
        'mixed-case active tag should match a lower-case stored chip value'
    );
}

function sschp_test_chip_engine_active_fallback_preserves_stored_label_case() {
    sschp_reset_test_state();

    $engine = new SSCHP_Chip_Engine();
    $posts  = array(
        sschp_make_chip_post( array( 'Spring Handle', 'Shared' ) ),
        sschp_make_chip_post( array( 'Spring Handle', 'Shared' ) ),
    );

    $chips = $engine->generate_chips( $posts, array( 'spring handle' ), 8, 40 );

    sschp_assert_true( isset( $chips['spring handle'] ), 'case-insensitive active fallback should restore the stored chip' );
    sschp_assert_same( 'Spring Handle', $chips['spring handle']['label'], 'active fallback should preserve the stored label case for display' );
    sschp_assert_same( 'Spring Handle', $chips['spring handle']['value'], 'active fallback should preserve the stored value case for URLs' );
}

function sschp_test_chip_engine_small_result_sets_show_one_off_tags() {
    sschp_reset_test_state();

    $engine = new SSCHP_Chip_Engine();
    $posts  = array(
        sschp_make_chip_post( array( 'Shared', 'Alpha' ) ),
        sschp_make_chip_post( array( 'Shared', 'Beta' ) ),
        sschp_make_chip_post( array( 'Shared', 'Gamma' ) ),
    );

    $chips = $engine->generate_chips( $posts, array(), 8, 40 );

    sschp_assert_true( isset( $chips['alpha'] ), 'small result sets should show useful one-off differentiating tags' );
    sschp_assert_true( isset( $chips['beta'] ), 'small result sets should show multiple useful one-off differentiating tags' );
    sschp_assert_true( isset( $chips['gamma'] ), 'small result sets should not look empty when every useful tag is unique' );
    sschp_assert_true( ! isset( $chips['shared'] ), 'tags present in every small-result item should still be hidden' );
}

function sschp_test_chip_engine_large_result_sets_hide_one_off_tags() {
    sschp_reset_test_state();

    $engine = new SSCHP_Chip_Engine();
    $posts  = array();

    for ( $i = 1; $i <= 10; $i++ ) {
        $tags = array( 'Shared', $i <= 5 ? 'Group A' : 'Group B' );
        if ( 1 === $i ) {
            $tags[] = 'Unique One';
        }
        $posts[] = sschp_make_chip_post( $tags );
    }

    $chips = $engine->generate_chips( $posts, array(), 8, 40 );

    sschp_assert_true( ! isset( $chips['unique one'] ), 'large result sets should hide one-off tags by default' );
    sschp_assert_true( isset( $chips['group a'] ), 'large result sets should still show repeated differentiating tags' );
    sschp_assert_true( isset( $chips['group b'] ), 'large result sets should still show repeated differentiating tags from another subset' );
    sschp_assert_true( ! isset( $chips['shared'] ), 'tags present in every large-result item should still be hidden' );
}

function sschp_test_chip_engine_min_count_filter_can_force_two() {
    global $sschp_test_filters;

    sschp_reset_test_state();
    $sschp_test_filters['sschp_min_chip_count'] = 2;

    $engine = new SSCHP_Chip_Engine();
    $posts  = array(
        sschp_make_chip_post( array( 'Shared', 'Pair', 'Alpha' ) ),
        sschp_make_chip_post( array( 'Shared', 'Pair', 'Beta' ) ),
        sschp_make_chip_post( array( 'Shared', 'Gamma' ) ),
    );

    $chips = $engine->generate_chips( $posts, array(), 8, 40 );

    sschp_assert_true( isset( $chips['pair'] ), 'min-count filter should allow repeated tags when forcing count 2' );
    sschp_assert_true( ! isset( $chips['alpha'] ), 'min-count filter should hide one-off tags when forcing count 2' );
    sschp_assert_true( ! isset( $chips['gamma'] ), 'min-count filter should hide all one-off tags when forcing count 2' );

    sschp_reset_test_state();
}

function sschp_test_chip_engine_min_count_filter_can_force_one() {
    global $sschp_test_filters;

    sschp_reset_test_state();
    $sschp_test_filters['sschp_min_chip_count'] = 1;

    $engine = new SSCHP_Chip_Engine();
    $posts  = array();

    for ( $i = 1; $i <= 10; $i++ ) {
        $tags = array( 'Shared', $i <= 5 ? 'Group A' : 'Group B' );
        if ( 1 === $i ) {
            $tags[] = 'Unique One';
        }
        $posts[] = sschp_make_chip_post( $tags );
    }

    $chips = $engine->generate_chips( $posts, array(), 8, 40 );

    sschp_assert_true( isset( $chips['unique one'] ), 'min-count filter should be able to force one-off tags to appear in larger result sets' );
    sschp_assert_true( ! isset( $chips['shared'] ), 'min-count filter should not bypass all-results redundancy elimination' );

    sschp_reset_test_state();
}

function sschp_test_chip_engine_all_results_tag_hidden_unless_active() {
    sschp_reset_test_state();

    $engine = new SSCHP_Chip_Engine();
    $posts  = array(
        sschp_make_chip_post( array( 'Shared', 'Alpha' ) ),
        sschp_make_chip_post( array( 'Shared', 'Beta' ) ),
        sschp_make_chip_post( array( 'Shared', 'Gamma' ) ),
    );

    $inactive_chips = $engine->generate_chips( $posts, array(), 8, 40 );
    $active_chips   = $engine->generate_chips( $posts, array( 'Shared' ), 8, 40 );

    sschp_assert_true( ! isset( $inactive_chips['shared'] ), 'all-results tags should be hidden when inactive' );
    sschp_assert_true( isset( $active_chips['shared'] ), 'active all-results tags should be restored so users can see and remove them' );
    sschp_assert_same( 3, $active_chips['shared']['count'], 'active all-results tags should preserve their real count' );
}

function sschp_test_chip_engine_active_tag_survives() {
    $engine = new SSCHP_Chip_Engine();
    $posts  = array(
        array( 'tags' => array( 'Steel', 'Outdoor' ), 'tags_lower' => array( 'steel', 'outdoor' ) ),
        array( 'tags' => array( 'Steel', 'Indoor' ), 'tags_lower' => array( 'steel', 'indoor' ) ),
    );

    $chips = $engine->generate_chips( $posts, array( 'Nonexistent Tag' ), 5, 40 );

    sschp_assert_true( isset( $chips['nonexistent tag'] ), 'generate_chips should preserve an active tag even if it would not normally qualify' );
    sschp_assert_same( 'Nonexistent Tag', $chips['nonexistent tag']['label'], 'active tags should preserve their original label in the chip output' );
}

function sschp_test_chip_engine_active_tags_are_ordered_first() {
    $engine = new SSCHP_Chip_Engine();
    $posts  = array(
        array( 'tags' => array( 'Shared', 'Alpha', 'Outdoor' ), 'tags_lower' => array( 'shared', 'alpha', 'outdoor' ) ),
        array( 'tags' => array( 'Shared', 'Alpha', 'Indoor' ), 'tags_lower' => array( 'shared', 'alpha', 'indoor' ) ),
        array( 'tags' => array( 'Shared', 'Beta', 'Outdoor' ), 'tags_lower' => array( 'shared', 'beta', 'outdoor' ) ),
        array( 'tags' => array( 'Shared', 'Beta', 'Indoor' ), 'tags_lower' => array( 'shared', 'beta', 'indoor' ) ),
    );

    $chips = $engine->generate_chips( $posts, array( 'Outdoor' ), 8, 40 );
    $keys  = array_keys( $chips );

    sschp_assert_same( 'outdoor', $keys[0], 'active chips should be ordered before inactive chips' );
}

function sschp_test_chip_engine_filter_by_tags() {
    $engine = new SSCHP_Chip_Engine();
    $posts  = array(
        array( 'id' => 1, 'tags_lower' => array( 'steel', 'outdoor' ) ),
        array( 'id' => 2, 'tags_lower' => array( 'steel', 'indoor' ) ),
        array( 'id' => 3, 'tags_lower' => array( 'plastic', 'outdoor' ) ),
    );

    $filtered = $engine->filter_by_tags( $posts, array( 'Steel', 'Outdoor' ) );

    sschp_assert_same( 1, count( $filtered ), 'filter_by_tags should require all active tags to be present' );
    sschp_assert_same( 1, $filtered[0]['id'], 'filter_by_tags should keep the matching item' );
}

function sschp_test_search_handler_normalizes_active_tags_case_insensitively() {
    $handler = new SSCHP_Search_Handler( new SSCHP_Chip_Engine(), new SSCHP_Test_Settings() );

    sschp_assert_same(
        array( 'Spring Handle', 'Outdoor' ),
        sschp_invoke_private( $handler, 'normalize_active_tags', array( array( 'Spring Handle', 'spring handle', 'Outdoor' ) ) ),
        'AJAX active tags should be deduplicated case-insensitively while preserving first labels'
    );
}

function sschp_test_search_handler_render_chips_marks_case_insensitive_active_tag() {
    $handler = new SSCHP_Search_Handler( new SSCHP_Chip_Engine(), new SSCHP_Test_Settings() );
    $chips   = array(
        'spring handle' => array(
            'label' => 'Spring Handle',
            'value' => 'Spring Handle',
            'count' => 2,
        ),
    );

    ob_start();
    sschp_invoke_private( $handler, 'render_chips', array( $chips, array( 'spring handle' ) ) );
    $markup = ob_get_clean();

    sschp_assert_contains( 'ssc-chip--active', $markup, 'AJAX chip rendering should mark mixed-case chips active from lower-case active values' );
    sschp_assert_contains( 'data-tag="Spring Handle"', $markup, 'AJAX chip rendering should preserve the stored chip value case' );
}

function sschp_test_frontend_archive_filter_url_toggles_case_insensitively() {
    $frontend = new SSCHP_Frontend( new SSCHP_Test_Settings(), new SSCHP_Chip_Engine() );
    $url      = sschp_invoke_private( $frontend, 'build_archive_filter_url', array( 'Spring Handle', array( 'spring handle' ) ) );
    $query    = array();

    parse_str( (string) parse_url( $url, PHP_URL_QUERY ), $query );

    sschp_assert_true( ! isset( $query['sschp_tags'] ), 'archive URL toggling should remove an active chip case-insensitively instead of adding a duplicate' );
}

function sschp_test_frontend_archive_filters_current_page_posts() {
    global $sschp_test_query_vars;

    sschp_reset_test_state();
    $sschp_test_query_vars['sschp_tags'] = 'Steel';

    $posts = array(
        sschp_make_frontend_post( 1, array( 'Steel', 'Outdoor' ) ),
        sschp_make_frontend_post( 2, array( 'Steel', 'Indoor' ) ),
        sschp_make_frontend_post( 3, array( 'Plastic', 'Outdoor' ) ),
    );
    $query = sschp_make_archive_query( $posts );

    $frontend = new SSCHP_Frontend( new SSCHP_Test_Settings(), new SSCHP_Chip_Engine() );
    $filtered = $frontend->filter_archive_posts( $posts, $query );

    sschp_assert_same( array( 1, 2 ), wp_list_pluck( $filtered, 'ID' ), 'active archive chips should filter only the currently loaded posts' );
    sschp_assert_same( 2, $query->post_count, 'filtered archive query should expose the current-page result count' );
    sschp_assert_same( 2, $query->found_posts, 'filtered archive query should not keep the original full-query found count' );
    sschp_assert_same( 1, $query->max_num_pages, 'filtered archive query should collapse stale pagination' );
}

function sschp_test_frontend_archive_filters_multiple_tags_with_and_logic() {
    global $sschp_test_query_vars;

    sschp_reset_test_state();
    $sschp_test_query_vars['sschp_tags'] = 'Steel,Outdoor';

    $posts = array(
        sschp_make_frontend_post( 1, array( 'Steel', 'Outdoor' ) ),
        sschp_make_frontend_post( 2, array( 'Steel', 'Indoor' ) ),
        sschp_make_frontend_post( 3, array( 'Plastic', 'Outdoor' ) ),
    );
    $query = sschp_make_archive_query( $posts );

    $frontend = new SSCHP_Frontend( new SSCHP_Test_Settings(), new SSCHP_Chip_Engine() );
    $filtered = $frontend->filter_archive_posts( $posts, $query );

    sschp_assert_same( array( 1 ), wp_list_pluck( $filtered, 'ID' ), 'multiple active archive chips should use AND filtering' );
    sschp_assert_same( array( 1 ), wp_list_pluck( $query->posts, 'ID' ), 'query posts should match the AND-filtered current page' );
}

function sschp_test_frontend_archive_chip_toggle_removes_pagination_params() {
    global $sschp_test_current_url;

    sschp_reset_test_state();
    $sschp_test_current_url = '/shop/?s=beacon&paged=3&product-page=2';

    $frontend = new SSCHP_Frontend( new SSCHP_Test_Settings(), new SSCHP_Chip_Engine() );
    $url      = sschp_invoke_private( $frontend, 'build_archive_filter_url', array( 'Outdoor', array() ) );
    $query    = array();

    parse_str( (string) parse_url( $url, PHP_URL_QUERY ), $query );

    sschp_assert_same( 'beacon', $query['s'], 'archive chip URL should preserve the search query' );
    sschp_assert_same( 'Outdoor', $query['sschp_tags'], 'archive chip URL should add the selected chip tag' );
    sschp_assert_true( ! isset( $query['paged'] ), 'archive chip URL should remove normal pagination params' );
    sschp_assert_true( ! isset( $query['product-page'] ), 'archive chip URL should remove WooCommerce pagination params' );
}

function sschp_test_frontend_archive_clear_filters_restores_url_without_chip_params() {
    global $sschp_test_current_url;

    sschp_reset_test_state();
    $sschp_test_current_url = '/shop/?s=beacon&sschp_tags=Outdoor&paged=3&product-page=2';

    $frontend = new SSCHP_Frontend( new SSCHP_Test_Settings(), new SSCHP_Chip_Engine() );
    $url      = sschp_invoke_private( $frontend, 'build_archive_filter_url', array( null, array() ) );
    $query    = array();

    parse_str( (string) parse_url( $url, PHP_URL_QUERY ), $query );

    sschp_assert_same( 'beacon', $query['s'], 'clear filters URL should preserve the search query' );
    sschp_assert_true( ! isset( $query['sschp_tags'] ), 'clear filters URL should remove active chip params' );
    sschp_assert_true( ! isset( $query['paged'] ), 'clear filters URL should remove normal pagination params' );
    sschp_assert_true( ! isset( $query['product-page'] ), 'clear filters URL should remove WooCommerce pagination params' );
}

function sschp_test_frontend_archive_markup_describes_current_page_scope() {
    sschp_reset_test_state();

    $posts = array(
        sschp_make_frontend_post( 1, array( 'Alpha', 'Shared' ) ),
        sschp_make_frontend_post( 2, array( 'Beta', 'Shared' ) ),
        sschp_make_frontend_post( 3, array( 'Gamma', 'Shared' ) ),
    );
    $query = sschp_make_archive_query( $posts );

    $frontend = new SSCHP_Frontend( new SSCHP_Test_Settings(), new SSCHP_Chip_Engine() );
    $markup   = sschp_invoke_private( $frontend, 'get_archive_chips_markup', array( $query ) );

    sschp_assert_contains( 'currently loaded on this page', $markup, 'archive chip markup should describe current-page filtering scope' );
}

function sschp_test_frontend_woocommerce_and_loop_do_not_render_archive_chips_twice() {
    global $wp_query;

    sschp_reset_test_state();

    $posts = array(
        sschp_make_frontend_post( 1, array( 'Alpha', 'Shared' ) ),
        sschp_make_frontend_post( 2, array( 'Beta', 'Shared' ) ),
        sschp_make_frontend_post( 3, array( 'Gamma', 'Shared' ) ),
    );
    $wp_query = sschp_make_archive_query( $posts, array( 'is_post_type_archive' => true, 'is_search' => false ) );

    $frontend = new SSCHP_Frontend( new SSCHP_Test_Settings(), new SSCHP_Chip_Engine() );

    ob_start();
    $frontend->render_woocommerce_loop_chips();
    $woocommerce_markup = ob_get_clean();

    ob_start();
    $frontend->render_archive_chips_before_loop( $wp_query );
    $loop_markup = ob_get_clean();

    sschp_assert_contains( 'ssc-loop-filters', $woocommerce_markup, 'WooCommerce loop hook should render archive chips once' );
    sschp_assert_same( '', $loop_markup, 'loop_start fallback should not duplicate chips after WooCommerce rendering' );
}

function sschp_test_frontend_block_theme_and_loop_do_not_render_archive_chips_twice() {
    global $wp_query;

    sschp_reset_test_state();

    $posts = array(
        sschp_make_frontend_post( 1, array( 'Alpha', 'Shared' ) ),
        sschp_make_frontend_post( 2, array( 'Beta', 'Shared' ) ),
        sschp_make_frontend_post( 3, array( 'Gamma', 'Shared' ) ),
    );
    $wp_query = sschp_make_archive_query( $posts );

    $frontend = new SSCHP_Frontend( new SSCHP_Test_Settings(), new SSCHP_Chip_Engine() );
    $content  = $frontend->inject_archive_chips_into_query_block(
        '<main class="wp-block-query">CONTENT</main>',
        array(
            'blockName' => 'core/query',
            'attrs'     => array(
                'query' => array(
                    'inherit' => true,
                ),
            ),
        )
    );

    ob_start();
    $frontend->render_archive_chips_before_loop( $wp_query );
    $loop_markup = ob_get_clean();

    sschp_assert_contains( 'ssc-loop-filters', $content, 'block theme query injection should render archive chips once' );
    sschp_assert_contains( 'CONTENT', $content, 'block theme query injection should preserve the original block content' );
    sschp_assert_same( '', $loop_markup, 'loop_start fallback should not duplicate chips after block query injection' );
}

function sschp_test_license_is_pro_false_without_freemius() {
    sschp_reset_test_state();

    sschp_assert_same( false, SSCHP_License::is_pro(), 'is_pro should safely return false when Freemius is unavailable' );
}

function sschp_test_settings_numeric_sanitizers() {
    $settings = new SSCHP_Settings();

    sschp_assert_same( 16, $settings->sanitize_chip_radius( '' ), 'empty chip radius should fall back to the default' );
    sschp_assert_same( 24, $settings->sanitize_chip_font_size( 99 ), 'chip font size should clamp to the configured maximum' );
    sschp_assert_same( 0, $settings->sanitize_chip_padding_x( -10 ), 'chip horizontal padding should clamp to zero' );
    sschp_assert_same( 24, $settings->sanitize_chip_padding_y( 24 ), 'chip vertical padding should keep valid values unchanged' );
}

function sschp_test_settings_reserved_custom_field_rejected() {
    global $sschp_test_settings_errors;

    sschp_reset_test_state();

    $settings = new SSCHP_Settings();
    $result   = $settings->sanitize_custom_tag_field( 'sschp_tags' );

    sschp_assert_same( '', $result, 'reserved built-in field name should be rejected for custom tag field mapping' );
    sschp_assert_true( ! empty( $sschp_test_settings_errors ), 'rejecting a reserved custom field should register a settings error' );
}

function sschp_test_settings_duplicate_field_rejected() {
    global $sschp_test_settings_errors, $sschp_test_options;

    sschp_reset_test_state();
    $sschp_test_options['sschp_tag_fields'] = 'manual_tags';
    $_POST['sschp_tag_fields']            = 'manual_tags';

    $settings = new SSCHP_Settings();
    $result   = $settings->sanitize_extra_tag_field( 'manual_tags' );

    sschp_assert_same( '', $result, 'extra tag field should reject the same key already used by the custom tag field' );
    sschp_assert_true( ! empty( $sschp_test_settings_errors ), 'duplicate mapped field names should register a settings error' );
}

function sschp_test_settings_valid_selector_is_preserved() {
    sschp_reset_test_state();

    $settings = new SSCHP_Settings();
    $selector = '.site-header .search-field, form[data-search="products"] input[name="query"]';

    sschp_assert_same(
        $selector,
        $settings->sanitize_search_input_selector( $selector ),
        'valid custom search input selector should be preserved'
    );
}

function sschp_test_settings_invalid_selector_keeps_previous_value() {
    global $sschp_test_settings_errors, $sschp_test_options;

    sschp_reset_test_state();
    $sschp_test_options['sschp_search_input_selector'] = '.previous-search-input';

    $settings = new SSCHP_Settings();
    $result   = $settings->sanitize_search_input_selector( '.broken[' );

    sschp_assert_same( '.previous-search-input', $result, 'invalid search input selector should keep the previous valid value' );
    sschp_assert_true( ! empty( $sschp_test_settings_errors ), 'invalid search input selector should register a settings error' );
}

function sschp_test_settings_invalid_selector_falls_back_to_default() {
    global $sschp_test_settings_errors, $sschp_test_options;

    sschp_reset_test_state();
    $sschp_test_options['sschp_result_container_selector'] = '.still-broken[';

    $settings = new SSCHP_Settings();
    $result   = $settings->sanitize_result_container_selector( '' );

    sschp_assert_same(
        '.js-ssc-results, .js-search__results, .ssc-search__results',
        $result,
        'invalid result container selector should fall back to the default if the previous value is invalid'
    );
    sschp_assert_true( ! empty( $sschp_test_settings_errors ), 'invalid result container selector should register a settings error' );
}

function sschp_test_settings_theme_compatibility_diagnostics_report_invalid_selector() {
    global $sschp_test_options;

    sschp_reset_test_state();
    $sschp_test_options['sschp_search_input_selector']      = '.broken[';
    $sschp_test_options['sschp_result_container_selector']  = '.theme-results';
    $sschp_test_options['sschp_override_ajax']              = 'search_processing';
    $sschp_test_options['sschp_ajax_keyword_field']         = 'keyword';

    $settings    = new SSCHP_Settings();
    $diagnostics = $settings->get_theme_compatibility_diagnostics();

    sschp_assert_same( false, $diagnostics['inputSelectorValid'], 'theme compatibility diagnostics should flag invalid input selectors' );
    sschp_assert_same( true, $diagnostics['resultSelectorValid'], 'theme compatibility diagnostics should pass valid result selectors' );
    sschp_assert_same( true, $diagnostics['ajaxActionConfigured'], 'theme compatibility diagnostics should report configured AJAX actions' );
}

function sschp_test_settings_theme_compatibility_diagnostics_report_valid_defaults() {
    sschp_reset_test_state();

    $settings    = new SSCHP_Settings();
    $diagnostics = $settings->get_theme_compatibility_diagnostics();

    sschp_assert_same( true, $diagnostics['inputSelectorValid'], 'default input selector should be diagnostic-valid' );
    sschp_assert_same( true, $diagnostics['resultSelectorValid'], 'default result selector should be diagnostic-valid' );
    sschp_assert_same( true, $diagnostics['defaultInputValid'], 'diagnostics should confirm the default input selector is valid' );
    sschp_assert_same( true, $diagnostics['defaultResultValid'], 'diagnostics should confirm the default result selector is valid' );
    sschp_assert_same( 'sschp_search', $diagnostics['ajaxActionLabel'], 'diagnostics should show the built-in AJAX action when no override is set' );
    sschp_assert_same( true, $diagnostics['shortcodeAvailable'], 'diagnostics should report shortcode availability in the lightweight test context' );
}

function sschp_test_ai_prompt_manual_mode() {
    $tagger = new SSCHP_AI_Tagger( new SSCHP_Test_Settings() );
    $posts  = array(
        (object) array( 'ID' => 12, 'post_type' => 'product', 'post_title' => 'Trapped Key Alpha' ),
        (object) array( 'ID' => 13, 'post_type' => 'product', 'post_title' => 'Trapped Key Beta' ),
    );
    $config = array(
        'custom_instruction' => 'Prefer engineering terminology.',
    );
    $summary = array(
        'restEnabled'       => false,
        'targetMetaKey'     => 'sschp_tags',
        'targetFieldLabel'  => 'Built-in Search Chips Tags',
        'customReadFields'  => array( 'material', 'compatibility' ),
        'maxTags'           => 0,
        'routes'            => array(),
    );

    $prompt = sschp_invoke_private( $tagger, 'build_agent_prompt', array( $posts, $config, $summary ) );

    sschp_assert_contains( 'manual review', $prompt, 'manual-mode prompt should explicitly describe manual review output' );
    sschp_assert_contains( 'There is no hard tag limit', $prompt, 'manual-mode prompt should explain the no-limit max tag case' );
    sschp_assert_contains( 'material, compatibility', $prompt, 'manual-mode prompt should include extra custom fields when provided' );
    sschp_assert_true( false === strpos( $prompt, 'WordPress Application Password' ), 'manual-mode prompt should not ask for REST credentials' );
}

function sschp_test_ai_prompt_rest_mode() {
    $tagger = new SSCHP_AI_Tagger( new SSCHP_Test_Settings() );
    $posts  = array(
        (object) array( 'ID' => 22, 'post_type' => 'product', 'post_title' => 'Trapped Key Gamma' ),
    );
    $config = array(
        'custom_instruction' => '',
    );
    $summary = array(
        'restEnabled'       => true,
        'targetMetaKey'     => 'sschp_tags',
        'targetFieldLabel'  => 'Built-in Search Chips Tags',
        'customReadFields'  => array(),
        'maxTags'           => 5,
        'routes'            => array(
            array(
                'id'    => 22,
                'route' => 'http://example.test/wp-json/wp/v2/product/22',
            ),
        ),
    );

    $prompt = sschp_invoke_private( $tagger, 'build_agent_prompt', array( $posts, $config, $summary ) );

    sschp_assert_contains( 'write the final values back to WordPress', $prompt, 'REST-mode prompt should describe write-back behavior' );
    sschp_assert_contains( '"sschp_tags": "Tag one; Tag two; Tag three"', $prompt, 'REST-mode prompt should include the meta payload example' );
    sschp_assert_contains( 'WordPress Application Password: <PASTE HERE>', $prompt, 'REST-mode prompt should include the WordPress credential placeholders' );
    sschp_assert_contains( 'REST route: http://example.test/wp-json/wp/v2/product/22', $prompt, 'REST-mode prompt should include the selected item route' );
}

function sschp_test_language_request_prefers_explicit_request_value() {
    sschp_reset_test_state();

    sschp_assert_same(
        'cs',
        SSCHP_Language::request_language( array( 'sschp_lang' => 'cs' ) ),
        'request_language should prefer the explicit request language when present'
    );
}

function sschp_test_language_detects_polylang_slug() {
    global $sschp_test_polylang_language;

    sschp_reset_test_state();
    $sschp_test_polylang_language = 'de';

    sschp_assert_same( 'de', SSCHP_Language::current_language(), 'current_language should use the active Polylang language slug' );
}

function sschp_test_language_applies_wpml_query_args() {
    global $sschp_test_filters, $sschp_test_actions;

    sschp_reset_test_state();
    $sschp_test_filters['wpml_current_language'] = 'fr';
    $sschp_test_filters['wpml_switch_language']  = true;

    $args = SSCHP_Language::apply_query_language( array( 'post_type' => 'post' ) );
    SSCHP_Language::switch_ajax_language( 'fr' );

    sschp_assert_same( 'fr', $args['lang'], 'apply_query_language should attach the current WPML language slug' );
    sschp_assert_same( false, $args['suppress_filters'], 'apply_query_language should keep WPML query filters enabled' );
    sschp_assert_same( 'wpml_switch_language', $sschp_test_actions[0]['hook'], 'switch_ajax_language should trigger the WPML language switch action' );
    sschp_assert_same( 'fr', $sschp_test_actions[0]['value'], 'switch_ajax_language should switch to the requested language' );
}

function sschp_test_analytics_sanitizes_chip_impression_labels() {
    $labels = sschp_invoke_private( 'SSCHP_Analytics', 'sanitize_chip_labels', array( array( ' spring handle ', 'spring handle', '', 'IP67' ) ) );

    sschp_assert_same( array( 'spring handle', 'IP67' ), $labels, 'sanitize_chip_labels should trim, deduplicate, and discard empty impression labels' );
}

function sschp_test_analytics_appends_ctr_percentages() {
    $rows = sschp_invoke_private( 'SSCHP_Analytics', 'append_percentage_to_rows', array(
        array(
            array(
                'chip_label'  => 'spring handle',
                'clicks'      => 3,
                'impressions' => 12,
            ),
        ),
        'clicks',
        'impressions',
        'click_through_rate',
    ) );

    sschp_assert_same( 25.0, $rows[0]['click_through_rate'], 'append_percentage_to_rows should compute percentage metrics with one decimal place' );
}

function sschp_test_analytics_escape_csv_value_neutralizes_spreadsheet_formulas() {
    $escaped_formula = sschp_invoke_private( 'SSCHP_Analytics', 'escape_csv_value', array( '=SUM(1,1)' ) );
    $escaped_spaced  = sschp_invoke_private( 'SSCHP_Analytics', 'escape_csv_value', array( '  @calc' ) );
    $safe_value      = sschp_invoke_private( 'SSCHP_Analytics', 'escape_csv_value', array( 'stoneware' ) );

    sschp_assert_same( "'=SUM(1,1)", $escaped_formula, 'escape_csv_value should prefix formula-like cells with a quote' );
    sschp_assert_same( "'  @calc", $escaped_spaced, 'escape_csv_value should also neutralize formula prefixes after whitespace' );
    sschp_assert_same( 'stoneware', $safe_value, 'escape_csv_value should leave normal strings untouched' );
}

function sschp_test_analytics_guard_tracking_rejects_immediate_replays() {
    sschp_reset_test_state();

    $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
    $payload                = array(
        'visitor_id'       => 'ssc-demo-visitor',
        'event_type'       => 'search',
        'language_code'    => 'en',
        'search_query'     => 'tea',
        'chip_label'       => '',
        'page_url'         => 'https://example.test/search/',
        'active_tag_count' => 0,
        'chip_labels'      => array(),
    );

    $first  = sschp_invoke_private( 'SSCHP_Analytics', 'guard_tracking_request', array( $payload ) );
    $second = sschp_invoke_private( 'SSCHP_Analytics', 'guard_tracking_request', array( $payload ) );

    sschp_assert_same( 'track', $first, 'guard_tracking_request should allow the first analytics event through' );
    sschp_assert_same( 'duplicate', $second, 'guard_tracking_request should reject immediate duplicate analytics events' );
}

function sschp_test_meta_auth_callback_requires_edit_post_capability() {
    global $sschp_test_current_user_can, $sschp_test_current_user_calls;

    sschp_reset_test_state();
    $sschp_test_current_user_can['edit_post|42'] = true;

    sschp_assert_true( sschp_can_edit_post_meta( false, 'sschp_tags', 42 ), 'sschp_can_edit_post_meta should allow access when the current user can edit the specific post' );
    sschp_assert_same( 'edit_post', $sschp_test_current_user_calls[0]['capability'], 'sschp_can_edit_post_meta should check the post-specific edit capability' );
    sschp_assert_same( array( 42 ), $sschp_test_current_user_calls[0]['args'], 'sschp_can_edit_post_meta should pass the exact post ID to current_user_can' );
    sschp_assert_true( ! sschp_can_edit_post_meta( false, 'sschp_tags', 0 ), 'sschp_can_edit_post_meta should reject requests that do not target a concrete post object' );
}

function sschp_test_woocommerce_bulk_generation_skips_uneditable_products() {
    global $sschp_test_current_user_can, $sschp_test_post_meta, $sschp_test_transients;

    sschp_reset_test_state();
    $sschp_test_current_user_can['edit_post|10'] = true;
    $sschp_test_current_user_can['edit_post|11'] = false;

    sschp_register_test_product(
        10,
        array( new SSCHP_Test_Product_Attribute( 'material', array( 'Steel' ) ) )
    );
    sschp_register_test_product(
        11,
        array( new SSCHP_Test_Product_Attribute( 'material', array( 'Plastic' ) ) )
    );

    $woo = new SSCHP_WooCommerce();
    $woo->handle_bulk_action( '/wp-admin/edit.php?post_type=product', 'sschp_generate_tags', array( '10', '11' ) );

    sschp_assert_same( 'Steel', $sschp_test_post_meta[10][SSCHP_WooCommerce::GENERATED_META_KEY], 'bulk generation should store generated tags for editable products' );
    sschp_assert_true( empty( $sschp_test_post_meta[11][SSCHP_WooCommerce::GENERATED_META_KEY] ), 'bulk generation should skip products the current user cannot edit' );
    sschp_assert_same( 1, $sschp_test_transients['sschp_tags_generated_1'], 'bulk generation count should include only products that were generated' );
}

function sschp_test_woocommerce_auto_generation_preserves_manual_tags() {
    global $sschp_test_current_user_can, $sschp_test_post_meta;

    sschp_reset_test_state();
    $sschp_test_current_user_can['edit_post|20'] = true;
    $sschp_test_post_meta[20][SSCHP_Meta_Box::META_KEY] = 'Manual Tag';

    sschp_register_test_product(
        20,
        array( new SSCHP_Test_Product_Attribute( 'material', array( 'Aluminum' ) ) )
    );

    $woo = new SSCHP_WooCommerce();
    $woo->auto_populate_tags( 20, (object) array( 'post_type' => 'product', 'post_status' => 'publish' ) );

    sschp_assert_same( 'Manual Tag', $sschp_test_post_meta[20][SSCHP_Meta_Box::META_KEY], 'WooCommerce auto generation should not overwrite manual Search Chips Tags' );
    sschp_assert_same( 'Aluminum', $sschp_test_post_meta[20][SSCHP_WooCommerce::GENERATED_META_KEY], 'WooCommerce auto generation should store generated tags separately' );
}

function sschp_test_woocommerce_generated_tags_update_when_attributes_change() {
    global $sschp_test_current_user_can, $sschp_test_post_meta;

    sschp_reset_test_state();
    $sschp_test_current_user_can['edit_post|21'] = true;

    sschp_register_test_product(
        21,
        array( new SSCHP_Test_Product_Attribute( 'material', array( 'Old Material' ) ) )
    );

    $woo = new SSCHP_WooCommerce();
    $woo->auto_populate_tags( 21, (object) array( 'post_type' => 'product', 'post_status' => 'publish' ) );

    sschp_register_test_product(
        21,
        array( new SSCHP_Test_Product_Attribute( 'material', array( 'New Material' ) ) )
    );
    $woo->auto_populate_tags( 21, (object) array( 'post_type' => 'product', 'post_status' => 'publish' ) );

    sschp_assert_same( 'New Material', $sschp_test_post_meta[21][SSCHP_WooCommerce::GENERATED_META_KEY], 'generated WooCommerce tags should be regenerated when product attributes change' );
}

function sschp_test_woocommerce_auto_generation_skips_revisions() {
    global $sschp_test_current_user_can, $sschp_test_post_meta, $sschp_test_post_revisions;

    sschp_reset_test_state();
    $sschp_test_current_user_can['edit_post|22'] = true;
    $sschp_test_post_revisions[22] = true;

    sschp_register_test_product(
        22,
        array( new SSCHP_Test_Product_Attribute( 'material', array( 'Steel' ) ) )
    );

    $woo = new SSCHP_WooCommerce();
    $woo->auto_populate_tags( 22, (object) array( 'post_type' => 'product', 'post_status' => 'publish' ) );

    sschp_assert_true( empty( $sschp_test_post_meta[22][SSCHP_WooCommerce::GENERATED_META_KEY] ), 'WooCommerce auto generation should skip post revisions' );
}

function sschp_test_woocommerce_sku_inclusion_is_disabled_by_default_and_opt_in() {
    global $sschp_test_options;

    sschp_reset_test_state();
    sschp_register_test_product(
        30,
        array( new SSCHP_Test_Product_Attribute( 'material', array( 'Steel' ) ) ),
        'SKU-123'
    );

    $woo          = new SSCHP_WooCommerce();
    $default_tags = $woo->extract_product_tags( 30 );

    sschp_assert_true( in_array( 'Steel', $default_tags, true ), 'WooCommerce extraction should include useful attribute values' );
    sschp_assert_true( ! in_array( 'SKU-123', $default_tags, true ), 'WooCommerce extraction should not include noisy SKU values by default' );

    $sschp_test_options['sschp_woo_include_sku'] = '1';
    $sku_tags = $woo->extract_product_tags( 30 );

    sschp_assert_true( in_array( 'SKU-123', $sku_tags, true ), 'WooCommerce SKU tags should be opt-in through the SKU setting' );
}

function sschp_test_woocommerce_price_range_tags_are_disabled_by_default() {
    sschp_reset_test_state();
    sschp_register_test_product(
        31,
        array( new SSCHP_Test_Product_Attribute( 'material', array( 'Steel' ) ) ),
        '',
        array(),
        'variable',
        array(
            'min' => 10,
            'max' => 20,
        )
    );

    $woo  = new SSCHP_WooCommerce();
    $tags = $woo->extract_product_tags( 31 );

    sschp_assert_true( ! in_array( '$10 - $20', $tags, true ), 'WooCommerce price range text tags should be disabled by default' );
}

function sschp_test_woocommerce_runtime_tags_merge_manual_and_generated_without_duplicates() {
    global $sschp_test_post_meta;

    sschp_reset_test_state();
    sschp_register_test_product( 40 );
    $sschp_test_post_meta[40][SSCHP_WooCommerce::GENERATED_META_KEY] = 'Steel; Outdoor; outdoor';

    $woo    = new SSCHP_WooCommerce();
    $source = $woo->add_runtime_product_tags(
        array(
            'primary' => 'Manual; Steel',
            'extra'   => 'Indoor',
        ),
        40,
        array( 'context' => 'ajax' )
    );

    sschp_assert_same( 'Manual; Steel', $source['primary'], 'runtime WooCommerce tags should preserve manual primary tags' );
    sschp_assert_same( 'Indoor; Outdoor', $source['extra'], 'runtime WooCommerce tags should merge generated tags without case-insensitive duplicates' );
}

function sschp_test_frontend_uses_configured_ajax_action() {
    $frontend = new SSCHP_Frontend(
        new SSCHP_Test_Settings(
            array(
                'override_ajax' => 'search_processing',
            )
        ),
        new SSCHP_Chip_Engine()
    );

    $action = sschp_invoke_private( $frontend, 'get_ajax_action_name' );

    sschp_assert_same( 'search_processing', $action, 'frontend should honor the configured AJAX action because theme compatibility is not Pro-gated' );
}

function sschp_test_search_handler_normalizes_active_tag_payload_shapes() {
    $handler = new SSCHP_Search_Handler( new SSCHP_Chip_Engine(), new SSCHP_Test_Settings() );

    sschp_assert_same(
        array( 'spring handle' ),
        sschp_invoke_private( $handler, 'normalize_active_tags', array( '["spring handle","spring handle",""]' ) ),
        'search handler should normalize JSON-encoded active tags and remove duplicates'
    );

    sschp_assert_same(
        array( 'Outdoor' ),
        sschp_invoke_private( $handler, 'normalize_active_tags', array( 'Outdoor' ) ),
        'search handler should normalize scalar active tag payloads'
    );

    sschp_assert_same(
        array( 'Steel', 'Outdoor' ),
        sschp_invoke_private( $handler, 'normalize_active_tags', array( array( 'Steel', array( 'Outdoor', 'Steel', '' ) ) ) ),
        'search handler should normalize nested active tag arrays and remove duplicates'
    );

    sschp_assert_same(
        array(),
        sschp_invoke_private( $handler, 'normalize_active_tags', array( '' ) ),
        'search handler should normalize empty active tag payloads to an empty array'
    );
}

function sschp_test_search_handler_builtin_action_without_nonce_fails() {
    sschp_reset_test_state();

    $_POST['action'] = 'sschp_search';

    $handler = new SSCHP_Search_Handler( new SSCHP_Chip_Engine(), new SSCHP_Test_Settings() );
    $result  = sschp_capture_wp_die( array( $handler, 'handle_search' ) );

    sschp_assert_true( $result['died'], 'built-in AJAX action without nonce should fail closed' );
    sschp_assert_contains( 'Security check failed', $result['message'], 'built-in AJAX action without nonce should show the security failure' );
}

function sschp_test_search_handler_override_without_marker_returns_early() {
    sschp_reset_test_state();

    $_POST['action']  = 'search_processing';
    $_POST['keyword'] = 'beacon';

    $handler = new SSCHP_Search_Handler(
        new SSCHP_Chip_Engine(),
        new SSCHP_Test_Settings(
            array(
                'override_ajax' => 'search_processing',
            )
        )
    );
    $result = sschp_capture_wp_die( array( $handler, 'handle_search' ) );

    sschp_assert_same( false, $result['died'], 'override AJAX action without SSC marker or nonce should not die' );
    sschp_assert_same( '', $result['output'], 'override AJAX action without SSC marker or nonce should not output anything' );
}

function sschp_test_search_handler_override_with_marker_and_valid_nonce_runs() {
    sschp_reset_test_state();

    $_POST['action']        = 'search_processing';
    $_POST['sschp_managed'] = '1';
    $_POST['sschp_nonce']   = 'valid-nonce';

    $handler = new SSCHP_Search_Handler(
        new SSCHP_Chip_Engine(),
        new SSCHP_Test_Settings(
            array(
                'override_ajax' => 'search_processing',
            )
        )
    );
    $result = sschp_capture_wp_die( array( $handler, 'handle_search' ) );

    sschp_assert_true( $result['died'], 'override AJAX action with SSC marker and valid nonce should be handled by SSC' );
    sschp_assert_same( '', $result['message'], 'valid marked override request should pass nonce verification before stopping on an empty keyword' );
}

function sschp_test_search_handler_override_with_marker_and_invalid_nonce_fails() {
    global $sschp_test_nonce_valid;

    sschp_reset_test_state();
    $sschp_test_nonce_valid = false;

    $_POST['action']        = 'search_processing';
    $_POST['sschp_managed'] = '1';
    $_POST['sschp_nonce']   = 'invalid-nonce';

    $handler = new SSCHP_Search_Handler(
        new SSCHP_Chip_Engine(),
        new SSCHP_Test_Settings(
            array(
                'override_ajax' => 'search_processing',
            )
        )
    );
    $result = sschp_capture_wp_die( array( $handler, 'handle_search' ) );

    sschp_assert_true( $result['died'], 'override AJAX action with SSC marker and invalid nonce should fail' );
    sschp_assert_contains( 'Security check failed', $result['message'], 'invalid marked override request should show the security failure' );
}

function sschp_test_frontend_registers_loop_fallback_for_block_themes() {
    global $sschp_test_added_actions, $sschp_test_added_filters, $sschp_test_is_block_theme;

    sschp_reset_test_state();
    $sschp_test_is_block_theme = true;

    $frontend = new SSCHP_Frontend( new SSCHP_Test_Settings(), new SSCHP_Chip_Engine() );
    $frontend->init();

    $action_hooks = array_column( $sschp_test_added_actions, 'hook' );
    $filter_hooks = array_column( $sschp_test_added_filters, 'hook' );

    sschp_assert_true( in_array( 'loop_start', $action_hooks, true ), 'frontend should always register the loop_start archive-chip fallback' );
    sschp_assert_true( in_array( 'render_block', $filter_hooks, true ), 'frontend should still register block-theme query injection on block themes' );
}

sschp_test_chip_engine_extract_tags();
sschp_test_chip_engine_extract_tags_supports_comma_delimited_primary_field();
sschp_test_chip_engine_generate_chips();
sschp_test_chip_engine_is_tag_active_case_insensitive();
sschp_test_chip_engine_active_fallback_preserves_stored_label_case();
sschp_test_chip_engine_small_result_sets_show_one_off_tags();
sschp_test_chip_engine_large_result_sets_hide_one_off_tags();
sschp_test_chip_engine_min_count_filter_can_force_two();
sschp_test_chip_engine_min_count_filter_can_force_one();
sschp_test_chip_engine_all_results_tag_hidden_unless_active();
sschp_test_chip_engine_active_tag_survives();
sschp_test_chip_engine_active_tags_are_ordered_first();
sschp_test_chip_engine_filter_by_tags();
sschp_test_search_handler_normalizes_active_tags_case_insensitively();
sschp_test_search_handler_render_chips_marks_case_insensitive_active_tag();
sschp_test_frontend_archive_filter_url_toggles_case_insensitively();
sschp_test_frontend_archive_filters_current_page_posts();
sschp_test_frontend_archive_filters_multiple_tags_with_and_logic();
sschp_test_frontend_archive_chip_toggle_removes_pagination_params();
sschp_test_frontend_archive_clear_filters_restores_url_without_chip_params();
sschp_test_frontend_archive_markup_describes_current_page_scope();
sschp_test_frontend_woocommerce_and_loop_do_not_render_archive_chips_twice();
sschp_test_frontend_block_theme_and_loop_do_not_render_archive_chips_twice();
sschp_test_license_is_pro_false_without_freemius();
sschp_test_settings_numeric_sanitizers();
sschp_test_settings_reserved_custom_field_rejected();
sschp_test_settings_duplicate_field_rejected();
sschp_test_settings_valid_selector_is_preserved();
sschp_test_settings_invalid_selector_keeps_previous_value();
sschp_test_settings_invalid_selector_falls_back_to_default();
sschp_test_settings_theme_compatibility_diagnostics_report_invalid_selector();
sschp_test_settings_theme_compatibility_diagnostics_report_valid_defaults();
sschp_run_if_class_exists( 'SSCHP_AI_Tagger', 'sschp_test_ai_prompt_manual_mode' );
sschp_run_if_class_exists( 'SSCHP_AI_Tagger', 'sschp_test_ai_prompt_rest_mode' );
sschp_test_language_request_prefers_explicit_request_value();
sschp_test_language_detects_polylang_slug();
sschp_test_language_applies_wpml_query_args();
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_sanitizes_chip_impression_labels' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_appends_ctr_percentages' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_escape_csv_value_neutralizes_spreadsheet_formulas' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_guard_tracking_rejects_immediate_replays' );
sschp_test_meta_auth_callback_requires_edit_post_capability();
sschp_test_woocommerce_bulk_generation_skips_uneditable_products();
sschp_test_woocommerce_auto_generation_preserves_manual_tags();
sschp_test_woocommerce_generated_tags_update_when_attributes_change();
sschp_test_woocommerce_auto_generation_skips_revisions();
sschp_test_woocommerce_sku_inclusion_is_disabled_by_default_and_opt_in();
sschp_test_woocommerce_price_range_tags_are_disabled_by_default();
sschp_test_woocommerce_runtime_tags_merge_manual_and_generated_without_duplicates();
sschp_test_frontend_uses_configured_ajax_action();
sschp_test_search_handler_normalizes_active_tag_payload_shapes();
sschp_test_search_handler_builtin_action_without_nonce_fails();
sschp_test_search_handler_override_without_marker_returns_early();
sschp_test_search_handler_override_with_marker_and_valid_nonce_runs();
sschp_test_search_handler_override_with_marker_and_invalid_nonce_fails();
sschp_test_frontend_registers_loop_fallback_for_block_themes();

if ( $sschp_test_failures > 0 ) {
    fwrite( STDERR, "\n{$sschp_test_failures} unit test(s) failed.\n" );
    exit( 1 );
}

fwrite( STDOUT, "All unit tests passed.\n" );
