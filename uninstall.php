<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Removes all plugin data from the database when the user deletes the plugin
 * from the WordPress admin.
 *
 * @package SalesNotification
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// -----------------------------------------------------------------------
// Remove plugin options
// -----------------------------------------------------------------------
delete_option( 'sales_notification_settings' );
delete_option( 'sn_db_version' );

// -----------------------------------------------------------------------
// Remove transients
// -----------------------------------------------------------------------
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sn_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_sn_%'" );

// -----------------------------------------------------------------------
// Drop custom tables (only if user opts in — check option)
// -----------------------------------------------------------------------
$settings = get_option( 'sales_notification_settings', array() );
$remove_data = isset( $settings['remove_data_on_uninstall'] ) ? (bool) $settings['remove_data_on_uninstall'] : false;

if ( $remove_data ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}sn_analytics" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}sn_demo_notifications" );
}

// -----------------------------------------------------------------------
// Clear scheduled cron events
// -----------------------------------------------------------------------
$timestamp = wp_next_scheduled( 'sn_prune_analytics' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'sn_prune_analytics' );
}
