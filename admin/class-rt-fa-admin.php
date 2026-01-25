<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/admin
 */

/**
 * The admin-specific functionality of the plugin.
 */
class RT_FA_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string $plugin_name The name of this plugin.
	 * @param    string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Handle form actions before any output
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function handle_form_actions() {
		// Only run in admin
		if ( ! is_admin() ) {
			return;
		}

		// Handle form deletion on forms page
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'raztech-form-architect-forms' ) {
			if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['form_id'] ) ) {
				// Verify nonce
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_form_' . intval( $_GET['form_id'] ) ) ) {
					wp_die( esc_html__( 'Security check failed.', 'raztech-form-architect' ) );
				}

				// Check permissions
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'raztech-form-architect' ) );
				}

				$form_id = intval( $_GET['form_id'] );
				RT_FA_Form_Builder::delete_form( $form_id );

				// Redirect
				wp_safe_redirect( admin_url( 'admin.php?page=raztech-form-architect-forms&deleted=1' ) );
				exit;
			}
		}

		// Handle CSV export on submissions page
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'raztech-form-architect-submissions' ) {
			if ( isset( $_GET['action'] ) && $_GET['action'] === 'export_csv' ) {
				// Check permissions
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( esc_html__( 'You do not have permission to export submissions.', 'raztech-form-architect' ) );
				}

				$form_id = isset( $_GET['form_id'] ) ? intval( $_GET['form_id'] ) : 0;
				RT_FA_Export::export_submissions_csv( $form_id );
				exit; // Stop execution after export
			}
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			RT_FA_URL . 'admin/css/admin-styles.css',
			array(),
			$this->version,
			'all'
		);

		// Enqueue welcome page styles
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'raztech-form-architect-welcome' ) {
			wp_enqueue_style(
				$this->plugin_name . '-welcome',
				RT_FA_URL . 'admin/css/welcome.css',
				array(),
				$this->version,
				'all'
			);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name . '-admin',
			RT_FA_URL . 'admin/js/admin-dashboard.js',
			array( 'jquery' ),
			$this->version,
			false
		);

		wp_enqueue_script(
			$this->plugin_name . '-form-builder',
			RT_FA_URL . 'admin/js/form-builder.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			$this->version,
			false
		);

		// PHASE 2: Enqueue AI generator script
		wp_enqueue_script(
			$this->plugin_name . '-ai-generator',
			RT_FA_URL . 'admin/js/ai-generator.js',
			array( 'jquery', $this->plugin_name . '-form-builder' ),
			$this->version,
			false
		);

		// PHASE 7: Enqueue Chart.js for analytics dashboard
		wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
			array(),
			'4.4.0',
			true
		);

		// PHASE 7: Enqueue analytics script
		wp_enqueue_script(
			$this->plugin_name . '-analytics',
			RT_FA_URL . 'admin/js/analytics.js',
			array( 'jquery', 'chartjs' ),
			$this->version,
			true
		);

		// Localize script for admin dashboard
		wp_localize_script(
			$this->plugin_name . '-admin',
			'smartformsAjax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'rt_fa_admin_nonce' ),
			)
		);

		// PHASE 2: Localize script for AI generator
		wp_localize_script(
			$this->plugin_name . '-ai-generator',
			'smartformsAjax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'rt_fa_admin_nonce' ),
			)
		);

		// Add AI example prompts data
		wp_localize_script(
			$this->plugin_name . '-ai-generator',
			'rt_faAIExamples',
			array(
				'contact'      => __( 'Create a professional contact form for a business with fields for full name, email address, phone number, company name, and a message textarea. Make name, email, and message required.', 'raztech-form-architect' ),
				'registration' => __( 'Create an event registration form with attendee name, email, phone, ticket type dropdown (General Admission, VIP, Student), dietary restrictions as radio buttons (None, Vegetarian, Vegan, Gluten-Free), and a terms agreement checkbox.', 'raztech-form-architect' ),
				'survey'       => __( 'Create a customer satisfaction survey with rating questions using radio buttons (Very Satisfied, Satisfied, Neutral, Dissatisfied, Very Dissatisfied), a multiple choice question about how they heard about us (checkbox with options: Google, Social Media, Friend, Advertisement), and a comments textarea.', 'raztech-form-architect' ),
				'booking'      => __( 'Create an appointment booking form for a medical clinic with patient name, email, phone, preferred date, preferred time slot dropdown (Morning 9-12, Afternoon 12-3, Evening 3-6), reason for visit dropdown (General Checkup, Follow-up, New Patient, Emergency), and additional notes.', 'raztech-form-architect' ),
				'feedback'     => __( 'Create a product feedback form with customer name, email, product purchased dropdown (Product A, Product B, Product C), rating from 1-5 stars using radio buttons, what they liked (textarea), what could be improved (textarea), and whether they\'d recommend to others (Yes/No radio).', 'raztech-form-architect' ),
				'quote'        => __( 'Create a quote request form for a web design agency with business name, contact person, email, phone, website URL, project type dropdown (New Website, Redesign, E-commerce, Custom Application), budget range dropdown (Under $5k, $5k-$10k, $10k-$25k, $25k+), timeline dropdown (ASAP, 1-3 months, 3-6 months, Flexible), and project details textarea.', 'raztech-form-architect' ),
			)
		);

		// PHASE 7: Pass analytics data to JavaScript
		wp_localize_script(
			$this->plugin_name . '-analytics',
			'smartformsAnalytics',
			array(
				'submissionsData' => self::get_submissions_chart_data(),
				'leadScoreData'   => self::get_lead_score_chart_data(),
				'spamData'        => self::get_spam_chart_data(),
			)
		);
	}

	/**
	 * Add admin menu items
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		// Main menu
		add_menu_page(
			__( 'SmartForms AI', 'raztech-form-architect' ),
			__( 'SmartForms AI', 'raztech-form-architect' ),
			'manage_options',
			'raztech-form-architect',
			array( $this, 'display_dashboard' ),
			'dashicons-feedback',
			30
		);

		// Dashboard submenu
		add_submenu_page(
			'raztech-form-architect',
			__( 'Dashboard', 'raztech-form-architect' ),
			__( 'Dashboard', 'raztech-form-architect' ),
			'manage_options',
			'raztech-form-architect',
			array( $this, 'display_dashboard' )
		);

		// All Forms submenu
		add_submenu_page(
			'raztech-form-architect',
			__( 'All Forms', 'raztech-form-architect' ),
			__( 'All Forms', 'raztech-form-architect' ),
			'manage_options',
			'raztech-form-architect-forms',
			array( $this, 'display_all_forms' )
		);

		// Add New Form submenu
		add_submenu_page(
			'raztech-form-architect',
			__( 'Add New Form', 'raztech-form-architect' ),
			__( 'Add New Form', 'raztech-form-architect' ),
			'manage_options',
			'raztech-form-architect-new-form',
			array( $this, 'display_form_builder' )
		);

		// Submissions submenu
		add_submenu_page(
			'raztech-form-architect',
			__( 'Submissions', 'raztech-form-architect' ),
			__( 'Submissions', 'raztech-form-architect' ),
			'manage_options',
			'raztech-form-architect-submissions',
			array( $this, 'display_submissions' )
		);

		// Settings submenu
		add_submenu_page(
			'raztech-form-architect',
			__( 'Settings', 'raztech-form-architect' ),
			__( 'Settings', 'raztech-form-architect' ),
			'manage_options',
			'raztech-form-architect-settings',
			array( $this, 'display_settings' )
		);

		// PHASE 8: Welcome page (hidden from menu)
		add_submenu_page(
			'', // No parent = hidden from menu
			__( 'Welcome to SmartForms AI', 'raztech-form-architect' ),
			__( 'Welcome', 'raztech-form-architect' ),
			'manage_options',
			'raztech-form-architect-welcome',
			array( $this, 'display_welcome' )
		);
	}

	/**
	 * Display dashboard page
	 *
	 * @since    1.0.0
	 */
	public function display_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'raztech-form-architect' ) );
		}

		require_once RT_FA_PATH . 'admin/partials/dashboard.php';
	}

	/**
	 * Display all forms page
	 *
	 * @since    1.0.0
	 */
	public function display_all_forms() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'raztech-form-architect' ) );
		}

		// Form actions (like deletion) are now handled early in handle_form_actions()
		// This method only loads the view

		require_once RT_FA_PATH . 'admin/partials/all-forms.php';
	}

	/**
	 * Process form save/update
	 *
	 * Runs on admin_init hook to process form BEFORE any output is sent.
	 * This prevents "headers already sent" errors when redirecting after save.
	 *
	 * @since    1.0.0
	 */
	public function process_form_save() {
		// Only process if form is being submitted
		if ( ! isset( $_POST['rt_fa_save_form'] ) ) {
			return;
		}

		// Check if we're on the form builder page
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'raztech-form-architect-new-form' ) {
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'raztech-form-architect' ) );
		}

		// Verify nonce
		if ( ! isset( $_POST['rt_fa_form_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rt_fa_form_nonce'] ) ), 'rt_fa_save_form' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'raztech-form-architect' ) );
		}

		// Sanitize and prepare form data
		$form_data = array(
			'form_name'           => isset( $_POST['form_name'] ) ? sanitize_text_field( wp_unslash( $_POST['form_name'] ) ) : '',
			'form_description'    => isset( $_POST['form_description'] ) ? wp_kses_post( wp_unslash( $_POST['form_description'] ) ) : '',
			'form_fields'         => isset( $_POST['form_fields'] ) ? json_decode( wp_unslash( $_POST['form_fields'] ), true ) : array(),
			'conversational_mode' => isset( $_POST['conversational_mode'] ) ? 1 : 0, // PHASE 4
			'settings'            => array(
				'submit_button_text' => isset( $_POST['submit_button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['submit_button_text'] ) ) : 'Submit',
				'success_message'    => isset( $_POST['success_message'] ) ? sanitize_text_field( wp_unslash( $_POST['success_message'] ) ) : 'Thank you for your submission!',
			),
		);

		// Get page creation options (only for new forms)
		$create_page = isset( $_POST['create_page'] ) && $_POST['create_page'] === '1';
		$page_status = isset( $_POST['page_status'] ) ? sanitize_text_field( wp_unslash( $_POST['page_status'] ) ) : 'publish';
		$page_title  = isset( $_POST['page_title'] ) ? sanitize_text_field( wp_unslash( $_POST['page_title'] ) ) : '';

		// Validate page status
		if ( ! in_array( $page_status, array( 'publish', 'draft' ), true ) ) {
			$page_status = 'publish';
		}

		// Use form name as page title if custom title not provided
		if ( empty( $page_title ) ) {
			$page_title = $form_data['form_name'];
		}

		// Determine if update or create
		if ( isset( $_GET['form_id'] ) ) {
			// Update existing form (no page creation for updates)
			$form_id      = intval( $_GET['form_id'] );
			RT_FA_Form_Builder::update_form( $form_id, $form_data );
			$redirect_url = admin_url( 'admin.php?page=raztech-form-architect-new-form&form_id=' . $form_id . '&updated=1' );
		} else {
			// Create new form
			$form_id = RT_FA_Form_Builder::create_form( $form_data );

			if ( is_wp_error( $form_id ) ) {
				wp_die( esc_html__( 'Error creating form. Please try again.', 'raztech-form-architect' ) );
			}

			// Create page if requested
			$page_id = null;
			if ( $create_page ) {
				$page_options = array(
					'page_title'    => $page_title,
					'page_status'   => $page_status,
					'add_intro_text' => true,
					'created_by'    => 'manual',
				);

				$page_id = RT_FA_Page_Creator::create_page_for_form( $form_id, $page_options );
			}

			// Build redirect URL with success parameters
			$redirect_params = array(
				'page'    => 'raztech-form-architect-new-form',
				'form_id' => $form_id,
				'created' => '1',
			);

			if ( $page_id && ! is_wp_error( $page_id ) ) {
				$redirect_params['page_created'] = $page_id;
				$redirect_params['page_status']  = $page_status;
			}

			$redirect_url = add_query_arg( $redirect_params, admin_url( 'admin.php' ) );
		}

		// Redirect to form builder with success message
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Display form builder page
	 *
	 * @since    1.0.0
	 */
	public function display_form_builder() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'raztech-form-architect' ) );
		}

		require_once RT_FA_PATH . 'admin/partials/form-builder.php';
	}

	/**
	 * Display submissions page
	 *
	 * @since    1.0.0
	 */
	public function display_submissions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'raztech-form-architect' ) );
		}

		// CSV export is now handled early in handle_form_actions()
		// This method only loads the view

		require_once RT_FA_PATH . 'admin/partials/submissions.php';
	}

	/**
	 * Process settings form submission
	 *
	 * Runs on admin_init hook to process form BEFORE any output is sent.
	 * This allows wp_safe_redirect() to work properly without "headers already sent" errors.
	 *
	 * @since    1.0.0
	 */
	public function process_settings_save() {
		// Only process if this is a settings page submission
		if ( ! isset( $_POST['rt_fa_save_settings'] ) ) {
			return;
		}

		// Check if we're on the settings page
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'raztech-form-architect-settings' ) {
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'raztech-form-architect' ) );
		}

		// Verify nonce
		if ( ! isset( $_POST['rt_fa_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rt_fa_settings_nonce'] ) ), 'rt_fa_save_settings' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'raztech-form-architect' ) );
		}

		// Save AI API settings
		update_option( 'rt_fa_api_provider', isset( $_POST['api_provider'] ) ? sanitize_text_field( wp_unslash( $_POST['api_provider'] ) ) : 'openai' );
		update_option( 'rt_fa_api_key', isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '' );
		update_option( 'rt_fa_rate_limit', isset( $_POST['rate_limit'] ) ? intval( $_POST['rate_limit'] ) : 50 );

		// PHASE 6: Save auto-response settings
		update_option( 'rt_fa_auto_response', isset( $_POST['auto_response'] ) ? 1 : 0 );
		update_option( 'rt_fa_from_name', isset( $_POST['from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['from_name'] ) ) : get_bloginfo( 'name' ) );
		update_option( 'rt_fa_from_email', isset( $_POST['from_email'] ) ? sanitize_email( wp_unslash( $_POST['from_email'] ) ) : get_option( 'admin_email' ) );
		update_option( 'rt_fa_reply_to_email', isset( $_POST['reply_to_email'] ) ? sanitize_email( wp_unslash( $_POST['reply_to_email'] ) ) : get_option( 'admin_email' ) );
		update_option( 'rt_fa_skip_low_scores', isset( $_POST['skip_low_scores'] ) ? 1 : 0 );

		// PHASE 5: Save spam detection settings
		update_option( 'rt_fa_spam_detection', isset( $_POST['spam_detection'] ) ? 1 : 0 );
		update_option( 'rt_fa_spam_threshold', isset( $_POST['spam_threshold'] ) ? intval( $_POST['spam_threshold'] ) : 60 );
		update_option( 'rt_fa_spam_ai_check', isset( $_POST['spam_ai_check'] ) ? 1 : 0 );

		// Redirect to settings page with success message
		wp_safe_redirect( admin_url( 'admin.php?page=raztech-form-architect-settings&updated=1' ) );
		exit;
	}

	/**
	 * Display settings page
	 *
	 * @since    1.0.0
	 */
	public function display_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'raztech-form-architect' ) );
		}

		require_once RT_FA_PATH . 'admin/partials/settings.php';
	}

	/**
	 * PHASE 2: Handle AI form generation AJAX request
	 *
	 * @since    1.0.0
	 */
	public function handle_ai_form_generation() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'rt_fa_admin_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed.', 'raztech-form-architect' ),
				)
			);
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform this action.', 'raztech-form-architect' ),
				)
			);
		}

		// Get description
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';

		if ( empty( $description ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please provide a form description.', 'raztech-form-architect' ),
				)
			);
		}

		// SECURITY FIX: Validate description length to prevent abuse
		if ( strlen( $description ) < 10 ) {
			wp_send_json_error(
				array(
					'message' => __( 'Form description is too short. Please provide at least 10 characters.', 'raztech-form-architect' ),
				)
			);
		}

		if ( strlen( $description ) > 2000 ) {
			wp_send_json_error(
				array(
					'message' => __( 'Form description is too long. Please keep it under 2000 characters.', 'raztech-form-architect' ),
				)
			);
		}

		// SECURITY FIX: Improved options sanitization with validation
		$options            = array();
		$raw_options        = isset( $_POST['options'] ) ? (array) wp_unslash( $_POST['options'] ) : array();
		$allowed_complexity = array( 'simple', 'intermediate', 'advanced' );

		// Whitelist validation for complexity
		if ( isset( $raw_options['complexity'] ) ) {
			$complexity             = sanitize_text_field( $raw_options['complexity'] );
			$options['complexity'] = in_array( $complexity, $allowed_complexity, true ) ? $complexity : 'intermediate';
		}

		// Sanitize purpose with length limit
		if ( isset( $raw_options['purpose'] ) && ! empty( $raw_options['purpose'] ) ) {
			$purpose             = sanitize_text_field( $raw_options['purpose'] );
			$options['purpose'] = substr( $purpose, 0, 200 ); // Max 200 chars
		}

		// Sanitize audience with length limit
		if ( isset( $raw_options['audience'] ) && ! empty( $raw_options['audience'] ) ) {
			$audience             = sanitize_text_field( $raw_options['audience'] );
			$options['audience'] = substr( $audience, 0, 200 ); // Max 200 chars
		}

		// Get page creation options
		$create_page = isset( $raw_options['create_page'] ) && $raw_options['create_page'] === true;
		$page_status = isset( $raw_options['page_status'] ) ? sanitize_text_field( $raw_options['page_status'] ) : 'publish';

		// Validate page status
		if ( ! in_array( $page_status, array( 'publish', 'draft' ), true ) ) {
			$page_status = 'publish';
		}

		// Generate form with AI
		$result = RT_FA_Generator::generate_form( $description, $options );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
					'code'    => $result->get_error_code(),
				)
			);
		}

		// Return success with form structure AND page creation flags
		wp_send_json_success(
			array(
				'message'        => __( 'Form generated successfully!', 'raztech-form-architect' ),
				'form_structure' => $result,
				'create_page'    => $create_page,
				'page_status'    => $page_status,
			)
		);
	}

	/**
	 * PHASE 7: Get submissions chart data (last 30 days)
	 *
	 * @since    1.0.0
	 * @return   array   Chart data with labels and values.
	 */
	private static function get_submissions_chart_data() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rt_fa_submissions';

		$labels = array();
		$values = array();

		for ( $i = 29; $i >= 0; $i-- ) {
			$date     = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
			$labels[] = gmdate( 'M j', strtotime( $date ) );

			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $table_name
					WHERE DATE(submitted_at) = %s",
					$date
				)
			);

			$values[] = intval( $count );
		}

		return array(
			'labels' => $labels,
			'values' => $values,
		);
	}

	/**
	 * PHASE 7: Get lead score distribution data
	 *
	 * @since    1.0.0
	 * @return   array   Chart data with values.
	 */
	private static function get_lead_score_chart_data() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rt_fa_submissions';

		$high = $wpdb->get_var(
			"SELECT COUNT(*) FROM $table_name WHERE lead_score >= 80"
		);

		$medium = $wpdb->get_var(
			"SELECT COUNT(*) FROM $table_name WHERE lead_score >= 50 AND lead_score < 80"
		);

		$low = $wpdb->get_var(
			"SELECT COUNT(*) FROM $table_name WHERE lead_score < 50"
		);

		return array(
			'values' => array( intval( $high ), intval( $medium ), intval( $low ) ),
		);
	}

	/**
	 * PHASE 7: Get spam detection data
	 *
	 * @since    1.0.0
	 * @return   array   Chart data with values.
	 */
	private static function get_spam_chart_data() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rt_fa_submissions';

		$legitimate = $wpdb->get_var(
			"SELECT COUNT(*) FROM $table_name WHERE is_spam = 0 AND spam_score < 40"
		);

		$spam = $wpdb->get_var(
			"SELECT COUNT(*) FROM $table_name WHERE is_spam = 1"
		);

		$suspicious = $wpdb->get_var(
			"SELECT COUNT(*) FROM $table_name WHERE spam_score >= 40 AND spam_score < 60"
		);

		return array(
			'values' => array( intval( $legitimate ), intval( $spam ), intval( $suspicious ) ),
		);
	}

	/**
	 * PHASE 8: Display welcome page
	 *
	 * @since    1.0.0
	 */
	public function display_welcome() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'raztech-form-architect' ) );
		}

		require_once RT_FA_PATH . 'admin/partials/welcome.php';
	}

	/**
	 * PHASE 7: Generate AI-powered insights
	 *
	 * @since    1.0.0
	 * @return   array   Array of insights with type and message.
	 */
	public static function get_ai_insights() {
		// Get all forms and their stats
		$forms    = RT_FA_Form_Builder::get_forms();
		$insights = array();

		foreach ( $forms as $form ) {
			$stats = self::get_form_stats( $form->id );

			// Low conversion rate
			if ( $stats['conversion_rate'] < 30 && $stats['views'] > 50 ) {
				$insights[] = array(
					'type'    => 'warning',
					'message' => sprintf(
						/* translators: 1: Form name, 2: Conversion rate */
						__( '%1$s has low conversion (%2$d%%). Consider reducing fields or simplifying the form.', 'raztech-form-architect' ),
						$form->form_name,
						$stats['conversion_rate']
					),
				);
			}

			// High spam rate
			if ( $stats['spam_rate'] > 50 && $stats['submissions'] > 10 ) {
				$insights[] = array(
					'type'    => 'danger',
					'message' => sprintf(
						/* translators: 1: Form name, 2: Spam rate */
						__( '%1$s receives %2$d%% spam. Consider increasing spam threshold or adding additional protection.', 'raztech-form-architect' ),
						$form->form_name,
						$stats['spam_rate']
					),
				);
			}

			// High quality leads
			if ( $stats['avg_lead_score'] > 75 && $stats['submissions'] > 5 ) {
				$insights[] = array(
					'type'    => 'success',
					'message' => sprintf(
						/* translators: 1: Form name, 2: Average score */
						__( '%1$s generates high-quality leads (avg score: %2$d). Prioritize follow-ups!', 'raztech-form-architect' ),
						$form->form_name,
						$stats['avg_lead_score']
					),
				);
			}

			// Good conversion rate
			if ( $stats['conversion_rate'] >= 40 && $stats['views'] > 50 ) {
				$insights[] = array(
					'type'    => 'success',
					'message' => sprintf(
						/* translators: 1: Form name, 2: Conversion rate */
						__( '%1$s has excellent conversion (%2$d%%). Keep up the great work!', 'raztech-form-architect' ),
						$form->form_name,
						$stats['conversion_rate']
					),
				);
			}

			// Very low spam rate
			if ( $stats['spam_rate'] < 10 && $stats['submissions'] > 20 ) {
				$insights[] = array(
					'type'    => 'success',
					'message' => sprintf(
						/* translators: 1: Form name */
						__( '%1$s has minimal spam (< 10%%). Your spam protection is working well.', 'raztech-form-architect' ),
						$form->form_name
					),
				);
			}
		}

		// Limit to 5 insights
		return array_slice( $insights, 0, 5 );
	}

	/**
	 * PHASE 7: Get form statistics
	 *
	 * PHASE 8: Added caching for performance optimization.
	 *
	 * @since    1.0.0
	 * @param    int $form_id Form ID.
	 * @return   array        Form statistics.
	 */
	public static function get_form_stats( $form_id ) {
		// PHASE 8: Check cache first
		$cache_key = 'rt_fa_stats_' . $form_id;
		$stats     = get_transient( $cache_key );

		if ( false !== $stats ) {
			return $stats;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'rt_fa_submissions';

		// Get views from analytics table
		$analytics_table = $wpdb->prefix . 'rt_fa_analytics';
		$views_result    = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT views FROM $analytics_table WHERE form_id = %d LIMIT 1",
				$form_id
			)
		);
		$views = $views_result ? intval( $views_result->views ) : 0;

		$submissions = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE form_id = %d",
				$form_id
			)
		);

		$spam_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE form_id = %d AND is_spam = 1",
				$form_id
			)
		);

		$avg_score = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(lead_score) FROM $table_name WHERE form_id = %d",
				$form_id
			)
		);

		$conversion_rate = $views > 0 ? round( ( $submissions / $views ) * 100 ) : 0;
		$spam_rate       = $submissions > 0 ? round( ( $spam_count / $submissions ) * 100 ) : 0;

		$stats = array(
			'views'           => intval( $views ),
			'submissions'     => intval( $submissions ),
			'conversion_rate' => $conversion_rate,
			'spam_rate'       => $spam_rate,
			'avg_lead_score'  => intval( $avg_score ),
		);

		// PHASE 8: Cache for 1 hour
		set_transient( $cache_key, $stats, HOUR_IN_SECONDS );

		return $stats;
	}

	/**
	 * Check if SMTP is configured
	 *
	 * @since    1.0.0
	 * @return   bool    True if SMTP plugin detected
	 */
	private function is_smtp_configured() {
		// Check for popular SMTP plugins
		$smtp_plugins = array(
			'wp-mail-smtp/wp_mail_smtp.php',
			'wp-mail-smtp-pro/wp_mail_smtp.php',
			'easy-wp-smtp/easy-wp-smtp.php',
			'post-smtp/postman-smtp.php',
			'wp-ses/wp-ses.php',
		);

		foreach ( $smtp_plugins as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Display SMTP recommendation notice
	 *
	 * @since    1.0.0
	 */
	public function display_smtp_notice() {
		// Only show on SmartForms AI pages
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'smartforms' ) === false ) {
			return;
		}

		// Check if user dismissed the notice
		$dismissed = get_user_meta( get_current_user_id(), 'rt_fa_smtp_notice_dismissed', true );
		if ( $dismissed ) {
			return;
		}

		// Check if SMTP already configured
		if ( $this->is_smtp_configured() ) {
			return;
		}

		// Check if auto-responses are enabled
		$auto_response = get_option( 'rt_fa_auto_response', 0 );
		if ( ! $auto_response ) {
			return; // Don't show if auto-responses disabled
		}

		?>
		<div class="notice notice-info is-dismissible smartforms-smtp-notice" data-notice="smtp">
			<p>
				<strong><?php esc_html_e( 'SmartForms AI - Email Delivery Tip', 'raztech-form-architect' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'For reliable auto-response email delivery, we recommend configuring SMTP. This is standard practice for WordPress (used by WooCommerce, Contact Form 7, etc.).', 'raztech-form-architect' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=WP+Mail+SMTP&tab=search&type=term' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Install WP Mail SMTP (Free)', 'raztech-form-architect' ); ?>
				</a>
				<a href="<?php echo esc_url( RT_FA_URL . 'docs/email-setup-guide.md' ); ?>" class="button" target="_blank">
					<?php esc_html_e( 'View Setup Guide', 'raztech-form-architect' ); ?>
				</a>
				<a href="#" class="button smartforms-dismiss-notice" data-notice="smtp">
					<?php esc_html_e( 'Dismiss', 'raztech-form-architect' ); ?>
				</a>
			</p>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.smartforms-dismiss-notice').on('click', function(e) {
				e.preventDefault();
				var notice = $(this).data('notice');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'rt_fa_dismiss_notice',
						notice: notice,
						nonce: '<?php echo esc_js( wp_create_nonce( 'rt_fa_dismiss_notice' ) ); ?>'
					}
				});

				$(this).closest('.notice').fadeOut();
			});
		});
		</script>
		<?php
	}

	/**
	 * Handle notice dismissal
	 *
	 * @since    1.0.0
	 */
	public function handle_notice_dismissal() {
		check_ajax_referer( 'rt_fa_dismiss_notice', 'nonce' );

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'raztech-form-architect' ) ) );
		}

		$notice = isset( $_POST['notice'] ) ? sanitize_text_field( wp_unslash( $_POST['notice'] ) ) : '';

		if ( $notice === 'smtp' ) {
			update_user_meta( get_current_user_id(), 'rt_fa_smtp_notice_dismissed', true );
		}

		wp_die();
	}

	/**
	 * Find all pages that contain a specific form shortcode
	 *
	 * @since    1.0.0
	 * @param    int $form_id The form ID
	 * @return   array Array of page objects with id, title, url, edit_url, and status
	 */
	private function get_pages_using_form( $form_id ) {
		global $wpdb;

		$pages = array();

		// Method 1: Check pages created automatically (have meta)
		$auto_pages = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				WHERE meta_key = '_rt_fa_form_id'
				AND meta_value = %d",
				$form_id
			)
		);

		foreach ( $auto_pages as $page ) {
			$post = get_post( $page->post_id );
			if ( $post && $post->post_status !== 'trash' ) {
				$pages[] = array(
					'id'       => $post->ID,
					'title'    => $post->post_title,
					'url'      => get_permalink( $post->ID ),
					'edit_url' => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
					'status'   => $post->post_status,
				);
			}
		}

		// Method 2: Search all pages for the shortcode
		$shortcode_pattern = '[smartform id="' . $form_id . '"]';
		$shortcode_pattern_alt = '[smartforms id="' . $form_id . '"]'; // Alternative shortcode

		$all_pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				's'              => $shortcode_pattern,
			)
		);

		foreach ( $all_pages as $post ) {
			// Check if already in array (from auto-created)
			$exists = false;
			foreach ( $pages as $existing_page ) {
				if ( $existing_page['id'] === $post->ID ) {
					$exists = true;
					break;
				}
			}

			if ( ! $exists && ( strpos( $post->post_content, $shortcode_pattern ) !== false || strpos( $post->post_content, $shortcode_pattern_alt ) !== false ) ) {
				$pages[] = array(
					'id'       => $post->ID,
					'title'    => $post->post_title,
					'url'      => get_permalink( $post->ID ),
					'edit_url' => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
					'status'   => $post->post_status,
				);
			}
		}

		return $pages;
	}

	/**
	 * AJAX: Get form deletion info (pages and submissions count)
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_delete_info() {
		// Verify nonce
		if ( ! isset( $_POST['form_id'] ) || ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing required parameters', 'raztech-form-architect' ) ) );
		}

		$form_id = intval( $_POST['form_id'] );
		check_ajax_referer( 'rt_fa_delete_form_' . $form_id, 'nonce' );

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'raztech-form-architect' ) ) );
		}

		// Get submissions count
		global $wpdb;
		$submissions_table = $wpdb->prefix . 'rt_fa_submissions';
		$submissions_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$submissions_table} WHERE form_id = %d",
				$form_id
			)
		);

		// Get pages using this form
		$pages = $this->get_pages_using_form( $form_id );

		wp_send_json_success(
			array(
				'submissions_count' => intval( $submissions_count ),
				'pages'             => $pages,
			)
		);
	}

	/**
	 * AJAX: Delete form and optionally delete pages
	 *
	 * @since    1.0.0
	 */
	public function ajax_delete_form() {
		// Verify nonce
		if ( ! isset( $_POST['form_id'] ) || ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing required parameters', 'raztech-form-architect' ) ) );
		}

		$form_id = intval( $_POST['form_id'] );
		check_ajax_referer( 'rt_fa_delete_form_' . $form_id, 'nonce' );

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'raztech-form-architect' ) ) );
		}

		$delete_pages = isset( $_POST['delete_pages'] ) && $_POST['delete_pages'] === 'true';

		$pages_deleted = 0;

		// Delete pages if requested
		if ( $delete_pages ) {
			$pages = $this->get_pages_using_form( $form_id );
			foreach ( $pages as $page ) {
				if ( wp_trash_post( $page['id'] ) ) {
					$pages_deleted++;
				}
			}
		}

		// Delete the form (this also deletes submissions via existing logic)
		$result = RT_FA_Form_Builder::delete_form( $form_id );

		if ( $result ) {
			wp_send_json_success(
				array(
					'message'       => __( 'Form deleted successfully', 'raztech-form-architect' ),
					'pages_deleted' => $pages_deleted,
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Error deleting form', 'raztech-form-architect' ) ) );
		}
	}

	/**
	 * Handle bulk actions AJAX request
	 *
	 * @since    1.0.0
	 */
	public function ajax_bulk_actions() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'rt_fa_bulk_actions' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'raztech-form-architect' ) ) );
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action', 'raztech-form-architect' ) ) );
			return;
		}

		// Get parameters
		if ( ! isset( $_POST['bulk_action'] ) || ! isset( $_POST['submission_ids'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing required parameters', 'raztech-form-architect' ) ) );
			return;
		}

		$bulk_action = sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$submission_ids = array_map( 'intval', wp_unslash( $_POST['submission_ids'] ) );

		if ( empty( $submission_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No submissions selected', 'raztech-form-architect' ) ) );
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'rt_fa_submissions';

		$success_count = 0;

		switch ( $bulk_action ) {
			case 'delete':
				foreach ( $submission_ids as $id ) {
					$result = $wpdb->delete(
						$table_name,
						array( 'id' => $id ),
						array( '%d' )
					);
					if ( $result !== false ) {
						$success_count++;
					}
				}
				break;

			case 'mark_spam':
				foreach ( $submission_ids as $id ) {
					$result = $wpdb->update(
						$table_name,
						array(
							'is_spam'    => 1,
							'spam_score' => 100,
						),
						array( 'id' => $id ),
						array( '%d', '%d' ),
						array( '%d' )
					);
					if ( $result !== false ) {
						$success_count++;
					}
				}
				break;

			case 'mark_clean':
				foreach ( $submission_ids as $id ) {
					$result = $wpdb->update(
						$table_name,
						array(
							'is_spam'    => 0,
							'spam_score' => 0,
						),
						array( 'id' => $id ),
						array( '%d', '%d' ),
						array( '%d' )
					);
					if ( $result !== false ) {
						$success_count++;
					}
				}
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Invalid action', 'raztech-form-architect' ) ) );
				return;
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: Number of submissions processed */
					_n(
						'%d submission processed successfully',
						'%d submissions processed successfully',
						$success_count,
						'raztech-form-architect'
					),
					$success_count
				),
				'count'   => $success_count,
			)
		);
	}
}
