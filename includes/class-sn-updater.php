<?php
/**
 * Automatic update integration using YahnisElsts/plugin-update-checker.
 *
 * Connects the plugin to its public GitHub repository and periodically
 * checks for new versions by comparing GitHub Release tags against the
 * version defined in this plugin's main file header.
 *
 * No configuration is required. Updates are delivered automatically
 * through the standard WordPress update UI.
 *
 * How to release an update:
 *   1. Bump the "Version:" header in sales-notification.php.
 *   2. Bump the SN_VERSION constant in sales-notification.php to the same value.
 *   3. Commit and push to GitHub.
 *   4. Create a GitHub Release tagged with the new version (e.g. "1.0.3").
 *   5. WordPress detects the new release on the next update-check cycle
 *      (~12 hours), or immediately via Plugins → Check for updates.
 *
 * @package    SalesNotification
 * @subpackage SalesNotification/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SN_Updater {

	/**
	 * The public GitHub repository URL.
	 * PUC auto-detects this as a GitHub source and uses GitHub Releases
	 * to compare version numbers. No token needed for public repositories.
	 */
	const GITHUB_REPO_URL = 'https://github.com/saifulalamcse/sales-notification';

	/**
	 * Cached update checker instance.
	 *
	 * @var \YahnisElsts\PluginUpdateChecker\v5\Plugin\UpdateChecker|null
	 */
	private static $update_checker = null;

	/**
	 * Bootstrap the update checker.
	 *
	 * Called on the plugins_loaded hook (priority 5) by the main plugin class
	 * so that updates are visible to WP-CLI and all management tools, not
	 * only to logged-in users on admin pages.
	 *
	 * @return void
	 */
	public static function init() {
		$puc_file = SN_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';

		// Guard: library must exist inside the plugin directory.
		if ( ! file_exists( $puc_file ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Sales Notification: plugin-update-checker library not found at ' . $puc_file );
			}
			return;
		}

		require_once $puc_file;

		// Build the update checker.
		// PucFactory detects the GitHub URL and creates a GitHubChecker that
		// reads version numbers from GitHub Releases (tagged releases). When a
		// Release tag is higher than the installed "Version:" header value,
		// WordPress shows the standard update notification.
		self::$update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			self::GITHUB_REPO_URL,
			SN_PLUGIN_FILE,         // Absolute path to the main plugin file.
			'sales-notification'    // Plugin slug — must match the directory name.
		);

		// Use GitHub Releases as the update source.
		// getLatestVersion() will look at the latest Release tag rather than
		// just the branch head, which is the intended version-based workflow.
		self::$update_checker->getVcsApi()->enableReleaseAssets();
	}

	/**
	 * Return the update checker instance, if initialised.
	 *
	 * Useful for developers who need to customise PUC behaviour, e.g.:
	 *
	 *   add_action( 'plugins_loaded', function() {
	 *       $checker = SN_Updater::get_checker();
	 *       if ( $checker ) {
	 *           $checker->setCheckPeriod( 6 ); // Check every 6 hours.
	 *       }
	 *   }, 10 );
	 *
	 * @return \YahnisElsts\PluginUpdateChecker\v5\Plugin\UpdateChecker|null
	 */
	public static function get_checker() {
		return self::$update_checker;
	}
}
