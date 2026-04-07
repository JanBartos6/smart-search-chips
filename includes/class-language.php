<?php
/**
 * Language helpers for multilingual plugins.
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSC_Language {

    /**
     * Get the active frontend language slug when available.
     *
     * Supports Polylang and WPML. Returns an empty string when the site is
     * effectively monolingual or when no language slug is available.
     *
     * @return string
     */
    public static function current_language() {
        if ( function_exists( 'pll_current_language' ) ) {
            $lang = pll_current_language( 'slug' );
            if ( is_string( $lang ) && '' !== $lang ) {
                return sanitize_key( $lang );
            }
        }

        if ( defined( 'ICL_SITEPRESS_VERSION' ) || has_filter( 'wpml_current_language' ) ) {
            $lang = apply_filters( 'wpml_current_language', null );
            if ( is_string( $lang ) && '' !== $lang ) {
                return sanitize_key( $lang );
            }
        }

        return '';
    }

    /**
     * Resolve the current request language from explicit request data or the
     * active frontend language.
     *
     * @return string
     */
    public static function request_language() {
        $candidates = array(
            isset( $_REQUEST['ssc_lang'] ) ? wp_unslash( $_REQUEST['ssc_lang'] ) : '',
            isset( $_REQUEST['lang'] ) ? wp_unslash( $_REQUEST['lang'] ) : '',
        );

        foreach ( $candidates as $candidate ) {
            $candidate = sanitize_key( (string) $candidate );
            if ( '' !== $candidate ) {
                return $candidate;
            }
        }

        return self::current_language();
    }

    /**
     * Apply the current language to a query argument array.
     *
     * @param array  $args Query arguments.
     * @param string $lang Optional language slug.
     * @return array
     */
    public static function apply_query_language( $args, $lang = '' ) {
        $lang = '' !== $lang ? sanitize_key( $lang ) : self::current_language();

        if ( '' === $lang ) {
            return $args;
        }

        $args['lang'] = $lang;

        if ( defined( 'ICL_SITEPRESS_VERSION' ) || has_filter( 'wpml_current_language' ) ) {
            $args['suppress_filters'] = false;
        }

        return $args;
    }

    /**
     * Switch WPML's language context for custom AJAX requests.
     *
     * @param string $lang Language slug.
     * @return void
     */
    public static function switch_ajax_language( $lang ) {
        $lang = sanitize_key( (string) $lang );

        if ( '' === $lang ) {
            return;
        }

        if ( defined( 'ICL_SITEPRESS_VERSION' ) || has_filter( 'wpml_switch_language' ) ) {
            do_action( 'wpml_switch_language', $lang );
        }
    }
}
