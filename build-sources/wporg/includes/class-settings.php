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
        add_action( 'wp_ajax_sschp_admin_test_search', array( $this, 'handle_admin_test_search' ) );
        add_action( 'wp_ajax_sschp_search_index_control', array( $this, 'handle_search_index_control' ) );
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
        $links[] = '<a href="' . esc_url( SSCHP_Edition::get_product_url() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Smart Search Chips Pro', 'smart-search-chips' ) . '</a>';
        return $links;
    }

    public function register_settings() {
        register_setting( 'smart-search-chips', 'sschp_post_types', array( 'sanitize_callback' => array( $this, 'sanitize_post_types' ) ) );
        register_setting( 'smart-search-chips', 'sschp_override_ajax', array( 'sanitize_callback' => 'sanitize_key' ) );
        register_setting( 'smart-search-chips', 'sschp_ajax_keyword_field', array( 'sanitize_callback' => 'sanitize_key' ) );
        register_setting( 'smart-search-chips', 'sschp_result_container_selector', array( 'sanitize_callback' => array( $this, 'sanitize_result_container_selector' ) ) );
        register_setting( 'smart-search-chips', 'sschp_search_input_selector', array( 'sanitize_callback' => array( $this, 'sanitize_search_input_selector' ) ) );
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
        register_setting( 'smart-search-chips', 'sschp_debounce_ms', array( 'sanitize_callback' => array( $this, 'sanitize_debounce_ms' ) ) );
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
        wp_localize_script( 'smart-search-chips-settings', 'sschpSettingsDiagnostics', array(
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'testSearch'  => array(
                'nonce' => wp_create_nonce( 'sschp_admin_test_search' ),
            ),
            'indexControl' => array(
                'nonce'  => wp_create_nonce( 'sschp_search_index_control' ),
                'labels' => array(
                    'not_started' => __( 'Not started', 'smart-search-chips' ),
                    'queued'      => __( 'Queued', 'smart-search-chips' ),
                    'processing'  => __( 'Processing', 'smart-search-chips' ),
                    'paused'      => __( 'Paused', 'smart-search-chips' ),
                    'complete'    => __( 'Ready', 'smart-search-chips' ),
                    'empty'       => __( 'Empty', 'smart-search-chips' ),
                ),
            ),
            'diagnostics' => $this->get_theme_compatibility_diagnostics(),
            'strings'     => array(
                'pass'                        => __( 'Pass', 'smart-search-chips' ),
                'warning'                     => __( 'Warning', 'smart-search-chips' ),
                'runningSearch'               => __( 'Running test search...', 'smart-search-chips' ),
                'searchFailed'                => __( 'The test search could not run.', 'smart-search-chips' ),
                'queryTooShort'               => __( 'Enter at least two characters to run a test search.', 'smart-search-chips' ),
                'path'                        => __( 'Path', 'smart-search-chips' ),
                'results'                     => __( 'Results', 'smart-search-chips' ),
                'taggedResults'               => __( 'Tagged results', 'smart-search-chips' ),
                'generatedChips'              => __( 'Generated chips', 'smart-search-chips' ),
                'firstResults'                => __( 'First results', 'smart-search-chips' ),
                'explanations'                => __( 'Explanations', 'smart-search-chips' ),
                'warnings'                    => __( 'Warnings', 'smart-search-chips' ),
                'suggestedFix'                => __( 'Suggested fix', 'smart-search-chips' ),
                'noChips'                     => __( 'No chips generated.', 'smart-search-chips' ),
                'noResults'                   => __( 'No preview results returned.', 'smart-search-chips' ),
                'indexRunning'                => __( 'Updating local index...', 'smart-search-chips' ),
                'indexActionFailed'           => __( 'The local index action could not run.', 'smart-search-chips' ),
                'notYetCompleted'             => __( 'Not yet completed', 'smart-search-chips' ),
                'selectorInvalid'             => __( 'This selector is invalid and should be corrected before testing theme integration.', 'smart-search-chips' ),
                'selectorValid'               => __( 'Selector syntax is valid.', 'smart-search-chips' ),
                'defaultSelectorsValid'       => __( 'Built-in fallback selectors are valid.', 'smart-search-chips' ),
                'actionConfigured'            => __( 'Custom AJAX action is configured.', 'smart-search-chips' ),
                'actionDefault'               => __( 'Using built-in action.', 'smart-search-chips' ),
                'keywordSane'                 => __( 'Keyword field looks valid.', 'smart-search-chips' ),
                'keywordInvalid'              => __( 'Keyword field should contain only letters, numbers, underscores, or hyphens.', 'smart-search-chips' ),
                'inputMatches'                => __( 'Matching search inputs found on this page:', 'smart-search-chips' ),
                'resultMatches'               => __( 'Matching result containers found on this page:', 'smart-search-chips' ),
                'resultMissingRecommendation' => __( 'No result container found. Use [sschp_search] or configure the result container selector.', 'smart-search-chips' ),
                'copied'                      => __( 'Copied.', 'smart-search-chips' ),
                'copyFailed'                  => __( 'Copy failed. Copy the snippet manually.', 'smart-search-chips' ),
            ),
        ) );
    }

    /**
     * Build diagnostics for the theme compatibility settings card.
     *
     * @return array
     */
    public function get_theme_compatibility_diagnostics() {
        $input_selector  = trim( (string) get_option( 'sschp_search_input_selector', $this->get_default_search_input_selector() ) );
        $result_selector = trim( (string) get_option( 'sschp_result_container_selector', $this->get_default_result_container_selector() ) );
        $ajax_action     = sanitize_key( get_option( 'sschp_override_ajax', '' ) );
        $keyword_field   = trim( (string) get_option( 'sschp_ajax_keyword_field', 'keyword' ) );

        if ( '' === $input_selector ) {
            $input_selector = $this->get_default_search_input_selector();
        }

        if ( '' === $result_selector ) {
            $result_selector = $this->get_default_result_container_selector();
        }

        $keyword_sane = '' !== $keyword_field && sanitize_key( $keyword_field ) === $keyword_field;

        return array(
            'inputSelector'        => $input_selector,
            'resultSelector'       => $result_selector,
            'ajaxAction'           => $ajax_action,
            'ajaxActionLabel'      => '' !== $ajax_action ? $ajax_action : 'sschp_search',
            'keywordField'         => $keyword_field,
            'inputSelectorValid'   => $this->looks_like_valid_css_selector( $input_selector ),
            'resultSelectorValid'  => $this->looks_like_valid_css_selector( $result_selector ),
            'defaultInputValid'    => $this->looks_like_valid_css_selector( $this->get_default_search_input_selector() ),
            'defaultResultValid'   => $this->looks_like_valid_css_selector( $this->get_default_result_container_selector() ),
            'ajaxActionConfigured' => '' !== $ajax_action,
            'keywordFieldSane'     => $keyword_sane,
            'woocommerceActive'    => class_exists( 'WooCommerce' ),
            'relevanssiActive'     => function_exists( 'relevanssi_do_query' ),
            'shortcodeAvailable'   => ! function_exists( 'shortcode_exists' ) || shortcode_exists( 'sschp_search' ),
            'debugUrl'             => add_query_arg( 'sschp_debug', '1', home_url( '/' ) ),
            'shortcode'            => '[sschp_search]',
        );
    }

    /**
     * Build first-run setup guidance from the current site state.
     *
     * @return array
     */
    public function get_setup_wizard_data() {
        $selected_post_types = $this->sanitize_post_types( get_option( 'sschp_post_types', array( 'post' ) ) );
        $post_type_objects   = get_post_types( array( 'public' => true ), 'objects' );
        $selected_labels     = array();

        foreach ( $selected_post_types as $post_type ) {
            if ( isset( $post_type_objects[ $post_type ] ) ) {
                $selected_labels[] = $post_type_objects[ $post_type ]->label;
            } else {
                $selected_labels[] = $post_type;
            }
        }

        $diagnostics = $this->get_theme_compatibility_diagnostics();

        return array(
            'woocommerceActive'       => class_exists( 'WooCommerce' ),
            'selectedPostTypes'       => $selected_post_types,
            'selectedPostTypeLabels'  => $selected_labels,
            'shortcode'               => '[sschp_search]',
            'blockPathLabel'          => __( 'Find and add the Smart Search Chips Search block from the block inserter.', 'smart-search-chips' ),
            'shortcodeBlockLabel'     => __( 'Search for WordPress\'s built-in Shortcode block and paste [sschp_search]. If your editor or page builder hides that block, paste the shortcode into its shortcode or widget module instead.', 'smart-search-chips' ),
            'guaranteedModeLabel'     => __( 'Guaranteed Search Mode uses the Smart Search Chips endpoint and does not depend on theme AJAX.', 'smart-search-chips' ),
            'themeEnhancementStatus'  => $diagnostics['ajaxActionConfigured'] ? __( 'Configured', 'smart-search-chips' ) : __( 'Passive by default', 'smart-search-chips' ),
            'themeEnhancementMessage' => $diagnostics['ajaxActionConfigured']
                ? __( 'Existing theme search enhancement is configured. Requests are only replaced when Smart Search Chips marks them as managed.', 'smart-search-chips' )
                : __( 'No existing theme AJAX action is configured. Theme search will not be intercepted; use the shortcode path below for a guaranteed working search.', 'smart-search-chips' ),
        );
    }

    /**
     * Render first-run setup guidance.
     */
    private function render_setup_wizard_card() {
        $setup       = $this->get_setup_wizard_data();
        $post_labels = ! empty( $setup['selectedPostTypeLabels'] ) ? implode( ', ', $setup['selectedPostTypeLabels'] ) : __( 'No public post types selected', 'smart-search-chips' );
        ?>
        <div class="ssc-settings-card ssc-settings-card--setup">
            <h2><?php esc_html_e( 'First-run Setup Wizard', 'smart-search-chips' ); ?></h2>
            <p class="description"><?php esc_html_e( 'For the most reliable setup, add the Smart Search Chips search block or shortcode to your search page.', 'smart-search-chips' ); ?></p>
            <p class="description"><?php esc_html_e( 'Existing theme search enhancement is automatic when compatible and configurable when needed.', 'smart-search-chips' ); ?></p>

            <div class="ssc-setup-grid">
                <div class="ssc-setup-step">
                    <strong><?php esc_html_e( '1. Site content', 'smart-search-chips' ); ?></strong>
                    <p><?php echo esc_html( $setup['woocommerceActive'] ? __( 'WooCommerce detected.', 'smart-search-chips' ) : __( 'WooCommerce not detected.', 'smart-search-chips' ) ); ?></p>
                    <p class="description">
                        <?php esc_html_e( 'Selected post types:', 'smart-search-chips' ); ?>
                        <code><?php echo esc_html( $post_labels ); ?></code>
                    </p>
                </div>

                <div class="ssc-setup-step">
                    <strong><?php esc_html_e( '2. Guaranteed Search Mode', 'smart-search-chips' ); ?></strong>
                    <p><?php echo esc_html( $setup['guaranteedModeLabel'] ); ?></p>
                    <label class="screen-reader-text" for="sschp-guaranteed-shortcode"><?php esc_html_e( 'Smart Search Chips shortcode', 'smart-search-chips' ); ?></label>
                    <div class="ssc-copy-row">
                        <input id="sschp-guaranteed-shortcode" type="text" class="regular-text code" value="<?php echo esc_attr( $setup['shortcode'] ); ?>" readonly />
                        <button type="button" class="button button-secondary" data-sschp-copy="<?php echo esc_attr( $setup['shortcode'] ); ?>"><?php esc_html_e( 'Copy shortcode', 'smart-search-chips' ); ?></button>
                    </div>
                    <div class="ssc-setup-paths" aria-label="<?php esc_attr_e( 'Guaranteed search setup paths', 'smart-search-chips' ); ?>">
                        <p>
                            <strong><?php esc_html_e( 'Block', 'smart-search-chips' ); ?></strong>
                            <span><?php echo esc_html( $setup['blockPathLabel'] ); ?></span>
                        </p>
                        <p>
                            <strong><?php esc_html_e( 'Shortcode fallback', 'smart-search-chips' ); ?></strong>
                            <span><?php echo esc_html( $setup['shortcodeBlockLabel'] ); ?></span>
                        </p>
                    </div>
                </div>

                <div class="ssc-setup-step">
                    <strong><?php esc_html_e( '3. Enhance Existing Theme Search', 'smart-search-chips' ); ?></strong>
                    <p>
                        <span class="ssc-badge ssc-badge--neutral"><?php echo esc_html( $setup['themeEnhancementStatus'] ); ?></span>
                    </p>
                    <p><?php echo esc_html( $setup['themeEnhancementMessage'] ); ?></p>
                    <p class="description"><?php esc_html_e( 'If detection fails, leave theme enhancement passive and use the guaranteed shortcode/block path.', 'smart-search-chips' ); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render local search index status.
     */
    private function render_search_index_card() {
        if ( ! class_exists( 'SSCHP_Search_Index' ) ) {
            return;
        }

        $progress = SSCHP_Search_Index::get_progress();
        $status   = isset( $progress['status'] ) ? sanitize_key( $progress['status'] ) : 'not_started';
        $labels   = array(
            'not_started' => __( 'Not started', 'smart-search-chips' ),
            'queued'      => __( 'Queued', 'smart-search-chips' ),
            'processing'  => __( 'Processing', 'smart-search-chips' ),
            'paused'      => __( 'Paused', 'smart-search-chips' ),
            'complete'    => __( 'Ready', 'smart-search-chips' ),
            'empty'       => __( 'Empty', 'smart-search-chips' ),
        );
        ?>
        <div class="ssc-settings-card ssc-settings-card--index">
            <h2><?php esc_html_e( 'Local Search Index', 'smart-search-chips' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Smart Search Chips stores a local index on this WordPress site so large catalogs can search and generate chips without sending content externally.', 'smart-search-chips' ); ?></p>
            <dl class="ssc-compat-config">
                <div>
                    <dt><?php esc_html_e( 'Status', 'smart-search-chips' ); ?></dt>
                    <dd><span class="ssc-badge ssc-badge--neutral" data-sschp-index-status><?php echo esc_html( isset( $labels[ $status ] ) ? $labels[ $status ] : $status ); ?></span></dd>
                </div>
                <div>
                    <dt><?php esc_html_e( 'Progress', 'smart-search-chips' ); ?></dt>
                    <dd data-sschp-index-progress><?php echo esc_html( sprintf( '%1$d%% (%2$d/%3$d)', (int) $progress['percent'], (int) $progress['processed'], (int) $progress['total'] ) ); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e( 'Indexed rows', 'smart-search-chips' ); ?></dt>
                    <dd data-sschp-index-rows><?php echo esc_html( (string) (int) $progress['rows'] ); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e( 'Last completed', 'smart-search-chips' ); ?></dt>
                    <dd data-sschp-index-updated><?php echo esc_html( '' !== $progress['updatedAt'] ? $progress['updatedAt'] : __( 'Not yet completed', 'smart-search-chips' ) ); ?></dd>
                </div>
            </dl>
            <p class="ssc-index-actions">
                <button type="button" class="button button-secondary" data-sschp-index-action="rebuild"><?php esc_html_e( 'Rebuild index now', 'smart-search-chips' ); ?></button>
                <button type="button" class="button button-secondary" data-sschp-index-action="process"><?php esc_html_e( 'Process next batch now', 'smart-search-chips' ); ?></button>
                <button type="button" class="button button-secondary" data-sschp-index-action="pause"><?php esc_html_e( 'Pause indexing', 'smart-search-chips' ); ?></button>
                <button type="button" class="button button-secondary" data-sschp-index-action="resume"><?php esc_html_e( 'Resume indexing', 'smart-search-chips' ); ?></button>
                <button type="button" class="button button-link-delete" data-sschp-index-action="clear"><?php esc_html_e( 'Clear index', 'smart-search-chips' ); ?></button>
            </p>
            <div class="ssc-index-action-result" data-sschp-index-message aria-live="polite"></div>
            <p class="description"><?php esc_html_e( 'Search remains usable before indexing finishes. Until the local index is ready, the plugin falls back to WordPress search or Relevanssi when available.', 'smart-search-chips' ); ?></p>
        </div>
        <?php
    }

    /**
     * Render the admin test search tool.
     */
    private function render_search_debug_card() {
        $selected_post_types = $this->sanitize_post_types( get_option( 'sschp_post_types', array( 'post' ) ) );
        $post_types          = get_post_types( array( 'public' => true ), 'objects' );
        ?>
        <div class="ssc-settings-card ssc-settings-card--search-debug">
            <h2><?php esc_html_e( 'Test Search', 'smart-search-chips' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Run the Smart Search Chips server-side search pipeline from this settings screen to confirm which search path is used and whether usable chips are generated.', 'smart-search-chips' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><label for="sschp-test-search-query"><?php esc_html_e( 'Query', 'smart-search-chips' ); ?></label></th>
                    <td>
                        <input type="search" id="sschp-test-search-query" class="regular-text" value="" placeholder="<?php echo esc_attr__( 'Try a product, topic, or keyword', 'smart-search-chips' ); ?>" />
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Post Types', 'smart-search-chips' ); ?></th>
                    <td>
                        <div class="ssc-test-post-types">
                            <?php foreach ( $post_types as $post_type ) : ?>
                                <?php
                                if ( 'attachment' === $post_type->name ) {
                                    continue;
                                }

                                $input_id = 'sschp-test-post-type-' . sanitize_key( $post_type->name );
                                ?>
                                <label for="<?php echo esc_attr( $input_id ); ?>">
                                    <input
                                        type="checkbox"
                                        id="<?php echo esc_attr( $input_id ); ?>"
                                        value="<?php echo esc_attr( $post_type->name ); ?>"
                                        data-sschp-test-post-type="1"
                                        <?php checked( in_array( $post_type->name, $selected_post_types, true ) ); ?>
                                    />
                                    <?php echo esc_html( $post_type->label ); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="description"><?php esc_html_e( 'The checked post types are used for this test only. If none are checked, the saved Search post type setting is used.', 'smart-search-chips' ); ?></p>
                    </td>
                </tr>
            </table>
            <p>
                <button type="button" class="button button-secondary" id="sschp-run-test-search"><?php esc_html_e( 'Run Test Search', 'smart-search-chips' ); ?></button>
            </p>
            <div id="sschp-test-search-results" class="ssc-test-search-results" aria-live="polite"></div>
        </div>
        <?php
    }

    /**
     * Render one static diagnostic row.
     *
     * @param string $label Row label.
     * @param bool   $pass  Whether the diagnostic passes.
     * @param string $text  Row detail.
     */
    private function render_diagnostic_row( $label, $pass, $text ) {
        printf(
            '<li class="ssc-diagnostic-row ssc-diagnostic-row--%1$s"><strong>%2$s</strong><span>%3$s</span><em>%4$s</em></li>',
            $pass ? 'pass' : 'warn',
            esc_html( $label ),
            esc_html( $text ),
            esc_html( $pass ? __( 'Pass', 'smart-search-chips' ) : __( 'Check', 'smart-search-chips' ) )
        );
    }

    /**
     * Render the theme compatibility diagnostics card.
     */
    private function render_theme_compatibility_card() {
        $diagnostics = $this->get_theme_compatibility_diagnostics();
        ?>
        <div class="ssc-settings-card ssc-settings-card--diagnostics">
            <h2><?php esc_html_e( 'Theme Compatibility Test', 'smart-search-chips' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Use this before assuming the plugin is broken. Most theme-search issues come from a different input selector, result container, AJAX action, or keyword field.', 'smart-search-chips' ); ?></p>

            <dl class="ssc-compat-config">
                <div>
                    <dt><?php esc_html_e( 'Search input selector', 'smart-search-chips' ); ?></dt>
                    <dd><code><?php echo esc_html( $diagnostics['inputSelector'] ); ?></code></dd>
                </div>
                <div>
                    <dt><?php esc_html_e( 'Result container selector', 'smart-search-chips' ); ?></dt>
                    <dd><code><?php echo esc_html( $diagnostics['resultSelector'] ); ?></code></dd>
                </div>
                <div>
                    <dt><?php esc_html_e( 'AJAX action', 'smart-search-chips' ); ?></dt>
                    <dd><code><?php echo esc_html( $diagnostics['ajaxActionLabel'] ); ?></code></dd>
                </div>
                <div>
                    <dt><?php esc_html_e( 'Keyword field', 'smart-search-chips' ); ?></dt>
                    <dd><code><?php echo esc_html( $diagnostics['keywordField'] ); ?></code></dd>
                </div>
            </dl>

            <ul class="ssc-diagnostic-list">
                <?php
                $this->render_diagnostic_row(
                    __( 'Search input selector', 'smart-search-chips' ),
                    $diagnostics['inputSelectorValid'],
                    $diagnostics['inputSelectorValid'] ? __( 'Configured selector syntax looks valid.', 'smart-search-chips' ) : __( 'Configured selector syntax looks invalid.', 'smart-search-chips' )
                );
                $this->render_diagnostic_row(
                    __( 'Result container selector', 'smart-search-chips' ),
                    $diagnostics['resultSelectorValid'],
                    $diagnostics['resultSelectorValid'] ? __( 'Configured selector syntax looks valid.', 'smart-search-chips' ) : __( 'Configured selector syntax looks invalid.', 'smart-search-chips' )
                );
                $this->render_diagnostic_row(
                    __( 'Default selectors', 'smart-search-chips' ),
                    $diagnostics['defaultInputValid'] && $diagnostics['defaultResultValid'],
                    __( 'Built-in fallback selectors are available.', 'smart-search-chips' )
                );
                $this->render_diagnostic_row(
                    __( 'AJAX action', 'smart-search-chips' ),
                    true,
                    $diagnostics['ajaxActionConfigured'] ? __( 'Theme AJAX action override is configured.', 'smart-search-chips' ) : __( 'Using the built-in shortcode AJAX action.', 'smart-search-chips' )
                );
                $this->render_diagnostic_row(
                    __( 'Keyword field', 'smart-search-chips' ),
                    $diagnostics['keywordFieldSane'],
                    $diagnostics['keywordFieldSane'] ? __( 'Keyword request field looks valid.', 'smart-search-chips' ) : __( 'Keyword request field should be a simple key.', 'smart-search-chips' )
                );
                $this->render_diagnostic_row(
                    __( 'WooCommerce', 'smart-search-chips' ),
                    $diagnostics['woocommerceActive'],
                    $diagnostics['woocommerceActive'] ? __( 'WooCommerce is active.', 'smart-search-chips' ) : __( 'WooCommerce is not active.', 'smart-search-chips' )
                );
                $this->render_diagnostic_row(
                    __( 'Relevanssi', 'smart-search-chips' ),
                    $diagnostics['relevanssiActive'],
                    $diagnostics['relevanssiActive'] ? __( 'Relevanssi is active.', 'smart-search-chips' ) : __( 'Relevanssi is not active. Search still works, but relevance ranking may be weaker.', 'smart-search-chips' )
                );
                $this->render_diagnostic_row(
                    __( 'Shortcode', 'smart-search-chips' ),
                    $diagnostics['shortcodeAvailable'],
                    $diagnostics['shortcodeAvailable'] ? __( 'The [sschp_search] shortcode is available.', 'smart-search-chips' ) : __( 'Shortcode registration was not detected.', 'smart-search-chips' )
                );
                ?>
            </ul>

            <p class="description">
                <?php esc_html_e( 'Recommendation: if your theme live search cannot be detected reliably, place the shortcode where you want a known-compatible Smart Search Chips search box:', 'smart-search-chips' ); ?>
                <code><?php echo esc_html( $diagnostics['shortcode'] ); ?></code>
            </p>
            <p>
                <button type="button" class="button button-secondary" id="sschp-run-compat-test"><?php esc_html_e( 'Run frontend compatibility test', 'smart-search-chips' ); ?></button>
                <a class="button button-link" href="<?php echo esc_url( $diagnostics['debugUrl'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open frontend debug mode', 'smart-search-chips' ); ?></a>
            </p>
            <?php
            $this->render_pro_context_card(
                __( 'Support and Diagnostics', 'smart-search-chips' ),
                __( 'The compatibility test above is included here. Smart Search Chips Pro is available separately for teams that want commercial support and deeper diagnostic handoff tools.', 'smart-search-chips' ),
                array(),
                __( 'View Pro Support Options', 'smart-search-chips' )
            );
            ?>
            <div id="sschp-compat-results" class="ssc-compat-results" aria-live="polite"></div>
        </div>
        <?php
    }

    /**
     * Render a contextual, admin-only Pro information card.
     *
     * @param string $title     Card title.
     * @param string $body      Card body.
     * @param array  $items     Optional bullet points.
     * @param string $link_text Link label.
     */
    private function render_pro_context_card( $title, $body, $items = array(), $link_text = '' ) {
        ?>
        <div class="ssc-pro-context-card">
            <h3><?php echo esc_html( $title ); ?></h3>
            <p><?php echo esc_html( $body ); ?></p>
            <?php if ( ! empty( $items ) ) : ?>
                <ul>
                    <?php foreach ( $items as $item ) : ?>
                        <li><?php echo esc_html( $item ); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <p><a class="button button-secondary" href="<?php echo esc_url( SSCHP_Edition::get_product_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $link_text ? $link_text : __( 'View Smart Search Chips Pro', 'smart-search-chips' ) ); ?></a></p>
        </div>
        <?php
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
        $render_limit         = $this->get_numeric_option_value( 'sschp_max_results', 10, 1 );
        $analyze_limit        = $this->get_numeric_option_value( 'sschp_search_results_count', 50, 5 );
        $debounce_ms          = $this->get_numeric_option_value( 'sschp_debounce_ms', 300, 50 );
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
                    <p><?php esc_html_e( 'For the most reliable setup, add the Smart Search Chips search block or shortcode to your search page.', 'smart-search-chips' ); ?></p>
                    <p class="description"><?php esc_html_e( 'Existing theme search enhancement is automatic when compatible and configurable when needed.', 'smart-search-chips' ); ?></p>
                    <p>
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
                            <div class="ssc-badge ssc-badge--free"><?php esc_html_e( 'Included', 'smart-search-chips' ); ?></div>
                            <p><strong><?php echo esc_html( SSCHP_Edition::get_status_label() ); ?></strong></p>
                            <p class="description"><?php echo esc_html( SSCHP_Edition::get_status_description() ); ?></p>
                        </div>

                        <?php $this->render_setup_wizard_card(); ?>
                        <?php $this->render_search_index_card(); ?>
                        <?php $this->render_search_debug_card(); ?>

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
                            <h2><?php esc_html_e( 'Custom Fields and ACF', 'smart-search-chips' ); ?></h2>
                            <p class="description"><?php esc_html_e( 'This WordPress.org edition uses the built-in Search Chips Tags field and optional WooCommerce helper tags. If your catalog already stores search labels in ACF or custom fields, field mapping is available separately in Smart Search Chips Pro.', 'smart-search-chips' ); ?></p>
                            <?php
                            $this->render_pro_context_card(
                                __( 'ACF/custom field mapping', 'smart-search-chips' ),
                                __( 'Pro add-on feature for sites that already maintain searchable labels in ACF, product meta, or other structured fields.', 'smart-search-chips' ),
                                array(
                                    __( 'Map an existing field as a chip source.', 'smart-search-chips' ),
                                    __( 'Use structured product fields in advanced tagging workflows.', 'smart-search-chips' ),
                                )
                            );
                            ?>
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
                            <?php
                            $this->render_pro_context_card(
                                __( 'Advanced chip rules', 'smart-search-chips' ),
                                __( 'Synonym rules, manual chip curation, pinned chips, hidden chips, and display-label overrides are available separately in Smart Search Chips Pro.', 'smart-search-chips' ),
                                array(
                                    __( 'Normalize related tags into one canonical chip.', 'smart-search-chips' ),
                                    __( 'Curate which chips appear first for important searches.', 'smart-search-chips' ),
                                )
                            );
                            ?>
                        </div>

                        <div class="ssc-settings-card">
                            <h2><?php esc_html_e( 'Numeric Range Chips', 'smart-search-chips' ); ?></h2>
                            <p class="description"><?php esc_html_e( 'The WordPress.org edition displays normal clickable chips. Numeric range slider chips are available separately in Smart Search Chips Pro for catalogs with structured values such as wattage, voltage, dimensions, weight, or other comparable measurements.', 'smart-search-chips' ); ?></p>
                            <?php
                            $this->render_pro_context_card(
                                __( 'Range slider workflow', 'smart-search-chips' ),
                                __( 'Pro add-on feature that can group compatible numeric chip variants into a slider when the values are structured enough to compare safely.', 'smart-search-chips' ),
                                array(
                                    __( 'Uses conservative grouping to avoid model numbers and unrelated values.', 'smart-search-chips' ),
                                    __( 'Works best with WooCommerce attributes or mapped fields.', 'smart-search-chips' ),
                                )
                            );
                            ?>
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
                                <tr><th><?php esc_html_e( 'Results Shown', 'smart-search-chips' ); ?></th><td><input type="number" name="sschp_max_results" value="<?php echo esc_attr( $render_limit ); ?>" class="small-text" min="1" max="100" /><?php if ( $render_limit > 50 ) : ?><p class="description notice-warning"><?php esc_html_e( 'High render limits can make live search feel slow. Keep this near 10-20 unless the theme really needs more visible results.', 'smart-search-chips' ); ?></p><?php endif; ?></td></tr>
                                <tr><th><?php esc_html_e( 'Results to Analyze', 'smart-search-chips' ); ?></th><td><input type="number" name="sschp_search_results_count" value="<?php echo esc_attr( $analyze_limit ); ?>" class="small-text" min="5" max="200" /><p class="description"><?php esc_html_e( 'More results can produce better chips but may be slower. Recommended: 50.', 'smart-search-chips' ); ?></p><?php if ( $analyze_limit > 100 ) : ?><p class="description notice-warning"><?php esc_html_e( 'High analysis limits can be expensive on large catalogs. Use Relevanssi or the local index before raising this.', 'smart-search-chips' ); ?></p><?php endif; ?></td></tr>
                                <tr><th><?php esc_html_e( 'Live Search Debounce', 'smart-search-chips' ); ?></th><td><input type="number" name="sschp_debounce_ms" value="<?php echo esc_attr( $debounce_ms ); ?>" class="small-text" min="50" max="2000" step="50" /> ms<p class="description"><?php esc_html_e( 'Delay before live search runs while typing. Lower feels faster; higher reduces server load. Recommended: 300 ms.', 'smart-search-chips' ); ?></p></td></tr>
                                <tr><th><?php esc_html_e( 'Relevance Threshold', 'smart-search-chips' ); ?></th><td><input type="range" name="sschp_relevance_threshold" value="<?php echo esc_attr( get_option( 'sschp_relevance_threshold', '0.3' ) ); ?>" min="0" max="1" step="0.05" /><span class="ssc-range-val"><?php echo esc_html( get_option( 'sschp_relevance_threshold', '0.3' ) ); ?></span><p class="description"><?php esc_html_e( 'Requires Relevanssi. 0 = all results, 1 = exact matches only.', 'smart-search-chips' ); ?></p></td></tr>
                            </table>
                        </div>

                        <div class="ssc-settings-card">
                            <h2><?php esc_html_e( 'Analytics and Reporting', 'smart-search-chips' ); ?></h2>
                            <p class="description"><?php esc_html_e( 'This WordPress.org edition does not track visitor searches. Local analytics, reporting, CSV export, and optional scheduled email digests are available separately in Smart Search Chips Pro.', 'smart-search-chips' ); ?></p>
                            <?php
                            $this->render_pro_context_card(
                                __( 'Pro analytics/reporting', 'smart-search-chips' ),
                                __( 'Advanced workflow for teams that want to review search demand, zero-result queries, chip impressions, chip clicks, and result-click performance inside WordPress.', 'smart-search-chips' ),
                                array(
                                    __( 'Stores analytics locally on the WordPress site.', 'smart-search-chips' ),
                                    __( 'Does not send reports to the plugin author or third parties.', 'smart-search-chips' ),
                                )
                            );
                            ?>
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

                        <?php $this->render_theme_compatibility_card(); ?>

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

    /**
     * Handle local search index control actions from the settings page.
     *
     * @return void
     */
    public function handle_search_index_control() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'You do not have permission to manage the local search index.', 'smart-search-chips' ),
                ),
                403
            );
        }

        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'sschp_search_index_control' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Security check failed.', 'smart-search-chips' ),
                ),
                403
            );
        }

        if ( ! class_exists( 'SSCHP_Search_Index' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'The local search index is not available.', 'smart-search-chips' ),
                ),
                500
            );
        }

        $operation = isset( $_POST['operation'] ) ? sanitize_key( wp_unslash( $_POST['operation'] ) ) : '';
        $progress  = null;
        $message   = '';

        switch ( $operation ) {
            case 'rebuild':
                SSCHP_Search_Index::rebuild_index( 'manual_rebuild' );
                $message = __( 'Index rebuild queued. The next batch will run in the background.', 'smart-search-chips' );
                break;

            case 'process':
                SSCHP_Search_Index::create_table();
                $engine       = new SSCHP_Chip_Engine();
                $search_index = new SSCHP_Search_Index( $this, $engine );
                $processed    = $search_index->process_batch();
                $progress     = SSCHP_Search_Index::get_progress();

                if ( 'paused' === $progress['status'] ) {
                    $message = __( 'Indexing is paused. Resume indexing before processing another batch.', 'smart-search-chips' );
                } elseif ( $processed > 0 ) {
                    $message = sprintf(
                        /* translators: %d: number of posts indexed. */
                        _n( '%d post indexed in this batch.', '%d posts indexed in this batch.', $processed, 'smart-search-chips' ),
                        $processed
                    );
                } elseif ( 'complete' === $progress['status'] ) {
                    $message = __( 'No queued posts remain. The index is ready.', 'smart-search-chips' );
                } else {
                    $message = __( 'No posts were processed in this batch.', 'smart-search-chips' );
                }
                break;

            case 'clear':
                SSCHP_Search_Index::create_table();
                SSCHP_Search_Index::clear_index();
                $message = __( 'Local search index cleared.', 'smart-search-chips' );
                break;

            case 'pause':
                SSCHP_Search_Index::pause_index();
                $message = __( 'Indexing paused.', 'smart-search-chips' );
                break;

            case 'resume':
                SSCHP_Search_Index::resume_index();
                $message = __( 'Indexing resumed. The next batch will run in the background.', 'smart-search-chips' );
                break;

            default:
                wp_send_json_error(
                    array(
                        'message' => __( 'Unknown local search index action.', 'smart-search-chips' ),
                    ),
                    400
                );
        }

        wp_send_json_success(
            array(
                'message'  => $message,
                'progress' => $progress ? $progress : SSCHP_Search_Index::get_progress(),
            )
        );
    }

    /**
     * Handle the settings-page test search AJAX request.
     *
     * @return void
     */
    public function handle_admin_test_search() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'You do not have permission to run test searches.', 'smart-search-chips' ),
                ),
                403
            );
        }

        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'sschp_admin_test_search' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Security check failed.', 'smart-search-chips' ),
                ),
                403
            );
        }

        $query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';

        $engine       = new SSCHP_Chip_Engine();
        $search_index = class_exists( 'SSCHP_Search_Index' ) ? new SSCHP_Search_Index( $this, $engine ) : null;
        $handler      = new SSCHP_Search_Handler( $engine, $this, $search_index );
        $data         = $handler->get_admin_test_search_data(
            array(
                'keyword'    => $query,
                'post_types' => $this->get_admin_test_search_post_types(),
            )
        );

        wp_send_json_success( $data );
    }

    /**
     * Read the optional post type choices from the admin test search request.
     *
     * @return array
     */
    private function get_admin_test_search_post_types() {
        if ( ! isset( $_POST['post_types'] ) ) {
            return $this->sanitize_post_types( get_option( 'sschp_post_types', array( 'post' ) ) );
        }

        $raw = wp_unslash( $_POST['post_types'] );
        if ( is_array( $raw ) ) {
            return $this->sanitize_post_types( $raw );
        }

        return $this->sanitize_post_types( preg_split( '/[\s,]+/', (string) $raw ) );
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

    /**
     * Sanitize the configured frontend search input selector.
     *
     * @param mixed $value Raw selector value.
     * @return string
     */
    public function sanitize_search_input_selector( $value ) {
        return $this->sanitize_css_selector_setting(
            $value,
            'sschp_search_input_selector',
            $this->get_default_search_input_selector(),
            __( 'Search Input Selector', 'smart-search-chips' )
        );
    }

    /**
     * Sanitize the configured frontend results container selector.
     *
     * @param mixed $value Raw selector value.
     * @return string
     */
    public function sanitize_result_container_selector( $value ) {
        return $this->sanitize_css_selector_setting(
            $value,
            'sschp_result_container_selector',
            $this->get_default_result_container_selector(),
            __( 'Result Container Selector', 'smart-search-chips' )
        );
    }

    /**
     * Get the default frontend input selector.
     *
     * @return string
     */
    private function get_default_search_input_selector() {
        return '.ssc-search__input, input[type="search"], input[name="keyword"], input[name="s"], input[name="query"], input[name="search"]';
    }

    /**
     * Get the default frontend results selector.
     *
     * @return string
     */
    private function get_default_result_container_selector() {
        return '.js-ssc-results, .js-search__results, .ssc-search__results';
    }

    /**
     * Sanitize a CSS selector option without over-restricting valid selectors.
     *
     * @param mixed  $value       Raw selector value.
     * @param string $option_name Full option name.
     * @param string $default     Default selector.
     * @param string $label       Human-readable field label.
     * @return string
     */
    private function sanitize_css_selector_setting( $value, $option_name, $default, $label ) {
        $selector = trim( sanitize_text_field( (string) $value ) );

        if ( $this->looks_like_valid_css_selector( $selector ) ) {
            return $selector;
        }

        add_settings_error(
            'smart-search-chips',
            $option_name . '_invalid',
            sprintf(
                /* translators: %s: settings field label. */
                __( '%s was not saved because it does not look like a valid CSS selector. The previous value or default selector is still being used.', 'smart-search-chips' ),
                $label
            ),
            'error'
        );

        $previous = trim( sanitize_text_field( (string) get_option( $option_name, $default ) ) );

        if ( $this->looks_like_valid_css_selector( $previous ) ) {
            return $previous;
        }

        return $default;
    }

    /**
     * Conservative CSS selector sanity check.
     *
     * @param string $selector Selector value.
     * @return bool
     */
    private function looks_like_valid_css_selector( $selector ) {
        if ( '' === trim( $selector ) ) {
            return false;
        }

        if ( preg_match( '/[\x00-\x1F\x7F]/', $selector ) ) {
            return false;
        }

        if ( preg_match( '/(^|,)\s*(,|$)/', $selector ) ) {
            return false;
        }

        $pairs = array(
            '(' => ')',
            '[' => ']',
            '{' => '}',
        );
        $stack = array();
        $quote = '';
        $len   = strlen( $selector );

        for ( $i = 0; $i < $len; $i++ ) {
            $char = $selector[ $i ];

            if ( '\\' === $char ) {
                $i++;
                continue;
            }

            if ( '' !== $quote ) {
                if ( $char === $quote ) {
                    $quote = '';
                }
                continue;
            }

            if ( '"' === $char || "'" === $char ) {
                $quote = $char;
                continue;
            }

            if ( isset( $pairs[ $char ] ) ) {
                $stack[] = $pairs[ $char ];
                continue;
            }

            if ( in_array( $char, $pairs, true ) ) {
                if ( empty( $stack ) || array_pop( $stack ) !== $char ) {
                    return false;
                }
            }
        }

        return '' === $quote && empty( $stack );
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
        $value = $this->sanitize_numeric_setting( $value, 10, 1, 100 );
        if ( $value > 50 ) {
            add_settings_error( 'smart-search-chips', 'sschp_high_render_limit', __( 'High Results Shown values can slow down live search rendering on large result sets.', 'smart-search-chips' ), 'warning' );
        }
        return $value;
    }

    public function sanitize_search_results_count( $value ) {
        $value = $this->sanitize_numeric_setting( $value, 50, 5, 200 );
        if ( $value > 100 ) {
            add_settings_error( 'smart-search-chips', 'sschp_high_analysis_limit', __( 'High Results to Analyze values can be expensive on large catalogs. Keep this near 50 unless indexing/search infrastructure can handle more.', 'smart-search-chips' ), 'warning' );
        }
        return $value;
    }

    public function sanitize_debounce_ms( $value ) {
        return $this->sanitize_numeric_setting( $value, 300, 50, 2000 );
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
