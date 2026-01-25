<?php
/**
 * RazTech AI Form Architect
 *
 * @package           RazTechFormArchitect
 * @author            Raz Technologies
 * @copyright         2025 Raz Technologies
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       RazTech AI Form Architect
 * Plugin URI:        https://raztechnologies.com
 * Description:       Create optimized forms in seconds using AI, automatically score lead quality, and provide conversational form experiences—all without coding.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Raz Technologies
 * Author URI:        https://raztechnologies.com
 * Text Domain:       raztech-form-architect
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'RT_FA_VERSION', '1.0.0' );

/**
 * Plugin directory path
 */
define( 'RT_FA_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL
 */
define( 'RT_FA_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename
 */
define( 'RT_FA_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function rt_fa_activate() {
	require_once RT_FA_PATH . 'includes/class-rt-fa-activator.php';
	RT_FA_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function rt_fa_deactivate() {
	require_once RT_FA_PATH . 'includes/class-rt-fa-deactivator.php';
	RT_FA_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'rt_fa_activate' );
register_deactivation_hook( __FILE__, 'rt_fa_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once RT_FA_PATH . 'includes/class-rt-fa.php';

/**
 * Begins execution of the plugin.
 */
function rt_fa_run() {
	$plugin = new RT_FA();
	$plugin->run();
}

rt_fa_run();
