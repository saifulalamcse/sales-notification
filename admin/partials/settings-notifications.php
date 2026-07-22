<?php
/**
 * Admin partial: Notifications Settings tab.
 *
 * @package SalesNotification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = SN_Settings::get_all();
?>
<div class="sn-settings-wrap">
	<h1 class="sn-page-title">
		<span class="dashicons dashicons-megaphone"></span>
		<?php esc_html_e( 'Sales Notification', 'sales-notification' ); ?>
		<span class="sn-version">v<?php echo esc_html( SN_VERSION ); ?></span>
	</h1>
	<?php
	$active_tab = 'notifications';
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
		<input type="hidden" name="sn_tab" value="notifications">

		<!-- Display Elements -->
		<div class="sn-card">
			<div class="sn-card__header">
				<h2><?php esc_html_e( 'Display Elements', 'sales-notification' ); ?></h2>
				<p class="sn-card__subtitle"><?php esc_html_e( 'Choose which elements appear in each notification popup.', 'sales-notification' ); ?></p>
			</div>
			<div class="sn-card__body sn-toggle-grid">
				<?php
				$display_fields = array(
					'show_name'    => array( 'label' => __( 'Customer Name', 'sales-notification' ), 'desc' => __( 'e.g. "John D."', 'sales-notification' ) ),
					'show_image'   => array( 'label' => __( 'Product Image', 'sales-notification' ), 'desc' => __( 'Product thumbnail (60×60 px by default).', 'sales-notification' ) ),
					'show_location'=> array( 'label' => __( 'Location', 'sales-notification' ), 'desc' => __( 'City and country from billing address.', 'sales-notification' ) ),
					'show_time'    => array( 'label' => __( 'Purchase Time', 'sales-notification' ), 'desc' => __( 'e.g. "2 hours ago".', 'sales-notification' ) ),
					'show_avatar'  => array( 'label' => __( 'Customer Avatar', 'sales-notification' ), 'desc' => __( 'Gravatar image with initials fallback.', 'sales-notification' ) ),
				);
				foreach ( $display_fields as $field_key => $field ) : ?>
					<div class="sn-toggle-item">
						<label class="sn-toggle-label">
							<label class="sn-toggle">
								<input type="checkbox" name="settings[<?php echo esc_attr( $field_key ); ?>]" value="1"
									<?php checked( $settings[ $field_key ], true ); ?>>
								<span class="sn-toggle__slider"></span>
							</label>
							<div>
								<strong><?php echo esc_html( $field['label'] ); ?></strong>
								<p class="sn-field__desc"><?php echo esc_html( $field['desc'] ); ?></p>
							</div>
						</label>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Privacy -->
		<div class="sn-card">
			<div class="sn-card__header">
				<h2><?php esc_html_e( 'Privacy', 'sales-notification' ); ?></h2>
			</div>
			<div class="sn-card__body">
				<div class="sn-field sn-field--switch">
					<label class="sn-label"><?php esc_html_e( 'Truncate Last Name', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<label class="sn-toggle">
							<input type="checkbox" name="settings[privacy_truncate_last_name]" value="1"
								<?php checked( $settings['privacy_truncate_last_name'], true ); ?>>
							<span class="sn-toggle__slider"></span>
						</label>
						<p class="sn-field__desc"><?php esc_html_e( 'Show only the first initial of the last name, e.g. "John D." instead of "John Doe".', 'sales-notification' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<!-- Timing -->
		<div class="sn-card">
			<div class="sn-card__header">
				<h2><?php esc_html_e( 'Notification Timing', 'sales-notification' ); ?></h2>
			</div>
			<div class="sn-card__body">
				<?php
				$timing_fields = array(
					'initial_delay' => array(
						'label' => __( 'Initial Delay (seconds)', 'sales-notification' ),
						'desc'  => __( 'How many seconds after page load before the first notification appears.', 'sales-notification' ),
						'min'   => 0, 'max' => 300,
					),
					'duration' => array(
						'label' => __( 'Display Duration (seconds)', 'sales-notification' ),
						'desc'  => __( 'How long each notification remains visible before auto-hiding.', 'sales-notification' ),
						'min'   => 1, 'max' => 60,
					),
					'interval' => array(
						'label' => __( 'Interval Between Notifications (seconds)', 'sales-notification' ),
						'desc'  => __( 'How long to wait between hiding one notification and showing the next.', 'sales-notification' ),
						'min'   => 1, 'max' => 120,
					),
					'max_count' => array(
						'label' => __( 'Max Notifications per Session', 'sales-notification' ),
						'desc'  => __( 'Maximum number of notifications to show in a single visitor session.', 'sales-notification' ),
						'min'   => 1, 'max' => 50,
					),
				);
				foreach ( $timing_fields as $field_key => $field ) : ?>
					<div class="sn-field">
						<label class="sn-label" for="sn_<?php echo esc_attr( $field_key ); ?>">
							<?php echo esc_html( $field['label'] ); ?>
						</label>
						<div class="sn-field__control">
							<div class="sn-range-wrap">
								<input type="range" class="sn-range"
									min="<?php echo esc_attr( $field['min'] ); ?>"
									max="<?php echo esc_attr( $field['max'] ); ?>"
									value="<?php echo esc_attr( $settings[ $field_key ] ); ?>"
									data-target="sn_<?php echo esc_attr( $field_key ); ?>">
								<input type="number" id="sn_<?php echo esc_attr( $field_key ); ?>"
									name="settings[<?php echo esc_attr( $field_key ); ?>]"
									value="<?php echo esc_attr( $settings[ $field_key ] ); ?>"
									min="<?php echo esc_attr( $field['min'] ); ?>"
									max="<?php echo esc_attr( $field['max'] ); ?>"
									class="sn-input-sm">
							</div>
							<p class="sn-field__desc"><?php echo esc_html( $field['desc'] ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>

				<div class="sn-field sn-field--switch">
					<label class="sn-label"><?php esc_html_e( 'Loop Notifications', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<label class="sn-toggle">
							<input type="checkbox" name="settings[loop]" value="1"
								<?php checked( $settings['loop'], true ); ?>>
							<span class="sn-toggle__slider"></span>
						</label>
						<p class="sn-field__desc"><?php esc_html_e( 'Restart the notification queue after all notifications have been shown.', 'sales-notification' ); ?></p>
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
