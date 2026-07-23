<?php
/**
 * Automatic update integration using YahnisElsts/plugin-update-checker.
 *
 * Connects the plugin to its private GitHub repository and periodically
 * checks for a new version by comparing the version tag in the repository
 * with the version defined in this plugin's main file header.
 *
 * How updates are released:
 *   1. Bump the version in sales-notification.php (plugin header + SN_VERSION constant).
 *   2. Push the changes and create a GitHub Release whose tag matches the
 *      new version number (e.g. "1.0.2" or "v1.0.2").
 *   3. WordPress will detect the new version on the next update-check cycle
 *      (~12 hours, or immediately via Plugins → Check for updates).
 *
 * GitHub Token storage:
 *   The access token is stored in WordPress options under the key
 *   'sn_github_token' so it never lives in version-controlled source code.
 *   Set it once from Settings → Advanced, or programmatically:
 *
 *     update_option( 'sn_github_token', 'your_token_here' );
 *
 * @package    SalesNotification
 * @subpackage SalesNotification/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SN_Updater {

	/**
	 * WordPress option key that holds the GitHub Personal Access Token.
	 */
	const TOKEN_OPTION_KEY = 'sn_github_token';

	/**
	 * WordPress option key that holds the full GitHub repository URL.
	 * Example: https://github.com/your-username/sales-notification
	 */
	const REPO_OPTION_KEY = 'sn_github_repo_url';

	/**
	 * Cached update checker instance (YahnisElsts\PluginUpdateChecker).
	 *
	 * @var \YahnisElsts\PluginUpdateChecker\v5\Plugin\UpdateChecker|null
	 */
	private static $update_checker = null;

	/**
	 * Bootstrap the update checker.
	 *
	 * Call this once from the main plugin class. It loads the PUC library,
	 * configures the GitHub source, attaches the access token, and registers
	 * the update checker with WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		// Retrieve configuration from options.
		$repo_url = get_option( self::REPO_OPTION_KEY, '' );
		$token    = get_option( self::TOKEN_OPTION_KEY, '' );

		// Both values must be set before we can initialise.
		if ( empty( $repo_url ) || empty( $token ) ) {
			return;
		}

		$puc_file = SN_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';

		// Guard: library must be present (cloned / extracted into the plugin dir).
		if ( ! file_exists( $puc_file ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Sales Notification: plugin-update-checker library not found at ' . $puc_file );
			}
			return;
		}

		require_once $puc_file;

		// Build the update checker pointed at the GitHub repository.
		// PucFactory auto-detects that this is a GitHub URL and returns a
		// GitHubChecker instance that uses the version-based update method:
		// it reads the "Version" header from the main plugin file in the
		// repository and compares it to the locally installed version.
		self::$update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			$repo_url,
			SN_PLUGIN_FILE, // Absolute path to the main plugin file.
			'sales-notification'  // Plugin slug — must match the directory name.
		);

		// Authenticate against the private repository.
		// setAuthentication() accepts a GitHub Personal Access Token (classic
		// or fine-grained) with at least "Contents: read" permission on the repo.
		self::$update_checker->setAuthentication( $token );

		// Instruct the checker to use the "stable branch" release mechanism:
		// a new update is triggered by a GitHub Release (tagged release).
		// The library will compare the tag to the installed Version header.
		self::$update_checker->setBranch( 'main' );
	}

	/**
	 * Return the update checker instance, if initialised.
	 *
	 * Useful for developers who need to further customise PUC behaviour,
	 * e.g. changing the update interval:
	 *
	 *   add_action( 'plugins_loaded', function() {
	 *       $checker = SN_Updater::get_checker();
	 *       if ( $checker ) {
	 *           $checker->setCheckPeriod( 6 ); // Check every 6 hours.
	 *       }
	 *   } );
	 *
	 * @return \YahnisElsts\PluginUpdateChecker\v5\Plugin\UpdateChecker|null
	 */
	public static function get_checker() {
		return self::$update_checker;
	}

	/**
	 * Save the GitHub repository URL.
	 *
	 * @param string $url Full GitHub repository URL (HTTPS).
	 * @return bool True on update, false on failure.
	 */
	public static function set_repo_url( $url ) {
		return update_option( self::REPO_OPTION_KEY, esc_url_raw( trim( $url ) ) );
	}

	/**
	 * Save the GitHub Personal Access Token.
	 *
	 * The token is stored as plain text in wp_options. It is only accessible
	 * server-side and is never sent to the browser.
	 *
	 * @param string $token GitHub PAT.
	 * @return bool True on update, false on failure.
	 */
	public static function set_token( $token ) {
		return update_option( self::TOKEN_OPTION_KEY, sanitize_text_field( trim( $token ) ) );
	}

	/**
	 * Delete the stored token (e.g. on plugin uninstall).
	 *
	 * @return void
	 */
	public static function delete_credentials() {
		delete_option( self::TOKEN_OPTION_KEY );
		delete_option( self::REPO_OPTION_KEY );
	}
}
