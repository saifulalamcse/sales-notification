<?php
/**
 * Analytics tracking, reporting, and database management.
 *
 * @package    SalesNotification
 * @subpackage SalesNotification/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SN_Analytics {

	/**
	 * Analytics table name (without prefix).
	 */
	const TABLE = 'sn_analytics';

	/**
	 * Maximum events accepted in a single batch request.
	 */
	const MAX_BATCH_SIZE = 50;

	/**
	 * Rate-limit: max events per IP per hour.
	 */
	const RATE_LIMIT = 100;

	// -----------------------------------------------------------------------
	// Event Recording
	// -----------------------------------------------------------------------

	/**
	 * Record an analytics event.
	 *
	 * @param string $notification_id Notification ID string.
	 * @param int    $product_id      Product ID.
	 * @param string $event_type      'impression' | 'click' | 'dismiss'.
	 * @param string $page_url        Current page URL.
	 * @return bool True on success.
	 */
	public function record_event( $notification_id, $product_id, $event_type, $page_url = '' ) {
		global $wpdb;

		$valid_types = array( 'impression', 'click', 'dismiss' );
		if ( ! in_array( $event_type, $valid_types, true ) ) {
			return false;
		}

		if ( empty( $notification_id ) ) {
			return false;
		}

		// Hash the IP with a per-site salt for GDPR compliance.
		// The salt makes rainbow-table reversal computationally infeasible.
		$raw_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ip_hash = $raw_ip ? hash( 'sha256', $raw_ip . NONCE_SALT ) : '';

		// Rate-limit: prevent analytics spam per IP.
		if ( $ip_hash && ! $this->check_rate_limit( $ip_hash ) ) {
			return false;
		}

		// Truncate user agent.
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$user_agent = mb_substr( $user_agent, 0, 500 );

		$table  = $wpdb->prefix . self::TABLE;
		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table,
			array(
				'notification_id' => sanitize_text_field( $notification_id ),
				'product_id'      => absint( $product_id ),
				'event_type'      => $event_type,
				'page_url'        => esc_url_raw( $page_url ),
				'user_agent'      => $user_agent,
				'ip_hash'         => $ip_hash,
				'created_at'      => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( $result ) {
			/**
			 * Action: sn_analytics_event_recorded
			 *
			 * @param string $event_type The event type.
			 * @param array  $data       The event data.
			 */
			do_action( 'sn_analytics_event_recorded', $event_type, array(
				'notification_id' => $notification_id,
				'product_id'      => $product_id,
				'page_url'        => $page_url,
			) );
		}

		return (bool) $result;
	}

	/**
	 * AJAX handler for tracking events from the frontend.
	 */
	public function ajax_track_event() {
		// Verify nonce — hard stop on failure.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sn_track_event' ) ) {
			wp_send_json_error( null, 403 );
			return;
		}

		if ( ! SN_Settings::get( 'enable_analytics' ) ) {
			wp_send_json_success( array( 'recorded' => false ) );
			return;
		}

		// Support batch events (capped to prevent abuse).
		if ( ! empty( $_POST['events'] ) && is_array( $_POST['events'] ) ) {
			$events  = array_slice( wp_unslash( $_POST['events'] ), 0, self::MAX_BATCH_SIZE ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$results = array();
			foreach ( $events as $event ) {
				$results[] = $this->record_event(
					isset( $event['notification_id'] ) ? sanitize_text_field( $event['notification_id'] ) : '',
					isset( $event['product_id'] ) ? absint( $event['product_id'] ) : 0,
					isset( $event['event_type'] ) ? sanitize_text_field( $event['event_type'] ) : '',
					isset( $event['page_url'] ) ? esc_url_raw( $event['page_url'] ) : ''
				);
			}
			wp_send_json_success( array( 'results' => $results ) );
			return;
		}

		// Single event.
		$recorded = $this->record_event(
			isset( $_POST['notification_id'] ) ? sanitize_text_field( wp_unslash( $_POST['notification_id'] ) ) : '',
			isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0,
			isset( $_POST['event_type'] ) ? sanitize_text_field( wp_unslash( $_POST['event_type'] ) ) : '',
			isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : ''
		);

		wp_send_json_success( array( 'recorded' => $recorded ) );
	}

	/**
	 * Transient-based per-IP rate limiter.
	 *
	 * @param string $ip_hash Hashed IP address.
	 * @return bool True if within limit, false if over.
	 */
	private function check_rate_limit( $ip_hash ) {
		$key   = 'sn_rl_' . substr( $ip_hash, 0, 16 );
		$count = (int) get_transient( $key );

		if ( $count >= self::RATE_LIMIT ) {
			return false;
		}

		if ( 0 === $count ) {
			set_transient( $key, 1, HOUR_IN_SECONDS );
		} else {
			// Increment without resetting TTL.
			set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		}

		return true;
	}

	// -----------------------------------------------------------------------
	// Reporting
	// -----------------------------------------------------------------------

	/**
	 * Get the overall analytics summary.
	 *
	 * @return array Summary data.
	 */
	public function get_summary() {
		$cached = get_transient( 'sn_analytics_summary' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$totals = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT event_type, COUNT(*) as total FROM %i GROUP BY event_type', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$table
			),
			ARRAY_A
		);

		$summary = array(
			'impressions_total' => 0,
			'clicks_total'      => 0,
			'dismissals_total'  => 0,
			'ctr'               => '0.00',
		);

		if ( is_array( $totals ) ) {
			foreach ( $totals as $row ) {
				switch ( $row['event_type'] ) {
					case 'impression':
						$summary['impressions_total'] = (int) $row['total'];
						break;
					case 'click':
						$summary['clicks_total'] = (int) $row['total'];
						break;
					case 'dismiss':
						$summary['dismissals_total'] = (int) $row['total'];
						break;
				}
			}
		}

		if ( $summary['impressions_total'] > 0 ) {
			$summary['ctr'] = number_format(
				( $summary['clicks_total'] / $summary['impressions_total'] ) * 100,
				2
			);
		}

		set_transient( 'sn_analytics_summary', $summary, HOUR_IN_SECONDS );

		return $summary;
	}

	/**
	 * Get the top N products by impression count.
	 *
	 * @param int $limit Number of products to return.
	 * @return array
	 */
	public function get_top_products( $limit = 5 ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		$limit = absint( $limit );

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT product_id, COUNT(*) as total
				 FROM {$table}
				 WHERE event_type = 'impression' AND product_id > 0
				 GROUP BY product_id
				 ORDER BY total DESC
				 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$limit
			)
		);
	}

	/**
	 * Get daily event counts for the last N days (for chart rendering).
	 *
	 * @param int $days Number of days to include.
	 * @return array [ 'labels' => [], 'impressions' => [], 'clicks' => [] ]
	 */
	public function get_chart_data( $days = 30 ) {
		global $wpdb;
		$table      = $wpdb->prefix . self::TABLE;
		$days       = absint( $days );
		$date_start = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT DATE(created_at) as day, event_type, COUNT(*) as total
				 FROM {$table}
				 WHERE created_at >= %s
				 GROUP BY day, event_type
				 ORDER BY day ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$date_start
			),
			ARRAY_A
		);

		// Build label array for all days.
		$labels      = array();
		$impressions = array();
		$clicks      = array();
		$data_map    = array();

		for ( $i = $days; $i >= 0; $i-- ) {
			$day              = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
			$labels[]         = gmdate( 'M j', strtotime( $day ) );
			$data_map[ $day ] = array( 'impression' => 0, 'click' => 0 );
		}

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$day  = $row['day'];
				$type = $row['event_type'];
				if ( isset( $data_map[ $day ][ $type ] ) ) {
					$data_map[ $day ][ $type ] = (int) $row['total'];
				}
			}
		}

		foreach ( $data_map as $day_data ) {
			$impressions[] = $day_data['impression'];
			$clicks[]      = $day_data['click'];
		}

		return compact( 'labels', 'impressions', 'clicks' );
	}

	// -----------------------------------------------------------------------
	// Maintenance
	// -----------------------------------------------------------------------

	/**
	 * Delete analytics records older than the configured retention period.
	 * Called by the sn_prune_analytics WP-Cron event.
	 */
	public function prune_old_records() {
		global $wpdb;

		$retention_days = absint( SN_Settings::get( 'analytics_retention_days', 90 ) );

		// Safety floor: never prune to less than 7 days.
		$retention_days = max( 7, $retention_days );

		$table       = $wpdb->prefix . self::TABLE;
		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$cutoff_date
			)
		);

		// Invalidate cached summary.
		delete_transient( 'sn_analytics_summary' );
	}
}
