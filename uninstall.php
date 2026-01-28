<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package    RAZTAIFO_AI
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if user wants to remove data on uninstall
$remove_data = get_option( 'raztaifo_remove_data_on_uninstall', false );

if ( $remove_data ) {
	global $wpdb;

	// Drop custom tables
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}raztaifo_forms" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}raztaifo_submissions" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}raztaifo_analytics" );

	// Delete all plugin options (wildcard delete for future-proofing)
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'raztaifo_%'" );

	// Delete all transients (both regular and site transients)
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_raztaifo_%' OR option_name LIKE '_transient_timeout_raztaifo_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_raztaifo_%' OR option_name LIKE '_site_transient_timeout_raztaifo_%'" );

	// Delete user meta
	$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'raztaifo_smtp_notice_dismissed'" );

	// Clear any cached data
	wp_cache_flush();
}
