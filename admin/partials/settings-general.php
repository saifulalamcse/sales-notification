<?php
/**
 * Admin partial: General Settings tab.
 *
 * @package SalesNotification
 * @var array $settings Current plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = SN_Settings::get_all();

$wc_statuses = array();
if ( function_exists( 'wc_get_order_statuses' ) ) {
	$wc_statuses = wc_get_order_statuses();
}
?>
<div class="sn-settings-wrap">
	<h1 class="sn-page-title">
		<span class="dashicons dashicons-megaphone"></span>
		<?php esc_html_e( 'Sales Notification', 'sales-notification' ); ?>
		<span class="sn-version">v<?php echo esc_html( SN_VERSION ); ?></span>
	</h1>

	<?php
	$active_tab = 'general';
	$base_url   = admin_url( 'admin.php?page=sales-notification-settings' );
	$tabs = array(
		'general'       => __( 'General', 'sales-notification' ),
		'notifications' => __( 'Notifications', 'sales-notification' ),
		'design'        => __( 'Design', 'sales-notification' ),
		'advanced'      => __( 'Advanced', 'sales-notification' ),
		'import-export' => __( 'Import / Export', 'sales-notification' ),
	);
	?>
	<nav class="sn-tab-nav">
		<?php foreach ( $tabs as $tab_id => $tab_label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'tab', $tab_id, $base_url ) ); ?>"
			   class="sn-tab<?php echo $active_tab === $tab_id ? ' sn-tab--active' : ''; ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div id="sn-notices" class="sn-notices-container"></div>

	<form id="sn-settings-form" method="post">
		<?php wp_nonce_field( 'sn_admin_nonce', 'sn_nonce' ); ?>
		<input type="hidden" name="sn_tab" value="general">

		<div class="sn-card">
			<div class="sn-card__header">
				<h2><?php esc_html_e( 'Plugin Status', 'sales-notification' ); ?></h2>
			</div>
			<div class="sn-card__body">
				<div class="sn-field sn-field--switch">
					<label class="sn-label" for="sn_enable">
						<?php esc_html_e( 'Enable Plugin', 'sales-notification' ); ?>
					</label>
					<div class="sn-field__control">
						<label class="sn-toggle">
							<input type="checkbox" id="sn_enable" name="settings[enable]" value="1"
								<?php checked( $settings['enable'], true ); ?>>
							<span class="sn-toggle__slider"></span>
						</label>
						<p class="sn-field__desc"><?php esc_html_e( 'Master switch — disable to stop all notifications without deactivating the plugin.', 'sales-notification' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<div class="sn-card">
			<div class="sn-card__header">
				<h2><?php esc_html_e( 'Notification Source', 'sales-notification' ); ?></h2>
			</div>
			<div class="sn-card__body">
				<div class="sn-field">
					<label class="sn-label"><?php esc_html_e( 'Data Source', 'sales-notification' ); ?></label>
					<div class="sn-field__control sn-radio-group">
						<label class="sn-radio">
							<input type="radio" name="settings[source]" value="real"
								<?php checked( $settings['source'], 'real' ); ?>>
							<span><?php esc_html_e( 'Real WooCommerce Orders', 'sales-notification' ); ?></span>
						</label>
						<label class="sn-radio">
							<input type="radio" name="settings[source]" value="demo"
								<?php checked( $settings['source'], 'demo' ); ?>>
							<span><?php esc_html_e( 'Demo Notifications', 'sales-notification' ); ?></span>
						</label>
					</div>
					<p class="sn-field__desc">
						<?php esc_html_e( 'Demo mode shows fake notifications — useful for testing appearance. A warning badge will appear in the admin bar when demo mode is active.', 'sales-notification' ); ?>
					</p>
				</div>

				<div class="sn-field sn-depends-on-real">
					<label class="sn-label" for="sn_order_statuses"><?php esc_html_e( 'Order Statuses', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<div class="sn-checkbox-group">
							<?php foreach ( $wc_statuses as $status_key => $status_label ) : ?>
								<label class="sn-checkbox">
									<input type="checkbox"
										name="settings[order_statuses][]"
										value="<?php echo esc_attr( $status_key ); ?>"
										<?php checked( in_array( $status_key, (array) $settings['order_statuses'], true ) ); ?>>
									<span><?php echo esc_html( $status_label ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
						<p class="sn-field__desc"><?php esc_html_e( 'Only orders with these statuses will appear in notifications.', 'sales-notification' ); ?></p>
					</div>
				</div>

				<div class="sn-field sn-depends-on-real">
					<label class="sn-label" for="sn_fetch_limit"><?php esc_html_e( 'Number of Recent Orders', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<input type="number" id="sn_fetch_limit" name="settings[fetch_limit]"
							value="<?php echo esc_attr( $settings['fetch_limit'] ); ?>" min="1" max="100" class="sn-input-sm">
						<p class="sn-field__desc"><?php esc_html_e( 'Maximum number of recent orders to fetch for notification display.', 'sales-notification' ); ?></p>
					</div>
				</div>

				<div class="sn-field sn-depends-on-real">
					<label class="sn-label" for="sn_fetch_days"><?php esc_html_e( 'Time Window (days)', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<input type="number" id="sn_fetch_days" name="settings[fetch_days]"
							value="<?php echo esc_attr( $settings['fetch_days'] ); ?>" min="1" max="365" class="sn-input-sm">
						<p class="sn-field__desc"><?php esc_html_e( 'Only fetch orders from the last N days.', 'sales-notification' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<div class="sn-form-footer">
			<button type="submit" class="sn-btn sn-btn--primary" id="sn-save-btn">
				<span class="sn-btn__icon dashicons dashicons-saved"></span>
				<?php esc_html_e( 'Save Settings', 'sales-notification' ); ?>
			</button>
		</div>
	</form>
</div>
