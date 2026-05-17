<?php
/**
 * Plugin Name: Smart Search Chips
 * Plugin URI:  https://smartsearchchips.app
 * Description: Dynamic filter chips for WordPress search. Generates smart, non-redundant filter tags from search results using custom fields. Works with Relevanssi for best results.
 * Version:     1.0.15
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

$sschp_current_basename = plugin_basename( __FILE__ );

if ( defined( 'SSCHP_PLUGIN_BASENAME' ) && SSCHP_PLUGIN_BASENAME !== $sschp_current_basename ) {
    if ( ! function_exists( 'sschp_premium_handoff_from_free' ) ) {
        /**
         * Deactivate the WordPress.org package before the premium package loads.
         *
         * The free WordPress.org build intentionally ships without Freemius. When
         * the premium ZIP is installed next to it, both packages would otherwise
         * declare the same classes during activation.
         */
        function sschp_premium_handoff_from_free() {
            if ( ! function_exists( 'deactivate_plugins' ) && is_admin() ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            if ( function_exists( 'deactivate_plugins' ) && defined( 'SSCHP_PLUGIN_BASENAME' ) ) {
                deactivate_plugins( SSCHP_PLUGIN_BASENAME );
                update_option( 'sschp_pending_pro_activation_defaults', '1', false );
            }
        }
    }

    sschp_premium_handoff_from_free();
    return;
}

if ( function_exists( 'sschp_fs' ) ) {
    $sschp_fs = sschp_fs();

    if ( $sschp_fs ) {
        $sschp_fs->set_basename( true, __FILE__ );
    }
} else {
    if ( ! function_exists( 'sschp_fs' ) ) {
        /**
         * Create a helper function for easy SDK access.
         *
         * @return Freemius_Abstract|null
         */
        function sschp_fs() {
            global $sschp_fs;

            if ( isset( $sschp_fs ) ) {
                return $sschp_fs;
            }

            $sdk_start = dirname( __FILE__ ) . '/vendor/freemius/start.php';

            if ( ! file_exists( $sdk_start ) ) {
                $sdk_start = dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk-master/start.php';
            }

            if ( ! file_exists( $sdk_start ) ) {
                return null;
            }

            require_once $sdk_start;

            $sschp_fs = fs_dynamic_init(
                array(
                    'id'                  => '27046',
                    'slug'                => 'smart-search-chips',
                    'premium_slug'        => 'smart-search-chips-premium',
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

            return $sschp_fs;
        }

        $sschp_fs = sschp_fs();

        if ( $sschp_fs ) {
            $sschp_fs->add_action( 'after_uninstall', 'sschp_fs_uninstall_cleanup' );
            do_action( 'sschp_fs_loaded' );
        }
    }
}

    /**
     * Clean up all plugin data on Freemius uninstall.
     */
    function sschp_fs_uninstall_cleanup() {
        if ( get_option( 'sschp_delete_data_on_uninstall', '0' ) !== '1' ) {
            return;
        }

        global $wpdb;

        $options = sschp_get_option_keys();
        foreach ( $options as $option ) {
            delete_option( $option );
        }

        delete_metadata( 'post', 0, 'sschp_tags', '', true );
        delete_metadata( 'post', 0, '_sschp_generated_tags', '', true );

        $table_name = $wpdb->prefix . 'sschp_events';
        $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    define( 'SSCHP_VERSION', '1.0.15' );
    define( 'SSCHP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
    define( 'SSCHP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    define( 'SSCHP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

    /**
     * Core includes
     */
    require_once SSCHP_PLUGIN_DIR . 'includes/class-license.php';
    require_once SSCHP_PLUGIN_DIR . 'includes/class-language.php';
    require_once SSCHP_PLUGIN_DIR . 'includes/class-field-normalizer.php';
    require_once SSCHP_PLUGIN_DIR . 'includes/class-chip-engine.php';
    require_once SSCHP_PLUGIN_DIR . 'includes/class-search-handler.php';
    require_once SSCHP_PLUGIN_DIR . 'includes/class-settings.php';
    require_once SSCHP_PLUGIN_DIR . 'includes/class-frontend.php';
    require_once SSCHP_PLUGIN_DIR . 'includes/class-meta-box.php';
    require_once SSCHP_PLUGIN_DIR . 'includes/class-woocommerce.php';

    if ( SSCHP_License::is_pro() ) {
        require_once SSCHP_PLUGIN_DIR . 'includes/class-chip-rules__premium_only.php';
        require_once SSCHP_PLUGIN_DIR . 'includes/class-range-filters__premium_only.php';
        require_once SSCHP_PLUGIN_DIR . 'includes/class-analytics__premium_only.php';
        require_once SSCHP_PLUGIN_DIR . 'includes/class-ai-tagger__premium_only.php';
    }

    /**
     * Initialize the plugin
     */
    function sschp_init() {
        if ( get_option( 'sschp_pending_pro_activation_defaults', '0' ) === '1' ) {
            sschp_activate();
            delete_option( 'sschp_pending_pro_activation_defaults' );
        }

        $license = new SSCHP_License();
        $license->init();

        $settings = new SSCHP_Settings();
        $settings->init();

        $engine = new SSCHP_Chip_Engine();

        $handler = new SSCHP_Search_Handler( $engine, $settings );
        $handler->init();

        $frontend = new SSCHP_Frontend( $settings, $engine );
        $frontend->init();

        if (
            SSCHP_License::is_pro()
            && class_exists( 'SSCHP_Chip_Rules' )
            && class_exists( 'SSCHP_Range_Filters' )
            && class_exists( 'SSCHP_Analytics' )
            && class_exists( 'SSCHP_AI_Tagger' )
        ) {
            $chip_rules = new SSCHP_Chip_Rules();
            $chip_rules->init();

            $range_filters = new SSCHP_Range_Filters();
            $range_filters->init();

            $analytics = new SSCHP_Analytics();
            $analytics->init();

            $ai_tagger = new SSCHP_AI_Tagger( $settings );
            $ai_tagger->init();
        }

        // Always show the built-in meta box (free feature)
        $meta_box = new SSCHP_Meta_Box();
        $meta_box->init();

        // WooCommerce integration (if WooCommerce is active)
        $woo = new SSCHP_WooCommerce();
        $woo->init();
    }
    add_action( 'plugins_loaded', 'sschp_init' );

    /**
     * Register meta fields for REST API access
     */
    function sschp_register_meta() {
        $post_types = get_option( 'sschp_post_types', array( 'post' ) );
        if ( ! is_array( $post_types ) ) {
            $post_types = array( $post_types );
        }

        // Only register custom field meta if Pro
        if ( SSCHP_License::is_pro() ) {
            $fields = array();
            $tag_field   = get_option( 'sschp_tag_fields', '' );
            $extra_field = get_option( 'sschp_extra_fields', '' );
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
                            'auth_callback' => 'sschp_can_edit_post_meta',
                        )
                    );
                }
            }
        }
    }
    add_action( 'init', 'sschp_register_meta' );

    /**
     * Activation hook
     */
    function sschp_activate() {
        $defaults = array(
            'sschp_post_types'              => array( 'post' ),
            'sschp_tag_fields'              => '',
            'sschp_extra_fields'            => '',
            'sschp_max_chips'               => 8,
            'sschp_max_results'             => 10,
            'sschp_relevance_threshold'     => 0.3,
            'sschp_max_tag_length'          => 40,
            'sschp_chip_color'              => '#e74c3c',
            'sschp_chip_text_color'         => '#ffffff',
            'sschp_chip_inactive_color'     => '#ffffff',
            'sschp_chip_inactive_text_color'=> '#333333',
            'sschp_chip_radius'             => 16,
            'sschp_chip_font_size'          => 13,
            'sschp_chip_padding_x'          => 12,
            'sschp_chip_padding_y'          => 4,
            'sschp_woo_enabled'             => '1',
            'sschp_woo_include_sku'         => '0',
            'sschp_override_ajax'           => '',
            'sschp_ajax_keyword_field'       => 'keyword',
            'sschp_result_container_selector'=> '.js-ssc-results, .js-search__results, .ssc-search__results',
            'sschp_search_input_selector'    => '.ssc-search__input, input[type="search"], input[name="keyword"], input[name="s"], input[name="query"], input[name="search"]',
            'sschp_result_image_fit'         => '',
            'sschp_result_image_size'        => 'medium',
            'sschp_result_image_display_size'=> '',
            'sschp_search_results_count'    => 50,
            'sschp_synonym_rules'            => '',
            'sschp_pinned_chips'             => '',
            'sschp_hidden_chips'             => '',
            'sschp_renamed_chips'            => '',
            'sschp_analytics_enabled'       => '0',
            'sschp_ai_custom_read_fields'   => '',
            'sschp_ai_target_field'         => 'built_in',
            'sschp_ai_rest_enabled'         => '1',
            'sschp_ai_max_tags'             => 8,
            'sschp_ai_language'             => '',
            'sschp_ai_custom_instruction'   => '',
            'sschp_delete_data_on_uninstall'=> '0',
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }

        if ( SSCHP_License::is_pro() && class_exists( 'SSCHP_Analytics' ) ) {
            SSCHP_Analytics::create_table();
        }
    }
    register_activation_hook( __FILE__, 'sschp_activate' );

    /**
     * Return the list of plugin option keys owned by Smart Search Chips.
     *
     * @return array
     */
    function sschp_get_option_keys() {
        return array(
            'sschp_post_types',
            'sschp_tag_fields',
            'sschp_extra_fields',
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
            'sschp_synonym_rules',
            'sschp_pinned_chips',
            'sschp_hidden_chips',
            'sschp_renamed_chips',
            'sschp_license_key',
            'sschp_license_status',
            'sschp_analytics_enabled',
            'sschp_ai_custom_read_fields',
            'sschp_ai_target_field',
            'sschp_ai_rest_enabled',
            'sschp_ai_max_tags',
            'sschp_ai_language',
            'sschp_ai_custom_instruction',
            'sschp_delete_data_on_uninstall',
        );
    }

    /**
     * Delete plugin-owned data while the plugin is still installed.
     *
     * @param bool $drop_analytics_table Whether to drop the analytics table entirely.
     * @return void
     */
    function sschp_delete_plugin_data( $drop_analytics_table = false ) {
        foreach ( sschp_get_option_keys() as $option ) {
            delete_option( $option );
        }

        delete_metadata( 'post', 0, 'sschp_tags', '', true );
        delete_metadata( 'post', 0, '_sschp_generated_tags', '', true );

        if ( SSCHP_License::is_pro() && class_exists( 'SSCHP_Analytics' ) ) {
            if ( $drop_analytics_table ) {
                SSCHP_Analytics::drop_table();
                delete_option( SSCHP_Analytics::DB_VERSION_OPTION );
            } else {
                SSCHP_Analytics::clear_events();
            }
        }
    }

    /**
     * Privacy Policy suggested content
     */
    function sschp_privacy_policy_content() {
        $content = '<h2>' . __( 'Smart Search Chips', 'smart-search-chips' ) . '</h2>'
            . '<p>' . __( 'This plugin processes search queries entered by visitors to generate dynamic filter chips. Search queries are processed on this server and are not stored or sent to any third-party service.', 'smart-search-chips' ) . '</p>'
            . '<p>' . __( 'If the site owner enables the optional analytics feature, the plugin stores anonymous search and chip interaction events on this WordPress site so administrators can review popular queries, chip usage, click-through rates, and zero-result searches. This analytics feature is designed for aggregate reporting and does not require LLM-provider credentials.', 'smart-search-chips' ) . '</p>'
            . '<p>' . __( 'If an administrator uses the optional AI Prompt Builder, the plugin prepares instructions for an external AI tool. The plugin itself does not send content or credentials to an AI provider and does not request LLM-provider API keys. If the site owner enables REST write-back, they may choose to paste their own WordPress REST credentials into an external service outside this plugin.', 'smart-search-chips' ) . '</p>'
            . '<p>' . __( 'No personal data is collected, stored, or shared by this plugin beyond the site owner\'s own WordPress configuration and any actions they choose to take with external tools.', 'smart-search-chips' ) . '</p>';

        wp_add_privacy_policy_content( 'Smart Search Chips', wp_kses_post( $content ) );
    }
    add_action( 'admin_init', 'sschp_privacy_policy_content' );

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
    function sschp_adjust_freemius_http_args( $args, $url ) {
        if ( false === strpos( $url, 'freemius.com' ) ) {
            return $args;
        }

        $args['connect_timeout'] = max( isset( $args['connect_timeout'] ) ? (int) $args['connect_timeout'] : 0, 20 );
        $args['timeout']         = max( isset( $args['timeout'] ) ? (int) $args['timeout'] : 0, 60 );

        return $args;
    }
    add_filter( 'http_request_args', 'sschp_adjust_freemius_http_args', 10, 2 );
