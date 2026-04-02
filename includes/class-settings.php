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
            'chip_color', 'chip_text_color', 'chip_inactive_color', 'chip_inactive_text_color', 'chip_radius', 'chip_font_size', 'chip_padding_x', 'chip_padding_y',
            'max_results', 'search_results_count', 'relevance_threshold',
        );
        foreach ( $fields as $f ) {
            register_setting( 'smart-search-chips', $this->prefix . $f );
        }
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
                                        <input type="number" name="ssc_max_chips" value="<?php echo esc_attr( get_option( 'ssc_max_chips', '8' ) ); ?>" class="small-text" min="1" max="<?php echo SSC_License::max_chips(); ?>" />
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
            #ssc-preview-panel { position: sticky; top: 48px; background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
            #wpbody-content, #wpbody, .wrap, .wrap > form, #ssc-settings-layout, #ssc-settings-right { overflow: visible !important; }
            #ssc-preview-panel h3 { margin: 0 0 12px; font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
            .ssc-settings-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 0 20px 12px; margin-bottom: 16px; }
            .ssc-settings-card h2 { font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 8px; }
            .ssc-settings-card .form-table th { width: 140px; padding: 10px 8px; font-weight: 500; }
            .ssc-settings-card .form-table td { padding: 10px 8px; }
            .ssc-badge { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
            .ssc-badge--pro { background: #e74c3c; color: #fff; }
            .ssc-badge--free { background: #eee; color: #666; }
            .ssc-range-val { font-family: monospace; font-size: 13px; color: #666; }
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
}
