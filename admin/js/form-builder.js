/**
 * SmartForms AI - Form Builder JavaScript
 *
 * @package    SmartFormsAI
 * @subpackage SmartFormsAI/admin/js
 */

(function($) {
	'use strict';

	let fieldIndex = 0;

	$(document).ready(function() {
		// Initialize field index based on existing fields
		fieldIndex = $('#rt_fa-fields-container .rt_fa-field-item').length;

		// Add field buttons
		$('#rt_fa-add-text-field').on('click', function() {
			addField('text');
		});

		$('#rt_fa-add-email-field').on('click', function() {
			addField('email');
		});

		$('#rt_fa-add-textarea-field').on('click', function() {
			addField('textarea');
		});

		$('#rt_fa-add-tel-field').on('click', function() {
			addField('tel');
		});

		$('#rt_fa-add-select-field').on('click', function() {
			addField('select');
		});

		// Toggle field body
		$(document).on('click', '.rt_fa-field-header', function(e) {
			if (!$(e.target).hasClass('rt_fa-field-delete')) {
				$(this).closest('.rt_fa-field-item').toggleClass('open');
			}
		});

		// Delete field
		$(document).on('click', '.rt_fa-field-delete', function(e) {
			e.stopPropagation();
			if (confirm('Are you sure you want to delete this field?')) {
				$(this).closest('.rt_fa-field-item').fadeOut(300, function() {
					$(this).remove();
					updateNoFieldsMessage();
					updateFormFieldsJSON();
				});
			}
		});

		// Update field data on input change
		$(document).on('change keyup', '.rt_fa-field-input, .rt_fa-field-select, .rt_fa-field-textarea, .rt_fa-field-checkbox', function() {
			const $fieldItem = $(this).closest('.rt_fa-field-item');
			updateFieldTitle($fieldItem);
			updateFormFieldsJSON();
		});

		// Show/hide options field based on field type
		$(document).on('change', '.rt_fa-field-type-select', function() {
			const $fieldItem = $(this).closest('.rt_fa-field-item');
			const fieldType = $(this).val();
			const $optionsRow = $fieldItem.find('.rt_fa-options-row');

			if (['select', 'radio', 'checkbox'].includes(fieldType)) {
				$optionsRow.show();
			} else {
				$optionsRow.hide();
			}

			// Update type badge
			$fieldItem.find('.rt_fa-field-type-badge').text(fieldType);
		});

		// Form submission
		$('#rt_fa-builder-form').on('submit', function() {
			updateFormFieldsJSON();
		});

		// Copy shortcode button
		$('.rt_fa-copy-btn, .rt_fa-copy-shortcode').on('click', function() {
			const shortcode = $(this).data('shortcode');
			copyToClipboard(shortcode);
			alert('Shortcode copied to clipboard!');
		});

		// Initialize existing fields
		$('.rt_fa-field-item').each(function() {
			updateFieldTitle($(this));
		});

		// Make fields sortable (drag and drop)
		if (typeof $.fn.sortable !== 'undefined') {
			$('#rt_fa-fields-container').sortable({
				handle: '.rt_fa-field-drag',
				placeholder: 'rt_fa-field-placeholder',
				update: function() {
					updateFormFieldsJSON();
				}
			});
		}

		// ================================================================
		// PAGE CREATION INTERACTIONS
		// ================================================================

		// Initialize page creation interactions
		initPageCreationUI();
	});

	/**
	 * Initialize page creation UI interactions
	 */
	function initPageCreationUI() {
		// === DEFENSIVE CHECK: Exit if not on form builder page ===
		const $createPageCheckbox = $('#rt_fa-create-page');
		if ($createPageCheckbox.length === 0) {
			return; // Page creation UI not present, skip initialization
		}

		// === Get DOM elements ===
		const $pageStatusContainer = $('#rt_fa-page-status-container');
		const $pageNameContainer = $('#rt_fa-page-name-container');
		const $pagePreviewContainer = $('#rt_fa-page-preview-container');
		const $formNameInput = $('#form_name');
		const $pageTitleInput = $('#rt_fa-page-title');
		const $previewTitle = $('#rt_fa-preview-title');
		const $previewStatus = $('#rt_fa-preview-status');

		// === Event: Toggle page options when checkbox changes ===
		$createPageCheckbox.on('change', function() {
			if ($(this).is(':checked')) {
				$pageStatusContainer.slideDown(300);
				$pageNameContainer.slideDown(300);
				updatePreview();
			} else {
				$pageStatusContainer.slideUp(300);
				$pageNameContainer.slideUp(300);
				$pagePreviewContainer.slideUp(300);
			}
		});

		// === Event: Update preview when form name changes ===
		$formNameInput.on('input', function() {
			updatePreview();
		});

		// === Event: Update preview when custom page title changes ===
		$pageTitleInput.on('input', function() {
			updatePreview();
		});

		// === Event: Update preview when page status changes ===
		$('input[name="page_status"]').on('change', function() {
			updatePreview();
		});

		// === Function: Update preview with null safety ===
		function updatePreview() {
			// Don't show preview if checkbox unchecked
			if (!$createPageCheckbox.is(':checked')) {
				$pagePreviewContainer.slideUp(300);
				return;
			}

			// Get values safely with fallbacks
			const formName = ($formNameInput.val() || '').trim();
			const customPageTitle = ($pageTitleInput.val() || '').trim();
			const pageStatus = $('input[name="page_status"]:checked').val() || 'publish';

			// Don't show preview if no form name
			if (!formName) {
				$pagePreviewContainer.slideUp(300);
				return;
			}

			// Determine display title
			const displayTitle = customPageTitle || formName;

			// Update preview text
			$previewTitle.text(displayTitle);

			// Update status text
			if (pageStatus === 'publish') {
				$previewStatus.text('(will be published immediately)');
			} else {
				$previewStatus.text('(will be saved as draft)');
			}

			// Show preview
			$pagePreviewContainer.slideDown(300);
		}

		// === Initialize preview if form name already exists ===
		const initialFormName = ($formNameInput.val() || '').trim();
		if (initialFormName) {
			updatePreview();
		}
	}

	/**
	 * Add a new field to the form
	 */
	function addField(type) {
		const fieldData = {
			type: type,
			label: getDefaultLabel(type),
			name: 'field_' + fieldIndex,
			placeholder: '',
			required: false,
			options: type === 'select' || type === 'radio' || type === 'checkbox' ? ['Option 1', 'Option 2', 'Option 3'] : []
		};

		const fieldHTML = createFieldHTML(fieldData, fieldIndex);
		$('#rt_fa-fields-container').append(fieldHTML);
		$('.rt_fa-no-fields').remove();
		fieldIndex++;
		updateFormFieldsJSON();

		// Scroll to the new field
		const $newField = $('#rt_fa-fields-container .rt_fa-field-item:last');
		$newField.addClass('open');
		$('html, body').animate({
			scrollTop: $newField.offset().top - 100
		}, 500);
	}

	/**
	 * Create HTML for a field
	 */
	function createFieldHTML(field, index) {
		const optionsDisplay = ['select', 'radio', 'checkbox'].includes(field.type) ? '' : 'display:none;';
		const optionsValue = Array.isArray(field.options) ? field.options.join('\n') : '';
		const requiredChecked = field.required ? 'checked' : '';

		return `
			<div class="rt_fa-field-item" data-index="${index}">
				<div class="rt_fa-field-header">
					<span class="rt_fa-field-drag">☰</span>
					<span class="rt_fa-field-title">${field.label || field.type.charAt(0).toUpperCase() + field.type.slice(1) + ' Field'}</span>
					<span class="rt_fa-field-type-badge">${field.type}</span>
					<button type="button" class="rt_fa-field-toggle">▼</button>
					<button type="button" class="rt_fa-field-delete">×</button>
				</div>
				<div class="rt_fa-field-body">
					<table class="form-table">
						<tr>
							<th><label>Field Type</label></th>
							<td>
								<select class="rt_fa-field-type-select" data-field="type">
									<option value="text" ${field.type === 'text' ? 'selected' : ''}>Text</option>
									<option value="email" ${field.type === 'email' ? 'selected' : ''}>Email</option>
									<option value="tel" ${field.type === 'tel' ? 'selected' : ''}>Phone</option>
									<option value="url" ${field.type === 'url' ? 'selected' : ''}>URL</option>
									<option value="number" ${field.type === 'number' ? 'selected' : ''}>Number</option>
									<option value="date" ${field.type === 'date' ? 'selected' : ''}>Date</option>
									<option value="textarea" ${field.type === 'textarea' ? 'selected' : ''}>Textarea</option>
									<option value="select" ${field.type === 'select' ? 'selected' : ''}>Dropdown</option>
									<option value="radio" ${field.type === 'radio' ? 'selected' : ''}>Radio</option>
									<option value="checkbox" ${field.type === 'checkbox' ? 'selected' : ''}>Checkbox</option>
									<option value="file" ${field.type === 'file' ? 'selected' : ''}>File Upload</option>
								</select>
							</td>
						</tr>
						<tr>
							<th><label>Label</label></th>
							<td>
								<input type="text" class="regular-text rt_fa-field-input" data-field="label" value="${field.label}" />
							</td>
						</tr>
						<tr>
							<th><label>Field Name</label></th>
							<td>
								<input type="text" class="regular-text rt_fa-field-input" data-field="name" value="${field.name}" />
								<p class="description">Unique field identifier (lowercase, no spaces).</p>
							</td>
						</tr>
						<tr>
							<th><label>Placeholder</label></th>
							<td>
								<input type="text" class="regular-text rt_fa-field-input" data-field="placeholder" value="${field.placeholder}" />
							</td>
						</tr>
						<tr class="rt_fa-options-row" style="${optionsDisplay}">
							<th><label>Options</label></th>
							<td>
								<textarea class="large-text rt_fa-field-textarea" data-field="options" rows="5">${optionsValue}</textarea>
								<p class="description">One option per line.</p>
							</td>
						</tr>
						<tr>
							<th><label>Required</label></th>
							<td>
								<label>
									<input type="checkbox" class="rt_fa-field-checkbox" data-field="required" ${requiredChecked} />
									Make this field required
								</label>
							</td>
						</tr>
					</table>
				</div>
			</div>
		`;
	}

	/**
	 * Update the field title in the header
	 */
	function updateFieldTitle($fieldItem) {
		const label = $fieldItem.find('[data-field="label"]').val();
		const type = $fieldItem.find('[data-field="type"]').val();
		const title = label || (type.charAt(0).toUpperCase() + type.slice(1) + ' Field');
		$fieldItem.find('.rt_fa-field-title').text(title);
	}

	/**
	 * Update the hidden JSON field with all form fields
	 */
	function updateFormFieldsJSON() {
		const fields = [];

		$('#rt_fa-fields-container .rt_fa-field-item').each(function() {
			const $field = $(this);
			const fieldData = {
				type: $field.find('[data-field="type"]').val(),
				label: $field.find('[data-field="label"]').val(),
				name: $field.find('[data-field="name"]').val(),
				placeholder: $field.find('[data-field="placeholder"]').val(),
				required: $field.find('[data-field="required"]').is(':checked')
			};

			// Add options for select, radio, checkbox
			if (['select', 'radio', 'checkbox'].includes(fieldData.type)) {
				const optionsText = $field.find('[data-field="options"]').val();
				fieldData.options = optionsText.split('\n').filter(opt => opt.trim() !== '');
			}

			fields.push(fieldData);
		});

		$('#form_fields').val(JSON.stringify(fields));
	}

	/**
	 * Update "no fields" message
	 */
	function updateNoFieldsMessage() {
		if ($('#rt_fa-fields-container .rt_fa-field-item').length === 0) {
			$('#rt_fa-fields-container').html('<p class="rt_fa-no-fields">No fields added yet. Click a button above to add your first field.</p>');
		}
	}

	/**
	 * Get default label for field type
	 */
	function getDefaultLabel(type) {
		const labels = {
			'text': 'Text Field',
			'email': 'Email Address',
			'tel': 'Phone Number',
			'url': 'Website URL',
			'number': 'Number',
			'date': 'Date',
			'textarea': 'Message',
			'select': 'Select Option',
			'radio': 'Choose One',
			'checkbox': 'Check All That Apply',
			'file': 'Upload File'
		};
		return labels[type] || 'Field';
	}

	/**
	 * Copy text to clipboard
	 */
	function copyToClipboard(text) {
		const $temp = $('<input>');
		$('body').append($temp);
		$temp.val(text).select();
		document.execCommand('copy');
		$temp.remove();
	}

})(jQuery);
