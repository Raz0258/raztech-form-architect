<?php
/**
 * AI-Powered Auto-Responder
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/includes
 */

/**
 * AI-Powered Auto-Responder class.
 *
 * Sends personalized, AI-generated email responses to form submissions.
 * Response quality and content are customized based on lead score:
 * - High priority (80-100): Detailed, personalized, with next steps
 * - Medium priority (50-79): Standard acknowledgment with timeline
 * - Low priority (0-49): Basic confirmation with resources
 */
class RAZTAIFO_Auto_Responder {

	/**
	 * Send auto-response to form submission
	 *
	 * @since    1.0.0
	 * @param    int   $submission_id   Submission ID.
	 * @param    array $submission_data Form data.
	 * @param    int   $lead_score      Lead score (0-100).
	 * @param    int   $form_id         Form ID.
	 * @return   bool                   True if sent, false otherwise.
	 */
	public static function send_auto_response( $submission_id, $submission_data, $lead_score, $form_id ) {
		// Check if auto-responses enabled
		if ( ! get_option( 'raztaifo_auto_response', 0 ) ) {
			return false;
		}

		// Check if we should skip low-quality leads
		if ( get_option( 'raztaifo_skip_low_scores', 0 ) && $lead_score < 30 ) {
			return false;
		}

		// Get form settings
		$form = RAZTAIFO_Form_Builder::get_form( $form_id );
		if ( ! $form ) {
			return false;
		}

		// Extract recipient email
		$recipient_email = self::extract_email( $submission_data );
		if ( empty( $recipient_email ) || ! is_email( $recipient_email ) ) {
			return false;
		}

		// Determine response type based on lead score
		if ( $lead_score >= 80 ) {
			$response_type = 'high_priority';
		} elseif ( $lead_score >= 50 ) {
			$response_type = 'medium_priority';
		} else {
			$response_type = 'low_priority';
		}

		// Generate personalized email using AI
		$email_content = self::generate_email_content(
			$submission_data,
			$lead_score,
			$response_type,
			$form
		);

		if ( ! $email_content || empty( $email_content['subject'] ) || empty( $email_content['body'] ) ) {
			return false;
		}

		// Send email
		$sent = self::send_email(
			$recipient_email,
			$email_content['subject'],
			$email_content['body'],
			$form
		);

		// Log auto-response
		if ( $sent ) {
			self::log_auto_response( $submission_id, $recipient_email );
		}

		return $sent;
	}

	/**
	 * Generate email content using AI
	 *
	 * @since    1.0.0
	 * @param    array  $submission_data Form data.
	 * @param    int    $lead_score      Lead score.
	 * @param    string $response_type   Response type (high/medium/low_priority).
	 * @param    object $form            Form object.
	 * @return   array|false             Array with 'subject' and 'body', or false on failure.
	 */
	private static function generate_email_content( $submission_data, $lead_score, $response_type, $form ) {
		// Check API configuration
		$api_key      = get_option( 'raztaifo_api_key', '' );
		$api_provider = get_option( 'raztaifo_api_provider', 'openai' );

		if ( empty( $api_key ) ) {
			// Fallback to generic templates if no API key
			return self::get_fallback_template( $submission_data, $lead_score, $response_type );
		}

		// Rate limit check (reuse spam detection limit)
		$rate_limit_key = 'raztaifo_autoresponse_' . date( 'YmdH' );
		$attempts       = get_transient( $rate_limit_key );
		if ( $attempts && $attempts >= 50 ) {
			// Fallback to template if rate limit exceeded
			return self::get_fallback_template( $submission_data, $lead_score, $response_type );
		}

		// Prepare submission data for AI
		$formatted_data = '';
		foreach ( $submission_data as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = implode( ', ', $value );
			}
			$formatted_data .= ucfirst( str_replace( '_', ' ', $key ) ) . ': ' . $value . "\n";
		}

		// Build AI prompt
		$company_name   = get_option( 'raztaifo_from_name', get_bloginfo( 'name' ) );
		$response_level = str_replace( '_', ' ', $response_type );

		$system_prompt = "You are a professional email writer for {$company_name}. Generate a personalized email response to a form submission.

Response Type: {$response_level}
Lead Score: {$lead_score}/100

Form Data:
{$formatted_data}

Requirements:
- Professional but friendly tone
- Personalize using their name and details from the submission
- Keep under 200 words
- Include clear next steps appropriate for the response type
- Match response enthusiasm to lead score (higher score = more enthusiastic)
- NO marketing spam or hard sales pressure
- Format as plain text email (no HTML)
- For high priority: offer to connect, schedule call, or provide detailed help
- For medium priority: thank them and set expectations for follow-up
- For low priority: acknowledge submission and provide general resources

Return ONLY valid JSON with this exact structure:
{
  \"subject\": \"Email subject line (max 60 characters)\",
  \"body\": \"Email body text\"
}";

		// Call AI API
		if ( $api_provider === 'openai' ) {
			$response = wp_remote_post(
				'https://api.openai.com/v1/chat/completions',
				array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $api_key,
					),
					'body'    => wp_json_encode(
						array(
							'model'       => 'gpt-3.5-turbo',
							'messages'    => array(
								array(
									'role'    => 'system',
									'content' => $system_prompt,
								),
								array(
									'role'    => 'user',
									'content' => 'Generate the auto-response email.',
								),
							),
							'max_tokens'  => 500,
							'temperature' => 0.7,
						)
					),
					'timeout' => 15,
				)
			);

			if ( ! is_wp_error( $response ) ) {
				$body = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( isset( $body['choices'][0]['message']['content'] ) ) {
					$ai_response = trim( $body['choices'][0]['message']['content'] );

					// Parse JSON response
					$email_data = json_decode( $ai_response, true );

					if ( $email_data && isset( $email_data['subject'], $email_data['body'] ) ) {
						// Update rate limit
						set_transient( $rate_limit_key, ( $attempts ? $attempts : 0 ) + 1, HOUR_IN_SECONDS );

						return array(
							'subject' => sanitize_text_field( $email_data['subject'] ),
							'body'    => sanitize_textarea_field( $email_data['body'] ),
						);
					}
				}
			}
		}

		// Fallback to template if AI fails
		return self::get_fallback_template( $submission_data, $lead_score, $response_type );
	}

	/**
	 * Get fallback email template
	 *
	 * Used when AI generation fails or is unavailable.
	 *
	 * @since    1.0.0
	 * @param    array  $submission_data Form data.
	 * @param    int    $lead_score      Lead score.
	 * @param    string $response_type   Response type.
	 * @return   array                   Email content.
	 */
	private static function get_fallback_template( $submission_data, $lead_score, $response_type ) {
		$name         = self::extract_name( $submission_data );
		$company_name = get_option( 'raztaifo_from_name', get_bloginfo( 'name' ) );
		$from_name    = get_option( 'raztaifo_from_name', get_bloginfo( 'name' ) );

		switch ( $response_type ) {
			case 'high_priority':
				$subject = "Great to hear from you" . ( $name ? ", {$name}" : '' ) . '!';
				$body    = "Hi" . ( $name ? " {$name}" : '' ) . ",\n\n";
				$body   .= "Thank you for reaching out to us. We're excited about the opportunity to work with you!\n\n";
				$body   .= "Based on what you've shared, I think we can definitely help. One of our team members will be in touch within 24 hours to discuss your needs in detail.\n\n";
				$body   .= "Looking forward to connecting!\n\n";
				$body   .= "Best regards,\n{$from_name}\n{$company_name}";
				break;

			case 'medium_priority':
				$subject = 'Thank you for your inquiry';
				$body    = "Hello" . ( $name ? " {$name}" : '' ) . ",\n\n";
				$body   .= "Thank you for contacting us. We've received your message and will review it shortly.\n\n";
				$body   .= "One of our team members will get back to you within 24-48 hours to discuss how we can assist you.\n\n";
				$body   .= "Best regards,\n{$from_name}\n{$company_name}";
				break;

			case 'low_priority':
			default:
				$subject = 'We received your submission';
				$body    = "Hello,\n\n";
				$body   .= "Thank you for your submission. We've received your information and will be in touch if we have any questions.\n\n";
				$body   .= "Best regards,\n{$company_name}";
				break;
		}

		return array(
			'subject' => $subject,
			'body'    => $body,
		);
	}

	/**
	 * Send email using WordPress wp_mail
	 *
	 * @since    1.0.0
	 * @param    string $to      Recipient email.
	 * @param    string $subject Email subject.
	 * @param    string $message Email body.
	 * @param    object $form    Form object.
	 * @return   bool            True if sent, false otherwise.
	 */
	private static function send_email( $to, $subject, $message, $form ) {
		// Get email settings
		$from_name  = get_option( 'raztaifo_from_name', get_bloginfo( 'name' ) );
		$from_email = get_option( 'raztaifo_from_email', get_option( 'admin_email' ) );
		$reply_to   = get_option( 'raztaifo_reply_to_email', get_option( 'admin_email' ) );

		// CRITICAL: Sanitize from_name to prevent email header injection
		$from_name = str_replace( array( "\r", "\n", "\r\n" ), '', $from_name );

		// Validate emails
		if ( ! is_email( $from_email ) ) {
			$from_email = get_option( 'admin_email' );
		}
		if ( ! is_email( $reply_to ) ) {
			$reply_to = get_option( 'admin_email' );
		}

		// Set headers
		$headers = array(
			'From: ' . $from_name . ' <' . $from_email . '>',
			'Reply-To: ' . $reply_to,
			'Content-Type: text/plain; charset=UTF-8',
		);

		// Send email
		$sent = wp_mail( $to, $subject, $message, $headers );

		return $sent;
	}

	/**
	 * Extract email from submission data
	 *
	 * @since    1.0.0
	 * @param    array $data Submission data.
	 * @return   string      Email address or empty string.
	 */
	private static function extract_email( $data ) {
		// First, check for fields with 'email' in the name
		foreach ( $data as $key => $value ) {
			if ( stripos( $key, 'email' ) !== false && is_string( $value ) ) {
				return trim( $value );
			}
		}

		// Fallback: check all values for valid email format
		foreach ( $data as $value ) {
			if ( is_string( $value ) && filter_var( trim( $value ), FILTER_VALIDATE_EMAIL ) ) {
				return trim( $value );
			}
		}

		return '';
	}

	/**
	 * Extract name from submission data
	 *
	 * @since    1.0.0
	 * @param    array $data Submission data.
	 * @return   string      Name or empty string.
	 */
	private static function extract_name( $data ) {
		// Check for common name fields
		$name_fields = array( 'name', 'full_name', 'first_name', 'your_name', 'fname' );

		foreach ( $name_fields as $field ) {
			if ( isset( $data[ $field ] ) && ! empty( $data[ $field ] ) ) {
				return sanitize_text_field( $data[ $field ] );
			}
		}

		// Check for fields containing 'name' in key
		foreach ( $data as $key => $value ) {
			if ( stripos( $key, 'name' ) !== false && is_string( $value ) && ! empty( $value ) ) {
				return sanitize_text_field( $value );
			}
		}

		return '';
	}

	/**
	 * Log auto-response
	 *
	 * Stores metadata about sent auto-responses for tracking.
	 *
	 * @since    1.0.0
	 * @param    int    $submission_id   Submission ID.
	 * @param    string $recipient_email Recipient email.
	 * @return   void
	 */
	private static function log_auto_response( $submission_id, $recipient_email ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'raztaifo_submissions';

		// Update submission metadata to indicate auto-response was sent
		// Note: This could be enhanced with a custom meta table in future phases
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $table_name
				SET user_agent = CONCAT(user_agent, ' [AutoResponse:Sent]')
				WHERE id = %d",
				$submission_id
			)
		);

		// Increment auto-response counter
		$counter = get_option( 'raztaifo_autoresponse_count', 0 );
		update_option( 'raztaifo_autoresponse_count', $counter + 1 );
	}
}
