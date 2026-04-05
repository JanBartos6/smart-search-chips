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
require_once SSC_PLUGIN_DIR . 'includes/class-license.php';
require_once SSC_PLUGIN_DIR . 'includes/class-chip-engine.php';
require_once SSC_PLUGIN_DIR . 'includes/class-search-handler.php';
require_once SSC_PLUGIN_DIR . 'includes/class-ai-tagger.php';
require_once SSC_PLUGIN_DIR . 'includes/class-settings.php';
require_once SSC_PLUGIN_DIR . 'includes/class-frontend.php';
require_once SSC_PLUGIN_DIR . 'includes/class-meta-box.php';
require_once SSC_PLUGIN_DIR . 'includes/class-woocommerce.php';

/**
 * Initialize the plugin
 */
function ssc_init() {
    $license = new SSC_License();
    $license->init();

    $settings = new SSC_Settings();
    $settings->init();

    $engine = new SSC_Chip_Engine();

    $handler = new SSC_Search_Handler( $engine, $settings );
    $handler->init();

    $frontend = new SSC_Frontend( $settings );
    $frontend->init();

    $ai_tagger = new SSC_AI_Tagger( $settings );
    $ai_tagger->init();

    // Always show the built-in meta box (free feature)
    $meta_box = new SSC_Meta_Box();
    $meta_box->init();

    // WooCommerce integration (if WooCommerce is active)
    $woo = new SSC_WooCommerce();
    $woo->init();
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

    // Only register custom field meta if Pro
    if ( SSC_License::is_pro() ) {
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
}
add_action( 'init', 'ssc_register_meta' );

/**
 * Activation hook
 */
function ssc_activate() {
    $defaults = array(
        'ssc_post_types'          => array( 'post' ),
        'ssc_tag_fields'          => '',
        'ssc_extra_fields'        => '',
        'ssc_max_chips'           => 8,
        'ssc_max_results'         => 10,
        'ssc_relevance_threshold' => 0.3,
        'ssc_max_tag_length'      => 40,
        'ssc_chip_color'           => '#e74c3c',
        'ssc_chip_text_color'      => '#ffffff',
        'ssc_chip_inactive_color'  => '#ffffff',
        'ssc_chip_inactive_text_color' => '#333333',
        'ssc_chip_radius'          => 16,
        'ssc_chip_font_size'       => 13,
        'ssc_chip_padding_x'       => 12,
        'ssc_chip_padding_y'       => 4,
        'ssc_woo_enabled'          => '1',
        'ssc_override_ajax'        => '',
        'ssc_search_results_count' => 50,
        'ssc_ai_source_fields'     => array( 'title', 'excerpt', 'content' ),
        'ssc_ai_custom_read_fields'=> '',
        'ssc_ai_target_field'      => 'built_in',
        'ssc_ai_rest_enabled'      => '1',
        'ssc_ai_max_tags'          => 8,
        'ssc_ai_language'          => '',
        'ssc_ai_custom_instruction'=> '',
    );

    foreach ( $defaults as $key => $value ) {
        if ( false === get_option( $key ) ) {
            add_option( $key, $value );
        }
    }
}
register_activation_hook( __FILE__, 'ssc_activate' );

/**
 * Privacy Policy suggested content
 */
function ssc_privacy_policy_content() {
    $content = '<h2>' . __( 'Smart Search Chips', 'smart-search-chips' ) . '</h2>'
        . '<p>' . __( 'This plugin processes search queries entered by visitors to generate dynamic filter chips. Search queries are processed on this server and are not stored or sent to any third-party service.', 'smart-search-chips' ) . '</p>'
        . '<p>' . __( 'If an administrator uses the optional AI Prompt Builder, the plugin prepares instructions for an external AI tool. The plugin itself does not send content or credentials to an AI provider, but the site owner may choose to paste selected content and their own REST API credentials into an external service outside this plugin.', 'smart-search-chips' ) . '</p>'
        . '<p>' . __( 'No personal data is collected, stored, or shared by this plugin beyond the site owner\'s own WordPress configuration and any actions they choose to take with external tools.', 'smart-search-chips' ) . '</p>';

    wp_add_privacy_policy_content( 'Smart Search Chips', wp_kses_post( $content ) );
}
add_action( 'admin_init', 'ssc_privacy_policy_content' );
