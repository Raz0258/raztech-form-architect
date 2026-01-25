<?php
/**
 * Export Functionality
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/admin
 */

/**
 * Export class for SmartForms AI.
 *
 * Handles CSV export of submissions and JSON export of settings.
 */
class RT_FA_Export {

	/**
	 * Export submissions to CSV
	 *
	 * @since    1.0.0
	 * @param    int $form_id Form ID (0 for all forms).
	 * @return   void
	 */
	public static function export_submissions_csv( $form_id = 0 ) {
		// Security check
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export submissions.', 'raztech-form-architect' ) );
		}

		// Verify nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'rt_fa_export_csv' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'raztech-form-architect' ) );
		}

		// Get submissions
		$submissions = RT_FA_Form_Builder::get_submissions( $form_id );

		if ( empty( $submissions ) ) {
			wp_die( esc_html__( 'No submissions to export.', 'raztech-form-architect' ) );
		}

		// Set headers for CSV download
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="smartforms-submissions-' . gmdate( 'Y-m-d-His' ) . '.csv"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Open output stream
		$output = fopen( 'php://output', 'w' );

		// Add UTF-8 BOM for Excel compatibility
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Get first submission to determine columns
		$first_submission = reset( $submissions );
		$data_keys        = array();
		if ( ! empty( $first_submission->submission_data ) && is_array( $first_submission->submission_data ) ) {
			$data_keys = array_keys( $first_submission->submission_data );
		}

		// CSV Headers
		$headers = array_merge(
			array(
				'Submission ID',
				'Form ID',
				'Form Name',
				'Submitted Date',
				'Submitted Time',
				'Lead Score',
				'Lead Quality',
				'Spam Score',
				'Is Spam',
				'IP Address',
				'User Agent',
			),
			$data_keys
		);

		fputcsv( $output, $headers );

		// CSV Rows
		foreach ( $submissions as $submission ) {
			// Get form name
			$form      = RT_FA_Form_Builder::get_form( $submission->form_id );
			$form_name = $form ? $form->form_name : 'Unknown';

			// Get lead quality category
			$lead_quality = RT_FA_Lead_Scorer::get_score_category( $submission->lead_score );

			// Format date and time
			$date_time = strtotime( $submission->submitted_at );
			$date      = gmdate( 'Y-m-d', $date_time );
			$time      = gmdate( 'H:i:s', $date_time );

			// Build row data
			$row = array(
				self::sanitize_csv_value( $submission->id ),
				self::sanitize_csv_value( $submission->form_id ),
				self::sanitize_csv_value( $form_name ),
				self::sanitize_csv_value( $date ),
				self::sanitize_csv_value( $time ),
				self::sanitize_csv_value( $submission->lead_score ),
				self::sanitize_csv_value( ucfirst( $lead_quality ) ),
				self::sanitize_csv_value( $submission->spam_score ),
				self::sanitize_csv_value( $submission->is_spam ? 'Yes' : 'No' ),
				self::sanitize_csv_value( $submission->ip_address ),
				self::sanitize_csv_value( $submission->user_agent ),
			);

			// Add submission data fields
			foreach ( $data_keys as $key ) {
				$value = '';
				if ( isset( $submission->submission_data[ $key ] ) ) {
					$field_value = $submission->submission_data[ $key ];
					if ( is_array( $field_value ) ) {
						$value = implode( ', ', $field_value );
					} else {
						$value = $field_value;
					}
				}
				$row[] = self::sanitize_csv_value( $value );
			}

			fputcsv( $output, $row );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Export settings to JSON
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public static function export_settings_json() {
		// Security check
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export settings.', 'raztech-form-architect' ) );
		}

		// Verify nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'rt_fa_export_settings' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'raztech-form-architect' ) );
		}

		// Gather all plugin settings
		$settings = array(
			'api_provider'          => get_option( 'rt_fa_api_provider', 'openai' ),
			'rate_limit'            => get_option( 'rt_fa_rate_limit', 50 ),
			'spam_detection'        => get_option( 'rt_fa_spam_detection', 1 ),
			'spam_threshold'        => get_option( 'rt_fa_spam_threshold', 60 ),
			'spam_ai_check'         => get_option( 'rt_fa_spam_ai_check', 0 ),
			'auto_response'         => get_option( 'rt_fa_auto_response', 0 ),
			'from_name'             => get_option( 'rt_fa_from_name', get_bloginfo( 'name' ) ),
			'from_email'            => get_option( 'rt_fa_from_email', get_option( 'admin_email' ) ),
			'reply_to_email'        => get_option( 'rt_fa_reply_to_email', get_option( 'admin_email' ) ),
			'skip_low_scores'       => get_option( 'rt_fa_skip_low_scores', 0 ),
			'export_date'           => gmdate( 'Y-m-d H:i:s' ),
			'plugin_version'        => RT_FA_VERSION,
			'wordpress_version'     => get_bloginfo( 'version' ),
		);

		// NOTE: API key is NOT exported for security reasons

		// Set headers for JSON download
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="smartforms-settings-' . gmdate( 'Y-m-d-His' ) . '.json"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		echo wp_json_encode( $settings, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Import settings from JSON
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public static function import_settings_json() {
		// Security check
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to import settings.', 'raztech-form-architect' ) );
		}

		// Verify nonce
		if ( ! isset( $_POST['rt_fa_import_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rt_fa_import_nonce'] ) ), 'rt_fa_import_settings' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'raztech-form-architect' ) );
		}

		// Validate file upload
		if ( ! isset( $_FILES['settings_file'] ) || $_FILES['settings_file']['error'] !== UPLOAD_ERR_OK ) {
			wp_safe_redirect( admin_url( 'admin.php?page=raztech-form-architect-settings&import=error&msg=upload_error' ) );
			exit;
		}

		// Get temp file path
		$tmp_file = $_FILES['settings_file']['tmp_name'];

		// Validate it's a real uploaded file
		if ( ! is_uploaded_file( $tmp_file ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=raztech-form-architect-settings&import=error&msg=invalid_file' ) );
			exit;
		}

		// Read file contents
		$json_content = file_get_contents( $tmp_file );
		$settings     = json_decode( $json_content, true );

		// Validate JSON
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $settings ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=raztech-form-architect-settings&import=error&msg=invalid_json' ) );
			exit;
		}

		// Import settings (excluding sensitive data like API key)
		$allowed_settings = array(
			'api_provider',
			'rate_limit',
			'spam_detection',
			'spam_threshold',
			'spam_ai_check',
			'auto_response',
			'from_name',
			'from_email',
			'reply_to_email',
			'skip_low_scores',
		);

		$imported_count = 0;
		foreach ( $allowed_settings as $setting_key ) {
			if ( isset( $settings[ $setting_key ] ) ) {
				// Sanitize based on type
				$value = $settings[ $setting_key ];
				if ( is_numeric( $value ) ) {
					$value = intval( $value );
				} elseif ( strpos( $setting_key, 'email' ) !== false ) {
					$value = sanitize_email( $value );
				} else {
					$value = sanitize_text_field( $value );
				}

				update_option( 'rt_fa_' . $setting_key, $value );
				$imported_count++;
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=raztech-form-architect-settings&import=success&count=' . $imported_count ) );
		exit;
	}

	/**
	 * Sanitize CSV value to prevent formula injection
	 *
	 * Prepends single quote to values starting with dangerous characters
	 * that could be interpreted as formulas in Excel/LibreOffice.
	 *
	 * @since    1.0.0
	 * @param    mixed $value Value to sanitize.
	 * @return   string       Sanitized value.
	 */
	private static function sanitize_csv_value( $value ) {
		// Convert to string
		$value = (string) $value;

		// Prepend single quote if starts with dangerous characters
		if ( strlen( $value ) > 0 &&
			in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			$value = "'" . $value;
		}

		return $value;
	}
}
