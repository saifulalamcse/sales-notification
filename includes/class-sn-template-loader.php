<?php
/**
 * Template loader with theme override support.
 *
 * Allows themes to override plugin templates by placing files at:
 *   yourtheme/sales-notification/template-{n}.php
 *
 * @package    SalesNotification
 * @subpackage SalesNotification/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SN_Template_Loader {

	/**
	 * The template slug used for the plugin directory.
	 */
	const TEMPLATE_FOLDER = 'sales-notification';

	/**
	 * Load a plugin template, checking theme overrides first.
	 *
	 * Priority:
	 *   1. Child theme: yourtheme/sales-notification/{template}.php
	 *   2. Parent theme: parenttheme/sales-notification/{template}.php
	 *   3. Plugin default: sales-notification/templates/{template}.php
	 *
	 * @param string $template_name Template filename (e.g. 'template-1.php').
	 * @param array  $args          Variables to pass to the template.
	 * @param bool   $return        If true, return the rendered HTML rather than echoing.
	 * @return string|void HTML if $return is true, otherwise void.
	 */
	public static function get_template( $template_name, $args = array(), $return = false ) {
		if ( ! empty( $args ) && is_array( $args ) ) {
			// Make variables available to the template.
			extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		}

		$template = self::locate_template( $template_name );

		if ( ! file_exists( $template ) ) {
			return '';
		}

		if ( $return ) {
			ob_start();
			include $template;
			return ob_get_clean();
		}

		include $template;
	}

	/**
	 * Locate a template file from theme or plugin.
	 *
	 * @param string $template_name Template filename.
	 * @return string Absolute path to the template file.
	 */
	public static function locate_template( $template_name ) {
		// Sanitize the template name.
		$template_name = sanitize_file_name( $template_name );

		// Check theme locations.
		$theme_template = locate_template( array(
			trailingslashit( self::TEMPLATE_FOLDER ) . $template_name,
		) );

		if ( $theme_template ) {
			return $theme_template;
		}

		// Fall back to plugin default.
		return SN_PLUGIN_DIR . 'templates/' . $template_name;
	}

	/**
	 * Get the path to all available templates.
	 *
	 * @return array Array of [ id => [ label, path ] ].
	 */
	public static function get_available_templates() {
		return array(
			'1' => array(
				'label' => __( 'Horizontal — image left, text right', 'sales-notification' ),
				'path'  => self::locate_template( 'template-1.php' ),
			),
			'2' => array(
				'label' => __( 'Vertical — image top, text below', 'sales-notification' ),
				'path'  => self::locate_template( 'template-2.php' ),
			),
			'3' => array(
				'label' => __( 'Minimal — text only', 'sales-notification' ),
				'path'  => self::locate_template( 'template-3.php' ),
			),
		);
	}
}
