/**
 * SmartForms AI - Form Templates Admin JavaScript
 * Enhanced 3-Step Wizard with Modern Interactions
 *
 * Handles template selection, configuration, preview, and installation.
 *
 * @package SmartForms_AI
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Initialize on document ready
    $(document).ready(function() {
        SmartFormsTemplates.init();
    });

    /**
     * Main Templates Module
     */
    var SmartFormsTemplates = {

        /**
         * Initialize the module
         */
        init: function() {
            this.cacheSelectors();
            this.bindEvents();
            this.updatePreview();
            this.updateSelectionCounter();
            this.togglePageOptions();
        },

        /**
         * Cache jQuery selectors
         */
        cacheSelectors: function() {
            this.$checkboxes = $('.template-checkbox');
            this.$installBtn = $('#install-templates-btn');
            this.$recommendedBtn = $('#select-recommended-btn');
            this.$deleteBtn = $('#delete-sample-data-btn');
            this.$submissionsSlider = $('#submissions_count');
            this.$sliderValue = $('.slider-value');
            this.$includeSpam = $('#include_spam');
            this.$spamPercentage = $('#spam_percentage');
            this.$createPages = $('#create_pages');
            this.$pageStatusOptions = $('#page-status-options');
            this.$previewStats = $('#preview-stats');
            this.$loading = $('#rt_fa-loading');
            this.$previewBtns = $('.preview-template-btn');
            this.$modal = $('#template-preview-modal');
            this.$modalClose = $('.rt_fa-modal-close, .rt_fa-modal-overlay');
            this.$selectionCounter = $('#selection-count');
            this.$distributionSection = $('#distribution-section');
            this.$spamPercentageContainer = $('#spam-percentage-container');
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            var self = this;

            // Template checkbox changes
            this.$checkboxes.on('change', function() {
                self.onTemplateSelection();
            });

            // Submissions slider
            this.$submissionsSlider.on('input', function() {
                self.$sliderValue.text($(this).val());
                self.updatePreview();
            });

            // Spam checkbox
            this.$includeSpam.on('change', function() {
                self.toggleSpamPercentage();
                self.updatePreview();
            });

            // Spam percentage
            this.$spamPercentage.on('input', function() {
                self.updatePreview();
            });

            // Create pages checkbox
            this.$createPages.on('change', function() {
                self.togglePageOptions();
                self.updatePreview();
            });

            // Install button
            this.$installBtn.on('click', function() {
                self.installTemplates();
            });

            // Select recommended button
            this.$recommendedBtn.on('click', function() {
                self.selectRecommended();
            });

            // Delete sample data button
            this.$deleteBtn.on('click', function() {
                self.deleteSampleData();
            });

            // Preview buttons
            this.$previewBtns.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var templateId = $(this).data('template-id');
                self.showTemplatePreview(templateId);
            });

            // Modal close
            this.$modalClose.on('click', function() {
                self.$modal.fadeOut(200);
            });

            // Close modal on escape key
            $(document).on('keyup', function(e) {
                if (e.key === 'Escape') {
                    self.$modal.fadeOut(200);
                }
            });

            // Initialize spam percentage toggle
            this.toggleSpamPercentage();
        },

        /**
         * Handle template selection
         */
        onTemplateSelection: function() {
            this.updateSelectionCounter();
            this.updatePreview();
            this.animateCounterUpdate();
        },

        /**
         * Update selection counter
         */
        updateSelectionCounter: function() {
            var selectedCount = this.$checkboxes.filter(':checked').length;
            var text = selectedCount + ' template' + (selectedCount !== 1 ? 's' : '') + ' selected';
            this.$selectionCounter.text(text);
        },

        /**
         * Animate counter update
         */
        animateCounterUpdate: function() {
            this.$selectionCounter.parent().addClass('fade-in');
            setTimeout(function() {
                $('.selection-counter').removeClass('fade-in');
            }, 300);
        },

        /**
         * Update installation preview
         */
        updatePreview: function() {
            var selectedCount = this.$checkboxes.filter(':checked').length;
            var submissionsPerForm = parseInt(this.$submissionsSlider.val());
            var totalSubmissions = selectedCount * submissionsPerForm;
            var spamPercentage = this.$includeSpam.is(':checked') ? parseInt(this.$spamPercentage.val()) : 0;
            var spamCount = Math.round(totalSubmissions * (spamPercentage / 100));
            var createPages = this.$createPages.is(':checked');
            var pagesCount = createPages ? selectedCount : 0;

            // Calculate distribution
            var excellent = Math.round(totalSubmissions * 0.25);
            var good = Math.round(totalSubmissions * 0.40);
            var fair = Math.round(totalSubmissions * 0.25);
            var poor = Math.round(totalSubmissions * 0.10);

            // Update preview display with animation
            this.animateValue('#preview-forms', parseInt($('#preview-forms').text()) || 0, selectedCount);
            this.animateValue('#preview-submissions', parseInt($('#preview-submissions').text()) || 0, totalSubmissions);
            this.animateValue('#preview-pages', parseInt($('#preview-pages').text()) || 0, pagesCount);
            this.animateValue('#preview-excellent', parseInt($('#preview-excellent').text()) || 0, excellent);
            this.animateValue('#preview-good', parseInt($('#preview-good').text()) || 0, good);
            this.animateValue('#preview-fair', parseInt($('#preview-fair').text()) || 0, fair);
            this.animateValue('#preview-poor', parseInt($('#preview-poor').text()) || 0, poor);
            this.animateValue('#preview-spam', parseInt($('#preview-spam').text()) || 0, spamCount);
            $('#preview-spam-percent').text(spamPercentage);

            // Show/hide distribution section based on submissions
            if (totalSubmissions > 0) {
                this.$distributionSection.slideDown(300);
            } else {
                this.$distributionSection.slideUp(300);
            }

            // Enable/disable install button
            this.$installBtn.prop('disabled', selectedCount === 0);

            // Update button text
            if (selectedCount > 0) {
                var btnText = 'Install ' + selectedCount + ' Template' + (selectedCount > 1 ? 's' : '');
                if (createPages && pagesCount > 0) {
                    btnText += ' & Create Pages';
                }
                this.$installBtn.find('.button-text').text(btnText);
            } else {
                this.$installBtn.find('.button-text').text('Install Selected Templates');
            }
        },

        /**
         * Animate number changes
         */
        animateValue: function(selector, start, end) {
            var $elem = $(selector);
            var duration = 400;
            var range = end - start;
            var startTime = null;

            function animation(currentTime) {
                if (startTime === null) startTime = currentTime;
                var timeElapsed = currentTime - startTime;
                var progress = Math.min(timeElapsed / duration, 1);

                var value = Math.floor(start + (range * progress));
                $elem.text(value);

                if (progress < 1) {
                    requestAnimationFrame(animation);
                }
            }

            requestAnimationFrame(animation);
        },

        /**
         * Toggle spam percentage input
         */
        toggleSpamPercentage: function() {
            if (this.$includeSpam.is(':checked')) {
                this.$spamPercentageContainer.slideDown(200);
            } else {
                this.$spamPercentageContainer.slideUp(200);
            }
        },

        /**
         * Toggle page options visibility
         */
        togglePageOptions: function() {
            if (this.$createPages.is(':checked')) {
                this.$pageStatusOptions.slideDown(300);
            } else {
                this.$pageStatusOptions.slideUp(300);
            }
        },

        /**
         * Select recommended templates
         */
        selectRecommended: function() {
            // Uncheck all first
            this.$checkboxes.prop('checked', false);

            // Check recommended templates
            $('.template-card').has('.recommended-badge').find('.template-checkbox').prop('checked', true);

            this.onTemplateSelection();

            // Visual feedback
            this.showNotice('✓ Recommended templates selected', 'success');

            // Smooth scroll to options
            this.smoothScrollTo('#step-2', 500);
        },

        /**
         * Install selected templates
         */
        installTemplates: function() {
            var self = this;
            var selectedTemplates = [];

            this.$checkboxes.filter(':checked').each(function() {
                selectedTemplates.push($(this).val());
            });

            if (selectedTemplates.length === 0) {
                this.showNotice('Please select at least one template', 'error');
                return;
            }

            // Show loading
            this.showLoading(rt_faTemplates.strings.installing);

            // Disable button
            this.$installBtn.prop('disabled', true);

            // Prepare data
            var data = {
                action: 'rt_fa_install_templates',
                nonce: rt_faTemplates.nonce,
                templates: selectedTemplates,
                submissions_count: parseInt(this.$submissionsSlider.val()),
                include_varied_scores: $('#include_varied_scores').is(':checked'),
                include_spam: this.$includeSpam.is(':checked'),
                spam_percentage: parseInt(this.$spamPercentage.val()),
                date_distribution: $('#distribute_dates').is(':checked') ? 'last_30_days' : 'today',
                create_pages: this.$createPages.is(':checked'),
                page_status: $('input[name="page_status"]:checked').val()
            };

            // AJAX request
            $.ajax({
                url: rt_faTemplates.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    self.hideLoading();

                    if (response.success) {
                        self.showSuccessMessage(response.data.message, response.data.results);

                        // Reload page after 3 seconds to show updated stats
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    } else {
                        self.showNotice(response.data.message || rt_faTemplates.strings.error, 'error');
                        self.$installBtn.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    self.hideLoading();
                    self.showNotice(rt_faTemplates.strings.error + ' ' + error, 'error');
                    self.$installBtn.prop('disabled', false);
                }
            });
        },

        /**
         * Delete all sample data
         */
        deleteSampleData: function() {
            var self = this;

            if (!confirm(rt_faTemplates.strings.confirmDelete)) {
                return;
            }

            // Show loading
            this.showLoading(rt_faTemplates.strings.deleting);

            // Disable button
            this.$deleteBtn.prop('disabled', true);

            // AJAX request
            $.ajax({
                url: rt_faTemplates.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rt_fa_delete_sample_data',
                    nonce: rt_faTemplates.nonce
                },
                success: function(response) {
                    self.hideLoading();

                    if (response.success) {
                        self.showNotice(response.data.message, 'success');

                        // Reload page after 1 second
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        self.showNotice(response.data.message || rt_faTemplates.strings.error, 'error');
                        self.$deleteBtn.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    self.hideLoading();
                    self.showNotice(rt_faTemplates.strings.error + ' ' + error, 'error');
                    self.$deleteBtn.prop('disabled', false);
                }
            });
        },

        /**
         * Show template preview modal
         */
        showTemplatePreview: function(templateId) {
            var self = this;

            // Show loading in modal
            $('#template-preview-body').html('<div class="loading-spinner" style="margin: 50px auto;"></div><p style="text-align: center;">Loading preview...</p>');
            this.$modal.fadeIn(200);

            // AJAX request
            $.ajax({
                url: rt_faTemplates.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rt_fa_get_template_preview',
                    nonce: rt_faTemplates.nonce,
                    template_id: templateId
                },
                success: function(response) {
                    if (response.success) {
                        $('#template-preview-body').html(response.data.html);
                    } else {
                        $('#template-preview-body').html('<p class="error" style="text-align: center; color: #dc3232;">Failed to load preview.</p>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#template-preview-body').html('<p class="error" style="text-align: center; color: #dc3232;">Error loading preview: ' + error + '</p>');
                }
            });
        },

        /**
         * Show loading indicator
         */
        showLoading: function(message) {
            this.$loading.find('.loading-message').text(message || 'Processing...');
            this.$loading.fadeIn(200);
        },

        /**
         * Hide loading indicator
         */
        hideLoading: function() {
            this.$loading.fadeOut(200);
        },

        /**
         * Show admin notice
         */
        showNotice: function(message, type) {
            type = type || 'info';

            var iconClass = type === 'success' ? 'dashicons-yes' :
                          type === 'error' ? 'dashicons-warning' : 'dashicons-info';

            var $notice = $('<div class="notice notice-' + type + ' is-dismissible">' +
                '<p><span class="dashicons ' + iconClass + '" style="margin-right: 8px;"></span>' + message + '</p>' +
                '</div>');

            $('.rt_fa-templates-wrap').prepend($notice);

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);

            // Make dismissible
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });

            // Scroll to notice
            this.smoothScrollTo('.rt_fa-templates-wrap', 300);
        },

        /**
         * Show success message with details
         */
        showSuccessMessage: function(message, results) {
            var $successMessage = $('<div class="notice notice-success is-dismissible" style="padding: 20px;">' +
                '<h3 style="margin-top: 0;"><span class="dashicons dashicons-yes" style="color: #46b450; margin-right: 8px;"></span>' +
                'Installation Successful!</h3>' +
                '<p style="font-size: 15px; margin: 10px 0;">' + message + '</p>' +
                '<div style="margin-top: 15px;">' +
                '<p><strong>What\'s next?</strong></p>' +
                '<ul style="margin-left: 20px;">' +
                '<li><a href="admin.php?page=raztech-form-architect" style="text-decoration: none;">View your forms</a></li>' +
                '<li><a href="edit.php?post_type=page" style="text-decoration: none;">View created pages</a></li>' +
                '<li><a href="admin.php?page=raztech-form-architect-submissions" style="text-decoration: none;">View sample submissions</a></li>' +
                '</ul>' +
                '</div>' +
                '</div>');

            $('.rt_fa-templates-wrap').prepend($successMessage);

            // Scroll to message
            this.smoothScrollTo('.rt_fa-templates-wrap', 300);
        },

        /**
         * Smooth scroll to element
         */
        smoothScrollTo: function(selector, duration) {
            duration = duration || 500;
            var $target = $(selector);

            if ($target.length) {
                $('html, body').animate({
                    scrollTop: $target.offset().top - 50
                }, duration);
            }
        }
    };

})(jQuery);
