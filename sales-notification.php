<?php
/**
 * Plugin Name:       Sales Notification
 * Plugin URI:        https://saiful.com/plugins/sales-notification
 * Description:       Display real-time WooCommerce purchase notifications on product pages to build social proof and increase conversion rates.
 * Version:           1.0.5
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Saiful Alam
 * Author URI:        https://saiful-alam.lovable.app
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sales-notification
 * Domain Path:       /languages
 * WC requires at least: 6.0
 * WC tested up to:   9.0
 *
 * @package SalesNotification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// -----------------------------------------------------------------------
// Constants
// -----------------------------------------------------------------------
define( 'SN_VERSION',     '1.0.5' );
define( 'SN_PLUGIN_FILE', __FILE__ );
define( 'SN_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'SN_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'SN_PLUGIN_BASE', plugin_basename( __FILE__ ) );
define( 'SN_TEXT_DOMAIN', 'sales-notification' );

// -----------------------------------------------------------------------
// WooCommerce HPOS Compatibility Declaration
// -----------------------------------------------------------------------
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true
		);
	}
} );

// -----------------------------------------------------------------------
// Activation / Deactivation Hooks
// -----------------------------------------------------------------------
register_activation_hook( __FILE__, function () {
	require_once SN_PLUGIN_DIR . 'includes/class-sn-activator.php';
	SN_Activator::activate();
} );

register_deactivation_hook( __FILE__, function () {
	require_once SN_PLUGIN_DIR . 'includes/class-sn-deactivator.php';
	SN_Deactivator::deactivate();
} );

// -----------------------------------------------------------------------
// Bootstrap
// -----------------------------------------------------------------------
/**
 * Returns the main plugin instance.
 *
 * @return Sales_Notification
 */
function sales_notification() {
	return Sales_Notification::instance();
}

require_once SN_PLUGIN_DIR . 'includes/class-sales-notification.php';
sales_notification();
