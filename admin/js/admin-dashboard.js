/**
 * SmartForms AI - Admin Dashboard JavaScript
 *
 * @package    SmartFormsAI
 * @subpackage SmartFormsAI/admin/js
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// ================================================================
		// BULK ACTIONS
		// ================================================================

		// Handle "select all" checkbox
		$('#cb-select-all-1').on('change', function() {
			const isChecked = $(this).prop('checked');
			$('input[name="submission_ids[]"]').prop('checked', isChecked);
		});

		// Handle individual checkbox changes
		$(document).on('change', 'input[name="submission_ids[]"]', function() {
			const totalCheckboxes = $('input[name="submission_ids[]"]').length;
			const checkedCheckboxes = $('input[name="submission_ids[]"]:checked').length;
			$('#cb-select-all-1').prop('checked', totalCheckboxes === checkedCheckboxes);
		});

		// Handle bulk actions form submission
		$('#rt_fa-bulk-actions-form').on('submit', function(e) {
			e.preventDefault();

			const action = $('#bulk-action-selector-top').val();
			const selectedIds = [];

			$('input[name="submission_ids[]"]:checked').each(function() {
				selectedIds.push($(this).val());
			});

			if (action === '-1') {
				alert('Please select an action.');
				return;
			}

			if (selectedIds.length === 0) {
				alert('Please select at least one submission.');
				return;
			}

			// Confirm action
			let confirmMessage = '';
			switch (action) {
				case 'delete':
					confirmMessage = 'Are you sure you want to delete ' + selectedIds.length + ' submission(s)? This action cannot be undone.';
					break;
				case 'mark_spam':
					confirmMessage = 'Are you sure you want to mark ' + selectedIds.length + ' submission(s) as spam?';
					break;
				case 'mark_clean':
					confirmMessage = 'Are you sure you want to mark ' + selectedIds.length + ' submission(s) as clean?';
					break;
			}

			if (!confirm(confirmMessage)) {
				return;
			}

			// Show loading indicator
			const $submitBtn = $(this).find('button[type="submit"]');
			const originalText = $submitBtn.text();
			$submitBtn.prop('disabled', true).text('Processing...');

			// Perform bulk action via AJAX
			$.ajax({
				url: rt_faAjax.ajax_url,
				type: 'POST',
				data: {
					action: 'rt_fa_bulk_actions',
					bulk_action: action,
					submission_ids: selectedIds,
					nonce: $('input[name="rt_fa_bulk_nonce"]').val()
				},
				success: function(response) {
					if (response.success) {
						// Reload page with success message
						window.location.href = window.location.href +
							(window.location.href.indexOf('?') > -1 ? '&' : '?') +
							'bulk_action=success&count=' + selectedIds.length;
					} else {
						alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
						$submitBtn.prop('disabled', false).text(originalText);
					}
				},
				error: function() {
					alert('Error communicating with server. Please try again.');
					$submitBtn.prop('disabled', false).text(originalText);
				}
			});
		});

		// ================================================================
		// VIEW SUBMISSION MODAL
		// ================================================================

		// View submission modal
		$('.rt_fa-view-submission').on('click', function() {
			const submissionId = $(this).data('submission-id');
			const submissionData = $('.rt_fa-submission-data[data-submission-id="' + submissionId + '"]').html();

			if (submissionData) {
				$('#rt_fa-modal-body').html(submissionData);
				$('#rt_fa-submission-modal').fadeIn(300);
			}
		});

		// Close modal
		$('.rt_fa-modal-close').on('click', function() {
			$('#rt_fa-submission-modal').fadeOut(300);
		});

		// Close modal on outside click
		$(window).on('click', function(e) {
			if ($(e.target).is('#rt_fa-submission-modal')) {
				$('#rt_fa-submission-modal').fadeOut(300);
			}
		});

		// Close modal on ESC key
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape') {
				$('#rt_fa-submission-modal').fadeOut(300);
			}
		});

		// Copy shortcode functionality
		$('.rt_fa-copy-shortcode').on('click', function() {
			const shortcode = $(this).data('shortcode');
			copyToClipboard(shortcode);

			// Show feedback
			const $btn = $(this);
			const originalText = $btn.text();
			$btn.text('Copied!');
			setTimeout(function() {
				$btn.text(originalText);
			}, 2000);
		});

		// ================================================================
		// FORM DELETION WITH PAGE CLEANUP
		// ================================================================

		// Handle delete form click
		$(document).on('click', '.rt_fa-delete-form', function(e) {
			e.preventDefault();

			const $link = $(this);
			const formId = $link.data('form-id');
			const formName = $link.data('form-name');
			const nonce = $link.data('nonce');

			// Show modal
			$('#rt_fa-delete-modal').fadeIn(200);
			$('#rt_fa-delete-form-name').text(formName);

			// Disable delete button initially
			$('.rt_fa-confirm-delete').prop('disabled', true).text('Loading...');

			// Fetch form data (pages and submissions count)
			$.ajax({
				url: rt_faAjax.ajax_url,
				type: 'POST',
				data: {
					action: 'rt_fa_get_delete_info',
					form_id: formId,
					nonce: nonce
				},
				success: function(response) {
					if (response.success) {
						const data = response.data;

						// Update submissions count
						$('#rt_fa-delete-submissions-count').text(data.submissions_count);

						// Show pages if any
						if (data.pages && data.pages.length > 0) {
							$('#rt_fa-pages-section').show();

							const $pagesList = $('#rt_fa-pages-list');
							$pagesList.empty();

							data.pages.forEach(function(page) {
								const statusBadge = page.status === 'publish' ?
									'<span class="rt_fa-page-status" style="color: #46b450;">● Published</span>' :
									'<span class="rt_fa-page-status">○ ' + page.status.charAt(0).toUpperCase() + page.status.slice(1) + '</span>';

								$pagesList.append(
									'<div class="rt_fa-page-item">' +
										'<span class="rt_fa-page-icon">📄</span>' +
										'<div class="rt_fa-page-info">' +
											'<span class="rt_fa-page-title">' + page.title + '</span>' +
											statusBadge +
										'</div>' +
										'<a href="' + page.edit_url + '" target="_blank" class="rt_fa-page-link">View</a>' +
									'</div>'
								);
							});
						} else {
							$('#rt_fa-pages-section').hide();
						}

						// Enable delete button
						$('.rt_fa-confirm-delete')
							.prop('disabled', false)
							.html('<span class="dashicons dashicons-trash"></span> Delete Form')
							.data('form-id', formId)
							.data('nonce', nonce);

					} else {
						alert('Error loading form information. Please try again.');
						$('#rt_fa-delete-modal').fadeOut(200);
					}
				},
				error: function() {
					alert('Error communicating with server. Please try again.');
					$('#rt_fa-delete-modal').fadeOut(200);
				}
			});
		});

		// Handle modal close
		$('.rt_fa-modal-close, .rt_fa-modal-cancel').on('click', function() {
			$('#rt_fa-delete-modal').fadeOut(200);
			$('#rt_fa-delete-pages-checkbox').prop('checked', false);
		});

		// Close on overlay click
		$(document).on('click', '.rt_fa-modal-overlay', function() {
			$('#rt_fa-delete-modal').fadeOut(200);
			$('#rt_fa-delete-pages-checkbox').prop('checked', false);
		});

		// Close on ESC key (delete modal)
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape' && $('#rt_fa-delete-modal').is(':visible')) {
				$('#rt_fa-delete-modal').fadeOut(200);
				$('#rt_fa-delete-pages-checkbox').prop('checked', false);
			}
		});

		// Handle confirm delete
		$('.rt_fa-confirm-delete').on('click', function() {
			const $button = $(this);
			const formId = $button.data('form-id');
			const nonce = $button.data('nonce');
			const deletePages = $('#rt_fa-delete-pages-checkbox').is(':checked');

			// Show loading spinner
			$('#rt_fa-delete-modal').fadeOut(200);
			$('#rt_fa-delete-spinner').fadeIn(200);

			// Perform deletion
			$.ajax({
				url: rt_faAjax.ajax_url,
				type: 'POST',
				data: {
					action: 'rt_fa_delete_form',
					form_id: formId,
					delete_pages: deletePages,
					nonce: nonce
				},
				success: function(response) {
					if (response.success) {
						// Show success message
						$('#rt_fa-delete-spinner').fadeOut(200, function() {
							// Reload page with success message
							window.location.href = window.location.href +
								(window.location.href.indexOf('?') > -1 ? '&' : '?') +
								'deleted=1' +
								(deletePages ? '&pages_deleted=' + response.data.pages_deleted : '');
						});
					} else {
						$('#rt_fa-delete-spinner').fadeOut(200);
						alert('Error: ' + response.data.message);
					}
				},
				error: function() {
					$('#rt_fa-delete-spinner').fadeOut(200);
					alert('Error communicating with server. Please try again.');
				}
			});
		});
	});

	/**
	 * Copy text to clipboard
	 */
	function copyToClipboard(text) {
		if (navigator.clipboard && window.isSecureContext) {
			// Use modern clipboard API
			navigator.clipboard.writeText(text);
		} else {
			// Fallback for older browsers
			const $temp = $('<input>');
			$('body').append($temp);
			$temp.val(text).select();
			document.execCommand('copy');
			$temp.remove();
		}
	}

})(jQuery);
