<?php
/**
 * Settings Page
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get current settings
$api_provider = get_option( 'rt_fa_api_provider', 'openai' );
$api_key      = get_option( 'rt_fa_api_key', '' );
$rate_limit   = get_option( 'rt_fa_rate_limit', 50 );
?>

<div class="wrap smartforms-settings">
	<h1><?php echo esc_html__( 'RazTech Form Architect Settings', 'raztech-form-architect' ); ?></h1>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html__( 'Settings saved successfully!', 'raztech-form-architect' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'rt_fa_save_settings', 'rt_fa_settings_nonce' ); ?>

		<div class="smartforms-settings-grid">
			<div class="smartforms-settings-main">
				<div class="smartforms-card">
					<h2><?php echo esc_html__( 'AI API Configuration', 'raztech-form-architect' ); ?></h2>
					<p><?php echo esc_html__( 'Configure your AI provider to enable AI-powered features like form generation, lead scoring, and conversational forms.', 'raztech-form-architect' ); ?></p>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="api_provider"><?php echo esc_html__( 'AI Provider', 'raztech-form-architect' ); ?></label>
							</th>
							<td>
								<select id="api_provider" name="api_provider" class="regular-text">
									<option value="openai" <?php selected( $api_provider, 'openai' ); ?>>
										<?php echo esc_html__( 'OpenAI (GPT-4)', 'raztech-form-architect' ); ?>
									</option>
									<option value="claude" <?php selected( $api_provider, 'claude' ); ?>>
										<?php echo esc_html__( 'Anthropic (Claude)', 'raztech-form-architect' ); ?>
									</option>
								</select>
								<p class="description">
									<?php echo esc_html__( 'Select your preferred AI provider. You will need an API key from the provider.', 'raztech-form-architect' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="api_key"><?php echo esc_html__( 'API Key', 'raztech-form-architect' ); ?></label>
							</th>
							<td>
								<input type="password" id="api_key" name="api_key" value="<?php echo esc_attr( $api_key ); ?>" class="large-text" />
								<p class="description">
									<?php
									echo wp_kses(
										sprintf(
											/* translators: 1: OpenAI link, 2: Anthropic link */
											__( 'Get your API key from <a href="%1$s" target="_blank">OpenAI</a> or <a href="%2$s" target="_blank">Anthropic</a>.', 'raztech-form-architect' ),
											'https://platform.openai.com/api-keys',
											'https://console.anthropic.com/settings/keys'
										),
										array(
											'a' => array(
												'href'   => array(),
												'target' => array(),
											),
										)
									);
									?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="rate_limit"><?php echo esc_html__( 'Rate Limit', 'raztech-form-architect' ); ?></label>
							</th>
							<td>
								<input type="number" id="rate_limit" name="rate_limit" value="<?php echo esc_attr( $rate_limit ); ?>" class="small-text" min="1" max="1000" />
								<span><?php echo esc_html__( 'requests per hour', 'raztech-form-architect' ); ?></span>
								<p class="description">
									<?php echo esc_html__( 'Maximum number of AI API requests per user per hour. Helps control API costs.', 'raztech-form-architect' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<h2><?php echo esc_html__( 'Auto-Response Settings', 'raztech-form-architect' ); ?></h2>
					<p><?php echo esc_html__( 'Configure AI-powered auto-responses to send personalized emails to form submitters based on their lead score.', 'raztech-form-architect' ); ?></p>

					<!-- Email Delivery Information Box -->
					<div class="smartforms-info-box">
						<h3>
							<span class="dashicons dashicons-info" style="color: #2271b1;"></span>
							<?php esc_html_e( 'About Email Delivery', 'raztech-form-architect' ); ?>
						</h3>
						<p>
							<?php esc_html_e( 'RazTech Form Architect uses WordPress\'s email system to send auto-responses. For reliable delivery:', 'raztech-form-architect' ); ?>
						</p>
						<ul>
							<li><?php esc_html_e( 'Configure SMTP using a plugin like WP Mail SMTP (free)', 'raztech-form-architect' ); ?></li>
							<li><?php esc_html_e( 'This is standard practice - used by WooCommerce, Contact Form 7, etc.', 'raztech-form-architect' ); ?></li>
							<li><?php esc_html_e( 'One-time setup benefits your entire WordPress site', 'raztech-form-architect' ); ?></li>
						</ul>
						<p>
							<a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=WP+Mail+SMTP&tab=search&type=term' ) ); ?>" class="button">
								<?php esc_html_e( 'Install WP Mail SMTP', 'raztech-form-architect' ); ?>
							</a>
							<a href="<?php echo esc_url( RT_FA_URL . 'docs/email-setup-guide.md' ); ?>" target="_blank" class="button">
								<?php esc_html_e( 'Setup Guide', 'raztech-form-architect' ); ?>
							</a>
						</p>
					</div>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="auto_response"><?php echo esc_html__( 'Auto-Responses', 'raztech-form-architect' ); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" id="auto_response" name="auto_response" value="1" <?php checked( get_option( 'rt_fa_auto_response', 0 ), 1 ); ?> />
									<?php echo esc_html__( 'Enable auto-responses', 'raztech-form-architect' ); ?>
								</label>
								<p class="description">
									<?php echo esc_html__( 'Automatically send personalized email responses to form submissions using AI.', 'raztech-form-architect' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="from_name"><?php echo esc_html__( 'From Name', 'raztech-form-architect' ); ?></label>
							</th>
							<td>
								<input type="text" id="from_name" name="from_name" value="<?php echo esc_attr( get_option( 'rt_fa_from_name', get_bloginfo( 'name' ) ) ); ?>" class="regular-text" />
								<p class="description">
									<?php echo esc_html__( 'The name that appears in auto-response emails.', 'raztech-form-architect' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="from_email"><?php echo esc_html__( 'From Email', 'raztech-form-architect' ); ?></label>
							</th>
							<td>
								<input type="email" id="from_email" name="from_email" value="<?php echo esc_attr( get_option( 'rt_fa_from_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text" />
								<p class="description">
									<?php echo esc_html__( 'The email address that auto-responses are sent from.', 'raztech-form-architect' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="reply_to_email"><?php echo esc_html__( 'Reply-To Email', 'raztech-form-architect' ); ?></label>
							</th>
							<td>
								<input type="email" id="reply_to_email" name="reply_to_email" value="<?php echo esc_attr( get_option( 'rt_fa_reply_to_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text" />
								<p class="description">
									<?php echo esc_html__( 'The email address where replies should be sent.', 'raztech-form-architect' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="skip_low_scores"><?php echo esc_html__( 'Skip Low-Quality Leads', 'raztech-form-architect' ); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" id="skip_low_scores" name="skip_low_scores" value="1" <?php checked( get_option( 'rt_fa_skip_low_scores', 0 ), 1 ); ?> />
									<?php echo esc_html__( 'Don\'t send auto-responses to leads scoring below 30', 'raztech-form-architect' ); ?>
								</label>
								<p class="description">
									<?php echo esc_html__( 'Conserve resources by not responding to very low-quality submissions.', 'raztech-form-architect' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<h2><?php echo esc_html__( 'Spam Detection Settings', 'raztech-form-architect' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="spam_detection"><?php echo esc_html__( 'Spam Detection', 'raztech-form-architect' ); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" id="spam_detection" name="spam_detection" value="1" <?php checked( get_option( 'rt_fa_spam_detection', 1 ), 1 ); ?> />
									<?php echo esc_html__( 'Enable spam detection', 'raztech-form-architect' ); ?>
								</label>
								<p class="description">
									<?php echo esc_html__( 'Automatically detects and flags spam submissions using pattern analysis. 95%+ accuracy.', 'raztech-form-architect' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="spam_threshold"><?php echo esc_html__( 'Spam Threshold', 'raztech-form-architect' ); ?></label>
							</th>
							<td>
								<input type="number" id="spam_threshold" name="spam_threshold" value="<?php echo esc_attr( get_option( 'rt_fa_spam_threshold', 60 ) ); ?>" class="small-text" min="0" max="100" />
								<span><?php echo esc_html__( '(0-100)', 'raztech-form-architect' ); ?></span>
								<p class="description">
									<?php echo esc_html__( 'Submissions scoring above this threshold are marked as spam. Default: 60 (recommended 50-70)', 'raztech-form-architect' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="spam_ai_check"><?php echo esc_html__( 'AI Content Analysis', 'raztech-form-architect' ); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" id="spam_ai_check" name="spam_ai_check" value="1" <?php checked( get_option( 'rt_fa_spam_ai_check', 0 ), 1 ); ?> />
									<?php echo esc_html__( 'Use AI for advanced spam detection', 'raztech-form-architect' ); ?>
								</label>
								<p class="description">
									<?php echo esc_html__( 'Adds GPT-3.5 content analysis for 99%+ accuracy. Uses API credits. Rate-limited to 100/hour.', 'raztech-form-architect' ); ?>
								</p>
							</td>
						</tr>
					</table>
				</div>

				<div class="smartforms-card">
					<h2><?php echo esc_html__( 'General Settings', 'raztech-form-architect' ); ?></h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<?php echo esc_html__( 'Plugin Version', 'raztech-form-architect' ); ?>
							</th>
							<td>
								<strong><?php echo esc_html( RT_FA_VERSION ); ?></strong>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php echo esc_html__( 'Database Tables', 'raztech-form-architect' ); ?>
							</th>
							<td>
								<?php
								global $wpdb;
								$tables = array(
									$wpdb->prefix . 'rt_fa_forms',
									$wpdb->prefix . 'rt_fa_submissions',
									$wpdb->prefix . 'rt_fa_analytics',
								);

								$all_exist = true;
								foreach ( $tables as $table ) {
									$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
									if ( ! $exists ) {
										$all_exist = false;
										break;
									}
								}

								if ( $all_exist ) {
									echo '<span style="color: green;">✓ ' . esc_html__( 'All tables exist', 'raztech-form-architect' ) . '</span>';
								} else {
									echo '<span style="color: red;">✗ ' . esc_html__( 'Some tables are missing', 'raztech-form-architect' ) . '</span>';
								}
								?>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="smartforms-settings-sidebar">
				<div class="smartforms-card">
					<h3><?php echo esc_html__( 'Save Settings', 'raztech-form-architect' ); ?></h3>
					<input type="submit" name="rt_fa_save_settings" class="button button-primary button-large" value="<?php echo esc_attr__( 'Save Changes', 'raztech-form-architect' ); ?>" />
				</div>

				<div class="smartforms-card">
					<h3><?php echo esc_html__( 'Need Help?', 'raztech-form-architect' ); ?></h3>
					<ul class="smartforms-help-links">
						<li><a href="#" target="_blank"><?php echo esc_html__( 'Documentation', 'raztech-form-architect' ); ?></a></li>
						<li><a href="#" target="_blank"><?php echo esc_html__( 'Video Tutorials', 'raztech-form-architect' ); ?></a></li>
						<li><a href="#" target="_blank"><?php echo esc_html__( 'Support Forum', 'raztech-form-architect' ); ?></a></li>
					</ul>
				</div>

				<div class="smartforms-card smartforms-info-card">
					<h3><?php echo esc_html__( 'About RazTech Form Architect', 'raztech-form-architect' ); ?></h3>
					<p><?php echo esc_html__( 'Create intelligent forms with AI-powered features.', 'raztech-form-architect' ); ?></p>
					<p><strong><?php echo esc_html__( 'Features:', 'raztech-form-architect' ); ?></strong></p>
					<ul>
						<li><?php echo esc_html__( 'AI Form Generation', 'raztech-form-architect' ); ?></li>
						<li><?php echo esc_html__( 'Lead Scoring', 'raztech-form-architect' ); ?></li>
						<li><?php echo esc_html__( 'Spam Detection', 'raztech-form-architect' ); ?></li>
						<li><?php echo esc_html__( 'Conversational Forms', 'raztech-form-architect' ); ?></li>
					</ul>
				</div>
			</div>
		</div>
	</form>
</div>
