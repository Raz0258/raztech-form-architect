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
$filter_form_id   = isset( $_GET['form_id'] ) ? intval( $_GET['form_id'] ) : 0;
$filter_score     = isset( $_GET['score_range'] ) ? sanitize_text_field( wp_unslash( $_GET['score_range'] ) ) : 'all';
$filter_spam      = isset( $_GET['spam_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['spam_filter'] ) ) : 'all';
$search_query     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

// Get sorting parameters
$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'submitted_at';
$order   = isset( $_GET['order'] ) && $_GET['order'] === 'asc' ? 'asc' : 'desc';

// Get pagination parameters
$per_page     = 20;
$current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$offset       = ( $current_page - 1 ) * $per_page;

// First, get total count (without pagination)
$count_args = array(
	'orderby' => $orderby,
	'order'   => strtoupper( $order ),
	'limit'   => -1,
	'offset'  => 0,
);

if ( $filter_spam !== 'all' ) {
	$all_submissions = RT_FA_Spam_Detector::get_submissions_by_spam_status( $filter_spam, $filter_form_id, $filter_score, $count_args );
} elseif ( $filter_score !== 'all' ) {
	$all_submissions = RT_FA_Lead_Scorer::get_submissions_by_score( $filter_score, $filter_form_id, $count_args );
} else {
	$all_submissions = RT_FA_Form_Builder::get_submissions( $filter_form_id, $count_args );
}

$total_items = count( $all_submissions );
$total_pages = ceil( $total_items / $per_page );

// Now get paginated submissions
$query_args = array(
	'orderby' => $orderby,
	'order'   => strtoupper( $order ),
	'limit'   => $per_page,
	'offset'  => $offset,
);

if ( $filter_spam !== 'all' ) {
	$submissions = RT_FA_Spam_Detector::get_submissions_by_spam_status( $filter_spam, $filter_form_id, $filter_score, $query_args );
} elseif ( $filter_score !== 'all' ) {
	$submissions = RT_FA_Lead_Scorer::get_submissions_by_score( $filter_score, $filter_form_id, $query_args );
} else {
	$submissions = RT_FA_Form_Builder::get_submissions( $filter_form_id, $query_args );
}

// Debug: Log filter queries (uncomment to debug)
// error_log( '=== Submission Filters Debug ===' );
// error_log( 'Form ID: ' . $filter_form_id );
// error_log( 'Lead Quality Filter: ' . $filter_score );
// error_log( 'Spam Status Filter: ' . $filter_spam );
// error_log( 'Search Query: ' . $search_query );
// error_log( 'Result Count: ' . count( $submissions ) );
// error_log( 'Total Items (before search): ' . $total_items );

// Apply search filter if provided
if ( ! empty( $search_query ) ) {
	$submissions = array_filter(
		$submissions,
		function( $submission ) use ( $search_query ) {
			// Search in submission data
			$search_string = strtolower( $search_query );

			// Search in submission ID
			if ( strpos( strtolower( (string) $submission->id ), $search_string ) !== false ) {
				return true;
			}

			// Search in submission data
			if ( is_array( $submission->submission_data ) ) {
				$data_string = strtolower( wp_json_encode( $submission->submission_data ) );
				if ( strpos( $data_string, $search_string ) !== false ) {
					return true;
				}
			}

			// Search in IP address
			if ( isset( $submission->ip_address ) && strpos( strtolower( $submission->ip_address ), $search_string ) !== false ) {
				return true;
			}

			return false;
		}
	);

	// Reindex array after filtering
	$submissions = array_values( $submissions );

	// Recalculate totals after search
	$total_items = count( $submissions );
	$total_pages = ceil( $total_items / $per_page );

	// Re-paginate after search
	$submissions = array_slice( $submissions, $offset, $per_page );
}

// Get all forms for filter dropdown
$forms = RT_FA_Form_Builder::get_forms();

// Get spam threshold from settings for consistent display
$spam_threshold = get_option( 'rt_fa_spam_threshold', 60 );

// Get accurate filter counts for dropdown (considering active filters)
global $wpdb;
$submissions_table = $wpdb->prefix . 'rt_fa_submissions';

// Build base WHERE clause for counts (include form and search filters if active)
$count_where_parts = array( '1=1' );

// Add form filter if active
if ( $filter_form_id > 0 ) {
	$count_where_parts[] = $wpdb->prepare( 'form_id = %d', $filter_form_id );
}

// Base WHERE clause for all counts
$count_base_where = implode( ' AND ', $count_where_parts );

// Total count (with form filter applied)
$total_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$submissions_table} WHERE {$count_base_where}" );

// Lead quality counts (considering active spam filter if set)
if ( $filter_spam !== 'all' ) {
	// Add spam filter condition for lead quality counts
	$spam_condition = '';
	if ( $filter_spam === 'spam' ) {
		$spam_condition = $wpdb->prepare( ' AND (spam_score >= %d OR is_spam = 1)', $spam_threshold );
	} elseif ( $filter_spam === 'not_spam' ) {
		$spam_condition = $wpdb->prepare( ' AND (spam_score < %d AND is_spam = 0)', $spam_threshold );
	} elseif ( $filter_spam === 'suspicious' ) {
		$spam_condition = $wpdb->prepare( ' AND spam_score >= 40 AND spam_score < %d', $spam_threshold );
	}

	$high_count   = $wpdb->get_var( "SELECT COUNT(*) FROM {$submissions_table} WHERE {$count_base_where} AND lead_score >= 80{$spam_condition}" );
	$medium_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$submissions_table} WHERE {$count_base_where} AND lead_score >= 50 AND lead_score < 80{$spam_condition}" );
	$low_count    = $wpdb->get_var( "SELECT COUNT(*) FROM {$submissions_table} WHERE {$count_base_where} AND lead_score < 50{$spam_condition}" );
} else {
	$high_count   = $wpdb->get_var( "SELECT COUNT(*) FROM {$submissions_table} WHERE {$count_base_where} AND lead_score >= 80" );
	$medium_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$submissions_table} WHERE {$count_base_where} AND lead_score >= 50 AND lead_score < 80" );
	$low_count    = $wpdb->get_var( "SELECT COUNT(*) FROM {$submissions_table} WHERE {$count_base_where} AND lead_score < 50" );
}

// Spam status counts (considering active lead quality filter if set)
if ( $filter_score !== 'all' ) {
	// Add lead quality condition for spam counts
	$score_condition = '';
	if ( $filter_score === 'high' ) {
		$score_condition = ' AND lead_score >= 80';
	} elseif ( $filter_score === 'medium' ) {
		$score_condition = ' AND lead_score >= 50 AND lead_score < 80';
	} elseif ( $filter_score === 'low' ) {
		$score_condition = ' AND lead_score < 50';
	}

	$spam_count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$submissions_table} WHERE {$count_base_where} AND (spam_score >= %d OR is_spam = 1){$score_condition}",
			$spam_threshold
		)
	);

	$clean_count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$submissions_table} WHERE {$count_base_where} AND (spam_score < %d AND is_spam = 0){$score_condition}",
			$spam_threshold
		)
	);

	$suspicious_count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$submissions_table} WHERE {$count_base_where} AND spam_score >= 40 AND spam_score < %d{$score_condition}",
			$spam_threshold
		)
	);
} else {
	$spam_count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$submissions_table} WHERE {$count_base_where} AND (spam_score >= %d OR is_spam = 1)",
			$spam_threshold
		)
	);

	$clean_count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$submissions_table} WHERE {$count_base_where} AND (spam_score < %d AND is_spam = 0)",
			$spam_threshold
		)
	);

	$suspicious_count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$submissions_table} WHERE {$count_base_where} AND spam_score >= 40 AND spam_score < %d",
			$spam_threshold
		)
	);
}

// Helper function to generate sortable column URLs
function rt_fa_get_sort_url( $column, $current_orderby, $current_order ) {
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
function rt_fa_get_pagination_url( $page_num ) {
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
			<input type="text" id="search-input" name="s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php echo esc_attr__( 'Search submissions...', 'raztech-form-architect' ); ?>" style="width: 200px;" />

			<label for="form-filter" style="margin-left: 15px;"><?php echo esc_html__( 'Filter by form:', 'raztech-form-architect' ); ?></label>
			<select name="form_id" id="form-filter">
				<option value="0"><?php echo esc_html__( 'All Forms', 'raztech-form-architect' ); ?></option>
				<?php foreach ( $forms as $form ) : ?>
					<option value="<?php echo esc_attr( $form->id ); ?>" <?php selected( $filter_form_id, $form->id ); ?>>
						<?php echo esc_html( $form->form_name ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<label for="score-filter" style="margin-left: 15px;"><?php echo esc_html__( 'Lead Quality:', 'raztech-form-architect' ); ?></label>
			<select name="score_range" id="score-filter">
				<option value="all" <?php selected( $filter_score, 'all' ); ?>>
					<?php
					/* translators: %d: Total count */
					echo esc_html( sprintf( __( 'All Scores (%d)', 'raztech-form-architect' ), $total_count ) );
					?>
				</option>
				<option value="high" <?php selected( $filter_score, 'high' ); ?>>
					<?php
					/* translators: %d: High score count */
					echo esc_html( sprintf( __( 'High (80-100) - %d', 'raztech-form-architect' ), $high_count ) );
					?>
				</option>
				<option value="medium" <?php selected( $filter_score, 'medium' ); ?>>
					<?php
					/* translators: %d: Medium score count */
					echo esc_html( sprintf( __( 'Medium (50-79) - %d', 'raztech-form-architect' ), $medium_count ) );
					?>
				</option>
				<option value="low" <?php selected( $filter_score, 'low' ); ?>>
					<?php
					/* translators: %d: Low score count */
					echo esc_html( sprintf( __( 'Low (0-49) - %d', 'raztech-form-architect' ), $low_count ) );
					?>
				</option>
			</select>

			<label for="spam-filter" style="margin-left: 15px;"><?php echo esc_html__( 'Spam Status:', 'raztech-form-architect' ); ?></label>
			<select name="spam_filter" id="spam-filter">
				<option value="all" <?php selected( $filter_spam, 'all' ); ?>>
					<?php
					/* translators: %d: Total count */
					echo esc_html( sprintf( __( 'All Submissions (%d)', 'raztech-form-architect' ), $total_count ) );
					?>
				</option>
				<option value="spam" <?php selected( $filter_spam, 'spam' ); ?>>
					<?php
					/* translators: 1: Spam threshold value, 2: Spam count */
					echo esc_html( sprintf( __( 'Spam Only (≥%1$d) - %2$d', 'raztech-form-architect' ), $spam_threshold, $spam_count ) );
					?>
				</option>
				<option value="not_spam" <?php selected( $filter_spam, 'not_spam' ); ?>>
					<?php
					/* translators: %d: Clean count */
					echo esc_html( sprintf( __( 'Clean Only - %d', 'raztech-form-architect' ), $clean_count ) );
					?>
				</option>
				<option value="suspicious" <?php selected( $filter_spam, 'suspicious' ); ?>>
					<?php
					/* translators: 1: Upper limit of suspicious range, 2: Suspicious count */
					echo esc_html( sprintf( __( 'Suspicious (40-%1$d) - %2$d', 'raztech-form-architect' ), $spam_threshold - 1, $suspicious_count ) );
					?>
				</option>
			</select>

			<button type="submit" class="button"><?php echo esc_html__( 'Filter', 'raztech-form-architect' ); ?></button>

			<?php if ( $filter_form_id || $filter_score !== 'all' || $filter_spam !== 'all' || ! empty( $search_query ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=raztech-form-architect-submissions' ) ); ?>" class="button">
					<?php echo esc_html__( 'Clear Filters', 'raztech-form-architect' ); ?>
				</a>
			<?php endif; ?>
		</form>

		<!-- PHASE 8: Export Button -->
		<?php if ( ! empty( $submissions ) ) : ?>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=raztech-form-architect-submissions&action=export_csv&form_id=' . $filter_form_id ), 'rt_fa_export_csv' ) ); ?>" class="button button-primary" style="margin-left: 15px;">
				📥 <?php echo esc_html__( 'Export to CSV', 'raztech-form-architect' ); ?>
			</a>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $submissions ) ) : ?>
		<form id="smartforms-bulk-actions-form" method="post">
			<?php wp_nonce_field( 'rt_fa_bulk_actions', 'rt_fa_bulk_nonce' ); ?>
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
						$sort_data = rt_fa_get_sort_url( 'id', $orderby, $order );
						?>
						<a href="<?php echo esc_url( $sort_data['url'] ); ?>">
							<span><?php echo esc_html__( 'ID', 'raztech-form-architect' ); ?></span>
							<span class="sorting-indicator"><?php echo esc_html( $sort_data['arrow'] ); ?></span>
						</a>
					</th>
					<th class="column-form sortable">
						<?php
						$sort_data = rt_fa_get_sort_url( 'form_id', $orderby, $order );
						?>
						<a href="<?php echo esc_url( $sort_data['url'] ); ?>">
							<span><?php echo esc_html__( 'Form', 'raztech-form-architect' ); ?></span>
							<span class="sorting-indicator"><?php echo esc_html( $sort_data['arrow'] ); ?></span>
						</a>
					</th>
					<th><?php echo esc_html__( 'Data', 'raztech-form-architect' ); ?></th>
					<th style="width: 100px;" class="column-lead-score sortable">
						<?php
						$sort_data = rt_fa_get_sort_url( 'lead_score', $orderby, $order );
						?>
						<a href="<?php echo esc_url( $sort_data['url'] ); ?>">
							<span><?php echo esc_html__( 'Lead Score', 'raztech-form-architect' ); ?></span>
							<span class="sorting-indicator"><?php echo esc_html( $sort_data['arrow'] ); ?></span>
						</a>
					</th>
					<th style="width: 100px;" class="column-spam sortable">
						<?php
						$sort_data = rt_fa_get_sort_url( 'spam_score', $orderby, $order );
						?>
						<a href="<?php echo esc_url( $sort_data['url'] ); ?>">
							<span><?php echo esc_html__( 'Spam', 'raztech-form-architect' ); ?></span>
							<span class="sorting-indicator"><?php echo esc_html( $sort_data['arrow'] ); ?></span>
						</a>
					</th>
					<th style="width: 150px;" class="column-submitted sortable">
						<?php
						$sort_data = rt_fa_get_sort_url( 'submitted_at', $orderby, $order );
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
				<?php foreach ( $submissions as $submission ) : ?>
					<?php
					$form = RT_FA_Form_Builder::get_form( $submission->form_id );
					?>
					<tr class="<?php echo $submission->is_spam ? 'smartforms-spam-row' : ''; ?>">
						<th scope="row" class="check-column">
							<input type="checkbox" name="submission_ids[]" value="<?php echo esc_attr( $submission->id ); ?>" />
						</th>
						<td><?php echo esc_html( $submission->id ); ?></td>
						<td>
							<?php echo $form ? esc_html( $form->form_name ) : esc_html__( 'Unknown', 'raztech-form-architect' ); ?>
						</td>
						<td>
							<button type="button" class="button button-small smartforms-view-submission" data-submission-id="<?php echo esc_attr( $submission->id ); ?>">
								<?php echo esc_html__( 'View Data', 'raztech-form-architect' ); ?>
							</button>

							<!-- Hidden data for modal -->
							<div class="smartforms-submission-data" style="display:none;" data-submission-id="<?php echo esc_attr( $submission->id ); ?>">
								<?php
								if ( ! empty( $submission->submission_data ) && is_array( $submission->submission_data ) ) {
									echo '<dl class="smartforms-data-list">';
									foreach ( $submission->submission_data as $key => $value ) {
										echo '<dt>' . esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ) . ':</dt>';
										if ( is_array( $value ) ) {
											echo '<dd>' . esc_html( implode( ', ', $value ) ) . '</dd>';
										} else {
											echo '<dd>' . esc_html( $value ) . '</dd>';
										}
									}
									echo '</dl>';
								}
								?>
							</div>
						</td>
						<td>
							<?php
							// PHASE 3: Get score color class for visual display
							$score_color = RT_FA_Lead_Scorer::get_score_color( $submission->lead_score );
							$score_category = RT_FA_Lead_Scorer::get_score_category( $submission->lead_score );
							?>
							<span class="smartforms-score-badge smartforms-score-<?php echo esc_attr( $score_color ); ?>" title="<?php echo esc_attr( ucfirst( $score_category ) . ' quality lead' ); ?>">
								<?php echo esc_html( $submission->lead_score ); ?>
							</span>
						</td>
						<td>
							<?php
							// Three-tier spam risk display using configurable threshold
							$spam_score = isset( $submission->spam_score ) ? intval( $submission->spam_score ) : 0;
							$spam_class = '';
							$spam_text  = '';
							$spam_icon  = '';

							if ( $spam_score >= $spam_threshold ) {
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
								<span class="spam-icon"><?php echo $spam_icon; ?></span>
								<span class="spam-text"><?php echo esc_html( $spam_text ); ?></span>
							</span>
						</td>
						<td>
							<?php
							echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $submission->submitted_at ) ) );
							?>
							<br>
							<small><?php echo esc_html( human_time_diff( strtotime( $submission->submitted_at ), current_time( 'timestamp' ) ) . ' ago' ); ?></small>
						</td>
						<td>
							<button type="button" class="button button-small smartforms-view-submission" data-submission-id="<?php echo esc_attr( $submission->id ); ?>">
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
					$start = ( ( $current_page - 1 ) * $per_page ) + 1;
					$end   = min( $current_page * $per_page, $total_items );
					/* translators: 1: Start item number, 2: End item number, 3: Total items */
					echo esc_html( sprintf( __( 'Showing %1$d-%2$d of %3$d submissions', 'raztech-form-architect' ), $start, $end, $total_items ) );
					?>
				</p>
			</div>

			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav">
					<div class="tablenav-pages">
						<span class="displaying-num">
							<?php
							/* translators: %s: Number of items */
							echo esc_html( sprintf( _n( '%s item', '%s items', $total_items, 'raztech-form-architect' ), number_format_i18n( $total_items ) ) );
							?>
						</span>
						<span class="pagination-links">
							<?php if ( $current_page > 1 ) : ?>
								<a class="first-page button" href="<?php echo esc_url( rt_fa_get_pagination_url( 1 ) ); ?>">
									<span aria-hidden="true">&laquo;</span>
								</a>
								<a class="prev-page button" href="<?php echo esc_url( rt_fa_get_pagination_url( $current_page - 1 ) ); ?>">
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
									echo esc_html( sprintf( __( '%1$s of %2$s', 'raztech-form-architect' ), $current_page, $total_pages ) );
									?>
								</span>
							</span>

							<?php if ( $current_page < $total_pages ) : ?>
								<a class="next-page button" href="<?php echo esc_url( rt_fa_get_pagination_url( $current_page + 1 ) ); ?>">
									<span aria-hidden="true">&rsaquo;</span>
								</a>
								<a class="last-page button" href="<?php echo esc_url( rt_fa_get_pagination_url( $total_pages ) ); ?>">
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
