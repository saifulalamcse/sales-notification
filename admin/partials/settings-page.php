<?php
/**
 * Admin settings page — shared wrapper and tabs.
 *
 * This file is the container for all settings tabs. Each tab partial
 * is loaded by class-sn-admin.php render_settings_page().
 *
 * @package SalesNotification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings   = SN_Settings::get_all();
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
$base_url   = admin_url( 'admin.php?page=sales-notification-settings' );

$tabs = array(
	'general'       => __( 'General', 'sales-notification' ),
	'notifications' => __( 'Notifications', 'sales-notification' ),
	'design'        => __( 'Design', 'sales-notification' ),
	'advanced'      => __( 'Advanced', 'sales-notification' ),
	'import-export' => __( 'Import / Export', 'sales-notification' ),
);
?>
<div class="wrap sn-wrap">
	<h1 class="sn-page-title">
		<span class="dashicons dashicons-megaphone"></span>
		<?php esc_html_e( 'Sales Notification — Settings', 'sales-notification' ); ?>
	</h1>

	<?php if ( ! class_exists( 'WooCommerce' ) ) : ?>
		<div class="notice notice-error inline"><p><?php esc_html_e( 'WooCommerce is not active.', 'sales-notification' ); ?></p></div>
	<?php endif; ?>

	<div class="sn-tabs-nav">
		<?php foreach ( $tabs as $tab_id => $tab_label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'tab', $tab_id, $base_url ) ); ?>"
			   class="sn-tab-link <?php echo $active_tab === $tab_id ? 'sn-tab-active' : ''; ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</div>

	<div id="sn-settings-notices" class="sn-notices"></div>

	<form id="sn-settings-form" class="sn-settings-form">
		<?php wp_nonce_field( 'sn_admin_nonce', 'sn_nonce' ); ?>
		<input type="hidden" name="tab" value="<?php echo esc_attr( $active_tab ); ?>">

		<?php
		// Load the active tab content.
		$tab_file = SN_PLUGIN_DIR . 'admin/partials/settings-' . $active_tab . '.php';
		if ( file_exists( $tab_file ) ) {
			include $tab_file;
		}
		?>

		<div class="sn-form-actions">
			<button type="submit" id="sn-save-settings" class="button button-primary sn-btn-primary">
				<?php esc_html_e( 'Save Settings', 'sales-notification' ); ?>
			</button>
		</div>
	</form>
</div>
