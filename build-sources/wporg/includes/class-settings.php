<?php
/**
 * Settings - Admin settings page for the WordPress.org edition.
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCHP_Settings {

    /** @var string Options prefix */
    private $prefix = 'sschp_';

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_post_sschp_delete_plugin_data_now', array( $this, 'handle_delete_data_now' ) );
        add_action( 'admin_post_sschp_dismiss_onboarding', array( $this, 'handle_dismiss_onboarding' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_filter( 'plugin_action_links_' . SSCHP_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
    }

    public function get( $key, $default = '' ) {
        $value = get_option( $this->prefix . $key, $default );
        return false !== $value ? $value : $default;
    }

    public function add_menu_page() {
        add_options_page(
            __( 'Smart Search Chips', 'smart-search-chips' ),
            __( 'Search Chips', 'smart-search-chips' ),
            'manage_options',
            'smart-search-chips',
            array( $this, 'render_settings_page' )
        );
    }

    public function add_settings_link( $links ) {
        $url  = admin_url( 'options-general.php?page=smart-search-chips' );
        $link = '<a href="' . esc_url( $url ) . '">' . __( 'Settings', 'smart-search-chips' ) . '</a>';
        array_unshift( $links, $link );
        $links[] = '<a href="' . esc_url( SSCHP_Edition::get_product_url() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View Pro Features', 'smart-search-chips' ) . '</a>';
        $links[] = '<a href="' . esc_url( SSCHP_Edition::get_product_url() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Upgrade to Pro', 'smart-search-chips' ) . '</a>';
        return $links;
    }

    public function register_settings() {
        register_setting( 'smart-search-chips', 'sschp_post_types', array( 'sanitize_callback' => array( $this, 'sanitize_post_types' ) ) );
        register_setting( 'smart-search-chips', 'sschp_override_ajax', array( 'sanitize_callback' => 'sanitize_key' ) );
        register_setting( 'smart-search-chips', 'sschp_ajax_keyword_field', array( 'sanitize_callback' => 'sanitize_key' ) );
        register_setting( 'smart-search-chips', 'sschp_result_container_selector', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'smart-search-chips', 'sschp_search_input_selector', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'smart-search-chips', 'sschp_max_chips', array( 'sanitize_callback' => array( $this, 'sanitize_max_chips' ) ) );
        register_setting( 'smart-search-chips', 'sschp_max_tag_length', array( 'sanitize_callback' => array( $this, 'sanitize_max_tag_length' ) ) );
        register_setting( 'smart-search-chips', 'sschp_woo_enabled', array( 'sanitize_callback' => array( $this, 'sanitize_checkbox' ) ) );
        register_setting( 'smart-search-chips', 'sschp_woo_include_sku', array( 'sanitize_callback' => array( $this, 'sanitize_checkbox' ) ) );
        register_setting( 'smart-search-chips', 'sschp_chip_color', array( 'sanitize_callback' => array( $this, 'sanitize_hex_color_setting' ) ) );
        register_setting( 'smart-search-chips', 'sschp_chip_text_color', array( 'sanitize_callback' => array( $this, 'sanitize_hex_color_setting' ) ) );
        register_setting( 'smart-search-chips', 'sschp_chip_inactive_color', array( 'sanitize_callback' => array( $this, 'sanitize_hex_color_setting' ) ) );
        register_setting( 'smart-search-chips', 'sschp_chip_inactive_text_color', array( 'sanitize_callback' => array( $this, 'sanitize_hex_color_setting' ) ) );
        register_setting( 'smart-search-chips', 'sschp_max_results', array( 'sanitize_callback' => array( $this, 'sanitize_max_results' ) ) );
        register_setting( 'smart-search-chips', 'sschp_search_results_count', array( 'sanitize_callback' => array( $this, 'sanitize_search_results_count' ) ) );
        register_setting( 'smart-search-chips', 'sschp_relevance_threshold', array( 'sanitize_callback' => array( $this, 'sanitize_relevance_threshold' ) ) );
        register_setting( 'smart-search-chips', 'sschp_chip_radius', array( 'sanitize_callback' => array( $this, 'sanitize_chip_radius' ) ) );
        register_setting( 'smart-search-chips', 'sschp_chip_font_size', array( 'sanitize_callback' => array( $this, 'sanitize_chip_font_size' ) ) );
        register_setting( 'smart-search-chips', 'sschp_chip_padding_x', array( 'sanitize_callback' => array( $this, 'sanitize_chip_padding_x' ) ) );
        register_setting( 'smart-search-chips', 'sschp_chip_padding_y', array( 'sanitize_callback' => array( $this, 'sanitize_chip_padding_y' ) ) );
        register_setting( 'smart-search-chips', 'sschp_delete_data_on_uninstall', array( 'sanitize_callback' => array( $this, 'sanitize_checkbox' ) ) );
        register_setting( 'smart-search-chips', 'sschp_result_image_fit', array( 'sanitize_callback' => array( $this, 'sanitize_result_image_fit' ) ) );
        register_setting( 'smart-search-chips', 'sschp_result_image_size', array( 'sanitize_callback' => array( $this, 'sanitize_result_image_size' ) ) );
        register_setting( 'smart-search-chips', 'sschp_result_image_display_size', array( 'sanitize_callback' => array( $this, 'sanitize_result_image_display_size' ) ) );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook_suffix Current admin page hook.
     */
    public function enqueue_admin_assets( $hook_suffix ) {
        if ( 'settings_page_smart-search-chips' !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style(
            'smart-search-chips-settings',
            SSCHP_PLUGIN_URL . 'assets/css/settings.css',
            array(),
            SSCHP_VERSION
        );

        wp_enqueue_script(
            'smart-search-chips-settings',
            SSCHP_PLUGIN_URL . 'assets/js/settings.js',
            array(),
            SSCHP_VERSION,
            true
        );
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $relevanssi_active    = function_exists( 'relevanssi_do_query' );
        $chip_color           = get_option( 'sschp_chip_color', '#e74c3c' );
        $chip_text            = get_option( 'sschp_chip_text_color', '#ffffff' );
        $chip_radius          = $this->get_numeric_option_value( 'sschp_chip_radius', 16, 0 );
        $chip_font            = $this->get_numeric_option_value( 'sschp_chip_font_size', 13, 8 );
        $chip_pad_x           = $this->get_numeric_option_value( 'sschp_chip_padding_x', 12, 0 );
        $chip_pad_y           = $this->get_numeric_option_value( 'sschp_chip_padding_y', 4, 0 );
        $delete_on_uninstall  = get_option( 'sschp_delete_data_on_uninstall', '0' );
        $data_deleted         = get_transient( 'sschp_data_deleted_' . get_current_user_id() );
        if ( $data_deleted ) {
            delete_transient( 'sschp_data_deleted_' . get_current_user_id() );
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <?php settings_errors( 'smart-search-chips' ); ?>

            <?php if ( '1' === $data_deleted ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Smart Search Chips data was deleted. Settings have been reset and built-in chip meta was removed.', 'smart-search-chips' ); ?></p></div>
            <?php endif; ?>

            <?php if ( ! $relevanssi_active ) : ?>
                <div class="notice notice-info"><p><strong><?php esc_html_e( 'Relevanssi not detected.', 'smart-search-chips' ); ?></strong> <?php esc_html_e( 'Install Relevanssi for relevance scoring. Smart Search Chips also works with default WordPress search.', 'smart-search-chips' ); ?></p></div>
            <?php endif; ?>

            <?php if ( ! get_user_meta( get_current_user_id(), 'sschp_onboarding_dismissed', true ) ) : ?>
                <div class="notice notice-info ssc-onboarding-panel">
                    <p><strong><?php esc_html_e( 'Set up Smart Search Chips', 'smart-search-chips' ); ?></strong></p>
                    <p><?php esc_html_e( 'Free setup: choose searchable post types, add Search Chips Tags to products or posts, then use the shortcode or archive chips to show filters.', 'smart-search-chips' ); ?></p>
                    <p class="description"><?php esc_html_e( 'Pro is a separate upgrade for ACF/custom field mapping, numeric slider chips, analytics, synonym rules, manual curation, AI-assisted tag workflows, and commercial support.', 'smart-search-chips' ); ?></p>
                    <p>
                        <a class="button button-secondary" href="<?php echo esc_url( SSCHP_Edition::get_product_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Pro Features', 'smart-search-chips' ); ?></a>
                        <a class="button-link" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sschp_dismiss_onboarding' ), 'sschp_dismiss_onboarding' ) ); ?>"><?php esc_html_e( 'Dismiss setup panel', 'smart-search-chips' ); ?></a>
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields( 'smart-search-chips' ); ?>

                <div id="ssc-settings-layout">
                    <div id="ssc-settings-left">

                        <div class="ssc-settings-card">
                            <h2><?php esc_html_e( 'WordPress.org Edition', 'smart-search-chips' ); ?></h2>
                            <div class="ssc-badge ssc-badge--free"><?php esc_html_e( 'Free', 'smart-search-chips' ); ?></div>
                            <p><strong><?php echo esc_html( SSCHP_Edition::get_status_label() ); ?></strong></p>
                            <p class="description"><?php echo esc_html( SSCHP_Edition::get_status_description() ); ?></p>
                        </div>

                        <div class="ssc-settings-card">
                            <h2><?php esc_html_e( 'Search', 'smart-search-chips' ); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e( 'Post Types', 'smart-search-chips' ); ?></th>
                                    <td><?php $this->render_post_types(); ?></td>
                                </tr>
                                <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                                <tr>
                                    <th><?php esc_html_e( 'WooCommerce Auto-Tags', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="hidden" name="sschp_woo_enabled" value="0" />
                                            <input type="checkbox" name="sschp_woo_enabled" value="1" <?php checked( get_option( 'sschp_woo_enabled', '1' ), '1' ); ?> />
                                            <?php esc_html_e( 'Generate helper tags from product attributes, categories, and WooCommerce product tags', 'smart-search-chips' ); ?>
                                        </label>
                                        <p class="description"><?php esc_html_e( 'Generated tags are stored separately from manual Search Chips Tags and are merged at runtime.', 'smart-search-chips' ); ?></p>
                                        <label>
                                            <input type="hidden" name="sschp_woo_include_sku" value="0" />
                                            <input type="checkbox" name="sschp_woo_include_sku" value="1" <?php checked( get_option( 'sschp_woo_include_sku', '0' ), '1' ); ?> />
                                            <?php esc_html_e( 'Include SKU values as chips', 'smart-search-chips' ); ?>
                                        </label>
                                        <p class="description"><?php esc_html_e( 'SKU chips are disabled by default because SKUs are often unique and noisy in search results.', 'smart-search-chips' ); ?></p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>

                        <div class="ssc-settings-card">
                            <h2><?php esc_html_e( 'Chips', 'smart-search-chips' ); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e( 'Max Chips', 'smart-search-chips' ); ?></th>
                                    <td><input type="number" name="sschp_max_chips" value="<?php echo esc_attr( min( (int) get_option( 'sschp_max_chips', '8' ), SSCHP_Edition::max_chips() ) ); ?>" class="small-text" min="1" max="<?php echo (int) SSCHP_Edition::max_chips(); ?>" /></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Max Tag Length', 'smart-search-chips' ); ?></th>
                                    <td><input type="number" name="sschp_max_tag_length" value="<?php echo esc_attr( get_option( 'sschp_max_tag_length', '40' ) ); ?>" class="small-text" min="5" /></td>
                                </tr>
                            </table>
                        </div>

                        <div class="ssc-settings-card">
                            <h2><?php esc_html_e( 'Design', 'smart-search-chips' ); ?></h2>
                            <table class="form-table">
                                <tr><th><?php esc_html_e( 'Active Color', 'smart-search-chips' ); ?></th><td><input type="color" name="sschp_chip_color" value="<?php echo esc_attr( $chip_color ); ?>" /></td></tr>
                                <tr><th><?php esc_html_e( 'Active Text', 'smart-search-chips' ); ?></th><td><input type="color" name="sschp_chip_text_color" value="<?php echo esc_attr( $chip_text ); ?>" /></td></tr>
                                <tr><th><?php esc_html_e( 'Inactive Color', 'smart-search-chips' ); ?></th><td><input type="color" name="sschp_chip_inactive_color" value="<?php echo esc_attr( get_option( 'sschp_chip_inactive_color', '#ffffff' ) ); ?>" /></td></tr>
                                <tr><th><?php esc_html_e( 'Inactive Text', 'smart-search-chips' ); ?></th><td><input type="color" name="sschp_chip_inactive_text_color" value="<?php echo esc_attr( get_option( 'sschp_chip_inactive_text_color', '#333333' ) ); ?>" /></td></tr>
                                <tr><th><?php esc_html_e( 'Border Radius', 'smart-search-chips' ); ?></th><td><input type="number" name="sschp_chip_radius" value="<?php echo esc_attr( $chip_radius ); ?>" class="small-text" min="0" max="50" /> px</td></tr>
                                <tr><th><?php esc_html_e( 'Font Size', 'smart-search-chips' ); ?></th><td><input type="number" name="sschp_chip_font_size" value="<?php echo esc_attr( $chip_font ); ?>" class="small-text" min="8" max="24" /> px</td></tr>
                                <tr><th><?php esc_html_e( 'Padding H', 'smart-search-chips' ); ?></th><td><input type="number" name="sschp_chip_padding_x" value="<?php echo esc_attr( $chip_pad_x ); ?>" class="small-text" min="0" max="40" /> px</td></tr>
                                <tr><th><?php esc_html_e( 'Padding V', 'smart-search-chips' ); ?></th><td><input type="number" name="sschp_chip_padding_y" value="<?php echo esc_attr( $chip_pad_y ); ?>" class="small-text" min="0" max="24" /> px</td></tr>
                            </table>
                        </div>

                        <div class="ssc-settings-card">
                            <h2><?php esc_html_e( 'Results', 'smart-search-chips' ); ?></h2>
                            <table class="form-table">
                                <tr><th><?php esc_html_e( 'Results Shown', 'smart-search-chips' ); ?></th><td><input type="number" name="sschp_max_results" value="<?php echo esc_attr( get_option( 'sschp_max_results', '10' ) ); ?>" class="small-text" min="1" /></td></tr>
                                <tr><th><?php esc_html_e( 'Results to Analyze', 'smart-search-chips' ); ?></th><td><input type="number" name="sschp_search_results_count" value="<?php echo esc_attr( get_option( 'sschp_search_results_count', '50' ) ); ?>" class="small-text" min="5" /><p class="description"><?php esc_html_e( 'More results can produce better chips but may be slower. Recommended: 50.', 'smart-search-chips' ); ?></p></td></tr>
                                <tr><th><?php esc_html_e( 'Relevance Threshold', 'smart-search-chips' ); ?></th><td><input type="range" name="sschp_relevance_threshold" value="<?php echo esc_attr( get_option( 'sschp_relevance_threshold', '0.3' ) ); ?>" min="0" max="1" step="0.05" /><span class="ssc-range-val"><?php echo esc_html( get_option( 'sschp_relevance_threshold', '0.3' ) ); ?></span><p class="description"><?php esc_html_e( 'Requires Relevanssi. 0 = all results, 1 = exact matches only.', 'smart-search-chips' ); ?></p></td></tr>
                            </table>
                        </div>

                        <details class="ssc-settings-card ssc-settings-card--collapsible">
                            <summary class="ssc-settings-card__summary"><span class="ssc-settings-card__summary-title"><?php esc_html_e( 'Advanced', 'smart-search-chips' ); ?></span></summary>
                            <div class="ssc-settings-card__body">
                                <p class="description"><?php esc_html_e( 'Only change these if you already know your field names or need to integrate with an existing setup.', 'smart-search-chips' ); ?></p>
                                <table class="form-table">
                                    <tr><th><?php esc_html_e( 'Override AJAX Action', 'smart-search-chips' ); ?></th><td><input type="text" name="sschp_override_ajax" value="<?php echo esc_attr( get_option( 'sschp_override_ajax', '' ) ); ?>" class="regular-text" /><p class="description"><?php esc_html_e( 'Optional. Use your theme existing AJAX search action name if you need Smart Search Chips to hook into it.', 'smart-search-chips' ); ?></p></td></tr>
                                    <tr><th><?php esc_html_e( 'Keyword Field Name', 'smart-search-chips' ); ?></th><td><input type="text" name="sschp_ajax_keyword_field" value="<?php echo esc_attr( get_option( 'sschp_ajax_keyword_field', 'keyword' ) ); ?>" class="regular-text" /><p class="description"><?php esc_html_e( 'The request field that contains the typed search term. Common values are `keyword`, `s`, `query`, and `search`.', 'smart-search-chips' ); ?></p></td></tr>
                                    <tr><th><?php esc_html_e( 'Search Input Selector', 'smart-search-chips' ); ?></th><td><input type="text" name="sschp_search_input_selector" value="<?php echo esc_attr( get_option( 'sschp_search_input_selector', '.ssc-search__input, input[type="search"], input[name="keyword"], input[name="s"], input[name="query"], input[name="search"]' ) ); ?>" class="large-text" /><p class="description"><?php esc_html_e( 'CSS selector used to find the live-search input. Keep the default unless your theme uses a custom search field.', 'smart-search-chips' ); ?></p></td></tr>
                                    <tr><th><?php esc_html_e( 'Result Container Selector', 'smart-search-chips' ); ?></th><td><input type="text" name="sschp_result_container_selector" value="<?php echo esc_attr( get_option( 'sschp_result_container_selector', '.js-ssc-results, .js-search__results, .ssc-search__results' ) ); ?>" class="large-text" /><p class="description"><?php esc_html_e( 'CSS selector for the theme element that receives AJAX results, for example `.js-search__results`.', 'smart-search-chips' ); ?></p></td></tr>
                                    <tr><th><?php esc_html_e( 'Result Image Fit', 'smart-search-chips' ); ?></th><td><?php $image_fit = $this->sanitize_result_image_fit( get_option( 'sschp_result_image_fit', '' ) ); ?><select name="sschp_result_image_fit"><option value="" <?php selected( $image_fit, '' ); ?>><?php esc_html_e( 'Theme/default', 'smart-search-chips' ); ?></option><option value="contain" <?php selected( $image_fit, 'contain' ); ?>><?php esc_html_e( 'Contain', 'smart-search-chips' ); ?></option><option value="cover" <?php selected( $image_fit, 'cover' ); ?>><?php esc_html_e( 'Cover', 'smart-search-chips' ); ?></option></select><p class="description"><?php esc_html_e( 'Leave as Theme/default unless SSC-rendered AJAX results need explicit image fitting.', 'smart-search-chips' ); ?></p></td></tr>
                                    <tr><th><?php esc_html_e( 'Result Image Size', 'smart-search-chips' ); ?></th><td><input type="text" name="sschp_result_image_size" value="<?php echo esc_attr( get_option( 'sschp_result_image_size', 'medium' ) ); ?>" class="regular-text" /><p class="description"><?php esc_html_e( 'WordPress image size used for AJAX results. Common values: `thumbnail`, `medium`, `large`, `full`.', 'smart-search-chips' ); ?></p></td></tr>
                                    <tr><th><?php esc_html_e( 'Result Image Display Size', 'smart-search-chips' ); ?></th><td><input type="number" name="sschp_result_image_display_size" value="<?php echo esc_attr( get_option( 'sschp_result_image_display_size', '' ) ); ?>" class="small-text" min="0" step="1" /><p class="description"><?php esc_html_e( 'Optional pixel size for SSC-rendered AJAX result thumbnails. Leave empty or 0 to avoid forcing a display size.', 'smart-search-chips' ); ?></p></td></tr>
                                </table>
                            </div>
                        </details>

                        <div class="ssc-settings-card ssc-settings-card--danger">
                            <h2><?php esc_html_e( 'Data Cleanup', 'smart-search-chips' ); ?></h2>
                            <table class="form-table">
                                <tr><th><?php esc_html_e( 'Delete on Uninstall', 'smart-search-chips' ); ?></th><td><label><input type="hidden" name="sschp_delete_data_on_uninstall" value="0" /><input type="checkbox" name="sschp_delete_data_on_uninstall" value="1" <?php checked( $delete_on_uninstall, '1' ); ?> /><?php esc_html_e( 'Delete all plugin data when the plugin is uninstalled', 'smart-search-chips' ); ?></label></td></tr>
                                <tr><th><?php esc_html_e( 'Delete Data Now', 'smart-search-chips' ); ?></th><td><button type="submit" form="ssc-delete-data-form" class="button button-secondary" id="ssc-delete-data-btn" data-confirm="<?php echo esc_attr__( 'Delete Smart Search Chips settings and built-in chip meta now? This cannot be undone.', 'smart-search-chips' ); ?>"><?php esc_html_e( 'Delete All Plugin Data Now', 'smart-search-chips' ); ?></button></td></tr>
                            </table>
                        </div>

                        <?php submit_button(); ?>
                    </div>

                    <div id="ssc-settings-right">
                        <div id="ssc-preview-panel">
                            <h3><?php esc_html_e( 'Style Preview', 'smart-search-chips' ); ?></h3>
                            <div id="ssc-preview-search" style="margin-bottom:12px;"><input type="text" value="example query" disabled style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;background:#f9f9f9;color:#999;" /></div>
                            <div id="ssc-preview-chips" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px;"></div>
                            <div id="ssc-preview-results">
                                <div class="ssc-preview-result"><div class="ssc-preview-thumb"></div><span>Product result 1</span></div>
                                <div class="ssc-preview-result"><div class="ssc-preview-thumb"></div><span>Product result 2</span></div>
                                <div class="ssc-preview-result"><div class="ssc-preview-thumb"></div><span>Product result 3</span></div>
                            </div>
                            <p class="ssc-preview-save"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'smart-search-chips' ); ?></button></p>
                        </div>

                        <div class="ssc-sidebar-card ssc-sidebar-card--pro">
                            <h3><?php esc_html_e( 'Need Pro Features?', 'smart-search-chips' ); ?></h3>
                            <p><?php esc_html_e( 'Pro is a separate upgrade for ACF/custom fields, numeric sliders, analytics, synonyms, manual curation, AI-assisted tag workflows, and commercial support.', 'smart-search-chips' ); ?></p>
                            <ul>
                                <li><?php esc_html_e( 'Map existing ACF or custom tag fields.', 'smart-search-chips' ); ?></li>
                                <li><?php esc_html_e( 'Turn numeric chip variants into range sliders.', 'smart-search-chips' ); ?></li>
                                <li><?php esc_html_e( 'Review local search analytics and curate chip labels.', 'smart-search-chips' ); ?></li>
                            </ul>
                            <p>
                                <a class="button button-primary" href="<?php echo esc_url( SSCHP_Edition::get_product_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Upgrade to Pro', 'smart-search-chips' ); ?></a>
                                <a class="button button-secondary" href="<?php echo esc_url( SSCHP_Edition::get_product_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Features', 'smart-search-chips' ); ?></a>
                            </p>
                        </div>
                    </div>
                </div>
            </form>

            <form id="ssc-delete-data-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="sschp_delete_plugin_data_now" />
                <?php wp_nonce_field( 'sschp_delete_plugin_data_now' ); ?>
            </form>

            <p class="ssc-feedback-link">
                <?php esc_html_e( 'Questions, bugs, or ideas?', 'smart-search-chips' ); ?>
                <a href="mailto:[redacted]?subject=Smart%20Search%20Chips%20Feedback"><?php esc_html_e( 'Send feedback', 'smart-search-chips' ); ?></a>
            </p>
        </div>
        <?php
    }

    public function handle_delete_data_now() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to delete plugin data.', 'smart-search-chips' ) );
        }

        check_admin_referer( 'sschp_delete_plugin_data_now' );
        sschp_delete_plugin_data();
        set_transient( 'sschp_data_deleted_' . get_current_user_id(), '1', MINUTE_IN_SECONDS );

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page' => 'smart-search-chips',
                ),
                admin_url( 'options-general.php' )
            )
        );
        exit;
    }

    public function handle_dismiss_onboarding() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to change this setting.', 'smart-search-chips' ) );
        }

        check_admin_referer( 'sschp_dismiss_onboarding' );
        update_user_meta( get_current_user_id(), 'sschp_onboarding_dismissed', '1' );

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page' => 'smart-search-chips',
                ),
                admin_url( 'options-general.php' )
            )
        );
        exit;
    }

    private function render_post_types() {
        $selected = get_option( 'sschp_post_types', array( 'post' ) );
        $selected = $this->sanitize_post_types( $selected );

        $post_types = get_post_types( array( 'public' => true ), 'objects' );

        echo '<input type="hidden" name="sschp_post_types[]" value="" />';

        foreach ( $post_types as $pt ) {
            if ( 'attachment' === $pt->name ) {
                continue;
            }

            printf(
                '<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="sschp_post_types[]" value="%s" %s /> %s</label>',
                esc_attr( $pt->name ),
                checked( in_array( $pt->name, $selected, true ), true, false ),
                esc_html( $pt->label )
            );
        }
    }

    public function sanitize_checkbox( $value ) {
        return ! empty( $value ) ? '1' : '0';
    }

    public function sanitize_post_types( $value ) {
        $selected = is_array( $value ) ? $value : array( $value );
        $selected = array_filter( array_map( 'sanitize_key', $selected ) );
        $public   = get_post_types( array( 'public' => true ), 'names' );
        $public   = array_diff( $public, array( 'attachment' ) );

        $selected = array_values( array_intersect( $selected, $public ) );

        return ! empty( $selected ) ? $selected : array( 'post' );
    }

    public function sanitize_hex_color_setting( $value ) {
        $color = sanitize_hex_color( $value );
        return $color ? $color : '';
    }

    public function sanitize_result_image_fit( $value ) {
        $value = sanitize_key( $value );
        return in_array( $value, array( '', 'contain', 'cover' ), true ) ? $value : '';
    }

    public function sanitize_result_image_size( $value ) {
        $value = sanitize_key( $value );
        return '' !== $value ? $value : 'medium';
    }

    public function sanitize_result_image_display_size( $value ) {
        $value = absint( $value );
        return $value > 0 ? $value : '';
    }

    public function sanitize_max_chips( $value ) {
        return $this->sanitize_numeric_setting( $value, 8, 1, SSCHP_Edition::max_chips() );
    }

    public function sanitize_max_tag_length( $value ) {
        return $this->sanitize_numeric_setting( $value, 40, 5, 120 );
    }

    public function sanitize_max_results( $value ) {
        return $this->sanitize_numeric_setting( $value, 10, 1, 100 );
    }

    public function sanitize_search_results_count( $value ) {
        return $this->sanitize_numeric_setting( $value, 50, 5, 200 );
    }

    public function sanitize_relevance_threshold( $value ) {
        if ( '' === $value || ! is_numeric( $value ) ) {
            return 0.3;
        }

        $value = (float) $value;
        $value = max( 0, $value );
        $value = min( 1, $value );

        return $value;
    }

    public function sanitize_chip_radius( $value ) {
        return $this->sanitize_numeric_setting( $value, 16, 0, 50 );
    }

    public function sanitize_chip_font_size( $value ) {
        return $this->sanitize_numeric_setting( $value, 13, 8, 24 );
    }

    public function sanitize_chip_padding_x( $value ) {
        return $this->sanitize_numeric_setting( $value, 12, 0, 40 );
    }

    public function sanitize_chip_padding_y( $value ) {
        return $this->sanitize_numeric_setting( $value, 4, 0, 24 );
    }

    private function get_numeric_option_value( $option_name, $default, $min = 0 ) {
        $value = get_option( $option_name, $default );

        if ( '' === $value || ! is_numeric( $value ) ) {
            return (int) $default;
        }

        return max( (int) $min, (int) $value );
    }

    private function sanitize_numeric_setting( $value, $default, $min, $max ) {
        if ( '' === $value || ! is_numeric( $value ) ) {
            return (int) $default;
        }

        $value = absint( $value );
        $value = max( (int) $min, $value );
        $value = min( (int) $max, $value );

        return (int) $value;
    }

}