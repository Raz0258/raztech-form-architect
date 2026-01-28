<?php
/**
 * AI Form Generator
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/includes
 */

/**
 * AI Form Generator class.
 *
 * Handles AI-powered form generation using OpenAI GPT-4 or Anthropic Claude.
 * Converts natural language descriptions into structured form configurations.
 *
 * Features:
 * - Multi-provider support (OpenAI, Anthropic)
 * - Rate limiting (hourly request limits)
 * - Intelligent field type detection
 * - Validation rule generation
 * - Option list creation for select/radio/checkbox fields
 *
 * @since    1.0.0
 */
class RAZTAIFO_Generator {

	/**
	 * Generate form structure from description using AI
	 *
	 * Takes a natural language description and optional parameters,
	 * calls the configured AI provider, and returns a structured form array.
	 *
	 * @since    1.0.0
	 * @param    string $description User's form description.
	 * @param    array  $options     Optional parameters (complexity, purpose, audience).
	 * @return   array|WP_Error      Form structure array on success, WP_Error on failure.
	 */
	public static function generate_form( $description, $options = array() ) {
		// Validate description
		$description = sanitize_textarea_field( $description );
		if ( empty( $description ) || strlen( $description ) < 10 ) {
			return new WP_Error(
				'invalid_description',
				__( 'Form description is too short. Please provide at least 10 characters.', 'raztech-form-architect' )
			);
		}

		// Check rate limit
		$rate_status = self::get_rate_limit_status();
		if ( $rate_status['remaining'] <= 0 ) {
			return new WP_Error(
				'rate_limit_exceeded',
				sprintf(
					/* translators: %d: Minutes until reset */
					__( 'Rate limit exceeded. Please try again in %d minutes.', 'raztech-form-architect' ),
					$rate_status['reset_in']
				)
			);
		}

		// Get API configuration
		$api_provider = get_option( 'raztaifo_api_provider', 'openai' );
		$api_key      = get_option( 'raztaifo_api_key', '' );

		if ( empty( $api_key ) ) {
			return new WP_Error(
				'missing_api_key',
				__( 'API key not configured. Please add your API key in Settings.', 'raztech-form-architect' )
			);
		}

		// Sanitize options
		$complexity = isset( $options['complexity'] ) ? sanitize_text_field( $options['complexity'] ) : 'intermediate';
		$purpose    = isset( $options['purpose'] ) ? sanitize_text_field( $options['purpose'] ) : '';
		$audience   = isset( $options['audience'] ) ? sanitize_text_field( $options['audience'] ) : '';

		// Build AI prompt
		$prompt = self::build_generation_prompt( $description, $complexity, $purpose, $audience );

		// Call AI provider
		$ai_response = self::call_ai_provider( $api_provider, $api_key, $prompt );

		if ( is_wp_error( $ai_response ) ) {
			return $ai_response;
		}

		// Parse AI response into form structure
		$form_structure = self::parse_ai_response( $ai_response );

		if ( is_wp_error( $form_structure ) ) {
			return $form_structure;
		}

		// Increment rate limit counter
		self::increment_rate_limit();

		return $form_structure;
	}

	/**
	 * Get current rate limit status
	 *
	 * Returns the current API usage statistics including:
	 * - Total requests used this hour
	 * - Maximum allowed requests per hour
	 * - Remaining requests
	 * - Minutes until counter resets
	 *
	 * @since    1.0.0
	 * @return   array Rate limit status with keys: used, limit, remaining, reset_in.
	 */
	public static function get_rate_limit_status() {
		$limit     = intval( get_option( 'raztaifo_rate_limit', 50 ) );
		$used_data = get_transient( 'raztaifo_generator_requests' );

		// If no data or expired, start fresh
		if ( false === $used_data ) {
			$used      = 0;
			$timestamp = time();
			set_transient( 'raztaifo_generator_requests', array( 'count' => 0, 'timestamp' => $timestamp ), HOUR_IN_SECONDS );
		} else {
			$used      = intval( $used_data['count'] );
			$timestamp = intval( $used_data['timestamp'] );
		}

		// Calculate minutes until reset
		$elapsed  = time() - $timestamp;
		$reset_in = max( 0, ceil( ( HOUR_IN_SECONDS - $elapsed ) / 60 ) );

		return array(
			'used'      => $used,
			'limit'     => $limit,
			'remaining' => max( 0, $limit - $used ),
			'reset_in'  => $reset_in,
		);
	}

	/**
	 * Build AI prompt for form generation
	 *
	 * Constructs a detailed prompt that instructs the AI to generate
	 * a structured JSON form configuration based on the user's description.
	 *
	 * @since    1.0.0
	 * @param    string $description User's form description.
	 * @param    string $complexity  Form complexity (simple, intermediate, advanced).
	 * @param    string $purpose     Optional form purpose.
	 * @param    string $audience    Optional target audience.
	 * @return   string              Complete AI prompt.
	 */
	private static function build_generation_prompt( $description, $complexity, $purpose, $audience ) {
		// Define field count based on complexity
		$field_counts = array(
			'simple'       => '3-5',
			'intermediate' => '6-10',
			'advanced'     => '10-15',
		);

		$field_count = isset( $field_counts[ $complexity ] ) ? $field_counts[ $complexity ] : '6-10';

		$prompt = "You are a professional form designer. Generate a complete, production-ready form structure based on the following requirements.\n\n";
		$prompt .= "FORM DESCRIPTION:\n{$description}\n\n";

		if ( ! empty( $purpose ) ) {
			$prompt .= "PURPOSE: {$purpose}\n";
		}

		if ( ! empty( $audience ) ) {
			$prompt .= "TARGET AUDIENCE: {$audience}\n";
		}

		$prompt .= "\nCOMPLEXITY: {$complexity} ({$field_count} fields)\n\n";

		$prompt .= "REQUIREMENTS:\n";
		$prompt .= "1. Create exactly {$field_count} relevant fields\n";
		$prompt .= "2. Use appropriate field types: text, email, tel, textarea, select, radio, checkbox, date, number, url\n";
		$prompt .= "3. Add clear, user-friendly labels\n";
		$prompt .= "4. Include helpful placeholder text\n";
		$prompt .= "5. Set appropriate validation (required fields, pattern matching, min/max values)\n";
		$prompt .= "6. For select/radio/checkbox fields, provide 3-6 realistic options\n";
		$prompt .= "7. Ensure logical field ordering (name/email first, message/comments last)\n\n";

		$prompt .= "OUTPUT FORMAT: Respond with ONLY valid JSON (no markdown, no explanation). Structure:\n";
		$prompt .= '```json' . "\n";
		$prompt .= "{\n";
		$prompt .= '  "form_title": "Clear, descriptive form title",' . "\n";
		$prompt .= '  "form_description": "Brief description of form purpose",' . "\n";
		$prompt .= '  "fields": [' . "\n";
		$prompt .= "    {\n";
		$prompt .= '      "id": "field_1",' . "\n";
		$prompt .= '      "type": "text|email|tel|textarea|select|radio|checkbox|date|number|url",' . "\n";
		$prompt .= '      "label": "Field Label",' . "\n";
		$prompt .= '      "placeholder": "Helpful placeholder text",' . "\n";
		$prompt .= '      "required": true|false,' . "\n";
		$prompt .= '      "validation": {"pattern": "regex", "min": number, "max": number},' . "\n";
		$prompt .= '      "options": ["Option 1", "Option 2"] // Only for select/radio/checkbox' . "\n";
		$prompt .= "    }\n";
		$prompt .= "  ],\n";
		$prompt .= '  "submit_button_text": "Submit button text"' . "\n";
		$prompt .= "}\n";
		$prompt .= '```' . "\n\n";

		$prompt .= "Generate the form now:";

		return $prompt;
	}

	/**
	 * Call AI provider API
	 *
	 * Makes HTTP request to OpenAI or Anthropic API with the generation prompt.
	 * Handles provider-specific request formatting and authentication.
	 *
	 * @since    1.0.0
	 * @param    string $provider API provider (openai or claude).
	 * @param    string $api_key  API key.
	 * @param    string $prompt   Generation prompt.
	 * @return   string|WP_Error  AI response text on success, WP_Error on failure.
	 */
	private static function call_ai_provider( $provider, $api_key, $prompt ) {
		if ( 'openai' === $provider ) {
			return self::call_openai( $api_key, $prompt );
		} elseif ( 'claude' === $provider ) {
			return self::call_anthropic( $api_key, $prompt );
		} else {
			return new WP_Error(
				'invalid_provider',
				__( 'Invalid AI provider selected.', 'raztech-form-architect' )
			);
		}
	}

	/**
	 * Call OpenAI API
	 *
	 * @since    1.0.0
	 * @param    string $api_key API key.
	 * @param    string $prompt  Generation prompt.
	 * @return   string|WP_Error Response text or error.
	 */
	private static function call_openai( $api_key, $prompt ) {
		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				),
				'body'    => wp_json_encode(
					array(
						'model'       => 'gpt-4',
						'messages'    => array(
							array(
								'role'    => 'system',
								'content' => 'You are a professional form designer. You generate structured form configurations in JSON format.',
							),
							array(
								'role'    => 'user',
								'content' => $prompt,
							),
						),
						'temperature' => 0.7,
						'max_tokens'  => 2000,
					)
				),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_request_failed',
				sprintf(
					/* translators: %s: Error message */
					__( 'API request failed: %s', 'raztech-form-architect' ),
					$response->get_error_message()
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( 200 !== $status_code ) {
			$error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Unknown API error', 'raztech-form-architect' );
			return new WP_Error(
				'api_error',
				sprintf(
					/* translators: %s: Error message */
					__( 'OpenAI API error: %s', 'raztech-form-architect' ),
					$error_message
				)
			);
		}

		if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
			return new WP_Error(
				'invalid_response',
				__( 'Invalid response format from OpenAI API.', 'raztech-form-architect' )
			);
		}

		return $data['choices'][0]['message']['content'];
	}

	/**
	 * Call Anthropic Claude API
	 *
	 * @since    1.0.0
	 * @param    string $api_key API key.
	 * @param    string $prompt  Generation prompt.
	 * @return   string|WP_Error Response text or error.
	 */
	private static function call_anthropic( $api_key, $prompt ) {
		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'headers' => array(
					'Content-Type'         => 'application/json',
					'x-api-key'            => $api_key,
					'anthropic-version'    => '2023-06-01',
				),
				'body'    => wp_json_encode(
					array(
						'model'      => 'claude-3-5-sonnet-20241022',
						'max_tokens' => 2000,
						'messages'   => array(
							array(
								'role'    => 'user',
								'content' => $prompt,
							),
						),
						'temperature' => 0.7,
					)
				),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_request_failed',
				sprintf(
					/* translators: %s: Error message */
					__( 'API request failed: %s', 'raztech-form-architect' ),
					$response->get_error_message()
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( 200 !== $status_code ) {
			$error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Unknown API error', 'raztech-form-architect' );
			return new WP_Error(
				'api_error',
				sprintf(
					/* translators: %s: Error message */
					__( 'Anthropic API error: %s', 'raztech-form-architect' ),
					$error_message
				)
			);
		}

		if ( ! isset( $data['content'][0]['text'] ) ) {
			return new WP_Error(
				'invalid_response',
				__( 'Invalid response format from Anthropic API.', 'raztech-form-architect' )
			);
		}

		return $data['content'][0]['text'];
	}

	/**
	 * Parse AI response into form structure
	 *
	 * Extracts JSON from AI response (handling markdown code blocks),
	 * validates the structure, and returns a sanitized form array.
	 *
	 * @since    1.0.0
	 * @param    string $ai_response Raw AI response text.
	 * @return   array|WP_Error      Form structure or error.
	 */
	private static function parse_ai_response( $ai_response ) {
		// Extract JSON from response (may be wrapped in markdown code blocks)
		$json_text = $ai_response;

		// Remove markdown code blocks if present
		if ( preg_match( '/```(?:json)?\s*(\{.*?\})\s*```/s', $ai_response, $matches ) ) {
			$json_text = $matches[1];
		}

		// Decode JSON
		$form_data = json_decode( $json_text, true );

		if ( null === $form_data ) {
			return new WP_Error(
				'invalid_json',
				__( 'Failed to parse AI response. Please try again.', 'raztech-form-architect' )
			);
		}

		// Validate required structure
		if ( ! isset( $form_data['fields'] ) || ! is_array( $form_data['fields'] ) ) {
			return new WP_Error(
				'invalid_structure',
				__( 'AI response missing required fields array.', 'raztech-form-architect' )
			);
		}

		if ( empty( $form_data['fields'] ) ) {
			return new WP_Error(
				'no_fields',
				__( 'AI response contains no fields. Please try again with a more detailed description.', 'raztech-form-architect' )
			);
		}

		// Sanitize and validate form structure
		// Note: JavaScript expects 'form_name', 'form_fields', and 'settings' structure
		$form_title = isset( $form_data['form_title'] ) ? sanitize_text_field( $form_data['form_title'] ) : __( 'Generated Form', 'raztech-form-architect' );
		$sanitized_form = array(
			'form_name'        => $form_title,  // JavaScript expects 'form_name', not 'form_title'
			'form_description' => isset( $form_data['form_description'] ) ? sanitize_text_field( $form_data['form_description'] ) : '',
			'settings'         => array(
				'submit_button_text' => isset( $form_data['submit_button_text'] ) ? sanitize_text_field( $form_data['submit_button_text'] ) : __( 'Submit', 'raztech-form-architect' ),
				'success_message'    => __( 'Thank you! Your form has been submitted successfully.', 'raztech-form-architect' ),
			),
			'form_fields'      => array(),  // JavaScript expects 'form_fields', not 'fields'
		);

		// Validate and sanitize each field
		$allowed_types = array( 'text', 'email', 'tel', 'textarea', 'select', 'radio', 'checkbox', 'date', 'number', 'url' );

		foreach ( $form_data['fields'] as $index => $field ) {
			if ( ! is_array( $field ) || ! isset( $field['type'] ) || ! isset( $field['label'] ) ) {
				continue; // Skip invalid fields
			}

			$field_type = sanitize_text_field( $field['type'] );
			if ( ! in_array( $field_type, $allowed_types, true ) ) {
				$field_type = 'text'; // Default to text for invalid types
			}

			// Generate field ID and name
			$field_id   = isset( $field['id'] ) ? sanitize_key( $field['id'] ) : 'field_' . ( $index + 1 );
			$field_name = sanitize_key( strtolower( str_replace( ' ', '_', $field['label'] ) ) );

			$sanitized_field = array(
				'id'          => $field_id,
				'name'        => $field_name,  // JavaScript expects 'name' field
				'type'        => $field_type,
				'label'       => sanitize_text_field( $field['label'] ),
				'placeholder' => isset( $field['placeholder'] ) ? sanitize_text_field( $field['placeholder'] ) : '',
				'required'    => isset( $field['required'] ) && $field['required'] ? true : false,
			);

			// Add validation rules if present
			if ( isset( $field['validation'] ) && is_array( $field['validation'] ) ) {
				$sanitized_field['validation'] = array();

				if ( isset( $field['validation']['pattern'] ) ) {
					$sanitized_field['validation']['pattern'] = sanitize_text_field( $field['validation']['pattern'] );
				}

				if ( isset( $field['validation']['min'] ) ) {
					$sanitized_field['validation']['min'] = intval( $field['validation']['min'] );
				}

				if ( isset( $field['validation']['max'] ) ) {
					$sanitized_field['validation']['max'] = intval( $field['validation']['max'] );
				}
			}

			// Add options for select/radio/checkbox fields
			if ( in_array( $field_type, array( 'select', 'radio', 'checkbox' ), true ) && isset( $field['options'] ) && is_array( $field['options'] ) ) {
				$sanitized_field['options'] = array_map( 'sanitize_text_field', $field['options'] );
			}

			$sanitized_form['form_fields'][] = $sanitized_field;
		}

		return $sanitized_form;
	}

	/**
	 * Increment rate limit counter
	 *
	 * Increases the request count for the current hour.
	 * Called after successful form generation.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	private static function increment_rate_limit() {
		$used_data = get_transient( 'raztaifo_generator_requests' );

		if ( false === $used_data ) {
			$count     = 1;
			$timestamp = time();
		} else {
			$count     = intval( $used_data['count'] ) + 1;
			$timestamp = intval( $used_data['timestamp'] );
		}

		set_transient(
			'raztaifo_generator_requests',
			array(
				'count'     => $count,
				'timestamp' => $timestamp,
			),
			HOUR_IN_SECONDS
		);
	}
}
