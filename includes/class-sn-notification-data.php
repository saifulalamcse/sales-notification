<?php
/**
 * Builds the notification data payload served to the frontend.
 *
 * Handles: data assembly, privacy masking, time-ago formatting,
 * transient caching, frontend asset enqueueing, and cache invalidation.
 *
 * @package    SalesNotification
 * @subpackage SalesNotification/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SN_Notification_Data {

	/**
	 * Transient key prefix.
	 */
	const TRANSIENT_PREFIX = 'sn_notification_data_';

	// -----------------------------------------------------------------------
	// Frontend Asset Enqueueing
	// -----------------------------------------------------------------------

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * Called on wp_enqueue_scripts.
	 */
	public function enqueue_frontend_assets() {
		if ( ! $this->should_show_on_current_page() ) {
			return;
		}

		// CSS.
		wp_enqueue_style(
			'sales-notification',
			SN_PLUGIN_URL . 'assets/css/sales-notification.css',
			array(),
			SN_VERSION
		);

		// JS.
		wp_enqueue_script(
			'sales-notification',
			SN_PLUGIN_URL . 'assets/js/sales-notification.js',
			array(),
			SN_VERSION,
			true  // Load in footer.
		);
		wp_script_add_data( 'sales-notification', 'defer', true );

		// Inline custom CSS if set.
		$custom_css = SN_Settings::get( 'custom_css' );
		if ( ! empty( $custom_css ) ) {
			wp_add_inline_style( 'sales-notification', $custom_css );
		}

		// Pass data inline (avoids extra HTTP request).
		$payload = $this->get_payload();
		wp_localize_script( 'sales-notification', 'snData', $payload );
	}

	// -----------------------------------------------------------------------
	// Payload Assembly
	// -----------------------------------------------------------------------

	/**
	 * Build and return the full JS payload (from cache or fresh).
	 *
	 * @return array
	 */
	public function get_payload() {
		$lang          = get_locale();
		$transient_key = self::TRANSIENT_PREFIX . md5( $lang . serialize( SN_Settings::get_all() ) );

		$cached = get_transient( $transient_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$settings      = SN_Settings::get_all();
		$notifications = $this->build_notifications( $settings );

		/**
		 * Filter: sn_notification_data
		 * Modify the full notifications array before it is sent to the frontend.
		 *
		 * @param array $notifications Array of notification data.
		 */
		$notifications = apply_filters( 'sn_notification_data', $notifications );

		$payload = array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'sn_track_event' ),
			'restUrl'       => rest_url( 'sales-notification/v1/' ),
			'restNonce'     => wp_create_nonce( 'wp_rest' ),
			'settings'      => array(
				'initial_delay'    => absint( $settings['initial_delay'] ),
				'duration'         => absint( $settings['duration'] ),
				'interval'         => absint( $settings['interval'] ),
				'max_count'        => absint( $settings['max_count'] ),
				'loop'             => (bool) $settings['loop'],
				'position'         => sanitize_text_field( $settings['position'] ),
				'template'         => sanitize_text_field( $settings['template'] ),
				'animation_in'     => sanitize_text_field( $settings['animation_in'] ),
				'animation_out'    => sanitize_text_field( $settings['animation_out'] ),
				'show_name'        => (bool) $settings['show_name'],
				'show_image'       => (bool) $settings['show_image'],
				'show_location'    => (bool) $settings['show_location'],
				'show_time'        => (bool) $settings['show_time'],
				'show_avatar'      => (bool) $settings['show_avatar'],
				'show_close'       => (bool) $settings['show_close_button'],
				'gdpr_mode'        => (bool) $settings['gdpr_mode'],
				'cookie_expiry'    => sanitize_text_field( $settings['cookie_expiry'] ),
				'consent_plugins'  => array_map( 'sanitize_text_field', (array) $settings['consent_plugins'] ),
				'enable_analytics' => (bool) $settings['enable_analytics'],
				'debug_mode'       => (bool) $settings['debug_mode'],
				'show_desktop'     => (bool) $settings['show_desktop'],
				'show_mobile'      => (bool) $settings['show_mobile'],
				'mobile_breakpoint'=> absint( $settings['mobile_breakpoint'] ),
				'image_size'       => absint( $settings['image_size'] ),
				'max_width'        => absint( $settings['max_width'] ),
				'border_radius'    => absint( $settings['border_radius'] ),
				'border_width'     => absint( $settings['border_width'] ),
				'box_shadow'       => sanitize_text_field( $settings['box_shadow'] ),
				'color_bg'         => sanitize_hex_color( $settings['color_bg'] ),
				'color_text'       => sanitize_hex_color( $settings['color_text'] ),
				'color_text_secondary' => sanitize_hex_color( $settings['color_text_secondary'] ),
				'color_accent'     => sanitize_hex_color( $settings['color_accent'] ),
				'color_border'     => sanitize_hex_color( $settings['color_border'] ),
				'font_family'      => sanitize_text_field( $settings['font_family'] ),
				'font_size'        => absint( $settings['font_size'] ),
				'font_weight'      => sanitize_text_field( $settings['font_weight'] ),
			),
			'notifications' => $notifications,
		);

		/**
		 * Filter: sn_rest_response
		 * Modify the full REST/inline payload.
		 *
		 * @param array $payload The complete data payload.
		 */
		$payload = apply_filters( 'sn_rest_response', $payload );

		/** Filter: sn_transient_ttl */
		$ttl = apply_filters( 'sn_transient_ttl', 300 );
		set_transient( $transient_key, $payload, $ttl );

		return $payload;
	}

	/**
	 * Build notification entries from orders or demo data.
	 *
	 * @param array $settings Plugin settings.
	 * @return array Array of notification objects for JS.
	 */
	private function build_notifications( array $settings ) {
		$raw = SN_Settings::is_demo_mode()
			? SN_WooCommerce::get_demo_notifications()
			: SN_WooCommerce::get_recent_orders();

		$notifications = array();

		foreach ( $raw as $item ) {
			/**
			 * Filter: sn_single_notification
			 * Modify a single notification entry before it's added to the payload.
			 *
			 * @param array $item  The notification data.
			 * @param array $item  The original raw order/demo data.
			 */
			$item = apply_filters( 'sn_single_notification', $item, $item );

			$notification_id = 'sn_' . md5( $item['order_id'] . '_' . $item['product_id'] );

			// Format customer name.
			$name = $this->format_customer_name(
				$item['first_name'],
				$item['last_name'],
				(bool) $settings['privacy_truncate_last_name']
			);

			// Format location.
			$location_parts = array_filter( array(
				trim( $item['city'] ),
				trim( $item['country'] ),
			) );
			$location = implode( ', ', $location_parts );

			// Format time.
			$time_ago = $this->time_ago( $item['timestamp'] );

			// Avatar URL.
			$avatar_url = '';
			if ( $settings['show_avatar'] ) {
				$avatar_url = isset( $item['avatar_url'] ) && $item['avatar_url']
					? $item['avatar_url']
					: $this->get_gravatar_url( $item['email_hash'], absint( $settings['image_size'] ) );
			}

			$notifications[] = array(
				'id'            => $notification_id,
				'name'          => $name,
				'product_name'  => esc_html( $item['product_name'] ),
				'product_url'   => esc_url( $item['product_url'] ),
				'product_image' => esc_url( $item['product_image'] ),
				'location'      => esc_html( $location ),
				'time_ago'      => esc_html( $time_ago ),
				'avatar_url'    => esc_url( $avatar_url ),
			);
		}

		return $notifications;
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Format customer name with privacy masking.
	 *
	 * @param string $first_name     First name.
	 * @param string $last_name      Last name.
	 * @param bool   $truncate_last  Whether to truncate last name to initial.
	 * @return string Formatted name.
	 */
	private function format_customer_name( $first_name, $last_name, $truncate_last ) {
		$first_name = sanitize_text_field( $first_name );
		$last_name  = sanitize_text_field( $last_name );

		if ( empty( $first_name ) ) {
			return esc_html__( 'Someone', 'sales-notification' );
		}

		if ( ! empty( $last_name ) && $truncate_last ) {
			$last_name = mb_strtoupper( mb_substr( $last_name, 0, 1 ) ) . '.';
		}

		$name = trim( $first_name . ' ' . $last_name );

		/**
		 * Filter: sn_customer_name
		 * Modify the formatted customer name.
		 *
		 * @param string $name       Formatted name.
		 * @param string $first_name Original first name.
		 * @param string $last_name  Original last name.
		 */
		return apply_filters( 'sn_customer_name', $name, $first_name, $last_name );
	}

	/**
	 * Convert a Unix timestamp to a human-readable relative time string.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @return string Relative time, e.g. "2 hours ago".
	 */
	public function time_ago( $timestamp ) {
		$diff = max( 0, time() - absint( $timestamp ) );

		if ( $diff < 60 ) {
			$time_ago = __( 'Just now', 'sales-notification' );
		} elseif ( $diff < 3600 ) {
			$mins     = round( $diff / 60 );
			/* translators: %d: number of minutes */
			$time_ago = sprintf( _n( '%d minute ago', '%d minutes ago', $mins, 'sales-notification' ), $mins );
		} elseif ( $diff < 86400 ) {
			$hours    = round( $diff / 3600 );
			/* translators: %d: number of hours */
			$time_ago = sprintf( _n( '%d hour ago', '%d hours ago', $hours, 'sales-notification' ), $hours );
		} elseif ( $diff < 604800 ) {
			$days     = round( $diff / 86400 );
			/* translators: %d: number of days */
			$time_ago = sprintf( _n( '%d day ago', '%d days ago', $days, 'sales-notification' ), $days );
		} elseif ( $diff < 2592000 ) {
			$weeks    = round( $diff / 604800 );
			/* translators: %d: number of weeks */
			$time_ago = sprintf( _n( '%d week ago', '%d weeks ago', $weeks, 'sales-notification' ), $weeks );
		} else {
			$months   = round( $diff / 2592000 );
			/* translators: %d: number of months */
			$time_ago = sprintf( _n( '%d month ago', '%d months ago', $months, 'sales-notification' ), $months );
		}

		/**
		 * Filter: sn_time_ago
		 * Modify the relative time string.
		 *
		 * @param string $time_ago  The formatted relative time.
		 * @param int    $timestamp The original timestamp.
		 */
		return apply_filters( 'sn_time_ago', $time_ago, $timestamp );
	}

	/**
	 * Build a Gravatar URL from an email MD5 hash.
	 *
	 * @param string $email_hash MD5 hash of the email address.
	 * @param int    $size       Image size in pixels.
	 * @return string Gravatar URL.
	 */
	private function get_gravatar_url( $email_hash, $size = 60 ) {
		if ( empty( $email_hash ) ) {
			return '';
		}
		$url = sprintf( 'https://www.gravatar.com/avatar/%s?s=%d&d=mp&r=g', $email_hash, $size );

		/**
		 * Filter: sn_avatar_url
		 * Override the avatar image URL.
		 *
		 * @param string $url        The Gravatar URL.
		 * @param string $email_hash The MD5 email hash.
		 */
		return apply_filters( 'sn_avatar_url', $url, $email_hash );
	}

	/**
	 * Determine if notifications should be shown on the current page.
	 *
	 * @return bool
	 */
	public function should_show_on_current_page() {
		// Master switch.
		if ( ! SN_Settings::is_enabled() ) {
			return false;
		}

		// WooCommerce required.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}

		// Disable for logged-in users.
		if ( is_user_logged_in() ) {
			if ( SN_Settings::get( 'disable_for_logged_in' ) ) {
				return false;
			}
			$disabled_roles = (array) SN_Settings::get( 'disable_for_roles' );
			if ( ! empty( $disabled_roles ) ) {
				$user = wp_get_current_user();
				if ( array_intersect( $disabled_roles, (array) $user->roles ) ) {
					return false;
				}
			}
		}

		// Page visibility rules.
		$visibility_mode = SN_Settings::get( 'page_visibility_mode' );
		$visibility_ids  = array_map( 'absint', (array) SN_Settings::get( 'page_visibility_ids' ) );
		$current_post_id = get_queried_object_id();

		if ( $visibility_mode === 'include' && ! empty( $visibility_ids ) ) {
			if ( ! in_array( $current_post_id, $visibility_ids, true ) ) {
				return false;
			}
		} elseif ( $visibility_mode === 'exclude' && ! empty( $visibility_ids ) ) {
			if ( in_array( $current_post_id, $visibility_ids, true ) ) {
				return false;
			}
		}

		/**
		 * Filter: sn_should_show
		 * Developers can override whether the plugin shows on a given page.
		 *
		 * @param bool $should_show Whether to show notifications.
		 * @param int  $post_id     Current post ID.
		 */
		return apply_filters( 'sn_should_show', true, $current_post_id );
	}

	// -----------------------------------------------------------------------
	// Cache Invalidation
	// -----------------------------------------------------------------------

	/**
	 * Invalidate the notification data transient cache.
	 * Called when a new order is created or an order status changes.
	 */
	public function invalidate_cache() {
		self::delete_all_transients();
	}

	/**
	 * Delete all plugin notification transients from the options table.
	 */
	public static function delete_all_transients() {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options}
				 WHERE option_name LIKE %s
				    OR option_name LIKE %s",
				'_transient_' . self::TRANSIENT_PREFIX . '%',
				'_transient_timeout_' . self::TRANSIENT_PREFIX . '%'
			)
		);
	}
}
