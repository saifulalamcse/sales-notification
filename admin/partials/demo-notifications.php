<?php
/**
 * Admin partial: Demo Notifications manager.
 *
 * @package SalesNotification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$demo_notifications = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	"SELECT * FROM {$wpdb->prefix}sn_demo_notifications ORDER BY sort_order ASC"
);

$products = array();
if ( class_exists( 'WooCommerce' ) ) {
	$products = wc_get_products( array( 'limit' => 500, 'status' => 'publish', 'return' => 'objects' ) );
}
?>
<div class="sn-settings-wrap">
	<h1 class="sn-page-title">
		<span class="dashicons dashicons-megaphone"></span>
		<?php esc_html_e( 'Demo Notifications', 'sales-notification' ); ?>
	</h1>

	<div class="sn-card">
		<div class="sn-card__header sn-card__header--flex">
			<h2><?php esc_html_e( 'Manage Demo Notifications', 'sales-notification' ); ?></h2>
			<button type="button" id="sn-add-demo-btn" class="sn-btn sn-btn--primary">
				<span class="dashicons dashicons-plus-alt2"></span>
				<?php esc_html_e( 'Add Notification', 'sales-notification' ); ?>
			</button>
		</div>
		<div class="sn-card__body">
			<p class="sn-card__subtitle">
				<?php esc_html_e( 'These fake notifications display when the source is set to "Demo". Drag rows to reorder.', 'sales-notification' ); ?>
			</p>

			<?php if ( empty( $demo_notifications ) ) : ?>
				<div class="sn-empty-state">
					<span class="dashicons dashicons-format-chat sn-empty-icon"></span>
					<p><?php esc_html_e( 'No demo notifications yet. Add one to get started.', 'sales-notification' ); ?></p>
				</div>
			<?php else : ?>
				<table class="sn-table widefat" id="sn-demo-table">
					<thead>
						<tr>
							<th class="sn-col-order"></th>
							<th><?php esc_html_e( 'Customer Name', 'sales-notification' ); ?></th>
							<th><?php esc_html_e( 'Product', 'sales-notification' ); ?></th>
							<th><?php esc_html_e( 'Location', 'sales-notification' ); ?></th>
							<th><?php esc_html_e( 'Time Offset', 'sales-notification' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'sales-notification' ); ?></th>
						</tr>
					</thead>
					<tbody id="sn-demo-list">
						<?php foreach ( $demo_notifications as $demo ) :
							$product = wc_get_product( $demo->product_id );
							$product_name = $product ? $product->get_name() : esc_html__( '(Deleted product)', 'sales-notification' );
							$offset_label = human_time_diff( time() - absint( $demo->time_offset ) );
							?>
							<tr class="sn-demo-row" data-id="<?php echo esc_attr( $demo->id ); ?>">
								<td class="sn-col-order"><span class="dashicons dashicons-move sn-drag-handle"></span></td>
								<td><?php echo esc_html( $demo->customer_name ); ?></td>
								<td><?php echo esc_html( $product_name ); ?></td>
								<td><?php echo esc_html( $demo->location ); ?></td>
								<td><?php echo esc_html( $offset_label ); ?> <?php esc_html_e( 'ago', 'sales-notification' ); ?></td>
								<td class="sn-col-actions">
									<button type="button" class="sn-btn sn-btn--xs sn-btn--secondary sn-edit-demo"
										data-id="<?php echo esc_attr( $demo->id ); ?>"
										data-name="<?php echo esc_attr( $demo->customer_name ); ?>"
										data-product="<?php echo esc_attr( $demo->product_id ); ?>"
										data-location="<?php echo esc_attr( $demo->location ); ?>"
										data-avatar="<?php echo esc_attr( $demo->avatar_url ); ?>"
										data-offset="<?php echo esc_attr( $demo->time_offset ); ?>">
										<?php esc_html_e( 'Edit', 'sales-notification' ); ?>
									</button>
									<button type="button" class="sn-btn sn-btn--xs sn-btn--danger sn-delete-demo"
										data-id="<?php echo esc_attr( $demo->id ); ?>">
										<?php esc_html_e( 'Delete', 'sales-notification' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>

	<!-- Add/Edit Modal -->
	<div id="sn-demo-modal" class="sn-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="sn-modal-title">
		<div class="sn-modal__overlay"></div>
		<div class="sn-modal__dialog">
			<div class="sn-modal__header">
				<h2 id="sn-modal-title"><?php esc_html_e( 'Add Demo Notification', 'sales-notification' ); ?></h2>
				<button type="button" class="sn-modal__close" aria-label="<?php esc_attr_e( 'Close', 'sales-notification' ); ?>">×</button>
			</div>
			<div class="sn-modal__body">
				<input type="hidden" id="sn-demo-id" value="0">
				<div class="sn-field">
					<label class="sn-label" for="sn-demo-name"><?php esc_html_e( 'Customer Name', 'sales-notification' ); ?> <span class="required">*</span></label>
					<input type="text" id="sn-demo-name" class="sn-input" placeholder="<?php esc_attr_e( 'e.g. John D.', 'sales-notification' ); ?>">
				</div>
				<div class="sn-field">
					<label class="sn-label" for="sn-demo-product"><?php esc_html_e( 'Product', 'sales-notification' ); ?> <span class="required">*</span></label>
					<select id="sn-demo-product" class="sn-select sn-select--searchable">
						<option value=""><?php esc_html_e( 'Select a product…', 'sales-notification' ); ?></option>
						<?php foreach ( $products as $product ) : ?>
							<option value="<?php echo esc_attr( $product->get_id() ); ?>"><?php echo esc_html( $product->get_name() ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="sn-field">
					<label class="sn-label" for="sn-demo-location"><?php esc_html_e( 'Location', 'sales-notification' ); ?></label>
					<input type="text" id="sn-demo-location" class="sn-input" placeholder="<?php esc_attr_e( 'e.g. New York, United States', 'sales-notification' ); ?>">
				</div>
				<div class="sn-field">
					<label class="sn-label" for="sn-demo-avatar"><?php esc_html_e( 'Avatar URL (optional)', 'sales-notification' ); ?></label>
					<input type="url" id="sn-demo-avatar" class="sn-input" placeholder="https://...">
				</div>
				<div class="sn-field">
					<label class="sn-label" for="sn-demo-offset"><?php esc_html_e( 'Time Offset (seconds ago)', 'sales-notification' ); ?></label>
					<input type="number" id="sn-demo-offset" class="sn-input-sm" value="3600" min="60">
					<p class="sn-field__desc"><?php esc_html_e( '3600 = 1 hour ago, 86400 = 1 day ago.', 'sales-notification' ); ?></p>
				</div>
			</div>
			<div class="sn-modal__footer">
				<button type="button" id="sn-demo-save" class="sn-btn sn-btn--primary"><?php esc_html_e( 'Save', 'sales-notification' ); ?></button>
				<button type="button" class="sn-btn sn-btn--ghost sn-modal__close"><?php esc_html_e( 'Cancel', 'sales-notification' ); ?></button>
			</div>
		</div>
	</div>

	<input type="hidden" id="sn-nonce" value="<?php echo esc_attr( wp_create_nonce( 'sn_admin_nonce' ) ); ?>">
</div>
