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

        register_setting( 'smart-search-chips', 'ssc_ai_provider', array(
            'sanitize_callback' => array( $this, 'sanitize_ai_provider' ),
        ) );
        register_setting( 'smart-search-chips', 'ssc_ai_model', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'smart-search-chips', 'ssc_ai_openai_key', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'smart-search-chips', 'ssc_ai_anthropic_key', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'smart-search-chips', 'ssc_ai_gemini_key', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'smart-search-chips', 'ssc_ai_source_fields', array(
            'sanitize_callback' => array( $this, 'sanitize_ai_source_fields' ),
        ) );
        register_setting( 'smart-search-chips', 'ssc_ai_custom_read_fields', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'smart-search-chips', 'ssc_ai_target_field', array(
            'sanitize_callback' => array( $this, 'sanitize_ai_target_field' ),
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
            'models'  => SSC_AI_Tagger::get_models(),
            'strings' => array(
                'working'    => __( 'Working...', 'smart-search-chips' ),
                'saved'      => __( 'Generated tags saved.', 'smart-search-chips' ),
                'preview'    => __( 'Preview generated tags', 'smart-search-chips' ),
                'connectOk'  => __( 'Connection OK.', 'smart-search-chips' ),
                'selectItem' => __( 'Choose a post or product first.', 'smart-search-chips' ),
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
        $ai_provider   = get_option( 'ssc_ai_provider', 'openai' );
        $ai_model      = get_option( 'ssc_ai_model', 'gpt-5-mini' );
        $ai_sources    = get_option( 'ssc_ai_source_fields', array( 'title', 'excerpt', 'content' ) );
        if ( ! is_array( $ai_sources ) ) {
            $ai_sources = array( 'title', 'excerpt', 'content' );
        }
        $ai_posts      = $this->get_ai_lab_posts();

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
                                <?php if ( $is_pro ) : ?>
                                <tr>
                                    <th><?php _e( 'Primary Tag Field', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <input type="text" name="ssc_tag_fields" value="<?php echo esc_attr( get_option( 'ssc_tag_fields', '' ) ); ?>" class="regular-text" />
                                        <p class="description"><?php _e( 'Semicolon-separated custom field (e.g. title_info).', 'smart-search-chips' ); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e( 'Extra Tag Field', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <input type="text" name="ssc_extra_fields" value="<?php echo esc_attr( get_option( 'ssc_extra_fields', '' ) ); ?>" class="regular-text" />
                                        <p class="description"><?php _e( 'Comma-separated custom field (e.g. extra_info). Merged with primary.', 'smart-search-chips' ); ?></p>
                                    </td>
                                </tr>
                                <?php else : ?>
                                <tr>
                                    <th><?php _e( 'Tag Source', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <p><?php _e( 'Built-in "Search Chips Tags" meta box.', 'smart-search-chips' ); ?></p>
                                        <p class="description"><?php _e( 'Upgrade to Pro to use custom fields (ACF, post meta).', 'smart-search-chips' ); ?></p>
                                    </td>
                                </tr>
                                <?php endif; ?>
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
                                <tr>
                                    <th><?php _e( 'Override AJAX Action', 'smart-search-chips' ); ?></th>
                                    <td>
                                        <input type="text" name="ssc_override_ajax" value="<?php echo esc_attr( get_option( 'ssc_override_ajax', '' ) ); ?>" class="regular-text" />
                                        <p class="description"><?php _e( 'Your theme\'s AJAX search action name (e.g. search_processing). Leave empty for built-in.', 'smart-search-chips' ); ?></p>
                                    </td>
                                </tr>
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
                            <h2><?php _e( 'AI Tagging', 'smart-search-chips' ); ?></h2>
                            <?php if ( ! $is_pro ) : ?>
                                <p><?php _e( 'AI tagging is part of Pro. It lets you connect OpenAI, Anthropic, or Gemini with your own API key, preview tags for a specific item, estimate cost, and save generated tags into your Search Chips field.', 'smart-search-chips' ); ?></p>
                                <p class="description"><?php _e( 'Upgrade to Pro to unlock provider setup, cost estimation, and AI tag generation.', 'smart-search-chips' ); ?></p>
                            <?php else : ?>
                                <table class="form-table">
                                    <tr>
                                        <th><?php _e( 'Provider', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <select name="ssc_ai_provider" id="ssc-ai-provider">
                                                <?php foreach ( SSC_AI_Tagger::get_providers() as $provider_key => $provider_label ) : ?>
                                                    <option value="<?php echo esc_attr( $provider_key ); ?>" <?php selected( $ai_provider, $provider_key ); ?>><?php echo esc_html( $provider_label ); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Model', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <select name="ssc_ai_model" id="ssc-ai-model" data-selected="<?php echo esc_attr( $ai_model ); ?>">
                                                <?php $this->render_ai_model_options( $ai_provider, $ai_model ); ?>
                                            </select>
                                            <p class="description"><?php _e( 'Curated model list only. The AI Lab below shows an approximate cost for the selected item before you generate tags.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'OpenAI API Key', 'smart-search-chips' ); ?></th>
                                        <td><input type="password" name="ssc_ai_openai_key" id="ssc-ai-openai-key" value="<?php echo esc_attr( get_option( 'ssc_ai_openai_key', '' ) ); ?>" class="regular-text code" autocomplete="off" /></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Anthropic API Key', 'smart-search-chips' ); ?></th>
                                        <td><input type="password" name="ssc_ai_anthropic_key" id="ssc-ai-anthropic-key" value="<?php echo esc_attr( get_option( 'ssc_ai_anthropic_key', '' ) ); ?>" class="regular-text code" autocomplete="off" /></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Gemini API Key', 'smart-search-chips' ); ?></th>
                                        <td><input type="password" name="ssc_ai_gemini_key" id="ssc-ai-gemini-key" value="<?php echo esc_attr( get_option( 'ssc_ai_gemini_key', '' ) ); ?>" class="regular-text code" autocomplete="off" /></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Source Fields', 'smart-search-chips' ); ?></th>
                                        <td><?php $this->render_ai_source_fields( $ai_sources ); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Extra Read Fields', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="ssc_ai_custom_read_fields" id="ssc-ai-custom-read-fields" value="<?php echo esc_attr( get_option( 'ssc_ai_custom_read_fields', '' ) ); ?>" class="regular-text" />
                                            <p class="description"><?php _e( 'Optional comma-separated custom fields to include as source context.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Target Field', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <label style="display:block;margin-bottom:6px;">
                                                <input type="radio" name="ssc_ai_target_field" value="built_in" <?php checked( get_option( 'ssc_ai_target_field', 'built_in' ), 'built_in' ); ?> />
                                                <?php _e( 'Built-in Search Chips Tags (ssc_tags)', 'smart-search-chips' ); ?>
                                            </label>
                                            <label style="display:block;">
                                                <input type="radio" name="ssc_ai_target_field" value="mapped_primary" <?php checked( get_option( 'ssc_ai_target_field', 'built_in' ), 'mapped_primary' ); ?> <?php disabled( get_option( 'ssc_tag_fields', '' ), '' ); ?> />
                                                <?php
                                                printf(
                                                    /* translators: %s: field name */
                                                    esc_html__( 'Mapped primary tag field (%s)', 'smart-search-chips' ),
                                                    esc_html( get_option( 'ssc_tag_fields', '' ) ?: __( 'not configured yet', 'smart-search-chips' ) )
                                                );
                                                ?>
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Max Tags', 'smart-search-chips' ); ?></th>
                                        <td><input type="number" name="ssc_ai_max_tags" id="ssc-ai-max-tags" value="<?php echo esc_attr( get_option( 'ssc_ai_max_tags', 8 ) ); ?>" class="small-text" min="3" max="20" /></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Language', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <input type="text" name="ssc_ai_language" id="ssc-ai-language" value="<?php echo esc_attr( get_option( 'ssc_ai_language', get_bloginfo( 'language' ) ) ); ?>" class="regular-text" />
                                            <p class="description"><?php _e( 'Examples: English, Czech, German. Leave the site language if you want tags to match site content language.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Custom Instruction', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <textarea name="ssc_ai_custom_instruction" id="ssc-ai-custom-instruction" rows="3" class="large-text"><?php echo esc_textarea( get_option( 'ssc_ai_custom_instruction', '' ) ); ?></textarea>
                                            <p class="description"><?php _e( 'Optional short instruction, for example: prefer technical specs, avoid brand names, or use Czech labels.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Connection Test', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <button type="button" class="button" id="ssc-ai-test-connection"><?php _e( 'Test connection', 'smart-search-chips' ); ?></button>
                                            <span id="ssc-ai-test-result" class="ssc-ai-inline-status" aria-live="polite"></span>
                                        </td>
                                    </tr>
                                </table>
                            <?php endif; ?>
                        </div>

                        <div class="ssc-settings-card ssc-settings-card--ai-lab">
                            <h2><?php _e( 'AI Lab', 'smart-search-chips' ); ?></h2>
                            <?php if ( ! $is_pro ) : ?>
                                <p class="description"><?php _e( 'The AI Lab becomes available in Pro. Use it to estimate cost for a selected item, preview generated tags, and save them into the target field.', 'smart-search-chips' ); ?></p>
                            <?php else : ?>
                                <table class="form-table">
                                    <tr>
                                        <th><?php _e( 'Item', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <select id="ssc-ai-post-id" class="regular-text">
                                                <option value=""><?php _e( 'Select a post or product', 'smart-search-chips' ); ?></option>
                                                <?php foreach ( $ai_posts as $item ) : ?>
                                                    <option value="<?php echo esc_attr( $item->ID ); ?>"><?php echo esc_html( $item->post_title . ' [' . $item->post_type . ']' ); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p class="description"><?php _e( 'Shows recent items from the post types selected above.', 'smart-search-chips' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Actions', 'smart-search-chips' ); ?></th>
                                        <td>
                                            <button type="button" class="button" id="ssc-ai-estimate"><?php _e( 'Estimate cost', 'smart-search-chips' ); ?></button>
                                            <button type="button" class="button" id="ssc-ai-preview"><?php _e( 'Preview tags', 'smart-search-chips' ); ?></button>
                                            <button type="button" class="button button-primary" id="ssc-ai-generate"><?php _e( 'Generate and save', 'smart-search-chips' ); ?></button>
                                            <label style="display:block;margin-top:10px;">
                                                <input type="checkbox" id="ssc-ai-overwrite" value="1" />
                                                <?php _e( 'Overwrite existing target tags', 'smart-search-chips' ); ?>
                                            </label>
                                        </td>
                                    </tr>
                                </table>
                                <div id="ssc-ai-estimate-panel" class="ssc-ai-panel">
                                    <strong><?php _e( 'Estimate', 'smart-search-chips' ); ?></strong>
                                    <p class="description"><?php _e( 'Approximate only. Final cost depends on provider tokenization and response length.', 'smart-search-chips' ); ?></p>
                                    <div id="ssc-ai-estimate-output"><?php _e( 'Select an item to see estimated token usage and cost.', 'smart-search-chips' ); ?></div>
                                </div>
                                <div id="ssc-ai-preview-panel" class="ssc-ai-panel">
                                    <strong><?php _e( 'Preview', 'smart-search-chips' ); ?></strong>
                                    <div id="ssc-ai-preview-output"><?php _e( 'Previewed tags will appear here.', 'smart-search-chips' ); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php submit_button(); ?>
                    </div>

                    <!-- RIGHT: Sticky Preview -->
                    <div id="ssc-settings-right">
                        <div id="ssc-preview-panel">
                            <h3><?php _e( 'Live Preview', 'smart-search-chips' ); ?></h3>
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
            .ssc-settings-card .form-table th { width: 140px; padding: 10px 8px; font-weight: 500; }
            .ssc-settings-card .form-table td { padding: 10px 8px; }
            .ssc-badge { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
            .ssc-badge--pro { background: #e74c3c; color: #fff; }
            .ssc-badge--free { background: #eee; color: #666; }
            .ssc-range-val { font-family: monospace; font-size: 13px; color: #666; }
            .ssc-ai-inline-status { margin-left: 10px; }
            .ssc-ai-panel { border-top: 1px solid #eee; margin-top: 16px; padding-top: 16px; }
            .ssc-ai-panel strong { display: block; margin-bottom: 8px; }
            .ssc-ai-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
            .ssc-ai-tags span { display: inline-flex; padding: 4px 10px; background: #f4f4f4; border: 1px solid #ddd; border-radius: 999px; }
            .ssc-preview-result { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
            .ssc-preview-result:last-child { border-bottom: none; }
            .ssc-preview-thumb { width: 36px; height: 36px; background: #eee; border-radius: 4px; flex-shrink: 0; }
            .ssc-preview-result span { font-size: 13px; color: #555; }
            @media (max-width: 960px) {
                #ssc-settings-layout { flex-direction: column; }
                #ssc-settings-right { width: 100%; }
                #ssc-preview-panel { position: static; }
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
     * Render model options for the selected provider.
     *
     * @param string $provider Selected provider.
     * @param string $selected Selected model.
     */
    private function render_ai_model_options( $provider, $selected ) {
        $models = SSC_AI_Tagger::get_models();
        if ( empty( $models[ $provider ] ) ) {
            return;
        }

        foreach ( $models[ $provider ] as $model ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $model['id'] ),
                selected( $selected, $model['id'], false ),
                esc_html( $model['label'] . ' - ' . $model['description'] )
            );
        }
    }

    /**
     * Render AI source field checkboxes.
     *
     * @param array $selected Selected sources.
     */
    private function render_ai_source_fields( $selected ) {
        $fields = array(
            'title'          => __( 'Title', 'smart-search-chips' ),
            'excerpt'        => __( 'Excerpt', 'smart-search-chips' ),
            'content'        => __( 'Content', 'smart-search-chips' ),
            'woo_attributes' => __( 'WooCommerce attributes and SKU', 'smart-search-chips' ),
        );

        foreach ( $fields as $key => $label ) {
            printf(
                '<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="ssc_ai_source_fields[]" value="%s" %s /> %s</label>',
                esc_attr( $key ),
                checked( in_array( $key, $selected, true ), true, false ),
                esc_html( $label )
            );
        }
    }

    /**
     * Get recent items for the AI lab selector.
     *
     * @return WP_Post[]
     */
    private function get_ai_lab_posts() {
        $post_types = get_option( 'ssc_post_types', array( 'post' ) );
        if ( ! is_array( $post_types ) ) {
            $post_types = array( $post_types );
        }

        return get_posts( array(
            'post_type'      => $post_types,
            'post_status'    => array( 'publish', 'draft', 'private' ),
            'posts_per_page' => 40,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );
    }

    /**
     * Sanitize AI provider choice.
     *
     * @param string $value Provider.
     * @return string
     */
    public function sanitize_ai_provider( $value ) {
        $value     = sanitize_key( $value );
        $providers = SSC_AI_Tagger::get_providers();
        return isset( $providers[ $value ] ) ? $value : 'openai';
    }

    /**
     * Sanitize selected AI source fields.
     *
     * @param array|string $value Raw selected fields.
     * @return array
     */
    public function sanitize_ai_source_fields( $value ) {
        $allowed = array( 'title', 'excerpt', 'content', 'woo_attributes' );
        $value   = is_array( $value ) ? $value : array();
        $value   = array_map( 'sanitize_key', $value );

        return array_values( array_intersect( $value, $allowed ) );
    }

    /**
     * Sanitize AI target field option.
     *
     * @param string $value Raw target option.
     * @return string
     */
    public function sanitize_ai_target_field( $value ) {
        return $value === 'mapped_primary' ? 'mapped_primary' : 'built_in';
    }
}
