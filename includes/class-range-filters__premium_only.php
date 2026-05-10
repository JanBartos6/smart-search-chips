<?php
/**
 * Pro numeric range chips.
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCHP_Range_Filters {

    const ACTIVE_PREFIX = 'sschp_range';
    const CHIP_TYPE     = 'range';

    /**
     * Register range chip hooks.
     */
    public function init() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 20 );
        add_filter( 'sschp_chip_tag_counts', array( $this, 'add_range_chips' ), 20, 3 );
        add_filter( 'sschp_filter_posts_by_tags', array( $this, 'filter_posts_by_range_tags' ), 10, 3 );
        add_filter( 'sschp_active_chip_fallback', array( $this, 'active_range_chip_fallback' ), 10, 5 );
        add_filter( 'sschp_render_chip_html', array( $this, 'render_range_chip' ), 10, 4 );
    }

    /**
     * Enqueue frontend range chip assets.
     */
    public function enqueue_assets() {
        $style_path  = SSCHP_PLUGIN_DIR . 'assets/css/range-filters__premium_only.css';
        $script_path = SSCHP_PLUGIN_DIR . 'assets/js/range-filters__premium_only.js';

        wp_enqueue_style(
            'smart-search-chips-range-filters',
            SSCHP_PLUGIN_URL . 'assets/css/range-filters__premium_only.css',
            array( 'smart-search-chips' ),
            file_exists( $style_path ) ? filemtime( $style_path ) : SSCHP_VERSION
        );

        wp_enqueue_script(
            'smart-search-chips-range-filters',
            SSCHP_PLUGIN_URL . 'assets/js/range-filters__premium_only.js',
            array( 'smart-search-chips' ),
            file_exists( $script_path ) ? filemtime( $script_path ) : SSCHP_VERSION,
            true
        );
    }

    /**
     * Replace repeated numeric tag variants with one range chip.
     *
     * @param array $tag_counts  Existing tag counts.
     * @param array $posts_data  Posts data.
     * @param array $active_tags Active tags.
     * @return array
     */
    public function add_range_chips( $tag_counts, $posts_data, $active_tags ) {
        $groups        = $this->collect_numeric_groups( $posts_data );
        $active_ranges = $this->get_active_ranges( $active_tags );

        foreach ( $groups as $group ) {
            if ( ! $this->group_is_filterable( $group ) ) {
                continue;
            }

            foreach ( $group['tag_keys'] as $tag_key ) {
                unset( $tag_counts[ $tag_key ] );
            }

            $chip_key                = $this->range_chip_key( $group['group_key'], $group['unit_key'] );
            $active_range            = isset( $active_ranges[ $chip_key ] ) ? $active_ranges[ $chip_key ] : null;
            $tag_counts[ $chip_key ] = $this->build_range_chip( $group, $active_range );
        }

        return $tag_counts;
    }

    /**
     * Apply active numeric range chips while keeping normal exact chip behavior.
     *
     * @param null|array $filtered    Existing extension result.
     * @param array      $posts_data  Posts data.
     * @param array      $active_tags Active tags.
     * @return null|array
     */
    public function filter_posts_by_range_tags( $filtered, $posts_data, $active_tags ) {
        $range_filters = array();
        $exact_tags    = array();

        foreach ( (array) $active_tags as $active_tag ) {
            $range = $this->parse_active_range( $active_tag );
            if ( $range ) {
                $range_filters[] = $range;
                continue;
            }

            $exact_tags[] = $active_tag;
        }

        if ( empty( $range_filters ) ) {
            return $filtered;
        }

        $posts = array_filter( $posts_data, function( $post ) use ( $exact_tags, $range_filters ) {
            foreach ( $exact_tags as $required_tag ) {
                if ( ! in_array( mb_strtolower( $required_tag ), $post['tags_lower'], true ) ) {
                    return false;
                }
            }

            foreach ( $range_filters as $range_filter ) {
                if ( ! $this->post_matches_range( $post, $range_filter ) ) {
                    return false;
                }
            }

            return true;
        } );

        return array_values( $posts );
    }

    /**
     * Keep an active range chip visible even when the selected range is narrow.
     *
     * @param null|false|array $fallback   Existing fallback.
     * @param string           $active_tag Active tag value.
     * @param array            $tag_counts Tag counts.
     * @param array            $posts_data Posts data.
     * @param int              $total      Current result count.
     * @return null|false|array
     */
    public function active_range_chip_fallback( $fallback, $active_tag, $tag_counts, $posts_data, $total ) {
        $active_range = $this->parse_active_range( $active_tag );
        if ( ! $active_range ) {
            return $fallback;
        }

        $chip_key = $this->range_chip_key( $active_range['group_key'], $active_range['unit_key'] );
        if ( isset( $tag_counts[ $chip_key ] ) ) {
            return array(
                'key'  => $chip_key,
                'chip' => $tag_counts[ $chip_key ],
            );
        }

        $groups = $this->collect_numeric_groups( $posts_data );
        foreach ( $groups as $group ) {
            if ( $group['group_key'] === $active_range['group_key'] && $group['unit_key'] === $active_range['unit_key'] ) {
                return array(
                    'key'  => $chip_key,
                    'chip' => $this->build_range_chip( $group, $active_range ),
                );
            }
        }

        return array(
            'key'  => $chip_key,
            'chip' => $this->build_range_chip(
                array(
                    'label'     => $this->humanize_key( $active_range['group_key'] ),
                    'group_key' => $active_range['group_key'],
                    'unit'      => '',
                    'unit_key'  => $active_range['unit_key'],
                    'min'       => $active_range['bounds_min'],
                    'max'       => $active_range['bounds_max'],
                    'count'     => max( 0, (int) $total ),
                    'precision' => $active_range['precision'],
                    'values'    => array( $active_range['selected_min'], $active_range['selected_max'] ),
                    'tag_keys'  => array(),
                ),
                $active_range
            ),
        );
    }

    /**
     * Render range chip markup.
     *
     * @param string $html        Existing custom HTML.
     * @param array  $chip        Chip data.
     * @param array  $active_tags Active tags.
     * @param array  $context     Render context.
     * @return string
     */
    public function render_range_chip( $html, $chip, $active_tags, $context ) {
        if ( ! isset( $chip['type'] ) || self::CHIP_TYPE !== $chip['type'] || empty( $chip['range'] ) || ! is_array( $chip['range'] ) ) {
            return $html;
        }

        $range        = $chip['range'];
        $active_range = $this->find_active_range( $active_tags, $range['group_key'], $range['unit_key'] );
        $is_active    = (bool) $active_range;
        $styles       = isset( $context['styles'] ) && is_array( $context['styles'] ) ? $context['styles'] : array();
        $render_mode  = isset( $context['context'] ) ? sanitize_key( $context['context'] ) : 'ajax';
        $unit         = isset( $range['unit'] ) ? $range['unit'] : '';
        $selected_min = $this->format_number( $range['selected_min'] );
        $selected_max = $this->format_number( $range['selected_max'] );
        $bounds_min   = $this->format_number( $range['min'] );
        $bounds_max   = $this->format_number( $range['max'] );
        $step         = $this->format_number( $range['step'] );
        $value        = $this->encode_active_range( $range['group_key'], $range['unit_key'], $selected_min, $selected_max, $bounds_min, $bounds_max );

        $style_attr = sprintf(
            '--ssc-range-accent:%s;--ssc-range-bg:%s;--ssc-range-color:%s;--ssc-range-border:%s;--ssc-range-radius:%spx;--ssc-range-font:%spx;',
            esc_attr( isset( $styles['border'] ) ? $styles['border'] : '#e74c3c' ),
            esc_attr( isset( $styles['background'] ) ? $styles['background'] : '#ffffff' ),
            esc_attr( isset( $styles['color'] ) ? $styles['color'] : '#333333' ),
            esc_attr( isset( $styles['border'] ) ? $styles['border'] : '#e74c3c' ),
            esc_attr( isset( $styles['radius'] ) ? $styles['radius'] : 16 ),
            esc_attr( isset( $styles['font_size'] ) ? $styles['font_size'] : 13 )
        );

        $classes = 'ssc-range-chip';
        if ( $is_active ) {
            $classes .= ' ssc-range-chip--active';
        }

        ob_start();
        ?>
        <div
            class="<?php echo esc_attr( $classes ); ?>"
            style="<?php echo esc_attr( $style_attr ); ?>"
            data-range-context="<?php echo esc_attr( $render_mode ); ?>"
            data-range-group-key="<?php echo esc_attr( $range['group_key'] ); ?>"
            data-range-unit-key="<?php echo esc_attr( $range['unit_key'] ); ?>"
            data-range-prefix="<?php echo esc_attr( self::ACTIVE_PREFIX . '|' . $range['group_key'] . '|' . $range['unit_key'] . '|' ); ?>"
            data-range-bounds-min="<?php echo esc_attr( $bounds_min ); ?>"
            data-range-bounds-max="<?php echo esc_attr( $bounds_max ); ?>"
            data-range-value="<?php echo esc_attr( $value ); ?>"
            data-range-label="<?php echo esc_attr( $chip['label'] ); ?>"
        >
            <div class="ssc-range-chip__values" aria-live="polite">
                <span class="ssc-range-chip__value" data-range-role="min-label"><?php echo esc_html( $selected_min ); ?></span>
                <span class="ssc-range-chip__separator">-</span>
                <span class="ssc-range-chip__value" data-range-role="max-label"><?php echo esc_html( $selected_max ); ?></span>
                <?php if ( '' !== $unit ) : ?>
                    <span class="ssc-range-chip__unit"><?php echo esc_html( $unit ); ?></span>
                <?php endif; ?>
            </div>
            <div class="ssc-range-chip__sliders">
                <input
                    class="ssc-range-chip__input ssc-range-chip__input--min"
                    type="range"
                    min="<?php echo esc_attr( $bounds_min ); ?>"
                    max="<?php echo esc_attr( $bounds_max ); ?>"
                    step="<?php echo esc_attr( $step ); ?>"
                    value="<?php echo esc_attr( $selected_min ); ?>"
                    data-range-role="min"
                    aria-label="<?php echo esc_attr( sprintf( __( 'Minimum %s', 'smart-search-chips' ), $chip['label'] ) ); ?>"
                />
                <input
                    class="ssc-range-chip__input ssc-range-chip__input--max"
                    type="range"
                    min="<?php echo esc_attr( $bounds_min ); ?>"
                    max="<?php echo esc_attr( $bounds_max ); ?>"
                    step="<?php echo esc_attr( $step ); ?>"
                    value="<?php echo esc_attr( $selected_max ); ?>"
                    data-range-role="max"
                    aria-label="<?php echo esc_attr( sprintf( __( 'Maximum %s', 'smart-search-chips' ), $chip['label'] ) ); ?>"
                />
            </div>
        </div>
        <?php

        return trim( ob_get_clean() );
    }

    /**
     * Build range metadata by scanning tags.
     *
     * @param array $posts_data Posts data.
     * @return array
     */
    private function collect_numeric_groups( $posts_data ) {
        $groups = array();

        foreach ( (array) $posts_data as $post ) {
            $seen_in_post = array();

            $tags = isset( $post['tags'] ) && is_array( $post['tags'] ) ? $post['tags'] : array();
            foreach ( $tags as $tag ) {
                $parsed = $this->parse_numeric_tag( $tag );
                if ( ! $parsed ) {
                    continue;
                }

                $group_id = $parsed['group_key'] . '|' . $parsed['unit_key'];
                if ( ! isset( $groups[ $group_id ] ) ) {
                    $groups[ $group_id ] = array(
                        'label'     => $parsed['label'],
                        'group_key' => $parsed['group_key'],
                        'unit'      => $parsed['unit'],
                        'unit_key'  => $parsed['unit_key'],
                        'min'       => $parsed['value'],
                        'max'       => $parsed['value'],
                        'count'     => 0,
                        'precision' => $parsed['precision'],
                        'values'    => array(),
                        'tag_keys'  => array(),
                    );
                }

                $groups[ $group_id ]['min']       = min( $groups[ $group_id ]['min'], $parsed['value'] );
                $groups[ $group_id ]['max']       = max( $groups[ $group_id ]['max'], $parsed['value'] );
                $groups[ $group_id ]['precision'] = max( $groups[ $group_id ]['precision'], $parsed['precision'] );
                $groups[ $group_id ]['values'][]  = $parsed['value'];
                $groups[ $group_id ]['tag_keys'][ mb_strtolower( $tag ) ] = mb_strtolower( $tag );

                if ( ! isset( $seen_in_post[ $group_id ] ) ) {
                    $groups[ $group_id ]['count']++;
                    $seen_in_post[ $group_id ] = true;
                }
            }
        }

        return array_values( $groups );
    }

    /**
     * Determine whether a numeric group has enough variation to be a range chip.
     *
     * @param array $group Numeric group.
     * @return bool
     */
    private function group_is_filterable( $group ) {
        if ( empty( $group['values'] ) || (int) $group['count'] < 2 ) {
            return false;
        }

        $distinct = array_unique( array_map( array( $this, 'format_number' ), $group['values'] ) );

        return count( $distinct ) > 1 && $group['max'] > $group['min'];
    }

    /**
     * Build a chip array for one numeric range group.
     *
     * @param array      $group        Numeric group.
     * @param null|array $active_range Active range metadata.
     * @return array
     */
    private function build_range_chip( $group, $active_range = null ) {
        $bounds_min   = isset( $group['min'] ) ? (float) $group['min'] : 0;
        $bounds_max   = isset( $group['max'] ) ? (float) $group['max'] : $bounds_min;
        $selected_min = $bounds_min;
        $selected_max = $bounds_max;

        if ( $active_range ) {
            $bounds_min   = $active_range['bounds_min'];
            $bounds_max   = $active_range['bounds_max'];
            if ( $bounds_min > $bounds_max ) {
                $swap       = $bounds_min;
                $bounds_min = $bounds_max;
                $bounds_max = $swap;
            }
            $selected_min = max( $bounds_min, min( $bounds_max, $active_range['selected_min'] ) );
            $selected_max = max( $bounds_min, min( $bounds_max, $active_range['selected_max'] ) );
        }

        if ( $selected_min > $selected_max ) {
            $swap         = $selected_min;
            $selected_min = $selected_max;
            $selected_max = $swap;
        }

        $precision = isset( $group['precision'] ) ? (int) $group['precision'] : 0;
        $step      = $precision > 0 ? pow( 10, -1 * min( 3, $precision ) ) : 1;
        $value     = $this->encode_active_range(
            $group['group_key'],
            $group['unit_key'],
            $selected_min,
            $selected_max,
            $bounds_min,
            $bounds_max
        );

        return array(
            'label' => $group['label'],
            'value' => $value,
            'count' => isset( $group['count'] ) ? (int) $group['count'] : 0,
            'type'  => self::CHIP_TYPE,
            'range' => array(
                'group_key'    => $group['group_key'],
                'unit_key'     => $group['unit_key'],
                'unit'         => isset( $group['unit'] ) ? $group['unit'] : '',
                'min'          => $bounds_min,
                'max'          => $bounds_max,
                'selected_min' => $selected_min,
                'selected_max' => $selected_max,
                'step'         => $step,
            ),
        );
    }

    /**
     * Check one post against one range filter.
     *
     * @param array $post         Post data.
     * @param array $range_filter Range filter.
     * @return bool
     */
    private function post_matches_range( $post, $range_filter ) {
        $tags = isset( $post['tags'] ) && is_array( $post['tags'] ) ? $post['tags'] : array();
        foreach ( $tags as $tag ) {
            $parsed = $this->parse_numeric_tag( $tag );
            if ( ! $parsed ) {
                continue;
            }

            if ( $parsed['group_key'] !== $range_filter['group_key'] || $parsed['unit_key'] !== $range_filter['unit_key'] ) {
                continue;
            }

            if ( $parsed['value'] >= $range_filter['selected_min'] && $parsed['value'] <= $range_filter['selected_max'] ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse a tag into a numeric variable if possible.
     *
     * @param string $tag Tag label.
     * @return null|array
     */
    private function parse_numeric_tag( $tag ) {
        $tag = trim( wp_strip_all_tags( (string) $tag ) );
        if ( '' === $tag ) {
            return null;
        }

        $label  = '';
        $number = '';
        $unit   = '';

        if ( preg_match( '/^\s*([^\d\+\-][^:=]*?)\s*(?:[:=]|\s+)\s*([-+]?\d+(?:[\.,]\d+)?)\s*([^,;]+)?\s*$/u', $tag, $matches ) ) {
            $label  = trim( $matches[1], " \t\n\r\0\x0B:-=" );
            $number = $matches[2];
            $unit   = isset( $matches[3] ) ? trim( $matches[3] ) : '';
        } elseif ( preg_match( '/^\s*([-+]?\d+(?:[\.,]\d+)?)\s*([^,;]+)?\s*$/u', $tag, $matches ) ) {
            $number = $matches[1];
            $unit   = isset( $matches[2] ) ? trim( $matches[2] ) : '';
            $label  = $this->get_default_numeric_label( $unit );
        }

        if ( '' === $label || '' === $number ) {
            return null;
        }

        $unit = $this->normalize_unit( $unit );
        if ( ! $this->is_supported_unit( $unit, $label, $tag ) ) {
            return null;
        }

        $normalized_number = str_replace( ',', '.', $number );
        if ( ! is_numeric( $normalized_number ) ) {
            return null;
        }

        $value = (float) $normalized_number;
        $parts = explode( '.', $normalized_number );

        return array(
            'label'     => sanitize_text_field( $label ),
            'group_key' => $this->label_to_key( $label ),
            'unit'      => sanitize_text_field( $unit ),
            'unit_key'  => $this->unit_to_key( $unit ),
            'value'     => $value,
            'precision' => isset( $parts[1] ) ? strlen( rtrim( $parts[1], '0' ) ) : 0,
        );
    }

    /**
     * Normalize a parsed measurement unit.
     *
     * @param string $unit Parsed unit.
     * @return string
     */
    private function normalize_unit( $unit ) {
        $unit = trim( (string) $unit );
        $unit = trim( $unit, ".:;()[]{}" );
        $unit = str_replace( array( 'μ' ), 'µ', $unit );
        $unit = preg_replace( '/\s+/', ' ', $unit );

        $normalized_key = $this->unit_to_key( $unit );
        $display_map    = array(
            'v'    => 'V',
            'a'    => 'A',
            'w'    => 'W',
            'watt' => 'watt',
            'volt' => 'volt',
            'amp'  => 'amp',
        );

        if ( isset( $display_map[ $normalized_key ] ) ) {
            return $display_map[ $normalized_key ];
        }

        return $unit;
    }

    /**
     * Decide whether a numeric tag is safe to turn into a range slider.
     *
     * Arbitrary numbers in product names/model labels are common, so range chips
     * require an explicit recognized measurement unit by default.
     *
     * @param string $unit  Parsed unit.
     * @param string $label Parsed label.
     * @param string $tag   Original tag.
     * @return bool
     */
    private function is_supported_unit( $unit, $label, $tag ) {
        $requires_unit = apply_filters( 'sschp_range_requires_unit', true, $tag, $label, $unit );
        if ( ! $requires_unit && '' === $unit ) {
            return true;
        }

        if ( '' === $unit ) {
            return false;
        }

        $normalized = $this->unit_to_key( $unit );
        $allowed    = apply_filters(
            'sschp_range_allowed_units',
            array(
                'w', 'kw', 'mw',
                'watt',
                'v', 'kv', 'mv',
                'volt',
                'a', 'ma',
                'amp',
                'ohm',
                'hz', 'khz', 'mhz', 'ghz',
                'mm', 'cm', 'm', 'km',
                'g', 'kg', 'mg', 't',
                'ml', 'l',
                'percent',
                'deg', 'degc', 'c', 'f',
                'n', 'kn', 'nm',
                'bar', 'mbar', 'psi', 'pa', 'kpa', 'mpa',
                'rpm',
                'lm', 'lux',
                'm3-h', 'm3-min', 'l-min', 'l-h',
                'mah', 'ah',
                'wh', 'kwh',
                'gb', 'mb', 'kb', 'tb',
                'px', 'em', 'rem',
            ),
            $tag,
            $label,
            $unit
        );

        return in_array( $normalized, array_map( 'sanitize_key', (array) $allowed ), true );
    }

    /**
     * Parse encoded active range value.
     *
     * @param string $active_tag Active tag.
     * @return null|array
     */
    private function parse_active_range( $active_tag ) {
        $active_tag = sanitize_text_field( (string) $active_tag );
        if ( 0 !== strpos( $active_tag, self::ACTIVE_PREFIX . '|' ) ) {
            return null;
        }

        $parts = explode( '|', $active_tag );
        if ( count( $parts ) < 7 ) {
            return null;
        }

        $selected_min = (float) $parts[3];
        $selected_max = (float) $parts[4];
        $bounds_min   = (float) $parts[5];
        $bounds_max   = (float) $parts[6];

        if ( $selected_min > $selected_max ) {
            $swap         = $selected_min;
            $selected_min = $selected_max;
            $selected_max = $swap;
        }

        return array(
            'group_key'    => sanitize_key( $parts[1] ),
            'unit_key'     => sanitize_key( $parts[2] ),
            'selected_min' => $selected_min,
            'selected_max' => $selected_max,
            'bounds_min'   => $bounds_min,
            'bounds_max'   => $bounds_max,
            'precision'    => max( $this->decimal_precision( $parts[3] ), $this->decimal_precision( $parts[4] ) ),
        );
    }

    /**
     * Find the active range for a specific group.
     *
     * @param array  $active_tags Active tags.
     * @param string $group_key   Group key.
     * @param string $unit_key    Unit key.
     * @return null|array
     */
    private function find_active_range( $active_tags, $group_key, $unit_key ) {
        foreach ( (array) $active_tags as $active_tag ) {
            $range = $this->parse_active_range( $active_tag );
            if ( $range && $range['group_key'] === $group_key && $range['unit_key'] === $unit_key ) {
                return $range;
            }
        }

        return null;
    }

    /**
     * Return active ranges keyed by chip key.
     *
     * @param array $active_tags Active tags.
     * @return array
     */
    private function get_active_ranges( $active_tags ) {
        $ranges = array();

        foreach ( (array) $active_tags as $active_tag ) {
            $range = $this->parse_active_range( $active_tag );
            if ( ! $range ) {
                continue;
            }

            $ranges[ $this->range_chip_key( $range['group_key'], $range['unit_key'] ) ] = $range;
        }

        return $ranges;
    }

    /**
     * Encode a selected numeric range into the active_tags array.
     *
     * @param string       $group_key    Group key.
     * @param string       $unit_key     Unit key.
     * @param float|string $selected_min Selected minimum.
     * @param float|string $selected_max Selected maximum.
     * @param float|string $bounds_min   Absolute minimum.
     * @param float|string $bounds_max   Absolute maximum.
     * @return string
     */
    private function encode_active_range( $group_key, $unit_key, $selected_min, $selected_max, $bounds_min, $bounds_max ) {
        return implode(
            '|',
            array(
                self::ACTIVE_PREFIX,
                sanitize_key( $group_key ),
                sanitize_key( $unit_key ),
                $this->format_number( $selected_min ),
                $this->format_number( $selected_max ),
                $this->format_number( $bounds_min ),
                $this->format_number( $bounds_max ),
            )
        );
    }

    /**
     * Build internal range chip key.
     *
     * @param string $group_key Group key.
     * @param string $unit_key  Unit key.
     * @return string
     */
    private function range_chip_key( $group_key, $unit_key ) {
        return self::ACTIVE_PREFIX . '|' . sanitize_key( $group_key ) . '|' . sanitize_key( $unit_key );
    }

    /**
     * Convert a label to a stable key.
     *
     * @param string $label Label.
     * @return string
     */
    private function label_to_key( $label ) {
        $key = sanitize_title( $label );

        if ( '' === $key ) {
            $key = 'range-' . substr( md5( mb_strtolower( (string) $label ) ), 0, 10 );
        }

        return sanitize_key( $key );
    }

    /**
     * Convert a unit to a stable key.
     *
     * @param string $unit Unit.
     * @return string
     */
    private function unit_to_key( $unit ) {
        $unit = str_replace( array( 'µ', 'μ' ), 'u', mb_strtolower( trim( (string) $unit ) ) );
        $unit = str_replace( array( '²', '³' ), array( '2', '3' ), $unit );
        $unit = str_replace( array( '%', '°', '/', '\\' ), array( 'percent', 'deg', '-', '-' ), $unit );
        $unit = preg_replace( '/\s+/', ' ', $unit );

        $compact_unit = str_replace( array( ' ', '.', '-' ), '', $unit );
        if ( preg_match( '/^v(?:ac|dc)?$/u', $compact_unit ) ) {
            return 'v';
        }

        if ( preg_match( '/^a(?:ac|dc)?$/u', $compact_unit ) ) {
            return 'a';
        }

        if ( preg_match( '/^watt(?:s|u|ů|y|ech|ům|em)?$/u', $compact_unit ) ) {
            return 'watt';
        }

        if ( preg_match( '/^volt(?:s|u|ů|y|ech|ům|em)?$/u', $compact_unit ) ) {
            return 'volt';
        }

        if ( preg_match( '/^(?:amp|amps|amp[eé]r(?:u|ů|y|ech|ům|em)?|amp[eé]ry)$/u', $compact_unit ) ) {
            return 'amp';
        }

        $key = trim( sanitize_key( preg_replace( '/[^a-z0-9_\-]+/i', '-', $unit ) ), '-' );

        return '' !== $key ? $key : 'none';
    }

    /**
     * Create a fallback label from a key.
     *
     * @param string $key Key.
     * @return string
     */
    private function humanize_key( $key ) {
        return ucwords( str_replace( array( '-', '_' ), ' ', sanitize_key( $key ) ) );
    }

    /**
     * Infer a label for pure number + unit tags.
     *
     * Keep this tied to the catalog text. Do not invent translated concept
     * labels; users need to see labels that exist in their data.
     *
     * @param string $unit Parsed unit.
     * @return string
     */
    private function get_default_numeric_label( $unit = '' ) {
        $unit_label = $this->normalize_unit( $unit );
        if ( '' !== $unit_label ) {
            return $unit_label;
        }

        $field = trim( (string) get_option( 'sschp_tag_fields', '' ) );

        if ( '' === $field ) {
            return __( 'Value', 'smart-search-chips' );
        }

        $field = preg_replace( '/[_\-]+/', ' ', $field );

        return ucwords( sanitize_text_field( $field ) );
    }

    /**
     * Format a float for stable transport.
     *
     * @param float|string $number Number.
     * @return string
     */
    private function format_number( $number ) {
        $number = (float) $number;
        $value  = rtrim( rtrim( number_format( $number, 4, '.', '' ), '0' ), '.' );

        return '-0' === $value ? '0' : $value;
    }

    /**
     * Determine decimal precision in a numeric string.
     *
     * @param string $number Number.
     * @return int
     */
    private function decimal_precision( $number ) {
        $number = str_replace( ',', '.', (string) $number );
        $parts  = explode( '.', $number );

        return isset( $parts[1] ) ? strlen( rtrim( $parts[1], '0' ) ) : 0;
    }
}
