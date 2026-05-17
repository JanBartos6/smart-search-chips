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

function sschp_reset_test_state() {
    global $sschp_test_options, $sschp_test_settings_errors, $sschp_test_filters, $sschp_test_actions, $sschp_test_polylang_language, $sschp_test_freemius, $sschp_test_added_actions, $sschp_test_added_filters, $sschp_test_is_block_theme, $sschp_test_current_user_can, $sschp_test_current_user_calls, $sschp_test_transients;

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
    $_POST                    = array();
    $_REQUEST                 = array();
    $_SERVER                  = array();
}

function sschp_invoke_private( $object, $method_name, array $args = array() ) {
    $reflection = new ReflectionMethod( $object, $method_name );
    $reflection->setAccessible( true );

    return $reflection->invokeArgs( is_object( $object ) ? $object : null, $args );
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
sschp_test_chip_engine_active_tag_survives();
sschp_test_chip_engine_active_tags_are_ordered_first();
sschp_test_chip_engine_filter_by_tags();
sschp_test_license_is_pro_false_without_freemius();
sschp_test_settings_numeric_sanitizers();
sschp_test_settings_reserved_custom_field_rejected();
sschp_test_settings_duplicate_field_rejected();
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
sschp_test_frontend_uses_configured_ajax_action();
sschp_test_search_handler_normalizes_active_tag_payload_shapes();
sschp_test_frontend_registers_loop_fallback_for_block_themes();

if ( $sschp_test_failures > 0 ) {
    fwrite( STDERR, "\n{$sschp_test_failures} unit test(s) failed.\n" );
    exit( 1 );
}

fwrite( STDOUT, "All unit tests passed.\n" );
