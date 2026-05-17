<?php

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'SSCHP_VERSION', 'test' );
define( 'SSCHP_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'SSCHP_PLUGIN_URL', 'http://example.test/wp-content/plugins/smart-search-chips/' );
define( 'SSCHP_PLUGIN_BASENAME', 'smart-search-chips/smart-search-chips.php' );

$sschp_test_options         = array();
$sschp_test_settings_errors = array();
$sschp_test_filters         = array();
$sschp_test_actions         = array();
$sschp_test_added_actions   = array();
$sschp_test_added_filters   = array();
$sschp_test_polylang_language = '';
$sschp_test_freemius        = null;
$sschp_test_is_block_theme  = false;
$sschp_test_current_user_can = array();
$sschp_test_current_user_calls = array();
$sschp_test_transients      = array();
$sschp_test_nonce_valid     = true;

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return $text;
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $value ) {
        return trim( (string) $value );
    }
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( $value ) {
        return trim( (string) $value );
    }
}

if ( ! function_exists( 'sanitize_hex_color' ) ) {
    function sanitize_hex_color( $color ) {
        $color = trim( (string) $color );
        return preg_match( '/^#[0-9a-fA-F]{6}$/', $color ) ? strtolower( $color ) : '';
    }
}

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $key ) {
        $key = strtolower( (string) $key );
        return preg_replace( '/[^a-z0-9_\-]/', '', $key );
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $value ) {
        return json_encode( $value );
    }
}

if ( ! function_exists( 'mb_strtolower' ) ) {
    function mb_strtolower( $string, $encoding = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return strtolower( (string) $string );
    }
}

if ( ! function_exists( 'mb_strlen' ) ) {
    function mb_strlen( $string, $encoding = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return strlen( (string) $string );
    }
}

if ( ! function_exists( 'wp_parse_args' ) ) {
    function wp_parse_args( $args, $defaults = array() ) {
        return array_merge( (array) $defaults, (array) $args );
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        return $value;
    }
}

if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( $url ) {
        return filter_var( (string) $url, FILTER_SANITIZE_URL );
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text, $domain = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return $text;
    }
}

if ( ! function_exists( 'esc_attr__' ) ) {
    function esc_attr__( $text, $domain = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return $text;
    }
}

if ( ! function_exists( 'esc_html_e' ) ) {
    function esc_html_e( $text, $domain = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        echo $text;
    }
}

if ( ! function_exists( 'esc_attr_e' ) ) {
    function esc_attr_e( $text, $domain = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        echo $text;
    }
}

if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $text ) {
        return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $text ) {
        return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'has_filter' ) ) {
    function has_filter( $hook_name ) {
        global $sschp_test_filters;

        return ! empty( $sschp_test_filters[ $hook_name ] );
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $hook_name, $value ) {
        global $sschp_test_filters;

        if ( isset( $sschp_test_filters[ $hook_name ] ) ) {
            return $sschp_test_filters[ $hook_name ];
        }

        return $value;
    }
}

if ( ! function_exists( 'do_action' ) ) {
    function do_action( $hook_name, $value = null ) {
        global $sschp_test_actions;

        $sschp_test_actions[] = array(
            'hook'  => $hook_name,
            'value' => $value,
        );
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        global $sschp_test_added_actions;

        $sschp_test_added_actions[] = array(
            'hook'     => $hook_name,
            'callback' => $callback,
        );
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        global $sschp_test_added_filters;

        $sschp_test_added_filters[] = array(
            'hook'     => $hook_name,
            'callback' => $callback,
        );
    }
}

if ( ! function_exists( 'pll_current_language' ) ) {
    function pll_current_language( $type = 'slug' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        global $sschp_test_polylang_language;

        return $sschp_test_polylang_language;
    }
}

if ( ! function_exists( 'sschp_fs' ) ) {
    function sschp_fs() {
        global $sschp_test_freemius;

        return $sschp_test_freemius;
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $name, $default = false ) {
        global $sschp_test_options;

        return array_key_exists( $name, $sschp_test_options ) ? $sschp_test_options[ $name ] : $default;
    }
}

if ( ! function_exists( 'add_settings_error' ) ) {
    function add_settings_error( $setting, $code, $message, $type = 'error' ) {
        global $sschp_test_settings_errors;

        $sschp_test_settings_errors[] = array(
            'setting' => $setting,
            'code'    => $code,
            'message' => $message,
            'type'    => $type,
        );
    }
}

if ( ! function_exists( 'add_shortcode' ) ) {
    function add_shortcode( $tag, $callback ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return true;
    }
}

if ( ! function_exists( 'register_setting' ) ) {
    function register_setting( $option_group, $option_name, $args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return true;
    }
}

if ( ! function_exists( 'add_options_page' ) ) {
    function add_options_page() {
        return true;
    }
}

if ( ! function_exists( 'plugin_basename' ) ) {
    function plugin_basename( $file ) {
        return basename( (string) $file );
    }
}

if ( ! function_exists( 'home_url' ) ) {
    function home_url( $path = '/' ) {
        $path = '/' . ltrim( (string) $path, '/' );
        return 'http://example.test' . $path;
    }
}

if ( ! function_exists( 'admin_url' ) ) {
    function admin_url( $path = '' ) {
        return 'http://example.test/wp-admin/' . ltrim( (string) $path, '/' );
    }
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
    function plugin_dir_url( $file ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return 'http://example.test/wp-content/plugins/smart-search-chips/';
    }
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( $file ) {
        return dirname( (string) $file ) . '/';
    }
}

if ( ! function_exists( 'wp_is_block_theme' ) ) {
    function wp_is_block_theme() {
        global $sschp_test_is_block_theme;

        return (bool) $sschp_test_is_block_theme;
    }
}

if ( ! function_exists( 'get_the_title' ) ) {
    function get_the_title( $post ) {
        if ( is_object( $post ) && isset( $post->post_title ) ) {
            return $post->post_title;
        }

        return '';
    }
}

if ( ! function_exists( 'absint' ) ) {
    function absint( $value ) {
        return abs( (int) $value );
    }
}

if ( ! function_exists( 'get_current_user_id' ) ) {
    function get_current_user_id() {
        return 1;
    }
}

if ( ! function_exists( 'update_user_meta' ) ) {
    function update_user_meta( $user_id, $meta_key, $meta_value ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return true;
    }
}

if ( ! function_exists( 'get_user_meta' ) ) {
    function get_user_meta( $user_id, $meta_key, $single = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return '';
    }
}

if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can( $capability ) {
        global $sschp_test_current_user_can, $sschp_test_current_user_calls;

        $args = func_get_args();
        array_shift( $args );

        $sschp_test_current_user_calls[] = array(
            'capability' => $capability,
            'args'       => $args,
        );

        $specific_key = $capability;
        if ( ! empty( $args ) ) {
            $specific_key .= '|' . implode( ':', array_map( 'strval', $args ) );
        }

        if ( array_key_exists( $specific_key, $sschp_test_current_user_can ) ) {
            return (bool) $sschp_test_current_user_can[ $specific_key ];
        }

        if ( array_key_exists( $capability, $sschp_test_current_user_can ) ) {
            return (bool) $sschp_test_current_user_can[ $capability ];
        }

        return false;
    }
}

if ( ! function_exists( 'get_transient' ) ) {
    function get_transient( $key ) {
        global $sschp_test_transients;

        return array_key_exists( $key, $sschp_test_transients ) ? $sschp_test_transients[ $key ] : false;
    }
}

if ( ! function_exists( 'set_transient' ) ) {
    function set_transient( $key, $value, $expiration ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        global $sschp_test_transients;

        $sschp_test_transients[ $key ] = $value;
        return true;
    }
}

if ( ! function_exists( 'delete_transient' ) ) {
    function delete_transient( $key ) {
        global $sschp_test_transients;

        unset( $sschp_test_transients[ $key ] );
        return true;
    }
}

if ( ! function_exists( 'get_post_types' ) ) {
    function get_post_types( $args = array(), $output = 'names' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        $types = array(
            'post' => (object) array(
                'name'  => 'post',
                'label' => 'Posts',
            ),
            'product' => (object) array(
                'name'  => 'product',
                'label' => 'Products',
            ),
        );

        return 'objects' === $output ? $types : array_keys( $types );
    }
}

if ( ! function_exists( 'post_type_exists' ) ) {
    function post_type_exists( $post_type ) {
        return in_array( $post_type, array( 'post', 'page', 'product' ), true );
    }
}

if ( ! function_exists( 'register_post_meta' ) ) {
    function register_post_meta( $post_type, $meta_key, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return true;
    }
}

if ( ! function_exists( 'get_post_meta' ) ) {
    function get_post_meta( $post_id, $key = '', $single = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return '';
    }
}

if ( ! function_exists( 'update_post_meta' ) ) {
    function update_post_meta( $post_id, $meta_key, $meta_value ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return true;
    }
}

if ( ! function_exists( 'add_meta_box' ) ) {
    function add_meta_box() {
        return true;
    }
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
    function wp_nonce_field() {
        return '';
    }
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
    function wp_verify_nonce( $nonce, $action = -1 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        global $sschp_test_nonce_valid;

        return $sschp_test_nonce_valid ? 1 : false;
    }
}

if ( ! function_exists( 'check_admin_referer' ) ) {
    function check_admin_referer( $action = -1, $query_arg = '_wpnonce' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return true;
    }
}

if ( ! function_exists( 'check_ajax_referer' ) ) {
    function check_ajax_referer( $action = -1, $query_arg = false, $stop = true ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return true;
    }
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
    function wp_create_nonce( $action = -1 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return 'test-nonce';
    }
}

if ( ! function_exists( 'wp_die' ) ) {
    function wp_die( $message = '' ) {
        throw new RuntimeException( (string) $message );
    }
}

if ( ! function_exists( 'wp_send_json_error' ) ) {
    function wp_send_json_error( $data = null, $status_code = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        throw new RuntimeException( wp_json_encode( $data ) );
    }
}

if ( ! function_exists( 'wp_send_json_success' ) ) {
    function wp_send_json_success( $data = null, $status_code = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        throw new RuntimeException( wp_json_encode( $data ) );
    }
}

if ( ! function_exists( 'wp_generate_uuid4' ) ) {
    function wp_generate_uuid4() {
        return '00000000-0000-4000-8000-000000000000';
    }
}

if ( ! function_exists( 'wp_timezone' ) ) {
    function wp_timezone() {
        return new DateTimeZone( 'UTC' );
    }
}

if ( ! function_exists( 'wp_safe_redirect' ) ) {
    function wp_safe_redirect( $url ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return true;
    }
}

if ( ! function_exists( 'add_query_arg' ) ) {
    function add_query_arg( $args, $url = '' ) {
        return rtrim( (string) $url, '?' ) . '?' . http_build_query( (array) $args );
    }
}

if ( ! function_exists( 'wp_nonce_url' ) ) {
    function wp_nonce_url( $actionurl, $action = -1, $name = '_wpnonce' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return add_query_arg( array( $name => 'test-nonce' ), $actionurl );
    }
}

if ( ! function_exists( 'checked' ) ) {
    function checked( $checked, $current = true, $echo = true ) {
        $result = (string) $checked === (string) $current ? ' checked="checked"' : '';
        if ( $echo ) {
            echo $result;
        }
        return $result;
    }
}

if ( ! function_exists( 'selected' ) ) {
    function selected( $selected, $current = true, $echo = true ) {
        $result = (string) $selected === (string) $current ? ' selected="selected"' : '';
        if ( $echo ) {
            echo $result;
        }
        return $result;
    }
}

require_once dirname( __DIR__ ) . '/includes/class-license.php';
require_once dirname( __DIR__ ) . '/includes/class-meta-box.php';
require_once dirname( __DIR__ ) . '/includes/class-language.php';
require_once dirname( __DIR__ ) . '/includes/class-chip-engine.php';
require_once dirname( __DIR__ ) . '/includes/class-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-frontend.php';
require_once dirname( __DIR__ ) . '/includes/class-search-handler.php';

$sschp_test_optional_requires = array(
    dirname( __DIR__ ) . '/includes/class-ai-tagger__premium_only.php',
    dirname( __DIR__ ) . '/includes/class-analytics__premium_only.php',
);

foreach ( $sschp_test_optional_requires as $sschp_test_optional_require ) {
    if ( file_exists( $sschp_test_optional_require ) ) {
        require_once $sschp_test_optional_require;
    }
}

class SSCHP_Test_Freemius {
    public $premium = false;

    public function can_use_premium_code() {
        return (bool) $this->premium;
    }

    public function can_use_premium_code__premium_only() {
        return (bool) $this->premium;
    }
}

class SSCHP_Test_Settings extends SSCHP_Settings {
    private $values;

    public function __construct( $values = array() ) {
        $this->values = $values;
    }

    public function get( $key, $default = '' ) {
        return array_key_exists( $key, $this->values ) ? $this->values[ $key ] : $default;
    }
}
