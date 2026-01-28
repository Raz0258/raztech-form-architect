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

	// Delete all plugin options
	delete_option( 'raztaifo_version' );
	delete_option( 'raztaifo_api_provider' );
	delete_option( 'raztaifo_api_key' );
	delete_option( 'raztaifo_anthropic_api_key' );
	delete_option( 'raztaifo_spam_detection_enabled' );
	delete_option( 'raztaifo_ai_spam_detection_enabled' );
	delete_option( 'raztaifo_lead_scoring_enabled' );
	delete_option( 'raztaifo_auto_response_enabled' );
	delete_option( 'raztaifo_rate_limit' );
	delete_option( 'raztaifo_notification_email' );
	delete_option( 'raztaifo_remove_data_on_uninstall' );

	// Delete transients
	delete_transient( 'raztaifo_analytics_cache' );
	delete_transient( 'raztaifo_dashboard_cache' );

	// Delete user meta
	$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'raztaifo_smtp_notice_dismissed'" );

	// Clear any cached data
	wp_cache_flush();
}
