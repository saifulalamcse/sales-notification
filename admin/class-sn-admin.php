<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin admin menu, settings registration, asset enqueueing,
 * AJAX handlers, and admin notices.
 *
 * @package    SalesNotification
 * @subpackage SalesNotification/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SN_Admin {

	/**
	 * The admin page slug.
	 */
	const PAGE_SLUG = 'sales-notification';

	// -----------------------------------------------------------------------
	// Menu
	// -----------------------------------------------------------------------

	/**
	 * Add the plugin admin menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			esc_html__( 'Sales Notification', 'sales-notification' ),
			esc_html__( 'Sales Notification', 'sales-notification' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( $this, 'render_dashboard_page' ),
			'dashicons-megaphone',
			56
		);

		add_submenu_page(
			self::PAGE_SLUG,
			esc_html__( 'Dashboard', 'sales-notification' ),
			esc_html__( 'Dashboard', 'sales-notification' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( $this, 'render_dashboard_page' )
		);

		add_submenu_page(
			self::PAGE_SLUG,
			esc_html__( 'Settings', 'sales-notification' ),
			esc_html__( 'Settings', 'sales-notification' ),
			'manage_woocommerce',
			self::PAGE_SLUG . '-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			self::PAGE_SLUG,
			esc_html__( 'Demo Notifications', 'sales-notification' ),
			esc_html__( 'Demo Notifications', 'sales-notification' ),
			'manage_woocommerce',
			self::PAGE_SLUG . '-demo',
			array( $this, 'render_demo_page' )
		);
	}

	// -----------------------------------------------------------------------
	// Settings Registration
	// -----------------------------------------------------------------------

	/**
	 * Register settings with the WordPress Settings API.
	 */
	public function register_settings() {
		register_setting(
			'sn_settings_group',
			SN_Settings::OPTION_KEY,
			array(
				'sanitize_callback' => array( 'SN_Settings', 'sanitize' ),
			)
		);
	}

	// -----------------------------------------------------------------------
	// Asset Enqueueing
	// -----------------------------------------------------------------------

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		$valid_hooks = array(
			'toplevel_page_' . self::PAGE_SLUG,
			'sales-notification_page_' . self::PAGE_SLUG . '-settings',
			'sales-notification_page_' . self::PAGE_SLUG . '-demo',
		);

		if ( ! in_array( $hook_suffix, $valid_hooks, true ) ) {
			return;
		}

		// WordPress built-in color picker.
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		// Admin styles.
		wp_enqueue_style(
			'sn-admin',
			SN_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			SN_VERSION
		);

		// Admin scripts.
		wp_enqueue_script(
			'sn-admin',
			SN_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			SN_VERSION,
			true
		);

		// Pass data to admin JS.
		wp_localize_script( 'sn-admin', 'snAdmin', array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'sn_admin_nonce' ),
			'pluginUrl' => SN_PLUGIN_URL,
			'settings'  => SN_Settings::get_all(),
			'i18n'      => array(
				'saved'          => __( 'Settings saved.', 'sales-notification' ),
				'error'          => __( 'An error occurred. Please try again.', 'sales-notification' ),
				'confirmReset'   => __( 'Are you sure you want to reset all settings to defaults?', 'sales-notification' ),
				'confirmDelete'  => __( 'Are you sure you want to delete this demo notification?', 'sales-notification' ),
				'importSuccess'  => __( 'Settings imported successfully.', 'sales-notification' ),
				'importError'    => __( 'Import failed: invalid file format.', 'sales-notification' ),
			),
		) );
	}

	// -----------------------------------------------------------------------
	// Page Renderers
	// -----------------------------------------------------------------------

	/**
	 * Render the dashboard (analytics) page.
	 */
	public function render_dashboard_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'sales-notification' ) );
		}
		include SN_PLUGIN_DIR . 'admin/partials/dashboard.php';
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'sales-notification' ) );
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
		include SN_PLUGIN_DIR . 'admin/partials/settings-' . $active_tab . '.php';
	}

	/**
	 * Render the demo notifications management page.
	 */
	public function render_demo_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'sales-notification' ) );
		}
		include SN_PLUGIN_DIR . 'admin/partials/demo-notifications.php';
	}

	// -----------------------------------------------------------------------
	// Admin Notices
	// -----------------------------------------------------------------------

	/**
	 * Display admin notices.
	 */
	public function admin_notices() {
		// WooCommerce not active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'Sales Notification requires WooCommerce to be installed and active.', 'sales-notification' ); ?></p>
			</div>
			<?php
			return;
		}

		// Analytics table missing.
		global $wpdb;
		$table_name = $wpdb->prefix . 'sn_analytics';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			?>
			<div class="notice notice-warning">
				<p><?php esc_html_e( 'Sales Notification: Analytics table could not be found. Please deactivate and reactivate the plugin.', 'sales-notification' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Add a demo mode indicator to the admin bar for logged-in admins.
	 *
	 * @param WP_Admin_Bar $admin_bar The admin bar object.
	 */
	public function admin_bar_demo_indicator( $admin_bar ) {
		if ( ! is_admin_bar_showing() ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( ! SN_Settings::is_demo_mode() ) {
			return;
		}

		$admin_bar->add_node( array(
			'id'    => 'sn-demo-mode',
			'title' => '<span class="sn-demo-badge">⚠ ' . esc_html__( 'SN: Demo Mode Active', 'sales-notification' ) . '</span>',
			'href'  => admin_url( 'admin.php?page=sales-notification-settings&tab=general' ),
			'meta'  => array( 'title' => esc_attr__( 'Sales Notification is showing demo notifications', 'sales-notification' ) ),
		) );
	}

	// -----------------------------------------------------------------------
	// AJAX Handlers
	// -----------------------------------------------------------------------

	/**
	 * AJAX: Save settings.
	 */
	public function ajax_save_settings() {
		$this->verify_nonce_and_cap();

		$raw_settings = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array();
		if ( ! is_array( $raw_settings ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data.', 'sales-notification' ) ) );
		}

		$sanitized = SN_Settings::sanitize( $raw_settings );
		SN_Settings::update( $sanitized );

		// Invalidate notification cache after settings change.
		SN_Notification_Data::delete_all_transients();

		wp_send_json_success( array( 'message' => __( 'Settings saved.', 'sales-notification' ) ) );
	}

	/**
	 * AJAX: Export settings as JSON.
	 */
	public function ajax_export_settings() {
		$this->verify_nonce_and_cap();

		$settings = SN_Settings::get_all();
		$json     = wp_json_encode( $settings, JSON_PRETTY_PRINT );

		wp_send_json_success( array(
			'json'     => $json,
			'filename' => 'sales-notification-settings-' . gmdate( 'Y-m-d' ) . '.json',
		) );
	}

	/**
	 * AJAX: Import settings from JSON.
	 */
	public function ajax_import_settings() {
		$this->verify_nonce_and_cap( 'manage_options' );

		if ( empty( $_POST['json'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No data received.', 'sales-notification' ) ) );
		}

		$json     = wp_unslash( $_POST['json'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$decoded  = json_decode( $json, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			wp_send_json_error( array( 'message' => __( 'Import failed: invalid file format.', 'sales-notification' ) ) );
		}

		$sanitized = SN_Settings::sanitize( $decoded );
		SN_Settings::update( $sanitized );
		SN_Settings::flush_cache();
		SN_Notification_Data::delete_all_transients();

		wp_send_json_success( array( 'message' => __( 'Settings imported successfully.', 'sales-notification' ) ) );
	}

	/**
	 * AJAX: Reset settings to defaults.
	 */
	public function ajax_reset_settings() {
		$this->verify_nonce_and_cap( 'manage_options' );

		SN_Settings::reset();
		SN_Notification_Data::delete_all_transients();

		wp_send_json_success( array( 'message' => __( 'Settings reset to defaults.', 'sales-notification' ) ) );
	}

	/**
	 * AJAX: Save a demo notification.
	 */
	public function ajax_save_demo_notification() {
		$this->verify_nonce_and_cap();

		global $wpdb;
		$table = $wpdb->prefix . 'sn_demo_notifications';

		$id            = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$customer_name = sanitize_text_field( wp_unslash( $_POST['customer_name'] ?? '' ) );
		$product_id    = absint( $_POST['product_id'] ?? 0 );
		$location      = sanitize_text_field( wp_unslash( $_POST['location'] ?? '' ) );
		$avatar_url    = esc_url_raw( wp_unslash( $_POST['avatar_url'] ?? '' ) );
		$time_offset   = absint( $_POST['time_offset'] ?? 3600 );

		if ( empty( $customer_name ) || empty( $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Customer name and product are required.', 'sales-notification' ) ) );
		}

		$data = array(
			'customer_name' => $customer_name,
			'product_id'    => $product_id,
			'location'      => $location,
			'avatar_url'    => $avatar_url,
			'time_offset'   => $time_offset,
		);

		if ( $id > 0 ) {
			$wpdb->update( $table, $data, array( 'id' => $id ), array( '%s', '%d', '%s', '%s', '%d' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		} else {
			$data['sort_order'] = (int) $wpdb->get_var( "SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->insert( $table, $data, array( '%s', '%d', '%s', '%s', '%d', '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$id = $wpdb->insert_id;
		}

		SN_Notification_Data::delete_all_transients();
		wp_send_json_success( array( 'id' => $id ) );
	}

	/**
	 * AJAX: Delete a demo notification.
	 */
	public function ajax_delete_demo_notification() {
		$this->verify_nonce_and_cap();

		global $wpdb;
		$id = absint( $_POST['id'] ?? 0 );

		if ( ! $id ) {
			wp_send_json_error();
		}

		$wpdb->delete( $wpdb->prefix . 'sn_demo_notifications', array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		SN_Notification_Data::delete_all_transients();
		wp_send_json_success();
	}

	/**
	 * AJAX: Reorder demo notifications.
	 */
	public function ajax_reorder_demo_notifications() {
		$this->verify_nonce_and_cap();

		global $wpdb;
		$order = isset( $_POST['order'] ) ? array_map( 'absint', (array) $_POST['order'] ) : array();

		foreach ( $order as $sort_order => $id ) {
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prefix . 'sn_demo_notifications',
				array( 'sort_order' => $sort_order ),
				array( 'id' => $id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		wp_send_json_success();
	}

	/**
	 * AJAX: Get analytics data for the dashboard.
	 */
	public function ajax_get_analytics() {
		$this->verify_nonce_and_cap();

		$analytics = new SN_Analytics();
		$data      = $analytics->get_summary();

		wp_send_json_success( $data );
	}

	// -----------------------------------------------------------------------
	// Utilities
	// -----------------------------------------------------------------------

	/**
	 * Verify nonce and capability. Dies on failure.
	 *
	 * @param string $cap Required capability. Defaults to 'manage_woocommerce'.
	 */
	private function verify_nonce_and_cap( $cap = 'manage_woocommerce' ) {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'sn_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'sales-notification' ) ), 403 );
		}
		if ( ! current_user_can( $cap ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'sales-notification' ) ), 403 );
		}
	}
}
