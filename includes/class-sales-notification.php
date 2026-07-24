<?php
/**
 * The core plugin class.
 *
 * Maintains the loader, internationalization, and registers all hooks for
 * the admin and the public-facing side of the site.
 *
 * @package    SalesNotification
 * @subpackage SalesNotification/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sales_Notification {

	/**
	 * Singleton instance.
	 *
	 * @var Sales_Notification
	 */
	private static $instance = null;

	/**
	 * The loader responsible for maintaining and registering all hooks.
	 *
	 * @var SN_Loader
	 */
	protected $loader;

	/**
	 * Returns the singleton instance.
	 *
	 * @return Sales_Notification
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — load dependencies and set up hooks.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_api_hooks();
		$this->setup_updater();
		$this->loader->run();
	}

	/**
	 * Load all required class files.
	 */
	private function load_dependencies() {
		require_once SN_PLUGIN_DIR . 'includes/class-sn-loader.php';
		require_once SN_PLUGIN_DIR . 'includes/class-sn-i18n.php';
		require_once SN_PLUGIN_DIR . 'includes/class-sn-settings.php';
		require_once SN_PLUGIN_DIR . 'includes/class-sn-woocommerce.php';
		require_once SN_PLUGIN_DIR . 'includes/class-sn-notification-data.php';
		require_once SN_PLUGIN_DIR . 'includes/class-sn-template-loader.php';
		require_once SN_PLUGIN_DIR . 'includes/class-sn-analytics.php';
		require_once SN_PLUGIN_DIR . 'includes/class-sn-rest-api.php';
		require_once SN_PLUGIN_DIR . 'includes/class-sn-privacy.php';
		require_once SN_PLUGIN_DIR . 'admin/class-sn-admin.php';
		require_once SN_PLUGIN_DIR . 'includes/class-sn-updater.php';

		$this->loader = new SN_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 */
	private function set_locale() {
		$plugin_i18n = new SN_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all hooks for the admin area.
	 */
	private function define_admin_hooks() {
		$admin = new SN_Admin();

		$this->loader->add_action( 'admin_menu',            $admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_init',            $admin, 'register_settings' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_notices',         $admin, 'admin_notices' );
		$this->loader->add_action( 'admin_bar_menu',        $admin, 'admin_bar_demo_indicator', 100 );

		// AJAX handlers (admin).
		$this->loader->add_action( 'wp_ajax_sn_save_settings',         $admin, 'ajax_save_settings' );
		$this->loader->add_action( 'wp_ajax_sn_export_settings',        $admin, 'ajax_export_settings' );
		$this->loader->add_action( 'wp_ajax_sn_import_settings',        $admin, 'ajax_import_settings' );
		$this->loader->add_action( 'wp_ajax_sn_reset_settings',         $admin, 'ajax_reset_settings' );
		$this->loader->add_action( 'wp_ajax_sn_save_demo_notification', $admin, 'ajax_save_demo_notification' );
		$this->loader->add_action( 'wp_ajax_sn_delete_demo_notification',$admin, 'ajax_delete_demo_notification' );
		$this->loader->add_action( 'wp_ajax_sn_reorder_demo_notifications',$admin, 'ajax_reorder_demo_notifications' );
		$this->loader->add_action( 'wp_ajax_sn_get_analytics',                $admin, 'ajax_get_analytics' );
	}

	/**
	 * Register all hooks for the public-facing side.
	 */
	private function define_public_hooks() {
		$notification_data = new SN_Notification_Data();
		$analytics         = new SN_Analytics();

		// Enqueue frontend assets.
		$this->loader->add_action( 'wp_enqueue_scripts', $notification_data, 'enqueue_frontend_assets' );

		// Invalidate cache when a new order is created or status changes.
		$this->loader->add_action( 'woocommerce_new_order',            $notification_data, 'invalidate_cache' );
		$this->loader->add_action( 'woocommerce_order_status_changed', $notification_data, 'invalidate_cache' );

		// AJAX analytics events (no-priv for logged-out visitors).
		$this->loader->add_action( 'wp_ajax_sn_track_event',        $analytics, 'ajax_track_event' );
		$this->loader->add_action( 'wp_ajax_nopriv_sn_track_event', $analytics, 'ajax_track_event' );

		// Cron for analytics pruning.
		$this->loader->add_action( 'sn_prune_analytics', $analytics, 'prune_old_records' );
	}

	/**
	 * Register REST API hooks.
	 */
	private function define_api_hooks() {
		$rest_api = new SN_REST_API();
		$this->loader->add_action( 'rest_api_init', $rest_api, 'register_routes' );
	}

	/**
	 * Initialise the automatic update checker.
	 *
	 * Hooked to plugins_loaded so that PUC is always available to both
	 * the WordPress admin and WP-CLI / management tools — not just on
	 * admin pages. The init() method is a no-op when credentials are not
	 * yet configured, so this is safe to call unconditionally.
	 */
	private function setup_updater() {
		// Initialise updater. If plugins_loaded has already fired, run init() immediately.
		if ( did_action( 'plugins_loaded' ) ) {
			SN_Updater::init();
		} else {
			add_action( 'plugins_loaded', array( 'SN_Updater', 'init' ), 5 );
			// Also run immediately to guarantee hooks are registered even if priority 5 passed
			SN_Updater::init();
		}
	}

	/**
	 * Returns the loader instance.
	 *
	 * @return SN_Loader
	 */
	public function get_loader() {
		return $this->loader;
	}
}
