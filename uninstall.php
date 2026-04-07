<?php
/**
 * Uninstall - Clean up all plugin data
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove all plugin options
$options = array(
    'ssc_post_types',
    'ssc_tag_fields',
    'ssc_extra_fields',
    'ssc_max_chips',
    'ssc_max_results',
    'ssc_relevance_threshold',
    'ssc_max_tag_length',
    'ssc_chip_color',
    'ssc_chip_text_color',
    'ssc_chip_inactive_color',
    'ssc_chip_inactive_text_color',
    'ssc_chip_radius',
    'ssc_chip_font_size',
    'ssc_chip_padding_x',
    'ssc_chip_padding_y',
    'ssc_woo_enabled',
    'ssc_override_ajax',
    'ssc_search_results_count',
    'ssc_license_key',
    'ssc_license_status',
    'ssc_analytics_enabled',
    'ssc_ai_custom_read_fields',
    'ssc_ai_target_field',
    'ssc_ai_rest_enabled',
    'ssc_ai_max_tags',
    'ssc_ai_language',
    'ssc_ai_custom_instruction',
);

foreach ( $options as $option ) {
    delete_option( $option );
}

// Optionally remove all ssc_tags post meta
// Uncomment the following if you want to clean up post meta on uninstall:
// global $wpdb;
// $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'ssc_tags' ) );
