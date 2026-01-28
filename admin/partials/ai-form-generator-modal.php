<?php
/**
 * AI Form Generator Modal
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get rate limit status
$raztaifo_rate_status = RAZTAIFO_Generator::get_rate_limit_status();
?>

<!-- AI Form Generator Modal -->
<div id="smartforms-ai-modal" class="smartforms-modal" style="display:none;">
	<div class="smartforms-modal-content smartforms-ai-modal-content">
		<span class="smartforms-modal-close" id="smartforms-ai-modal-close">&times;</span>

		<div class="smartforms-ai-header">
			<h2><?php echo esc_html__( '🤖 Generate Form with AI', 'raztech-form-architect' ); ?></h2>
			<p class="smartforms-ai-subtitle">
				<?php echo esc_html__( 'Describe your form in natural language and let AI create it for you.', 'raztech-form-architect' ); ?>
			</p>

			<!-- Privacy Notice -->
			<div class="notice notice-warning inline" style="margin: 15px 0; padding: 10px; background: #fff3cd; border-left: 4px solid #f0b849;">
				<p style="margin: 0;">
					<strong><span class="dashicons dashicons-privacy" style="color: #f0b849;"></span> <?php esc_html_e( 'Privacy Notice:', 'raztech-form-architect' ); ?></strong>
					<?php esc_html_e( 'Your form description will be sent to your configured AI provider (OpenAI or Anthropic) for processing. By clicking "Generate Form", you acknowledge that this data will be transmitted to an external service.', 'raztech-form-architect' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-settings' ) ); ?>" target="_blank" style="white-space: nowrap;">
						<?php esc_html_e( 'Review Privacy Policies', 'raztech-form-architect' ); ?> ↗
					</a>
				</p>
			</div>
		</div>

		<div class="smartforms-ai-body">
			<!-- Description Input -->
			<div class="smartforms-ai-field">
				<label for="smartforms-ai-description" class="smartforms-ai-label">
					<?php echo esc_html__( 'Describe Your Form', 'raztech-form-architect' ); ?>
					<span class="required">*</span>
				</label>
				<textarea
					id="smartforms-ai-description"
					class="smartforms-ai-textarea"
					rows="6"
					placeholder="<?php echo esc_attr__( 'Example: Create a consultation booking form for a law firm with fields for name, email, phone, preferred date, case type (dropdown with options: Family Law, Criminal Law, Corporate Law), and a message field.', 'raztech-form-architect' ); ?>"
				></textarea>
				<p class="description">
					<?php echo esc_html__( 'Be as specific as possible. Include field types, options for dropdowns, and any validation requirements.', 'raztech-form-architect' ); ?>
				</p>
			</div>

			<!-- Example Prompts -->
			<div class="smartforms-ai-examples">
				<label class="smartforms-ai-label"><?php echo esc_html__( 'Example Prompts:', 'raztech-form-architect' ); ?></label>
				<div class="smartforms-ai-example-grid">
					<button type="button" class="smartforms-ai-example-btn" data-example="contact">
						<?php echo esc_html__( '📧 Contact Form', 'raztech-form-architect' ); ?>
					</button>
					<button type="button" class="smartforms-ai-example-btn" data-example="registration">
						<?php echo esc_html__( '📝 Event Registration', 'raztech-form-architect' ); ?>
					</button>
					<button type="button" class="smartforms-ai-example-btn" data-example="survey">
						<?php echo esc_html__( '📊 Customer Survey', 'raztech-form-architect' ); ?>
					</button>
					<button type="button" class="smartforms-ai-example-btn" data-example="booking">
						<?php echo esc_html__( '📅 Appointment Booking', 'raztech-form-architect' ); ?>
					</button>
					<button type="button" class="smartforms-ai-example-btn" data-example="feedback">
						<?php echo esc_html__( '💬 Feedback Form', 'raztech-form-architect' ); ?>
					</button>
					<button type="button" class="smartforms-ai-example-btn" data-example="quote">
						<?php echo esc_html__( '💰 Quote Request', 'raztech-form-architect' ); ?>
					</button>
				</div>
			</div>

			<!-- Options -->
			<div class="smartforms-ai-options">
				<div class="smartforms-ai-option-group">
					<label for="smartforms-ai-complexity" class="smartforms-ai-label">
						<?php echo esc_html__( 'Form Complexity', 'raztech-form-architect' ); ?>
					</label>
					<select id="smartforms-ai-complexity" class="smartforms-ai-select">
						<option value="simple"><?php echo esc_html__( 'Simple (3-5 fields)', 'raztech-form-architect' ); ?></option>
						<option value="intermediate" selected><?php echo esc_html__( 'Intermediate (6-10 fields)', 'raztech-form-architect' ); ?></option>
						<option value="advanced"><?php echo esc_html__( 'Advanced (10-15 fields)', 'raztech-form-architect' ); ?></option>
					</select>
				</div>

				<div class="smartforms-ai-option-group">
					<label for="smartforms-ai-purpose" class="smartforms-ai-label">
						<?php echo esc_html__( 'Form Purpose (Optional)', 'raztech-form-architect' ); ?>
					</label>
					<input
						type="text"
						id="smartforms-ai-purpose"
						class="smartforms-ai-input"
						placeholder="<?php echo esc_attr__( 'e.g., Lead generation, Customer support', 'raztech-form-architect' ); ?>"
					/>
				</div>

				<div class="smartforms-ai-option-group">
					<label for="smartforms-ai-audience" class="smartforms-ai-label">
						<?php echo esc_html__( 'Target Audience (Optional)', 'raztech-form-architect' ); ?>
					</label>
					<input
						type="text"
						id="smartforms-ai-audience"
						class="smartforms-ai-input"
						placeholder="<?php echo esc_attr__( 'e.g., B2B clients, General public', 'raztech-form-architect' ); ?>"
					/>
				</div>
			</div>

			<!-- Page Creation Options (NEW) -->
			<div class="smartforms-ai-page-section">
				<h3 class="smartforms-ai-section-title">
					<?php echo esc_html__( '🚀 Automatic Page Creation', 'raztech-form-architect' ); ?>
					<span class="smartforms-badge-new"><?php echo esc_html__( 'TIME SAVER!', 'raztech-form-architect' ); ?></span>
				</h3>

				<p class="smartforms-ai-modal-description">
					<?php echo esc_html__( 'We\'ll automatically create a WordPress page with your AI-generated form embedded and ready to use. Save 10+ minutes of manual work!', 'raztech-form-architect' ); ?>
				</p>

				<div class="smartforms-ai-benefits-box">
					<ul class="smartforms-benefits-list">
						<li><?php echo esc_html__( '✓ Professional intro text generated automatically', 'raztech-form-architect' ); ?></li>
						<li><?php echo esc_html__( '✓ Form shortcode embedded in the page', 'raztech-form-architect' ); ?></li>
						<li><?php echo esc_html__( '✓ SEO-friendly page structure', 'raztech-form-architect' ); ?></li>
						<li><?php echo esc_html__( '✓ Choose to publish or save as draft', 'raztech-form-architect' ); ?></li>
					</ul>
				</div>

				<label class="smartforms-ai-checkbox-label smartforms-ai-primary-option">
					<input
						type="checkbox"
						id="smartforms-ai-create-page"
						name="ai_create_page"
						checked
					/>
					<strong><?php echo esc_html__( 'Create page automatically with my AI-generated form', 'raztech-form-architect' ); ?></strong>
				</label>

				<div class="smartforms-ai-sub-options" id="smartforms-ai-page-options">
					<p class="smartforms-ai-sub-label"><?php echo esc_html__( 'Page Status:', 'raztech-form-architect' ); ?></p>
					<label class="smartforms-ai-radio-label">
						<input
							type="radio"
							name="ai_page_status"
							value="publish"
							checked
						/>
						<span>
							<strong><?php echo esc_html__( 'Publish immediately', 'raztech-form-architect' ); ?></strong>
							<span class="smartforms-hint"><?php echo esc_html__( '(page goes live right away)', 'raztech-form-architect' ); ?></span>
						</span>
					</label>
					<label class="smartforms-ai-radio-label">
						<input
							type="radio"
							name="ai_page_status"
							value="draft"
						/>
						<span>
							<strong><?php echo esc_html__( 'Save as draft', 'raztech-form-architect' ); ?></strong>
							<span class="smartforms-hint"><?php echo esc_html__( '(review and customize before publishing)', 'raztech-form-architect' ); ?></span>
						</span>
					</label>
				</div>
			</div>

			<!-- Rate Limit Info -->
			<div class="smartforms-ai-rate-info">
				<span class="smartforms-ai-rate-badge">
					<?php
					echo esc_html( sprintf(
						/* translators: 1: Used requests, 2: Total limit */
						__( 'API Usage: %1$d / %2$d requests this hour', 'raztech-form-architect' ),
						$raztaifo_rate_status['used'],
						$raztaifo_rate_status['limit']
					) );
					?>
				</span>
				<?php if ( $raztaifo_rate_status['remaining'] <= 5 && $raztaifo_rate_status['remaining'] > 0 ) : ?>
					<span class="smartforms-ai-rate-warning">
						<?php
						echo esc_html( sprintf(
							/* translators: %d: Remaining requests */
							__( '⚠️ Only %d requests remaining', 'raztech-form-architect' ),
							$raztaifo_rate_status['remaining']
						) );
						?>
					</span>
				<?php endif; ?>
			</div>

			<!-- Error Message Container -->
			<div id="smartforms-ai-error" class="smartforms-ai-error" style="display:none;"></div>

			<!-- Loading Indicator -->
			<div id="smartforms-ai-loading" class="smartforms-ai-loading" style="display:none;">
				<div class="smartforms-ai-spinner"></div>
				<p><?php echo esc_html__( 'AI is generating your form... This may take 5-10 seconds.', 'raztech-form-architect' ); ?></p>
			</div>
		</div>

		<div class="smartforms-ai-footer">
			<button type="button" id="smartforms-ai-generate" class="button button-primary button-large">
				<?php echo esc_html__( '✨ Generate Form', 'raztech-form-architect' ); ?>
			</button>
			<button type="button" id="smartforms-ai-cancel" class="button button-large">
				<?php echo esc_html__( 'Cancel', 'raztech-form-architect' ); ?>
			</button>
		</div>
	</div>
</div>

