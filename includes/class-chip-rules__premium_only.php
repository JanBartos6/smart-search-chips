<?php
/**
 * Pro chip rules for synonyms and manual chip curation.
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCHP_Chip_Rules {

    /** @var string Admin-post action for exporting chip rules */
    const EXPORT_ACTION = 'sschp_export_chip_rules';

    /** @var string Admin-post action for importing chip rules */
    const IMPORT_ACTION = 'sschp_import_chip_rules';

    /** @var string Import result transient prefix */
    const IMPORT_RESULT_TRANSIENT_PREFIX = 'sschp_chip_rules_import_result_';

    /**
     * Register rule filters.
     */
    public function init() {
        add_filter( 'sschp_extracted_tags', array( $this, 'apply_synonyms_to_tag_data' ), 10, 3 );
        add_filter( 'sschp_active_tags', array( $this, 'normalize_active_tags' ) );
        add_filter( 'sschp_generated_chips', array( $this, 'apply_curation_rules' ), 10, 6 );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        add_action( 'admin_post_' . self::EXPORT_ACTION, array( $this, 'handle_export_chip_rules' ) );
        add_action( 'admin_post_' . self::IMPORT_ACTION, array( $this, 'handle_import_chip_rules' ) );
    }

    /**
     * Register authenticated REST routes for external automation.
     */
    public function register_rest_routes() {
        register_rest_route(
            'smart-search-chips/v1',
            '/chip-rules',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_rest_chip_rules' ),
                    'permission_callback' => array( $this, 'can_manage_rules' ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'update_rest_chip_rules' ),
                    'permission_callback' => array( $this, 'can_manage_rules' ),
                    'args'                => array(
                        'synonym_rules' => array(
                            'type'        => 'string',
                            'required'    => false,
                            'description' => __( 'Synonym rules, one rule per line: Canonical = alias, alias.', 'smart-search-chips' ),
                        ),
                        'pinned_chips' => array(
                            'type'        => 'string',
                            'required'    => false,
                            'description' => __( 'Pinned chips shown first when present in the current result set.', 'smart-search-chips' ),
                        ),
                        'hidden_chips' => array(
                            'type'        => 'string',
                            'required'    => false,
                            'description' => __( 'Chips hidden from suggestions unless they are currently active.', 'smart-search-chips' ),
                        ),
                        'renamed_chips' => array(
                            'type'        => 'string',
                            'required'    => false,
                            'description' => __( 'Display label overrides, one rule per line: source => display label.', 'smart-search-chips' ),
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * Restrict rule management to administrators.
     *
     * @return bool
     */
    public function can_manage_rules() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Return current chip rules over REST.
     *
     * @return WP_REST_Response
     */
    public function get_rest_chip_rules() {
        return rest_ensure_response( $this->get_rest_rules_payload() );
    }

    /**
     * Update chip rules over REST.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response
     */
    public function update_rest_chip_rules( $request ) {
        foreach ( $this->get_rest_option_map() as $field => $option ) {
            if ( ! $this->rest_request_has_param( $request, $field ) ) {
                continue;
            }

            update_option( $option, $this->sanitize_rules_text( $this->rest_request_get_param( $request, $field ) ) );
        }

        return rest_ensure_response( $this->get_rest_rules_payload() );
    }

    /**
     * Return the JSON export payload for Pro chip rules.
     *
     * @return array
     */
    public function export_rules_payload() {
        return array(
            'schema'         => 'smart-search-chips/chip-rules',
            'version'        => 1,
            'plugin_version' => defined( 'SSCHP_VERSION' ) ? SSCHP_VERSION : '',
            'exported_at'    => gmdate( 'c' ),
            'rules'          => $this->get_rest_rules_payload(),
        );
    }

    /**
     * Return chip rules as JSON.
     *
     * @return string
     */
    public function export_rules_json() {
        $json = wp_json_encode( $this->export_rules_payload() );

        return is_string( $json ) ? $json : '{}';
    }

    /**
     * Handle admin JSON export.
     *
     * @return void
     */
    public function handle_export_chip_rules() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to export chip rules.', 'smart-search-chips' ) );
        }

        check_admin_referer( self::EXPORT_ACTION );

        if ( ! headers_sent() ) {
            header( 'Content-Type: application/json; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=smart-search-chips-rules.json' );
            header( 'X-Content-Type-Options: nosniff' );
        }

        echo $this->export_rules_json(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON response body.
        exit;
    }

    /**
     * Handle admin JSON import or preview.
     *
     * @return void
     */
    public function handle_import_chip_rules() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to import chip rules.', 'smart-search-chips' ) );
        }

        check_admin_referer( self::IMPORT_ACTION );

        $raw_json = isset( $_POST['sschp_chip_rules_json'] ) ? wp_unslash( $_POST['sschp_chip_rules_json'] ) : '';
        $mode     = isset( $_POST['sschp_chip_rules_import_mode'] ) ? sanitize_key( wp_unslash( $_POST['sschp_chip_rules_import_mode'] ) ) : 'import';
        $dry_run  = 'preview' === $mode || ! empty( $_POST['sschp_chip_rules_dry_run'] );
        $result   = $this->import_rules_json( $raw_json, $dry_run );

        $this->store_import_result( $result );

        wp_safe_redirect( admin_url( 'options-general.php?page=smart-search-chips' ) );
        exit;
    }

    /**
     * Validate and optionally import chip rules from JSON.
     *
     * @param string $json    Raw JSON.
     * @param bool   $dry_run Whether to validate without saving.
     * @return array
     */
    public function import_rules_json( $json, $dry_run = false ) {
        $json = trim( (string) $json );
        if ( '' === $json ) {
            return $this->build_import_result(
                false,
                (bool) $dry_run,
                array(),
                array( __( 'No JSON was provided.', 'smart-search-chips' ) )
            );
        }

        $decoded = json_decode( $json, true );
        if ( JSON_ERROR_NONE !== json_last_error() ) {
            return $this->build_import_result(
                false,
                (bool) $dry_run,
                array(),
                array(
                    sprintf(
                        /* translators: %s: JSON parser error. */
                        __( 'Invalid JSON: %s', 'smart-search-chips' ),
                        function_exists( 'json_last_error_msg' ) ? json_last_error_msg() : __( 'Unable to parse the JSON.', 'smart-search-chips' )
                    ),
                )
            );
        }

        $validation = $this->validate_import_payload( $decoded );
        if ( ! $validation['valid'] ) {
            return $this->build_import_result( false, (bool) $dry_run, array(), $validation['errors'], $validation['warnings'] );
        }

        if ( ! $dry_run ) {
            $option_map = $this->get_rest_option_map();

            foreach ( $validation['rules'] as $field => $value ) {
                $option = $option_map[ $field ];
                update_option( $option, $value );
            }
        }

        return $this->build_import_result( true, (bool) $dry_run, $validation['rules'], array(), $validation['warnings'] );
    }

    /**
     * Validate a decoded import payload before anything is saved.
     *
     * @param mixed $payload Decoded JSON.
     * @return array
     */
    public function validate_import_payload( $payload ) {
        $errors = array();
        $warnings = array();
        $rules  = array();

        if ( ! is_array( $payload ) ) {
            return array(
                'valid'    => false,
                'rules'    => array(),
                'errors'   => array( __( 'The JSON must be an object containing a rules object.', 'smart-search-chips' ) ),
                'warnings' => array(),
            );
        }

        $source = isset( $payload['rules'] ) ? $payload['rules'] : $payload;
        if ( ! is_array( $source ) ) {
            return array(
                'valid'    => false,
                'rules'    => array(),
                'errors'   => array( __( 'The rules field must be an object.', 'smart-search-chips' ) ),
                'warnings' => array(),
            );
        }

        $known_fields = array_keys( $this->get_rest_option_map() );
        foreach ( array_keys( $source ) as $field ) {
            if ( ! in_array( $field, $known_fields, true ) ) {
                $warnings[] = sprintf(
                    /* translators: %s: ignored import field. */
                    __( 'Ignored unknown rule field: %s', 'smart-search-chips' ),
                    sanitize_text_field( (string) $field )
                );
            }
        }

        foreach ( $known_fields as $field ) {
            if ( ! array_key_exists( $field, $source ) ) {
                continue;
            }

            $normalized = $this->normalize_import_value( $source[ $field ], $field );
            if ( ! $normalized['valid'] ) {
                $errors[] = $normalized['message'];
                continue;
            }

            $rules[ $field ] = $normalized['value'];
        }

        if ( empty( $rules ) && empty( $errors ) ) {
            $errors[] = __( 'No supported chip rule fields were found. Expected synonym_rules, pinned_chips, hidden_chips, or renamed_chips.', 'smart-search-chips' );
        }

        return array(
            'valid'    => empty( $errors ),
            'rules'    => $rules,
            'errors'   => $errors,
            'warnings' => $warnings,
        );
    }

    /**
     * Normalize one import field.
     *
     * @param mixed  $value Import value.
     * @param string $field Field name.
     * @return array
     */
    private function normalize_import_value( $value, $field ) {
        if ( is_array( $value ) ) {
            $lines = array();
            foreach ( $value as $line ) {
                if ( is_array( $line ) || is_object( $line ) ) {
                    return array(
                        'valid'   => false,
                        'value'   => '',
                        'message' => sprintf(
                            /* translators: %s: field name. */
                            __( 'The %s field must contain text or a simple array of text lines.', 'smart-search-chips' ),
                            sanitize_key( $field )
                        ),
                    );
                }

                $lines[] = (string) $line;
            }
            $value = implode( "\n", $lines );
        } elseif ( is_bool( $value ) || is_object( $value ) || null === $value ) {
            return array(
                'valid'   => false,
                'value'   => '',
                'message' => sprintf(
                    /* translators: %s: field name. */
                    __( 'The %s field must be text.', 'smart-search-chips' ),
                    sanitize_key( $field )
                ),
            );
        }

        return array(
            'valid' => true,
            'value' => $this->sanitize_rules_text( $value ),
            'message' => '',
        );
    }

    /**
     * Build a consistent import response for tests and admin notices.
     *
     * @param bool  $valid    Whether import payload is valid.
     * @param bool  $dry_run  Whether this was a preview.
     * @param array $rules    Normalized rules.
     * @param array $errors   Errors.
     * @param array $warnings Warnings.
     * @return array
     */
    private function build_import_result( $valid, $dry_run, $rules, $errors = array(), $warnings = array() ) {
        return array(
            'valid'      => (bool) $valid,
            'dry_run'    => (bool) $dry_run,
            'saved'      => (bool) $valid && ! $dry_run,
            'rules'      => $rules,
            'summary'    => $this->summarize_rules( $rules ),
            'errors'     => array_values( array_filter( array_map( 'strval', (array) $errors ) ) ),
            'warnings'   => array_values( array_filter( array_map( 'strval', (array) $warnings ) ) ),
        );
    }

    /**
     * Count imported lines per rule field.
     *
     * @param array $rules Rules.
     * @return array
     */
    private function summarize_rules( $rules ) {
        $summary = array();
        foreach ( $this->get_rest_option_map() as $field => $option ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
            $summary[ $field ] = isset( $rules[ $field ] ) ? $this->count_rule_lines( $rules[ $field ] ) : 0;
        }

        return $summary;
    }

    /**
     * Count non-empty rule lines.
     *
     * @param string $value Rule text.
     * @return int
     */
    private function count_rule_lines( $value ) {
        $lines = preg_split( '/\r\n|\r|\n/', (string) $value );
        $lines = array_filter(
            array_map( 'trim', (array) $lines ),
            function( $line ) {
                return '' !== $line;
            }
        );

        return count( $lines );
    }

    /**
     * Store import result for the next settings page load.
     *
     * @param array $result Import result.
     * @return void
     */
    private function store_import_result( $result ) {
        if ( function_exists( 'set_transient' ) && function_exists( 'get_current_user_id' ) ) {
            set_transient( self::IMPORT_RESULT_TRANSIENT_PREFIX . get_current_user_id(), $result, MINUTE_IN_SECONDS );
        }
    }

    /**
     * Normalize extracted tags through synonym rules.
     *
     * @param array  $tag_data        Extracted tag data.
     * @param string $semicolon_field Primary field value.
     * @param string $comma_field     Extra field value.
     * @return array
     */
    public function apply_synonyms_to_tag_data( $tag_data, $semicolon_field, $comma_field ) {
        $synonyms = $this->get_synonym_map();

        if ( empty( $synonyms ) || empty( $tag_data['tags'] ) || ! is_array( $tag_data['tags'] ) ) {
            return $tag_data;
        }

        $tags = array();
        foreach ( $tag_data['tags'] as $tag ) {
            $canonical = $this->canonicalize_label( $tag, $synonyms );
            $key       = $this->label_key( $canonical );

            if ( '' !== $key && ! isset( $tags[ $key ] ) ) {
                $tags[ $key ] = $canonical;
            }
        }

        return array(
            'tags'       => array_values( $tags ),
            'tags_lower' => array_keys( $tags ),
        );
    }

    /**
     * Normalize requested active tags through synonym rules.
     *
     * @param array $active_tags Raw active tags.
     * @return array
     */
    public function normalize_active_tags( $active_tags ) {
        $synonyms = $this->get_synonym_map();
        $normalized = array();

        foreach ( (array) $active_tags as $tag ) {
            if ( is_string( $tag ) && 0 === strpos( $tag, 'sschp_range|' ) ) {
                $normalized[ $tag ] = $tag;
                continue;
            }

            $canonical = $this->canonicalize_label( $tag, $synonyms );
            $key       = $this->label_key( $canonical );

            if ( '' !== $key && ! isset( $normalized[ $key ] ) ) {
                $normalized[ $key ] = $canonical;
            }
        }

        return array_values( $normalized );
    }

    /**
     * Apply pinned, hidden, and renamed chip rules to generated chips.
     *
     * @param array $chips      Generated chips.
     * @param array $tag_counts Tag counts.
     * @param array $posts_data Posts data.
     * @param array $active_tags Active tags.
     * @param int   $max_chips Max chips.
     * @param int   $max_length Max label length.
     * @return array
     */
    public function apply_curation_rules( $chips, $tag_counts, $posts_data, $active_tags, $max_chips, $max_length ) {
        $synonyms    = $this->get_synonym_map();
        $active_keys = $this->labels_to_key_set( $active_tags, $synonyms );
        $hidden_keys = $this->labels_to_key_set( $this->get_list_option( 'sschp_hidden_chips' ), $synonyms );
        $rename_map  = $this->get_rename_map();

        $curated = array();

        foreach ( $this->get_list_option( 'sschp_pinned_chips' ) as $label ) {
            $canonical = $this->canonicalize_label( $label, $synonyms );
            $key       = $this->label_key( $canonical );

            if ( '' === $key || ! isset( $tag_counts[ $key ] ) || ( isset( $hidden_keys[ $key ] ) && ! isset( $active_keys[ $key ] ) ) ) {
                continue;
            }

            $curated[ $key ] = $this->format_chip( $tag_counts[ $key ], $rename_map );

            if ( count( $curated ) >= $max_chips ) {
                return $curated;
            }
        }

        foreach ( $chips as $key => $chip ) {
            if ( $this->is_virtual_range_chip( $chip ) ) {
                if ( ! isset( $curated[ $key ] ) ) {
                    $curated[ $key ] = $chip;
                }

                if ( count( $curated ) >= $max_chips ) {
                    break;
                }

                continue;
            }

            $value = isset( $chip['value'] ) ? $chip['value'] : $chip['label'];
            $key   = $this->label_key( $value );

            if ( '' === $key || ( isset( $hidden_keys[ $key ] ) && ! isset( $active_keys[ $key ] ) ) || isset( $curated[ $key ] ) ) {
                continue;
            }

            $curated[ $key ] = $this->format_chip( $chip, $rename_map );

            if ( count( $curated ) >= $max_chips ) {
                break;
            }
        }

        return $curated;
    }

    /**
     * Range chips use structured keys that must survive curation.
     *
     * @param array $chip Chip data.
     * @return bool
     */
    private function is_virtual_range_chip( $chip ) {
        return is_array( $chip ) && isset( $chip['type'] ) && 'range' === $chip['type'];
    }

    /**
     * Apply display naming while preserving the filter value.
     *
     * @param array $chip Chip data.
     * @param array $rename_map Rename rules.
     * @return array
     */
    private function format_chip( $chip, $rename_map ) {
        $value = isset( $chip['value'] ) ? $chip['value'] : $chip['label'];
        $key   = $this->label_key( $value );

        $chip['value'] = $value;

        if ( isset( $rename_map[ $key ] ) ) {
            $chip['label'] = $rename_map[ $key ];
        }

        return $chip;
    }

    /**
     * Get synonym aliases mapped to canonical labels.
     *
     * @return array
     */
    private function get_synonym_map() {
        $rules = (string) get_option( 'sschp_synonym_rules', '' );
        $map   = array();

        foreach ( preg_split( '/\r\n|\r|\n/', $rules ) as $line ) {
            $line = trim( $line );

            if ( '' === $line ) {
                continue;
            }

            $parts = preg_split( '/\s*(?:=>|=|:)\s*/', $line, 2 );
            if ( count( $parts ) < 2 ) {
                continue;
            }

            $canonical = sanitize_text_field( trim( $parts[0] ) );
            if ( '' === $canonical ) {
                continue;
            }

            $aliases = $this->split_list( $parts[1] );
            $aliases[] = $canonical;

            foreach ( $aliases as $alias ) {
                $key = $this->label_key( $alias );
                if ( '' !== $key ) {
                    $map[ $key ] = $canonical;
                }
            }
        }

        return $map;
    }

    /**
     * Get label rename rules keyed by canonical chip value.
     *
     * @return array
     */
    private function get_rename_map() {
        $rules    = (string) get_option( 'sschp_renamed_chips', '' );
        $synonyms = $this->get_synonym_map();
        $map      = array();

        foreach ( preg_split( '/\r\n|\r|\n/', $rules ) as $line ) {
            $line = trim( $line );

            if ( '' === $line ) {
                continue;
            }

            $parts = preg_split( '/\s*(?:=>|=|:)\s*/', $line, 2 );
            if ( count( $parts ) < 2 ) {
                continue;
            }

            $source = $this->canonicalize_label( $parts[0], $synonyms );
            $target = sanitize_text_field( trim( $parts[1] ) );
            $key    = $this->label_key( $source );

            if ( '' !== $key && '' !== $target ) {
                $map[ $key ] = $target;
            }
        }

        return $map;
    }

    /**
     * Read a comma/semicolon/newline separated option.
     *
     * @param string $option Option key.
     * @return array
     */
    private function get_list_option( $option ) {
        return $this->split_list( (string) get_option( $option, '' ) );
    }

    /**
     * Return REST field names mapped to stored options.
     *
     * @return array
     */
    private function get_rest_option_map() {
        return array(
            'synonym_rules' => 'sschp_synonym_rules',
            'pinned_chips'  => 'sschp_pinned_chips',
            'hidden_chips'  => 'sschp_hidden_chips',
            'renamed_chips' => 'sschp_renamed_chips',
        );
    }

    /**
     * Build the REST response payload.
     *
     * @return array
     */
    private function get_rest_rules_payload() {
        $payload = array();

        foreach ( $this->get_rest_option_map() as $field => $option ) {
            $payload[ $field ] = (string) get_option( $option, '' );
        }

        return $payload;
    }

    /**
     * Check if a REST request contains a field.
     *
     * @param WP_REST_Request $request REST request.
     * @param string          $field   Field name.
     * @return bool
     */
    private function rest_request_has_param( $request, $field ) {
        if ( is_object( $request ) && method_exists( $request, 'has_param' ) ) {
            return $request->has_param( $field );
        }

        if ( is_array( $request ) ) {
            return array_key_exists( $field, $request );
        }

        return false;
    }

    /**
     * Read a REST request field.
     *
     * @param WP_REST_Request $request REST request.
     * @param string          $field   Field name.
     * @return mixed
     */
    private function rest_request_get_param( $request, $field ) {
        if ( is_object( $request ) && method_exists( $request, 'get_param' ) ) {
            return $request->get_param( $field );
        }

        if ( is_array( $request ) && array_key_exists( $field, $request ) ) {
            return $request[ $field ];
        }

        return '';
    }

    /**
     * Sanitize multi-line REST rule text.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private function sanitize_rules_text( $value ) {
        $value = sanitize_textarea_field( (string) $value );
        $lines = array();

        foreach ( preg_split( '/\r\n|\r|\n/', $value ) as $line ) {
            $line = trim( $line );
            if ( '' !== $line ) {
                $lines[] = $line;
            }
        }

        return implode( "\n", $lines );
    }

    /**
     * Split list-style values.
     *
     * @param string $value Raw list.
     * @return array
     */
    private function split_list( $value ) {
        $items = array();

        foreach ( preg_split( '/[;,\r\n]+/', (string) $value ) as $item ) {
            $item = sanitize_text_field( trim( $item ) );
            if ( '' !== $item ) {
                $items[] = $item;
            }
        }

        return array_values( array_unique( $items ) );
    }

    /**
     * Convert labels to lookup keys.
     *
     * @param array $labels Labels.
     * @return array
     */
    private function labels_to_key_set( $labels, $synonyms = array() ) {
        $keys = array();

        foreach ( (array) $labels as $label ) {
            $key = $this->label_key( $this->canonicalize_label( $label, $synonyms ) );
            if ( '' !== $key ) {
                $keys[ $key ] = true;
            }
        }

        return $keys;
    }

    /**
     * Convert an alias to its canonical label.
     *
     * @param string $label Label.
     * @param array  $synonyms Synonym map.
     * @return string
     */
    private function canonicalize_label( $label, $synonyms ) {
        $label = sanitize_text_field( trim( (string) $label ) );
        $key   = $this->label_key( $label );

        return isset( $synonyms[ $key ] ) ? $synonyms[ $key ] : $label;
    }

    /**
     * Return a stable lookup key for a label.
     *
     * @param string $label Label.
     * @return string
     */
    private function label_key( $label ) {
        return mb_strtolower( trim( sanitize_text_field( (string) $label ) ) );
    }
}
