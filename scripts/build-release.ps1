param(
    [ValidateSet('all', 'pro', 'wporg')]
    [string] $Target = 'all'
)

$ErrorActionPreference = 'Stop'

$Slug = 'smart-search-chips'
$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$BuildRoot = Join-Path $Root 'build'
$ProDir = Join-Path $BuildRoot "$Slug-pro\$Slug"
$WpOrgDir = Join-Path $BuildRoot "$Slug-wporg\$Slug"
$ProZip = Join-Path $BuildRoot "$Slug-pro.zip"
$WpOrgZip = Join-Path $BuildRoot "$Slug-wporg.zip"

function Assert-ChildPath {
    param(
        [string] $Parent,
        [string] $Child
    )

    $parentFull = [System.IO.Path]::GetFullPath($Parent).TrimEnd('\') + '\'
    $childFull = [System.IO.Path]::GetFullPath($Child)

    if (-not $childFull.StartsWith($parentFull, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "Refusing to operate outside $parentFull`: $childFull"
    }
}

function Reset-BuildRoot {
    if (Test-Path $BuildRoot) {
        Assert-ChildPath -Parent $Root -Child $BuildRoot
        Remove-Item -LiteralPath $BuildRoot -Recurse -Force
    }

    New-Item -ItemType Directory -Path $BuildRoot | Out-Null
}

function Copy-PluginTree {
    param(
        [string] $Destination,
        [bool] $IncludeVendor
    )

    New-Item -ItemType Directory -Path $Destination -Force | Out-Null

    $rootFiles = @(
        'smart-search-chips.php',
        'readme.txt',
        'icon.png',
        'screenshot-1.png',
        'screenshot-2.png',
        'screenshot-3.png'
    )

    foreach ($file in $rootFiles) {
        $source = Join-Path $Root $file
        if (Test-Path $source) {
            Copy-Item -LiteralPath $source -Destination (Join-Path $Destination $file) -Force
        }
    }

    foreach ($dir in @('assets', 'includes', 'languages')) {
        $source = Join-Path $Root $dir
        if (Test-Path $source) {
            Copy-Item -LiteralPath $source -Destination (Join-Path $Destination $dir) -Recurse -Force
        }
    }

    if ($IncludeVendor) {
        $vendor = Join-Path $Root 'vendor'
        if (Test-Path $vendor) {
            Copy-Item -LiteralPath $vendor -Destination (Join-Path $Destination 'vendor') -Recurse -Force
        }
    }
}

function Compress-Plugin {
    param(
        [string] $ParentDir,
        [string] $ZipPath
    )

    if (Test-Path $ZipPath) {
        Remove-Item -LiteralPath $ZipPath -Force
    }

    Push-Location $ParentDir
    try {
        Compress-Archive -Path $Slug -DestinationPath $ZipPath -Force
    } finally {
        Pop-Location
    }
}

function Set-Text {
    param(
        [string] $Path,
        [string] $Content
    )

    $encoding = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($Path, $Content, $encoding)
}

function Replace-RegexLiteral {
    param(
        [string] $Text,
        [string] $Pattern,
        [string] $Replacement
    )

    $evaluator = [System.Text.RegularExpressions.MatchEvaluator]{
        param($Match)
        return $Replacement
    }

    return [System.Text.RegularExpressions.Regex]::Replace($Text, $Pattern, $evaluator)
}

function Write-WpOrgMain {
    param([string] $PluginDir)

    Set-Text -Path (Join-Path $PluginDir 'smart-search-chips.php') -Content @'
<?php
/**
 * Plugin Name: Smart Search Chips
 * Plugin URI:  https://smartsearchchips.app
 * Description: Dynamic filter chips for WordPress search. Generates smart, non-redundant filter tags from the built-in Search Chips Tags field. Works with Relevanssi for best results.
 * Version:     1.0.13
 * Author:      Jan Bartos
 * Author URI:  https://teprostroj.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smart-search-chips
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SSCHP_VERSION', '1.0.13' );
define( 'SSCHP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSCHP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SSCHP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once SSCHP_PLUGIN_DIR . 'includes/class-edition.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-language.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-chip-engine.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-search-handler.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-settings.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-frontend.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-meta-box.php';
require_once SSCHP_PLUGIN_DIR . 'includes/class-woocommerce.php';

/**
 * Initialize the plugin.
 */
function sschp_init() {
    $settings = new SSCHP_Settings();
    $settings->init();

    $engine = new SSCHP_Chip_Engine();

    $handler = new SSCHP_Search_Handler( $engine, $settings );
    $handler->init();

    $frontend = new SSCHP_Frontend( $settings, $engine );
    $frontend->init();

    $meta_box = new SSCHP_Meta_Box();
    $meta_box->init();

    $woo = new SSCHP_WooCommerce();
    $woo->init();
}
add_action( 'plugins_loaded', 'sschp_init' );

/**
 * Register mapped meta fields for REST API access.
 */
function sschp_register_meta() {
    $post_types = get_option( 'sschp_post_types', array( 'post' ) );
    if ( ! is_array( $post_types ) ) {
        $post_types = array( $post_types );
    }

    foreach ( $post_types as $pt ) {
        register_post_meta(
            $pt,
            SSCHP_Meta_Box::META_KEY,
            array(
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => 'string',
                'auth_callback' => 'sschp_can_edit_post_meta',
            )
        );
    }
}
add_action( 'init', 'sschp_register_meta' );

/**
 * Activation hook.
 */
function sschp_activate() {
    $defaults = array(
        'sschp_post_types'               => array( 'post' ),
        'sschp_max_chips'                => 8,
        'sschp_max_results'              => 10,
        'sschp_relevance_threshold'      => 0.3,
        'sschp_max_tag_length'           => 40,
        'sschp_chip_color'               => '#e74c3c',
        'sschp_chip_text_color'          => '#ffffff',
        'sschp_chip_inactive_color'      => '#ffffff',
        'sschp_chip_inactive_text_color' => '#333333',
        'sschp_chip_radius'              => 16,
        'sschp_chip_font_size'           => 13,
        'sschp_chip_padding_x'           => 12,
        'sschp_chip_padding_y'           => 4,
        'sschp_woo_enabled'              => '1',
        'sschp_override_ajax'            => '',
        'sschp_search_results_count'     => 50,
        'sschp_delete_data_on_uninstall' => '0',
    );

    foreach ( $defaults as $key => $value ) {
        if ( false === get_option( $key ) ) {
            add_option( $key, $value );
        }
    }
}
register_activation_hook( __FILE__, 'sschp_activate' );

/**
 * Return plugin-owned option keys.
 *
 * @return array
 */
function sschp_get_option_keys() {
    return array(
        'sschp_post_types',
        'sschp_max_chips',
        'sschp_max_results',
        'sschp_relevance_threshold',
        'sschp_max_tag_length',
        'sschp_chip_color',
        'sschp_chip_text_color',
        'sschp_chip_inactive_color',
        'sschp_chip_inactive_text_color',
        'sschp_chip_radius',
        'sschp_chip_font_size',
        'sschp_chip_padding_x',
        'sschp_chip_padding_y',
        'sschp_woo_enabled',
        'sschp_override_ajax',
        'sschp_search_results_count',
        'sschp_delete_data_on_uninstall',
    );
}

/**
 * Delete plugin-owned data while the plugin is still installed.
 *
 * @return void
 */
function sschp_delete_plugin_data() {
    foreach ( sschp_get_option_keys() as $option ) {
        delete_option( $option );
    }

    delete_metadata( 'post', 0, 'sschp_tags', '', true );
}

/**
 * Optional uninstall cleanup.
 */
function sschp_uninstall() {
    if ( get_option( 'sschp_delete_data_on_uninstall', '0' ) !== '1' ) {
        return;
    }

    sschp_delete_plugin_data();
}
register_uninstall_hook( __FILE__, 'sschp_uninstall' );

/**
 * Privacy Policy suggested content.
 */
function sschp_privacy_policy_content() {
    $content = '<h2>' . __( 'Smart Search Chips', 'smart-search-chips' ) . '</h2>'
        . '<p>' . __( 'This plugin processes search queries entered by visitors to generate dynamic filter chips. Search queries are processed on this server and are not stored or sent to any third-party service.', 'smart-search-chips' ) . '</p>'
        . '<p>' . __( 'No personal data is collected, stored, or shared by this plugin beyond the site owner\'s own WordPress configuration.', 'smart-search-chips' ) . '</p>';

    wp_add_privacy_policy_content( 'Smart Search Chips', wp_kses_post( $content ) );
}
add_action( 'admin_init', 'sschp_privacy_policy_content' );
'@
}

function Write-WpOrgEdition {
    param([string] $PluginDir)

    Set-Text -Path (Join-Path $PluginDir 'includes\class-edition.php') -Content @'
<?php
/**
 * WordPress.org edition metadata.
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCHP_Edition {

    /**
     * @return string
     */
    public static function get_product_url() {
        return 'https://smartsearchchips.app/';
    }

    /**
     * @return string
     */
    public static function get_status_label() {
        return __( 'WordPress.org edition', 'smart-search-chips' );
    }

    /**
     * @return string
     */
    public static function get_status_description() {
        return __( 'All features included in this WordPress.org package are available without accounts or payment.', 'smart-search-chips' );
    }

    /**
     * @return int
     */
    public static function max_chips() {
        return 99;
    }
}
'@
}

function Write-WpOrgSettings {
    param([string] $PluginDir)

    Set-Text -Path (Join-Path $PluginDir 'includes\class-settings.php') -Content @'
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
        return $links;
    }

    public function register_settings() {
        register_setting( 'smart-search-chips', 'sschp_post_types', array( 'sanitize_callback' => array( $this, 'sanitize_post_types' ) ) );
        register_setting( 'smart-search-chips', 'sschp_override_ajax', array( 'sanitize_callback' => 'sanitize_key' ) );
        register_setting( 'smart-search-chips', 'sschp_max_chips', array( 'sanitize_callback' => array( $this, 'sanitize_max_chips' ) ) );
        register_setting( 'smart-search-chips', 'sschp_max_tag_length', array( 'sanitize_callback' => array( $this, 'sanitize_max_tag_length' ) ) );
        register_setting( 'smart-search-chips', 'sschp_woo_enabled', array( 'sanitize_callback' => array( $this, 'sanitize_checkbox' ) ) );
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
                            <h2><?php esc_html_e( 'Smart Search Chips Pro', 'smart-search-chips' ); ?></h2>
                            <p><?php esc_html_e( 'Need ACF/custom field connectors, numeric range slider chips, search analytics, synonym rules, manual chip curation, AI-assisted tag workflows, and commercial support? Smart Search Chips Pro is available as a separate product from our website.', 'smart-search-chips' ); ?></p>
                            <p class="description"><?php esc_html_e( 'The WordPress.org plugin remains fully functional with its built-in Search Chips Tags field. Pro is an optional add-on for teams that want existing field integrations, catalog optimization, reporting, and workflow extras.', 'smart-search-chips' ); ?></p>
                            <p><a class="button button-secondary" href="<?php echo esc_url( SSCHP_Edition::get_product_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Pro Features', 'smart-search-chips' ); ?></a></p>
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
                                            <?php esc_html_e( 'Auto-populate tags from product attributes, categories, and SKU', 'smart-search-chips' ); ?>
                                        </label>
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
                                    <tr><th><?php esc_html_e( 'Override AJAX Action', 'smart-search-chips' ); ?></th><td><input type="text" name="sschp_override_ajax" value="<?php echo esc_attr( get_option( 'sschp_override_ajax', '' ) ); ?>" class="regular-text" /><p class="description"><?php esc_html_e( 'Use your theme existing AJAX search action name if you need Smart Search Chips to hook into it. Requests still require the Smart Search Chips nonce.', 'smart-search-chips' ); ?></p></td></tr>
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
'@
}

function Patch-WpOrgCoreFiles {
    param([string] $PluginDir)

    $frontendPath = Join-Path $PluginDir 'includes\class-frontend.php'
    $frontend = Get-Content -Raw -LiteralPath $frontendPath
    $frontend = Replace-RegexLiteral -Text $frontend -Pattern '(?s)\s*// Pass settings to JS\s*\r?\n\s*\$analytics_enabled = false;\s*\r?\n\s*\$analytics_nonce\s+= '''';\s*\r?\n\s*if \( sschp_fs\(\)->can_use_premium_code__premium_only\(\) \) \{\s*\r?\n\s*\$analytics_enabled = SSCHP_Analytics::is_enabled\(\);\s*\r?\n\s*\$analytics_nonce\s+= wp_create_nonce\( ''sschp_analytics_nonce'' \);\s*\r?\n\s*\}' -Replacement "`r`n        // Pass settings to JS`r`n        `$analytics_enabled = false;`r`n        `$analytics_nonce   = '';"
    $frontend = Replace-RegexLiteral -Text $frontend -Pattern '(?s)private function get_ajax_action_name\(\) \{.*?return ''sschp_search'';\s*\r?\n\s*\}' -Replacement "private function get_ajax_action_name() {`r`n        `$ajax_action = `$this->settings->get( 'override_ajax' );`r`n        return ! empty( `$ajax_action ) ? `$ajax_action : 'sschp_search';`r`n    }"
    $frontend = Replace-RegexLiteral -Text $frontend -Pattern '(?s)private function get_ajax_keyword_field_name\(\) \{.*?return ''keyword'';\s*\r?\n\s*\}' -Replacement "private function get_ajax_keyword_field_name() {`r`n        return 'keyword';`r`n    }"
    $frontend = Replace-RegexLiteral -Text $frontend -Pattern '(?s)private function get_result_container_selector\(\) \{.*?return \$default;\s*\r?\n\s*\}' -Replacement "private function get_result_container_selector() {`r`n        return '.js-ssc-results, .js-search__results, .ssc-search__results';`r`n    }"
    $frontend = $frontend -replace 'Free sites should always use the built-in handler even if an old Pro-only\s*\r?\n\s*\* override setting is still stored after a downgrade\.', 'Sites use the configured AJAX handler when one is provided.'
    $frontend = $frontend -replace 'SSCHP_License::max_chips\(\)', 'SSCHP_Edition::max_chips()'
    $frontend = Replace-RegexLiteral -Text $frontend -Pattern '\$request_uri = isset\( \$_SERVER\[''REQUEST_URI''\] \) \? wp_unslash\( \$_SERVER\[''REQUEST_URI''\] \) : ''/'';' -Replacement '$request_uri = isset( $_SERVER[''REQUEST_URI''] ) ? esc_url_raw( wp_unslash( $_SERVER[''REQUEST_URI''] ) ) : ''/'';'
    $frontend = $frontend -replace 'if \( sschp_fs\(\)->can_use_premium_code__premium_only\(\) \)', 'if ( true )'
    $frontend = Replace-RegexLiteral -Text $frontend -Pattern '(?s)\s*if \( true \) \{\s*\r?\n\s*\$radius\s+= \$this->get_numeric_setting\( ''chip_radius'', 16, 0 \);\s*\r?\n\s*\$font_size = \$this->get_numeric_setting\( ''chip_font_size'', 13, 8 \);\s*\r?\n\s*\$pad_x\s+= \$this->get_numeric_setting\( ''chip_padding_x'', 12, 0 \);\s*\r?\n\s*\$pad_y\s+= \$this->get_numeric_setting\( ''chip_padding_y'', 4, 0 \);\s*\r?\n\s*\}' -Replacement "`r`n        `$radius    = `$this->get_numeric_setting( 'chip_radius', 16, 0 );`r`n        `$font_size = `$this->get_numeric_setting( 'chip_font_size', 13, 8 );`r`n        `$pad_x     = `$this->get_numeric_setting( 'chip_padding_x', 12, 0 );`r`n        `$pad_y     = `$this->get_numeric_setting( 'chip_padding_y', 4, 0 );"
    $frontend = Replace-RegexLiteral -Text $frontend -Pattern '(?s)\s*if \( true \) \{\s*\r?\n\s*\$tag_field\s+= \$this->settings->get\( ''tag_fields'', '''' \);\s*\r?\n\s*\$extra_field = \$this->settings->get\( ''extra_fields'', '''' \);\s*\r?\n\s*if \( ! empty\( \$tag_field \) \|\| ! empty\( \$extra_field \) \) \{\s*\r?\n\s*\$primary = \$this->get_field_value\( \$post_id, \$tag_field \);\s*\r?\n\s*\$extra\s+= \$this->get_field_value\( \$post_id, \$extra_field \);\s*\r?\n\s*if \( '''' === \$primary && '''' === \$extra && empty\( \$tag_field \) \) \{\s*\r?\n\s*\$primary = get_post_meta\( \$post_id, SSCHP_Meta_Box::META_KEY, true \) \?: '''';\s*\r?\n\s*\}\s*\r?\n\s*\}\s*\r?\n\s*\}' -Replacement ''
    $frontend = Replace-RegexLiteral -Text $frontend -Pattern '(?s)\s*/\*\*\s*\r?\n\s*\* Read a field value using ACF when available\..*?private function get_field_value\( \$post_id, \$field_name \) \{.*?\r?\n\s*\}\s*\r?\n\s*/\*\*\s*\r?\n\s*\* Parse active archive chip filters from the URL\.' -Replacement "`r`n    /**`r`n     * Parse active archive chip filters from the URL."
    Set-Text -Path $frontendPath -Content $frontend

    $handlerPath = Join-Path $PluginDir 'includes\class-search-handler.php'
    $handler = Get-Content -Raw -LiteralPath $handlerPath
    $handler = Replace-RegexLiteral -Text $handler -Pattern '(?s)\s*// Nonce verification \(optional .*?\s*if \( isset\( \$_POST\[''sschp_nonce''\] \) && ! wp_verify_nonce\( sanitize_text_field\( wp_unslash\( \$_POST\[''sschp_nonce''\] \) \), ''sschp_search_nonce'' \) \) \{\s*\r?\n\s*wp_die\( esc_html__\( ''Security check failed\.'', ''smart-search-chips'' \) \);\s*\r?\n\s*\}' -Replacement "`r`n        if ( ! isset( `$_POST['sschp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( `$_POST['sschp_nonce'] ) ), 'sschp_search_nonce' ) ) {`r`n            wp_die( esc_html__( 'Security check failed.', 'smart-search-chips' ) );`r`n        }"
    $handler = Replace-RegexLiteral -Text $handler -Pattern '(?s)\s*// Filter by relevance threshold \(Pro \+ Relevanssi only\)\s*\r?\n\s*\$use_relevance = false;\s*\r?\n\s*if \( sschp_fs\(\)->can_use_premium_code__premium_only\(\) \) \{\s*\r?\n\s*\$use_relevance = \$top_score > 0;\s*\r?\n\s*\}' -Replacement "`r`n        `$use_relevance = `$top_score > 0;"
    $handler = $handler -replace 'SSCHP_License::max_chips\(\)', 'SSCHP_Edition::max_chips()'
    $handler = $handler -replace 'if \( sschp_fs\(\)->can_use_premium_code__premium_only\(\) \)', 'if ( true )'
    $handler = Replace-RegexLiteral -Text $handler -Pattern '(?s)\s*if \( true \) \{\s*\r?\n\s*\$radius\s+= \$this->get_numeric_setting\( ''chip_radius'', 16, 0 \);\s*\r?\n\s*\$font_size = \$this->get_numeric_setting\( ''chip_font_size'', 13, 8 \);\s*\r?\n\s*\$pad_x\s+= \$this->get_numeric_setting\( ''chip_padding_x'', 12, 0 \);\s*\r?\n\s*\$pad_y\s+= \$this->get_numeric_setting\( ''chip_padding_y'', 4, 0 \);\s*\r?\n\s*\}' -Replacement "`r`n        `$radius    = `$this->get_numeric_setting( 'chip_radius', 16, 0 );`r`n        `$font_size = `$this->get_numeric_setting( 'chip_font_size', 13, 8 );`r`n        `$pad_x     = `$this->get_numeric_setting( 'chip_padding_x', 12, 0 );`r`n        `$pad_y     = `$this->get_numeric_setting( 'chip_padding_y', 4, 0 );"
    $handler = $handler -replace 'Override existing AJAX action \(Pro only\)', 'Override an existing AJAX action when configured'
    $handler = $handler -replace 'Pro: use configured custom fields', 'Use configured custom fields'
    $handler = $handler -replace 'Pro is active before field mapping is configured', 'field mapping is changed'
    $handler = $handler -replace 'Pro design options \(defaults for free\)', 'Design options'
    $handler = Replace-RegexLiteral -Text $handler -Pattern '(?s)\s*if \( true \) \{\s*\r?\n\s*// Use configured custom fields when present, otherwise keep\s*\r?\n\s*// the built-in fallback so sites do not lose chips just because\s*\r?\n\s*// field mapping is changed\.\s*\r?\n\s*\$tag_field\s+= \$this->settings->get\( ''tag_fields'', '''' \);\s*\r?\n\s*\$extra_field = \$this->settings->get\( ''extra_fields'', '''' \);\s*\r?\n\s*if \( ! empty\( \$tag_field \) \|\| ! empty\( \$extra_field \) \) \{\s*\r?\n\s*\$primary = \$this->get_field_value\( \$pid, \$tag_field \);\s*\r?\n\s*\$extra\s+= \$this->get_field_value\( \$pid, \$extra_field \);\s*\r?\n\s*if \( '''' === \$primary && '''' === \$extra && empty\( \$tag_field \) \) \{\s*\r?\n\s*\$primary = get_post_meta\( \$pid, SSCHP_Meta_Box::META_KEY, true \) \?: '''';\s*\r?\n\s*\}\s*\r?\n\s*\}\s*\r?\n\s*\}' -Replacement ''
    $handler = Replace-RegexLiteral -Text $handler -Pattern '(?s)\s*if \( true \) \{\s*\r?\n\s*\$configured = sanitize_key\( \$this->settings->get\( ''ajax_keyword_field'', ''keyword'' \) \);\s*\r?\n\s*if \( '''' !== \$configured \) \{\s*\r?\n\s*array_unshift\( \$fields, \$configured \);\s*\r?\n\s*\}\s*\r?\n\s*\}' -Replacement ''
    $handler = Replace-RegexLiteral -Text $handler -Pattern '(?s)\s*/\*\*\s*\r?\n\s*\* Get a field value from a post, supporting ACF and regular post meta\..*?private function get_field_value\( \$post_id, \$field_name \) \{.*?\r?\n\s*\}\s*\r?\n\s*/\*\*\s*\r?\n\s*\* Render chips HTML\.' -Replacement "`r`n    /**`r`n     * Render chips HTML."
    $handler = Replace-RegexLiteral -Text $handler -Pattern '(?s)public function init\(\) \{.*?add_action\( ''wp_ajax_nopriv_sschp_search'', array\( \$this, ''handle_search'' \) \);\s*\r?\n\s*\}' -Replacement "public function init() {`r`n        `$ajax_action = `$this->settings->get( 'override_ajax' );`r`n        if ( ! empty( `$ajax_action ) ) {`r`n            add_action( 'wp_ajax_' . `$ajax_action, array( `$this, 'handle_search' ), 0 );`r`n            add_action( 'wp_ajax_nopriv_' . `$ajax_action, array( `$this, 'handle_search' ), 0 );`r`n        }`r`n`r`n        add_action( 'wp_ajax_sschp_search', array( `$this, 'handle_search' ) );`r`n        add_action( 'wp_ajax_nopriv_sschp_search', array( `$this, 'handle_search' ) );`r`n    }"
    Set-Text -Path $handlerPath -Content $handler

    $readmePath = Join-Path $PluginDir 'readme.txt'
    $readme = Get-Content -Raw -LiteralPath $readmePath
    $readme = $readme -replace 'Generates smart, non-redundant filter tags from search results using custom fields\.', 'Generates smart, non-redundant filter tags from the built-in Search Chips Tags field.'
    $readme = $readme -replace 'Auto-generates smart, non-redundant filter tags from your search results\.', 'Auto-generates smart, non-redundant filter tags from the built-in Search Chips Tags field.'
    $readme = $readme -replace 'Plugin analyzes the top results and their custom field tags', 'Plugin analyzes the top results and their Search Chips Tags'
    $readme = $readme -replace '\* Uses custom fields \(ACF or regular post meta\) as tag sources\s*\r?\n', "* Uses the built-in Search Chips Tags field as the free tag source`r`n"
    $readme = $readme -replace '\* Synonym rules for normalizing equivalent catalog terms into one chip\s*\r?\n', ''
    $readme = $readme -replace '\* Numeric range slider chips for values like .*? or mapped numeric fields\s*\r?\n', ''
    $readme = $readme -replace '\* Manual chip curation with pinned chips, hidden chips, and display label overrides\s*\r?\n', ''
    $readme = $readme -replace '\* Search analytics for query, chip-click, impression, and zero-result insights\s*\r?\n', ''
    $readme = $readme -replace '\* AI prompt builder for external agent-assisted tag workflows\s*\r?\n', ''
    $readme = $readme -replace '4\. Set your custom field names \(primary tag field \+ extra tag field\)\s*\r?\n', "4. Add Search Chips Tags to your posts/products using the plugin meta box`r`n"
    $readme = $readme -replace '1\. Create a custom field on your posts/products \(e\.g\. `search_tags`\) with semicolon-separated tags\s*\r?\n2\. In plugin settings, set "Primary Tag Field" to your field name\s*\r?\n3\. Add `\[sschp_search\]` shortcode to any page, or set "Override AJAX Action" to hook into your theme''s existing search', "1. Add semicolon-separated tags to the built-in Search Chips Tags field on your posts/products`r`n2. Select which post types to search in Settings > Search Chips`r`n3. Add ``[sschp_search]`` shortcode to any page, or set ""Override AJAX Action"" to hook into your theme's existing search"
    $readme = $readme -replace 'You can use ACF \(Advanced Custom Fields\), regular WordPress custom fields, or populate them programmatically via the REST API\. Tags should be semicolon-separated in the primary field and comma-separated in the extra field\.', 'Use the built-in Search Chips Tags meta box on each post/product. Tags should be semicolon-separated. ACF/custom field connectors are available separately in Smart Search Chips Pro.'
    $readme = $readme -replace 'Yes\. Set "Post Types" to include "product" and use WooCommerce product attributes or ACF fields as your tag source\.', 'Yes. Set "Post Types" to include "product". The WooCommerce helper can populate the built-in Search Chips Tags field from product attributes, categories, tags, and SKU.'
    $readme = $readme -replace '(?s)== Privacy Policy ==.*?== Upgrade Notice ==', @'
== Smart Search Chips Pro ==

Smart Search Chips Pro is available separately from https://smartsearchchips.app/ for teams that want ACF/custom field connectors, numeric range slider chips, search analytics, synonym rules, manual chip curation, AI-assisted tag workflows, and commercial support. The WordPress.org plugin is fully functional with its built-in Search Chips Tags field and does not require accounts or payment.

== Privacy Policy ==

Smart Search Chips processes search queries entered by visitors to generate dynamic filter chips. Search queries are processed on this server and are not stored or sent to any third-party service by this WordPress.org edition. Full policy: https://smartsearchchips.app/privacy-policy/

== Upgrade Notice ==
'@
    Set-Text -Path $readmePath -Content $readme
}

function Remove-WpOrgExcludedFiles {
    param([string] $PluginDir)

    $paths = @(
        'vendor',
        'includes\class-license.php',
        'includes\class-chip-rules__premium_only.php',
        'includes\class-range-filters__premium_only.php',
        'includes\class-ai-tagger__premium_only.php',
        'includes\class-analytics__premium_only.php',
        'assets\js\admin-ai.js',
        'assets\js\range-filters__premium_only.js',
        'assets\css\range-filters__premium_only.css',
        'assets\css\analytics.css',
        'languages\smart-search-chips.pot'
    )

    foreach ($path in $paths) {
        $fullPath = Join-Path $PluginDir $path
        if (Test-Path $fullPath) {
            Assert-ChildPath -Parent $PluginDir -Child $fullPath
            Remove-Item -LiteralPath $fullPath -Recurse -Force
        }
    }
}

function Build-Pro {
    $parent = Split-Path -Parent $ProDir
    New-Item -ItemType Directory -Path $parent -Force | Out-Null
    Copy-PluginTree -Destination $ProDir -IncludeVendor $true
    Compress-Plugin -ParentDir $parent -ZipPath $ProZip
}

function Build-WpOrg {
    $parent = Split-Path -Parent $WpOrgDir
    New-Item -ItemType Directory -Path $parent -Force | Out-Null
    Copy-PluginTree -Destination $WpOrgDir -IncludeVendor $false
    Remove-WpOrgExcludedFiles -PluginDir $WpOrgDir
    Write-WpOrgMain -PluginDir $WpOrgDir
    Write-WpOrgEdition -PluginDir $WpOrgDir
    Write-WpOrgSettings -PluginDir $WpOrgDir
    Patch-WpOrgCoreFiles -PluginDir $WpOrgDir
    Compress-Plugin -ParentDir $parent -ZipPath $WpOrgZip
}

Reset-BuildRoot

if ($Target -in @('all', 'pro')) {
    Build-Pro
    Write-Host "Built pro package: $ProZip"
}

if ($Target -in @('all', 'wporg')) {
    Build-WpOrg
    Write-Host "Built WordPress.org package: $WpOrgZip"
}
