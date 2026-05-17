<?php
/**
 * Chip Engine - Core algorithm for generating smart, non-redundant filter chips
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCHP_Chip_Engine {

    /**
     * Generate chips from a set of posts with tags.
     *
     * @param array $posts_data  Array of posts, each with 'tags' and 'tags_lower' keys.
     * @param array $active_tags Currently selected tags.
     * @param int   $max_chips   Maximum number of chips to return.
     * @param int   $max_length  Maximum character length for a chip label.
     * @return array Filtered and ranked chips.
     */
    public function generate_chips( $posts_data, $active_tags = array(), $max_chips = 8, $max_length = 40 ) {
        $total = count( $posts_data );
        $active_tags = $this->normalize_active_tags( $active_tags );

        if ( $total < 1 ) {
            return array();
        }

        // Count tag frequency across all posts
        $tag_counts = $this->count_tags( $posts_data );
        $tag_counts = apply_filters( 'sschp_chip_tag_counts', $tag_counts, $posts_data, $active_tags );

        // Sort by frequency descending
        uasort( $tag_counts, function( $a, $b ) {
            return $b['count'] - $a['count'];
        });

        // Build chips with redundancy elimination
        $chips        = array();
        $selected_keys = array();
        $min_count     = $total <= 5 ? 1 : 2;
        $min_count     = max( 1, (int) apply_filters( 'sschp_min_chip_count', $min_count, $total, $posts_data, $active_tags ) );

        foreach ( $tag_counts as $key => $data ) {
            $is_virtual_chip = isset( $data['type'] ) && 'range' === $data['type'];

            if ( count( $chips ) >= $max_chips ) {
                break;
            }

            // Skip tags that are too long
            if ( mb_strlen( $data['label'] ) > $max_length ) {
                continue;
            }

            // Skip tags that do not meet the dynamic usefulness threshold.
            if ( $data['count'] < $min_count ) {
                continue;
            }

            // Skip tags that appear in ALL results (they don't filter anything)
            if ( ! $is_virtual_chip && $data['count'] >= $total ) {
                continue;
            }

            // Redundancy check: for each already-selected chip, get its subset.
            // If this candidate appears in ALL products of any existing chip's subset,
            // it's redundant (wouldn't help narrow down within that group).
            if ( ! $is_virtual_chip && $this->is_redundant( $key, $selected_keys, $posts_data ) ) {
                continue;
            }

            $chips[ $key ]   = $data;
            $selected_keys[] = $key;
        }

        // Re-add active tags as chips even if they wouldn't normally show
        foreach ( $active_tags as $at ) {
            $at_lower = mb_strtolower( $at );
            if ( ! isset( $chips[ $at_lower ] ) ) {
                $fallback = apply_filters( 'sschp_active_chip_fallback', null, $at, $tag_counts, $posts_data, $total );
                if ( false === $fallback ) {
                    continue;
                }

                if ( is_array( $fallback ) && isset( $fallback['key'], $fallback['chip'] ) && is_array( $fallback['chip'] ) ) {
                    $chips[ $fallback['key'] ] = $fallback['chip'];
                    continue;
                }

                $count = isset( $tag_counts[ $at_lower ] ) ? $tag_counts[ $at_lower ]['count'] : $total;
                $chips[ $at_lower ] = array(
                    'label' => $at,
                    'value' => $at,
                    'count' => $count,
                );
            }
        }

        $chips = apply_filters( 'sschp_generated_chips', $chips, $tag_counts, $posts_data, $active_tags, $max_chips, $max_length );

        if ( empty( $active_tags ) ) {
            return $chips;
        }

        // Keep active chips visible first while preserving the existing
        // ranking order inside the active and inactive groups.
        $ordered = array();

        $active_tags_lower = array_map( 'mb_strtolower', $active_tags );

        foreach ( $chips as $key => $chip ) {
            $value = isset( $chip['value'] ) ? $chip['value'] : $key;

            if ( in_array( $key, $active_tags_lower, true ) || in_array( $value, $active_tags, true ) ) {
                $ordered[ $key ] = $chip;
            }
        }

        foreach ( $chips as $key => $chip ) {
            if ( ! isset( $ordered[ $key ] ) ) {
                $ordered[ $key ] = $chip;
            }
        }

        return $ordered;
    }

    /**
     * Filter posts by active tags (exact match).
     *
     * @param array $posts_data  Posts to filter.
     * @param array $active_tags Tags that must all be present.
     * @return array Filtered posts.
     */
    public function filter_by_tags( $posts_data, $active_tags ) {
        $active_tags = $this->normalize_active_tags( $active_tags );

        if ( empty( $active_tags ) ) {
            return $posts_data;
        }

        $filtered_by_extension = apply_filters( 'sschp_filter_posts_by_tags', null, $posts_data, $active_tags );
        if ( is_array( $filtered_by_extension ) ) {
            return array_values( $filtered_by_extension );
        }

        $filtered = array_filter( $posts_data, function( $post ) use ( $active_tags ) {
            foreach ( $active_tags as $required_tag ) {
                if ( ! in_array( mb_strtolower( $required_tag ), $post['tags_lower'], true ) ) {
                    return false;
                }
            }
            return true;
        });

        return array_values( $filtered );
    }

    /**
     * Count tag occurrences across posts.
     *
     * @param array $posts_data Posts with tags.
     * @return array Tag counts: key => ['label' => string, 'count' => int].
     */
    public function count_tags( $posts_data ) {
        $tag_counts = array();

        foreach ( $posts_data as $post ) {
            foreach ( $post['tags'] as $tag ) {
                $tag_lower = mb_strtolower( $tag );
                if ( ! isset( $tag_counts[ $tag_lower ] ) ) {
                    $tag_counts[ $tag_lower ] = array(
                        'label' => $tag,
                        'value' => $tag,
                        'count' => 0,
                    );
                }
                $tag_counts[ $tag_lower ]['count']++;
            }
        }

        return $tag_counts;
    }

    /**
     * Check if a candidate tag is redundant given already-selected chips.
     *
     * A tag is redundant if it appears in ALL products of any existing chip's subset,
     * meaning it wouldn't help narrow down results within that group.
     *
     * @param string $candidate_key  Lowercase candidate tag.
     * @param array  $selected_keys  Already selected chip keys (lowercase).
     * @param array  $posts_data     All posts.
     * @return bool True if redundant.
     */
    private function is_redundant( $candidate_key, $selected_keys, $posts_data ) {
        foreach ( $selected_keys as $existing_key ) {
            // Get products that have the existing chip's tag
            $subset = array_filter( $posts_data, function( $p ) use ( $existing_key ) {
                return in_array( $existing_key, $p['tags_lower'], true );
            });

            // Count how many in this subset also have the candidate tag
            $candidate_in_subset = 0;
            foreach ( $subset as $p ) {
                if ( in_array( $candidate_key, $p['tags_lower'], true ) ) {
                    $candidate_in_subset++;
                }
            }

            // If candidate appears in ALL of this subset, it's redundant
            if ( count( $subset ) > 0 && $candidate_in_subset >= count( $subset ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract and deduplicate tags from field values.
     *
     * The built-in `sschp_tags` field is stored as a comma-delimited string, while
     * some external sources are semicolon-delimited. Accept both separators from
     * either field so the parser stays resilient across Free/Pro sources.
     *
     * @param string $semicolon_field Primary field value.
     * @param string $comma_field     Extra field value.
     * @return array ['tags' => array, 'tags_lower' => array]
     */
    public function extract_tags( $semicolon_field = '', $comma_field = '' ) {
        $raw_values = array_filter( array( $semicolon_field, $comma_field ), function( $value ) {
            return '' !== trim( (string) $value );
        } );

        $tags = array();
        foreach ( $raw_values as $raw_value ) {
            $parts = preg_split( '/[;,]/', (string) $raw_value );
            foreach ( $parts as $t ) {
                $t = trim( $t );
                if ( $t && ! in_array( mb_strtolower( $t ), array_map( 'mb_strtolower', $tags ), true ) ) {
                    $tags[] = $t;
                }
            }
        }

        $tag_data = array(
            'tags'       => $tags,
            'tags_lower' => array_map( 'mb_strtolower', $tags ),
        );

        return apply_filters( 'sschp_extracted_tags', $tag_data, $semicolon_field, $comma_field );
    }

    /**
     * Normalize active tags through plugin filters.
     *
     * @param array $active_tags Raw active tags.
     * @return array
     */
    private function normalize_active_tags( $active_tags ) {
        if ( ! is_array( $active_tags ) ) {
            $active_tags = array();
        }

        return apply_filters( 'sschp_active_tags', array_values( $active_tags ) );
    }
}
