<?php
/**
 * Meta Box - Built-in tag editor for the post editor (Free version)
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'sschp_can_edit_post_meta' ) ) {
    /**
     * Restrict REST-exposed Search Chips meta to users who can edit the specific post.
     *
     * WordPress calls registered meta auth callbacks with the same arguments as the
     * underlying auth_*_meta_* filter, so we only allow access when a concrete post
     * ID is provided and the current user can edit that object.
     *
     * @param bool|null $allowed Existing decision.
     * @param string    $meta_key Meta key being checked.
     * @param int       $post_id Post ID being edited.
     * @param int       $user_id User ID when WordPress provides one.
     * @return bool
     */
    function sschp_can_edit_post_meta( $allowed, $meta_key, $post_id, $user_id = 0 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        $post_id = absint( $post_id );

        if ( $post_id <= 0 ) {
            return false;
        }

        if ( function_exists( 'user_can' ) && $user_id > 0 ) {
            return user_can( $user_id, 'edit_post', $post_id );
        }

        return current_user_can( 'edit_post', $post_id );
    }
}

class SSCHP_Meta_Box {

    /** @var string The built-in meta key */
    const META_KEY = 'sschp_tags';

    /**
     * Initialize meta box hooks.
     */
    public function init() {
        add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_meta_box' ) );
        add_action( 'init', array( $this, 'register_meta' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
    }

    /**
     * Register the meta key for REST API.
     */
    public function register_meta() {
        $post_types = get_option( 'sschp_post_types', array( 'post' ) );
        if ( ! is_array( $post_types ) ) {
            $post_types = array( $post_types );
        }

        foreach ( $post_types as $pt ) {
            register_post_meta( $pt, self::META_KEY, array(
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => 'string',
                'auth_callback' => 'sschp_can_edit_post_meta',
            ) );
        }
    }

    /**
     * Register the meta box on configured post types.
     */
    public function register_meta_box() {
        $post_types = get_option( 'sschp_post_types', array( 'post' ) );
        if ( ! is_array( $post_types ) ) {
            $post_types = array( $post_types );
        }

        foreach ( $post_types as $pt ) {
            add_meta_box(
                'sschp_tags_meta_box',
                __( 'Search Chips Tags', 'smart-search-chips' ),
                array( $this, 'render_meta_box' ),
                $pt,
                'side',
                'default'
            );
        }
    }

    /**
     * Render the meta box content.
     *
     * @param WP_Post $post Current post.
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'sschp_tags_nonce', 'sschp_tags_nonce_field' );

        $value = get_post_meta( $post->ID, self::META_KEY, true );
        $tags  = $value ? array_filter( array_map( 'trim', explode( ';', $value ) ) ) : array();

        ?>
        <div class="ssc-meta-box">
            <div class="ssc-meta-box__tags" id="ssc-tags-container">
                <?php foreach ( $tags as $tag ) : ?>
                    <span class="ssc-meta-box__tag">
                        <?php echo esc_html( $tag ); ?>
                        <button type="button" class="ssc-meta-box__remove" data-tag="<?php echo esc_attr( $tag ); ?>">&times;</button>
                    </span>
                <?php endforeach; ?>
            </div>
            <div class="ssc-meta-box__input-row">
                <input type="text"
                       id="ssc-tag-input"
                       placeholder="<?php esc_attr_e( 'Add tag...', 'smart-search-chips' ); ?>"
                       class="widefat" />
                <button type="button" id="ssc-add-tag" class="button button-small">
                    <?php esc_html_e( 'Add', 'smart-search-chips' ); ?>
                </button>
            </div>
            <input type="hidden"
                   name="sschp_tags"
                   id="ssc-tags-hidden"
                   value="<?php echo esc_attr( $value ); ?>" />
            <p class="description">
                <?php esc_html_e( 'Tags used for search filter chips. Add key product attributes like material, IP rating, certifications, etc.', 'smart-search-chips' ); ?>
            </p>
        </div>

        <?php
    }

    /**
     * Save meta box data.
     *
     * @param int $post_id Post ID.
     */
    public function save_meta_box( $post_id ) {
        if ( ! isset( $_POST['sschp_tags_nonce_field'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sschp_tags_nonce_field'] ) ), 'sschp_tags_nonce' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['sschp_tags'] ) ) {
            $value = sanitize_text_field( wp_unslash( $_POST['sschp_tags'] ) );
            update_post_meta( $post_id, self::META_KEY, $value );
        }
    }

    /**
     * Enqueue admin styles for the meta box.
     */
    public function enqueue_admin_styles( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        wp_enqueue_style(
            'ssc-meta-box',
            SSCHP_PLUGIN_URL . 'assets/css/meta-box.css',
            array(),
            SSCHP_VERSION
        );

        wp_enqueue_script(
            'ssc-meta-box',
            SSCHP_PLUGIN_URL . 'assets/js/meta-box.js',
            array(),
            SSCHP_VERSION,
            true
        );
    }
}
