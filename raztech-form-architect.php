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
 * Description:       Create optimized forms in seconds using AI, automatically score lead quality, and provide conversational form experiences—all without coding.
 * Version:           1.0.2
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Raz Technologies
 * Author URI:        https://raz-technologies.com
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
define( 'RAZTAIFO_VERSION', '1.0.2' );

/**
 * Plugin directory path
 */
define( 'RAZTAIFO_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL
 */
define( 'RAZTAIFO_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename
 */
define( 'RAZTAIFO_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function raztaifo_activate() {
	require_once RAZTAIFO_PATH . 'includes/class-raztaifo-activator.php';
	RAZTAIFO_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function raztaifo_deactivate() {
	require_once RAZTAIFO_PATH . 'includes/class-raztaifo-deactivator.php';
	RAZTAIFO_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'raztaifo_activate' );
register_deactivation_hook( __FILE__, 'raztaifo_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once RAZTAIFO_PATH . 'includes/class-raztaifo.php';

/**
 * Begins execution of the plugin.
 */
function raztaifo_run() {
	$plugin = new RAZTAIFO();
	$plugin->run();
}

raztaifo_run();
