<?php
/**
 * License - Free/Pro feature gating
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSC_License {

    /** @var string Option key for license */
    const LICENSE_KEY_OPTION = 'ssc_license_key';
    const LICENSE_STATUS_OPTION = 'ssc_license_status';

    /**
     * Check if Pro features are active.
     *
     * @return bool
     */
    public static function is_pro() {
        $status = get_option( self::LICENSE_STATUS_OPTION, 'free' );
        return $status === 'valid';
    }

    /**
     * Get the maximum number of chips allowed.
     *
     * @return int
     */
    public static function max_chips() {
        return self::is_pro() ? 99 : 4;
    }

    /**
     * Check if custom field mapping is allowed (Pro only).
     *
     * @return bool
     */
    public static function can_use_custom_fields() {
        return self::is_pro();
    }

    /**
     * Check if AJAX override is allowed (Pro only).
     *
     * @return bool
     */
    public static function can_override_ajax() {
        return self::is_pro();
    }

    /**
     * Check if Relevanssi integration is allowed (Pro only).
     *
     * @return bool
     */
    public static function can_use_relevanssi() {
        return self::is_pro();
    }

    /**
     * Initialize license hooks.
     */
    public function init() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Register license settings.
     */
    public function register_settings() {
        add_settings_section( 'ssc_license', __( 'License', 'smart-search-chips' ), array( $this, 'render_license_section' ), 'smart-search-chips' );

        register_setting( 'smart-search-chips', self::LICENSE_KEY_OPTION, array(
            'sanitize_callback' => array( $this, 'validate_license' ),
        ) );

        add_settings_field(
            self::LICENSE_KEY_OPTION,
            __( 'License Key', 'smart-search-chips' ),
            array( $this, 'render_license_field' ),
            'smart-search-chips',
            'ssc_license'
        );
    }

    /**
     * Render license section description.
     */
    public function render_license_section() {
        if ( self::is_pro() ) {
            echo '<div class="notice notice-success inline"><p><strong>' . esc_html__( 'Pro license active.', 'smart-search-chips' ) . '</strong> ' . esc_html__( 'All features unlocked.', 'smart-search-chips' ) . '</p></div>';
        } else {
            echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Free version. Upgrade to Pro for custom field mapping, unlimited chips, Relevanssi integration, and AJAX override.', 'smart-search-chips' ) . '</p></div>';
        }
    }

    /**
     * Render license key field.
     */
    public function render_license_field() {
        $key    = get_option( self::LICENSE_KEY_OPTION, '' );
        $status = get_option( self::LICENSE_STATUS_OPTION, 'free' );

        printf(
            '<input type="text" name="%s" value="%s" class="regular-text" placeholder="%s" />',
            esc_attr( self::LICENSE_KEY_OPTION ),
            esc_attr( $key ),
            esc_attr__( 'Enter your Pro license key', 'smart-search-chips' )
        );

        if ( $key && $status === 'valid' ) {
            echo ' <span style="color:green;">&#10003; ' . esc_html__( 'Active', 'smart-search-chips' ) . '</span>';
        } elseif ( $key && $status !== 'valid' ) {
            echo ' <span style="color:red;">&#10007; ' . esc_html__( 'Invalid', 'smart-search-chips' ) . '</span>';
        }

        echo '<p class="description">' . esc_html__( 'Enter your license key to unlock Pro features.', 'smart-search-chips' ) . '</p>';
    }

    /**
     * Validate license key on save.
     *
     * @param string $key License key.
     * @return string Sanitized key.
     */
    public function validate_license( $key ) {
        $key = sanitize_text_field( $key );

        if ( empty( $key ) ) {
            update_option( self::LICENSE_STATUS_OPTION, 'free' );
            return $key;
        }

        // TODO: Replace with actual license server validation
        // For now, validate format: SSC-PRO-XXXX-XXXX-XXXX
        if ( preg_match( '/^SSC-PRO-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $key ) ) {
            update_option( self::LICENSE_STATUS_OPTION, 'valid' );
        } else {
            update_option( self::LICENSE_STATUS_OPTION, 'invalid' );
        }

        return $key;
    }
}
