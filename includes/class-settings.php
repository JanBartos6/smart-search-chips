<?php
/**
 * Settings - Admin settings page with live preview panel
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSC_Settings {

    /** @var string Options prefix */
    private $prefix = 'ssc_';

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_filter( 'plugin_action_links_' . SSC_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
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
            'post_types', 'tag_fields', 'extra_fields', 'override_ajax',
            'max_chips', 'max_tag_length',
            'woo_enabled',
            'chip_color', 'chip_text_color', 'chip_inactive_color', 'chip_inactive_text_color', 'chip_radius', 'chip_font_size', 'chip_padding_x', 'chip_padding_y',
            'max_results', 'search_results_count', 'relevance_threshold',
        );
        foreach ( $fields as $f ) {
            register_setting( 'smart-search-chips', $this->prefix . $f );
        }

        register_setting( 'smart-search-chips', 'ssc_ai_custom_read_fields', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'smart-search-chips', 'ssc_ai_target_field', array(
            'sanitize_callback' => array( $this, 'sanitize_ai_target_field' ),
        ) );
        register_setting( 'smart-search-chips', 'ssc_ai_rest_enabled', array(
            'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
        ) );
        register_setting( 'smart-search-chips', 'ssc_ai_max_tags', array(
            'sanitize_callback' => 'absint',
        ) );
        register_setting( 'smart-search-chips', 'ssc_ai_language', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'smart-search-chips', 'ssc_ai_custom_instruction', array(
            'sanitize_callback' => 'sanitize_textarea_field',
        ) );
    }

    /**
     * Enqueue admin assets for the settings page.
     *
     * @param string $hook_suffix Current admin page hook.
     */
    public function enqueue_admin_assets( $hook_suffix ) {
        if ( $hook_suffix !== 'settings_page_smart-search-chips' ) {
            return;
        }

        wp_enqueue_script(
            'smart-search-chips-admin-ai',
            SSC_PLUGIN_URL . 'assets/js/admin-ai.js',
            array(),
            SSC_VERSION,
            true
        );

        wp_localize_script( 'smart-search-chips-admin-ai', 'sscAiAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( SSC_AI_Tagger::AJAX_NONCE ),
            'strings' => array(
                'working'             => __( 'Working...', 'smart-search-chips' ),
                'loadingTerms'        => __( 'Loading categories...', 'smart-search-chips' ),
                'loadTermsFailed'     => __( 'Could not load categories for the picker.', 'smart-search-chips' ),
                'loadingItems'        => __( 'Loading items for this category...', 'smart-search-chips' ),
                'loadItemsFailed'     => __( 'Could not load items for this category.', 'smart-search-chips' ),
                'searchPlaceholder'   => __( 'Filter categories or products...', 'smart-search-chips' ),
                'selectionEmptyShort' => __( 'No items selected yet.', 'smart-search-chips' ),
                'selectionEmpty'      => __( 'No items selected yet. Build a batch on the left and the selected items will stay pinned here.', 'smart-search-chips' ),
                'selectionCount'      => __( '%d items selected', 'smart-search-chips' ),
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

    /**
     * Render the full settings page with two-column layout.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $is_pro = SSC_License::is_pro();
        $relevanssi_active = function_exists( 'relevanssi_do_query' );

        // Get current values
        $chip_color    = get_option( 'ssc_chip_color', '#e74c3c' );
        $chip_text     = get_option( 'ssc_chip_text_color', '#ffffff' );
        $chip_radius   = get_option( 'ssc_chip_radius', '16' );
        $chip_font     = get_option( 'ssc_chip_font_size', '13' );
        $chip_pad_x    = get_option( 'ssc_chip_padding_x', '12' );
        $chip_pad_y    = get_option( 'ssc_chip_padding_y', '4' );
        $ai_max_tags   = max( 0, (int) get_option( 'ssc_ai_max_tags', 8 ) );
        $ai_rest_mode  = get_option( 'ssc_ai_rest_enabled', '1' );
        $ai_target     = $this->sanitize_ai_target_field( get_option( 'ssc_ai_target_field', 'built_in' ) );

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <?php if ( $is_pro && ! $relevanssi_active ) : ?>
                <div class="notice notice-info"><p><strong><?php _e( 'Relevanssi not detected.', 'smart-search-chips' ); ?></strong> <?php _e( 'Install Relevanssi (free) for relevance scoring.', 'smart-search-chips' ); ?></p></div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields( 'smart-search-chips' ); ?>

                <div id="ssc-settings-layout">
                    <!-- LEFT: Settings -->
                    <div id="ssc-settings-left">

                        <!-- License -->
                        <div class="ssc-settings-card">
                            <h2><?php _e( 'License', 'smart-search-chips' ); ?></h2>
                            <?php
                            $key    = get_option( SSC_License::LICENSE_KEY_OPTION, '' );
                            $status = get_option( SSC_License::LICENSE_STATUS_OPTION, 'free' );
                            ?>
                            <?php if ( $is_pro ) : ?>
                                <div class="ssc-badge ssc-badge--pro">Pro</div>
                            <?php else : ?>
                                <div class="ssc-badge ssc-badge--free">Free</div>
                            <?php endif; ?>
                            <table class="form-table"><tr>
                                <th><?php _e( 'License Key', 'smart-search-chips' ); ?></th>
                                <td>
                                    <input type="text" name="ssc_license_key" value="<?php echo esc_attr( $key ); ?>" class="regular-text" placeholder="SSC-PRO-XXXX-XXXX-XXXX" />
                                    <?php if ( $key && $status === 'valid' ) : ?>
                                        <span style="color:green;">&#10003; <?php _e( 'Active', 'smart-search-chips' ); ?></span>
                                    <?php elseif ( $key ) : ?>
                                        <span style="color:red;">&#10007; <?php _e( 'Invalid', 'smart-search-chips' ); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr></table>
                        </div>

                        <!-- Search Settings -->
                        <div class="ssc-settings-card">
                            <h2><?php _e( 'Search', 'smart-search-chips' ); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th><?php _e( 'Post Types', 'smart-search-chips' ); ?></th>
                                    <td><?php $this->render_post_types(); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Tag Source', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <p><?php _e( 'Built-in "Search Chips Tags" field (`ssc_tags`) is used by default.', 'smart-search-chips' ); ?></p>
                                        <?php if ( $is_pro ) : ?>
                                            <p class="description"><?php _e( 'If you already use your own tag meta field, you can map it in Advanced settings.', 'smart-search-chips' ); ?></p>
                                        <?php else : ?>
                                            <p class="description"><?php _e( 'Upgrade to Pro to map an existing custom field instead.', 'smart-search-chips' ); ?></p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                                <tr>
                                    <th><?php _e( 'WooCommerce Auto-Tags', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="ssc_woo_enabled" value="1" <?php checked( get_option( 'ssc_woo_enabled', '1' ), '1' ); ?> />
                                            <?php _e( 'Auto-populate tags from product attributes, categories, and SKU', 'smart-search-chips' ); ?>
                                        </label>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>

                        <!-- Chip Settings -->
                        <div class="ssc-settings-card">
                            <h2><?php _e( 'Chips', 'smart-search-chips' ); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th><?php _e( 'Max Chips', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <input type="number" name="ssc_max_chips" value="<?php echo esc_attr( min( (int) get_option( 'ssc_max_chips', '5' ), SSC_License::max_chips() ) ); ?>" class="small-text" min="1" max="<?php echo SSC_License::max_chips(); ?>" />
                                        <?php if ( ! $is_pro ) : ?>
                                            <span class="description"><?php _e( 'Free: max 5', 'smart-search-chips' ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Max Tag Length', 'smart-search-chips' ); ?></th>
                                    <td><input type="number" name="ssc_max_tag_length" value="<?php echo esc_attr( get_option( 'ssc_max_tag_length', '40' ) ); ?>" class="small-text" min="5" /></td>
                                </tr>
                            </table>
                        </div>

                        <!-- Design -->
                        <div class="ssc-settings-card">
                            <h2><?php _e( 'Design', 'smart-search-chips' ); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th><?php _e( 'Active Color', 'smart-search-chips' ); ?></th>
                                    <td><input type="color" name="ssc_chip_color" value="<?php echo esc_attr( $chip_color ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Active Text', 'smart-search-chips' ); ?></th>
                                    <td><input type="color" name="ssc_chip_text_color" value="<?php echo esc_attr( $chip_text ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Inactive Color', 'smart-search-chips' ); ?></th>
                                    <td><input type="color" name="ssc_chip_inactive_color" value="<?php echo esc_attr( get_option( 'ssc_chip_inactive_color', '#ffffff' ) ); ?>" /></td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Inactive Text', 'smart-search-chips' ); ?></th>
                                    <td><input type="color" name="ssc_chip_inactive_text_color" value="<?php echo esc_attr( get_option( 'ssc_chip_inactive_text_color', '#333333' ) ); ?>" /></td>
                                </tr>
                                <?php if ( $is_pro ) : ?>
                                <tr>
                                    <th><?php _e( 'Border Radius', 'smart-search-chips' ); ?></th>
                                    <td><input type="number" name="ssc_chip_radius" value="<?php echo esc_attr( $chip_radius ); ?>" class="small-text" min="0" max="50" placeholder="16" /> px</td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Font Size', 'smart-search-chips' ); ?></th>
                                    <td><input type="number" name="ssc_chip_font_size" value="<?php echo esc_attr( $chip_font ); ?>" class="small-text" min="8" max="24" placeholder="13" /> px</td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Padding H', 'smart-search-chips' ); ?></th>
                                    <td><input type="number" name="ssc_chip_padding_x" value="<?php echo esc_attr( $chip_pad_x ); ?>" class="small-text" min="0" max="40" placeholder="12" /> px</td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Padding V', 'smart-search-chips' ); ?></th>
                                    <td><input type="number" name="ssc_chip_padding_y" value="<?php echo esc_attr( $chip_pad_y ); ?>" class="small-text" min="0" max="24" placeholder="4" /> px</td>
                                </tr>
                                <?php else : ?>
                                <tr>
                                    <td colspan="2"><p class="description"><?php _e( 'Upgrade to Pro for border radius, font size, and padding controls.', 'smart-search-chips' ); ?></p></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>

                        <!-- Results -->
                        <div class="ssc-settings-card">
                            <h2><?php _e( 'Results', 'smart-search-chips' ); ?></h2>
                            <table class="form-table">
                                <tr>
                                    <th><?php _e( 'Results Shown', 'smart-search-chips' ); ?></th>
                                    <td><input type="number" name="ssc_max_results" value="<?php echo esc_attr( get_option( 'ssc_max_results', '10' ) ); ?>" class="small-text" min="1" /></td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Results to Analyze', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <input type="number" name="ssc_search_results_count" value="<?php echo esc_attr( get_option( 'ssc_search_results_count', '50' ) ); ?>" class="small-text" min="5" />
                                        <p class="description"><?php _e( 'More = better chips, slower. Recommended: 50.', 'smart-search-chips' ); ?></p>
                                    </td>
                                </tr>
                                <?php if ( $is_pro ) : ?>
                                <tr>
                                    <th><?php _e( 'Relevance Threshold', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <input type="range" name="ssc_relevance_threshold" value="<?php echo esc_attr( get_option( 'ssc_relevance_threshold', '0.3' ) ); ?>" min="0" max="1" step="0.05" />
                                        <span class="ssc-range-val"><?php echo esc_html( get_option( 'ssc_relevance_threshold', '0.3' ) ); ?></span>
                                        <p class="description"><?php _e( 'Requires Relevanssi. 0 = all results, 1 = exact matches only.', 'smart-search-chips' ); ?></p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>

                        <div class="ssc-settings-card ssc-settings-card--ai">
                            <h2><?php _e( 'AI Prompt Builder', 'smart-search-chips' ); ?></h2>
                            <?php if ( ! $is_pro ) : ?>
                                <p><?php _e( 'The AI Prompt Builder is part of Pro. It prepares a structured agent prompt that you can paste into Codex, Claude Code, ChatGPT, Gemini, or another external AI tool. That external tool can then use your own WordPress REST credentials to fetch and write tags.', 'smart-search-chips' ); ?></p>
                                <p class="description"><?php _e( 'Upgrade to Pro to unlock REST-aware prompt generation, multi-product analysis, custom field targets, and structured agent instructions. This plugin does not request or store any OpenAI, Anthropic, or Gemini API keys.', 'smart-search-chips' ); ?></p>
                            <?php else : ?>
                                <table class="form-table">
                                    <tr>
                                        <th><?php _e( 'REST API', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <label class="ssc-ai-toggle">
                                                <input type="hidden" name="ssc_ai_rest_enabled" value="0" />
                                                <input type="checkbox" name="ssc_ai_rest_enabled" id="ssc-ai-rest-enabled" value="1" <?php checked( $ai_rest_mode, '1' ); ?> />
                                                <span><?php _e( 'Allow REST API write-back in generated prompt', 'smart-search-chips' ); ?></span>
                                            </label>
                                            <p class="description"><?php _e( 'Turn this on if you want the external agent to read and write through the WordPress REST API. Turn it off if you only want the prompt to analyze the selected products and return tags for manual review.', 'smart-search-chips' ); ?></p>
                                            <p class="description"><?php _e( 'When enabled, the prompt assumes you will use WordPress Application Passwords or another REST-compatible WordPress authentication method outside this plugin. The plugin does not ask for or store LLM-provider credentials.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Extra Read Fields', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="ssc_ai_custom_read_fields" id="ssc-ai-custom-read-fields" value="<?php echo esc_attr( get_option( 'ssc_ai_custom_read_fields', '' ) ); ?>" class="regular-text" />
                                            <p class="description"><?php _e( 'Optional comma-separated meta keys the external agent should inspect as extra context if they are available over REST.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Target Field', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <?php $target_options = $this->get_ai_target_field_options(); ?>
                                            <select name="ssc_ai_target_field" id="ssc-ai-target-field">
                                                <?php foreach ( $target_options as $value => $label ) : ?>
                                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $ai_target, $value ); ?>><?php echo esc_html( $label ); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p class="description"><?php _e( 'By default, generated tags target the built-in `ssc_tags` field. If you already mapped your own custom tag field in Advanced settings, you can select it here instead.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Max Tags', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="number" name="ssc_ai_max_tags" id="ssc-ai-max-tags" value="<?php echo esc_attr( $ai_max_tags ); ?>" class="small-text" min="0" step="1" />
                                            <p class="description"><?php _e( 'Set to 0 for no limit. Any positive number caps the number of generated tags per item.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Language Override', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="ssc_ai_language" id="ssc-ai-language" value="<?php echo esc_attr( get_option( 'ssc_ai_language', '' ) ); ?>" class="regular-text" />
                                            <p class="description"><?php _e( 'Leave empty to keep chips in the same language as each selected product or post. Use this only if you want the agent prompt to force another language.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Custom Instruction', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <textarea name="ssc_ai_custom_instruction" id="ssc-ai-custom-instruction" rows="4" class="large-text"><?php echo esc_textarea( get_option( 'ssc_ai_custom_instruction', '' ) ); ?></textarea>
                                            <p class="description"><?php _e( 'Optional instructions for the external agent, for example: focus on technical differences, ignore brand names, keep tags very short, avoid generic marketing phrases, or use a specific language.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            <?php endif; ?>
                        </div>

                        <div class="ssc-settings-card ssc-settings-card--ai-lab">
                            <h2><?php _e( 'Agent Prompt', 'smart-search-chips' ); ?></h2>
                            <?php if ( ! $is_pro ) : ?>
                                <p class="description"><?php _e( 'The agent prompt builder becomes available in Pro. It prepares a long-form prompt that tells an external LLM agent to compare similar products together and, when enabled, write semicolon-separated tags back through the WordPress REST API.', 'smart-search-chips' ); ?></p>
                            <?php else : ?>
                                <table class="form-table">
                                    <tr>
                                        <th><?php _e( 'Selected Items', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <div class="ssc-ai-picker">
                                                <div class="ssc-ai-picker__browser">
                                                    <div class="ssc-ai-browser-toolbar">
                                                        <label class="screen-reader-text" for="ssc-ai-browser-search"><?php _e( 'Filter categories', 'smart-search-chips' ); ?></label>
                                                        <input type="search" id="ssc-ai-browser-search" class="regular-text" placeholder="<?php echo esc_attr__( 'Filter categories or products...', 'smart-search-chips' ); ?>" />
                                                        <button type="button" class="button" id="ssc-ai-browser-refresh"><?php _e( 'Refresh', 'smart-search-chips' ); ?></button>
                                                    </div>
                                                    <div class="ssc-ai-browser-meta">
                                                        <strong id="ssc-ai-browser-summary"><?php _e( 'Loading categories...', 'smart-search-chips' ); ?></strong>
                                                        <span class="description"><?php _e( 'Start from taxonomy groups like product categories. Expand a category to inspect its products, or add the whole category to the batch in one click.', 'smart-search-chips' ); ?></span>
                                                    </div>
                                                    <div id="ssc-ai-browser-results" class="ssc-ai-browser-results ssc-ai-browser-results--terms" aria-live="polite">
                                                        <div class="ssc-ai-browser-empty"><?php _e( 'Loading categories...', 'smart-search-chips' ); ?></div>
                                                    </div>
                                                </div>
                                                <div class="ssc-ai-picker__selection">
                                                    <div class="ssc-ai-selection-header">
                                                        <div>
                                                            <strong><?php _e( 'Analysis Batch', 'smart-search-chips' ); ?></strong>
                                                            <p class="description"><?php _e( 'These are the items that will be sent into one prompt together. Keep similar products in one batch so the external agent can spot what actually differentiates them.', 'smart-search-chips' ); ?></p>
                                                        </div>
                                                        <button type="button" class="button-link-delete" id="ssc-ai-clear-selection"><?php _e( 'Clear all', 'smart-search-chips' ); ?></button>
                                                    </div>
                                                    <div id="ssc-ai-selection-summary" class="ssc-ai-selection-summary"><?php _e( 'No items selected yet.', 'smart-search-chips' ); ?></div>
                                                    <div id="ssc-ai-selected-items" class="ssc-ai-selected-items">
                                                        <div class="ssc-ai-browser-empty"><?php _e( 'No items selected yet. Build a batch on the left and the selected items will stay pinned here.', 'smart-search-chips' ); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="description"><?php _e( 'Build the batch from categories first, then inspect individual products only where needed. This avoids endless paginated product lists and fits large catalogs much better.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Actions', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <button type="button" class="button button-primary" id="ssc-ai-build-prompt"><?php _e( 'Generate Agent Prompt', 'smart-search-chips' ); ?></button>
                                            <span id="ssc-ai-build-status" class="ssc-ai-inline-status" aria-live="polite"></span>
                                            <p class="description"><?php _e( 'The generated prompt will always describe the selected batch clearly. If REST write-back is enabled, it will also end with placeholders for your WordPress REST credentials. Paste those WordPress credentials into your external AI tool, not into this plugin.', 'smart-search-chips' ); ?></p>
                                            <p class="ssc-ai-warning"><?php _e( 'You remain responsible for your WordPress REST credentials and for any changes the external AI tool makes through your site. Review the prompt before use. This plugin does not request or store LLM-provider API keys.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                </table>
                                <div id="ssc-ai-rest-panel" class="ssc-ai-panel">
                                    <strong><?php _e( 'Run Summary', 'smart-search-chips' ); ?></strong>
                                    <div id="ssc-ai-rest-output"><?php _e( 'Generate the prompt to inspect the current batch, write mode, and output target.', 'smart-search-chips' ); ?></div>
                                </div>
                                <div id="ssc-ai-prompt-panel" class="ssc-ai-panel">
                                    <strong><?php _e( 'Generated Prompt', 'smart-search-chips' ); ?></strong>
                                    <textarea id="ssc-ai-prompt-output" class="large-text code" rows="18" readonly><?php echo esc_textarea( __( 'Generate the prompt to see the external-agent instructions here.', 'smart-search-chips' ) ); ?></textarea>
                                    <p style="margin-top:10px;">
                                        <button type="button" class="button button-secondary" id="ssc-ai-copy-prompt-panel"><?php _e( 'Copy Prompt', 'smart-search-chips' ); ?></button>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <details class="ssc-settings-card ssc-settings-card--collapsible">
                            <summary class="ssc-settings-card__summary">
                                <span class="ssc-settings-card__summary-title"><?php _e( 'Advanced', 'smart-search-chips' ); ?></span>
                            </summary>
                            <div class="ssc-settings-card__body">
                                <p class="description"><?php _e( 'Only change these if you already know your field names or need to integrate with an existing setup.', 'smart-search-chips' ); ?></p>
                                <table class="form-table">
                                    <?php if ( $is_pro ) : ?>
                                    <tr>
                                        <th><?php _e( 'Custom Tag Field', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="ssc_tag_fields" value="<?php echo esc_attr( get_option( 'ssc_tag_fields', '' ) ); ?>" class="regular-text" />
                                            <p class="description"><?php _e( 'Optional existing meta key that already stores your semicolon-separated search tags, for example `title_info`.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Extra Tag Field', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="ssc_extra_fields" value="<?php echo esc_attr( get_option( 'ssc_extra_fields', '' ) ); ?>" class="regular-text" />
                                            <p class="description"><?php _e( 'Optional extra meta key with comma-separated helper tags. These values are merged with the primary tag source.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <?php else : ?>
                                    <tr>
                                        <th><?php _e( 'Custom Fields', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <p class="description"><?php _e( 'Pro can map an existing custom tag field if you already maintain labeled products elsewhere.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <th><?php _e( 'Override AJAX Action', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="ssc_override_ajax" value="<?php echo esc_attr( get_option( 'ssc_override_ajax', '' ) ); ?>" class="regular-text" />
                                            <p class="description"><?php _e( 'Advanced integration only. Use your theme\'s existing AJAX search action name (for example `search_processing`) if you need Smart Search Chips to hook into it.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </details>

                        <?php submit_button(); ?>
                    </div>

                    <!-- RIGHT: Sticky Preview -->
                    <div id="ssc-settings-right">
                        <div id="ssc-preview-panel">
                            <h3><?php _e( 'Style Preview', 'smart-search-chips' ); ?></h3>
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
        </div>

        <style>
            #ssc-settings-layout { display: flex; gap: 24px; align-items: flex-start; margin-top: 16px; }
            #ssc-settings-left { flex: 1; min-width: 0; }
            #ssc-settings-right { width: 320px; flex-shrink: 0; }
            #ssc-preview-panel { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
            #ssc-preview-panel h3 { margin: 0 0 12px; font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
            .ssc-settings-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 0 20px 12px; margin-bottom: 16px; }
            .ssc-settings-card h2 { font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 8px; }
            .ssc-settings-card--collapsible { padding: 0; overflow: hidden; }
            .ssc-settings-card__summary { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 16px 20px; cursor: pointer; list-style: none; font-size: 16px; font-weight: 600; }
            .ssc-settings-card__summary::-webkit-details-marker { display: none; }
            .ssc-settings-card__summary::after { content: "+"; font-size: 18px; line-height: 1; color: #50575e; }
            .ssc-settings-card--collapsible[open] .ssc-settings-card__summary { border-bottom: 1px solid #eee; }
            .ssc-settings-card--collapsible[open] .ssc-settings-card__summary::after { content: "\2212"; }
            .ssc-settings-card__summary-title { color: #1d2327; }
            .ssc-settings-card__body { padding: 0 20px 12px; }
            .ssc-settings-card .form-table th { width: 140px; padding: 10px 8px; font-weight: 500; }
            .ssc-settings-card .form-table td { padding: 10px 8px; }
            .ssc-badge { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
            .ssc-badge--pro { background: #e74c3c; color: #fff; }
            .ssc-badge--free { background: #eee; color: #666; }
            .ssc-range-val { font-family: monospace; font-size: 13px; color: #666; }
            .ssc-ai-inline-status { margin-left: 10px; }
            .ssc-ai-panel { border-top: 1px solid #eee; margin-top: 16px; padding-top: 16px; }
            .ssc-ai-panel strong { display: block; margin-bottom: 8px; }
            .ssc-ai-warning { margin-top: 10px; padding: 10px 12px; background: #fff8e5; border-left: 4px solid #dba617; }
            .ssc-ai-toggle { display: inline-flex; align-items: center; gap: 10px; font-weight: 600; }
            #ssc-ai-prompt-output { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
            .ssc-ai-picker { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(280px, 1fr); gap: 18px; }
            .ssc-ai-picker__browser, .ssc-ai-picker__selection { border: 1px solid #dcdcde; border-radius: 12px; background: #fcfcfd; overflow: hidden; }
            .ssc-ai-browser-toolbar { display: flex; gap: 10px; align-items: center; padding: 14px; border-bottom: 1px solid #ebecef; background: linear-gradient(180deg, #ffffff 0%, #f7f8fa 100%); }
            .ssc-ai-browser-toolbar input[type="search"] { flex: 1; min-width: 0; }
            .ssc-ai-browser-meta { display: grid; gap: 4px; padding: 12px 14px; }
            .ssc-ai-browser-results { display: grid; gap: 10px; padding: 0 14px 14px; max-height: 520px; overflow: auto; }
            .ssc-ai-browser-results--terms { align-content: start; }
            .ssc-ai-taxonomy { border: 1px solid #e3e5e8; border-radius: 12px; background: #fff; overflow: hidden; }
            .ssc-ai-taxonomy__header { display: flex; justify-content: space-between; gap: 12px; align-items: center; padding: 12px 14px; background: #f7f8fa; border-bottom: 1px solid #ebecef; }
            .ssc-ai-taxonomy__header strong { margin: 0; }
            .ssc-ai-taxonomy__terms { display: grid; gap: 0; }
            .ssc-ai-term { border-top: 1px solid #f0f1f3; }
            .ssc-ai-term:first-child { border-top: 0; }
            .ssc-ai-term.is-hidden { display: none; }
            .ssc-ai-term__row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 10px; align-items: center; padding: 10px 14px; }
            .ssc-ai-term__main { display: flex; align-items: center; gap: 10px; min-width: 0; }
            .ssc-ai-term__toggle { width: 32px; height: 32px; border: 1px solid #d0d7df; border-radius: 999px; background: #fff; cursor: pointer; flex-shrink: 0; }
            .ssc-ai-term__toggle[disabled] { opacity: 0.5; cursor: default; }
            .ssc-ai-term__title { min-width: 0; }
            .ssc-ai-term__name { font-weight: 600; color: #1d2327; }
            .ssc-ai-term__actions { display: flex; gap: 8px; align-items: center; }
            .ssc-ai-term__children { display: grid; gap: 0; }
            .ssc-ai-term__items { display: none; padding: 0 14px 14px 56px; }
            .ssc-ai-term.is-expanded > .ssc-ai-term__items { display: block; }
            .ssc-ai-term-items { display: grid; gap: 8px; margin-top: 8px; }
            .ssc-ai-term-item { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 10px; align-items: start; padding: 10px 12px; border: 1px solid #ececec; border-radius: 10px; background: #fcfcfd; }
            .ssc-ai-term-item__title { margin: 0 0 4px; font-size: 13px; line-height: 1.4; }
            .ssc-ai-term-item__meta { display: flex; flex-wrap: wrap; gap: 6px; }
            .ssc-ai-browser-card { display: grid; gap: 10px; grid-template-columns: minmax(0, 1fr) auto; align-items: start; padding: 14px; border: 1px solid #e3e5e8; border-radius: 12px; background: #fff; box-shadow: 0 1px 0 rgba(0, 0, 0, 0.02); }
            .ssc-ai-browser-card.is-selected { border-color: #135e96; background: #f4f9ff; }
            .ssc-ai-browser-card__title { margin: 0 0 6px; font-size: 14px; line-height: 1.4; }
            .ssc-ai-browser-card__meta { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }
            .ssc-ai-browser-card__terms { display: flex; flex-wrap: wrap; gap: 6px; }
            .ssc-ai-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 9px; border-radius: 999px; background: #eff2f6; color: #1d2327; font-size: 12px; line-height: 1.2; }
            .ssc-ai-badge--soft { background: #f7efe3; color: #8a5a12; }
            .ssc-ai-badge--active { background: #dbeafe; color: #0b4f83; }
            .ssc-ai-browser-empty { padding: 24px 18px; border: 1px dashed #ccd0d4; border-radius: 12px; background: #fff; color: #50575e; text-align: center; }
            .ssc-ai-selection-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; padding: 14px 14px 10px; border-bottom: 1px solid #ebecef; background: linear-gradient(180deg, #fffaf1 0%, #fff 100%); }
            .ssc-ai-selection-header p { margin: 6px 0 0; }
            .ssc-ai-selection-summary { padding: 12px 14px 0; font-size: 13px; color: #50575e; }
            .ssc-ai-selected-items { display: grid; gap: 12px; padding: 12px 14px 14px; max-height: 520px; overflow: auto; }
            .ssc-ai-selection-group { display: grid; gap: 8px; }
            .ssc-ai-selection-group__title { font-size: 12px; text-transform: uppercase; letter-spacing: 0.06em; color: #50575e; }
            .ssc-ai-selection-item { display: grid; gap: 8px; grid-template-columns: minmax(0, 1fr) auto; align-items: start; padding: 12px; border: 1px solid #ececec; border-radius: 12px; background: #fff; }
            .ssc-ai-selection-item__title { margin: 0 0 6px; font-size: 13px; line-height: 1.4; }
            .ssc-ai-selection-item__meta { display: flex; flex-wrap: wrap; gap: 6px; }
            .ssc-preview-result { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
            .ssc-preview-result:last-child { border-bottom: none; }
            .ssc-preview-thumb { width: 36px; height: 36px; background: #eee; border-radius: 4px; flex-shrink: 0; }
            .ssc-preview-result span { font-size: 13px; color: #555; }
            @media (max-width: 960px) {
                #ssc-settings-layout { flex-direction: column; }
                #ssc-settings-right { width: 100%; }
                #ssc-preview-panel { position: static; }
                .ssc-ai-picker { grid-template-columns: 1fr; }
                .ssc-ai-browser-results, .ssc-ai-selected-items { max-height: none; }
                .ssc-ai-term__row, .ssc-ai-term-item { grid-template-columns: 1fr; }
                .ssc-ai-term__actions { justify-content: flex-start; }
                .ssc-ai-term__items { padding-left: 14px; }
            }
        </style>

        <script>
        (function() {
            function val(name) {
                var el = document.querySelector('[name="' + name + '"]');
                return el ? el.value : null;
            }

            function renderPreview() {
                var color = val('ssc_chip_color') || '#e74c3c';
                var textColor = val('ssc_chip_text_color') || '#ffffff';
                var inactiveColor = val('ssc_chip_inactive_color') || '#ffffff';
                var inactiveText = val('ssc_chip_inactive_text_color') || '#333333';
                var radius = (val('ssc_chip_radius') || '16') + 'px';
                var fontSize = (val('ssc_chip_font_size') || '13') + 'px';
                var padX = (val('ssc_chip_padding_x') || '12') + 'px';
                var padY = (val('ssc_chip_padding_y') || '4') + 'px';

                var container = document.getElementById('ssc-preview-chips');
                if (!container) return;

                var chips = [
                    { label: 'Active chip (3)', active: true },
                    { label: 'Inactive chip (7)', active: false },
                    { label: 'Another chip (5)', active: false },
                ];

                container.innerHTML = '';
                chips.forEach(function(c) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = c.label;
                    btn.style.cssText = 'cursor:pointer;white-space:nowrap;font-family:inherit;'
                        + 'border-radius:' + radius + ';'
                        + 'font-size:' + fontSize + ';'
                        + 'padding:' + padY + ' ' + padX + ';'
                        + 'border:1px solid ' + color + ';'
                        + (c.active
                            ? 'background:' + color + ';color:' + textColor + ';'
                            : 'background:' + inactiveColor + ';color:' + inactiveText + ';');
                    container.appendChild(btn);
                });
            }

            // Listen to all design inputs
            var inputs = ['ssc_chip_color', 'ssc_chip_text_color', 'ssc_chip_inactive_color', 'ssc_chip_inactive_text_color', 'ssc_chip_radius', 'ssc_chip_font_size', 'ssc_chip_padding_x', 'ssc_chip_padding_y'];
            inputs.forEach(function(name) {
                var el = document.querySelector('[name="' + name + '"]');
                if (el) {
                    el.addEventListener('input', renderPreview);
                    el.addEventListener('change', renderPreview);
                }
            });

            // Initial render
            renderPreview();

            // Floating preview - JS approach since CSS sticky doesn't work in WP admin
            var panel = document.getElementById('ssc-preview-panel');
            var right = document.getElementById('ssc-settings-right');
            if (panel && right && window.innerWidth > 960) {
                // Capture original measurements before anything changes
                var rect = right.getBoundingClientRect();
                var origTop = rect.top + window.scrollY;
                var origLeft = rect.left;
                var availableWidth = document.documentElement.clientWidth - rect.left - 20;
                var origWidth = Math.min(rect.width, availableWidth);

                function resetPanel() {
                    panel.removeAttribute('style');
                }

                function fixPanel() {
                    panel.style.position = 'fixed';
                    panel.style.top = '48px';
                    panel.style.left = origLeft + 'px';
                    panel.style.width = origWidth + 'px';
                    panel.style.boxSizing = 'border-box';
                    panel.style.zIndex = '100';
                    panel.style.background = '#fff';
                    panel.style.border = '1px solid #ddd';
                    panel.style.borderRadius = '8px';
                    panel.style.padding = '20px';
                    panel.style.boxShadow = '0 1px 4px rgba(0,0,0,0.06)';
                }

                function onScroll() {
                    if (window.innerWidth <= 960) { resetPanel(); return; }
                    var scrollTop = window.scrollY || document.documentElement.scrollTop;
                    if (scrollTop + 48 > origTop) {
                        fixPanel();
                    } else {
                        resetPanel();
                    }
                }

                window.addEventListener('scroll', onScroll, { passive: true });
                window.addEventListener('resize', function() {
                    resetPanel();
                    var r = right.getBoundingClientRect();
                    origTop = r.top + window.scrollY;
                    origLeft = r.left;
                    origWidth = r.width;
                    onScroll();
                });
            }

        })();
        </script>
        <?php
    }

    // ─── Helper renderers ────────────────────────────────────────────

    private function render_post_types() {
        $selected = get_option( 'ssc_post_types', array( 'post' ) );
        if ( ! is_array( $selected ) ) {
            $selected = array( $selected );
        }

        $post_types = get_post_types( array( 'public' => true ), 'objects' );

        foreach ( $post_types as $pt ) {
            if ( $pt->name === 'attachment' ) continue;
            printf(
                '<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="ssc_post_types[]" value="%s" %s /> %s</label>',
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
            'built_in' => __( 'Built-in Search Chips Tags (ssc_tags)', 'smart-search-chips' ),
        );

        $custom_tag_field = trim( (string) get_option( 'ssc_tag_fields', '' ) );
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
     * Sanitize AI target field option.
     *
     * @param string $value Raw target option.
     * @return string
     */
    public function sanitize_ai_target_field( $value ) {
        $custom_tag_field = trim( (string) get_option( 'ssc_tag_fields', '' ) );

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
}
