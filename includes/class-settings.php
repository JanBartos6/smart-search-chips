<?php
/**
 * Settings - Admin settings page
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSC_Settings {

    /** @var string Options prefix */
    private $prefix = 'ssc_';

    /**
     * Initialize settings.
     */
    public function init() {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_filter( 'plugin_action_links_' . SSC_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
    }

    /**
     * Get a setting value.
     *
     * @param string $key     Setting key (without prefix).
     * @param mixed  $default Default value.
     * @return mixed
     */
    public function get( $key, $default = '' ) {
        $value = get_option( $this->prefix . $key, $default );
        return $value !== false ? $value : $default;
    }

    /**
     * Add settings page to admin menu.
     */
    public function add_menu_page() {
        add_options_page(
            __( 'Smart Search Chips', 'smart-search-chips' ),
            __( 'Search Chips', 'smart-search-chips' ),
            'manage_options',
            'smart-search-chips',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Add settings link to plugin list.
     */
    public function add_settings_link( $links ) {
        $url  = admin_url( 'options-general.php?page=smart-search-chips' );
        $link = '<a href="' . esc_url( $url ) . '">' . __( 'Settings', 'smart-search-chips' ) . '</a>';
        array_unshift( $links, $link );
        return $links;
    }

    /**
     * Register all settings.
     */
    public function register_settings() {
        // Search section
        add_settings_section( 'ssc_search', __( 'Search Settings', 'smart-search-chips' ), null, 'smart-search-chips' );

        $this->add_field( 'post_types', __( 'Post Types', 'smart-search-chips' ), 'render_post_types_field', 'ssc_search' );
        $this->add_field( 'tag_fields', __( 'Primary Tag Field', 'smart-search-chips' ), 'render_text_field', 'ssc_search',
            __( 'Custom field name containing semicolon-separated tags (e.g. title_info). Supports ACF and regular post meta.', 'smart-search-chips' ) );
        $this->add_field( 'extra_fields', __( 'Extra Tag Field', 'smart-search-chips' ), 'render_text_field', 'ssc_search',
            __( 'Custom field name containing comma-separated tags (e.g. extra_info). Tags from both fields are merged and deduplicated.', 'smart-search-chips' ) );
        $this->add_field( 'override_ajax', __( 'Override AJAX Action', 'smart-search-chips' ), 'render_text_field', 'ssc_search',
            __( 'If your theme has its own AJAX search handler, enter its action name here (e.g. search_processing) to override it. Leave empty to use the built-in ssc_search action.', 'smart-search-chips' ) );

        // Chip section
        add_settings_section( 'ssc_chips', __( 'Chip Settings', 'smart-search-chips' ), null, 'smart-search-chips' );

        $this->add_field( 'max_chips', __( 'Max Chips', 'smart-search-chips' ), 'render_number_field', 'ssc_chips',
            __( 'Maximum number of filter chips to display.', 'smart-search-chips' ) );
        $this->add_field( 'max_tag_length', __( 'Max Tag Length', 'smart-search-chips' ), 'render_number_field', 'ssc_chips',
            __( 'Maximum character length for a chip label. Longer tags are hidden.', 'smart-search-chips' ) );
        $this->add_field( 'chip_color', __( 'Active Chip Color', 'smart-search-chips' ), 'render_color_field', 'ssc_chips',
            __( 'Background color for active (selected) chips. Also used as border color for inactive chips.', 'smart-search-chips' ) );
        $this->add_field( 'chip_text_color', __( 'Active Chip Text Color', 'smart-search-chips' ), 'render_color_field', 'ssc_chips',
            __( 'Text color for active (selected) chips.', 'smart-search-chips' ) );

        // Results section
        add_settings_section( 'ssc_results', __( 'Results Settings', 'smart-search-chips' ), null, 'smart-search-chips' );

        $this->add_field( 'max_results', __( 'Results Shown', 'smart-search-chips' ), 'render_number_field', 'ssc_results',
            __( 'Maximum number of search results to display.', 'smart-search-chips' ) );
        $this->add_field( 'search_results_count', __( 'Results to Analyze', 'smart-search-chips' ), 'render_number_field', 'ssc_results',
            __( 'Number of results to fetch for chip generation. Higher = more accurate chips but slower. Recommended: 50.', 'smart-search-chips' ) );
        $this->add_field( 'relevance_threshold', __( 'Relevance Threshold', 'smart-search-chips' ), 'render_range_field', 'ssc_results',
            __( 'Minimum relevance score (as fraction of top score) to include a result. Only works with Relevanssi. 0 = include all, 1 = only exact matches.', 'smart-search-chips' ) );
    }

    /**
     * Helper to register a field.
     */
    private function add_field( $key, $title, $callback, $section, $description = '' ) {
        $option_name = $this->prefix . $key;

        register_setting( 'smart-search-chips', $option_name );

        add_settings_field(
            $option_name,
            $title,
            array( $this, $callback ),
            'smart-search-chips',
            $section,
            array(
                'key'         => $key,
                'option_name' => $option_name,
                'description' => $description,
            )
        );
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $relevanssi_active = function_exists( 'relevanssi_do_query' );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <?php if ( ! $relevanssi_active ) : ?>
                <div class="notice notice-info">
                    <p><strong><?php _e( 'Relevanssi not detected.', 'smart-search-chips' ); ?></strong>
                    <?php _e( 'Smart Search Chips works best with Relevanssi (free version). Without it, relevance scoring and threshold filtering are disabled.', 'smart-search-chips' ); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'smart-search-chips' );
                do_settings_sections( 'smart-search-chips' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // ─── Field Renderers ─────────────────────────────────────────────

    public function render_text_field( $args ) {
        $value = get_option( $args['option_name'], '' );
        printf(
            '<input type="text" name="%s" value="%s" class="regular-text" />',
            esc_attr( $args['option_name'] ),
            esc_attr( $value )
        );
        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    public function render_number_field( $args ) {
        $value = get_option( $args['option_name'], '' );
        printf(
            '<input type="number" name="%s" value="%s" class="small-text" min="1" />',
            esc_attr( $args['option_name'] ),
            esc_attr( $value )
        );
        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    public function render_color_field( $args ) {
        $value = get_option( $args['option_name'], '#e74c3c' );
        printf(
            '<input type="color" name="%s" value="%s" />',
            esc_attr( $args['option_name'] ),
            esc_attr( $value )
        );
        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    public function render_range_field( $args ) {
        $value = get_option( $args['option_name'], '0.3' );
        printf(
            '<input type="range" name="%s" value="%s" min="0" max="1" step="0.05" oninput="this.nextElementSibling.textContent=this.value" /> <span>%s</span>',
            esc_attr( $args['option_name'] ),
            esc_attr( $value ),
            esc_html( $value )
        );
        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    public function render_post_types_field( $args ) {
        $selected = get_option( $args['option_name'], array( 'post' ) );
        if ( ! is_array( $selected ) ) {
            $selected = array( $selected );
        }

        $post_types = get_post_types( array( 'public' => true ), 'objects' );

        foreach ( $post_types as $pt ) {
            if ( $pt->name === 'attachment' ) {
                continue;
            }
            printf(
                '<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="%s[]" value="%s" %s /> %s (%s)</label>',
                esc_attr( $args['option_name'] ),
                esc_attr( $pt->name ),
                checked( in_array( $pt->name, $selected, true ), true, false ),
                esc_html( $pt->label ),
                esc_html( $pt->name )
            );
        }

        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }
}
