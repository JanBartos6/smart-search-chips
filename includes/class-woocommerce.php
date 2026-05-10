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

    /**
     * Initialize WooCommerce hooks.
     */
    public function init() {
        // Only load if WooCommerce is active and feature is enabled
        if ( ! class_exists( 'WooCommerce' ) || get_option( 'sschp_woo_enabled', '1' ) !== '1' ) {
            return;
        }

        // Auto-populate sschp_tags when a product is saved
        add_action( 'save_post_product', array( $this, 'auto_populate_tags' ), 20, 2 );
        add_filter( 'sschp_post_tag_source', array( $this, 'add_runtime_product_tags' ), 10, 3 );

        // Add bulk action to generate tags for all products
        add_filter( 'bulk_actions-edit-product', array( $this, 'add_bulk_action' ) );
        add_filter( 'handle_bulk_actions-edit-product', array( $this, 'handle_bulk_action' ), 10, 3 );
        add_action( 'admin_notices', array( $this, 'bulk_action_notice' ) );
    }

    /**
     * Auto-populate sschp_tags from WooCommerce product attributes on save.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function auto_populate_tags( $post_id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Don't overwrite manually entered tags
        $existing = get_post_meta( $post_id, SSCHP_Meta_Box::META_KEY, true );
        if ( ! empty( $existing ) ) {
            return;
        }

        $tags = $this->extract_product_tags( $post_id );

        if ( ! empty( $tags ) ) {
            update_post_meta( $post_id, SSCHP_Meta_Box::META_KEY, implode( '; ', $tags ) );
        }
    }

    /**
     * Extract tags from a WooCommerce product.
     *
     * @param int $product_id Product ID.
     * @return array Tags.
     */
    public function extract_product_tags( $product_id ) {
        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            return array();
        }

        $tags = array();

        // Product attributes (Color, Size, Material, etc.)
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

        // Product categories
        $categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
        if ( ! is_wp_error( $categories ) ) {
            $tags = array_merge( $tags, $categories );
        }

        // Product tags (WooCommerce tags)
        $woo_tags = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'names' ) );
        if ( ! is_wp_error( $woo_tags ) ) {
            $tags = array_merge( $tags, $woo_tags );
        }

        // SKU
        $sku = $product->get_sku();
        if ( $sku ) {
            $tags[] = $sku;
        }

        // Price range indicator
        if ( $product->is_type( 'variable' ) ) {
            $min = $product->get_variation_price( 'min' );
            $max = $product->get_variation_price( 'max' );
            if ( $min && $max && $min !== $max ) {
                $tags[] = get_woocommerce_currency_symbol() . $min . ' - ' . get_woocommerce_currency_symbol() . $max;
            }
        }

        // Deduplicate
        $tags = array_unique( array_filter( array_map( 'trim', $tags ) ) );

        return apply_filters( 'sschp_woocommerce_tags', $tags, $product_id, $product );
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

        if ( function_exists( 'get_post_type' ) && 'product' !== get_post_type( $post_id ) ) {
            return $source;
        }

        $tags = $this->extract_product_tags( $post_id );
        if ( empty( $tags ) ) {
            return $source;
        }

        $tag_string = implode( '; ', $tags );
        $primary    = isset( $source['primary'] ) ? trim( (string) $source['primary'] ) : '';
        $extra      = isset( $source['extra'] ) ? trim( (string) $source['extra'] ) : '';

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

        if ( ! current_user_can( 'edit_posts' ) ) {
            return $redirect_to;
        }

        $count = 0;
        foreach ( $post_ids as $post_id ) {
            $tags = $this->extract_product_tags( $post_id );
            if ( ! empty( $tags ) ) {
                update_post_meta( $post_id, SSCHP_Meta_Box::META_KEY, implode( '; ', $tags ) );
                $count++;
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
