<?php
/**
 * Form builder functionality
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/includes
 */

/**
 * Form builder class.
 *
 * Handles form creation, updating, and deletion.
 */
class RT_FA_Form_Builder {

	/**
	 * Create a new form
	 *
	 * @since    1.0.0
	 * @param    array $form_data Form data including name, description, fields, settings.
	 * @return   int|false        Form ID on success, false on failure.
	 */
	public static function create_form( $form_data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rt_fa_forms';

		// Sanitize form data
		$form_name        = isset( $form_data['form_name'] ) ? sanitize_text_field( $form_data['form_name'] ) : '';
		$form_description = isset( $form_data['form_description'] ) ? wp_kses_post( $form_data['form_description'] ) : '';
		$form_fields      = isset( $form_data['form_fields'] ) ? wp_json_encode( $form_data['form_fields'] ) : '[]';
		$settings         = isset( $form_data['settings'] ) ? wp_json_encode( $form_data['settings'] ) : '{}';
		$conversational   = isset( $form_data['conversational_mode'] ) ? (int) $form_data['conversational_mode'] : 0;

		// Insert form
		$result = $wpdb->insert(
			$table_name,
			array(
				'form_name'           => $form_name,
				'form_description'    => $form_description,
				'form_fields'         => $form_fields,
				'settings'            => $settings,
				'conversational_mode' => $conversational,
				'created_at'          => current_time( 'mysql' ),
				'updated_at'          => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		if ( $result === false ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Update an existing form
	 *
	 * @since    1.0.0
	 * @param    int   $form_id   Form ID to update.
	 * @param    array $form_data Updated form data.
	 * @return   bool             True on success, false on failure.
	 */
	public static function update_form( $form_id, $form_data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rt_fa_forms';
		$form_id    = absint( $form_id );

		if ( ! $form_id ) {
			return false;
		}

		// Sanitize form data
		$update_data = array(
			'updated_at' => current_time( 'mysql' ),
		);
		$format      = array( '%s' );

		if ( isset( $form_data['form_name'] ) ) {
			$update_data['form_name'] = sanitize_text_field( $form_data['form_name'] );
			$format[]                 = '%s';
		}

		if ( isset( $form_data['form_description'] ) ) {
			$update_data['form_description'] = wp_kses_post( $form_data['form_description'] );
			$format[]                        = '%s';
		}

		if ( isset( $form_data['form_fields'] ) ) {
			$update_data['form_fields'] = wp_json_encode( $form_data['form_fields'] );
			$format[]                   = '%s';
		}

		if ( isset( $form_data['settings'] ) ) {
			$update_data['settings'] = wp_json_encode( $form_data['settings'] );
			$format[]                = '%s';
		}

		if ( isset( $form_data['conversational_mode'] ) ) {
			$update_data['conversational_mode'] = (int) $form_data['conversational_mode'];
			$format[]                           = '%d';
		}

		$result = $wpdb->update(
			$table_name,
			$update_data,
			array( 'id' => $form_id ),
			$format,
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get a form by ID
	 *
	 * @since    1.0.0
	 * @param    int $form_id Form ID.
	 * @return   object|null  Form object or null if not found.
	 */
	public static function get_form( $form_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rt_fa_forms';
		$form_id    = absint( $form_id );

		if ( ! $form_id ) {
			return null;
		}

		$form = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE id = %d",
				$form_id
			)
		);

		if ( $form ) {
			// Decode JSON fields
			$form->form_fields = ! empty( $form->form_fields ) ? json_decode( $form->form_fields, true ) : array();
			$form->settings    = ! empty( $form->settings ) ? json_decode( $form->settings, true ) : array();

			// Decode form_settings if it exists (added in templates feature)
			if ( isset( $form->form_settings ) ) {
				$form->form_settings = ! empty( $form->form_settings ) ? json_decode( $form->form_settings, true ) : array();
			}
		}

		return $form;
	}

	/**
	 * Get all forms
	 *
	 * @since    1.0.0
	 * @param    array $args Query arguments.
	 * @return   array       Array of form objects.
	 */
	public static function get_forms( $args = array() ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rt_fa_forms';

		$defaults = array(
			'orderby' => 'id',
			'order'   => 'DESC',
			'limit'   => -1,
			'offset'  => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$orderby = sanitize_text_field( $args['orderby'] );
		$order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$limit   = intval( $args['limit'] );
		$offset  = intval( $args['offset'] );

		$sql = "SELECT * FROM $table_name ORDER BY $orderby $order";

		if ( $limit > 0 ) {
			$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $limit, $offset );
		}

		$forms = $wpdb->get_results( $sql );

		foreach ( $forms as $form ) {
			$form->form_fields = ! empty( $form->form_fields ) ? json_decode( $form->form_fields, true ) : array();
			$form->settings    = ! empty( $form->settings ) ? json_decode( $form->settings, true ) : array();

			// Decode form_settings if it exists (added in templates feature)
			if ( isset( $form->form_settings ) ) {
				$form->form_settings = ! empty( $form->form_settings ) ? json_decode( $form->form_settings, true ) : array();
			}
		}

		return $forms;
	}

	/**
	 * Delete a form
	 *
	 * @since    1.0.0
	 * @param    int $form_id Form ID to delete.
	 * @return   bool         True on success, false on failure.
	 */
	public static function delete_form( $form_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rt_fa_forms';
		$form_id    = absint( $form_id );

		if ( ! $form_id ) {
			return false;
		}

		// Delete form
		$result = $wpdb->delete(
			$table_name,
			array( 'id' => $form_id ),
			array( '%d' )
		);

		// Also delete submissions for this form
		if ( $result !== false ) {
			$submissions_table = $wpdb->prefix . 'rt_fa_submissions';
			$wpdb->delete(
				$submissions_table,
				array( 'form_id' => $form_id ),
				array( '%d' )
			);

			// Delete analytics for this form
			$analytics_table = $wpdb->prefix . 'rt_fa_analytics';
			$wpdb->delete(
				$analytics_table,
				array( 'form_id' => $form_id ),
				array( '%d' )
			);
		}

		return $result !== false;
	}

	/**
	 * Save a form submission
	 *
	 * @since    1.0.0
	 * @param    int   $form_id         Form ID.
	 * @param    array $submission_data Submission data.
	 * @return   int|false              Submission ID on success, false on failure.
	 */
	public static function save_submission( $form_id, $submission_data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rt_fa_submissions';
		$form_id    = absint( $form_id );

		if ( ! $form_id ) {
			return false;
		}

		// Get user information
		$ip_address = self::get_user_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		// Insert submission
		$result = $wpdb->insert(
			$table_name,
			array(
				'form_id'         => $form_id,
				'submission_data' => wp_json_encode( $submission_data ),
				'lead_score'      => 0, // Will be calculated in Phase 3
				'spam_score'      => 0, // Will be calculated in Phase 5
				'is_spam'         => 0,
				'ip_address'      => $ip_address,
				'user_agent'      => $user_agent,
				'submitted_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s' )
		);

		if ( $result === false ) {
			return false;
		}

		$submission_id = $wpdb->insert_id;

		// PHASE 3: Calculate AI-powered lead score
		$lead_score = RT_FA_Lead_Scorer::calculate_score( $submission_id, $submission_data, $form_id );

		// PHASE 5: Detect spam
		$spam_analysis = RT_FA_Spam_Detector::analyze_submission(
			$submission_id,
			$submission_data,
			$form_id
		);

		// Update submission with lead score and spam analysis
		$wpdb->update(
			$table_name,
			array(
				'lead_score' => $lead_score,
				'spam_score' => $spam_analysis['spam_score'],
				'is_spam'    => $spam_analysis['is_spam'] ? 1 : 0,
			),
			array( 'id' => $submission_id ),
			array( '%d', '%d', '%d' ),
			array( '%d' )
		);

		// PHASE 6: Send auto-response (only for non-spam submissions)
		if ( ! $spam_analysis['is_spam'] ) {
			RT_FA_Auto_Responder::send_auto_response(
				$submission_id,
				$submission_data,
				$lead_score,
				$form_id
			);
		}

		// Update analytics
		self::update_analytics( $form_id, 'submission' );

		return $submission_id;
	}

	/**
	 * Get submissions for a form
	 *
	 * @since    1.0.0
	 * @param    int   $form_id Form ID.
	 * @param    array $args    Query arguments.
	 * @return   array          Array of submission objects.
	 */
	public static function get_submissions( $form_id = 0, $args = array() ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rt_fa_submissions';

		$defaults = array(
			'orderby' => 'id',
			'order'   => 'DESC',
			'limit'   => -1,
			'offset'  => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$orderby = sanitize_text_field( $args['orderby'] );
		$order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$limit   = intval( $args['limit'] );
		$offset  = intval( $args['offset'] );

		if ( $form_id > 0 ) {
			$sql = $wpdb->prepare(
				"SELECT * FROM $table_name WHERE form_id = %d ORDER BY $orderby $order",
				$form_id
			);
		} else {
			$sql = "SELECT * FROM $table_name ORDER BY $orderby $order";
		}

		if ( $limit > 0 ) {
			$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $limit, $offset );
		}

		$submissions = $wpdb->get_results( $sql );

		foreach ( $submissions as $submission ) {
			$submission->submission_data = ! empty( $submission->submission_data ) ? json_decode( $submission->submission_data, true ) : array();
		}

		return $submissions;
	}

	/**
	 * Update analytics for a form
	 *
	 * @since    1.0.0
	 * @param    int    $form_id Form ID.
	 * @param    string $type    Type of update: 'view' or 'submission'.
	 * @return   bool            True on success, false on failure.
	 */
	public static function update_analytics( $form_id, $type = 'view' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rt_fa_analytics';
		$form_id    = absint( $form_id );
		$today      = current_time( 'Y-m-d' );

		if ( ! $form_id ) {
			return false;
		}

		// Check if record exists for today
		$record = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE form_id = %d AND date = %s",
				$form_id,
				$today
			)
		);

		if ( $record ) {
			// Update existing record
			$update_data = array();
			if ( $type === 'view' ) {
				$update_data['views'] = $record->views + 1;
			} elseif ( $type === 'submission' ) {
				$update_data['submissions'] = $record->submissions + 1;
			}

			// Calculate conversion rate
			$views                          = $type === 'view' ? $update_data['views'] : $record->views;
			$submissions                    = $type === 'submission' ? $update_data['submissions'] : $record->submissions;
			$update_data['conversion_rate'] = $views > 0 ? round( ( $submissions / $views ) * 100, 2 ) : 0;

			$wpdb->update(
				$table_name,
				$update_data,
				array(
					'form_id' => $form_id,
					'date'    => $today,
				),
				array( '%d', '%d', '%f' ),
				array( '%d', '%s' )
			);
		} else {
			// Insert new record
			$views       = $type === 'view' ? 1 : 0;
			$submissions = $type === 'submission' ? 1 : 0;

			$wpdb->insert(
				$table_name,
				array(
					'form_id'         => $form_id,
					'views'           => $views,
					'submissions'     => $submissions,
					'conversion_rate' => 0,
					'date'            => $today,
				),
				array( '%d', '%d', '%d', '%f', '%s' )
			);
		}

		return true;
	}

	/**
	 * Get user IP address
	 *
	 * @since    1.0.0
	 * @return   string User IP address.
	 */
	private static function get_user_ip() {
		$ip_address = '';

		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip_address;
	}
}
