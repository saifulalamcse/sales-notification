<?php
/**
 * Admin partial: Import / Export Settings tab.
 *
 * @package SalesNotification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="sn-settings-wrap">
	<h1 class="sn-page-title">
		<span class="dashicons dashicons-megaphone"></span>
		<?php esc_html_e( 'Sales Notification', 'sales-notification' ); ?>
		<span class="sn-version">v<?php echo esc_html( SN_VERSION ); ?></span>
	</h1>
	<?php
	$active_tab = 'import-export';
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

	<!-- Export -->
	<div class="sn-card">
		<div class="sn-card__header">
			<h2><?php esc_html_e( 'Export Settings', 'sales-notification' ); ?></h2>
			<p class="sn-card__subtitle"><?php esc_html_e( 'Download all plugin settings as a JSON file. Use this to back up your configuration or migrate it to another site.', 'sales-notification' ); ?></p>
		</div>
		<div class="sn-card__body">
			<button type="button" id="sn-export-btn" class="sn-btn sn-btn--secondary">
				<span class="dashicons dashicons-download"></span>
				<?php esc_html_e( 'Export Settings', 'sales-notification' ); ?>
			</button>
		</div>
	</div>

	<!-- Import -->
	<div class="sn-card">
		<div class="sn-card__header">
			<h2><?php esc_html_e( 'Import Settings', 'sales-notification' ); ?></h2>
			<p class="sn-card__subtitle"><?php esc_html_e( 'Upload a previously exported JSON settings file to restore a configuration. This will overwrite your current settings.', 'sales-notification' ); ?></p>
		</div>
		<div class="sn-card__body">
			<div class="sn-import-drop-zone" id="sn-import-drop-zone">
				<span class="dashicons dashicons-upload sn-import-icon"></span>
				<p><?php esc_html_e( 'Drag & drop your JSON file here, or click to browse.', 'sales-notification' ); ?></p>
				<input type="file" id="sn-import-file" accept=".json" class="sn-file-input">
			</div>
			<div id="sn-import-preview" class="sn-import-preview" style="display:none;">
				<p class="sn-import-filename"></p>
				<button type="button" id="sn-import-btn" class="sn-btn sn-btn--primary">
					<span class="dashicons dashicons-upload"></span>
					<?php esc_html_e( 'Import Settings', 'sales-notification' ); ?>
				</button>
				<button type="button" id="sn-import-cancel" class="sn-btn sn-btn--ghost">
					<?php esc_html_e( 'Cancel', 'sales-notification' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Reset -->
	<div class="sn-card sn-card--danger">
		<div class="sn-card__header">
			<h2><?php esc_html_e( 'Reset Settings', 'sales-notification' ); ?></h2>
			<p class="sn-card__subtitle"><?php esc_html_e( 'Restore all plugin settings to their factory defaults. This cannot be undone.', 'sales-notification' ); ?></p>
		</div>
		<div class="sn-card__body">
			<button type="button" id="sn-reset-btn" class="sn-btn sn-btn--danger">
				<span class="dashicons dashicons-trash"></span>
				<?php esc_html_e( 'Reset to Defaults', 'sales-notification' ); ?>
			</button>
		</div>
	</div>

	<!-- Hidden nonce for AJAX -->
	<input type="hidden" id="sn-nonce" value="<?php echo esc_attr( wp_create_nonce( 'sn_admin_nonce' ) ); ?>">
</div>
