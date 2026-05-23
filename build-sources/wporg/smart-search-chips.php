<?php
/**
 * Plugin Name: Smart Search Chips
 * Plugin URI:  https://smartsearchchips.app
 * Description: Dynamic filter chips for WordPress search. Generates smart, non-redundant filter tags from the built-in Search Chips Tags field. Works with Relevanssi for best results.
 * Version:     1.1.2
 * Author:      Jan Bartos
 * Author URI:  https://teprostroj.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smart-search-chips
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SSCHP_VERSION', '1.1.2' );
define( 'SSCHP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSCHP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SSCHP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once SSCHP_PLUGIN_DIR . 'includes/class-edition.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-language.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-field-normalizer.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-chip-engine.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-search-explainer.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-relevance-scorer.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-search-index.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-search-handler.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-settings.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-frontend.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-meta-box.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-woocommerce.php';

/**
 * Initialize the plugin.
 */
function sschp_init() {
    $settings = new SSCHP_Settings();
    $settings->init();

    $engine = new SSCHP_Chip_Engine();

    $search_index = new SSCHP_Search_Index( $settings, $engine );
    $search_index->init();

    $handler = new SSCHP_Search_Handler( $engine, $settings, $search_index );
    $handler->init();

    $frontend = new SSCHP_Frontend( $settings, $engine );
    $frontend->init();

    $meta_box = new SSCHP_Meta_Box();
    $meta_box->init();

    $woo = new SSCHP_WooCommerce();
    $woo->init();
}
add_action( 'plugins_loaded', 'sschp_init' );

/**
 * Register mapped meta fields for REST API access.
 */
function sschp_register_meta() {
    $post_types = get_option( 'sschp_post_types', array( 'post' ) );
    if ( ! is_array( $post_types ) ) {
        $post_types = array( $post_types );
    }

    foreach ( $post_types as $pt ) {
        register_post_meta(
            $pt,
            SSCHP_Meta_Box::META_KEY,
            array(
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => 'string',
                'auth_callback' => 'sschp_can_edit_post_meta',
            )
        );
    }
}
add_action( 'init', 'sschp_register_meta' );

/**
 * Activation hook.
 */
function sschp_activate() {
    $defaults = array(
        'sschp_post_types'               => array( 'post' ),
        'sschp_max_chips'                => 8,
        'sschp_max_results'              => 10,
        'sschp_relevance_threshold'      => 0.3,
        'sschp_max_tag_length'           => 40,
        'sschp_chip_color'               => '#e74c3c',
        'sschp_chip_text_color'          => '#ffffff',
        'sschp_chip_inactive_color'      => '#ffffff',
        'sschp_chip_inactive_text_color' => '#333333',
        'sschp_chip_radius'              => 16,
        'sschp_chip_font_size'           => 13,
        'sschp_chip_padding_x'           => 12,
        'sschp_chip_padding_y'           => 4,
        'sschp_woo_enabled'              => '1',
        'sschp_woo_include_sku'          => '0',
        'sschp_override_ajax'            => '',
        'sschp_ajax_keyword_field'        => 'keyword',
        'sschp_result_container_selector' => '.js-ssc-results, .js-search__results, .ssc-search__results',
        'sschp_search_input_selector'     => '.ssc-search__input, input[type="search"], input[name="keyword"], input[name="s"], input[name="query"], input[name="search"]',
        'sschp_result_image_fit'          => '',
        'sschp_result_image_size'         => 'medium',
        'sschp_result_image_display_size' => '',
        'sschp_search_results_count'     => 50,
        'sschp_debounce_ms'              => 300,
        'sschp_cache_version'            => '1',
        'sschp_delete_data_on_uninstall' => '0',
    );

    foreach ( $defaults as $key => $value ) {
        if ( false === get_option( $key ) ) {
            add_option( $key, $value );
        }
    }

    SSCHP_Search_Index::create_table();
    SSCHP_Search_Index::queue_initial_index( 'activation' );
}
register_activation_hook( __FILE__, 'sschp_activate' );

/**
 * Return plugin-owned option keys.
 *
 * @return array
 */
function sschp_get_option_keys() {
    return array(
        'sschp_post_types',
        'sschp_max_chips',
        'sschp_max_results',
        'sschp_relevance_threshold',
        'sschp_max_tag_length',
        'sschp_chip_color',
        'sschp_chip_text_color',
        'sschp_chip_inactive_color',
        'sschp_chip_inactive_text_color',
        'sschp_chip_radius',
        'sschp_chip_font_size',
        'sschp_chip_padding_x',
        'sschp_chip_padding_y',
        'sschp_woo_enabled',
        'sschp_woo_include_sku',
        'sschp_override_ajax',
        'sschp_ajax_keyword_field',
        'sschp_result_container_selector',
        'sschp_search_input_selector',
        'sschp_result_image_fit',
        'sschp_result_image_size',
        'sschp_result_image_display_size',
        'sschp_search_results_count',
        'sschp_debounce_ms',
        SSCHP_Search_Handler::CACHE_VERSION_OPTION,
        'sschp_delete_data_on_uninstall',
        SSCHP_Search_Index::DB_VERSION_OPTION,
        SSCHP_Search_Index::STATUS_OPTION,
        SSCHP_Search_Index::TOTAL_OPTION,
        SSCHP_Search_Index::PROCESSED_OPTION,
        SSCHP_Search_Index::CURSOR_OPTION,
        SSCHP_Search_Index::UPDATED_OPTION,
    );
}

/**
 * Delete plugin-owned data while the plugin is still installed.
 *
 * @return void
 */
function sschp_delete_plugin_data() {
    foreach ( sschp_get_option_keys() as $option ) {
        delete_option( $option );
    }

    delete_metadata( 'post', 0, 'sschp_tags', '', true );
    delete_metadata( 'post', 0, '_sschp_generated_tags', '', true );
    SSCHP_Search_Index::drop_table();
}

/**
 * Optional uninstall cleanup.
 */
function sschp_uninstall() {
    if ( get_option( 'sschp_delete_data_on_uninstall', '0' ) !== '1' ) {
        return;
    }

    sschp_delete_plugin_data();
}
register_uninstall_hook( __FILE__, 'sschp_uninstall' );

/**
 * Privacy Policy suggested content.
 */
function sschp_privacy_policy_content() {
    $content = '<h2>' . __( 'Smart Search Chips', 'smart-search-chips' ) . '</h2>'
        . '<p>' . __( 'This plugin processes search queries entered by visitors to generate dynamic filter chips. Search queries are processed on this server and are not stored or sent to any third-party service.', 'smart-search-chips' ) . '</p>'
        . '<p>' . __( 'No personal data is collected, stored, or shared by this plugin beyond the site owner\'s own WordPress configuration.', 'smart-search-chips' ) . '</p>';

    wp_add_privacy_policy_content( 'Smart Search Chips', wp_kses_post( $content ) );
}
add_action( 'admin_init', 'sschp_privacy_policy_content' );
