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

function sschp_register_test_post_for_index( $post_id, $post_type = 'post', $title = '', $content = '', $status = 'publish', $excerpt = '' ) {
    global $sschp_test_posts, $sschp_test_post_types;

    $post_id = (int) $post_id;
    $post    = (object) array(
        'ID'           => $post_id,
        'post_type'    => $post_type,
        'post_title'   => '' !== $title ? $title : 'Post ' . $post_id,
        'post_content' => $content,
        'post_excerpt' => $excerpt,
        'post_status'  => $status,
    );

    $sschp_test_posts[ $post_id ]      = $post;
    $sschp_test_post_types[ $post_id ] = $post_type;

    return $post;
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

function sschp_test_scheduled_hooks() {
    global $sschp_test_scheduled_events;

    return array_map(
        function( $event ) {
            return is_array( $event ) && isset( $event['hook'] ) ? $event['hook'] : $event;
        },
        $sschp_test_scheduled_events
    );
}

function sschp_reset_test_state() {
    global $sschp_test_options, $sschp_test_settings_errors, $sschp_test_filters, $sschp_test_actions, $sschp_test_polylang_language, $sschp_test_freemius, $sschp_test_added_actions, $sschp_test_added_filters, $sschp_test_is_block_theme, $sschp_test_current_user_can, $sschp_test_current_user_calls, $sschp_test_transients, $sschp_test_nonce_valid, $sschp_test_query_vars, $sschp_test_post_meta, $sschp_test_current_url, $sschp_test_is_admin, $sschp_test_is_search, $sschp_test_products, $sschp_test_post_types, $sschp_test_post_terms, $sschp_test_post_revisions, $sschp_test_post_autosaves, $sschp_test_acf_fields, $sschp_test_posts, $sschp_test_index_rows, $sschp_test_db_queries, $sschp_test_db_delta_sql, $sschp_test_scheduled_events, $sschp_test_mail, $sschp_test_wp_query_count, $sschp_test_post_terms_calls;

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
    $sschp_test_acf_fields         = array();
    $sschp_test_posts              = array();
    $sschp_test_index_rows         = array();
    $sschp_test_db_queries         = array();
    $sschp_test_db_delta_sql       = array();
    $sschp_test_scheduled_events   = array();
    $sschp_test_mail               = array();
    $sschp_test_wp_query_count     = 0;
    $sschp_test_post_terms_calls   = array();
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

function sschp_test_no_legacy_public_interface_references_in_fixtures() {
    $root       = dirname( __DIR__ );
    $scan_roots = array(
        $root . '/tests',
        $root . '/marketing/ssc-cozy-demo-theme',
        $root . '/local-wp',
    );
    $forbidden = array(
        '[ssc' . '_search',
        'action=ssc' . '_search',
        'ssc' . '_tags',
        'ssc' . '_lang',
    );
    $allowed_files = array(
        realpath( __FILE__ ) => true,
    );
    $extensions = array(
        'php' => true,
        'js'  => true,
        'sh'  => true,
        'md'  => true,
        'txt' => true,
    );

    foreach ( $scan_roots as $scan_root ) {
        if ( ! is_dir( $scan_root ) ) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $scan_root, FilesystemIterator::SKIP_DOTS )
        );

        foreach ( $iterator as $file ) {
            if ( ! $file->isFile() ) {
                continue;
            }

            $path = $file->getPathname();
            if ( isset( $allowed_files[ realpath( $path ) ] ) ) {
                continue;
            }

            $extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
            if ( ! isset( $extensions[ $extension ] ) ) {
                continue;
            }

            $contents = file_get_contents( $path );
            if ( false === $contents ) {
                continue;
            }

            foreach ( $forbidden as $needle ) {
                sschp_assert_true(
                    false === strpos( $contents, $needle ),
                    'tracked fixtures should not use legacy public interface reference "' . $needle . '" in ' . str_replace( $root . DIRECTORY_SEPARATOR, '', $path )
                );
            }
        }
    }
}

function sschp_test_wporg_settings_source_keeps_selector_safety() {
    $path = dirname( __DIR__ ) . '/build-sources/wporg/includes/class-settings.php';

    sschp_assert_true( is_file( $path ), 'WP.org settings source should exist for explicit release builds' );

    $contents = is_file( $path ) ? file_get_contents( $path ) : '';

    sschp_assert_contains( 'sanitize_search_input_selector', $contents, 'WP.org settings source should validate custom input selectors' );
    sschp_assert_contains( 'sanitize_result_container_selector', $contents, 'WP.org settings source should validate custom result selectors' );
    sschp_assert_contains( 'get_theme_compatibility_diagnostics', $contents, 'WP.org settings source should keep the compatibility diagnostics card' );
    sschp_assert_contains( 'get_setup_wizard_data', $contents, 'WP.org settings source should keep the first-run setup wizard data' );
    sschp_assert_contains( 'render_search_index_card', $contents, 'WP.org settings source should show local search index progress' );
    sschp_assert_contains( 'sschp-run-compat-test', $contents, 'WP.org settings source should render the frontend compatibility test control' );
    sschp_assert_contains( 'sanitize_debounce_ms', $contents, 'WP.org settings source should keep the configurable debounce setting' );
}

function sschp_test_premium_ajax_numeric_inputs_are_unslashed() {
    $root  = dirname( __DIR__ );
    $files = array(
        $root . '/includes/class-ai-tagger__premium_only.php',
        $root . '/includes/class-analytics__premium_only.php',
    );
    $forbidden = array(
        'absint( $_' . 'POST',
        'intval( $_' . 'POST',
        'is_array( $_' . 'POST',
        '! empty( $_' . 'POST',
    );

    foreach ( $files as $path ) {
        $contents = is_file( $path ) ? file_get_contents( $path ) : '';

        foreach ( $forbidden as $needle ) {
            sschp_assert_true(
                false === strpos( $contents, $needle ),
                basename( $path ) . ' should unslash and sanitize AJAX numeric/checkbox inputs before casting'
            );
        }
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

function sschp_test_field_normalizer_handles_acf_checkbox_array() {
    $value = SSCHP_Field_Normalizer::normalize( array( ' Waterproof ', 'Dimmable', 'waterproof' ) );

    sschp_assert_same( 'Waterproof; Dimmable', $value, 'field normalizer should flatten ACF checkbox arrays and dedupe values case-insensitively' );
}

function sschp_test_field_normalizer_handles_acf_relationship_posts() {
    $value = SSCHP_Field_Normalizer::normalize(
        array(
            (object) array(
                'ID'         => 12,
                'post_title' => 'Signal Beacon',
            ),
            (object) array(
                'ID'         => 13,
                'post_title' => 'Safety Relay',
            ),
        )
    );

    sschp_assert_same( 'Signal Beacon; Safety Relay', $value, 'field normalizer should use post titles from ACF relationship post objects' );
}

function sschp_test_field_normalizer_handles_taxonomy_terms() {
    $value = SSCHP_Field_Normalizer::normalize(
        array(
            (object) array(
                'term_id' => 4,
                'name'    => 'Outdoor',
            ),
            (object) array(
                'term_id' => 5,
                'name'    => 'Industrial',
            ),
        )
    );

    sschp_assert_same( 'Outdoor; Industrial', $value, 'field normalizer should use term names from taxonomy term objects' );
}

function sschp_test_field_normalizer_handles_numeric_fields() {
    sschp_assert_same( '42.5', SSCHP_Field_Normalizer::normalize( 42.5 ), 'field normalizer should keep numeric field values as strings' );
}

function sschp_test_field_normalizer_ignores_empty_null_and_plain_objects() {
    $value = SSCHP_Field_Normalizer::normalize(
        array(
            null,
            '',
            false,
            (object) array(
                'unexpected' => 'ignored',
            ),
        )
    );

    sschp_assert_same( '', $value, 'field normalizer should ignore null, empty, booleans, and plain non-stringable objects' );
}

function sschp_test_search_handler_get_field_value_normalizes_acf_arrays() {
    global $sschp_test_acf_fields;

    sschp_reset_test_state();
    $sschp_test_acf_fields[50]['features'] = array( 'IP65', 'Outdoor' );

    $handler = new SSCHP_Search_Handler( new SSCHP_Chip_Engine(), new SSCHP_Test_Settings() );
    $value   = sschp_invoke_private( $handler, 'get_field_value', array( 50, 'features' ) );

    sschp_assert_same( 'IP65; Outdoor', $value, 'search mapped field reads should normalize ACF arrays into semicolon tags' );
    sschp_assert_true( 'Array' !== $value, 'search mapped field reads should never return the literal Array string' );
}

function sschp_test_frontend_get_field_value_normalizes_acf_relationship_posts() {
    global $sschp_test_acf_fields;

    sschp_reset_test_state();
    $sschp_test_acf_fields[51]['related_products'] = array(
        (object) array(
            'ID'         => 100,
            'post_title' => 'Signal Beacon',
        ),
    );

    $frontend = new SSCHP_Frontend( new SSCHP_Test_Settings(), new SSCHP_Chip_Engine() );
    $value    = sschp_invoke_private( $frontend, 'get_field_value', array( 51, 'related_products' ) );

    sschp_assert_same( 'Signal Beacon', $value, 'frontend mapped field reads should normalize ACF relationship post objects into titles' );
}

function sschp_make_range_post( $post_id, array $tags, $title = '' ) {
    return array(
        'id'         => (int) $post_id,
        'title'      => '' !== $title ? $title : 'Post ' . (int) $post_id,
        'tags'       => $tags,
        'tags_lower' => array_map( 'mb_strtolower', $tags ),
    );
}

class SSCHP_Test_Counting_Chip_Engine extends SSCHP_Chip_Engine {
    public $generate_calls = 0;

    public function generate_chips( $posts_data, $active_tags = array(), $max_chips = 8, $max_length = 40 ) {
        $this->generate_calls++;

        return parent::generate_chips( $posts_data, $active_tags, $max_chips, $max_length );
    }
}

function sschp_test_range_filters_create_watt_slider_from_consistent_facts() {
    $range = new SSCHP_Range_Filters();
    $chips = $range->add_range_chips(
        array(),
        array(
            sschp_make_range_post( 1, array( '100 W' ) ),
            sschp_make_range_post( 2, array( '200 W' ) ),
            sschp_make_range_post( 3, array( '300 wattů' ) ),
        ),
        array()
    );

    sschp_assert_true( isset( $chips['sschp_range|w|w'] ), 'consistent watt facts should create one watt range chip' );
    sschp_assert_same( 100.0, $chips['sschp_range|w|w']['range']['min'], 'watt range should preserve the minimum value' );
    sschp_assert_same( 300.0, $chips['sschp_range|w|w']['range']['max'], 'watt range should preserve the maximum value' );
}

function sschp_test_range_filters_keep_ac_dc_voltage_separate() {
    $range = new SSCHP_Range_Filters();
    $chips = $range->add_range_chips(
        array(),
        array(
            sschp_make_range_post( 1, array( '24 V DC' ) ),
            sschp_make_range_post( 2, array( '48 V DC' ) ),
            sschp_make_range_post( 3, array( '230 V AC' ) ),
            sschp_make_range_post( 4, array( '400 V AC' ) ),
        ),
        array()
    );

    sschp_assert_true( isset( $chips['sschp_range|v-dc|v_dc'] ), 'DC voltage facts should create a separate DC slider' );
    sschp_assert_true( isset( $chips['sschp_range|v-ac|v_ac'] ), 'AC voltage facts should create a separate AC slider' );
    sschp_assert_same( 24.0, $chips['sschp_range|v-dc|v_dc']['range']['min'], 'DC slider should not include AC values' );
    sschp_assert_same( 230.0, $chips['sschp_range|v-ac|v_ac']['range']['min'], 'AC slider should not include DC values' );
}

function sschp_test_range_filters_reject_ip_ratings_as_categorical() {
    $range = new SSCHP_Range_Filters();
    $posts = array(
        sschp_make_range_post( 1, array( 'IP65' ) ),
        sschp_make_range_post( 2, array( 'IP67' ) ),
    );
    $chips  = $range->add_range_chips( array(), $posts, array() );
    $report = $range->get_debug_report( $posts );

    sschp_assert_same( array(), $chips, 'IP ratings should not create numeric range chips' );
    sschp_assert_same( 'IP ratings are categorical', $report['products'][0]['rejected'][0]['reason'], 'range debug should explain rejected IP ratings' );
}

function sschp_test_range_filters_reject_model_numbers() {
    $range = new SSCHP_Range_Filters();
    $chips = $range->add_range_chips(
        array(),
        array(
            sschp_make_range_post( 1, array( 'Model A100' ) ),
            sschp_make_range_post( 2, array( 'Model B200' ) ),
        ),
        array()
    );

    sschp_assert_same( array(), $chips, 'model numbers should not create range chips' );
}

function sschp_test_range_filters_normalize_dimensions_to_common_unit() {
    $range = new SSCHP_Range_Filters();
    $chips = $range->add_range_chips(
        array(),
        array(
            sschp_make_range_post( 1, array( 'Length: 10 cm' ) ),
            sschp_make_range_post( 2, array( 'Length: 100 mm' ) ),
            sschp_make_range_post( 3, array( 'Length: 20 cm' ) ),
        ),
        array()
    );

    sschp_assert_true( isset( $chips['sschp_range|length|mm'] ), 'cm and mm facts with the same label should normalize into one mm slider' );
    sschp_assert_same( 100.0, $chips['sschp_range|length|mm']['range']['min'], '10 cm should normalize to 100 mm' );
    sschp_assert_same( 200.0, $chips['sschp_range|length|mm']['range']['max'], '20 cm should normalize to 200 mm' );
}

function sschp_test_range_filters_do_not_mix_prices_with_wattage() {
    $range = new SSCHP_Range_Filters();
    $chips = $range->add_range_chips(
        array(),
        array(
            sschp_make_range_post( 1, array( 'Price: $100', 'Power: 100 W' ) ),
            sschp_make_range_post( 2, array( 'Price: $200', 'Power: 200 W' ) ),
        ),
        array()
    );

    sschp_assert_true( isset( $chips['sschp_range|price|currency_usd'] ), 'prices should create their own currency range when consistent' );
    sschp_assert_true( isset( $chips['sschp_range|power|w'] ), 'wattage should create its own power range' );
    sschp_assert_same( '$', $chips['sschp_range|price|currency_usd']['range']['unit'], 'price slider should keep currency as its unit' );
    sschp_assert_same( 'W', $chips['sschp_range|power|w']['range']['unit'], 'watt slider should keep wattage as its unit' );
}

function sschp_test_frontend_shortcode_markup_has_accessible_search_and_live_results() {
    sschp_reset_test_state();

    $frontend = new SSCHP_Frontend( new SSCHP_Test_Settings(), new SSCHP_Chip_Engine() );
    $markup   = $frontend->render_search_form();

    sschp_assert_contains( 'aria-label="Search"', $markup, 'shortcode search button should have an accessible label' );
    sschp_assert_contains( 'aria-live="polite"', $markup, 'shortcode results container should politely announce AJAX updates' );
    sschp_assert_contains( 'aria-busy="false"', $markup, 'shortcode results container should expose an initial not-busy state' );
    sschp_assert_contains( 'data-sschp-mode="guaranteed"', $markup, 'shortcode search should identify the guaranteed integration mode' );
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
    sschp_assert_contains( 'aria-pressed="true"', $markup, 'AJAX chip rendering should expose active chip state to assistive technology' );
    sschp_assert_contains( 'aria-label="Remove filter Spring Handle, 2 results"', $markup, 'active AJAX chip labels should describe the remove action' );
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

function sschp_test_frontend_archive_active_chip_has_current_state() {
    global $sschp_test_query_vars;

    sschp_reset_test_state();
    $sschp_test_query_vars['sschp_tags'] = 'Alpha';

    $posts = array(
        sschp_make_frontend_post( 1, array( 'Alpha', 'Shared' ) ),
        sschp_make_frontend_post( 2, array( 'Beta', 'Shared' ) ),
        sschp_make_frontend_post( 3, array( 'Gamma', 'Shared' ) ),
    );
    $query = sschp_make_archive_query( $posts );

    $frontend = new SSCHP_Frontend( new SSCHP_Test_Settings(), new SSCHP_Chip_Engine() );
    $markup   = sschp_invoke_private( $frontend, 'get_archive_chips_markup', array( $query ) );

    sschp_assert_contains( 'aria-current="true"', $markup, 'active archive chip links should expose current filter state' );
    sschp_assert_contains( 'aria-label="Remove filter Alpha, 1 result"', $markup, 'active archive chip links should describe the remove action' );
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
    sschp_assert_same( 50, $settings->sanitize_debounce_ms( 1 ), 'debounce should clamp to the safe minimum' );
    sschp_assert_same( 2000, $settings->sanitize_debounce_ms( 5000 ), 'debounce should clamp to the safe maximum' );
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

function sschp_test_analytics_sanitizes_nested_chip_impression_labels() {
    $labels = sschp_invoke_private(
        'SSCHP_Analytics',
        'sanitize_chip_labels',
        array(
            array(
                'Outdoor',
                array(
                    'IP67',
                    array( ' Outdoor ', '' ),
                ),
                (object) array( 'label' => 'ignored' ),
            ),
        )
    );

    sschp_assert_same( array( 'Outdoor', 'IP67' ), $labels, 'sanitize_chip_labels should recursively flatten scalar labels and ignore objects' );
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

function sschp_enable_pro_for_analytics_tests() {
    global $sschp_test_freemius;

    $sschp_test_freemius          = new SSCHP_Test_Freemius();
    $sschp_test_freemius->premium = true;
    update_option( SSCHP_Analytics::DB_VERSION_OPTION, SSCHP_Analytics::DB_VERSION );
}

function sschp_test_analytics_digest_schedules_when_enabled() {
    global $sschp_test_scheduled_events;

    sschp_reset_test_state();
    sschp_enable_pro_for_analytics_tests();
    update_option( 'sschp_analytics_enabled', '1' );
    update_option( 'sschp_analytics_digest_enabled', '1' );
    update_option( 'sschp_analytics_digest_frequency', 'weekly' );
    update_option( 'sschp_analytics_digest_day', 'monday' );
    update_option( 'sschp_analytics_digest_time', '09:00' );

    SSCHP_Analytics::handle_digest_option_changed( 'sschp_analytics_digest_enabled', '0', '1' );

    sschp_assert_same( 1, count( $sschp_test_scheduled_events ), 'analytics digest should schedule one cron event when enabled' );
    sschp_assert_same( SSCHP_Analytics::DIGEST_HOOK, $sschp_test_scheduled_events[0]['hook'], 'analytics digest should use the expected cron hook' );
}

function sschp_test_analytics_digest_unschedules_when_disabled() {
    global $sschp_test_scheduled_events;

    sschp_reset_test_state();
    sschp_enable_pro_for_analytics_tests();
    update_option( 'sschp_analytics_enabled', '1' );
    update_option( 'sschp_analytics_digest_enabled', '1' );
    SSCHP_Analytics::schedule_digest( true );

    update_option( 'sschp_analytics_digest_enabled', '0' );
    SSCHP_Analytics::handle_digest_option_changed( 'sschp_analytics_digest_enabled', '1', '0' );

    sschp_assert_same( array(), $sschp_test_scheduled_events, 'analytics digest should remove scheduled cron events when disabled' );
}

function sschp_test_analytics_digest_test_email_uses_wp_mail() {
    global $sschp_test_mail;

    sschp_reset_test_state();
    sschp_enable_pro_for_analytics_tests();
    update_option( 'sschp_analytics_enabled', '1' );
    update_option( 'sschp_analytics_digest_recipients', 'owner@example.test' );
    update_option( 'sschp_analytics_digest_include_csv', '0' );

    $sent = SSCHP_Analytics::send_digest_email( true );

    sschp_assert_true( $sent, 'test analytics digest should report successful send when wp_mail succeeds' );
    sschp_assert_same( 1, count( $sschp_test_mail ), 'test analytics digest should call wp_mail once' );
    sschp_assert_same( array( 'owner@example.test' ), $sschp_test_mail[0]['to'], 'test analytics digest should use configured recipients' );
    sschp_assert_contains( '[Test]', $sschp_test_mail[0]['subject'], 'test analytics digest subject should be clearly marked' );
    sschp_assert_contains( 'does not send this analytics data to the plugin author', $sschp_test_mail[0]['message'], 'analytics digest email should include local-data privacy copy' );
}

function sschp_test_analytics_digest_can_attach_csv_export() {
    global $sschp_test_mail;

    sschp_reset_test_state();
    sschp_enable_pro_for_analytics_tests();
    update_option( 'sschp_analytics_enabled', '1' );
    update_option( 'sschp_analytics_digest_recipients', 'owner@example.test' );
    update_option( 'sschp_analytics_digest_include_csv', '1' );

    $sent = SSCHP_Analytics::send_digest_email( true );

    sschp_assert_true( $sent, 'analytics digest should send when CSV attachment is enabled' );
    sschp_assert_same( 1, count( $sschp_test_mail[0]['attachments'] ), 'analytics digest should attach one CSV export when requested' );
}

function sschp_test_analytics_digest_does_not_send_when_analytics_disabled() {
    global $sschp_test_mail, $sschp_test_scheduled_events;

    sschp_reset_test_state();
    sschp_enable_pro_for_analytics_tests();
    update_option( 'sschp_analytics_enabled', '0' );
    update_option( 'sschp_analytics_digest_enabled', '1' );
    update_option( 'sschp_analytics_digest_recipients', 'owner@example.test' );

    $sent      = SSCHP_Analytics::send_digest_email( true );
    $scheduled = SSCHP_Analytics::schedule_digest( true );

    sschp_assert_true( ! $sent, 'analytics digest should not send email when analytics tracking is disabled' );
    sschp_assert_true( ! $scheduled, 'analytics digest should not schedule when analytics tracking is disabled' );
    sschp_assert_same( array(), $sschp_test_mail, 'analytics digest should not call wp_mail when analytics is disabled' );
    sschp_assert_same( array(), $sschp_test_scheduled_events, 'analytics digest should leave cron unscheduled when analytics is disabled' );
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

function sschp_test_woocommerce_runtime_product_tag_cache_invalidates_on_product_save() {
    global $sschp_test_post_terms, $sschp_test_post_terms_calls;

    sschp_reset_test_state();
    sschp_register_test_product(
        41,
        array( new SSCHP_Test_Product_Attribute( 'pa_material', array(), true ) )
    );
    $sschp_test_post_terms['41|pa_material'] = array( 'Steel' );

    $woo    = new SSCHP_WooCommerce();
    $source = array(
        'primary' => '',
        'extra'   => '',
    );

    $woo->add_runtime_product_tags( $source, 41, array( 'context' => 'ajax' ) );
    $calls_after_first = count( $sschp_test_post_terms_calls );
    $woo->add_runtime_product_tags( $source, 41, array( 'context' => 'ajax' ) );
    $calls_after_second = count( $sschp_test_post_terms_calls );

    $woo->invalidate_product_tag_cache( 41 );
    $woo->add_runtime_product_tags( $source, 41, array( 'context' => 'ajax' ) );
    $calls_after_invalidation = count( $sschp_test_post_terms_calls );

    sschp_assert_true( $calls_after_first > 0, 'first runtime WooCommerce tag read should inspect product terms' );
    sschp_assert_same( $calls_after_first, $calls_after_second, 'second runtime WooCommerce tag read should use the product tag cache' );
    sschp_assert_true( $calls_after_invalidation > $calls_after_second, 'invalidating the product tag cache should force fresh product term extraction' );
}

function sschp_test_frontend_shortcode_uses_guaranteed_ajax_action() {
    $frontend = new SSCHP_Frontend(
        new SSCHP_Test_Settings(
            array(
                'override_ajax' => 'search_processing',
            )
        ),
        new SSCHP_Chip_Engine()
    );

    $action = sschp_invoke_private( $frontend, 'get_ajax_action_name' );

    sschp_assert_same( 'sschp_search', $action, 'shortcode search should always use the guaranteed SSC endpoint' );
}

function sschp_test_frontend_exposes_theme_ajax_action_separately() {
    $frontend = new SSCHP_Frontend(
        new SSCHP_Test_Settings(
            array(
                'override_ajax' => 'search_processing',
            )
        ),
        new SSCHP_Chip_Engine()
    );

    $action = sschp_invoke_private( $frontend, 'get_theme_ajax_action_name' );

    sschp_assert_same( 'search_processing', $action, 'theme AJAX enhancement should remain configurable without changing shortcode requests' );
}

function sschp_test_settings_setup_wizard_recommends_guaranteed_shortcode() {
    global $sschp_test_options;

    sschp_reset_test_state();
    $sschp_test_options['sschp_post_types'] = array( 'post', 'product' );

    $settings = new SSCHP_Settings();
    $setup    = $settings->get_setup_wizard_data();

    sschp_assert_same( '[sschp_search]', $setup['shortcode'], 'setup wizard should expose the guaranteed shortcode' );
    sschp_assert_contains( 'Shortcode block', $setup['shortcodeBlockLabel'], 'setup wizard should recommend the WordPress Shortcode block path' );
    sschp_assert_contains( 'Smart Search Chips endpoint', $setup['guaranteedModeLabel'], 'setup wizard should explain that guaranteed mode uses the SSC endpoint' );
    sschp_assert_same( 'Passive by default', $setup['themeEnhancementStatus'], 'theme enhancement should be passive when no override action is configured' );
    sschp_assert_same( array( 'Posts', 'Products' ), $setup['selectedPostTypeLabels'], 'setup wizard should show selected post type labels' );
}

function sschp_test_search_index_table_creation_defines_required_columns() {
    global $sschp_test_db_delta_sql, $sschp_test_options;

    sschp_reset_test_state();
    SSCHP_Search_Index::create_table();

    $sql = implode( "\n", $sschp_test_db_delta_sql );

    sschp_assert_contains( 'object_id BIGINT(20) UNSIGNED NOT NULL', $sql, 'search index table should include object_id' );
    sschp_assert_contains( 'post_type VARCHAR(32) NOT NULL', $sql, 'search index table should include post_type' );
    sschp_assert_contains( 'language_code VARCHAR(20) NOT NULL', $sql, 'search index table should include language_code' );
    sschp_assert_contains( 'title_text LONGTEXT NOT NULL', $sql, 'search index table should include title_text' );
    sschp_assert_contains( 'content_text LONGTEXT NOT NULL', $sql, 'search index table should include content_text' );
    sschp_assert_contains( 'taxonomy_text LONGTEXT NOT NULL', $sql, 'search index table should include taxonomy_text' );
    sschp_assert_contains( 'meta_text LONGTEXT NOT NULL', $sql, 'search index table should include meta_text' );
    sschp_assert_contains( 'chip_tags LONGTEXT NOT NULL', $sql, 'search index table should include chip_tags' );
    sschp_assert_contains( 'numeric_facts LONGTEXT NOT NULL', $sql, 'search index table should include numeric_facts' );
    sschp_assert_contains( 'updated_at DATETIME NOT NULL', $sql, 'search index table should include updated_at' );
    sschp_assert_same( SSCHP_Search_Index::DB_VERSION, $sschp_test_options[SSCHP_Search_Index::DB_VERSION_OPTION], 'search index table creation should store the DB version' );
}

function sschp_test_search_index_queue_initial_index_schedules_batch() {
    global $sschp_test_options, $sschp_test_scheduled_events;

    sschp_reset_test_state();
    $sschp_test_options['sschp_post_types'] = array( 'post' );
    sschp_register_test_post_for_index( 1, 'post', 'Alpha Beacon' );
    sschp_register_test_post_for_index( 2, 'post', 'Beta Beacon' );

    SSCHP_Search_Index::queue_initial_index( 'test' );

    sschp_assert_same( 'queued', $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION], 'initial indexing should enter queued status' );
    sschp_assert_same( 2, $sschp_test_options[SSCHP_Search_Index::TOTAL_OPTION], 'initial indexing should count eligible posts' );
    sschp_assert_true( in_array( SSCHP_Search_Index::CRON_HOOK, sschp_test_scheduled_hooks(), true ), 'initial indexing should schedule a background batch' );
}

function sschp_test_search_index_settings_change_queues_rebuild() {
    global $sschp_test_options;

    sschp_reset_test_state();
    sschp_register_test_post_for_index( 3, 'product', 'Product Beacon' );

    $index = new SSCHP_Search_Index(
        new SSCHP_Test_Settings(
            array(
                'post_types' => array( 'product' ),
            )
        ),
        new SSCHP_Chip_Engine()
    );
    $index->handle_option_changed( 'sschp_post_types', array( 'post' ), array( 'product' ) );

    sschp_assert_same( 'queued', $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION], 'changing indexed settings should queue a rebuild' );
}

function sschp_test_search_index_reindexes_saved_post() {
    global $sschp_test_index_rows, $sschp_test_post_meta;

    sschp_reset_test_state();
    $post = sschp_register_test_post_for_index( 10, 'product', 'Signal Beacon', 'Reliable warning light' );
    $sschp_test_post_meta[10][SSCHP_Meta_Box::META_KEY] = 'Outdoor; IP65';

    $index = new SSCHP_Search_Index(
        new SSCHP_Test_Settings(
            array(
                'post_types' => array( 'product' ),
            )
        ),
        new SSCHP_Chip_Engine()
    );
    $index->handle_save_post( 10, $post, true );

    $row = reset( $sschp_test_index_rows );
    sschp_assert_same( 10, (int) $row->object_id, 'saved post should be written to the local search index' );
    sschp_assert_same( 'product', $row->post_type, 'saved post index row should preserve post type' );
    sschp_assert_contains( 'Signal Beacon', $row->title_text, 'saved post index row should include title text' );
    sschp_assert_contains( 'Outdoor', $row->chip_tags, 'saved post index row should include chip tags' );
}

function sschp_test_search_index_reindexes_product_attribute_meta_change() {
    global $sschp_test_index_rows, $sschp_test_post_meta;

    sschp_reset_test_state();
    sschp_register_test_post_for_index( 11, 'product', 'Attribute Product', 'Product content' );
    $sschp_test_post_meta[11][SSCHP_Meta_Box::META_KEY] = 'Manual Chip';

    $index = new SSCHP_Search_Index(
        new SSCHP_Test_Settings(
            array(
                'post_types' => array( 'product' ),
            )
        ),
        new SSCHP_Chip_Engine()
    );
    $index->handle_post_meta_changed( 1, 11, '_product_attributes', array() );

    sschp_assert_true( ! empty( $sschp_test_index_rows ), 'WooCommerce attribute meta changes should reindex the product' );
}

function sschp_test_search_index_reindexes_term_change() {
    global $sschp_test_index_rows, $sschp_test_post_terms;

    sschp_reset_test_state();
    sschp_register_test_post_for_index( 12, 'product', 'Categorized Product', 'Product content' );
    $sschp_test_post_terms['12|product_cat'] = array( 'Warning Lights' );

    $index = new SSCHP_Search_Index(
        new SSCHP_Test_Settings(
            array(
                'post_types' => array( 'product' ),
            )
        ),
        new SSCHP_Chip_Engine()
    );
    $index->handle_object_terms_changed( 12 );

    $row = reset( $sschp_test_index_rows );
    sschp_assert_contains( 'Warning Lights', $row->taxonomy_text, 'taxonomy changes should reindex product category/tag text' );
}

function sschp_test_search_handler_falls_back_before_index_complete() {
    global $sschp_test_options, $sschp_test_post_meta;

    sschp_reset_test_state();
    $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION] = 'queued';
    $post = sschp_register_test_post_for_index( 20, 'post', 'Beacon Guide', 'Install a beacon safely.' );
    $sschp_test_post_meta[20][SSCHP_Meta_Box::META_KEY] = 'Guide; Safety';

    $engine   = new SSCHP_Chip_Engine();
    $settings = new SSCHP_Test_Settings(
        array(
            'post_types' => array( 'post' ),
        )
    );
    $index    = new SSCHP_Search_Index( $settings, $engine );
    $handler  = new SSCHP_Search_Handler( $engine, $settings, $index );
    $posts    = sschp_invoke_private( $handler, 'get_posts_data_for_keyword', array( 'Beacon', array( 'post' ), array(), '', 50, 0.3 ) );

    sschp_assert_same( 1, count( $posts ), 'search handler should fall back to WP_Query before the local index is complete' );
    sschp_assert_same( 20, $posts[0]['id'], 'fallback search should return the matching post' );
    sschp_assert_same( array( 'Guide', 'Safety' ), $posts[0]['tags'], 'fallback search should still collect chip tags' );
}

function sschp_test_search_handler_caches_query_results() {
    global $sschp_test_wp_query_count, $sschp_test_post_meta;

    sschp_reset_test_state();
    $post = sschp_register_test_post_for_index( 21, 'post', 'Beacon Manual', 'Beacon installation notes.' );
    $sschp_test_post_meta[21][SSCHP_Meta_Box::META_KEY] = 'Manual; Beacon';

    $handler = new SSCHP_Search_Handler(
        new SSCHP_Chip_Engine(),
        new SSCHP_Test_Settings(
            array(
                'post_types' => array( 'post' ),
            )
        )
    );

    $first = sschp_invoke_private( $handler, 'get_posts_data_for_keyword', array( 'Beacon', array( 'post' ), array(), '', 50, 0.3 ) );
    $after_first_count = $sschp_test_wp_query_count;
    $second = sschp_invoke_private( $handler, 'get_posts_data_for_keyword', array( 'Beacon', array( 'post' ), array(), '', 50, 0.3 ) );

    sschp_assert_same( 1, $after_first_count, 'first uncached search should run one WP_Query' );
    sschp_assert_same( $after_first_count, $sschp_test_wp_query_count, 'second identical search should be served from cache without another WP_Query' );
    sschp_assert_same( $first, $second, 'cached search results should match the original query results' );
}

function sschp_test_search_handler_caches_chip_generation_separately() {
    sschp_reset_test_state();

    $engine  = new SSCHP_Test_Counting_Chip_Engine();
    $handler = new SSCHP_Search_Handler( $engine, new SSCHP_Test_Settings() );
    $posts   = array(
        array(
            'id'         => 1,
            'tags'       => array( 'Steel', 'Outdoor' ),
            'tags_lower' => array( 'steel', 'outdoor' ),
            'score'      => 1,
        ),
        array(
            'id'         => 2,
            'tags'       => array( 'Steel', 'Indoor' ),
            'tags_lower' => array( 'steel', 'indoor' ),
            'score'      => 1,
        ),
    );

    $first  = sschp_invoke_private( $handler, 'get_cached_chips', array( $posts, array(), 8, 40 ) );
    $second = sschp_invoke_private( $handler, 'get_cached_chips', array( $posts, array(), 8, 40 ) );

    sschp_assert_same( 1, $engine->generate_calls, 'chip generation should run once for identical post/tag/settings input' );
    sschp_assert_same( $first, $second, 'cached chips should match original generated chips' );
}

function sschp_test_search_handler_cache_version_changes_on_relevant_updates() {
    global $sschp_test_options;

    sschp_reset_test_state();
    $handler = new SSCHP_Search_Handler( new SSCHP_Chip_Engine(), new SSCHP_Test_Settings() );
    $handler->bump_cache_version( 'seed' );
    $first = $sschp_test_options[SSCHP_Search_Handler::CACHE_VERSION_OPTION];

    usleep( 1000 );
    $handler->handle_cache_option_changed( 'sschp_search_results_count', 50, 100 );
    $second = $sschp_test_options[SSCHP_Search_Handler::CACHE_VERSION_OPTION];

    sschp_assert_true( $first !== $second, 'changing search-affecting settings should bump the cache version' );
}

function sschp_test_search_handler_hard_caps_large_catalog_limits() {
    sschp_reset_test_state();

    $handler = new SSCHP_Search_Handler( new SSCHP_Chip_Engine(), new SSCHP_Test_Settings() );

    sschp_assert_same( 200, sschp_invoke_private( $handler, 'limit_analyzed_results', array( 1000 ) ), 'analyzed result count should be hard-capped by default' );
    sschp_assert_same( 100, sschp_invoke_private( $handler, 'limit_rendered_results', array( 1000 ) ), 'rendered result count should be hard-capped by default' );
}

function sschp_test_search_index_search_after_complete_returns_ranked_results() {
    global $sschp_test_options, $sschp_test_post_meta;

    sschp_reset_test_state();
    $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION] = 'complete';

    $exact = sschp_register_test_post_for_index( 31, 'product', 'Signal Beacon', 'Compact light' );
    $broad = sschp_register_test_post_for_index( 32, 'product', 'Industrial Light', 'Signal beacon for machinery' );
    $sschp_test_post_meta[31][SSCHP_Meta_Box::META_KEY] = 'Beacon; Outdoor';
    $sschp_test_post_meta[32][SSCHP_Meta_Box::META_KEY] = 'Light; Industrial';

    $index = new SSCHP_Search_Index(
        new SSCHP_Test_Settings(
            array(
                'post_types' => array( 'product' ),
            )
        ),
        new SSCHP_Chip_Engine()
    );
    $index->index_post( 31, $exact );
    $index->index_post( 32, $broad );

    $results = $index->search( 'Signal Beacon', array( 'product' ), '', 10 );

    sschp_assert_same( 2, count( $results ), 'completed local index search should return matching rows' );
    sschp_assert_same( 31, $results[0]['id'], 'exact title match should rank above broad content matches' );
}

function sschp_test_search_index_large_catalog_smoke() {
    global $sschp_test_options, $sschp_test_post_meta;

    sschp_reset_test_state();
    $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION] = 'complete';

    $index = new SSCHP_Search_Index(
        new SSCHP_Test_Settings(
            array(
                'post_types' => array( 'product' ),
            )
        ),
        new SSCHP_Chip_Engine()
    );

    for ( $i = 1; $i <= 500; $i++ ) {
        $title = 250 === $i ? 'Steel Beacon Exact' : 'Catalog Product ' . $i;
        $body  = 0 === $i % 10 ? 'Steel beacon replacement part' : 'General catalog item';
        $post  = sschp_register_test_post_for_index( 1000 + $i, 'product', $title, $body );
        $sschp_test_post_meta[ 1000 + $i ][SSCHP_Meta_Box::META_KEY] = 0 === $i % 10 ? 'Steel; Beacon' : 'Catalog';
        $index->index_post( 1000 + $i, $post );
    }

    $results = $index->search( 'steel beacon', array( 'product' ), '', 50 );

    sschp_assert_true( ! empty( $results ), 'large catalog indexed search should return matching rows' );
    sschp_assert_true( count( $results ) <= 50, 'large catalog indexed search should respect the requested limit' );
    sschp_assert_same( 1250, $results[0]['id'], 'large catalog indexed search should rank the best title match first' );
}

function sschp_test_fallback_relevance_exact_title_beats_content_only_match() {
    sschp_reset_test_state();

    $content_post = sschp_register_test_post_for_index( 51, 'post', 'General Article', 'This article mentions signal beacon in the body.' );
    $title_post   = sschp_register_test_post_for_index( 52, 'post', 'Signal Beacon', 'Short content.' );
    $handler      = new SSCHP_Search_Handler( new SSCHP_Chip_Engine(), new SSCHP_Test_Settings() );
    $ranked       = sschp_invoke_private(
        $handler,
        'score_fallback_posts',
        array(
            array(
                array(
                    'post'  => $content_post,
                    'score' => 0,
                ),
                array(
                    'post'  => $title_post,
                    'score' => 0,
                ),
            ),
            'signal beacon',
        )
    );

    sschp_assert_same( 52, $ranked[0]['post']->ID, 'fallback relevance should rank exact title matches above content-only matches' );
}

function sschp_test_fallback_relevance_product_attribute_beats_random_content() {
    sschp_reset_test_state();

    $content_post = sschp_register_test_post_for_index( 61, 'post', 'Material Notes', 'A paragraph that happens to mention brass.' );
    $product_post = sschp_register_test_post_for_index( 62, 'product', 'Fixture Product', 'Product body.' );
    sschp_register_test_product( 62, array( new SSCHP_Test_Product_Attribute( 'material', array( 'Brass' ) ) ) );

    $handler = new SSCHP_Search_Handler( new SSCHP_Chip_Engine(), new SSCHP_Test_Settings() );
    $ranked  = sschp_invoke_private(
        $handler,
        'score_fallback_posts',
        array(
            array(
                array(
                    'post'  => $content_post,
                    'score' => 0,
                ),
                array(
                    'post'  => $product_post,
                    'score' => 0,
                ),
            ),
            'brass',
        )
    );

    sschp_assert_same( 62, $ranked[0]['post']->ID, 'fallback relevance should rank WooCommerce attribute matches above random content matches' );
}

function sschp_test_fallback_relevance_sku_match_works() {
    sschp_reset_test_state();

    $content_post = sschp_register_test_post_for_index( 71, 'post', 'SKU Notes', 'ABC-123 appears in generic content.' );
    $product_post = sschp_register_test_post_for_index( 72, 'product', 'Replacement Part', 'Product body.' );
    sschp_register_test_product( 72, array(), 'ABC-123' );

    $handler = new SSCHP_Search_Handler( new SSCHP_Chip_Engine(), new SSCHP_Test_Settings() );
    $ranked  = sschp_invoke_private(
        $handler,
        'score_fallback_posts',
        array(
            array(
                array(
                    'post'  => $content_post,
                    'score' => 0,
                ),
                array(
                    'post'  => $product_post,
                    'score' => 0,
                ),
            ),
            'ABC-123',
        )
    );

    sschp_assert_same( 72, $ranked[0]['post']->ID, 'fallback relevance should score exact WooCommerce SKU matches' );
}

function sschp_test_fallback_relevance_accent_and_case_normalization() {
    sschp_reset_test_state();

    $content_post = sschp_register_test_post_for_index( 81, 'post', 'Generic Result', 'signalni majak is mentioned in content.' );
    $title_post   = sschp_register_test_post_for_index( 82, 'post', 'Signální Maják', 'Short content.' );
    $handler      = new SSCHP_Search_Handler( new SSCHP_Chip_Engine(), new SSCHP_Test_Settings() );
    $ranked       = sschp_invoke_private(
        $handler,
        'score_fallback_posts',
        array(
            array(
                array(
                    'post'  => $content_post,
                    'score' => 0,
                ),
                array(
                    'post'  => $title_post,
                    'score' => 0,
                ),
            ),
            'SIGNALNI MAJAK',
        )
    );

    sschp_assert_same( 82, $ranked[0]['post']->ID, 'fallback relevance should normalize accents and case for matching' );
}

function sschp_test_fallback_relevance_multilingual_text_does_not_break() {
    sschp_reset_test_state();

    $scorer = new SSCHP_Relevance_Scorer();
    $post   = sschp_register_test_post_for_index( 91, 'post', '赤い信号灯', '日本語の説明文です。' );
    $score  = $scorer->score_post( $post, '信号灯', array() );

    sschp_assert_true( $score > 0, 'fallback relevance should handle multilingual Unicode text without English-only stemming' );
}

function sschp_test_fallback_relevance_does_not_override_relevanssi_order_by_default() {
    global $sschp_test_filters, $sschp_test_post_meta;

    sschp_reset_test_state();
    $sschp_test_filters['sschp_relevanssi_available'] = true;

    sschp_register_test_post_for_index( 101, 'post', 'General Article', 'This article mentions signal beacon in the body.' );
    sschp_register_test_post_for_index( 102, 'post', 'Signal Beacon', 'Short content.' );
    $sschp_test_post_meta[101][SSCHP_Meta_Box::META_KEY] = 'Content';
    $sschp_test_post_meta[102][SSCHP_Meta_Box::META_KEY] = 'Title';

    $handler = new SSCHP_Search_Handler(
        new SSCHP_Chip_Engine(),
        new SSCHP_Test_Settings(
            array(
                'post_types' => array( 'post' ),
            )
        )
    );
    $posts   = sschp_invoke_private( $handler, 'get_posts_data_for_keyword', array( 'signal beacon', array( 'post' ), array(), '', 50, 0.3 ) );

    sschp_assert_same( 101, $posts[0]['id'], 'fallback scorer should not reorder results when Relevanssi is available by default' );

    sschp_reset_test_state();
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
sschp_test_field_normalizer_handles_acf_checkbox_array();
sschp_test_field_normalizer_handles_acf_relationship_posts();
sschp_test_field_normalizer_handles_taxonomy_terms();
sschp_test_field_normalizer_handles_numeric_fields();
sschp_test_field_normalizer_ignores_empty_null_and_plain_objects();
sschp_test_search_handler_get_field_value_normalizes_acf_arrays();
sschp_test_frontend_get_field_value_normalizes_acf_relationship_posts();
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_create_watt_slider_from_consistent_facts' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_keep_ac_dc_voltage_separate' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_reject_ip_ratings_as_categorical' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_reject_model_numbers' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_normalize_dimensions_to_common_unit' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_do_not_mix_prices_with_wattage' );
sschp_test_frontend_shortcode_markup_has_accessible_search_and_live_results();
sschp_test_search_handler_normalizes_active_tags_case_insensitively();
sschp_test_search_handler_render_chips_marks_case_insensitive_active_tag();
sschp_test_frontend_archive_filter_url_toggles_case_insensitively();
sschp_test_frontend_archive_filters_current_page_posts();
sschp_test_frontend_archive_filters_multiple_tags_with_and_logic();
sschp_test_frontend_archive_chip_toggle_removes_pagination_params();
sschp_test_frontend_archive_clear_filters_restores_url_without_chip_params();
sschp_test_frontend_archive_markup_describes_current_page_scope();
sschp_test_frontend_archive_active_chip_has_current_state();
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
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_sanitizes_nested_chip_impression_labels' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_appends_ctr_percentages' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_escape_csv_value_neutralizes_spreadsheet_formulas' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_guard_tracking_rejects_immediate_replays' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_digest_schedules_when_enabled' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_digest_unschedules_when_disabled' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_digest_test_email_uses_wp_mail' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_digest_can_attach_csv_export' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_digest_does_not_send_when_analytics_disabled' );
sschp_test_meta_auth_callback_requires_edit_post_capability();
sschp_test_woocommerce_bulk_generation_skips_uneditable_products();
sschp_test_woocommerce_auto_generation_preserves_manual_tags();
sschp_test_woocommerce_generated_tags_update_when_attributes_change();
sschp_test_woocommerce_auto_generation_skips_revisions();
sschp_test_woocommerce_sku_inclusion_is_disabled_by_default_and_opt_in();
sschp_test_woocommerce_price_range_tags_are_disabled_by_default();
sschp_test_woocommerce_runtime_tags_merge_manual_and_generated_without_duplicates();
sschp_test_woocommerce_runtime_product_tag_cache_invalidates_on_product_save();
sschp_test_frontend_shortcode_uses_guaranteed_ajax_action();
sschp_test_frontend_exposes_theme_ajax_action_separately();
sschp_test_settings_setup_wizard_recommends_guaranteed_shortcode();
sschp_test_search_index_table_creation_defines_required_columns();
sschp_test_search_index_queue_initial_index_schedules_batch();
sschp_test_search_index_settings_change_queues_rebuild();
sschp_test_search_index_reindexes_saved_post();
sschp_test_search_index_reindexes_product_attribute_meta_change();
sschp_test_search_index_reindexes_term_change();
sschp_test_search_handler_falls_back_before_index_complete();
sschp_test_search_handler_caches_query_results();
sschp_test_search_handler_caches_chip_generation_separately();
sschp_test_search_handler_cache_version_changes_on_relevant_updates();
sschp_test_search_handler_hard_caps_large_catalog_limits();
sschp_test_search_index_search_after_complete_returns_ranked_results();
sschp_test_search_index_large_catalog_smoke();
sschp_test_fallback_relevance_exact_title_beats_content_only_match();
sschp_test_fallback_relevance_product_attribute_beats_random_content();
sschp_test_fallback_relevance_sku_match_works();
sschp_test_fallback_relevance_accent_and_case_normalization();
sschp_test_fallback_relevance_multilingual_text_does_not_break();
sschp_test_fallback_relevance_does_not_override_relevanssi_order_by_default();
sschp_test_search_handler_normalizes_active_tag_payload_shapes();
sschp_test_search_handler_builtin_action_without_nonce_fails();
sschp_test_search_handler_override_without_marker_returns_early();
sschp_test_search_handler_override_with_marker_and_valid_nonce_runs();
sschp_test_search_handler_override_with_marker_and_invalid_nonce_fails();
sschp_test_frontend_registers_loop_fallback_for_block_themes();
sschp_test_no_legacy_public_interface_references_in_fixtures();
sschp_test_wporg_settings_source_keeps_selector_safety();
sschp_test_premium_ajax_numeric_inputs_are_unslashed();

if ( $sschp_test_failures > 0 ) {
    fwrite( STDERR, "\n{$sschp_test_failures} unit test(s) failed.\n" );
    exit( 1 );
}

fwrite( STDOUT, "All unit tests passed.\n" );
