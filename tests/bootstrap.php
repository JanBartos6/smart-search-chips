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
$sschp_test_query_vars      = array();
$sschp_test_post_meta       = array();
$sschp_test_current_url     = '/';
$sschp_test_is_admin        = false;
$sschp_test_is_search       = false;
$sschp_test_products        = array();
$sschp_test_post_types      = array();
$sschp_test_post_terms      = array();
$sschp_test_post_revisions  = array();
$sschp_test_post_autosaves  = array();
$sschp_test_acf_fields      = array();
$sschp_test_posts           = array();
$sschp_test_index_rows      = array();
$sschp_test_db_queries      = array();
$sschp_test_db_delta_sql    = array();
$sschp_test_scheduled_events = array();

if ( ! class_exists( 'SSCHP_Test_WPDB' ) ) {
    class SSCHP_Test_WPDB {
        public $prefix = 'wp_';
        public $posts = 'wp_posts';

        public function get_charset_collate() {
            return 'DEFAULT CHARSET=utf8mb4';
        }

        public function prepare( $query ) {
            $args = array_slice( func_get_args(), 1 );
            if ( 1 === count( $args ) && is_array( $args[0] ) ) {
                $args = $args[0];
            }

            foreach ( $args as $arg ) {
                $query = preg_replace( '/%[sd]/', $this->quote_prepare_arg( $arg ), $query, 1 );
            }

            return $query;
        }

        public function esc_like( $text ) {
            return addcslashes( (string) $text, '_%\\' );
        }

        public function query( $sql ) {
            global $sschp_test_db_queries, $sschp_test_index_rows;

            $sschp_test_db_queries[] = (string) $sql;

            if ( false !== stripos( (string) $sql, 'TRUNCATE TABLE' ) || false !== stripos( (string) $sql, 'DROP TABLE IF EXISTS' ) ) {
                $sschp_test_index_rows = array();
            }

            return true;
        }

        public function replace( $table, $data, $format = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
            global $sschp_test_index_rows;

            $key = (int) $data['object_id'] . '|' . (string) $data['language_code'];
            $sschp_test_index_rows[ $key ] = (object) $data;

            return 1;
        }

        public function delete( $table, $where, $where_format = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
            global $sschp_test_index_rows;

            if ( isset( $where['object_id'] ) ) {
                foreach ( array_keys( $sschp_test_index_rows ) as $key ) {
                    if ( 0 === strpos( $key, (int) $where['object_id'] . '|' ) ) {
                        unset( $sschp_test_index_rows[ $key ] );
                    }
                }
            }

            return 1;
        }

        public function get_var( $sql ) {
            global $sschp_test_index_rows, $sschp_test_posts;

            $sql = (string) $sql;
            if ( false !== stripos( $sql, 'sschp_search_index' ) && false !== stripos( $sql, 'COUNT' ) ) {
                return count( $sschp_test_index_rows );
            }

            if ( false !== stripos( $sql, 'wp_posts' ) && false !== stripos( $sql, 'COUNT' ) ) {
                return count( $this->filter_posts_from_sql( $sql, $sschp_test_posts ) );
            }

            return 0;
        }

        public function get_col( $sql ) {
            global $sschp_test_posts;

            $sql    = (string) $sql;
            $cursor = 0;
            $limit  = 25;

            if ( preg_match( '/ID\s*>\s*(\d+)/i', $sql, $match ) ) {
                $cursor = (int) $match[1];
            }

            if ( preg_match( '/LIMIT\s+(\d+)/i', $sql, $match ) ) {
                $limit = (int) $match[1];
            }

            $posts = $this->filter_posts_from_sql( $sql, $sschp_test_posts );
            $ids   = array();

            foreach ( $posts as $post ) {
                if ( (int) $post->ID > $cursor ) {
                    $ids[] = (int) $post->ID;
                }
            }

            sort( $ids );

            return array_slice( $ids, 0, $limit );
        }

        public function get_results( $sql ) {
            global $sschp_test_index_rows;

            $sql        = (string) $sql;
            $post_types = $this->extract_post_types_from_sql( $sql );
            $rows       = array_values( $sschp_test_index_rows );

            if ( ! empty( $post_types ) ) {
                $rows = array_values(
                    array_filter(
                        $rows,
                        function( $row ) use ( $post_types ) {
                            return in_array( $row->post_type, $post_types, true );
                        }
                    )
                );
            }

            return $rows;
        }

        private function quote_prepare_arg( $arg ) {
            if ( is_int( $arg ) || is_float( $arg ) || ( is_string( $arg ) && preg_match( '/^-?\d+$/', $arg ) ) ) {
                return (string) $arg;
            }

            return "'" . str_replace( "'", "''", (string) $arg ) . "'";
        }

        private function filter_posts_from_sql( $sql, $posts ) {
            $post_types = $this->extract_post_types_from_sql( $sql );
            $filtered   = array();

            foreach ( $posts as $post ) {
                if ( isset( $post->post_status ) && 'publish' !== $post->post_status ) {
                    continue;
                }

                if ( ! empty( $post_types ) && ! in_array( $post->post_type, $post_types, true ) ) {
                    continue;
                }

                $filtered[] = $post;
            }

            return $filtered;
        }

        private function extract_post_types_from_sql( $sql ) {
            if ( ! preg_match( '/post_type\s+IN\s*\(([^)]+)\)/i', (string) $sql, $match ) ) {
                return array();
            }

            $parts = array_map( 'trim', explode( ',', $match[1] ) );

            return array_values(
                array_filter(
                    array_map(
                        function( $part ) {
                            return trim( $part, "'\" " );
                        },
                        $parts
                    )
                )
            );
        }
    }
}

$wpdb = new SSCHP_Test_WPDB();

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return $text;
    }
}

if ( ! function_exists( '_n' ) ) {
    function _n( $single, $plural, $number, $domain = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return 1 === (int) $number ? $single : $plural;
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

if ( ! function_exists( 'map_deep' ) ) {
    function map_deep( $value, $callback ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $key => $item ) {
                $value[ $key ] = map_deep( $item, $callback );
            }

            return $value;
        }

        return call_user_func( $callback, $value );
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        return $value;
    }
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
    function wp_strip_all_tags( $text ) {
        return strip_tags( (string) $text );
    }
}

if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( $url ) {
        return filter_var( (string) $url, FILTER_SANITIZE_URL );
    }
}

if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $url ) {
        return esc_url_raw( $url );
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

if ( ! function_exists( 'wp_kses' ) ) {
    function wp_kses( $content, $allowed_html = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return $content;
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

if ( ! function_exists( 'add_option' ) ) {
    function add_option( $name, $value = '', $deprecated = '', $autoload = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        global $sschp_test_options;

        if ( array_key_exists( $name, $sschp_test_options ) ) {
            return false;
        }

        $sschp_test_options[ $name ] = $value;
        return true;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( $name, $value, $autoload = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        global $sschp_test_options;

        $old_value = array_key_exists( $name, $sschp_test_options ) ? $sschp_test_options[ $name ] : false;
        $sschp_test_options[ $name ] = $value;

        return $old_value !== $value;
    }
}

if ( ! function_exists( 'delete_option' ) ) {
    function delete_option( $name ) {
        global $sschp_test_options;

        unset( $sschp_test_options[ $name ] );
        return true;
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

if ( ! function_exists( 'shortcode_atts' ) ) {
    function shortcode_atts( $pairs, $atts, $shortcode = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return array_merge( (array) $pairs, (array) $atts );
    }
}

if ( ! function_exists( 'shortcode_exists' ) ) {
    function shortcode_exists( $tag ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return 'sschp_search' === $tag;
    }
}

if ( ! function_exists( 'is_admin' ) ) {
    function is_admin() {
        global $sschp_test_is_admin;

        return (bool) $sschp_test_is_admin;
    }
}

if ( ! function_exists( 'is_search' ) ) {
    function is_search() {
        global $sschp_test_is_search;

        return (bool) $sschp_test_is_search;
    }
}

if ( ! function_exists( 'get_query_var' ) ) {
    function get_query_var( $query_var, $default = '' ) {
        global $sschp_test_query_vars;

        return array_key_exists( $query_var, $sschp_test_query_vars ) ? $sschp_test_query_vars[ $query_var ] : $default;
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
        global $sschp_test_posts;

        if ( is_numeric( $post ) && isset( $sschp_test_posts[ (int) $post ] ) ) {
            return $sschp_test_posts[ (int) $post ]->post_title;
        }

        if ( is_object( $post ) && isset( $post->post_title ) ) {
            return $post->post_title;
        }

        return '';
    }
}

if ( ! function_exists( 'get_post' ) ) {
    function get_post( $post_id ) {
        global $sschp_test_posts;

        return isset( $sschp_test_posts[ (int) $post_id ] ) ? $sschp_test_posts[ (int) $post_id ] : null;
    }
}

if ( ! function_exists( 'get_permalink' ) ) {
    function get_permalink( $post_id ) {
        return 'http://example.test/?p=' . (int) $post_id;
    }
}

if ( ! function_exists( 'get_the_post_thumbnail' ) ) {
    function get_the_post_thumbnail( $post_id, $size = 'thumbnail', $attr = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return '';
    }
}

if ( ! function_exists( 'get_intermediate_image_sizes' ) ) {
    function get_intermediate_image_sizes() {
        return array( 'thumbnail', 'medium', 'large' );
    }
}

if ( ! function_exists( 'locate_template' ) ) {
    function locate_template( $template_names ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return '';
    }
}

if ( ! function_exists( 'wp_reset_postdata' ) ) {
    function wp_reset_postdata() {
        unset( $GLOBALS['post'] );
    }
}

if ( ! function_exists( 'current_filter' ) ) {
    function current_filter() {
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

if ( ! function_exists( 'get_post_type' ) ) {
    function get_post_type( $post_id ) {
        global $sschp_test_post_types, $sschp_test_posts;

        if ( isset( $sschp_test_posts[ (int) $post_id ] ) && isset( $sschp_test_posts[ (int) $post_id ]->post_type ) ) {
            return $sschp_test_posts[ (int) $post_id ]->post_type;
        }

        return isset( $sschp_test_post_types[ $post_id ] ) ? $sschp_test_post_types[ $post_id ] : 'post';
    }
}

if ( ! function_exists( 'get_object_taxonomies' ) ) {
    function get_object_taxonomies( $object_type, $output = 'names' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        if ( 'product' === $object_type ) {
            return array( 'product_cat', 'product_tag', 'pa_material', 'pa_power' );
        }

        return array( 'category', 'post_tag' );
    }
}

if ( ! function_exists( 'register_post_meta' ) ) {
    function register_post_meta( $post_type, $meta_key, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return true;
    }
}

if ( ! function_exists( 'get_post_meta' ) ) {
    function get_post_meta( $post_id, $key = '', $single = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        global $sschp_test_post_meta;

        if ( '' === $key ) {
            return isset( $sschp_test_post_meta[ $post_id ] ) ? $sschp_test_post_meta[ $post_id ] : array();
        }

        if ( isset( $sschp_test_post_meta[ $post_id ] ) && array_key_exists( $key, $sschp_test_post_meta[ $post_id ] ) ) {
            return $sschp_test_post_meta[ $post_id ][ $key ];
        }

        return '';
    }
}

if ( ! function_exists( 'get_field' ) ) {
    function get_field( $field_name, $post_id = false ) {
        global $sschp_test_acf_fields;

        return isset( $sschp_test_acf_fields[ $post_id ] ) && array_key_exists( $field_name, $sschp_test_acf_fields[ $post_id ] )
            ? $sschp_test_acf_fields[ $post_id ][ $field_name ]
            : null;
    }
}

if ( ! function_exists( 'update_post_meta' ) ) {
    function update_post_meta( $post_id, $meta_key, $meta_value ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        global $sschp_test_post_meta;

        $sschp_test_post_meta[ $post_id ][ $meta_key ] = $meta_value;
        return true;
    }
}

if ( ! function_exists( 'delete_post_meta' ) ) {
    function delete_post_meta( $post_id, $meta_key, $meta_value = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        global $sschp_test_post_meta;

        unset( $sschp_test_post_meta[ $post_id ][ $meta_key ] );
        return true;
    }
}

if ( ! function_exists( 'wp_is_post_revision' ) ) {
    function wp_is_post_revision( $post_id ) {
        global $sschp_test_post_revisions;

        return ! empty( $sschp_test_post_revisions[ $post_id ] );
    }
}

if ( ! function_exists( 'wp_is_post_autosave' ) ) {
    function wp_is_post_autosave( $post_id ) {
        global $sschp_test_post_autosaves;

        return ! empty( $sschp_test_post_autosaves[ $post_id ] );
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) {
        return $thing instanceof WP_Error;
    }
}

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {}
}

if ( ! function_exists( 'wc_get_product' ) ) {
    function wc_get_product( $product_id ) {
        global $sschp_test_products;

        return isset( $sschp_test_products[ $product_id ] ) ? $sschp_test_products[ $product_id ] : null;
    }
}

if ( ! function_exists( 'wp_get_post_terms' ) ) {
    function wp_get_post_terms( $post_id, $taxonomy, $args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        global $sschp_test_post_terms;

        $key = (int) $post_id . '|' . (string) $taxonomy;
        return isset( $sschp_test_post_terms[ $key ] ) ? $sschp_test_post_terms[ $key ] : array();
    }
}

if ( ! function_exists( 'get_woocommerce_currency_symbol' ) ) {
    function get_woocommerce_currency_symbol() {
        return '$';
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

if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type, $gmt = 0 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return gmdate( 'Y-m-d H:i:s' );
    }
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
    function wp_next_scheduled( $hook ) {
        global $sschp_test_scheduled_events;

        return in_array( $hook, $sschp_test_scheduled_events, true ) ? time() + 30 : false;
    }
}

if ( ! function_exists( 'wp_schedule_single_event' ) ) {
    function wp_schedule_single_event( $timestamp, $hook, $args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        global $sschp_test_scheduled_events;

        $sschp_test_scheduled_events[] = $hook;
        return true;
    }
}

if ( ! function_exists( 'dbDelta' ) ) {
    function dbDelta( $sql ) {
        global $sschp_test_db_delta_sql;

        $sschp_test_db_delta_sql[] = (string) $sql;
        return array();
    }
}

if ( ! function_exists( 'wp_safe_redirect' ) ) {
    function wp_safe_redirect( $url ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        return true;
    }
}

if ( ! function_exists( 'add_query_arg' ) ) {
    function add_query_arg( $args, $url = '' ) {
        global $sschp_test_current_url;

        $function_args = func_get_args();

        if ( is_array( $args ) ) {
            $params = $args;
            $url    = isset( $function_args[1] ) ? (string) $function_args[1] : $sschp_test_current_url;
        } else {
            $params = array(
                (string) $args => isset( $function_args[1] ) ? $function_args[1] : '',
            );
            $url = isset( $function_args[2] ) ? (string) $function_args[2] : $sschp_test_current_url;
        }

        if ( '' === $url ) {
            $url = '/';
        }

        $parts = parse_url( $url );
        $query = array();

        if ( ! empty( $parts['query'] ) ) {
            parse_str( $parts['query'], $query );
        }

        foreach ( $params as $key => $value ) {
            $query[ $key ] = $value;
        }

        $path = isset( $parts['path'] ) ? $parts['path'] : $url;
        if ( isset( $parts['scheme'], $parts['host'] ) ) {
            $path = $parts['scheme'] . '://' . $parts['host'] . $path;
        }

        return $path . ( empty( $query ) ? '' : '?' . http_build_query( $query ) );
    }
}

if ( ! function_exists( 'wp_list_pluck' ) ) {
    function wp_list_pluck( $input_list, $field ) {
        $values = array();

        foreach ( (array) $input_list as $item ) {
            if ( is_array( $item ) && array_key_exists( $field, $item ) ) {
                $values[] = $item[ $field ];
            } elseif ( is_object( $item ) && isset( $item->{$field} ) ) {
                $values[] = $item->{$field};
            }
        }

        return $values;
    }
}

if ( ! function_exists( 'remove_query_arg' ) ) {
    function remove_query_arg( $key, $url = '' ) {
        if ( '' === $url ) {
            $url = '/';
        }

        $keys  = (array) $key;
        $parts = parse_url( (string) $url );
        $query = array();

        if ( ! empty( $parts['query'] ) ) {
            parse_str( $parts['query'], $query );
        }

        foreach ( $keys as $query_key ) {
            unset( $query[ $query_key ] );
        }

        $path = isset( $parts['path'] ) ? $parts['path'] : (string) $url;
        if ( isset( $parts['scheme'], $parts['host'] ) ) {
            $path = $parts['scheme'] . '://' . $parts['host'] . $path;
        }

        return $path . ( empty( $query ) ? '' : '?' . http_build_query( $query ) );
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

if ( ! class_exists( 'WP_Query' ) ) {
    class WP_Query {
        public $posts = array();
        public $post_count = 0;
        public $found_posts = 0;
        public $max_num_pages = 0;

        private $flags = array();
        private $position = -1;

        public function __construct( $args = array() ) {
            global $sschp_test_posts;

            if ( isset( $args['posts'] ) ) {
                $this->posts = (array) $args['posts'];
            } elseif ( isset( $args['s'] ) ) {
                $query      = mb_strtolower( (string) $args['s'] );
                $post_types = isset( $args['post_type'] ) ? (array) $args['post_type'] : array( 'post' );
                $limit      = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : 10;
                $this->posts = array_values(
                    array_filter(
                        $sschp_test_posts,
                        function( $post ) use ( $query, $post_types ) {
                            if ( isset( $post->post_status ) && 'publish' !== $post->post_status ) {
                                return false;
                            }

                            if ( ! empty( $post_types ) && ! in_array( $post->post_type, $post_types, true ) ) {
                                return false;
                            }

                            $haystack = mb_strtolower( $post->post_title . ' ' . ( isset( $post->post_content ) ? $post->post_content : '' ) );
                            return '' === $query || false !== strpos( $haystack, $query );
                        }
                    )
                );

                $this->posts = array_slice( $this->posts, 0, $limit );
            } else {
                $this->posts = array();
            }

            $this->post_count    = count( $this->posts );
            $this->found_posts   = isset( $args['found_posts'] ) ? (int) $args['found_posts'] : $this->post_count;
            $this->max_num_pages = isset( $args['max_num_pages'] ) ? (int) $args['max_num_pages'] : 1;
            $this->flags         = (array) $args;
        }

        public function have_posts() {
            return ( $this->position + 1 ) < $this->post_count;
        }

        public function the_post() {
            $this->position++;
            if ( isset( $this->posts[ $this->position ] ) ) {
                $GLOBALS['post'] = $this->posts[ $this->position ];
            }
        }

        public function is_main_query() {
            return ! array_key_exists( 'is_main_query', $this->flags ) || (bool) $this->flags['is_main_query'];
        }

        public function is_feed() {
            return ! empty( $this->flags['is_feed'] );
        }

        public function is_singular() {
            return ! empty( $this->flags['is_singular'] );
        }

        public function is_search() {
            return ! empty( $this->flags['is_search'] );
        }

        public function is_post_type_archive() {
            return ! empty( $this->flags['is_post_type_archive'] );
        }

        public function is_tax() {
            return ! empty( $this->flags['is_tax'] );
        }

        public function is_category() {
            return ! empty( $this->flags['is_category'] );
        }

        public function is_tag() {
            return ! empty( $this->flags['is_tag'] );
        }
    }
}

require_once dirname( __DIR__ ) . '/includes/class-license.php';
require_once dirname( __DIR__ ) . '/includes/class-meta-box.php';
require_once dirname( __DIR__ ) . '/includes/class-woocommerce.php';
require_once dirname( __DIR__ ) . '/includes/class-language.php';
require_once dirname( __DIR__ ) . '/includes/class-field-normalizer.php';
require_once dirname( __DIR__ ) . '/includes/class-chip-engine.php';
require_once dirname( __DIR__ ) . '/includes/class-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-frontend.php';
require_once dirname( __DIR__ ) . '/includes/class-search-index.php';
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

class SSCHP_Test_Product_Attribute {
    private $name;
    private $options;
    private $taxonomy;

    public function __construct( $name, $options = array(), $taxonomy = false ) {
        $this->name     = $name;
        $this->options  = $options;
        $this->taxonomy = (bool) $taxonomy;
    }

    public function is_taxonomy() {
        return $this->taxonomy;
    }

    public function get_name() {
        return $this->name;
    }

    public function get_options() {
        return $this->options;
    }
}

class SSCHP_Test_Product {
    private $attributes;
    private $sku;
    private $type;
    private $variation_prices;

    public function __construct( $attributes = array(), $sku = '', $type = 'simple', $variation_prices = array() ) {
        $this->attributes       = $attributes;
        $this->sku              = $sku;
        $this->type             = $type;
        $this->variation_prices = $variation_prices;
    }

    public function get_attributes() {
        return $this->attributes;
    }

    public function get_sku() {
        return $this->sku;
    }

    public function is_type( $type ) {
        return $this->type === $type;
    }

    public function get_variation_price( $type ) {
        return isset( $this->variation_prices[ $type ] ) ? $this->variation_prices[ $type ] : '';
    }
}
