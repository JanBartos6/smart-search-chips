<?php
/**
 * Chip Engine - Core algorithm for generating smart, non-redundant filter chips
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSC_Chip_Engine {

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

        if ( $total < 1 ) {
            return array();
        }

        // Count tag frequency across all posts
        $tag_counts = $this->count_tags( $posts_data );

        // Sort by frequency descending
        uasort( $tag_counts, function( $a, $b ) {
            return $b['count'] - $a['count'];
        });

        // Build chips with redundancy elimination
        $chips        = array();
        $selected_keys = array();

        foreach ( $tag_counts as $key => $data ) {
            if ( count( $chips ) >= $max_chips ) {
                break;
            }

            // Skip tags that are too long
            if ( mb_strlen( $data['label'] ) > $max_length ) {
                continue;
            }

            // Skip tags that appear fewer than 2 times
            if ( $data['count'] < 2 ) {
                continue;
            }

            // Skip tags that appear in ALL results (they don't filter anything)
            if ( $data['count'] >= $total ) {
                continue;
            }

            // Redundancy check: for each already-selected chip, get its subset.
            // If this candidate appears in ALL products of any existing chip's subset,
            // it's redundant (wouldn't help narrow down within that group).
            if ( $this->is_redundant( $key, $selected_keys, $posts_data ) ) {
                continue;
            }

            $chips[ $key ]   = $data;
            $selected_keys[] = $key;
        }

        // Re-add active tags as chips even if they wouldn't normally show
        foreach ( $active_tags as $at ) {
            $at_lower = mb_strtolower( $at );
            if ( ! isset( $chips[ $at_lower ] ) ) {
                $count = isset( $tag_counts[ $at_lower ] ) ? $tag_counts[ $at_lower ]['count'] : $total;
                $chips[ $at_lower ] = array(
                    'label' => $at,
                    'count' => $count,
                );
            }
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
        if ( empty( $active_tags ) ) {
            return $posts_data;
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
     * Extract and deduplicate tags from custom fields.
     *
     * @param string $semicolon_field Semicolon-separated field value (primary tags).
     * @param string $comma_field     Comma-separated field value (extra tags).
     * @return array ['tags' => array, 'tags_lower' => array]
     */
    public function extract_tags( $semicolon_field = '', $comma_field = '' ) {
        $all_raw = '';

        if ( $semicolon_field ) {
            $all_raw .= $semicolon_field;
        }

        if ( $comma_field ) {
            $parts    = array_map( 'trim', explode( ',', $comma_field ) );
            $all_raw .= '; ' . implode( '; ', $parts );
        }

        $tags = array();
        foreach ( explode( ';', $all_raw ) as $t ) {
            $t = trim( $t );
            if ( $t && ! in_array( mb_strtolower( $t ), array_map( 'mb_strtolower', $tags ), true ) ) {
                $tags[] = $t;
            }
        }

        return array(
            'tags'       => $tags,
            'tags_lower' => array_map( 'mb_strtolower', $tags ),
        );
    }
}
