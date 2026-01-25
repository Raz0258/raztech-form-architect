<?php
/**
 * Field Template for Form Builder
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$field_type        = isset( $field['type'] ) ? $field['type'] : 'text';
$field_label       = isset( $field['label'] ) ? $field['label'] : '';
$field_name        = isset( $field['name'] ) ? $field['name'] : '';
$field_placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
$field_required    = isset( $field['required'] ) && $field['required'] ? 'checked' : '';
$field_options     = isset( $field['options'] ) && is_array( $field['options'] ) ? implode( "\n", $field['options'] ) : '';
?>

<div class="smartforms-field-item" data-index="<?php echo esc_attr( $index ); ?>">
	<div class="smartforms-field-header">
		<span class="smartforms-field-drag">☰</span>
		<span class="smartforms-field-title"><?php echo esc_html( $field_label ? $field_label : ucfirst( $field_type ) . ' Field' ); ?></span>
		<span class="smartforms-field-type-badge"><?php echo esc_html( $field_type ); ?></span>
		<button type="button" class="smartforms-field-toggle">▼</button>
		<button type="button" class="smartforms-field-delete">×</button>
	</div>

	<div class="smartforms-field-body">
		<table class="form-table">
			<tr>
				<th>
					<label><?php echo esc_html__( 'Field Type', 'raztech-form-architect' ); ?></label>
				</th>
				<td>
					<select class="smartforms-field-type-select" data-field="type">
						<option value="text" <?php selected( $field_type, 'text' ); ?>><?php echo esc_html__( 'Text', 'raztech-form-architect' ); ?></option>
						<option value="email" <?php selected( $field_type, 'email' ); ?>><?php echo esc_html__( 'Email', 'raztech-form-architect' ); ?></option>
						<option value="tel" <?php selected( $field_type, 'tel' ); ?>><?php echo esc_html__( 'Phone', 'raztech-form-architect' ); ?></option>
						<option value="url" <?php selected( $field_type, 'url' ); ?>><?php echo esc_html__( 'URL', 'raztech-form-architect' ); ?></option>
						<option value="number" <?php selected( $field_type, 'number' ); ?>><?php echo esc_html__( 'Number', 'raztech-form-architect' ); ?></option>
						<option value="date" <?php selected( $field_type, 'date' ); ?>><?php echo esc_html__( 'Date', 'raztech-form-architect' ); ?></option>
						<option value="textarea" <?php selected( $field_type, 'textarea' ); ?>><?php echo esc_html__( 'Textarea', 'raztech-form-architect' ); ?></option>
						<option value="select" <?php selected( $field_type, 'select' ); ?>><?php echo esc_html__( 'Dropdown', 'raztech-form-architect' ); ?></option>
						<option value="radio" <?php selected( $field_type, 'radio' ); ?>><?php echo esc_html__( 'Radio', 'raztech-form-architect' ); ?></option>
						<option value="checkbox" <?php selected( $field_type, 'checkbox' ); ?>><?php echo esc_html__( 'Checkbox', 'raztech-form-architect' ); ?></option>
						<option value="file" <?php selected( $field_type, 'file' ); ?>><?php echo esc_html__( 'File Upload', 'raztech-form-architect' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th>
					<label><?php echo esc_html__( 'Label', 'raztech-form-architect' ); ?></label>
				</th>
				<td>
					<input type="text" class="regular-text smartforms-field-input" data-field="label" value="<?php echo esc_attr( $field_label ); ?>" />
				</td>
			</tr>
			<tr>
				<th>
					<label><?php echo esc_html__( 'Field Name', 'raztech-form-architect' ); ?></label>
				</th>
				<td>
					<input type="text" class="regular-text smartforms-field-input" data-field="name" value="<?php echo esc_attr( $field_name ); ?>" />
					<p class="description"><?php echo esc_html__( 'Unique field identifier (lowercase, no spaces).', 'raztech-form-architect' ); ?></p>
				</td>
			</tr>
			<tr>
				<th>
					<label><?php echo esc_html__( 'Placeholder', 'raztech-form-architect' ); ?></label>
				</th>
				<td>
					<input type="text" class="regular-text smartforms-field-input" data-field="placeholder" value="<?php echo esc_attr( $field_placeholder ); ?>" />
				</td>
			</tr>
			<tr class="smartforms-options-row" style="<?php echo in_array( $field_type, array( 'select', 'radio', 'checkbox' ) ) ? '' : 'display:none;'; ?>">
				<th>
					<label><?php echo esc_html__( 'Options', 'raztech-form-architect' ); ?></label>
				</th>
				<td>
					<textarea class="large-text smartforms-field-textarea" data-field="options" rows="5"><?php echo esc_textarea( $field_options ); ?></textarea>
					<p class="description"><?php echo esc_html__( 'One option per line.', 'raztech-form-architect' ); ?></p>
				</td>
			</tr>
			<tr>
				<th>
					<label><?php echo esc_html__( 'Required', 'raztech-form-architect' ); ?></label>
				</th>
				<td>
					<label>
						<input type="checkbox" class="smartforms-field-checkbox" data-field="required" <?php echo esc_attr( $field_required ); ?> />
						<?php echo esc_html__( 'Make this field required', 'raztech-form-architect' ); ?>
					</label>
				</td>
			</tr>
		</table>
	</div>
</div>
