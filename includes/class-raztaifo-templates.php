<?php
/**
 * SmartForms AI Templates Manager
 *
 * Handles form template loading, installation, and sample data generation.
 *
 * @package    RAZTAIFO_AI
 * @subpackage RAZTAIFO_AI/includes

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
 * @since      1.0.0
 */

class RAZTAIFO_Templates {

    /**
     * Path to templates directory
     *
     * @var string
     */
    private $templates_path;

    /**
     * Templates index data
     *
     * @var array
     */
    private $templates_index;

    /**
     * Initialize the class
     */
    public function __construct() {
        $this->templates_path = plugin_dir_path(dirname(__FILE__)) . 'templates/';
        $this->load_templates_index();
    }

    /**
     * Load templates index file
     *
     * @return void
     */
    private function load_templates_index() {
        $index_file = $this->templates_path . 'templates-index.json';
        
        if (file_exists($index_file)) {
            $index_content = file_get_contents($index_file);
            $this->templates_index = json_decode($index_content, true);
        } else {
            $this->templates_index = array(
                'version' => '1.0.0',
                'total_templates' => 0,
                'categories' => array(),
                'recommended_set' => array()
            );
        }
    }

    /**
     * Get all available templates organized by category
     *
     * @return array Categories with templates
     */
    public function get_all_templates() {
        $organized_templates = array();

        foreach ($this->templates_index['categories'] as $category_id => $category_data) {
            $category_templates = array();

            foreach ($category_data['templates'] as $template_id) {
                $template = $this->load_template($template_id, $category_id);
                if ($template) {
                    $category_templates[] = $template;
                }
            }

            if (!empty($category_templates)) {
                $organized_templates[$category_id] = array(
                    'name' => $category_data['name'],
                    'icon' => $category_data['icon'],
                    'description' => $category_data['description'],
                    'templates' => $category_templates
                );
            }
        }

        return $organized_templates;
    }

    /**
     * Load a specific template
     *
     * @param string $template_id Template identifier
     * @param string $category Category identifier
     * @return array|false Template data or false on failure
     */
    public function load_template($template_id, $category = null) {
        // If category not provided, search for it
        if (!$category) {
            $category = $this->find_template_category($template_id);
        }

        if (!$category) {
            return false;
        }

        $template_file = $this->templates_path . $category . '/' . $template_id . '.json';

        if (!file_exists($template_file)) {
            return false;
        }

        $template_content = file_get_contents($template_file);
        $template_data = json_decode($template_content, true);

        if (!$template_data) {
            return false;
        }

        // Add category info
        $template_data['category'] = $category;
        
        return $template_data;
    }

    /**
     * Find which category a template belongs to
     *
     * @param string $template_id Template identifier
     * @return string|false Category ID or false if not found
     */
    private function find_template_category($template_id) {
        foreach ($this->templates_index['categories'] as $category_id => $category_data) {
            if (in_array($template_id, $category_data['templates'])) {
                return $category_id;
            }
        }
        return false;
    }

    /**
     * Get recommended template set
     *
     * @return array Recommended templates
     */
    public function get_recommended_templates() {
        $recommended = array();
        
        foreach ($this->templates_index['recommended_set'] as $template_id) {
            $category = $this->find_template_category($template_id);
            $template = $this->load_template($template_id, $category);
            if ($template) {
                $recommended[] = $template;
            }
        }

        return $recommended;
    }

    /**
     * Create form from template
     *
     * @param string $template_id Template identifier
     * @param array $options Installation options
     * @return int|WP_Error Form ID or error
     */
    public function create_form_from_template($template_id, $options = array()) {
        global $wpdb;

        $template = $this->load_template($template_id);
        
        if (!$template) {
            return new WP_Error('template_not_found', __('Template not found', 'raztech-form-architect'));
        }

        // Prepare form data
        $form_data = array(
            'form_name' => $template['name'],
            'form_fields' => json_encode($template['fields']),
            'form_settings' => json_encode(array(
                'created_from_template' => $template_id,
                'template_category' => $template['category']
            )),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        // Insert form
        $result = $wpdb->insert(
            $wpdb->prefix . 'raztaifo_forms',
            $form_data,
            array('%s', '%s', '%s', '%s', '%s')
        );

        if (!$result) {
            return new WP_Error('form_creation_failed', __('Failed to create form', 'raztech-form-architect'));
        }

        $form_id = $wpdb->insert_id;

        // Generate sample submissions if requested
        if (!empty($options['generate_submissions'])) {
            $submissions_count = isset($options['submissions_count']) ? intval($options['submissions_count']) : 20;
            $this->generate_sample_submissions($form_id, $template, $submissions_count, $options);
        }

        // Create page if requested
        if (!empty($options['create_pages'])) {
            $page_options = array(
                'page_title' => $template['name'],
                'page_status' => isset($options['page_status']) ? $options['page_status'] : 'publish',
                'add_intro_text' => true,
                'created_by' => 'template'
            );

            $page_id = RAZTAIFO_Page_Creator::create_page_for_form($form_id, $page_options);
            
            // Store page ID in options for return data
            if (!is_wp_error($page_id)) {
                $options['page_created'] = $page_id;
            }
        }

        return $form_id;
    }

    /**
     * Generate sample submissions for a form
     *
     * @param int $form_id Form ID
     * @param array $template Template data
     * @param int $count Number of submissions to generate
     * @param array $options Generation options
     * @return int Number of submissions created
     */
    public function generate_sample_submissions($form_id, $template, $count = 20, $options = array()) {
        global $wpdb;

        $defaults = array(
            'include_varied_scores' => true,
            'include_spam' => true,
            'spam_percentage' => 10,
            'date_distribution' => 'last_30_days',
            'score_distribution' => array(
                'excellent' => 25, // 25%
                'good' => 40,      // 40%
                'fair' => 25,      // 25%
                'poor' => 10       // 10%
            )
        );

        $options = wp_parse_args($options, $defaults);

        $created_count = 0;
        $sample_data_generator = new RAZTAIFO_Sample_Data_Generator();

        for ($i = 0; $i < $count; $i++) {
            // Determine quality level based on distribution
            $quality_level = $this->get_weighted_quality_level($options['score_distribution']);
            
            // Determine if this should be spam
            $is_spam = false;
            if ($options['include_spam']) {
                $spam_chance = rand(1, 100);
                $is_spam = ($spam_chance <= $options['spam_percentage']);
            }

            // Generate submission data
            $submission_data = $sample_data_generator->generate_submission_data(
                $template,
                $quality_level,
                $is_spam
            );

            // Generate submission date
            $submission_date = $this->generate_random_date($options['date_distribution']);

            // Calculate lead score (using existing lead scorer)
            $lead_scorer = new RAZTAIFO_Lead_Scorer();
            $lead_score = $lead_scorer->calculate_score(0, $submission_data, $form_id);

            // Calculate spam score (using existing spam detector)
            $spam_analysis = RAZTAIFO_Spam_Detector::analyze_submission(0, $submission_data, $form_id);
            $spam_score = $spam_analysis['spam_score'];

            // If marked as spam, ensure spam score reflects that
            if ($is_spam && $spam_score < 60) {
                $spam_score = rand(60, 95);
            }

            // Insert submission
            $result = $wpdb->insert(
                $wpdb->prefix . 'raztaifo_submissions',
                array(
                    'form_id' => $form_id,
                    'submission_data' => json_encode($submission_data),
                    'lead_score' => $lead_score,
                    'spam_score' => $spam_score,
                    'ip_address' => $this->generate_random_ip(),
                    'user_agent' => $this->get_random_user_agent(),
                    'submitted_at' => $submission_date,
                    'created_at' => $submission_date
                ),
                array('%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s')
            );

            if ($result) {
                $created_count++;
            }
        }

        return $created_count;
    }

    /**
     * Get weighted quality level based on distribution
     *
     * @param array $distribution Score distribution percentages
     * @return string Quality level (excellent|good|fair|poor)
     */
    private function get_weighted_quality_level($distribution) {
        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($distribution as $level => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $level;
            }
        }

        return 'good'; // Default fallback
    }

    /**
     * Generate random date within specified range
     *
     * @param string $range Date range (last_30_days, last_60_days, today, etc.)
     * @return string MySQL datetime
     */
    private function generate_random_date($range = 'last_30_days') {
        $now = current_time('timestamp');

        switch ($range) {
            case 'today':
                $start = strtotime('today', $now);
                $end = $now;
                break;

            case 'last_7_days':
                $start = strtotime('-7 days', $now);
                $end = $now;
                break;

            case 'last_30_days':
                $start = strtotime('-30 days', $now);
                $end = $now;
                break;

            case 'last_60_days':
                $start = strtotime('-60 days', $now);
                $end = $now;
                break;

            case 'last_90_days':
                $start = strtotime('-90 days', $now);
                $end = $now;
                break;

            default:
                $start = strtotime('-30 days', $now);
                $end = $now;
        }

        $random_timestamp = rand($start, $end);
        return date('Y-m-d H:i:s', $random_timestamp);
    }

    /**
     * Generate random IP address
     *
     * @return string IP address
     */
    private function generate_random_ip() {
        return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255);
    }

    /**
     * Get random user agent string
     *
     * @return string User agent
     */
    private function get_random_user_agent() {
        $user_agents = array(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        );

        return $user_agents[array_rand($user_agents)];
    }

    /**
     * Delete all sample data (forms created from templates and their submissions)
     *
     * @return array Result with counts
     */
    public function delete_all_sample_data() {
        global $wpdb;

        // Find all forms created from templates
        $forms = $wpdb->get_results(
            "SELECT id, form_settings FROM {$wpdb->prefix}raztaifo_forms 
             WHERE form_settings LIKE '%created_from_template%'"
        );

        $forms_deleted = 0;
        $submissions_deleted = 0;

        foreach ($forms as $form) {
            $settings = json_decode($form->form_settings, true);
            
            if (isset($settings['created_from_template'])) {
                // Delete submissions for this form
                $deleted = $wpdb->delete(
                    $wpdb->prefix . 'raztaifo_submissions',
                    array('form_id' => $form->id),
                    array('%d')
                );
                
                if ($deleted !== false) {
                    $submissions_deleted += $deleted;
                }

                // Delete the form
                $wpdb->delete(
                    $wpdb->prefix . 'raztaifo_forms',
                    array('id' => $form->id),
                    array('%d')
                );
                
                $forms_deleted++;
            }
        }

        return array(
            'forms_deleted' => $forms_deleted,
            'submissions_deleted' => $submissions_deleted
        );
    }

    /**
     * Get statistics about installed sample data
     *
     * @return array Statistics
     */
    public function get_sample_data_stats() {
        global $wpdb;

        $forms = $wpdb->get_results(
            "SELECT id, form_name, created_at FROM {$wpdb->prefix}raztaifo_forms 
             WHERE form_settings LIKE '%created_from_template%'
             ORDER BY created_at DESC"
        );

        $total_submissions = 0;
        $last_installed = null;

        if (!empty($forms)) {
            $form_ids = array_column($forms, 'id');
            $placeholders = implode(',', array_fill(0, count($form_ids), '%d'));
            
            $total_submissions = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}raztaifo_submissions 
                     WHERE form_id IN ($placeholders)",
                    ...$form_ids
                )
            );

            $last_installed = $forms[0]->created_at;
        }

        return array(
            'forms_count' => count($forms),
            'submissions_count' => $total_submissions,
            'last_installed' => $last_installed,
            'has_sample_data' => (count($forms) > 0)
        );
    }

    /**
     * Get template count
     *
     * @return int Total number of templates
     */
    public function get_template_count() {
        return $this->templates_index['total_templates'];
    }

    /**
     * Get categories
     *
     * @return array Categories data
     */
    public function get_categories() {
        return $this->templates_index['categories'];
    }
}
