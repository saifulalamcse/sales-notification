<?php
/**
 * Fired during plugin deactivation.
 *
 * @package    SalesNotification
 * @subpackage SalesNotification/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SN_Deactivator {

	/**
	 * Run on plugin deactivation.
	 *
	 * Clears transients and deschedules cron events.
	 * Database tables and options are intentionally preserved so data
	 * is not lost when a user temporarily deactivates the plugin.
	 */
	public static function deactivate() {
		// Clear notification data transients.
		self::clear_transients();

		// Remove the scheduled cron event.
		$timestamp = wp_next_scheduled( 'sn_prune_analytics' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'sn_prune_analytics' );
		}

		/**
		 * Action: sn_plugin_deactivated
		 * Fires when the plugin is deactivated.
		 */
		do_action( 'sn_plugin_deactivated' );
	}

	/**
	 * Delete all plugin-related transients from the options table.
	 */
	private static function clear_transients() {
		global $wpdb;
		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			 WHERE option_name LIKE '_transient_sn_%'
			    OR option_name LIKE '_transient_timeout_sn_%'"
		);
	}
}
