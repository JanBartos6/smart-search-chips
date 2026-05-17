<?php
/**
 * Analytics - Search and chip-click event tracking with admin dashboard helpers
 *
 * @package SmartSearchChips
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SSCHP_Analytics {

    /** @var string Cron hook for scheduled analytics digest emails */
    const DIGEST_HOOK = 'sschp_send_analytics_digest';

    /** @var string Admin-post action for sending a test digest */
    const TEST_DIGEST_ACTION = 'sschp_send_test_analytics_digest';

    /** @var string Database version option */
    const DB_VERSION_OPTION = 'sschp_analytics_db_version';

    /** @var string Current analytics schema version */
    const DB_VERSION = '1.2';

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
        add_action( 'wp_ajax_sschp_track_event', array( $this, 'ajax_track_event' ) );
        add_action( 'wp_ajax_nopriv_sschp_track_event', array( $this, 'ajax_track_event' ) );
        add_action( 'admin_post_sschp_export_analytics', array( $this, 'handle_export' ) );
        add_action( 'admin_post_' . self::TEST_DIGEST_ACTION, array( $this, 'handle_send_test_digest' ) );
        add_action( self::DIGEST_HOOK, array( __CLASS__, 'send_scheduled_digest' ) );
        add_action( 'updated_option', array( __CLASS__, 'handle_digest_option_changed' ), 10, 3 );
        add_action( 'added_option', array( __CLASS__, 'handle_digest_option_added' ), 10, 2 );
        add_action( 'deleted_option', array( __CLASS__, 'handle_digest_option_deleted' ), 10, 1 );

        self::schedule_digest();
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
            post_type varchar(32) DEFAULT '',
            product_category varchar(191) DEFAULT '',
            page_url varchar(255) DEFAULT '',
            result_count int(11) DEFAULT NULL,
            active_tag_count int(11) DEFAULT NULL,
            result_object_id bigint(20) unsigned DEFAULT NULL,
            result_position int(11) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY event_type_created_at (event_type, created_at),
            KEY language_created_at (language_code, created_at),
            KEY post_type_created_at (post_type, created_at),
            KEY product_category_created_at (product_category(100), created_at),
            KEY chip_label_created_at (chip_label(100), created_at),
            KEY search_query_created_at (search_query(100), created_at),
            KEY result_object_created_at (result_object_id, created_at),
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
        return $wpdb->prefix . 'sschp_events';
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
        return SSCHP_License::is_pro() && get_option( 'sschp_analytics_enabled', '0' ) === '1';
    }

    /**
     * Whether the current request should be ignored as administrator noise.
     *
     * @return bool
     */
    private static function should_ignore_current_user() {
        return get_option( 'sschp_analytics_ignore_admins', '1' ) === '1'
            && function_exists( 'current_user_can' )
            && current_user_can( 'manage_options' );
    }

    /**
     * Whether scheduled analytics digest emails are enabled.
     *
     * @return bool
     */
    public static function is_digest_enabled() {
        return self::is_enabled() && get_option( 'sschp_analytics_digest_enabled', '0' ) === '1';
    }

    /**
     * Return option keys that affect digest scheduling or content.
     *
     * @return array
     */
    public static function digest_option_keys() {
        return array(
            'sschp_analytics_enabled',
            'sschp_analytics_digest_enabled',
            'sschp_analytics_digest_recipients',
            'sschp_analytics_digest_frequency',
            'sschp_analytics_digest_day',
            'sschp_analytics_digest_time',
            'sschp_analytics_digest_date_range',
            'sschp_analytics_digest_include_csv',
            'sschp_analytics_digest_include_zero_results',
            'sschp_analytics_digest_include_top_chips',
            'sschp_analytics_digest_include_low_ctr',
        );
    }

    /**
     * Reschedule digest when a relevant option changes.
     *
     * @param string $option    Option name.
     * @param mixed  $old_value Old value.
     * @param mixed  $value     New value.
     */
    public static function handle_digest_option_changed( $option, $old_value, $value ) {
        if ( $old_value === $value ) {
            return;
        }

        if ( in_array( $option, self::digest_option_keys(), true ) ) {
            self::schedule_digest( true );
        }
    }

    /**
     * Schedule digest when a relevant option is first added.
     *
     * @param string $option Option name.
     * @param mixed  $value  Option value.
     */
    public static function handle_digest_option_added( $option, $value ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        if ( in_array( $option, self::digest_option_keys(), true ) ) {
            self::schedule_digest( true );
        }
    }

    /**
     * Unschedule or reschedule digest when a relevant option is removed.
     *
     * @param string $option Option name.
     */
    public static function handle_digest_option_deleted( $option ) {
        if ( in_array( $option, self::digest_option_keys(), true ) ) {
            self::schedule_digest( true );
        }
    }

    /**
     * Schedule the next digest email if enabled.
     *
     * @param bool $force Clear existing scheduled event first.
     * @return bool
     */
    public static function schedule_digest( $force = false ) {
        if ( ! function_exists( 'wp_next_scheduled' ) || ! function_exists( 'wp_schedule_single_event' ) ) {
            return false;
        }

        if ( ! self::is_digest_enabled() ) {
            self::unschedule_digest();
            return false;
        }

        if ( $force ) {
            self::unschedule_digest();
        } elseif ( wp_next_scheduled( self::DIGEST_HOOK ) ) {
            return true;
        }

        return (bool) wp_schedule_single_event( self::calculate_next_run_timestamp(), self::DIGEST_HOOK );
    }

    /**
     * Remove any scheduled digest event.
     *
     * @return void
     */
    public static function unschedule_digest() {
        if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
            wp_clear_scheduled_hook( self::DIGEST_HOOK );
            return;
        }

        if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_unschedule_event' ) ) {
            $timestamp = wp_next_scheduled( self::DIGEST_HOOK );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, self::DIGEST_HOOK );
            }
        }
    }

    /**
     * Calculate the next scheduled digest timestamp in the site timezone.
     *
     * @param int|null $from_timestamp Optional base timestamp for tests.
     * @return int
     */
    public static function calculate_next_run_timestamp( $from_timestamp = null ) {
        $timezone  = wp_timezone();
        $base      = null === $from_timestamp
            ? new DateTimeImmutable( 'now', $timezone )
            : ( new DateTimeImmutable( '@' . (int) $from_timestamp ) )->setTimezone( $timezone );
        $time      = self::get_digest_time_parts();
        $frequency = self::get_digest_frequency();
        $day       = self::get_digest_day();

        if ( 'monthly' === $frequency ) {
            $day_number = ctype_digit( $day ) ? max( 1, min( 28, (int) $day ) ) : 1;
            $candidate  = $base->setDate( (int) $base->format( 'Y' ), (int) $base->format( 'm' ), $day_number )->setTime( $time['hour'], $time['minute'] );

            if ( $candidate <= $base ) {
                $candidate = $candidate->modify( 'first day of next month' )->setDate(
                    (int) $candidate->modify( 'first day of next month' )->format( 'Y' ),
                    (int) $candidate->modify( 'first day of next month' )->format( 'm' ),
                    $day_number
                )->setTime( $time['hour'], $time['minute'] );
            }

            return $candidate->getTimestamp();
        }

        $weekdays = self::get_weekday_map();
        $target   = isset( $weekdays[ $day ] ) ? $weekdays[ $day ] : 1;
        $current  = (int) $base->format( 'N' );
        $days     = ( $target - $current + 7 ) % 7;
        $candidate = $base->modify( '+' . $days . ' days' )->setTime( $time['hour'], $time['minute'] );

        if ( $candidate <= $base ) {
            $candidate = $candidate->modify( '+7 days' );
        }

        return $candidate->getTimestamp();
    }

    /**
     * Send a scheduled digest and queue the next one.
     *
     * @return bool
     */
    public static function send_scheduled_digest() {
        if ( ! self::is_digest_enabled() ) {
            self::unschedule_digest();
            return false;
        }

        $sent = self::send_digest_email( false );
        self::schedule_digest( true );

        return $sent;
    }

    /**
     * Handle the admin "Send test email" action.
     */
    public function handle_send_test_digest() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to send analytics emails.', 'smart-search-chips' ) );
        }

        check_admin_referer( self::TEST_DIGEST_ACTION );

        $sent = self::send_digest_email( true );
        wp_safe_redirect(
            add_query_arg(
                'sschp_digest_test',
                $sent ? 'sent' : 'failed',
                admin_url( 'options-general.php?page=smart-search-chips-analytics' )
            )
        );
        exit;
    }

    /**
     * Send an analytics digest email to configured recipients.
     *
     * @param bool $is_test Whether this is a manual test email.
     * @return bool
     */
    public static function send_digest_email( $is_test = false ) {
        if ( ! self::is_enabled() ) {
            return false;
        }

        if ( ! $is_test && ! self::is_digest_enabled() ) {
            return false;
        }

        $recipients = self::get_digest_recipients();
        if ( empty( $recipients ) ) {
            return false;
        }

        $range     = self::get_digest_range_args();
        $analytics = self::get_dashboard_data( $range );
        $subject   = self::build_digest_subject( $analytics, $is_test );
        $message   = self::build_digest_message( $analytics, $is_test );
        $headers   = array( 'Content-Type: text/html; charset=UTF-8' );
        $files     = array();

        if ( self::get_digest_checkbox( 'sschp_analytics_digest_include_csv', '0' ) ) {
            $csv_file = self::create_digest_csv_attachment( $range );
            if ( '' !== $csv_file ) {
                $files[] = $csv_file;
            }
        }

        $sent = false;
        if ( function_exists( 'wp_mail' ) ) {
            $sent = (bool) wp_mail( $recipients, $subject, $message, $headers, $files );
        }

        foreach ( $files as $file ) {
            if ( is_string( $file ) && file_exists( $file ) ) {
                unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- temporary email attachment cleanup.
            }
        }

        return $sent;
    }

    /**
     * Build the analytics digest email subject.
     *
     * @param array $analytics Dashboard data.
     * @param bool  $is_test   Whether this is a test email.
     * @return string
     */
    private static function build_digest_subject( $analytics, $is_test ) {
        $host   = parse_url( home_url( '/' ), PHP_URL_HOST );
        $prefix = $is_test ? __( '[Test] ', 'smart-search-chips' ) : '';
        $range  = isset( $analytics['range']['from'], $analytics['range']['to'] )
            ? $analytics['range']['from'] . ' to ' . $analytics['range']['to']
            : '';

        return sprintf(
            /* translators: %1$s: optional test prefix, %2$s: site host, %3$s: date range */
            __( '%1$sSmart Search Chips analytics for %2$s (%3$s)', 'smart-search-chips' ),
            $prefix,
            $host ? $host : home_url( '/' ),
            $range
        );
    }

    /**
     * Build the analytics digest email body.
     *
     * @param array $analytics Dashboard data.
     * @param bool  $is_test   Whether this is a test email.
     * @return string
     */
    private static function build_digest_message( $analytics, $is_test ) {
        $summary         = isset( $analytics['summary'] ) && is_array( $analytics['summary'] ) ? $analytics['summary'] : array();
        $range           = isset( $analytics['range'] ) && is_array( $analytics['range'] ) ? $analytics['range'] : array();
        $include_zero    = self::get_digest_checkbox( 'sschp_analytics_digest_include_zero_results', '1' );
        $include_chips   = self::get_digest_checkbox( 'sschp_analytics_digest_include_top_chips', '1' );
        $include_low_ctr = self::get_digest_checkbox( 'sschp_analytics_digest_include_low_ctr', '1' );
        $recommendations = self::build_digest_recommendations( $analytics );

        ob_start();
        ?>
        <div style="font-family:Arial,sans-serif;color:#1d2327;line-height:1.5">
            <h1><?php echo esc_html( $is_test ? __( 'Smart Search Chips test analytics digest', 'smart-search-chips' ) : __( 'Smart Search Chips analytics digest', 'smart-search-chips' ) ); ?></h1>
            <p>
                <?php
                printf(
                    /* translators: %1$s: start date, %2$s: end date */
                    esc_html__( 'Local analytics report for %1$s to %2$s.', 'smart-search-chips' ),
                    esc_html( isset( $range['from'] ) ? $range['from'] : '' ),
                    esc_html( isset( $range['to'] ) ? $range['to'] : '' )
                );
                ?>
            </p>
            <p><?php esc_html_e( 'This email is generated by your WordPress site. Smart Search Chips does not send this analytics data to the plugin author or any external service.', 'smart-search-chips' ); ?></p>

            <h2><?php esc_html_e( 'Summary', 'smart-search-chips' ); ?></h2>
            <table cellpadding="8" cellspacing="0" border="1" style="border-collapse:collapse;border-color:#dcdcde">
                <tr><th align="left"><?php esc_html_e( 'Total searches', 'smart-search-chips' ); ?></th><td><?php echo esc_html( self::format_number( $summary['total_searches'] ?? 0 ) ); ?></td></tr>
                <tr><th align="left"><?php esc_html_e( 'Chip clicks', 'smart-search-chips' ); ?></th><td><?php echo esc_html( self::format_number( $summary['chip_clicks'] ?? 0 ) ); ?></td></tr>
                <tr><th align="left"><?php esc_html_e( 'Chip impressions', 'smart-search-chips' ); ?></th><td><?php echo esc_html( self::format_number( $summary['chip_impressions'] ?? 0 ) ); ?></td></tr>
                <tr><th align="left"><?php esc_html_e( 'Chip click-through rate', 'smart-search-chips' ); ?></th><td><?php echo esc_html( self::format_percent( $summary['chip_click_through_rate'] ?? 0 ) ); ?></td></tr>
                <tr><th align="left"><?php esc_html_e( 'Result clicks', 'smart-search-chips' ); ?></th><td><?php echo esc_html( self::format_number( $summary['result_clicks'] ?? 0 ) ); ?></td></tr>
                <tr><th align="left"><?php esc_html_e( 'Search-to-result-click rate', 'smart-search-chips' ); ?></th><td><?php echo esc_html( self::format_percent( $summary['search_to_result_click_rate'] ?? 0 ) ); ?></td></tr>
                <tr><th align="left"><?php esc_html_e( 'Zero-result searches', 'smart-search-chips' ); ?></th><td><?php echo esc_html( self::format_number( $summary['zero_result_search'] ?? 0 ) ); ?></td></tr>
                <tr><th align="left"><?php esc_html_e( 'Zero-result rate', 'smart-search-chips' ); ?></th><td><?php echo esc_html( self::format_percent( $summary['zero_result_rate'] ?? 0 ) ); ?></td></tr>
            </table>

            <h2><?php esc_html_e( 'Top queries', 'smart-search-chips' ); ?></h2>
            <?php echo self::render_email_table( $analytics['top_queries'] ?? array(), array( 'search_query' => __( 'Query', 'smart-search-chips' ), 'searches' => __( 'Searches', 'smart-search-chips' ), 'chip_clicks' => __( 'Chip clicks', 'smart-search-chips' ), 'result_clicks' => __( 'Result clicks', 'smart-search-chips' ), 'result_click_rate' => __( 'Result CTR', 'smart-search-chips' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- table renderer escapes cells. ?>

            <h2><?php esc_html_e( 'Top query refinements', 'smart-search-chips' ); ?></h2>
            <?php echo self::render_email_table( $analytics['top_refinements'] ?? array(), array( 'search_query' => __( 'Query', 'smart-search-chips' ), 'chip_label' => __( 'Chip', 'smart-search-chips' ), 'refinements' => __( 'Refinements', 'smart-search-chips' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- table renderer escapes cells. ?>

            <?php if ( $include_chips ) : ?>
                <h2><?php esc_html_e( 'Top clicked chips', 'smart-search-chips' ); ?></h2>
                <?php echo self::render_email_table( $analytics['top_chips'] ?? array(), array( 'chip_label' => __( 'Chip', 'smart-search-chips' ), 'clicks' => __( 'Clicks', 'smart-search-chips' ), 'visitors' => __( 'Visitors', 'smart-search-chips' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- table renderer escapes cells. ?>
            <?php endif; ?>

            <?php if ( $include_zero ) : ?>
                <h2><?php esc_html_e( 'Top zero-result queries', 'smart-search-chips' ); ?></h2>
                <?php echo self::render_email_table( $analytics['zero_queries'] ?? array(), array( 'search_query' => __( 'Query', 'smart-search-chips' ), 'misses' => __( 'Misses', 'smart-search-chips' ), 'last_seen' => __( 'Last seen', 'smart-search-chips' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- table renderer escapes cells. ?>
            <?php endif; ?>

            <?php if ( $include_low_ctr ) : ?>
                <h2><?php esc_html_e( 'Low CTR chips', 'smart-search-chips' ); ?></h2>
                <?php echo self::render_email_table( $analytics['low_chip_ctr'] ?? array(), array( 'chip_label' => __( 'Chip', 'smart-search-chips' ), 'impressions' => __( 'Impressions', 'smart-search-chips' ), 'clicks' => __( 'Clicks', 'smart-search-chips' ), 'click_through_rate' => __( 'CTR', 'smart-search-chips' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- table renderer escapes cells. ?>
            <?php endif; ?>

            <h2><?php esc_html_e( 'Recommendations', 'smart-search-chips' ); ?></h2>
            <ul>
                <?php foreach ( $recommendations as $recommendation ) : ?>
                    <li><?php echo esc_html( $recommendation ); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * Render a compact HTML table for email.
     *
     * @param array $rows    Row data.
     * @param array $columns Column key => label map.
     * @return string
     */
    private static function render_email_table( $rows, $columns ) {
        if ( empty( $rows ) || empty( $columns ) ) {
            return '<p>' . esc_html__( 'No data for this period.', 'smart-search-chips' ) . '</p>';
        }

        ob_start();
        ?>
        <table cellpadding="8" cellspacing="0" border="1" style="border-collapse:collapse;border-color:#dcdcde">
            <thead>
                <tr>
                    <?php foreach ( $columns as $label ) : ?>
                        <th align="left"><?php echo esc_html( $label ); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( array_slice( (array) $rows, 0, 10 ) as $row ) : ?>
                    <tr>
                        <?php foreach ( $columns as $key => $label ) : ?>
                            <td><?php echo esc_html( self::format_email_cell( $row[ $key ] ?? '', $key ) ); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * Format one email table value.
     *
     * @param mixed  $value Raw value.
     * @param string $key   Column key.
     * @return string
     */
    private static function format_email_cell( $value, $key = '' ) {
        if ( is_numeric( $value ) ) {
            return false !== strpos( (string) $key, 'rate' ) || false !== strpos( (string) $key, 'ctr' )
                ? self::format_percent( $value )
                : self::format_number( $value );
        }

        return (string) $value;
    }

    /**
     * Build recommendation bullets from analytics results.
     *
     * @param array $analytics Dashboard data.
     * @return array
     */
    private static function build_digest_recommendations( $analytics ) {
        $summary = isset( $analytics['summary'] ) && is_array( $analytics['summary'] ) ? $analytics['summary'] : array();
        $items   = array();

        if ( (int) ( $summary['zero_result_search'] ?? 0 ) > 0 ) {
            $items[] = __( 'Review zero-result queries and add missing content, synonyms, or chip tags where the catalog has matching items.', 'smart-search-chips' );
        }

        if ( (float) ( $summary['search_to_click_rate'] ?? 0 ) < 10 && (int) ( $summary['total_searches'] ?? 0 ) >= 20 ) {
            $items[] = __( 'Search-to-click rate is low. Review whether the first chips are specific enough and whether low CTR chips should be renamed or hidden.', 'smart-search-chips' );
        }

        if ( ! empty( $analytics['low_chip_ctr'] ) ) {
            $items[] = __( 'Low CTR chips are getting impressions without many clicks. Consider renaming, hiding, or replacing them with more buyer-oriented labels.', 'smart-search-chips' );
        }

        if ( empty( $items ) ) {
            $items[] = __( 'No urgent issues detected for this period. Keep monitoring zero-result queries and chip click-through rate as search traffic grows.', 'smart-search-chips' );
        }

        return $items;
    }

    /**
     * Create a temporary CSV attachment for digest emails.
     *
     * @param array $range Date range args.
     * @return string Temporary file path or empty string.
     */
    private static function create_digest_csv_attachment( $range ) {
        $events = self::get_recent_events( array_merge( $range, array( 'limit' => 0 ) ) );
        $file   = function_exists( 'wp_tempnam' ) ? wp_tempnam( 'sschp-analytics-digest.csv' ) : tempnam( sys_get_temp_dir(), 'sschp-analytics-' );

        if ( ! is_string( $file ) || '' === $file ) {
            return '';
        }

        $handle = fopen( $file, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- temporary CSV attachment.
        if ( ! $handle ) {
            return '';
        }

        self::write_csv_row( $handle, array( 'created_at_utc', 'event_type', 'language_code', 'post_type', 'product_category', 'search_query', 'chip_label', 'result_count', 'active_tag_count', 'result_object_id', 'result_position', 'page_url', 'visitor_id' ) );

        foreach ( $events as $event ) {
            self::write_csv_row(
                $handle,
                self::escape_csv_row(
                    array(
                        $event['created_at'] ?? '',
                        $event['event_type'] ?? '',
                        $event['language_code'] ?? '',
                        $event['post_type'] ?? '',
                        $event['product_category'] ?? '',
                        $event['search_query'] ?? '',
                        $event['chip_label'] ?? '',
                        $event['result_count'] ?? '',
                        $event['active_tag_count'] ?? '',
                        $event['result_object_id'] ?? '',
                        $event['result_position'] ?? '',
                        $event['page_url'] ?? '',
                        $event['visitor_id'] ?? '',
                    )
                )
            );
        }

        fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- temporary CSV attachment.

        return $file;
    }

    /**
     * Return sanitized digest recipients.
     *
     * @return array
     */
    public static function get_digest_recipients() {
        $raw = (string) get_option( 'sschp_analytics_digest_recipients', '' );

        if ( '' === trim( $raw ) ) {
            $raw = (string) get_option( 'admin_email', '' );
        }

        $parts      = preg_split( '/[\s,;]+/', $raw );
        $recipients = array();

        foreach ( (array) $parts as $part ) {
            $email = sanitize_email( $part );
            if ( '' !== $email && is_email( $email ) && ! in_array( $email, $recipients, true ) ) {
                $recipients[] = $email;
            }
        }

        return $recipients;
    }

    /**
     * Return digest date range args for dashboard queries.
     *
     * @return array
     */
    private static function get_digest_range_args() {
        $days     = self::get_digest_date_range_days();
        $timezone = wp_timezone();
        $to       = new DateTimeImmutable( 'now', $timezone );
        $from     = $to->sub( new DateInterval( 'P' . max( 0, $days - 1 ) . 'D' ) );

        return array(
            'from' => $from->format( 'Y-m-d' ),
            'to'   => $to->format( 'Y-m-d' ),
        );
    }

    /**
     * Return digest date range in days.
     *
     * @return int
     */
    private static function get_digest_date_range_days() {
        $days = absint( get_option( 'sschp_analytics_digest_date_range', 30 ) );

        return in_array( $days, array( 7, 30, 90 ), true ) ? $days : 30;
    }

    /**
     * Return sanitized digest frequency.
     *
     * @return string
     */
    private static function get_digest_frequency() {
        $frequency = sanitize_key( get_option( 'sschp_analytics_digest_frequency', 'weekly' ) );

        return in_array( $frequency, array( 'weekly', 'monthly' ), true ) ? $frequency : 'weekly';
    }

    /**
     * Return sanitized digest day.
     *
     * @return string
     */
    private static function get_digest_day() {
        $day = sanitize_key( get_option( 'sschp_analytics_digest_day', 'monday' ) );

        if ( isset( self::get_weekday_map()[ $day ] ) || preg_match( '/^(?:[1-9]|1[0-9]|2[0-8])$/', $day ) ) {
            return $day;
        }

        return 'monday';
    }

    /**
     * Return digest send time parts.
     *
     * @return array
     */
    private static function get_digest_time_parts() {
        $time = (string) get_option( 'sschp_analytics_digest_time', '09:00' );

        if ( ! preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $match ) ) {
            return array(
                'hour'   => 9,
                'minute' => 0,
            );
        }

        return array(
            'hour'   => (int) $match[1],
            'minute' => (int) $match[2],
        );
    }

    /**
     * Return weekday map using ISO-8601 weekday numbers.
     *
     * @return array
     */
    private static function get_weekday_map() {
        return array(
            'monday'    => 1,
            'tuesday'   => 2,
            'wednesday' => 3,
            'thursday'  => 4,
            'friday'    => 5,
            'saturday'  => 6,
            'sunday'    => 7,
        );
    }

    /**
     * Return a digest checkbox value.
     *
     * @param string $option  Option key.
     * @param string $default Default option value.
     * @return bool
     */
    private static function get_digest_checkbox( $option, $default = '0' ) {
        return get_option( $option, $default ) === '1';
    }

    /**
     * Format a number for emails without requiring admin-only helpers in tests.
     *
     * @param mixed $number Number.
     * @return string
     */
    private static function format_number( $number ) {
        return function_exists( 'number_format_i18n' )
            ? number_format_i18n( (float) $number )
            : number_format( (float) $number );
    }

    /**
     * Format a percentage for emails.
     *
     * @param mixed $number Number.
     * @return string
     */
    private static function format_percent( $number ) {
        $formatted = function_exists( 'number_format_i18n' )
            ? number_format_i18n( (float) $number, 1 )
            : number_format( (float) $number, 1 );

        return $formatted . '%';
    }

    /**
     * Track a frontend analytics event.
     */
    public function ajax_track_event() {
        check_ajax_referer( 'sschp_analytics_nonce', 'nonce' );

        if ( ! self::is_enabled() ) {
            wp_send_json_success( array( 'tracked' => false ) );
        }

        if ( self::should_ignore_current_user() ) {
            wp_send_json_success(
                array(
                    'tracked' => false,
                    'reason'  => 'admin_ignored',
                )
            );
        }

        $event_type = isset( $_POST['event_type'] ) ? sanitize_key( wp_unslash( $_POST['event_type'] ) ) : '';
        if ( ! in_array( $event_type, array( 'search', 'chip_click', 'chip_impression', 'result_click' ), true ) ) {
            wp_send_json_error( array( 'message' => __( 'Unsupported analytics event type.', 'smart-search-chips' ) ), 400 );
        }

        $visitor_id = isset( $_POST['visitor_id'] ) ? self::sanitize_visitor_id( wp_unslash( $_POST['visitor_id'] ) ) : '';
        if ( $visitor_id === '' ) {
            $visitor_id = wp_generate_uuid4();
        }

        $search_query     = isset( $_POST['search_query'] ) ? sanitize_text_field( wp_unslash( $_POST['search_query'] ) ) : '';
        $chip_label       = isset( $_POST['chip_label'] ) ? sanitize_text_field( wp_unslash( $_POST['chip_label'] ) ) : '';
        $post_type        = isset( $_POST['post_type'] ) ? sanitize_key( wp_unslash( $_POST['post_type'] ) ) : '';
        $product_category = isset( $_POST['product_category'] ) ? sanitize_title( wp_unslash( $_POST['product_category'] ) ) : '';
        $page_url         = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';
        $result_count     = isset( $_POST['result_count'] ) ? intval( wp_unslash( $_POST['result_count'] ) ) : null;
        $active_tag_count = isset( $_POST['active_tag_count'] ) ? max( 0, intval( wp_unslash( $_POST['active_tag_count'] ) ) ) : null;
        $result_object_id = isset( $_POST['result_object_id'] ) ? absint( wp_unslash( $_POST['result_object_id'] ) ) : 0;
        $result_position  = isset( $_POST['result_position'] ) ? absint( wp_unslash( $_POST['result_position'] ) ) : 0;
        $language_code    = SSCHP_Language::request_language(
            array(
                'sschp_lang' => isset( $_POST['sschp_lang'] ) ? sanitize_key( wp_unslash( $_POST['sschp_lang'] ) ) : '',
                'lang'       => isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( $_POST['lang'] ) ) : '',
            )
        );
        $chip_labels      = self::sanitize_chip_labels( isset( $_POST['chip_labels'] ) ? wp_unslash( $_POST['chip_labels'] ) : array() );
        $product_category = self::resolve_product_category_slug( $result_object_id, $product_category );

        if ( $event_type === 'search' && strlen( $search_query ) < 2 ) {
            wp_send_json_success( array( 'tracked' => false ) );
        }
        if ( $event_type === 'chip_impression' && empty( $chip_labels ) ) {
            wp_send_json_success( array( 'tracked' => false ) );
        }
        if ( $event_type === 'result_click' && $result_object_id <= 0 ) {
            wp_send_json_success( array( 'tracked' => false, 'reason' => 'missing_result_object' ) );
        }

        $tracking_decision = self::guard_tracking_request(
            array(
                'visitor_id'       => $visitor_id,
                'event_type'       => $event_type,
                'language_code'    => $language_code,
                'search_query'     => $search_query,
                'chip_label'       => $chip_label,
                'post_type'        => $post_type,
                'product_category' => $product_category,
                'page_url'         => $page_url,
                'result_count'     => $result_count,
                'active_tag_count' => $active_tag_count,
                'chip_labels'      => $chip_labels,
                'result_object_id' => $result_object_id,
                'result_position'  => $result_position,
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

        global $wpdb;

        $timestamp = current_time( 'mysql', true );
        $base_row  = array(
            'created_at'       => $timestamp,
            'visitor_id'       => substr( $visitor_id, 0, 64 ),
            'user_id'          => 0,
            'event_type'       => $event_type,
            'language_code'    => substr( $language_code, 0, 32 ),
            'search_query'     => substr( $search_query, 0, 191 ),
            'chip_label'       => substr( $chip_label, 0, 191 ),
            'post_type'        => substr( $post_type, 0, 32 ),
            'product_category' => substr( $product_category, 0, 191 ),
            'page_url'         => substr( $page_url, 0, 255 ),
            'result_count'     => $result_count,
            'active_tag_count' => $active_tag_count,
            'result_object_id' => $result_object_id > 0 ? $result_object_id : null,
            'result_position'  => $result_position > 0 ? $result_position : null,
        );
        $formats   = array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' );
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
     * @param array $args Date range and filter arguments.
     * @return array
     */
    public static function get_dashboard_data( $args = array() ) {
        global $wpdb;

        self::maybe_install();

        $table_name = self::table_name();
        $range      = self::get_date_range( $args );
        $filter     = self::get_report_filter_sql( $range, $args );

        $summary = array(
            'total_searches'      => (int) $wpdb->get_var( self::prepare_report_query( "SELECT COUNT(*) FROM {$table_name} WHERE event_type = %s AND {$filter['where']}", $filter, array( 'search' ) ) ),
            'unique_visitors'     => (int) $wpdb->get_var( self::prepare_report_query( "SELECT COUNT(DISTINCT visitor_id) FROM {$table_name} WHERE {$filter['where']}", $filter ) ),
            'zero_result_search'  => (int) $wpdb->get_var( self::prepare_report_query( "SELECT COUNT(*) FROM {$table_name} WHERE event_type = %s AND {$filter['where']} AND result_count = 0", $filter, array( 'search' ) ) ),
            'chip_impressions'    => (int) $wpdb->get_var( self::prepare_report_query( "SELECT COUNT(*) FROM {$table_name} WHERE event_type = %s AND {$filter['where']}", $filter, array( 'chip_impression' ) ) ),
            'chip_clicks'         => (int) $wpdb->get_var( self::prepare_report_query( "SELECT COUNT(*) FROM {$table_name} WHERE event_type = %s AND {$filter['where']}", $filter, array( 'chip_click' ) ) ),
            'result_clicks'       => (int) $wpdb->get_var( self::prepare_report_query( "SELECT COUNT(*) FROM {$table_name} WHERE event_type = %s AND {$filter['where']}", $filter, array( 'result_click' ) ) ),
        );
        $summary['search_linked_clicks'] = (int) $wpdb->get_var( self::prepare_report_query( "SELECT COUNT(*) FROM {$table_name} WHERE event_type = %s AND {$filter['where']} AND search_query <> ''", $filter, array( 'chip_click' ) ) );
        $summary['search_linked_result_clicks'] = (int) $wpdb->get_var( self::prepare_report_query( "SELECT COUNT(*) FROM {$table_name} WHERE event_type = %s AND {$filter['where']} AND search_query <> ''", $filter, array( 'result_click' ) ) );
        $summary = self::enrich_summary_rates( $summary );

        $top_chips = $wpdb->get_results(
            self::prepare_report_query(
                "SELECT chip_label, COUNT(*) AS clicks, COUNT(DISTINCT visitor_id) AS visitors
                FROM {$table_name}
                WHERE event_type = %s AND {$filter['where']} AND chip_label <> ''
                GROUP BY chip_label
                ORDER BY clicks DESC, visitors DESC
                LIMIT 10",
                $filter,
                array( 'chip_click' )
            ),
            ARRAY_A
        );

        $top_queries = $wpdb->get_results(
            self::prepare_report_query(
                "SELECT search_query,
                    SUM(CASE WHEN event_type = %s THEN 1 ELSE 0 END) AS searches,
                    SUM(CASE WHEN event_type = %s THEN 1 ELSE 0 END) AS chip_clicks,
                    SUM(CASE WHEN event_type = %s THEN 1 ELSE 0 END) AS result_clicks,
                    AVG(CASE WHEN event_type = %s THEN COALESCE(result_count, 0) ELSE NULL END) AS avg_results
                FROM {$table_name}
                WHERE event_type IN (%s, %s, %s) AND {$filter['where']} AND search_query <> ''
                GROUP BY search_query
                ORDER BY searches DESC
                LIMIT 10",
                $filter,
                array( 'search', 'chip_click', 'result_click', 'search', 'search', 'chip_click', 'result_click' )
            ),
            ARRAY_A
        );
        $top_queries = self::append_percentage_to_rows( $top_queries, 'chip_clicks', 'searches', 'click_rate' );
        $top_queries = self::append_percentage_to_rows( $top_queries, 'result_clicks', 'searches', 'result_click_rate' );

        $zero_queries = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix.
            self::prepare_report_query(
                "SELECT search_query, COUNT(*) AS misses, MIN(created_at) AS first_seen, MAX(created_at) AS last_seen
                FROM {$table_name}
                WHERE event_type = %s AND {$filter['where']} AND result_count = 0 AND search_query <> ''
                GROUP BY search_query
                ORDER BY misses DESC, last_seen DESC
                LIMIT 10",
                $filter,
                array( 'search' )
            ),
            ARRAY_A
        );
        $top_refinements = $wpdb->get_results(
            self::prepare_report_query(
                "SELECT search_query, chip_label, COUNT(*) AS refinements, COUNT(DISTINCT visitor_id) AS visitors
                FROM {$table_name}
                WHERE event_type = %s AND {$filter['where']} AND search_query <> '' AND chip_label <> ''
                GROUP BY search_query, chip_label
                ORDER BY refinements DESC, visitors DESC
                LIMIT 10",
                $filter,
                array( 'chip_click' )
            ),
            ARRAY_A
        );
        $top_chip_ctr = self::get_top_chip_ctr_rows( $range, $args );
        $low_chip_ctr = self::get_low_chip_ctr_rows( $range, $args );

        return array(
            'range'         => $range,
            'filters'       => $filter['filters'],
            'summary'       => $summary,
            'timeseries'    => self::get_time_series( $args ),
            'top_chips'     => is_array( $top_chips ) ? $top_chips : array(),
            'top_chip_ctr'  => $top_chip_ctr,
            'low_chip_ctr'  => $low_chip_ctr,
            'top_queries'   => is_array( $top_queries ) ? $top_queries : array(),
            'zero_queries'  => is_array( $zero_queries ) ? $zero_queries : array(),
            'top_refinements' => is_array( $top_refinements ) ? $top_refinements : array(),
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
        $filter     = self::get_report_filter_sql( $range, $args );
        $table_name = self::table_name();
        $timezone   = wp_timezone();
        $rows       = $wpdb->get_results(
            self::prepare_report_query(
                "SELECT created_at, event_type, visitor_id, result_count
                FROM {$table_name}
                WHERE {$filter['where']}
                ORDER BY created_at ASC",
                $filter
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
                'result_clicks'   => 0,
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
            } elseif ( $row['event_type'] === 'result_click' ) {
                $series[ $bucket ]['result_clicks']++;
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
        $filter     = self::get_report_filter_sql( $range, $args );
        $table_name = self::table_name();
        $limit      = isset( $args['limit'] ) ? absint( $args['limit'] ) : 100;

        if ( $limit > 0 ) {
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT created_at, event_type, language_code, search_query, chip_label, post_type, product_category, result_count, active_tag_count, result_object_id, result_position, page_url, visitor_id
                    FROM {$table_name}
                    WHERE {$filter['where']}
                    ORDER BY created_at DESC
                    LIMIT %d",
                    array_merge( $filter['params'], array( $limit ) )
                ),
                ARRAY_A
            );
        } else {
            $results = $wpdb->get_results(
                self::prepare_report_query(
                    "SELECT created_at, event_type, language_code, search_query, chip_label, post_type, product_category, result_count, active_tag_count, result_object_id, result_position, page_url, visitor_id
                    FROM {$table_name}
                    WHERE {$filter['where']}
                    ORDER BY created_at DESC",
                    $filter
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

        check_admin_referer( 'sschp_export_analytics' );

        if ( ! self::is_enabled() ) {
            wp_die( esc_html__( 'Analytics is not enabled.', 'smart-search-chips' ) );
        }

        $args   = array(
            'from'             => self::get_request_text_arg( 'from' ),
            'to'               => self::get_request_text_arg( 'to' ),
            'language'         => self::get_request_key_arg( 'language' ),
            'post_type'        => self::get_request_key_arg( 'post_type' ),
            'product_category' => sanitize_title( self::get_request_text_arg( 'product_category' ) ),
            'limit'            => 0,
        );
        $range  = self::get_date_range( $args );
        $events = self::get_recent_events( $args );

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=ssc-analytics-' . $range['from'] . '-to-' . $range['to'] . '.csv' );

        $out = fopen( 'php://output', 'w' );
        self::write_csv_row( $out, array( 'created_at_utc', 'event_type', 'language_code', 'post_type', 'product_category', 'search_query', 'chip_label', 'result_count', 'active_tag_count', 'result_object_id', 'result_position', 'page_url', 'visitor_id' ) );

        foreach ( $events as $event ) {
            self::write_csv_row(
                $out,
                self::escape_csv_row(
                    array(
                        $event['created_at'] ?? '',
                        $event['event_type'] ?? '',
                        $event['language_code'] ?? '',
                        $event['post_type'] ?? '',
                        $event['product_category'] ?? '',
                        $event['search_query'] ?? '',
                        $event['chip_label'] ?? '',
                        $event['result_count'] ?? '',
                        $event['active_tag_count'] ?? '',
                        $event['result_object_id'] ?? '',
                        $event['result_position'] ?? '',
                        $event['page_url'] ?? '',
                        $event['visitor_id'] ?? '',
                    )
                )
            );
        }

        fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- streaming CSV to php://output.
        exit;
    }

    /**
     * Read and sanitize an optional analytics export date query argument.
     *
     * @param string $key Query variable name.
     * @return string
     */
    private static function get_request_text_arg( $key ) {
        $value = filter_input( INPUT_GET, $key, FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        if ( null === $value || false === $value ) {
            return '';
        }

        return sanitize_text_field( wp_unslash( $value ) );
    }

    /**
     * Read and sanitize a key-style analytics export query argument.
     *
     * @param string $key Query variable name.
     * @return string
     */
    private static function get_request_key_arg( $key ) {
        return sanitize_key( self::get_request_text_arg( $key ) );
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
     * Build sanitized dashboard filters and SQL fragments.
     *
     * @param array $range Normalized date range.
     * @param array $args  Report arguments.
     * @return array
     */
    private static function get_report_filter_sql( $range, $args = array() ) {
        $filters = array(
            'language'         => isset( $args['language'] ) ? sanitize_key( $args['language'] ) : '',
            'post_type'        => isset( $args['post_type'] ) ? sanitize_key( $args['post_type'] ) : '',
            'product_category' => isset( $args['product_category'] ) ? sanitize_title( $args['product_category'] ) : '',
        );
        $where   = array( 'created_at BETWEEN %s AND %s' );
        $params  = array( $range['from_gmt'], $range['to_gmt'] );

        if ( '' !== $filters['language'] ) {
            $where[]  = 'language_code = %s';
            $params[] = $filters['language'];
        }

        if ( '' !== $filters['post_type'] ) {
            $where[]  = 'post_type = %s';
            $params[] = $filters['post_type'];
        }

        if ( '' !== $filters['product_category'] ) {
            $where[]  = 'product_category = %s';
            $params[] = $filters['product_category'];
        }

        return array(
            'where'   => implode( ' AND ', $where ),
            'params'  => $params,
            'filters' => $filters,
        );
    }

    /**
     * Prepare a dashboard query with shared report filters.
     *
     * @param string $query  SQL query.
     * @param array  $filter Filter SQL data.
     * @param array  $params Query parameters before filter parameters.
     * @return string
     */
    private static function prepare_report_query( $query, $filter, $params = array() ) {
        global $wpdb;

        return $wpdb->prepare( $query, array_merge( (array) $params, (array) $filter['params'] ) );
    }

    /**
     * Add calculated rates to a summary row.
     *
     * @param array $summary Raw summary values.
     * @return array
     */
    private static function enrich_summary_rates( $summary ) {
        $summary['zero_result_rate']            = self::calculate_percentage( $summary['zero_result_search'] ?? 0, $summary['total_searches'] ?? 0 );
        $summary['chip_click_through_rate']     = self::calculate_percentage( $summary['chip_clicks'] ?? 0, $summary['chip_impressions'] ?? 0 );
        $summary['search_to_click_rate']        = self::calculate_percentage( $summary['search_linked_clicks'] ?? 0, $summary['total_searches'] ?? 0 );
        $summary['search_to_result_click_rate'] = self::calculate_percentage( $summary['search_linked_result_clicks'] ?? 0, $summary['total_searches'] ?? 0 );

        return $summary;
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
        $labels = array();
        $clean  = array();

        if ( is_array( $raw_labels ) ) {
            array_walk_recursive(
                $raw_labels,
                function( $label ) use ( &$labels ) {
                    if ( is_scalar( $label ) ) {
                        $labels[] = $label;
                    }
                }
            );
        } elseif ( is_scalar( $raw_labels ) ) {
            $labels[] = $raw_labels;
        }

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
     * Resolve a product category slug from the clicked result when the browser did not provide one.
     *
     * @param int    $result_object_id Clicked post/product ID.
     * @param string $provided_slug    Category slug supplied by the browser.
     * @return string
     */
    private static function resolve_product_category_slug( $result_object_id, $provided_slug = '' ) {
        $provided_slug = sanitize_title( $provided_slug );
        if ( '' !== $provided_slug || $result_object_id <= 0 || ! function_exists( 'get_the_terms' ) ) {
            return $provided_slug;
        }

        $terms = get_the_terms( $result_object_id, 'product_cat' );
        if ( ! is_array( $terms ) || ( function_exists( 'is_wp_error' ) && is_wp_error( $terms ) ) ) {
            return '';
        }

        foreach ( $terms as $term ) {
            if ( isset( $term->slug ) && '' !== $term->slug ) {
                return sanitize_title( $term->slug );
            }
        }

        return '';
    }

    /**
     * Write a CSV row with explicit escape handling for PHP 8.4+.
     *
     * @param resource $handle CSV output handle.
     * @param array    $row    Row data.
     * @return int|false
     */
    private static function write_csv_row( $handle, $row ) {
        return fputcsv( $handle, $row, ',', '"', '' );
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

        $transient_key = 'sschp_rate_' . md5( $subject );
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
            'post_type'        => isset( $payload['post_type'] ) ? (string) $payload['post_type'] : '',
            'product_category' => isset( $payload['product_category'] ) ? (string) $payload['product_category'] : '',
            'page_url'         => isset( $payload['page_url'] ) ? (string) $payload['page_url'] : '',
            'result_count'     => isset( $payload['result_count'] ) ? (int) $payload['result_count'] : -1,
            'active_tag_count' => isset( $payload['active_tag_count'] ) ? (int) $payload['active_tag_count'] : 0,
            'result_object_id' => isset( $payload['result_object_id'] ) ? (int) $payload['result_object_id'] : 0,
            'result_position'  => isset( $payload['result_position'] ) ? (int) $payload['result_position'] : 0,
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

        $transient_key = 'sschp_evt_' . substr( $fingerprint, 0, 32 );

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
     * @param array $args  Filter arguments.
     * @return array
     */
    private static function get_top_chip_ctr_rows( $range, $args = array() ) {
        global $wpdb;

        $table  = self::table_name();
        $filter = self::get_report_filter_sql( $range, $args );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $table is from $wpdb->prefix, not user input.
        $rows = $wpdb->get_results(
            self::prepare_report_query(
                "SELECT chip_label,
                    SUM(CASE WHEN event_type = %s THEN 1 ELSE 0 END) AS impressions,
                    SUM(CASE WHEN event_type = %s THEN 1 ELSE 0 END) AS clicks,
                    COUNT(DISTINCT CASE WHEN event_type = %s THEN visitor_id ELSE NULL END) AS click_visitors,
                    ( SUM(CASE WHEN event_type = %s THEN 1 ELSE 0 END) / NULLIF(SUM(CASE WHEN event_type = %s THEN 1 ELSE 0 END), 0) ) AS click_ratio
                FROM {$table}
                WHERE event_type IN (%s, %s)
                    AND {$filter['where']}
                    AND chip_label <> ''
                GROUP BY chip_label
                HAVING impressions > 0
                ORDER BY click_ratio DESC, clicks DESC
                LIMIT 10",
                $filter,
                array( 'chip_impression', 'chip_click', 'chip_click', 'chip_click', 'chip_impression', 'chip_impression', 'chip_click' )
            ),
            ARRAY_A
        );

        return self::append_percentage_to_rows( is_array( $rows ) ? $rows : array(), 'clicks', 'impressions', 'click_through_rate' );
    }

    /**
     * Return chips that are shown often but clicked rarely.
     *
     * @param array $range Date range.
     * @param array $args  Filter arguments.
     * @return array
     */
    private static function get_low_chip_ctr_rows( $range, $args = array() ) {
        global $wpdb;

        $table  = self::table_name();
        $filter = self::get_report_filter_sql( $range, $args );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $table is from $wpdb->prefix, not user input.
        $rows = $wpdb->get_results(
            self::prepare_report_query(
                "SELECT chip_label,
                    SUM(CASE WHEN event_type = %s THEN 1 ELSE 0 END) AS impressions,
                    SUM(CASE WHEN event_type = %s THEN 1 ELSE 0 END) AS clicks,
                    ( SUM(CASE WHEN event_type = %s THEN 1 ELSE 0 END) / NULLIF(SUM(CASE WHEN event_type = %s THEN 1 ELSE 0 END), 0) ) AS click_ratio
                FROM {$table}
                WHERE event_type IN (%s, %s)
                    AND {$filter['where']}
                    AND chip_label <> ''
                GROUP BY chip_label
                HAVING impressions >= 3
                ORDER BY click_ratio ASC, impressions DESC
                LIMIT 10",
                $filter,
                array( 'chip_impression', 'chip_click', 'chip_click', 'chip_impression', 'chip_impression', 'chip_click' )
            ),
            ARRAY_A
        );

        return self::append_percentage_to_rows( is_array( $rows ) ? $rows : array(), 'clicks', 'impressions', 'click_through_rate' );
    }
}
