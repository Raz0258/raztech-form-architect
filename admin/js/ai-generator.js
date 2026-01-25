/**
 * SmartForms AI - AI Generator JavaScript
 *
 * @package    SmartFormsAI
 * @subpackage SmartFormsAI/admin/js
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Open AI modal
		$('#rt_fa-open-ai-modal, #rt_fa-open-ai-modal-alt').on('click', function() {
			$('#raztech-form-architect-modal').fadeIn(300);
		});

		// Close AI modal
		$('#raztech-form-architect-modal-close, #raztech-form-architect-cancel').on('click', function() {
			closeAIModal();
		});

		// Close modal on outside click
		$(window).on('click', function(e) {
			if ($(e.target).is('#raztech-form-architect-modal')) {
				closeAIModal();
			}
		});

		// Close modal on ESC key
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape' && $('#raztech-form-architect-modal').is(':visible')) {
				closeAIModal();
			}
		});

		// Example prompts
		$('.raztech-form-architect-example-btn').on('click', function() {
			const exampleType = $(this).data('example');
			if (rt_faAIExamples && rt_faAIExamples[exampleType]) {
				$('#raztech-form-architect-description').val(rt_faAIExamples[exampleType]);
				// Auto-focus the textarea
				$('#raztech-form-architect-description').focus();
			}
		});

		// Generate form with AI
		$('#raztech-form-architect-generate').on('click', function() {
			generateFormWithAI();
		});

		// Allow Enter key to submit (but not Shift+Enter)
		$('#raztech-form-architect-description').on('keydown', function(e) {
			if (e.key === 'Enter' && !e.shiftKey) {
				e.preventDefault();
				generateFormWithAI();
			}
		});

		// Toggle AI page creation options
		$('#raztech-form-architect-create-page').on('change', function() {
			if ($(this).is(':checked')) {
				$('#raztech-form-architect-page-options').slideDown(300);
			} else {
				$('#raztech-form-architect-page-options').slideUp(300);
			}
		});
	});

	/**
	 * Close AI modal
	 */
	function closeAIModal() {
		$('#raztech-form-architect-modal').fadeOut(300);
		// Clear error messages
		$('#raztech-form-architect-error').hide().html('');
	}

	/**
	 * Generate form with AI
	 */
	function generateFormWithAI() {
		const description = $('#raztech-form-architect-description').val().trim();

		// Validate input
		if (!description) {
			showAIError('Please describe the form you want to create.');
			$('#raztech-form-architect-description').focus();
			return;
		}

		if (description.length < 10) {
			showAIError('Please provide a more detailed description (at least 10 characters).');
			$('#raztech-form-architect-description').focus();
			return;
		}

		// Get options
		const options = {
			complexity: $('#raztech-form-architect-complexity').val(),
			purpose: $('#raztech-form-architect-purpose').val().trim(),
			audience: $('#raztech-form-architect-audience').val().trim(),
			create_page: $('#raztech-form-architect-create-page').is(':checked'),
			page_status: $('input[name="ai_page_status"]:checked').val()
		};

		// Show loading
		$('#raztech-form-architect-error').hide();
		$('#raztech-form-architect-loading').show();
		$('#raztech-form-architect-generate').prop('disabled', true);

		// Make AJAX request
		$.ajax({
			url: rt_faAjax.ajax_url,
			type: 'POST',
			data: {
				action: 'rt_fa_generate_ai_form',
				nonce: rt_faAjax.nonce,
				description: description,
				options: options
			},
			success: function(response) {
				if (response.success && response.data.form_structure) {
					// Populate form with AI-generated data
					populateFormBuilder(response.data.form_structure);

					// Populate page creation settings
					if (response.data.create_page) {
						$('#rt_fa-create-page').prop('checked', true);
						$('input[name="page_status"][value="' + response.data.page_status + '"]').prop('checked', true);
						$('#rt_fa-page-status-container').show();
						$('#rt_fa-page-name-container').show();
					}

					// Close modal
					closeAIModal();

					// Show success message
					showSuccessNotice('AI form generated successfully! Review and edit the fields below, then save your form.');

					// Scroll to form fields
					$('html, body').animate({
						scrollTop: $('#rt_fa-fields-container').offset().top - 100
					}, 500);
				} else {
					showAIError(response.data.message || 'Failed to generate form. Please try again.');
				}
			},
			error: function(xhr, status, error) {
				console.error('AI Generation Error:', error);
				showAIError('An error occurred while connecting to the AI service. Please try again.');
			},
			complete: function() {
				$('#raztech-form-architect-loading').hide();
				$('#raztech-form-architect-generate').prop('disabled', false);
			}
		});
	}

	/**
	 * Show AI error message
	 */
	function showAIError(message) {
		$('#raztech-form-architect-error').html('<strong>Error:</strong> ' + message).fadeIn(300);
	}

	/**
	 * Show success notice
	 */
	function showSuccessNotice(message) {
		const $notice = $('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>');
		$('.wrap.rt_fa-form-builder h1').after($notice);

		// Auto-dismiss after 5 seconds
		setTimeout(function() {
			$notice.fadeOut(300, function() {
				$(this).remove();
			});
		}, 5000);
	}

	/**
	 * Populate form builder with AI-generated structure
	 */
	function populateFormBuilder(formStructure) {
		// Set form name and description
		if (formStructure.form_name) {
			$('#form_name').val(formStructure.form_name);
		}

		if (formStructure.form_description) {
			$('#form_description').val(formStructure.form_description);
		}

		// Set settings
		if (formStructure.settings) {
			if (formStructure.settings.submit_button_text) {
				$('#submit_button_text').val(formStructure.settings.submit_button_text);
			}
			if (formStructure.settings.success_message) {
				$('#success_message').val(formStructure.settings.success_message);
			}
		}

		// Clear existing fields
		$('#rt_fa-fields-container').html('');

		// Add AI-generated fields
		if (formStructure.form_fields && formStructure.form_fields.length > 0) {
			// Reset field index
			if (typeof window.rt_faFieldIndex !== 'undefined') {
				window.rt_faFieldIndex = 0;
			}

			formStructure.form_fields.forEach(function(field, index) {
				addFieldToBuilder(field, index);
			});

			// Update the hidden JSON field
			if (typeof window.rt_faUpdateFormFieldsJSON === 'function') {
				window.rt_faUpdateFormFieldsJSON();
			}
		}
	}

	/**
	 * Add a field to the form builder
	 */
	function addFieldToBuilder(field, index) {
		const optionsDisplay = ['select', 'radio', 'checkbox'].includes(field.type) ? '' : 'display:none;';
		const optionsValue = Array.isArray(field.options) ? field.options.join('\n') : '';
		const requiredChecked = field.required ? 'checked' : '';
		// SECURITY FIX: Escape field label to prevent XSS attacks
		const fieldLabel = escapeHtml(field.label || 'Field');

		const fieldHTML = `
			<div class="rt_fa-field-item open" data-index="${index}">
				<div class="rt_fa-field-header">
					<span class="rt_fa-field-drag">☰</span>
					<span class="rt_fa-field-title">${fieldLabel}</span>
					<span class="rt_fa-field-type-badge">${escapeHtml(field.type)}</span>
					<button type="button" class="rt_fa-field-toggle">▼</button>
					<button type="button" class="rt_fa-field-delete">×</button>
				</div>
				<div class="rt_fa-field-body" style="display:block;">
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
								<input type="text" class="regular-text rt_fa-field-input" data-field="label" value="${escapeHtml(field.label || '')}" />
							</td>
						</tr>
						<tr>
							<th><label>Field Name</label></th>
							<td>
								<input type="text" class="regular-text rt_fa-field-input" data-field="name" value="${escapeHtml(field.name || '')}" />
								<p class="description">Unique field identifier (lowercase, no spaces).</p>
							</td>
						</tr>
						<tr>
							<th><label>Placeholder</label></th>
							<td>
								<input type="text" class="regular-text rt_fa-field-input" data-field="placeholder" value="${escapeHtml(field.placeholder || '')}" />
							</td>
						</tr>
						<tr class="rt_fa-options-row" style="${optionsDisplay}">
							<th><label>Options</label></th>
							<td>
								<textarea class="large-text rt_fa-field-textarea" data-field="options" rows="5">${escapeHtml(optionsValue)}</textarea>
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

		$('#rt_fa-fields-container').append(fieldHTML);

		// Update field index
		if (typeof window.rt_faFieldIndex !== 'undefined') {
			window.rt_faFieldIndex++;
		}
	}

	/**
	 * Escape HTML to prevent XSS
	 */
	function escapeHtml(text) {
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.replace(/[&<>"']/g, function(m) { return map[m]; });
	}

	// Expose functions for form-builder.js to use
	window.rt_faPopulateFormBuilder = populateFormBuilder;

})(jQuery);
