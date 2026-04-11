<?php
/**
 * Meta Box - Built-in tag editor for the post editor (Free version)
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSC_Meta_Box {

    /** @var string The built-in meta key */
    const META_KEY = 'ssc_tags';

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
        $post_types = get_option( 'ssc_post_types', array( 'post' ) );
        if ( ! is_array( $post_types ) ) {
            $post_types = array( $post_types );
        }

        foreach ( $post_types as $pt ) {
            register_post_meta( $pt, self::META_KEY, array(
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => 'string',
                'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
            ) );
        }
    }

    /**
     * Register the meta box on configured post types.
     */
    public function register_meta_box() {
        $post_types = get_option( 'ssc_post_types', array( 'post' ) );
        if ( ! is_array( $post_types ) ) {
            $post_types = array( $post_types );
        }

        foreach ( $post_types as $pt ) {
            add_meta_box(
                'ssc_tags_meta_box',
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
        wp_nonce_field( 'ssc_tags_nonce', 'ssc_tags_nonce_field' );

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
                   name="ssc_tags"
                   id="ssc-tags-hidden"
                   value="<?php echo esc_attr( $value ); ?>" />
            <p class="description">
                <?php esc_html_e( 'Tags used for search filter chips. Add key product attributes like material, IP rating, certifications, etc.', 'smart-search-chips' ); ?>
            </p>
        </div>

        <script>
        (function() {
            var container = document.getElementById('ssc-tags-container');
            var input = document.getElementById('ssc-tag-input');
            var hidden = document.getElementById('ssc-tags-hidden');
            var addBtn = document.getElementById('ssc-add-tag');

            function updateHidden() {
                var tags = [];
                container.querySelectorAll('.ssc-meta-box__tag').forEach(function(el) {
                    var btn = el.querySelector('.ssc-meta-box__remove');
                    if (btn) tags.push(btn.getAttribute('data-tag'));
                });
                hidden.value = tags.join('; ');
            }

            function addTag(text) {
                text = text.trim();
                if (!text) return;

                // Check duplicate
                var existing = hidden.value.split(';').map(function(t) { return t.trim().toLowerCase(); });
                if (existing.indexOf(text.toLowerCase()) > -1) return;

                var span = document.createElement('span');
                span.className = 'ssc-meta-box__tag';
                span.textContent = text + ' ';
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ssc-meta-box__remove';
                btn.setAttribute('data-tag', text);
                btn.textContent = '\u00d7';
                span.appendChild(btn);
                container.appendChild(span);
                updateHidden();
                input.value = '';
                input.focus();
            }

            addBtn.addEventListener('click', function() { addTag(input.value); });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addTag(input.value);
                }
            });

            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('ssc-meta-box__remove')) {
                    e.target.parentElement.remove();
                    updateHidden();
                }
            });
        })();
        </script>
        <?php
    }

    /**
     * Save meta box data.
     *
     * @param int $post_id Post ID.
     */
    public function save_meta_box( $post_id ) {
        if ( ! isset( $_POST['ssc_tags_nonce_field'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $_POST['ssc_tags_nonce_field'], 'ssc_tags_nonce' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['ssc_tags'] ) ) {
            $value = sanitize_text_field( $_POST['ssc_tags'] );
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

        wp_add_inline_style( 'wp-admin', '
            .ssc-meta-box__tags { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px; }
            .ssc-meta-box__tag { display: inline-flex; align-items: center; gap: 2px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 12px; padding: 2px 8px; font-size: 12px; }
            .ssc-meta-box__remove { background: none; border: none; cursor: pointer; font-size: 14px; color: #a00; padding: 0 2px; line-height: 1; }
            .ssc-meta-box__remove:hover { color: #d00; }
            .ssc-meta-box__input-row { display: flex; gap: 4px; margin-bottom: 8px; }
            .ssc-meta-box__input-row input { flex: 1; }
        ' );
    }
}
