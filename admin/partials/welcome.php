<?php
/**
 * Welcome/Onboarding Screen
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div class="wrap smartforms-welcome">
	<div class="rt-fa-welcome-header">
		<h1><?php echo esc_html__( 'Welcome to RazTech AI Form Architect!', 'raztech-form-architect' ); ?> 🎉</h1>
		<p class="rt-fa-welcome-subtitle">
			<?php echo esc_html__( 'Thank you for installing RazTech AI Form Architect. Let\'s get you set up in 3 easy steps.', 'raztech-form-architect' ); ?>
		</p>
	</div>

	<div class="rt-fa-setup-steps">
		<div class="rt-fa-step">
			<div class="rt-fa-step-number">1</div>
			<div class="rt-fa-step-content">
				<h3><?php echo esc_html__( 'Add API Key', 'raztech-form-architect' ); ?></h3>
				<p><?php echo esc_html__( 'Connect your OpenAI account to unlock AI-powered form generation, lead scoring, spam detection, and auto-responses.', 'raztech-form-architect' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-settings' ) ); ?>" class="button button-primary button-hero">
					<?php echo esc_html__( 'Go to Settings', 'raztech-form-architect' ); ?>
				</a>
				<p class="rt-fa-step-note">
					<?php
					echo wp_kses_post( sprintf(
						/* translators: %s: OpenAI API keys URL */
						__( 'Don\'t have an API key? Get one at %s', 'raztech-form-architect' ),
						'<a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a>'
					) );
					?>
				</p>
			</div>
		</div>

		<div class="rt-fa-step">
			<div class="rt-fa-step-number">2</div>
			<div class="rt-fa-step-content">
				<h3><?php echo esc_html__( 'Create First Form', 'raztech-form-architect' ); ?></h3>
				<p><?php echo esc_html__( 'Use AI to generate a form in seconds by describing it in plain English, or build manually with our drag & drop builder.', 'raztech-form-architect' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-new-form' ) ); ?>" class="button button-primary button-hero">
					<?php echo esc_html__( 'Create Form', 'raztech-form-architect' ); ?>
				</a>
				<p class="rt-fa-step-note">
					<?php echo esc_html__( 'Try: "Create a contact form with name, email, phone, and message fields"', 'raztech-form-architect' ); ?>
				</p>
			</div>
		</div>

		<div class="rt-fa-step">
			<div class="rt-fa-step-number">3</div>
			<div class="rt-fa-step-content">
				<h3><?php echo esc_html__( 'Add to Your Site', 'raztech-form-architect' ); ?></h3>
				<p><?php echo esc_html__( 'Copy the shortcode from your form and paste it anywhere on your WordPress site - pages, posts, widgets, or page builders.', 'raztech-form-architect' ); ?></p>
				<div class="rt-fa-shortcode-example">
					<code>[smartform id="1"]</code>
				</div>
				<p class="rt-fa-step-note">
					<?php echo esc_html__( 'Works with Elementor, Divi, Gutenberg, and all major page builders!', 'raztech-form-architect' ); ?>
				</p>
			</div>
		</div>
	</div>

	<div class="rt-fa-welcome-features">
		<h2><?php echo esc_html__( 'What You Can Do with RazTech AI Form Architect', 'raztech-form-architect' ); ?></h2>
		<div class="rt-fa-features-grid">
			<div class="rt-fa-feature">
				<div class="rt-fa-feature-icon">🤖</div>
				<h4><?php echo esc_html__( 'AI Form Generation', 'raztech-form-architect' ); ?></h4>
				<p><?php echo esc_html__( 'Describe your form in plain English and let AI build it instantly.', 'raztech-form-architect' ); ?></p>
			</div>

			<div class="rt-fa-feature">
				<div class="rt-fa-feature-icon">🎯</div>
				<h4><?php echo esc_html__( 'Smart Lead Scoring', 'raztech-form-architect' ); ?></h4>
				<p><?php echo esc_html__( 'Automatic 0-100 scoring to prioritize your best leads.', 'raztech-form-architect' ); ?></p>
			</div>

			<div class="rt-fa-feature">
				<div class="rt-fa-feature-icon">🛡️</div>
				<h4><?php echo esc_html__( 'Spam Detection', 'raztech-form-architect' ); ?></h4>
				<p><?php echo esc_html__( '95%+ accuracy with AI-powered spam filtering.', 'raztech-form-architect' ); ?></p>
			</div>

			<div class="rt-fa-feature">
				<div class="rt-fa-feature-icon">📧</div>
				<h4><?php echo esc_html__( 'Auto-Responses', 'raztech-form-architect' ); ?></h4>
				<p><?php echo esc_html__( 'Personalized emails based on lead quality.', 'raztech-form-architect' ); ?></p>
			</div>

			<div class="rt-fa-feature">
				<div class="rt-fa-feature-icon">💬</div>
				<h4><?php echo esc_html__( 'Conversational Forms', 'raztech-form-architect' ); ?></h4>
				<p><?php echo esc_html__( 'Chat-style forms with 40% higher completion rates.', 'raztech-form-architect' ); ?></p>
			</div>

			<div class="rt-fa-feature">
				<div class="rt-fa-feature-icon">📊</div>
				<h4><?php echo esc_html__( 'Advanced Analytics', 'raztech-form-architect' ); ?></h4>
				<p><?php echo esc_html__( 'Interactive charts with AI-powered insights.', 'raztech-form-architect' ); ?></p>
			</div>
		</div>
	</div>

	<div class="rt-fa-resources">
		<h2><?php echo esc_html__( 'Helpful Resources', 'raztech-form-architect' ); ?></h2>
		<div class="rt-fa-resources-grid">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=smartforms-ai' ) ); ?>" class="rt-fa-resource-card">
				<span class="rt-fa-resource-icon">📊</span>
				<h4><?php echo esc_html__( 'Dashboard', 'raztech-form-architect' ); ?></h4>
				<p><?php echo esc_html__( 'View analytics and insights', 'raztech-form-architect' ); ?></p>
			</a>

			<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-forms' ) ); ?>" class="rt-fa-resource-card">
				<span class="rt-fa-resource-icon">📝</span>
				<h4><?php echo esc_html__( 'All Forms', 'raztech-form-architect' ); ?></h4>
				<p><?php echo esc_html__( 'Manage your forms', 'raztech-form-architect' ); ?></p>
			</a>

			<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-submissions' ) ); ?>" class="rt-fa-resource-card">
				<span class="rt-fa-resource-icon">📥</span>
				<h4><?php echo esc_html__( 'Submissions', 'raztech-form-architect' ); ?></h4>
				<p><?php echo esc_html__( 'View form responses', 'raztech-form-architect' ); ?></p>
			</a>

			<a href="https://raztechnologies.com/docs/smartforms-ai" target="_blank" class="rt-fa-resource-card">
				<span class="rt-fa-resource-icon">📖</span>
				<h4><?php echo esc_html__( 'Documentation', 'raztech-form-architect' ); ?></h4>
				<p><?php echo esc_html__( 'Guides and tutorials', 'raztech-form-architect' ); ?></p>
			</a>

			<a href="https://raztechnologies.com/support" target="_blank" class="rt-fa-resource-card">
				<span class="rt-fa-resource-icon">💬</span>
				<h4><?php echo esc_html__( 'Support', 'raztech-form-architect' ); ?></h4>
				<p><?php echo esc_html__( 'Get help from our team', 'raztech-form-architect' ); ?></p>
			</a>

			<a href="https://wordpress.org/support/plugin/smartforms-ai/reviews/#new-post" target="_blank" class="rt-fa-resource-card">
				<span class="rt-fa-resource-icon">⭐</span>
				<h4><?php echo esc_html__( 'Rate Plugin', 'raztech-form-architect' ); ?></h4>
				<p><?php echo esc_html__( 'Share your experience', 'raztech-form-architect' ); ?></p>
			</a>
		</div>
	</div>

	<div class="rt-fa-welcome-footer">
		<p>
			<?php
			echo wp_kses_post( sprintf(
				/* translators: %s: Dashboard URL */
				__( 'Ready to go? %s to see your analytics.', 'raztech-form-architect' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=smartforms-ai' ) ) . '">' . esc_html__( 'Visit the Dashboard', 'raztech-form-architect' ) . '</a>'
			) );
			?>
		</p>
	</div>
</div>

