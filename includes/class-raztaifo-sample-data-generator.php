<?php
/**
 * SmartForms AI Sample Data Generator
 *
 * Generates realistic sample submission data based on quality profiles.
 *
 * @package    RAZTAIFO_AI
 * @subpackage RAZTAIFO_AI/includes
 * @since      1.0.0
 */

class RAZTAIFO_Sample_Data_Generator {

    /**
     * Disposable email domains for spam generation
     *
     * @var array
     */
    private $disposable_domains = array(
        'tempmail.com',
        'mailinator.com',
        '10minutemail.com',
        'guerrillamail.com',
        'throwaway.email'
    );

    /**
     * Spam keywords for content generation
     *
     * @var array
     */
    private $spam_keywords = array(
        'click here',
        'buy now',
        'limited time',
        'make money fast',
        'free money',
        'weight loss',
        'viagra',
        'casino',
        'winner'
    );

    /**
     * Generate submission data based on template and quality level
     *
     * @param array $template Template data with fields and sample_data_profiles
     * @param string $quality_level Quality level (excellent|good|fair|poor)
     * @param bool $force_spam Force this to be spam
     * @return array Submission data
     */
    public function generate_submission_data($template, $quality_level = 'good', $force_spam = false) {
        $submission_data = array();

        // Get sample data profile for this quality level
        $profile = isset($template['sample_data_profiles'][$quality_level]) 
            ? $template['sample_data_profiles'][$quality_level]
            : array();

        foreach ($template['fields'] as $field) {
            $field_name = $field['name'];
            $field_type = $field['type'];
            $is_required = isset($field['required']) ? $field['required'] : false;

            // Generate value based on field type and quality level
            $value = $this->generate_field_value(
                $field,
                $profile,
                $quality_level,
                $force_spam
            );

            // Only include value if it's required or randomly include optional fields
            if ($is_required || $value !== '' || $quality_level === 'excellent') {
                $submission_data[$field_name] = $value;
            } elseif ($quality_level === 'good' && rand(1, 10) > 3) {
                // 70% chance to include optional fields for 'good' quality
                $submission_data[$field_name] = $value;
            } elseif ($quality_level === 'fair' && rand(1, 10) > 6) {
                // 40% chance for 'fair' quality
                $submission_data[$field_name] = $value;
            }
        }

        return $submission_data;
    }

    /**
     * Generate value for a specific field
     *
     * @param array $field Field configuration
     * @param array $profile Sample data profile
     * @param string $quality_level Quality level
     * @param bool $force_spam Force spam content
     * @return mixed Field value
     */
    private function generate_field_value($field, $profile, $quality_level, $force_spam) {
        $field_name = $field['name'];
        $field_type = $field['type'];

        // Check if profile has specific data for this field
        if (isset($profile[$field_name]) && !empty($profile[$field_name])) {
            $values = $profile[$field_name];
            
            if (is_array($values)) {
                $value = $values[array_rand($values)];
                
                // Process template variables in value
                $value = $this->process_template_variables($value, $quality_level, $force_spam);
                
                return $value;
            }
            
            return $values;
        }

        // Generate generic value based on field type
        return $this->generate_generic_value($field, $quality_level, $force_spam);
    }

    /**
     * Process template variables in sample data
     *
     * @param string $value Value with potential template variables
     * @param string $quality_level Quality level
     * @param bool $force_spam Force spam
     * @return string Processed value
     */
    private function process_template_variables($value, $quality_level, $force_spam) {
        // Generate first and last names if needed
        static $first_name = null;
        static $last_name = null;

        if ($first_name === null) {
            $names = $this->get_sample_names($quality_level);
            $full_name = explode(' ', $names[array_rand($names)]);
            $first_name = $full_name[0];
            $last_name = isset($full_name[1]) ? $full_name[1] : 'User';
        }

        // Replace variables
        $value = str_replace('{first}', strtolower($first_name), $value);
        $value = str_replace('{last}', strtolower($last_name), $value);
        $value = str_replace('{First}', $first_name, $value);
        $value = str_replace('{Last}', $last_name, $value);
        $value = str_replace('{rand}', rand(100, 999), $value);

        // Use disposable domain if spam
        if ($force_spam && strpos($value, '@') !== false) {
            $parts = explode('@', $value);
            $value = $parts[0] . '@' . $this->disposable_domains[array_rand($this->disposable_domains)];
        }

        return $value;
    }

    /**
     * Generate generic value based on field type
     *
     * @param array $field Field configuration
     * @param string $quality_level Quality level
     * @param bool $force_spam Force spam
     * @return mixed Generated value
     */
    private function generate_generic_value($field, $quality_level, $force_spam) {
        $field_type = $field['type'];

        switch ($field_type) {
            case 'text':
                return $this->generate_text_value($field, $quality_level, $force_spam);

            case 'email':
                return $this->generate_email_value($quality_level, $force_spam);

            case 'phone':
                return $this->generate_phone_value($quality_level);

            case 'textarea':
                return $this->generate_textarea_value($field, $quality_level, $force_spam);

            case 'select':
                return $this->generate_select_value($field, $quality_level);

            case 'checkbox':
                return $this->generate_checkbox_value($field, $quality_level);

            case 'radio':
                return $this->generate_radio_value($field, $quality_level);

            default:
                return '';
        }
    }

    /**
     * Generate text field value
     */
    private function generate_text_value($field, $quality_level, $force_spam) {
        $field_name = strtolower($field['name']);

        // Name fields
        if (strpos($field_name, 'name') !== false) {
            $names = $this->get_sample_names($quality_level);
            return $names[array_rand($names)];
        }

        // Company fields
        if (strpos($field_name, 'company') !== false) {
            return $this->get_sample_company($quality_level);
        }

        // Subject fields
        if (strpos($field_name, 'subject') !== false) {
            return $this->get_sample_subject($quality_level, $force_spam);
        }

        // Default text
        switch ($quality_level) {
            case 'excellent':
                return 'Professional input provided';
            case 'good':
                return 'Valid information';
            case 'fair':
                return 'basic info';
            case 'poor':
                return $force_spam ? 'BUY NOW!!!' : 'test';
        }
    }

    /**
     * Generate email value
     */
    private function generate_email_value($quality_level, $force_spam) {
        static $counter = 0;
        $counter++;

        if ($force_spam) {
            $domain = $this->disposable_domains[array_rand($this->disposable_domains)];
            return 'temp' . rand(100, 999) . '@' . $domain;
        }

        $domains = array(
            'excellent' => array('company.com', 'business.com', 'corporation.com'),
            'good' => array('gmail.com', 'email.com', 'yahoo.com'),
            'fair' => array('gmail.com', 'yahoo.com'),
            'poor' => array('test.com', 'example.com')
        );

        $domain_set = isset($domains[$quality_level]) ? $domains[$quality_level] : $domains['good'];
        $domain = $domain_set[array_rand($domain_set)];

        $prefix = ($quality_level === 'poor') ? 'test' : 'user' . $counter;
        
        return $prefix . '@' . $domain;
    }

    /**
     * Generate phone value
     */
    private function generate_phone_value($quality_level) {
        switch ($quality_level) {
            case 'excellent':
                return '(' . rand(200, 999) . ') ' . rand(200, 999) . '-' . rand(1000, 9999);
            case 'good':
                return rand(200, 999) . '-' . rand(200, 999) . '-' . rand(1000, 9999);
            case 'fair':
                return rand(2000000000, 9999999999);
            case 'poor':
                return '1234567890';
        }
    }

    /**
     * Generate textarea value
     */
    private function generate_textarea_value($field, $quality_level, $force_spam) {
        if ($force_spam) {
            $spam_text = $this->spam_keywords[array_rand($this->spam_keywords)];
            return strtoupper($spam_text) . '!!! Visit our website NOW! ' . $spam_text;
        }

        $messages = array(
            'excellent' => 'I am writing to express my interest in your services. I have reviewed your offerings and believe there is a strong alignment with our needs. I would appreciate the opportunity to discuss this further at your earliest convenience. Please let me know your availability for a detailed consultation.',
            'good' => 'I\'m interested in learning more about your services. Could you please provide additional information about pricing and availability? Thank you.',
            'fair' => 'need more info about services',
            'poor' => 'test message'
        );

        return isset($messages[$quality_level]) ? $messages[$quality_level] : $messages['good'];
    }

    /**
     * Generate select field value
     */
    private function generate_select_value($field, $quality_level) {
        if (empty($field['options']) || !is_array($field['options'])) {
            return '';
        }

        $options = $field['options'];

        // For poor quality, often select last or "other" option
        if ($quality_level === 'poor') {
            $poor_options = array('Other', 'Not sure', 'Not sure yet', 'Need guidance');
            foreach ($poor_options as $poor_opt) {
                if (in_array($poor_opt, $options)) {
                    return $poor_opt;
                }
            }
            return end($options);
        }

        // For fair quality, sometimes select vague options
        if ($quality_level === 'fair' && rand(1, 10) > 5) {
            $fair_options = array('Other', 'Not sure', 'Flexible', 'No preference');
            foreach ($fair_options as $fair_opt) {
                if (in_array($fair_opt, $options)) {
                    return $fair_opt;
                }
            }
        }

        // For good and excellent, select from first 70% of options
        $max_index = ($quality_level === 'excellent') 
            ? ceil(count($options) * 0.5)  // First half for excellent
            : ceil(count($options) * 0.7); // First 70% for good

        $selected_options = array_slice($options, 0, max(1, $max_index));
        return $selected_options[array_rand($selected_options)];
    }

    /**
     * Generate checkbox field value
     */
    private function generate_checkbox_value($field, $quality_level) {
        if (empty($field['options']) || !is_array($field['options'])) {
            return array();
        }

        $options = $field['options'];
        $selected = array();

        // Number of selections based on quality
        $selection_count = array(
            'excellent' => rand(2, min(4, count($options))),
            'good' => rand(1, min(3, count($options))),
            'fair' => rand(0, min(2, count($options))),
            'poor' => 0
        );

        $count = isset($selection_count[$quality_level]) ? $selection_count[$quality_level] : 1;

        // Select random options
        if ($count > 0) {
            $shuffled = $options;
            shuffle($shuffled);
            $selected = array_slice($shuffled, 0, $count);
        }

        return $selected;
    }

    /**
     * Generate radio field value
     */
    private function generate_radio_value($field, $quality_level) {
        // Radio is same as select - single value
        return $this->generate_select_value($field, $quality_level);
    }

    /**
     * Get sample names based on quality
     */
    private function get_sample_names($quality_level) {
        $names = array(
            'excellent' => array(
                'Michael Anderson', 'Sarah Thompson', 'Jennifer Martinez',
                'David Chen', 'Alexandra Wilson', 'Robert Johnson',
                'Emily Rodriguez', 'James Taylor', 'Amanda Brown'
            ),
            'good' => array(
                'John Smith', 'Mary Johnson', 'Robert Wilson',
                'Jennifer Davis', 'Michael Brown', 'Lisa Garcia'
            ),
            'fair' => array(
                'mike', 'sarah', 'john doe', 'jane smith', 'bob jones'
            ),
            'poor' => array(
                'test', 'user', 'test user', 'asdf', 'qwerty'
            )
        );

        return isset($names[$quality_level]) ? $names[$quality_level] : $names['good'];
    }

    /**
     * Get sample company name
     */
    private function get_sample_company($quality_level) {
        $companies = array(
            'excellent' => array(
                'TechCorp Solutions', 'Digital Innovations Inc', 'Global Ventures LLC',
                'Strategic Partners Group', 'Innovation Dynamics', 'Premier Business Solutions'
            ),
            'good' => array(
                'Smith Consulting', 'Johnson & Associates', 'Brown Services',
                'Wilson Enterprises', 'Davis Solutions'
            ),
            'fair' => array(
                'My Company', 'ABC Inc', 'The Company'
            ),
            'poor' => array(
                'test', 'company', 'test company'
            )
        );

        $company_set = isset($companies[$quality_level]) ? $companies[$quality_level] : $companies['good'];
        return $company_set[array_rand($company_set)];
    }

    /**
     * Get sample subject line
     */
    private function get_sample_subject($quality_level, $force_spam) {
        if ($force_spam) {
            return strtoupper($this->spam_keywords[array_rand($this->spam_keywords)]) . '!!!';
        }

        $subjects = array(
            'excellent' => array(
                'Partnership Opportunity', 'Project Consultation Request',
                'Service Inquiry', 'Detailed Quote Request', 'Business Proposal'
            ),
            'good' => array(
                'Question about services', 'Need information', 'Interested in product',
                'Service inquiry', 'Request for quote'
            ),
            'fair' => array(
                'question', 'info', 'inquiry', 'need help'
            ),
            'poor' => array(
                'test', 'hello', 'hi', ''
            )
        );

        $subject_set = isset($subjects[$quality_level]) ? $subjects[$quality_level] : $subjects['good'];
        return $subject_set[array_rand($subject_set)];
    }
}
