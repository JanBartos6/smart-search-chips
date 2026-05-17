<?php
/**
 * WooCommerce Integration - Auto-populate tags from product attributes
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCHP_WooCommerce {

    /** Hidden post meta used for generated WooCommerce helper tags. */
    const GENERATED_META_KEY = '_sschp_generated_tags';

    /**
     * Initialize WooCommerce hooks.
     */
    public function init() {
        // Only load if WooCommerce is active and feature is enabled
        if ( ! class_exists( 'WooCommerce' ) || get_option( 'sschp_woo_enabled', '1' ) !== '1' ) {
            return;
        }

        // Auto-populate hidden generated tags when a product is saved.
        add_action( 'save_post_product', array( $this, 'auto_populate_tags' ), 20, 2 );
        add_filter( 'sschp_post_tag_source', array( $this, 'add_runtime_product_tags' ), 10, 3 );

        // Add bulk action to generate tags for all products
        add_filter( 'bulk_actions-edit-product', array( $this, 'add_bulk_action' ) );
        add_filter( 'handle_bulk_actions-edit-product', array( $this, 'handle_bulk_action' ), 10, 3 );
        add_action( 'admin_notices', array( $this, 'bulk_action_notice' ) );
    }

    /**
     * Auto-populate generated WooCommerce tags from product attributes on save.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function auto_populate_tags( $post_id, $post ) {
        $post_id = absint( $post_id );
        if ( $post_id <= 0 ) {
            return;
        }

        if ( $this->should_skip_save( $post_id, $post ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $tags = $this->extract_product_tags( $post_id );
        $this->store_generated_tags( $post_id, $tags );
    }

    /**
     * Decide whether save-time generation should be skipped.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     * @return bool
     */
    private function should_skip_save( $post_id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return true;
        }

        if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
            return true;
        }

        if ( function_exists( 'wp_is_post_autosave' ) && wp_is_post_autosave( $post_id ) ) {
            return true;
        }

        if ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post_id ) ) {
            return true;
        }

        if ( is_object( $post ) && isset( $post->post_type ) && 'product' !== $post->post_type ) {
            return true;
        }

        if ( is_object( $post ) && isset( $post->post_status ) && in_array( $post->post_status, array( 'auto-draft', 'inherit' ), true ) ) {
            return true;
        }

        return false;
    }

    /**
     * Extract tags from a WooCommerce product.
     *
     * @param int $product_id Product ID.
     * @return array Tags.
     */
    public function extract_product_tags( $product_id ) {
        $product_id = absint( $product_id );
        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return array();
        }

        $tags = array();

        // Product attributes (Color, Size, Material, etc.).
        $attributes = $product->get_attributes();
        foreach ( $attributes as $attr ) {
            if ( $attr->is_taxonomy() ) {
                $terms = wp_get_post_terms( $product_id, $attr->get_name(), array( 'fields' => 'names' ) );
                if ( ! is_wp_error( $terms ) ) {
                    $tags = array_merge( $tags, $terms );
                }
            } else {
                $options = $attr->get_options();
                $tags = array_merge( $tags, $options );
            }
        }

        // Product categories.
        $categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
        if ( ! is_wp_error( $categories ) ) {
            $tags = array_merge( $tags, $categories );
        }

        // Product tags (WooCommerce tags).
        $woo_tags = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'names' ) );
        if ( ! is_wp_error( $woo_tags ) ) {
            $tags = array_merge( $tags, $woo_tags );
        }

        // SKU values are usually unique and noisy, so they are opt-in.
        $sku = method_exists( $product, 'get_sku' ) ? $product->get_sku() : '';
        if ( $sku && $this->should_include_sku( $product_id, $product ) ) {
            $tags[] = $sku;
        }

        // Price labels are disabled by default until represented by real range filters.
        if ( apply_filters( 'sschp_woocommerce_include_price_range_tag', false, $product_id, $product ) && $product->is_type( 'variable' ) ) {
            $min = $product->get_variation_price( 'min' );
            $max = $product->get_variation_price( 'max' );
            if ( $min && $max && $min !== $max ) {
                $tags[] = get_woocommerce_currency_symbol() . $min . ' - ' . get_woocommerce_currency_symbol() . $max;
            }
        }

        $tags = $this->normalize_tags( $tags );
        $tags = apply_filters( 'sschp_woocommerce_tags', $tags, $product_id, $product );

        return $this->normalize_tags( $tags );
    }

    /**
     * Add WooCommerce product-derived tags at read time.
     *
     * This keeps existing catalogs useful immediately, even before the stored
     * Search Chips Tags field has been generated through save/bulk actions.
     *
     * @param array $source  Current tag source values.
     * @param int   $post_id Post ID.
     * @param array $context Read context.
     * @return array
     */
    public function add_runtime_product_tags( $source, $post_id, $context = array() ) {
        if ( ! is_array( $source ) ) {
            $source = array();
        }

        $post_id = absint( $post_id );
        if ( $post_id <= 0 ) {
            return $source;
        }

        if ( function_exists( 'get_post_type' ) && 'product' !== get_post_type( $post_id ) ) {
            return $source;
        }

        $tags    = $this->get_generated_tags( $post_id );

        if ( empty( $tags ) ) {
            $tags = $this->extract_product_tags( $post_id );
        }

        if ( empty( $tags ) ) {
            return $source;
        }

        return $this->merge_tags_into_source( $source, $tags );
    }

    /**
     * Check whether SKU should be included as a generated tag.
     *
     * @param int       $product_id Product ID.
     * @param WC_Product $product   Product object.
     * @return bool
     */
    private function should_include_sku( $product_id, $product ) {
        $include = get_option( 'sschp_woo_include_sku', '0' ) === '1';

        return (bool) apply_filters( 'sschp_woocommerce_include_sku', $include, $product_id, $product );
    }

    /**
     * Store generated tags without overwriting manual tags.
     *
     * @param int   $post_id Post ID.
     * @param array $tags    Generated tags.
     * @return void
     */
    private function store_generated_tags( $post_id, $tags ) {
        $tags = $this->normalize_tags( $tags );

        if ( empty( $tags ) ) {
            delete_post_meta( $post_id, self::GENERATED_META_KEY );
            return;
        }

        update_post_meta( $post_id, self::GENERATED_META_KEY, implode( '; ', $tags ) );
    }

    /**
     * Read generated tags from hidden meta.
     *
     * @param int $post_id Post ID.
     * @return array
     */
    private function get_generated_tags( $post_id ) {
        return $this->parse_tag_string( get_post_meta( $post_id, self::GENERATED_META_KEY, true ) );
    }

    /**
     * Merge generated tags into the current runtime source without duplicates.
     *
     * @param array $source Current tag source.
     * @param array $tags   Generated tags.
     * @return array
     */
    private function merge_tags_into_source( $source, $tags ) {
        $primary = isset( $source['primary'] ) ? trim( (string) $source['primary'] ) : '';
        $extra   = isset( $source['extra'] ) ? trim( (string) $source['extra'] ) : '';

        $existing = array_merge(
            $this->parse_tag_string( $primary ),
            $this->parse_tag_string( $extra )
        );
        $existing_keys = array();
        foreach ( $existing as $tag ) {
            $existing_keys[ mb_strtolower( $tag ) ] = true;
        }

        $new_tags = array();
        foreach ( $this->normalize_tags( $tags ) as $tag ) {
            $key = mb_strtolower( $tag );
            if ( isset( $existing_keys[ $key ] ) ) {
                continue;
            }

            $existing_keys[ $key ] = true;
            $new_tags[]            = $tag;
        }

        if ( empty( $new_tags ) ) {
            $source['primary'] = $primary;
            $source['extra']   = $extra;
            return $source;
        }

        $tag_string = implode( '; ', $new_tags );

        if ( '' === $primary && '' === $extra ) {
            $source['primary'] = $tag_string;
            $source['extra']   = '';
            return $source;
        }

        $source['primary'] = $primary;
        $source['extra']   = '' === $extra ? $tag_string : $extra . '; ' . $tag_string;

        return $source;
    }

    /**
     * Parse a stored tag string.
     *
     * @param string $value Tag string.
     * @return array
     */
    private function parse_tag_string( $value ) {
        if ( '' === trim( (string) $value ) ) {
            return array();
        }

        return $this->normalize_tags( preg_split( '/[;,]/', (string) $value ) );
    }

    /**
     * Normalize and deduplicate tags case-insensitively.
     *
     * @param array $tags Raw tags.
     * @return array
     */
    private function normalize_tags( $tags ) {
        $normalized = array();
        $seen       = array();

        foreach ( (array) $tags as $tag ) {
            if ( is_array( $tag ) || is_object( $tag ) ) {
                continue;
            }

            $tag = sanitize_text_field( trim( (string) $tag ) );
            if ( '' === $tag ) {
                continue;
            }

            $key = mb_strtolower( $tag );
            if ( isset( $seen[ $key ] ) ) {
                continue;
            }

            $seen[ $key ] = true;
            $normalized[] = $tag;
        }

        return $normalized;
    }

    /**
     * Add bulk action to products list.
     *
     * @param array $actions Bulk actions.
     * @return array
     */
    public function add_bulk_action( $actions ) {
        $actions['sschp_generate_tags'] = __( 'Generate Search Chips Tags', 'smart-search-chips' );
        return $actions;
    }

    /**
     * Handle bulk tag generation.
     *
     * @param string $redirect_to Redirect URL.
     * @param string $action      Action name.
     * @param array  $post_ids    Selected post IDs.
     * @return string
     */
    public function handle_bulk_action( $redirect_to, $action, $post_ids ) {
        if ( $action !== 'sschp_generate_tags' ) {
            return $redirect_to;
        }

        check_admin_referer( 'bulk-posts' );

        $count = 0;
        $post_ids = array_unique( array_filter( array_map( 'absint', (array) $post_ids ) ) );

        foreach ( $post_ids as $post_id ) {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                continue;
            }

            $tags = $this->extract_product_tags( $post_id );
            if ( ! empty( $tags ) ) {
                $this->store_generated_tags( $post_id, $tags );
                $count++;
            } else {
                $this->store_generated_tags( $post_id, array() );
            }
        }

        set_transient( 'sschp_tags_generated_' . get_current_user_id(), (int) $count, MINUTE_IN_SECONDS );

        return $redirect_to;
    }

    /**
     * Show admin notice after bulk action.
     */
    public function bulk_action_notice() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        $transient_key = 'sschp_tags_generated_' . get_current_user_id();
        $count = absint( get_transient( $transient_key ) );

        if ( $count > 0 ) {
            delete_transient( $transient_key );
            printf(
                '<div class="notice notice-success is-dismissible"><p>' .
                /* translators: %d: number of products tagged */
                esc_html( _n(
                    'Search Chips tags generated for %d product.',
                    'Search Chips tags generated for %d products.',
                    $count,
                    'smart-search-chips'
                ) ) . '</p></div>',
                (int) $count
            );
        }
    }
}
