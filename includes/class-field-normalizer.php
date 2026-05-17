<?php
/**
 * Field Normalizer - Converts custom field values into chip-safe strings.
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCHP_Field_Normalizer {

    /**
     * Normalize a custom field value into a semicolon-compatible string.
     *
     * @param mixed $value   Raw field value from ACF or post meta.
     * @param array $context Optional context such as post_id and field_name.
     * @return string
     */
    public static function normalize( $value, $context = array() ) {
        $parts = array();
        self::flatten_value( $value, $parts );
        $parts = self::dedupe_parts( $parts );

        $normalized = implode( '; ', $parts );
        $filtered   = apply_filters( 'sschp_normalize_field_value', $normalized, $value, $context, $parts );

        if ( is_string( $filtered ) || is_int( $filtered ) || is_float( $filtered ) ) {
            return trim( (string) $filtered );
        }

        return $normalized;
    }

    /**
     * Recursively collect scalar-like values from mixed field data.
     *
     * @param mixed $value Raw value.
     * @param array $parts Collected string parts.
     * @return void
     */
    private static function flatten_value( $value, &$parts ) {
        if ( null === $value || is_bool( $value ) ) {
            return;
        }

        if ( is_string( $value ) ) {
            self::add_part( $parts, $value );
            return;
        }

        if ( is_int( $value ) || is_float( $value ) ) {
            self::add_part( $parts, (string) $value );
            return;
        }

        if ( is_array( $value ) ) {
            foreach ( $value as $item ) {
                self::flatten_value( $item, $parts );
            }
            return;
        }

        if ( is_object( $value ) ) {
            if ( self::is_term_object( $value ) ) {
                self::add_part( $parts, $value->name );
                return;
            }

            if ( self::is_post_object( $value ) ) {
                self::add_part( $parts, $value->post_title );
                return;
            }

            if ( method_exists( $value, '__toString' ) ) {
                self::add_part( $parts, (string) $value );
            }
        }
    }

    /**
     * Identify a WP_Term-like object without requiring WordPress classes in tests.
     *
     * @param object $value Object to check.
     * @return bool
     */
    private static function is_term_object( $value ) {
        return ( class_exists( 'WP_Term' ) && $value instanceof WP_Term )
            || ( isset( $value->term_id, $value->name ) && ! isset( $value->post_title ) );
    }

    /**
     * Identify a WP_Post-like object without requiring WordPress classes in tests.
     *
     * @param object $value Object to check.
     * @return bool
     */
    private static function is_post_object( $value ) {
        return ( class_exists( 'WP_Post' ) && $value instanceof WP_Post )
            || isset( $value->ID, $value->post_title );
    }

    /**
     * Add one trimmed, sanitized string part.
     *
     * @param array  $parts Collected parts.
     * @param string $value Raw string.
     * @return void
     */
    private static function add_part( &$parts, $value ) {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            return;
        }

        $parts[] = function_exists( 'sanitize_text_field' ) ? sanitize_text_field( $value ) : $value;
    }

    /**
     * Remove empty and duplicate values case-insensitively.
     *
     * @param array $parts Collected parts.
     * @return array
     */
    private static function dedupe_parts( $parts ) {
        $deduped = array();
        $seen    = array();

        foreach ( $parts as $part ) {
            $part = trim( (string) $part );
            if ( '' === $part ) {
                continue;
            }

            $key = mb_strtolower( $part );
            if ( isset( $seen[ $key ] ) ) {
                continue;
            }

            $seen[ $key ] = true;
            $deduped[]    = $part;
        }

        return $deduped;
    }
}
