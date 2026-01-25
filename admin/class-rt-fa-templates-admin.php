<?php
/**
 * SmartForms AI Templates Admin Page
 *
 * Handles the Form Templates admin interface and AJAX operations.
 *
 * @package    RT_FA_AI
 * @subpackage RT_FA_AI/admin
 * @since      1.0.0
 */

class RT_FA_Templates_Admin {

    /**
     * Templates manager instance
     *
     * @var RT_FA_Templates
     */
    private $templates_manager;

    /**
     * Initialize the class
     */
    public function __construct() {
        $this->templates_manager = new RT_FA_Templates();
        $this->register_hooks();
    }

    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'), 25);
        
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_rt_fa_install_templates', array($this, 'ajax_install_templates'));
        add_action('wp_ajax_rt_fa_delete_sample_data', array($this, 'ajax_delete_sample_data'));
        add_action('wp_ajax_rt_fa_get_template_preview', array($this, 'ajax_get_template_preview'));
    }

    /**
     * Add admin menu page as submenu under SmartForms AI
     */
    public function add_admin_menu() {
        add_submenu_page(
            'raztech-form-architect',                              // Parent slug (SmartForms AI menu)
            __('Form Templates', 'raztech-form-architect'),        // Page title
            __('Form Templates', 'raztech-form-architect'),        // Menu title
            'manage_options',                             // Capability
            'raztech-form-architect-templates',                       // Menu slug
            array($this, 'render_templates_page')         // Callback function
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our templates page (submenu under raztech-form-architect)
        if ($hook !== 'raztech-form-architect_page_raztech-form-architect-templates') {
            return;
        }

        // CSS
        wp_enqueue_style(
            'raztech-form-architect-templates-admin',
            plugins_url('css/templates-admin.css', __FILE__),
            array(),
            '1.0.0'
        );

        // JavaScript
        wp_enqueue_script(
            'raztech-form-architect-templates-admin',
            plugins_url('js/templates-admin.js', __FILE__),
            array('jquery'),
            '1.0.0',
            true
        );

        // Localize script with AJAX URL and nonces
        wp_localize_script('raztech-form-architect-templates-admin', 'smartformsTemplates', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rt_fa_templates_nonce'),
            'strings' => array(
                'installing' => __('Installing templates...', 'raztech-form-architect'),
                'success' => __('Templates installed successfully!', 'raztech-form-architect'),
                'error' => __('An error occurred. Please try again.', 'raztech-form-architect'),
                'confirmDelete' => __('Are you sure you want to delete all sample data? This cannot be undone.', 'raztech-form-architect'),
                'deleting' => __('Deleting sample data...', 'raztech-form-architect'),
                'deleted' => __('Sample data deleted successfully!', 'raztech-form-architect')
            )
        ));
    }

    /**
     * Render templates page
     */
    public function render_templates_page() {
        // Get all templates organized by category
        $categories = $this->templates_manager->get_all_templates();
        
        // Get recommended template IDs
        $recommended_templates = $this->templates_manager->get_recommended_templates();
        $recommended_ids = array_column($recommended_templates, 'template_id');
        
        // Get sample data statistics
        $sample_stats = $this->templates_manager->get_sample_data_stats();
        
        // Include the view
        include plugin_dir_path(__FILE__) . 'partials/templates-library.php';

    }

    /**
     * AJAX: Install selected templates
     */
    public function ajax_install_templates() {
        // Verify nonce
        check_ajax_referer('rt_fa_templates_nonce', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'raztech-form-architect')));
        }

        // Get POST data
        $selected_templates = isset($_POST['templates']) ? (array) $_POST['templates'] : array();
        $submissions_count = isset($_POST['submissions_count']) ? intval($_POST['submissions_count']) : 20;
        $include_varied_scores = isset($_POST['include_varied_scores']) && $_POST['include_varied_scores'] === 'true';
        $include_spam = isset($_POST['include_spam']) && $_POST['include_spam'] === 'true';
        $spam_percentage = isset($_POST['spam_percentage']) ? intval($_POST['spam_percentage']) : 10;
        $date_distribution = isset($_POST['date_distribution']) ? sanitize_text_field($_POST['date_distribution']) : 'last_30_days';
        $create_pages = isset($_POST['create_pages']) && $_POST['create_pages'] === 'true';
        $page_status = isset($_POST['page_status']) ? sanitize_text_field($_POST['page_status']) : 'publish';

        if (empty($selected_templates)) {
            wp_send_json_error(array('message' => __('Please select at least one template', 'raztech-form-architect')));
        }

        // Prepare installation options
        $options = array(
            'generate_submissions' => ($submissions_count > 0),
            'submissions_count' => $submissions_count,
            'include_varied_scores' => $include_varied_scores,
            'include_spam' => $include_spam,
            'spam_percentage' => $spam_percentage,
            'date_distribution' => $date_distribution,
            'create_pages' => $create_pages,
            'page_status' => $page_status
        );

        $results = array(
            'forms_created' => 0,
            'submissions_created' => 0,
            'pages_created' => 0,
            'failed' => array()
        );

        // Install each selected template
        foreach ($selected_templates as $template_id) {
            $template_id = sanitize_text_field($template_id);
            
            $form_id = $this->templates_manager->create_form_from_template($template_id, $options);
            
            if (is_wp_error($form_id)) {
                $results['failed'][] = array(
                    'template' => $template_id,
                    'error' => $form_id->get_error_message()
                );
            } else {
                $results['forms_created']++;
                if ($submissions_count > 0) {
                    $results['submissions_created'] += $submissions_count;
                }
                if ($create_pages && isset($options['page_created'])) {
                    $results['pages_created']++;
                }
            }
        }

        // Prepare success message
        $message_parts = array();
        $message_parts[] = sprintf(__('%d forms', 'raztech-form-architect'), $results['forms_created']);
        
        if ($results['submissions_created'] > 0) {
            $message_parts[] = sprintf(__('%d submissions', 'raztech-form-architect'), $results['submissions_created']);
        }
        
        if ($results['pages_created'] > 0) {
            $message_parts[] = sprintf(__('%d pages', 'raztech-form-architect'), $results['pages_created']);
        }
        
        $message = sprintf(
            __('Successfully created %s!', 'raztech-form-architect'),
            implode(', ', $message_parts)
        );

        if (!empty($results['failed'])) {
            $message .= ' ' . sprintf(
                __('%d templates failed to install.', 'raztech-form-architect'),
                count($results['failed'])
            );
        }

        wp_send_json_success(array(
            'message' => $message,
            'results' => $results
        ));
    }

    /**
     * AJAX: Delete all sample data
     */
    public function ajax_delete_sample_data() {
        // Verify nonce
        check_ajax_referer('rt_fa_templates_nonce', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'raztech-form-architect')));
        }

        $result = $this->templates_manager->delete_all_sample_data();

        $message = sprintf(
            __('Deleted %d forms and %d submissions.', 'raztech-form-architect'),
            $result['forms_deleted'],
            $result['submissions_deleted']
        );

        wp_send_json_success(array(
            'message' => $message,
            'result' => $result
        ));
    }

    /**
     * AJAX: Get template preview
     */
    public function ajax_get_template_preview() {
        // Verify nonce
        check_ajax_referer('rt_fa_templates_nonce', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'raztech-form-architect')));
        }

        $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';

        if (empty($template_id)) {
            wp_send_json_error(array('message' => __('Template ID required', 'raztech-form-architect')));
        }

        $template = $this->templates_manager->load_template($template_id);

        if (!$template) {
            wp_send_json_error(array('message' => __('Template not found', 'raztech-form-architect')));
        }

        // Build preview HTML
        $preview_html = $this->build_template_preview_html($template);

        wp_send_json_success(array(
            'html' => $preview_html,
            'template' => $template
        ));
    }

    /**
     * Build template preview HTML
     *
     * @param array $template Template data
     * @return string HTML
     */
    private function build_template_preview_html($template) {
        $html = '<div class="template-preview">';
        $html .= '<h3>' . esc_html($template['name']) . '</h3>';
        $html .= '<p class="template-description">' . esc_html($template['description']) . '</p>';
        
        $html .= '<div class="template-fields">';
        $html .= '<h4>' . __('Fields:', 'raztech-form-architect') . ' (' . count($template['fields']) . ')</h4>';
        $html .= '<ul class="field-list">';
        
        foreach ($template['fields'] as $field) {
            $required = isset($field['required']) && $field['required'] ? ' <span class="required">*</span>' : '';
            $html .= '<li>';
            $html .= '<strong>' . esc_html($field['label']) . '</strong>' . $required;
            $html .= ' <span class="field-type">(' . esc_html($field['type']) . ')</span>';
            
            if (isset($field['options']) && is_array($field['options'])) {
                $html .= '<br><small>' . __('Options:', 'raztech-form-architect') . ' ' . implode(', ', array_slice($field['options'], 0, 3));
                if (count($field['options']) > 3) {
                    $html .= '...';
                }
                $html .= '</small>';
            }
            
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
}
