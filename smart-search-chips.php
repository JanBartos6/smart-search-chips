<?php
/**
 * Plugin Name: Smart Search Chips
 * Plugin URI:  https://smartsearchchips.app
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

if ( function_exists( 'ssc_fs' ) ) {
    $ssc_fs = ssc_fs();

    if ( $ssc_fs ) {
        $ssc_fs->set_basename( true, __FILE__ );
    }
} else {
    if ( ! function_exists( 'ssc_fs' ) ) {
        /**
         * Create a helper function for easy SDK access.
         *
         * @return Freemius_Abstract|null
         */
        function ssc_fs() {
            global $ssc_fs;

            if ( isset( $ssc_fs ) ) {
                return $ssc_fs;
            }

            $sdk_start = dirname( __FILE__ ) . '/vendor/freemius/start.php';

            if ( ! file_exists( $sdk_start ) ) {
                $sdk_start = dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk-master/start.php';
            }

            if ( ! file_exists( $sdk_start ) ) {
                return null;
            }

            require_once $sdk_start;

            $ssc_fs = fs_dynamic_init(
                array(
                    'id'                  => '27046',
                    'slug'                => 'smart-search-chips',
                    'type'                => 'plugin',
                    'public_key'          => 'pk_69b2a1db30add56864a42e4ea9929',
                    'is_premium'          => true,
                    'premium_suffix'      => 'Pro',
                    'has_premium_version' => true,
                    'has_addons'          => false,
                    'has_paid_plans'      => true,
                    'is_org_compliant'    => true,
                    'wp_org_gatekeeper'   => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
                    'menu'                => array(
                        'slug'           => 'smart-search-chips',
                        'first-path'     => 'options-general.php?page=smart-search-chips',
                        'parent'         => array(
                            'slug' => 'options-general.php',
                        ),
                        'account'        => true,
                        'contact'        => true,
                        'support'        => false,
                    ),
                )
            );

            return $ssc_fs;
        }

        $ssc_fs = ssc_fs();

        if ( $ssc_fs ) {
            do_action( 'ssc_fs_loaded' );
        }
    }

    define( 'SSC_VERSION', '1.0.0' );
    define( 'SSC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
    define( 'SSC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    define( 'SSC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

    /**
     * Core includes
     */
    require_once SSC_PLUGIN_DIR . 'includes/class-license.php';
    require_once SSC_PLUGIN_DIR . 'includes/class-language.php';
    require_once SSC_PLUGIN_DIR . 'includes/class-chip-engine.php';
    require_once SSC_PLUGIN_DIR . 'includes/class-search-handler.php';
    require_once SSC_PLUGIN_DIR . 'includes/class-analytics.php';
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

        $analytics = new SSC_Analytics();
        $analytics->init();

        $frontend = new SSC_Frontend( $settings, $engine );
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
            if ( $tag_field ) {
                $fields[] = $tag_field;
            }
            if ( $extra_field ) {
                $fields[] = $extra_field;
            }

            foreach ( $post_types as $pt ) {
                foreach ( $fields as $field ) {
                    register_post_meta(
                        $pt,
                        $field,
                        array(
                            'show_in_rest'  => true,
                            'single'        => true,
                            'type'          => 'string',
                            'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
                        )
                    );
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
            'ssc_post_types'              => array( 'post' ),
            'ssc_tag_fields'              => '',
            'ssc_extra_fields'            => '',
            'ssc_max_chips'               => 8,
            'ssc_max_results'             => 10,
            'ssc_relevance_threshold'     => 0.3,
            'ssc_max_tag_length'          => 40,
            'ssc_chip_color'              => '#e74c3c',
            'ssc_chip_text_color'         => '#ffffff',
            'ssc_chip_inactive_color'     => '#ffffff',
            'ssc_chip_inactive_text_color'=> '#333333',
            'ssc_chip_radius'             => 16,
            'ssc_chip_font_size'          => 13,
            'ssc_chip_padding_x'          => 12,
            'ssc_chip_padding_y'          => 4,
            'ssc_woo_enabled'             => '1',
            'ssc_override_ajax'           => '',
            'ssc_search_results_count'    => 50,
            'ssc_analytics_enabled'       => '0',
            'ssc_ai_custom_read_fields'   => '',
            'ssc_ai_target_field'         => 'built_in',
            'ssc_ai_rest_enabled'         => '1',
            'ssc_ai_max_tags'             => 8,
            'ssc_ai_language'             => '',
            'ssc_ai_custom_instruction'   => '',
            'ssc_delete_data_on_uninstall'=> '0',
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }

        SSC_Analytics::create_table();
    }
    register_activation_hook( __FILE__, 'ssc_activate' );

    /**
     * Return the list of plugin option keys owned by Smart Search Chips.
     *
     * @return array
     */
    function ssc_get_option_keys() {
        return array(
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
            'ssc_delete_data_on_uninstall',
        );
    }

    /**
     * Delete plugin-owned data while the plugin is still installed.
     *
     * @param bool $drop_analytics_table Whether to drop the analytics table entirely.
     * @return void
     */
    function ssc_delete_plugin_data( $drop_analytics_table = false ) {
        global $wpdb;

        foreach ( ssc_get_option_keys() as $option ) {
            delete_option( $option );
        }

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
                'ssc_tags'
            )
        );

        if ( $drop_analytics_table ) {
            SSC_Analytics::drop_table();
            delete_option( SSC_Analytics::DB_VERSION_OPTION );
        } else {
            SSC_Analytics::clear_events();
        }
    }

    /**
     * Privacy Policy suggested content
     */
    function ssc_privacy_policy_content() {
        $content = '<h2>' . __( 'Smart Search Chips', 'smart-search-chips' ) . '</h2>'
            . '<p>' . __( 'This plugin processes search queries entered by visitors to generate dynamic filter chips. Search queries are processed on this server and are not stored or sent to any third-party service.', 'smart-search-chips' ) . '</p>'
            . '<p>' . __( 'If the site owner enables the optional analytics feature, the plugin stores anonymous search and chip interaction events on this WordPress site so administrators can review popular queries, chip usage, click-through rates, and zero-result searches. This analytics feature is designed for aggregate reporting and does not require LLM-provider credentials.', 'smart-search-chips' ) . '</p>'
            . '<p>' . __( 'If an administrator uses the optional AI Prompt Builder, the plugin prepares instructions for an external AI tool. The plugin itself does not send content or credentials to an AI provider and does not request LLM-provider API keys. If the site owner enables REST write-back, they may choose to paste their own WordPress REST credentials into an external service outside this plugin.', 'smart-search-chips' ) . '</p>'
            . '<p>' . __( 'No personal data is collected, stored, or shared by this plugin beyond the site owner\'s own WordPress configuration and any actions they choose to take with external tools.', 'smart-search-chips' ) . '</p>';

        wp_add_privacy_policy_content( 'Smart Search Chips', wp_kses_post( $content ) );
    }
    add_action( 'admin_init', 'ssc_privacy_policy_content' );

    /**
     * Give Freemius API calls a little more room to establish a connection.
     *
     * Some local Docker setups can hit the default 10-second connect timeout even
     * though the request succeeds once the TLS connection is established.
     *
     * @param array  $args Request arguments.
     * @param string $url  Request URL.
     * @return array
     */
    function ssc_adjust_freemius_http_args( $args, $url ) {
        if ( false === strpos( $url, 'freemius.com' ) ) {
            return $args;
        }

        $args['connect_timeout'] = max( isset( $args['connect_timeout'] ) ? (int) $args['connect_timeout'] : 0, 20 );
        $args['timeout']         = max( isset( $args['timeout'] ) ? (int) $args['timeout'] : 0, 60 );

        return $args;
    }
    add_filter( 'http_request_args', 'ssc_adjust_freemius_http_args', 10, 2 );
}
