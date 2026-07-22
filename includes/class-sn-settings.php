<?php
/**
 * Plugin settings management — storage, retrieval, sanitization, and defaults.
 *
 * @package    SalesNotification
 * @subpackage SalesNotification/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SN_Settings {

	/**
	 * Option key used to store all plugin settings.
	 */
	const OPTION_KEY = 'sales_notification_settings';

	/**
	 * Cached settings array.
	 *
	 * @var array|null
	 */
	private static $settings = null;

	// -----------------------------------------------------------------------
	// Defaults
	// -----------------------------------------------------------------------

	/**
	 * Returns the full list of default settings.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			// General
			'enable'                     => true,
			'source'                     => 'real',          // 'real' | 'demo'
			'order_statuses'             => array( 'completed', 'processing' ),
			'fetch_limit'                => 20,
			'fetch_days'                 => 30,
			'remove_data_on_uninstall'   => false,

			// Notification display
			'show_name'                  => true,
			'show_image'                 => true,
			'show_location'              => true,
			'show_time'                  => true,
			'show_avatar'                => false,
			'privacy_truncate_last_name' => true,

			// Timing
			'initial_delay'              => 5,
			'duration'                   => 6,
			'interval'                   => 10,
			'max_count'                  => 10,
			'loop'                       => true,

			// Position & template
			'position'                   => 'bottom-left',
			'template'                   => '1',

			// Animation
			'animation_in'               => 'slide-in',
			'animation_out'              => 'fade-out',

			// Design
			'color_bg'                   => '#ffffff',
			'color_text'                 => '#333333',
			'color_text_secondary'       => '#777777',
			'color_accent'               => '#0071a1',
			'color_border'               => '#e0e0e0',
			'border_width'               => 1,
			'border_radius'              => 8,
			'box_shadow'                 => 'soft',          // 'none' | 'soft' | 'medium' | 'strong'
			'font_family'                => 'inherit',
			'font_size'                  => 14,
			'font_weight'                => '400',
			'max_width'                  => 320,
			'image_size'                 => 60,
			'show_close_button'          => true,
			'custom_css'                 => '',

			// Visibility
			'show_desktop'               => true,
			'show_mobile'                => true,
			'mobile_breakpoint'          => 768,
			'page_visibility_mode'       => 'all',           // 'all' | 'include' | 'exclude'
			'page_visibility_ids'        => array(),
			'excluded_products'          => array(),
			'excluded_categories'        => array(),

			// Advanced
			'gdpr_mode'                  => false,
			'cookie_expiry'              => 'session',       // 'session' | '1' | '7' | '30'
			'consent_plugins'            => array(),
			'disable_for_logged_in'      => false,
			'disable_for_roles'          => array(),
			'enable_analytics'           => true,
			'analytics_retention_days'   => 90,
			'debug_mode'                 => false,
			'inline_data'                => true,            // true = wp_localize_script | false = async fetch
		);
	}

	// -----------------------------------------------------------------------
	// CRUD
	// -----------------------------------------------------------------------

	/**
	 * Get all settings (with defaults as fallback).
	 *
	 * @return array
	 */
	public static function get_all() {
		if ( is_null( self::$settings ) ) {
			$saved          = get_option( self::OPTION_KEY, array() );
			self::$settings = wp_parse_args( $saved, self::get_defaults() );
		}
		return self::$settings;
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback value (uses plugin default if not supplied).
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$settings = self::get_all();
		if ( isset( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}
		if ( ! is_null( $default ) ) {
			return $default;
		}
		$defaults = self::get_defaults();
		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : null;
	}

	/**
	 * Update settings (merges with existing).
	 *
	 * @param array $new_settings Key-value pairs to update.
	 * @return bool True on success.
	 */
	public static function update( array $new_settings ) {
		$current        = self::get_all();
		$merged         = array_merge( $current, $new_settings );
		self::$settings = $merged;
		return update_option( self::OPTION_KEY, $merged );
	}

	/**
	 * Reset all settings to defaults.
	 *
	 * @return bool
	 */
	public static function reset() {
		self::$settings = null;
		return update_option( self::OPTION_KEY, self::get_defaults() );
	}

	/**
	 * Invalidate the in-memory cache (used after import).
	 */
	public static function flush_cache() {
		self::$settings = null;
	}

	// -----------------------------------------------------------------------
	// Sanitization
	// -----------------------------------------------------------------------

	/**
	 * Sanitize a raw settings array from a form POST or JSON import.
	 *
	 * @param array $raw Raw input.
	 * @return array Sanitized settings ready for storage.
	 */
	public static function sanitize( array $raw ) {
		$defaults = self::get_defaults();
		$clean    = array();

		// Booleans.
		$bool_keys = array(
			'enable', 'show_name', 'show_image', 'show_location', 'show_time',
			'show_avatar', 'privacy_truncate_last_name', 'loop', 'show_close_button',
			'show_desktop', 'show_mobile', 'gdpr_mode', 'disable_for_logged_in',
			'enable_analytics', 'debug_mode', 'inline_data', 'remove_data_on_uninstall',
		);
		foreach ( $bool_keys as $key ) {
			$clean[ $key ] = isset( $raw[ $key ] ) ? (bool) $raw[ $key ] : $defaults[ $key ];
		}

		// Integers.
		$int_keys = array(
			'fetch_limit', 'fetch_days', 'initial_delay', 'duration', 'interval',
			'max_count', 'border_width', 'border_radius', 'font_size', 'max_width',
			'image_size', 'mobile_breakpoint', 'analytics_retention_days',
		);
		foreach ( $int_keys as $key ) {
			$clean[ $key ] = isset( $raw[ $key ] ) ? absint( $raw[ $key ] ) : $defaults[ $key ];
		}

		// Strings (plain text).
		$string_keys = array(
			'source', 'position', 'template', 'animation_in', 'animation_out',
			'box_shadow', 'font_family', 'font_weight', 'page_visibility_mode',
			'cookie_expiry',
		);
		foreach ( $string_keys as $key ) {
			$clean[ $key ] = isset( $raw[ $key ] ) ? sanitize_text_field( $raw[ $key ] ) : $defaults[ $key ];
		}

		// Colors.
		$color_keys = array(
			'color_bg', 'color_text', 'color_text_secondary', 'color_accent', 'color_border',
		);
		foreach ( $color_keys as $key ) {
			$clean[ $key ] = isset( $raw[ $key ] ) ? sanitize_hex_color( $raw[ $key ] ) : $defaults[ $key ];
		}

		// Arrays of integers (IDs).
		$array_int_keys = array(
			'page_visibility_ids', 'excluded_products', 'excluded_categories',
		);
		foreach ( $array_int_keys as $key ) {
			if ( isset( $raw[ $key ] ) && is_array( $raw[ $key ] ) ) {
				$clean[ $key ] = array_map( 'absint', $raw[ $key ] );
			} else {
				$clean[ $key ] = $defaults[ $key ];
			}
		}

		// Arrays of strings.
		$array_string_keys = array( 'order_statuses', 'consent_plugins', 'disable_for_roles' );
		foreach ( $array_string_keys as $key ) {
			if ( isset( $raw[ $key ] ) && is_array( $raw[ $key ] ) ) {
				$clean[ $key ] = array_map( 'sanitize_text_field', $raw[ $key ] );
			} else {
				$clean[ $key ] = $defaults[ $key ];
			}
		}

		// Custom CSS — strip any script tags but allow CSS.
		if ( isset( $raw['custom_css'] ) ) {
			$css = $raw['custom_css'];
			// Remove closing style tags to prevent injection.
			$css           = str_ireplace( '</style>', '', $css );
			$clean['custom_css'] = strip_tags( $css );
		} else {
			$clean['custom_css'] = '';
		}

		/**
		 * Filter: sn_plugin_settings
		 * Allows developers to modify settings before they are stored.
		 *
		 * @param array $clean Sanitized settings.
		 * @param array $raw   Raw input.
		 */
		return apply_filters( 'sn_plugin_settings', $clean, $raw );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Check whether the plugin is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return (bool) self::get( 'enable' );
	}

	/**
	 * Check whether demo mode is active.
	 *
	 * @return bool
	 */
	public static function is_demo_mode() {
		return self::get( 'source' ) === 'demo';
	}
}
