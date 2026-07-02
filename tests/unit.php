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

function sschp_normalize_newlines( $value ) {
    return str_replace( array( "\r\n", "\r" ), "\n", (string) $value );
}

function sschp_explanation_codes( $explanations ) {
    return array_values(
        array_map(
            function( $explanation ) {
                return isset( $explanation['code'] ) ? $explanation['code'] : '';
            },
            (array) $explanations
        )
    );
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
        'post_date'    => '2026-05-01 12:00:00',
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
    global $sschp_test_options, $sschp_test_settings_errors, $sschp_test_filters, $sschp_test_actions, $sschp_test_polylang_language, $sschp_test_freemius, $sschp_test_added_actions, $sschp_test_added_filters, $sschp_test_is_block_theme, $sschp_test_current_user_can, $sschp_test_current_user_calls, $sschp_test_transients, $sschp_test_nonce_valid, $sschp_test_query_vars, $sschp_test_post_meta, $sschp_test_current_url, $sschp_test_is_admin, $sschp_test_is_search, $sschp_test_products, $sschp_test_post_types, $sschp_test_post_terms, $sschp_test_post_revisions, $sschp_test_post_autosaves, $sschp_test_acf_fields, $sschp_test_posts, $sschp_test_taxonomies, $sschp_test_terms, $sschp_test_last_get_posts_args, $sschp_test_registered_meta_keys, $sschp_test_index_rows, $sschp_test_index_table_exists, $sschp_test_analytics_rows, $sschp_test_db_queries, $sschp_test_db_delta_sql, $sschp_test_scheduled_events, $sschp_test_mail, $sschp_test_wp_query_count, $sschp_test_post_terms_calls, $sschp_test_registered_blocks, $sschp_test_shortcodes;

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
    $sschp_test_taxonomies         = array();
    $sschp_test_terms              = array();
    $sschp_test_last_get_posts_args = array();
    $sschp_test_registered_meta_keys = array();
    $sschp_test_index_rows         = array();
    $sschp_test_index_table_exists = false;
    $sschp_test_analytics_rows     = array();
    $sschp_test_db_queries         = array();
    $sschp_test_db_delta_sql       = array();
    $sschp_test_scheduled_events   = array();
    $sschp_test_mail               = array();
    $sschp_test_wp_query_count     = 0;
    $sschp_test_post_terms_calls   = array();
    $sschp_test_registered_blocks  = array();
    $sschp_test_shortcodes         = array();
    $_POST                    = array();
    $_REQUEST                 = array();
    $_GET                     = array();
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

function sschp_decode_json_die_message( $result ) {
    $data = json_decode( $result['message'], true );

    sschp_assert_true( is_array( $data ), 'captured wp_die message should contain JSON data' );

    return is_array( $data ) ? $data : array();
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
    sschp_assert_contains( 'render_search_debug_card', $contents, 'WP.org settings source should render the admin test search card' );
    sschp_assert_contains( 'sschp_admin_test_search', $contents, 'WP.org settings source should register the admin test search AJAX endpoint' );
    sschp_assert_contains( 'sschp_search_index_control', $contents, 'WP.org settings source should register the local search index controls endpoint' );
    sschp_assert_contains( 'Rebuild index now', $contents, 'WP.org settings source should render manual index rebuild control' );
    sschp_assert_contains( 'Pause indexing', $contents, 'WP.org settings source should render pause indexing control' );
    sschp_assert_contains( 'blockPathLabel', $contents, 'WP.org settings source should keep explicit guaranteed search block guidance' );
    sschp_assert_contains( 'Shortcode fallback', $contents, 'WP.org settings source should keep explicit guaranteed search shortcode fallback guidance' );
    sschp_assert_contains( 'class="button button-secondary" href="<?php echo esc_url( $diagnostics[\'debugUrl\'] ); ?>"', $contents, 'WP.org settings source should render frontend debug mode as a padded secondary button' );
    sschp_assert_true( false === strpos( $contents, 'class="button button-link" href="<?php echo esc_url( $diagnostics[\'debugUrl\'] ); ?>"' ), 'WP.org settings source should not render frontend debug mode as an unpadded button link' );
}

function sschp_test_wporg_search_handler_keeps_admin_test_search_api() {
    $path = dirname( __DIR__ ) . '/build-sources/wporg/includes/class-search-handler.php';

    sschp_assert_true( is_file( $path ), 'WP.org search handler source should exist for explicit release builds' );

    $contents = is_file( $path ) ? file_get_contents( $path ) : '';

    sschp_assert_contains( 'get_admin_test_search_data', $contents, 'WP.org search handler should keep the reusable admin test search API' );
    sschp_assert_contains( 'local_index', $contents, 'WP.org search handler should report the local index path' );
    sschp_assert_contains( 'relevanssi', $contents, 'WP.org search handler should report the Relevanssi path' );
    sschp_assert_contains( 'wp_query_fallback', $contents, 'WP.org search handler should report the WP_Query fallback path' );
    sschp_assert_contains( 'explanations', $contents, 'WP.org search handler should return structured admin test search explanations' );
}

function sschp_test_wporg_main_loads_search_explainer() {
    $path = dirname( __DIR__ ) . '/build-sources/wporg/smart-search-chips.php';

    sschp_assert_true( is_file( $path ), 'WP.org main plugin source should exist for explicit release builds' );

    $contents = is_file( $path ) ? file_get_contents( $path ) : '';

    sschp_assert_contains( 'includes/class-search-explainer.php', $contents, 'WP.org build should load the shared search explanation helper' );
    sschp_assert_contains( 'SSCHP_Search_Index::maybe_install_or_upgrade()', $contents, 'WP.org build should self-heal missing local search index tables after updates' );
}

function sschp_test_plugin_lifecycle_hooks_unschedule_background_work() {
    $root_contents  = file_get_contents( dirname( __DIR__ ) . '/smart-search-chips.php' );
    $wporg_contents = file_get_contents( dirname( __DIR__ ) . '/build-sources/wporg/smart-search-chips.php' );

    foreach ( array( 'root' => $root_contents, 'wporg' => $wporg_contents ) as $label => $contents ) {
        sschp_assert_contains( 'register_deactivation_hook', $contents, $label . ' plugin source should register a deactivation hook' );
        sschp_assert_contains( 'register_uninstall_hook', $contents, $label . ' plugin source should register a standard uninstall hook' );
        sschp_assert_contains( 'sschp_unschedule_plugin_tasks', $contents, $label . ' plugin source should centralize scheduled task cleanup' );
        sschp_assert_contains( 'wp_clear_scheduled_hook', $contents, $label . ' plugin source should clear WP-Cron events on deactivate/uninstall' );
        sschp_assert_contains( 'as_unschedule_all_actions', $contents, $label . ' plugin source should clear Action Scheduler batches when available' );
    }

    sschp_assert_contains( 'sschp_delete_plugin_data( true )', $root_contents, 'root uninstall should use full table cleanup when data deletion is enabled' );
    sschp_assert_contains( 'DROP TABLE IF EXISTS {$analytics_table_name}', $wporg_contents, 'WP.org cleanup should remove leftover plugin analytics tables from prior Pro installs' );
}

function sschp_test_wporg_frontend_source_registers_search_block() {
    $path = dirname( __DIR__ ) . '/build-sources/wporg/includes/class-frontend.php';

    sschp_assert_true( is_file( $path ), 'WP.org frontend source should exist for explicit release builds' );

    $contents = is_file( $path ) ? file_get_contents( $path ) : '';

    sschp_assert_contains( 'register_search_block', $contents, 'WP.org frontend source should register the guaranteed search block' );
    sschp_assert_contains( 'render_search_block', $contents, 'WP.org frontend source should server-render the guaranteed search block' );
    sschp_assert_contains( 'blocks/search', $contents, 'WP.org frontend source should use the shared search block metadata directory' );
}

function sschp_test_search_block_files_are_release_validated() {
    $root        = dirname( __DIR__ );
    $block_json  = $root . '/blocks/search/block.json';
    $editor_js   = $root . '/blocks/search/editor.js';
    $asset_php   = $root . '/blocks/search/editor.asset.php';
    $build       = $root . '/scripts/build-release.ps1';
    $validator   = $root . '/scripts/validate-wporg-package.php';

    sschp_assert_true( is_file( $block_json ), 'search block metadata should exist' );
    sschp_assert_true( is_file( $editor_js ), 'search block editor script should exist' );
    sschp_assert_true( is_file( $asset_php ), 'search block editor asset metadata should exist' );

    $metadata = json_decode( file_get_contents( $block_json ), true );
    sschp_assert_same( 'smart-search-chips/search', $metadata['name'], 'search block should use the public Smart Search Chips namespace' );
    sschp_assert_true( ! isset( $metadata['attributes']['premium'] ), 'search block metadata should not expose premium-only attributes' );

    $combined_block_source = file_get_contents( $block_json ) . "\n" . file_get_contents( $editor_js ) . "\n" . file_get_contents( $asset_php );
    foreach ( array( '__premium_only', 'SSCHP_License', 'SSCHP_Analytics', 'Freemius', 'Upgrade to Pro' ) as $forbidden ) {
        sschp_assert_true( false === stripos( $combined_block_source, $forbidden ), 'search block files should not reference premium-only code: ' . $forbidden );
    }

    sschp_assert_contains( "'blocks'", file_get_contents( $build ), 'release build should copy block files into generated packages' );
    sschp_assert_contains( 'blocks/search/block.json', file_get_contents( $validator ), 'WP.org validator should require search block metadata' );
    sschp_assert_contains( 'blocks/search/editor.js', file_get_contents( $validator ), 'WP.org validator should require search block editor script' );
    sschp_assert_contains( 'sschp_wporg_is_child_path', file_get_contents( $validator ), 'WP.org validator should reject arbitrary package paths outside the generated build directory' );
    sschp_assert_contains( 'Refusing to validate a path outside the generated WordPress.org package directory', file_get_contents( $validator ), 'WP.org validator should explain when the caller passes the wrong package directory' );
}

function sschp_test_release_workflow_requires_version_tag_and_freemius_sdk() {
    $root     = dirname( __DIR__ );
    $workflow = sschp_normalize_newlines( file_get_contents( $root . '/.github/workflows/release.yml' ) );
    $build    = file_get_contents( $root . '/scripts/build-release.ps1' );

    sschp_assert_contains( "tags:\n      - 'v*'", $workflow, 'release workflow should only deploy from version tags' );
    sschp_assert_true( false === strpos( $workflow, 'workflow_dispatch' ), 'release workflow should not allow manual same-version deployment' );
    sschp_assert_contains( 'Verify version metadata', $workflow, 'release workflow should verify tag and plugin metadata match before deploying' );
    sschp_assert_contains( 'Verify deployment secrets', $workflow, 'release workflow should fail before deployment when required secrets are missing' );
    sschp_assert_true(
        strpos( $workflow, 'Verify deployment secrets' ) < strpos( $workflow, 'Create GitHub release' ),
        'release workflow should validate deployment secrets before creating a GitHub release'
    );
    sschp_assert_contains( "permissions:\n  contents: read", $workflow, 'release workflow should default to read-only repository permissions' );
    sschp_assert_contains( "permissions:\n      contents: write", $workflow, 'release workflow should grant write permissions only to the deploy job' );
    sschp_assert_contains( 'FREEMIUS_DEVELOPER_ID', $workflow, 'release workflow should accept the Freemius developer ID secret alias' );
    sschp_assert_contains( 'release_mode: released', $workflow, 'Freemius uploads should be released immediately for now' );
    sschp_assert_true( false === strpos( $workflow, 'release_mode: pending' ), 'Freemius uploads should not be left pending by the release workflow' );
    sschp_assert_true( false === strpos( $workflow, '10up/action-wordpress-plugin-deploy@stable' ), 'WP.org deploy action should not use the floating stable branch' );
    sschp_assert_contains( '10up/action-wordpress-plugin-deploy@54bd289b8525fd23a5c365ec369185f2966529c2', $workflow, 'WP.org deploy action should be pinned to a concrete revision' );

    sschp_assert_contains( 'Ensure-FreemiusSdk', $build, 'release build should prepare the Freemius SDK before building the Pro zip' );
    sschp_assert_contains( 'vendor\freemius\start.php', $build, 'release build should verify the Freemius SDK entrypoint exists' );
    sschp_assert_contains( 'github.com/Freemius/wordpress-sdk', $build, 'release build should download the Freemius SDK when it is absent from the checkout' );
    sschp_assert_contains( 'Get-FileHash -Algorithm SHA256', $build, 'release build should verify the downloaded Freemius SDK archive checksum' );
    sschp_assert_contains( 'aab118b5fee33c66c9035fbe6aacc5f4cac6e87d1f8cac40df6610d18ca54442', $build, 'release build should pin the expected Freemius SDK checksum' );
    sschp_assert_contains( 'Get-Command zip', $build, 'release build should prefer the native zip command for Freemius-compatible packages' );
}

function sschp_test_ci_workflow_runs_expanded_release_gates() {
    $workflow = file_get_contents( dirname( __DIR__ ) . '/.github/workflows/ci.yml' );

    foreach ( array(
        'npm ci',
        'npm run lint:php',
        'npm run test:unit',
        'npm run build:wporg',
        'npm run validate:wporg',
        'npm run lint:wporg',
        'npm run build:release',
        'npm run test:browser:compat',
        'npm run local:wp:reset',
        'npm run test:browser',
    ) as $command ) {
        sschp_assert_contains( $command, $workflow, 'CI workflow should run release gate: ' . $command );
    }

    sschp_assert_contains( 'release-package-candidates', $workflow, 'CI workflow should upload release package candidates as artifacts' );
    sschp_assert_contains( 'playwright-report', $workflow, 'CI workflow should upload Playwright debugging artifacts on failure' );
    sschp_assert_contains( 'SSCHP_TEST_BASE_URL: http://localhost:8899', $workflow, 'CI full browser tests should target the local WordPress integration site' );
}

function sschp_test_release_smoke_checklist_covers_manual_risks() {
    $path     = dirname( __DIR__ ) . '/docs/release-smoke-checklist.md';
    $contents = is_file( $path ) ? file_get_contents( $path ) : '';

    sschp_assert_true( is_file( $path ), 'manual release smoke checklist should exist for non-automatable checks' );
    foreach ( array(
        'Freemius checkout',
        'free-to-Pro handoff',
        'Pro-to-free downgrade',
        'real analytics test digest',
        'real WooCommerce catalog',
        'block theme',
        'classic theme',
        'page-builder-like',
        'WordPress.org package',
        'smartsearchchips.app',
    ) as $manual_check ) {
        sschp_assert_contains( $manual_check, $contents, 'manual release smoke checklist should cover: ' . $manual_check );
    }
}

function sschp_test_wporg_pro_promotion_copy_is_contextual_and_compliant() {
    $root           = dirname( __DIR__ ) . '/build-sources/wporg';
    $readme         = $root . '/readme.txt';
    $settings       = $root . '/includes/class-settings.php';
    $frontend       = $root . '/includes/class-frontend.php';
    $search_handler = $root . '/includes/class-search-handler.php';

    $readme_contents   = is_file( $readme ) ? file_get_contents( $readme ) : '';
    $settings_contents = is_file( $settings ) ? file_get_contents( $settings ) : '';
    $frontend_contents = is_file( $frontend ) ? file_get_contents( $frontend ) : '';
    $search_contents   = is_file( $search_handler ) ? file_get_contents( $search_handler ) : '';

    foreach ( array( $readme_contents, $settings_contents ) as $contents ) {
        foreach ( array( 'trial', 'quota', 'free limit', 'unlock included feature', 'unlock included functionality', 'Upgrade to Pro to unlock', 'becomes available in Pro' ) as $forbidden ) {
            sschp_assert_true(
                false === stripos( $contents, $forbidden ),
                'WP.org Pro promotion copy should not use misleading wording: ' . $forbidden
            );
        }
    }

    foreach ( array( 'Custom Fields and ACF', 'Numeric Range Chips', 'Analytics and Reporting', 'Support and Diagnostics' ) as $expected_card ) {
        sschp_assert_contains( $expected_card, $settings_contents, 'WP.org settings should keep contextual Pro card: ' . $expected_card );
    }

    sschp_assert_true( false === stripos( $frontend_contents, 'smartsearchchips.app' ), 'WP.org frontend source should not contain public Pro links' );
    sschp_assert_true( false === stripos( $search_contents, 'smartsearchchips.app' ), 'WP.org search handler should not contain public Pro links' );
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

function sschp_test_range_chip_css_uses_aligned_desktop_thumbs() {
    $path = dirname( __DIR__ ) . '/assets/css/range-filters__premium_only.css';

    sschp_assert_true( is_file( $path ), 'Pro range chip stylesheet should exist' );

    $css = is_file( $path ) ? file_get_contents( $path ) : '';

    sschp_assert_contains( '--ssc-range-track-height: 3px;', $css, 'range chip rail should use a compact desktop track height' );
    sschp_assert_contains( '--ssc-range-thumb-size: 8px;', $css, 'range chip desktop thumb should stay visually compact for pointer precision' );
    sschp_assert_contains( '--ssc-range-hit-size: 22px;', $css, 'range chip desktop drag area should remain easy to target' );
    sschp_assert_contains( '--ssc-range-active-bg: var(--ssc-range-border);', $css, 'range chip active background should default to the normal selected chip color' );
    sschp_assert_contains( '.ssc-range-chip--active {', $css, 'range chip stylesheet should style selected range chips as active chips' );
    sschp_assert_contains( 'background: var(--ssc-range-active-bg);', $css, 'selected range chips should use a filled active background like normal chips' );
    sschp_assert_contains( '.ssc-range-chip--active .ssc-range-chip__sliders::before', $css, 'selected range chip rail should change color on active chips' );
    sschp_assert_contains( 'background: rgba(255, 255, 255, 0.34);', $css, 'selected range chip rail should be softly visible on a filled chip' );
    sschp_assert_contains( '.ssc-range-chip--active .ssc-range-chip__input::-webkit-slider-thumb', $css, 'selected range chip WebKit thumb should invert on active chips' );
    sschp_assert_contains( '.ssc-range-chip--active .ssc-range-chip__input::-moz-range-thumb', $css, 'selected range chip Firefox thumb should invert on active chips' );
    sschp_assert_contains( 'top: 50%;', $css, 'range chip rail should be vertically centered in the drag area' );
    sschp_assert_contains( 'transform: translateY(-50%);', $css, 'range chip rail centering should not depend on hard-coded offsets' );
    sschp_assert_contains( 'margin-top: calc((var(--ssc-range-track-height) - var(--ssc-range-thumb-size)) / 2);', $css, 'range chip WebKit thumb should align from the track/thumb variables' );
    sschp_assert_contains( '@media (pointer: coarse)', $css, 'range chip should keep larger handles for touch/coarse pointers' );
    sschp_assert_contains( '--ssc-range-thumb-size: 13px;', $css, 'range chip coarse pointer handles should remain touch-friendly without looking bulky' );
    sschp_assert_contains( 'flex-direction: row;', $css, 'range chip should keep the label, values, and slider in one compact row' );
    sschp_assert_contains( 'align-items: center;', $css, 'range chip inline content should align with normal chips' );
    sschp_assert_contains( 'min-height: calc(var(--ssc-range-hit-size) + 12px);', $css, 'range chip height should be driven by the slider hit area instead of stacked rows' );
    sschp_assert_contains( 'max-width: 9em;', $css, 'range chip labels should truncate before forcing an oversized chip' );
    sschp_assert_contains( 'white-space: nowrap;', $css, 'range chip label and values should stay inline in the compact layout' );
    sschp_assert_contains( 'flex: 0 1 96px;', $css, 'range chip slider should have a compact inline width' );
    sschp_assert_contains( '.ssc-range-chip__label', $css, 'range chip stylesheet should style a visible inline range label' );
    sschp_assert_contains( 'ssc-range-chip__label', file_get_contents( dirname( __DIR__ ) . '/includes/class-range-filters__premium_only.php' ), 'range chip markup should show the range label instead of exposing only numeric values' );
}

function sschp_test_range_chip_renderer_exports_active_chip_colors() {
    $range = new SSCHP_Range_Filters();
    $chip  = array(
        'type'  => 'range',
        'label' => 'Volume',
        'count' => 4,
        'value' => 'sschp_range|volume|l|12|45|12|45',
        'range' => array(
            'group_key'    => 'volume',
            'unit_key'     => 'l',
            'unit'         => 'L',
            'min'          => 12,
            'max'          => 45,
            'selected_min' => 12,
            'selected_max' => 45,
            'step'         => 1,
        ),
    );
    $html  = $range->render_range_chip(
        '',
        $chip,
        array( 'sschp_range|volume|l|20|45|12|45' ),
        array(
            'context' => 'ajax',
            'styles'  => array(
                'background'        => '#ffffff',
                'color'             => '#111820',
                'border'            => '#087b3f',
                'active_background' => '#087b3f',
                'active_color'      => '#ffffff',
                'radius'            => 8,
                'font_size'         => 13,
            ),
        )
    );

    sschp_assert_contains( 'ssc-range-chip--active', $html, 'active range chip markup should expose the selected chip class' );
    sschp_assert_contains( '--ssc-range-active-bg:#087b3f;', $html, 'active range chip markup should expose the selected chip background variable' );
    sschp_assert_contains( '--ssc-range-active-color:#ffffff;', $html, 'active range chip markup should expose the selected chip text variable' );
    sschp_assert_contains( 'data-range-label="Volume"', $html, 'range chip markup should keep the human range label for UI and analytics events' );
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

function sschp_test_chip_engine_extract_tags_preserves_decimal_commas() {
    $engine = new SSCHP_Chip_Engine();
    $tags   = $engine->extract_tags( 'Zatížitelnost 0,1 A, Zatížitelnost 8,0 A; Max. ovládací síla 1,6 N', '' );

    sschp_assert_same(
        array( 'Zatížitelnost 0,1 A', 'Zatížitelnost 8,0 A', 'Max. ovládací síla 1,6 N' ),
        $tags['tags'],
        'extract_tags should split comma-delimited tags without splitting decimal comma values'
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

function sschp_test_chip_engine_active_tag_survives_zero_results() {
    $engine = new SSCHP_Chip_Engine();
    $chips  = $engine->generate_chips( array(), array( 'Nonexistent Tag' ), 5, 40 );

    sschp_assert_true( isset( $chips['nonexistent tag'] ), 'generate_chips should preserve an active tag when filtering returns zero results' );
    sschp_assert_same( 'Nonexistent Tag', $chips['nonexistent tag']['label'], 'zero-result active tags should preserve their original label in the chip output' );
    sschp_assert_same( 0, $chips['nonexistent tag']['count'], 'zero-result active tags should expose a zero count' );
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

function sschp_test_chip_engine_active_tags_follow_range_chips() {
    global $sschp_test_filters;

    sschp_reset_test_state();

    $sschp_test_filters['sschp_generated_chips'] = array(
        'travel' => array( 'label' => 'travel', 'value' => 'travel', 'count' => 1 ),
        'water resistant' => array( 'label' => 'water resistant', 'value' => 'water resistant', 'count' => 1 ),
        'packable' => array( 'label' => 'packable', 'value' => 'packable', 'count' => 2 ),
        'sschp_range|volume|l' => array(
            'label' => 'Volume',
            'value' => 'sschp_range|volume|l|20|20|20|30',
            'count' => 2,
            'type'  => 'range',
            'range' => array(
                'group_key' => 'volume',
                'unit_key'  => 'l',
                'unit'      => 'L',
            ),
        ),
    );

    $engine = new SSCHP_Chip_Engine();
    $chips  = $engine->generate_chips(
        array(
            array( 'tags' => array( 'travel', 'packable' ), 'tags_lower' => array( 'travel', 'packable' ) ),
            array( 'tags' => array( 'water resistant', 'packable' ), 'tags_lower' => array( 'water resistant', 'packable' ) ),
        ),
        array( 'packable' ),
        8,
        40
    );
    $keys = array_keys( $chips );

    sschp_assert_same( 'sschp_range|volume|l', $keys[0], 'range chips should stay before selected regular chips' );
    sschp_assert_same( 'packable', $keys[1], 'selected regular chips should follow range chips before suggestions' );
    sschp_assert_same( 'travel', $keys[2], 'inactive suggestions should follow selected chips' );

    sschp_reset_test_state();
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

function sschp_test_chip_rules_export_returns_expected_json_shape() {
    sschp_reset_test_state();
    update_option( 'sschp_synonym_rules', 'Sofa = couch, settee' );
    update_option( 'sschp_pinned_chips', 'Bestseller' );
    update_option( 'sschp_hidden_chips', 'Internal' );
    update_option( 'sschp_renamed_chips', 'eco => Eco friendly' );

    $rules   = new SSCHP_Chip_Rules();
    $payload = json_decode( $rules->export_rules_json(), true );

    sschp_assert_same( 'smart-search-chips/chip-rules', $payload['schema'], 'chip rule export should include a stable schema identifier' );
    sschp_assert_same( 1, $payload['version'], 'chip rule export should include a schema version' );
    sschp_assert_same( 'Sofa = couch, settee', $payload['rules']['synonym_rules'], 'chip rule export should include synonym rules' );
    sschp_assert_same( 'Bestseller', $payload['rules']['pinned_chips'], 'chip rule export should include pinned chips' );
    sschp_assert_same( 'Internal', $payload['rules']['hidden_chips'], 'chip rule export should include hidden chips' );
    sschp_assert_same( 'eco => Eco friendly', $payload['rules']['renamed_chips'], 'chip rule export should include renamed chips' );
}

function sschp_test_chip_rules_valid_import_saves_rules() {
    sschp_reset_test_state();

    $rules  = new SSCHP_Chip_Rules();
    $result = $rules->import_rules_json(
        json_encode(
            array(
                'schema' => 'smart-search-chips/chip-rules',
                'version' => 1,
                'rules' => array(
                    'synonym_rules' => " Sofa = couch, settee \n",
                    'pinned_chips'  => array( 'Bestseller', 'New arrival' ),
                    'hidden_chips'  => 'Internal',
                    'renamed_chips' => 'eco => Eco friendly',
                ),
            )
        ),
        false
    );

    sschp_assert_true( $result['valid'], 'valid chip rule import should pass validation' );
    sschp_assert_true( $result['saved'], 'valid chip rule import should save when not in dry-run mode' );
    sschp_assert_same( 'Sofa = couch, settee', get_option( 'sschp_synonym_rules', '' ), 'valid import should save sanitized synonym rules' );
    sschp_assert_same( "Bestseller\nNew arrival", get_option( 'sschp_pinned_chips', '' ), 'valid import should save array chip lists as lines' );
    sschp_assert_same( 'Internal', get_option( 'sschp_hidden_chips', '' ), 'valid import should save hidden chips' );
    sschp_assert_same( 'eco => Eco friendly', get_option( 'sschp_renamed_chips', '' ), 'valid import should save renamed chips' );
}

function sschp_test_chip_rules_import_dry_run_does_not_save_rules() {
    sschp_reset_test_state();
    update_option( 'sschp_pinned_chips', 'Existing' );

    $rules  = new SSCHP_Chip_Rules();
    $result = $rules->import_rules_json(
        json_encode(
            array(
                'rules' => array(
                    'pinned_chips' => 'Preview only',
                ),
            )
        ),
        true
    );

    sschp_assert_true( $result['valid'], 'dry-run chip rule import should validate good JSON' );
    sschp_assert_true( ! $result['saved'], 'dry-run chip rule import should not save' );
    sschp_assert_same( 'Existing', get_option( 'sschp_pinned_chips', '' ), 'dry-run chip rule import should leave existing options unchanged' );
    sschp_assert_same( 1, $result['summary']['pinned_chips'], 'dry-run chip rule import should summarize the previewed rules' );
}

function sschp_test_chip_rules_invalid_import_is_rejected() {
    sschp_reset_test_state();
    update_option( 'sschp_hidden_chips', 'Keep me' );

    $rules  = new SSCHP_Chip_Rules();
    $result = $rules->import_rules_json( '{"rules":', false );

    sschp_assert_true( ! $result['valid'], 'invalid chip rule import should fail validation' );
    sschp_assert_contains( 'Invalid JSON', implode( ' ', $result['errors'] ), 'invalid chip rule import should explain JSON parse failures clearly' );
    sschp_assert_same( 'Keep me', get_option( 'sschp_hidden_chips', '' ), 'invalid chip rule import should not overwrite existing rules' );
}

function sschp_test_chip_rules_unauthorized_import_is_rejected() {
    global $sschp_test_current_user_can;

    sschp_reset_test_state();
    $sschp_test_current_user_can['manage_options'] = false;
    update_option( 'sschp_pinned_chips', 'Existing' );
    $_POST['sschp_chip_rules_json'] = json_encode(
        array(
            'rules' => array(
                'pinned_chips' => 'Should not save',
            ),
        )
    );

    $rules  = new SSCHP_Chip_Rules();
    $result = sschp_capture_wp_die( array( $rules, 'handle_import_chip_rules' ) );

    sschp_assert_true( $result['died'], 'unauthorized chip rule import should stop with wp_die' );
    sschp_assert_contains( 'permission', $result['message'], 'unauthorized chip rule import should explain the permission failure' );
    sschp_assert_same( 'Existing', get_option( 'sschp_pinned_chips', '' ), 'unauthorized chip rule import should not save rules' );
}

function sschp_test_chip_rules_import_export_handlers_are_admin_secured() {
    $path     = dirname( __DIR__ ) . '/includes/class-chip-rules__premium_only.php';
    $contents = is_file( $path ) ? file_get_contents( $path ) : '';

    sschp_assert_contains( 'admin_post_\' . self::EXPORT_ACTION', $contents, 'chip rule export should register an admin-post handler' );
    sschp_assert_contains( 'admin_post_\' . self::IMPORT_ACTION', $contents, 'chip rule import should register an admin-post handler' );
    sschp_assert_contains( "current_user_can( 'manage_options' )", $contents, 'chip rule import/export handlers should require manage_options' );
    sschp_assert_contains( 'check_admin_referer( self::IMPORT_ACTION )', $contents, 'chip rule import handler should verify a nonce' );
}

function sschp_test_chip_rules_preserve_range_chip_keys_for_prioritizer() {
    if ( ! class_exists( 'SSCHP_Range_Filters' ) ) {
        return;
    }

    sschp_reset_test_state();

    $range = new SSCHP_Range_Filters();
    $rules = new SSCHP_Chip_Rules();
    $posts = array(
        sschp_make_range_post( 1, array( '307 mm' ) ),
        sschp_make_range_post( 2, array( '940 mm' ) ),
    );

    $tag_counts = $range->add_range_chips( array(), $posts, array() );
    $range_key  = 'sschp_range|mm|mm';
    $chips      = array(
        $range_key => $tag_counts[ $range_key ],
    );

    $curated = $rules->apply_curation_rules( $chips, $tag_counts, $posts, array(), 4, 40 );

    sschp_assert_true( isset( $curated[ $range_key ] ), 'chip rules should preserve virtual range chip keys' );
    sschp_assert_true( ! isset( $curated[ $chips[ $range_key ]['value'] ] ), 'chip rules should not re-key range chips by encoded range value' );

    $prioritized = $range->prioritize_range_chips( $curated, $tag_counts, $posts, array(), 4, 40 );
    $range_count = 0;
    foreach ( $prioritized as $chip ) {
        if ( is_array( $chip ) && isset( $chip['type'] ) && 'range' === $chip['type'] ) {
            $range_count++;
        }
    }

    sschp_assert_same( 1, $range_count, 'range prioritization should not duplicate a curated range chip' );

    $rekeyed = array(
        $chips[ $range_key ]['value'] => $chips[ $range_key ],
    );
    $prioritized_rekeyed = $range->prioritize_range_chips( $rekeyed, $tag_counts, $posts, array(), 4, 40 );
    $rekeyed_range_count = 0;
    foreach ( $prioritized_rekeyed as $chip ) {
        if ( is_array( $chip ) && isset( $chip['type'] ) && 'range' === $chip['type'] ) {
            $rekeyed_range_count++;
        }
    }

    sschp_assert_same( 1, $rekeyed_range_count, 'range prioritization should recognize an existing range chip even if another filter changed its array key' );
}

function sschp_test_wporg_build_excludes_pro_chip_rules_class() {
    $root      = dirname( __DIR__ );
    $build     = $root . '/scripts/build-release.ps1';
    $wporg     = $root . '/build-sources/wporg';

    sschp_assert_contains( 'includes\class-chip-rules__premium_only.php', file_get_contents( $build ), 'WP.org release build should remove the Pro chip rules class' );
    sschp_assert_true( ! is_file( $wporg . '/includes/class-chip-rules__premium_only.php' ), 'WP.org source overrides should not contain the Pro chip rules class file' );

    $main_contents = file_get_contents( $wporg . '/smart-search-chips.php' );
    sschp_assert_true( false === strpos( $main_contents, 'class-chip-rules__premium_only.php' ), 'WP.org main plugin source should not load the Pro chip rules class' );
    sschp_assert_true( false === strpos( $main_contents, 'SSCHP_Chip_Rules' ), 'WP.org main plugin source should not reference the Pro chip rules class' );

    $settings_contents = file_get_contents( $wporg . '/includes/class-settings.php' );
    sschp_assert_true( false === strpos( $settings_contents, 'SSCHP_Chip_Rules' ), 'WP.org settings source should not reference the Pro chip rules class' );
    sschp_assert_true( false === strpos( $settings_contents, 'sschp_export_chip_rules' ), 'WP.org settings source should not expose Pro chip rule export handlers' );
    sschp_assert_true( false === strpos( $settings_contents, 'sschp_import_chip_rules' ), 'WP.org settings source should not expose Pro chip rule import handlers' );

    $validator_contents = file_get_contents( $root . '/scripts/validate-wporg-package.php' );
    sschp_assert_contains( 'SSCHP_Chip_Rules', $validator_contents, 'WP.org package validation should reject Pro chip rule class references' );
    sschp_assert_contains( 'sschp_import_chip_rules', $validator_contents, 'WP.org package validation should reject Pro chip rule import handlers' );
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

function sschp_test_range_filters_can_link_current_ranges_to_voltage_values() {
    $range = new SSCHP_Range_Filters();
    $posts = array(
        sschp_make_range_post( 1, array( '690 V => 63 A' ) ),
        sschp_make_range_post( 2, array( '690 V => 80 A' ) ),
        sschp_make_range_post( 3, array( '400 V => 16 A' ) ),
        sschp_make_range_post( 4, array( '400 V => 25 A' ) ),
    );
    $chips = $range->add_range_chips(
        array(),
        $posts,
        array()
    );

    sschp_assert_true( isset( $chips['sschp_range|v|v'] ), 'voltage range should remain available when current is linked to voltage' );
    sschp_assert_true( ! isset( $chips['sschp_range|a|a'] ), 'linked current values should not be mixed into one unqualified current slider' );
    sschp_assert_true( isset( $chips['sschp_range|a-at-v-690-v|a'] ), 'current values at 690 V should create their own range chip' );
    sschp_assert_true( isset( $chips['sschp_range|a-at-v-400-v|a'] ), 'current values at 400 V should create their own range chip' );
    sschp_assert_same( 'A at 690 V', $chips['sschp_range|a-at-v-690-v|a']['label'], 'linked current chip should expose the voltage context in its label' );
    sschp_assert_same( 63.0, $chips['sschp_range|a-at-v-690-v|a']['range']['min'], 'linked 690 V current range should keep its own minimum' );
    sschp_assert_same( 80.0, $chips['sschp_range|a-at-v-690-v|a']['range']['max'], 'linked 690 V current range should keep its own maximum' );

    $filtered = $range->filter_posts_by_range_tags( null, $posts, array( $chips['sschp_range|a-at-v-690-v|a']['value'] ) );
    sschp_assert_same( array( 1, 2 ), array_map( 'intval', array_column( $filtered, 'id' ) ), 'active linked 690 V current range should not match current values from another voltage group' );
}

function sschp_test_range_filters_do_not_link_plain_language_between_voltage_and_current() {
    $range = new SSCHP_Range_Filters();
    $chips = $range->add_range_chips(
        array(),
        array(
            sschp_make_range_post( 1, array( '690 V pri 63 A' ) ),
            sschp_make_range_post( 2, array( '690 V při 80 A' ) ),
            sschp_make_range_post( 3, array( '400 V pri 16 A' ) ),
            sschp_make_range_post( 4, array( '400 V při 25 A' ) ),
        ),
        array()
    );

    sschp_assert_true( isset( $chips['sschp_range|a|a'] ), 'plain language current values should stay in the normal current range' );
    sschp_assert_true( ! isset( $chips['sschp_range|a-at-v-690-v|a'] ), 'plain language pri/při should not create linked 690 V current range' );
    sschp_assert_true( ! isset( $chips['sschp_range|a-at-v-400-v|a'] ), 'plain language pri/při should not create linked 400 V current range' );
}

function sschp_test_range_filters_create_asa_ampere_and_newton_sliders() {
    $range = new SSCHP_Range_Filters();
    $chips = $range->add_range_chips(
        array(),
        array(
            sschp_make_range_post( 1, array( 'Zatížitelnost 0,1 A', 'Max. ovládací síla 1,6 N' ) ),
            sschp_make_range_post( 2, array( 'Zatížitelnost 8,0 A', 'Max. ovládací síla 2,2 N' ) ),
            sschp_make_range_post( 3, array( 'Zatížitelnost 9,0 A', 'Max. ovládací síla 3,3 N' ) ),
        ),
        array()
    );

    sschp_assert_true( isset( $chips['sschp_range|zatizitelnost|a'] ), 'ampere facts should create a range chip for ASA load capacity values' );
    sschp_assert_true( isset( $chips['sschp_range|max-ovladaci-sila|n'] ), 'newton facts should create a range chip for ASA actuation force values' );
    sschp_assert_same( 0.1, $chips['sschp_range|zatizitelnost|a']['range']['min'], 'ampere slider should parse Czech decimal commas' );
    sschp_assert_same( 9.0, $chips['sschp_range|zatizitelnost|a']['range']['max'], 'ampere slider should preserve the maximum load value' );
    sschp_assert_same( 1.6, $chips['sschp_range|max-ovladaci-sila|n']['range']['min'], 'newton slider should parse Czech decimal commas' );
    sschp_assert_same( 3.3, $chips['sschp_range|max-ovladaci-sila|n']['range']['max'], 'newton slider should preserve the maximum force value' );
}

function sschp_test_range_filters_create_valve_sliders_from_dn_pn_and_bar_values() {
    $range = new SSCHP_Range_Filters();
    $chips = $range->add_range_chips(
        array(),
        array(
            sschp_make_range_post( 1, array( 'DN 15', 'PN 16', 'Pressure: 6 bar' ), 'Ventil DN 15 PN 16' ),
            sschp_make_range_post( 2, array( 'DN 20', 'PN 25', 'Pressure: 10 bar' ), 'Ventil DN 20 PN 25' ),
            sschp_make_range_post( 3, array( 'DN 25', 'PN 40', 'Pressure: 16 bar' ), 'Ventil DN 25 PN 40' ),
        ),
        array()
    );

    sschp_assert_true( isset( $chips['sschp_range|dn|dn'] ), 'DN values should create a valve size range chip' );
    sschp_assert_true( isset( $chips['sschp_range|pn|pn'] ), 'PN values should create a valve pressure class range chip' );
    sschp_assert_true( isset( $chips['sschp_range|pressure|bar'] ), 'bar values should create a pressure range chip' );
    sschp_assert_same( 15.0, $chips['sschp_range|dn|dn']['range']['min'], 'DN range should preserve the minimum nominal size' );
    sschp_assert_same( 25.0, $chips['sschp_range|dn|dn']['range']['max'], 'DN range should preserve the maximum nominal size' );
    sschp_assert_same( 16.0, $chips['sschp_range|pn|pn']['range']['min'], 'PN range should not treat the preceding DN value as a PN value' );
    sschp_assert_same( 6.0, $chips['sschp_range|pressure|bar']['range']['min'], 'bar range should preserve the minimum pressure' );
    sschp_assert_same( 'bar', $chips['sschp_range|pressure|bar']['range']['unit'], 'bar range should keep bar as its unit' );
}

function sschp_test_range_filters_prioritize_ranges_when_normal_chips_fill_limit() {
    $range      = new SSCHP_Range_Filters();
    $tag_counts = $range->add_range_chips(
        array(
            'brand a' => array( 'label' => 'Brand A', 'value' => 'Brand A', 'count' => 9 ),
            'steel'   => array( 'label' => 'Steel', 'value' => 'Steel', 'count' => 8 ),
            'indoor'  => array( 'label' => 'Indoor', 'value' => 'Indoor', 'count' => 7 ),
            'thread'  => array( 'label' => 'Thread', 'value' => 'Thread', 'count' => 6 ),
        ),
        array(
            sschp_make_range_post( 1, array( 'DN 15' ) ),
            sschp_make_range_post( 2, array( 'DN 20' ) ),
        ),
        array()
    );

    $chips = array(
        'brand a' => $tag_counts['brand a'],
        'steel'   => $tag_counts['steel'],
        'indoor'  => $tag_counts['indoor'],
        'thread'  => $tag_counts['thread'],
    );

    $prioritized = $range->prioritize_range_chips( $chips, $tag_counts, array(), array(), 4, 40 );

    sschp_assert_true( isset( $prioritized['sschp_range|dn|dn'] ), 'accepted range chips should remain visible when normal chips fill the max chip limit' );
    sschp_assert_same( 4, count( $prioritized ), 'range prioritization should respect the max chip limit' );
}

function sschp_test_range_filters_selected_chips_follow_range_chips() {
    $range      = new SSCHP_Range_Filters();
    $tag_counts = $range->add_range_chips(
        array(
            'travel'          => array( 'label' => 'travel', 'value' => 'travel', 'count' => 1 ),
            'water resistant' => array( 'label' => 'water resistant', 'value' => 'water resistant', 'count' => 1 ),
            'carry-on'        => array( 'label' => 'carry-on', 'value' => 'carry-on', 'count' => 1 ),
            'packable'        => array( 'label' => 'packable', 'value' => 'packable', 'count' => 2 ),
        ),
        array(
            sschp_make_range_post( 1, array( 'DN 15' ) ),
            sschp_make_range_post( 2, array( 'DN 20' ) ),
        ),
        array( 'packable' )
    );

    $chips = array(
        'travel'          => $tag_counts['travel'],
        'water resistant' => $tag_counts['water resistant'],
        'carry-on'        => $tag_counts['carry-on'],
        'packable'        => $tag_counts['packable'],
    );

    $prioritized = $range->prioritize_range_chips( $chips, $tag_counts, array(), array( 'packable' ), 5, 40 );
    $keys        = array_keys( $prioritized );

    sschp_assert_same( 'sschp_range|dn|dn', $keys[0], 'range chips should be the first visible controls' );
    sschp_assert_same( 'packable', $keys[1], 'selected regular chips should be pinned immediately after range chips' );
    sschp_assert_same( 'travel', $keys[2], 'inactive suggestions should remain after selected chips' );
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

function sschp_test_range_filters_create_sliders_from_title_shared_unit_series() {
    $range = new SSCHP_Range_Filters();
    $chips = $range->add_range_chips(
        array(),
        array(
            sschp_make_range_post( 1, array(), 'Miniaturní DC ventilátor (40 / 60 mm)' ),
            sschp_make_range_post( 2, array(), 'Kruhový axiální ventilátor (Ø 220 / Ø 254 mm)' ),
            sschp_make_range_post( 3, array(), 'Axiální ventilátor 172×150 mm (velký, AC / DC)' ),
            sschp_make_range_post( 4, array(), 'Superkompaktní ventilátorové PTC topení TMC (200–350 W)' ),
            sschp_make_range_post( 5, array(), 'Ventilátorové PTC topení TMX Touch-Safe (150-600 W)' ),
        ),
        array()
    );

    sschp_assert_true( isset( $chips['sschp_range|mm|mm'] ), 'shared trailing-unit dimensions in titles should create an mm range chip' );
    sschp_assert_same( 40.0, $chips['sschp_range|mm|mm']['range']['min'], 'slash dimensions should expose the lowest title dimension' );
    sschp_assert_same( 254.0, $chips['sschp_range|mm|mm']['range']['max'], 'diameter dimensions should expose the highest title dimension' );

    sschp_assert_true( isset( $chips['sschp_range|w|w'] ), 'shared trailing-unit wattage ranges in titles should create a watt range chip' );
    sschp_assert_same( 150.0, $chips['sschp_range|w|w']['range']['min'], 'dash ranges should expose the lowest title wattage' );
    sschp_assert_same( 600.0, $chips['sschp_range|w|w']['range']['max'], 'dash ranges should expose the highest title wattage' );
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

function sschp_test_range_debug_report_splits_accepted_and_rejected_groups() {
    $range  = new SSCHP_Range_Filters();
    $report = $range->get_debug_report(
        array(
            sschp_make_range_post( 1, array( 'Power: 100 W', 'Weight: 5 kg' ) ),
            sschp_make_range_post( 2, array( 'Power: 200 W' ) ),
        )
    );

    sschp_assert_same( 1, count( $report['accepted_groups'] ), 'range debug should split accepted groups into their own list' );
    sschp_assert_same( 'power', $report['accepted_groups'][0]['group_key'], 'power should be accepted when two products have varied wattage' );
    sschp_assert_true( isset( $report['accepted_groups'][0]['confidence'] ), 'accepted range groups should expose a confidence score' );

    sschp_assert_same( 1, count( $report['rejected_groups'] ), 'range debug should split rejected groups into their own list' );
    sschp_assert_same( 'weight', $report['rejected_groups'][0]['group_key'], 'single-product weight should be rejected as its own group' );
    sschp_assert_true( in_array( 'too_few_products', $report['rejected_groups'][0]['reason_codes'], true ), 'rejected groups should expose a stable too-few-products code' );
}

function sschp_test_range_debug_report_uses_structured_rejection_reason_codes() {
    $range  = new SSCHP_Range_Filters();
    $report = $range->get_debug_report(
        array(
            sschp_make_range_post( 1, array( 'IP65', 'Model A100', 'SKU-12345' ), 'Only $99 today' ),
        )
    );

    $codes = array_map(
        function( $rejected ) {
            return isset( $rejected['reason_code'] ) ? $rejected['reason_code'] : '';
        },
        $report['products'][0]['rejected']
    );

    foreach ( array( 'ip_rating', 'model_number', 'sku_like_value', 'price_like_value' ) as $expected_code ) {
        sschp_assert_true( in_array( $expected_code, $codes, true ), 'range debug should expose rejection reason code: ' . $expected_code );
    }

    sschp_assert_contains( 'IP ratings are categorical', implode( ' ', $report['products'][0]['rejected'][0] ), 'range debug should keep human-readable rejection messages' );
}

function sschp_test_range_debug_report_explains_mixed_units_and_low_confidence() {
    $range = new SSCHP_Range_Filters();

    $mixed_report = $range->get_debug_report(
        array(
            sschp_make_range_post( 1, array( 'Size: 10 cm' ) ),
            sschp_make_range_post( 2, array( 'Size: 2 kg' ) ),
        )
    );
    $mixed_codes = array();
    foreach ( $mixed_report['rejected_groups'] as $group ) {
        $mixed_codes = array_merge( $mixed_codes, $group['reason_codes'] );
    }
    sschp_assert_true( in_array( 'mixed_units', $mixed_codes, true ), 'range debug should explain groups rejected because their units are mixed' );

    $low_confidence_report = $range->get_debug_report(
        array(
            sschp_make_range_post( 3, array(), '100 W pump' ),
            sschp_make_range_post( 4, array(), '200 W pump' ),
        )
    );
    $low_confidence_codes = array();
    foreach ( $low_confidence_report['rejected_groups'] as $group ) {
        $low_confidence_codes = array_merge( $low_confidence_codes, $group['reason_codes'] );
    }
    sschp_assert_true( in_array( 'low_confidence', $low_confidence_codes, true ), 'range debug should explain groups rejected because confidence is low' );
}

function sschp_test_range_debug_report_labels_sources_for_non_developers() {
    global $sschp_test_acf_fields;

    sschp_reset_test_state();
    sschp_register_test_product(
        31,
        array(
            new SSCHP_Test_Product_Attribute( 'pa_power', array( '100 W' ) ),
        ),
        '',
        array(
            'pa_power' => array( '200 W' ),
        )
    );
    update_option( 'sschp_tag_fields', 'power_w' );
    $sschp_test_acf_fields[31]['power_w'] = '300';

    $range  = new SSCHP_Range_Filters();
    $report = $range->get_debug_report(
        array(
            sschp_make_range_post( 31, array(), 'Fallback: 400 W' ),
        )
    );

    $source_labels = array();
    foreach ( $report['products'][0]['facts'] as $fact ) {
        $source_labels[] = isset( $fact['source_label'] ) ? $fact['source_label'] : '';
    }

    foreach ( array( 'WooCommerce attribute', 'Mapped custom field', 'Taxonomy', 'Title/content fallback' ) as $expected_label ) {
        sschp_assert_true( in_array( $expected_label, $source_labels, true ), 'range debug should show non-developer source label: ' . $expected_label );
    }
}

function sschp_test_range_debug_context_uses_search_query_first() {
    global $sschp_test_options, $sschp_test_post_meta;

    sschp_reset_test_state();
    $sschp_test_options['sschp_post_types'] = array( 'product' );
    sschp_register_test_post_for_index( 41, 'product', 'AULED compact 307 mm fan' );
    sschp_register_test_post_for_index( 42, 'product', 'AULED tall 940 mm fan' );
    sschp_register_test_post_for_index( 43, 'product', 'Different product 500 mm' );
    $sschp_test_post_meta[41][SSCHP_Meta_Box::META_KEY] = '307 mm';
    $sschp_test_post_meta[42][SSCHP_Meta_Box::META_KEY] = '940 mm';
    $sschp_test_post_meta[43][SSCHP_Meta_Box::META_KEY] = '500 mm';

    $range   = new SSCHP_Range_Filters();
    $context = $range->get_debug_context(
        array(
            'query'      => 'auled',
            'post_types' => array( 'product' ),
            'limit'      => 10,
        )
    );

    sschp_assert_same( 'query', $context['mode'], 'range diagnostics should use a normal search query as the primary flow' );
    sschp_assert_same( 2, $context['post_count'], 'query-based range diagnostics should inspect matching products only' );
    sschp_assert_same( 'wp_query_fallback', $context['search_path'], 'query-based range diagnostics should expose the shared search pipeline path' );
    sschp_assert_same( 'WP_Query fallback', $context['search_path_label'], 'query-based range diagnostics should expose a human search path label' );
    sschp_assert_same( 1, count( $context['report']['accepted_groups'] ), 'query-based range diagnostics should evaluate accepted range groups' );
    sschp_assert_same( 'mm', $context['report']['accepted_groups'][0]['unit_key'], 'query-based range diagnostics should keep range units in the report' );
}

function sschp_test_range_debug_context_reuses_search_handler_pipeline() {
    global $sschp_test_options, $sschp_test_post_meta;

    $range_source   = file_get_contents( dirname( __DIR__ ) . '/includes/class-range-filters__premium_only.php' );
    $handler_source = file_get_contents( dirname( __DIR__ ) . '/includes/class-search-handler.php' );
    $wporg_handler  = file_get_contents( dirname( __DIR__ ) . '/build-sources/wporg/includes/class-search-handler.php' );

    sschp_assert_contains( 'get_diagnostic_posts_data', $handler_source, 'search handler should expose reusable diagnostic post data for internal tools' );
    sschp_assert_contains( 'get_diagnostic_posts_data', $wporg_handler, 'WP.org search handler should keep reusable diagnostic post data for free admin tools' );
    sschp_assert_contains( 'SSCHP_Search_Handler', $range_source, 'range diagnostics should reuse the shared search handler instead of owning a separate search path' );
    sschp_assert_contains( 'Range Diagnostics uses the same server-side query pipeline as Test Search', $range_source, 'range diagnostics UI should disclose the shared search path' );

    sschp_reset_test_state();
    $sschp_test_options['sschp_post_types']                  = array( 'product' );
    $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION]   = 'complete';
    $sschp_test_options[SSCHP_Search_Index::TOTAL_OPTION]    = 2;
    $sschp_test_options[SSCHP_Search_Index::PROCESSED_OPTION] = 2;

    $first  = sschp_register_test_post_for_index( 331, 'product', 'Indexed AULED 307 mm fan', 'Indexed range detail.' );
    $second = sschp_register_test_post_for_index( 332, 'product', 'Indexed AULED 940 mm fan', 'Indexed range detail.' );
    $sschp_test_post_meta[331][SSCHP_Meta_Box::META_KEY] = '307 mm';
    $sschp_test_post_meta[332][SSCHP_Meta_Box::META_KEY] = '940 mm';

    $engine = new SSCHP_Chip_Engine();
    $index  = new SSCHP_Search_Index( new SSCHP_Test_Settings( array( 'post_types' => array( 'product' ) ) ), $engine );
    $index->index_post( 331, $first );
    $index->index_post( 332, $second );

    $context = ( new SSCHP_Range_Filters() )->get_debug_context(
        array(
            'query'      => 'auled',
            'post_types' => array( 'product' ),
            'limit'      => 10,
        )
    );

    sschp_assert_same( 'local_index', $context['search_path'], 'range diagnostics should use the shared local-index search path when the index is ready' );
    sschp_assert_same( 2, $context['post_count'], 'range diagnostics should inspect the posts returned by the shared diagnostic search pipeline' );
    sschp_assert_same( 1, count( $context['report']['accepted_groups'] ), 'range diagnostics should build range groups from shared search results' );
}

function sschp_test_range_debug_context_uses_ids_as_advanced_override() {
    global $sschp_test_post_meta;

    sschp_reset_test_state();
    sschp_register_test_post_for_index( 51, 'product', 'Selected 100 W product' );
    sschp_register_test_post_for_index( 52, 'product', 'Selected 200 W product' );
    sschp_register_test_post_for_index( 53, 'product', 'Search-only 300 W product' );
    $sschp_test_post_meta[51][SSCHP_Meta_Box::META_KEY] = '100 W';
    $sschp_test_post_meta[52][SSCHP_Meta_Box::META_KEY] = '200 W';
    $sschp_test_post_meta[53][SSCHP_Meta_Box::META_KEY] = '300 W';

    $range   = new SSCHP_Range_Filters();
    $context = $range->get_debug_context(
        array(
            'query'      => 'product',
            'post_types' => array( 'product' ),
            'raw_ids'    => '51, 52',
            'limit'      => 10,
        )
    );

    sschp_assert_same( 'ids', $context['mode'], 'range diagnostics exact IDs should remain available as an advanced override' );
    sschp_assert_same( array( 51, 52 ), $context['post_ids'], 'range diagnostics should parse exact IDs from the advanced field' );
    sschp_assert_same( 2, $context['post_count'], 'range diagnostics should inspect exact IDs instead of all query matches when IDs are entered' );
    sschp_assert_same( 200.0, $context['report']['accepted_groups'][0]['max'], 'advanced ID override should exclude query-only products from the range report' );
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

function sschp_test_frontend_registers_legacy_search_shortcode_alias() {
    global $sschp_test_shortcodes;

    sschp_reset_test_state();

    $frontend = new SSCHP_Frontend( new SSCHP_Test_Settings(), new SSCHP_Chip_Engine() );
    $frontend->init();

    sschp_assert_true( isset( $sschp_test_shortcodes['sschp_search'] ), 'frontend should register the documented guaranteed search shortcode' );
    sschp_assert_true( isset( $sschp_test_shortcodes['ssc_search'] ), 'frontend should register the legacy search shortcode alias' );
    sschp_assert_true( is_callable( $sschp_test_shortcodes['ssc_search'] ), 'legacy shortcode alias should render through the normal search form callback' );

    $markup = call_user_func( $sschp_test_shortcodes['ssc_search'], array( 'placeholder' => 'Try searching: tea' ) );

    sschp_assert_contains( 'placeholder="Try searching: tea"', $markup, 'legacy shortcode alias should pass attributes to the guaranteed search form' );
    sschp_assert_contains( 'data-sschp-mode="guaranteed"', $markup, 'legacy shortcode alias should render the guaranteed search UI' );
}

function sschp_test_frontend_registers_guaranteed_search_block() {
    global $sschp_test_registered_blocks;

    sschp_reset_test_state();

    $frontend = new SSCHP_Frontend( new SSCHP_Test_Settings(), new SSCHP_Chip_Engine() );
    $frontend->register_search_block();

    sschp_assert_true( isset( $sschp_test_registered_blocks['smart-search-chips/search'] ), 'frontend should register the guaranteed search block' );
    sschp_assert_same( 'smart-search-chips/search', $sschp_test_registered_blocks['smart-search-chips/search']['metadata']['name'], 'registered block metadata should preserve the block name' );
    sschp_assert_true( is_callable( $sschp_test_registered_blocks['smart-search-chips/search']['args']['render_callback'] ), 'search block should be server rendered' );
}

function sschp_test_frontend_search_block_renders_shortcode_ui() {
    sschp_reset_test_state();

    $frontend = new SSCHP_Frontend( new SSCHP_Test_Settings(), new SSCHP_Chip_Engine() );
    $markup   = $frontend->render_search_block(
        array(
            'placeholder' => 'Find products',
            'className'   => 'custom-search-block',
        )
    );

    sschp_assert_contains( 'placeholder="Find products"', $markup, 'search block should render editable placeholder text' );
    sschp_assert_contains( 'custom-search-block', $markup, 'search block should pass through the optional custom class' );
    sschp_assert_contains( 'data-sschp-mode="guaranteed"', $markup, 'search block should use the guaranteed search UI' );
    sschp_assert_contains( 'js-ssc-search', $markup, 'search block should render the same frontend search form as the shortcode' );
    sschp_assert_contains( 'js-ssc-results', $markup, 'search block should render the same live result container as the shortcode' );
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

function sschp_test_search_handler_results_do_not_show_per_product_chip_badge() {
    $handler = new SSCHP_Search_Handler( new SSCHP_Chip_Engine(), new SSCHP_Test_Settings() );

    ob_start();
    sschp_invoke_private(
        $handler,
        'render_results',
        array(
            array(
                array(
                    'id'         => 77,
                    'url'        => 'https://example.test/product/beacon',
                    'thumbnail'  => '',
                    'title'      => 'Signal Beacon',
                    'post_type'  => 'product',
                    'tags'       => array( 'Industrial', '24 V DC' ),
                    'tags_lower' => array( 'industrial', '24 v dc' ),
                ),
            ),
            10,
        )
    );
    $markup = ob_get_clean();

    sschp_assert_contains( 'Signal Beacon', $markup, 'AJAX result cards should still render the result title' );
    sschp_assert_true( false === strpos( $markup, 'ssc-result__chip-count' ), 'AJAX result cards should not render per-product chip count badges' );
    sschp_assert_true( false === strpos( $markup, '2 chips' ), 'AJAX result cards should not expose tag counts as visitor-facing product metadata' );
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

function sschp_enable_pro_for_unit_tests() {
    global $sschp_test_freemius;

    $sschp_test_freemius          = new SSCHP_Test_Freemius();
    $sschp_test_freemius->premium = true;
}

function sschp_register_ai_item_browser_fixture() {
    global $sschp_test_current_user_can, $sschp_test_taxonomies, $sschp_test_terms, $sschp_test_post_terms;

    sschp_reset_test_state();
    sschp_enable_pro_for_unit_tests();

    $sschp_test_current_user_can['manage_options'] = true;
    update_option( 'sschp_post_types', array( 'product' ) );

    $taxonomy = (object) array(
        'name'         => 'product_cat',
        'labels'       => (object) array(
            'name'          => 'Product categories',
            'singular_name' => 'Product category',
        ),
        'public'       => true,
        'show_ui'      => true,
        'hierarchical' => true,
        'object_type'  => array( 'product' ),
    );
    $term     = (object) array(
        'term_id'  => 7,
        'taxonomy' => 'product_cat',
        'name'     => 'Valves',
        'slug'     => 'valves',
        'count'    => 4,
        'parent'   => 0,
    );

    $sschp_test_taxonomies['product_cat'] = $taxonomy;
    $sschp_test_terms['product_cat|7']    = $term;

    $titles = array(
        401 => 'Alpha Valve',
        402 => 'Beta Valve',
        403 => 'Delta Valve',
        404 => 'Gamma Valve',
    );

    foreach ( $titles as $post_id => $title ) {
        sschp_register_test_post_for_index( $post_id, 'product', $title, 'Catalog item.', 'publish' );
        $sschp_test_post_terms[ $post_id . '|product_cat' ] = array( $term );
    }
}

function sschp_test_ai_rest_writeback_defaults_to_manual_mode() {
    sschp_reset_test_state();

    $tagger = new SSCHP_AI_Tagger( new SSCHP_Test_Settings() );
    $config = sschp_invoke_private( $tagger, 'get_request_config' );

    sschp_assert_same( '0', $config['rest_enabled'], 'AI REST write-back should default to manual review when no request value or saved option exists' );

    $_POST['rest_enabled'] = '1';
    $config                = sschp_invoke_private( $tagger, 'get_request_config' );

    sschp_assert_same( '1', $config['rest_enabled'], 'AI REST write-back should turn on only when the admin submits the REST checkbox value' );

    sschp_reset_test_state();
    $saved_on_config = sschp_invoke_private( new SSCHP_AI_Tagger( new SSCHP_Test_Settings( array( 'ai_rest_enabled' => '1' ) ) ), 'get_request_config' );

    sschp_assert_same( '1', $saved_on_config['rest_enabled'], 'AI prompt requests should honor an explicitly saved REST write-back setting' );
}

function sschp_test_ai_item_browser_uses_bounded_paging() {
    global $sschp_test_last_get_posts_args;

    sschp_register_ai_item_browser_fixture();

    $_POST = array(
        'nonce'    => 'test-nonce',
        'taxonomy' => 'product_cat',
        'term_id'  => '7',
        'page'     => '1',
        'per_page' => '2',
    );

    $tagger = new SSCHP_AI_Tagger( new SSCHP_Test_Settings() );
    $result = sschp_capture_wp_die(
        function() use ( $tagger ) {
            $tagger->ajax_load_term_items();
        }
    );
    $data   = sschp_decode_json_die_message( $result );

    sschp_assert_true( $result['died'], 'AI item browser should complete the AJAX request with a JSON response' );
    sschp_assert_same( 2, $data['count'], 'AI item browser should return only the requested page size' );
    sschp_assert_same( true, $data['hasMore'], 'AI item browser should report more items when the sentinel row exists' );
    sschp_assert_same( 2, $data['perPage'], 'AI item browser response should expose the bounded page size' );
    sschp_assert_same( array( 401, 402 ), wp_list_pluck( $data['items'], 'id' ), 'AI item browser should return the first sorted page of selected term items' );
    sschp_assert_same( 3, $sschp_test_last_get_posts_args['posts_per_page'], 'AI item browser should request one extra sentinel row, not the whole catalog' );
    sschp_assert_same( 0, $sschp_test_last_get_posts_args['offset'], 'AI item browser first page should start at offset zero' );
    sschp_assert_same( array( 'product' ), $sschp_test_last_get_posts_args['post_type'], 'AI item browser should query only configured post types that belong to the taxonomy' );
}

function sschp_test_ai_item_browser_load_more_uses_non_overlapping_offsets() {
    global $sschp_test_last_get_posts_args;

    sschp_register_ai_item_browser_fixture();

    $_POST = array(
        'nonce'    => 'test-nonce',
        'taxonomy' => 'product_cat',
        'term_id'  => '7',
        'page'     => '2',
        'per_page' => '2',
    );

    $tagger = new SSCHP_AI_Tagger( new SSCHP_Test_Settings() );
    $result = sschp_capture_wp_die(
        function() use ( $tagger ) {
            $tagger->ajax_load_term_items();
        }
    );
    $data   = sschp_decode_json_die_message( $result );

    sschp_assert_same( 2, $sschp_test_last_get_posts_args['offset'], 'AI item browser load-more pages should advance by the displayed page size, not the sentinel size' );
    sschp_assert_same( array( 403, 404 ), wp_list_pluck( $data['items'], 'id' ), 'AI item browser load-more should not skip the first item after the sentinel row' );
    sschp_assert_same( false, $data['hasMore'], 'AI item browser should stop showing load more after the final page' );
}

function sschp_test_ai_item_browser_caps_large_page_sizes() {
    global $sschp_test_last_get_posts_args;

    sschp_register_ai_item_browser_fixture();

    $_POST = array(
        'nonce'    => 'test-nonce',
        'taxonomy' => 'product_cat',
        'term_id'  => '7',
        'page'     => '1',
        'per_page' => '999',
    );

    $tagger = new SSCHP_AI_Tagger( new SSCHP_Test_Settings() );
    $result = sschp_capture_wp_die(
        function() use ( $tagger ) {
            $tagger->ajax_load_term_items();
        }
    );
    $data   = sschp_decode_json_die_message( $result );

    sschp_assert_same( 100, $data['perPage'], 'AI item browser should cap oversized requested page sizes at 100' );
    sschp_assert_same( 101, $sschp_test_last_get_posts_args['posts_per_page'], 'AI item browser should still use only one bounded sentinel row after capping' );
}

function sschp_test_ai_item_browser_requires_manage_options() {
    sschp_register_ai_item_browser_fixture();

    global $sschp_test_current_user_can;
    $sschp_test_current_user_can['manage_options'] = false;

    $_POST = array(
        'nonce'    => 'test-nonce',
        'taxonomy' => 'product_cat',
        'term_id'  => '7',
    );

    $tagger = new SSCHP_AI_Tagger( new SSCHP_Test_Settings() );
    $result = sschp_capture_wp_die(
        function() use ( $tagger ) {
            $tagger->ajax_load_term_items();
        }
    );

    sschp_assert_true( $result['died'], 'AI item browser should stop unauthorized AJAX requests' );
    sschp_assert_contains( 'permission', $result['message'], 'AI item browser should explain the manage_options requirement' );
}

function sschp_test_ai_settings_card_wraps_narrow_copy() {
    $css = file_get_contents( dirname( __DIR__ ) . '/assets/css/settings.css' );

    sschp_assert_contains( '.ssc-settings-card--ai .form-table { table-layout: fixed; width: 100%; }', $css, 'AI settings table should not push past the settings card at narrow widths' );
    sschp_assert_contains( '.ssc-settings-card--ai .form-table td { min-width: 0; overflow-wrap: anywhere; }', $css, 'AI settings table cells should wrap long explanatory copy' );
    sschp_assert_contains( '.ssc-ai-toggle { display: inline-flex; align-items: flex-start; flex-wrap: wrap; max-width: 100%; gap: 10px; font-weight: 600; }', $css, 'AI REST toggle label should wrap instead of widening the card' );
    sschp_assert_contains( '.ssc-ai-toggle span { min-width: 0; overflow-wrap: anywhere; }', $css, 'AI REST toggle text should stay inside its card' );
}

function sschp_test_ai_prompt_picker_keeps_selection_visible_when_narrow() {
    $css = file_get_contents( dirname( __DIR__ ) . '/assets/css/settings.css' );

    sschp_assert_contains( '.ssc-settings-card--ai-lab .form-table { table-layout: fixed; width: 100%; }', $css, 'AI prompt picker table should stay inside the settings card' );
    sschp_assert_contains( '.ssc-ai-picker > * { min-width: 0; }', $css, 'AI prompt picker columns should be allowed to shrink' );
    sschp_assert_contains( '.ssc-ai-picker__selection { order: -1; }', $css, 'selected items panel should appear before the browser when the picker stacks' );
    sschp_assert_contains( '.ssc-ai-browser-results { max-height: 420px; }', $css, 'narrow AI browser results should stay scrollable so selected items remain reachable' );
    sschp_assert_contains( '.ssc-ai-selected-items { max-height: 320px; }', $css, 'narrow selected items list should remain visible without taking over the page' );
}

function sschp_test_free_admin_menu_does_not_add_analytics_upsell_page() {
    $contents       = sschp_normalize_newlines( file_get_contents( dirname( __DIR__ ) . '/includes/class-settings.php' ) );
    $wporg_contents = sschp_normalize_newlines( file_get_contents( dirname( __DIR__ ) . '/build-sources/wporg/includes/class-settings.php' ) );
    $plugin_file    = file_get_contents( dirname( __DIR__ ) . '/smart-search-chips.php' );

    sschp_assert_contains( '$this->settings_page_hooks[] = add_menu_page(', $contents, 'Plugin settings should register as a top-level admin menu' );
    sschp_assert_contains( '$this->settings_page_hooks[] = add_menu_page(', $wporg_contents, 'WP.org settings should register as a top-level admin menu' );
    sschp_assert_contains( "if ( SSCHP_License::is_pro() ) {\n            \$this->analytics_page_hooks[] = add_submenu_page(\n                self::MENU_SLUG,\n                __( 'Search Chips Analytics'", $contents, 'Analytics menu page should only be registered for Pro sites' );
    sschp_assert_contains( "'first-path'     => 'admin.php?page=smart-search-chips'", $plugin_file, 'Freemius first path should point at the top-level admin page' );
    sschp_assert_true( false === strpos( $wporg_contents, 'smart-search-chips-analytics' ), 'WP.org settings should not register the Pro analytics page' );
}

function sschp_test_frontend_count_markup_is_supported_in_both_editions() {
    $search_handler       = file_get_contents( dirname( __DIR__ ) . '/includes/class-search-handler.php' );
    $wporg_search_handler = file_get_contents( dirname( __DIR__ ) . '/build-sources/wporg/includes/class-search-handler.php' );
    $frontend             = file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend.php' );
    $wporg_frontend       = file_get_contents( dirname( __DIR__ ) . '/build-sources/wporg/includes/class-frontend.php' );
    $script               = file_get_contents( dirname( __DIR__ ) . '/assets/js/chips.js' );
    $styles               = file_get_contents( dirname( __DIR__ ) . '/assets/css/chips.css' );

    foreach ( array( $search_handler, $wporg_search_handler ) as $contents ) {
        sschp_assert_contains( 'render_search_summary', $contents, 'AJAX search should render a result/chip count summary' );
        sschp_assert_contains( 'ssc-search-summary', $contents, 'Search summary markup should be present' );
        sschp_assert_contains( 'data-ssc-count-to', $contents, 'Search summaries and chip counts should expose animation targets' );
        sschp_assert_true( false === strpos( $contents, 'ssc-result__chip-count' ), 'Result previews should not expose per-product chip count badges to visitors' );
    }

    foreach ( array( $frontend, $wporg_frontend ) as $contents ) {
        sschp_assert_contains( 'ssc-chip__count', $contents, 'Archive/front-end chips should render count badges' );
        sschp_assert_contains( 'data-ssc-count-to', $contents, 'Archive/front-end chip counts should expose animation targets' );
    }

    sschp_assert_contains( 'function decorateRenderedOutput', $script, 'Rendered output should be decorated after hydration and AJAX updates' );
    sschp_assert_contains( 'prefers-reduced-motion: reduce', $styles, 'Count/chip animations should respect reduced-motion preferences' );
    sschp_assert_true( false === strpos( $styles, 'ssc-result__chip-count' ), 'Frontend CSS should not ship visitor-facing per-result chip badge styles' );
}

function sschp_test_public_contact_links_do_not_expose_private_email() {
    $paths = array(
        'smart-search-chips.php',
        'includes/class-settings.php',
        'build-sources/wporg/includes/class-settings.php',
        'marketing/launch-site/index.html',
        'marketing/launch-site/privacy-policy/index.html',
        'marketing/launch-site/refund-policy/index.html',
        'marketing/launch-site/support-policy/index.html',
        'marketing/launch-site/terms-of-service/index.html',
    );

    foreach ( $paths as $path ) {
        $contents = file_get_contents( dirname( __DIR__ ) . '/' . $path );

        sschp_assert_true( false === strpos( $contents, '[redacted]' ), $path . ' should not expose a private Gmail address' );
        sschp_assert_true( false === strpos( $contents, '[redacted]' ), $path . ' should not expose the old Gmail support address' );
    }

    $plugin_file = file_get_contents( dirname( __DIR__ ) . '/smart-search-chips.php' );
    sschp_assert_contains( "'contact'        => true", $plugin_file, 'Freemius contact submenu should remain available for licensed customer support' );
    sschp_assert_contains( 'mailto:support@smartsearchchips.app', file_get_contents( dirname( __DIR__ ) . '/includes/class-settings.php' ), 'Plugin feedback should use the public support email' );
    sschp_assert_contains( 'mailto:support@smartsearchchips.app', file_get_contents( dirname( __DIR__ ) . '/marketing/launch-site/index.html' ), 'Launch site footer should use the public support email' );
}

function sschp_test_setup_wizard_card_wraps_medium_width_copy() {
    $css = file_get_contents( dirname( __DIR__ ) . '/assets/css/settings.css' );

    sschp_assert_contains( '.ssc-settings-card { box-sizing: border-box; max-width: 100%;', $css, 'settings cards should not grow beyond their column' );
    sschp_assert_contains( '.ssc-setup-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr));', $css, 'setup wizard columns should reflow before medium-width overflow' );
    sschp_assert_contains( '.ssc-setup-step { box-sizing: border-box; min-width: 0;', $css, 'setup steps should be allowed to shrink inside the card' );
    sschp_assert_contains( '.ssc-setup-step p { margin: 0; overflow-wrap: anywhere; }', $css, 'setup explanatory text should wrap inside its panel' );
    sschp_assert_contains( '.ssc-copy-row { display: flex; flex-wrap: wrap; max-width: 100%; min-width: 0;', $css, 'shortcode copy row should wrap instead of widening the setup panel' );
    sschp_assert_contains( '.ssc-copy-row input { flex: 1 1 160px; min-width: 0; max-width: 100%; }', $css, 'shortcode input should shrink within the setup panel' );
}

function sschp_test_settings_page_has_section_navigation() {
    $root_settings  = file_get_contents( dirname( __DIR__ ) . '/includes/class-settings.php' );
    $wporg_settings = file_get_contents( dirname( __DIR__ ) . '/build-sources/wporg/includes/class-settings.php' );
    $css            = file_get_contents( dirname( __DIR__ ) . '/assets/css/settings.css' );
    $js             = file_get_contents( dirname( __DIR__ ) . '/assets/js/settings.js' );
    $tabs           = array( 'start', 'search', 'chips', 'index', 'diagnostics', 'pro-rules', 'range', 'analytics', 'ai', 'advanced' );

    foreach ( $tabs as $tab ) {
        sschp_assert_contains( 'data-sschp-settings-tab="' . $tab . '"', $root_settings, 'Pro settings should expose the ' . $tab . ' navigation tab' );
        sschp_assert_contains( 'data-sschp-settings-tab="' . $tab . '"', $wporg_settings, 'WP.org settings should expose the ' . $tab . ' navigation tab' );
        sschp_assert_contains( 'data-sschp-settings-section="' . $tab, $root_settings, 'Pro settings should have content assigned to the ' . $tab . ' section' );
        sschp_assert_contains( 'data-sschp-settings-section="' . $tab, $wporg_settings, 'WP.org settings should have content assigned to the ' . $tab . ' section' );
    }

    sschp_assert_contains( '.ssc-settings-tabs { position: sticky;', $css, 'settings navigation should remain reachable while scrolling the admin page' );
    sschp_assert_contains( 'function initSettingsTabs()', $js, 'settings script should initialize section navigation' );
    sschp_assert_contains( 'data-sschp-settings-section', $js, 'settings script should show and hide cards by section' );
    sschp_assert_contains( "card.tagName.toLowerCase() === 'details'", $js, 'settings tabs should open matching collapsible details cards so selected tabs are not visually empty' );
    sschp_assert_contains( 'card.open = true;', $js, 'settings tabs should expand collapsible cards for the active tab' );
    sschp_assert_contains( 'data-sschp-relevance-threshold', $root_settings, 'Pro settings should mark the relevance threshold slider for live value sync' );
    sschp_assert_contains( 'data-sschp-relevance-threshold', $wporg_settings, 'WP.org settings should mark the relevance threshold slider for live value sync' );
    sschp_assert_contains( 'initRelevanceThresholdValue', $js, 'settings script should keep the relevance threshold value label in sync while dragging' );
}

function sschp_test_admin_test_search_range_chips_render_as_ranges() {
    $js  = file_get_contents( dirname( __DIR__ ) . '/assets/js/settings.js' );
    $css = file_get_contents( dirname( __DIR__ ) . '/assets/css/settings.css' );

    sschp_assert_contains( 'formatTestSearchRangeSummary', $js, 'settings script should format admin test search range summaries' );
    sschp_assert_contains( 'ssc-test-search-chip__badge', $js, 'settings script should label range chips in admin test search results' );
    sschp_assert_contains( '.ssc-test-search-chip--range', $css, 'settings CSS should visually distinguish admin test search range chips' );
    sschp_assert_contains( '.ssc-test-search-chip__detail', $css, 'settings CSS should style admin test search range values' );
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

function sschp_test_analytics_summary_rates_are_consistent() {
    $summary = sschp_invoke_private(
        'SSCHP_Analytics',
        'enrich_summary_rates',
        array(
            array(
                'total_searches'              => 4,
                'zero_result_search'          => 1,
                'chip_impressions'            => 10,
                'chip_clicks'                 => 2,
                'search_linked_clicks'        => 2,
                'result_clicks'               => 3,
                'search_linked_result_clicks' => 3,
            ),
        )
    );

    sschp_assert_same( 25.0, $summary['zero_result_rate'], 'zero-result rate should divide zero-result searches by all searches' );
    sschp_assert_same( 20.0, $summary['chip_click_through_rate'], 'chip CTR should divide chip clicks by chip impressions' );
    sschp_assert_same( 50.0, $summary['search_to_click_rate'], 'search-to-chip-click rate should divide linked chip clicks by all searches' );
    sschp_assert_same( 75.0, $summary['search_to_result_click_rate'], 'search-to-result-click rate should divide linked result clicks by all searches' );
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

function sschp_test_analytics_guard_dedupes_chip_impression_batches() {
    sschp_reset_test_state();

    $_SERVER['REMOTE_ADDR'] = '203.0.113.11';
    $payload                = array(
        'visitor_id'       => 'ssc-demo-visitor',
        'event_type'       => 'chip_impression',
        'language_code'    => 'en',
        'search_query'     => 'spring',
        'chip_label'       => '',
        'post_type'        => 'product',
        'product_category' => 'handles',
        'page_url'         => 'https://example.test/search/',
        'active_tag_count' => 0,
        'chip_labels'      => array( 'Outdoor', 'Spring Handle' ),
    );

    $first  = sschp_invoke_private( 'SSCHP_Analytics', 'guard_tracking_request', array( $payload ) );
    $second = sschp_invoke_private( 'SSCHP_Analytics', 'guard_tracking_request', array( $payload ) );

    sschp_assert_same( 'track', $first, 'first chip impression batch should be recorded' );
    sschp_assert_same( 'duplicate', $second, 'identical chip impression batches should be deduplicated' );
}

function sschp_test_analytics_report_filters_are_sanitized() {
    $range  = array(
        'from_gmt' => '2026-05-01 00:00:00',
        'to_gmt'   => '2026-05-31 23:59:59',
    );
    $filter = sschp_invoke_private(
        'SSCHP_Analytics',
        'get_report_filter_sql',
        array(
            $range,
            array(
                'language'         => 'CS!',
                'post_type'        => 'Product',
                'product_category' => 'Power switches',
            ),
        )
    );

    sschp_assert_contains( 'language_code = %s', $filter['where'], 'report filter should include language when provided' );
    sschp_assert_contains( 'post_type = %s', $filter['where'], 'report filter should include post type when provided' );
    sschp_assert_contains( 'product_category = %s', $filter['where'], 'report filter should include WooCommerce category when provided' );
    sschp_assert_same( array( '2026-05-01 00:00:00', '2026-05-31 23:59:59', 'cs', 'product', 'power-switches' ), $filter['params'], 'report filter values should be sanitized before SQL prepare' );
}

function sschp_test_analytics_result_click_tracking_stores_post_and_position() {
    global $sschp_test_analytics_rows, $sschp_test_post_terms;

    sschp_reset_test_state();
    sschp_enable_pro_for_analytics_tests();
    update_option( 'sschp_analytics_enabled', '1' );
    update_option( 'sschp_analytics_consent_ack', '1' );
    update_option( 'sschp_analytics_ignore_admins', '0' );
    $sschp_test_post_terms['42|product_cat'] = array(
        (object) array(
            'slug' => 'Power Switches',
        ),
    );

    $_SERVER['REMOTE_ADDR'] = '203.0.113.12';
    $_POST                  = array(
        'nonce'              => 'test-nonce',
        'event_type'         => 'result_click',
        'visitor_id'         => 'visitor-one',
        'search_query'       => 'spring handle',
        'active_tag_count'   => '2',
        'post_type'          => 'product',
        'result_object_id'   => '42',
        'result_position'    => '3',
        'page_url'           => 'https://example.test/?s=spring',
        'sschp_lang'         => 'cs',
    );

    $handler = new SSCHP_Analytics();
    $result  = sschp_capture_wp_die(
        function() use ( $handler ) {
            $handler->ajax_track_event();
        }
    );

    sschp_assert_true( $result['died'], 'ajax_track_event should finish by sending a JSON response' );
    sschp_assert_contains( '"tracked":true', $result['message'], 'result click should be tracked when the object ID is present' );
    sschp_assert_same( 1, count( $sschp_test_analytics_rows ), 'result click should insert one analytics row' );
    sschp_assert_same( 'result_click', $sschp_test_analytics_rows[0]['event_type'], 'result click row should keep the event type' );
    sschp_assert_true( $sschp_test_analytics_rows[0]['visitor_id'] !== 'visitor-one', 'analytics rows should hash browser visitor IDs before storage' );
    sschp_assert_same( 64, strlen( $sschp_test_analytics_rows[0]['visitor_id'] ), 'hashed analytics visitor IDs should use a fixed SHA-256 length' );
    sschp_assert_same( 'https://example.test/', $sschp_test_analytics_rows[0]['page_url'], 'analytics rows should strip query strings before storing page URLs' );
    sschp_assert_same( 42, $sschp_test_analytics_rows[0]['result_object_id'], 'result click row should store clicked post/product ID' );
    sschp_assert_same( 3, $sschp_test_analytics_rows[0]['result_position'], 'result click row should store clicked result position' );
    sschp_assert_same( 0, $sschp_test_analytics_rows[0]['user_id'], 'analytics rows should not store personal WordPress user IDs' );
    sschp_assert_same( 'product', $sschp_test_analytics_rows[0]['post_type'], 'result click row should store post type for filtering' );
    sschp_assert_same( 'power-switches', $sschp_test_analytics_rows[0]['product_category'], 'result click row should derive a product category slug when the browser does not send one' );
}

function sschp_test_analytics_does_not_track_without_consent_acknowledgement() {
    global $sschp_test_analytics_rows;

    sschp_reset_test_state();
    sschp_enable_pro_for_analytics_tests();
    update_option( 'sschp_analytics_enabled', '1' );
    update_option( 'sschp_analytics_consent_ack', '0' );
    update_option( 'sschp_analytics_ignore_admins', '0' );

    $_SERVER['REMOTE_ADDR'] = '203.0.113.13';
    $_POST                  = array(
        'nonce'        => 'test-nonce',
        'event_type'   => 'search',
        'visitor_id'   => 'visitor-one',
        'search_query' => 'spring handle',
    );

    $handler = new SSCHP_Analytics();
    $result  = sschp_capture_wp_die(
        function() use ( $handler ) {
            $handler->ajax_track_event();
        }
    );

    sschp_assert_true( $result['died'], 'ajax_track_event should finish by sending a JSON response' );
    sschp_assert_contains( '"tracked":false', $result['message'], 'analytics should not track when consent/legal-basis acknowledgement is missing' );
    sschp_assert_same( array(), $sschp_test_analytics_rows, 'analytics should not insert rows without consent/legal-basis acknowledgement' );
}

function sschp_test_analytics_csv_helpers_omit_visitor_and_page_url() {
    $headers = sschp_invoke_private( 'SSCHP_Analytics', 'get_csv_headers' );
    $row     = sschp_invoke_private(
        'SSCHP_Analytics',
        'get_csv_event_row',
        array(
            array(
                'created_at'       => '2026-05-30 10:00:00',
                'event_type'       => 'search',
                'language_code'    => 'en',
                'post_type'        => 'product',
                'product_category' => 'handles',
                'search_query'     => 'spring',
                'chip_label'       => '',
                'result_count'     => 4,
                'active_tag_count' => 0,
                'result_object_id' => '',
                'result_position'  => '',
                'page_url'         => 'https://example.test/?s=spring',
                'visitor_id'       => 'hashed-visitor',
            ),
        )
    );

    sschp_assert_true( ! in_array( 'page_url', $headers, true ), 'analytics CSV exports should omit page URLs by default' );
    sschp_assert_true( ! in_array( 'visitor_id', $headers, true ), 'analytics CSV exports should omit visitor IDs by default' );
    sschp_assert_same( count( $headers ), count( $row ), 'analytics CSV rows should match the privacy-minimized header shape' );
    sschp_assert_true( ! in_array( 'https://example.test/?s=spring', $row, true ), 'analytics CSV rows should not include page URLs' );
    sschp_assert_true( ! in_array( 'hashed-visitor', $row, true ), 'analytics CSV rows should not include visitor IDs' );
}

function sschp_enable_pro_for_analytics_tests() {
    global $sschp_test_freemius;

    $sschp_test_freemius          = new SSCHP_Test_Freemius();
    $sschp_test_freemius->premium = true;
    update_option( SSCHP_Analytics::DB_VERSION_OPTION, SSCHP_Analytics::DB_VERSION );
    update_option( 'sschp_analytics_consent_ack', '1' );
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

function sschp_test_analytics_digest_reports_next_scheduled_run() {
    global $sschp_test_scheduled_events;

    sschp_reset_test_state();
    sschp_enable_pro_for_analytics_tests();
    update_option( 'sschp_analytics_enabled', '1' );
    update_option( 'sschp_analytics_digest_enabled', '1' );
    update_option( 'sschp_analytics_digest_frequency', 'weekly' );
    update_option( 'sschp_analytics_digest_day', 'monday' );
    update_option( 'sschp_analytics_digest_time', '09:00' );

    SSCHP_Analytics::schedule_digest( true );

    sschp_assert_same( 1, count( $sschp_test_scheduled_events ), 'analytics digest should schedule one cron event for next-run reporting' );
    sschp_assert_same( $sschp_test_scheduled_events[0]['timestamp'], SSCHP_Analytics::get_next_digest_run_timestamp(), 'next digest run helper should return the scheduled cron timestamp' );
    sschp_assert_true( SSCHP_Analytics::get_expected_digest_run_timestamp() > 0, 'expected digest run helper should calculate a timestamp when scheduling is enabled' );
}

function sschp_test_analytics_digest_monthly_last_day_fallback() {
    sschp_reset_test_state();
    sschp_enable_pro_for_analytics_tests();
    update_option( 'sschp_analytics_digest_frequency', 'monthly' );
    update_option( 'sschp_analytics_digest_day', '31' );
    update_option( 'sschp_analytics_digest_time', '09:00' );

    $february_run = SSCHP_Analytics::calculate_next_run_timestamp( strtotime( '2026-02-10 08:00:00 UTC' ) );
    $march_run    = SSCHP_Analytics::calculate_next_run_timestamp( strtotime( '2026-02-28 10:00:00 UTC' ) );

    sschp_assert_same( '2026-02-28 09:00', gmdate( 'Y-m-d H:i', $february_run ), 'monthly digest day 31 should fall back to February month end' );
    sschp_assert_same( '2026-03-31 09:00', gmdate( 'Y-m-d H:i', $march_run ), 'monthly digest day 31 should use the selected day when the next month has it' );
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

function sschp_test_analytics_digest_can_attach_chart_export() {
    global $sschp_test_mail;

    sschp_reset_test_state();
    sschp_enable_pro_for_analytics_tests();
    update_option( 'sschp_analytics_enabled', '1' );
    update_option( 'sschp_analytics_digest_recipients', 'owner@example.test' );
    update_option( 'sschp_analytics_digest_include_csv', '0' );
    update_option( 'sschp_analytics_digest_include_charts', '1' );

    $sent = SSCHP_Analytics::send_digest_email( true );

    sschp_assert_true( $sent, 'analytics digest should send when chart attachment is enabled' );
    sschp_assert_same( 1, count( $sschp_test_mail[0]['attachments'] ), 'analytics digest should attach one chart report when requested' );
    sschp_assert_contains( 'Chart report attached', $sschp_test_mail[0]['message'], 'analytics digest should explain when a chart report is attached' );
}

function sschp_test_analytics_chart_export_builds_svg_report() {
    $html = SSCHP_Analytics::build_chart_export_html(
        array(
            'range'      => array(
                'from' => '2026-05-01',
                'to'   => '2026-05-02',
            ),
            'timeseries' => array(
                array(
                    'date'            => '2026-05-01',
                    'label'           => 'May 1',
                    'searches'        => 3,
                    'chip_clicks'     => 1,
                    'result_clicks'   => 2,
                    'unique_visitors' => 2,
                    'zero_results'    => 0,
                ),
            ),
        )
    );

    sschp_assert_contains( '<svg', $html, 'chart export should render inline SVG graphs' );
    sschp_assert_contains( 'Search Activity Over Time', $html, 'chart export should include the search activity graph' );
    sschp_assert_contains( 'Visitors And Zero-Result Searches', $html, 'chart export should include the visitors and zero-results graph' );
    sschp_assert_contains( 'Generated locally', $html, 'chart export should explain that the report is generated locally' );
}

function sschp_test_analytics_chart_export_requires_manage_options() {
    sschp_reset_test_state();

    $analytics = new SSCHP_Analytics();

    try {
        $analytics->handle_chart_export();
    } catch ( RuntimeException $exception ) {
        sschp_assert_contains( 'permission', $exception->getMessage(), 'chart export should require manage_options' );
        return;
    }

    sschp_assert_true( false, 'chart export should stop unauthorized requests' );
}

function sschp_test_analytics_dashboard_tables_are_scrollable() {
    $settings = file_get_contents( dirname( __DIR__ ) . '/includes/class-settings.php' );
    $css      = file_get_contents( dirname( __DIR__ ) . '/assets/css/analytics.css' );

    sschp_assert_contains( 'ssc-analytics-table-wrap', $settings, 'analytics tables should render inside a scroll wrapper' );
    sschp_assert_contains( 'overflow-x: auto', $css, 'analytics table wrapper should allow horizontal scrolling instead of clipping columns' );
    sschp_assert_contains( 'repeat(auto-fit, minmax(min(100%, 320px), 1fr))', $css, 'analytics panels should wrap before tables become too narrow' );
    sschp_assert_contains( '.ssc-analytics-panel { min-width: 0;', $css, 'analytics panels should be allowed to shrink inside the dashboard grid' );
    sschp_assert_contains( 'Search To Result Click Rate', $settings, 'analytics dashboard should label search-to-result-click rate as a rate' );
    sschp_assert_contains( 'ssc-analytics-scorecard', $settings, 'analytics dashboard should render a visual scorecard above detail tables' );
    sschp_assert_contains( 'render_analytics_funnel', $settings, 'analytics dashboard should render a graphical search funnel from existing metrics' );
    sschp_assert_contains( 'get_analytics_insight_cards', $settings, 'analytics dashboard should render data-driven insight cards instead of only tables' );
    sschp_assert_contains( 'Click Rate', $settings, 'top query analytics should expose chip click rate in the visible table' );
    sschp_assert_contains( '.ssc-analytics-insights', $css, 'analytics insight cards should have dedicated responsive styling' );
    sschp_assert_contains( '.ssc-analytics-funnel__bar', $css, 'analytics funnel should render graphical bars' );
}

function sschp_test_settings_analytics_dashboard_reference_is_styled() {
    $css = file_get_contents( dirname( __DIR__ ) . '/assets/css/settings.css' );

    sschp_assert_contains( '.ssc-analytics-dashboard-reference { display: grid;', $css, 'settings analytics dashboard reference should be styled as a compact callout' );
    sschp_assert_contains( '.ssc-analytics-dashboard-reference p { margin: 0;', $css, 'settings analytics dashboard reference copy should stay compact' );
    sschp_assert_contains( '.ssc-analytics-chart-attachment-preview { display: grid;', $css, 'settings analytics preview should style the chart attachment preview' );
    sschp_assert_contains( '.ssc-analytics-chart-attachment-preview svg { display: block; width: 100%; height: auto; }', $css, 'settings analytics preview should scale chart SVGs inside the preview card' );
}

function sschp_test_analytics_digest_preview_exposes_next_email_without_sending() {
    global $sschp_test_mail;

    sschp_reset_test_state();
    sschp_enable_pro_for_analytics_tests();
    update_option( 'sschp_analytics_enabled', '1' );
    update_option( 'sschp_analytics_digest_enabled', '0' );
    update_option( 'sschp_analytics_digest_recipients', "owner@example.test\nreports@example.test" );
    update_option( 'sschp_analytics_digest_include_csv', '1' );
    update_option( 'sschp_analytics_digest_include_charts', '1' );

    $preview = SSCHP_Analytics::get_digest_preview_data();

    sschp_assert_same( array( 'owner@example.test', 'reports@example.test' ), $preview['recipients'], 'digest preview should expose configured recipients' );
    sschp_assert_contains( 'Smart Search Chips analytics', $preview['subject'], 'digest preview should expose the scheduled email subject' );
    sschp_assert_contains( 'This email is generated by your WordPress site', $preview['message'], 'digest preview should expose the generated email body' );
    sschp_assert_contains( 'generated locally', $preview['generated_by'], 'digest preview should describe local report generation' );
    sschp_assert_true( ! empty( $preview['include_csv'] ), 'digest preview should report whether CSV attachments are enabled' );
    sschp_assert_true( ! empty( $preview['include_charts'] ), 'digest preview should report whether chart attachments are enabled' );
    sschp_assert_contains( '<svg', $preview['chart_preview'], 'digest preview should include chart attachment SVG markup when charts are enabled' );
    sschp_assert_contains( 'Search Activity Over Time', $preview['chart_preview'], 'digest preview should show the chart attachment content when charts are enabled' );
    sschp_assert_same( array(), $sschp_test_mail, 'digest preview should not send email' );
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

function sschp_test_settings_source_renders_analytics_digest_preview_ui() {
    $path     = dirname( __DIR__ ) . '/includes/class-settings.php';
    $contents = is_file( $path ) ? file_get_contents( $path ) : '';
    $js       = file_get_contents( dirname( __DIR__ ) . '/assets/js/settings.js' );

    sschp_assert_contains( 'Next Scheduled Run', $contents, 'Pro settings should render the analytics digest next-run display' );
    sschp_assert_contains( 'Preview Next Analytics Email', $contents, 'Pro settings should render the analytics digest email preview card' );
    sschp_assert_contains( 'get_digest_preview_data', $contents, 'Pro settings should use the analytics preview helper instead of duplicating email logic' );
    sschp_assert_contains( 'generated locally', $contents, 'Pro settings should tell admins reports are generated locally' );
    sschp_assert_contains( 'configured recipients', $contents, 'Pro settings should tell admins emails go only to configured recipients' );
    sschp_assert_contains( 'TEST_DIGEST_ACTION', $contents, 'Pro settings should keep the existing send-test-email flow' );
    sschp_assert_contains( 'sschp_analytics_digest_include_charts', $contents, 'Pro settings should let admins attach chart reports to analytics digests' );
    sschp_assert_contains( 'Chart Attachment', $contents, 'Pro settings should preview whether chart attachments are enabled' );
    sschp_assert_contains( 'Preview Chart Attachment', $contents, 'Pro settings should render the chart attachment preview when chart emails are enabled' );
    sschp_assert_contains( 'get_analytics_chart_preview_allowed_html', $contents, 'Pro settings should allow safe SVG chart markup in the digest preview' );
    sschp_assert_contains( 'CHART_EXPORT_ACTION', $contents, 'Pro analytics page should expose chart export from the dashboard filters' );
    sschp_assert_contains( 'Detailed Analytics Dashboard', $contents, 'Pro settings analytics tab should reference the dedicated dashboard near the digest controls' );
    sschp_assert_contains( 'Open Analytics Dashboard', $contents, 'Pro settings analytics tab should link back to detailed analytics' );
    sschp_assert_contains( 'CSV export, and chart export', $contents, 'Pro settings analytics copy should mention both analytics export paths' );
    sschp_assert_contains( 'sschp_analytics_consent_ack', $contents, 'Pro settings should require consent/legal-basis acknowledgement before analytics tracking' );
    sschp_assert_contains( 'required visitor consent or legal basis', $contents, 'Pro settings should explain analytics consent/legal-basis requirements' );
    sschp_assert_contains( 'data-sschp-digest-frequency', $contents, 'Pro settings should expose digest frequency to the schedule picker' );
    sschp_assert_contains( 'data-sschp-digest-weekday-select', $contents, 'Pro settings should render a weekday select for weekly digests' );
    sschp_assert_contains( 'ssc-digest-calendar__grid', $contents, 'Pro settings should render a calendar-style monthly digest day grid' );
    sschp_assert_contains( 'data-sschp-digest-monthday-input', $contents, 'Pro settings should render controlled monthly day choices' );
    sschp_assert_contains( 'get_digest_monthday_options', $contents, 'Pro settings should generate monthly digest day choices instead of asking for free text' );
    sschp_assert_contains( 'does not exist in a month', $contents, 'Pro settings should explain the last-day fallback for 29th through 31st schedules' );
    sschp_assert_true( false === strpos( $contents, 'type="text" name="sschp_analytics_digest_day"' ), 'Pro settings should not render analytics digest day as a free-text input' );
    sschp_assert_contains( 'initAnalyticsDigestDayPicker', $js, 'settings script should switch the visible digest day picker based on frequency' );
}

function sschp_test_settings_digest_day_sanitizer_accepts_controlled_choices() {
    sschp_reset_test_state();

    $settings = new SSCHP_Settings();

    sschp_assert_same( 'monday', $settings->sanitize_digest_day( 'Monday' ), 'digest day sanitizer should accept case-insensitive weekday values' );
    sschp_assert_same( '31', $settings->sanitize_digest_day( '31' ), 'digest day sanitizer should accept monthly days from 1 to 31' );
    sschp_assert_same( 'monday', $settings->sanitize_digest_day( '32' ), 'digest day sanitizer should reject monthly days after 31' );
}

function sschp_test_settings_digest_sanitizers_default_empty_without_warning() {
    global $sschp_test_settings_errors;

    sschp_reset_test_state();

    $settings = new SSCHP_Settings();

    sschp_assert_same( 'monday', $settings->sanitize_digest_day( '' ), 'empty digest day should default without treating an omitted Pro-only field as invalid' );
    sschp_assert_same( '09:00', $settings->sanitize_digest_time( '' ), 'empty digest time should default without treating an omitted Pro-only field as invalid' );
    sschp_assert_same( array(), $sschp_test_settings_errors, 'empty digest fields should not show analytics warnings on free settings saves' );
}

function sschp_test_wporg_build_excludes_pro_analytics_class() {
    $root      = dirname( __DIR__ );
    $build     = $root . '/scripts/build-release.ps1';
    $validator = $root . '/scripts/validate-wporg-package.php';
    $wporg     = $root . '/build-sources/wporg';

    sschp_assert_contains( 'includes\class-analytics__premium_only.php', file_get_contents( $build ), 'WP.org release build should remove the Pro analytics class' );
    sschp_assert_contains( 'assets\js\analytics__premium_only.js', file_get_contents( $build ), 'WP.org release build should remove the Pro analytics frontend transport' );
    sschp_assert_contains( 'SSCHP_Analytics', file_get_contents( $validator ), 'WP.org validator should reject Pro analytics class references' );
    sschp_assert_contains( 'sschp_track_event', file_get_contents( $validator ), 'WP.org validator should reject Pro analytics tracking endpoint references' );
    sschp_assert_true( ! is_file( $wporg . '/includes/class-analytics__premium_only.php' ), 'WP.org source overrides should not contain the Pro analytics class file' );
    sschp_assert_true( ! is_file( $wporg . '/assets/js/analytics__premium_only.js' ), 'WP.org source overrides should not contain the Pro analytics frontend transport' );

    $main_contents = file_get_contents( $wporg . '/smart-search-chips.php' );
    sschp_assert_true( false === strpos( $main_contents, 'class-analytics__premium_only.php' ), 'WP.org main plugin source should not load the Pro analytics class' );
    sschp_assert_true( false === strpos( $main_contents, 'SSCHP_Analytics' ), 'WP.org main plugin source should not reference the Pro analytics class' );
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
    sschp_assert_contains( 'Smart Search Chips Search block', $setup['blockPathLabel'], 'setup wizard should recommend the guaranteed Gutenberg block path' );
    sschp_assert_contains( 'block inserter', $setup['blockPathLabel'], 'setup wizard should explain where to find the guaranteed Gutenberg block' );
    sschp_assert_contains( 'built-in Shortcode block', $setup['shortcodeBlockLabel'], 'setup wizard should clarify that the second path uses WordPress core Shortcode block' );
    sschp_assert_contains( 'page builder', $setup['shortcodeBlockLabel'], 'setup wizard should explain that the shortcode can be pasted outside the core Shortcode block' );
    sschp_assert_contains( 'Smart Search Chips endpoint', $setup['guaranteedModeLabel'], 'setup wizard should explain that guaranteed mode uses the SSC endpoint' );
    sschp_assert_same( 'Passive by default', $setup['themeEnhancementStatus'], 'theme enhancement should be passive when no override action is configured' );
    sschp_assert_same( array( 'Posts', 'Products' ), $setup['selectedPostTypeLabels'], 'setup wizard should show selected post type labels' );
}

function sschp_test_search_index_table_creation_defines_required_columns() {
    global $sschp_test_db_delta_sql, $sschp_test_index_table_exists, $sschp_test_options;

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
    sschp_assert_true( $sschp_test_index_table_exists, 'search index table creation should mark the table available for later health checks' );
}

function sschp_test_search_index_upgrade_creates_missing_table_and_queues_index() {
    global $sschp_test_db_delta_sql, $sschp_test_options;

    sschp_reset_test_state();
    $sschp_test_options['sschp_post_types'] = array( 'post' );
    sschp_register_test_post_for_index( 401, 'post', 'Upgrade Beacon' );

    $did_upgrade = SSCHP_Search_Index::maybe_install_or_upgrade();

    sschp_assert_true( $did_upgrade, 'search index upgrade should run when the DB version option is missing' );
    sschp_assert_true( ! empty( $sschp_test_db_delta_sql ), 'search index upgrade should create the missing table' );
    sschp_assert_same( SSCHP_Search_Index::DB_VERSION, $sschp_test_options[SSCHP_Search_Index::DB_VERSION_OPTION], 'search index upgrade should store the current DB version' );
    sschp_assert_same( 'queued', $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION], 'search index upgrade should queue indexing for old installs without progress state' );
    sschp_assert_same( 1, $sschp_test_options[SSCHP_Search_Index::TOTAL_OPTION], 'search index upgrade should count eligible posts when queueing' );
    sschp_assert_true( in_array( SSCHP_Search_Index::CRON_HOOK, sschp_test_scheduled_hooks(), true ), 'search index upgrade should schedule a background batch' );
}

function sschp_test_search_index_upgrade_repairs_missing_table_even_with_current_version() {
    global $sschp_test_db_delta_sql, $sschp_test_index_table_exists, $sschp_test_options;

    sschp_reset_test_state();
    $sschp_test_options[SSCHP_Search_Index::DB_VERSION_OPTION] = SSCHP_Search_Index::DB_VERSION;
    $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION]     = 'complete';
    $sschp_test_index_table_exists                             = false;

    $did_upgrade = SSCHP_Search_Index::maybe_install_or_upgrade();

    sschp_assert_true( $did_upgrade, 'search index upgrade should repair a missing table even when the stored DB version is current' );
    sschp_assert_true( ! empty( $sschp_test_db_delta_sql ), 'search index repair should recreate the table' );
    sschp_assert_same( 'complete', $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION], 'search index repair should not overwrite existing progress state' );
}

function sschp_test_search_index_upgrade_skips_current_installed_schema() {
    global $sschp_test_db_delta_sql, $sschp_test_index_table_exists, $sschp_test_options;

    sschp_reset_test_state();
    $sschp_test_options[SSCHP_Search_Index::DB_VERSION_OPTION] = SSCHP_Search_Index::DB_VERSION;
    $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION]     = 'complete';
    $sschp_test_index_table_exists                             = true;

    $did_upgrade = SSCHP_Search_Index::maybe_install_or_upgrade();

    sschp_assert_same( false, $did_upgrade, 'search index upgrade should skip current installed schemas' );
    sschp_assert_same( array(), $sschp_test_db_delta_sql, 'search index upgrade should not run dbDelta when the schema is current and table exists' );
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

function sschp_test_search_index_rebuild_queues_index() {
    global $sschp_test_index_rows, $sschp_test_options;

    sschp_reset_test_state();
    $sschp_test_options['sschp_post_types'] = array( 'product' );
    $sschp_test_index_rows['999|en']        = (object) array(
        'object_id'     => 999,
        'language_code' => 'en',
    );
    sschp_register_test_post_for_index( 201, 'product', 'Queued Product One' );
    sschp_register_test_post_for_index( 202, 'product', 'Queued Product Two' );

    SSCHP_Search_Index::rebuild_index( 'test_rebuild' );

    sschp_assert_same( 'queued', $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION], 'manual rebuild should queue indexing' );
    sschp_assert_same( 2, $sschp_test_options[SSCHP_Search_Index::TOTAL_OPTION], 'manual rebuild should recount eligible posts' );
    sschp_assert_same( 0, $sschp_test_options[SSCHP_Search_Index::PROCESSED_OPTION], 'manual rebuild should reset processed progress' );
    sschp_assert_same( 0, $sschp_test_options[SSCHP_Search_Index::CURSOR_OPTION], 'manual rebuild should reset the cursor' );
    sschp_assert_same( '', $sschp_test_options[SSCHP_Search_Index::UPDATED_OPTION], 'manual rebuild should clear the completed timestamp' );
    sschp_assert_same( 0, count( $sschp_test_index_rows ), 'manual rebuild should clear existing index rows before queueing' );
    sschp_assert_true( in_array( SSCHP_Search_Index::CRON_HOOK, sschp_test_scheduled_hooks(), true ), 'manual rebuild should schedule a background batch' );
}

function sschp_test_search_index_clear_index_clears_rows_and_status() {
    global $sschp_test_index_rows, $sschp_test_options, $sschp_test_scheduled_events;

    sschp_reset_test_state();
    $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION]    = 'processing';
    $sschp_test_options[SSCHP_Search_Index::TOTAL_OPTION]     = 4;
    $sschp_test_options[SSCHP_Search_Index::PROCESSED_OPTION] = 2;
    $sschp_test_options[SSCHP_Search_Index::CURSOR_OPTION]    = 202;
    $sschp_test_options[SSCHP_Search_Index::UPDATED_OPTION]   = '2026-05-22 12:00:00';
    $sschp_test_scheduled_events[]                            = SSCHP_Search_Index::CRON_HOOK;
    $sschp_test_index_rows['201|en']                          = (object) array(
        'object_id'     => 201,
        'language_code' => 'en',
    );

    SSCHP_Search_Index::clear_index();

    sschp_assert_same( 0, count( $sschp_test_index_rows ), 'clear index should remove indexed rows' );
    sschp_assert_same( 'empty', $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION], 'clear index should mark the index empty' );
    sschp_assert_same( 0, $sschp_test_options[SSCHP_Search_Index::TOTAL_OPTION], 'clear index should reset total progress' );
    sschp_assert_same( 0, $sschp_test_options[SSCHP_Search_Index::PROCESSED_OPTION], 'clear index should reset processed progress' );
    sschp_assert_same( 0, $sschp_test_options[SSCHP_Search_Index::CURSOR_OPTION], 'clear index should reset the cursor' );
    sschp_assert_same( '', $sschp_test_options[SSCHP_Search_Index::UPDATED_OPTION], 'clear index should clear the completed timestamp' );
    sschp_assert_true( ! in_array( SSCHP_Search_Index::CRON_HOOK, sschp_test_scheduled_hooks(), true ), 'clear index should unschedule pending batches' );
}

function sschp_test_search_index_process_batch_indexes_one_batch() {
    global $sschp_test_index_rows, $sschp_test_options, $sschp_test_post_meta;

    sschp_reset_test_state();
    $sschp_test_options['sschp_post_types'] = array( 'product' );
    sschp_register_test_post_for_index( 211, 'product', 'Batch Product One' );
    sschp_register_test_post_for_index( 212, 'product', 'Batch Product Two' );
    sschp_register_test_post_for_index( 213, 'product', 'Batch Product Three' );
    $sschp_test_post_meta[211][SSCHP_Meta_Box::META_KEY] = 'Batch; One';
    $sschp_test_post_meta[212][SSCHP_Meta_Box::META_KEY] = 'Batch; Two';
    $sschp_test_post_meta[213][SSCHP_Meta_Box::META_KEY] = 'Batch; Three';

    SSCHP_Search_Index::queue_initial_index( 'test_batch' );
    $index     = new SSCHP_Search_Index( new SSCHP_Test_Settings( array( 'post_types' => array( 'product' ) ) ), new SSCHP_Chip_Engine() );
    $processed = $index->process_batch( 2 );

    sschp_assert_same( 2, $processed, 'process batch should only index the requested batch size' );
    sschp_assert_same( 2, count( $sschp_test_index_rows ), 'process batch should write one batch of rows' );
    sschp_assert_same( 2, $sschp_test_options[SSCHP_Search_Index::PROCESSED_OPTION], 'process batch should advance processed progress by one batch' );
    sschp_assert_same( 212, $sschp_test_options[SSCHP_Search_Index::CURSOR_OPTION], 'process batch should advance the cursor to the last processed post' );
    sschp_assert_same( 'processing', $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION], 'partial batch should leave the index processing' );
}

function sschp_test_search_index_paused_index_does_not_process() {
    global $sschp_test_index_rows, $sschp_test_options;

    sschp_reset_test_state();
    $sschp_test_options['sschp_post_types'] = array( 'product' );
    sschp_register_test_post_for_index( 221, 'product', 'Paused Product One' );
    sschp_register_test_post_for_index( 222, 'product', 'Paused Product Two' );

    SSCHP_Search_Index::queue_initial_index( 'test_pause' );
    SSCHP_Search_Index::pause_index();
    $index     = new SSCHP_Search_Index( new SSCHP_Test_Settings( array( 'post_types' => array( 'product' ) ) ), new SSCHP_Chip_Engine() );
    $processed = $index->process_batch( 2 );

    sschp_assert_same( 0, $processed, 'paused index should not process rows' );
    sschp_assert_same( 0, count( $sschp_test_index_rows ), 'paused index should not write index rows' );
    sschp_assert_same( 0, $sschp_test_options[SSCHP_Search_Index::PROCESSED_OPTION], 'paused index should not advance progress' );
    sschp_assert_same( 'paused', $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION], 'paused index should keep paused status' );
    sschp_assert_true( ! in_array( SSCHP_Search_Index::CRON_HOOK, sschp_test_scheduled_hooks(), true ), 'paused index should unschedule pending batches' );
}

function sschp_test_search_index_resumed_index_can_process_again() {
    global $sschp_test_index_rows, $sschp_test_options;

    sschp_reset_test_state();
    $sschp_test_options['sschp_post_types'] = array( 'product' );
    sschp_register_test_post_for_index( 231, 'product', 'Resumed Product One' );
    sschp_register_test_post_for_index( 232, 'product', 'Resumed Product Two' );
    sschp_register_test_post_for_index( 233, 'product', 'Resumed Product Three' );

    SSCHP_Search_Index::queue_initial_index( 'test_resume' );
    SSCHP_Search_Index::pause_index();
    SSCHP_Search_Index::resume_index();
    $index     = new SSCHP_Search_Index( new SSCHP_Test_Settings( array( 'post_types' => array( 'product' ) ) ), new SSCHP_Chip_Engine() );
    $processed = $index->process_batch( 2 );

    sschp_assert_same( 2, $processed, 'resumed index should process the next batch' );
    sschp_assert_same( 2, count( $sschp_test_index_rows ), 'resumed index should write index rows again' );
    sschp_assert_same( 2, $sschp_test_options[SSCHP_Search_Index::PROCESSED_OPTION], 'resumed index should advance processed progress' );
    sschp_assert_same( 'processing', $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION], 'resumed partial batch should continue processing' );
}

function sschp_test_search_index_paused_complete_index_remains_ready() {
    global $sschp_test_options;

    sschp_reset_test_state();
    $post = sschp_register_test_post_for_index( 241, 'product', 'Ready Product' );
    $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION]    = 'complete';
    $sschp_test_options[SSCHP_Search_Index::TOTAL_OPTION]     = 1;
    $sschp_test_options[SSCHP_Search_Index::PROCESSED_OPTION] = 1;

    $index = new SSCHP_Search_Index( new SSCHP_Test_Settings( array( 'post_types' => array( 'product' ) ) ), new SSCHP_Chip_Engine() );
    $index->index_post( 241, $post );
    SSCHP_Search_Index::pause_index();

    sschp_assert_true( $index->is_ready( array( 'product' ), 'en' ), 'pausing a complete index should stop processing without disabling local search' );
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

function sschp_test_search_explainer_reports_query_and_chip_causes() {
    sschp_reset_test_state();

    $explainer = new SSCHP_Search_Explainer( new SSCHP_Chip_Engine(), new SSCHP_Test_Settings() );

    $codes = sschp_explanation_codes( $explainer->explain( array( 'query' => '' ) ) );
    sschp_assert_true( in_array( 'no_search_query', $codes, true ), 'search explainer should report a missing query' );

    $codes = sschp_explanation_codes( $explainer->explain( array( 'query' => 'a' ) ) );
    sschp_assert_true( in_array( 'query_too_short', $codes, true ), 'search explainer should report a short query' );

    $codes = sschp_explanation_codes(
        $explainer->explain(
            array(
                'query'      => 'Beacon',
                'posts_data' => array(),
            )
        )
    );
    sschp_assert_true( in_array( 'no_matching_results', $codes, true ), 'search explainer should report no matching results' );

    $posts_without_tags = array(
        array( 'tags' => array(), 'tags_lower' => array() ),
        array( 'tags' => array(), 'tags_lower' => array() ),
    );
    $codes              = sschp_explanation_codes(
        $explainer->explain(
            array(
                'query'      => 'Beacon',
                'posts_data' => $posts_without_tags,
            )
        )
    );
    sschp_assert_true( in_array( 'results_missing_tags', $codes, true ), 'search explainer should report results without chip tags' );

    $redundant_posts = array(
        array( 'tags' => array( 'Steel' ), 'tags_lower' => array( 'steel' ) ),
        array( 'tags' => array( 'Steel' ), 'tags_lower' => array( 'steel' ) ),
    );
    $codes           = sschp_explanation_codes(
        $explainer->explain(
            array(
                'query'      => 'Beacon',
                'posts_data' => $redundant_posts,
                'chips'      => array(),
            )
        )
    );
    sschp_assert_true( in_array( 'all_tags_redundant', $codes, true ), 'search explainer should report tags shared by every result' );

    $long_tag_posts = array(
        array( 'tags' => array( 'Very Long Catalog Descriptor' ), 'tags_lower' => array( 'very long catalog descriptor' ) ),
        array( 'tags' => array( 'Very Long Catalog Descriptor' ), 'tags_lower' => array( 'very long catalog descriptor' ) ),
    );
    $codes          = sschp_explanation_codes(
        $explainer->explain(
            array(
                'query'          => 'Beacon',
                'posts_data'     => $long_tag_posts,
                'chips'          => array(),
                'max_tag_length' => 8,
            )
        )
    );
    sschp_assert_true( in_array( 'tags_exceed_max_length', $codes, true ), 'search explainer should report tags filtered by max tag length' );
}

function sschp_test_search_explainer_reports_environment_causes() {
    global $sschp_test_options;

    sschp_reset_test_state();

    $engine   = new SSCHP_Chip_Engine();
    $settings = new SSCHP_Test_Settings(
        array(
            'post_types'  => array( 'post' ),
            'woo_enabled' => '0',
        )
    );
    $index    = new SSCHP_Search_Index( $settings, $engine );
    $explainer = new SSCHP_Search_Explainer( $engine, $settings );

    $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION] = 'queued';
    $codes = sschp_explanation_codes(
        $explainer->explain(
            array(
                'query'              => 'Beacon',
                'post_types'         => array( 'post' ),
                'search_path'        => 'wp_query_fallback',
                'search_index'       => $index,
                'woocommerce_active' => false,
            )
        )
    );
    sschp_assert_true( in_array( 'local_index_queued', $codes, true ), 'search explainer should report queued local index state' );

    $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION] = 'processing';
    $codes = sschp_explanation_codes(
        $explainer->explain(
            array(
                'query'              => 'Beacon',
                'post_types'         => array( 'post' ),
                'search_path'        => 'wp_query_fallback',
                'search_index'       => $index,
                'woocommerce_active' => false,
            )
        )
    );
    sschp_assert_true( in_array( 'local_index_processing', $codes, true ), 'search explainer should report processing local index state' );

    $codes = sschp_explanation_codes(
        $explainer->explain(
            array(
                'query'                           => 'Beacon',
                'post_types'                      => array( 'post' ),
                'woocommerce_active'              => true,
                'woocommerce_helper_tags_enabled' => false,
            )
        )
    );
    sschp_assert_true( in_array( 'woocommerce_products_not_selected', $codes, true ), 'search explainer should report when product searches are not selected for WooCommerce' );
    sschp_assert_true( in_array( 'woocommerce_helper_tags_disabled', $codes, true ), 'search explainer should report disabled WooCommerce helper tags' );

    $codes = sschp_explanation_codes(
        $explainer->explain(
            array(
                'query'                     => 'Beacon',
                'theme_context'             => true,
                'result_container_selector' => 'div[',
            )
        )
    );
    sschp_assert_true( in_array( 'result_container_selector_invalid', $codes, true ), 'search explainer should report invalid theme result selector' );

    $codes = sschp_explanation_codes(
        $explainer->explain(
            array(
                'query'                     => 'Beacon',
                'theme_context'             => true,
                'result_container_selector' => '',
            )
        )
    );
    sschp_assert_true( in_array( 'result_container_selector_missing', $codes, true ), 'search explainer should report missing theme result selector' );
}

function sschp_test_admin_test_search_reports_wp_query_fallback_data() {
    global $sschp_test_post_meta;

    sschp_reset_test_state();
    sschp_register_test_post_for_index( 301, 'post', 'Beacon Alpha', 'Beacon product detail.' );
    sschp_register_test_post_for_index( 302, 'post', 'Beacon Beta', 'Beacon product detail.' );
    $sschp_test_post_meta[301][SSCHP_Meta_Box::META_KEY] = 'Steel; Outdoor';
    $sschp_test_post_meta[302][SSCHP_Meta_Box::META_KEY] = 'Steel; Indoor';

    $handler = new SSCHP_Search_Handler(
        new SSCHP_Chip_Engine(),
        new SSCHP_Test_Settings(
            array(
                'post_types' => array( 'post' ),
            )
        )
    );

    $data = $handler->get_admin_test_search_data(
        array(
            'keyword'    => 'Beacon',
            'post_types' => array( 'post' ),
        )
    );

    sschp_assert_same( 'wp_query_fallback', $data['path'], 'admin test search should report WP_Query fallback when no index or Relevanssi is available' );
    sschp_assert_same( 2, $data['resultCount'], 'admin test search should report the result count' );
    sschp_assert_same( 2, $data['taggedResultCount'], 'admin test search should report tagged result count' );
    sschp_assert_true( ! empty( $data['chips'] ), 'admin test search should include generated chips' );
    sschp_assert_same( 2, count( $data['results'] ), 'admin test search should include first result previews' );
    sschp_assert_same( 'post', $data['results'][0]['postType'], 'admin test search result previews should include post type' );
}

function sschp_test_admin_test_search_reports_local_index_path() {
    global $sschp_test_options, $sschp_test_post_meta;

    sschp_reset_test_state();
    $sschp_test_options[SSCHP_Search_Index::STATUS_OPTION] = 'complete';

    $post = sschp_register_test_post_for_index( 311, 'product', 'Indexed Beacon', 'Indexed product detail.' );
    $sschp_test_post_meta[311][SSCHP_Meta_Box::META_KEY] = 'Indexed; Outdoor';

    $engine   = new SSCHP_Chip_Engine();
    $settings = new SSCHP_Test_Settings(
        array(
            'post_types' => array( 'product' ),
        )
    );
    $index    = new SSCHP_Search_Index( $settings, $engine );
    $index->index_post( 311, $post );

    $handler = new SSCHP_Search_Handler( $engine, $settings, $index );
    $data    = $handler->get_admin_test_search_data(
        array(
            'keyword'    => 'Indexed Beacon',
            'post_types' => array( 'product' ),
        )
    );

    sschp_assert_same( 'local_index', $data['path'], 'admin test search should report when the local index serves the query' );
    sschp_assert_same( 1, $data['resultCount'], 'local-index admin test search should report indexed result count' );
    sschp_assert_same( 1, $data['taggedResultCount'], 'local-index admin test search should report tagged indexed results' );
}

function sschp_test_admin_test_search_formats_range_chip_metadata() {
    $handler = new SSCHP_Search_Handler(
        new SSCHP_Chip_Engine(),
        new SSCHP_Test_Settings()
    );

    $chips = sschp_invoke_private(
        $handler,
        'format_admin_test_chips',
        array(
            array(
                array(
                    'label' => 'mm',
                    'value' => 'sschp_range|mm|mm|307|940|307|940',
                    'count' => 12,
                    'type'  => 'range',
                    'range' => array(
                        'group_key'    => 'mm',
                        'unit_key'     => 'mm',
                        'unit'         => 'mm',
                        'min'          => 307,
                        'max'          => 940,
                        'selected_min' => 307,
                        'selected_max' => 940,
                        'step'         => 1,
                    ),
                ),
            ),
        )
    );

    sschp_assert_same( 'range', $chips[0]['type'], 'admin test search should preserve range chip type' );
    sschp_assert_same( 'mm', $chips[0]['range']['unit'], 'admin test search should expose the range chip unit' );
    sschp_assert_same( 307.0, $chips[0]['range']['min'], 'admin test search should expose the range minimum' );
    sschp_assert_same( 940.0, $chips[0]['range']['max'], 'admin test search should expose the range maximum' );
    sschp_assert_same( 307.0, $chips[0]['range']['selectedMin'], 'admin test search should expose selected range minimum' );
    sschp_assert_same( 940.0, $chips[0]['range']['selectedMax'], 'admin test search should expose selected range maximum' );
}

function sschp_test_settings_admin_test_search_ajax_requires_manage_options() {
    sschp_reset_test_state();
    $_POST = array(
        'nonce' => 'test-nonce',
        'query' => 'Beacon',
    );

    $settings = new SSCHP_Settings();
    $result   = sschp_capture_wp_die(
        function() use ( $settings ) {
            $settings->handle_admin_test_search();
        }
    );

    sschp_assert_true( $result['died'], 'admin test search AJAX should finish through a JSON response' );
    sschp_assert_contains( 'permission', $result['message'], 'admin test search AJAX should reject users without manage_options' );
}

function sschp_test_settings_admin_test_search_ajax_returns_search_json() {
    global $sschp_test_current_user_can, $sschp_test_post_meta;

    sschp_reset_test_state();
    $sschp_test_current_user_can['manage_options'] = true;
    sschp_register_test_post_for_index( 321, 'post', 'Beacon Admin Test', 'Admin test content.' );
    $sschp_test_post_meta[321][SSCHP_Meta_Box::META_KEY] = 'Admin; Test';
    $_POST = array(
        'nonce'      => 'test-nonce',
        'query'      => 'Beacon',
        'post_types' => array( 'post' ),
    );

    $settings = new SSCHP_Settings();
    $result   = sschp_capture_wp_die(
        function() use ( $settings ) {
            $settings->handle_admin_test_search();
        }
    );
    $data = json_decode( $result['message'], true );

    sschp_assert_true( $result['died'], 'admin test search AJAX should finish through a JSON response' );
    sschp_assert_same( 'Beacon', $data['query'], 'admin test search AJAX should return the tested query' );
    sschp_assert_same( 1, $data['resultCount'], 'admin test search AJAX should return result counts' );
    sschp_assert_same( 'wp_query_fallback', $data['path'], 'admin test search AJAX should return the search path' );
    sschp_assert_true( isset( $data['explanations'] ) && is_array( $data['explanations'] ), 'admin test search AJAX should return structured explanations' );
}

function sschp_test_settings_admin_test_search_ajax_returns_short_query_explanations() {
    global $sschp_test_current_user_can;

    sschp_reset_test_state();
    $sschp_test_current_user_can['manage_options'] = true;
    $_POST = array(
        'nonce' => 'test-nonce',
        'query' => '',
    );

    $settings = new SSCHP_Settings();
    $result   = sschp_capture_wp_die(
        function() use ( $settings ) {
            $settings->handle_admin_test_search();
        }
    );
    $data  = json_decode( $result['message'], true );
    $codes = isset( $data['explanations'] ) ? sschp_explanation_codes( $data['explanations'] ) : array();

    sschp_assert_true( $result['died'], 'admin test search AJAX should finish through a JSON response for empty queries' );
    sschp_assert_same( 'not_run', $data['path'], 'empty admin test search should not run a backend search path' );
    sschp_assert_true( in_array( 'no_search_query', $codes, true ), 'empty admin test search should return no-query explanation' );
}

function sschp_test_settings_search_index_control_ajax_rebuilds_queue() {
    global $sschp_test_current_user_can, $sschp_test_options;

    sschp_reset_test_state();
    $sschp_test_current_user_can['manage_options'] = true;
    $sschp_test_options['sschp_post_types']        = array( 'product' );
    sschp_register_test_post_for_index( 331, 'product', 'Control Product One' );
    sschp_register_test_post_for_index( 332, 'product', 'Control Product Two' );
    $_POST = array(
        'nonce'     => 'test-nonce',
        'operation' => 'rebuild',
    );

    $settings = new SSCHP_Settings();
    $result   = sschp_capture_wp_die(
        function() use ( $settings ) {
            $settings->handle_search_index_control();
        }
    );
    $data = json_decode( $result['message'], true );

    sschp_assert_true( $result['died'], 'search index control AJAX should finish through a JSON response' );
    sschp_assert_contains( 'queued', $data['message'], 'search index control AJAX should report rebuild queueing' );
    sschp_assert_same( 'queued', $data['progress']['status'], 'search index control AJAX should return queued status after rebuild' );
    sschp_assert_same( 2, $data['progress']['total'], 'search index control AJAX should return rebuilt index progress' );
    sschp_assert_true( in_array( SSCHP_Search_Index::CRON_HOOK, sschp_test_scheduled_hooks(), true ), 'search index control AJAX rebuild should schedule a batch' );
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
sschp_test_chip_engine_extract_tags_preserves_decimal_commas();
sschp_test_chip_engine_generate_chips();
sschp_test_chip_engine_is_tag_active_case_insensitive();
sschp_test_chip_engine_active_fallback_preserves_stored_label_case();
sschp_test_chip_engine_small_result_sets_show_one_off_tags();
sschp_test_chip_engine_large_result_sets_hide_one_off_tags();
sschp_test_chip_engine_min_count_filter_can_force_two();
sschp_test_chip_engine_min_count_filter_can_force_one();
sschp_test_chip_engine_all_results_tag_hidden_unless_active();
sschp_test_chip_engine_active_tag_survives();
sschp_test_chip_engine_active_tag_survives_zero_results();
sschp_test_chip_engine_active_tags_are_ordered_first();
sschp_test_chip_engine_active_tags_follow_range_chips();
sschp_test_chip_engine_filter_by_tags();
sschp_test_field_normalizer_handles_acf_checkbox_array();
sschp_test_field_normalizer_handles_acf_relationship_posts();
sschp_test_field_normalizer_handles_taxonomy_terms();
sschp_test_field_normalizer_handles_numeric_fields();
sschp_test_field_normalizer_ignores_empty_null_and_plain_objects();
sschp_test_search_handler_get_field_value_normalizes_acf_arrays();
sschp_test_frontend_get_field_value_normalizes_acf_relationship_posts();
sschp_run_if_class_exists( 'SSCHP_Chip_Rules', 'sschp_test_chip_rules_export_returns_expected_json_shape' );
sschp_run_if_class_exists( 'SSCHP_Chip_Rules', 'sschp_test_chip_rules_valid_import_saves_rules' );
sschp_run_if_class_exists( 'SSCHP_Chip_Rules', 'sschp_test_chip_rules_import_dry_run_does_not_save_rules' );
sschp_run_if_class_exists( 'SSCHP_Chip_Rules', 'sschp_test_chip_rules_invalid_import_is_rejected' );
sschp_run_if_class_exists( 'SSCHP_Chip_Rules', 'sschp_test_chip_rules_unauthorized_import_is_rejected' );
sschp_run_if_class_exists( 'SSCHP_Chip_Rules', 'sschp_test_chip_rules_import_export_handlers_are_admin_secured' );
sschp_run_if_class_exists( 'SSCHP_Chip_Rules', 'sschp_test_chip_rules_preserve_range_chip_keys_for_prioritizer' );
sschp_test_wporg_build_excludes_pro_chip_rules_class();
sschp_test_range_chip_css_uses_aligned_desktop_thumbs();
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_chip_renderer_exports_active_chip_colors' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_create_watt_slider_from_consistent_facts' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_keep_ac_dc_voltage_separate' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_can_link_current_ranges_to_voltage_values' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_do_not_link_plain_language_between_voltage_and_current' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_create_asa_ampere_and_newton_sliders' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_create_valve_sliders_from_dn_pn_and_bar_values' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_prioritize_ranges_when_normal_chips_fill_limit' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_selected_chips_follow_range_chips' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_reject_ip_ratings_as_categorical' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_reject_model_numbers' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_normalize_dimensions_to_common_unit' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_create_sliders_from_title_shared_unit_series' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_filters_do_not_mix_prices_with_wattage' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_debug_report_splits_accepted_and_rejected_groups' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_debug_report_uses_structured_rejection_reason_codes' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_debug_report_explains_mixed_units_and_low_confidence' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_debug_report_labels_sources_for_non_developers' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_debug_context_uses_search_query_first' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_debug_context_reuses_search_handler_pipeline' );
sschp_run_if_class_exists( 'SSCHP_Range_Filters', 'sschp_test_range_debug_context_uses_ids_as_advanced_override' );
sschp_test_frontend_shortcode_markup_has_accessible_search_and_live_results();
sschp_test_frontend_registers_legacy_search_shortcode_alias();
sschp_test_frontend_registers_guaranteed_search_block();
sschp_test_frontend_search_block_renders_shortcode_ui();
sschp_test_search_handler_normalizes_active_tags_case_insensitively();
sschp_test_search_handler_render_chips_marks_case_insensitive_active_tag();
sschp_test_search_handler_results_do_not_show_per_product_chip_badge();
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
sschp_run_if_class_exists( 'SSCHP_AI_Tagger', 'sschp_test_ai_rest_writeback_defaults_to_manual_mode' );
sschp_run_if_class_exists( 'SSCHP_AI_Tagger', 'sschp_test_ai_item_browser_uses_bounded_paging' );
sschp_run_if_class_exists( 'SSCHP_AI_Tagger', 'sschp_test_ai_item_browser_load_more_uses_non_overlapping_offsets' );
sschp_run_if_class_exists( 'SSCHP_AI_Tagger', 'sschp_test_ai_item_browser_caps_large_page_sizes' );
sschp_run_if_class_exists( 'SSCHP_AI_Tagger', 'sschp_test_ai_item_browser_requires_manage_options' );
sschp_test_ai_settings_card_wraps_narrow_copy();
sschp_test_ai_prompt_picker_keeps_selection_visible_when_narrow();
sschp_test_free_admin_menu_does_not_add_analytics_upsell_page();
sschp_test_setup_wizard_card_wraps_medium_width_copy();
sschp_test_settings_page_has_section_navigation();
sschp_test_admin_test_search_range_chips_render_as_ranges();
sschp_test_language_request_prefers_explicit_request_value();
sschp_test_language_detects_polylang_slug();
sschp_test_language_applies_wpml_query_args();
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_sanitizes_chip_impression_labels' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_sanitizes_nested_chip_impression_labels' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_appends_ctr_percentages' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_summary_rates_are_consistent' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_escape_csv_value_neutralizes_spreadsheet_formulas' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_guard_tracking_rejects_immediate_replays' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_guard_dedupes_chip_impression_batches' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_report_filters_are_sanitized' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_result_click_tracking_stores_post_and_position' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_does_not_track_without_consent_acknowledgement' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_csv_helpers_omit_visitor_and_page_url' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_digest_schedules_when_enabled' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_digest_reports_next_scheduled_run' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_digest_monthly_last_day_fallback' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_digest_unschedules_when_disabled' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_digest_test_email_uses_wp_mail' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_digest_can_attach_csv_export' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_digest_can_attach_chart_export' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_chart_export_builds_svg_report' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_chart_export_requires_manage_options' );
sschp_test_analytics_dashboard_tables_are_scrollable();
sschp_test_settings_analytics_dashboard_reference_is_styled();
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_digest_preview_exposes_next_email_without_sending' );
sschp_run_if_class_exists( 'SSCHP_Analytics', 'sschp_test_analytics_digest_does_not_send_when_analytics_disabled' );
sschp_test_settings_source_renders_analytics_digest_preview_ui();
sschp_test_settings_digest_day_sanitizer_accepts_controlled_choices();
sschp_test_settings_digest_sanitizers_default_empty_without_warning();
sschp_test_wporg_build_excludes_pro_analytics_class();
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
sschp_test_search_index_upgrade_creates_missing_table_and_queues_index();
sschp_test_search_index_upgrade_repairs_missing_table_even_with_current_version();
sschp_test_search_index_upgrade_skips_current_installed_schema();
sschp_test_search_index_queue_initial_index_schedules_batch();
sschp_test_search_index_rebuild_queues_index();
sschp_test_search_index_clear_index_clears_rows_and_status();
sschp_test_search_index_process_batch_indexes_one_batch();
sschp_test_search_index_paused_index_does_not_process();
sschp_test_search_index_resumed_index_can_process_again();
sschp_test_search_index_paused_complete_index_remains_ready();
sschp_test_search_index_settings_change_queues_rebuild();
sschp_test_search_index_reindexes_saved_post();
sschp_test_search_index_reindexes_product_attribute_meta_change();
sschp_test_search_index_reindexes_term_change();
sschp_test_search_handler_falls_back_before_index_complete();
sschp_test_search_handler_caches_query_results();
sschp_test_search_handler_caches_chip_generation_separately();
sschp_test_search_handler_cache_version_changes_on_relevant_updates();
sschp_test_search_handler_hard_caps_large_catalog_limits();
sschp_test_search_explainer_reports_query_and_chip_causes();
sschp_test_search_explainer_reports_environment_causes();
sschp_test_admin_test_search_reports_wp_query_fallback_data();
sschp_test_admin_test_search_reports_local_index_path();
sschp_test_admin_test_search_formats_range_chip_metadata();
sschp_test_settings_admin_test_search_ajax_requires_manage_options();
sschp_test_settings_admin_test_search_ajax_returns_search_json();
sschp_test_settings_admin_test_search_ajax_returns_short_query_explanations();
sschp_test_settings_search_index_control_ajax_rebuilds_queue();
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
sschp_test_wporg_search_handler_keeps_admin_test_search_api();
sschp_test_wporg_main_loads_search_explainer();
sschp_test_plugin_lifecycle_hooks_unschedule_background_work();
sschp_test_wporg_frontend_source_registers_search_block();
sschp_test_search_block_files_are_release_validated();
sschp_test_release_workflow_requires_version_tag_and_freemius_sdk();
sschp_test_ci_workflow_runs_expanded_release_gates();
sschp_test_release_smoke_checklist_covers_manual_risks();
sschp_test_wporg_pro_promotion_copy_is_contextual_and_compliant();
sschp_test_premium_ajax_numeric_inputs_are_unslashed();

if ( $sschp_test_failures > 0 ) {
    fwrite( STDERR, "\n{$sschp_test_failures} unit test(s) failed.\n" );
    exit( 1 );
}

fwrite( STDOUT, "All unit tests passed.\n" );
