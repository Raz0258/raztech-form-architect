<?php
/**
 * Form Builder Page
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Check if editing existing form
$form_id = isset( $_GET['form_id'] ) ? intval( $_GET['form_id'] ) : 0;
$form    = $form_id ? RAZTAIFO_Form_Builder::get_form( $form_id ) : null;

// FIXED: Ensure all variables default to empty strings, never null
$form_name        = ( $form && $form->form_name ) ? $form->form_name : '';
$form_description = ( $form && $form->form_description ) ? $form->form_description : '';
$form_fields      = ( $form && ! empty( $form->form_fields ) ) ? $form->form_fields : array();
$submit_text      = ( $form && ! empty( $form->settings['submit_button_text'] ) ) ? $form->settings['submit_button_text'] : 'Submit';
$success_message  = ( $form && ! empty( $form->settings['success_message'] ) ) ? $form->settings['success_message'] : 'Thank you for your submission!';
?>

<div class="wrap smartforms-form-builder">
	<h1 class="wp-heading-inline"><?php echo $form_id ? esc_html__( 'Edit Form', 'raztech-form-architect' ) : esc_html__( 'Add New Form', 'raztech-form-architect' ); ?></h1>

	<?php if ( ! $form_id ) : ?>
		<!-- PHASE 2: AI Form Generation Button -->
		<button type="button" id="smartforms-open-ai-modal" class="page-title-action smartforms-ai-button">
			✨ <?php echo esc_html__( 'Generate with AI', 'raztech-form-architect' ); ?>
		</button>
		<hr class="wp-header-end">
		<div class="smartforms-ai-notice">
			<p>
				<strong><?php echo esc_html__( 'New!', 'raztech-form-architect' ); ?></strong>
				<?php echo esc_html__( 'Try our AI form generator to create forms in seconds by describing them in natural language.', 'raztech-form-architect' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['created'] ) ) : ?>
		<div class="notice notice-success is-dismissible smartforms-success-notice">
			<p>
				<strong><?php echo esc_html__( '🎉 Success!', 'raztech-form-architect' ); ?></strong>
				<?php echo esc_html__( 'Your form has been created successfully!', 'raztech-form-architect' ); ?>
			</p>

			<?php if ( isset( $_GET['page_created'] ) ) : ?>
				<?php
				$page_id       = intval( $_GET['page_created'] );
				$page_status   = isset( $_GET['page_status'] ) ? sanitize_text_field( wp_unslash( $_GET['page_status'] ) ) : 'publish';
				$page_edit_url = admin_url( 'post.php?post=' . $page_id . '&action=edit' );
				$page_view_url = get_permalink( $page_id );
				$page_title    = get_the_title( $page_id );
				?>

				<div class="smartforms-page-created-info">
					<p class="smartforms-page-notice">
						<span class="dashicons dashicons-admin-page"></span>
						<strong><?php echo esc_html__( 'Page Created:', 'raztech-form-architect' ); ?></strong>
						"<?php echo esc_html( $page_title ); ?>"
						<?php if ( $page_status === 'draft' ) : ?>
							<span class="smartforms-draft-badge"><?php echo esc_html__( '(Draft)', 'raztech-form-architect' ); ?></span>
						<?php endif; ?>
					</p>

					<p class="smartforms-page-actions">
						<?php if ( $page_status === 'publish' ) : ?>
							<a href="<?php echo esc_url( $page_view_url ); ?>" class="button button-secondary" target="_blank">
								<span class="dashicons dashicons-external"></span>
								<?php echo esc_html__( 'View Live Page', 'raztech-form-architect' ); ?>
							</a>
						<?php endif; ?>

						<a href="<?php echo esc_url( $page_edit_url ); ?>" class="button button-secondary" target="_blank">
							<span class="dashicons dashicons-edit"></span>
							<?php echo esc_html__( 'Edit Page', 'raztech-form-architect' ); ?>
						</a>

						<?php if ( $page_status === 'draft' ) : ?>
							<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $page_id . '&action=edit' ) ); ?>" class="button button-primary" target="_blank">
								<?php echo esc_html__( 'Publish Page', 'raztech-form-architect' ); ?>
							</a>
						<?php endif; ?>
					</p>

					<p class="smartforms-help-text">
						<?php if ( $page_status === 'publish' ) : ?>
							<?php echo esc_html__( 'Your form is now live and accessible to visitors!', 'raztech-form-architect' ); ?>
						<?php else : ?>
							<?php echo esc_html__( 'Your page is saved as a draft. Edit and publish it when you\'re ready!', 'raztech-form-architect' ); ?>
						<?php endif; ?>
					</p>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html__( 'Form updated successfully!', 'raztech-form-architect' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="" id="smartforms-builder-form">
		<?php wp_nonce_field( 'raztaifo_save_form', 'raztaifo_form_nonce' ); ?>

		<div class="smartforms-builder-grid">
			<div class="smartforms-builder-main">
				<div class="smartforms-card">
					<h2><?php echo esc_html__( 'Form Details', 'raztech-form-architect' ); ?></h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="form_name"><?php echo esc_html__( 'Form Name', 'raztech-form-architect' ); ?> <span class="required">*</span></label>
							</th>
							<td>
								<input type="text" id="form_name" name="form_name" value="<?php echo esc_attr( $form_name ); ?>" class="regular-text" required />
								<p class="description"><?php echo esc_html__( 'Give your form a descriptive name.', 'raztech-form-architect' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="form_description"><?php echo esc_html__( 'Description', 'raztech-form-architect' ); ?></label>
							</th>
							<td>
								<textarea id="form_description" name="form_description" rows="3" class="large-text"><?php echo esc_textarea( $form_description ); ?></textarea>
								<p class="description"><?php echo esc_html__( 'Optional description shown above the form.', 'raztech-form-architect' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<div class="smartforms-card">
					<h2><?php echo esc_html__( 'Form Fields', 'raztech-form-architect' ); ?></h2>

					<div class="smartforms-field-controls">
						<button type="button" class="button" id="smartforms-add-text-field">
							<?php echo esc_html__( '+ Text Field', 'raztech-form-architect' ); ?>
						</button>
						<button type="button" class="button" id="smartforms-add-email-field">
							<?php echo esc_html__( '+ Email Field', 'raztech-form-architect' ); ?>
						</button>
						<button type="button" class="button" id="smartforms-add-textarea-field">
							<?php echo esc_html__( '+ Textarea', 'raztech-form-architect' ); ?>
						</button>
						<button type="button" class="button" id="smartforms-add-tel-field">
							<?php echo esc_html__( '+ Phone Field', 'raztech-form-architect' ); ?>
						</button>
						<button type="button" class="button" id="smartforms-add-select-field">
							<?php echo esc_html__( '+ Dropdown', 'raztech-form-architect' ); ?>
						</button>

						<?php if ( ! $form_id ) : ?>
							<!-- PHASE 2: AI Quick Access -->
							<div class="smartforms-field-controls-divider">
								<span><?php echo esc_html__( 'or', 'raztech-form-architect' ); ?></span>
							</div>
							<button type="button" class="button button-secondary" id="smartforms-open-ai-modal-alt">
								✨ <?php echo esc_html__( 'Generate with AI', 'raztech-form-architect' ); ?>
							</button>
						<?php endif; ?>
					</div>

					<div id="smartforms-fields-container" class="smartforms-fields-container">
						<?php if ( ! empty( $form_fields ) ) : ?>
							<?php foreach ( $form_fields as $index => $field ) : ?>
								<?php include RAZTAIFO_PATH . 'admin/partials/field-template.php'; ?>
							<?php endforeach; ?>
						<?php else : ?>
							<p class="smartforms-no-fields"><?php echo esc_html__( 'No fields added yet. Click a button above to add your first field.', 'raztech-form-architect' ); ?></p>
						<?php endif; ?>
					</div>

					<input type="hidden" id="form_fields" name="form_fields" value="<?php echo esc_attr( wp_json_encode( $form_fields ) ); ?>" />
				</div>

				<div class="smartforms-card">
					<h2><?php echo esc_html__( 'Form Settings', 'raztech-form-architect' ); ?></h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="submit_button_text"><?php echo esc_html__( 'Submit Button Text', 'raztech-form-architect' ); ?></label>
							</th>
							<td>
								<input type="text" id="submit_button_text" name="submit_button_text" value="<?php echo esc_attr( $submit_text ); ?>" class="regular-text" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="success_message"><?php echo esc_html__( 'Success Message', 'raztech-form-architect' ); ?></label>
							</th>
							<td>
								<input type="text" id="success_message" name="success_message" value="<?php echo esc_attr( $success_message ); ?>" class="large-text" />
								<p class="description"><?php echo esc_html__( 'Message displayed after successful submission.', 'raztech-form-architect' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="conversational_mode"><?php echo esc_html__( 'Conversational Mode', 'raztech-form-architect' ); ?></label>
							</th>
							<td>
								<label>
									<input
										type="checkbox"
										id="conversational_mode"
										name="conversational_mode"
										value="1"
										<?php checked( ! empty( $form->conversational_mode ), true ); ?>
									/>
									<?php echo esc_html__( 'Enable conversational chat-style form', 'raztech-form-architect' ); ?>
								</label>
								<p class="description">
									<?php echo esc_html__( 'Present form as a friendly chat conversation instead of traditional fields. Great for mobile and increases completion rates by up to 40%.', 'raztech-form-architect' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="smartforms-builder-sidebar">
				<div class="smartforms-card">
					<h3><?php echo esc_html__( 'Publish', 'raztech-form-architect' ); ?></h3>

					<div class="smartforms-publish-actions">
						<input type="submit" name="raztaifo_save_form" class="button button-primary button-large" value="<?php echo $form_id ? esc_attr__( 'Update Form', 'raztech-form-architect' ) : esc_attr__( 'Create Form', 'raztech-form-architect' ); ?>" />

						<?php if ( $form_id ) : ?>
							<p class="smartforms-form-id">
								<?php
								/* translators: %d: Form ID */
								echo esc_html( sprintf( __( 'Form ID: %d', 'raztech-form-architect' ), $form_id ) );
								?>
							</p>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( ! $form_id ) : ?>
					<!-- Page Creation Card (NEW FORMS ONLY) -->
					<div class="smartforms-card smartforms-page-creation-card">
						<h3>
							<?php echo esc_html__( '🚀 Automatic Page Creation', 'raztech-form-architect' ); ?>
							<span class="smartforms-badge-new"><?php echo esc_html__( 'NEW!', 'raztech-form-architect' ); ?></span>
						</h3>

						<p class="smartforms-description">
							<?php echo esc_html__( 'Save time! Automatically create a WordPress page with your form embedded and ready to use.', 'raztech-form-architect' ); ?>
						</p>

						<div class="smartforms-page-creation-benefits">
							<ul class="smartforms-benefits-list">
								<li><?php echo esc_html__( '✓ Professional intro text generated', 'raztech-form-architect' ); ?></li>
								<li><?php echo esc_html__( '✓ Form shortcode embedded automatically', 'raztech-form-architect' ); ?></li>
								<li><?php echo esc_html__( '✓ SEO-friendly page structure', 'raztech-form-architect' ); ?></li>
								<li><?php echo esc_html__( '✓ Ready to publish or save as draft', 'raztech-form-architect' ); ?></li>
							</ul>
						</div>

						<div class="smartforms-page-creation-options">
							<label class="smartforms-checkbox-label">
								<input
									type="checkbox"
									id="smartforms-create-page"
									name="create_page"
									value="1"
									checked
								/>
								<strong><?php echo esc_html__( 'Create page automatically when saving this form', 'raztech-form-architect' ); ?></strong>
							</label>

							<div class="smartforms-page-status-options" id="smartforms-page-status-container">
								<p class="smartforms-label"><?php echo esc_html__( 'Page Status:', 'raztech-form-architect' ); ?></p>
								<label class="smartforms-radio-label">
									<input
										type="radio"
										name="page_status"
										value="publish"
										checked
									/>
									<?php echo esc_html__( 'Publish immediately (page goes live)', 'raztech-form-architect' ); ?>
								</label>
								<label class="smartforms-radio-label">
									<input
										type="radio"
										name="page_status"
										value="draft"
									/>
									<?php echo esc_html__( 'Save as draft (review before publishing)', 'raztech-form-architect' ); ?>
								</label>
							</div>

							<div class="smartforms-page-name-field" id="smartforms-page-name-container">
								<label for="smartforms-page-title">
									<strong><?php echo esc_html__( 'Page Title:', 'raztech-form-architect' ); ?></strong>
									<span class="smartforms-help-text"><?php echo esc_html__( '(Optional - defaults to form name)', 'raztech-form-architect' ); ?></span>
								</label>
								<input
									type="text"
									id="smartforms-page-title"
									name="page_title"
									class="regular-text"
									placeholder="<?php echo esc_attr__( 'Leave empty to use form name', 'raztech-form-architect' ); ?>"
								/>
							</div>
						</div>

						<div class="smartforms-page-preview" id="smartforms-page-preview-container" style="display:none;">
							<p class="smartforms-preview-label">
								<strong><?php echo esc_html__( '📄 Page to be created:', 'raztech-form-architect' ); ?></strong>
							</p>
							<div class="smartforms-preview-content">
								<span class="smartforms-preview-title" id="smartforms-preview-title"></span>
								<span class="smartforms-preview-status" id="smartforms-preview-status"></span>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( $form_id ) : ?>
					<div class="smartforms-card">
						<h3><?php echo esc_html__( 'Shortcode', 'raztech-form-architect' ); ?></h3>
						<p><?php echo esc_html__( 'Use this shortcode to display your form:', 'raztech-form-architect' ); ?></p>
						<input type="text" readonly value='[smartform id="<?php echo esc_attr( $form_id ); ?>"]' class="regular-text smartforms-shortcode-input" onclick="this.select();" />
						<button type="button" class="button button-secondary smartforms-copy-btn" data-shortcode='[smartform id="<?php echo esc_attr( $form_id ); ?>"]'>
							<?php echo esc_html__( 'Copy Shortcode', 'raztech-form-architect' ); ?>
						</button>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</form>

	<?php
	// PHASE 2: Include AI Form Generator Modal
	if ( ! $form_id ) {
		include RAZTAIFO_PATH . 'admin/partials/ai-form-generator-modal.php';
	}
	?>
</div>