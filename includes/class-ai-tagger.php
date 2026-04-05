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
        add_action( 'wp_ajax_ssc_ai_browse_terms', array( $this, 'ajax_browse_terms' ) );
        add_action( 'wp_ajax_ssc_ai_load_term_items', array( $this, 'ajax_load_term_items' ) );
        add_action( 'wp_ajax_ssc_ai_build_prompt', array( $this, 'ajax_build_prompt' ) );
    }

    /**
     * AJAX: load taxonomy collections for the prompt builder.
     */
    public function ajax_browse_terms() {
        $this->assert_admin_request();

        $allowed_post_types = $this->get_allowed_post_types();
        if ( empty( $allowed_post_types ) ) {
            wp_send_json_error( array( 'message' => __( 'No selectable post types are configured for the AI prompt builder.', 'smart-search-chips' ) ), 400 );
        }

        wp_send_json_success( array(
            'collections' => $this->get_term_collections( $allowed_post_types ),
        ) );
    }

    /**
     * AJAX: load all items for a specific taxonomy term.
     */
    public function ajax_load_term_items() {
        $this->assert_admin_request();

        $taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( wp_unslash( $_POST['taxonomy'] ) ) : '';
        $term_id  = isset( $_POST['term_id'] ) ? absint( $_POST['term_id'] ) : 0;

        if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) || $term_id < 1 ) {
            wp_send_json_error( array( 'message' => __( 'Select a valid category before loading its items.', 'smart-search-chips' ) ), 400 );
        }

        $allowed_post_types = $this->get_allowed_post_types();
        $taxonomy_object    = get_taxonomy( $taxonomy );

        if ( ! $taxonomy_object ) {
            wp_send_json_error( array( 'message' => __( 'The selected taxonomy is not available.', 'smart-search-chips' ) ), 400 );
        }

        $query_post_types = array_values( array_intersect( $allowed_post_types, (array) $taxonomy_object->object_type ) );
        if ( empty( $query_post_types ) ) {
            wp_send_json_error( array( 'message' => __( 'That category does not belong to the configured post types.', 'smart-search-chips' ) ), 400 );
        }

        $term = get_term( $term_id, $taxonomy );
        if ( ! $term || is_wp_error( $term ) ) {
            wp_send_json_error( array( 'message' => __( 'The selected category could not be loaded.', 'smart-search-chips' ) ), 400 );
        }

        $posts = get_posts( array(
            'post_type'           => $query_post_types,
            'post_status'         => array( 'publish', 'draft', 'private' ),
            'posts_per_page'      => -1,
            'orderby'             => 'title',
            'order'               => 'ASC',
            'ignore_sticky_posts' => true,
            'tax_query'           => array(
                array(
                    'taxonomy'         => $taxonomy,
                    'terms'            => array( $term_id ),
                    'field'            => 'term_id',
                    'include_children' => is_taxonomy_hierarchical( $taxonomy ),
                ),
            ),
        ) );

        $items = array();
        foreach ( $posts as $post ) {
            $items[] = $this->format_browser_item( $post );
        }

        wp_send_json_success( array(
            'taxonomy' => $taxonomy,
            'termId'   => $term_id,
            'termName' => $term->name,
            'count'    => count( $items ),
            'items'    => $items,
        ) );
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
        $post_ids = array();
        if ( isset( $_POST['post_ids'] ) && is_array( $_POST['post_ids'] ) ) {
            $post_ids = array_map( 'absint', wp_unslash( $_POST['post_ids'] ) );
        }

        return array(
            'post_ids'           => array_values( array_filter( array_unique( $post_ids ) ) ),
            'custom_read_fields' => isset( $_POST['custom_read_fields'] ) ? sanitize_text_field( wp_unslash( $_POST['custom_read_fields'] ) ) : $this->settings->get( 'ai_custom_read_fields', '' ),
            'target_field'       => isset( $_POST['target_field'] ) ? sanitize_text_field( wp_unslash( $_POST['target_field'] ) ) : $this->settings->get( 'ai_target_field', 'built_in' ),
            'rest_enabled'       => isset( $_POST['rest_enabled'] ) ? ( ! empty( $_POST['rest_enabled'] ) ? '1' : '0' ) : $this->settings->get( 'ai_rest_enabled', '1' ),
            'max_tags'           => isset( $_POST['max_tags'] ) ? absint( $_POST['max_tags'] ) : (int) $this->settings->get( 'ai_max_tags', 8 ),
            'language'           => isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : $this->settings->get( 'ai_language', '' ),
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
        $routes       = array();
        $rest_enabled = ! empty( $config['rest_enabled'] ) && $config['rest_enabled'] === '1';

        $target_meta_key = $this->get_target_meta_key( $config );
        if ( $target_meta_key === '' ) {
            return new WP_Error( 'ssc_ai_missing_target', __( 'Configure a valid target field before generating the prompt.', 'smart-search-chips' ) );
        }

        if ( $rest_enabled ) {
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
        }

        return array(
            'selectedCount'     => count( $posts ),
            'customReadFields'  => $this->parse_csv_option( $config['custom_read_fields'] ),
            'targetFieldLabel'  => $this->get_target_label( $config ),
            'targetMetaKey'     => $target_meta_key,
            'maxTags'           => max( 0, absint( $config['max_tags'] ) ),
            'maxTagsLabel'      => absint( $config['max_tags'] ) === 0 ? __( 'No limit', 'smart-search-chips' ) : (string) absint( $config['max_tags'] ),
            'languageMode'      => $config['language'] ? $config['language'] : __( 'Same language as each selected item by default', 'smart-search-chips' ),
            'customInstruction' => $config['custom_instruction'],
            'restEnabled'       => $rest_enabled,
            'routes'            => $routes,
            'restAuth'          => $rest_enabled
                ? __( 'Use WordPress Application Passwords or another authenticated WordPress REST setup controlled by the site owner.', 'smart-search-chips' )
                : __( 'REST write-back disabled. The agent should return tags for manual review instead of updating WordPress directly.', 'smart-search-chips' ),
            'writeFormat'       => $rest_enabled
                ? sprintf(
                    /* translators: %s: target meta key */
                    __( 'Write semicolon-separated tags into meta.%s', 'smart-search-chips' ),
                    $target_meta_key
                )
                : __( 'Return semicolon-separated tags in the response only.', 'smart-search-chips' ),
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
        if ( ! empty( $summary['restEnabled'] ) ) {
            $lines[] = 'Use agent mode and your HTTP/REST tools for this task. Do not answer as a plain chat completion.';
        } else {
            $lines[] = 'Use agent mode for this task. Do not answer as a plain chat completion.';
        }
        $lines[] = '';
        $lines[] = 'Goal';
        $lines[] = ! empty( $summary['restEnabled'] )
            ? 'Generate high-signal search-chip tags for every selected item and write them back to the WordPress REST API.'
            : 'Generate high-signal search-chip tags for every selected item and return them for manual review.';
        $lines[] = '';
        $lines[] = 'What to do';
        if ( ! empty( $summary['restEnabled'] ) ) {
            $lines[] = '1. Authenticate to the WordPress REST API using the WordPress REST credentials pasted at the end of this prompt.';
            $lines[] = '2. Fetch every selected item from its REST route using context=edit when available.';
            $lines[] = '3. Analyze the entire selected batch together before writing anything. Cluster similar items by shared category, taxonomy, attributes, materials, certifications, compatibility, and naming patterns so you can identify what stands out within each group.';
            $lines[] = '4. Use only facts supported by the fetched data. Do not invent missing specifications.';
            if ( $summary['maxTags'] > 0 ) {
                $lines[] = '5. Produce up to ' . $summary['maxTags'] . ' tags per item.';
            } else {
                $lines[] = '5. There is no hard tag limit per item, but keep the tag list disciplined and useful for filtering.';
            }
            $lines[] = '6. Store tags as a single semicolon-separated string, for example: `IP65; Stainless steel; CE; Outdoor use`.';
            $lines[] = '7. Default to the same language as the content of each selected item. Only override that language behavior if the custom instruction explicitly requires it.';
            $lines[] = '8. Write the result back into `' . $summary['targetMetaKey'] . '` through the WordPress REST API using the `meta` payload.';
            $lines[] = '9. Preserve unrelated content and fields. Only update the chosen target field.';
            $lines[] = '10. At the end, report which items were updated and the exact semicolon-separated value written for each one.';
        } else {
            $lines[] = '1. Analyze the entire selected batch together before writing anything. Cluster similar items by shared category, taxonomy, attributes, materials, certifications, compatibility, and naming patterns so you can identify what stands out within each group.';
            $lines[] = '2. Use only facts supported by the known item titles and context. Do not invent missing specifications.';
            if ( $summary['maxTags'] > 0 ) {
                $lines[] = '3. Produce up to ' . $summary['maxTags'] . ' tags per item.';
            } else {
                $lines[] = '3. There is no hard tag limit per item, but keep the tag list disciplined and useful for filtering.';
            }
            $lines[] = '4. Store tags as a single semicolon-separated string, for example: `IP65; Stainless steel; CE; Outdoor use`.';
            $lines[] = '5. Default to the same language as the content of each selected item. Only override that language behavior if the custom instruction explicitly requires it.';
            $lines[] = '6. Do not write anything back to WordPress automatically.';
            $lines[] = '7. Return a final list of every selected item with its proposed semicolon-separated value so the site owner can review and paste it manually.';
        }
        if ( ! empty( $summary['customReadFields'] ) ) {
            $lines[] = '';
            $lines[] = 'Extra custom fields to consider';
            $lines[] = '- Extra custom fields: ' . implode( ', ', $summary['customReadFields'] );
        }
        $lines[] = '';
        $lines[] = 'Target field';
        if ( ! empty( $summary['restEnabled'] ) ) {
            $lines[] = $summary['targetFieldLabel'] . ' (`meta.' . $summary['targetMetaKey'] . '`)';
            $lines[] = '';
            $lines[] = 'REST routes to process';

            foreach ( $summary['routes'] as $route ) {
                $lines[] = '- ID ' . $route['id'] . ' | ' . $route['postType'] . ' | ' . $route['title'];
                $lines[] = '  Route: ' . $route['route'];
            }
        } else {
            $lines[] = $summary['targetFieldLabel'] . ' (manual review only, no automatic write-back)';
        }

        if ( ! empty( $summary['restEnabled'] ) ) {
            $lines[] = '';
            $lines[] = 'REST update format';
            $lines[] = 'Send authenticated requests to each item route and update the selected field with a `meta` object. Example payload:';
            $lines[] = '{';
            $lines[] = '  "meta": {';
            $lines[] = '    "' . $summary['targetMetaKey'] . '": "Tag one; Tag two; Tag three"';
            $lines[] = '  }';
            $lines[] = '}';
        }

        $lines[] = '';
        $lines[] = 'Operational notes';
        if ( ! empty( $summary['restEnabled'] ) ) {
            $lines[] = '- If an item route fails, stop and report the failure instead of guessing.';
            $lines[] = '- If the current item already has useful tags in the target field, use them as context but replace them only with a clearly better semicolon-separated final value.';
            $lines[] = '- Be consistent across the whole batch. Similar products should share taxonomy-level tags and differ on the dimensions that actually matter.';
            $lines[] = '- If WooCommerce-specific attributes are not available from the REST route, continue with the rest of the fetched item data instead of hallucinating them.';
        } else {
            $lines[] = '- Be consistent across the whole batch. Similar products should share taxonomy-level tags and differ on the dimensions that actually matter.';
            $lines[] = '- Return one semicolon-separated value per selected item, clearly labeled by item title or ID.';
            $lines[] = '- Do not assume access to unpublished fields or hidden metadata when REST write-back is disabled.';
        }

        if ( ! empty( $config['custom_instruction'] ) ) {
            $lines[] = '';
            $lines[] = 'Custom instruction';
            $lines[] = trim( $config['custom_instruction'] );
        }

        if ( ! empty( $summary['restEnabled'] ) ) {
            $lines[] = '';
            $lines[] = 'WordPress REST credentials to paste before running the agent';
            $lines[] = 'Enter your WordPress REST credentials here:';
            $lines[] = 'WordPress site URL: ' . home_url( '/' );
            $lines[] = 'WordPress username: <PASTE HERE>';
            $lines[] = 'WordPress Application Password: <PASTE HERE>';
            $lines[] = 'Authentication method: Basic Auth with WordPress Application Passwords';
            $lines[] = 'Do not use or request OpenAI, Anthropic, Gemini, or other LLM-provider API keys for this task.';
        }

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
     * Return post types enabled for prompt-builder selection.
     *
     * @return string[]
     */
    private function get_allowed_post_types() {
        $post_types = get_option( 'ssc_post_types', array( 'post' ) );
        if ( ! is_array( $post_types ) ) {
            $post_types = array( $post_types );
        }

        $post_types = array_values( array_filter( array_map( 'sanitize_key', $post_types ) ) );

        return array_values( array_filter( $post_types, 'post_type_exists' ) );
    }

    /**
     * Build taxonomy collections for the term browser.
     *
     * @param string[] $allowed_post_types Allowed post types.
     * @return array[]
     */
    private function get_term_collections( $allowed_post_types ) {
        $collections = array();
        $taxonomies  = $this->get_allowed_taxonomies( $allowed_post_types );

        foreach ( $taxonomies as $taxonomy ) {
            $terms = get_terms( array(
                'taxonomy'   => $taxonomy->name,
                'hide_empty' => true,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ) );

            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                continue;
            }

            $collections[] = array(
                'taxonomy'     => $taxonomy->name,
                'label'        => ! empty( $taxonomy->labels->name ) ? $taxonomy->labels->name : $taxonomy->name,
                'singular'     => ! empty( $taxonomy->labels->singular_name ) ? $taxonomy->labels->singular_name : $taxonomy->name,
                'hierarchical' => ! empty( $taxonomy->hierarchical ),
                'terms'        => $this->build_term_tree( $terms, ! empty( $taxonomy->hierarchical ) ),
            );
        }

        return $collections;
    }

    /**
     * Collect public taxonomies for the configured post types.
     *
     * @param string[] $allowed_post_types Allowed post types.
     * @return WP_Taxonomy[]
     */
    private function get_allowed_taxonomies( $allowed_post_types ) {
        $taxonomies = array();

        foreach ( $allowed_post_types as $post_type ) {
            $objects = get_object_taxonomies( $post_type, 'objects' );

            foreach ( $objects as $taxonomy ) {
                if ( empty( $taxonomy->public ) || empty( $taxonomy->show_ui ) ) {
                    continue;
                }

                $taxonomies[ $taxonomy->name ] = $taxonomy;
            }
        }

        uasort( $taxonomies, array( $this, 'sort_taxonomies' ) );

        return array_values( $taxonomies );
    }

    /**
     * Sort taxonomy collections with hierarchical ones first.
     *
     * @param WP_Taxonomy $left  Taxonomy object.
     * @param WP_Taxonomy $right Taxonomy object.
     * @return int
     */
    private function sort_taxonomies( $left, $right ) {
        if ( ! empty( $left->hierarchical ) && empty( $right->hierarchical ) ) {
            return -1;
        }

        if ( empty( $left->hierarchical ) && ! empty( $right->hierarchical ) ) {
            return 1;
        }

        return strcmp( $left->labels->name, $right->labels->name );
    }

    /**
     * Build a nested term tree for the browser.
     *
     * @param WP_Term[] $terms          Terms.
     * @param bool      $hierarchical   Whether the taxonomy is hierarchical.
     * @return array[]
     */
    private function build_term_tree( $terms, $hierarchical ) {
        if ( ! $hierarchical ) {
            return array_map( array( $this, 'format_term_node' ), $terms );
        }

        $terms_by_parent = array();
        foreach ( $terms as $term ) {
            $parent = (int) $term->parent;
            if ( ! isset( $terms_by_parent[ $parent ] ) ) {
                $terms_by_parent[ $parent ] = array();
            }
            $terms_by_parent[ $parent ][] = $term;
        }

        return $this->build_term_branch( $terms_by_parent, 0, 0 );
    }

    /**
     * Recursively build a term branch.
     *
     * @param array $terms_by_parent Grouped terms.
     * @param int   $parent_id       Parent term ID.
     * @param int   $depth           Current depth.
     * @return array[]
     */
    private function build_term_branch( $terms_by_parent, $parent_id, $depth ) {
        $branch = array();

        if ( empty( $terms_by_parent[ $parent_id ] ) ) {
            return $branch;
        }

        foreach ( $terms_by_parent[ $parent_id ] as $term ) {
            $node             = $this->format_term_node( $term );
            $node['depth']    = $depth;
            $node['children'] = $this->build_term_branch( $terms_by_parent, (int) $term->term_id, $depth + 1 );
            $branch[]         = $node;
        }

        return $branch;
    }

    /**
     * Format a term node for the browser.
     *
     * @param WP_Term $term Term object.
     * @return array
     */
    private function format_term_node( $term ) {
        return array(
            'id'       => (int) $term->term_id,
            'taxonomy' => $term->taxonomy,
            'name'     => $term->name,
            'slug'     => $term->slug,
            'count'    => (int) $term->count,
            'depth'    => 0,
            'children' => array(),
        );
    }

    /**
     * Format a post for the item browser card UI.
     *
     * @param WP_Post $post Post object.
     * @return array
     */
    private function format_browser_item( $post ) {
        $post_type_object = get_post_type_object( $post->post_type );
        $title            = get_the_title( $post );
        $status_object    = get_post_status_object( $post->post_status );

        return array(
            'id'            => (int) $post->ID,
            'title'         => $title !== '' ? $title : sprintf( __( '(no title) #%d', 'smart-search-chips' ), $post->ID ),
            'postType'      => $post->post_type,
            'postTypeLabel' => $post_type_object && ! empty( $post_type_object->labels->singular_name ) ? $post_type_object->labels->singular_name : $post->post_type,
            'status'        => $post->post_status,
            'statusLabel'   => $status_object && ! empty( $status_object->label ) ? $status_object->label : $post->post_status,
            'dateLabel'     => mysql2date( get_option( 'date_format' ), $post->post_date ),
            'terms'         => $this->get_browser_terms( $post ),
        );
    }

    /**
     * Collect a short list of visible taxonomy terms for the item browser.
     *
     * @param WP_Post $post Post object.
     * @return string[]
     */
    private function get_browser_terms( $post ) {
        $labels     = array();
        $taxonomies = get_object_taxonomies( $post->post_type, 'objects' );

        foreach ( $taxonomies as $taxonomy ) {
            if ( empty( $taxonomy->public ) ) {
                continue;
            }

            $terms = get_the_terms( $post, $taxonomy->name );
            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                continue;
            }

            foreach ( $terms as $term ) {
                $labels[ $term->name ] = $term->name;
                if ( count( $labels ) >= 4 ) {
                    break 2;
                }
            }
        }

        return array_values( $labels );
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
