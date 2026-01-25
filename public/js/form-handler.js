/**
 * SmartForms AI - Form Handler JavaScript
 *
 * @package    SmartFormsAI
 * @subpackage SmartFormsAI/public/js
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Handle form submission
		$('.rt_fa-form').on('submit', function(e) {
			e.preventDefault();

			const $form = $(this);
			const $submitBtn = $form.find('.rt_fa-submit-button');
			const $spinner = $form.find('.rt_fa-spinner');
			const $message = $form.find('.rt_fa-message');
			const formId = $form.data('form-id');

			// Clear previous messages and errors
			$message.hide().removeClass('success error').html('');
			$form.find('.rt_fa-field').removeClass('has-error');
			$form.find('.rt_fa-field-error').remove();

			// Validate form
			let isValid = true;
			$form.find('[required]').each(function() {
				const $field = $(this);
				const $fieldWrapper = $field.closest('.rt_fa-field');
				const fieldLabel = $fieldWrapper.find('.rt_fa-label').text().replace('*', '').trim();

				if (!$field.val() || ($field.is(':checkbox') && !$field.is(':checked'))) {
					isValid = false;
					$fieldWrapper.addClass('has-error');
					$fieldWrapper.append('<span class="rt_fa-field-error">' + fieldLabel + ' is required.</span>');
				}
			});

			if (!isValid) {
				// Scroll to first error
				$('html, body').animate({
					scrollTop: $form.find('.has-error').first().offset().top - 100
				}, 500);
				return;
			}

			// Disable submit button and show spinner
			$submitBtn.prop('disabled', true);
			$spinner.show();

			// Collect form data
			const formData = new FormData($form[0]);
			formData.append('action', 'rt_fa_submit');
			formData.append('form_id', formId);

			// Submit via AJAX
			$.ajax({
				url: rt_faPublic.ajax_url,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function(response) {
					if (response.success) {
						// Show success message
						$message.addClass('success')
							.html(response.data.message)
							.fadeIn(300);

						// Reset form
						$form[0].reset();

						// Scroll to message
						$('html, body').animate({
							scrollTop: $message.offset().top - 100
						}, 500);
					} else {
						// Show error message
						$message.addClass('error')
							.html(response.data.message || 'An error occurred. Please try again.')
							.fadeIn(300);
					}
				},
				error: function() {
					// Show error message
					$message.addClass('error')
						.html('An error occurred. Please try again.')
						.fadeIn(300);
				},
				complete: function() {
					// Re-enable submit button and hide spinner
					$submitBtn.prop('disabled', false);
					$spinner.hide();
				}
			});
		});

		// Real-time email validation
		$('.rt_fa-input-email').on('blur', function() {
			const $input = $(this);
			const email = $input.val();

			if (email && !isValidEmail(email)) {
				const $fieldWrapper = $input.closest('.rt_fa-field');
				$fieldWrapper.addClass('has-error');
				$fieldWrapper.find('.rt_fa-field-error').remove();
				$fieldWrapper.append('<span class="rt_fa-field-error">Please enter a valid email address.</span>');
			} else {
				const $fieldWrapper = $input.closest('.rt_fa-field');
				$fieldWrapper.removeClass('has-error');
				$fieldWrapper.find('.rt_fa-field-error').remove();
			}
		});

		// Real-time URL validation
		$('.rt_fa-input-url').on('blur', function() {
			const $input = $(this);
			const url = $input.val();

			if (url && !isValidURL(url)) {
				const $fieldWrapper = $input.closest('.rt_fa-field');
				$fieldWrapper.addClass('has-error');
				$fieldWrapper.find('.rt_fa-field-error').remove();
				$fieldWrapper.append('<span class="rt_fa-field-error">Please enter a valid URL.</span>');
			} else {
				const $fieldWrapper = $input.closest('.rt_fa-field');
				$fieldWrapper.removeClass('has-error');
				$fieldWrapper.find('.rt_fa-field-error').remove();
			}
		});

		// Remove error state on input
		$('.rt_fa-input, .rt_fa-textarea, .rt_fa-select').on('input change', function() {
			const $fieldWrapper = $(this).closest('.rt_fa-field');
			if ($fieldWrapper.hasClass('has-error') && $(this).val()) {
				$fieldWrapper.removeClass('has-error');
				$fieldWrapper.find('.rt_fa-field-error').remove();
			}
		});
	});

	/**
	 * Validate email address
	 */
	function isValidEmail(email) {
		const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return re.test(email);
	}

	/**
	 * Validate URL
	 */
	function isValidURL(url) {
		try {
			new URL(url);
			return true;
		} catch (e) {
			return false;
		}
	}

})(jQuery);
