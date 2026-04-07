<?php

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

$ssc_test_options         = array();
$ssc_test_settings_errors = array();
$ssc_test_filters         = array();
$ssc_test_actions         = array();
$ssc_test_polylang_language = '';

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

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $key ) {
        $key = strtolower( (string) $key );
        return preg_replace( '/[^a-z0-9_\-]/', '', $key );
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        return $value;
    }
}

if ( ! function_exists( 'has_filter' ) ) {
    function has_filter( $hook_name ) {
        global $ssc_test_filters;

        return ! empty( $ssc_test_filters[ $hook_name ] );
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $hook_name, $value ) {
        global $ssc_test_filters;

        if ( isset( $ssc_test_filters[ $hook_name ] ) ) {
            return $ssc_test_filters[ $hook_name ];
        }

        return $value;
    }
}

if ( ! function_exists( 'do_action' ) ) {
    function do_action( $hook_name, $value = null ) {
        global $ssc_test_actions;

        $ssc_test_actions[] = array(
            'hook'  => $hook_name,
            'value' => $value,
        );
    }
}

if ( ! function_exists( 'pll_current_language' ) ) {
    function pll_current_language( $type = 'slug' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        global $ssc_test_polylang_language;

        return $ssc_test_polylang_language;
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $name, $default = false ) {
        global $ssc_test_options;

        return array_key_exists( $name, $ssc_test_options ) ? $ssc_test_options[ $name ] : $default;
    }
}

if ( ! function_exists( 'add_settings_error' ) ) {
    function add_settings_error( $setting, $code, $message, $type = 'error' ) {
        global $ssc_test_settings_errors;

        $ssc_test_settings_errors[] = array(
            'setting' => $setting,
            'code'    => $code,
            'message' => $message,
            'type'    => $type,
        );
    }
}

if ( ! function_exists( 'home_url' ) ) {
    function home_url( $path = '/' ) {
        $path = '/' . ltrim( (string) $path, '/' );
        return 'http://example.test' . $path;
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

require_once dirname( __DIR__ ) . '/includes/class-meta-box.php';
require_once dirname( __DIR__ ) . '/includes/class-language.php';
require_once dirname( __DIR__ ) . '/includes/class-chip-engine.php';
require_once dirname( __DIR__ ) . '/includes/class-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-ai-tagger.php';
require_once dirname( __DIR__ ) . '/includes/class-analytics.php';

class SSC_Test_Settings extends SSC_Settings {
    private $values;

    public function __construct( $values = array() ) {
        $this->values = $values;
    }

    public function get( $key, $default = '' ) {
        return array_key_exists( $key, $this->values ) ? $this->values[ $key ] : $default;
    }
}
