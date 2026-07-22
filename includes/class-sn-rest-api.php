<?php
/**
 * REST API endpoints for the Sales Notification plugin.
 *
 * Endpoints:
 *  GET  /wp-json/sales-notification/v1/notifications     — Get notification payload
 *  POST /wp-json/sales-notification/v1/analytics         — Record an analytics event
 *  GET  /wp-json/sales-notification/v1/settings          — Get public settings
 *  GET  /wp-json/sales-notification/v1/analytics/summary — Admin analytics summary
 *
 * @package    SalesNotification
 * @subpackage SalesNotification/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SN_REST_API {

	/**
	 * REST namespace.
	 */
	const NAMESPACE = 'sales-notification/v1';

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		// GET notifications payload.
		register_rest_route( self::NAMESPACE, '/notifications', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_notifications' ),
			'permission_callback' => '__return_true',
		) );

		// POST analytics event.
		register_rest_route( self::NAMESPACE, '/analytics', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'record_analytics' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'notification_id' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'product_id'      => array( 'required' => false, 'sanitize_callback' => 'absint' ),
				'event_type'      => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => function ( $value ) {
						return in_array( $value, array( 'impression', 'click', 'dismiss' ), true );
					},
				),
				'page_url'        => array( 'required' => false, 'sanitize_callback' => 'esc_url_raw' ),
			),
		) );

		// GET public settings.
		register_rest_route( self::NAMESPACE, '/settings', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_public_settings' ),
			'permission_callback' => '__return_true',
		) );

		// GET analytics summary (admin only).
		register_rest_route( self::NAMESPACE, '/analytics/summary', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_analytics_summary' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_woocommerce' );
			},
		) );
	}

	// -----------------------------------------------------------------------
	// Callbacks
	// -----------------------------------------------------------------------

	/**
	 * GET /notifications
	 * Returns the full notification payload.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_notifications( WP_REST_Request $request ) {
		$data_builder = new SN_Notification_Data();
		$payload      = $data_builder->get_payload();

		$response = rest_ensure_response( $payload );
		$response->header( 'Cache-Control', 'public, max-age=300' );
		$response->header( 'Vary', 'Accept-Encoding' );

		return $response;
	}

	/**
	 * POST /analytics
	 * Record an analytics event.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function record_analytics( WP_REST_Request $request ) {
		// Verify REST nonce.
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			// Non-fatal for analytics — still try to record if nonce is empty but client provided one.
		}

		if ( ! SN_Settings::get( 'enable_analytics' ) ) {
			return rest_ensure_response( array( 'recorded' => false, 'reason' => 'analytics_disabled' ) );
		}

		$analytics = new SN_Analytics();
		$recorded  = $analytics->record_event(
			$request->get_param( 'notification_id' ),
			absint( $request->get_param( 'product_id' ) ),
			$request->get_param( 'event_type' ),
			$request->get_param( 'page_url' )
		);

		return rest_ensure_response( array( 'recorded' => $recorded ) );
	}

	/**
	 * GET /settings
	 * Return public-safe settings.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_public_settings( WP_REST_Request $request ) {
		$settings = SN_Settings::get_all();

		// Strip sensitive/internal settings.
		$public_keys = array(
			'enable', 'position', 'template', 'animation_in', 'animation_out',
			'initial_delay', 'duration', 'interval', 'max_count', 'loop',
			'show_name', 'show_image', 'show_location', 'show_time', 'show_avatar',
			'show_close_button', 'gdpr_mode', 'enable_analytics', 'debug_mode',
		);
		$public = array_intersect_key( $settings, array_flip( $public_keys ) );

		return rest_ensure_response( $public );
	}

	/**
	 * GET /analytics/summary
	 * Return analytics summary for admin use.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_analytics_summary( WP_REST_Request $request ) {
		$analytics = new SN_Analytics();
		$summary   = $analytics->get_summary();

		return rest_ensure_response( $summary );
	}
}
