<?php
/**
 * Frontend - Enqueue scripts/styles and provide search form shortcode
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSC_Frontend {

    /** @var SSC_Settings */
    private $settings;

    public function __construct( SSC_Settings $settings ) {
        $this->settings = $settings;
    }

    /**
     * Initialize frontend hooks.
     */
    public function init() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_shortcode( 'ssc_search', array( $this, 'render_search_form' ) );
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'smart-search-chips',
            SSC_PLUGIN_URL . 'assets/css/chips.css',
            array(),
            SSC_VERSION
        );

        wp_enqueue_script(
            'smart-search-chips',
            SSC_PLUGIN_URL . 'assets/js/chips.js',
            array(),
            SSC_VERSION,
            true
        );

        // Pass settings to JS
        $ajax_action = $this->settings->get( 'override_ajax' );

        wp_localize_script( 'smart-search-chips', 'sscConfig', array(
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'action'     => ! empty( $ajax_action ) ? $ajax_action : 'ssc_search',
            'minChars'   => 2,
            'postType'   => implode( ',', (array) $this->settings->get( 'post_types', array( 'post' ) ) ),
            'nonce'      => wp_create_nonce( 'ssc_search_nonce' ),
            'analytics'  => array(
                'enabled' => SSC_Analytics::is_enabled(),
                'nonce'   => wp_create_nonce( 'ssc_analytics_nonce' ),
            ),
        ) );
    }

    /**
     * Render search form shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_search_form( $atts = array() ) {
        $atts = shortcode_atts( array(
            'placeholder' => __( 'Search...', 'smart-search-chips' ),
            'class'       => '',
        ), $atts );

        ob_start();
        ?>
        <div class="ssc-search <?php echo esc_attr( $atts['class'] ); ?>">
            <form class="ssc-search__form js-ssc-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <input type="search"
                       class="ssc-search__input"
                       name="s"
                       placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
                       autocomplete="off"
                       required />
                <button type="submit" class="ssc-search__button">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                </button>
            </form>
            <div class="ssc-search__results js-ssc-results"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}
