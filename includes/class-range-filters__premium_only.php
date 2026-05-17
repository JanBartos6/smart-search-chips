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
        add_action( 'admin_menu', array( $this, 'add_debug_page' ) );
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
     * Register the admin-only range diagnostics page.
     */
    public function add_debug_page() {
        if ( ! function_exists( 'add_submenu_page' ) ) {
            return;
        }

        add_submenu_page(
            'options-general.php',
            __( 'Search Chips Range Debug', 'smart-search-chips' ),
            __( 'Range Debug', 'smart-search-chips' ),
            'manage_options',
            'smart-search-chips-range-debug',
            array( $this, 'render_debug_page' )
        );
    }

    /**
     * Render admin diagnostics for numeric fact extraction.
     */
    public function render_debug_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to view this page.', 'smart-search-chips' ) );
        }

        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
        $raw_ids        = '';
        if ( 'POST' === $request_method ) {
            check_admin_referer( 'sschp_range_debug', 'sschp_range_debug_nonce' );
            $raw_ids = isset( $_POST['sschp_range_debug_post_ids'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sschp_range_debug_post_ids'] ) ) : '';
        }

        $post_ids   = $this->parse_debug_post_ids( $raw_ids );
        $posts_data = $this->build_debug_posts_data( $post_ids );
        $report     = ! empty( $posts_data ) ? $this->get_debug_report( $posts_data ) : array(
            'products' => array(),
            'groups'   => array(),
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Smart Search Chips Range Debug', 'smart-search-chips' ); ?></h1>
            <p><?php esc_html_e( 'Enter product or post IDs to inspect detected numeric facts and why range groups are accepted or rejected.', 'smart-search-chips' ); ?></p>
            <form method="post">
                <?php wp_nonce_field( 'sschp_range_debug', 'sschp_range_debug_nonce' ); ?>
                <textarea name="sschp_range_debug_post_ids" rows="4" class="large-text" placeholder="123, 124, 125"><?php echo esc_textarea( $raw_ids ); ?></textarea>
                <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Inspect Numeric Facts', 'smart-search-chips' ); ?></button></p>
            </form>

            <?php if ( ! empty( $posts_data ) ) : ?>
                <h2><?php esc_html_e( 'Detected Facts', 'smart-search-chips' ); ?></h2>
                <?php foreach ( $report['products'] as $product ) : ?>
                    <h3><?php echo esc_html( sprintf( __( 'Post #%d', 'smart-search-chips' ), $product['post_id'] ) ); ?></h3>
                    <?php if ( empty( $product['facts'] ) && empty( $product['rejected'] ) ) : ?>
                        <p><?php esc_html_e( 'No numeric facts detected.', 'smart-search-chips' ); ?></p>
                    <?php endif; ?>
                    <?php if ( ! empty( $product['facts'] ) ) : ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Label', 'smart-search-chips' ); ?></th>
                                    <th><?php esc_html_e( 'Value', 'smart-search-chips' ); ?></th>
                                    <th><?php esc_html_e( 'Unit', 'smart-search-chips' ); ?></th>
                                    <th><?php esc_html_e( 'Source', 'smart-search-chips' ); ?></th>
                                    <th><?php esc_html_e( 'Confidence', 'smart-search-chips' ); ?></th>
                                    <th><?php esc_html_e( 'Raw Text', 'smart-search-chips' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $product['facts'] as $fact ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $fact['label'] ); ?></td>
                                        <td><?php echo esc_html( $this->format_number( $fact['value'] ) ); ?></td>
                                        <td><?php echo esc_html( $fact['unit'] ); ?></td>
                                        <td><?php echo esc_html( $fact['source'] . ( '' !== $fact['source_field'] ? ':' . $fact['source_field'] : '' ) ); ?></td>
                                        <td><?php echo esc_html( (string) $fact['confidence'] ); ?></td>
                                        <td><?php echo esc_html( $fact['raw'] ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                    <?php if ( ! empty( $product['rejected'] ) ) : ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Rejected Text', 'smart-search-chips' ); ?></th>
                                    <th><?php esc_html_e( 'Reason', 'smart-search-chips' ); ?></th>
                                    <th><?php esc_html_e( 'Source', 'smart-search-chips' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $product['rejected'] as $rejected ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $rejected['raw'] ); ?></td>
                                        <td><?php echo esc_html( $rejected['reason'] ); ?></td>
                                        <td><?php echo esc_html( $rejected['source'] . ( '' !== $rejected['source_field'] ? ':' . $rejected['source_field'] : '' ) ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endforeach; ?>

                <h2><?php esc_html_e( 'Range Groups', 'smart-search-chips' ); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Label', 'smart-search-chips' ); ?></th>
                            <th><?php esc_html_e( 'Unit', 'smart-search-chips' ); ?></th>
                            <th><?php esc_html_e( 'Range', 'smart-search-chips' ); ?></th>
                            <th><?php esc_html_e( 'Products', 'smart-search-chips' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'smart-search-chips' ); ?></th>
                            <th><?php esc_html_e( 'Reason', 'smart-search-chips' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $report['groups'] as $group ) : ?>
                            <tr>
                                <td><?php echo esc_html( $group['label'] ); ?></td>
                                <td><?php echo esc_html( $group['unit'] ); ?></td>
                                <td><?php echo esc_html( $this->format_number( $group['min'] ) . ' - ' . $this->format_number( $group['max'] ) ); ?></td>
                                <td><?php echo esc_html( (string) $group['count'] ); ?></td>
                                <td><?php echo esc_html( $group['accepted'] ? __( 'Accepted', 'smart-search-chips' ) : __( 'Rejected', 'smart-search-chips' ) ); ?></td>
                                <td><?php echo esc_html( implode( ', ', $group['reasons'] ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
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
     * Return a structured diagnostics report for the admin debug page and tests.
     *
     * @param array $posts_data Posts data.
     * @return array
     */
    public function get_debug_report( $posts_data ) {
        $report = array(
            'products' => array(),
            'groups'   => array(),
        );
        $groups = $this->collect_numeric_groups( $posts_data, $report );

        foreach ( $groups as $group ) {
            $evaluation = $this->evaluate_group( $group );
            $report['groups'][] = array_merge(
                $group,
                array(
                    'accepted' => $evaluation['accepted'],
                    'reasons'  => $evaluation['reasons'],
                )
            );
        }

        return $report;
    }

    /**
     * Parse comma/space separated debug post IDs.
     *
     * @param string $raw_ids Raw IDs from the admin form.
     * @return array
     */
    private function parse_debug_post_ids( $raw_ids ) {
        $ids = preg_split( '/[\s,]+/', (string) $raw_ids );
        $ids = array_map( 'absint', (array) $ids );
        $ids = array_filter(
            $ids,
            function( $id ) {
                return $id > 0;
            }
        );

        return array_values( array_unique( $ids ) );
    }

    /**
     * Build minimal post data for the admin diagnostics page.
     *
     * @param array $post_ids Post IDs.
     * @return array
     */
    private function build_debug_posts_data( $post_ids ) {
        $posts_data = array();

        foreach ( (array) $post_ids as $post_id ) {
            $post_id = absint( $post_id );
            if ( $post_id <= 0 ) {
                continue;
            }

            $raw_tags = get_post_meta( $post_id, SSCHP_Meta_Box::META_KEY, true );
            $tags     = $this->split_source_text( $raw_tags );

            $posts_data[] = array(
                'id'         => $post_id,
                'tags'       => $tags,
                'tags_lower' => array_map( 'mb_strtolower', $tags ),
            );
        }

        return $posts_data;
    }

    /**
     * Build range metadata by scanning conservative numeric facts.
     *
     * @param array      $posts_data Posts data.
     * @param null|array $debug      Optional debug report passed by reference.
     * @return array
     */
    private function collect_numeric_groups( $posts_data, &$debug = null ) {
        $groups = array();

        foreach ( (array) $posts_data as $post ) {
            $post_id      = isset( $post['id'] ) ? absint( $post['id'] ) : 0;
            $seen_in_post = array();
            $rejected     = array();
            $facts        = $this->extract_numeric_facts_from_post( $post, $rejected );

            if ( is_array( $debug ) ) {
                $debug['products'][] = array(
                    'post_id'  => $post_id,
                    'facts'    => $facts,
                    'rejected' => $rejected,
                );
            }

            foreach ( $facts as $fact ) {
                $group_id = $fact['group_key'] . '|' . $fact['unit_key'];
                if ( ! isset( $groups[ $group_id ] ) ) {
                    $groups[ $group_id ] = array(
                        'label'            => $fact['label'],
                        'group_key'        => $fact['group_key'],
                        'unit'             => $fact['unit'],
                        'unit_key'         => $fact['unit_key'],
                        'min'              => $fact['value'],
                        'max'              => $fact['value'],
                        'count'            => 0,
                        'precision'        => $fact['precision'],
                        'values'           => array(),
                        'tag_keys'         => array(),
                        'sources'          => array(),
                        'confidence_total' => 0,
                    );
                }

                $groups[ $group_id ]['min']              = min( $groups[ $group_id ]['min'], $fact['value'] );
                $groups[ $group_id ]['max']              = max( $groups[ $group_id ]['max'], $fact['value'] );
                $groups[ $group_id ]['precision']        = max( $groups[ $group_id ]['precision'], $fact['precision'] );
                $groups[ $group_id ]['values'][]         = $fact['value'];
                $groups[ $group_id ]['confidence_total'] += (int) $fact['confidence'];
                $groups[ $group_id ]['sources'][ $fact['source'] ] = true;

                if ( ! empty( $fact['tag_key'] ) ) {
                    $groups[ $group_id ]['tag_keys'][ $fact['tag_key'] ] = $fact['tag_key'];
                }

                if ( ! isset( $seen_in_post[ $group_id ] ) ) {
                    $groups[ $group_id ]['count']++;
                    $seen_in_post[ $group_id ] = true;
                }
            }
        }

        foreach ( $groups as &$group ) {
            $value_count         = max( 1, count( $group['values'] ) );
            $group['confidence'] = (int) round( $group['confidence_total'] / $value_count );
            $group['sources']    = array_keys( $group['sources'] );
            $group['tag_keys']   = array_values( $group['tag_keys'] );
            unset( $group['confidence_total'] );
        }
        unset( $group );

        return array_values( $groups );
    }

    /**
     * Determine whether a numeric group has enough variation to be a range chip.
     *
     * @param array $group Numeric group.
     * @return bool
     */
    private function group_is_filterable( $group ) {
        $evaluation = $this->evaluate_group( $group );

        return $evaluation['accepted'];
    }

    /**
     * Evaluate a group and keep rejected reasons available for diagnostics.
     *
     * @param array $group Numeric group.
     * @return array
     */
    private function evaluate_group( $group ) {
        $reasons      = array();
        $min_products = (int) apply_filters( 'sschp_range_min_products', 2, $group );
        $min_products = max( 2, $min_products );
        $min_conf     = (int) apply_filters( 'sschp_range_min_confidence', 65, $group );
        $values       = isset( $group['values'] ) ? (array) $group['values'] : array();

        if ( empty( $values ) ) {
            $reasons[] = 'no numeric values';
        }

        if ( (int) $group['count'] < $min_products ) {
            $reasons[] = 'not enough matching products';
        }

        $distinct = array_unique( array_map( array( $this, 'format_number' ), $values ) );
        if ( count( $distinct ) < 2 || $group['max'] <= $group['min'] ) {
            $reasons[] = 'range would not filter results';
        }

        if ( isset( $group['confidence'] ) && (int) $group['confidence'] < $min_conf ) {
            $reasons[] = 'confidence below threshold';
        }

        return array(
            'accepted' => empty( $reasons ),
            'reasons'  => $reasons,
        );
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
        $rejected = array();
        $facts    = $this->extract_numeric_facts_from_post( $post, $rejected );

        foreach ( $facts as $fact ) {
            if ( $fact['group_key'] !== $range_filter['group_key'] || $fact['unit_key'] !== $range_filter['unit_key'] ) {
                continue;
            }

            if ( $fact['value'] >= $range_filter['selected_min'] && $fact['value'] <= $range_filter['selected_max'] ) {
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
        $rejected = array();
        $facts    = $this->extract_numeric_facts_from_text(
            $tag,
            array(
                'source'       => 'chip_tag',
                'source_field' => '',
                'confidence'   => 75,
            ),
            $rejected
        );

        if ( empty( $facts ) ) {
            return null;
        }

        $fact = reset( $facts );

        return array(
            'label'     => $fact['label'],
            'group_key' => $fact['group_key'],
            'unit'      => $fact['unit'],
            'unit_key'  => $fact['unit_key'],
            'value'     => $fact['value'],
            'precision' => $fact['precision'],
        );
    }

    /**
     * Extract numeric facts from all known sources for one result.
     *
     * @param array $post     Post data from the search pipeline.
     * @param array $rejected Rejected candidates passed by reference.
     * @return array
     */
    private function extract_numeric_facts_from_post( $post, &$rejected = array() ) {
        $post_id = isset( $post['id'] ) ? absint( $post['id'] ) : 0;
        $facts   = array();
        $sources = $this->collect_numeric_sources( $post, $post_id );

        foreach ( $sources as $source ) {
            foreach ( $this->split_source_text( $source['text'] ) as $text ) {
                $facts = array_merge(
                    $facts,
                    $this->extract_numeric_facts_from_text(
                        $text,
                        array(
                            'post_id'      => $post_id,
                            'source'       => $source['source'],
                            'source_field' => $source['source_field'],
                            'confidence'   => $source['confidence'],
                            'raw_source'   => $source['text'],
                        ),
                        $rejected
                    )
                );
            }
        }

        return $this->dedupe_facts( $facts );
    }

    /**
     * Collect source texts ordered from structured to fallback content.
     *
     * @param array $post    Post data.
     * @param int   $post_id Post ID.
     * @return array
     */
    private function collect_numeric_sources( $post, $post_id ) {
        $sources = array();

        if ( $post_id > 0 ) {
            $sources = array_merge( $sources, $this->collect_woocommerce_attribute_sources( $post_id ) );
            $sources = array_merge( $sources, $this->collect_mapped_field_sources( $post_id ) );
            $sources = array_merge( $sources, $this->collect_product_meta_sources( $post_id ) );
            $sources = array_merge( $sources, $this->collect_taxonomy_attribute_sources( $post_id ) );
        }

        $tags = isset( $post['tags'] ) && is_array( $post['tags'] ) ? $post['tags'] : array();
        foreach ( $tags as $tag ) {
            $sources[] = array(
                'text'         => $tag,
                'source'       => 'chip_tag',
                'source_field' => '',
                'confidence'   => 75,
            );
        }

        foreach ( $this->collect_fallback_text_sources( $post, $post_id ) as $source ) {
            $sources[] = $source;
        }

        return $sources;
    }

    /**
     * Collect WooCommerce attribute sources.
     *
     * @param int $post_id Post ID.
     * @return array
     */
    private function collect_woocommerce_attribute_sources( $post_id ) {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return array();
        }

        $product = wc_get_product( $post_id );
        if ( ! $product || ! method_exists( $product, 'get_attributes' ) ) {
            return array();
        }

        $sources = array();
        foreach ( (array) $product->get_attributes() as $attribute ) {
            if ( ! is_object( $attribute ) || ! method_exists( $attribute, 'get_name' ) ) {
                continue;
            }

            $field_name = $attribute->get_name();
            $values     = array();

            if ( method_exists( $attribute, 'is_taxonomy' ) && $attribute->is_taxonomy() && function_exists( 'wp_get_post_terms' ) ) {
                $terms = wp_get_post_terms( $post_id, $field_name, array( 'fields' => 'names' ) );
                if ( ! is_wp_error( $terms ) ) {
                    $values = (array) $terms;
                }
            } elseif ( method_exists( $attribute, 'get_options' ) ) {
                $values = (array) $attribute->get_options();
            }

            foreach ( $values as $value ) {
                $text = SSCHP_Field_Normalizer::normalize(
                    $value,
                    array(
                        'post_id'    => $post_id,
                        'field_name' => $field_name,
                        'source'     => 'woocommerce_attribute',
                    )
                );

                if ( '' !== $text ) {
                    $sources[] = array(
                        'text'         => $text,
                        'source'       => 'woocommerce_attribute',
                        'source_field' => $field_name,
                        'confidence'   => 90,
                    );
                }
            }
        }

        return $sources;
    }

    /**
     * Collect mapped ACF/custom field sources.
     *
     * @param int $post_id Post ID.
     * @return array
     */
    private function collect_mapped_field_sources( $post_id ) {
        $field_names = array_filter(
            array_unique(
                array_map(
                    'sanitize_key',
                    array(
                        get_option( 'sschp_tag_fields', '' ),
                        get_option( 'sschp_extra_fields', '' ),
                    )
                )
            )
        );
        $sources = array();

        foreach ( $field_names as $field_name ) {
            $value = null;
            if ( function_exists( 'get_field' ) ) {
                $value = get_field( $field_name, $post_id );
            }

            $text = SSCHP_Field_Normalizer::normalize(
                $value,
                array(
                    'post_id'    => $post_id,
                    'field_name' => $field_name,
                    'source'     => 'acf',
                )
            );

            if ( '' === $text ) {
                $text = SSCHP_Field_Normalizer::normalize(
                    get_post_meta( $post_id, $field_name, true ),
                    array(
                        'post_id'    => $post_id,
                        'field_name' => $field_name,
                        'source'     => 'post_meta',
                    )
                );
            }

            if ( '' !== $text ) {
                $sources[] = array(
                    'text'         => $text,
                    'source'       => 'mapped_field',
                    'source_field' => $field_name,
                    'confidence'   => 92,
                );
            }
        }

        return $sources;
    }

    /**
     * Collect conservative product meta sources.
     *
     * @param int $post_id Post ID.
     * @return array
     */
    private function collect_product_meta_sources( $post_id ) {
        if ( function_exists( 'get_post_type' ) && 'product' !== get_post_type( $post_id ) ) {
            return array();
        }

        $sources = array();
        foreach ( array( '_price', '_regular_price', '_sale_price' ) as $meta_key ) {
            $value = get_post_meta( $post_id, $meta_key, true );
            if ( '' === trim( (string) $value ) || ! is_numeric( $value ) ) {
                continue;
            }

            $currency = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$';
            $sources[] = array(
                'text'         => $currency . $value,
                'source'       => 'product_meta',
                'source_field' => $meta_key,
                'confidence'   => 88,
            );
        }

        return $sources;
    }

    /**
     * Collect taxonomy attribute sources, primarily pa_* product attributes.
     *
     * @param int $post_id Post ID.
     * @return array
     */
    private function collect_taxonomy_attribute_sources( $post_id ) {
        if ( ! function_exists( 'get_object_taxonomies' ) || ! function_exists( 'wp_get_post_terms' ) ) {
            return array();
        }

        $post_type  = function_exists( 'get_post_type' ) ? get_post_type( $post_id ) : 'post';
        $taxonomies = get_object_taxonomies( $post_type, 'names' );
        $sources    = array();

        foreach ( (array) $taxonomies as $taxonomy ) {
            if ( 0 !== strpos( (string) $taxonomy, 'pa_' ) ) {
                continue;
            }

            $terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
            if ( is_wp_error( $terms ) ) {
                continue;
            }

            foreach ( (array) $terms as $term ) {
                $text = SSCHP_Field_Normalizer::normalize(
                    $term,
                    array(
                        'post_id'    => $post_id,
                        'field_name' => $taxonomy,
                        'source'     => 'taxonomy_attribute',
                    )
                );

                if ( '' !== $text ) {
                    $sources[] = array(
                        'text'         => $text,
                        'source'       => 'taxonomy_attribute',
                        'source_field' => $taxonomy,
                        'confidence'   => 86,
                    );
                }
            }
        }

        return $sources;
    }

    /**
     * Collect title/content fallback sources.
     *
     * @param array $post    Post data.
     * @param int   $post_id Post ID.
     * @return array
     */
    private function collect_fallback_text_sources( $post, $post_id ) {
        $sources = array();

        if ( ! empty( $post['title'] ) ) {
            $sources[] = array(
                'text'         => $post['title'],
                'source'       => 'title',
                'source_field' => 'post_title',
                'confidence'   => 64,
            );
        }

        $wp_post = $post_id > 0 && function_exists( 'get_post' ) ? get_post( $post_id ) : null;
        if ( $wp_post && isset( $wp_post->post_title ) && empty( $post['title'] ) ) {
            $sources[] = array(
                'text'         => $wp_post->post_title,
                'source'       => 'title',
                'source_field' => 'post_title',
                'confidence'   => 64,
            );
        }

        if ( $wp_post && ! empty( $wp_post->post_content ) ) {
            $sources[] = array(
                'text'         => $wp_post->post_content,
                'source'       => 'content',
                'source_field' => 'post_content',
                'confidence'   => 55,
            );
        }

        return $sources;
    }

    /**
     * Extract numeric facts from one normalized text value.
     *
     * @param string $text     Source text.
     * @param array  $context  Source context.
     * @param array  $rejected Rejected candidates passed by reference.
     * @return array
     */
    private function extract_numeric_facts_from_text( $text, $context, &$rejected = array() ) {
        $text = trim( wp_strip_all_tags( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ) ) );
        if ( '' === $text ) {
            return array();
        }

        $this->record_rejected_patterns( $text, $context, $rejected );

        $facts      = array();
        $candidates = array_merge(
            $this->match_currency_candidates( $text ),
            $this->match_unit_candidates( $text ),
            $this->match_inferred_unit_candidates( $text, $context )
        );
        $seen       = array();

        foreach ( $candidates as $candidate ) {
            $seen_key = $candidate['number'] . '|' . $candidate['unit'] . '|' . $candidate['raw'];
            if ( isset( $seen[ $seen_key ] ) ) {
                continue;
            }
            $seen[ $seen_key ] = true;

            $fact = $this->build_numeric_fact( $candidate, $text, $context, $rejected );
            if ( $fact ) {
                $facts[] = $fact;
            }
        }

        return $facts;
    }

    /**
     * Match prefix currency candidates.
     *
     * @param string $text Text.
     * @return array
     */
    private function match_currency_candidates( $text ) {
        if ( ! preg_match_all( '/(?P<unit>\$|€|£|Kč|CZK|USD|EUR)\s*(?P<number>[-+]?\d+(?:[\.,]\d+)?)/iu', $text, $matches, PREG_SET_ORDER ) ) {
            return array();
        }

        $candidates = array();
        foreach ( $matches as $match ) {
            $candidates[] = array(
                'number' => $match['number'],
                'unit'   => $match['unit'],
                'raw'    => $match[0],
                'before' => $this->text_before_match( $text, $match[0] ),
                'after'  => '',
            );
        }

        return $candidates;
    }

    /**
     * Match number + explicit unit candidates.
     *
     * @param string $text Text.
     * @return array
     */
    private function match_unit_candidates( $text ) {
        $pattern = '/(?<![\pL\pN])(?P<number>[-+]?\d+(?:[\.,]\d+)?)\s*(?P<unit>°\s*C|°C|Kč|CZK|USD|EUR|[\pLµμ%]+(?:\s*(?:AC|DC))?)(?![\pL\pN])/iu';
        if ( ! preg_match_all( $pattern, $text, $matches, PREG_SET_ORDER ) ) {
            return array();
        }

        $candidates = array();
        foreach ( $matches as $match ) {
            $candidates[] = array(
                'number' => $match['number'],
                'unit'   => $match['unit'],
                'raw'    => $match[0],
                'before' => $this->text_before_match( $text, $match[0] ),
                'after'  => '',
            );
        }

        return $candidates;
    }

    /**
     * Match bare numeric values only when the source field implies a unit.
     *
     * @param string $text    Text.
     * @param array  $context Source context.
     * @return array
     */
    private function match_inferred_unit_candidates( $text, $context ) {
        $unit_data = $this->normalize_unit_data( '', isset( $context['source_field'] ) ? $context['source_field'] : '', $text );
        if ( empty( $unit_data['unit_key'] ) || 'none' === $unit_data['unit_key'] ) {
            return array();
        }

        if ( ! preg_match_all( '/(?<![\pL\pN])(?P<number>[-+]?\d+(?:[\.,]\d+)?)(?![\pL\pN])/u', $text, $matches, PREG_SET_ORDER ) ) {
            return array();
        }

        $candidates = array();
        foreach ( $matches as $match ) {
            $candidates[] = array(
                'number' => $match['number'],
                'unit'   => $unit_data['raw_unit'],
                'raw'    => $match[0],
                'before' => $this->text_before_match( $text, $match[0] ),
                'after'  => '',
            );
        }

        return $candidates;
    }

    /**
     * Build and validate one numeric fact.
     *
     * @param array  $candidate Candidate data.
     * @param string $text      Full source text.
     * @param array  $context   Source context.
     * @param array  $rejected  Rejected candidates passed by reference.
     * @return null|array
     */
    private function build_numeric_fact( $candidate, $text, $context, &$rejected ) {
        $number = str_replace( ',', '.', $candidate['number'] );
        if ( ! is_numeric( $number ) ) {
            $this->reject_candidate( $rejected, $candidate, $context, 'invalid number' );
            return null;
        }

        $unit_data = $this->normalize_unit_data(
            $candidate['unit'],
            isset( $context['source_field'] ) ? $context['source_field'] : '',
            $text
        );
        if ( ! $unit_data || 'none' === $unit_data['unit_key'] ) {
            $this->reject_candidate( $rejected, $candidate, $context, 'missing or unsupported unit' );
            return null;
        }

        $value = (float) $number * (float) $unit_data['factor'];
        $label = $this->derive_fact_label( $candidate, $context, $unit_data );
        if ( '' === $label ) {
            $this->reject_candidate( $rejected, $candidate, $context, 'missing semantic label' );
            return null;
        }

        $reason = $this->reject_reason_for_fact( $candidate, $text, $context, $unit_data, $value, $label );
        if ( '' !== $reason ) {
            $this->reject_candidate( $rejected, $candidate, $context, $reason );
            return null;
        }

        $precision = max( $this->decimal_precision( $number ), $this->decimal_precision( $this->format_number( $value ) ) );
        $fact      = array(
            'value'        => $value,
            'unit'         => $unit_data['display'],
            'unit_key'     => $unit_data['unit_key'],
            'label'        => sanitize_text_field( $label ),
            'group_key'    => $this->label_to_key( $label ),
            'source'       => sanitize_key( isset( $context['source'] ) ? $context['source'] : 'unknown' ),
            'source_field' => sanitize_key( isset( $context['source_field'] ) ? $context['source_field'] : '' ),
            'confidence'   => $this->score_numeric_fact( $context, $unit_data, $candidate, $label ),
            'raw'          => sanitize_text_field( $candidate['raw'] ),
            'tag_key'      => 'chip_tag' === ( isset( $context['source'] ) ? $context['source'] : '' ) ? mb_strtolower( trim( (string) $text ) ) : '',
            'precision'    => $precision,
        );

        $filtered = apply_filters( 'sschp_numeric_fact', $fact, $candidate, $context, $text );

        return is_array( $filtered ) ? $filtered : $fact;
    }

    /**
     * Normalize a parsed measurement unit.
     *
     * @param string $unit Parsed unit.
     * @return string
     */
    private function normalize_unit( $unit ) {
        $unit_data = $this->normalize_unit_data( $unit );

        return $unit_data ? $unit_data['display'] : trim( (string) $unit );
    }

    /**
     * Normalize unit aliases and convert compatible units to a base unit.
     *
     * @param string $unit         Parsed unit.
     * @param string $source_field Source field name.
     * @param string $raw          Raw source text.
     * @return null|array
     */
    private function normalize_unit_data( $unit, $source_field = '', $raw = '' ) {
        $raw_unit = trim( (string) $unit );
        if ( '' === $raw_unit ) {
            $raw_unit = $this->infer_unit_from_source_field( $source_field );
        }

        $raw_unit = trim( $raw_unit, ".:;()[]{}" );
        $raw_unit = str_replace( array( 'μ' ), 'µ', $raw_unit );
        $raw_unit = preg_replace( '/\s+/', ' ', $raw_unit );

        if ( '' === $raw_unit ) {
            return null;
        }

        $unit_lc = mb_strtolower( $raw_unit );
        $compact = str_replace( array( ' ', '.', '-', '_' ), '', $unit_lc );
        $compact = str_replace( array( 'µ', 'μ', '²', '³' ), array( 'u', 'u', '2', '3' ), $compact );

        $currency = $this->normalize_currency_unit( $raw_unit );
        if ( $currency ) {
            return array(
                'raw_unit' => $raw_unit,
                'display'  => $currency['display'],
                'unit_key' => $currency['unit_key'],
                'factor'   => 1,
            );
        }

        $map = array(
            'w'     => array( 'W', 'w', 1 ),
            'watt'  => array( 'W', 'w', 1 ),
            'watts' => array( 'W', 'w', 1 ),
            'kw'    => array( 'W', 'w', 1000 ),
            'mw'    => array( 'W', 'w', 0.001 ),
            'v'     => array( 'V', 'v', 1 ),
            'vac'   => array( 'V AC', 'v_ac', 1 ),
            'vdc'   => array( 'V DC', 'v_dc', 1 ),
            'mm'    => array( 'mm', 'mm', 1 ),
            'cm'    => array( 'mm', 'mm', 10 ),
            'm'     => array( 'mm', 'mm', 1000 ),
            'g'     => array( 'g', 'g', 1 ),
            'kg'    => array( 'g', 'g', 1000 ),
            'c'     => array( '°C', 'degc', 1 ),
            'degc'  => array( '°C', 'degc', 1 ),
            'oc'    => array( '°C', 'degc', 1 ),
        );

        if ( preg_match( '/^watt(?:s|u|ů|y|ech|ům|em)?$/u', $compact ) ) {
            $compact = 'watt';
        } elseif ( preg_match( '/^volt(?:s|u|ů|y|ech|ům|em)?$/u', $compact ) ) {
            $compact = 'v';
        } elseif ( preg_match( '/^v(?:ac)$/u', $compact ) ) {
            $compact = 'vac';
        } elseif ( preg_match( '/^v(?:dc)$/u', $compact ) ) {
            $compact = 'vdc';
        } elseif ( preg_match( '/^(?:°c|degc|celsius)$/u', $compact ) ) {
            $compact = 'degc';
        }

        if ( ! isset( $map[ $compact ] ) ) {
            return null;
        }

        $data = array(
            'raw_unit' => $raw_unit,
            'display'  => $map[ $compact ][0],
            'unit_key' => $map[ $compact ][1],
            'factor'   => $map[ $compact ][2],
        );

        return $this->is_supported_unit( $data['display'], '', $raw ) ? $data : null;
    }

    /**
     * Normalize supported currency aliases.
     *
     * @param string $unit Unit.
     * @return null|array
     */
    private function normalize_currency_unit( $unit ) {
        $unit_lc = mb_strtolower( trim( (string) $unit ) );
        $map     = array(
            '$'   => array( '$', 'currency_usd' ),
            'usd' => array( 'USD', 'currency_usd' ),
            '€'   => array( '€', 'currency_eur' ),
            'eur' => array( 'EUR', 'currency_eur' ),
            '£'   => array( '£', 'currency_gbp' ),
            'kč'  => array( 'Kč', 'currency_czk' ),
            'czk' => array( 'CZK', 'currency_czk' ),
        );

        if ( ! isset( $map[ $unit_lc ] ) ) {
            return null;
        }

        return array(
            'display'  => $map[ $unit_lc ][0],
            'unit_key' => $map[ $unit_lc ][1],
        );
    }

    /**
     * Infer a unit from structured field names such as power_w or length_cm.
     *
     * @param string $source_field Source field.
     * @return string
     */
    private function infer_unit_from_source_field( $source_field ) {
        $field = mb_strtolower( (string) $source_field );
        $field = trim( str_replace( array( '-', ' ' ), '_', $field ), '_' );

        $suffixes = array(
            '_watt' => 'W',
            '_watts' => 'W',
            '_w' => 'W',
            '_vac' => 'V AC',
            '_v_ac' => 'V AC',
            '_vdc' => 'V DC',
            '_v_dc' => 'V DC',
            '_v' => 'V',
            '_mm' => 'mm',
            '_cm' => 'cm',
            '_m' => 'm',
            '_kg' => 'kg',
            '_g' => 'g',
            '_c' => '°C',
            '_degc' => '°C',
        );

        foreach ( $suffixes as $suffix => $unit ) {
            if ( substr( $field, -1 * strlen( $suffix ) ) === $suffix ) {
                return $unit;
            }
        }

        if ( in_array( $field, array( '_price', 'price', 'regular_price', 'sale_price' ), true ) ) {
            return function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$';
        }

        return '';
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
                'w',
                'v', 'v_ac', 'v_dc',
                'mm',
                'g',
                'degc',
                'currency_usd', 'currency_eur', 'currency_gbp', 'currency_czk',
            ),
            $tag,
            $label,
            $unit
        );

        return 0 === strpos( $normalized, 'currency_' ) || in_array( $normalized, array_map( 'sanitize_key', (array) $allowed ), true );
    }

    /**
     * Split normalized field/tag source text into candidate values.
     *
     * @param mixed $text Source text.
     * @return array
     */
    private function split_source_text( $text ) {
        $parts = preg_split( '/[;\r\n]+/', (string) $text );
        $parts = array_map( 'trim', (array) $parts );
        $parts = array_filter(
            $parts,
            function( $part ) {
                return '' !== $part;
            }
        );

        return array_values( $parts );
    }

    /**
     * Add explicit debug entries for known unsafe numeric patterns.
     *
     * @param string $text     Source text.
     * @param array  $context  Source context.
     * @param array  $rejected Rejected candidates passed by reference.
     * @return void
     */
    private function record_rejected_patterns( $text, $context, &$rejected ) {
        if ( preg_match_all( '/\bIP\s*[-:]?\s*\d{2}\b/iu', $text, $matches ) ) {
            foreach ( $matches[0] as $match ) {
                $this->reject_candidate(
                    $rejected,
                    array(
                        'raw'    => $match,
                        'number' => '',
                        'unit'   => '',
                    ),
                    $context,
                    'IP ratings are categorical'
                );
            }
        }

        if ( preg_match_all( '/\b[A-Z]{1,6}[-_ ]?\d{2,}\b/u', $text, $matches ) ) {
            foreach ( $matches[0] as $match ) {
                $this->reject_candidate(
                    $rejected,
                    array(
                        'raw'    => $match,
                        'number' => '',
                        'unit'   => '',
                    ),
                    $context,
                    'model or SKU-like number'
                );
            }
        }
    }

    /**
     * Return text before the first match occurrence.
     *
     * @param string $text  Full text.
     * @param string $match Matched text.
     * @return string
     */
    private function text_before_match( $text, $match ) {
        $position = strpos( $text, $match );
        if ( false === $position ) {
            return '';
        }

        return trim( substr( $text, 0, $position ) );
    }

    /**
     * Derive semantic range label from field names or explicit text context.
     *
     * @param array $candidate Candidate.
     * @param array $context   Source context.
     * @param array $unit_data Unit data.
     * @return string
     */
    private function derive_fact_label( $candidate, $context, $unit_data ) {
        $source_field = isset( $context['source_field'] ) ? sanitize_key( $context['source_field'] ) : '';
        if ( '' !== $source_field && ! in_array( $source_field, array( 'post_title', 'post_content' ), true ) ) {
            return $this->field_name_to_label( $source_field, $unit_data );
        }

        $before = isset( $candidate['before'] ) ? trim( (string) $candidate['before'] ) : '';
        if ( '' !== $before && preg_match( '/[:=]\s*([^:=]+)$/u', $before, $match ) ) {
            return $this->clean_label( $match[1], $unit_data );
        }

        if ( '' !== $before && preg_match( '/^([^:=]+)\s*[:=]\s*$/u', $before, $match ) ) {
            return $this->clean_label( $match[1], $unit_data );
        }

        return $unit_data['display'];
    }

    /**
     * Convert a source field name into a readable label without unit suffixes.
     *
     * @param string $field_name Field name.
     * @param array  $unit_data  Unit data.
     * @return string
     */
    private function field_name_to_label( $field_name, $unit_data ) {
        $field = preg_replace( '/^pa_/', '', sanitize_key( $field_name ) );
        $field = preg_replace( '/^_+/', '', $field );

        $unit_suffixes = array(
            'watt', 'watts', 'w',
            'vac', 'v_ac', 'vdc', 'v_dc', 'v',
            'mm', 'cm', 'm',
            'kg', 'g',
            'degc', 'c',
        );

        foreach ( $unit_suffixes as $suffix ) {
            $field = preg_replace( '/[_\-]' . preg_quote( $suffix, '/' ) . '$/u', '', $field );
        }

        if ( in_array( $field, array( 'price', 'regular_price', 'sale_price' ), true ) ) {
            return __( 'Price', 'smart-search-chips' );
        }

        $label = trim( preg_replace( '/[_\-]+/', ' ', $field ) );

        return '' !== $label ? ucwords( sanitize_text_field( $label ) ) : $unit_data['display'];
    }

    /**
     * Clean a text-derived label.
     *
     * @param string $label     Label.
     * @param array  $unit_data Unit data.
     * @return string
     */
    private function clean_label( $label, $unit_data ) {
        $label = trim( wp_strip_all_tags( (string) $label ) );
        $label = trim( $label, " \t\n\r\0\x0B:-=,;()[]{}" );

        if ( '' === $label || preg_match( '/\d/', $label ) ) {
            return $unit_data['display'];
        }

        return sanitize_text_field( $label );
    }

    /**
     * Decide whether a fact candidate should be rejected even if it has a unit.
     *
     * @param array  $candidate Candidate data.
     * @param string $text      Source text.
     * @param array  $context   Source context.
     * @param array  $unit_data Unit data.
     * @param float  $value     Normalized value.
     * @param string $label     Label.
     * @return string Empty string when accepted.
     */
    private function reject_reason_for_fact( $candidate, $text, $context, $unit_data, $value, $label ) {
        $source_field = isset( $context['source_field'] ) ? sanitize_key( $context['source_field'] ) : '';
        $source       = isset( $context['source'] ) ? sanitize_key( $context['source'] ) : '';
        $raw          = isset( $candidate['raw'] ) ? (string) $candidate['raw'] : '';

        if ( preg_match( '/\bIP\s*[-:]?\s*\d{2}\b/iu', $text ) || 'ip' === $this->label_to_key( $label ) ) {
            return 'IP ratings are categorical';
        }

        if ( preg_match( '/(?:^|[_\-])(sku|model|part|code|kod|serial)(?:$|[_\-])/u', $source_field ) ) {
            return 'model or SKU-like number';
        }

        if ( in_array( $source, array( 'title', 'content' ), true ) && preg_match( '/\b[A-Z]{1,6}[-_ ]?\d{2,}\b/u', $text ) && ! preg_match( '/\d+\s*' . preg_quote( $unit_data['raw_unit'], '/' ) . '/iu', $raw ) ) {
            return 'model or SKU-like number';
        }

        if ( $value >= 1900 && $value <= 2099 && in_array( $source, array( 'title', 'content' ), true ) && ! in_array( $unit_data['unit_key'], array( 'w', 'v', 'v_ac', 'v_dc', 'mm', 'g', 'degc' ), true ) ) {
            return 'year-like number';
        }

        return '';
    }

    /**
     * Store a rejected candidate for diagnostics.
     *
     * @param array $rejected  Rejected candidates.
     * @param array $candidate Candidate.
     * @param array $context   Source context.
     * @param string $reason   Rejection reason.
     * @return void
     */
    private function reject_candidate( &$rejected, $candidate, $context, $reason ) {
        $rejected[] = array(
            'raw'          => sanitize_text_field( isset( $candidate['raw'] ) ? $candidate['raw'] : '' ),
            'reason'       => sanitize_text_field( $reason ),
            'source'       => sanitize_key( isset( $context['source'] ) ? $context['source'] : 'unknown' ),
            'source_field' => sanitize_key( isset( $context['source_field'] ) ? $context['source_field'] : '' ),
        );
    }

    /**
     * Score a numeric fact based on source quality and parsing certainty.
     *
     * @param array $context   Source context.
     * @param array $unit_data Unit data.
     * @param array $candidate Candidate.
     * @param string $label    Label.
     * @return int
     */
    private function score_numeric_fact( $context, $unit_data, $candidate, $label ) {
        $score        = isset( $context['confidence'] ) ? (int) $context['confidence'] : 60;
        $source_field = isset( $context['source_field'] ) ? sanitize_key( $context['source_field'] ) : '';

        if ( '' !== $source_field && ! in_array( $source_field, array( 'post_title', 'post_content' ), true ) ) {
            $score += 4;
        }

        if ( isset( $candidate['before'] ) && preg_match( '/[:=]\s*$/u', (string) $candidate['before'] ) ) {
            $score += 5;
        }

        if ( $label === $unit_data['display'] && in_array( isset( $context['source'] ) ? $context['source'] : '', array( 'title', 'content' ), true ) ) {
            $score -= 3;
        }

        return max( 0, min( 100, $score ) );
    }

    /**
     * Dedupe equivalent facts inside one post.
     *
     * @param array $facts Facts.
     * @return array
     */
    private function dedupe_facts( $facts ) {
        $deduped = array();
        $seen    = array();

        foreach ( (array) $facts as $fact ) {
            $key = implode(
                '|',
                array(
                    isset( $fact['group_key'] ) ? $fact['group_key'] : '',
                    isset( $fact['unit_key'] ) ? $fact['unit_key'] : '',
                    isset( $fact['value'] ) ? $this->format_number( $fact['value'] ) : '',
                    isset( $fact['source_field'] ) ? $fact['source_field'] : '',
                )
            );

            if ( isset( $seen[ $key ] ) ) {
                continue;
            }

            $seen[ $key ] = true;
            $deduped[]    = $fact;
        }

        return $deduped;
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
        $unit = str_replace( array( '/', '\\' ), array( '-', '-' ), $unit );
        $unit = preg_replace( '/\s+/', ' ', $unit );

        $compact_unit = str_replace( array( ' ', '.', '-' ), '', $unit );
        $currency     = $this->normalize_currency_unit( $unit );
        if ( $currency ) {
            return $currency['unit_key'];
        }

        if ( preg_match( '/^v(?:ac)$/u', $compact_unit ) ) {
            return 'v_ac';
        }

        if ( preg_match( '/^v(?:dc)$/u', $compact_unit ) ) {
            return 'v_dc';
        }

        if ( 'v' === $compact_unit || preg_match( '/^volt(?:s|u|ů|y|ech|ům|em)?$/u', $compact_unit ) ) {
            return 'v';
        }

        if ( preg_match( '/^a(?:ac|dc)?$/u', $compact_unit ) ) {
            return 'a';
        }

        if ( 'w' === $compact_unit || preg_match( '/^watt(?:s|u|ů|y|ech|ům|em)?$/u', $compact_unit ) ) {
            return 'w';
        }

        if ( preg_match( '/^(?:amp|amps|amp[eé]r(?:u|ů|y|ech|ům|em)?|amp[eé]ry)$/u', $compact_unit ) ) {
            return 'amp';
        }

        if ( in_array( $compact_unit, array( 'mm', 'cm', 'm' ), true ) ) {
            return 'mm';
        }

        if ( in_array( $compact_unit, array( 'g', 'kg' ), true ) ) {
            return 'g';
        }

        if ( in_array( $compact_unit, array( '°c', 'oc', 'c', 'degc', 'celsius' ), true ) ) {
            return 'degc';
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
