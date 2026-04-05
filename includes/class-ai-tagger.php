<?php
/**
 * AI Prompt Builder - Prepares external agent prompts for tag generation
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSC_AI_Tagger {

    /** @var SSC_Settings */
    private $settings;

    /** @var string */
    const AJAX_NONCE = 'ssc_ai_tagging_nonce';

    /**
     * @param SSC_Settings $settings Settings helper.
     */
    public function __construct( SSC_Settings $settings ) {
        $this->settings = $settings;
    }

    /**
     * Register admin AJAX hooks.
     */
    public function init() {
        add_action( 'wp_ajax_ssc_ai_check_rest', array( $this, 'ajax_check_rest' ) );
        add_action( 'wp_ajax_ssc_ai_build_prompt', array( $this, 'ajax_build_prompt' ) );
    }

    /**
     * Tag writing styles offered in the UI.
     *
     * @return array
     */
    public static function get_tag_styles() {
        return array(
            'technical' => __( 'Technical attributes', 'smart-search-chips' ),
            'catalog'   => __( 'Catalog filters', 'smart-search-chips' ),
            'mixed'     => __( 'Balanced mix', 'smart-search-chips' ),
            'minimal'   => __( 'Very short chips', 'smart-search-chips' ),
        );
    }

    /**
     * AJAX: verify that the selected items and target field are REST-ready.
     */
    public function ajax_check_rest() {
        $this->assert_admin_request();

        $config = $this->get_request_config();
        $posts  = $this->get_selected_posts( $config['post_ids'] );

        if ( is_wp_error( $posts ) ) {
            wp_send_json_error( array( 'message' => $posts->get_error_message() ), 400 );
        }

        $summary = $this->build_rest_summary( $posts, $config );
        if ( is_wp_error( $summary ) ) {
            wp_send_json_error( array( 'message' => $summary->get_error_message() ), 400 );
        }

        wp_send_json_success( $summary );
    }

    /**
     * AJAX: build the external-agent prompt for the selected items.
     */
    public function ajax_build_prompt() {
        $this->assert_admin_request();

        $config = $this->get_request_config();
        $posts  = $this->get_selected_posts( $config['post_ids'] );

        if ( is_wp_error( $posts ) ) {
            wp_send_json_error( array( 'message' => $posts->get_error_message() ), 400 );
        }

        $summary = $this->build_rest_summary( $posts, $config );
        if ( is_wp_error( $summary ) ) {
            wp_send_json_error( array( 'message' => $summary->get_error_message() ), 400 );
        }

        $prompt = $this->build_agent_prompt( $posts, $config, $summary );

        wp_send_json_success( array(
            'prompt'  => $prompt,
            'summary' => $summary,
        ) );
    }

    /**
     * Parse prompt-builder config from the request and stored settings.
     *
     * @return array
     */
    private function get_request_config() {
        $source_fields = array();
        if ( isset( $_POST['source_fields'] ) && is_array( $_POST['source_fields'] ) ) {
            $source_fields = array_map( 'sanitize_key', wp_unslash( $_POST['source_fields'] ) );
        } else {
            $saved_source_fields = $this->settings->get( 'ai_source_fields', array( 'title', 'excerpt', 'content' ) );
            $source_fields       = is_array( $saved_source_fields ) ? $saved_source_fields : array( 'title', 'excerpt', 'content' );
        }

        $post_ids = array();
        if ( isset( $_POST['post_ids'] ) && is_array( $_POST['post_ids'] ) ) {
            $post_ids = array_map( 'absint', wp_unslash( $_POST['post_ids'] ) );
        }

        return array(
            'post_ids'           => array_values( array_filter( array_unique( $post_ids ) ) ),
            'source_fields'      => array_values( array_unique( array_filter( $source_fields ) ) ),
            'custom_read_fields' => isset( $_POST['custom_read_fields'] ) ? sanitize_text_field( wp_unslash( $_POST['custom_read_fields'] ) ) : $this->settings->get( 'ai_custom_read_fields', '' ),
            'target_field'       => isset( $_POST['target_field'] ) ? sanitize_text_field( wp_unslash( $_POST['target_field'] ) ) : $this->settings->get( 'ai_target_field', 'built_in' ),
            'max_tags'           => isset( $_POST['max_tags'] ) ? absint( $_POST['max_tags'] ) : (int) $this->settings->get( 'ai_max_tags', 8 ),
            'language'           => isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : $this->settings->get( 'ai_language', '' ),
            'tag_style'          => isset( $_POST['tag_style'] ) ? sanitize_key( wp_unslash( $_POST['tag_style'] ) ) : $this->settings->get( 'ai_tag_style', 'technical' ),
            'custom_instruction' => isset( $_POST['custom_instruction'] ) ? sanitize_textarea_field( wp_unslash( $_POST['custom_instruction'] ) ) : $this->settings->get( 'ai_custom_instruction', '' ),
        );
    }

    /**
     * Load and validate the selected posts.
     *
     * @param array $post_ids Selected post IDs.
     * @return array|WP_Error
     */
    private function get_selected_posts( $post_ids ) {
        if ( empty( $post_ids ) ) {
            return new WP_Error( 'ssc_ai_missing_posts', __( 'Select at least one product or post for the prompt.', 'smart-search-chips' ) );
        }

        $posts = array();

        foreach ( $post_ids as $post_id ) {
            $post = get_post( $post_id );
            if ( ! $post ) {
                continue;
            }

            $posts[] = $post;
        }

        if ( empty( $posts ) ) {
            return new WP_Error( 'ssc_ai_invalid_posts', __( 'The selected items could not be loaded.', 'smart-search-chips' ) );
        }

        return $posts;
    }

    /**
     * Build a REST-access summary for the selected items and target field.
     *
     * @param WP_Post[] $posts  Selected posts.
     * @param array     $config Prompt config.
     * @return array|WP_Error
     */
    private function build_rest_summary( $posts, $config ) {
        $routes      = array();
        $source_map  = $this->get_source_field_map();
        $source_keys = array();

        foreach ( $config['source_fields'] as $field ) {
            if ( isset( $source_map[ $field ] ) ) {
                $source_keys[] = $source_map[ $field ];
            }
        }

        $target_meta_key = $this->get_target_meta_key( $config );
        if ( $target_meta_key === '' ) {
            return new WP_Error( 'ssc_ai_missing_target', __( 'Configure a valid target field before generating the prompt.', 'smart-search-chips' ) );
        }

        foreach ( $posts as $post ) {
            $post_type_object = get_post_type_object( $post->post_type );
            if ( ! $post_type_object || empty( $post_type_object->show_in_rest ) ) {
                return new WP_Error(
                    'ssc_ai_rest_unavailable',
                    sprintf(
                        /* translators: %s: post type */
                        __( 'The post type "%s" is not exposed in the WordPress REST API.', 'smart-search-chips' ),
                        $post->post_type
                    )
                );
            }

            if ( function_exists( 'get_registered_meta_keys' ) ) {
                $registered_meta = get_registered_meta_keys( 'post', $post->post_type );
                if ( ! isset( $registered_meta[ $target_meta_key ] ) ) {
                    return new WP_Error(
                        'ssc_ai_meta_not_rest_ready',
                        sprintf(
                            /* translators: 1: meta key, 2: post type */
                            __( 'The target field "%1$s" is not registered for REST updates on post type "%2$s".', 'smart-search-chips' ),
                            $target_meta_key,
                            $post->post_type
                        )
                    );
                }
            }

            $namespace = ! empty( $post_type_object->rest_namespace ) ? $post_type_object->rest_namespace : 'wp/v2';
            $rest_base = ! empty( $post_type_object->rest_base ) ? $post_type_object->rest_base : $post->post_type;
            $routes[]  = array(
                'id'       => $post->ID,
                'title'    => get_the_title( $post ),
                'postType' => $post->post_type,
                'route'    => rest_url( trailingslashit( $namespace ) . $rest_base . '/' . $post->ID ),
            );
        }

        return array(
            'selectedCount'     => count( $posts ),
            'sourceLabels'      => $source_keys,
            'customReadFields'  => $this->parse_csv_option( $config['custom_read_fields'] ),
            'targetFieldLabel'  => $this->get_target_label( $config ),
            'targetMetaKey'     => $target_meta_key,
            'maxTags'           => max( 3, min( 20, absint( $config['max_tags'] ) ) ),
            'tagStyleLabel'     => $this->get_tag_style_label( $config['tag_style'] ),
            'languageMode'      => $config['language'] ? $config['language'] : __( 'Same language as each selected item by default', 'smart-search-chips' ),
            'customInstruction' => $config['custom_instruction'],
            'routes'            => $routes,
            'restAuth'          => __( 'Use WordPress Application Passwords or another authenticated REST setup controlled by the site owner.', 'smart-search-chips' ),
            'writeFormat'       => sprintf(
                /* translators: %s: target meta key */
                __( 'Write semicolon-separated tags into meta.%s', 'smart-search-chips' ),
                $target_meta_key
            ),
        );
    }

    /**
     * Build the external-agent prompt.
     *
     * @param WP_Post[] $posts   Selected posts.
     * @param array     $config  Prompt config.
     * @param array     $summary REST summary.
     * @return string
     */
    private function build_agent_prompt( $posts, $config, $summary ) {
        $lines   = array();
        $lines[] = 'Use agent mode and your HTTP/REST tools for this task. Do not answer as a plain chat completion.';
        $lines[] = '';
        $lines[] = 'Goal';
        $lines[] = 'Generate high-signal search-chip tags for every selected item and write them back to the WordPress REST API.';
        $lines[] = '';
        $lines[] = 'What to do';
        $lines[] = '1. Authenticate to the WordPress REST API using the credentials pasted at the end of this prompt.';
        $lines[] = '2. Fetch every selected item from its REST route using context=edit when available.';
        $lines[] = '3. Analyze the entire selected batch together before writing anything. Cluster similar items by shared category, taxonomy, attributes, materials, certifications, compatibility, and naming patterns so you can identify what stands out within each group.';
        $lines[] = '4. Use only facts supported by the fetched data. Do not invent missing specifications.';
        $lines[] = '5. Produce up to ' . $summary['maxTags'] . ' tags per item.';
        $lines[] = '6. Store tags as a single semicolon-separated string, for example: `IP65; Stainless steel; CE; Outdoor use`.';
        $lines[] = '7. Default to the same language as the content of each selected item. Only override that language behavior if the custom instruction explicitly requires it.';
        $lines[] = '8. Write the result back into `' . $summary['targetMetaKey'] . '` through the WordPress REST API using the `meta` payload.';
        $lines[] = '9. Preserve unrelated content and fields. Only update the chosen target field.';
        $lines[] = '10. At the end, report which items were updated and the exact semicolon-separated value written for each one.';
        $lines[] = '';
        $lines[] = 'Selected source fields';
        foreach ( $summary['sourceLabels'] as $label ) {
            $lines[] = '- ' . $label;
        }

        if ( ! empty( $summary['customReadFields'] ) ) {
            $lines[] = '- Extra custom fields: ' . implode( ', ', $summary['customReadFields'] );
        }

        $lines[] = '';
        $lines[] = 'Tag writing style';
        $lines[] = $summary['tagStyleLabel'] . '. Prioritize tags that help users filter similar products apart, not generic marketing claims.';
        $lines[] = '';
        $lines[] = 'Target field';
        $lines[] = $summary['targetFieldLabel'] . ' (`meta.' . $summary['targetMetaKey'] . '`)';
        $lines[] = '';
        $lines[] = 'REST routes to process';

        foreach ( $summary['routes'] as $route ) {
            $lines[] = '- ID ' . $route['id'] . ' | ' . $route['postType'] . ' | ' . $route['title'];
            $lines[] = '  Route: ' . $route['route'];
        }

        $lines[] = '';
        $lines[] = 'REST update format';
        $lines[] = 'Send authenticated requests to each item route and update the selected field with a `meta` object. Example payload:';
        $lines[] = '{';
        $lines[] = '  "meta": {';
        $lines[] = '    "' . $summary['targetMetaKey'] . '": "Tag one; Tag two; Tag three"';
        $lines[] = '  }';
        $lines[] = '}';
        $lines[] = '';
        $lines[] = 'Operational notes';
        $lines[] = '- If an item route fails, stop and report the failure instead of guessing.';
        $lines[] = '- If the current item already has useful tags in the target field, use them as context but replace them only with a clearly better semicolon-separated final value.';
        $lines[] = '- Be consistent across the whole batch. Similar products should share taxonomy-level tags and differ on the dimensions that actually matter.';
        $lines[] = '- If WooCommerce-specific attributes are not available from the REST route, continue with the rest of the fetched item data instead of hallucinating them.';

        if ( ! empty( $config['custom_instruction'] ) ) {
            $lines[] = '';
            $lines[] = 'Custom instruction';
            $lines[] = trim( $config['custom_instruction'] );
        }

        $lines[] = '';
        $lines[] = 'Credentials to paste before running the agent';
        $lines[] = 'Enter your credentials here:';
        $lines[] = 'WordPress site URL: ' . home_url( '/' );
        $lines[] = 'WordPress username: <PASTE HERE>';
        $lines[] = 'WordPress Application Password: <PASTE HERE>';
        $lines[] = 'Authentication method: Basic Auth with WordPress Application Passwords';

        if ( ! empty( $posts ) ) {
            $lines[] = '';
            $lines[] = 'Batch scope';
            $lines[] = 'Selected items: ' . count( $posts );
        }

        return implode( "\n", $lines );
    }

    /**
     * Ensure a privileged, nonce-protected Pro request.
     */
    private function assert_admin_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to use the AI prompt builder.', 'smart-search-chips' ) ), 403 );
        }

        check_ajax_referer( self::AJAX_NONCE, 'nonce' );

        if ( ! SSC_License::is_pro() ) {
            wp_send_json_error( array( 'message' => __( 'The AI prompt builder is available in Pro.', 'smart-search-chips' ) ), 403 );
        }
    }

    /**
     * Resolve the current target meta key.
     *
     * @param array $config Runtime config.
     * @return string
     */
    private function get_target_meta_key( $config ) {
        if ( $config['target_field'] === 'mapped_primary' ) {
            $field_name = trim( (string) $this->settings->get( 'tag_fields', '' ) );
            if ( $field_name !== '' ) {
                return $field_name;
            }
        }

        return SSC_Meta_Box::META_KEY;
    }

    /**
     * Return a target field label for UI display.
     *
     * @param array $config Runtime config.
     * @return string
     */
    private function get_target_label( $config ) {
        if ( $config['target_field'] === 'mapped_primary' ) {
            $field_name = trim( (string) $this->settings->get( 'tag_fields', '' ) );
            if ( $field_name !== '' ) {
                return sprintf(
                    /* translators: %s: field name */
                    __( 'Mapped primary field (%s)', 'smart-search-chips' ),
                    $field_name
                );
            }
        }

        return __( 'Built-in Search Chips Tags', 'smart-search-chips' );
    }

    /**
     * Return the label map for selectable source fields.
     *
     * @return array
     */
    private function get_source_field_map() {
        return array(
            'title'          => __( 'Title', 'smart-search-chips' ),
            'excerpt'        => __( 'Excerpt', 'smart-search-chips' ),
            'content'        => __( 'Content', 'smart-search-chips' ),
            'taxonomies'     => __( 'Categories and taxonomies', 'smart-search-chips' ),
            'woo_attributes' => __( 'WooCommerce attributes and SKU', 'smart-search-chips' ),
        );
    }

    /**
     * Get a tag style label from its key.
     *
     * @param string $key Style key.
     * @return string
     */
    private function get_tag_style_label( $key ) {
        $styles = self::get_tag_styles();
        return isset( $styles[ $key ] ) ? $styles[ $key ] : $styles['technical'];
    }

    /**
     * Parse a CSV option into trimmed field names.
     *
     * @param string $value CSV string.
     * @return array
     */
    private function parse_csv_option( $value ) {
        if ( ! is_string( $value ) || trim( $value ) === '' ) {
            return array();
        }

        return array_values(
            array_filter(
                array_map(
                    'trim',
                    explode( ',', $value )
                )
            )
        );
    }
}
