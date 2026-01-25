/**
 * SmartForms AI - Validation JavaScript
 *
 * @package    SmartFormsAI
 * @subpackage SmartFormsAI/public/js
 */

(function($) {
	'use strict';

	/**
	 * Validation rules for different field types
	 */
	const validationRules = {
		email: {
			pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
			message: 'Please enter a valid email address.'
		},
		tel: {
			pattern: /^[\d\s\-\+\(\)]+$/,
			message: 'Please enter a valid phone number.'
		},
		url: {
			pattern: /^https?:\/\/.+/,
			message: 'Please enter a valid URL (starting with http:// or https://).'
		},
		number: {
			pattern: /^-?\d*\.?\d+$/,
			message: 'Please enter a valid number.'
		}
	};

	/**
	 * Validate a single field
	 */
	function validateField($field) {
		const fieldType = $field.attr('type') || 'text';
		const validation = $field.data('validation') || fieldType;
		const value = $field.val();
		const $fieldWrapper = $field.closest('.rt_fa-field');

		// Clear previous errors
		$fieldWrapper.removeClass('has-error');
		$fieldWrapper.find('.rt_fa-field-error').remove();

		// Check if field is required
		if ($field.prop('required') && !value) {
			const fieldLabel = $fieldWrapper.find('.rt_fa-label').text().replace('*', '').trim();
			showFieldError($fieldWrapper, fieldLabel + ' is required.');
			return false;
		}

		// Skip validation if field is empty and not required
		if (!value) {
			return true;
		}

		// Validate based on validation rule
		if (validationRules[validation]) {
			const rule = validationRules[validation];
			if (!rule.pattern.test(value)) {
				showFieldError($fieldWrapper, rule.message);
				return false;
			}
		}

		return true;
	}

	/**
	 * Show field error
	 */
	function showFieldError($fieldWrapper, message) {
		$fieldWrapper.addClass('has-error');
		if (!$fieldWrapper.find('.rt_fa-field-error').length) {
			$fieldWrapper.append('<span class="rt_fa-field-error">' + message + '</span>');
		}
	}

	/**
	 * Initialize validation
	 */
	$(document).ready(function() {
		// Validate on blur
		$(document).on('blur', '.rt_fa-input, .rt_fa-textarea', function() {
			validateField($(this));
		});

		// Clear error on focus
		$(document).on('focus', '.rt_fa-input, .rt_fa-textarea', function() {
			const $fieldWrapper = $(this).closest('.rt_fa-field');
			$fieldWrapper.removeClass('has-error');
			$fieldWrapper.find('.rt_fa-field-error').remove();
		});

		// Validate checkbox groups
		$(document).on('change', '.rt_fa-checkbox', function() {
			const $fieldWrapper = $(this).closest('.rt_fa-field');
			const $checkboxes = $fieldWrapper.find('.rt_fa-checkbox');
			const isRequired = $checkboxes.first().prop('required');

			if (isRequired) {
				const checkedCount = $checkboxes.filter(':checked').length;
				if (checkedCount === 0) {
					const fieldLabel = $fieldWrapper.find('.rt_fa-label').text().replace('*', '').trim();
					showFieldError($fieldWrapper, fieldLabel + ' is required.');
				} else {
					$fieldWrapper.removeClass('has-error');
					$fieldWrapper.find('.rt_fa-field-error').remove();
				}
			}
		});

		// Validate radio groups
		$(document).on('change', '.rt_fa-radio', function() {
			const $fieldWrapper = $(this).closest('.rt_fa-field');
			$fieldWrapper.removeClass('has-error');
			$fieldWrapper.find('.rt_fa-field-error').remove();
		});

		// File size validation
		$(document).on('change', '.rt_fa-file', function() {
			const $field = $(this);
			const $fieldWrapper = $field.closest('.rt_fa-field');
			const maxSize = 5 * 1024 * 1024; // 5MB

			if (this.files && this.files[0]) {
				const fileSize = this.files[0].size;
				if (fileSize > maxSize) {
					showFieldError($fieldWrapper, 'File size must be less than 5MB.');
					$field.val('');
				} else {
					$fieldWrapper.removeClass('has-error');
					$fieldWrapper.find('.rt_fa-field-error').remove();
				}
			}
		});
	});

	// Export validation function for use in other scripts
	window.SmartFormsValidation = {
		validateField: validateField
	};

})(jQuery);
