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
    const DB_VERSION = '1.0';

    /**
     * Register analytics hooks.
     */
    public function init() {
        add_action( 'init', array( __CLASS__, 'maybe_install' ) );
        add_action( 'wp_ajax_ssc_track_event', array( $this, 'ajax_track_event' ) );
        add_action( 'wp_ajax_nopriv_ssc_track_event', array( $this, 'ajax_track_event' ) );
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
            search_query varchar(191) DEFAULT '',
            chip_label varchar(191) DEFAULT '',
            page_url varchar(255) DEFAULT '',
            result_count int(11) DEFAULT NULL,
            active_tag_count int(11) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY event_type_created_at (event_type, created_at),
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
        if ( ! in_array( $event_type, array( 'search', 'chip_click' ), true ) ) {
            wp_send_json_error( array( 'message' => __( 'Unsupported analytics event type.', 'smart-search-chips' ) ), 400 );
        }

        $visitor_id = isset( $_POST['visitor_id'] ) ? sanitize_text_field( wp_unslash( $_POST['visitor_id'] ) ) : '';
        if ( $visitor_id === '' ) {
            $visitor_id = wp_generate_uuid4();
        }

        $search_query     = isset( $_POST['search_query'] ) ? sanitize_text_field( wp_unslash( $_POST['search_query'] ) ) : '';
        $chip_label       = isset( $_POST['chip_label'] ) ? sanitize_text_field( wp_unslash( $_POST['chip_label'] ) ) : '';
        $page_url         = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';
        $result_count     = isset( $_POST['result_count'] ) ? intval( $_POST['result_count'] ) : null;
        $active_tag_count = isset( $_POST['active_tag_count'] ) ? max( 0, intval( $_POST['active_tag_count'] ) ) : null;

        if ( $event_type === 'search' && strlen( $search_query ) < 2 ) {
            wp_send_json_success( array( 'tracked' => false ) );
        }

        $user_id = get_current_user_id();
        $user_id = $user_id > 0 ? $user_id : 0;

        global $wpdb;

        $wpdb->insert(
            self::table_name(),
            array(
                'created_at'       => current_time( 'mysql', true ),
                'visitor_id'       => substr( $visitor_id, 0, 64 ),
                'user_id'          => $user_id,
                'event_type'       => $event_type,
                'search_query'     => substr( $search_query, 0, 191 ),
                'chip_label'       => substr( $chip_label, 0, 191 ),
                'page_url'         => substr( $page_url, 0, 255 ),
                'result_count'     => $result_count,
                'active_tag_count' => $active_tag_count,
            ),
            array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d' )
        );

        wp_send_json_success( array( 'tracked' => true ) );
    }

    /**
     * Aggregate analytics data for the dashboard.
     *
     * @param int $days Rolling time window in days.
     * @return array
     */
    public static function get_dashboard_data( $days = 30 ) {
        global $wpdb;

        self::maybe_install();

        $table_name = self::table_name();
        $days       = max( 1, absint( $days ) );
        $from_gmt   = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

        $summary = array(
            'total_searches'     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE event_type = %s AND created_at >= %s", 'search', $from_gmt ) ),
            'unique_visitors'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT visitor_id) FROM {$table_name} WHERE created_at >= %s", $from_gmt ) ),
            'zero_result_search' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE event_type = %s AND created_at >= %s AND result_count = 0", 'search', $from_gmt ) ),
            'chip_clicks'        => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE event_type = %s AND created_at >= %s", 'chip_click', $from_gmt ) ),
        );

        $top_chips = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT chip_label, COUNT(*) AS clicks, COUNT(DISTINCT visitor_id) AS visitors
                FROM {$table_name}
                WHERE event_type = %s AND created_at >= %s AND chip_label <> ''
                GROUP BY chip_label
                ORDER BY clicks DESC, visitors DESC
                LIMIT 10",
                'chip_click',
                $from_gmt
            ),
            ARRAY_A
        );

        $top_queries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT search_query, COUNT(*) AS searches, AVG(COALESCE(result_count, 0)) AS avg_results
                FROM {$table_name}
                WHERE event_type = %s AND created_at >= %s AND search_query <> ''
                GROUP BY search_query
                ORDER BY searches DESC
                LIMIT 10",
                'search',
                $from_gmt
            ),
            ARRAY_A
        );

        $zero_queries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT search_query, COUNT(*) AS misses
                FROM {$table_name}
                WHERE event_type = %s AND created_at >= %s AND result_count = 0 AND search_query <> ''
                GROUP BY search_query
                ORDER BY misses DESC
                LIMIT 10",
                'search',
                $from_gmt
            ),
            ARRAY_A
        );

        return array(
            'days'        => $days,
            'summary'     => $summary,
            'top_chips'   => is_array( $top_chips ) ? $top_chips : array(),
            'top_queries' => is_array( $top_queries ) ? $top_queries : array(),
            'zero_queries'=> is_array( $zero_queries ) ? $zero_queries : array(),
        );
    }
}
