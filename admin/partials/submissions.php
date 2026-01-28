<?php
/**
 * Submissions Page
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get filter parameters
$raztaifo_filter_form_id   = isset( $_GET['form_id'] ) ? intval( $_GET['form_id'] ) : 0;
$raztaifo_filter_score     = isset( $_GET['score_range'] ) ? sanitize_text_field( wp_unslash( $_GET['score_range'] ) ) : 'all';
$raztaifo_filter_spam      = isset( $_GET['spam_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['spam_filter'] ) ) : 'all';
$raztaifo_search_query     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

// Get sorting parameters
$raztaifo_orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'submitted_at';
$raztaifo_order   = isset( $_GET['order'] ) && $_GET['order'] === 'asc' ? 'asc' : 'desc';

// Get pagination parameters
$raztaifo_per_page     = 20;
$raztaifo_current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$raztaifo_offset       = ( $raztaifo_current_page - 1 ) * $raztaifo_per_page;

// First, get total count (without pagination)
$raztaifo_count_args = array(
	'orderby' => $raztaifo_orderby,
	'order'   => strtoupper( $raztaifo_order ),
	'limit'   => -1,
	'offset'  => 0,
);

if ( $raztaifo_filter_spam !== 'all' ) {
	$raztaifo_all_submissions = RAZTAIFO_Spam_Detector::get_submissions_by_spam_status( $raztaifo_filter_spam, $raztaifo_filter_form_id, $raztaifo_filter_score, $raztaifo_count_args );
} elseif ( $raztaifo_filter_score !== 'all' ) {
	$raztaifo_all_submissions = RAZTAIFO_Lead_Scorer::get_submissions_by_score( $raztaifo_filter_score, $raztaifo_filter_form_id, $raztaifo_count_args );
} else {
	$raztaifo_all_submissions = RAZTAIFO_Form_Builder::get_submissions( $raztaifo_filter_form_id, $raztaifo_count_args );
}

$raztaifo_total_items = count( $raztaifo_all_submissions );
$raztaifo_total_pages = ceil( $raztaifo_total_items / $raztaifo_per_page );

// Now get paginated submissions
$raztaifo_query_args = array(
	'orderby' => $raztaifo_orderby,
	'order'   => strtoupper( $raztaifo_order ),
	'limit'   => $raztaifo_per_page,
	'offset'  => $raztaifo_offset,
);

if ( $raztaifo_filter_spam !== 'all' ) {
	$raztaifo_submissions = RAZTAIFO_Spam_Detector::get_submissions_by_spam_status( $raztaifo_filter_spam, $raztaifo_filter_form_id, $raztaifo_filter_score, $raztaifo_query_args );
} elseif ( $raztaifo_filter_score !== 'all' ) {
	$raztaifo_submissions = RAZTAIFO_Lead_Scorer::get_submissions_by_score( $raztaifo_filter_score, $raztaifo_filter_form_id, $raztaifo_query_args );
} else {
	$raztaifo_submissions = RAZTAIFO_Form_Builder::get_submissions( $raztaifo_filter_form_id, $raztaifo_query_args );
}

// Debug: Log filter queries (uncomment to debug)
// error_log( '=== Submission Filters Debug ===' );
// error_log( 'Form ID: ' . $raztaifo_filter_form_id );
// error_log( 'Lead Quality Filter: ' . $raztaifo_filter_score );
// error_log( 'Spam Status Filter: ' . $raztaifo_filter_spam );
// error_log( 'Search Query: ' . $raztaifo_search_query );
// error_log( 'Result Count: ' . count( $raztaifo_submissions ) );
// error_log( 'Total Items (before search): ' . $raztaifo_total_items );

// Apply search filter if provided
if ( ! empty( $raztaifo_search_query ) ) {
	$raztaifo_submissions = array_filter(
		$raztaifo_submissions,
		function( $raztaifo_submission ) use ( $raztaifo_search_query ) {
			// Search in submission data
			$search_string = strtolower( $raztaifo_search_query );

			// Search in submission ID
			if ( strpos( strtolower( (string) $raztaifo_submission->id ), $search_string ) !== false ) {
				return true;
			}

			// Search in submission data
			if ( is_array( $raztaifo_submission->submission_data ) ) {
				$data_string = strtolower( wp_json_encode( $raztaifo_submission->submission_data ) );
				if ( strpos( $data_string, $search_string ) !== false ) {
					return true;
				}
			}

			// Search in IP address
			if ( isset( $raztaifo_submission->ip_address ) && strpos( strtolower( $raztaifo_submission->ip_address ), $search_string ) !== false ) {
				return true;
			}

			return false;
		}
	);

	// Reindex array after filtering
	$raztaifo_submissions = array_values( $raztaifo_submissions );

	// Recalculate totals after search
	$raztaifo_total_items = count( $raztaifo_submissions );
	$raztaifo_total_pages = ceil( $raztaifo_total_items / $raztaifo_per_page );

	// Re-paginate after search
	$raztaifo_submissions = array_slice( $raztaifo_submissions, $raztaifo_offset, $raztaifo_per_page );
}

// Get all forms for filter dropdown
$raztaifo_forms = RAZTAIFO_Form_Builder::get_forms();

// Get spam threshold from settings for consistent display
$raztaifo_spam_threshold = get_option( 'raztaifo_spam_threshold', 60 );

// Get accurate filter counts for dropdown (considering active filters)
global $wpdb;
$raztaifo_submissions_table = $wpdb->prefix . 'raztaifo_submissions';

// Build base WHERE clause for counts (include form and search filters if active)
$raztaifo_count_where_parts = array( '1=1' );

// Add form filter if active
if ( $raztaifo_filter_form_id > 0 ) {
	$raztaifo_count_where_parts[] = $wpdb->prepare( 'form_id = %d', $raztaifo_filter_form_id );
}

// Base WHERE clause for all counts
$raztaifo_count_base_where = implode( ' AND ', $raztaifo_count_where_parts );

// Total count (with form filter applied)
// Note: $raztaifo_submissions_table is safe (constructed from $wpdb->prefix)
$raztaifo_total_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$raztaifo_submissions_table} WHERE {$raztaifo_count_base_where}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

// Lead quality counts (considering active spam filter if set)
if ( $raztaifo_filter_spam !== 'all' ) {
	// Add spam filter condition for lead quality counts
	$spam_condition = '';
	if ( $raztaifo_filter_spam === 'spam' ) {
		$spam_condition = $wpdb->prepare( ' AND (spam_score >= %d OR is_spam = 1)', $raztaifo_spam_threshold );
	} elseif ( $raztaifo_filter_spam === 'not_spam' ) {
		$spam_condition = $wpdb->prepare( ' AND (spam_score < %d AND is_spam = 0)', $raztaifo_spam_threshold );
	} elseif ( $raztaifo_filter_spam === 'suspicious' ) {
		$spam_condition = $wpdb->prepare( ' AND spam_score >= 40 AND spam_score < %d', $raztaifo_spam_threshold );
	}

	$raztaifo_high_count   = $wpdb->get_var( "SELECT COUNT(*) FROM {$raztaifo_submissions_table} WHERE {$raztaifo_count_base_where} AND lead_score >= 80{$spam_condition}" );
	$raztaifo_medium_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$raztaifo_submissions_table} WHERE {$raztaifo_count_base_where} AND lead_score >= 50 AND lead_score < 80{$spam_condition}" );
	$raztaifo_low_count    = $wpdb->get_var( "SELECT COUNT(*) FROM {$raztaifo_submissions_table} WHERE {$raztaifo_count_base_where} AND lead_score < 50{$spam_condition}" );
} else {
	$raztaifo_high_count   = $wpdb->get_var( "SELECT COUNT(*) FROM {$raztaifo_submissions_table} WHERE {$raztaifo_count_base_where} AND lead_score >= 80" );
	$raztaifo_medium_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$raztaifo_submissions_table} WHERE {$raztaifo_count_base_where} AND lead_score >= 50 AND lead_score < 80" );
	$raztaifo_low_count    = $wpdb->get_var( "SELECT COUNT(*) FROM {$raztaifo_submissions_table} WHERE {$raztaifo_count_base_where} AND lead_score < 50" );
}

// Spam status counts (considering active lead quality filter if set)
if ( $raztaifo_filter_score !== 'all' ) {
	// Add lead quality condition for spam counts
	$score_condition = '';
	if ( $raztaifo_filter_score === 'high' ) {
		$score_condition = ' AND lead_score >= 80';
	} elseif ( $raztaifo_filter_score === 'medium' ) {
		$score_condition = ' AND lead_score >= 50 AND lead_score < 80';
	} elseif ( $raztaifo_filter_score === 'low' ) {
		$score_condition = ' AND lead_score < 50';
	}

	$raztaifo_spam_count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$raztaifo_submissions_table} WHERE {$raztaifo_count_base_where} AND (spam_score >= %d OR is_spam = 1){$score_condition}",
			$raztaifo_spam_threshold
		)
	);

	$raztaifo_clean_count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$raztaifo_submissions_table} WHERE {$raztaifo_count_base_where} AND (spam_score < %d AND is_spam = 0){$score_condition}",
			$raztaifo_spam_threshold
		)
	);

	$raztaifo_suspicious_count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$raztaifo_submissions_table} WHERE {$raztaifo_count_base_where} AND spam_score >= 40 AND spam_score < %d{$score_condition}",
			$raztaifo_spam_threshold
		)
	);
} else {
	$raztaifo_spam_count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$raztaifo_submissions_table} WHERE {$raztaifo_count_base_where} AND (spam_score >= %d OR is_spam = 1)",
			$raztaifo_spam_threshold
		)
	);

	$raztaifo_clean_count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$raztaifo_submissions_table} WHERE {$raztaifo_count_base_where} AND (spam_score < %d AND is_spam = 0)",
			$raztaifo_spam_threshold
		)
	);

	$raztaifo_suspicious_count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$raztaifo_submissions_table} WHERE {$raztaifo_count_base_where} AND spam_score >= 40 AND spam_score < %d",
			$raztaifo_spam_threshold
		)
	);
}

// Helper function to generate sortable column URLs
function raztaifo_get_sort_url( $column, $current_orderby, $current_order ) {
	$new_order = 'desc';
	$arrow     = '';

	if ( $current_orderby === $column ) {
		// Toggle order if clicking the same column
		$new_order = $current_order === 'desc' ? 'asc' : 'desc';
		$arrow     = $current_order === 'desc' ? ' ▼' : ' ▲';
	}

	$url_params = array(
		'page'    => 'raztech-form-architect-submissions',
		'orderby' => $column,
		'order'   => $new_order,
	);

	// Preserve existing filters and pagination
	if ( isset( $_GET['form_id'] ) && intval( $_GET['form_id'] ) > 0 ) {
		$url_params['form_id'] = intval( $_GET['form_id'] );
	}
	if ( isset( $_GET['score_range'] ) && $_GET['score_range'] !== 'all' ) {
		$url_params['score_range'] = sanitize_text_field( wp_unslash( $_GET['score_range'] ) );
	}
	if ( isset( $_GET['spam_filter'] ) && $_GET['spam_filter'] !== 'all' ) {
		$url_params['spam_filter'] = sanitize_text_field( wp_unslash( $_GET['spam_filter'] ) );
	}
	if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
		$url_params['s'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
	}
	if ( isset( $_GET['paged'] ) && intval( $_GET['paged'] ) > 1 ) {
		$url_params['paged'] = intval( $_GET['paged'] );
	}

	return array(
		'url'   => add_query_arg( $url_params, admin_url( 'admin.php' ) ),
		'arrow' => $arrow,
	);
}

// Helper function to generate pagination URLs
function raztaifo_get_pagination_url( $page_num ) {
	$url_params = array(
		'page'  => 'raztech-form-architect-submissions',
		'paged' => $page_num,
	);

	// Preserve existing filters and sorting
	if ( isset( $_GET['form_id'] ) && intval( $_GET['form_id'] ) > 0 ) {
		$url_params['form_id'] = intval( $_GET['form_id'] );
	}
	if ( isset( $_GET['score_range'] ) && $_GET['score_range'] !== 'all' ) {
		$url_params['score_range'] = sanitize_text_field( wp_unslash( $_GET['score_range'] ) );
	}
	if ( isset( $_GET['spam_filter'] ) && $_GET['spam_filter'] !== 'all' ) {
		$url_params['spam_filter'] = sanitize_text_field( wp_unslash( $_GET['spam_filter'] ) );
	}
	if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
		$url_params['s'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
	}
	if ( isset( $_GET['orderby'] ) ) {
		$url_params['orderby'] = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
	}
	if ( isset( $_GET['order'] ) ) {
		$url_params['order'] = sanitize_text_field( wp_unslash( $_GET['order'] ) );
	}

	return add_query_arg( $url_params, admin_url( 'admin.php' ) );
}
?>

<div class="wrap smartforms-submissions">
	<h1><?php echo esc_html__( 'Form Submissions', 'raztech-form-architect' ); ?></h1>

	<?php if ( isset( $_GET['bulk_action'] ) && $_GET['bulk_action'] === 'success' && isset( $_GET['count'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
				<strong>
					<?php
					$count = intval( $_GET['count'] );
					/* translators: %d: Number of submissions processed */
					echo esc_html(
						sprintf(
							_n(
								'%d submission processed successfully.',
								'%d submissions processed successfully.',
								$count,
								'raztech-form-architect'
							),
							$count
						)
					);
					?>
				</strong>
			</p>
		</div>
	<?php endif; ?>

	<div class="smartforms-filters">
		<form method="get" action="">
			<input type="hidden" name="page" value="raztech-form-architect-submissions" />

			<label for="search-input"><?php echo esc_html__( 'Search:', 'raztech-form-architect' ); ?></label>
			<input type="text" id="search-input" name="s" value="<?php echo esc_attr( $raztaifo_search_query ); ?>" placeholder="<?php echo esc_attr__( 'Search submissions...', 'raztech-form-architect' ); ?>" style="width: 200px;" />

			<label for="form-filter" style="margin-left: 15px;"><?php echo esc_html__( 'Filter by form:', 'raztech-form-architect' ); ?></label>
			<select name="form_id" id="form-filter">
				<option value="0"><?php echo esc_html__( 'All Forms', 'raztech-form-architect' ); ?></option>
				<?php foreach ( $raztaifo_forms as $raztaifo_form ) : ?>
					<option value="<?php echo esc_attr( $raztaifo_form->id ); ?>" <?php selected( $raztaifo_filter_form_id, $raztaifo_form->id ); ?>>
						<?php echo esc_html( $raztaifo_form->form_name ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<label for="score-filter" style="margin-left: 15px;"><?php echo esc_html__( 'Lead Quality:', 'raztech-form-architect' ); ?></label>
			<select name="score_range" id="score-filter">
				<option value="all" <?php selected( $raztaifo_filter_score, 'all' ); ?>>
					<?php
					/* translators: %d: Total count */
					echo esc_html( sprintf( __( 'All Scores (%d)', 'raztech-form-architect' ), $raztaifo_total_count ) );
					?>
				</option>
				<option value="high" <?php selected( $raztaifo_filter_score, 'high' ); ?>>
					<?php
					/* translators: %d: High score count */
					echo esc_html( sprintf( __( 'High (80-100) - %d', 'raztech-form-architect' ), $raztaifo_high_count ) );
					?>
				</option>
				<option value="medium" <?php selected( $raztaifo_filter_score, 'medium' ); ?>>
					<?php
					/* translators: %d: Medium score count */
					echo esc_html( sprintf( __( 'Medium (50-79) - %d', 'raztech-form-architect' ), $raztaifo_medium_count ) );
					?>
				</option>
				<option value="low" <?php selected( $raztaifo_filter_score, 'low' ); ?>>
					<?php
					/* translators: %d: Low score count */
					echo esc_html( sprintf( __( 'Low (0-49) - %d', 'raztech-form-architect' ), $raztaifo_low_count ) );
					?>
				</option>
			</select>

			<label for="spam-filter" style="margin-left: 15px;"><?php echo esc_html__( 'Spam Status:', 'raztech-form-architect' ); ?></label>
			<select name="spam_filter" id="spam-filter">
				<option value="all" <?php selected( $raztaifo_filter_spam, 'all' ); ?>>
					<?php
					/* translators: %d: Total count */
					echo esc_html( sprintf( __( 'All Submissions (%d)', 'raztech-form-architect' ), $raztaifo_total_count ) );
					?>
				</option>
				<option value="spam" <?php selected( $raztaifo_filter_spam, 'spam' ); ?>>
					<?php
					/* translators: 1: Spam threshold value, 2: Spam count */
					echo esc_html( sprintf( __( 'Spam Only (≥%1$d) - %2$d', 'raztech-form-architect' ), $raztaifo_spam_threshold, $raztaifo_spam_count ) );
					?>
				</option>
				<option value="not_spam" <?php selected( $raztaifo_filter_spam, 'not_spam' ); ?>>
					<?php
					/* translators: %d: Clean count */
					echo esc_html( sprintf( __( 'Clean Only - %d', 'raztech-form-architect' ), $raztaifo_clean_count ) );
					?>
				</option>
				<option value="suspicious" <?php selected( $raztaifo_filter_spam, 'suspicious' ); ?>>
					<?php
					/* translators: 1: Upper limit of suspicious range, 2: Suspicious count */
					echo esc_html( sprintf( __( 'Suspicious (40-%1$d) - %2$d', 'raztech-form-architect' ), $raztaifo_spam_threshold - 1, $raztaifo_suspicious_count ) );
					?>
				</option>
			</select>

			<button type="submit" class="button"><?php echo esc_html__( 'Filter', 'raztech-form-architect' ); ?></button>

			<?php if ( $raztaifo_filter_form_id || $raztaifo_filter_score !== 'all' || $raztaifo_filter_spam !== 'all' || ! empty( $raztaifo_search_query ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-submissions' ) ); ?>" class="button">
					<?php echo esc_html__( 'Clear Filters', 'raztech-form-architect' ); ?>
				</a>
			<?php endif; ?>
		</form>

		<!-- PHASE 8: Export Button -->
		<?php if ( ! empty( $raztaifo_submissions ) ) : ?>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=raztech-form-architect-submissions&action=export_csv&form_id=' . $raztaifo_filter_form_id ), 'raztaifo_export_csv' ) ); ?>" class="button button-primary" style="margin-left: 15px;">
				📥 <?php echo esc_html__( 'Export to CSV', 'raztech-form-architect' ); ?>
			</a>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $raztaifo_submissions ) ) : ?>
		<form id="smartforms-bulk-actions-form" method="post">
			<?php wp_nonce_field( 'raztaifo_bulk_actions', 'raztaifo_bulk_nonce' ); ?>
			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<select name="action" id="bulk-action-selector-top">
						<option value="-1"><?php echo esc_html__( 'Bulk Actions', 'raztech-form-architect' ); ?></option>
						<option value="delete"><?php echo esc_html__( 'Delete', 'raztech-form-architect' ); ?></option>
						<option value="mark_spam"><?php echo esc_html__( 'Mark as Spam', 'raztech-form-architect' ); ?></option>
						<option value="mark_clean"><?php echo esc_html__( 'Mark as Clean', 'raztech-form-architect' ); ?></option>
					</select>
					<button type="submit" class="button action"><?php echo esc_html__( 'Apply', 'raztech-form-architect' ); ?></button>
				</div>
			</div>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col" class="manage-column column-cb check-column">
						<input id="cb-select-all-1" type="checkbox" />
					</th>
					<th style="width: 50px;" class="column-id sortable">
						<?php
						$sort_data = raztaifo_get_sort_url( 'id', $raztaifo_orderby, $raztaifo_order );
						?>
						<a href="<?php echo esc_url( $sort_data['url'] ); ?>">
							<span><?php echo esc_html__( 'ID', 'raztech-form-architect' ); ?></span>
							<span class="sorting-indicator"><?php echo esc_html( $sort_data['arrow'] ); ?></span>
						</a>
					</th>
					<th class="column-form sortable">
						<?php
						$sort_data = raztaifo_get_sort_url( 'form_id', $raztaifo_orderby, $raztaifo_order );
						?>
						<a href="<?php echo esc_url( $sort_data['url'] ); ?>">
							<span><?php echo esc_html__( 'Form', 'raztech-form-architect' ); ?></span>
							<span class="sorting-indicator"><?php echo esc_html( $sort_data['arrow'] ); ?></span>
						</a>
					</th>
					<th><?php echo esc_html__( 'Data', 'raztech-form-architect' ); ?></th>
					<th style="width: 100px;" class="column-lead-score sortable">
						<?php
						$sort_data = raztaifo_get_sort_url( 'lead_score', $raztaifo_orderby, $raztaifo_order );
						?>
						<a href="<?php echo esc_url( $sort_data['url'] ); ?>">
							<span><?php echo esc_html__( 'Lead Score', 'raztech-form-architect' ); ?></span>
							<span class="sorting-indicator"><?php echo esc_html( $sort_data['arrow'] ); ?></span>
						</a>
					</th>
					<th style="width: 100px;" class="column-spam sortable">
						<?php
						$sort_data = raztaifo_get_sort_url( 'spam_score', $raztaifo_orderby, $raztaifo_order );
						?>
						<a href="<?php echo esc_url( $sort_data['url'] ); ?>">
							<span><?php echo esc_html__( 'Spam', 'raztech-form-architect' ); ?></span>
							<span class="sorting-indicator"><?php echo esc_html( $sort_data['arrow'] ); ?></span>
						</a>
					</th>
					<th style="width: 150px;" class="column-submitted sortable">
						<?php
						$sort_data = raztaifo_get_sort_url( 'submitted_at', $raztaifo_orderby, $raztaifo_order );
						?>
						<a href="<?php echo esc_url( $sort_data['url'] ); ?>">
							<span><?php echo esc_html__( 'Submitted', 'raztech-form-architect' ); ?></span>
							<span class="sorting-indicator"><?php echo esc_html( $sort_data['arrow'] ); ?></span>
						</a>
					</th>
					<th style="width: 100px;"><?php echo esc_html__( 'Actions', 'raztech-form-architect' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $raztaifo_submissions as $raztaifo_submission ) : ?>
					<?php
					$raztaifo_form = RAZTAIFO_Form_Builder::get_form( $raztaifo_submission->form_id );
					?>
					<tr class="<?php echo esc_attr( $raztaifo_submission->is_spam ? 'smartforms-spam-row' : '' ); ?>">
						<th scope="row" class="check-column">
							<input type="checkbox" name="submission_ids[]" value="<?php echo esc_attr( $raztaifo_submission->id ); ?>" />
						</th>
						<td><?php echo esc_html( $raztaifo_submission->id ); ?></td>
						<td>
							<?php echo $raztaifo_form ? esc_html( $raztaifo_form->form_name ) : esc_html__( 'Unknown', 'raztech-form-architect' ); ?>
						</td>
						<td>
							<button type="button" class="button button-small smartforms-view-submission" data-submission-id="<?php echo esc_attr( $raztaifo_submission->id ); ?>">
								<?php echo esc_html__( 'View Data', 'raztech-form-architect' ); ?>
							</button>

							<!-- Hidden data for modal -->
							<div class="smartforms-submission-data" style="display:none;" data-submission-id="<?php echo esc_attr( $raztaifo_submission->id ); ?>">
								<?php
								if ( ! empty( $raztaifo_submission->submission_data ) && is_array( $raztaifo_submission->submission_data ) ) {
									?>
									<dl class="smartforms-data-list">
									<?php
									foreach ( $raztaifo_submission->submission_data as $key => $value ) {
										?>
										<dt><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?>:</dt>
										<?php
										if ( is_array( $value ) ) {
											?>
											<dd><?php echo esc_html( implode( ', ', $value ) ); ?></dd>
											<?php
										} else {
											?>
											<dd><?php echo esc_html( $value ); ?></dd>
											<?php
										}
									}
									?>
									</dl>
									<?php
								}
								?>
							</div>
						</td>
						<td>
							<?php
							// PHASE 3: Get score color class for visual display
							$score_color = RAZTAIFO_Lead_Scorer::get_score_color( $raztaifo_submission->lead_score );
							$score_category = RAZTAIFO_Lead_Scorer::get_score_category( $raztaifo_submission->lead_score );
							?>
							<span class="smartforms-score-badge smartforms-score-<?php echo esc_attr( $score_color ); ?>" title="<?php echo esc_attr( ucfirst( $score_category ) . ' quality lead' ); ?>">
								<?php echo esc_html( $raztaifo_submission->lead_score ); ?>
							</span>
						</td>
						<td>
							<?php
							// Three-tier spam risk display using configurable threshold
							$spam_score = isset( $raztaifo_submission->spam_score ) ? intval( $raztaifo_submission->spam_score ) : 0;
							$spam_class = '';
							$spam_text  = '';
							$spam_icon  = '';

							if ( $spam_score >= $raztaifo_spam_threshold ) {
								// High risk: SPAM (using configurable threshold)
								$spam_class = 'spam-flagged';
								$spam_text  = __( 'Spam', 'raztech-form-architect' );
								$spam_icon  = '✗';
							} elseif ( $spam_score >= 40 ) {
								// Medium risk: SUSPICIOUS (40 to threshold-1)
								$spam_class = 'spam-review';
								$spam_text  = __( 'Suspicious', 'raztech-form-architect' );
								$spam_icon  = '⚠';
							} else {
								// Low risk: CLEAN
								$spam_class = 'spam-clean';
								$spam_text  = __( 'Clean', 'raztech-form-architect' );
								$spam_icon  = '✓';
							}
							?>
							<span class="spam-badge <?php echo esc_attr( $spam_class ); ?>" title="<?php echo esc_attr( sprintf( __( 'Spam Score: %d', 'raztech-form-architect' ), $spam_score ) ); ?>">
								<span class="spam-icon"><?php echo esc_html( $spam_icon ); ?></span>
								<span class="spam-text"><?php echo esc_html( $spam_text ); ?></span>
							</span>
						</td>
						<td>
							<?php
							echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $raztaifo_submission->submitted_at ) ) );
							?>
							<br>
							<small><?php echo esc_html( human_time_diff( strtotime( $raztaifo_submission->submitted_at ), current_time( 'timestamp' ) ) . ' ago' ); ?></small>
						</td>
						<td>
							<button type="button" class="button button-small smartforms-view-submission" data-submission-id="<?php echo esc_attr( $raztaifo_submission->id ); ?>">
								<?php echo esc_html__( 'View', 'raztech-form-architect' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<div class="smartforms-submissions-footer">
			<div class="smartforms-submissions-info">
				<p>
					<?php
					$start = ( ( $raztaifo_current_page - 1 ) * $raztaifo_per_page ) + 1;
					$end   = min( $raztaifo_current_page * $raztaifo_per_page, $raztaifo_total_items );
					/* translators: 1: Start item number, 2: End item number, 3: Total items */
					echo esc_html( sprintf( __( 'Showing %1$d-%2$d of %3$d submissions', 'raztech-form-architect' ), $start, $end, $raztaifo_total_items ) );
					?>
				</p>
			</div>

			<?php if ( $raztaifo_total_pages > 1 ) : ?>
				<div class="tablenav">
					<div class="tablenav-pages">
						<span class="displaying-num">
							<?php
							/* translators: %s: Number of items */
							echo esc_html( sprintf( _n( '%s item', '%s items', $raztaifo_total_items, 'raztech-form-architect' ), number_format_i18n( $raztaifo_total_items ) ) );
							?>
						</span>
						<span class="pagination-links">
							<?php if ( $raztaifo_current_page > 1 ) : ?>
								<a class="first-page button" href="<?php echo esc_url( raztaifo_get_pagination_url( 1 ) ); ?>">
									<span aria-hidden="true">&laquo;</span>
								</a>
								<a class="prev-page button" href="<?php echo esc_url( raztaifo_get_pagination_url( $raztaifo_current_page - 1 ) ); ?>">
									<span aria-hidden="true">&lsaquo;</span>
								</a>
							<?php else : ?>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>
							<?php endif; ?>

							<span class="paging-input">
								<span class="tablenav-paging-text">
									<?php
									/* translators: 1: Current page, 2: Total pages */
									echo esc_html( sprintf( __( '%1$s of %2$s', 'raztech-form-architect' ), $raztaifo_current_page, $raztaifo_total_pages ) );
									?>
								</span>
							</span>

							<?php if ( $raztaifo_current_page < $raztaifo_total_pages ) : ?>
								<a class="next-page button" href="<?php echo esc_url( raztaifo_get_pagination_url( $raztaifo_current_page + 1 ) ); ?>">
									<span aria-hidden="true">&rsaquo;</span>
								</a>
								<a class="last-page button" href="<?php echo esc_url( raztaifo_get_pagination_url( $raztaifo_total_pages ) ); ?>">
									<span aria-hidden="true">&raquo;</span>
								</a>
							<?php else : ?>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>
								<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>
							<?php endif; ?>
						</span>
					</div>
				</div>
			<?php endif; ?>
		</div>
		</form>
	<?php else : ?>
		<div class="smartforms-empty-state">
			<h2><?php echo esc_html__( 'No submissions yet', 'raztech-form-architect' ); ?></h2>
			<p><?php echo esc_html__( 'Submissions will appear here once users start filling out your forms.', 'raztech-form-architect' ); ?></p>
		</div>
	<?php endif; ?>
</div>

<!-- Modal for viewing submission details -->
<div id="smartforms-submission-modal" class="smartforms-modal" style="display:none;">
	<div class="smartforms-modal-content">
		<span class="smartforms-modal-close">&times;</span>
		<h2><?php echo esc_html__( 'Submission Details', 'raztech-form-architect' ); ?></h2>
		<div id="smartforms-modal-body"></div>
	</div>
</div>
