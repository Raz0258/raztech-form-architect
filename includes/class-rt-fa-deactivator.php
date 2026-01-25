<?php
/**
 * Fired during plugin deactivation
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class RT_FA_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Cleans up transients and flushes rewrite rules.
	 * Note: We don't delete database tables on deactivation - only on uninstall.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Clean up transients
		global $wpdb;
		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_rt_fa_%'
			OR option_name LIKE '_transient_timeout_rt_fa_%'"
		);

		// Flush rewrite rules
		flush_rewrite_rules();
	}
}
