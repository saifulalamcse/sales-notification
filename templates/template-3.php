<?php
/**
 * Template 3 — Minimal text-only layout.
 *
 * Override this template from your theme by placing a copy at:
 *   yourtheme/sales-notification/template-3.php
 *
 * @package SalesNotification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="sn-popup sn-template-3" role="alert" aria-live="polite">
	<button class="sn-popup__close" aria-label="<?php esc_attr_e( 'Close notification', 'sales-notification' ); ?>">×</button>
	<div class="sn-popup__inner">
		<div class="sn-popup__content">
			<p class="sn-popup__name">
				<strong></strong> <?php esc_html_e( 'purchased', 'sales-notification' ); ?>
			</p>
			<p class="sn-popup__product"></p>
			<p class="sn-popup__meta">
				<span class="sn-popup__location"></span>
				<span class="sn-popup__time"></span>
			</p>
		</div>
	</div>
</div>
