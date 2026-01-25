/**
 * SmartForms AI - Conversational Form JavaScript
 *
 * Handles chat-style form interactions, question flow, and submission
 *
 * @package    SmartFormsAI
 * @subpackage SmartFormsAI/public/js
 * @since      1.0.0
 */

(function($) {
	'use strict';

	/**
	 * Conversational Form Handler
	 */
	window.rt_faInitConversational = function(formId, fields, config) {
		const chatState = {
			formId: formId,
			fields: fields,
			config: config,
			currentIndex: 0,
			answers: {},
			isProcessing: false
		};

		const messagesContainer = $('#rt_fa-messages-' + formId);
		const inputField = $('#rt_fa-chat-input-' + formId);
		const sendButton = $('#rt_fa-chat-send-' + formId);
		const typingIndicator = $('#rt_fa-chat-' + formId).find('.rt_fa-typing-indicator');
		const progressBar = $('#rt_fa-chat-' + formId).find('.rt_fa-progress-bar');

		/**
		 * Initialize conversational form
		 */
		function init() {
			// Show welcome message
			setTimeout(function() {
				displayBotMessage('👋 Hi! Let\'s get started.');

				if (config.form_description) {
					setTimeout(function() {
						displayBotMessage(config.form_description);
						setTimeout(askNextQuestion, 800);
					}, 600);
				} else {
					setTimeout(askNextQuestion, 800);
				}
			}, 500);

			// Event listeners
			sendButton.on('click', handleSendClick);
			inputField.on('keypress', handleInputKeypress);

			// ESC key clears input
			inputField.on('keydown', function(e) {
				if (e.key === 'Escape') {
					inputField.val('');
				}
			});
		}

		/**
		 * Ask next question
		 */
		function askNextQuestion() {
			if (chatState.currentIndex >= chatState.fields.length) {
				showSubmitButton();
				return;
			}

			const field = chatState.fields[chatState.currentIndex];
			updateProgress();

			showTypingIndicator();
			setTimeout(function() {
				hideTypingIndicator();

				// Build question message
				let questionText = field.label;
				if (field.required) {
					questionText += ' *';
				}

				displayBotMessage(questionText);

				// Handle different field types
				if (['select', 'radio'].includes(field.type) && field.options && field.options.length > 0) {
					// Show option buttons
					displayOptions(field.options);
				} else if (field.type === 'checkbox' && field.options && field.options.length > 0) {
					// Show checkbox options
					displayBotMessage('You can select multiple options. Type them separated by commas, or type "none" to skip.');
					displayOptions(field.options);
				} else {
					// Enable text input
					enableInput(field);
				}
			}, 800);
		}

		/**
		 * Display option buttons
		 */
		function displayOptions(options) {
			const optionsHtml = $('<div class="rt_fa-chat-message rt_fa-bot-message"><div class="rt_fa-chat-options"></div></div>');
			const optionsContainer = optionsHtml.find('.rt_fa-chat-options');

			options.forEach(function(option) {
				const btn = $('<button class="rt_fa-chat-option-btn"></button>')
					.text(option)
					.on('click', function() {
						handleOptionClick(option);
					});
				optionsContainer.append(btn);
			});

			messagesContainer.append(optionsHtml);
			scrollToBottom();
		}

		/**
		 * Handle option button click
		 */
		function handleOptionClick(option) {
			if (chatState.isProcessing) return;

			// Remove option buttons
			messagesContainer.find('.rt_fa-chat-options').parent().remove();

			// Display user's choice
			displayUserMessage(option);

			// Save answer and move to next question
			const field = chatState.fields[chatState.currentIndex];
			chatState.answers[field.name] = option;
			chatState.currentIndex++;

			setTimeout(askNextQuestion, 600);
		}

		/**
		 * Enable text input
		 */
		function enableInput(field) {
			inputField.prop('disabled', false).focus();
			sendButton.prop('disabled', false);

			// Update placeholder based on field type
			let placeholder = 'Type your answer...';
			if (field.placeholder) {
				placeholder = field.placeholder;
			} else if (field.type === 'email') {
				placeholder = 'your@email.com';
			} else if (field.type === 'tel') {
				placeholder = '555-1234';
			} else if (field.type === 'url') {
				placeholder = 'https://example.com';
			} else if (field.type === 'number') {
				placeholder = 'Enter a number';
			}

			inputField.attr('placeholder', placeholder);
		}

		/**
		 * Disable input
		 */
		function disableInput() {
			inputField.prop('disabled', true).val('');
			sendButton.prop('disabled', true);
		}

		/**
		 * Handle send button click
		 */
		function handleSendClick() {
			handleUserAnswer();
		}

		/**
		 * Handle input keypress
		 */
		function handleInputKeypress(e) {
			if (e.key === 'Enter' && !e.shiftKey) {
				e.preventDefault();
				handleUserAnswer();
			}
		}

		/**
		 * Handle user answer
		 */
		function handleUserAnswer() {
			if (chatState.isProcessing) return;

			const answer = inputField.val().trim();
			if (!answer) return;

			const field = chatState.fields[chatState.currentIndex];

			// Validate answer
			const validation = validateAnswer(field, answer);
			if (!validation.valid) {
				displayErrorMessage(validation.message);
				return;
			}

			// Processing
			chatState.isProcessing = true;
			disableInput();

			// Display user's answer
			displayUserMessage(answer);

			// Save answer
			chatState.answers[field.name] = answer;
			chatState.currentIndex++;

			// Move to next question
			setTimeout(function() {
				chatState.isProcessing = false;
				askNextQuestion();
			}, 600);
		}

		/**
		 * Validate answer
		 */
		function validateAnswer(field, answer) {
			// Required field check
			if (field.required && !answer) {
				return {
					valid: false,
					message: 'This field is required. Please provide an answer.'
				};
			}

			// Skip validation for optional empty fields
			if (!answer) {
				return { valid: true };
			}

			// Type-specific validation
			switch (field.type) {
				case 'email':
					if (!isValidEmail(answer)) {
						return {
							valid: false,
							message: 'Please enter a valid email address.'
						};
					}
					break;

				case 'url':
					if (!isValidUrl(answer)) {
						return {
							valid: false,
							message: 'Please enter a valid URL (e.g., https://example.com).'
						};
					}
					break;

				case 'tel':
					if (answer.length < 7) {
						return {
							valid: false,
							message: 'Please enter a valid phone number.'
						};
					}
					break;

				case 'number':
					if (isNaN(answer)) {
						return {
							valid: false,
							message: 'Please enter a valid number.'
						};
					}
					break;

				case 'textarea':
				case 'text':
					if (answer.length > 5000) {
						return {
							valid: false,
							message: 'Your answer is too long. Please keep it under 5000 characters.'
						};
					}
					break;
			}

			return { valid: true };
		}

		/**
		 * Validate email
		 */
		function isValidEmail(email) {
			const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			return re.test(email);
		}

		/**
		 * Validate URL
		 */
		function isValidUrl(url) {
			try {
				new URL(url);
				return true;
			} catch (e) {
				return false;
			}
		}

		/**
		 * Display bot message
		 */
		function displayBotMessage(message) {
			const messageHtml = `
				<div class="rt_fa-chat-message rt_fa-bot-message">
					<div class="rt_fa-chat-avatar">🤖</div>
					<div class="rt_fa-chat-bubble">${escapeHtml(message)}</div>
				</div>
			`;
			messagesContainer.append(messageHtml);
			scrollToBottom();
		}

		/**
		 * Display user message
		 */
		function displayUserMessage(message) {
			const messageHtml = `
				<div class="rt_fa-chat-message rt_fa-user-message">
					<div class="rt_fa-chat-bubble">${escapeHtml(message)}</div>
					<div class="rt_fa-chat-avatar">👤</div>
				</div>
			`;
			messagesContainer.append(messageHtml);
			scrollToBottom();
		}

		/**
		 * Display error message
		 */
		function displayErrorMessage(message) {
			const messageHtml = `
				<div class="rt_fa-chat-message rt_fa-bot-message rt_fa-error-message">
					<div class="rt_fa-chat-avatar">🤖</div>
					<div class="rt_fa-chat-bubble">${escapeHtml(message)}</div>
				</div>
			`;
			messagesContainer.append(messageHtml);
			scrollToBottom();

			// Re-enable input
			enableInput(chatState.fields[chatState.currentIndex]);
		}

		/**
		 * Show typing indicator
		 */
		function showTypingIndicator() {
			typingIndicator.show();
			scrollToBottom();
		}

		/**
		 * Hide typing indicator
		 */
		function hideTypingIndicator() {
			typingIndicator.hide();
		}

		/**
		 * Update progress bar
		 */
		function updateProgress() {
			const progress = (chatState.currentIndex / chatState.fields.length) * 100;
			progressBar.css('width', progress + '%');
		}

		/**
		 * Show submit button
		 */
		function showSubmitButton() {
			updateProgress(); // Set to 100%

			showTypingIndicator();
			setTimeout(function() {
				hideTypingIndicator();
				displayBotMessage('Perfect! Click submit to send your response.');

				const submitBtn = $('<button class="rt_fa-chat-submit-btn">' + escapeHtml(config.submit_text) + '</button>')
					.on('click', submitForm);

				const submitMessage = $('<div class="rt_fa-chat-message rt_fa-bot-message"></div>')
					.append(submitBtn);

				messagesContainer.append(submitMessage);
				scrollToBottom();
			}, 800);
		}

		/**
		 * Submit form
		 */
		function submitForm() {
			if (chatState.isProcessing) return;
			chatState.isProcessing = true;

			// Disable submit button
			messagesContainer.find('.rt_fa-chat-submit-btn').prop('disabled', true).text('Submitting...');

			showTypingIndicator();

			// Prepare data
			const formData = {
				action: 'rt_fa_submit',
				form_id: chatState.formId,
				form_data: chatState.answers,
				conversational_token: chatState.config.token // CSRF token for security
			};

			// Submit via AJAX
			$.ajax({
				url: config.ajax_url,
				type: 'POST',
				data: formData,
				success: function(response) {
					hideTypingIndicator();
					messagesContainer.find('.rt_fa-chat-submit-btn').parent().remove();

					if (response.success) {
						displaySuccessMessage(config.success_message || 'Thank you! Your submission has been received.');
					} else {
						displayErrorMessage(response.data.message || 'Something went wrong. Please try again.');
						chatState.isProcessing = false;
					}
				},
				error: function() {
					hideTypingIndicator();
					messagesContainer.find('.rt_fa-chat-submit-btn').parent().remove();
					displayErrorMessage('An error occurred. Please try again.');
					chatState.isProcessing = false;
				}
			});
		}

		/**
		 * Display success message
		 */
		function displaySuccessMessage(message) {
			const messageHtml = `
				<div class="rt_fa-chat-message rt_fa-bot-message rt_fa-success-message">
					<div class="rt_fa-chat-avatar">✅</div>
					<div class="rt_fa-chat-bubble">${escapeHtml(message)}</div>
				</div>
			`;
			messagesContainer.append(messageHtml);
			scrollToBottom();
		}

		/**
		 * Scroll to bottom of messages
		 */
		function scrollToBottom() {
			messagesContainer.animate({
				scrollTop: messagesContainer[0].scrollHeight
			}, 300);
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
			return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
		}

		// Initialize the conversational form
		init();
	};

})(jQuery);
