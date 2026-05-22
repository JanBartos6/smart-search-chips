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
        add_action( 'admin_post_sschp_dismiss_onboarding', array( $this, 'handle_dismiss_onboarding' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_ajax_sschp_admin_test_search', array( $this, 'handle_admin_test_search' ) );
        add_action( 'wp_ajax_sschp_search_index_control', array( $this, 'handle_search_index_control' ) );
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

        if ( SSCHP_License::is_pro() ) {
            $account_url = SSCHP_License::get_account_url();
            if ( $account_url ) {
                $links[] = '<a href="' . esc_url( $account_url ) . '">' . esc_html__( 'Manage License', 'smart-search-chips' ) . '</a>';
            }

            return $links;
        }

        $links[] = '<a href="' . esc_url( $this->get_pro_features_url() ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View Pro Features', 'smart-search-chips' ) . '</a>';
        $links[] = '<a href="' . esc_url( $this->get_upgrade_url() ) . '">' . esc_html__( 'Upgrade to Pro', 'smart-search-chips' ) . '</a>';

        return $links;
    }

    public function register_settings() {
        // All settings registered flat (no sections — we render manually)
        register_setting( 'smart-search-chips', 'sschp_post_types', array(
            'sanitize_callback' => array( $this, 'sanitize_post_types' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_override_ajax', array(
            'sanitize_callback' => 'sanitize_key',
        ) );
        register_setting( 'smart-search-chips', 'sschp_ajax_keyword_field', array(
            'sanitize_callback' => 'sanitize_key',
        ) );
        register_setting( 'smart-search-chips', 'sschp_result_container_selector', array(
            'sanitize_callback' => array( $this, 'sanitize_result_container_selector' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_search_input_selector', array(
            'sanitize_callback' => array( $this, 'sanitize_search_input_selector' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_max_chips', array(
            'sanitize_callback' => array( $this, 'sanitize_max_chips' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_max_tag_length', array(
            'sanitize_callback' => array( $this, 'sanitize_max_tag_length' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_woo_enabled', array(
            'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_woo_include_sku', array(
            'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_chip_color', array(
            'sanitize_callback' => array( $this, 'sanitize_hex_color_setting' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_chip_text_color', array(
            'sanitize_callback' => array( $this, 'sanitize_hex_color_setting' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_chip_inactive_color', array(
            'sanitize_callback' => array( $this, 'sanitize_hex_color_setting' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_chip_inactive_text_color', array(
            'sanitize_callback' => array( $this, 'sanitize_hex_color_setting' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_max_results', array(
            'sanitize_callback' => array( $this, 'sanitize_max_results' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_search_results_count', array(
            'sanitize_callback' => array( $this, 'sanitize_search_results_count' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_debounce_ms', array(
            'sanitize_callback' => array( $this, 'sanitize_debounce_ms' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_relevance_threshold', array(
            'sanitize_callback' => array( $this, 'sanitize_relevance_threshold' ),
        ) );

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
        register_setting( 'smart-search-chips', 'sschp_analytics_ignore_admins', array(
            'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_analytics_digest_enabled', array(
            'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_analytics_digest_recipients', array(
            'sanitize_callback' => array( $this, 'sanitize_email_recipients' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_analytics_digest_frequency', array(
            'sanitize_callback' => array( $this, 'sanitize_digest_frequency' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_analytics_digest_day', array(
            'sanitize_callback' => array( $this, 'sanitize_digest_day' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_analytics_digest_time', array(
            'sanitize_callback' => array( $this, 'sanitize_digest_time' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_analytics_digest_date_range', array(
            'sanitize_callback' => array( $this, 'sanitize_digest_date_range' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_analytics_digest_include_csv', array(
            'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_analytics_digest_include_zero_results', array(
            'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_analytics_digest_include_top_chips', array(
            'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
        ) );
        register_setting( 'smart-search-chips', 'sschp_analytics_digest_include_low_ctr', array(
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
        register_setting( 'smart-search-chips', 'sschp_result_image_display_size', array(
            'sanitize_callback' => array( $this, 'sanitize_result_image_display_size' ),
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
                    'pass'                       => __( 'Pass', 'smart-search-chips' ),
                    'warning'                    => __( 'Warning', 'smart-search-chips' ),
                    'runningSearch'              => __( 'Running test search...', 'smart-search-chips' ),
                    'searchFailed'               => __( 'The test search could not run.', 'smart-search-chips' ),
                    'queryTooShort'              => __( 'Enter at least two characters to run a test search.', 'smart-search-chips' ),
                    'path'                       => __( 'Path', 'smart-search-chips' ),
                    'results'                    => __( 'Results', 'smart-search-chips' ),
                    'taggedResults'              => __( 'Tagged results', 'smart-search-chips' ),
                    'generatedChips'             => __( 'Generated chips', 'smart-search-chips' ),
                    'firstResults'               => __( 'First results', 'smart-search-chips' ),
                    'explanations'               => __( 'Explanations', 'smart-search-chips' ),
                    'warnings'                   => __( 'Warnings', 'smart-search-chips' ),
                    'suggestedFix'               => __( 'Suggested fix', 'smart-search-chips' ),
                    'noChips'                    => __( 'No chips generated.', 'smart-search-chips' ),
                    'noResults'                  => __( 'No preview results returned.', 'smart-search-chips' ),
                    'indexRunning'                => __( 'Updating local index...', 'smart-search-chips' ),
                    'indexActionFailed'           => __( 'The local index action could not run.', 'smart-search-chips' ),
                    'notYetCompleted'             => __( 'Not yet completed', 'smart-search-chips' ),
                    'notFound'                   => __( 'Not found on this page', 'smart-search-chips' ),
                    'selectorInvalid'            => __( 'This selector is invalid and should be corrected before testing theme integration.', 'smart-search-chips' ),
                    'selectorValid'              => __( 'Selector syntax is valid.', 'smart-search-chips' ),
                    'defaultSelectorsValid'      => __( 'Default selectors are valid.', 'smart-search-chips' ),
                    'actionConfigured'           => __( 'Custom AJAX action is configured.', 'smart-search-chips' ),
                    'actionDefault'              => __( 'No theme AJAX action is configured. The shortcode will use the built-in sschp_search action.', 'smart-search-chips' ),
                    'keywordSane'                => __( 'Keyword field looks valid.', 'smart-search-chips' ),
                    'keywordInvalid'             => __( 'Keyword field should be a simple request key such as keyword, s, query, or search.', 'smart-search-chips' ),
                    'resultMissingRecommendation' => __( 'No result container was found on this admin page. If your theme search cannot be detected on the frontend, use the [sschp_search] shortcode or configure the result container selector.', 'smart-search-chips' ),
                    'inputMatches'               => __( 'Matching search inputs found on this page:', 'smart-search-chips' ),
                    'resultMatches'              => __( 'Matching result containers found on this page:', 'smart-search-chips' ),
                    'copied'                     => __( 'Copied.', 'smart-search-chips' ),
                    'copyFailed'                 => __( 'Copy failed. Copy the snippet manually.', 'smart-search-chips' ),
                ),
            ) );
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

        if ( SSCHP_License::is_pro() && class_exists( 'SSCHP_AI_Tagger' ) ) {
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
            'shortcodeBlockLabel'     => __( 'Add the Smart Search Chips Search block, or use a Shortcode block with [sschp_search].', 'smart-search-chips' ),
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
                    <p class="description"><?php echo esc_html( $setup['shortcodeBlockLabel'] ); ?></p>
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
            <div id="sschp-compat-results" class="ssc-compat-results" aria-live="polite"></div>
        </div>
        <?php
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
        $analytics_ignore_admins = get_option( 'sschp_analytics_ignore_admins', '1' );
        $digest_on     = get_option( 'sschp_analytics_digest_enabled', '0' );
        $digest_recipients = get_option( 'sschp_analytics_digest_recipients', get_option( 'admin_email', '' ) );
        $digest_frequency  = $this->sanitize_digest_frequency( get_option( 'sschp_analytics_digest_frequency', 'weekly' ) );
        $digest_day        = $this->sanitize_digest_day( get_option( 'sschp_analytics_digest_day', 'monday' ) );
        $digest_time       = $this->sanitize_digest_time( get_option( 'sschp_analytics_digest_time', '09:00' ) );
        $digest_range      = $this->sanitize_digest_date_range( get_option( 'sschp_analytics_digest_date_range', 30 ) );
        $digest_csv        = get_option( 'sschp_analytics_digest_include_csv', '0' );
        $digest_zero       = get_option( 'sschp_analytics_digest_include_zero_results', '1' );
        $digest_top_chips  = get_option( 'sschp_analytics_digest_include_top_chips', '1' );
        $digest_low_ctr    = get_option( 'sschp_analytics_digest_include_low_ctr', '1' );
        $digest_next_run   = 0;
        $digest_expected_next_run = 0;
        $digest_preview    = array();
        if ( $is_pro && class_exists( 'SSCHP_Analytics' ) ) {
            $digest_next_run          = SSCHP_Analytics::get_next_digest_run_timestamp();
            $digest_expected_next_run = SSCHP_Analytics::get_expected_digest_run_timestamp();
            $digest_preview           = $analytics_on === '1' ? SSCHP_Analytics::get_digest_preview_data() : array();
        }
        $delete_on_uninstall = get_option( 'sschp_delete_data_on_uninstall', '0' );
        $render_limit  = $this->get_numeric_option_value( 'sschp_max_results', 10, 1 );
        $analyze_limit = $this->get_numeric_option_value( 'sschp_search_results_count', 50, 5 );
        $debounce_ms   = $this->get_numeric_option_value( 'sschp_debounce_ms', 300, 50 );
        $data_deleted = get_transient( 'sschp_data_deleted_' . get_current_user_id() );
        if ( $data_deleted ) {
            delete_transient( 'sschp_data_deleted_' . get_current_user_id() );
        }
        $chip_rule_import_result = null;
        if ( $is_pro && class_exists( 'SSCHP_Chip_Rules' ) ) {
            $chip_rule_import_key    = SSCHP_Chip_Rules::IMPORT_RESULT_TRANSIENT_PREFIX . get_current_user_id();
            $chip_rule_import_result = get_transient( $chip_rule_import_key );
            if ( $chip_rule_import_result ) {
                delete_transient( $chip_rule_import_key );
            }
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

            <?php if ( ! get_user_meta( get_current_user_id(), 'sschp_onboarding_dismissed', true ) ) : ?>
                <div class="notice notice-info ssc-onboarding-panel">
                    <p><strong><?php esc_html_e( 'Set up Smart Search Chips', 'smart-search-chips' ); ?></strong></p>
                    <p><?php esc_html_e( 'For the most reliable setup, add the Smart Search Chips search block or shortcode to your search page.', 'smart-search-chips' ); ?></p>
                    <p class="description"><?php esc_html_e( 'Existing theme search enhancement is automatic when compatible and configurable when needed.', 'smart-search-chips' ); ?></p>
                    <p class="description"><?php esc_html_e( 'Pro is a separate upgrade for ACF/custom field mapping, numeric slider chips, analytics, synonym rules, manual curation, AI-assisted tag workflows, and commercial support.', 'smart-search-chips' ); ?></p>
                    <p>
                        <a class="button button-secondary" href="<?php echo esc_url( $this->get_pro_features_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Pro Features', 'smart-search-chips' ); ?></a>
                        <a class="button-link" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sschp_dismiss_onboarding' ), 'sschp_dismiss_onboarding' ) ); ?>"><?php esc_html_e( 'Dismiss setup panel', 'smart-search-chips' ); ?></a>
                    </p>
                </div>
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

                        <?php $this->render_setup_wizard_card(); ?>
                        <?php $this->render_search_index_card(); ?>
                        <?php $this->render_search_debug_card(); ?>

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
                                            <p class="description"><?php esc_html_e( 'Need ACF/custom fields? Smart Search Chips Pro supports mapped fields as a separate upgrade.', 'smart-search-chips' ); ?></p>
                                        <?php endif; ?>
                                    </td>
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

                        <?php $this->render_theme_compatibility_card(); ?>

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
                                        <p><?php esc_html_e( 'Pro automatically groups numeric chip variants such as "100 W" and "200 W" into one slider chip.', 'smart-search-chips' ); ?></p>
                                        <p class="description"><?php esc_html_e( 'Use the same catalog label or unit near each value for best results, for example "100 W", "Length: 120 cm", or a mapped numeric field.', 'smart-search-chips' ); ?></p>
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
                            <?php if ( $is_pro && class_exists( 'SSCHP_Chip_Rules' ) ) : ?>
                                <div class="ssc-chip-rules-transfer">
                                    <h3><?php esc_html_e( 'Import / Export Chip Rules', 'smart-search-chips' ); ?></h3>
                                    <p class="description"><?php esc_html_e( 'Export synonym rules, pinned chips, hidden chips, and label overrides as JSON. Imports are validated before saving, and preview mode checks the JSON without changing settings.', 'smart-search-chips' ); ?></p>

                                    <?php if ( is_array( $chip_rule_import_result ) ) : ?>
                                        <?php
                                        $notice_class = ! empty( $chip_rule_import_result['valid'] ) ? 'notice-success' : 'notice-error';
                                        $summary      = isset( $chip_rule_import_result['summary'] ) && is_array( $chip_rule_import_result['summary'] ) ? $chip_rule_import_result['summary'] : array();
                                        ?>
                                        <div class="notice <?php echo esc_attr( $notice_class ); ?> inline">
                                            <p>
                                                <?php
                                                if ( empty( $chip_rule_import_result['valid'] ) ) {
                                                    esc_html_e( 'Chip rule import failed. Nothing was saved.', 'smart-search-chips' );
                                                } elseif ( ! empty( $chip_rule_import_result['dry_run'] ) ) {
                                                    esc_html_e( 'Import preview passed. Nothing was saved.', 'smart-search-chips' );
                                                } else {
                                                    esc_html_e( 'Chip rules imported successfully.', 'smart-search-chips' );
                                                }
                                                ?>
                                            </p>
                                            <?php if ( ! empty( $summary ) ) : ?>
                                                <p class="description">
                                                    <?php
                                                    printf(
                                                        /* translators: %1$d: synonym lines, %2$d: pinned lines, %3$d: hidden lines, %4$d: renamed lines */
                                                        esc_html__( 'Rules found: %1$d synonym, %2$d pinned, %3$d hidden, %4$d renamed.', 'smart-search-chips' ),
                                                        (int) ( $summary['synonym_rules'] ?? 0 ),
                                                        (int) ( $summary['pinned_chips'] ?? 0 ),
                                                        (int) ( $summary['hidden_chips'] ?? 0 ),
                                                        (int) ( $summary['renamed_chips'] ?? 0 )
                                                    );
                                                    ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php foreach ( array( 'errors', 'warnings' ) as $message_type ) : ?>
                                                <?php if ( ! empty( $chip_rule_import_result[ $message_type ] ) && is_array( $chip_rule_import_result[ $message_type ] ) ) : ?>
                                                    <ul>
                                                        <?php foreach ( $chip_rule_import_result[ $message_type ] as $message ) : ?>
                                                            <li><?php echo esc_html( $message ); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <p>
                                        <a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', SSCHP_Chip_Rules::EXPORT_ACTION, admin_url( 'admin-post.php' ) ), SSCHP_Chip_Rules::EXPORT_ACTION ) ); ?>"><?php esc_html_e( 'Export chip rules JSON', 'smart-search-chips' ); ?></a>
                                    </p>
                                    <label for="sschp-chip-rules-json"><strong><?php esc_html_e( 'Import JSON', 'smart-search-chips' ); ?></strong></label>
                                    <textarea id="sschp-chip-rules-json" name="sschp_chip_rules_json" form="ssc-chip-rules-import-form" rows="8" class="large-text code" placeholder='{"schema":"smart-search-chips/chip-rules","version":1,"rules":{"synonym_rules":"Sofa = couch, settee","pinned_chips":"Bestseller","hidden_chips":"Internal","renamed_chips":"eco => Eco friendly"}}'></textarea>
                                    <p>
                                        <button type="submit" form="ssc-chip-rules-import-form" name="sschp_chip_rules_import_mode" value="preview" class="button button-secondary"><?php esc_html_e( 'Preview import', 'smart-search-chips' ); ?></button>
                                        <button type="submit" form="ssc-chip-rules-import-form" name="sschp_chip_rules_import_mode" value="import" class="button button-primary"><?php esc_html_e( 'Import and save', 'smart-search-chips' ); ?></button>
                                    </p>
                                </div>
                            <?php endif; ?>
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
                                    <td>
                                        <input type="number" name="sschp_max_results" value="<?php echo esc_attr( $render_limit ); ?>" class="small-text" min="1" max="100" />
                                        <?php if ( $render_limit > 50 ) : ?>
                                            <p class="description notice-warning"><?php esc_html_e( 'High render limits can make live search feel slow. Keep this near 10-20 unless the theme really needs more visible results.', 'smart-search-chips' ); ?></p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Results to Analyze', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <input type="number" name="sschp_search_results_count" value="<?php echo esc_attr( $analyze_limit ); ?>" class="small-text" min="5" max="200" />
                                        <p class="description"><?php esc_html_e( 'More = better chips, slower. Recommended: 50.', 'smart-search-chips' ); ?></p>
                                        <?php if ( $analyze_limit > 100 ) : ?>
                                            <p class="description notice-warning"><?php esc_html_e( 'High analysis limits can be expensive on large catalogs. Use Relevanssi or the local index before raising this.', 'smart-search-chips' ); ?></p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Live Search Debounce', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <input type="number" name="sschp_debounce_ms" value="<?php echo esc_attr( $debounce_ms ); ?>" class="small-text" min="50" max="2000" step="50" /> ms
                                        <p class="description"><?php esc_html_e( 'Delay before live search runs while typing. Lower feels faster; higher reduces server load. Recommended: 300 ms.', 'smart-search-chips' ); ?></p>
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
                                    <tr>
                                        <th><?php esc_html_e( 'Noise Reduction', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <label>
                                                <input type="hidden" name="sschp_analytics_ignore_admins" value="0" />
                                                <input type="checkbox" name="sschp_analytics_ignore_admins" value="1" <?php checked( $analytics_ignore_admins, '1' ); ?> />
                                                <?php esc_html_e( 'Ignore visits from administrators', 'smart-search-chips' ); ?>
                                            </label>
                                            <p class="description"><?php esc_html_e( 'Recommended. Keeps setup/testing activity out of analytics while preserving public visitor data.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Email Digest', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <label>
                                                <input type="hidden" name="sschp_analytics_digest_enabled" value="0" />
                                                <input type="checkbox" name="sschp_analytics_digest_enabled" value="1" <?php checked( $digest_on, '1' ); ?> />
                                                <?php esc_html_e( 'Send scheduled analytics email reports', 'smart-search-chips' ); ?>
                                            </label>
                                            <p class="description"><?php esc_html_e( 'Opt-in only. Digest emails are sent by this WordPress site to the recipients below; analytics data is not sent to Smart Search Chips or any external service.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Digest Recipients', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <textarea name="sschp_analytics_digest_recipients" rows="3" class="large-text code"><?php echo esc_textarea( $digest_recipients ); ?></textarea>
                                            <p class="description"><?php esc_html_e( 'Comma, space, or newline-separated email addresses. Leave empty to use the site admin email.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Frequency', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <select name="sschp_analytics_digest_frequency">
                                                <option value="weekly" <?php selected( $digest_frequency, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'smart-search-chips' ); ?></option>
                                                <option value="monthly" <?php selected( $digest_frequency, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'smart-search-chips' ); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Day / Time', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="sschp_analytics_digest_day" value="<?php echo esc_attr( $digest_day ); ?>" class="regular-text" />
                                            <input type="time" name="sschp_analytics_digest_time" value="<?php echo esc_attr( $digest_time ); ?>" />
                                            <p class="description"><?php esc_html_e( 'For weekly digests, use monday through sunday. For monthly digests, use a day number from 1 to 28. Time uses the site timezone.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Date Range', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <select name="sschp_analytics_digest_date_range">
                                                <option value="7" <?php selected( $digest_range, 7 ); ?>><?php esc_html_e( 'Last 7 days', 'smart-search-chips' ); ?></option>
                                                <option value="30" <?php selected( $digest_range, 30 ); ?>><?php esc_html_e( 'Last 30 days', 'smart-search-chips' ); ?></option>
                                                <option value="90" <?php selected( $digest_range, 90 ); ?>><?php esc_html_e( 'Last 90 days', 'smart-search-chips' ); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Next Scheduled Run', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <?php if ( $analytics_on !== '1' ) : ?>
                                                <p class="description"><?php esc_html_e( 'Enable analytics and save settings before scheduled digest emails can run.', 'smart-search-chips' ); ?></p>
                                            <?php elseif ( $digest_on !== '1' ) : ?>
                                                <p class="description"><?php esc_html_e( 'Scheduled analytics emails are currently off. You can still send a test email and preview the next report below.', 'smart-search-chips' ); ?></p>
                                            <?php elseif ( $digest_next_run > 0 ) : ?>
                                                <p><strong><?php echo esc_html( $this->format_digest_run_time( $digest_next_run ) ); ?></strong></p>
                                                <p class="description"><?php esc_html_e( 'Uses the site timezone and the saved digest schedule.', 'smart-search-chips' ); ?></p>
                                            <?php else : ?>
                                                <p class="description"><?php esc_html_e( 'No analytics digest cron event is currently scheduled. Save settings or toggle the digest setting to refresh the schedule.', 'smart-search-chips' ); ?></p>
                                                <?php if ( $digest_expected_next_run > 0 ) : ?>
                                                    <p class="description">
                                                        <?php
                                                        printf(
                                                            /* translators: %s: formatted next run date/time */
                                                            esc_html__( 'Based on the current controls, the next run would be %s.', 'smart-search-chips' ),
                                                            esc_html( $this->format_digest_run_time( $digest_expected_next_run ) )
                                                        );
                                                        ?>
                                                    </p>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Digest Content', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <label style="display:block;margin-bottom:6px;"><input type="hidden" name="sschp_analytics_digest_include_csv" value="0" /><input type="checkbox" name="sschp_analytics_digest_include_csv" value="1" <?php checked( $digest_csv, '1' ); ?> /> <?php esc_html_e( 'Attach CSV export', 'smart-search-chips' ); ?></label>
                                            <label style="display:block;margin-bottom:6px;"><input type="hidden" name="sschp_analytics_digest_include_zero_results" value="0" /><input type="checkbox" name="sschp_analytics_digest_include_zero_results" value="1" <?php checked( $digest_zero, '1' ); ?> /> <?php esc_html_e( 'Include top zero-result searches', 'smart-search-chips' ); ?></label>
                                            <label style="display:block;margin-bottom:6px;"><input type="hidden" name="sschp_analytics_digest_include_top_chips" value="0" /><input type="checkbox" name="sschp_analytics_digest_include_top_chips" value="1" <?php checked( $digest_top_chips, '1' ); ?> /> <?php esc_html_e( 'Include top clicked chips', 'smart-search-chips' ); ?></label>
                                            <label style="display:block;margin-bottom:6px;"><input type="hidden" name="sschp_analytics_digest_include_low_ctr" value="0" /><input type="checkbox" name="sschp_analytics_digest_include_low_ctr" value="1" <?php checked( $digest_low_ctr, '1' ); ?> /> <?php esc_html_e( 'Include low CTR chips', 'smart-search-chips' ); ?></label>
                                        </td>
                                    </tr>
                                    <?php if ( class_exists( 'SSCHP_Analytics' ) ) : ?>
                                        <tr>
                                            <th><?php esc_html_e( 'Test Email', 'smart-search-chips' ); ?></th>
                                            <td>
                                                <a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', SSCHP_Analytics::TEST_DIGEST_ACTION, admin_url( 'admin-post.php' ) ), SSCHP_Analytics::TEST_DIGEST_ACTION ) ); ?>"><?php esc_html_e( 'Send test analytics email', 'smart-search-chips' ); ?></a>
                                                <p class="description"><?php esc_html_e( 'Sends the current digest to the configured recipients. Analytics must be enabled, but the scheduled digest toggle can remain off while testing.', 'smart-search-chips' ); ?></p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </table>
                                <?php if ( class_exists( 'SSCHP_Analytics' ) ) : ?>
                                    <?php if ( ! empty( $digest_preview ) ) : ?>
                                        <?php
                                        $preview_recipients = isset( $digest_preview['recipients'] ) && is_array( $digest_preview['recipients'] ) ? $digest_preview['recipients'] : array();
                                        $preview_range      = isset( $digest_preview['range'] ) && is_array( $digest_preview['range'] ) ? $digest_preview['range'] : array();
                                        ?>
                                        <div class="ssc-analytics-email-preview">
                                            <h3><?php esc_html_e( 'Preview Next Analytics Email', 'smart-search-chips' ); ?></h3>
                                            <p class="description"><?php echo esc_html( $digest_preview['generated_by'] ?? __( 'Analytics reports are generated locally and emailed only to configured recipients.', 'smart-search-chips' ) ); ?></p>
                                            <dl>
                                                <div>
                                                    <dt><?php esc_html_e( 'Recipients', 'smart-search-chips' ); ?></dt>
                                                    <dd><?php echo esc_html( ! empty( $preview_recipients ) ? implode( ', ', $preview_recipients ) : __( 'No valid recipients configured', 'smart-search-chips' ) ); ?></dd>
                                                </div>
                                                <div>
                                                    <dt><?php esc_html_e( 'Subject', 'smart-search-chips' ); ?></dt>
                                                    <dd><?php echo esc_html( $digest_preview['subject'] ?? '' ); ?></dd>
                                                </div>
                                                <div>
                                                    <dt><?php esc_html_e( 'Date Range', 'smart-search-chips' ); ?></dt>
                                                    <dd>
                                                        <?php
                                                        printf(
                                                            /* translators: %1$s: start date, %2$s: end date */
                                                            esc_html__( '%1$s to %2$s', 'smart-search-chips' ),
                                                            esc_html( $preview_range['from'] ?? '' ),
                                                            esc_html( $preview_range['to'] ?? '' )
                                                        );
                                                        ?>
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt><?php esc_html_e( 'CSV Attachment', 'smart-search-chips' ); ?></dt>
                                                    <dd><?php echo esc_html( ! empty( $digest_preview['include_csv'] ) ? __( 'Enabled', 'smart-search-chips' ) : __( 'Not attached', 'smart-search-chips' ) ); ?></dd>
                                                </div>
                                            </dl>
                                            <div class="ssc-analytics-email-preview__body">
                                                <?php echo wp_kses( $digest_preview['message'] ?? '', $this->get_analytics_email_preview_allowed_html() ); ?>
                                            </div>
                                        </div>
                                    <?php else : ?>
                                        <div class="ssc-analytics-email-preview ssc-analytics-email-preview--empty">
                                            <h3><?php esc_html_e( 'Preview Next Analytics Email', 'smart-search-chips' ); ?></h3>
                                            <p class="description"><?php esc_html_e( 'Enable analytics and save settings to preview the next locally generated email report.', 'smart-search-chips' ); ?></p>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ( $analytics_on === '1' && SSCHP_License::is_pro() && class_exists( 'SSCHP_Analytics' ) ) : ?>
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
                                    <tr>
                                        <th><?php esc_html_e( 'Override AJAX Action', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="sschp_override_ajax" value="<?php echo esc_attr( get_option( 'sschp_override_ajax', '' ) ); ?>" class="regular-text" />
                                            <p class="description"><?php esc_html_e( 'Optional. Use your theme\'s existing AJAX search action name (for example `search_processing`) if you need Smart Search Chips to hook into it.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Keyword Field Name', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="sschp_ajax_keyword_field" value="<?php echo esc_attr( get_option( 'sschp_ajax_keyword_field', 'keyword' ) ); ?>" class="regular-text" />
                                            <p class="description"><?php esc_html_e( 'The request field that contains the typed search term. Common values are `keyword`, `s`, `query`, and `search`.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Search Input Selector', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="sschp_search_input_selector" value="<?php echo esc_attr( get_option( 'sschp_search_input_selector', '.ssc-search__input, input[type="search"], input[name="keyword"], input[name="s"], input[name="query"], input[name="search"]' ) ); ?>" class="large-text" />
                                            <p class="description"><?php esc_html_e( 'CSS selector used to find the live-search input. Keep the default unless your theme uses a custom search field.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Result Container Selector', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="sschp_result_container_selector" value="<?php echo esc_attr( get_option( 'sschp_result_container_selector', '.js-ssc-results, .js-search__results, .ssc-search__results' ) ); ?>" class="large-text" />
                                            <p class="description"><?php esc_html_e( 'CSS selector for the theme element that receives AJAX results, for example `.js-search__results`.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Result Image Fit', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <?php $image_fit = $this->sanitize_result_image_fit( get_option( 'sschp_result_image_fit', '' ) ); ?>
                                            <select name="sschp_result_image_fit">
                                                <option value="" <?php selected( $image_fit, '' ); ?>><?php esc_html_e( 'Theme/default', 'smart-search-chips' ); ?></option>
                                                <option value="contain" <?php selected( $image_fit, 'contain' ); ?>><?php esc_html_e( 'Contain', 'smart-search-chips' ); ?></option>
                                                <option value="cover" <?php selected( $image_fit, 'cover' ); ?>><?php esc_html_e( 'Cover', 'smart-search-chips' ); ?></option>
                                            </select>
                                            <p class="description"><?php esc_html_e( 'Leave as Theme/default unless SSC-rendered AJAX results need explicit image fitting.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Result Image Size', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="sschp_result_image_size" value="<?php echo esc_attr( get_option( 'sschp_result_image_size', 'medium' ) ); ?>" class="regular-text" />
                                            <p class="description"><?php esc_html_e( 'WordPress image size used for AJAX results. Common values: `thumbnail`, `medium`, `large`, `full`. Developers can override this with the `sschp_result_image_size` filter.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e( 'Result Image Display Size', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="number" name="sschp_result_image_display_size" value="<?php echo esc_attr( get_option( 'sschp_result_image_display_size', '' ) ); ?>" class="small-text" min="0" step="1" />
                                            <p class="description"><?php esc_html_e( 'Optional pixel size for SSC-rendered AJAX result thumbnails. Leave empty or 0 to avoid forcing a display size.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
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
                            <p class="ssc-preview-save">
                                <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'smart-search-chips' ); ?></button>
                            </p>
                        </div>

                        <div class="ssc-sidebar-card ssc-sidebar-card--pro">
                            <?php if ( $is_pro ) : ?>
                                <h3><?php esc_html_e( 'Pro Active', 'smart-search-chips' ); ?></h3>
                                <p><?php esc_html_e( 'This site can use Pro features including ACF/custom fields, numeric sliders, analytics, synonyms, manual curation, and AI-assisted tag workflows.', 'smart-search-chips' ); ?></p>
                                <?php if ( SSCHP_License::get_account_url() ) : ?>
                                    <p><a class="button button-secondary" href="<?php echo esc_url( SSCHP_License::get_account_url() ); ?>"><?php esc_html_e( 'Manage License', 'smart-search-chips' ); ?></a></p>
                                <?php endif; ?>
                            <?php else : ?>
                                <h3><?php esc_html_e( 'Need Pro Features?', 'smart-search-chips' ); ?></h3>
                                <p><?php esc_html_e( 'Pro is a separate upgrade for ACF/custom fields, numeric sliders, analytics, synonyms, manual curation, AI-assisted tag workflows, and commercial support.', 'smart-search-chips' ); ?></p>
                                <ul>
                                    <li><?php esc_html_e( 'Map existing ACF or custom tag fields.', 'smart-search-chips' ); ?></li>
                                    <li><?php esc_html_e( 'Turn numeric chip variants into range sliders.', 'smart-search-chips' ); ?></li>
                                    <li><?php esc_html_e( 'Review local search analytics and curate chip labels.', 'smart-search-chips' ); ?></li>
                                </ul>
                                <p>
                                    <a class="button button-primary" href="<?php echo esc_url( $this->get_upgrade_url() ); ?>"><?php esc_html_e( 'Upgrade to Pro', 'smart-search-chips' ); ?></a>
                                    <a class="button button-secondary" href="<?php echo esc_url( $this->get_pro_features_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Features', 'smart-search-chips' ); ?></a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
            <form id="ssc-delete-data-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="sschp_delete_plugin_data_now" />
                <?php wp_nonce_field( 'sschp_delete_plugin_data_now' ); ?>
            </form>
            <?php if ( $is_pro && class_exists( 'SSCHP_Chip_Rules' ) ) : ?>
                <form id="ssc-chip-rules-import-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="<?php echo esc_attr( SSCHP_Chip_Rules::IMPORT_ACTION ); ?>" />
                    <?php wp_nonce_field( SSCHP_Chip_Rules::IMPORT_ACTION ); ?>
                </form>
            <?php endif; ?>

            <p class="ssc-feedback-link">
                <?php esc_html_e( 'Questions, bugs, or ideas?', 'smart-search-chips' ); ?>
                <a href="mailto:[redacted]?subject=Smart%20Search%20Chips%20Feedback"><?php esc_html_e( 'Send feedback', 'smart-search-chips' ); ?> &rarr;</a>
            </p>
        </div>
        <?php
    }

    /**
     * Format a cron timestamp for the admin digest schedule display.
     *
     * @param int $timestamp Unix timestamp.
     * @return string
     */
    private function format_digest_run_time( $timestamp ) {
        $timestamp = absint( $timestamp );
        if ( $timestamp <= 0 ) {
            return '';
        }

        $format = get_option( 'date_format', 'Y-m-d' ) . ' ' . get_option( 'time_format', 'H:i' );
        if ( function_exists( 'wp_date' ) ) {
            $timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : null;
            return wp_date( $format, $timestamp, $timezone );
        }

        if ( function_exists( 'date_i18n' ) ) {
            return date_i18n( $format, $timestamp );
        }

        return gmdate( 'Y-m-d H:i', $timestamp );
    }

    /**
     * Allow the generated analytics email markup inside the admin preview.
     *
     * @return array
     */
    private function get_analytics_email_preview_allowed_html() {
        $style_attribute = array( 'style' => true );

        return array(
            'a'      => array_merge( $style_attribute, array( 'href' => true, 'target' => true, 'rel' => true ) ),
            'br'     => array(),
            'div'    => $style_attribute,
            'h1'     => $style_attribute,
            'h2'     => $style_attribute,
            'li'     => $style_attribute,
            'p'      => $style_attribute,
            'span'   => $style_attribute,
            'strong' => $style_attribute,
            'table'  => array_merge( $style_attribute, array( 'cellpadding' => true, 'cellspacing' => true, 'border' => true ) ),
            'tbody'  => $style_attribute,
            'td'     => array_merge( $style_attribute, array( 'align' => true ) ),
            'th'     => array_merge( $style_attribute, array( 'align' => true ) ),
            'thead'  => $style_attribute,
            'tr'     => $style_attribute,
            'ul'     => $style_attribute,
        );
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

    /**
     * Dismiss the settings-page setup panel for the current administrator.
     *
     * @return void
     */
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

    // ─── Helper renderers ────────────────────────────────────────────

    private function render_post_types() {
        $selected = get_option( 'sschp_post_types', array( 'post' ) );
        $selected = $this->sanitize_post_types( $selected );

        $post_types = get_post_types( array( 'public' => true ), 'objects' );

        echo '<input type="hidden" name="sschp_post_types[]" value="" />';

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
        $range_args   = $this->get_admin_analytics_args();
        $range     = array( 'from' => '', 'to' => '' );
        $analytics = null;
        $events    = array();

        if ( SSCHP_License::is_pro() && class_exists( 'SSCHP_Analytics' ) ) {
            $is_pro    = SSCHP_License::is_pro();
            $range     = SSCHP_Analytics::get_date_range( $range_args );
            $analytics = $is_pro && $analytics_on === '1' ? SSCHP_Analytics::get_dashboard_data( $range_args ) : null;
            $events    = $is_pro && $analytics_on === '1' ? SSCHP_Analytics::get_recent_events( array_merge( $range_args, array( 'limit' => 150 ) ) ) : array();
        }
        $export_url   = wp_nonce_url(
            add_query_arg(
                array(
                    'action' => 'sschp_export_analytics',
                    'from'   => $range['from'],
                    'to'     => $range['to'],
                    'language' => isset( $range_args['language'] ) ? $range_args['language'] : '',
                    'post_type' => isset( $range_args['post_type'] ) ? $range_args['post_type'] : '',
                    'product_category' => isset( $range_args['product_category'] ) ? $range_args['product_category'] : '',
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
                <label>
                    <span><?php esc_html_e( 'Language', 'smart-search-chips' ); ?></span>
                    <input type="text" name="language" value="<?php echo esc_attr( isset( $range_args['language'] ) ? $range_args['language'] : '' ); ?>" placeholder="en" />
                </label>
                <label>
                    <span><?php esc_html_e( 'Post Type', 'smart-search-chips' ); ?></span>
                    <select name="post_type">
                        <option value=""><?php esc_html_e( 'All', 'smart-search-chips' ); ?></option>
                        <?php foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) : ?>
                            <?php
                            if ( 'attachment' === $type->name ) {
                                continue;
                            }
                            ?>
                            <option value="<?php echo esc_attr( $type->name ); ?>" <?php selected( isset( $range_args['post_type'] ) ? $range_args['post_type'] : '', $type->name ); ?>><?php echo esc_html( $type->label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                    <label>
                        <span><?php esc_html_e( 'Product Category', 'smart-search-chips' ); ?></span>
                        <input type="text" name="product_category" value="<?php echo esc_attr( isset( $range_args['product_category'] ) ? $range_args['product_category'] : '' ); ?>" placeholder="category-slug" />
                    </label>
                <?php endif; ?>
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Apply Filters', 'smart-search-chips' ); ?></button>
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
                        <strong><?php echo esc_html( number_format_i18n( $analytics['summary']['zero_result_rate'], 1 ) . '%' ); ?></strong>
                        <span><?php esc_html_e( 'Zero-Result Rate', 'smart-search-chips' ); ?></span>
                    </div>
                    <div class="ssc-analytics-stat">
                        <strong><?php echo esc_html( number_format_i18n( $analytics['summary']['chip_impressions'] ) ); ?></strong>
                        <span><?php esc_html_e( 'Chip Impressions', 'smart-search-chips' ); ?></span>
                    </div>
                    <div class="ssc-analytics-stat">
                        <strong><?php echo esc_html( number_format_i18n( $analytics['summary']['chip_click_through_rate'], 1 ) . '%' ); ?></strong>
                        <span><?php esc_html_e( 'Chip CTR', 'smart-search-chips' ); ?></span>
                    </div>
                    <div class="ssc-analytics-stat">
                        <strong><?php echo esc_html( number_format_i18n( $analytics['summary']['result_clicks'] ) ); ?></strong>
                        <span><?php esc_html_e( 'Result Clicks', 'smart-search-chips' ); ?></span>
                    </div>
                    <div class="ssc-analytics-stat">
                        <strong><?php echo esc_html( number_format_i18n( $analytics['summary']['search_to_result_click_rate'], 1 ) . '%' ); ?></strong>
                        <span><?php esc_html_e( 'Search To Result Click', 'smart-search-chips' ); ?></span>
                    </div>
                </div>

                <div class="ssc-analytics-charts">
                    <div class="ssc-analytics-panel">
                        <h3><?php esc_html_e( 'Search Activity Over Time', 'smart-search-chips' ); ?></h3>
                        <div class="ssc-chart">
                            <?php $this->render_analytics_chart( $analytics['timeseries'], array( 'searches' => '#1d4ed8', 'chip_clicks' => '#d97706', 'result_clicks' => '#7c3aed' ) ); ?>
                            <div class="ssc-chart-meta">
                                <span class="ssc-chart-searches"><?php esc_html_e( 'Searches', 'smart-search-chips' ); ?></span>
                                <span class="ssc-chart-clicks"><?php esc_html_e( 'Chip Clicks', 'smart-search-chips' ); ?></span>
                                <span><?php esc_html_e( 'Result Clicks', 'smart-search-chips' ); ?></span>
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
                        <?php $this->render_analytics_table( $analytics['top_queries'], array( 'search_query' => __( 'Query', 'smart-search-chips' ), 'searches' => __( 'Searches', 'smart-search-chips' ), 'chip_clicks' => __( 'Chip Clicks', 'smart-search-chips' ), 'result_clicks' => __( 'Result Clicks', 'smart-search-chips' ), 'result_click_rate' => __( 'Result CTR', 'smart-search-chips' ), 'avg_results' => __( 'Avg. Results', 'smart-search-chips' ) ) ); ?>
                    </div>
                    <div class="ssc-analytics-panel">
                        <h3><?php esc_html_e( 'Top Zero-Result Queries Over Time', 'smart-search-chips' ); ?></h3>
                        <?php $this->render_analytics_table( $analytics['zero_queries'], array( 'search_query' => __( 'Query', 'smart-search-chips' ), 'misses' => __( 'Misses', 'smart-search-chips' ), 'first_seen' => __( 'First Seen', 'smart-search-chips' ), 'last_seen' => __( 'Last Seen', 'smart-search-chips' ) ) ); ?>
                    </div>
                    <div class="ssc-analytics-panel">
                        <h3><?php esc_html_e( 'Top Query Refinements', 'smart-search-chips' ); ?></h3>
                        <?php $this->render_analytics_table( $analytics['top_refinements'], array( 'search_query' => __( 'Query', 'smart-search-chips' ), 'chip_label' => __( 'Chip', 'smart-search-chips' ), 'refinements' => __( 'Refinements', 'smart-search-chips' ), 'visitors' => __( 'Visitors', 'smart-search-chips' ) ) ); ?>
                    </div>
                    <div class="ssc-analytics-panel">
                        <h3><?php esc_html_e( 'Often Seen, Rarely Clicked Chips', 'smart-search-chips' ); ?></h3>
                        <?php $this->render_analytics_table( $analytics['low_chip_ctr'], array( 'chip_label' => __( 'Chip', 'smart-search-chips' ), 'impressions' => __( 'Impressions', 'smart-search-chips' ), 'clicks' => __( 'Clicks', 'smart-search-chips' ), 'click_through_rate' => __( 'CTR', 'smart-search-chips' ) ) ); ?>
                    </div>
                </div>

                <div class="ssc-analytics-panel">
                    <h3><?php esc_html_e( 'Recent Events', 'smart-search-chips' ); ?></h3>
                    <?php $this->render_analytics_table( $events, array( 'created_at' => __( 'Date', 'smart-search-chips' ), 'event_type' => __( 'Event', 'smart-search-chips' ), 'language_code' => __( 'Lang', 'smart-search-chips' ), 'post_type' => __( 'Type', 'smart-search-chips' ), 'search_query' => __( 'Query', 'smart-search-chips' ), 'chip_label' => __( 'Chip', 'smart-search-chips' ), 'result_object_id' => __( 'Result ID', 'smart-search-chips' ), 'result_position' => __( 'Position', 'smart-search-chips' ), 'result_count' => __( 'Results', 'smart-search-chips' ) ) ); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Read the optional analytics filters from the admin request.
     *
     * @return array
     */
    private function get_admin_analytics_args() {
        return array(
            'from'             => $this->get_admin_text_query_var( 'from' ),
            'to'               => $this->get_admin_text_query_var( 'to' ),
            'language'         => sanitize_key( $this->get_admin_text_query_var( 'language' ) ),
            'post_type'        => sanitize_key( $this->get_admin_text_query_var( 'post_type' ) ),
            'product_category' => sanitize_title( $this->get_admin_text_query_var( 'product_category' ) ),
        );
    }

    /**
     * Read and sanitize a text filter query variable.
     *
     * @param string $key Query variable name.
     * @return string
     */
    private function get_admin_text_query_var( $key ) {
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
                } elseif ( in_array( $key, array( 'click_rate', 'click_through_rate', 'search_to_click_rate', 'result_click_rate', 'zero_result_rate', 'chip_click_through_rate', 'search_to_result_click_rate' ), true ) ) {
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

    /**
     * Sanitize newline/comma separated digest recipients.
     *
     * @param mixed $value Raw recipients value.
     * @return string
     */
    public function sanitize_email_recipients( $value ) {
        $parts  = preg_split( '/[\s,;]+/', (string) $value );
        $emails = array();

        foreach ( (array) $parts as $part ) {
            $email = sanitize_email( $part );
            if ( '' === $email ) {
                continue;
            }

            if ( is_email( $email ) ) {
                if ( ! in_array( $email, $emails, true ) ) {
                    $emails[] = $email;
                }
            } else {
                add_settings_error(
                    'smart-search-chips',
                    'sschp_digest_invalid_email',
                    __( 'One or more analytics digest recipient emails were invalid and were removed.', 'smart-search-chips' ),
                    'warning'
                );
            }
        }

        return implode( "\n", $emails );
    }

    /**
     * Sanitize digest frequency.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public function sanitize_digest_frequency( $value ) {
        $value = sanitize_key( $value );

        return in_array( $value, array( 'weekly', 'monthly' ), true ) ? $value : 'weekly';
    }

    /**
     * Sanitize digest day.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public function sanitize_digest_day( $value ) {
        $value    = sanitize_key( $value );
        $weekdays = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );

        if ( in_array( $value, $weekdays, true ) || preg_match( '/^(?:[1-9]|1[0-9]|2[0-8])$/', $value ) ) {
            return $value;
        }

        add_settings_error(
            'smart-search-chips',
            'sschp_digest_invalid_day',
            __( 'Analytics digest day must be a weekday name or a monthly day from 1 to 28.', 'smart-search-chips' ),
            'warning'
        );

        return 'monday';
    }

    /**
     * Sanitize digest send time.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public function sanitize_digest_time( $value ) {
        $value = trim( (string) $value );

        if ( preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $value ) ) {
            return $value;
        }

        add_settings_error(
            'smart-search-chips',
            'sschp_digest_invalid_time',
            __( 'Analytics digest time must use HH:MM 24-hour format.', 'smart-search-chips' ),
            'warning'
        );

        return '09:00';
    }

    /**
     * Sanitize digest date range.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public function sanitize_digest_date_range( $value ) {
        $value = absint( $value );

        return in_array( $value, array( 7, 30, 90 ), true ) ? $value : 30;
    }

    /**
     * Sanitize the selected searchable post types.
     *
     * @param mixed $value Raw option value.
     * @return array
     */
    public function sanitize_post_types( $value ) {
        $selected = is_array( $value ) ? $value : array( $value );
        $selected = array_filter( array_map( 'sanitize_key', $selected ) );
        $public   = get_post_types( array( 'public' => true ), 'names' );
        $public   = array_diff( $public, array( 'attachment' ) );

        $selected = array_values( array_intersect( $selected, $public ) );

        return ! empty( $selected ) ? $selected : array( 'post' );
    }

    /**
     * Sanitize a hex color option.
     *
     * @param mixed $value Raw option value.
     * @return string
     */
    public function sanitize_hex_color_setting( $value ) {
        $color = sanitize_hex_color( (string) $value );
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
     * PHP cannot fully parse CSS selectors, so this rejects only values that are
     * obviously unsafe or broken and keeps the previous valid value otherwise.
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

    /**
     * Sanitize the max chips setting.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public function sanitize_max_chips( $value ) {
        return $this->sanitize_numeric_setting( $value, 8, 1, SSCHP_License::max_chips() );
    }

    /**
     * Sanitize the max tag length setting.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public function sanitize_max_tag_length( $value ) {
        return $this->sanitize_numeric_setting( $value, 40, 5, 120 );
    }

    /**
     * Sanitize the visible result count.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public function sanitize_max_results( $value ) {
        $value = $this->sanitize_numeric_setting( $value, 10, 1, 100 );
        if ( $value > 50 ) {
            add_settings_error(
                'smart-search-chips',
                'sschp_high_render_limit',
                __( 'High Results Shown values can slow down live search rendering on large result sets.', 'smart-search-chips' ),
                'warning'
            );
        }

        return $value;
    }

    /**
     * Sanitize the analyzed result count.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public function sanitize_search_results_count( $value ) {
        $value = $this->sanitize_numeric_setting( $value, 50, 5, 200 );
        if ( $value > 100 ) {
            add_settings_error(
                'smart-search-chips',
                'sschp_high_analysis_limit',
                __( 'High Results to Analyze values can be expensive on large catalogs. Keep this near 50 unless indexing/search infrastructure can handle more.', 'smart-search-chips' ),
                'warning'
            );
        }

        return $value;
    }

    /**
     * Sanitize live search debounce delay.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public function sanitize_debounce_ms( $value ) {
        return $this->sanitize_numeric_setting( $value, 300, 50, 2000 );
    }

    /**
     * Sanitize the Relevanssi relevance threshold.
     *
     * @param mixed $value Raw value.
     * @return float
     */
    public function sanitize_relevance_threshold( $value ) {
        if ( '' === $value || ! is_numeric( $value ) ) {
            return 0.3;
        }

        $value = (float) $value;
        $value = max( 0, $value );
        $value = min( 1, $value );

        return $value;
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
     * Public product page for feature details.
     *
     * @return string
     */
    private function get_pro_features_url() {
        return 'https://smartsearchchips.app/';
    }

    /**
     * Prefer Freemius checkout in the Pro-capable build, with website fallback.
     *
     * @return string
     */
    private function get_upgrade_url() {
        $upgrade_url = SSCHP_License::get_upgrade_url();

        return $upgrade_url ? $upgrade_url : $this->get_pro_features_url();
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
