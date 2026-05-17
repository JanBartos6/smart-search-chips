<?php
/**
 * Relevance scoring for fallback search results.
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCHP_Relevance_Scorer {

    /**
     * Score one post against a search query.
     *
     * @param WP_Post $post      Post object.
     * @param string  $query     Raw search query.
     * @param array   $chip_tags Chip labels available for this post.
     * @return float
     */
    public function score_post( $post, $query, $chip_tags = array() ) {
        $query      = (string) $query;
        $normalized = $this->normalize_text( $query );
        $tokens     = $this->tokenize( $query );

        if ( '' === $normalized || empty( $tokens ) || ! is_object( $post ) ) {
            return 0.0;
        }

        $context = $this->build_post_context( $post, $chip_tags );
        $weights = apply_filters(
            'sschp_result_score_weights',
            $this->get_default_weights(),
            $query,
            $tokens,
            $post,
            $context
        );
        $weights = is_array( $weights ) ? array_merge( $this->get_default_weights(), $weights ) : $this->get_default_weights();

        $score          = 0.0;
        $strong_matches = 0;
        $weak_partials  = 0;

        $title = $this->normalize_text( $context['title'] );
        if ( '' !== $title && $title === $normalized ) {
            $score += $weights['exact_title'];
            $strong_matches++;
        }

        if ( '' !== $title && false !== strpos( $title, $normalized ) ) {
            $score += $weights['phrase_title'];
            $strong_matches++;
        }

        if ( $this->all_tokens_match_text( $tokens, $title ) ) {
            $score += $weights['all_title_tokens'];
            $strong_matches++;
        }

        $score += $this->score_token_field( $title, $tokens, $weights['title_token'], $weights['weak_partial'], $strong_matches, $weak_partials );

        $sku = $this->normalize_text( $context['sku'] );
        if ( '' !== $sku ) {
            $compact_sku   = $this->compact_text( $sku );
            $compact_query = $this->compact_text( $normalized );

            if ( '' !== $compact_query && $compact_sku === $compact_query ) {
                $score += $weights['sku_exact'];
                $strong_matches++;
            } elseif ( false !== strpos( $compact_sku, $compact_query ) || false !== strpos( $compact_query, $compact_sku ) ) {
                $score += $weights['sku_partial'];
                $strong_matches++;
            }

            $score += $this->score_token_field( $sku, $tokens, $weights['sku_token'], $weights['weak_partial'], $strong_matches, $weak_partials );
        }

        $score += $this->score_weighted_text_list( $context['taxonomy'], $normalized, $tokens, $weights['phrase_taxonomy'], $weights['taxonomy_token'], $weights['weak_partial'], $strong_matches, $weak_partials );
        $score += $this->score_weighted_text_list( $context['attributes'], $normalized, $tokens, $weights['phrase_attribute'], $weights['attribute_token'], $weights['weak_partial'], $strong_matches, $weak_partials );
        $score += $this->score_weighted_text_list( $context['chips'], $normalized, $tokens, $weights['phrase_chip'], $weights['chip_token'], $weights['weak_partial'], $strong_matches, $weak_partials );
        $score += $this->score_weighted_text_list( array( $context['excerpt'] ), $normalized, $tokens, $weights['phrase_excerpt'], $weights['excerpt_token'], $weights['weak_partial'], $strong_matches, $weak_partials );
        $score += $this->score_weighted_text_list( array( $context['content'] ), $normalized, $tokens, $weights['phrase_content'], $weights['content_token'], $weights['weak_partial'], $strong_matches, $weak_partials );

        if ( 'product' === $context['post_type'] && $score > 0 ) {
            $score += $weights['product_bonus'];
        }

        if ( $score > 0 && 0 === $strong_matches && $weak_partials > 0 ) {
            $score *= $weights['weak_partial_penalty'];
        }

        return (float) apply_filters( 'sschp_result_score', $score, $post, $context, $query, $tokens, $weights );
    }

    /**
     * Normalize text for matching without changing stored/displayed text.
     *
     * @param mixed $text Text value.
     * @return string
     */
    public function normalize_text( $text ) {
        $text = html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' );

        if ( function_exists( 'wp_strip_all_tags' ) ) {
            $text = wp_strip_all_tags( $text );
        } else {
            $text = strip_tags( $text );
        }

        if ( function_exists( 'remove_accents' ) ) {
            $text = remove_accents( $text );
        }

        $text = mb_strtolower( $text, 'UTF-8' );
        $text = preg_replace( '/[^\p{L}\p{N}_\-]+/u', ' ', $text );
        $text = preg_replace( '/\s+/u', ' ', trim( (string) $text ) );

        return is_string( $text ) ? $text : '';
    }

    /**
     * Tokenize a query using language-neutral Unicode boundaries.
     *
     * @param string $query Raw search query.
     * @return array
     */
    public function tokenize( $query ) {
        $normalized = $this->normalize_text( $query );
        if ( '' === $normalized ) {
            return array();
        }

        $tokens = preg_split( '/\s+/u', $normalized );
        if ( ! is_array( $tokens ) ) {
            $tokens = array();
        }

        $tokens = array_values(
            array_unique(
                array_filter(
                    array_map( 'trim', $tokens ),
                    function( $token ) {
                        return '' !== $token;
                    }
                )
            )
        );

        return apply_filters( 'sschp_search_tokens', $tokens, $query, $normalized );
    }

    /**
     * Build text context for scoring.
     *
     * @param WP_Post $post      Post object.
     * @param array   $chip_tags Chip labels.
     * @return array
     */
    private function build_post_context( $post, $chip_tags ) {
        $post_id   = isset( $post->ID ) ? absint( $post->ID ) : 0;
        $post_type = isset( $post->post_type ) ? sanitize_key( $post->post_type ) : get_post_type( $post_id );
        $context   = array(
            'post_id'    => $post_id,
            'post_type'  => $post_type,
            'title'      => isset( $post->post_title ) ? (string) $post->post_title : '',
            'excerpt'    => isset( $post->post_excerpt ) ? (string) $post->post_excerpt : '',
            'content'    => isset( $post->post_content ) ? (string) $post->post_content : '',
            'taxonomy'   => $this->get_taxonomy_values( $post_id, $post_type ),
            'attributes' => array(),
            'chips'      => $this->normalize_value_list( $chip_tags ),
            'sku'        => '',
        );

        if ( 'product' === $post_type && function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $post_id );
            if ( $product ) {
                $context['sku']        = method_exists( $product, 'get_sku' ) ? (string) $product->get_sku() : '';
                $context['attributes'] = $this->get_product_attribute_values( $post_id, $product );

                if ( method_exists( $product, 'get_short_description' ) ) {
                    $context['excerpt'] .= ' ' . (string) $product->get_short_description();
                }
            }
        }

        return apply_filters( 'sschp_result_score_context', $context, $post, $chip_tags );
    }

    /**
     * Score a list of text values.
     *
     * @param array  $values         Text values.
     * @param string $normalized     Normalized query.
     * @param array  $tokens         Query tokens.
     * @param float  $phrase_weight  Phrase score.
     * @param float  $token_weight   Token score.
     * @param float  $partial_weight Weak partial score.
     * @param int    $strong_matches Strong match counter.
     * @param int    $weak_partials  Weak partial counter.
     * @return float
     */
    private function score_weighted_text_list( $values, $normalized, $tokens, $phrase_weight, $token_weight, $partial_weight, &$strong_matches, &$weak_partials ) {
        $score = 0.0;

        foreach ( (array) $values as $value ) {
            $text = $this->normalize_text( $value );
            if ( '' === $text ) {
                continue;
            }

            if ( false !== strpos( $text, $normalized ) ) {
                $score += $phrase_weight;
                $strong_matches++;
            }

            $score += $this->score_token_field( $text, $tokens, $token_weight, $partial_weight, $strong_matches, $weak_partials );
        }

        return $score;
    }

    /**
     * Score token matches within a normalized field.
     *
     * @param string $text           Normalized field text.
     * @param array  $tokens         Query tokens.
     * @param float  $token_weight   Full token score.
     * @param float  $partial_weight Weak partial score.
     * @param int    $strong_matches Strong match counter.
     * @param int    $weak_partials  Weak partial counter.
     * @return float
     */
    private function score_token_field( $text, $tokens, $token_weight, $partial_weight, &$strong_matches, &$weak_partials ) {
        if ( '' === $text ) {
            return 0.0;
        }

        $field_tokens = preg_split( '/\s+/u', $text );
        $field_tokens = is_array( $field_tokens ) ? $field_tokens : array();
        $score        = 0.0;

        foreach ( $tokens as $token ) {
            if ( in_array( $token, $field_tokens, true ) ) {
                $score += $token_weight;
                $strong_matches++;
                continue;
            }

            if ( mb_strlen( $token, 'UTF-8' ) >= 4 && false !== strpos( $text, $token ) ) {
                $score += $partial_weight;
                $weak_partials++;
            }
        }

        return $score;
    }

    /**
     * Check if all query tokens appear in text.
     *
     * @param array  $tokens Query tokens.
     * @param string $text   Normalized text.
     * @return bool
     */
    private function all_tokens_match_text( $tokens, $text ) {
        if ( '' === $text || empty( $tokens ) ) {
            return false;
        }

        $field_tokens = preg_split( '/\s+/u', $text );
        $field_tokens = is_array( $field_tokens ) ? $field_tokens : array();

        foreach ( $tokens as $token ) {
            if ( ! in_array( $token, $field_tokens, true ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Read taxonomy values.
     *
     * @param int    $post_id   Post ID.
     * @param string $post_type Post type.
     * @return array
     */
    private function get_taxonomy_values( $post_id, $post_type ) {
        if ( ! function_exists( 'get_object_taxonomies' ) || ! function_exists( 'wp_get_post_terms' ) ) {
            return array();
        }

        $values = array();
        foreach ( (array) get_object_taxonomies( $post_type, 'names' ) as $taxonomy ) {
            $terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
            if ( is_wp_error( $terms ) ) {
                continue;
            }

            $values = array_merge( $values, $this->normalize_value_list( $terms ) );
        }

        return $this->dedupe_values( $values );
    }

    /**
     * Read WooCommerce product attribute values.
     *
     * @param int       $post_id Product ID.
     * @param WC_Product $product Product object.
     * @return array
     */
    private function get_product_attribute_values( $post_id, $product ) {
        if ( ! method_exists( $product, 'get_attributes' ) ) {
            return array();
        }

        $values = array();
        foreach ( (array) $product->get_attributes() as $attribute ) {
            if ( ! is_object( $attribute ) ) {
                continue;
            }

            if ( method_exists( $attribute, 'get_name' ) ) {
                $values[] = $attribute->get_name();
            }

            if ( method_exists( $attribute, 'is_taxonomy' ) && $attribute->is_taxonomy() && method_exists( $attribute, 'get_name' ) ) {
                $terms = wp_get_post_terms( $post_id, $attribute->get_name(), array( 'fields' => 'names' ) );
                if ( ! is_wp_error( $terms ) ) {
                    $values = array_merge( $values, $this->normalize_value_list( $terms ) );
                }
                continue;
            }

            if ( method_exists( $attribute, 'get_options' ) ) {
                $values = array_merge( $values, $this->normalize_value_list( $attribute->get_options() ) );
            }
        }

        return $this->dedupe_values( $values );
    }

    /**
     * Normalize a mixed list of scalar/object values.
     *
     * @param array $values Values.
     * @return array
     */
    private function normalize_value_list( $values ) {
        $normalized = array();

        foreach ( (array) $values as $value ) {
            if ( is_object( $value ) && isset( $value->name ) ) {
                $value = $value->name;
            } elseif ( is_object( $value ) && isset( $value->post_title ) ) {
                $value = $value->post_title;
            } elseif ( is_array( $value ) || is_object( $value ) ) {
                continue;
            }

            $value = sanitize_text_field( (string) $value );
            if ( '' !== $value ) {
                $normalized[] = $value;
            }
        }

        return $this->dedupe_values( $normalized );
    }

    /**
     * Case/accent-insensitive list dedupe.
     *
     * @param array $values Values.
     * @return array
     */
    private function dedupe_values( $values ) {
        $deduped = array();
        $seen    = array();

        foreach ( (array) $values as $value ) {
            $key = $this->normalize_text( $value );
            if ( '' === $key || isset( $seen[ $key ] ) ) {
                continue;
            }

            $seen[ $key ] = true;
            $deduped[]    = $value;
        }

        return $deduped;
    }

    /**
     * Remove non-alphanumeric separators from normalized text.
     *
     * @param string $text Normalized text.
     * @return string
     */
    private function compact_text( $text ) {
        $text = preg_replace( '/[^\p{L}\p{N}]+/u', '', (string) $text );

        return is_string( $text ) ? $text : '';
    }

    /**
     * Default scoring weights.
     *
     * @return array
     */
    private function get_default_weights() {
        return array(
            'exact_title'          => 1000,
            'phrase_title'         => 700,
            'all_title_tokens'     => 450,
            'title_token'          => 90,
            'sku_exact'            => 950,
            'sku_token'            => 300,
            'sku_partial'          => 180,
            'phrase_taxonomy'      => 260,
            'taxonomy_token'       => 140,
            'phrase_attribute'     => 260,
            'attribute_token'      => 140,
            'phrase_chip'          => 240,
            'chip_token'           => 125,
            'phrase_excerpt'       => 90,
            'excerpt_token'        => 50,
            'phrase_content'       => 60,
            'content_token'        => 25,
            'weak_partial'         => 5,
            'weak_partial_penalty' => 0.25,
            'product_bonus'        => 5,
        );
    }
}
