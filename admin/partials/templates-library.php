<?php
/**
 * Form Templates Library View - 3-Step Wizard
 *
 * @package    RT_FA_AI
 * @subpackage RT_FA_AI/admin/partials
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap raztech-form-architect-templates-wrap">

    <!-- Header -->
    <div class="raztech-form-architect-templates-header">
        <h1 class="smartforms-page-title">
            📋 <?php esc_html_e('Form Templates Library', 'raztech-form-architect'); ?>
        </h1>
        <p class="smartforms-page-subtitle">
            <?php esc_html_e('Create professional forms in minutes with our 3-step wizard', 'raztech-form-architect'); ?>
        </p>
    </div>

    <!-- Progress Bar -->
    <div class="smartforms-progress-bar">
        <div class="progress-step active" data-step="1">
            <div class="step-number">1</div>
            <div class="step-label"><?php esc_html_e('Select Templates', 'raztech-form-architect'); ?></div>
        </div>
        <div class="progress-connector"></div>
        <div class="progress-step" data-step="2">
            <div class="step-number">2</div>
            <div class="step-label"><?php esc_html_e('Configure', 'raztech-form-architect'); ?></div>
        </div>
        <div class="progress-connector"></div>
        <div class="progress-step" data-step="3">
            <div class="step-number">3</div>
            <div class="step-label"><?php esc_html_e('Review & Install', 'raztech-form-architect'); ?></div>
        </div>
    </div>

    <!-- Step 1: Select Templates -->
    <div class="smartforms-step" id="step-1">
        <div class="step-header">
            <h2 class="step-title">
                <span class="step-badge">Step 1 of 3</span>
                <?php esc_html_e('Select Form Templates', 'raztech-form-architect'); ?>
            </h2>
            <p class="step-description">
                <?php esc_html_e('Choose from 15 professional, industry-tested form templates. Select one or more templates to get started.', 'raztech-form-architect'); ?>
            </p>
            <div class="selection-counter">
                <span class="counter-badge" id="selection-count">0 templates selected</span>
            </div>
        </div>

        <!-- Templates Grid -->
        <div class="raztech-form-architect-templates-grid">
            <?php foreach ($categories as $category_id => $category): ?>
                <div class="category-section" data-category="<?php echo esc_attr($category_id); ?>">
                    <div class="category-header">
                        <h3>
                            <span class="category-icon"><?php echo esc_html($category['icon']); ?></span>
                            <?php echo esc_html($category['name']); ?>
                        </h3>
                        <p class="category-description"><?php echo esc_html($category['description']); ?></p>
                    </div>

                    <div class="templates-row">
                        <?php foreach ($category['templates'] as $template):
                            $is_recommended = in_array($template['template_id'], $recommended_ids);
                        ?>
                            <div class="template-card <?php echo $is_recommended ? 'recommended' : ''; ?>"
                                 data-template-id="<?php echo esc_attr($template['template_id']); ?>">

                                <?php if ($is_recommended): ?>
                                    <span class="recommended-badge">⭐ <?php esc_html_e('Recommended', 'raztech-form-architect'); ?></span>
                                <?php endif; ?>

                                <label class="template-card-content">
                                    <input type="checkbox"
                                           class="template-checkbox"
                                           name="templates[]"
                                           value="<?php echo esc_attr($template['template_id']); ?>"
                                           <?php echo $is_recommended ? 'checked' : ''; ?>>

                                    <div class="template-icon-wrapper">
                                        <span class="template-icon"><?php echo esc_html($template['icon']); ?></span>
                                    </div>

                                    <div class="template-info">
                                        <h4 class="template-name"><?php echo esc_html($template['name']); ?></h4>
                                        <p class="template-description">
                                            <?php echo esc_html($template['description']); ?>
                                        </p>
                                        <div class="template-meta">
                                            <span class="meta-item">
                                                <span class="dashicons dashicons-edit"></span>
                                                <?php echo count($template['fields']); ?> <?php esc_html_e('fields', 'raztech-form-architect'); ?>
                                            </span>
                                            <?php if (!empty($template['industry']) && $template['industry'] !== 'general'): ?>
                                                <span class="meta-item">
                                                    <span class="dashicons dashicons-building"></span>
                                                    <?php echo esc_html(ucfirst($template['industry'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="template-checkmark">
                                        <span class="dashicons dashicons-yes"></span>
                                    </div>
                                </label>

                                <div class="template-card-footer">
                                    <button type="button"
                                            class="button-link preview-template-btn"
                                            data-template-id="<?php echo esc_attr($template['template_id']); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                        <?php esc_html_e('Preview Fields', 'raztech-form-architect'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="step-actions">
            <button type="button"
                    id="select-recommended-btn"
                    class="button button-secondary">
                <span class="dashicons dashicons-star-filled"></span>
                <?php esc_html_e('Select Recommended Set', 'raztech-form-architect'); ?>
            </button>
        </div>
    </div>

    <!-- Step 2: Configure Options -->
    <div class="smartforms-step" id="step-2">
        <div class="step-header">
            <h2 class="step-title">
                <span class="step-badge">Step 2 of 3</span>
                <?php esc_html_e('Configure Your Templates', 'raztech-form-architect'); ?>
            </h2>
            <p class="step-description">
                <?php esc_html_e('Customize how your templates will be installed. Add sample data for testing or create pages automatically.', 'raztech-form-architect'); ?>
            </p>
        </div>

        <div class="options-container">

            <!-- Sample Data Options Card -->
            <div class="option-card">
                <div class="option-card-header">
                    <span class="option-icon">📊</span>
                    <h3><?php esc_html_e('Sample Data Generation', 'raztech-form-architect'); ?></h3>
                </div>
                <div class="option-card-body">
                    <p class="option-help-text">
                        <?php esc_html_e('Add realistic sample submissions to test your forms. Perfect for seeing how lead scoring and spam detection work.', 'raztech-form-architect'); ?>
                    </p>

                    <div class="option-group">
                        <label class="option-label">
                            <?php esc_html_e('Sample Submissions per Form:', 'raztech-form-architect'); ?>
                            <span class="option-tooltip" title="<?php esc_attr_e('Set to 0 to create forms without sample data', 'raztech-form-architect'); ?>">
                                <span class="dashicons dashicons-info"></span>
                            </span>
                        </label>
                        <div class="slider-container">
                            <input type="range"
                                   id="submissions_count"
                                   name="submissions_count"
                                   min="0"
                                   max="50"
                                   value="20"
                                   class="submissions-slider">
                            <span class="slider-value">20</span>
                        </div>
                        <small class="option-description">
                            <?php esc_html_e('0 = forms only | 50 = max sample data', 'raztech-form-architect'); ?>
                        </small>
                    </div>

                    <div class="option-group-row">
                        <label class="option-checkbox">
                            <input type="checkbox"
                                   id="include_varied_scores"
                                   name="include_varied_scores"
                                   checked>
                            <span class="checkbox-label">
                                <?php esc_html_e('Include varied lead scores', 'raztech-form-architect'); ?>
                                <small><?php esc_html_e('(Excellent, Good, Fair, Poor)', 'raztech-form-architect'); ?></small>
                            </span>
                        </label>
                    </div>

                    <div class="option-group-row">
                        <label class="option-checkbox">
                            <input type="checkbox"
                                   id="include_spam"
                                   name="include_spam"
                                   checked>
                            <span class="checkbox-label">
                                <?php esc_html_e('Include spam examples', 'raztech-form-architect'); ?>
                            </span>
                        </label>
                        <div class="spam-percentage-input" id="spam-percentage-container">
                            <input type="number"
                                   id="spam_percentage"
                                   name="spam_percentage"
                                   value="10"
                                   min="0"
                                   max="100">
                            <span>%</span>
                        </div>
                    </div>

                    <div class="option-group-row">
                        <label class="option-checkbox">
                            <input type="checkbox"
                                   id="distribute_dates"
                                   name="distribute_dates"
                                   checked>
                            <span class="checkbox-label">
                                <?php esc_html_e('Distribute submissions over last 30 days', 'raztech-form-architect'); ?>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Page Creation Options Card (HIGHLIGHTED) -->
            <div class="option-card option-card-highlight">
                <div class="new-feature-badge">
                    ✨ <?php esc_html_e('TIME SAVER', 'raztech-form-architect'); ?>
                </div>
                <div class="option-card-header">
                    <span class="option-icon">🚀</span>
                    <h3><?php esc_html_e('Automatic Page Creation', 'raztech-form-architect'); ?></h3>
                </div>
                <div class="option-card-body">
                    <p class="option-highlight-text">
                        <strong><?php esc_html_e('Save 10+ minutes per form!', 'raztech-form-architect'); ?></strong>
                        <?php esc_html_e('We\'ll automatically create WordPress pages with your forms embedded and ready to use.', 'raztech-form-architect'); ?>
                    </p>

                    <div class="benefits-list">
                        <div class="benefit-item">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Professional intro text generated', 'raztech-form-architect'); ?>
                        </div>
                        <div class="benefit-item">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Form shortcode embedded automatically', 'raztech-form-architect'); ?>
                        </div>
                        <div class="benefit-item">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('SEO-friendly page structure', 'raztech-form-architect'); ?>
                        </div>
                        <div class="benefit-item">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Choose publish or draft status', 'raztech-form-architect'); ?>
                        </div>
                    </div>

                    <div class="option-group-main">
                        <label class="option-checkbox option-checkbox-large">
                            <input type="checkbox"
                                   id="create_pages"
                                   name="create_pages"
                                   checked>
                            <span class="checkbox-label-large">
                                <?php esc_html_e('Create pages automatically', 'raztech-form-architect'); ?>
                            </span>
                        </label>
                    </div>

                    <div class="page-status-options" id="page-status-options">
                        <label class="page-status-label"><?php esc_html_e('Page Status:', 'raztech-form-architect'); ?></label>
                        <div class="radio-group">
                            <label class="option-radio">
                                <input type="radio"
                                       name="page_status"
                                       value="publish"
                                       checked>
                                <span class="radio-label">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php esc_html_e('Publish immediately', 'raztech-form-architect'); ?>
                                </span>
                            </label>
                            <label class="option-radio">
                                <input type="radio"
                                       name="page_status"
                                       value="draft">
                                <span class="radio-label">
                                    <span class="dashicons dashicons-edit"></span>
                                    <?php esc_html_e('Save as draft', 'raztech-form-architect'); ?>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Step 3: Review & Install -->
    <div class="smartforms-step" id="step-3">
        <div class="step-header">
            <h2 class="step-title">
                <span class="step-badge">Step 3 of 3</span>
                <?php esc_html_e('Review & Install', 'raztech-form-architect'); ?>
            </h2>
            <p class="step-description">
                <?php esc_html_e('Review what will be created and install your templates.', 'raztech-form-architect'); ?>
            </p>
        </div>

        <!-- Preview Stats -->
        <div class="preview-stats-grid">
            <div class="stat-card stat-card-primary">
                <div class="stat-icon">
                    <span class="dashicons dashicons-forms"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="preview-forms">0</div>
                    <div class="stat-label"><?php esc_html_e('Forms', 'raztech-form-architect'); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="preview-submissions">0</div>
                    <div class="stat-label"><?php esc_html_e('Submissions', 'raztech-form-architect'); ?></div>
                </div>
            </div>

            <div class="stat-card stat-card-highlight">
                <div class="stat-badge">✨ <?php esc_html_e('NEW', 'raztech-form-architect'); ?></div>
                <div class="stat-icon">
                    <span class="dashicons dashicons-media-document"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value" id="preview-pages">0</div>
                    <div class="stat-label"><?php esc_html_e('Pages', 'raztech-form-architect'); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-shield"></span>
                </div>
                <div class="stat-content">
                    <div class="stat-value">
                        <span id="preview-spam">0</span>
                        <small>(<span id="preview-spam-percent">10</span>%)</small>
                    </div>
                    <div class="stat-label"><?php esc_html_e('Spam Examples', 'raztech-form-architect'); ?></div>
                </div>
            </div>
        </div>

        <!-- Lead Score Distribution -->
        <div class="distribution-card" id="distribution-section">
            <h3><?php esc_html_e('Lead Score Distribution', 'raztech-form-architect'); ?></h3>
            <div class="distribution-grid">
                <div class="dist-item dist-excellent">
                    <span class="dist-icon">🟢</span>
                    <div class="dist-info">
                        <div class="dist-label"><?php esc_html_e('Excellent', 'raztech-form-architect'); ?></div>
                        <div class="dist-range">(70-100)</div>
                    </div>
                    <div class="dist-value" id="preview-excellent">0</div>
                    <div class="dist-percent">25%</div>
                </div>
                <div class="dist-item dist-good">
                    <span class="dist-icon">🟡</span>
                    <div class="dist-info">
                        <div class="dist-label"><?php esc_html_e('Good', 'raztech-form-architect'); ?></div>
                        <div class="dist-range">(50-69)</div>
                    </div>
                    <div class="dist-value" id="preview-good">0</div>
                    <div class="dist-percent">40%</div>
                </div>
                <div class="dist-item dist-fair">
                    <span class="dist-icon">🟠</span>
                    <div class="dist-info">
                        <div class="dist-label"><?php esc_html_e('Fair', 'raztech-form-architect'); ?></div>
                        <div class="dist-range">(30-49)</div>
                    </div>
                    <div class="dist-value" id="preview-fair">0</div>
                    <div class="dist-percent">25%</div>
                </div>
                <div class="dist-item dist-poor">
                    <span class="dist-icon">🔴</span>
                    <div class="dist-info">
                        <div class="dist-label"><?php esc_html_e('Poor', 'raztech-form-architect'); ?></div>
                        <div class="dist-range">(0-29)</div>
                    </div>
                    <div class="dist-value" id="preview-poor">0</div>
                    <div class="dist-percent">10%</div>
                </div>
            </div>
        </div>

        <!-- Date Range Info -->
        <div class="info-card">
            <span class="dashicons dashicons-calendar-alt"></span>
            <strong><?php esc_html_e('Date Range:', 'raztech-form-architect'); ?></strong>
            <span id="preview-date-range"><?php echo date('M j', strtotime('-30 days')) . ' - ' . date('M j, Y'); ?></span>
        </div>

        <!-- Warning Notice -->
        <div class="notice-card notice-warning">
            <span class="dashicons dashicons-info"></span>
            <div>
                <strong><?php esc_html_e('Important:', 'raztech-form-architect'); ?></strong>
                <?php esc_html_e('This will add forms, submissions, and pages to your database. You can delete them anytime using the management section below.', 'raztech-form-architect'); ?>
            </div>
        </div>

        <!-- Install Button -->
        <div class="install-button-container">
            <button type="button"
                    id="install-templates-btn"
                    class="button button-primary button-hero button-install"
                    disabled>
                <span class="button-icon">🚀</span>
                <span class="button-text"><?php esc_html_e('Install Selected Templates', 'raztech-form-architect'); ?></span>
            </button>
        </div>
    </div>

    <!-- Sample Data Management -->
    <?php if ($sample_stats['has_sample_data']): ?>
        <div class="smartforms-sample-data-management">
            <div class="management-header">
                <span class="dashicons dashicons-admin-tools"></span>
                <h2><?php esc_html_e('Manage Installed Templates', 'raztech-form-architect'); ?></h2>
            </div>

            <div class="management-stats">
                <div class="management-stat">
                    <strong><?php esc_html_e('Sample data installed:', 'raztech-form-architect'); ?></strong>
                    <?php
                    printf(
                        __('%d forms, %d submissions', 'raztech-form-architect'),
                        $sample_stats['forms_count'],
                        $sample_stats['submissions_count']
                    );
                    ?>
                </div>
                <?php if ($sample_stats['last_installed']): ?>
                    <div class="management-stat">
                        <strong><?php esc_html_e('Last installed:', 'raztech-form-architect'); ?></strong>
                        <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($sample_stats['last_installed'])); ?>
                    </div>
                <?php endif; ?>
            </div>

            <button type="button"
                    id="delete-sample-data-btn"
                    class="button button-link-delete">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Delete All Sample Data', 'raztech-form-architect'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Template Preview Modal -->
<div id="template-preview-modal" class="smartforms-modal" style="display: none;">
    <div class="smartforms-modal-overlay"></div>
    <div class="smartforms-modal-content">
        <div class="smartforms-modal-header">
            <h2><?php esc_html_e('Template Preview', 'raztech-form-architect'); ?></h2>
            <button type="button" class="smartforms-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="smartforms-modal-body" id="template-preview-body">
            <!-- Preview content loaded via AJAX -->
        </div>
    </div>
</div>

<!-- Loading Indicator -->
<div id="smartforms-loading" class="smartforms-loading" style="display: none;">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <p class="loading-message"><?php esc_html_e('Processing...', 'raztech-form-architect'); ?></p>
    </div>
</div>
