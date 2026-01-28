<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/public
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The public-facing functionality of the plugin.
 */
class RAZTAIFO_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string $plugin_name The name of the plugin.
	 * @param    string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			RAZTAIFO_URL . 'public/css/form-styles.css',
			array(),
			$this->version,
			'all'
		);

		// PHASE 4: Enqueue conversational form styles
		wp_enqueue_style(
			$this->plugin_name . '-conversational',
			RAZTAIFO_URL . 'public/css/conversational-form.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name . '-form-handler',
			RAZTAIFO_URL . 'public/js/form-handler.js',
			array( 'jquery' ),
			$this->version,
			false
		);

		wp_enqueue_script(
			$this->plugin_name . '-validation',
			RAZTAIFO_URL . 'public/js/validation.js',
			array( 'jquery' ),
			$this->version,
			false
		);

		// PHASE 4: Enqueue conversational form scripts
		wp_enqueue_script(
			$this->plugin_name . '-conversational',
			RAZTAIFO_URL . 'public/js/conversational-form.js',
			array( 'jquery' ),
			$this->version,
			false
		);

		// Localize script
		wp_localize_script(
			$this->plugin_name . '-form-handler',
			'raztaifo_public',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Shortcode to display a form
	 *
	 * @since    1.0.0
	 * @param    array $atts Shortcode attributes.
	 * @return   string      Form HTML.
	 */
	public function smartform_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'smartform'
		);

		$form_id = intval( $atts['id'] );

		if ( ! $form_id ) {
			return '<p class="smartforms-error">' . esc_html__( 'Please provide a form ID.', 'raztech-form-architect' ) . '</p>';
		}

		return RAZTAIFO_Form_Renderer::render_form( $form_id );
	}

	/**
	 * Handle form submission via AJAX
	 *
	 * @since    1.0.0
	 */
	public function handle_form_submission() {
		// Get form ID
		$form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;

		if ( ! $form_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid form ID.', 'raztech-form-architect' ),
				)
			);
		}

		// Get form
		$form = RAZTAIFO_Form_Builder::get_form( $form_id );

		if ( ! $form ) {
			wp_send_json_error(
				array(
					'message' => __( 'Form not found.', 'raztech-form-architect' ),
				)
			);
		}

		// PHASE 4: Check if conversational mode
		$is_conversational = ! empty( $form->conversational_mode );

		if ( ! $is_conversational ) {
			// Verify nonce for traditional forms
			if ( ! isset( $_POST['raztaifo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['raztaifo_nonce'] ) ), 'raztaifo_submit_' . $form_id ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Security check failed.', 'raztech-form-architect' ),
					)
				);
			}
		} else {
			// Verify token for conversational forms (CSRF protection)
			if ( ! isset( $_POST['conversational_token'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['conversational_token'] ) ), 'raztaifo_conversational_' . $form_id ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Security check failed. Please refresh the page and try again.', 'raztech-form-architect' ),
					)
				);
			}
		}

		// Collect submission data
		$submission_data = array();

		// PHASE 4: Handle conversational form data
		if ( $is_conversational && isset( $_POST['form_data'] ) ) {
			// Conversational forms send all data in form_data object
			$raw_data = wp_unslash( $_POST['form_data'] );

			if ( ! empty( $form->form_fields ) && is_array( $form->form_fields ) ) {
				foreach ( $form->form_fields as $field ) {
					$field_name = isset( $field['name'] ) ? $field['name'] : '';

					if ( ! $field_name || ! isset( $raw_data[ $field_name ] ) ) {
						continue;
					}

					$field_value = $raw_data[ $field_name ];
					$field_type  = isset( $field['type'] ) ? $field['type'] : 'text';

					// Sanitize based on field type
					switch ( $field_type ) {
						case 'email':
							$field_value = sanitize_email( $field_value );
							break;
						case 'url':
							$field_value = esc_url_raw( $field_value );
							break;
						case 'textarea':
							$field_value = sanitize_textarea_field( $field_value );
							break;
						default:
							$field_value = sanitize_text_field( $field_value );
							break;
					}

					$submission_data[ $field_name ] = $field_value;

					// Validate required fields
					if ( isset( $field['required'] ) && $field['required'] && empty( $submission_data[ $field_name ] ) ) {
						$field_label = isset( $field['label'] ) ? $field['label'] : $field_name;
						wp_send_json_error(
							array(
								'message' => sprintf(
									/* translators: %s: Field label */
									__( '%s is required.', 'raztech-form-architect' ),
									$field_label
								),
							)
						);
					}
				}
			}
		} elseif ( ! empty( $form->form_fields ) && is_array( $form->form_fields ) ) {
			// Traditional form handling
			foreach ( $form->form_fields as $field ) {
				$field_name = isset( $field['name'] ) ? $field['name'] : '';

				if ( ! $field_name ) {
					continue;
				}

				// Get field value
				if ( isset( $_POST[ $field_name ] ) ) {
					$field_value = wp_unslash( $_POST[ $field_name ] );

					// Sanitize based on field type
					$field_type = isset( $field['type'] ) ? $field['type'] : 'text';

					switch ( $field_type ) {
						case 'email':
							$field_value = sanitize_email( $field_value );
							break;
						case 'url':
							$field_value = esc_url_raw( $field_value );
							break;
						case 'textarea':
							$field_value = sanitize_textarea_field( $field_value );
							break;
						case 'checkbox':
							if ( is_array( $field_value ) ) {
								$field_value = array_map( 'sanitize_text_field', $field_value );
							} else {
								$field_value = sanitize_text_field( $field_value );
							}
							break;
						default:
							$field_value = sanitize_text_field( $field_value );
							break;
					}

					$submission_data[ $field_name ] = $field_value;
				}

				// Validate required fields
				if ( isset( $field['required'] ) && $field['required'] && empty( $submission_data[ $field_name ] ) ) {
					$field_label = isset( $field['label'] ) ? $field['label'] : $field_name;
					wp_send_json_error(
						array(
							'message' => sprintf(
								/* translators: %s: Field label */
								__( '%s is required.', 'raztech-form-architect' ),
								$field_label
							),
						)
					);
				}
			}
		}

		// Save submission
		$submission_id = RAZTAIFO_Form_Builder::save_submission( $form_id, $submission_data );

		if ( ! $submission_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to save submission. Please try again.', 'raztech-form-architect' ),
				)
			);
		}

		// Get success message
		$success_message = ! empty( $form->settings['success_message'] ) ? $form->settings['success_message'] : __( 'Thank you for your submission!', 'raztech-form-architect' );

		wp_send_json_success(
			array(
				'message'       => $success_message,
				'submission_id' => $submission_id,
			)
		);
	}
}
