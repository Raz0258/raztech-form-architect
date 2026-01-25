<?php
/**
 * Fired during plugin activation
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class RT_FA_Activator {

	/**
	 * Activate the plugin.
	 *
	 * Creates database tables and sets default options.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Table names
		$forms_table       = $wpdb->prefix . 'rt_fa_forms';
		$submissions_table = $wpdb->prefix . 'rt_fa_submissions';
		$analytics_table   = $wpdb->prefix . 'rt_fa_analytics';

		// SQL for forms table
		$sql_forms = "CREATE TABLE IF NOT EXISTS $forms_table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			form_name varchar(255) NOT NULL,
			form_description text,
			form_fields longtext NOT NULL,
			settings longtext,
			form_settings text DEFAULT NULL,
			conversational_mode tinyint(1) DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY form_name (form_name)
		) $charset_collate;";

		// SQL for submissions table
		$sql_submissions = "CREATE TABLE IF NOT EXISTS $submissions_table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id bigint(20) UNSIGNED NOT NULL,
			submission_data longtext NOT NULL,
			lead_score int(3) DEFAULT 0,
			spam_score int(3) DEFAULT 0,
			is_spam tinyint(1) DEFAULT 0,
			ip_address varchar(45),
			user_agent text,
			submitted_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY form_id (form_id),
			KEY lead_score (lead_score),
			KEY is_spam (is_spam),
			KEY submitted_at (submitted_at)
		) $charset_collate;";

		// SQL for analytics table
		$sql_analytics = "CREATE TABLE IF NOT EXISTS $analytics_table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id bigint(20) UNSIGNED NOT NULL,
			views int(11) DEFAULT 0,
			submissions int(11) DEFAULT 0,
			conversion_rate decimal(5,2) DEFAULT 0.00,
			date date NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY form_id (form_id),
			KEY date (date),
			UNIQUE KEY form_date (form_id, date)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_forms );
		dbDelta( $sql_submissions );
		dbDelta( $sql_analytics );

		// Handle database upgrades for existing installations
		self::upgrade_database( $wpdb, $forms_table, $submissions_table, $analytics_table );

		// Set default options
		add_option( 'rt_fa_version', RT_FA_VERSION );
		add_option( 'rt_fa_api_provider', 'openai' );
		add_option( 'rt_fa_api_key', '' );
		add_option( 'rt_fa_rate_limit', 50 );

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Upgrade database schema for existing installations.
	 *
	 * @since    1.0.0
	 * @param    object    $wpdb                WordPress database object.
	 * @param    string    $forms_table         Forms table name.
	 * @param    string    $submissions_table   Submissions table name.
	 * @param    string    $analytics_table     Analytics table name.
	 */
	private static function upgrade_database( $wpdb, $forms_table, $submissions_table, $analytics_table ) {
		// Check if form_settings column exists in forms table
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
				WHERE TABLE_SCHEMA = %s
				AND TABLE_NAME = %s
				AND COLUMN_NAME = 'form_settings'",
				DB_NAME,
				$forms_table
			)
		);

		// Add form_settings column if it doesn't exist
		if ( empty( $column_exists ) ) {
			$wpdb->query(
				"ALTER TABLE $forms_table
				ADD COLUMN form_settings text DEFAULT NULL
				AFTER settings"
			);
		}

		// Check if created_at column exists in submissions table
		$created_at_exists = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
				WHERE TABLE_SCHEMA = %s
				AND TABLE_NAME = %s
				AND COLUMN_NAME = 'created_at'",
				DB_NAME,
				$submissions_table
			)
		);

		// Add created_at column to submissions table if it doesn't exist
		if ( empty( $created_at_exists ) ) {
			$wpdb->query(
				"ALTER TABLE $submissions_table
				ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP
				AFTER submitted_at"
			);
		}

		// Check if created_at column exists in analytics table
		$analytics_created_at_exists = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
				WHERE TABLE_SCHEMA = %s
				AND TABLE_NAME = %s
				AND COLUMN_NAME = 'created_at'",
				DB_NAME,
				$analytics_table
			)
		);

		// Add created_at column to analytics table if it doesn't exist
		if ( empty( $analytics_created_at_exists ) ) {
			$wpdb->query(
				"ALTER TABLE $analytics_table
				ADD COLUMN created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
				AFTER date"
			);
		}

		// Check if updated_at column exists in analytics table
		$analytics_updated_at_exists = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
				WHERE TABLE_SCHEMA = %s
				AND TABLE_NAME = %s
				AND COLUMN_NAME = 'updated_at'",
				DB_NAME,
				$analytics_table
			)
		);

		// Add updated_at column to analytics table if it doesn't exist
		if ( empty( $analytics_updated_at_exists ) ) {
			$wpdb->query(
				"ALTER TABLE $analytics_table
				ADD COLUMN updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
				AFTER created_at"
			);
		}
	}
}
