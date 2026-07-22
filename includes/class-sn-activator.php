<?php
/**
 * Fired during plugin activation.
 *
 * Creates custom database tables and schedules cron events.
 *
 * @package    SalesNotification
 * @subpackage SalesNotification/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SN_Activator {

	/**
	 * Current DB schema version.
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Run on plugin activation.
	 */
	public static function activate() {
		self::create_tables();
		self::schedule_cron();
		self::set_default_options();
		update_option( 'sn_db_version', self::DB_VERSION );

		/**
		 * Action: sn_plugin_activated
		 * Fires when the plugin is activated.
		 */
		do_action( 'sn_plugin_activated' );
	}

	/**
	 * Create the plugin's custom database tables.
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Analytics table.
		$analytics_table = "CREATE TABLE {$wpdb->prefix}sn_analytics (
			id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			notification_id VARCHAR(64)        NOT NULL DEFAULT '',
			product_id    BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			event_type    VARCHAR(20)         NOT NULL DEFAULT 'impression',
			page_url      TEXT,
			user_agent    VARCHAR(500)        DEFAULT NULL,
			ip_hash       VARCHAR(64)         DEFAULT NULL,
			created_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_notification_id (notification_id),
			KEY idx_product_id (product_id),
			KEY idx_event_type (event_type),
			KEY idx_created_at (created_at)
		) {$charset_collate};";

		// Demo notifications table.
		$demo_table = "CREATE TABLE {$wpdb->prefix}sn_demo_notifications (
			id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			customer_name VARCHAR(100)        NOT NULL DEFAULT '',
			product_id    BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			location      VARCHAR(200)        NOT NULL DEFAULT '',
			avatar_url    TEXT,
			time_offset   INT(11)             NOT NULL DEFAULT 3600,
			sort_order    INT(11)             NOT NULL DEFAULT 0,
			created_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_sort_order (sort_order)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $analytics_table );
		dbDelta( $demo_table );
	}

	/**
	 * Schedule the analytics pruning cron event.
	 */
	private static function schedule_cron() {
		if ( ! wp_next_scheduled( 'sn_prune_analytics' ) ) {
			wp_schedule_event( time(), 'daily', 'sn_prune_analytics' );
		}
	}

	/**
	 * Set default plugin options if not already set.
	 */
	private static function set_default_options() {
		if ( false === get_option( 'sales_notification_settings' ) ) {
			require_once SN_PLUGIN_DIR . 'includes/class-sn-settings.php';
			update_option( 'sales_notification_settings', SN_Settings::get_defaults() );
		}
	}
}
