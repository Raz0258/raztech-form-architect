<?php
/**
 * All Forms Page
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get all forms
$forms = RAZTAIFO_Form_Builder::get_forms();
?>

<div class="wrap smartforms-all-forms">
	<h1 class="wp-heading-inline"><?php echo esc_html__( 'All Forms', 'raztech-form-architect' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-new-form' ) ); ?>" class="page-title-action">
		<?php echo esc_html__( 'Add New', 'raztech-form-architect' ); ?>
	</a>

	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
				<strong><?php echo esc_html__( '✓ Form deleted successfully', 'raztech-form-architect' ); ?></strong>
			</p>
			<?php if ( isset( $_GET['pages_deleted'] ) && intval( $_GET['pages_deleted'] ) > 0 ) : ?>
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: Number of pages deleted */
							_n(
								'%d page was also moved to trash.',
								'%d pages were also moved to trash.',
								intval( $_GET['pages_deleted'] ),
								'raztech-form-architect'
							),
							intval( $_GET['pages_deleted'] )
						)
					);
					?>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_status=trash&post_type=page' ) ); ?>">
						<?php echo esc_html__( 'View trash', 'raztech-form-architect' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $forms ) ) : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width: 50px;"><?php echo esc_html__( 'ID', 'raztech-form-architect' ); ?></th>
					<th><?php echo esc_html__( 'Form Name', 'raztech-form-architect' ); ?></th>
					<th><?php echo esc_html__( 'Shortcode', 'raztech-form-architect' ); ?></th>
					<th><?php echo esc_html__( 'Submissions', 'raztech-form-architect' ); ?></th>
					<th><?php echo esc_html__( 'Created', 'raztech-form-architect' ); ?></th>
					<th><?php echo esc_html__( 'Actions', 'raztech-form-architect' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $forms as $form ) : ?>
					<?php
					$submissions_count = count( RAZTAIFO_Form_Builder::get_submissions( $form->id ) );
					?>
					<tr>
						<td><?php echo esc_html( $form->id ); ?></td>
						<td>
							<strong>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-new-form&form_id=' . $form->id ) ); ?>">
									<?php echo esc_html( $form->form_name ); ?>
								</a>
							</strong>
							<?php if ( ! empty( $form->form_description ) ) : ?>
								<br>
								<small><?php echo esc_html( wp_trim_words( $form->form_description, 10 ) ); ?></small>
							<?php endif; ?>
						</td>
						<td>
							<code>[smartform id="<?php echo esc_attr( $form->id ); ?>"]</code>
							<button class="button button-small smartforms-copy-shortcode" data-shortcode='[smartform id="<?php echo esc_attr( $form->id ); ?>"]'>
								<?php echo esc_html__( 'Copy', 'raztech-form-architect' ); ?>
							</button>
						</td>
						<td><?php echo esc_html( $submissions_count ); ?></td>
						<td><?php echo esc_html( human_time_diff( strtotime( $form->created_at ), current_time( 'timestamp' ) ) . ' ago' ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-new-form&form_id=' . $form->id ) ); ?>" class="button button-small">
								<?php echo esc_html__( 'Edit', 'raztech-form-architect' ); ?>
							</a>
							<a href="#"
							   class="button button-small button-link-delete smartforms-delete-form"
							   data-form-id="<?php echo esc_attr( $form->id ); ?>"
							   data-form-name="<?php echo esc_attr( $form->form_name ); ?>"
							   data-nonce="<?php echo esc_attr( wp_create_nonce( 'raztaifo_delete_form_' . $form->id ) ); ?>">
								<?php echo esc_html__( 'Delete', 'raztech-form-architect' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<div class="smartforms-empty-state">
			<h2><?php echo esc_html__( 'No forms yet', 'raztech-form-architect' ); ?></h2>
			<p><?php echo esc_html__( 'Create your first form to get started.', 'raztech-form-architect' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-new-form' ) ); ?>" class="button button-primary button-hero">
				<?php echo esc_html__( 'Create Your First Form', 'raztech-form-architect' ); ?>
			</a>
		</div>
	<?php endif; ?>

	<!-- Delete Confirmation Modal -->
	<div id="smartforms-delete-modal" class="smartforms-modal" style="display:none;">
		<div class="smartforms-modal-overlay"></div>
		<div class="smartforms-modal-content">
			<div class="smartforms-modal-header">
				<h2><?php echo esc_html__( '⚠️ Delete Form', 'raztech-form-architect' ); ?></h2>
				<button class="smartforms-modal-close">&times;</button>
			</div>

			<div class="smartforms-modal-body">
				<p class="smartforms-warning-text">
					<strong><?php echo esc_html__( 'Are you sure you want to delete this form?', 'raztech-form-architect' ); ?></strong>
				</p>

				<div class="smartforms-delete-info">
					<p><?php echo esc_html__( 'Form:', 'raztech-form-architect' ); ?> <strong id="smartforms-delete-form-name"></strong></p>
					<p class="smartforms-note"><?php echo esc_html__( 'This will permanently delete:', 'raztech-form-architect' ); ?></p>
					<ul class="smartforms-delete-list">
						<li><?php echo esc_html__( '✗ The form structure', 'raztech-form-architect' ); ?></li>
						<li><?php echo esc_html__( '✗ All submissions', 'raztech-form-architect' ); ?> (<span id="smartforms-delete-submissions-count">0</span>)</li>
					</ul>
				</div>

				<div id="smartforms-pages-section" style="display:none;">
					<hr>
					<p class="smartforms-pages-warning">
						<strong><?php echo esc_html__( '⚠️ This form is used on the following pages:', 'raztech-form-architect' ); ?></strong>
					</p>
					<div id="smartforms-pages-list" class="smartforms-pages-list"></div>

					<label class="smartforms-checkbox-label smartforms-delete-pages-option">
						<input type="checkbox" id="smartforms-delete-pages-checkbox" />
						<span>
							<strong><?php echo esc_html__( 'Also delete these pages', 'raztech-form-architect' ); ?></strong>
							<span class="smartforms-help-text"><?php echo esc_html__( '(The pages will be moved to trash)', 'raztech-form-architect' ); ?></span>
						</span>
					</label>
				</div>

				<p class="smartforms-final-warning">
					<strong><?php echo esc_html__( 'This action cannot be undone!', 'raztech-form-architect' ); ?></strong>
				</p>
			</div>

			<div class="smartforms-modal-footer">
				<button type="button" class="button button-secondary smartforms-modal-cancel">
					<?php echo esc_html__( 'Cancel', 'raztech-form-architect' ); ?>
				</button>
				<button type="button" class="button button-primary smartforms-confirm-delete" disabled>
					<span class="dashicons dashicons-trash"></span>
					<?php echo esc_html__( 'Delete Form', 'raztech-form-architect' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Loading Spinner -->
	<div id="smartforms-delete-spinner" class="smartforms-spinner" style="display:none;">
		<div class="smartforms-spinner-overlay"></div>
		<div class="smartforms-spinner-content">
			<div class="spinner is-active"></div>
			<p><?php echo esc_html__( 'Deleting form...', 'raztech-form-architect' ); ?></p>
		</div>
	</div>
</div>
