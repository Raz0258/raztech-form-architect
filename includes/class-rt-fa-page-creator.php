<?php
/**
 * SmartForms AI Page Creator
 *
 * Handles automatic WordPress page creation for forms.
 * Creates professional pages with form shortcodes automatically.
 *
 * @package    RT_FA_AI
 * @subpackage RT_FA_AI/includes
 * @since      1.0.0
 */

class RT_FA_Page_Creator {

    /**
     * Create a WordPress page for a form
     *
     * @param int $form_id Form ID
     * @param array $options Page creation options
     * @return int|WP_Error Page ID or error
     */
    public static function create_page_for_form($form_id, $options = array()) {
        global $wpdb;

        // Get form details
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rt_fa_forms WHERE id = %d",
            $form_id
        ));

        if (!$form) {
            return new WP_Error('form_not_found', __('Form not found', 'raztech-form-architect'));
        }

        // Parse options with defaults
        $defaults = array(
            'page_title' => $form->form_name,
            'page_status' => 'publish', // publish or draft
            'page_template' => '', // empty for default template
            'add_intro_text' => true,
            'intro_text' => '',
            'created_by' => 'template', // template, manual, or ai
        );

        $options = wp_parse_args($options, $defaults);

        // Generate page slug
        $page_slug = self::generate_unique_slug($options['page_title']);

        // Build page content
        $page_content = self::build_page_content($form_id, $form->form_name, $options);

        // Prepare page data
        $page_data = array(
            'post_title'    => sanitize_text_field($options['page_title']),
            'post_name'     => $page_slug,
            'post_content'  => $page_content,
            'post_status'   => $options['page_status'],
            'post_type'     => 'page',
            'post_author'   => get_current_user_id(),
            'comment_status' => 'closed',
            'ping_status'   => 'closed',
        );

        // Add template if specified
        if (!empty($options['page_template'])) {
            $page_data['page_template'] = $options['page_template'];
        }

        // Create the page
        $page_id = wp_insert_post($page_data, true);

        if (is_wp_error($page_id)) {
            return $page_id;
        }

        // Store page relationship in form settings
        self::link_page_to_form($form_id, $page_id, $options);

        // Add custom meta
        update_post_meta($page_id, '_rt_fa_form_id', $form_id);
        update_post_meta($page_id, '_rt_fa_auto_created', true);
        update_post_meta($page_id, '_rt_fa_created_by', $options['created_by']);
        update_post_meta($page_id, '_rt_fa_created_date', current_time('mysql'));

        return $page_id;
    }

    /**
     * Build page content with form shortcode
     *
     * @param int $form_id Form ID
     * @param string $form_name Form name
     * @param array $options Creation options
     * @return string Page content HTML
     */
    private static function build_page_content($form_id, $form_name, $options) {
        $content = '';

        // Add intro text if enabled
        if ($options['add_intro_text']) {
            if (!empty($options['intro_text'])) {
                $intro = $options['intro_text'];
            } else {
                // Generate smart intro text based on form name
                $intro = self::generate_intro_text($form_name);
            }
            
            $content .= "<!-- wp:paragraph -->\n";
            $content .= "<p>" . wp_kses_post($intro) . "</p>\n";
            $content .= "<!-- /wp:paragraph -->\n\n";
        }

        // Add form shortcode
        $content .= "<!-- wp:shortcode -->\n";
        $content .= "[smartforms id=\"{$form_id}\"]\n";
        $content .= "<!-- /wp:shortcode -->\n";

        return $content;
    }

    /**
     * Generate smart intro text based on form name
     *
     * @param string $form_name Form name
     * @return string Intro text
     */
    private static function generate_intro_text($form_name) {
        $name_lower = strtolower($form_name);

        // Contact forms
        if (strpos($name_lower, 'contact') !== false) {
            return "Have a question or need assistance? Fill out the form below and we'll get back to you as soon as possible.";
        }

        // Quote/consultation forms
        if (strpos($name_lower, 'quote') !== false || strpos($name_lower, 'consultation') !== false) {
            return "Request a free quote by completing the form below. We'll review your requirements and get back to you within 24 hours.";
        }

        // Application forms
        if (strpos($name_lower, 'application') !== false || strpos($name_lower, 'apply') !== false) {
            return "Please complete the application form below. We'll review your submission and contact you regarding next steps.";
        }

        // Feedback/survey forms
        if (strpos($name_lower, 'feedback') !== false || strpos($name_lower, 'survey') !== false) {
            return "We value your feedback! Please take a moment to complete this form and help us improve our services.";
        }

        // Booking/reservation forms
        if (strpos($name_lower, 'booking') !== false || strpos($name_lower, 'reservation') !== false) {
            return "Ready to book? Fill out the form below and we'll confirm your reservation shortly.";
        }

        // Newsletter/signup forms
        if (strpos($name_lower, 'newsletter') !== false || strpos($name_lower, 'subscribe') !== false) {
            return "Join our mailing list to receive updates, exclusive offers, and valuable content delivered to your inbox.";
        }

        // Default generic intro
        return "Please complete the form below and submit your information. We'll be in touch shortly.";
    }

    /**
     * Generate unique page slug
     *
     * @param string $title Page title
     * @return string Unique slug
     */
    private static function generate_unique_slug($title) {
        $slug = sanitize_title($title);
        $original_slug = $slug;
        $counter = 1;

        // Check if slug exists
        while (self::slug_exists($slug)) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug already exists
     *
     * @param string $slug Slug to check
     * @return bool True if exists
     */
    private static function slug_exists($slug) {
        global $wpdb;

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'page' AND post_status != 'trash'",
            $slug
        ));

        return !empty($exists);
    }

    /**
     * Link page to form in database
     *
     * @param int $form_id Form ID
     * @param int $page_id Page ID
     * @param array $options Creation options
     * @return bool Success
     */
    private static function link_page_to_form($form_id, $page_id, $options) {
        global $wpdb;

        // Get current form settings
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT form_settings FROM {$wpdb->prefix}rt_fa_forms WHERE id = %d",
            $form_id
        ));

        $settings = !empty($form->form_settings) ? json_decode($form->form_settings, true) : array();

        // Add page information
        $settings['associated_page'] = array(
            'page_id' => $page_id,
            'page_url' => get_permalink($page_id),
            'created_at' => current_time('mysql'),
            'created_by' => $options['created_by'],
            'auto_created' => true
        );

        // Update form settings
        $result = $wpdb->update(
            $wpdb->prefix . 'rt_fa_forms',
            array('form_settings' => json_encode($settings)),
            array('id' => $form_id),
            array('%s'),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Update page title when form name changes
     *
     * @param int $form_id Form ID
     * @param string $new_form_name New form name
     * @return bool Success
     */
    public static function update_page_title($form_id, $new_form_name) {
        $page_id = self::get_page_id_for_form($form_id);

        if (!$page_id) {
            return false;
        }

        $result = wp_update_post(array(
            'ID' => $page_id,
            'post_title' => sanitize_text_field($new_form_name)
        ));

        return !is_wp_error($result);
    }

    /**
     * Get page ID associated with a form
     *
     * @param int $form_id Form ID
     * @return int|false Page ID or false
     */
    public static function get_page_id_for_form($form_id) {
        global $wpdb;

        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT form_settings FROM {$wpdb->prefix}rt_fa_forms WHERE id = %d",
            $form_id
        ));

        if (!$form || empty($form->form_settings)) {
            return false;
        }

        $settings = json_decode($form->form_settings, true);

        if (isset($settings['associated_page']['page_id'])) {
            return intval($settings['associated_page']['page_id']);
        }

        return false;
    }

    /**
     * Delete page associated with form
     *
     * @param int $form_id Form ID
     * @param bool $force_delete Bypass trash and force deletion
     * @return bool Success
     */
    public static function delete_page_for_form($form_id, $force_delete = false) {
        $page_id = self::get_page_id_for_form($form_id);

        if (!$page_id) {
            return false;
        }

        $result = wp_delete_post($page_id, $force_delete);

        return !empty($result);
    }

    /**
     * Check if form has an associated page
     *
     * @param int $form_id Form ID
     * @return bool True if has page
     */
    public static function form_has_page($form_id) {
        $page_id = self::get_page_id_for_form($form_id);
        
        if (!$page_id) {
            return false;
        }

        // Verify page still exists and is not trashed
        $page = get_post($page_id);
        
        return ($page && $page->post_status !== 'trash');
    }

    /**
     * Get page URL for form
     *
     * @param int $form_id Form ID
     * @return string|false Page URL or false
     */
    public static function get_page_url_for_form($form_id) {
        $page_id = self::get_page_id_for_form($form_id);

        if (!$page_id) {
            return false;
        }

        return get_permalink($page_id);
    }

    /**
     * Get all forms that have associated pages
     *
     * @return array Array of form IDs with page info
     */
    public static function get_all_forms_with_pages() {
        global $wpdb;

        $forms = $wpdb->get_results(
            "SELECT id, form_name, form_settings 
             FROM {$wpdb->prefix}rt_fa_forms 
             WHERE form_settings LIKE '%associated_page%'"
        );

        $result = array();

        foreach ($forms as $form) {
            $settings = json_decode($form->form_settings, true);
            
            if (isset($settings['associated_page'])) {
                $result[] = array(
                    'form_id' => $form->id,
                    'form_name' => $form->form_name,
                    'page_id' => $settings['associated_page']['page_id'],
                    'page_url' => get_permalink($settings['associated_page']['page_id']),
                    'created_at' => $settings['associated_page']['created_at']
                );
            }
        }

        return $result;
    }

    /**
     * Batch create pages for multiple forms
     *
     * @param array $form_ids Array of form IDs
     * @param array $options Page creation options
     * @return array Results with success/failure for each form
     */
    public static function batch_create_pages($form_ids, $options = array()) {
        $results = array(
            'success' => array(),
            'failed' => array(),
            'skipped' => array()
        );

        foreach ($form_ids as $form_id) {
            // Skip if form already has a page
            if (self::form_has_page($form_id)) {
                $results['skipped'][] = array(
                    'form_id' => $form_id,
                    'reason' => 'already_has_page'
                );
                continue;
            }

            $page_id = self::create_page_for_form($form_id, $options);

            if (is_wp_error($page_id)) {
                $results['failed'][] = array(
                    'form_id' => $form_id,
                    'error' => $page_id->get_error_message()
                );
            } else {
                $results['success'][] = array(
                    'form_id' => $form_id,
                    'page_id' => $page_id,
                    'page_url' => get_permalink($page_id)
                );
            }
        }

        return $results;
    }

    /**
     * Get page creation statistics
     *
     * @return array Statistics
     */
    public static function get_statistics() {
        global $wpdb;

        $total_forms = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rt_fa_forms"
        );

        $forms_with_pages = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rt_fa_forms 
             WHERE form_settings LIKE '%associated_page%'"
        );

        return array(
            'total_forms' => intval($total_forms),
            'forms_with_pages' => intval($forms_with_pages),
            'forms_without_pages' => intval($total_forms) - intval($forms_with_pages),
            'percentage' => $total_forms > 0 ? round(($forms_with_pages / $total_forms) * 100, 1) : 0
        );
    }
}
