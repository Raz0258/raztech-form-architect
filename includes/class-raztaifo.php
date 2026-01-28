<?php
/**
 * The core plugin class.
 *
 * @package    RazTechFormArchitect
 * @subpackage RazTechFormArchitect/includes
 */


// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 */
class RAZTAIFO {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      RAZTAIFO_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->version     = RAZTAIFO_VERSION;
		$this->plugin_name = 'raztech-form-architect';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once RAZTAIFO_PATH . 'admin/class-raztaifo-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once RAZTAIFO_PATH . 'public/class-raztaifo-public.php';

		/**
		 * The class responsible for form building functionality.
		 */
		require_once RAZTAIFO_PATH . 'includes/class-raztaifo-form-builder.php';

		/**
		 * The class responsible for rendering forms on the frontend.
		 */
		require_once RAZTAIFO_PATH . 'includes/class-raztaifo-form-renderer.php';

		/**
		 * PHASE 2: The class responsible for AI form generation.
		 */
		require_once RAZTAIFO_PATH . 'includes/class-raztaifo-generator.php';

		/**
		 * PHASE 3: The class responsible for AI-powered lead scoring.
		 */
		require_once RAZTAIFO_PATH . 'includes/class-raztaifo-lead-scorer.php';

		/**
		 * PHASE 5: The class responsible for AI-powered spam detection.
		 */
		require_once RAZTAIFO_PATH . 'includes/class-raztaifo-spam-detector.php';

		/**
		 * PHASE 6: The class responsible for AI-powered auto-responses.
		 */
		require_once RAZTAIFO_PATH . 'includes/class-raztaifo-auto-responder.php';

		/**
		 * PHASE 8: The class responsible for export functionality.
		 */
		require_once RAZTAIFO_PATH . 'includes/class-raztaifo-export.php';

		/**
		 * Form Templates Feature: Core template management system.
		 */
		require_once RAZTAIFO_PATH . 'includes/class-raztaifo-templates.php';

		/**
		 * Form Templates Feature: Sample data generation for templates.
		 */
		require_once RAZTAIFO_PATH . 'includes/class-raztaifo-sample-data-generator.php';

		/**
		 * Form Templates Feature: Page creation functionality for templates.
		 */
		require_once RAZTAIFO_PATH . 'includes/class-raztaifo-page-creator.php';

		/**
		 * Form Templates Feature: Admin interface for templates library.
		 */
		require_once RAZTAIFO_PATH . 'admin/class-raztaifo-templates-admin.php';
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new RAZTAIFO_Admin( $this->get_plugin_name(), $this->get_version() );

		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );
		add_action( 'admin_menu', array( $plugin_admin, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $plugin_admin, 'handle_form_actions' ) );
		add_action( 'admin_init', array( $plugin_admin, 'process_settings_save' ) );
		add_action( 'admin_init', array( $plugin_admin, 'process_form_save' ) );

		// PHASE 2: Register AJAX handler for AI form generation
		add_action( 'wp_ajax_raztaifo_generate_ai_form', array( $plugin_admin, 'handle_ai_form_generation' ) );

		// Add admin notices for SMTP recommendation
		add_action( 'admin_notices', array( $plugin_admin, 'display_smtp_notice' ) );

		// Handle notice dismissal
		add_action( 'wp_ajax_raztaifo_dismiss_notice', array( $plugin_admin, 'handle_notice_dismissal' ) );

		// Register AJAX handlers for form deletion with page cleanup
		add_action( 'wp_ajax_raztaifo_get_delete_info', array( $plugin_admin, 'ajax_get_delete_info' ) );
		add_action( 'wp_ajax_raztaifo_delete_form', array( $plugin_admin, 'ajax_delete_form' ) );

		// Register AJAX handler for bulk actions
		add_action( 'wp_ajax_raztaifo_bulk_actions', array( $plugin_admin, 'ajax_bulk_actions' ) );

		// Form Templates Feature: Initialize templates admin interface
		$templates_admin = new RAZTAIFO_Templates_Admin();
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new RAZTAIFO_Public( $this->get_plugin_name(), $this->get_version() );

		add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts' ) );

		// Register shortcodes (both singular and plural for flexibility)
		add_shortcode( 'smartform', array( $plugin_public, 'smartform_shortcode' ) );
		add_shortcode( 'smartforms', array( $plugin_public, 'smartform_shortcode' ) ); // Plural alias

		// Register form submission handler
		add_action( 'wp_ajax_raztaifo_submit', array( $plugin_public, 'handle_form_submission' ) );
		add_action( 'wp_ajax_nopriv_raztaifo_submit', array( $plugin_public, 'handle_form_submission' ) );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		// All hooks are already registered in the constructor
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
