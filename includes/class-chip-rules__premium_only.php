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

    /**
     * Register rule filters.
     */
    public function init() {
        add_filter( 'sschp_extracted_tags', array( $this, 'apply_synonyms_to_tag_data' ), 10, 3 );
        add_filter( 'sschp_active_tags', array( $this, 'normalize_active_tags' ) );
        add_filter( 'sschp_generated_chips', array( $this, 'apply_curation_rules' ), 10, 6 );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
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
