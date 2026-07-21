<?php
/**
 * Language helpers for multilingual plugins.
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCHP_Language {

    /**
     * Resolve the language used by archive filter controls.
     *
     * @param string $setting Saved language setting.
     * @return string One of auto, en, or cs.
     */
    public static function archive_filter_language( $setting = 'auto' ) {
        $setting = sanitize_key( (string) $setting );

        if ( in_array( $setting, array( 'en', 'cs' ), true ) ) {
            return $setting;
        }

        $language = self::current_language();
        if ( '' === $language ) {
            if ( function_exists( 'determine_locale' ) ) {
                $language = determine_locale();
            } elseif ( function_exists( 'get_locale' ) ) {
                $language = get_locale();
            }
        }

        $language = strtolower( str_replace( '-', '_', (string) $language ) );
        if ( 'cs' === $language || 0 === strpos( $language, 'cs_' ) ) {
            return 'cs';
        }

        if ( 'en' === $language || 0 === strpos( $language, 'en_' ) ) {
            return 'en';
        }

        return 'auto';
    }

    /**
     * Return a localized archive filter interface string.
     *
     * @param string $key     String identifier.
     * @param string $setting Saved language setting.
     * @return string
     */
    public static function archive_filter_text( $key, $setting = 'auto' ) {
        $language = self::archive_filter_language( $setting );
        $english  = self::translated_archive_filter_text( $key, false );

        if ( 'cs' === $language ) {
            $czech = array(
                'filter_results'           => 'Filtrovat tyto výsledky',
                'refine_search'            => 'Upřesnit toto hledání',
                'clear_filters'            => 'Vymazat filtry',
                'category_description'     => 'Pomocí těchto filtrů můžete porovnávat a filtrovat všechny produkty v této kategorii.',
                'current_page_description' => 'Tyto filtry upřesňují výsledky aktuálně načtené na této stránce. Změna filtrů resetuje stránkování.',
            );

            $text = isset( $czech[ $key ] ) ? $czech[ $key ] : $english;
        } elseif ( 'en' === $language ) {
            $text = $english;
        } else {
            $text = self::translated_archive_filter_text( $key, true );
        }

        return apply_filters( 'sschp_archive_filter_text', $text, $key, $language );
    }

    /**
     * Return a localized archive chip action label for assistive technology.
     *
     * @param string $action   Either remove or filter.
     * @param string $label    Chip label.
     * @param int    $count    Matching result count.
     * @param string $setting  Saved language setting.
     * @return string
     */
    public static function archive_filter_count_label( $action, $label, $count, $setting = 'auto' ) {
        $action   = 'remove' === $action ? 'remove' : 'filter';
        $count    = max( 0, (int) $count );
        $language = self::archive_filter_language( $setting );

        if ( 'cs' === $language ) {
            if ( 1 === $count ) {
                $result_word = 'výsledek';
            } elseif ( $count >= 2 && $count <= 4 ) {
                $result_word = 'výsledky';
            } else {
                $result_word = 'výsledků';
            }

            $format = 'remove' === $action
                ? 'Odebrat filtr %1$s, %2$d %3$s'
                : 'Použít filtr %1$s, %2$d %3$s';
            $text = sprintf( $format, $label, $count, $result_word );
        } elseif ( 'en' === $language ) {
            if ( 'remove' === $action ) {
                $format = 1 === $count ? 'Remove filter %1$s, %2$d result' : 'Remove filter %1$s, %2$d results';
            } else {
                $format = 1 === $count ? 'Filter by %1$s, %2$d result' : 'Filter by %1$s, %2$d results';
            }

            $text = sprintf( $format, $label, $count );
        } else {
            if ( 'remove' === $action ) {
                /* translators: 1: chip label, 2: matching result count. */
                $format = _n( 'Remove filter %1$s, %2$d result', 'Remove filter %1$s, %2$d results', $count, 'smart-search-chips' );
            } else {
                /* translators: 1: chip label, 2: matching result count. */
                $format = _n( 'Filter by %1$s, %2$d result', 'Filter by %1$s, %2$d results', $count, 'smart-search-chips' );
            }

            $text = sprintf( $format, $label, $count );
        }

        return apply_filters( 'sschp_archive_filter_count_label', $text, $action, $label, $count, $language );
    }

    /**
     * Return source or normally translated archive interface text.
     *
     * @param string $key       String identifier.
     * @param bool   $translate Whether to use the active WordPress locale.
     * @return string
     */
    private static function translated_archive_filter_text( $key, $translate ) {
        switch ( $key ) {
            case 'filter_results':
                return $translate ? __( 'Filter These Results', 'smart-search-chips' ) : 'Filter These Results';
            case 'refine_search':
                return $translate ? __( 'Refine This Search', 'smart-search-chips' ) : 'Refine This Search';
            case 'clear_filters':
                return $translate ? __( 'Clear filters', 'smart-search-chips' ) : 'Clear filters';
            case 'category_description':
                return $translate ? __( 'These chips compare and filter all products in this category.', 'smart-search-chips' ) : 'These chips compare and filter all products in this category.';
            case 'current_page_description':
                return $translate ? __( 'These chips refine the results currently loaded on this page. Changing filters resets pagination.', 'smart-search-chips' ) : 'These chips refine the results currently loaded on this page. Changing filters resets pagination.';
            default:
                return '';
        }
    }

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
     * @param array $request Sanitized request values.
     * @return string
     */
    public static function request_language( $request = array() ) {
        if ( ! is_array( $request ) ) {
            $request = array();
        }

        $candidates = array(
            isset( $request['sschp_lang'] ) ? sanitize_key( wp_unslash( $request['sschp_lang'] ) ) : '',
            isset( $request['lang'] ) ? sanitize_key( wp_unslash( $request['lang'] ) ) : '',
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
