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
     * Determine whether a tag value is active using case-insensitive matching.
     *
     * Display labels and URL values are kept unchanged elsewhere; this helper
     * is only for comparisons.
     *
     * @param string $value       Tag value to check.
     * @param array  $active_tags Active tag values.
     * @return bool
     */
    public function is_tag_active( $value, $active_tags ) {
        $value_lower = mb_strtolower( (string) $value );
        if ( '' === $value_lower ) {
            return false;
        }

        foreach ( (array) $active_tags as $active_tag ) {
            $active_tag_lower = mb_strtolower( (string) $active_tag );
            if ( '' !== $active_tag_lower && $value_lower === $active_tag_lower ) {
                return true;
            }
        }

        return false;
    }

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
        $tag_counts  = array();

        if ( $total < 1 ) {
            $chips = $this->add_active_tag_chips( array(), $active_tags, $tag_counts, $posts_data, $total );
            $chips = apply_filters( 'sschp_generated_chips', $chips, $tag_counts, $posts_data, $active_tags, $max_chips, $max_length );
            return $this->order_chips_for_active_filters( $chips, $active_tags );
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

        $chips = $this->add_active_tag_chips( $chips, $active_tags, $tag_counts, $posts_data, $total );

        $chips = apply_filters( 'sschp_generated_chips', $chips, $tag_counts, $posts_data, $active_tags, $max_chips, $max_length );

        return $this->order_chips_for_active_filters( $chips, $active_tags );
    }

    /**
     * Keep every selected chip before every unselected chip.
     *
     * Within those groups, range controls retain priority so active ranges
     * stay first and inactive ranges remain ahead of ordinary suggestions.
     *
     * @param array $chips       Generated chips.
     * @param array $active_tags Active tags.
     * @return array Ordered chips.
     */
    private function order_chips_for_active_filters( $chips, $active_tags ) {
        if ( empty( $active_tags ) ) {
            return $chips;
        }

        $active_chips         = array();
        $inactive_range_chips = array();
        $suggestion_chips     = array();
        $active_tags_lower    = array_map( 'mb_strtolower', array_map( 'strval', $active_tags ) );

        foreach ( $chips as $key => $chip ) {
            if ( $this->chip_matches_active_tag( $key, $chip, $active_tags, $active_tags_lower ) ) {
                $active_chips[ $key ] = $chip;
                continue;
            }

            if ( $this->is_range_chip( $chip ) ) {
                $inactive_range_chips[ $key ] = $chip;
                continue;
            }

            $suggestion_chips[ $key ] = $chip;
        }

        return $active_chips + $inactive_range_chips + $suggestion_chips;
    }

    /**
     * Check whether a chip is a numeric range control.
     *
     * @param array $chip Chip data.
     * @return bool
     */
    private function is_range_chip( $chip ) {
        return is_array( $chip ) && isset( $chip['type'] ) && 'range' === $chip['type'];
    }

    /**
     * Check whether a chip represents an active tag.
     *
     * @param string $key               Chip key.
     * @param array  $chip              Chip data.
     * @param array  $active_tags       Active tags.
     * @param array  $active_tags_lower Lowercase active tags.
     * @return bool
     */
    private function chip_matches_active_tag( $key, $chip, $active_tags, $active_tags_lower ) {
        if ( ! empty( $chip['is_active'] ) ) {
            return true;
        }

        if ( in_array( mb_strtolower( (string) $key ), $active_tags_lower, true ) ) {
            return true;
        }

        $value = isset( $chip['value'] ) ? $chip['value'] : $key;

        return $this->is_tag_active( $value, $active_tags );
    }

    /**
     * Re-add active tags as chips even if the current result set cannot produce
     * normal chip suggestions. This keeps zero-result filtered searches
     * reversible from the frontend.
     *
     * @param array $chips Existing generated chips.
     * @param array $active_tags Active tags.
     * @param array $tag_counts Counted tag data.
     * @param array $posts_data Current posts.
     * @param int   $total Current result count.
     * @return array Chips with active fallbacks included.
     */
    private function add_active_tag_chips( $chips, $active_tags, $tag_counts, $posts_data, $total ) {
        foreach ( $active_tags as $at ) {
            $at_lower = mb_strtolower( $at );
            if ( isset( $chips[ $at_lower ] ) ) {
                continue;
            }

            $fallback = apply_filters( 'sschp_active_chip_fallback', null, $at, $tag_counts, $posts_data, $total );
            if ( false === $fallback ) {
                continue;
            }

            if ( is_array( $fallback ) && isset( $fallback['key'], $fallback['chip'] ) && is_array( $fallback['chip'] ) ) {
                $chips[ $fallback['key'] ] = $fallback['chip'];
                continue;
            }

            if ( isset( $tag_counts[ $at_lower ] ) ) {
                $chips[ $at_lower ] = $tag_counts[ $at_lower ];
                continue;
            }

            $chips[ $at_lower ] = array(
                'label' => $at,
                'value' => $at,
                'count' => $total,
            );
        }

        return $chips;
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
     * either field so the parser stays resilient across tag sources, but do
     * not split decimal comma values such as 0,1 A into broken chip fragments.
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
            $parts = $this->split_tag_values( $raw_value );
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
     * Split tag field values without treating decimal commas as separators.
     *
     * @param mixed $value Raw field value.
     * @return array
     */
    private function split_tag_values( $value ) {
        return preg_split( '/[;\r\n]+|(?<!\d),(?!\d)/u', (string) $value );
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

        $normalized = array();
        $seen       = array();

        foreach ( $active_tags as $active_tag ) {
            $active_tag = trim( (string) $active_tag );
            if ( '' === $active_tag ) {
                continue;
            }

            $key = mb_strtolower( $active_tag );
            if ( isset( $seen[ $key ] ) ) {
                continue;
            }

            $seen[ $key ]  = true;
            $normalized[] = $active_tag;
        }

        return apply_filters( 'sschp_active_tags', array_values( $normalized ) );
    }
}
