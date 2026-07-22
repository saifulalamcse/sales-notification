<?php
/**
 * WooCommerce integration — order data retrieval with HPOS compatibility.
 *
 * @package    SalesNotification
 * @subpackage SalesNotification/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SN_WooCommerce {

	/**
	 * Fetch recent WooCommerce orders as raw data arrays.
	 *
	 * @return array Array of order data arrays.
	 */
	public static function get_recent_orders() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		$settings   = SN_Settings::get_all();
		$statuses   = (array) $settings['order_statuses'];
		$limit      = absint( $settings['fetch_limit'] );
		$days       = absint( $settings['fetch_days'] );
		$excl_prods = array_map( 'absint', (array) $settings['excluded_products'] );
		$excl_cats  = array_map( 'absint', (array) $settings['excluded_categories'] );

		// Ensure statuses are prefixed with 'wc-'.
		$statuses = array_map( function ( $s ) {
			return strpos( $s, 'wc-' ) === 0 ? $s : 'wc-' . $s;
		}, $statuses );

		$date_after = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		/**
		 * Filter: sn_order_query_args
		 * Modify the WooCommerce order query arguments.
		 *
		 * @param array $args Query arguments.
		 */
		$args = apply_filters( 'sn_order_query_args', array(
			'status'       => $statuses,
			'limit'        => $limit,
			'orderby'      => 'date',
			'order'        => 'DESC',
			'date_created' => '>' . $date_after,
			'return'       => 'objects',
		) );

		/**
		 * Action: sn_before_fetch_orders
		 * Fires before the WooCommerce orders are fetched.
		 *
		 * @param array $args The query args.
		 */
		do_action( 'sn_before_fetch_orders', $args );

		$orders = wc_get_orders( $args );

		if ( is_wp_error( $orders ) ) {
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Sales Notification: wc_get_orders returned WP_Error: ' . $orders->get_error_message() );
			}
			return array();
		}

		/**
		 * Action: sn_after_fetch_orders
		 * Fires after WooCommerce orders are fetched.
		 *
		 * @param array $orders Array of WC_Order objects.
		 */
		do_action( 'sn_after_fetch_orders', $orders );

		$results = array();

		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			$items = $order->get_items();

			foreach ( $items as $item ) {
				$product_id = absint( $item->get_product_id() );

				// Skip excluded products.
				if ( in_array( $product_id, $excl_prods, true ) ) {
					continue;
				}

				// Skip excluded categories.
				if ( ! empty( $excl_cats ) ) {
					$cat_ids = wc_get_product_term_ids( $product_id, 'product_cat' );
					if ( array_intersect( $excl_cats, $cat_ids ) ) {
						continue;
					}
				}

				$product = $item->get_product();
				if ( ! $product ) {
					continue;
				}

				$billing_email   = $order->get_billing_email();
				$billing_country = $order->get_billing_country();
				$country_name    = '';
				if ( ! empty( $billing_country ) && function_exists( 'WC' ) ) {
					$countries    = WC()->countries->get_countries();
					$country_name = $countries[ $billing_country ] ?? $billing_country;
				}

				$date_created = $order->get_date_created();

				$results[] = array(
					'order_id'       => $order->get_id(),
					'first_name'     => $order->get_billing_first_name(),
					'last_name'      => $order->get_billing_last_name(),
					'city'           => $order->get_billing_city(),
					'country'        => $country_name,
					'email_hash'     => $billing_email ? md5( strtolower( trim( $billing_email ) ) ) : '',
					'timestamp'      => $date_created ? $date_created->getTimestamp() : 0,
					'product_id'     => $product_id,
					'product_name'   => $product->get_name(),
					'product_url'    => get_permalink( $product_id ),
					'product_image'  => self::get_product_image_url( $product ),
				);

				// Only take the first qualifying product per order to avoid duplicate notifications.
				break;
			}
		}

		return $results;
	}

	/**
	 * Get the product thumbnail URL at the configured image size.
	 *
	 * @param WC_Product $product The product object.
	 * @return string Image URL or empty string.
	 */
	public static function get_product_image_url( $product ) {
		$image_size = absint( SN_Settings::get( 'image_size', 60 ) );
		$image_id   = $product->get_image_id();

		if ( ! $image_id ) {
			return wc_placeholder_img_src( array( $image_size, $image_size ) );
		}

		$image_data = wp_get_attachment_image_src( $image_id, array( $image_size, $image_size ) );
		return $image_data ? esc_url( $image_data[0] ) : '';
	}

	/**
	 * Fetch demo notifications from the database.
	 *
	 * @return array Array of demo notification data.
	 */
	public static function get_demo_notifications() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}sn_demo_notifications ORDER BY sort_order ASC"
		);

		if ( empty( $rows ) ) {
			return self::get_built_in_demo_data();
		}

		$excl_prods = array_map( 'absint', (array) SN_Settings::get( 'excluded_products' ) );
		$excl_cats  = array_map( 'absint', (array) SN_Settings::get( 'excluded_categories' ) );

		$results = array();

		foreach ( $rows as $row ) {
			$product_id = absint( $row->product_id );

			if ( in_array( $product_id, $excl_prods, true ) ) {
				continue;
			}

			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			if ( ! empty( $excl_cats ) ) {
				$cat_ids = wc_get_product_term_ids( $product_id, 'product_cat' );
				if ( array_intersect( $excl_cats, $cat_ids ) ) {
					continue;
				}
			}

			$results[] = array(
				'order_id'      => 'demo_' . $row->id,
				'first_name'    => $row->customer_name,
				'last_name'     => '',
				'city'          => '',
				'country'       => $row->location,
				'email_hash'    => '',
				'timestamp'     => time() - absint( $row->time_offset ),
				'product_id'    => $product_id,
				'product_name'  => $product->get_name(),
				'product_url'   => get_permalink( $product_id ),
				'product_image' => self::get_product_image_url( $product ),
				'avatar_url'    => esc_url( $row->avatar_url ),
			);
		}

		return $results;
	}

	/**
	 * Built-in demo data used when no custom demo notifications are configured.
	 *
	 * @return array
	 */
	private static function get_built_in_demo_data() {
		return array(
			array(
				'order_id'      => 'demo_builtin_1',
				'first_name'    => 'Sarah',
				'last_name'     => 'M.',
				'city'          => 'London',
				'country'       => 'United Kingdom',
				'email_hash'    => '',
				'timestamp'     => time() - 7200,
				'product_id'    => 0,
				'product_name'  => 'Sample Product',
				'product_url'   => '#',
				'product_image' => '',
				'avatar_url'    => '',
			),
			array(
				'order_id'      => 'demo_builtin_2',
				'first_name'    => 'James',
				'last_name'     => 'K.',
				'city'          => 'Toronto',
				'country'       => 'Canada',
				'email_hash'    => '',
				'timestamp'     => time() - 3600,
				'product_id'    => 0,
				'product_name'  => 'Sample Product',
				'product_url'   => '#',
				'product_image' => '',
				'avatar_url'    => '',
			),
		);
	}
}
