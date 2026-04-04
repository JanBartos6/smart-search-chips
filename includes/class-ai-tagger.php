<?php
/**
 * AI Tagger - Pro AI-assisted tag generation for posts and products
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
        add_action( 'wp_ajax_ssc_ai_estimate_cost', array( $this, 'ajax_estimate_cost' ) );
        add_action( 'wp_ajax_ssc_ai_test_connection', array( $this, 'ajax_test_connection' ) );
        add_action( 'wp_ajax_ssc_ai_preview_tags', array( $this, 'ajax_preview_tags' ) );
        add_action( 'wp_ajax_ssc_ai_generate_tags', array( $this, 'ajax_generate_tags' ) );
    }

    /**
     * Available providers.
     *
     * @return array
     */
    public static function get_providers() {
        return array(
            'openai'   => 'OpenAI',
            'anthropic'=> 'Anthropic',
            'gemini'   => 'Gemini',
        );
    }

    /**
     * Curated text models with approximate Standard per-token pricing.
     *
     * @return array
     */
    public static function get_models() {
        return array(
            'openai' => array(
                array(
                    'id'           => 'gpt-5-mini',
                    'label'        => 'GPT-5 mini',
                    'input_cost'   => 0.25,
                    'output_cost'  => 2.00,
                    'description'  => 'Low-cost, fast tagging.',
                ),
                array(
                    'id'           => 'gpt-4.1-mini',
                    'label'        => 'GPT-4.1 mini',
                    'input_cost'   => 0.40,
                    'output_cost'  => 1.60,
                    'description'  => 'Stable, inexpensive general tagging.',
                ),
            ),
            'anthropic' => array(
                array(
                    'id'           => 'claude-sonnet-4-20250514',
                    'label'        => 'Claude Sonnet 4',
                    'input_cost'   => 3.00,
                    'output_cost'  => 15.00,
                    'description'  => 'Higher quality at a higher cost.',
                ),
                array(
                    'id'           => 'claude-3-5-haiku-latest',
                    'label'        => 'Claude Haiku 3.5',
                    'input_cost'   => 0.80,
                    'output_cost'  => 4.00,
                    'description'  => 'Faster and cheaper for high-volume runs.',
                ),
            ),
            'gemini' => array(
                array(
                    'id'           => 'gemini-2.5-flash-lite',
                    'label'        => 'Gemini 2.5 Flash-Lite',
                    'input_cost'   => 0.10,
                    'output_cost'  => 0.40,
                    'description'  => 'Lowest-cost Gemini option.',
                ),
                array(
                    'id'           => 'gemini-2.5-flash',
                    'label'        => 'Gemini 2.5 Flash',
                    'input_cost'   => 0.30,
                    'output_cost'  => 2.50,
                    'description'  => 'Balanced cost and quality.',
                ),
                array(
                    'id'           => 'gemini-2.5-pro',
                    'label'        => 'Gemini 2.5 Pro',
                    'input_cost'   => 1.25,
                    'output_cost'  => 10.00,
                    'description'  => 'Highest quality, highest cost.',
                ),
            ),
        );
    }

    /**
     * AJAX: estimate tokens and approximate cost for a post.
     */
    public function ajax_estimate_cost() {
        $this->assert_admin_request();

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $config  = $this->get_request_config();
        $context = $this->build_post_context( $post_id, $config );

        if ( is_wp_error( $context ) ) {
            wp_send_json_error( array( 'message' => $context->get_error_message() ), 400 );
        }

        $estimate = $this->estimate_usage( $context['prompt'], $config );

        wp_send_json_success( array(
            'postTitle'      => $context['post_title'],
            'sourceSummary'  => $context['source_summary'],
            'inputTokens'    => $estimate['input_tokens'],
            'outputTokens'   => $estimate['output_tokens'],
            'estimatedCost'  => $estimate['estimated_cost'],
            'estimatedRange' => $estimate['estimated_range'],
            'targetField'    => $this->get_target_label( $config ),
        ) );
    }

    /**
     * AJAX: validate provider credentials with a tiny request.
     */
    public function ajax_test_connection() {
        $this->assert_admin_request();

        $config = $this->get_request_config();
        $system = 'You are a brief connectivity checker.';
        $user   = 'Reply with OK only.';

        $response = $this->request_completion( $system, $user, $config, 12 );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ), 400 );
        }

        wp_send_json_success( array(
            'message' => sprintf(
                /* translators: 1: provider, 2: model */
                __( 'Connection successful using %1$s / %2$s.', 'smart-search-chips' ),
                $this->get_provider_label( $config['provider'] ),
                $config['model']
            ),
        ) );
    }

    /**
     * AJAX: preview generated tags without saving.
     */
    public function ajax_preview_tags() {
        $this->assert_admin_request();

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $config  = $this->get_request_config();
        $result  = $this->generate_tags_for_post( $post_id, $config, false, false );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
        }

        wp_send_json_success( $result );
    }

    /**
     * AJAX: generate and save tags to the configured target field.
     */
    public function ajax_generate_tags() {
        $this->assert_admin_request();

        $post_id    = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        $overwrite  = ! empty( $_POST['overwrite'] );
        $config     = $this->get_request_config();
        $result     = $this->generate_tags_for_post( $post_id, $config, true, $overwrite );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
        }

        wp_send_json_success( $result );
    }

    /**
     * Generate tags for a post and optionally save them.
     *
     * @param int   $post_id    Post ID.
     * @param array $config     Runtime config.
     * @param bool  $save       Whether to persist generated tags.
     * @param bool  $overwrite  Whether to overwrite existing target tags.
     * @return array|WP_Error
     */
    private function generate_tags_for_post( $post_id, $config, $save = false, $overwrite = false ) {
        $context = $this->build_post_context( $post_id, $config );
        if ( is_wp_error( $context ) ) {
            return $context;
        }

        $existing_value = get_post_meta( $post_id, $this->get_target_meta_key( $config ), true );
        if ( $save && ! $overwrite && ! empty( $existing_value ) ) {
            return new WP_Error(
                'ssc_ai_existing_tags',
                __( 'Target field already has tags. Enable overwrite to replace them.', 'smart-search-chips' )
            );
        }

        $response = $this->request_completion( $context['system_prompt'], $context['prompt'], $config, 220 );
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $tags = $this->extract_tags_from_response( $response, $config );
        if ( empty( $tags ) ) {
            return new WP_Error(
                'ssc_ai_empty_tags',
                __( 'The model returned no usable tags for this item.', 'smart-search-chips' )
            );
        }

        $target_key = $this->get_target_meta_key( $config );
        if ( $save ) {
            $this->save_generated_tags( $post_id, $target_key, $tags );
        }

        $estimate = $this->estimate_usage( $context['prompt'], $config );

        return array(
            'postId'         => $post_id,
            'postTitle'      => $context['post_title'],
            'tags'           => $tags,
            'targetField'    => $this->get_target_label( $config ),
            'targetMetaKey'  => $target_key,
            'saved'          => (bool) $save,
            'sourceSummary'  => $context['source_summary'],
            'inputTokens'    => $estimate['input_tokens'],
            'outputTokens'   => $estimate['output_tokens'],
            'estimatedCost'  => $estimate['estimated_cost'],
            'estimatedRange' => $estimate['estimated_range'],
        );
    }

    /**
     * Build prompt context from the selected post.
     *
     * @param int   $post_id Post ID.
     * @param array $config  Runtime config.
     * @return array|WP_Error
     */
    private function build_post_context( $post_id, $config ) {
        if ( empty( $post_id ) ) {
            return new WP_Error( 'ssc_ai_missing_post', __( 'Choose a post or product first.', 'smart-search-chips' ) );
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_Error( 'ssc_ai_invalid_post', __( 'The selected post could not be loaded.', 'smart-search-chips' ) );
        }

        $sections      = array();
        $source_labels = array();
        $source_fields = isset( $config['source_fields'] ) && is_array( $config['source_fields'] )
            ? $config['source_fields']
            : array();

        if ( in_array( 'title', $source_fields, true ) ) {
            $sections[]      = "Title:\n" . $post->post_title;
            $source_labels[] = __( 'title', 'smart-search-chips' );
        }

        if ( in_array( 'excerpt', $source_fields, true ) ) {
            $excerpt = has_excerpt( $post ) ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 45 );
            if ( ! empty( $excerpt ) ) {
                $sections[]      = "Excerpt:\n" . $excerpt;
                $source_labels[] = __( 'excerpt', 'smart-search-chips' );
            }
        }

        if ( in_array( 'content', $source_fields, true ) ) {
            $content = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
            $content = trim( preg_replace( '/\s+/', ' ', $content ) );
            if ( ! empty( $content ) ) {
                $sections[]      = "Content:\n" . $this->truncate_text( $content, 5000 );
                $source_labels[] = __( 'content', 'smart-search-chips' );
            }
        }

        if ( in_array( 'woo_attributes', $source_fields, true ) && class_exists( 'WooCommerce' ) && $post->post_type === 'product' ) {
            $woo_context = $this->get_woocommerce_context( $post_id );
            if ( ! empty( $woo_context ) ) {
                $sections[]      = "WooCommerce:\n" . $woo_context;
                $source_labels[] = __( 'WooCommerce attributes', 'smart-search-chips' );
            }
        }

        $custom_field_names = $this->parse_csv_option( $config['custom_read_fields'] );
        if ( ! empty( $custom_field_names ) ) {
            $custom_sections = array();
            foreach ( $custom_field_names as $field_name ) {
                $value = get_post_meta( $post_id, $field_name, true );
                if ( is_string( $value ) && $value !== '' ) {
                    $custom_sections[] = $field_name . ': ' . $this->truncate_text( wp_strip_all_tags( $value ), 1200 );
                }
            }

            if ( ! empty( $custom_sections ) ) {
                $sections[]      = "Custom fields:\n" . implode( "\n", $custom_sections );
                $source_labels[] = __( 'custom fields', 'smart-search-chips' );
            }
        }

        if ( empty( $sections ) ) {
            return new WP_Error( 'ssc_ai_empty_source', __( 'No source content is available with the current AI source settings.', 'smart-search-chips' ) );
        }

        $max_tags = max( 3, min( 20, absint( $config['max_tags'] ) ) );
        $language = sanitize_text_field( $config['language'] );
        if ( empty( $language ) ) {
            $language = get_bloginfo( 'language' );
        }

        $system_prompt = 'You generate concise search filter tags for WordPress content. '
            . 'Use only facts supported by the provided source material. '
            . 'Prefer short, high-signal tags such as product attributes, certifications, materials, compatibility terms, key topics, and concrete use cases. '
            . 'Do not invent tags, avoid duplicates, avoid marketing fluff, and avoid full sentences. '
            . 'Return only a valid JSON object like {"tags":["Tag 1","Tag 2"]}.';

        $custom_instruction = trim( $config['custom_instruction'] );
        if ( $custom_instruction !== '' ) {
            $system_prompt .= ' Additional site instruction: ' . $custom_instruction;
        }

        $prompt = sprintf(
            "Create up to %d tags in %s for this %s.\nKeep tags specific enough to be useful as clickable search filters.\n\n%s",
            $max_tags,
            $language,
            $post->post_type,
            implode( "\n\n", $sections )
        );

        return array(
            'post_title'    => get_the_title( $post ),
            'system_prompt' => $system_prompt,
            'prompt'        => $prompt,
            'source_summary'=> implode( ', ', $source_labels ),
        );
    }

    /**
     * Provider-agnostic request dispatcher.
     *
     * @param string $system      System instructions.
     * @param string $user        User prompt.
     * @param array  $config      Runtime config.
     * @param int    $max_tokens  Max output tokens.
     * @return string|WP_Error
     */
    private function request_completion( $system, $user, $config, $max_tokens ) {
        $provider = $config['provider'];
        $key      = $this->get_api_key_for_provider( $provider, $config );

        if ( empty( $key ) ) {
            return new WP_Error( 'ssc_ai_missing_key', __( 'Enter an API key for the selected provider.', 'smart-search-chips' ) );
        }

        if ( empty( $config['model'] ) ) {
            return new WP_Error( 'ssc_ai_missing_model', __( 'Choose a model for the selected provider.', 'smart-search-chips' ) );
        }

        if ( $provider === 'openai' ) {
            return $this->request_openai( $key, $config['model'], $system, $user, $max_tokens );
        }

        if ( $provider === 'anthropic' ) {
            return $this->request_anthropic( $key, $config['model'], $system, $user, $max_tokens );
        }

        if ( $provider === 'gemini' ) {
            return $this->request_gemini( $key, $config['model'], $system, $user, $max_tokens );
        }

        return new WP_Error( 'ssc_ai_bad_provider', __( 'Unsupported AI provider.', 'smart-search-chips' ) );
    }

    /**
     * OpenAI request.
     *
     * @return string|WP_Error
     */
    private function request_openai( $key, $model, $system, $user, $max_tokens ) {
        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            array(
                'timeout' => 45,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode( array(
                    'model'                 => $model,
                    'temperature'           => 0.2,
                    'max_completion_tokens' => $max_tokens,
                    'messages'              => array(
                        array(
                            'role'    => 'system',
                            'content' => $system,
                        ),
                        array(
                            'role'    => 'user',
                            'content' => $user,
                        ),
                    ),
                ) ),
            )
        );

        return $this->extract_remote_text(
            $response,
            array( 'choices', 0, 'message', 'content' ),
            __( 'OpenAI request failed.', 'smart-search-chips' )
        );
    }

    /**
     * Anthropic request.
     *
     * @return string|WP_Error
     */
    private function request_anthropic( $key, $model, $system, $user, $max_tokens ) {
        $response = wp_remote_post(
            'https://api.anthropic.com/v1/messages',
            array(
                'timeout' => 45,
                'headers' => array(
                    'x-api-key'         => $key,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ),
                'body'    => wp_json_encode( array(
                    'model'      => $model,
                    'max_tokens' => $max_tokens,
                    'system'     => $system,
                    'messages'   => array(
                        array(
                            'role'    => 'user',
                            'content' => $user,
                        ),
                    ),
                ) ),
            )
        );

        return $this->extract_remote_text(
            $response,
            array( 'content', 0, 'text' ),
            __( 'Anthropic request failed.', 'smart-search-chips' )
        );
    }

    /**
     * Gemini request.
     *
     * @return string|WP_Error
     */
    private function request_gemini( $key, $model, $system, $user, $max_tokens ) {
        $endpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            rawurlencode( $model ),
            rawurlencode( $key )
        );

        $response = wp_remote_post(
            $endpoint,
            array(
                'timeout' => 45,
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
                'body'    => wp_json_encode( array(
                    'contents' => array(
                        array(
                            'parts' => array(
                                array(
                                    'text' => $system . "\n\n" . $user,
                                ),
                            ),
                        ),
                    ),
                    'generationConfig' => array(
                        'temperature'     => 0.2,
                        'maxOutputTokens' => $max_tokens,
                    ),
                ) ),
            )
        );

        return $this->extract_remote_text(
            $response,
            array( 'candidates', 0, 'content', 'parts', 0, 'text' ),
            __( 'Gemini request failed.', 'smart-search-chips' )
        );
    }

    /**
     * Extract a nested response string from a remote request.
     *
     * @param array|WP_Error $response       HTTP response.
     * @param array          $path           Nested path to expected text.
     * @param string         $fallback_error Default error.
     * @return string|WP_Error
     */
    private function extract_remote_text( $response, $path, $fallback_error ) {
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $json = json_decode( $body, true );

        if ( $code < 200 || $code >= 300 ) {
            $message = $fallback_error;
            if ( isset( $json['error']['message'] ) ) {
                $message = $json['error']['message'];
            } elseif ( isset( $json['error']['type'] ) ) {
                $message = $json['error']['type'];
            } elseif ( isset( $json['error']['status'] ) ) {
                $message = $json['error']['status'];
            }

            return new WP_Error( 'ssc_ai_remote_error', sanitize_text_field( $message ) );
        }

        $value = $json;
        foreach ( $path as $segment ) {
            if ( ! isset( $value[ $segment ] ) ) {
                return new WP_Error( 'ssc_ai_bad_response', __( 'The AI provider returned an unexpected response format.', 'smart-search-chips' ) );
            }
            $value = $value[ $segment ];
        }

        if ( ! is_string( $value ) || trim( $value ) === '' ) {
            return new WP_Error( 'ssc_ai_empty_response', __( 'The AI provider returned an empty response.', 'smart-search-chips' ) );
        }

        return trim( $value );
    }

    /**
     * Parse model output into deduplicated tags.
     *
     * @param string $response Model response.
     * @param array  $config   Runtime config.
     * @return array
     */
    private function extract_tags_from_response( $response, $config ) {
        $max_tags = max( 3, min( 20, absint( $config['max_tags'] ) ) );
        $decoded  = null;
        $trimmed  = trim( $response );

        if ( strpos( $trimmed, '{' ) !== false ) {
            if ( preg_match( '/\{.*\}/s', $trimmed, $matches ) ) {
                $decoded = json_decode( $matches[0], true );
            }
        } else {
            $decoded = json_decode( $trimmed, true );
        }

        $tags = array();
        if ( is_array( $decoded ) && ! empty( $decoded['tags'] ) && is_array( $decoded['tags'] ) ) {
            $tags = $decoded['tags'];
        } else {
            $parts = preg_split( '/[\n,;|]+/', $trimmed );
            if ( is_array( $parts ) ) {
                $tags = $parts;
            }
        }

        $normalized = array();
        $seen       = array();

        foreach ( $tags as $tag ) {
            if ( ! is_string( $tag ) ) {
                continue;
            }

            $tag = wp_strip_all_tags( trim( $tag ) );
            $tag = trim( $tag, "\"' \t\n\r\0\x0B" );

            if ( $tag === '' || strlen( $tag ) > 60 ) {
                continue;
            }

            $key = mb_strtolower( $tag );
            if ( isset( $seen[ $key ] ) ) {
                continue;
            }

            $seen[ $key ] = true;
            $normalized[] = $tag;

            if ( count( $normalized ) >= $max_tags ) {
                break;
            }
        }

        return $normalized;
    }

    /**
     * Save tags to post meta and ACF when possible.
     *
     * @param int    $post_id    Post ID.
     * @param string $target_key Meta key.
     * @param array  $tags       Generated tags.
     */
    private function save_generated_tags( $post_id, $target_key, $tags ) {
        $value = implode( '; ', $tags );

        if ( function_exists( 'update_field' ) && $target_key !== SSC_Meta_Box::META_KEY ) {
            update_field( $target_key, $value, $post_id );
        }

        update_post_meta( $post_id, $target_key, $value );
    }

    /**
     * Estimate token usage and approximate request cost.
     *
     * @param string $prompt Prompt text.
     * @param array  $config Runtime config.
     * @return array
     */
    private function estimate_usage( $prompt, $config ) {
        $input_tokens  = max( 120, (int) ceil( mb_strlen( $prompt ) / 4 ) + 180 );
        $output_tokens = max( 40, min( 180, absint( $config['max_tags'] ) * 12 ) );
        $pricing       = $this->get_model_pricing( $config['provider'], $config['model'] );

        $estimated_cost = 0;
        if ( ! empty( $pricing ) ) {
            $estimated_cost = ( $input_tokens / 1000000 ) * $pricing['input_cost']
                + ( $output_tokens / 1000000 ) * $pricing['output_cost'];
        }

        return array(
            'input_tokens'   => $input_tokens,
            'output_tokens'  => $output_tokens,
            'estimated_cost' => $estimated_cost,
            'estimated_range'=> max( 0.0001, $estimated_cost * 1.35 ),
        );
    }

    /**
     * Parse and sanitize current runtime config from AJAX or saved settings.
     *
     * @return array
     */
    private function get_request_config() {
        $provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : $this->settings->get( 'ai_provider', 'openai' );
        $model    = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : $this->settings->get( 'ai_model', 'gpt-5-mini' );

        $source_fields = array();
        if ( isset( $_POST['source_fields'] ) && is_array( $_POST['source_fields'] ) ) {
            $source_fields = array_map( 'sanitize_key', wp_unslash( $_POST['source_fields'] ) );
        } else {
            $saved_source_fields = $this->settings->get( 'ai_source_fields', array( 'title', 'excerpt', 'content' ) );
            $source_fields       = is_array( $saved_source_fields ) ? $saved_source_fields : array( 'title', 'excerpt', 'content' );
        }

        return array(
            'provider'            => $provider,
            'model'               => $model,
            'openai_key'          => isset( $_POST['openai_key'] ) ? sanitize_text_field( wp_unslash( $_POST['openai_key'] ) ) : $this->settings->get( 'ai_openai_key', '' ),
            'anthropic_key'       => isset( $_POST['anthropic_key'] ) ? sanitize_text_field( wp_unslash( $_POST['anthropic_key'] ) ) : $this->settings->get( 'ai_anthropic_key', '' ),
            'gemini_key'          => isset( $_POST['gemini_key'] ) ? sanitize_text_field( wp_unslash( $_POST['gemini_key'] ) ) : $this->settings->get( 'ai_gemini_key', '' ),
            'source_fields'       => array_values( array_unique( array_filter( $source_fields ) ) ),
            'custom_read_fields'  => isset( $_POST['custom_read_fields'] ) ? sanitize_text_field( wp_unslash( $_POST['custom_read_fields'] ) ) : $this->settings->get( 'ai_custom_read_fields', '' ),
            'target_field'        => isset( $_POST['target_field'] ) ? sanitize_text_field( wp_unslash( $_POST['target_field'] ) ) : $this->settings->get( 'ai_target_field', 'built_in' ),
            'max_tags'            => isset( $_POST['max_tags'] ) ? absint( $_POST['max_tags'] ) : (int) $this->settings->get( 'ai_max_tags', 8 ),
            'language'            => isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : $this->settings->get( 'ai_language', get_bloginfo( 'language' ) ),
            'custom_instruction'  => isset( $_POST['custom_instruction'] ) ? sanitize_textarea_field( wp_unslash( $_POST['custom_instruction'] ) ) : $this->settings->get( 'ai_custom_instruction', '' ),
        );
    }

    /**
     * Ensure a privileged, nonce-protected Pro request.
     */
    private function assert_admin_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to use AI tagging.', 'smart-search-chips' ) ), 403 );
        }

        check_ajax_referer( self::AJAX_NONCE, 'nonce' );

        if ( ! SSC_License::is_pro() ) {
            wp_send_json_error( array( 'message' => __( 'AI tagging is available in Pro.', 'smart-search-chips' ) ), 403 );
        }
    }

    /**
     * Return a provider label.
     *
     * @param string $provider Provider key.
     * @return string
     */
    private function get_provider_label( $provider ) {
        $providers = self::get_providers();
        return isset( $providers[ $provider ] ) ? $providers[ $provider ] : $provider;
    }

    /**
     * Pick the correct key for the current provider.
     *
     * @param string $provider Provider key.
     * @param array  $config   Runtime config.
     * @return string
     */
    private function get_api_key_for_provider( $provider, $config ) {
        if ( $provider === 'anthropic' ) {
            return $config['anthropic_key'];
        }

        if ( $provider === 'gemini' ) {
            return $config['gemini_key'];
        }

        return $config['openai_key'];
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
     * Get a model pricing row for the selected provider/model.
     *
     * @param string $provider Provider key.
     * @param string $model    Model ID.
     * @return array
     */
    private function get_model_pricing( $provider, $model ) {
        $models = self::get_models();
        if ( empty( $models[ $provider ] ) ) {
            return array();
        }

        foreach ( $models[ $provider ] as $candidate ) {
            if ( $candidate['id'] === $model ) {
                return $candidate;
            }
        }

        return array();
    }

    /**
     * Create additional WooCommerce source context.
     *
     * @param int $post_id Product ID.
     * @return string
     */
    private function get_woocommerce_context( $post_id ) {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return '';
        }

        $product = wc_get_product( $post_id );
        if ( ! $product ) {
            return '';
        }

        $lines      = array();
        $attributes = $product->get_attributes();

        foreach ( $attributes as $attribute ) {
            if ( $attribute->is_taxonomy() ) {
                $terms = wp_get_post_terms( $post_id, $attribute->get_name(), array( 'fields' => 'names' ) );
                if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                    $lines[] = wc_attribute_label( $attribute->get_name() ) . ': ' . implode( ', ', $terms );
                }
            } else {
                $options = $attribute->get_options();
                if ( ! empty( $options ) ) {
                    $lines[] = $attribute->get_name() . ': ' . implode( ', ', $options );
                }
            }
        }

        $categories = wp_get_post_terms( $post_id, 'product_cat', array( 'fields' => 'names' ) );
        if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
            $lines[] = 'Categories: ' . implode( ', ', $categories );
        }

        $sku = $product->get_sku();
        if ( ! empty( $sku ) ) {
            $lines[] = 'SKU: ' . $sku;
        }

        return implode( "\n", $lines );
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

    /**
     * Truncate text for prompts.
     *
     * @param string $text       Source text.
     * @param int    $max_length Max characters.
     * @return string
     */
    private function truncate_text( $text, $max_length ) {
        if ( mb_strlen( $text ) <= $max_length ) {
            return $text;
        }

        return mb_substr( $text, 0, $max_length ) . '...';
    }
}
