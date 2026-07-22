<?php
/**
 * Admin partial: Advanced Settings tab.
 *
 * @package SalesNotification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = SN_Settings::get_all();

// Get all pages for the page selector.
$pages = get_pages( array( 'sort_column' => 'post_title', 'sort_order' => 'ASC' ) );

// Get all product categories.
$product_cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );

// Get products for the product selector.
$products = array();
if ( class_exists( 'WooCommerce' ) ) {
	$products = wc_get_products( array( 'limit' => 200, 'status' => 'publish', 'return' => 'objects' ) );
}

// Get all editable roles.
$all_roles = wp_roles()->get_names();

$consent_plugins = array(
	'cookieyes'           => __( 'CookieYes', 'sales-notification' ),
	'complianz'           => __( 'Complianz', 'sales-notification' ),
	'gdpr-cookie-consent' => __( 'GDPR Cookie Consent (WebToffee)', 'sales-notification' ),
);
?>
<div class="sn-settings-wrap">
	<h1 class="sn-page-title">
		<span class="dashicons dashicons-megaphone"></span>
		<?php esc_html_e( 'Sales Notification', 'sales-notification' ); ?>
		<span class="sn-version">v<?php echo esc_html( SN_VERSION ); ?></span>
	</h1>
	<?php
	$active_tab = 'advanced';
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
		<input type="hidden" name="sn_tab" value="advanced">

		<!-- Device Visibility -->
		<div class="sn-card">
			<div class="sn-card__header"><h2><?php esc_html_e( 'Device Visibility', 'sales-notification' ); ?></h2></div>
			<div class="sn-card__body">
				<div class="sn-field sn-field--switch">
					<label class="sn-label"><?php esc_html_e( 'Show on Desktop', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<label class="sn-toggle">
							<input type="checkbox" name="settings[show_desktop]" value="1" <?php checked( $settings['show_desktop'], true ); ?>>
							<span class="sn-toggle__slider"></span>
						</label>
					</div>
				</div>
				<div class="sn-field sn-field--switch">
					<label class="sn-label"><?php esc_html_e( 'Show on Mobile', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<label class="sn-toggle">
							<input type="checkbox" name="settings[show_mobile]" value="1" <?php checked( $settings['show_mobile'], true ); ?>>
							<span class="sn-toggle__slider"></span>
						</label>
					</div>
				</div>
				<div class="sn-field">
					<label class="sn-label" for="sn_mobile_breakpoint"><?php esc_html_e( 'Mobile Breakpoint (px)', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<input type="number" id="sn_mobile_breakpoint" name="settings[mobile_breakpoint]"
							value="<?php echo esc_attr( $settings['mobile_breakpoint'] ); ?>" min="320" max="1200" class="sn-input-sm">
						<p class="sn-field__desc"><?php esc_html_e( 'Screens narrower than this value are treated as mobile.', 'sales-notification' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<!-- Page Visibility -->
		<div class="sn-card">
			<div class="sn-card__header"><h2><?php esc_html_e( 'Page Visibility', 'sales-notification' ); ?></h2></div>
			<div class="sn-card__body">
				<div class="sn-field">
					<label class="sn-label"><?php esc_html_e( 'Visibility Mode', 'sales-notification' ); ?></label>
					<div class="sn-field__control sn-radio-group">
						<?php
						$visibility_modes = array(
							'all'     => __( 'Show on all pages', 'sales-notification' ),
							'include' => __( 'Show only on selected pages', 'sales-notification' ),
							'exclude' => __( 'Show on all pages except selected', 'sales-notification' ),
						);
						foreach ( $visibility_modes as $mode_key => $mode_label ) : ?>
							<label class="sn-radio">
								<input type="radio" name="settings[page_visibility_mode]" value="<?php echo esc_attr( $mode_key ); ?>"
									<?php checked( $settings['page_visibility_mode'], $mode_key ); ?>>
								<span><?php echo esc_html( $mode_label ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="sn-field sn-visibility-page-select">
					<label class="sn-label"><?php esc_html_e( 'Selected Pages', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<select name="settings[page_visibility_ids][]" multiple class="sn-select sn-select--multi sn-select--searchable">
							<?php foreach ( $pages as $page ) : ?>
								<option value="<?php echo esc_attr( $page->ID ); ?>"
									<?php echo in_array( $page->ID, (array) $settings['page_visibility_ids'], true ) ? 'selected' : ''; ?>>
									<?php echo esc_html( $page->post_title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
		</div>

		<!-- Product & Category Exclusion -->
		<div class="sn-card">
			<div class="sn-card__header"><h2><?php esc_html_e( 'Product & Category Exclusion', 'sales-notification' ); ?></h2></div>
			<div class="sn-card__body">
				<div class="sn-field">
					<label class="sn-label"><?php esc_html_e( 'Excluded Products', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<select name="settings[excluded_products][]" multiple class="sn-select sn-select--multi sn-select--searchable">
							<?php foreach ( $products as $product ) : ?>
								<option value="<?php echo esc_attr( $product->get_id() ); ?>"
									<?php echo in_array( $product->get_id(), (array) $settings['excluded_products'], true ) ? 'selected' : ''; ?>>
									<?php echo esc_html( $product->get_name() ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="sn-field__desc"><?php esc_html_e( 'Notifications for these products will never appear.', 'sales-notification' ); ?></p>
					</div>
				</div>
				<div class="sn-field">
					<label class="sn-label"><?php esc_html_e( 'Excluded Categories', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<select name="settings[excluded_categories][]" multiple class="sn-select sn-select--multi sn-select--searchable">
							<?php if ( ! is_wp_error( $product_cats ) ) : foreach ( $product_cats as $cat ) : ?>
								<option value="<?php echo esc_attr( $cat->term_id ); ?>"
									<?php echo in_array( $cat->term_id, (array) $settings['excluded_categories'], true ) ? 'selected' : ''; ?>>
									<?php echo esc_html( $cat->name ); ?>
								</option>
							<?php endforeach; endif; ?>
						</select>
						<p class="sn-field__desc"><?php esc_html_e( 'Notifications for products in these categories will never appear.', 'sales-notification' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<!-- Privacy & GDPR -->
		<div class="sn-card">
			<div class="sn-card__header"><h2><?php esc_html_e( 'Privacy & GDPR', 'sales-notification' ); ?></h2></div>
			<div class="sn-card__body">
				<div class="sn-field sn-field--switch">
					<label class="sn-label"><?php esc_html_e( 'GDPR Mode', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<label class="sn-toggle">
							<input type="checkbox" name="settings[gdpr_mode]" value="1" <?php checked( $settings['gdpr_mode'], true ); ?>>
							<span class="sn-toggle__slider"></span>
						</label>
						<p class="sn-field__desc"><?php esc_html_e( 'Suppress cookies and notifications until a consent signal is detected from a supported Consent Management Plugin (CMP).', 'sales-notification' ); ?></p>
					</div>
				</div>
				<div class="sn-field">
					<label class="sn-label" for="sn_cookie_expiry"><?php esc_html_e( 'Cookie Expiry', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<select id="sn_cookie_expiry" name="settings[cookie_expiry]" class="sn-select sn-select--sm">
							<option value="session" <?php selected( $settings['cookie_expiry'], 'session' ); ?>><?php esc_html_e( 'Session only', 'sales-notification' ); ?></option>
							<option value="1" <?php selected( $settings['cookie_expiry'], '1' ); ?>><?php esc_html_e( '1 Day', 'sales-notification' ); ?></option>
							<option value="7" <?php selected( $settings['cookie_expiry'], '7' ); ?>><?php esc_html_e( '7 Days', 'sales-notification' ); ?></option>
							<option value="30" <?php selected( $settings['cookie_expiry'], '30' ); ?>><?php esc_html_e( '30 Days', 'sales-notification' ); ?></option>
						</select>
					</div>
				</div>
				<div class="sn-field">
					<label class="sn-label"><?php esc_html_e( 'Compatible CMPs', 'sales-notification' ); ?></label>
					<div class="sn-field__control sn-checkbox-group">
						<?php foreach ( $consent_plugins as $cmp_key => $cmp_label ) : ?>
							<label class="sn-checkbox">
								<input type="checkbox" name="settings[consent_plugins][]" value="<?php echo esc_attr( $cmp_key ); ?>"
									<?php checked( in_array( $cmp_key, (array) $settings['consent_plugins'], true ) ); ?>>
								<span><?php echo esc_html( $cmp_label ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="sn-field sn-field--switch">
					<label class="sn-label"><?php esc_html_e( 'Remove All Data on Uninstall', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<label class="sn-toggle">
							<input type="checkbox" name="settings[remove_data_on_uninstall]" value="1" <?php checked( $settings['remove_data_on_uninstall'], true ); ?>>
							<span class="sn-toggle__slider"></span>
						</label>
						<p class="sn-field__desc"><?php esc_html_e( 'Delete all plugin tables and options when the plugin is uninstalled. This cannot be undone.', 'sales-notification' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<!-- User Visibility -->
		<div class="sn-card">
			<div class="sn-card__header"><h2><?php esc_html_e( 'User Visibility', 'sales-notification' ); ?></h2></div>
			<div class="sn-card__body">
				<div class="sn-field sn-field--switch">
					<label class="sn-label"><?php esc_html_e( 'Disable for Logged-in Users', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<label class="sn-toggle">
							<input type="checkbox" name="settings[disable_for_logged_in]" value="1" <?php checked( $settings['disable_for_logged_in'], true ); ?>>
							<span class="sn-toggle__slider"></span>
						</label>
					</div>
				</div>
				<div class="sn-field">
					<label class="sn-label"><?php esc_html_e( 'Disable for Roles', 'sales-notification' ); ?></label>
					<div class="sn-field__control sn-checkbox-group">
						<?php foreach ( $all_roles as $role_key => $role_name ) : ?>
							<label class="sn-checkbox">
								<input type="checkbox" name="settings[disable_for_roles][]" value="<?php echo esc_attr( $role_key ); ?>"
									<?php checked( in_array( $role_key, (array) $settings['disable_for_roles'], true ) ); ?>>
								<span><?php echo esc_html( $role_name ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Analytics & Debug -->
		<div class="sn-card">
			<div class="sn-card__header"><h2><?php esc_html_e( 'Analytics & Debug', 'sales-notification' ); ?></h2></div>
			<div class="sn-card__body">
				<div class="sn-field sn-field--switch">
					<label class="sn-label"><?php esc_html_e( 'Enable Analytics', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<label class="sn-toggle">
							<input type="checkbox" name="settings[enable_analytics]" value="1" <?php checked( $settings['enable_analytics'], true ); ?>>
							<span class="sn-toggle__slider"></span>
						</label>
						<p class="sn-field__desc"><?php esc_html_e( 'Track impressions, clicks, and dismissals.', 'sales-notification' ); ?></p>
					</div>
				</div>
				<div class="sn-field">
					<label class="sn-label" for="sn_analytics_retention"><?php esc_html_e( 'Analytics Retention (days)', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<input type="number" id="sn_analytics_retention" name="settings[analytics_retention_days]"
							value="<?php echo esc_attr( $settings['analytics_retention_days'] ); ?>" min="7" max="365" class="sn-input-sm">
					</div>
				</div>
				<div class="sn-field sn-field--switch">
					<label class="sn-label"><?php esc_html_e( 'Debug Mode', 'sales-notification' ); ?></label>
					<div class="sn-field__control">
						<label class="sn-toggle">
							<input type="checkbox" name="settings[debug_mode]" value="1" <?php checked( $settings['debug_mode'], true ); ?>>
							<span class="sn-toggle__slider"></span>
						</label>
						<p class="sn-field__desc"><?php esc_html_e( 'Log plugin events to the browser console. Disable on production.', 'sales-notification' ); ?></p>
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
