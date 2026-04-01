<?php
/**
 * Plugin Name: Smart Search Chips
 * Plugin URI:  https://github.com/janbartos/smart-search-chips
 * Description: Dynamic filter chips for WordPress search. Generates smart, non-redundant filter tags from search results using custom fields. Works with Relevanssi for best results.
 * Version:     1.0.0
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

define( 'SSC_VERSION', '1.0.0' );
define( 'SSC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SSC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Core includes
 */
require_once SSC_PLUGIN_DIR . 'includes/class-chip-engine.php';
require_once SSC_PLUGIN_DIR . 'includes/class-search-handler.php';
require_once SSC_PLUGIN_DIR . 'includes/class-settings.php';
require_once SSC_PLUGIN_DIR . 'includes/class-frontend.php';

/**
 * Initialize the plugin
 */
function ssc_init() {
    $settings = new SSC_Settings();
    $settings->init();

    $engine = new SSC_Chip_Engine();

    $handler = new SSC_Search_Handler( $engine, $settings );
    $handler->init();

    $frontend = new SSC_Frontend( $settings );
    $frontend->init();
}
add_action( 'plugins_loaded', 'ssc_init' );

/**
 * Register meta fields for REST API access
 */
function ssc_register_meta() {
    $post_types = get_option( 'ssc_post_types', array( 'post' ) );
    if ( ! is_array( $post_types ) ) {
        $post_types = array( $post_types );
    }

    $fields = array();
    $tag_field   = get_option( 'ssc_tag_fields', '' );
    $extra_field = get_option( 'ssc_extra_fields', '' );
    if ( $tag_field )   $fields[] = $tag_field;
    if ( $extra_field ) $fields[] = $extra_field;

    foreach ( $post_types as $pt ) {
        foreach ( $fields as $field ) {
            register_post_meta( $pt, $field, array(
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => 'string',
                'auth_callback' => '__return_true',
            ) );
        }
    }
}
add_action( 'init', 'ssc_register_meta' );

/**
 * Activation hook
 */
function ssc_activate() {
    $defaults = array(
        'ssc_post_types'        => array( 'post' ),
        'ssc_tag_fields'        => '',
        'ssc_extra_fields'      => '',
        'ssc_max_chips'         => 8,
        'ssc_max_results'       => 10,
        'ssc_relevance_threshold' => 0.3,
        'ssc_max_tag_length'    => 40,
        'ssc_chip_color'        => '#e74c3c',
        'ssc_chip_text_color'   => '#ffffff',
        'ssc_override_ajax'     => '',
        'ssc_search_results_count' => 50,
    );

    foreach ( $defaults as $key => $value ) {
        if ( false === get_option( $key ) ) {
            add_option( $key, $value );
        }
    }
}
register_activation_hook( __FILE__, 'ssc_activate' );
