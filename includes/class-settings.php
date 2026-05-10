<?php
/**
 * Settings - Admin settings page with live preview panel
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
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_filter( 'plugin_action_links_' . SSCHP_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
    }

    public function get( $key, $default = '' ) {
        $value = get_option( $this->prefix . $key, $default );
        return $value !== false ? $value : $default;
    }

    public function add_menu_page() {
        add_options_page(
            __( 'Smart Search Chips', 'smart-search-chips' ),
            __( 'Search Chips', 'smart-search-chips' ),
            'manage_options',
            'smart-search-chips',
            array( $this, 'render_settings_page' )
        );

        add_options_page(
            __( 'Search Chips Analytics', 'smart-search-chips' ),
            __( 'Search Chips Analytics', 'smart-search-chips' ),
            'manage_options',
            'smart-search-chips-analytics',
            array( $this, 'render_analytics_page' )
        );
    }

    public function add_settings_link( $links ) {
        $url  = admin_url( 'options-general.php?page=smart-search-chips' );
        $link = '<a href="' . esc_url( $url ) . '">' . __( 'Settings', 'smart-search-chips' ) . '</a>';
        array_unshift( $links, $link );
        return $links;
    }

    public function register_settings() {
        // All settings registered flat (no sections — we render manually)
        $fields = array(
            'post_types', 'override_ajax', 'ajax_keyword_field', 'result_container_selector',
            'max_chips', 'max_tag_length',
            'woo_enabled',
            'chip_color', 'chip_text_color', 'chip_inactive_color', 'chip_inactive_text_color',
            'max_results', 'search_results_count', 'relevance_threshold',
        );
        foreach ( $fields as $f ) {
            register_setting( 'smart-search-chips', $this->prefix . $f, array(
                'sanitize_callback' => 'sanitize_text_field',
            ) );
        }

        register_setting( 'smart-search-chips', 'sschp_tag_fields', array(
            'sanitize_callback' => array( $this, 'sanitize_custom_tag_field' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_extra_fields', array(
            'sanitize_callback' => array( $this, 'sanitize_extra_tag_field' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_chip_radius', array(
            'sanitize_callback' => array( $this, 'sanitize_chip_radius' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_chip_font_size', array(
            'sanitize_callback' => array( $this, 'sanitize_chip_font_size' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_chip_padding_x', array(
            'sanitize_callback' => array( $this, 'sanitize_chip_padding_x' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_chip_padding_y', array(
            'sanitize_callback' => array( $this, 'sanitize_chip_padding_y' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_synonym_rules', array(
            'sanitize_callback' => array( $this, 'sanitize_chip_rules_text' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_pinned_chips', array(
            'sanitize_callback' => array( $this, 'sanitize_chip_rules_text' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_hidden_chips', array(
            'sanitize_callback' => array( $this, 'sanitize_chip_rules_text' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_renamed_chips', array(
            'sanitize_callback' => array( $this, 'sanitize_chip_rules_text' ),
        ) );

        register_setting( 'smart-search-chips', 'sschp_ai_custom_read_fields', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'smart-search-chips', 'sschp_ai_target_field', array(
            'sanitize_callback' => array( $this, 'sanitize_ai_target_field' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_ai_rest_enabled', array(
            'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_ai_max_tags', array(
            'sanitize_callback' => 'absint',
        ) );
        register_setting( 'smart-search-chips', 'sschp_ai_language', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'smart-search-chips', 'sschp_ai_custom_instruction', array(
            'sanitize_callback' => 'sanitize_textarea_field',
        ) );
        register_setting( 'smart-search-chips', 'sschp_analytics_enabled', array(
            'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_delete_data_on_uninstall', array(
            'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_result_image_fit', array(
            'sanitize_callback' => array( $this, 'sanitize_result_image_fit' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_result_image_size', array(
            'sanitize_callback' => array( $this, 'sanitize_result_image_size' ),
        ) );
    }

    /**
     * Enqueue admin assets for the settings page.
     *
     * @param string $hook_suffix Current admin page hook.
     */
    public function enqueue_admin_assets( $hook_suffix ) {
        $is_settings  = ( $hook_suffix === 'settings_page_smart-search-chips' );
        $is_analytics = ( $hook_suffix === 'settings_page_smart-search-chips-analytics' );

        if ( ! $is_settings && ! $is_analytics ) {
            return;
        }

        if ( $is_settings ) {
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

        if ( $is_analytics ) {
            wp_enqueue_style(
                'smart-search-chips-analytics',
                SSCHP_PLUGIN_URL . 'assets/css/analytics.css',
                array(),
                SSCHP_VERSION
            );
        }

        if ( ! $is_settings ) {
            return;
        }

        if ( sschp_fs()->can_use_premium_code__premium_only() ) {
            wp_enqueue_script(
                'smart-search-chips-admin-ai',
                SSCHP_PLUGIN_URL . 'assets/js/admin-ai.js',
                array(),
                file_exists( SSCHP_PLUGIN_DIR . 'assets/js/admin-ai.js' ) ? filemtime( SSCHP_PLUGIN_DIR . 'assets/js/admin-ai.js' ) : SSCHP_VERSION,
                true
            );

            wp_localize_script( 'smart-search-chips-admin-ai', 'sschpAiAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( SSCHP_AI_Tagger::AJAX_NONCE ),
            'strings' => array(
                'working'             => __( 'Working...', 'smart-search-chips' ),
                'loadingTerms'        => __( 'Loading categories...', 'smart-search-chips' ),
                'loadTermsFailed'     => __( 'Could not load categories for the picker.', 'smart-search-chips' ),
                'loadingItems'        => __( 'Loading items for this category...', 'smart-search-chips' ),
                'loadItemsFailed'     => __( 'Could not load items for this category.', 'smart-search-chips' ),
                'searchPlaceholder'   => __( 'Filter categories or products...', 'smart-search-chips' ),
                'selectionEmptyShort' => __( 'No items selected yet.', 'smart-search-chips' ),
                'selectionEmpty'      => __( 'No items selected yet. Build a batch on the left and the selected items will stay pinned here.', 'smart-search-chips' ),
                /* translators: %d: number of selected items */
                'selectionCount'      => __( '%d items selected', 'smart-search-chips' ),
                /* translators: %1$d: number of taxonomies, %2$d: number of visible categories */
                'browseSummary'       => __( '%1$d taxonomies · %2$d visible categories', 'smart-search-chips' ),
                'browseEmpty'         => __( 'No categories matched the current filter.', 'smart-search-chips' ),
                'itemsEmpty'          => __( 'No items were found in this category.', 'smart-search-chips' ),
                'clearAll'            => __( 'Clear all', 'smart-search-chips' ),
                'addItem'             => __( 'Add to batch', 'smart-search-chips' ),
                'removeItem'          => __( 'Remove', 'smart-search-chips' ),
                'addGroup'            => __( 'Add category', 'smart-search-chips' ),
                'removeGroup'         => __( 'Remove category items', 'smart-search-chips' ),
                'expandGroup'         => __( 'Show products', 'smart-search-chips' ),
                'collapseGroup'       => __( 'Hide products', 'smart-search-chips' ),
                'selectItems'         => __( 'Select at least one product or post first.', 'smart-search-chips' ),
                'promptReady'         => __( 'Agent prompt generated.', 'smart-search-chips' ),
                'copied'              => __( 'Copied.', 'smart-search-chips' ),
                    'copyFailed'          => __( 'Copy failed. Copy the snippet manually.', 'smart-search-chips' ),
                ),
            ) );
        }
    }

    /**
     * Render the full settings page with two-column layout.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $is_pro = SSCHP_License::is_pro();
        $relevanssi_active = function_exists( 'relevanssi_do_query' );

        // Get current values
        $chip_color    = get_option( 'sschp_chip_color', '#e74c3c' );
        $chip_text     = get_option( 'sschp_chip_text_color', '#ffffff' );
        $chip_radius   = $this->get_numeric_option_value( 'sschp_chip_radius', 16, 0 );
        $chip_font     = $this->get_numeric_option_value( 'sschp_chip_font_size', 13, 8 );
        $chip_pad_x    = $this->get_numeric_option_value( 'sschp_chip_padding_x', 12, 0 );
        $chip_pad_y    = $this->get_numeric_option_value( 'sschp_chip_padding_y', 4, 0 );
        $ai_max_tags   = max( 0, (int) get_option( 'sschp_ai_max_tags', 8 ) );
        $ai_rest_mode  = get_option( 'sschp_ai_rest_enabled', '1' );
        $ai_target     = $this->sanitize_ai_target_field( get_option( 'sschp_ai_target_field', 'built_in' ) );
        $analytics_on  = get_option( 'sschp_analytics_enabled', '0' );
        $delete_on_uninstall = get_option( 'sschp_delete_data_on_uninstall', '0' );
        $data_deleted = get_transient( 'sschp_data_deleted_' . get_current_user_id() );
        if ( $data_deleted ) {
            delete_transient( 'sschp_data_deleted_' . get_current_user_id() );
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <?php settings_errors( 'smart-search-chips' ); ?>
            <?php if ( '1' === $data_deleted ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Smart Search Chips data was deleted. Settings have been reset, built-in chip meta was removed, and analytics events were cleared.', 'smart-search-chips' ); ?></p></div>
            <?php endif; ?>

            <?php if ( $is_pro && ! $relevanssi_active ) : ?>
                <div class="notice notice-info"><p><strong><?php esc_html_e( 'Relevanssi not detected.', 'smart-search-chips' ); ?></strong> <?php esc_html_e( 'Install Relevanssi (free) for relevance scoring.', 'smart-search-chips' ); ?></p></div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields( 'smart-search-chips' ); ?>

                <div id="ssc-settings-layout">
                    <!-- LEFT: Settings -->
                    <div id="ssc-settings-left">

                        <!-- License -->
                        <div class="ssc-settings-card">
                            <h2><?php esc_html_e( 'License', 'smart-search-chips' ); ?></h2>
                            <?php if ( $is_pro ) : ?>
                                <div class="ssc-badge ssc-badge--pro">Pro</div>
                            <?php else : ?>
                                <div class="ssc-badge ssc-badge--free">Free</div>
                            <?php endif; ?>
                            <p><strong><?php echo esc_html( SSCHP_License::get_status_label() ); ?></strong></p>
                            <p class="description"><?php echo esc_html( SSCHP_License::get_status_description() ); ?></p>
                            <?php if ( SSCHP_License::is_sdk_ready() ) : ?>
                                <p>
                                    <?php if ( ! $is_pro && SSCHP_License::get_upgrade_url() ) : ?>
                                        <a class="button button-primary" href="<?php echo esc_url( SSCHP_License::get_upgrade_url() ); ?>"><?php esc_html_e( 'Upgrade to Pro', 'smart-search-chips' ); ?></a>
                                    <?php endif; ?>
                                    <?php if ( SSCHP_License::get_account_url() ) : ?>
                                        <a class="button button-secondary" href="<?php echo esc_url( SSCHP_License::get_account_url() ); ?>"><?php esc_html_e( 'Manage License', 'smart-search-chips' ); ?></a>
                                    <?php endif; ?>
                                </p>
                            <?php else : ?>
                                <p class="description"><?php esc_html_e( 'Install the Freemius SDK in `vendor/freemius` to enable activation and billing on this screen.', 'smart-search-chips' ); ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Search Settings -->
                        <div class="ssc-settings-card">
                            <h2><?php esc_html_e( 'Search', 'smart-search-chips' ); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e( 'Post Types', 'smart-search-chips' ); ?></th>
                                    <td><?php $this->render_post_types(); ?></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Tag Source', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <p><?php esc_html_e( 'Built-in "Search Chips Tags" field (`sschp_tags`) is used by default.', 'smart-search-chips' ); ?></p>
                                        <?php if ( $is_pro ) : ?>
                                            <p class="description"><?php esc_html_e( 'If you already use your own tag meta field, you can map it in Advanced settings.', 'smart-search-chips' ); ?></p>
                                        <?php else : ?>
                                            <p class="description"><?php esc_html_e( 'Upgrade to Pro to map an existing custom field instead.', 'smart-search-chips' ); ?></p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                                <tr>
                                    <th><?php esc_html_e( 'WooCommerce Auto-Tags', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="sschp_woo_enabled" value="1" <?php checked( get_option( 'sschp_woo_enabled', '1' ), '1' ); ?> />
                                            <?php esc_html_e( 'Auto-populate tags from product attributes, categories, and SKU', 'smart-search-chips' ); ?>
                                        </label>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>

                        <!-- Chip Settings -->
                        <div class="ssc-settings-card">
                            <h2><?php esc_html_e( 'Chips', 'smart-search-chips' ); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e( 'Max Chips', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <input type="number" name="sschp_max_chips" value="<?php echo esc_attr( min( (int) get_option( 'sschp_max_chips', '8' ), SSCHP_License::max_chips() ) ); ?>" class="small-text" min="1" max="<?php echo (int) SSCHP_License::max_chips(); ?>" />
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Max Tag Length', 'smart-search-chips' ); ?></th>
                                    <td><input type="number" name="sschp_max_tag_length" value="<?php echo esc_attr( get_option( 'sschp_max_tag_length', '40' ) ); ?>" class="small-text" min="5" /></td>
                                </tr>
                                <?php if ( $is_pro ) : ?>
                                <tr>
                                    <th><?php esc_html_e( 'Synonym Rules', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <textarea name="sschp_synonym_rules" rows="5" class="large-text code" placeholder="Sofa = couch, settee&#10;Organic = bio, eco"><?php echo esc_textarea( get_option( 'sschp_synonym_rules', '' ) ); ?></textarea>
                                        <p class="description"><?php esc_html_e( 'Pro: normalize several tag names into one canonical chip. Use one rule per line: Canonical = alias, alias.', 'smart-search-chips' ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Numeric Range Chips', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <p><?php esc_html_e( 'Pro automatically groups numeric chip variants such as "Power 100 W" and "Power 200 W" into one slider chip.', 'smart-search-chips' ); ?></p>
                                        <p class="description"><?php esc_html_e( 'Use labels before values for best results, for example "Power 100 W", "Length: 120 cm", or a mapped numeric field.', 'smart-search-chips' ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Pinned Chips', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <textarea name="sschp_pinned_chips" rows="3" class="large-text code" placeholder="Bestseller, New arrival, Eco"><?php echo esc_textarea( get_option( 'sschp_pinned_chips', '' ) ); ?></textarea>
                                        <p class="description"><?php esc_html_e( 'Pro: show these chips first whenever they exist in the current result set, even if the normal ranking would hide them.', 'smart-search-chips' ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Hidden Chips', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <textarea name="sschp_hidden_chips" rows="3" class="large-text code" placeholder="Internal, Old SKU, Supplier only"><?php echo esc_textarea( get_option( 'sschp_hidden_chips', '' ) ); ?></textarea>
                                        <p class="description"><?php esc_html_e( 'Pro: prevent noisy or internal chips from being suggested. Active chips remain visible so users can clear them.', 'smart-search-chips' ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Chip Label Overrides', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <textarea name="sschp_renamed_chips" rows="4" class="large-text code" placeholder="eco => Eco friendly&#10;small => Compact"><?php echo esc_textarea( get_option( 'sschp_renamed_chips', '' ) ); ?></textarea>
                                        <p class="description"><?php esc_html_e( 'Pro: change the displayed chip label without editing product field values. Use one rule per line: source => display label.', 'smart-search-chips' ); ?></p>
                                    </td>
                                </tr>
                                <?php else : ?>
                                <tr>
                                    <td colspan="2"><p class="description"><?php esc_html_e( 'Upgrade to Pro for synonym rules, numeric range slider chips, pinned chips, hidden chips, and display label overrides.', 'smart-search-chips' ); ?></p></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>

                        <!-- Design -->
                        <div class="ssc-settings-card">
                            <h2><?php esc_html_e( 'Design', 'smart-search-chips' ); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e( 'Active Color', 'smart-search-chips' ); ?></th>
                                    <td><input type="color" name="sschp_chip_color" value="<?php echo esc_attr( $chip_color ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Active Text', 'smart-search-chips' ); ?></th>
                                    <td><input type="color" name="sschp_chip_text_color" value="<?php echo esc_attr( $chip_text ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Inactive Color', 'smart-search-chips' ); ?></th>
                                    <td><input type="color" name="sschp_chip_inactive_color" value="<?php echo esc_attr( get_option( 'sschp_chip_inactive_color', '#ffffff' ) ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Inactive Text', 'smart-search-chips' ); ?></th>
                                    <td><input type="color" name="sschp_chip_inactive_text_color" value="<?php echo esc_attr( get_option( 'sschp_chip_inactive_text_color', '#333333' ) ); ?>" /></td>
                                </tr>
                                <?php if ( $is_pro ) : ?>
                                <tr>
                                    <th><?php esc_html_e( 'Border Radius', 'smart-search-chips' ); ?></th>
                                    <td><input type="number" name="sschp_chip_radius" value="<?php echo esc_attr( $chip_radius ); ?>" class="small-text" min="0" max="50" placeholder="16" /> px</td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Font Size', 'smart-search-chips' ); ?></th>
                                    <td><input type="number" name="sschp_chip_font_size" value="<?php echo esc_attr( $chip_font ); ?>" class="small-text" min="8" max="24" placeholder="13" /> px</td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Padding H', 'smart-search-chips' ); ?></th>
                                    <td><input type="number" name="sschp_chip_padding_x" value="<?php echo esc_attr( $chip_pad_x ); ?>" class="small-text" min="0" max="40" placeholder="12" /> px</td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Padding V', 'smart-search-chips' ); ?></th>
                                    <td><input type="number" name="sschp_chip_padding_y" value="<?php echo esc_attr( $chip_pad_y ); ?>" class="small-text" min="0" max="24" placeholder="4" /> px</td>
                                </tr>
                                <?php else : ?>
                                <tr>
                                    <td colspan="2"><p class="description"><?php esc_html_e( 'Upgrade to Pro for border radius, font size, and padding controls.', 'smart-search-chips' ); ?></p></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>

                        <!-- Results -->
                        <div class="ssc-settings-card">
                            <h2><?php esc_html_e( 'Results', 'smart-search-chips' ); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e( 'Results Shown', 'smart-search-chips' ); ?></th>
                                    <td><input type="number" name="sschp_max_results" value="<?php echo esc_attr( get_option( 'sschp_max_results', '10' ) ); ?>" class="small-text" min="1" /></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Results to Analyze', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <input type="number" name="sschp_search_results_count" value="<?php echo esc_attr( get_option( 'sschp_search_results_count', '50' ) ); ?>" class="small-text" min="5" />
                                        <p class="description"><?php esc_html_e( 'More = better chips, slower. Recommended: 50.', 'smart-search-chips' ); ?></p>
                                    </td>
                                </tr>
                                <?php if ( $is_pro ) : ?>
                                <tr>
                                    <th><?php esc_html_e( 'Relevance Threshold', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <input type="range" name="sschp_relevance_threshold" value="<?php echo esc_attr( get_option( 'sschp_relevance_threshold', '0.3' ) ); ?>" min="0" max="1" step="0.05" />
                                        <span class="ssc-range-val"><?php echo esc_html( get_option( 'sschp_relevance_threshold', '0.3' ) ); ?></span>
                                        <p class="description"><?php esc_html_e( 'Requires Relevanssi. 0 = all results, 1 = exact matches only.', 'smart-search-chips' ); ?></p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>

                        <div class="ssc-settings-card">
                            <h2><?php esc_html_e( 'Analytics', 'smart-search-chips' ); ?></h2>
                            <?php if ( ! $is_pro ) : ?>
                                <p><?php esc_html_e( 'Analytics is part of Pro. It shows which search chips get clicked, what visitors search for, and which queries return no results.', 'smart-search-chips' ); ?></p>
                                <p class="description"><?php esc_html_e( 'Upgrade to Pro to enable anonymous search analytics and view the dashboard directly in WordPress.', 'smart-search-chips' ); ?></p>
                            <?php else : ?>
                                <table class="form-table">
                                    <tr>
                                        <th><?php esc_html_e( 'Enable Analytics', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <label>
                                                <input type="hidden" name="sschp_analytics_enabled" value="0" />
                                                <input type="checkbox" name="sschp_analytics_enabled" value="1" <?php checked( $analytics_on, '1' ); ?> />
                                                <?php esc_html_e( 'Track anonymous search, chip-click, and chip-impression events for admins', 'smart-search-chips' ); ?>
                                            </label>
                                            <p class="description"><?php esc_html_e( 'Stores aggregate search and chip interaction events on this WordPress site. Uses anonymous visitor IDs for unique-visitor counts.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                </table>
                                <?php if ( $analytics_on === '1' && sschp_fs()->can_use_premium_code__premium_only() ) : ?>
                                    <?php $analytics = SSCHP_Analytics::get_dashboard_data(); ?>
                                    <div class="ssc-analytics-dashboard">
                                        <div class="ssc-analytics-stats">
                                            <div class="ssc-analytics-stat">
                                                <strong><?php echo esc_html( number_format_i18n( $analytics['summary']['total_searches'] ) ); ?></strong>
                                                <span><?php esc_html_e( 'Searches', 'smart-search-chips' ); ?></span>
                                            </div>
                                            <div class="ssc-analytics-stat">
                                                <strong><?php echo esc_html( number_format_i18n( $analytics['summary']['chip_clicks'] ) ); ?></strong>
                                                <span><?php esc_html_e( 'Chip Clicks', 'smart-search-chips' ); ?></span>
                                            </div>
                                            <div class="ssc-analytics-stat">
                                                <strong><?php echo esc_html( number_format_i18n( $analytics['summary']['unique_visitors'] ) ); ?></strong>
                                                <span><?php esc_html_e( 'Unique Visitors', 'smart-search-chips' ); ?></span>
                                            </div>
                                        <div class="ssc-analytics-stat">
                                            <strong><?php echo esc_html( number_format_i18n( $analytics['summary']['zero_result_search'] ) ); ?></strong>
                                            <span><?php esc_html_e( 'Zero-Result Searches', 'smart-search-chips' ); ?></span>
                                        </div>
                                        <div class="ssc-analytics-stat">
                                            <strong><?php echo esc_html( number_format_i18n( $analytics['summary']['search_to_click_rate'], 1 ) . '%' ); ?></strong>
                                            <span><?php esc_html_e( 'Search To Click Rate', 'smart-search-chips' ); ?></span>
                                        </div>
                                    </div>
                                        <p class="description"><?php
                                            /* translators: %1$s: start date, %2$s: end date */
                                            printf( esc_html__( 'Current overview: %1$s to %2$s. Open the dedicated analytics page for date filters, detailed tables, and CSV export.', 'smart-search-chips' ), esc_html( $analytics['range']['from'] ), esc_html( $analytics['range']['to'] ) );
                                        ?></p>
                                        <p><a class="button button-secondary" href="<?php echo esc_url( admin_url( 'options-general.php?page=smart-search-chips-analytics' ) ); ?>"><?php esc_html_e( 'Open Detailed Analytics', 'smart-search-chips' ); ?></a></p>
                                    </div>
                                <?php else : ?>
                                    <p class="description"><?php esc_html_e( 'Enable analytics and save the page to start collecting anonymous usage data. The dashboard will appear here after events are recorded.', 'smart-search-chips' ); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="ssc-settings-card ssc-settings-card--ai">
                            <h2><?php esc_html_e( 'AI Prompt Builder', 'smart-search-chips' ); ?></h2>
                            <?php if ( ! $is_pro ) : ?>
                                <p><?php esc_html_e( 'The AI Prompt Builder is part of Pro. It prepares a structured agent prompt that you can paste into Codex, Claude Code, ChatGPT, Gemini, or another external AI tool. That external tool can then use your own WordPress REST credentials to fetch and write tags.', 'smart-search-chips' ); ?></p>
                                <p class="description"><?php esc_html_e( 'Upgrade to Pro to unlock REST-aware prompt generation, multi-product analysis, custom field targets, and structured agent instructions. This plugin does not request or store any OpenAI, Anthropic, or Gemini API keys.', 'smart-search-chips' ); ?></p>
                            <?php else : ?>
                                <table class="form-table">
                                    <tr>
                                        <th><?php esc_html_e( 'REST API', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <label class="ssc-ai-toggle">
                                                <input type="hidden" name="sschp_ai_rest_enabled" value="0" />
                                                <input type="checkbox" name="sschp_ai_rest_enabled" id="ssc-ai-rest-enabled" value="1" <?php checked( $ai_rest_mode, '1' ); ?> />
                                                <span><?php esc_html_e( 'Allow REST API write-back in generated prompt', 'smart-search-chips' ); ?></span>
                                            </label>
                                            <p class="description"><?php esc_html_e( 'Turn this on if you want the external agent to read and write through the WordPress REST API. Turn it off if you only want the prompt to analyze the selected products and return tags for manual review.', 'smart-search-chips' ); ?></p>
                                            <p class="description"><?php esc_html_e( 'When enabled, the prompt assumes you will use WordPress Application Passwords or another REST-compatible WordPress authentication method outside this plugin. The plugin does not ask for or store LLM-provider credentials.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Extra Read Fields', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="sschp_ai_custom_read_fields" id="ssc-ai-custom-read-fields" value="<?php echo esc_attr( get_option( 'sschp_ai_custom_read_fields', '' ) ); ?>" class="regular-text" />
                                            <p class="description"><?php esc_html_e( 'Optional comma-separated meta keys the external agent should inspect as extra context if they are available over REST.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Target Field', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <?php $target_options = $this->get_ai_target_field_options(); ?>
                                            <select name="sschp_ai_target_field" id="ssc-ai-target-field">
                                                <?php foreach ( $target_options as $value => $label ) : ?>
                                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $ai_target, $value ); ?>><?php echo esc_html( $label ); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p class="description"><?php esc_html_e( 'By default, generated tags target the built-in `sschp_tags` field. If you already mapped your own custom tag field in Advanced settings, you can select it here instead.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Max Tags', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="number" name="sschp_ai_max_tags" id="ssc-ai-max-tags" value="<?php echo esc_attr( $ai_max_tags ); ?>" class="small-text" min="0" step="1" />
                                            <p class="description"><?php esc_html_e( 'Set to 0 for no limit. Any positive number caps the number of generated tags per item.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Language Override', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="sschp_ai_language" id="ssc-ai-language" value="<?php echo esc_attr( get_option( 'sschp_ai_language', '' ) ); ?>" class="regular-text" />
                                            <p class="description"><?php esc_html_e( 'Leave empty to keep chips in the same language as each selected product or post. Use this only if you want the agent prompt to force another language.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Custom Instruction', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <textarea name="sschp_ai_custom_instruction" id="ssc-ai-custom-instruction" rows="4" class="large-text"><?php echo esc_textarea( get_option( 'sschp_ai_custom_instruction', '' ) ); ?></textarea>
                                            <p class="description"><?php esc_html_e( 'Optional instructions for the external agent, for example: focus on technical differences, ignore brand names, keep tags very short, avoid generic marketing phrases, or use a specific language.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            <?php endif; ?>
                        </div>

                        <div class="ssc-settings-card ssc-settings-card--ai-lab">
                            <h2><?php esc_html_e( 'Agent Prompt', 'smart-search-chips' ); ?></h2>
                            <?php if ( ! $is_pro ) : ?>
                                <p class="description"><?php esc_html_e( 'The agent prompt builder becomes available in Pro. It prepares a long-form prompt that tells an external LLM agent to compare similar products together and, when enabled, write semicolon-separated tags back through the WordPress REST API.', 'smart-search-chips' ); ?></p>
                            <?php else : ?>
                                <table class="form-table">
                                    <tr>
                                        <th><?php esc_html_e( 'Selected Items', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <div class="ssc-ai-picker">
                                                <div class="ssc-ai-picker__browser">
                                                    <div class="ssc-ai-browser-toolbar">
                                                        <label class="screen-reader-text" for="ssc-ai-browser-search"><?php esc_html_e( 'Filter categories', 'smart-search-chips' ); ?></label>
                                                        <input type="search" id="ssc-ai-browser-search" class="regular-text" placeholder="<?php echo esc_attr__( 'Filter categories or products...', 'smart-search-chips' ); ?>" />
                                                        <button type="button" class="button" id="ssc-ai-browser-refresh"><?php esc_html_e( 'Refresh', 'smart-search-chips' ); ?></button>
                                                    </div>
                                                    <div class="ssc-ai-browser-meta">
                                                        <strong id="ssc-ai-browser-summary"><?php esc_html_e( 'Loading categories...', 'smart-search-chips' ); ?></strong>
                                                        <span class="description"><?php esc_html_e( 'Start from taxonomy groups like product categories. Expand a category to inspect its products, or add the whole category to the batch in one click.', 'smart-search-chips' ); ?></span>
                                                    </div>
                                                    <div id="ssc-ai-browser-results" class="ssc-ai-browser-results ssc-ai-browser-results--terms" aria-live="polite">
                                                        <div class="ssc-ai-browser-empty"><?php esc_html_e( 'Loading categories...', 'smart-search-chips' ); ?></div>
                                                    </div>
                                                </div>
                                                <div class="ssc-ai-picker__selection">
                                                    <div class="ssc-ai-selection-header">
                                                        <div>
                                                            <strong><?php esc_html_e( 'Analysis Batch', 'smart-search-chips' ); ?></strong>
                                                            <p class="description"><?php esc_html_e( 'These are the items that will be sent into one prompt together. Keep similar products in one batch so the external agent can spot what actually differentiates them.', 'smart-search-chips' ); ?></p>
                                                        </div>
                                                        <button type="button" class="button-link-delete" id="ssc-ai-clear-selection"><?php esc_html_e( 'Clear all', 'smart-search-chips' ); ?></button>
                                                    </div>
                                                    <div id="ssc-ai-selection-summary" class="ssc-ai-selection-summary"><?php esc_html_e( 'No items selected yet.', 'smart-search-chips' ); ?></div>
                                                    <div id="ssc-ai-selected-items" class="ssc-ai-selected-items">
                                                        <div class="ssc-ai-browser-empty"><?php esc_html_e( 'No items selected yet. Build a batch on the left and the selected items will stay pinned here.', 'smart-search-chips' ); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="description"><?php esc_html_e( 'Build the batch from categories first, then inspect individual products only where needed. This avoids endless paginated product lists and fits large catalogs much better.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Actions', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <button type="button" class="button button-primary" id="ssc-ai-build-prompt"><?php esc_html_e( 'Generate Agent Prompt', 'smart-search-chips' ); ?></button>
                                            <span id="ssc-ai-build-status" class="ssc-ai-inline-status" aria-live="polite"></span>
                                            <p class="description"><?php esc_html_e( 'The generated prompt will always describe the selected batch clearly. If REST write-back is enabled, it will also end with placeholders for your WordPress REST credentials. Paste those WordPress credentials into your external AI tool, not into this plugin.', 'smart-search-chips' ); ?></p>
                                            <p class="ssc-ai-warning"><?php esc_html_e( 'You remain responsible for your WordPress REST credentials and for any changes the external AI tool makes through your site. Review the prompt before use. This plugin does not request or store LLM-provider API keys.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                </table>
                                <div id="ssc-ai-rest-panel" class="ssc-ai-panel">
                                    <strong><?php esc_html_e( 'Run Summary', 'smart-search-chips' ); ?></strong>
                                    <div id="ssc-ai-rest-output"><?php esc_html_e( 'Generate the prompt to inspect the current batch, write mode, and output target.', 'smart-search-chips' ); ?></div>
                                </div>
                                <div id="ssc-ai-prompt-panel" class="ssc-ai-panel">
                                    <strong><?php esc_html_e( 'Generated Prompt', 'smart-search-chips' ); ?></strong>
                                    <textarea id="ssc-ai-prompt-output" class="large-text code" rows="18" readonly><?php echo esc_textarea( __( 'Generate the prompt to see the external-agent instructions here.', 'smart-search-chips' ) ); ?></textarea>
                                    <p style="margin-top:10px;">
                                        <button type="button" class="button button-secondary" id="ssc-ai-copy-prompt-panel"><?php esc_html_e( 'Copy Prompt', 'smart-search-chips' ); ?></button>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <details class="ssc-settings-card ssc-settings-card--collapsible">
                            <summary class="ssc-settings-card__summary">
                                <span class="ssc-settings-card__summary-title"><?php esc_html_e( 'Advanced', 'smart-search-chips' ); ?></span>
                            </summary>
                            <div class="ssc-settings-card__body">
                                <p class="description"><?php esc_html_e( 'Only change these if you already know your field names or need to integrate with an existing setup.', 'smart-search-chips' ); ?></p>
                                <table class="form-table">
                                    <?php if ( $is_pro ) : ?>
                                    <tr>
                                        <th><?php esc_html_e( 'Custom Tag Field', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="sschp_tag_fields" value="<?php echo esc_attr( get_option( 'sschp_tag_fields', '' ) ); ?>" class="regular-text" />
                                            <p class="description"><?php esc_html_e( 'Optional existing meta key that already stores your semicolon-separated search tags, for example `title_info`.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Extra Tag Field', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="sschp_extra_fields" value="<?php echo esc_attr( get_option( 'sschp_extra_fields', '' ) ); ?>" class="regular-text" />
                                            <p class="description"><?php esc_html_e( 'Optional extra meta key with comma-separated helper tags. These values are merged with the primary tag source.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <?php else : ?>
                                    <tr>
                                        <th><?php esc_html_e( 'Custom Fields', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <p class="description"><?php esc_html_e( 'Pro can map an existing custom tag field if you already maintain labeled products elsewhere.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ( $is_pro ) : ?>
                                    <tr>
                                        <th><?php esc_html_e( 'Override AJAX Action', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="sschp_override_ajax" value="<?php echo esc_attr( get_option( 'sschp_override_ajax', '' ) ); ?>" class="regular-text" />
                                            <p class="description"><?php esc_html_e( 'Pro advanced integration only. Use your theme\'s existing AJAX search action name (for example `search_processing`) if you need Smart Search Chips to hook into it.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Keyword Field Name', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="sschp_ajax_keyword_field" value="<?php echo esc_attr( get_option( 'sschp_ajax_keyword_field', 'keyword' ) ); ?>" class="regular-text" />
                                            <p class="description"><?php esc_html_e( 'The request field that contains the typed search term. Your theme uses `keyword`; other common values are `s` and `query`.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Result Container Selector', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="sschp_result_container_selector" value="<?php echo esc_attr( get_option( 'sschp_result_container_selector', '.js-ssc-results, .js-search__results, .ssc-search__results' ) ); ?>" class="regular-text" />
                                            <p class="description"><?php esc_html_e( 'CSS selector for the theme element that receives AJAX results, for example `.js-search__results`.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Result Image Fit', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <?php $image_fit = $this->sanitize_result_image_fit( get_option( 'sschp_result_image_fit', 'contain' ) ); ?>
                                            <select name="sschp_result_image_fit">
                                                <option value="contain" <?php selected( $image_fit, 'contain' ); ?>><?php esc_html_e( 'Contain', 'smart-search-chips' ); ?></option>
                                                <option value="cover" <?php selected( $image_fit, 'cover' ); ?>><?php esc_html_e( 'Cover', 'smart-search-chips' ); ?></option>
                                            </select>
                                            <p class="description"><?php esc_html_e( 'Contain keeps full product images visible. Cover crops images to fill the square.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Result Image Size', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="sschp_result_image_size" value="<?php echo esc_attr( get_option( 'sschp_result_image_size', 'medium' ) ); ?>" class="regular-text" />
                                            <p class="description"><?php esc_html_e( 'WordPress image size used for AJAX results. Common values: `thumbnail`, `medium`, `large`, `full`. Developers can override this with the `sschp_result_image_size` filter.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <?php else : ?>
                                    <tr>
                                        <th><?php esc_html_e( 'Override AJAX Action', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <p class="description"><?php esc_html_e( 'Pro can hook into an existing theme AJAX action when you need to piggyback on a custom live-search implementation. Standard shortcode AJAX search and loop chips still work in Free.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </details>

                        <div class="ssc-settings-card ssc-settings-card--danger">
                            <h2><?php esc_html_e( 'Data Cleanup', 'smart-search-chips' ); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e( 'Delete on Uninstall', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="hidden" name="sschp_delete_data_on_uninstall" value="0" />
                                            <input type="checkbox" name="sschp_delete_data_on_uninstall" value="1" <?php checked( $delete_on_uninstall, '1' ); ?> />
                                            <?php esc_html_e( 'Delete all plugin data when the plugin is uninstalled', 'smart-search-chips' ); ?>
                                        </label>
                                        <p class="description"><?php esc_html_e( 'By default, uninstall keeps your settings and analytics data. Turn this on only if you want uninstall to remove built-in `sschp_tags` meta, plugin settings, and the analytics table.', 'smart-search-chips' ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Delete Data Now', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <p class="description"><?php esc_html_e( 'Use this if you want to wipe Smart Search Chips data without uninstalling the plugin. Export analytics CSV first if you want to keep a copy.', 'smart-search-chips' ); ?></p>
                                        <button
                                            type="submit"
                                            form="ssc-delete-data-form"
                                            class="button button-secondary"
                                            id="ssc-delete-data-btn"
                                            data-confirm="<?php echo esc_attr__( 'Delete Smart Search Chips settings, built-in chip meta, and stored analytics events now? This cannot be undone.', 'smart-search-chips' ); ?>"
                                        ><?php esc_html_e( 'Delete All Plugin Data Now', 'smart-search-chips' ); ?></button>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <?php submit_button(); ?>
                    </div>

                    <!-- RIGHT: Sticky Preview -->
                    <div id="ssc-settings-right">
                        <div id="ssc-preview-panel">
                            <h3><?php esc_html_e( 'Style Preview', 'smart-search-chips' ); ?></h3>
                            <div id="ssc-preview-search" style="margin-bottom:12px;">
                                <input type="text" value="example query" disabled style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;background:#f9f9f9;color:#999;" />
                            </div>
                            <div id="ssc-preview-chips" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px;">
                                <!-- Chips injected by JS -->
                            </div>
                            <div id="ssc-preview-results">
                                <div class="ssc-preview-result"><div class="ssc-preview-thumb"></div><span>Product result 1</span></div>
                                <div class="ssc-preview-result"><div class="ssc-preview-thumb"></div><span>Product result 2</span></div>
                                <div class="ssc-preview-result"><div class="ssc-preview-thumb"></div><span>Product result 3</span></div>
                            </div>
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
                <a href="mailto:[redacted]?subject=Smart%20Search%20Chips%20Feedback"><?php esc_html_e( 'Send feedback', 'smart-search-chips' ); ?> &rarr;</a>
            </p>
        </div>
        <?php
    }

    /**
     * Handle the manual data deletion action.
     *
     * @return void
     */
    public function handle_delete_data_now() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to delete plugin data.', 'smart-search-chips' ) );
        }

        check_admin_referer( 'sschp_delete_plugin_data_now' );

        sschp_delete_plugin_data( false );
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

    // ─── Helper renderers ────────────────────────────────────────────

    private function render_post_types() {
        $selected = get_option( 'sschp_post_types', array( 'post' ) );
        if ( ! is_array( $selected ) ) {
            $selected = array( $selected );
        }

        $post_types = get_post_types( array( 'public' => true ), 'objects' );

        foreach ( $post_types as $pt ) {
            if ( $pt->name === 'attachment' ) continue;
            printf(
                '<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="sschp_post_types[]" value="%s" %s /> %s</label>',
                esc_attr( $pt->name ),
                checked( in_array( $pt->name, $selected, true ), true, false ),
                esc_html( $pt->label )
            );
        }
    }

    /**
     * Return the controlled list of AI target field options.
     *
     * @return array
     */
    private function get_ai_target_field_options() {
        $options = array(
            'built_in' => __( 'Built-in Search Chips Tags (sschp_tags)', 'smart-search-chips' ),
        );

        $custom_tag_field = trim( (string) get_option( 'sschp_tag_fields', '' ) );
        if ( $custom_tag_field !== '' ) {
            $options['mapped_primary'] = sprintf(
                /* translators: %s: configured custom field name */
                __( 'Custom tag field (%s)', 'smart-search-chips' ),
                $custom_tag_field
            );
        }

        return $options;
    }

    /**
     * Render the dedicated analytics screen.
     */
    public function render_analytics_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $is_pro       = false;
        $analytics_on = get_option( 'sschp_analytics_enabled', '0' );
        $range_args   = $this->get_admin_date_range_args();
        $range     = array( 'from' => '', 'to' => '' );
        $analytics = null;
        $events    = array();

        if ( sschp_fs()->can_use_premium_code__premium_only() ) {
            $is_pro    = SSCHP_License::is_pro();
            $range     = SSCHP_Analytics::get_date_range( $range_args );
            $analytics = $is_pro && $analytics_on === '1' ? SSCHP_Analytics::get_dashboard_data( $range ) : null;
            $events    = $is_pro && $analytics_on === '1' ? SSCHP_Analytics::get_recent_events( array_merge( $range, array( 'limit' => 150 ) ) ) : array();
        }
        $export_url   = wp_nonce_url(
            add_query_arg(
                array(
                    'action' => 'sschp_export_analytics',
                    'from'   => $range['from'],
                    'to'     => $range['to'],
                ),
                admin_url( 'admin-post.php' )
            ),
            'sschp_export_analytics'
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Search Chips Analytics', 'smart-search-chips' ); ?></h1>
            <p><a href="<?php echo esc_url( admin_url( 'options-general.php?page=smart-search-chips' ) ); ?>">&larr; <?php esc_html_e( 'Back to Settings', 'smart-search-chips' ); ?></a></p>

            <?php if ( ! $is_pro ) : ?>
                <div class="notice notice-info"><p><?php esc_html_e( 'Analytics is available in Pro.', 'smart-search-chips' ); ?></p></div>
                </div>
                <?php return; ?>
            <?php endif; ?>

            <?php if ( $analytics_on !== '1' ) : ?>
                <div class="notice notice-info"><p><?php esc_html_e( 'Enable analytics in the main Search Chips settings page to start collecting data.', 'smart-search-chips' ); ?></p></div>
                </div>
                <?php return; ?>
            <?php endif; ?>
            <form method="get" action="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>" class="ssc-analytics-toolbar">
                <input type="hidden" name="page" value="smart-search-chips-analytics" />
                <label>
                    <span><?php esc_html_e( 'From', 'smart-search-chips' ); ?></span>
                    <input type="date" name="from" value="<?php echo esc_attr( $range['from'] ); ?>" />
                </label>
                <label>
                    <span><?php esc_html_e( 'To', 'smart-search-chips' ); ?></span>
                    <input type="date" name="to" value="<?php echo esc_attr( $range['to'] ); ?>" />
                </label>
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Apply Range', 'smart-search-chips' ); ?></button>
                <a class="button" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Export CSV', 'smart-search-chips' ); ?></a>
            </form>

            <div class="ssc-analytics-dashboard">
                <div class="ssc-analytics-stats">
                    <div class="ssc-analytics-stat">
                        <strong><?php echo esc_html( number_format_i18n( $analytics['summary']['total_searches'] ) ); ?></strong>
                        <span><?php esc_html_e( 'Searches', 'smart-search-chips' ); ?></span>
                    </div>
                    <div class="ssc-analytics-stat">
                        <strong><?php echo esc_html( number_format_i18n( $analytics['summary']['chip_clicks'] ) ); ?></strong>
                        <span><?php esc_html_e( 'Chip Clicks', 'smart-search-chips' ); ?></span>
                    </div>
                    <div class="ssc-analytics-stat">
                        <strong><?php echo esc_html( number_format_i18n( $analytics['summary']['unique_visitors'] ) ); ?></strong>
                        <span><?php esc_html_e( 'Unique Visitors', 'smart-search-chips' ); ?></span>
                    </div>
                    <div class="ssc-analytics-stat">
                        <strong><?php echo esc_html( number_format_i18n( $analytics['summary']['zero_result_search'] ) ); ?></strong>
                        <span><?php esc_html_e( 'Zero-Result Searches', 'smart-search-chips' ); ?></span>
                    </div>
                    <div class="ssc-analytics-stat">
                        <strong><?php echo esc_html( number_format_i18n( $analytics['summary']['search_to_click_rate'], 1 ) . '%' ); ?></strong>
                        <span><?php esc_html_e( 'Search To Click Rate', 'smart-search-chips' ); ?></span>
                    </div>
                </div>

                <div class="ssc-analytics-charts">
                    <div class="ssc-analytics-panel">
                        <h3><?php esc_html_e( 'Search Activity Over Time', 'smart-search-chips' ); ?></h3>
                        <div class="ssc-chart">
                            <?php $this->render_analytics_chart( $analytics['timeseries'], array( 'searches' => '#1d4ed8', 'chip_clicks' => '#d97706' ) ); ?>
                            <div class="ssc-chart-meta">
                                <span class="ssc-chart-searches"><?php esc_html_e( 'Searches', 'smart-search-chips' ); ?></span>
                                <span class="ssc-chart-clicks"><?php esc_html_e( 'Chip Clicks', 'smart-search-chips' ); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="ssc-analytics-panel">
                        <h3><?php esc_html_e( 'Visitors And Zero-Result Searches', 'smart-search-chips' ); ?></h3>
                        <div class="ssc-chart">
                            <?php $this->render_analytics_chart( $analytics['timeseries'], array( 'unique_visitors' => '#059669', 'zero_results' => '#dc2626' ) ); ?>
                            <div class="ssc-chart-meta">
                                <span class="ssc-chart-visitors"><?php esc_html_e( 'Unique Visitors', 'smart-search-chips' ); ?></span>
                                <span class="ssc-chart-zero"><?php esc_html_e( 'Zero-Result Searches', 'smart-search-chips' ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ssc-analytics-panels ssc-analytics-panels--quad">
                    <div class="ssc-analytics-panel">
                        <h3><?php esc_html_e( 'Top Clicked Chips', 'smart-search-chips' ); ?></h3>
                        <?php $this->render_analytics_table( $analytics['top_chips'], array( 'chip_label' => __( 'Chip', 'smart-search-chips' ), 'clicks' => __( 'Clicks', 'smart-search-chips' ), 'visitors' => __( 'Visitors', 'smart-search-chips' ) ) ); ?>
                    </div>
                    <div class="ssc-analytics-panel">
                        <h3><?php esc_html_e( 'Top Chips By Click-Through Rate', 'smart-search-chips' ); ?></h3>
                        <?php $this->render_analytics_table( $analytics['top_chip_ctr'], array( 'chip_label' => __( 'Chip', 'smart-search-chips' ), 'impressions' => __( 'Impressions', 'smart-search-chips' ), 'clicks' => __( 'Clicks', 'smart-search-chips' ), 'click_through_rate' => __( 'CTR', 'smart-search-chips' ) ) ); ?>
                    </div>
                    <div class="ssc-analytics-panel">
                        <h3><?php esc_html_e( 'Top Search Queries', 'smart-search-chips' ); ?></h3>
                        <?php $this->render_analytics_table( $analytics['top_queries'], array( 'search_query' => __( 'Query', 'smart-search-chips' ), 'searches' => __( 'Searches', 'smart-search-chips' ), 'chip_clicks' => __( 'Chip Clicks', 'smart-search-chips' ), 'click_rate' => __( 'Click Rate', 'smart-search-chips' ), 'avg_results' => __( 'Avg. Results', 'smart-search-chips' ) ) ); ?>
                    </div>
                    <div class="ssc-analytics-panel">
                        <h3><?php esc_html_e( 'Top Zero-Result Queries Over Time', 'smart-search-chips' ); ?></h3>
                        <?php $this->render_analytics_table( $analytics['zero_queries'], array( 'search_query' => __( 'Query', 'smart-search-chips' ), 'misses' => __( 'Misses', 'smart-search-chips' ), 'first_seen' => __( 'First Seen', 'smart-search-chips' ), 'last_seen' => __( 'Last Seen', 'smart-search-chips' ) ) ); ?>
                    </div>
                </div>

                <div class="ssc-analytics-panel">
                    <h3><?php esc_html_e( 'Recent Events', 'smart-search-chips' ); ?></h3>
                    <?php $this->render_analytics_table( $events, array( 'created_at' => __( 'Date', 'smart-search-chips' ), 'event_type' => __( 'Event', 'smart-search-chips' ), 'language_code' => __( 'Lang', 'smart-search-chips' ), 'search_query' => __( 'Query', 'smart-search-chips' ), 'chip_label' => __( 'Chip', 'smart-search-chips' ), 'result_count' => __( 'Results', 'smart-search-chips' ) ) ); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Read the optional analytics date filters from the admin request.
     *
     * @return array
     */
    private function get_admin_date_range_args() {
        return array(
            'from' => $this->get_admin_date_query_var( 'from' ),
            'to'   => $this->get_admin_date_query_var( 'to' ),
        );
    }

    /**
     * Read and sanitize a date filter query variable.
     *
     * @param string $key Query variable name.
     * @return string
     */
    private function get_admin_date_query_var( $key ) {
        $value = filter_input( INPUT_GET, $key, FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        if ( null === $value || false === $value ) {
            return '';
        }

        return sanitize_text_field( wp_unslash( $value ) );
    }

    /**
     * Render a simple analytics table.
     *
     * @param array $rows    Table rows.
     * @param array $columns Column map of key => label.
     */
    private function render_analytics_table( $rows, $columns ) {
        if ( empty( $rows ) ) {
            echo '<div class="ssc-analytics-empty">' . esc_html__( 'No analytics data yet.', 'smart-search-chips' ) . '</div>';
            return;
        }

        echo '<table>';
        echo '<thead><tr>';
        foreach ( $columns as $label ) {
            echo '<th>' . esc_html( $label ) . '</th>';
        }
        echo '</tr></thead><tbody>';

        foreach ( $rows as $row ) {
            echo '<tr>';
            foreach ( array_keys( $columns ) as $key ) {
                $value = isset( $row[ $key ] ) ? $row[ $key ] : '';
                if ( $key === 'avg_results' ) {
                    $value = number_format_i18n( (float) $value, 1 );
                } elseif ( in_array( $key, array( 'click_rate', 'click_through_rate', 'search_to_click_rate' ), true ) ) {
                    $value = number_format_i18n( (float) $value, 1 ) . '%';
                } elseif ( in_array( $key, array( 'created_at', 'first_seen', 'last_seen' ), true ) && $value ) {
                    $value = get_date_from_gmt( $value, 'Y-m-d H:i' );
                } elseif ( is_numeric( $value ) ) {
                    $value = number_format_i18n( $value );
                }

                echo '<td>' . esc_html( $value ) . '</td>';
            }
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Render a simple inline SVG line chart for analytics series.
     *
     * @param array $rows    Daily time-series rows.
     * @param array $metrics Metric map of key => color.
     */
    private function render_analytics_chart( $rows, $metrics ) {
        if ( empty( $rows ) ) {
            echo '<div class="ssc-analytics-empty">' . esc_html__( 'No analytics data yet.', 'smart-search-chips' ) . '</div>';
            return;
        }

        $width      = 720;
        $height     = 220;
        $left_pad   = 36;
        $right_pad  = 12;
        $top_pad    = 16;
        $bottom_pad = 34;
        $plot_w     = $width - $left_pad - $right_pad;
        $plot_h     = $height - $top_pad - $bottom_pad;
        $point_count= count( $rows );
        $max_value  = 0;

        foreach ( $rows as $row ) {
            foreach ( array_keys( $metrics ) as $metric ) {
                $max_value = max( $max_value, isset( $row[ $metric ] ) ? (int) $row[ $metric ] : 0 );
            }
        }

        $max_value = max( 1, $max_value );
        $grid_vals = array( 0, (int) ceil( $max_value / 2 ), $max_value );

        echo '<svg viewBox="0 0 ' . esc_attr( $width ) . ' ' . esc_attr( $height ) . '" role="img" aria-label="' . esc_attr__( 'Analytics chart', 'smart-search-chips' ) . '">';

        foreach ( $grid_vals as $grid_value ) {
            $y = $top_pad + $plot_h - ( $grid_value / $max_value ) * $plot_h;
            echo '<line x1="' . esc_attr( $left_pad ) . '" y1="' . esc_attr( round( $y, 2 ) ) . '" x2="' . esc_attr( $left_pad + $plot_w ) . '" y2="' . esc_attr( round( $y, 2 ) ) . '" stroke="#e6e9ed" stroke-width="1" />';
            echo '<text x="' . esc_attr( $left_pad - 8 ) . '" y="' . esc_attr( round( $y + 4, 2 ) ) . '" text-anchor="end" font-size="11" fill="#6b7280">' . esc_html( number_format_i18n( $grid_value ) ) . '</text>';
        }

        foreach ( $rows as $index => $row ) {
            if ( $point_count === 1 ) {
                $x = $left_pad + ( $plot_w / 2 );
            } else {
                $x = $left_pad + ( $plot_w * $index / ( $point_count - 1 ) );
            }

            if ( $index === 0 || $index === $point_count - 1 || $index === (int) floor( ( $point_count - 1 ) / 2 ) ) {
                echo '<text x="' . esc_attr( round( $x, 2 ) ) . '" y="' . esc_attr( $top_pad + $plot_h + 20 ) . '" text-anchor="middle" font-size="11" fill="#6b7280">' . esc_html( $row['label'] ) . '</text>';
            }
        }

        foreach ( $metrics as $metric => $color ) {
            $path = '';

            foreach ( $rows as $index => $row ) {
                $value = isset( $row[ $metric ] ) ? (int) $row[ $metric ] : 0;
                $x     = $point_count === 1 ? $left_pad + ( $plot_w / 2 ) : $left_pad + ( $plot_w * $index / ( $point_count - 1 ) );
                $y     = $top_pad + $plot_h - ( $value / $max_value ) * $plot_h;
                $path .= ( $index === 0 ? 'M ' : ' L ' ) . round( $x, 2 ) . ' ' . round( $y, 2 );
            }

            echo '<path d="' . esc_attr( $path ) . '" fill="none" stroke="' . esc_attr( $color ) . '" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />';

            foreach ( $rows as $index => $row ) {
                $value = isset( $row[ $metric ] ) ? (int) $row[ $metric ] : 0;
                $x     = $point_count === 1 ? $left_pad + ( $plot_w / 2 ) : $left_pad + ( $plot_w * $index / ( $point_count - 1 ) );
                $y     = $top_pad + $plot_h - ( $value / $max_value ) * $plot_h;
                echo '<circle cx="' . esc_attr( round( $x, 2 ) ) . '" cy="' . esc_attr( round( $y, 2 ) ) . '" r="3.5" fill="' . esc_attr( $color ) . '">';
                echo '<title>' . esc_html( $row['label'] . ': ' . $value ) . '</title>';
                echo '</circle>';
            }
        }

        echo '</svg>';
    }

    /**
     * Sanitize AI target field option.
     *
     * @param string $value Raw target option.
     * @return string
     */
    public function sanitize_ai_target_field( $value ) {
        $custom_tag_field = trim( (string) get_option( 'sschp_tag_fields', '' ) );

        if ( $value === 'mapped_primary' && $custom_tag_field !== '' ) {
            return 'mapped_primary';
        }

        return 'built_in';
    }

    /**
     * Sanitize checkbox-style settings.
     *
     * @param mixed $value Raw checkbox value.
     * @return string
     */
    public function sanitize_checkbox( $value ) {
        return ! empty( $value ) ? '1' : '0';
    }

    public function sanitize_result_image_fit( $value ) {
        $value = sanitize_key( $value );
        return in_array( $value, array( 'contain', 'cover' ), true ) ? $value : 'contain';
    }

    public function sanitize_result_image_size( $value ) {
        $value = sanitize_key( $value );
        return '' !== $value ? $value : 'medium';
    }

    /**
     * Sanitize the mapped custom tag field.
     *
     * @param string $value Raw value.
     * @return string
     */
    public function sanitize_custom_tag_field( $value ) {
        return $this->sanitize_meta_mapping_field( $value, 'tag' );
    }

    /**
     * Sanitize the mapped extra tag field.
     *
     * @param string $value Raw value.
     * @return string
     */
    public function sanitize_extra_tag_field( $value ) {
        return $this->sanitize_meta_mapping_field( $value, 'extra' );
    }

    /**
     * Sanitize chip radius value.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public function sanitize_chip_radius( $value ) {
        return $this->sanitize_numeric_setting( $value, 16, 0, 50 );
    }

    /**
     * Sanitize chip font size value.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public function sanitize_chip_font_size( $value ) {
        return $this->sanitize_numeric_setting( $value, 13, 8, 24 );
    }

    /**
     * Sanitize chip horizontal padding value.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public function sanitize_chip_padding_x( $value ) {
        return $this->sanitize_numeric_setting( $value, 12, 0, 40 );
    }

    /**
     * Sanitize chip vertical padding value.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public function sanitize_chip_padding_y( $value ) {
        return $this->sanitize_numeric_setting( $value, 4, 0, 24 );
    }

    /**
     * Sanitize multi-line chip rule settings.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public function sanitize_chip_rules_text( $value ) {
        $value = sanitize_textarea_field( (string) $value );
        $lines = array();

        foreach ( preg_split( '/\r\n|\r|\n/', $value ) as $line ) {
            $line = trim( $line );
            if ( '' !== $line ) {
                $lines[] = $line;
            }
        }

        return implode( "\n", $lines );
    }

    /**
     * Normalize the numeric design controls for display.
     *
     * @param string $option_name Option name.
     * @param int    $default     Default value.
     * @param int    $min         Minimum value.
     * @return int
     */
    private function get_numeric_option_value( $option_name, $default, $min = 0 ) {
        $value = get_option( $option_name, $default );

        if ( '' === $value || ! is_numeric( $value ) ) {
            return (int) $default;
        }

        return max( (int) $min, (int) $value );
    }

    /**
     * Sanitize a numeric design setting.
     *
     * @param mixed $value   Raw value.
     * @param int   $default Default value.
     * @param int   $min     Minimum value.
     * @param int   $max     Maximum value.
     * @return int
     */
    private function sanitize_numeric_setting( $value, $default, $min, $max ) {
        if ( '' === $value || ! is_numeric( $value ) ) {
            return (int) $default;
        }

        $value = (int) $value;
        $value = max( $min, $value );
        $value = min( $max, $value );

        return $value;
    }

    /**
     * Sanitize an advanced mapped meta key and reject collisions.
     *
     * @param string $value Field value.
     * @param string $role  Mapping role.
     * @return string
     */
    private function sanitize_meta_mapping_field( $value, $role ) {
        $value = trim( sanitize_key( (string) $value ) );

        if ( '' === $value ) {
            return '';
        }

        $posted_primary = isset( $_POST['sschp_tag_fields'] ) ? trim( sanitize_key( wp_unslash( $_POST['sschp_tag_fields'] ) ) ) : trim( (string) get_option( 'sschp_tag_fields', '' ) );
        $posted_extra   = isset( $_POST['sschp_extra_fields'] ) ? trim( sanitize_key( wp_unslash( $_POST['sschp_extra_fields'] ) ) ) : trim( (string) get_option( 'sschp_extra_fields', '' ) );
        $other_value    = ( 'tag' === $role ) ? $posted_extra : $posted_primary;

        if ( $value === SSCHP_Meta_Box::META_KEY ) {
            add_settings_error(
                'smart-search-chips',
                'sschp_reserved_meta_key',
                __( 'The built-in field key `sschp_tags` is reserved by Smart Search Chips. Choose a different custom field key.', 'smart-search-chips' ),
                'error'
            );
            return '';
        }

        if ( $value === $other_value ) {
            add_settings_error(
                'smart-search-chips',
                'sschp_duplicate_meta_key',
                __( 'Custom Tag Field and Extra Tag Field must be different meta keys.', 'smart-search-chips' ),
                'error'
            );
            return '';
        }

        return $value;
    }
}
