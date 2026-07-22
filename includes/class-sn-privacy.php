<?php
/**
 * GDPR / Privacy integration.
 *
 * Registers data exporters and erasers with the WordPress Personal Data tools
 * so that notification analytics data linked to a user can be exported or deleted
 * on request (WP Admin → Tools → Erase Personal Data / Export Personal Data).
 *
 * @package    SalesNotification
 * @subpackage SalesNotification/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SN_Privacy {

	/**
	 * Register the plugin's personal data exporter.
	 *
	 * @param array $exporters Registered exporters.
	 * @return array
	 */
	public static function register_exporter( $exporters ) {
		$exporters['sales-notification'] = array(
			'exporter_friendly_name' => __( 'Sales Notification Analytics', 'sales-notification' ),
			'callback'               => array( __CLASS__, 'export_personal_data' ),
		);
		return $exporters;
	}

	/**
	 * Register the plugin's personal data eraser.
	 *
	 * @param array $erasers Registered erasers.
	 * @return array
	 */
	public static function register_eraser( $erasers ) {
		$erasers['sales-notification'] = array(
			'eraser_friendly_name' => __( 'Sales Notification Analytics', 'sales-notification' ),
			'callback'             => array( __CLASS__, 'erase_personal_data' ),
		);
		return $erasers;
	}

	/**
	 * Export personal analytics data for a given email address.
	 *
	 * The plugin does not store raw email addresses — it stores SHA-256 hashes of IPs.
	 * We include any analytics records whose ip_hash matches the hashed requester IP.
	 * Since we cannot reverse-lookup email → ip_hash, we query by notification_id patterns
	 * and export any records that might relate to the user.
	 *
	 * @param string $email_address The user's email address.
	 * @param int    $page          Page number (for pagination).
	 * @return array Export data.
	 */
	public static function export_personal_data( $email_address, $page = 1 ) {
		global $wpdb;

		$email_address = sanitize_email( $email_address );
		$items_per_page = 500;
		$offset         = ( $page - 1 ) * $items_per_page;
		$table          = $wpdb->prefix . 'sn_analytics';

		// We identify records by the email MD5 hash embedded in notification IDs.
		// Since ip_hash is SHA-256 of REMOTE_ADDR and not the email, we export
		// all records found for the email MD5 in notification_id patterns.
		$email_md5  = md5( strtolower( trim( $email_address ) ) );
		$like_param = '%' . $wpdb->esc_like( $email_md5 ) . '%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$records = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, notification_id, product_id, event_type, page_url, created_at
				 FROM {$table}
				 WHERE notification_id LIKE %s
				 LIMIT %d OFFSET %d",
				$like_param,
				$items_per_page,
				$offset
			),
			ARRAY_A
		);

		$export_items = array();

		foreach ( $records as $record ) {
			$export_items[] = array(
				'group_id'    => 'sn-analytics',
				'group_label' => __( 'Sales Notification Analytics', 'sales-notification' ),
				'item_id'     => 'sn-analytics-' . $record['id'],
				'data'        => array(
					array( 'name' => __( 'Event Type', 'sales-notification' ), 'value' => $record['event_type'] ),
					array( 'name' => __( 'Page URL', 'sales-notification' ),   'value' => $record['page_url'] ),
					array( 'name' => __( 'Date', 'sales-notification' ),       'value' => $record['created_at'] ),
				),
			);
		}

		$done = count( $records ) < $items_per_page;

		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	/**
	 * Erase personal analytics data for a given email address.
	 *
	 * @param string $email_address The user's email address.
	 * @param int    $page          Page number.
	 * @return array Erasure result.
	 */
	public static function erase_personal_data( $email_address, $page = 1 ) {
		global $wpdb;

		$email_address  = sanitize_email( $email_address );
		$email_md5      = md5( strtolower( trim( $email_address ) ) );
		$like_param     = '%' . $wpdb->esc_like( $email_md5 ) . '%';
		$table          = $wpdb->prefix . 'sn_analytics';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE notification_id LIKE %s",
				$like_param
			)
		);

		// Invalidate summary cache.
		delete_transient( 'sn_analytics_summary' );

		return array(
			'items_removed'  => (int) $deleted,
			'items_retained' => 0,
			'messages'       => array(),
			'done'           => true,
		);
	}
}

// Register hooks immediately (not through the loader, as they need to be early).
add_filter( 'wp_privacy_personal_data_exporters', array( 'SN_Privacy', 'register_exporter' ) );
add_filter( 'wp_privacy_personal_data_erasers',   array( 'SN_Privacy', 'register_eraser' ) );
