<?php
/**
 * WooCommerce Integration - Auto-populate tags from product attributes
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSC_WooCommerce {

    /**
     * Initialize WooCommerce hooks.
     */
    public function init() {
        // Only load if WooCommerce is active and feature is enabled
        if ( ! class_exists( 'WooCommerce' ) || get_option( 'ssc_woo_enabled', '1' ) !== '1' ) {
            return;
        }

        // Auto-populate ssc_tags when a product is saved
        add_action( 'save_post_product', array( $this, 'auto_populate_tags' ), 20, 2 );

        // Add bulk action to generate tags for all products
        add_filter( 'bulk_actions-edit-product', array( $this, 'add_bulk_action' ) );
        add_filter( 'handle_bulk_actions-edit-product', array( $this, 'handle_bulk_action' ), 10, 3 );
        add_action( 'admin_notices', array( $this, 'bulk_action_notice' ) );
    }

    /**
     * Auto-populate ssc_tags from WooCommerce product attributes on save.
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
        $existing = get_post_meta( $post_id, SSC_Meta_Box::META_KEY, true );
        if ( ! empty( $existing ) ) {
            return;
        }

        $tags = $this->extract_product_tags( $post_id );

        if ( ! empty( $tags ) ) {
            update_post_meta( $post_id, SSC_Meta_Box::META_KEY, implode( '; ', $tags ) );
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

        return apply_filters( 'ssc_woocommerce_tags', $tags, $product_id, $product );
    }

    /**
     * Add bulk action to products list.
     *
     * @param array $actions Bulk actions.
     * @return array
     */
    public function add_bulk_action( $actions ) {
        $actions['ssc_generate_tags'] = __( 'Generate Search Chips Tags', 'smart-search-chips' );
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
        if ( $action !== 'ssc_generate_tags' ) {
            return $redirect_to;
        }

        $count = 0;
        foreach ( $post_ids as $post_id ) {
            $tags = $this->extract_product_tags( $post_id );
            if ( ! empty( $tags ) ) {
                update_post_meta( $post_id, SSC_Meta_Box::META_KEY, implode( '; ', $tags ) );
                $count++;
            }
        }

        return add_query_arg( 'ssc_tags_generated', $count, $redirect_to );
    }

    /**
     * Show admin notice after bulk action.
     */
    public function bulk_action_notice() {
        if ( ! empty( $_REQUEST['ssc_tags_generated'] ) ) {
            $count = intval( $_REQUEST['ssc_tags_generated'] );
            printf(
                '<div class="notice notice-success is-dismissible"><p>' .
                _n(
                    'Search Chips tags generated for %d product.',
                    'Search Chips tags generated for %d products.',
                    $count,
                    'smart-search-chips'
                ) . '</p></div>',
                $count
            );
        }
    }
}
