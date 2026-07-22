<?php
/**
 * Admin partial: Analytics Dashboard.
 *
 * @package SalesNotification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$analytics = new SN_Analytics();
$summary   = $analytics->get_summary();
$top_products = $analytics->get_top_products( 5 );
$chart_data   = $analytics->get_chart_data( 30 );
?>
<div class="sn-settings-wrap">
	<h1 class="sn-page-title">
		<span class="dashicons dashicons-megaphone"></span>
		<?php esc_html_e( 'Sales Notification — Dashboard', 'sales-notification' ); ?>
		<span class="sn-version">v<?php echo esc_html( SN_VERSION ); ?></span>
	</h1>

	<?php if ( ! SN_Settings::get( 'enable_analytics' ) ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<?php esc_html_e( 'Analytics tracking is disabled. ', 'sales-notification' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=sales-notification-settings&tab=advanced' ) ); ?>">
					<?php esc_html_e( 'Enable it in Advanced Settings.', 'sales-notification' ); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>

	<!-- Summary Cards -->
	<div class="sn-stats-grid">
		<div class="sn-stat-card">
			<div class="sn-stat-card__icon sn-stat-card__icon--blue">
				<span class="dashicons dashicons-visibility"></span>
			</div>
			<div class="sn-stat-card__body">
				<div class="sn-stat-card__value"><?php echo esc_html( number_format_i18n( $summary['impressions_total'] ) ); ?></div>
				<div class="sn-stat-card__label"><?php esc_html_e( 'Total Impressions', 'sales-notification' ); ?></div>
			</div>
		</div>
		<div class="sn-stat-card">
			<div class="sn-stat-card__icon sn-stat-card__icon--green">
				<span class="dashicons dashicons-admin-links"></span>
			</div>
			<div class="sn-stat-card__body">
				<div class="sn-stat-card__value"><?php echo esc_html( number_format_i18n( $summary['clicks_total'] ) ); ?></div>
				<div class="sn-stat-card__label"><?php esc_html_e( 'Total Clicks', 'sales-notification' ); ?></div>
			</div>
		</div>
		<div class="sn-stat-card">
			<div class="sn-stat-card__icon sn-stat-card__icon--purple">
				<span class="dashicons dashicons-chart-line"></span>
			</div>
			<div class="sn-stat-card__body">
				<div class="sn-stat-card__value"><?php echo esc_html( $summary['ctr'] ); ?>%</div>
				<div class="sn-stat-card__label"><?php esc_html_e( 'Click-Through Rate', 'sales-notification' ); ?></div>
			</div>
		</div>
		<div class="sn-stat-card">
			<div class="sn-stat-card__icon sn-stat-card__icon--orange">
				<span class="dashicons dashicons-no-alt"></span>
			</div>
			<div class="sn-stat-card__body">
				<div class="sn-stat-card__value"><?php echo esc_html( number_format_i18n( $summary['dismissals_total'] ) ); ?></div>
				<div class="sn-stat-card__label"><?php esc_html_e( 'Total Dismissals', 'sales-notification' ); ?></div>
			</div>
		</div>
	</div>

	<div class="sn-dashboard-row">
		<!-- Chart -->
		<div class="sn-card sn-card--chart">
			<div class="sn-card__header">
				<h2><?php esc_html_e( 'Last 30 Days', 'sales-notification' ); ?></h2>
			</div>
			<div class="sn-card__body">
				<canvas id="sn-analytics-chart" height="120"
					data-chart='<?php echo esc_attr( wp_json_encode( $chart_data ) ); ?>'></canvas>
			</div>
		</div>

		<!-- Top Products -->
		<div class="sn-card">
			<div class="sn-card__header">
				<h2><?php esc_html_e( 'Top Notified Products', 'sales-notification' ); ?></h2>
			</div>
			<div class="sn-card__body">
				<?php if ( empty( $top_products ) ) : ?>
					<p class="sn-empty"><?php esc_html_e( 'No data yet.', 'sales-notification' ); ?></p>
				<?php else : ?>
					<ol class="sn-top-products">
						<?php foreach ( $top_products as $item ) :
							$product = wc_get_product( $item->product_id );
							if ( ! $product ) continue;
							?>
							<li class="sn-top-products__item">
								<div class="sn-top-products__image">
									<?php echo $product->get_image( array( 40, 40 ) ); // phpcs:ignore ?>
								</div>
								<div class="sn-top-products__info">
									<span class="sn-top-products__name"><?php echo esc_html( $product->get_name() ); ?></span>
									<span class="sn-top-products__count"><?php echo esc_html( number_format_i18n( $item->total ) ); ?> <?php esc_html_e( 'impressions', 'sales-notification' ); ?></span>
								</div>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Quick Actions -->
	<div class="sn-quick-actions">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sales-notification-settings&tab=general' ) ); ?>" class="sn-btn sn-btn--secondary">
			<span class="dashicons dashicons-admin-settings"></span>
			<?php esc_html_e( 'Settings', 'sales-notification' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sales-notification-demo' ) ); ?>" class="sn-btn sn-btn--secondary">
			<span class="dashicons dashicons-format-chat"></span>
			<?php esc_html_e( 'Demo Notifications', 'sales-notification' ); ?>
		</a>
	</div>
</div>
