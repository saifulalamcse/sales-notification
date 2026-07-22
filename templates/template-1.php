<?php
/**
 * Template 1 — Horizontal layout (image left, text right).
 *
 * Override this template from your theme by placing a copy at:
 *   yourtheme/sales-notification/template-1.php
 *
 * Available variables (passed via wp_localize_script — rendered by JS):
 * This PHP template provides the fallback SSR structure only.
 * The JavaScript engine creates the popup DOM dynamically.
 *
 * @package SalesNotification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php
/**
 * This template is rendered by the JavaScript engine (sales-notification.js).
 * The PHP template below provides the no-JS accessible fallback structure.
 * It is not directly output on the page — it is used only as a reference
 * for the JS templating system and for server-side rendering if needed.
 *
 * To customise the structure, override via JS hook:
 *   document.addEventListener('sn:show', function(e) { ... });
 */
?>
<div class="sn-popup sn-template-1" role="alert" aria-live="polite">
	<button class="sn-popup__close" aria-label="<?php esc_attr_e( 'Close notification', 'sales-notification' ); ?>">×</button>
	<div class="sn-popup__inner">
		<div class="sn-popup__image-wrap">
			<img class="sn-popup__image" src="" alt="" width="60" height="60" loading="lazy">
		</div>
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
