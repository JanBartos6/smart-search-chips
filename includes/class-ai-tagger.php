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
        add_action( 'wp_ajax_ssc_ai_list_models', array( $this, 'ajax_list_models' ) );
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
                    'id'           => 'gpt-5.4-mini',
                    'label'        => 'GPT-5.4 mini',
                    'input_cost'   => 0.75,
                    'output_cost'  => 4.50,
                    'description'  => 'Current OpenAI mini recommendation.',
                ),
                array(
                    'id'           => 'gpt-5.4-nano',
                    'label'        => 'GPT-5.4 nano',
                    'input_cost'   => 0.20,
                    'output_cost'  => 1.25,
                    'description'  => 'Lowest-cost current OpenAI fallback.',
                ),
                array(
                    'id'           => 'gpt-4.1-mini',
                    'label'        => 'GPT-4.1 mini',
                    'input_cost'   => 0.40,
                    'output_cost'  => 1.60,
                    'description'  => 'Fallback for broader compatibility.',
                ),
            ),
            'anthropic' => array(
                array(
                    'id'           => 'claude-sonnet-4-5',
                    'label'        => 'Claude Sonnet 4.5',
                    'input_cost'   => 3.00,
                    'output_cost'  => 15.00,
                    'description'  => 'Current Anthropic recommended balance.',
                ),
                array(
                    'id'           => 'claude-haiku-4-5',
                    'label'        => 'Claude Haiku 4.5',
                    'input_cost'   => 1.00,
                    'output_cost'  => 5.00,
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
     * Supported secret references per provider.
     *
     * @return array
     */
    public static function get_secret_references() {
        return array(
            'openai' => array(
                'constants' => array( 'SSC_OPENAI_API_KEY', 'OPENAI_API_KEY' ),
                'env'       => array( 'SSC_OPENAI_API_KEY', 'OPENAI_API_KEY' ),
            ),
            'anthropic' => array(
                'constants' => array( 'SSC_ANTHROPIC_API_KEY', 'ANTHROPIC_API_KEY' ),
                'env'       => array( 'SSC_ANTHROPIC_API_KEY', 'ANTHROPIC_API_KEY' ),
            ),
            'gemini' => array(
                'constants' => array( 'SSC_GEMINI_API_KEY', 'GEMINI_API_KEY', 'GOOGLE_API_KEY' ),
                'env'       => array( 'SSC_GEMINI_API_KEY', 'GEMINI_API_KEY', 'GOOGLE_API_KEY' ),
            ),
        );
    }

    /**
     * Return current secret status for a provider.
     *
     * @param string $provider Provider key.
     * @return array
     */
    public static function get_secret_status( $provider ) {
        $references = self::get_secret_references();
        $refs       = isset( $references[ $provider ] ) ? $references[ $provider ] : array( 'constants' => array(), 'env' => array() );

        foreach ( $refs['constants'] as $name ) {
            if ( defined( $name ) && constant( $name ) !== '' ) {
                return array(
                    'configured' => true,
                    'source'     => 'constant',
                    'name'       => $name,
                );
            }
        }

        foreach ( $refs['env'] as $name ) {
            $value = getenv( $name );
            if ( is_string( $value ) && $value !== '' ) {
                return array(
                    'configured' => true,
                    'source'     => 'env',
                    'name'       => $name,
                );
            }
        }

        return array(
            'configured' => false,
            'source'     => '',
            'name'       => '',
        );
    }

    /**
     * AJAX: list current models from the selected provider, with fallback.
     */
    public function ajax_list_models() {
        $this->assert_admin_request();

        $config = $this->get_request_config();
        $models = $this->list_provider_models( $config['provider'], $config );

        if ( is_wp_error( $models ) || empty( $models ) ) {
            $fallback = self::get_models();
            $models   = isset( $fallback[ $config['provider'] ] ) ? $fallback[ $config['provider'] ] : array();

            if ( empty( $models ) ) {
                wp_send_json_error( array(
                    'message' => is_wp_error( $models ) ? $models->get_error_message() : __( 'No models available.', 'smart-search-chips' ),
                ), 400 );
            }

            wp_send_json_success( array(
                'models'   => array_values( $models ),
                'fallback' => true,
            ) );
        }

        wp_send_json_success( array(
            'models'   => array_values( $models ),
            'fallback' => false,
        ) );
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
            'providerLabel'  => $this->get_provider_label( $config['provider'] ),
            'model'          => $config['model'],
            'requestSummary' => $this->build_request_summary( $config, $context ),
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
            'providerLabel'  => $this->get_provider_label( $config['provider'] ),
            'model'          => $config['model'],
            'requestSummary' => $this->build_request_summary( $config, $context ),
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
            'post_title'        => get_the_title( $post ),
            'post_type'         => $post->post_type,
            'system_prompt'     => $system_prompt,
            'prompt'            => $prompt,
            'source_summary'    => implode( ', ', $source_labels ),
            'source_labels'     => $source_labels,
            'custom_instruction'=> $custom_instruction,
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
            return new WP_Error( 'ssc_ai_missing_key', $this->get_missing_secret_message( $provider ) );
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
     * Fetch available models from a provider's models endpoint.
     *
     * @param string $provider Provider key.
     * @param array  $config   Runtime config.
     * @return array|WP_Error
     */
    private function list_provider_models( $provider, $config ) {
        $key = $this->get_api_key_for_provider( $provider, $config );

        if ( empty( $key ) ) {
            return new WP_Error( 'ssc_ai_missing_key', $this->get_missing_secret_message( $provider ) );
        }

        if ( $provider === 'openai' ) {
            return $this->list_openai_models( $key );
        }

        if ( $provider === 'anthropic' ) {
            return $this->list_anthropic_models( $key );
        }

        if ( $provider === 'gemini' ) {
            return $this->list_gemini_models( $key );
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
     * OpenAI models endpoint.
     *
     * @param string $key API key.
     * @return array|WP_Error
     */
    private function list_openai_models( $key ) {
        $response = wp_remote_get(
            'https://api.openai.com/v1/models',
            array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $key,
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $json = json_decode( wp_remote_retrieve_body( $response ), true );
        $code = wp_remote_retrieve_response_code( $response );

        if ( $code < 200 || $code >= 300 ) {
            return new WP_Error(
                'ssc_ai_openai_models_error',
                $this->extract_error_message( $json, __( 'Unable to load OpenAI models.', 'smart-search-chips' ) )
            );
        }

        if ( empty( $json['data'] ) || ! is_array( $json['data'] ) ) {
            return new WP_Error( 'ssc_ai_openai_models_empty', __( 'OpenAI returned no models.', 'smart-search-chips' ) );
        }

        $models = array();

        foreach ( $json['data'] as $item ) {
            if ( empty( $item['id'] ) || ! is_string( $item['id'] ) ) {
                continue;
            }

            $id = $item['id'];
            if ( ! preg_match( '/^(gpt-|o[1-9])/', $id ) ) {
                continue;
            }

            if ( preg_match( '/(audio|image|realtime|transcribe|tts|embedding|moderation|search|codex|chatgpt|instruct)/', $id ) ) {
                continue;
            }

            $models[] = array(
                'id'          => $id,
                'label'       => $id,
                'description' => isset( $item['owned_by'] ) ? sprintf( __( 'Owner: %s', 'smart-search-chips' ), $item['owned_by'] ) : '',
                'created'     => isset( $item['created'] ) ? (int) $item['created'] : 0,
            );
        }

        usort( $models, function( $left, $right ) {
            return (int) $right['created'] - (int) $left['created'];
        } );

        return array_slice( $models, 0, 30 );
    }

    /**
     * Anthropic models endpoint.
     *
     * @param string $key API key.
     * @return array|WP_Error
     */
    private function list_anthropic_models( $key ) {
        $response = wp_remote_get(
            'https://api.anthropic.com/v1/models',
            array(
                'timeout' => 30,
                'headers' => array(
                    'x-api-key'         => $key,
                    'anthropic-version' => '2023-06-01',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $json = json_decode( wp_remote_retrieve_body( $response ), true );
        $code = wp_remote_retrieve_response_code( $response );

        if ( $code < 200 || $code >= 300 ) {
            return new WP_Error(
                'ssc_ai_anthropic_models_error',
                $this->extract_error_message( $json, __( 'Unable to load Anthropic models.', 'smart-search-chips' ) )
            );
        }

        if ( empty( $json['data'] ) || ! is_array( $json['data'] ) ) {
            return new WP_Error( 'ssc_ai_anthropic_models_empty', __( 'Anthropic returned no models.', 'smart-search-chips' ) );
        }

        $models = array();

        foreach ( $json['data'] as $item ) {
            if ( empty( $item['id'] ) || ! is_string( $item['id'] ) ) {
                continue;
            }

            $id = $item['id'];
            if ( strpos( $id, 'claude-' ) !== 0 ) {
                continue;
            }

            $models[] = array(
                'id'          => $id,
                'label'       => ! empty( $item['display_name'] ) ? $item['display_name'] : $id,
                'description' => ! empty( $item['created_at'] ) ? $item['created_at'] : '',
                'created'     => ! empty( $item['created_at'] ) ? strtotime( $item['created_at'] ) : 0,
            );
        }

        usort( $models, function( $left, $right ) {
            return (int) $right['created'] - (int) $left['created'];
        } );

        return array_slice( $models, 0, 30 );
    }

    /**
     * Gemini models endpoint.
     *
     * @param string $key API key.
     * @return array|WP_Error
     */
    private function list_gemini_models( $key ) {
        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . rawurlencode( $key ) . '&pageSize=1000';
        $response = wp_remote_get(
            $endpoint,
            array(
                'timeout' => 30,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $json = json_decode( wp_remote_retrieve_body( $response ), true );
        $code = wp_remote_retrieve_response_code( $response );

        if ( $code < 200 || $code >= 300 ) {
            return new WP_Error(
                'ssc_ai_gemini_models_error',
                $this->extract_error_message( $json, __( 'Unable to load Gemini models.', 'smart-search-chips' ) )
            );
        }

        if ( empty( $json['models'] ) || ! is_array( $json['models'] ) ) {
            return new WP_Error( 'ssc_ai_gemini_models_empty', __( 'Gemini returned no models.', 'smart-search-chips' ) );
        }

        $models = array();

        foreach ( $json['models'] as $item ) {
            if ( empty( $item['name'] ) || ! is_string( $item['name'] ) ) {
                continue;
            }

            $id = str_replace( 'models/', '', $item['name'] );
            if ( strpos( $id, 'gemini-' ) !== 0 ) {
                continue;
            }

            if ( preg_match( '/(image|tts|live|embedding|veo|nano-banana|aqa|learnlm)/', $id ) ) {
                continue;
            }

            if ( ! empty( $item['supportedGenerationMethods'] ) && is_array( $item['supportedGenerationMethods'] ) && ! in_array( 'generateContent', $item['supportedGenerationMethods'], true ) ) {
                continue;
            }

            $models[] = array(
                'id'          => $id,
                'label'       => ! empty( $item['displayName'] ) ? $item['displayName'] : $id,
                'description' => ! empty( $item['description'] ) ? $item['description'] : '',
                'created'     => 0,
            );
        }

        usort( $models, function( $left, $right ) {
            return strcmp( $left['id'], $right['id'] );
        } );

        return array_slice( $models, 0, 30 );
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
     * Extract a reasonable provider error message.
     *
     * @param array|null $json     Decoded response.
     * @param string     $fallback Fallback message.
     * @return string
     */
    private function extract_error_message( $json, $fallback ) {
        if ( ! is_array( $json ) ) {
            return $fallback;
        }

        if ( ! empty( $json['error']['message'] ) && is_string( $json['error']['message'] ) ) {
            return sanitize_text_field( $json['error']['message'] );
        }

        if ( ! empty( $json['error']['status'] ) && is_string( $json['error']['status'] ) ) {
            return sanitize_text_field( $json['error']['status'] );
        }

        if ( ! empty( $json['error']['details'] ) && is_string( $json['error']['details'] ) ) {
            return sanitize_text_field( $json['error']['details'] );
        }

        if ( ! empty( $json['message'] ) && is_string( $json['message'] ) ) {
            return sanitize_text_field( $json['message'] );
        }

        return $fallback;
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
     * Build a user-facing summary of what the request will do.
     *
     * @param array $config  Runtime config.
     * @param array $context Prompt context.
     * @return array
     */
    private function build_request_summary( $config, $context ) {
        return array(
            'provider'            => $config['provider'],
            'provider_label'      => $this->get_provider_label( $config['provider'] ),
            'model'               => $config['model'],
            'post_type'           => $context['post_type'],
            'source_labels'       => isset( $context['source_labels'] ) ? $context['source_labels'] : array(),
            'target_field_label'  => $this->get_target_label( $config ),
            'max_tags'            => max( 3, min( 20, absint( $config['max_tags'] ) ) ),
            'language'            => sanitize_text_field( $config['language'] ? $config['language'] : get_bloginfo( 'language' ) ),
            'has_custom_instruction' => ! empty( $context['custom_instruction'] ),
            'custom_read_fields'  => $this->parse_csv_option( $config['custom_read_fields'] ),
            'data_notice'         => sprintf(
                /* translators: %s: provider name */
                __( 'Selected content will be sent from this WordPress site directly to %s for tag generation.', 'smart-search-chips' ),
                $this->get_provider_label( $config['provider'] )
            ),
            'cost_notice'         => __( 'The site owner is responsible for provider-side budgets, usage caps, and charges.', 'smart-search-chips' ),
        );
    }

    /**
     * Parse and sanitize current runtime config from AJAX or saved settings.
     *
     * @return array
     */
    private function get_request_config() {
        $provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : $this->settings->get( 'ai_provider', 'openai' );
        $model    = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : $this->settings->get( 'ai_model', 'gpt-5.4-mini' );

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
     * Build a setup message for missing provider secrets.
     *
     * @param string $provider Provider key.
     * @return string
     */
    private function get_missing_secret_message( $provider ) {
        $references = self::get_secret_references();
        $refs       = isset( $references[ $provider ] ) ? $references[ $provider ] : array( 'constants' => array(), 'env' => array() );
        $constant   = ! empty( $refs['constants'][0] ) ? $refs['constants'][0] : '';
        $env        = ! empty( $refs['env'][0] ) ? $refs['env'][0] : '';

        return sprintf(
            /* translators: 1: provider label, 2: wp-config constant, 3: environment variable */
            __( '%1$s is not configured. Add a key in wp-config.php via %2$s or expose %3$s as an environment variable.', 'smart-search-chips' ),
            $this->get_provider_label( $provider ),
            $constant,
            $env
        );
    }

    /**
     * Pick the correct key for the current provider.
     *
     * @param string $provider Provider key.
     * @param array  $config   Runtime config.
     * @return string
     */
    private function get_api_key_for_provider( $provider, $config ) {
        unset( $config );

        $status = self::get_secret_status( $provider );
        if ( ! empty( $status['configured'] ) ) {
            if ( $status['source'] === 'constant' && defined( $status['name'] ) ) {
                return (string) constant( $status['name'] );
            }

            if ( $status['source'] === 'env' ) {
                $value = getenv( $status['name'] );
                return is_string( $value ) ? $value : '';
            }
        }

        return '';
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
