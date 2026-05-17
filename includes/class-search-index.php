<?php
/**
 * Local search/chip index.
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCHP_Search_Index {

    const DB_VERSION        = '1';
    const DB_VERSION_OPTION = 'sschp_search_index_db_version';
    const STATUS_OPTION     = 'sschp_search_index_status';
    const TOTAL_OPTION      = 'sschp_search_index_total';
    const PROCESSED_OPTION  = 'sschp_search_index_processed';
    const CURSOR_OPTION     = 'sschp_search_index_cursor';
    const UPDATED_OPTION    = 'sschp_search_index_updated_at';
    const CRON_HOOK         = 'sschp_process_search_index_batch';
    const BATCH_SIZE        = 25;

    /** @var SSCHP_Settings */
    private $settings;

    /** @var SSCHP_Chip_Engine */
    private $engine;

    public function __construct( SSCHP_Settings $settings, SSCHP_Chip_Engine $engine ) {
        $this->settings = $settings;
        $this->engine   = $engine;
    }

    /**
     * Register index lifecycle hooks.
     */
    public function init() {
        add_action( self::CRON_HOOK, array( $this, 'process_batch' ) );
        add_action( 'save_post', array( $this, 'handle_save_post' ), 60, 3 );
        add_action( 'deleted_post', array( $this, 'delete_post' ) );
        add_action( 'set_object_terms', array( $this, 'handle_object_terms_changed' ), 10, 6 );
        add_action( 'added_post_meta', array( $this, 'handle_post_meta_changed' ), 10, 4 );
        add_action( 'updated_post_meta', array( $this, 'handle_post_meta_changed' ), 10, 4 );
        add_action( 'deleted_post_meta', array( $this, 'handle_post_meta_changed' ), 10, 4 );
        add_action( 'updated_option', array( $this, 'handle_option_changed' ), 10, 3 );
    }

    /**
     * Get the index table name.
     *
     * @return string
     */
    public static function table_name() {
        global $wpdb;

        return $wpdb->prefix . 'sschp_search_index';
    }

    /**
     * Create or update the index table.
     */
    public static function create_table() {
        global $wpdb;

        $table_name      = self::table_name();
        $charset_collate = method_exists( $wpdb, 'get_charset_collate' ) ? $wpdb->get_charset_collate() : '';

        $sql = "CREATE TABLE {$table_name} (
            object_id BIGINT(20) UNSIGNED NOT NULL,
            post_type VARCHAR(32) NOT NULL DEFAULT '',
            language_code VARCHAR(20) NOT NULL DEFAULT '',
            title_text LONGTEXT NOT NULL,
            content_text LONGTEXT NOT NULL,
            taxonomy_text LONGTEXT NOT NULL,
            meta_text LONGTEXT NOT NULL,
            chip_tags LONGTEXT NOT NULL,
            numeric_facts LONGTEXT NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (object_id, language_code),
            KEY post_type (post_type),
            KEY language_code (language_code),
            KEY updated_at (updated_at)
        ) {$charset_collate};";

        if ( ! function_exists( 'dbDelta' ) ) {
            $upgrade_path = ABSPATH . 'wp-admin/includes/upgrade.php';
            if ( file_exists( $upgrade_path ) ) {
                require_once $upgrade_path;
            }
        }

        if ( function_exists( 'dbDelta' ) ) {
            dbDelta( $sql );
        } else {
            $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }

        update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
    }

    /**
     * Queue a full index rebuild.
     *
     * @param string $reason Reason for queuing, kept filterable for diagnostics.
     */
    public static function queue_initial_index( $reason = 'manual' ) {
        $post_types = self::get_configured_post_types();
        $total      = self::count_indexable_posts( $post_types );

        update_option( self::STATUS_OPTION, 'queued', false );
        update_option( self::TOTAL_OPTION, (int) $total, false );
        update_option( self::PROCESSED_OPTION, 0, false );
        update_option( self::CURSOR_OPTION, 0, false );
        update_option( self::UPDATED_OPTION, '', false );

        do_action( 'sschp_search_index_queued', $reason, $post_types, $total );
        self::schedule_batch();
    }

    /**
     * Drop the index table.
     */
    public static function drop_table() {
        global $wpdb;

        $wpdb->query( 'DROP TABLE IF EXISTS ' . self::table_name() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        delete_option( self::DB_VERSION_OPTION );
        delete_option( self::STATUS_OPTION );
        delete_option( self::TOTAL_OPTION );
        delete_option( self::PROCESSED_OPTION );
        delete_option( self::CURSOR_OPTION );
        delete_option( self::UPDATED_OPTION );
    }

    /**
     * Clear indexed rows without deleting settings.
     */
    public static function clear_index() {
        global $wpdb;

        $wpdb->query( 'TRUNCATE TABLE ' . self::table_name() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        update_option( self::STATUS_OPTION, 'empty', false );
        update_option( self::TOTAL_OPTION, 0, false );
        update_option( self::PROCESSED_OPTION, 0, false );
        update_option( self::CURSOR_OPTION, 0, false );
        update_option( self::UPDATED_OPTION, '', false );
    }

    /**
     * Return compact progress data for the settings page.
     *
     * @return array
     */
    public static function get_progress() {
        $status    = sanitize_key( get_option( self::STATUS_OPTION, 'not_started' ) );
        $total     = max( 0, (int) get_option( self::TOTAL_OPTION, 0 ) );
        $processed = max( 0, (int) get_option( self::PROCESSED_OPTION, 0 ) );
        $percent   = $total > 0 ? min( 100, (int) floor( ( $processed / $total ) * 100 ) ) : ( 'complete' === $status ? 100 : 0 );
        $rows      = self::count_index_rows();
        $ready     = 'complete' === $status && ( $rows > 0 || 0 === $total );

        return array(
            'status'    => $status,
            'total'     => $total,
            'processed' => $processed,
            'percent'   => $percent,
            'updatedAt' => sanitize_text_field( get_option( self::UPDATED_OPTION, '' ) ),
            'rows'      => $rows,
            'ready'     => $ready,
        );
    }

    /**
     * Process one background batch.
     *
     * @param int|null $limit Optional batch size.
     */
    public function process_batch( $limit = null ) {
        $limit      = $limit ? max( 1, (int) $limit ) : self::BATCH_SIZE;
        $post_types = $this->normalize_post_types( $this->settings->get( 'post_types', array( 'post' ) ) );
        $cursor     = max( 0, (int) get_option( self::CURSOR_OPTION, 0 ) );
        $ids        = $this->get_indexable_post_ids_after( $cursor, $limit, $post_types );

        if ( empty( $ids ) ) {
            update_option( self::STATUS_OPTION, 'complete', false );
            update_option( self::UPDATED_OPTION, $this->current_mysql_time(), false );
            return;
        }

        update_option( self::STATUS_OPTION, 'processing', false );

        $processed = max( 0, (int) get_option( self::PROCESSED_OPTION, 0 ) );
        foreach ( $ids as $post_id ) {
            $post_id = absint( $post_id );
            if ( $post_id <= 0 ) {
                continue;
            }

            $this->index_post( $post_id );
            $cursor = max( $cursor, $post_id );
            $processed++;
        }

        update_option( self::CURSOR_OPTION, (int) $cursor, false );
        update_option( self::PROCESSED_OPTION, (int) $processed, false );
        self::schedule_batch();
    }

    /**
     * Index a saved post if it is eligible.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function handle_save_post( $post_id, $post = null, $update = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        $post_id = absint( $post_id );
        if ( $post_id <= 0 || $this->should_skip_save( $post_id, $post ) ) {
            return;
        }

        $post = $post ? $post : get_post( $post_id );
        if ( ! $post || ! is_object( $post ) ) {
            return;
        }

        $post_type = isset( $post->post_type ) ? sanitize_key( $post->post_type ) : get_post_type( $post_id );
        if ( ! in_array( $post_type, $this->normalize_post_types( $this->settings->get( 'post_types', array( 'post' ) ) ), true ) ) {
            $this->delete_post( $post_id );
            return;
        }

        if ( isset( $post->post_status ) && 'publish' !== $post->post_status ) {
            $this->delete_post( $post_id );
            return;
        }

        $this->index_post( $post_id, $post );
    }

    /**
     * Reindex when terms are changed.
     *
     * @param int $object_id Object ID.
     */
    public function handle_object_terms_changed( $object_id ) {
        $object_id = absint( $object_id );
        if ( $object_id > 0 ) {
            $this->handle_save_post( $object_id );
        }
    }

    /**
     * Reindex when relevant post meta changes.
     *
     * @param int    $meta_id    Meta row ID.
     * @param int    $object_id  Object ID.
     * @param string $meta_key   Meta key.
     * @param mixed  $meta_value Meta value.
     */
    public function handle_post_meta_changed( $meta_id, $object_id, $meta_key, $meta_value = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        $meta_key = sanitize_key( (string) $meta_key );

        if ( ! $this->is_indexed_meta_key( $meta_key ) ) {
            return;
        }

        $this->handle_save_post( absint( $object_id ) );
    }

    /**
     * Queue a rebuild when index-affecting settings change.
     *
     * @param string $option    Option name.
     * @param mixed  $old_value Previous value.
     * @param mixed  $value     New value.
     */
    public function handle_option_changed( $option, $old_value, $value ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        if ( $old_value === $value || ! $this->is_index_affecting_option( $option ) ) {
            return;
        }

        self::queue_initial_index( 'settings_changed' );
    }

    /**
     * Index one post immediately.
     *
     * @param int          $post_id Post ID.
     * @param WP_Post|null $post    Optional post object.
     * @return bool
     */
    public function index_post( $post_id, $post = null ) {
        global $wpdb;

        $post_id = absint( $post_id );
        $post    = $post ? $post : get_post( $post_id );

        if ( $post_id <= 0 || ! $post || ! is_object( $post ) ) {
            return false;
        }

        $post_type = isset( $post->post_type ) ? sanitize_key( $post->post_type ) : get_post_type( $post_id );
        if ( ! in_array( $post_type, $this->normalize_post_types( $this->settings->get( 'post_types', array( 'post' ) ) ), true ) ) {
            return false;
        }

        if ( isset( $post->post_status ) && 'publish' !== $post->post_status ) {
            $this->delete_post( $post_id );
            return false;
        }

        $language_code = SSCHP_Language::current_language();
        $tag_source    = $this->get_post_tag_source( $post_id );
        $chip_tags     = trim( implode( '; ', array_filter( array( $tag_source['primary'], $tag_source['extra'] ) ) ) );
        $taxonomy_text = $this->get_taxonomy_text( $post_id, $post_type );
        $meta_text     = $this->get_meta_text( $post_id, $tag_source );
        $title_text    = isset( $post->post_title ) ? $this->clean_text( $post->post_title ) : '';
        $content_text  = isset( $post->post_content ) ? $this->clean_text( $post->post_content ) : '';

        $row = array(
            'object_id'     => $post_id,
            'post_type'     => $post_type,
            'language_code' => $language_code,
            'title_text'    => $title_text,
            'content_text'  => $content_text,
            'taxonomy_text' => $taxonomy_text,
            'meta_text'     => $meta_text,
            'chip_tags'     => $chip_tags,
            'numeric_facts' => wp_json_encode( $this->extract_numeric_facts( $title_text . ' ' . $taxonomy_text . ' ' . $meta_text . ' ' . $chip_tags ) ),
            'updated_at'    => $this->current_mysql_time(),
        );

        $row = apply_filters( 'sschp_search_index_row', $row, $post_id, $post );

        return false !== $wpdb->replace(
            self::table_name(),
            $row,
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Remove a post from the index.
     *
     * @param int $post_id Post ID.
     */
    public function delete_post( $post_id ) {
        global $wpdb;

        $post_id = absint( $post_id );
        if ( $post_id <= 0 ) {
            return;
        }

        $wpdb->delete( self::table_name(), array( 'object_id' => $post_id ), array( '%d' ) );
    }

    /**
     * Search local index. Returns null when caller should fall back.
     *
     * @param string $keyword    Search phrase.
     * @param array  $post_types Post types.
     * @param string $language   Language code.
     * @param int    $limit      Result limit.
     * @return array|null
     */
    public function search( $keyword, $post_types, $language = '', $limit = 50 ) {
        $keyword = sanitize_text_field( (string) $keyword );
        $tokens  = $this->tokenize( $keyword );

        if ( '' === $keyword || empty( $tokens ) ) {
            return null;
        }

        $post_types = $this->normalize_post_types( $post_types );
        $language   = sanitize_key( (string) $language );
        $limit      = max( 1, (int) $limit );

        if ( ! $this->is_ready( $post_types, $language ) ) {
            return null;
        }

        $rows = $this->get_candidate_rows( $keyword, $tokens, $post_types, $language, max( $limit * 8, 100 ) );
        if ( empty( $rows ) ) {
            return array();
        }

        $ranked = array();
        foreach ( $rows as $row ) {
            $score = $this->rank_row( $row, $keyword, $tokens );
            if ( $score <= 0 ) {
                continue;
            }

            $row->_sschp_score = $score;
            $ranked[]          = $row;
        }

        usort( $ranked, array( $this, 'sort_ranked_rows' ) );
        $ranked = array_slice( $ranked, 0, $limit );

        $posts_data = array();
        foreach ( $ranked as $row ) {
            $posts_data[] = $this->build_post_data_from_row( $row );
        }

        return $posts_data;
    }

    /**
     * Check if the index is complete enough for search.
     *
     * @param array  $post_types Post types.
     * @param string $language   Language code.
     * @return bool
     */
    public function is_ready( $post_types = array(), $language = '' ) {
        $ready = 'complete' === get_option( self::STATUS_OPTION, 'not_started' )
            && ( self::count_index_rows() > 0 || (int) get_option( self::TOTAL_OPTION, 0 ) === 0 );

        return (bool) apply_filters( 'sschp_search_index_ready', $ready, $post_types, $language );
    }

    /**
     * Schedule a background batch through Action Scheduler or WP-Cron.
     */
    private static function schedule_batch() {
        if ( function_exists( 'as_enqueue_async_action' ) ) {
            as_enqueue_async_action( self::CRON_HOOK, array(), 'smart-search-chips' );
            return;
        }

        if ( function_exists( 'wp_next_scheduled' ) && wp_next_scheduled( self::CRON_HOOK ) ) {
            return;
        }

        if ( function_exists( 'wp_schedule_single_event' ) ) {
            wp_schedule_single_event( time() + 30, self::CRON_HOOK );
        }
    }

    /**
     * Count indexable posts in the configured post types.
     *
     * @param array $post_types Post types.
     * @return int
     */
    private static function count_indexable_posts( $post_types ) {
        global $wpdb;

        $post_types = self::sanitize_post_type_list( $post_types );
        if ( empty( $post_types ) ) {
            return 0;
        }

        $placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
        $params       = array_merge( array( 'publish' ), $post_types );
        $sql          = $wpdb->prepare(
            "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_status = %s AND post_type IN ({$placeholders})",
            $params
        );

        return max( 0, (int) $wpdb->get_var( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Count indexed rows.
     *
     * @return int
     */
    private static function count_index_rows() {
        global $wpdb;

        return max( 0, (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table_name() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Read configured post types without needing a settings instance.
     *
     * @return array
     */
    private static function get_configured_post_types() {
        return self::sanitize_post_type_list( get_option( 'sschp_post_types', array( 'post' ) ) );
    }

    /**
     * Normalize post type list.
     *
     * @param mixed $post_types Post type values.
     * @return array
     */
    private static function sanitize_post_type_list( $post_types ) {
        $post_types = is_array( $post_types ) ? $post_types : array( $post_types );
        $post_types = array_values( array_filter( array_map( 'sanitize_key', $post_types ) ) );

        return ! empty( $post_types ) ? $post_types : array( 'post' );
    }

    /**
     * Normalize post types and verify they exist.
     *
     * @param mixed $post_types Post types.
     * @return array
     */
    private function normalize_post_types( $post_types ) {
        $post_types = self::sanitize_post_type_list( $post_types );
        $post_types = array_values( array_filter( $post_types, 'post_type_exists' ) );

        return ! empty( $post_types ) ? $post_types : array( 'post' );
    }

    /**
     * Fetch post IDs after the current cursor.
     *
     * @param int   $cursor     Last processed post ID.
     * @param int   $limit      Limit.
     * @param array $post_types Post types.
     * @return array
     */
    private function get_indexable_post_ids_after( $cursor, $limit, $post_types ) {
        global $wpdb;

        $post_types = $this->normalize_post_types( $post_types );
        if ( empty( $post_types ) ) {
            return array();
        }

        $placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
        $params       = array_merge( array( 'publish', (int) $cursor ), $post_types, array( (int) $limit ) );
        $sql          = $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_status = %s AND ID > %d AND post_type IN ({$placeholders}) ORDER BY ID ASC LIMIT %d",
            $params
        );

        return array_map( 'absint', (array) $wpdb->get_col( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Read candidates from the local index.
     *
     * @param string $keyword    Keyword phrase.
     * @param array  $tokens     Query tokens.
     * @param array  $post_types Post types.
     * @param string $language   Language code.
     * @param int    $limit      Candidate limit.
     * @return array
     */
    private function get_candidate_rows( $keyword, $tokens, $post_types, $language, $limit ) {
        global $wpdb;

        $fields       = array( 'title_text', 'content_text', 'taxonomy_text', 'meta_text', 'chip_tags' );
        $where        = array();
        $params       = array();
        $post_types   = $this->normalize_post_types( $post_types );
        $placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );

        $where[] = "post_type IN ({$placeholders})";
        $params  = array_merge( $params, $post_types );

        if ( '' !== $language ) {
            $where[]  = '(language_code = %s OR language_code = %s)';
            $params[] = $language;
            $params[] = '';
        }

        $needles      = array_values( array_unique( array_merge( array( $keyword ), $tokens ) ) );
        $like_clauses = array();

        foreach ( $needles as $needle ) {
            $needle = trim( (string) $needle );
            if ( '' === $needle ) {
                continue;
            }

            foreach ( $fields as $field ) {
                $like_clauses[] = "{$field} LIKE %s";
                $params[]       = '%' . $wpdb->esc_like( $needle ) . '%';
            }
        }

        if ( ! empty( $like_clauses ) ) {
            $where[] = '(' . implode( ' OR ', $like_clauses ) . ')';
        }

        $params[] = max( 1, (int) $limit );
        $sql      = $wpdb->prepare(
            'SELECT * FROM ' . self::table_name() . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY updated_at DESC LIMIT %d',
            $params
        );

        return (array) $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Convert an indexed row to the post data shape used by the chip engine.
     *
     * @param object $row Indexed row.
     * @return array
     */
    private function build_post_data_from_row( $row ) {
        $post_id  = isset( $row->object_id ) ? absint( $row->object_id ) : 0;
        $tag_data = $this->engine->extract_tags( isset( $row->chip_tags ) ? $row->chip_tags : '', '' );
        $title    = get_the_title( $post_id );

        if ( '' === $title && isset( $row->title_text ) ) {
            $title = $row->title_text;
        }

        return array(
            'id'         => $post_id,
            'url'        => function_exists( 'get_permalink' ) ? get_permalink( $post_id ) : '',
            'title'      => $title,
            'thumbnail'  => function_exists( 'get_the_post_thumbnail' ) ? get_the_post_thumbnail(
                $post_id,
                apply_filters(
                    'sschp_result_image_size',
                    'medium',
                    $post_id,
                    array(
                        'context' => 'index',
                    )
                ),
                array(
                    'class'   => 'ssc-result__img',
                    'loading' => 'lazy',
                )
            ) : '',
            'score'      => isset( $row->_sschp_score ) ? (float) $row->_sschp_score : 0.0,
            'tags'       => $tag_data['tags'],
            'tags_lower' => $tag_data['tags_lower'],
        );
    }

    /**
     * Rank an indexed row for a query.
     *
     * @param object $row     Indexed row.
     * @param string $keyword Full keyword phrase.
     * @param array  $tokens  Query tokens.
     * @return float
     */
    private function rank_row( $row, $keyword, $tokens ) {
        $keyword_lower = mb_strtolower( $keyword );
        $title         = $this->row_field_lower( $row, 'title_text' );
        $content       = $this->row_field_lower( $row, 'content_text' );
        $taxonomy      = $this->row_field_lower( $row, 'taxonomy_text' );
        $meta          = $this->row_field_lower( $row, 'meta_text' );
        $chips         = $this->row_field_lower( $row, 'chip_tags' );
        $combined      = trim( $title . ' ' . $taxonomy . ' ' . $chips . ' ' . $meta . ' ' . $content );
        $score         = 0.0;

        if ( '' !== $keyword_lower && $title === $keyword_lower ) {
            $score += 1000;
        }

        if ( '' !== $keyword_lower && false !== strpos( $title, $keyword_lower ) ) {
            $score += 700;
        }

        if ( '' !== $keyword_lower && false !== strpos( $combined, $keyword_lower ) ) {
            $score += 300;
        }

        $matched_tokens = 0;
        foreach ( $tokens as $token ) {
            $token = mb_strtolower( $token );
            if ( '' === $token ) {
                continue;
            }

            if ( false !== strpos( $combined, $token ) ) {
                $matched_tokens++;
                $score += 20;
            }

            if ( false !== strpos( $taxonomy, $token ) ) {
                $score += 120;
            }

            if ( false !== strpos( $chips, $token ) ) {
                $score += 100;
            }

            if ( false !== strpos( $meta, $token ) ) {
                $score += 80;
            }

            if ( false !== strpos( $content, $token ) ) {
                $score += 30;
            }
        }

        if ( count( $tokens ) > 0 && $matched_tokens === count( $tokens ) ) {
            $score += 250;
        }

        if ( isset( $row->post_type ) && 'product' === $row->post_type ) {
            $score += 5;
        }

        return (float) apply_filters( 'sschp_search_index_row_score', $score, $row, $keyword, $tokens );
    }

    /**
     * Sort rows by score, then freshness, then ID.
     *
     * @param object $a First row.
     * @param object $b Second row.
     * @return int
     */
    private function sort_ranked_rows( $a, $b ) {
        $score_a = isset( $a->_sschp_score ) ? (float) $a->_sschp_score : 0.0;
        $score_b = isset( $b->_sschp_score ) ? (float) $b->_sschp_score : 0.0;

        if ( $score_a !== $score_b ) {
            return $score_a < $score_b ? 1 : -1;
        }

        $time_a = isset( $a->updated_at ) ? strtotime( $a->updated_at ) : 0;
        $time_b = isset( $b->updated_at ) ? strtotime( $b->updated_at ) : 0;
        if ( $time_a !== $time_b ) {
            return $time_a < $time_b ? 1 : -1;
        }

        return absint( $a->object_id ) < absint( $b->object_id ) ? 1 : -1;
    }

    /**
     * Get a lower-cased row field.
     *
     * @param object $row   Row.
     * @param string $field Field name.
     * @return string
     */
    private function row_field_lower( $row, $field ) {
        return isset( $row->{$field} ) ? mb_strtolower( (string) $row->{$field} ) : '';
    }

    /**
     * Read tag fields for a post.
     *
     * @param int $post_id Post ID.
     * @return array
     */
    private function get_post_tag_source( $post_id ) {
        $primary = SSCHP_Field_Normalizer::normalize(
            get_post_meta( $post_id, SSCHP_Meta_Box::META_KEY, true ),
            array(
                'post_id'    => $post_id,
                'field_name' => SSCHP_Meta_Box::META_KEY,
                'context'    => 'index',
                'source'     => 'post_meta',
            )
        );
        $extra   = '';

        $tag_field   = sanitize_key( $this->settings->get( 'tag_fields', '' ) );
        $extra_field = sanitize_key( $this->settings->get( 'extra_fields', '' ) );

        if ( '' !== $tag_field ) {
            $mapped = $this->get_field_value( $post_id, $tag_field );
            if ( '' !== $mapped ) {
                $primary = $mapped;
            }
        }

        if ( '' !== $extra_field ) {
            $extra = $this->get_field_value( $post_id, $extra_field );
        }

        $tag_source = apply_filters(
            'sschp_post_tag_source',
            array(
                'primary' => $primary,
                'extra'   => $extra,
            ),
            $post_id,
            array(
                'context' => 'index',
            )
        );

        if ( is_array( $tag_source ) ) {
            $primary = isset( $tag_source['primary'] ) ? SSCHP_Field_Normalizer::normalize( $tag_source['primary'] ) : '';
            $extra   = isset( $tag_source['extra'] ) ? SSCHP_Field_Normalizer::normalize( $tag_source['extra'] ) : '';
        }

        return array(
            'primary' => $primary,
            'extra'   => $extra,
        );
    }

    /**
     * Read and normalize a mapped field value.
     *
     * @param int    $post_id    Post ID.
     * @param string $field_name Field name.
     * @return string
     */
    private function get_field_value( $post_id, $field_name ) {
        if ( '' === $field_name ) {
            return '';
        }

        $context = array(
            'post_id'    => $post_id,
            'field_name' => $field_name,
            'context'    => 'index',
            'source'     => 'post_meta',
        );

        if ( function_exists( 'get_field' ) ) {
            $acf_value = SSCHP_Field_Normalizer::normalize(
                get_field( $field_name, $post_id ),
                array_merge(
                    $context,
                    array(
                        'source' => 'acf',
                    )
                )
            );

            if ( '' !== $acf_value ) {
                return $acf_value;
            }
        }

        return SSCHP_Field_Normalizer::normalize( get_post_meta( $post_id, $field_name, true ), $context );
    }

    /**
     * Build taxonomy text from assigned terms.
     *
     * @param int    $post_id   Post ID.
     * @param string $post_type Post type.
     * @return string
     */
    private function get_taxonomy_text( $post_id, $post_type ) {
        if ( ! function_exists( 'get_object_taxonomies' ) || ! function_exists( 'wp_get_post_terms' ) ) {
            return '';
        }

        $values     = array();
        $taxonomies = get_object_taxonomies( $post_type, 'names' );

        foreach ( (array) $taxonomies as $taxonomy ) {
            $terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
            if ( is_wp_error( $terms ) ) {
                continue;
            }

            foreach ( (array) $terms as $term ) {
                $term = $this->clean_text( $term );
                if ( '' !== $term ) {
                    $values[] = $term;
                }
            }
        }

        return $this->join_unique_values( $values );
    }

    /**
     * Build indexed meta text from known chip-related fields.
     *
     * @param int   $post_id    Post ID.
     * @param array $tag_source Tag source.
     * @return string
     */
    private function get_meta_text( $post_id, $tag_source ) {
        $values = array(
            isset( $tag_source['primary'] ) ? $tag_source['primary'] : '',
            isset( $tag_source['extra'] ) ? $tag_source['extra'] : '',
        );

        foreach ( array( 'tag_fields', 'extra_fields' ) as $setting_key ) {
            $field_name = sanitize_key( $this->settings->get( $setting_key, '' ) );
            if ( '' !== $field_name ) {
                $values[] = $this->get_field_value( $post_id, $field_name );
            }
        }

        return $this->join_unique_values( $values );
    }

    /**
     * Extract numeric facts for future range filters and diagnostics.
     *
     * @param string $text Searchable text.
     * @return array
     */
    private function extract_numeric_facts( $text ) {
        $facts = array();
        if ( ! preg_match_all( '/(.{0,30}?)(-?\d+(?:[\.,]\d+)?)(.{0,30}?)(?:\s|$)/u', (string) $text, $matches, PREG_SET_ORDER ) ) {
            return $facts;
        }

        foreach ( $matches as $match ) {
            $number = str_replace( ',', '.', $match[2] );
            if ( ! is_numeric( $number ) ) {
                continue;
            }

            $facts[] = array(
                'value'   => (float) $number,
                'context' => $this->clean_text( trim( $match[1] . ' ' . $match[3] ) ),
            );
        }

        return array_slice( $facts, 0, 50 );
    }

    /**
     * Tokenize a search phrase.
     *
     * @param string $keyword Keyword phrase.
     * @return array
     */
    private function tokenize( $keyword ) {
        $parts  = preg_split( '/\s+/u', mb_strtolower( (string) $keyword ) );
        $tokens = array();

        foreach ( (array) $parts as $part ) {
            $part = trim( $part );
            if ( '' === $part ) {
                continue;
            }

            $tokens[] = $part;
        }

        return array_values( array_unique( $tokens ) );
    }

    /**
     * Clean searchable text.
     *
     * @param mixed $value Text value.
     * @return string
     */
    private function clean_text( $value ) {
        $value = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
        if ( function_exists( 'wp_strip_all_tags' ) ) {
            $value = wp_strip_all_tags( $value );
        } else {
            $value = strip_tags( $value );
        }

        return sanitize_textarea_field( preg_replace( '/\s+/u', ' ', trim( $value ) ) );
    }

    /**
     * Join normalized unique values.
     *
     * @param array $values Values.
     * @return string
     */
    private function join_unique_values( $values ) {
        $joined = array();
        $seen   = array();

        foreach ( (array) $values as $value ) {
            $value = SSCHP_Field_Normalizer::normalize( $value );
            if ( '' === $value ) {
                continue;
            }

            foreach ( preg_split( '/[;,]/', $value ) as $part ) {
                $part = $this->clean_text( $part );
                $key  = mb_strtolower( $part );

                if ( '' === $part || isset( $seen[ $key ] ) ) {
                    continue;
                }

                $seen[ $key ] = true;
                $joined[]     = $part;
            }
        }

        return implode( '; ', $joined );
    }

    /**
     * Determine if save should be ignored.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     * @return bool
     */
    private function should_skip_save( $post_id, $post = null ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return true;
        }

        if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
            return true;
        }

        if ( function_exists( 'wp_is_post_autosave' ) && wp_is_post_autosave( $post_id ) ) {
            return true;
        }

        if ( function_exists( 'wp_is_post_revision' ) && wp_is_post_revision( $post_id ) ) {
            return true;
        }

        if ( is_object( $post ) && isset( $post->post_status ) && in_array( $post->post_status, array( 'auto-draft', 'inherit' ), true ) ) {
            return true;
        }

        return false;
    }

    /**
     * Check whether a meta key affects index contents.
     *
     * @param string $meta_key Meta key.
     * @return bool
     */
    private function is_indexed_meta_key( $meta_key ) {
        $keys = array(
            SSCHP_Meta_Box::META_KEY,
            '_sschp_generated_tags',
            '_product_attributes',
            '_sku',
            sanitize_key( $this->settings->get( 'tag_fields', '' ) ),
            sanitize_key( $this->settings->get( 'extra_fields', '' ) ),
        );

        return in_array( $meta_key, array_filter( $keys ), true );
    }

    /**
     * Check whether an option change requires a rebuild.
     *
     * @param string $option Option name.
     * @return bool
     */
    private function is_index_affecting_option( $option ) {
        return in_array(
            sanitize_key( $option ),
            array(
                'sschp_post_types',
                'sschp_tag_fields',
                'sschp_extra_fields',
                'sschp_woo_enabled',
                'sschp_woo_include_sku',
            ),
            true
        );
    }

    /**
     * Get current GMT MySQL time.
     *
     * @return string
     */
    private function current_mysql_time() {
        return function_exists( 'current_time' ) ? current_time( 'mysql', true ) : gmdate( 'Y-m-d H:i:s' );
    }
}
