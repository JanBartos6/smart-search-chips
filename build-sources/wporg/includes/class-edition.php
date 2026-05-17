<?php
/**
 * WordPress.org edition metadata.
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCHP_Edition {

    /**
     * @return string
     */
    public static function get_product_url() {
        return 'https://smartsearchchips.app/';
    }

    /**
     * @return string
     */
    public static function get_status_label() {
        return __( 'WordPress.org edition', 'smart-search-chips' );
    }

    /**
     * @return string
     */
    public static function get_status_description() {
        return __( 'All features included in this WordPress.org package are available without accounts or payment.', 'smart-search-chips' );
    }

    /**
     * @return int
     */
    public static function max_chips() {
        return 99;
    }
}