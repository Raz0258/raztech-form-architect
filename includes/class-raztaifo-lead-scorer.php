<?php
/**
 * AI Lead Scorer
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/includes
 */

/**
 * AI Lead Scorer class.
 *
 * Handles intelligent lead scoring based on submission quality indicators.
 * Analyzes email domain, response completeness, content quality, and business indicators.
 */
class RAZTAIFO_Lead_Scorer {

	/**
	 * Calculate lead score for a submission
	 *
	 * New Balanced Scoring System (Total: 100 points)
	 *
	 * TIER 1: Essential Fields (40 points)
	 * - Valid email address: +20 points
	 * - Full name provided: +10 points
	 * - Contact method (phone OR message): +10 points
	 *
	 * TIER 2: Quality Signals (30 points)
	 * - Phone number provided: +10 points
	 * - Detailed message (>20 chars): +10 points
	 * - Multiple selections (checkboxes): +10 points
	 *
	 * TIER 3: Business Indicators (20 points)
	 * - Company name provided: +10 points
	 * - Business email domain: +5 points
	 * - Professional keywords: +5 points
	 *
	 * TIER 4: Engagement (10 points)
	 * - Long message (>100 chars): +5 points
	 * - Preferred contact specified: +5 points
	 *
	 * @since    1.0.0
	 * @param    int   $submission_id   Submission ID.
	 * @param    array $submission_data Form data.
	 * @param    int   $form_id         Form ID.
	 * @return   int                    Score 0-100.
	 */
	public static function calculate_score( $submission_id, $submission_data, $form_id ) {
		$score = 0;

		// TIER 1: Essential Fields (40 points)
		$score += self::score_essential_fields( $submission_data );

		// TIER 2: Quality Signals (30 points)
		$score += self::score_quality_signals( $submission_data );

		// TIER 3: Business Indicators (20 points)
		$score += self::score_business_indicators( $submission_data );

		// TIER 4: Engagement (10 points)
		$score += self::score_engagement( $submission_data );

		// Ensure score is within valid range
		$score = max( 0, min( $score, 100 ) );

		return intval( $score );
	}

	/**
	 * TIER 1: Score essential fields (40 points total)
	 *
	 * - Valid email address: +20 points (required)
	 * - Full name provided: +10 points
	 * - Contact method (phone OR message): +10 points
	 *
	 * @since    1.0.0
	 * @param    array $data Submission data.
	 * @return   int         Score 0-40.
	 */
	private static function score_essential_fields( $data ) {
		$score = 0;

		// 1. Email validation (20 points)
		$email = self::extract_email( $data );
		if ( ! empty( $email ) && filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			$score += 20;
		}

		// 2. Name field (10 points)
		$name_found = false;
		foreach ( $data as $key => $value ) {
			$key_lower = strtolower( $key );
			if ( ( strpos( $key_lower, 'name' ) !== false ||
				   strpos( $key_lower, 'full_name' ) !== false ||
				   strpos( $key_lower, 'fullname' ) !== false ) &&
				 ! empty( $value ) && is_string( $value ) && strlen( trim( $value ) ) > 2 ) {
				$score += 10;
				$name_found = true;
				break;
			}
		}

		// 3. Contact method - phone OR message (10 points)
		$has_phone   = false;
		$has_message = false;

		foreach ( $data as $key => $value ) {
			$key_lower = strtolower( $key );

			// Check for phone
			if ( ! $has_phone && ( strpos( $key_lower, 'phone' ) !== false ||
				 strpos( $key_lower, 'tel' ) !== false ||
				 strpos( $key_lower, 'mobile' ) !== false ) &&
				 ! empty( $value ) && is_string( $value ) && strlen( trim( $value ) ) > 5 ) {
				$has_phone = true;
			}

			// Check for message/comment
			if ( ! $has_message && ( strpos( $key_lower, 'message' ) !== false ||
				 strpos( $key_lower, 'comment' ) !== false ||
				 strpos( $key_lower, 'description' ) !== false ||
				 strpos( $key_lower, 'details' ) !== false ) &&
				 ! empty( $value ) && is_string( $value ) && strlen( trim( $value ) ) > 5 ) {
				$has_message = true;
			}
		}

		if ( $has_phone || $has_message ) {
			$score += 10;
		}

		return $score;
	}

	/**
	 * TIER 2: Score quality signals (30 points total)
	 *
	 * - Phone number provided: +10 points
	 * - Detailed message (>20 chars): +10 points
	 * - Multiple selections (checkboxes): +10 points
	 *
	 * @since    1.0.0
	 * @param    array $data Submission data.
	 * @return   int         Score 0-30.
	 */
	private static function score_quality_signals( $data ) {
		$score = 0;

		// 1. Phone number provided (10 points)
		$has_phone = false;
		foreach ( $data as $key => $value ) {
			$key_lower = strtolower( $key );
			if ( ( strpos( $key_lower, 'phone' ) !== false ||
				 strpos( $key_lower, 'tel' ) !== false ||
				 strpos( $key_lower, 'mobile' ) !== false ) &&
				 ! empty( $value ) && is_string( $value ) && strlen( trim( $value ) ) > 5 ) {
				$score += 10;
				$has_phone = true;
				break;
			}
		}

		// 2. Detailed message (>20 chars) (10 points)
		$has_detailed_message = false;
		foreach ( $data as $key => $value ) {
			$key_lower = strtolower( $key );
			if ( ( strpos( $key_lower, 'message' ) !== false ||
				 strpos( $key_lower, 'comment' ) !== false ||
				 strpos( $key_lower, 'description' ) !== false ||
				 strpos( $key_lower, 'details' ) !== false ) &&
				 ! empty( $value ) && is_string( $value ) && strlen( trim( $value ) ) > 20 ) {
				$score += 10;
				$has_detailed_message = true;
				break;
			}
		}

		// 3. Multiple selections from checkboxes (10 points)
		$has_multiple_selections = false;
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) && count( $value ) > 0 ) {
				$score += 10;
				$has_multiple_selections = true;
				break;
			}
		}

		return $score;
	}

	/**
	 * TIER 4: Score engagement signals (10 points total)
	 *
	 * - Long message (>100 chars): +5 points
	 * - Preferred contact method specified: +5 points
	 *
	 * @since    1.0.0
	 * @param    array $data Submission data.
	 * @return   int         Score 0-10.
	 */
	private static function score_engagement( $data ) {
		$score = 0;

		// 1. Long message (>100 chars) (5 points)
		$has_long_message = false;
		foreach ( $data as $key => $value ) {
			$key_lower = strtolower( $key );
			if ( ( strpos( $key_lower, 'message' ) !== false ||
				 strpos( $key_lower, 'comment' ) !== false ||
				 strpos( $key_lower, 'description' ) !== false ||
				 strpos( $key_lower, 'details' ) !== false ) &&
				 ! empty( $value ) && is_string( $value ) && strlen( trim( $value ) ) > 100 ) {
				$score += 5;
				$has_long_message = true;
				break;
			}
		}

		// 2. Preferred contact method specified (5 points)
		$has_contact_preference = false;
		foreach ( $data as $key => $value ) {
			$key_lower = strtolower( $key );
			if ( ( strpos( $key_lower, 'prefer' ) !== false ||
				 strpos( $key_lower, 'contact' ) !== false ||
				 strpos( $key_lower, 'reach' ) !== false ||
				 strpos( $key_lower, 'best_time' ) !== false ||
				 strpos( $key_lower, 'availability' ) !== false ) &&
				 ! empty( $value ) ) {
				$score += 5;
				$has_contact_preference = true;
				break;
			}
		}

		return $score;
	}

	/**
	 * TIER 3: Score business indicators (20 points total)
	 *
	 * - Company name provided: +10 points
	 * - Business email domain: +5 points
	 * - Professional keywords: +5 points
	 *
	 * @since    1.0.0
	 * @param    array $data Submission data.
	 * @return   int         Score 0-20.
	 */
	private static function score_business_indicators( $data ) {
		$score = 0;

		// 1. Company name provided (10 points)
		$has_company = false;
		foreach ( $data as $key => $value ) {
			$key_lower = strtolower( $key );
			if ( ( strpos( $key_lower, 'company' ) !== false ||
				   strpos( $key_lower, 'organization' ) !== false ||
				   strpos( $key_lower, 'business' ) !== false ) &&
				 ! empty( $value ) && is_string( $value ) && strlen( trim( $value ) ) > 2 ) {
				$score += 10;
				$has_company = true;
				break;
			}
		}

		// 2. Business email domain (5 points)
		$email = self::extract_email( $data );
		if ( ! empty( $email ) && filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			$domain = substr( strrchr( $email, '@' ), 1 );

			// Personal email providers
			$personal_domains = array(
				'gmail.com',
				'yahoo.com',
				'hotmail.com',
				'outlook.com',
				'aol.com',
				'icloud.com',
				'live.com',
				'msn.com',
				'ymail.com',
				'mail.com',
				'protonmail.com',
				'zoho.com',
			);

			// Disposable email providers
			$disposable_domains = array(
				'tempmail.com',
				'10minutemail.com',
				'guerrillamail.com',
				'mailinator.com',
				'throwaway.email',
				'temp-mail.org',
				'fakeinbox.com',
				'trashmail.com',
				'getnada.com',
				'maildrop.cc',
			);

			// Business email = not personal and not disposable
			if ( ! in_array( strtolower( $domain ), $personal_domains, true ) &&
				 ! in_array( strtolower( $domain ), $disposable_domains, true ) ) {
				$score += 5;
			}
		}

		// 3. Professional keywords in content (5 points)
		// Flatten array values to handle checkboxes and multi-selects
		$flattened_data = array_map(
			function( $value ) {
				if ( is_array( $value ) ) {
					return implode( ', ', $value );
				} elseif ( is_string( $value ) || is_numeric( $value ) ) {
					return (string) $value;
				} else {
					return '';
				}
			},
			$data
		);

		$text_content = strtolower( implode( ' ', $flattened_data ) );

		$business_keywords = array(
			'company',
			'business',
			'organization',
			'corporation',
			'enterprise',
			'firm',
			'agency',
			'professional',
			'commercial',
			'b2b',
		);

		foreach ( $business_keywords as $keyword ) {
			if ( strpos( $text_content, $keyword ) !== false ) {
				$score += 5;
				break; // Only count once
			}
		}

		return $score;
	}

	/**
	 * Extract email from submission data
	 *
	 * Looks for email field by name or validates email format.
	 *
	 * @since    1.0.0
	 * @param    array $data Submission data.
	 * @return   string      Email address or empty string.
	 */
	private static function extract_email( $data ) {
		// First, check for fields with 'email' in the name
		foreach ( $data as $key => $value ) {
			if ( stripos( $key, 'email' ) !== false && is_string( $value ) ) {
				return $value;
			}
		}

		// Fallback: check all values for valid email format
		foreach ( $data as $value ) {
			if ( is_string( $value ) && filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * Get score category
	 *
	 * Updated to match new scoring system:
	 * - High: 70-100 (all/most fields filled)
	 * - Medium: 50-69 (core fields filled)
	 * - Low: 0-49 (minimal fields or spam-like)
	 *
	 * @since    1.0.0
	 * @param    int $score Lead score.
	 * @return   string     Category: 'high', 'medium', or 'low'.
	 */
	public static function get_score_category( $score ) {
		if ( $score >= 70 ) {
			return 'high';
		} elseif ( $score >= 50 ) {
			return 'medium';
		} else {
			return 'low';
		}
	}

	/**
	 * Get score color for display
	 *
	 * Updated thresholds:
	 * - 70-100: Green (high quality)
	 * - 60-69: Light green (medium-high)
	 * - 50-59: Yellow (medium)
	 * - 40-49: Orange (low)
	 * - 0-39: Red (very low/spam)
	 *
	 * @since    1.0.0
	 * @param    int $score Lead score.
	 * @return   string     CSS class suffix.
	 */
	public static function get_score_color( $score ) {
		if ( $score >= 70 ) {
			return 'high'; // Green
		} elseif ( $score >= 60 ) {
			return 'medium-high'; // Light green
		} elseif ( $score >= 50 ) {
			return 'medium'; // Yellow
		} elseif ( $score >= 40 ) {
			return 'low'; // Orange
		} else {
			return 'very-low'; // Red
		}
	}

	/**
	 * Get submissions by score range
	 *
	 * @since    1.0.0
	 * @param    string $range Score range: 'all', 'high', 'medium', 'low'.
	 * @param    int    $form_id Optional form ID filter.
	 * @return   array           Submissions.
	 */
	public static function get_submissions_by_score( $range = 'all', $form_id = 0, $args = array() ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'raztaifo_submissions';

		// Sanitize and validate range parameter
		$range          = sanitize_text_field( $range );
		$allowed_ranges = array( 'all', 'high', 'medium', 'low' );
		if ( ! in_array( $range, $allowed_ranges, true ) ) {
			$range = 'all';
		}

		// Parse query arguments
		$defaults = array(
			'orderby' => 'submitted_at',
			'order'   => 'DESC',
			'limit'   => -1,
			'offset'  => 0,
		);
		$args     = wp_parse_args( $args, $defaults );

		// Whitelist valid orderby columns
		$allowed_orderby = array( 'id', 'form_id', 'submitted_at', 'lead_score', 'spam_score', 'is_spam' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'submitted_at';

		// Validate order direction
		$order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		$limit   = intval( $args['limit'] );
		$offset  = intval( $args['offset'] );

		// Build WHERE clause
		$where_clauses = array();

		if ( $form_id > 0 ) {
			$where_clauses[] = $wpdb->prepare( 'form_id = %d', $form_id );
		}

		// Add score range filter
		switch ( $range ) {
			case 'high':
				$where_clauses[] = 'lead_score >= 80';
				break;
			case 'medium':
				$where_clauses[] = 'lead_score >= 50 AND lead_score < 80';
				break;
			case 'low':
				$where_clauses[] = 'lead_score < 50';
				break;
			case 'all':
			default:
				// No score filter
				break;
		}

		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );
		}

		$query = "SELECT * FROM $table_name $where_sql ORDER BY $orderby $order";

		if ( $limit > 0 ) {
			$query .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $limit, $offset );
		}

		$submissions = $wpdb->get_results( $query );

		// Decode JSON data
		foreach ( $submissions as $submission ) {
			$submission->submission_data = json_decode( $submission->submission_data, true );
		}

		return $submissions;
	}

	/**
	 * Get average lead score efficiently using SQL AVG()
	 *
	 * @since    1.0.0
	 * @param    int $form_id Optional form ID filter.
	 * @return   int          Average score rounded to nearest integer.
	 */
	public static function get_average_score( $form_id = 0 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'raztaifo_submissions';

		if ( $form_id > 0 ) {
			$average = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT AVG(lead_score) FROM {$wpdb->prefix}raztaifo_submissions WHERE form_id = %d",
					$form_id
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe, comes from $wpdb->prefix
			$average = $wpdb->get_var( "SELECT AVG(lead_score) FROM {$wpdb->prefix}raztaifo_submissions" );
		}

		return $average ? intval( round( $average ) ) : 0;
	}
}
