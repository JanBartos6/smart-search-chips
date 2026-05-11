<?php
/**
 * License - Free/Pro feature gating
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCHP_License {

    /** @var string Legacy manual license option keys kept for cleanup */
    const LEGACY_LICENSE_KEY_OPTION    = 'sschp_license_key';
    const LEGACY_LICENSE_STATUS_OPTION = 'sschp_license_status';

    /**
     * Return the Freemius instance when available.
     *
     * @return Freemius_Abstract|null
     */
    public static function freemius() {
        if ( ! function_exists( 'sschp_fs' ) ) {
            return null;
        }

        return sschp_fs();
    }

    /**
     * Check whether the SDK is available.
     *
     * @return bool
     */
    public static function is_sdk_ready() {
        return null !== self::freemius();
    }

    /**
     * Check if Pro features are active.
     *
     * @return bool
     */
    public static function is_pro() {
        return self::can_use_premium_code();
    }

    /**
     * Safely check whether premium-only code may run.
     *
     * Freemius strips or renames premium helpers depending on the build. Keeping
     * this in one wrapper prevents direct installs from fataling when the SDK or
     * premium helper method is unavailable.
     *
     * @return bool
     */
    public static function can_use_premium_code() {
        $freemius = self::freemius();

        if ( ! $freemius ) {
            return false;
        }

        if ( method_exists( $freemius, 'can_use_premium_code__premium_only' ) ) {
            return (bool) $freemius->can_use_premium_code__premium_only();
        }

        return method_exists( $freemius, 'can_use_premium_code' ) ? (bool) $freemius->can_use_premium_code() : false;
    }

    /**
     * Check if the current site is connected to Freemius.
     *
     * @return bool
     */
    public static function is_registered() {
        $freemius = self::freemius();

        return $freemius ? (bool) $freemius->is_registered() : false;
    }

    /**
     * Check if the current site is in trial.
     *
     * @return bool
     */
    public static function is_trial() {
        $freemius = self::freemius();

        return $freemius && method_exists( $freemius, 'is_trial' ) ? (bool) $freemius->is_trial() : false;
    }

    /**
     * Check if there is an upgrade path configured.
     *
     * @return bool
     */
    public static function has_paid_plan() {
        $freemius = self::freemius();

        return $freemius ? (bool) $freemius->has_paid_plan() : false;
    }

    /**
     * Return the Freemius upgrade URL when available.
     *
     * @return string
     */
    public static function get_upgrade_url() {
        $freemius = self::freemius();

        return $freemius && self::has_paid_plan() ? $freemius->get_upgrade_url() : '';
    }

    /**
     * Return the Freemius account URL when available.
     *
     * @return string
     */
    public static function get_account_url() {
        $freemius = self::freemius();

        return $freemius && self::is_registered() ? $freemius->get_account_url() : '';
    }

    /**
     * Return a short status label for the settings screen.
     *
     * @return string
     */
    public static function get_status_label() {
        if ( ! self::is_sdk_ready() ) {
            return __( 'Freemius SDK missing', 'smart-search-chips' );
        }

        if ( self::is_trial() ) {
            return __( 'Trial active', 'smart-search-chips' );
        }

        if ( self::is_pro() ) {
            return __( 'Pro active', 'smart-search-chips' );
        }

        if ( self::is_registered() ) {
            return __( 'Free connected', 'smart-search-chips' );
        }

        return __( 'Free', 'smart-search-chips' );
    }

    /**
     * Return descriptive text for the settings screen.
     *
     * @return string
     */
    public static function get_status_description() {
        if ( ! self::is_sdk_ready() ) {
            return __( 'The Freemius SDK was not found in vendor/freemius, so license activation and paid upgrades are currently unavailable.', 'smart-search-chips' );
        }

        if ( self::is_trial() ) {
            return __( 'This site is using a Pro trial through Freemius. All paid features are currently unlocked.', 'smart-search-chips' );
        }

        if ( self::is_pro() ) {
            return __( 'This site can use Pro features through Freemius, including custom field mapping, chip curation rules, synonym rules, analytics, and the AI prompt builder.', 'smart-search-chips' );
        }

        if ( self::is_registered() ) {
            return __( 'This site is connected to Freemius on the free tier. Upgrade from here when you want to unlock Pro features.', 'smart-search-chips' );
        }

        return __( 'This site is running the free tier. Connect or upgrade through Freemius to unlock Pro features.', 'smart-search-chips' );
    }

    /**
     * Get the maximum number of chips allowed.
     *
     * @return int
     */
    public static function max_chips() {
        return 99;
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
     * Check if Relevanssi integration is allowed (Pro only).
     *
     * @return bool
     */
    public static function can_use_relevanssi() {
        return true;
    }

    /**
     * Check if advanced design options are available (Pro only).
     *
     * @return bool
     */
    public static function can_use_advanced_design() {
        return true;
    }

    /**
     * Check if advanced AJAX override integration is available.
     *
     * @return bool
     */
    public static function can_use_ajax_override() {
        return true;
    }

    /**
     * Initialize license hooks.
     */
    public function init() {
        // Freemius manages activation, upgrades, and account state.
    }
}
