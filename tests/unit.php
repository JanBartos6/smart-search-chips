<?php

require_once __DIR__ . '/bootstrap.php';

$ssc_test_failures = 0;

function ssc_test_fail( $message ) {
    global $ssc_test_failures;
    $ssc_test_failures++;
    fwrite( STDERR, "FAIL: {$message}\n" );
}

function ssc_assert_true( $condition, $message ) {
    if ( ! $condition ) {
        ssc_test_fail( $message );
    }
}

function ssc_assert_same( $expected, $actual, $message ) {
    if ( $expected !== $actual ) {
        ssc_test_fail(
            $message . "\n  expected: " . var_export( $expected, true ) . "\n  actual:   " . var_export( $actual, true )
        );
    }
}

function ssc_assert_contains( $needle, $haystack, $message ) {
    if ( false === strpos( $haystack, $needle ) ) {
        ssc_test_fail( $message . "\n  missing: " . $needle );
    }
}

function ssc_reset_test_state() {
    global $ssc_test_options, $ssc_test_settings_errors, $ssc_test_filters, $ssc_test_actions, $ssc_test_polylang_language, $ssc_test_freemius, $ssc_test_added_actions, $ssc_test_added_filters, $ssc_test_is_block_theme;

    $ssc_test_options         = array();
    $ssc_test_settings_errors = array();
    $ssc_test_filters         = array();
    $ssc_test_actions         = array();
    $ssc_test_added_actions   = array();
    $ssc_test_added_filters   = array();
    $ssc_test_polylang_language = '';
    $ssc_test_freemius        = null;
    $ssc_test_is_block_theme  = false;
    $_POST                    = array();
    $_REQUEST                 = array();
}

function ssc_invoke_private( $object, $method_name, array $args = array() ) {
    $reflection = new ReflectionMethod( $object, $method_name );
    $reflection->setAccessible( true );

    return $reflection->invokeArgs( is_object( $object ) ? $object : null, $args );
}

function ssc_test_chip_engine_extract_tags() {
    $engine = new SSC_Chip_Engine();
    $tags   = $engine->extract_tags( 'Steel; IP65; steel', 'Outdoor, CE, outdoor' );

    ssc_assert_same(
        array( 'Steel', 'IP65', 'Outdoor', 'CE' ),
        $tags['tags'],
        'extract_tags should merge primary and extra tags while deduplicating case-insensitively'
    );
}

function ssc_test_chip_engine_extract_tags_supports_comma_delimited_primary_field() {
    $engine = new SSC_Chip_Engine();
    $tags   = $engine->extract_tags( 'spring handle, stainless steel, spring handle', '' );

    ssc_assert_same(
        array( 'spring handle', 'stainless steel' ),
        $tags['tags'],
        'extract_tags should split comma-delimited primary field values used by the built-in ssc_tags field'
    );
}

function ssc_test_chip_engine_generate_chips() {
    $engine = new SSC_Chip_Engine();
    $posts  = array(
        array( 'tags' => array( 'Shared', 'Alpha', 'Outdoor' ), 'tags_lower' => array( 'shared', 'alpha', 'outdoor' ) ),
        array( 'tags' => array( 'Shared', 'Alpha', 'Indoor' ), 'tags_lower' => array( 'shared', 'alpha', 'indoor' ) ),
        array( 'tags' => array( 'Shared', 'Beta', 'Outdoor' ), 'tags_lower' => array( 'shared', 'beta', 'outdoor' ) ),
        array( 'tags' => array( 'Shared', 'Beta', 'Indoor' ), 'tags_lower' => array( 'shared', 'beta', 'indoor' ) ),
    );

    $chips = $engine->generate_chips( $posts, array(), 8, 40 );

    ssc_assert_true( ! isset( $chips['shared'] ), 'generate_chips should skip tags present in every result' );
    ssc_assert_true( isset( $chips['alpha'] ), 'generate_chips should keep useful differentiating tags' );
    ssc_assert_true( isset( $chips['beta'] ), 'generate_chips should keep useful differentiating tags from the other half of the set' );
}

function ssc_test_chip_engine_active_tag_survives() {
    $engine = new SSC_Chip_Engine();
    $posts  = array(
        array( 'tags' => array( 'Steel', 'Outdoor' ), 'tags_lower' => array( 'steel', 'outdoor' ) ),
        array( 'tags' => array( 'Steel', 'Indoor' ), 'tags_lower' => array( 'steel', 'indoor' ) ),
    );

    $chips = $engine->generate_chips( $posts, array( 'Nonexistent Tag' ), 5, 40 );

    ssc_assert_true( isset( $chips['nonexistent tag'] ), 'generate_chips should preserve an active tag even if it would not normally qualify' );
    ssc_assert_same( 'Nonexistent Tag', $chips['nonexistent tag']['label'], 'active tags should preserve their original label in the chip output' );
}

function ssc_test_chip_engine_active_tags_are_ordered_first() {
    $engine = new SSC_Chip_Engine();
    $posts  = array(
        array( 'tags' => array( 'Shared', 'Alpha', 'Outdoor' ), 'tags_lower' => array( 'shared', 'alpha', 'outdoor' ) ),
        array( 'tags' => array( 'Shared', 'Alpha', 'Indoor' ), 'tags_lower' => array( 'shared', 'alpha', 'indoor' ) ),
        array( 'tags' => array( 'Shared', 'Beta', 'Outdoor' ), 'tags_lower' => array( 'shared', 'beta', 'outdoor' ) ),
        array( 'tags' => array( 'Shared', 'Beta', 'Indoor' ), 'tags_lower' => array( 'shared', 'beta', 'indoor' ) ),
    );

    $chips = $engine->generate_chips( $posts, array( 'Outdoor' ), 8, 40 );
    $keys  = array_keys( $chips );

    ssc_assert_same( 'outdoor', $keys[0], 'active chips should be ordered before inactive chips' );
}

function ssc_test_chip_engine_filter_by_tags() {
    $engine = new SSC_Chip_Engine();
    $posts  = array(
        array( 'id' => 1, 'tags_lower' => array( 'steel', 'outdoor' ) ),
        array( 'id' => 2, 'tags_lower' => array( 'steel', 'indoor' ) ),
        array( 'id' => 3, 'tags_lower' => array( 'plastic', 'outdoor' ) ),
    );

    $filtered = $engine->filter_by_tags( $posts, array( 'Steel', 'Outdoor' ) );

    ssc_assert_same( 1, count( $filtered ), 'filter_by_tags should require all active tags to be present' );
    ssc_assert_same( 1, $filtered[0]['id'], 'filter_by_tags should keep the matching item' );
}

function ssc_test_settings_numeric_sanitizers() {
    $settings = new SSC_Settings();

    ssc_assert_same( 16, $settings->sanitize_chip_radius( '' ), 'empty chip radius should fall back to the default' );
    ssc_assert_same( 24, $settings->sanitize_chip_font_size( 99 ), 'chip font size should clamp to the configured maximum' );
    ssc_assert_same( 0, $settings->sanitize_chip_padding_x( -10 ), 'chip horizontal padding should clamp to zero' );
    ssc_assert_same( 24, $settings->sanitize_chip_padding_y( 24 ), 'chip vertical padding should keep valid values unchanged' );
}

function ssc_test_settings_reserved_custom_field_rejected() {
    global $ssc_test_settings_errors;

    ssc_reset_test_state();

    $settings = new SSC_Settings();
    $result   = $settings->sanitize_custom_tag_field( 'ssc_tags' );

    ssc_assert_same( '', $result, 'reserved built-in field name should be rejected for custom tag field mapping' );
    ssc_assert_true( ! empty( $ssc_test_settings_errors ), 'rejecting a reserved custom field should register a settings error' );
}

function ssc_test_settings_duplicate_field_rejected() {
    global $ssc_test_settings_errors, $ssc_test_options;

    ssc_reset_test_state();
    $ssc_test_options['ssc_tag_fields'] = 'manual_tags';
    $_POST['ssc_tag_fields']            = 'manual_tags';

    $settings = new SSC_Settings();
    $result   = $settings->sanitize_extra_tag_field( 'manual_tags' );

    ssc_assert_same( '', $result, 'extra tag field should reject the same key already used by the custom tag field' );
    ssc_assert_true( ! empty( $ssc_test_settings_errors ), 'duplicate mapped field names should register a settings error' );
}

function ssc_test_ai_prompt_manual_mode() {
    $tagger = new SSC_AI_Tagger( new SSC_Test_Settings() );
    $posts  = array(
        (object) array( 'ID' => 12, 'post_type' => 'product', 'post_title' => 'Trapped Key Alpha' ),
        (object) array( 'ID' => 13, 'post_type' => 'product', 'post_title' => 'Trapped Key Beta' ),
    );
    $config = array(
        'custom_instruction' => 'Prefer engineering terminology.',
    );
    $summary = array(
        'restEnabled'       => false,
        'targetMetaKey'     => 'ssc_tags',
        'targetFieldLabel'  => 'Built-in Search Chips Tags',
        'customReadFields'  => array( 'material', 'compatibility' ),
        'maxTags'           => 0,
        'routes'            => array(),
    );

    $prompt = ssc_invoke_private( $tagger, 'build_agent_prompt', array( $posts, $config, $summary ) );

    ssc_assert_contains( 'manual review', $prompt, 'manual-mode prompt should explicitly describe manual review output' );
    ssc_assert_contains( 'There is no hard tag limit', $prompt, 'manual-mode prompt should explain the no-limit max tag case' );
    ssc_assert_contains( 'material, compatibility', $prompt, 'manual-mode prompt should include extra custom fields when provided' );
    ssc_assert_true( false === strpos( $prompt, 'WordPress Application Password' ), 'manual-mode prompt should not ask for REST credentials' );
}

function ssc_test_ai_prompt_rest_mode() {
    $tagger = new SSC_AI_Tagger( new SSC_Test_Settings() );
    $posts  = array(
        (object) array( 'ID' => 22, 'post_type' => 'product', 'post_title' => 'Trapped Key Gamma' ),
    );
    $config = array(
        'custom_instruction' => '',
    );
    $summary = array(
        'restEnabled'       => true,
        'targetMetaKey'     => 'ssc_tags',
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

    $prompt = ssc_invoke_private( $tagger, 'build_agent_prompt', array( $posts, $config, $summary ) );

    ssc_assert_contains( 'write the final values back to WordPress', $prompt, 'REST-mode prompt should describe write-back behavior' );
    ssc_assert_contains( '"ssc_tags": "Tag one; Tag two; Tag three"', $prompt, 'REST-mode prompt should include the meta payload example' );
    ssc_assert_contains( 'WordPress Application Password: <PASTE HERE>', $prompt, 'REST-mode prompt should include the WordPress credential placeholders' );
    ssc_assert_contains( 'REST route: http://example.test/wp-json/wp/v2/product/22', $prompt, 'REST-mode prompt should include the selected item route' );
}

function ssc_test_language_request_prefers_explicit_request_value() {
    ssc_reset_test_state();

    $_REQUEST['ssc_lang'] = 'cs';

    ssc_assert_same( 'cs', SSC_Language::request_language(), 'request_language should prefer the explicit request language when present' );
}

function ssc_test_language_detects_polylang_slug() {
    global $ssc_test_polylang_language;

    ssc_reset_test_state();
    $ssc_test_polylang_language = 'de';

    ssc_assert_same( 'de', SSC_Language::current_language(), 'current_language should use the active Polylang language slug' );
}

function ssc_test_language_applies_wpml_query_args() {
    global $ssc_test_filters, $ssc_test_actions;

    ssc_reset_test_state();
    $ssc_test_filters['wpml_current_language'] = 'fr';
    $ssc_test_filters['wpml_switch_language']  = true;

    $args = SSC_Language::apply_query_language( array( 'post_type' => 'post' ) );
    SSC_Language::switch_ajax_language( 'fr' );

    ssc_assert_same( 'fr', $args['lang'], 'apply_query_language should attach the current WPML language slug' );
    ssc_assert_same( false, $args['suppress_filters'], 'apply_query_language should keep WPML query filters enabled' );
    ssc_assert_same( 'wpml_switch_language', $ssc_test_actions[0]['hook'], 'switch_ajax_language should trigger the WPML language switch action' );
    ssc_assert_same( 'fr', $ssc_test_actions[0]['value'], 'switch_ajax_language should switch to the requested language' );
}

function ssc_test_analytics_sanitizes_chip_impression_labels() {
    $labels = ssc_invoke_private( 'SSC_Analytics', 'sanitize_chip_labels', array( array( ' spring handle ', 'spring handle', '', 'IP67' ) ) );

    ssc_assert_same( array( 'spring handle', 'IP67' ), $labels, 'sanitize_chip_labels should trim, deduplicate, and discard empty impression labels' );
}

function ssc_test_analytics_appends_ctr_percentages() {
    $rows = ssc_invoke_private( 'SSC_Analytics', 'append_percentage_to_rows', array(
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

    ssc_assert_same( 25.0, $rows[0]['click_through_rate'], 'append_percentage_to_rows should compute percentage metrics with one decimal place' );
}

function ssc_test_frontend_uses_default_ajax_action_after_free_downgrade() {
    $frontend = new SSC_Frontend(
        new SSC_Test_Settings(
            array(
                'override_ajax' => 'search_processing',
            )
        ),
        new SSC_Chip_Engine()
    );

    $action = ssc_invoke_private( $frontend, 'get_ajax_action_name' );

    ssc_assert_same( 'ssc_search', $action, 'frontend should fall back to the built-in AJAX action when a Pro override remains stored on the free tier' );
}

function ssc_test_search_handler_normalizes_scalar_active_tags() {
    $handler = new SSC_Search_Handler( new SSC_Chip_Engine(), new SSC_Test_Settings() );

    $tags = ssc_invoke_private( $handler, 'normalize_active_tags', array( '["spring handle","spring handle",""]' ) );

    ssc_assert_same(
        array( 'spring handle' ),
        $tags,
        'search handler should safely normalize JSON-encoded or malformed scalar active tag payloads'
    );
}

function ssc_test_frontend_registers_loop_fallback_for_block_themes() {
    global $ssc_test_added_actions, $ssc_test_added_filters, $ssc_test_is_block_theme;

    ssc_reset_test_state();
    $ssc_test_is_block_theme = true;

    $frontend = new SSC_Frontend( new SSC_Test_Settings(), new SSC_Chip_Engine() );
    $frontend->init();

    $action_hooks = array_column( $ssc_test_added_actions, 'hook' );
    $filter_hooks = array_column( $ssc_test_added_filters, 'hook' );

    ssc_assert_true( in_array( 'loop_start', $action_hooks, true ), 'frontend should always register the loop_start archive-chip fallback' );
    ssc_assert_true( in_array( 'render_block', $filter_hooks, true ), 'frontend should still register block-theme query injection on block themes' );
}

ssc_test_chip_engine_extract_tags();
ssc_test_chip_engine_extract_tags_supports_comma_delimited_primary_field();
ssc_test_chip_engine_generate_chips();
ssc_test_chip_engine_active_tag_survives();
ssc_test_chip_engine_active_tags_are_ordered_first();
ssc_test_chip_engine_filter_by_tags();
ssc_test_settings_numeric_sanitizers();
ssc_test_settings_reserved_custom_field_rejected();
ssc_test_settings_duplicate_field_rejected();
ssc_test_ai_prompt_manual_mode();
ssc_test_ai_prompt_rest_mode();
ssc_test_language_request_prefers_explicit_request_value();
ssc_test_language_detects_polylang_slug();
ssc_test_language_applies_wpml_query_args();
ssc_test_analytics_sanitizes_chip_impression_labels();
ssc_test_analytics_appends_ctr_percentages();
ssc_test_frontend_uses_default_ajax_action_after_free_downgrade();
ssc_test_search_handler_normalizes_scalar_active_tags();
ssc_test_frontend_registers_loop_fallback_for_block_themes();

if ( $ssc_test_failures > 0 ) {
    fwrite( STDERR, "\n{$ssc_test_failures} unit test(s) failed.\n" );
    exit( 1 );
}

fwrite( STDOUT, "All unit tests passed.\n" );
