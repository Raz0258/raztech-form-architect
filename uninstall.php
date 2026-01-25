<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package    RT_FA_AI
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if user wants to remove data on uninstall
$remove_data = get_option( 'rt_fa_remove_data_on_uninstall', false );

if ( $remove_data ) {
	global $wpdb;

	// Drop custom tables
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rt_fa_forms" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rt_fa_submissions" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rt_fa_analytics" );

	// Delete all plugin options
	delete_option( 'rt_fa_version' );
	delete_option( 'rt_fa_api_provider' );
	delete_option( 'rt_fa_api_key' );
	delete_option( 'rt_fa_anthropic_api_key' );
	delete_option( 'rt_fa_spam_detection_enabled' );
	delete_option( 'rt_fa_ai_spam_detection_enabled' );
	delete_option( 'rt_fa_lead_scoring_enabled' );
	delete_option( 'rt_fa_auto_response_enabled' );
	delete_option( 'rt_fa_rate_limit' );
	delete_option( 'rt_fa_notification_email' );
	delete_option( 'rt_fa_remove_data_on_uninstall' );

	// Delete transients
	delete_transient( 'rt_fa_analytics_cache' );
	delete_transient( 'rt_fa_dashboard_cache' );

	// Clear any cached data
	wp_cache_flush();
}
