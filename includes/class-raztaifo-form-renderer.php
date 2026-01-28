<?php
/**
 * Form renderer functionality
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/includes
 */

/**
 * Form renderer class.
 *
 * Handles rendering forms on the frontend.
 */
class RAZTAIFO_Form_Renderer {

	/**
	 * Render a form
	 *
	 * @since    1.0.0
	 * @param    int $form_id Form ID to render.
	 * @return   string       HTML output of the form.
	 */
	public static function render_form( $form_id ) {
		$form = RAZTAIFO_Form_Builder::get_form( $form_id );

		if ( ! $form ) {
			return '<p class="smartforms-error">' . esc_html__( 'Form not found.', 'raztech-form-architect' ) . '</p>';
		}

		// PHASE 4: Check if conversational mode enabled
		if ( ! empty( $form->conversational_mode ) ) {
			return self::render_conversational_form( $form_id );
		}

		// Track form view
		RAZTAIFO_Form_Builder::update_analytics( $form_id, 'view' );

		// Start output buffering
		ob_start();

		?>
		<div class="smartforms-wrapper" id="smartforms-wrapper-<?php echo esc_attr( $form_id ); ?>">
			<form class="smartforms-form" id="smartforms-<?php echo esc_attr( $form_id ); ?>" data-form-id="<?php echo esc_attr( $form_id ); ?>">

				<?php if ( ! empty( $form->form_description ) ) : ?>
					<div class="smartforms-description">
						<?php echo wp_kses_post( $form->form_description ); ?>
					</div>
				<?php endif; ?>

				<div class="smartforms-fields">
					<?php
					if ( ! empty( $form->form_fields ) && is_array( $form->form_fields ) ) {
						foreach ( $form->form_fields as $field ) {
							self::render_field( $field );
						}
					}
					?>
				</div>

				<div class="smartforms-submit-wrapper">
					<button type="submit" class="smartforms-submit-button">
						<?php
						$submit_text = ! empty( $form->settings['submit_button_text'] ) ? $form->settings['submit_button_text'] : __( 'Submit', 'raztech-form-architect' );
						echo esc_html( $submit_text );
						?>
					</button>
					<span class="smartforms-spinner" style="display:none;">
						<span class="smartforms-spinner-icon"></span>
					</span>
				</div>

				<div class="smartforms-message" style="display:none;"></div>

				<?php wp_nonce_field( 'raztaifo_submit_' . $form_id, 'raztaifo_nonce' ); ?>
			</form>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render a single form field
	 *
	 * @since    1.0.0
	 * @param    array $field Field data.
	 * @return   void
	 */
	private static function render_field( $field ) {
		$type        = isset( $field['type'] ) ? $field['type'] : 'text';
		$label       = isset( $field['label'] ) ? $field['label'] : '';
		$name        = isset( $field['name'] ) ? $field['name'] : '';
		$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
		$required    = isset( $field['required'] ) && $field['required'] ? true : false;
		$validation  = isset( $field['validation'] ) ? $field['validation'] : '';
		$options     = isset( $field['options'] ) ? $field['options'] : array();

		$required_attr = $required ? 'required' : '';
		$required_mark = $required ? '<span class="smartforms-required">*</span>' : '';

		?>
		<div class="smartforms-field smartforms-field-<?php echo esc_attr( $type ); ?>" data-field-name="<?php echo esc_attr( $name ); ?>">

			<?php if ( $label && ! in_array( $type, array( 'checkbox' ) ) ) : ?>
				<label for="smartforms-field-<?php echo esc_attr( $name ); ?>" class="smartforms-label">
					<?php echo esc_html( $label ); ?> <?php echo wp_kses_post( $required_mark ); ?>
				</label>
			<?php endif; ?>

			<?php
			switch ( $type ) {
				case 'text':
				case 'email':
				case 'tel':
				case 'url':
				case 'date':
				case 'number':
					?>
					<input
						type="<?php echo esc_attr( $type ); ?>"
						id="smartforms-field-<?php echo esc_attr( $name ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						class="smartforms-input smartforms-input-<?php echo esc_attr( $type ); ?>"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						<?php echo esc_attr( $required_attr ); ?>
						data-validation="<?php echo esc_attr( $validation ); ?>"
					/>
					<?php
					break;

				case 'textarea':
					?>
					<textarea
						id="smartforms-field-<?php echo esc_attr( $name ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						class="smartforms-input smartforms-textarea"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						<?php echo esc_attr( $required_attr ); ?>
						rows="5"
					></textarea>
					<?php
					break;

				case 'select':
					?>
					<select
						id="smartforms-field-<?php echo esc_attr( $name ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						class="smartforms-input smartforms-select"
						<?php echo esc_attr( $required_attr ); ?>
					>
						<option value=""><?php echo esc_html( $placeholder ? $placeholder : __( 'Select an option', 'raztech-form-architect' ) ); ?></option>
						<?php
						if ( is_array( $options ) ) {
							foreach ( $options as $option ) {
								?>
								<option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
								<?php
							}
						}
						?>
					</select>
					<?php
					break;

				case 'radio':
					?>
					<div class="smartforms-radio-group">
						<?php
						if ( is_array( $options ) ) {
							foreach ( $options as $index => $option ) {
								?>
								<label class="smartforms-radio-label">
									<input
										type="radio"
										name="<?php echo esc_attr( $name ); ?>"
										value="<?php echo esc_attr( $option ); ?>"
										class="smartforms-radio"
										<?php echo esc_attr( $required_attr ); ?>
									/>
									<span><?php echo esc_html( $option ); ?></span>
								</label>
								<?php
							}
						}
						?>
					</div>
					<?php
					break;

				case 'checkbox':
					if ( is_array( $options ) && count( $options ) > 0 ) {
						// Multiple checkboxes
						?>
						<div class="smartforms-checkbox-group">
							<label class="smartforms-label">
								<?php echo esc_html( $label ); ?> <?php echo wp_kses_post( $required_mark ); ?>
							</label>
							<?php
							foreach ( $options as $option ) {
								?>
								<label class="smartforms-checkbox-label">
									<input
										type="checkbox"
										name="<?php echo esc_attr( $name ); ?>[]"
										value="<?php echo esc_attr( $option ); ?>"
										class="smartforms-checkbox"
									/>
									<span><?php echo esc_html( $option ); ?></span>
								</label>
								<?php
							}
							?>
						</div>
						<?php
					} else {
						// Single checkbox
						?>
						<label class="smartforms-checkbox-label smartforms-checkbox-single">
							<input
								type="checkbox"
								id="smartforms-field-<?php echo esc_attr( $name ); ?>"
								name="<?php echo esc_attr( $name ); ?>"
								value="1"
								class="smartforms-checkbox"
								<?php echo esc_attr( $required_attr ); ?>
							/>
							<span><?php echo esc_html( $label ); ?> <?php echo wp_kses_post( $required_mark ); ?></span>
						</label>
						<?php
					}
					break;

				case 'file':
					?>
					<input
						type="file"
						id="smartforms-field-<?php echo esc_attr( $name ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						class="smartforms-input smartforms-file"
						<?php echo esc_attr( $required_attr ); ?>
					/>
					<?php
					break;

				default:
					?>
					<input
						type="text"
						id="smartforms-field-<?php echo esc_attr( $name ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						class="smartforms-input smartforms-input-text"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						<?php echo esc_attr( $required_attr ); ?>
					/>
					<?php
					break;
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render conversational form
	 *
	 * PHASE 4: Renders form as a chat-style conversational interface
	 *
	 * @since    1.0.0
	 * @param    int $form_id Form ID.
	 * @return   string       HTML for chat interface.
	 */
	public static function render_conversational_form( $form_id ) {
		$form = RAZTAIFO_Form_Builder::get_form( $form_id );

		if ( ! $form ) {
			return '<p class="smartforms-error">' . esc_html__( 'Form not found.', 'raztech-form-architect' ) . '</p>';
		}

		// Track form view
		RAZTAIFO_Form_Builder::update_analytics( $form_id, 'view' );

		// Prepare fields data for JavaScript
		$fields_json = wp_json_encode( $form->form_fields );

		// Generate CSRF token for conversational form
		$token = wp_create_nonce( 'raztaifo_conversational_' . $form_id );

		// Prepare config
		$config = array(
			'ajax_url'         => admin_url( 'admin-ajax.php' ),
			'form_description' => $form->form_description,
			'submit_text'      => ! empty( $form->settings['submit_button_text'] ) ? $form->settings['submit_button_text'] : __( 'Submit', 'raztech-form-architect' ),
			'success_message'  => ! empty( $form->settings['success_message'] ) ? $form->settings['success_message'] : __( 'Thank you!', 'raztech-form-architect' ),
			'token'            => $token, // CSRF token for security
		);
		$config_json = wp_json_encode( $config );

		ob_start();
		?>
		<div class="smartforms-chat-wrapper" id="smartforms-chat-<?php echo esc_attr( $form_id ); ?>">
			<div class="smartforms-chat-container">
				<div class="smartforms-chat-progress">
					<div class="smartforms-progress-bar" style="width: 0%;"></div>
				</div>

				<div class="smartforms-chat-messages" id="smartforms-messages-<?php echo esc_attr( $form_id ); ?>">
					<!-- Messages will be inserted here by JavaScript -->
				</div>

				<div class="smartforms-typing-indicator" style="display: none;">
					<span></span><span></span><span></span>
				</div>

				<div class="smartforms-chat-input-area">
					<input
						type="text"
						class="smartforms-chat-input"
						id="smartforms-chat-input-<?php echo esc_attr( $form_id ); ?>"
						placeholder="<?php echo esc_attr__( 'Type your answer...', 'raztech-form-architect' ); ?>"
						disabled
						aria-label="<?php echo esc_attr__( 'Chat input', 'raztech-form-architect' ); ?>"
					/>
					<button
						type="button"
						class="smartforms-chat-send-btn"
						id="smartforms-chat-send-<?php echo esc_attr( $form_id ); ?>"
						disabled
						aria-label="<?php echo esc_attr__( 'Send message', 'raztech-form-architect' ); ?>"
					>
						<?php echo esc_html__( 'Send', 'raztech-form-architect' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php

		// Add form configuration as data attributes for JavaScript initialization
		$form_selector = 'rt-fa-conversational-form-' . intval( $form_id );
		$init_script = sprintf(
			'jQuery(document).ready(function($) {
				if (typeof rt_faInitConversational === "function") {
					rt_faInitConversational(%d, %s, %s);
				}
			});',
			intval( $form_id ),
			$fields_json,
			$config_json
		);
		wp_add_inline_script( 'rt-fa-conversational-form', $init_script );
		?>
		<?php

		return ob_get_clean();
	}
}
