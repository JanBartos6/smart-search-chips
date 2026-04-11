<?php
/**
 * Analytics - Search and chip-click event tracking with admin dashboard helpers
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSC_Analytics {

    /** @var string Database version option */
    const DB_VERSION_OPTION = 'ssc_analytics_db_version';

    /** @var string Current analytics schema version */
    const DB_VERSION = '1.1';

    /** @var int Max analytics requests allowed per fingerprint in the rolling window */
    const RATE_LIMIT_MAX_REQUESTS = 120;

    /** @var int Rolling analytics rate-limit window in seconds */
    const RATE_LIMIT_WINDOW = 60;

    /** @var int Short dedupe window for identical search/click events */
    const EVENT_REPLAY_WINDOW = 15;

    /** @var int Longer dedupe window for chip impression batches */
    const IMPRESSION_REPLAY_WINDOW = 60;

    /**
     * Register analytics hooks.
     */
    public function init() {
        add_action( 'init', array( __CLASS__, 'maybe_install' ) );
        add_action( 'wp_ajax_ssc_track_event', array( $this, 'ajax_track_event' ) );
        add_action( 'wp_ajax_nopriv_ssc_track_event', array( $this, 'ajax_track_event' ) );
        add_action( 'admin_post_ssc_export_analytics', array( $this, 'handle_export' ) );
    }

    /**
     * Ensure the analytics table exists on normal plugin loads.
     */
    public static function maybe_install() {
        if ( get_option( self::DB_VERSION_OPTION ) !== self::DB_VERSION ) {
            self::create_table();
        }
    }

    /**
     * Create or update the analytics table.
     */
    public static function create_table() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table_name      = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL,
            visitor_id varchar(64) NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            event_type varchar(32) NOT NULL,
            language_code varchar(32) DEFAULT '',
            search_query varchar(191) DEFAULT '',
            chip_label varchar(191) DEFAULT '',
            page_url varchar(255) DEFAULT '',
            result_count int(11) DEFAULT NULL,
            active_tag_count int(11) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY event_type_created_at (event_type, created_at),
            KEY language_created_at (language_code, created_at),
            KEY chip_label_created_at (chip_label(100), created_at),
            KEY search_query_created_at (search_query(100), created_at),
            KEY visitor_created_at (visitor_id, created_at)
        ) {$charset_collate};";

        dbDelta( $sql );
        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
    }

    /**
     * Return the analytics table name.
     *
     * @return string
     */
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'ssc_events';
    }

    /**
     * Remove all stored analytics events while keeping the table structure.
     *
     * @return void
     */
    public static function clear_events() {
        global $wpdb;

        $table_name = self::table_name();
        $wpdb->query( "DELETE FROM {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Drop the analytics table entirely.
     *
     * @return void
     */
    public static function drop_table() {
        global $wpdb;

        $table_name = self::table_name();
        $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Whether analytics is enabled for tracking and dashboard reporting.
     *
     * @return bool
     */
    public static function is_enabled() {
        return SSC_License::is_pro() && get_option( 'ssc_analytics_enabled', '0' ) === '1';
    }

    /**
     * Track a frontend analytics event.
     */
    public function ajax_track_event() {
        check_ajax_referer( 'ssc_analytics_nonce', 'nonce' );

        if ( ! self::is_enabled() ) {
            wp_send_json_success( array( 'tracked' => false ) );
        }

        $event_type = isset( $_POST['event_type'] ) ? sanitize_key( wp_unslash( $_POST['event_type'] ) ) : '';
        if ( ! in_array( $event_type, array( 'search', 'chip_click', 'chip_impression' ), true ) ) {
            wp_send_json_error( array( 'message' => __( 'Unsupported analytics event type.', 'smart-search-chips' ) ), 400 );
        }

        $visitor_id = isset( $_POST['visitor_id'] ) ? self::sanitize_visitor_id( wp_unslash( $_POST['visitor_id'] ) ) : '';
        if ( $visitor_id === '' ) {
            $visitor_id = wp_generate_uuid4();
        }

        $search_query     = isset( $_POST['search_query'] ) ? sanitize_text_field( wp_unslash( $_POST['search_query'] ) ) : '';
        $chip_label       = isset( $_POST['chip_label'] ) ? sanitize_text_field( wp_unslash( $_POST['chip_label'] ) ) : '';
        $page_url         = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';
        $result_count     = isset( $_POST['result_count'] ) ? intval( $_POST['result_count'] ) : null;
        $active_tag_count = isset( $_POST['active_tag_count'] ) ? max( 0, intval( $_POST['active_tag_count'] ) ) : null;
        $language_code    = SSC_Language::request_language();
        $chip_labels      = self::sanitize_chip_labels( isset( $_POST['chip_labels'] ) ? wp_unslash( $_POST['chip_labels'] ) : array() );

        if ( $event_type === 'search' && strlen( $search_query ) < 2 ) {
            wp_send_json_success( array( 'tracked' => false ) );
        }
        if ( $event_type === 'chip_impression' && empty( $chip_labels ) ) {
            wp_send_json_success( array( 'tracked' => false ) );
        }

        $tracking_decision = self::guard_tracking_request(
            array(
                'visitor_id'       => $visitor_id,
                'event_type'       => $event_type,
                'language_code'    => $language_code,
                'search_query'     => $search_query,
                'chip_label'       => $chip_label,
                'page_url'         => $page_url,
                'active_tag_count' => $active_tag_count,
                'chip_labels'      => $chip_labels,
            )
        );

        if ( 'track' !== $tracking_decision ) {
            wp_send_json_success(
                array(
                    'tracked' => false,
                    'reason'  => $tracking_decision,
                )
            );
        }

        $user_id = get_current_user_id();
        $user_id = $user_id > 0 ? $user_id : 0;

        global $wpdb;

        $timestamp = current_time( 'mysql', true );
        $base_row  = array(
            'created_at'       => $timestamp,
            'visitor_id'       => substr( $visitor_id, 0, 64 ),
            'user_id'          => $user_id,
            'event_type'       => $event_type,
            'language_code'    => substr( $language_code, 0, 32 ),
            'search_query'     => substr( $search_query, 0, 191 ),
            'chip_label'       => substr( $chip_label, 0, 191 ),
            'page_url'         => substr( $page_url, 0, 255 ),
            'result_count'     => $result_count,
            'active_tag_count' => $active_tag_count,
        );
        $formats   = array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d' );
        $tracked   = 0;

        if ( 'chip_impression' === $event_type ) {
            foreach ( $chip_labels as $impression_label ) {
                $row               = $base_row;
                $row['chip_label'] = substr( $impression_label, 0, 191 );

                if ( false !== $wpdb->insert( self::table_name(), $row, $formats ) ) {
                    $tracked++;
                }
            }
        } else {
            if ( false !== $wpdb->insert( self::table_name(), $base_row, $formats ) ) {
                $tracked = 1;
            }
        }

        wp_send_json_success( array( 'tracked' => $tracked > 0, 'tracked_count' => $tracked ) );
    }

    /**
     * Aggregate analytics data for the dashboard.
     *
     * @param int $days Rolling time window in days.
     * @return array
     */
    public static function get_dashboard_data( $args = array() ) {
        global $wpdb;

        self::maybe_install();

        $table_name = self::table_name();
        $range      = self::get_date_range( $args );
        $from_gmt   = $range['from_gmt'];
        $to_gmt     = $range['to_gmt'];

        $summary = array(
            'total_searches'     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE event_type = %s AND created_at BETWEEN %s AND %s", 'search', $from_gmt, $to_gmt ) ),
            'unique_visitors'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT visitor_id) FROM {$table_name} WHERE created_at BETWEEN %s AND %s", $from_gmt, $to_gmt ) ),
            'zero_result_search' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE event_type = %s AND created_at BETWEEN %s AND %s AND result_count = 0", 'search', $from_gmt, $to_gmt ) ),
            'chip_clicks'        => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE event_type = %s AND created_at BETWEEN %s AND %s", 'chip_click', $from_gmt, $to_gmt ) ),
        );
        $summary['search_linked_clicks'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$table_name}
                WHERE event_type = %s AND created_at BETWEEN %s AND %s AND search_query <> ''",
                'chip_click',
                $from_gmt,
                $to_gmt
            )
        );
        $summary['search_to_click_rate'] = self::calculate_percentage( $summary['search_linked_clicks'], $summary['total_searches'] );

        $top_chips = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT chip_label, COUNT(*) AS clicks, COUNT(DISTINCT visitor_id) AS visitors
                FROM {$table_name}
                WHERE event_type = %s AND created_at BETWEEN %s AND %s AND chip_label <> ''
                GROUP BY chip_label
                ORDER BY clicks DESC, visitors DESC
                LIMIT 10",
                'chip_click',
                $from_gmt,
                $to_gmt
            ),
            ARRAY_A
        );

        $top_queries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT search_query,
                    SUM(CASE WHEN event_type = %s THEN 1 ELSE 0 END) AS searches,
                    SUM(CASE WHEN event_type = %s THEN 1 ELSE 0 END) AS chip_clicks,
                    AVG(CASE WHEN event_type = %s THEN COALESCE(result_count, 0) ELSE NULL END) AS avg_results
                FROM {$table_name}
                WHERE event_type IN (%s, %s) AND created_at BETWEEN %s AND %s AND search_query <> ''
                GROUP BY search_query
                ORDER BY searches DESC
                LIMIT 10",
                'search',
                'chip_click',
                'search',
                'search',
                'chip_click',
                $from_gmt,
                $to_gmt
            ),
            ARRAY_A
        );
        $top_queries = self::append_percentage_to_rows( $top_queries, 'chip_clicks', 'searches', 'click_rate' );

        $zero_queries = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix.
            $wpdb->prepare(
                "SELECT search_query, COUNT(*) AS misses, MIN(created_at) AS first_seen, MAX(created_at) AS last_seen
                FROM {$table_name}
                WHERE event_type = %s AND created_at BETWEEN %s AND %s AND result_count = 0 AND search_query <> ''
                GROUP BY search_query
                ORDER BY misses DESC, last_seen DESC
                LIMIT 10",
                'search',
                $from_gmt,
                $to_gmt
            ),
            ARRAY_A
        );
        $top_chip_ctr = self::get_top_chip_ctr_rows( $range );

        return array(
            'range'         => $range,
            'summary'       => $summary,
            'timeseries'    => self::get_time_series( $range ),
            'top_chips'     => is_array( $top_chips ) ? $top_chips : array(),
            'top_chip_ctr'  => $top_chip_ctr,
            'top_queries'   => is_array( $top_queries ) ? $top_queries : array(),
            'zero_queries'  => is_array( $zero_queries ) ? $zero_queries : array(),
        );
    }

    /**
     * Build a daily analytics time series for the selected date range.
     *
     * @param array $args Range arguments.
     * @return array
     */
    public static function get_time_series( $args = array() ) {
        global $wpdb;

        self::maybe_install();

        $range      = self::get_date_range( $args );
        $table_name = self::table_name();
        $timezone   = wp_timezone();
        $rows       = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT created_at, event_type, visitor_id, result_count
                FROM {$table_name}
                WHERE created_at BETWEEN %s AND %s
                ORDER BY created_at ASC",
                $range['from_gmt'],
                $range['to_gmt']
            ),
            ARRAY_A
        );

        $series       = array();
        $visitor_sets = array();
        $period       = new DatePeriod(
            new DateTimeImmutable( $range['from'] . ' 00:00:00', $timezone ),
            new DateInterval( 'P1D' ),
            ( new DateTimeImmutable( $range['to'] . ' 00:00:00', $timezone ) )->modify( '+1 day' )
        );

        foreach ( $period as $day ) {
            $key             = $day->format( 'Y-m-d' );
            $series[ $key ]  = array(
                'date'            => $key,
                'label'           => $day->format( 'M j' ),
                'searches'        => 0,
                'chip_clicks'     => 0,
                'zero_results'    => 0,
                'unique_visitors' => 0,
            );
            $visitor_sets[ $key ] = array();
        }

        foreach ( (array) $rows as $row ) {
            if ( empty( $row['created_at'] ) ) {
                continue;
            }

            $event_at = new DateTimeImmutable( $row['created_at'], new DateTimeZone( 'UTC' ) );
            $bucket   = $event_at->setTimezone( $timezone )->format( 'Y-m-d' );

            if ( ! isset( $series[ $bucket ] ) ) {
                continue;
            }

            if ( $row['event_type'] === 'search' ) {
                $series[ $bucket ]['searches']++;
                if ( isset( $row['result_count'] ) && (int) $row['result_count'] === 0 ) {
                    $series[ $bucket ]['zero_results']++;
                }
            } elseif ( $row['event_type'] === 'chip_click' ) {
                $series[ $bucket ]['chip_clicks']++;
            }

            if ( ! empty( $row['visitor_id'] ) ) {
                $visitor_sets[ $bucket ][ $row['visitor_id'] ] = true;
            }
        }

        foreach ( $series as $bucket => $point ) {
            $series[ $bucket ]['unique_visitors'] = count( $visitor_sets[ $bucket ] );
        }

        return array_values( $series );
    }

    /**
     * Return recent analytics events for the selected range.
     *
     * @param array $args Range/limit arguments.
     * @return array
     */
    public static function get_recent_events( $args = array() ) {
        global $wpdb;

        self::maybe_install();

        $range      = self::get_date_range( $args );
        $table_name = self::table_name();
        $limit      = isset( $args['limit'] ) ? absint( $args['limit'] ) : 100;

        if ( $limit > 0 ) {
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT created_at, event_type, language_code, search_query, chip_label, result_count, active_tag_count, page_url, visitor_id
                    FROM {$table_name}
                    WHERE created_at BETWEEN %s AND %s
                    ORDER BY created_at DESC
                    LIMIT %d",
                    $range['from_gmt'],
                    $range['to_gmt'],
                    $limit
                ),
                ARRAY_A
            );
        } else {
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT created_at, event_type, language_code, search_query, chip_label, result_count, active_tag_count, page_url, visitor_id
                    FROM {$table_name}
                    WHERE created_at BETWEEN %s AND %s
                    ORDER BY created_at DESC",
                    $range['from_gmt'],
                    $range['to_gmt']
                ),
                ARRAY_A
            );
        }

        return is_array( $results ) ? $results : array();
    }

    /**
     * Export analytics events as CSV.
     */
    public function handle_export() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to export analytics.', 'smart-search-chips' ) );
        }

        check_admin_referer( 'ssc_export_analytics' );

        if ( ! self::is_enabled() ) {
            wp_die( esc_html__( 'Analytics is not enabled.', 'smart-search-chips' ) );
        }

        $args   = array(
            'from'  => isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '',
            'to'    => isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '',
            'limit' => 0,
        );
        $range  = self::get_date_range( $args );
        $events = self::get_recent_events( $args );

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=ssc-analytics-' . $range['from'] . '-to-' . $range['to'] . '.csv' );

        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, array( 'created_at_utc', 'event_type', 'language_code', 'search_query', 'chip_label', 'result_count', 'active_tag_count', 'page_url', 'visitor_id' ) );

        foreach ( $events as $event ) {
            fputcsv(
                $out,
                self::escape_csv_row(
                    array(
                        $event['created_at'],
                        $event['event_type'],
                        $event['language_code'],
                        $event['search_query'],
                        $event['chip_label'],
                        $event['result_count'],
                        $event['active_tag_count'],
                        $event['page_url'],
                        $event['visitor_id'],
                    )
                )
            );
        }

        fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- streaming CSV to php://output.
        exit;
    }

    /**
     * Normalize a date range for dashboard queries and export.
     *
     * @param array $args Date arguments.
     * @return array
     */
    public static function get_date_range( $args = array() ) {
        $timezone = wp_timezone();
        $now      = new DateTimeImmutable( 'now', $timezone );

        $default_to   = $now->format( 'Y-m-d' );
        $default_from = $now->sub( new DateInterval( 'P29D' ) )->format( 'Y-m-d' );

        $from = isset( $args['from'] ) ? self::sanitize_date_value( $args['from'] ) : $default_from;
        $to   = isset( $args['to'] ) ? self::sanitize_date_value( $args['to'] ) : $default_to;

        if ( $from === '' ) {
            $from = $default_from;
        }
        if ( $to === '' ) {
            $to = $default_to;
        }
        if ( $from > $to ) {
            $swap = $from;
            $from = $to;
            $to   = $swap;
        }

        $from_local = new DateTimeImmutable( $from . ' 00:00:00', $timezone );
        $to_local   = new DateTimeImmutable( $to . ' 23:59:59', $timezone );

        return array(
            'from'     => $from,
            'to'       => $to,
            'from_gmt' => $from_local->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ),
            'to_gmt'   => $to_local->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ),
        );
    }

    /**
     * Validate a YYYY-MM-DD date string.
     *
     * @param string $value Raw date value.
     * @return string
     */
    private static function sanitize_date_value( $value ) {
        $value = sanitize_text_field( (string) $value );

        return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
    }

    /**
     * Sanitize a chip label array for impression tracking.
     *
     * @param mixed $raw_labels Raw request value.
     * @return array
     */
    private static function sanitize_chip_labels( $raw_labels ) {
        $labels = is_array( $raw_labels ) ? $raw_labels : array();
        $clean  = array();

        foreach ( $labels as $label ) {
            $label = sanitize_text_field( (string) $label );
            if ( '' === $label || in_array( $label, $clean, true ) ) {
                continue;
            }

            $clean[] = $label;
        }

        return array_slice( $clean, 0, 20 );
    }

    /**
     * Sanitize a visitor identifier used for anonymous analytics correlation.
     *
     * @param mixed $value Raw visitor ID.
     * @return string
     */
    private static function sanitize_visitor_id( $value ) {
        $value = sanitize_text_field( (string) $value );
        $value = preg_replace( '/[^A-Za-z0-9_-]/', '', $value );

        return substr( (string) $value, 0, 64 );
    }

    /**
     * Decide whether a tracking request should be recorded.
     *
     * @param array $payload Normalized event data.
     * @return string One of track, duplicate, or rate_limited.
     */
    private static function guard_tracking_request( $payload ) {
        $subject = self::get_rate_limit_subject( isset( $payload['visitor_id'] ) ? (string) $payload['visitor_id'] : '' );

        if ( self::is_rate_limited( $subject ) ) {
            return 'rate_limited';
        }

        $ttl = 'chip_impression' === ( $payload['event_type'] ?? '' ) ? self::IMPRESSION_REPLAY_WINDOW : self::EVENT_REPLAY_WINDOW;

        if ( ! self::reserve_event_fingerprint( self::build_event_fingerprint( $payload ), $ttl ) ) {
            return 'duplicate';
        }

        return 'track';
    }

    /**
     * Build a stable subject key for analytics rate limiting.
     *
     * @param string $visitor_id Visitor identifier from the browser.
     * @return string
     */
    private static function get_rate_limit_subject( $visitor_id ) {
        $remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

        if ( '' !== $remote_addr ) {
            return 'ip:' . hash( 'sha256', $remote_addr );
        }

        return '' !== $visitor_id ? 'visitor:' . $visitor_id : '';
    }

    /**
     * Enforce a lightweight per-client rate limit on the public analytics endpoint.
     *
     * @param string $subject Rate-limit subject key.
     * @return bool
     */
    private static function is_rate_limited( $subject ) {
        if ( '' === $subject || ! function_exists( 'get_transient' ) || ! function_exists( 'set_transient' ) ) {
            return false;
        }

        $transient_key = 'ssc_rate_' . md5( $subject );
        $count         = (int) get_transient( $transient_key );

        if ( $count >= self::RATE_LIMIT_MAX_REQUESTS ) {
            return true;
        }

        set_transient( $transient_key, $count + 1, self::RATE_LIMIT_WINDOW );

        return false;
    }

    /**
     * Build a short-lived replay fingerprint for analytics events.
     *
     * @param array $payload Normalized event data.
     * @return string
     */
    private static function build_event_fingerprint( $payload ) {
        $fingerprint = array(
            'visitor_id'       => isset( $payload['visitor_id'] ) ? (string) $payload['visitor_id'] : '',
            'event_type'       => isset( $payload['event_type'] ) ? (string) $payload['event_type'] : '',
            'language_code'    => isset( $payload['language_code'] ) ? (string) $payload['language_code'] : '',
            'search_query'     => isset( $payload['search_query'] ) ? (string) $payload['search_query'] : '',
            'chip_label'       => isset( $payload['chip_label'] ) ? (string) $payload['chip_label'] : '',
            'page_url'         => isset( $payload['page_url'] ) ? (string) $payload['page_url'] : '',
            'active_tag_count' => isset( $payload['active_tag_count'] ) ? (int) $payload['active_tag_count'] : 0,
            'chip_labels'      => isset( $payload['chip_labels'] ) && is_array( $payload['chip_labels'] ) ? array_values( $payload['chip_labels'] ) : array(),
        );

        return hash( 'sha256', wp_json_encode( $fingerprint ) );
    }

    /**
     * Reserve an analytics fingerprint so immediate replays are ignored.
     *
     * @param string $fingerprint Event fingerprint.
     * @param int    $ttl         Replay window in seconds.
     * @return bool
     */
    private static function reserve_event_fingerprint( $fingerprint, $ttl ) {
        if ( '' === $fingerprint || ! function_exists( 'get_transient' ) || ! function_exists( 'set_transient' ) ) {
            return true;
        }

        $transient_key = 'ssc_evt_' . substr( $fingerprint, 0, 32 );

        if ( false !== get_transient( $transient_key ) ) {
            return false;
        }

        set_transient( $transient_key, 1, max( 1, (int) $ttl ) );

        return true;
    }

    /**
     * Escape a single CSV export value so spreadsheet apps do not treat it as a formula.
     *
     * @param mixed $value Raw export value.
     * @return string
     */
    private static function escape_csv_value( $value ) {
        if ( null === $value ) {
            return '';
        }

        if ( is_bool( $value ) ) {
            return $value ? '1' : '0';
        }

        if ( ! is_scalar( $value ) ) {
            $value = wp_json_encode( $value );
        }

        $value = (string) $value;

        if ( preg_match( '/^[\t\r]/', $value ) || preg_match( '/^\s*[=\+\-@]/', $value ) ) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * Escape all CSV cells in a single row before export.
     *
     * @param array $row Raw CSV row.
     * @return array
     */
    private static function escape_csv_row( $row ) {
        if ( ! is_array( $row ) ) {
            return array();
        }

        foreach ( $row as &$value ) {
            $value = self::escape_csv_value( $value );
        }
        unset( $value );

        return $row;
    }

    /**
     * Calculate a percentage with one decimal place.
     *
     * @param int|float $numerator   Numerator.
     * @param int|float $denominator Denominator.
     * @return float
     */
    private static function calculate_percentage( $numerator, $denominator ) {
        if ( (float) $denominator <= 0 ) {
            return 0.0;
        }

        return round( ( (float) $numerator / (float) $denominator ) * 100, 1 );
    }

    /**
     * Append a percentage field to aggregated analytics rows.
     *
     * @param array  $rows            Rows to enrich.
     * @param string $numerator_key   Numerator field.
     * @param string $denominator_key Denominator field.
     * @param string $target_key      Output field.
     * @return array
     */
    private static function append_percentage_to_rows( $rows, $numerator_key, $denominator_key, $target_key ) {
        if ( ! is_array( $rows ) ) {
            return array();
        }

        foreach ( $rows as &$row ) {
            $numerator          = isset( $row[ $numerator_key ] ) ? (float) $row[ $numerator_key ] : 0;
            $denominator        = isset( $row[ $denominator_key ] ) ? (float) $row[ $denominator_key ] : 0;
            $row[ $target_key ] = self::calculate_percentage( $numerator, $denominator );
        }
        unset( $row );

        return $rows;
    }

    /**
     * Return the top chips ranked by click-through rate.
     *
     * @param array $range Date range.
     * @return array
     */
    private static function get_top_chip_ctr_rows( $range ) {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT chip_label,
                    SUM(CASE WHEN event_type = %s THEN 1 ELSE 0 END) AS impressions,
                    SUM(CASE WHEN event_type = %s THEN 1 ELSE 0 END) AS clicks,
                    COUNT(DISTINCT CASE WHEN event_type = %s THEN visitor_id ELSE NULL END) AS click_visitors
                FROM " . self::table_name() . " /* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from \$wpdb->prefix */
                WHERE event_type IN (%s, %s)
                    AND created_at BETWEEN %s AND %s
                    AND chip_label <> ''
                GROUP BY chip_label
                HAVING impressions > 0
                ORDER BY ( SUM(CASE WHEN event_type = %s THEN 1 ELSE 0 END) / SUM(CASE WHEN event_type = %s THEN 1 ELSE 0 END) ) DESC, clicks DESC
                LIMIT 10",
                'chip_impression',
                'chip_click',
                'chip_click',
                'chip_impression',
                'chip_click',
                $range['from_gmt'],
                $range['to_gmt'],
                'chip_click',
                'chip_impression'
            ),
            ARRAY_A
        );

        return self::append_percentage_to_rows( is_array( $rows ) ? $rows : array(), 'clicks', 'impressions', 'click_through_rate' );
    }
}
