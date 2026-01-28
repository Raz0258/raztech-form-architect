<?php
/**
 * Admin Dashboard
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get statistics
$all_forms          = RAZTAIFO_Form_Builder::get_forms();
$total_forms        = count( $all_forms );
$total_submissions  = count( RAZTAIFO_Form_Builder::get_submissions() );
$recent_forms       = RAZTAIFO_Form_Builder::get_forms( array( 'limit' => 5 ) );
$recent_submissions = RAZTAIFO_Form_Builder::get_submissions( 0, array( 'limit' => 10 ) );

// PHASE 3: Calculate average lead score efficiently
$average_score = RAZTAIFO_Lead_Scorer::get_average_score();

// PHASE 7: Get additional statistics
$auto_response_count = get_option( 'raztaifo_autoresponse_count', 0 );

// PHASE 5: Calculate spam statistics
$all_submissions = RAZTAIFO_Form_Builder::get_submissions();
$spam_count      = 0;
foreach ( $all_submissions as $submission ) {
	if ( ! empty( $submission->is_spam ) ) {
		$spam_count++;
	}
}
?>

<div class="wrap smartforms-dashboard">
	<h1><?php echo esc_html__( 'RazTech Form Architect Dashboard', 'raztech-form-architect' ); ?></h1>

	<div class="smartforms-welcome-panel">
		<?php if ( $total_forms === 0 ) : ?>
			<h2><?php echo esc_html__( 'Welcome to RazTech Form Architect', 'raztech-form-architect' ); ?></h2>
			<p><?php echo esc_html__( 'Create optimized forms in seconds using AI, automatically score lead quality, and provide conversational form experiences—all without coding.', 'raztech-form-architect' ); ?></p>
		<?php else : ?>
			<h2><?php echo esc_html__( 'Welcome Back!', 'raztech-form-architect' ); ?></h2>
			<p>
				<?php
				echo esc_html( sprintf(
					/* translators: 1: Number of forms, 2: Number of submissions */
					__( 'You have %1$d %2$s and %3$d %4$s. Keep up the great work!', 'raztech-form-architect' ),
					$total_forms,
					$total_forms === 1 ? esc_html__( 'form', 'raztech-form-architect' ) : esc_html__( 'forms', 'raztech-form-architect' ),
					$total_submissions,
					$total_submissions === 1 ? esc_html__( 'submission', 'raztech-form-architect' ) : esc_html__( 'submissions', 'raztech-form-architect' )
				) );
				?>
			</p>
		<?php endif; ?>

		<div class="smartforms-quick-links">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-new-form' ) ); ?>" class="button button-primary button-hero">
				<?php
				if ( $total_forms === 0 ) {
					echo esc_html__( 'Create Your First Form', 'raztech-form-architect' );
				} else {
					echo esc_html__( 'Add New Form', 'raztech-form-architect' );
				}
				?>
			</a>
			<?php if ( $total_forms > 0 ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-forms' ) ); ?>" class="button button-hero">
					<?php echo esc_html__( 'View All Forms', 'raztech-form-architect' ); ?>
				</a>
			<?php endif; ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-settings' ) ); ?>" class="button button-hero">
				<?php echo esc_html__( 'Configure Settings', 'raztech-form-architect' ); ?>
			</a>
		</div>
	</div>

	<div class="smartforms-stats-grid">
		<div class="smartforms-stat-card">
			<div class="smartforms-stat-icon">📝</div>
			<div class="smartforms-stat-content">
				<h3><?php echo esc_html( $total_forms ); ?></h3>
				<p><?php echo esc_html__( 'Total Forms', 'raztech-form-architect' ); ?></p>
			</div>
		</div>

		<div class="smartforms-stat-card">
			<div class="smartforms-stat-icon">📊</div>
			<div class="smartforms-stat-content">
				<h3><?php echo esc_html( $total_submissions ); ?></h3>
				<p><?php echo esc_html__( 'Total Submissions', 'raztech-form-architect' ); ?></p>
			</div>
		</div>

		<div class="smartforms-stat-card">
			<div class="smartforms-stat-icon">⭐</div>
			<div class="smartforms-stat-content">
				<h3><?php echo esc_html( $total_submissions > 0 ? round( ( $total_submissions / max( $total_forms, 1 ) ), 1 ) : 0 ); ?></h3>
				<p><?php echo esc_html__( 'Avg Submissions/Form', 'raztech-form-architect' ); ?></p>
			</div>
		</div>

		<div class="smartforms-stat-card">
			<div class="smartforms-stat-icon">🎯</div>
			<div class="smartforms-stat-content">
				<?php
				// PHASE 3: Display average score with color indicator
				$avg_score_color = RAZTAIFO_Lead_Scorer::get_score_color( $average_score );
				?>
				<h3>
					<span class="smartforms-score-badge smartforms-score-<?php echo esc_attr( $avg_score_color ); ?>" style="font-size: 28px; padding: 8px 16px;">
						<?php echo esc_html( $average_score ); ?>
					</span>
				</h3>
				<p><?php echo esc_html__( 'Average Lead Score', 'raztech-form-architect' ); ?></p>
			</div>
		</div>

		<div class="smartforms-stat-card">
			<div class="smartforms-stat-icon">🛡️</div>
			<div class="smartforms-stat-content">
				<h3><?php echo esc_html( $spam_count ); ?></h3>
				<p><?php echo esc_html__( 'Spam Blocked', 'raztech-form-architect' ); ?></p>
			</div>
		</div>
	</div>

	<!-- PHASE 7: Charts Section -->
	<div class="smartforms-card" style="margin-top: 30px;">
		<h2><?php echo esc_html__( 'Submissions Over Time (Last 30 Days)', 'raztech-form-architect' ); ?></h2>
		<div style="height: 300px; padding: 20px 10px;">
			<canvas id="submissionsChart"></canvas>
		</div>
	</div>

	<div class="smartforms-dashboard-grid" style="margin-top: 20px;">
		<div class="smartforms-dashboard-col">
			<div class="smartforms-card">
				<h3><?php echo esc_html__( 'Lead Score Distribution', 'raztech-form-architect' ); ?></h3>
				<div style="height: 280px; padding: 20px 10px;">
					<canvas id="leadScoreChart"></canvas>
				</div>
			</div>
		</div>

		<div class="smartforms-dashboard-col">
			<div class="smartforms-card">
				<h3><?php echo esc_html__( 'Spam Detection Results', 'raztech-form-architect' ); ?></h3>
				<div style="height: 280px; padding: 20px 10px;">
					<canvas id="spamChart"></canvas>
				</div>
			</div>
		</div>
	</div>

	<!-- PHASE 7: AI Insights Section -->
	<?php $insights = RAZTAIFO_Admin::get_ai_insights(); ?>
	<?php if ( ! empty( $insights ) ) : ?>
	<div class="smartforms-card" style="margin-top: 20px;">
		<h2><?php echo esc_html__( 'AI-Powered Insights', 'raztech-form-architect' ); ?></h2>
		<div class="smartforms-insights">
			<?php foreach ( $insights as $insight ) : ?>
				<div class="smartforms-insight smartforms-insight-<?php echo esc_attr( $insight['type'] ); ?>">
					<span class="smartforms-insight-icon">
						<?php
						switch ( $insight['type'] ) {
							case 'success':
								echo esc_html( '✅' );
								break;
							case 'warning':
								echo esc_html( '⚠️' );
								break;
							case 'danger':
								echo esc_html( '🚨' );
								break;
						}
						?>
					</span>
					<span class="smartforms-insight-message"><?php echo esc_html( $insight['message'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<!-- PHASE 7: Form Performance Table -->
	<?php if ( ! empty( $all_forms ) ) : ?>
	<div class="smartforms-card" style="margin-top: 20px;">
		<h2><?php echo esc_html__( 'Form Performance', 'raztech-form-architect' ); ?></h2>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php echo esc_html__( 'Form Name', 'raztech-form-architect' ); ?></th>
					<th style="width: 80px;"><?php echo esc_html__( 'Views', 'raztech-form-architect' ); ?></th>
					<th style="width: 110px;"><?php echo esc_html__( 'Submissions', 'raztech-form-architect' ); ?></th>
					<th style="width: 110px;"><?php echo esc_html__( 'Conversion', 'raztech-form-architect' ); ?></th>
					<th style="width: 100px;"><?php echo esc_html__( 'Avg Score', 'raztech-form-architect' ); ?></th>
					<th style="width: 100px;"><?php echo esc_html__( 'Spam Rate', 'raztech-form-architect' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $all_forms as $form ) : ?>
					<?php $stats = RAZTAIFO_Admin::get_form_stats( $form->id ); ?>
					<tr>
						<td><strong><?php echo esc_html( $form->form_name ); ?></strong></td>
						<td><?php echo esc_html( number_format( $stats['views'] ) ); ?></td>
						<td><?php echo esc_html( number_format( $stats['submissions'] ) ); ?></td>
						<td>
							<span class="smartforms-conversion-badge smartforms-conversion-<?php echo esc_attr( $stats['conversion_rate'] >= 30 ? 'good' : ( $stats['conversion_rate'] >= 15 ? 'medium' : 'low' ) ); ?>">
								<?php echo esc_html( $stats['conversion_rate'] ); ?>%
							</span>
						</td>
						<td>
							<?php
							$score_color = RAZTAIFO_Lead_Scorer::get_score_color( $stats['avg_lead_score'] );
							?>
							<span class="smartforms-score-badge smartforms-score-<?php echo esc_attr( $score_color ); ?>" style="font-size: 13px; padding: 4px 10px;">
								<?php echo esc_html( $stats['avg_lead_score'] ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $stats['spam_rate'] ); ?>%</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

	<div class="smartforms-dashboard-grid">
		<div class="smartforms-dashboard-col">
			<div class="smartforms-card">
				<h2><?php echo esc_html__( 'Recent Forms', 'raztech-form-architect' ); ?></h2>

				<?php if ( ! empty( $recent_forms ) ) : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Form Name', 'raztech-form-architect' ); ?></th>
								<th><?php echo esc_html__( 'Created', 'raztech-form-architect' ); ?></th>
								<th><?php echo esc_html__( 'Actions', 'raztech-form-architect' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent_forms as $form ) : ?>
								<tr>
									<td>
										<strong><?php echo esc_html( $form->form_name ); ?></strong>
									</td>
									<td>
										<?php echo esc_html( human_time_diff( strtotime( $form->created_at ), current_time( 'timestamp' ) ) . ' ago' ); ?>
									</td>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-new-form&form_id=' . $form->id ) ); ?>" class="button button-small">
											<?php echo esc_html__( 'Edit', 'raztech-form-architect' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<p class="smartforms-view-all">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-forms' ) ); ?>">
							<?php echo esc_html__( 'View All Forms →', 'raztech-form-architect' ); ?>
						</a>
					</p>
				<?php else : ?>
					<p><?php echo esc_html__( 'No forms created yet.', 'raztech-form-architect' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-new-form' ) ); ?>" class="button button-primary">
						<?php echo esc_html__( 'Create Your First Form', 'raztech-form-architect' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div class="smartforms-dashboard-col">
			<div class="smartforms-card">
				<h2><?php echo esc_html__( 'Recent Submissions', 'raztech-form-architect' ); ?></h2>

				<?php if ( ! empty( $recent_submissions ) ) : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Form', 'raztech-form-architect' ); ?></th>
								<th><?php echo esc_html__( 'Submitted', 'raztech-form-architect' ); ?></th>
								<th><?php echo esc_html__( 'Lead Score', 'raztech-form-architect' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent_submissions as $submission ) : ?>
								<?php $form = RAZTAIFO_Form_Builder::get_form( $submission->form_id ); ?>
								<tr>
									<td>
										<?php echo $form ? esc_html( $form->form_name ) : esc_html__( 'Unknown', 'raztech-form-architect' ); ?>
									</td>
									<td>
										<?php echo esc_html( human_time_diff( strtotime( $submission->submitted_at ), current_time( 'timestamp' ) ) . ' ago' ); ?>
									</td>
									<td>
										<?php
										// PHASE 3: Get score color class for visual display
										$score_color = RAZTAIFO_Lead_Scorer::get_score_color( $submission->lead_score );
										?>
										<span class="smartforms-score-badge smartforms-score-<?php echo esc_attr( $score_color ); ?>">
											<?php echo esc_html( $submission->lead_score ); ?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<p class="smartforms-view-all">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-submissions' ) ); ?>">
							<?php echo esc_html__( 'View All Submissions →', 'raztech-form-architect' ); ?>
						</a>
					</p>
				<?php else : ?>
					<p><?php echo esc_html__( 'No submissions yet.', 'raztech-form-architect' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
