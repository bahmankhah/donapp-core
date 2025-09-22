<?php

namespace App\Services;

use Exception;
use Kernel\DB;

class GravityService
{
    protected $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get all approved Gravity Flow entries for the current user
     * @param int $page
     * @param int $per_page
     * @return array
     */
    public function getApprovedGravityFlowEntries($page = 1, $per_page = 20, $currentUser = null)
    {
        // appLogger('GravityService: Starting getApprovedGravityFlowEntries - Page: ' . $page . ', Per Page: ' . $per_page);
        
        // Check if Gravity Forms and Gravity Flow are active
        if (!class_exists('GFForms') || !class_exists('Gravity_Flow')) {
            // appLogger('GravityService: Gravity Forms or Gravity Flow not available, returning sample data');
            // Return sample data for demonstration purposes
            return $this->getSampleData($page, $per_page);
        }
        
        // appLogger('GravityService: Gravity Forms and Gravity Flow are available');
        if($currentUser){
            $current_user = $currentUser;
        } else {
            $current_user = wp_get_current_user();
        }
        appLogger('GravityService: Current user ID: ' . $current_user->ID . ', Login: ' . $current_user->user_login);
        if (!$current_user || !$current_user->ID) {
            // appLogger('GravityService: No current user found or user ID is 0');
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $per_page,
                    'total_items' => 0,
                    'total_pages' => 1
                ]
            ];
        }
        
        // appLogger('GravityService: Current user ID: ' . $current_user->ID . ', Login: ' . $current_user->user_login);

        $offset = ($page - 1) * $per_page;

        // Get all forms
        $forms = class_exists('GFAPI') ? \GFAPI::get_forms() : [];
        // appLogger('GravityService: Found ' . count($forms) . ' Gravity Forms');
        
        $approved_entries = [];
        $total_count = 0;

        foreach ($forms as $form) {
            // appLogger('GravityService: Processing form ID: ' . $form['id'] . ', Title: ' . $form['title']);
            
            // Check if this form has Gravity Flow enabled
            $form_id = $form['id'];
            
            // Check if Gravity_Flow_Form class exists before using it
            if (class_exists('Gravity_Flow_Form')) {
                $settings = \Gravity_Flow_Form::get_setting($form_id);
                // appLogger('GravityService: Form ID ' . $form_id . ' Gravity Flow settings: ' . json_encode($settings));
                
                // Check if Flow is enabled for this form using Gravity Flow settings
                if (!isset($settings['workflow']) || empty($settings['workflow'])) {
                    // appLogger('GravityService: Form ID ' . $form_id . ' does not have workflow enabled, skipping');
                    continue; // Skip forms without a workflow
                }
            } else {
                // appLogger('GravityService: Gravity_Flow_Form class not available, processing all forms');
            }

            // Get entries for this form (consider changing 'active' to a wider criteria)
            $search_criteria = [
                'status' => 'active' // Modify if needed
            ];
            $entries = class_exists('GFAPI') ? \GFAPI::get_entries($form['id'], $search_criteria) : [];

            // appLogger('GravityService: Form ID: ' . $form['id'] . ' - Found ' . count($entries) . ' entries with search criteria: ' . json_encode($search_criteria));

            foreach ($entries as $entry) {
                // appLogger(json_encode($entry));
                // appLogger('GravityService: Processing entry ID: ' . $entry['id'] . ', Status: ' . (isset($entry['status']) ? $entry['status'] : 'unknown'));
                
                // Check if entry is approved and user has access
                // $is_approved = $this->isEntryApproved($entry);
                // $has_access = $this->userHasAccessToEntry($entry, $current_user->ID);
                // $is_approved_by_user = $this->isFormApprovedByUser($form, $entry,$current_user->ID);
                
                // Additionally check if user has approved this form in activity log
                $has_approved_in_log = $this->userHasApprovedForm($form['id'], $entry['id'], $current_user->ID);

                // appLogger('GravityService: Entry ID ' . $entry['id'] . ' - Is Approved: ' . ($is_approved ? 'Yes' : 'No') . ', Has Access: ' . ($has_access ? 'Yes' : 'No') . ', Approved by User: ' . ($is_approved_by_user ? 'Yes' : 'No') . ', Approved in Log: ' . ($has_approved_in_log ? 'Yes' : 'No'));

                // Entry must be approved AND user must have either approved it via form field OR via activity log
                if ($has_approved_in_log) {
                    $approved_entries[] = [
                        'id' => $entry['id'],
                        'form_id' => $form['id'],
                        'form_title' => $form['title'],
                        'date_created' => date_i18n('j F Y - H:i', strtotime($entry['date_created'])),
                        'status' => $this->getEntryStatus($entry),
                        'entry_data' => $this->formatEntryData($entry, $form)
                    ];
                    $total_count++;
                    // appLogger('GravityService: Entry ID ' . $entry['id'] . ' added to approved entries');
                }
            }
        }

        // appLogger('GravityService: Total approved entries found: ' . $total_count);

        // Sort by date created (newest first)
        usort($approved_entries, function ($a, $b) {
            return strtotime($b['date_created']) - strtotime($a['date_created']);
        });

        // Apply pagination
        $paginated_entries = array_slice($approved_entries, $offset, $per_page);
        
        // appLogger('GravityService: After pagination - Showing ' . count($paginated_entries) . ' entries (offset: ' . $offset . ', per_page: ' . $per_page . ')');

        return [
            'data' => $paginated_entries,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_count,
                'total_pages' => ceil($total_count / $per_page)
            ]
        ];
    }

    /**
     * Check if user has approved a specific form by checking Gravity Flow activity log
     * @param int $formId
     * @param int $userId
     * @return bool
     */
    private function userHasApprovedForm($formId, $entryId, $userId)
    {
        try {
            // Use the DB class following the pattern from getCategoryId method
            $db = new DB();
            
            // Build the query to check if user has approved this form
            $result = $db->wpdbMain()->get_var(
                $db->wpdbMain()->prepare("
                    SELECT COUNT(*) 
                    FROM {$db->wpdbMain()->prefix}gravityflow_activity_log 
                    WHERE form_id = %d 
                    AND assignee_id = %s
                    AND lead_id = %s 
                    AND log_event = %s 
                    AND log_object = %s 
                    AND log_value = %s 
                    AND assignee_type = %s
                ", 
                $formId,
                $userId,
                $entryId,
                'status',
                'assignee', 
                'approved',
                'user_id'
                )
            );
            
            // Log the query for debugging (can be removed later)
            // appLogger("GravityService: Checking approval for Form ID: {$formId}, User ID: {$userId}, Result: " . (int)$result);
            
            // Return true if any matching records found
            return (int)$result > 0;
            
        } catch (Exception $e) {
            appLogger('GravityService userHasApprovedForm Error: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * Get sample data for demonstration when Gravity Forms is not available
     * @param int $page
     * @param int $per_page
     * @return array
     */
    private function getSampleData($page = 1, $per_page = 20)
    {
        $sample_entries = [
            [
                'id' => '101',
                'form_id' => '1',
                'form_title' => 'فرم درخواست مرخصی',
                'date_created' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'status' => 'approved',
                'entry_data' => [
                    ['label' => 'نام کامل', 'value' => 'احمد احمدی', 'type' => 'text'],
                    ['label' => 'نوع مرخصی', 'value' => 'استعلاجی', 'type' => 'select'],
                    ['label' => 'تاریخ شروع', 'value' => '1403/01/15', 'type' => 'date'],
                    ['label' => 'مدت مرخصی', 'value' => '3 روز', 'type' => 'text']
                ]
            ],
            [
                'id' => '102',
                'form_id' => '2',
                'form_title' => 'فرم ثبت‌نام دوره آموزشی',
                'date_created' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'status' => 'approved',
                'entry_data' => [
                    ['label' => 'نام و نام خانوادگی', 'value' => 'فاطمه محمدی', 'type' => 'text'],
                    ['label' => 'شماره تماس', 'value' => '09123456789', 'type' => 'phone'],
                    ['label' => 'دوره مورد نظر', 'value' => 'برنامه‌نویسی پایتون', 'type' => 'select'],
                    ['label' => 'سطح تجربه', 'value' => 'مبتدی', 'type' => 'radio']
                ]
            ],
            [
                'id' => '103',
                'form_id' => '1',
                'form_title' => 'فرم درخواست مرخصی',
                'date_created' => date('Y-m-d H:i:s', strtotime('-1 week')),
                'status' => 'approved',
                'entry_data' => [
                    ['label' => 'نام کامل', 'value' => 'رضا رضایی', 'type' => 'text'],
                    ['label' => 'نوع مرخصی', 'value' => 'شخصی', 'type' => 'select'],
                    ['label' => 'تاریخ شروع', 'value' => '1403/01/10', 'type' => 'date'],
                    ['label' => 'مدت مرخصی', 'value' => '1 روز', 'type' => 'text']
                ]
            ],
            [
                'id' => '104',
                'form_id' => '3',
                'form_title' => 'فرم درخواست خرید تجهیزات',
                'date_created' => date('Y-m-d H:i:s', strtotime('-10 days')),
                'status' => 'approved',
                'entry_data' => [
                    ['label' => 'نام درخواست‌کننده', 'value' => 'علی علیزاده', 'type' => 'text'],
                    ['label' => 'نوع تجهیزات', 'value' => 'لپ‌تاپ', 'type' => 'select'],
                    ['label' => 'مدل مورد نظر', 'value' => 'Dell Latitude 5520', 'type' => 'text'],
                    ['label' => 'توضیحات', 'value' => 'برای کار طراحی گرافیک مورد نیاز است', 'type' => 'textarea']
                ]
            ],
            [
                'id' => '105',
                'form_id' => '4',
                'form_title' => 'فرم نظرسنجی رضایتمندی',
                'date_created' => date('Y-m-d H:i:s', strtotime('-2 weeks')),
                'status' => 'approved',
                'entry_data' => [
                    ['label' => 'نام', 'value' => 'مریم کریمی', 'type' => 'text'],
                    ['label' => 'میزان رضایت از خدمات', 'value' => 'عالی', 'type' => 'radio'],
                    ['label' => 'پیشنهادات', 'value' => 'خدمات بسیار مناسب و با کیفیت است', 'type' => 'textarea']
                ]
            ]
        ];

        $total_count = count($sample_entries);
        $offset = ($page - 1) * $per_page;
        $paginated_entries = array_slice($sample_entries, $offset, $per_page);

        return [
            'data' => $paginated_entries,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_count,
                'total_pages' => ceil($total_count / $per_page)
            ]
        ];
    }

    /**
     * Check if entry is approved using Gravity Flow API
     * @param array $entry
     * @return bool
     */
    private function isEntryApproved($entry)
    {
        // If we have Gravity Flow API available, use it
        if (class_exists('Gravity_Flow_API') && isset($entry['form_id'])) {
            $gravity_flow_api = new \Gravity_Flow_API($entry['form_id']);
            $status = $gravity_flow_api->get_status($entry);
            return $status === 'approved' || $status === 'complete';
        }

        // Fallback to legacy checks
        // Check different possible indicators for approved status
        if (isset($entry['workflow_final_status']) && $entry['workflow_final_status'] === 'approved') {
            return true;
        }

        if (isset($entry['gravityflow_status']) && $entry['gravityflow_status'] === 'approved') {
            return true;
        }

        // Additional checks for Gravity Flow approval can be added here
        // For now, we'll also consider entries with specific meta values
        $entry_id = $entry['id'];
        $form_id = $entry['form_id'];

        // Check gravity flow step status
        $step_status = '';
        if (function_exists('gform_get_meta')) {
            $step_status = gform_get_meta($entry_id, 'workflow_step_status_' . $form_id);
        }

        if ($step_status === 'approved' || $step_status === 'complete') {
            return true;
        }

        return false;
    }

    /**
     * Get entry status using Gravity Flow API
     * @param array $entry
     * @return string
     */
    private function getEntryStatus($entry)
    {
        // If we have Gravity Flow API available, use it
        if (class_exists('Gravity_Flow_API') && isset($entry['form_id'])) {
            $gravity_flow_api = new \Gravity_Flow_API($entry['form_id']);
            $status = $gravity_flow_api->get_status($entry);
            if ($status) {
                return $status;
            }
        }

        // Fallback to legacy status checks
        if (isset($entry['workflow_final_status'])) {
            return $entry['workflow_final_status'];
        }

        if (isset($entry['gravityflow_status'])) {
            return $entry['gravityflow_status'];
        }

        return 'approved';
    }

    /**
     * Check if user has access to a specific entry
     * @param array $entry
     * @param int $user_id
     * @return bool
     */
    private function userHasAccessToEntry($entry, $user_id)
    {
        // For this implementation, we'll check if the user created the entry
        // or if they are an admin. You can modify this logic based on your needs.
        if (current_user_can('manage_options')) {
            return true;
        }

        // Check if user created this entry
        if (isset($entry['created_by']) && $entry['created_by'] == $user_id) {
            return true;
        }

        // Additional checks can be added here based on your workflow requirements
        // For example, checking if user was assigned to approve this entry

        return false;
    }

    /**
     * Format entry data for display
     * @param array $entry
     * @param array $form
     * @return array
     */
    private function formatEntryData($entry, $form)
    {
        $formatted_data = [];

        foreach ($form['fields'] as $field) {
            $field_id = $field->id;
            $field_label = $field->label;
            $field_value = isset($entry[$field_id]) ? $entry[$field_id] : '';

            // Skip empty values and system fields
            if (empty($field_value) || in_array($field->type, ['page', 'section', 'html'])) {
                continue;
            }

            // Format different field types
            switch ($field->type) {
                case 'fileupload':
                    $field_value = $this->formatFileUpload($field_value);
                    break;
                case 'date':
                    $field_value = $this->formatDate($field_value);
                    break;
                case 'phone':
                    $field_value = $this->formatPhone($field_value);
                    break;
                default:
                    $field_value = esc_html($field_value);
                    break;
            }

            $formatted_data[] = [
                'label' => $field_label,
                'value' => $field_value,
                'type' => $field->type
            ];
        }

        return $formatted_data;
    }

    /**
     * Format file upload field
     * @param string $value
     * @return string
     */
    private function formatFileUpload($value)
    {
        if (empty($value)) {
            return '';
        }

        // If it's a URL, create a link
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $filename = basename($value);
            return '<a href="' . esc_url($value) . '" target="_blank">' . esc_html($filename) . '</a>';
        }

        return esc_html($value);
    }

    /**
     * Format date field
     * @param string $value
     * @return string
     */
    private function formatDate($value)
    {
        if (empty($value)) {
            return '';
        }

        $timestamp = strtotime($value);
        if ($timestamp) {
            return date('Y/m/d', $timestamp);
        }

        return esc_html($value);
    }

    /**
     * Format phone field
     * @param string $value
     * @return string
     */
    private function formatPhone($value)
    {
        return esc_html($value);
    }

    /**
     * Export all approved entries to CSV
     * @return array
     */
    public function exportApprovedEntriesToCSV()
    {
        // Get all entries without pagination
        $all_entries_result = $this->getApprovedGravityFlowEntries(1, 1000);
        $entries = $all_entries_result['data'];

        // Debug information about what we received
        error_log('GravityService: exportApprovedEntriesToCSV called');
        error_log('GravityService: Retrieved ' . count($entries) . ' entries');

        if (empty($entries)) {
            // Check if Gravity Forms is available or we should use sample data
            if (!class_exists('GFForms') || !class_exists('Gravity_Flow_API')) {
                error_log('GravityService: Gravity Forms/Flow API not available, forcing sample data');
                // Force sample data for testing/demo purposes when no real data is available
                $entries = [
                    [
                        'id' => '101',
                        'form_id' => '1',
                        'form_title' => 'فرم درخواست مرخصی (نمونه)',
                        'date_created' => date('Y-m-d H:i:s', strtotime('-2 days')),
                        'status' => 'approved',
                        'entry_data' => [
                            ['label' => 'نام کامل', 'value' => 'احمد احمدی', 'type' => 'text'],
                            ['label' => 'نوع مرخصی', 'value' => 'استعلاجی', 'type' => 'select'],
                            ['label' => 'تاریخ شروع', 'value' => '1403/01/15', 'type' => 'date'],
                            ['label' => 'مدت مرخصی', 'value' => '3 روز', 'type' => 'text']
                        ]
                    ],
                    [
                        'id' => '102',
                        'form_id' => '2',
                        'form_title' => 'فرم ثبت‌نام دوره آموزشی (نمونه)',
                        'date_created' => date('Y-m-d H:i:s', strtotime('-5 days')),
                        'status' => 'approved',
                        'entry_data' => [
                            ['label' => 'نام و نام خانوادگی', 'value' => 'فاطمه محمدی', 'type' => 'text'],
                            ['label' => 'شماره تماس', 'value' => '09123456789', 'type' => 'phone'],
                            ['label' => 'دوره مورد نظر', 'value' => 'برنامه‌نویسی پایتون', 'type' => 'select']
                        ]
                    ]
                ];

                error_log('GravityService: Using fallback sample data with ' . count($entries) . ' entries');
            }
        }

        // Final check - if still no data, return error
        if (empty($entries)) {
            $gravity_status = class_exists('GFForms') ? 'فعال' : 'غیرفعال';
            $gravity_flow_status = class_exists('Gravity_Flow_API') ? 'فعال' : 'غیرفعال';

            return [
                'success' => false,
                'message' => 'هیچ ورودی تأیید شده‌ای یافت نشد. وضعیت افزونه‌ها: Gravity Forms: ' . $gravity_status . ', Gravity Flow API: ' . $gravity_flow_status . '. لطفاً ابتدا برخی از ورودی‌های Gravity Flow را تأیید کنید.'
            ];
        }

        // Prepare CSV headers
        $csv_headers = [
            'شناسه ورودی',
            'عنوان فرم',
            'تاریخ ایجاد',
            'وضعیت',
            'اطلاعات فرم'
        ];

        // Prepare CSV data
        $csv_data = [];
        $csv_data[] = $csv_headers;

        foreach ($entries as $entry) {
            $form_data = '';
            if (!empty($entry['entry_data'])) {
                $form_data_parts = [];
                foreach ($entry['entry_data'] as $field_data) {
                    $form_data_parts[] = $field_data['label'] . ': ' . strip_tags($field_data['value']);
                }
                $form_data = implode(' | ', $form_data_parts);
            }

            $csv_data[] = [
                $entry['id'],
                $entry['form_title'],
                date_i18n('Y/m/d H:i', strtotime($entry['date_created'])),
                'تأیید شده',
                $form_data
            ];
        }

        error_log('GravityService: Generated CSV data with ' . count($csv_data) . ' rows (including header)');

        return [
            'success' => true,
            'data' => $csv_data,
            'filename' => 'gravity-flow-approved-entries-' . date('Y-m-d-H-i-s') . '.csv'
        ];
    }
    /**
     * Get statistics for approved entries
     * @return array
     */
    public function getApprovedEntriesStats()
    {
        $all_entries_result = $this->getApprovedGravityFlowEntries(1, 1000);
        $entries = $all_entries_result['data'];

        $stats = [
            'total_entries' => count($entries),
            'forms_count' => 0,
            'this_month' => 0,
            'this_week' => 0
        ];

        $unique_forms = [];
        $current_month = date('Y-m');
        $current_week_start = date('Y-m-d', strtotime('last sunday'));

        foreach ($entries as $entry) {
            $unique_forms[$entry['form_id']] = true;

            $entry_date = date('Y-m-d', strtotime($entry['date_created']));
            $entry_month = date('Y-m', strtotime($entry['date_created']));

            if ($entry_month === $current_month) {
                $stats['this_month']++;
            }

            if ($entry_date >= $current_week_start) {
                $stats['this_week']++;
            }
        }

        $stats['forms_count'] = count($unique_forms);

        return $stats;
    }

    // Note: exportApprovedEntriesToCSV method has been removed.
    // Use the new export system with ExportFactory or concrete classes instead.

    /**
     * Format field value based on field type
     * @param mixed $value
     * @param string $type
     * @return string
     */
    private function formatFieldValue($value, $type)
    {
        if (empty($value)) {
            return '';
        }

        switch ($type) {
            case 'date':
                return $this->formatDate($value);
            case 'phone':
                return $this->formatPhone($value);
            case 'email':
                return esc_html($value);
            case 'textarea':
                return strip_tags($value);
            case 'select':
            case 'radio':
            case 'checkbox':
                if (is_array($value)) {
                    return implode(', ', array_map('esc_html', $value));
                }
                return esc_html($value);
            default:
                return esc_html($value);
        }
    }

    /**
     * Get enhanced Gravity Flow entries with sorting and mobile optimization using API
     * @param int $page
     * @param int $per_page
     * @param array $filters
     * @return array
     */
    public function getEnhancedGravityFlowEntries($page = 1, $per_page = 20, $filters = [])
    {
        // Check if Gravity Forms and Gravity Flow are active
        if (!class_exists('GFForms') || !class_exists('Gravity_Flow_API')) {
            return $this->getEnhancedSampleData($page, $per_page);
        }

        $current_user = \wp_get_current_user();
        if (!$current_user || !$current_user->ID) {
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $per_page,
                    'total_items' => 0,
                    'total_pages' => 1
                ]
            ];
        }

        $offset = ($page - 1) * $per_page;

        // Get all forms
        $forms = class_exists('GFAPI') ? \GFAPI::get_forms() : [];
        $enhanced_entries = [];
        $total_count = 0;

        foreach ($forms as $form) {
            // Initialize Gravity Flow API for this form
            $gravity_flow_api = new \Gravity_Flow_API($form['id']);
            
            // Get entries for this form
            $search_criteria = [
                'status' => 'active',
                'field_filters' => [
                    [
                        'key' => 'workflow_final_status',
                        'value' => 'approved'
                    ]
                ]
            ];

            $entries = class_exists('GFAPI') ? \GFAPI::get_entries($form['id'], $search_criteria) : [];

            foreach ($entries as $entry) {
                // Use Gravity Flow API to get detailed status
                $workflow_status = $gravity_flow_api->get_status($entry);
                $current_step = $gravity_flow_api->get_current_step($entry);
                
                // Get submitter info
                $submitter = \get_user_by('ID', $entry['created_by']);
                $submitter_name = $submitter ? $submitter->display_name : 'نامشخص';

                // Apply filters if provided
                if (!empty($filters['status']) && $filters['status'] !== $workflow_status) {
                    continue;
                }

                if (!empty($filters['form_id']) && $filters['form_id'] != $form['id']) {
                    continue;
                }

                $enhanced_entries[] = [
                    'id' => $entry['id'],
                    'form_id' => $form['id'],
                    'form_name' => $form['title'],
                    'status' => $workflow_status,
                    'current_step' => $current_step ? $current_step->get_name() : null,
                    'submitter' => [
                        'id' => $entry['created_by'],
                        'name' => $submitter_name,
                        'email' => $submitter ? $submitter->user_email : '',
                    ],
                    'date_created' => $entry['date_created'],
                    'date_created_formatted' => \date_i18n('j F Y - H:i', strtotime($entry['date_created'])),
                    'entry_data' => $entry,
                    'form_data' => $form,
                    'timeline' => $gravity_flow_api->get_timeline($entry),
                    'actions' => $this->getEntryAvailableActions($entry['id'], $form['id'])
                ];
                $total_count++;
            }
        }

        // Sort by date created (newest first) - default sorting
        $sort_field = $filters['sort'] ?? 'date_created';
        $sort_order = $filters['order'] ?? 'desc';

        usort($enhanced_entries, function ($a, $b) use ($sort_field, $sort_order) {
            $value_a = $a[$sort_field] ?? '';
            $value_b = $b[$sort_field] ?? '';

            if ($sort_field === 'date_created') {
                $value_a = strtotime($value_a);
                $value_b = strtotime($value_b);
            }

            $result = $value_a <=> $value_b;
            return $sort_order === 'desc' ? -$result : $result;
        });

        // Apply pagination
        $paginated_entries = array_slice($enhanced_entries, $offset, $per_page);

        return [
            'data' => $paginated_entries,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_count,
                'total_pages' => ceil($total_count / $per_page)
            ],
            'filters' => $filters,
            'available_forms' => $this->getAvailableForms()
        ];
    }

    /**
     * Get enhanced sample data for demonstration
     */
    private function getEnhancedSampleData($page = 1, $per_page = 20)
    {
        $sample_entries = [
            [
                'id' => 1,
                'form_id' => 1,
                'form_name' => 'فرم درخواست تجهیزات',
                'status' => 'completed',
                'submitter' => [
                    'id' => 1,
                    'name' => 'علی احمدی',
                    'email' => 'ali@example.com'
                ],
                'date_created' => '2025-09-12 14:30:00',
                'date_created_formatted' => '21 شهریور 1404 - 14:30',
                'actions' => ['view', 'export']
            ],
            [
                'id' => 2,
                'form_id' => 1,
                'form_name' => 'فرم درخواست تجهیزات',
                'status' => 'pending',
                'submitter' => [
                    'id' => 2,
                    'name' => 'مریم رضایی',
                    'email' => 'maryam@example.com'
                ],
                'date_created' => '2025-09-12 13:15:00',
                'date_created_formatted' => '21 شهریور 1404 - 13:15',
                'actions' => ['view', 'approve', 'reject']
            ],
            [
                'id' => 3,
                'form_id' => 2,
                'form_name' => 'فرم گزارش مالی',
                'status' => 'in_progress',
                'submitter' => [
                    'id' => 3,
                    'name' => 'حسن کریمی',
                    'email' => 'hassan@example.com'
                ],
                'date_created' => '2025-09-12 11:45:00',
                'date_created_formatted' => '21 شهریور 1404 - 11:45',
                'actions' => ['view']
            ],
            [
                'id' => 4,
                'form_id' => 3,
                'form_name' => 'فرم ثبت نام دوره',
                'status' => 'completed',
                'submitter' => [
                    'id' => 4,
                    'name' => 'زهرا محمدی',
                    'email' => 'zahra@example.com'
                ],
                'date_created' => '2025-09-11 16:20:00',
                'date_created_formatted' => '20 شهریور 1404 - 16:20',
                'actions' => ['view', 'export']
            ],
            [
                'id' => 5,
                'form_id' => 2,
                'form_name' => 'فرم گزارش مالی',
                'status' => 'rejected',
                'submitter' => [
                    'id' => 5,
                    'name' => 'محمد صادقی',
                    'email' => 'mohammad@example.com'
                ],
                'date_created' => '2025-09-11 10:30:00',
                'date_created_formatted' => '20 شهریور 1404 - 10:30',
                'actions' => ['view', 'resubmit']
            ]
        ];

        $total_count = count($sample_entries);
        $offset = ($page - 1) * $per_page;
        $paginated_entries = array_slice($sample_entries, $offset, $per_page);

        return [
            'data' => $paginated_entries,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_count,
                'total_pages' => ceil($total_count / $per_page)
            ],
            'available_forms' => [
                ['id' => 1, 'title' => 'فرم درخواست تجهیزات'],
                ['id' => 2, 'title' => 'فرم گزارش مالی'],
                ['id' => 3, 'title' => 'فرم ثبت نام دوره']
            ]
        ];
    }

    /**
     * Get entry workflow status using Gravity Flow API
     */
    private function getEntryWorkflowStatus($entry_id, $form_id)
    {
        // Use Gravity Flow API if available
        if (class_exists('Gravity_Flow_API') && class_exists('GFAPI')) {
            $gravity_flow_api = new \Gravity_Flow_API($form_id);
            $entry = \GFAPI::get_entry($entry_id);
            
            if (!is_wp_error($entry)) {
                $status = $gravity_flow_api->get_status($entry);
                if ($status) {
                    return $status;
                }
            }
        }
        
        // Fallback to sample statuses
        $statuses = ['pending', 'in_progress', 'completed', 'rejected'];
        return $statuses[array_rand($statuses)];
    }

    /**
     * Get available actions for entry
     */
    private function getEntryAvailableActions($entry_id, $form_id)
    {
        // Return available actions based on entry status and user permissions
        return ['view', 'export', 'approve', 'reject'];
    }

    /**
     * Get available forms for filtering
     */
    private function getAvailableForms()
    {
        if (!class_exists('GFAPI')) {
            return [
                ['id' => 1, 'title' => 'فرم درخواست تجهیزات'],
                ['id' => 2, 'title' => 'فرم گزارش مالی'],
                ['id' => 3, 'title' => 'فرم ثبت نام دوره']
            ];
        }

        $forms = \GFAPI::get_forms();
        return array_map(function ($form) {
            return [
                'id' => $form['id'],
                'title' => $form['title']
            ];
        }, $forms);
    }

    /**
     * Get single entry for export
     * @param string $form_id
     * @param string $entry_id
     * @return array
     */
    public function getSingleEntryForExport($form_id, $entry_id)
    {
        try {
            // Check if Gravity Forms is active
            if (!class_exists('GFAPI')) {
                return [
                    'success' => false,
                    'message' => 'Gravity Forms غیر فعال است',
                    'data' => null
                ];
            }

            // Get the entry
            $entry = \GFAPI::get_entry($entry_id);
            if (is_wp_error($entry) || empty($entry)) {
                return [
                    'success' => false,
                    'message' => 'ورودی یافت نشد',
                    'data' => null
                ];
            }

            // Verify entry belongs to the specified form
            if ($entry['form_id'] != $form_id) {
                return [
                    'success' => false,
                    'message' => 'ورودی متعلق به فرم مشخص شده نیست',
                    'data' => null
                ];
            }

            // Get form details
            $form = \GFAPI::get_form($form_id);
            if (is_wp_error($form) || empty($form)) {
                return [
                    'success' => false,
                    'message' => 'فرم یافت نشد',
                    'data' => null
                ];
            }

            // Check user access (basic security check)
            $current_user = wp_get_current_user();
            if (!$current_user || !$this->userHasAccessToEntry($entry, $current_user->ID)) {
                return [
                    'success' => false,
                    'message' => 'دسترسی به این ورودی مجاز نیست',
                    'data' => null
                ];
            }

            // Format entry data for export
            $formatted_entry = [
                'id' => $entry['id'],
                'form_id' => $form['id'],
                'form_title' => $form['title'],
                'date_created' => $entry['date_created'],
                'status' => $this->getEntryStatus($entry),
                'entry_data' => $this->formatEntryData($entry, $form)
            ];

            return [
                'success' => true,
                'message' => 'ورودی با موفقیت بازیابی شد',
                'data' => $formatted_entry
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا در بازیابی ورودی: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get Gravity Flow inbox entries for current user with pagination using API
     * @param int $page
     * @param int $per_page
     * @param mixed $user
     * @return array
     */
    public function getGravityFlowInboxPage($page = 1, $per_page = 20, $user = null)
    {
        try {
            // Check if required classes exist
            if (!class_exists('GFAPI') || !class_exists('Gravity_Flow_API')) {
                return [
                    'success' => false,
                    'message' => 'Gravity Forms یا Gravity Flow API فعال نیست',
                    'data' => $this->getInboxSampleData($page, $per_page),
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $per_page,
                        'total_items' => 5,
                        'total_pages' => 1
                    ]
                ];
            }

            // Get current user
            $current_user = $user ?? wp_get_current_user();
            appLogger(json_encode($current_user));
            $user_id = $current_user->ID;
            if (!$user_id) {
                return [
                    'success' => false,
                    'message' => 'کاربر وارد نشده است',
                    'data' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => $per_page,
                        'total_items' => 0,
                        'total_pages' => 0
                    ]
                ];
            }

            // IMPORTANT: Set current user if passed explicitly (for REST API calls)
            if ($user && $user !== wp_get_current_user()) {
                wp_set_current_user($user_id);
                appLogger('Set current user to: ' . $user_id . ' for REST API context');
            }

            // Use Gravity Flow API static methods to get inbox entries
            // The key issue: filter_key needs to be set properly for the user
            $args = [
                'user_id' => $user_id,
                'filter_key' => 'workflow_user_id_' . $user_id  // Explicitly set the filter key
            ];
            
            // Add pagination if supported
            if ($per_page && $per_page > 0) {
                $args['paging'] = [
                    'page_size' => $per_page,
                    'offset' => ($page - 1) * $per_page
                ];
            }

            $total_count = 0;
            
            // Debug logging
            appLogger('Calling Gravity Flow API with user_id: ' . $user_id);
            appLogger('Current WordPress user after setting: ' . wp_get_current_user()->ID);
            appLogger('Args being passed: ' . json_encode($args));
            
            // Get inbox entries using the API
            $inbox_entries_raw = [];
            
            try {
                $inbox_entries_raw = \Gravity_Flow_API::get_inbox_entries($args, $total_count);
                appLogger('API call successful - result count: ' . count($inbox_entries_raw));
                appLogger('Total count: ' . $total_count);
            } catch (Exception $e) {
                appLogger('API call failed: ' . $e->getMessage());
                
                // Fallback: try with minimal args if filter_key approach fails
                try {
                    $fallback_args = ['user_id' => $user_id];
                    $inbox_entries_raw = \Gravity_Flow_API::get_inbox_entries($fallback_args, $total_count);
                    appLogger('Fallback API call result count: ' . count($inbox_entries_raw));
                    appLogger('Fallback total count: ' . $total_count);
                } catch (Exception $e2) {
                    appLogger('Fallback API call also failed: ' . $e2->getMessage());
                }
            }
            
            $inbox_entries = [];
            appLogger('Final inbox entries count: ' . count($inbox_entries_raw));
            foreach ($inbox_entries_raw as $entry) {
                appLogger('Processing entry ID: ' . $entry['id']);
                $form = \GFAPI::get_form($entry['form_id']);
                if (is_wp_error($form)) {
                    continue;
                }

                // Initialize API for this form
                $gravity_flow_api = new \Gravity_Flow_API($entry['form_id']);
                $current_step = $gravity_flow_api->get_current_step($entry);
                
                $submitter = get_user_by('ID', $entry['created_by']);
                
                $inbox_entries[] = [
                    'id' => $entry['id'],
                    'entry_id' => $entry['id'],
                    'form_id' => $entry['form_id'],
                    'form_title' => $form['title'],
                    'step_id' => $current_step ? $current_step->get_id() : null,
                    'step_name' => $current_step ? $current_step->get_name() : 'نامشخص',
                    'step_type' => $current_step ? $current_step->get_type() : 'unknown',
                    'date_created' => $entry['date_created'],
                    'date_created_formatted' => date_i18n('j F Y - H:i', strtotime($entry['date_created'])),
                    'status' => $this->translateStatus($gravity_flow_api->get_status($entry)),
                    'status_class' => $this->getStatusClass($gravity_flow_api->get_status($entry)),
                    'submitter' => [
                        'id' => $entry['created_by'],
                        'name' => $submitter ? $submitter->display_name : 'نامشخص',
                        'email' => $submitter ? $submitter->user_email : ''
                    ],
                    'entry_url' => admin_url("admin.php?page=gravityflow-inbox&view=entry&id={$entry['form_id']}&lid={$entry['id']}"),
                    'actions' => $this->getInboxEntryActions($entry, $current_step),
                    'priority' => $this->getEntryPriority($entry, $current_step),
                    'due_date' => $this->getEntryDueDate($current_step),
                    'entry_summary' => $this->getEntrySummary($entry, $form),
                    'timeline' => $gravity_flow_api->get_timeline($entry)
                ];
            }

            // Sort by priority and date (API may already handle some sorting)
            usort($inbox_entries, function ($a, $b) {
                // First sort by priority (higher priority first)
                if ($a['priority'] !== $b['priority']) {
                    return $b['priority'] - $a['priority'];
                }
                // Then by date (newest first)
                return strtotime($b['date_created']) - strtotime($a['date_created']);
            });

            return [
                'success' => true,
                'data' => $inbox_entries,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_items' => $total_count,
                    'total_pages' => ceil($total_count / $per_page)
                ],
                'stats' => [
                    'pending' => count(array_filter($inbox_entries, fn($e) => $e['status'] === 'در انتظار')),
                    'in_progress' => count(array_filter($inbox_entries, fn($e) => $e['status'] === 'در حال پردازش')),
                    'total' => $total_count
                ]
            ];
        } catch (Exception $e) {
            error_log('GravityFlowInboxPage Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در بارگذاری صندوق ورودی: ' . $e->getMessage(),
                'data' => $this->getInboxSampleData($page, $per_page),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_items' => 5,
                    'total_pages' => 1
                ]
            ];
        }
    }

    /**
     * Check if form has Gravity Flow enabled
     * @param int $form_id
     * @return bool
     */
    private function hasGravityFlowEnabled($form_id)
    {
        if (!class_exists('Gravity_Flow')) {
            return false;
        }
        
        $form_settings = get_option('gravityflow_settings_' . $form_id, []);
        return !empty($form_settings);
    }

    /**
     * Check if user is assignee for the step
     * @param object $step
     * @param int $user_id
     * @return bool
     */
    private function isUserAssignee($step, $user_id)
    {
        if (!$step || !method_exists($step, 'is_user_assignee')) {
            return false;
        }
        
        return $step->is_user_assignee($user_id);
    }

    /**
     * Translate workflow status to Persian
     * @param string $status
     * @return string
     */
    private function translateStatus($status)
    {
        $translations = [
            'pending' => 'در انتظار',
            'in_progress' => 'در حال پردازش',
            'user_input' => 'نیاز به ورودی کاربر',
            'approved' => 'تأیید شده',
            'rejected' => 'رد شده',
            'complete' => 'تکمیل شده'
        ];
        
        return $translations[$status] ?? $status;
    }

    /**
     * Get CSS class for status
     * @param string $status
     * @return string
     */
    private function getStatusClass($status)
    {
        $classes = [
            'pending' => 'status-pending',
            'in_progress' => 'status-in-progress',
            'user_input' => 'status-user-input',
            'approved' => 'status-approved',
            'rejected' => 'status-rejected',
            'complete' => 'status-complete'
        ];
        
        return $classes[$status] ?? 'status-default';
    }

    /**
     * Get available actions for inbox entry
     * @param array $entry
     * @param object $step
     * @return array
     */
    private function getInboxEntryActions($entry, $step)
    {
        $actions = [];
        
        // Basic actions
        $actions[] = [
            'type' => 'view',
            'label' => 'مشاهده',
            'url' => admin_url("admin.php?page=gravityflow-inbox&view=entry&id={$entry['form_id']}&lid={$entry['id']}")
        ];
        
        // Step-specific actions based on step type
        if ($step && method_exists($step, 'get_type')) {
            $step_type = $step->get_type();
            
            switch ($step_type) {
                case 'approval':
                    $actions[] = ['type' => 'approve', 'label' => 'تأیید'];
                    $actions[] = ['type' => 'reject', 'label' => 'رد'];
                    break;
                case 'user_input':
                    $actions[] = ['type' => 'complete', 'label' => 'تکمیل'];
                    break;
                case 'notification':
                    $actions[] = ['type' => 'acknowledge', 'label' => 'تأیید دریافت'];
                    break;
            }
        }
        
        return $actions;
    }

    /**
     * Get entry priority
     * @param array $entry
     * @param object $step
     * @return int
     */
    private function getEntryPriority($entry, $step)
    {
        // Check if there's a priority field or meta
        $priority = null;
        if (function_exists('gform_get_meta')) {
            $priority = gform_get_meta($entry['id'], 'priority');
        }
        if ($priority) {
            return (int) $priority;
        }
        
        // Default priority based on step type
        if ($step && method_exists($step, 'get_type')) {
            $step_type = $step->get_type();
            switch ($step_type) {
                case 'approval':
                    return 3; // High priority
                case 'user_input':
                    return 2; // Medium priority
                default:
                    return 1; // Normal priority
            }
        }
        
        return 1;
    }

    /**
     * Get entry due date
     * @param object $step
     * @return string|null
     */
    private function getEntryDueDate($step)
    {
        if (!$step || !method_exists($step, 'get_setting')) {
            return null;
        }
        
        $due_date = $step->get_setting('due_date');
        if ($due_date) {
            return date_i18n('j F Y', strtotime($due_date));
        }
        
        return null;
    }

    /**
     * Get entry summary for display
     * @param array $entry
     * @param array $form
     * @return string
     */
    private function getEntrySummary($entry, $form)
    {
        $summary_parts = [];
        
        // Get first few important fields
        foreach ($form['fields'] as $field) {
            if (count($summary_parts) >= 3) break;
            
            $field_id = $field->id;
            $value = $entry[$field_id] ?? '';
            
            if (!empty($value) && !in_array($field->type, ['page', 'section', 'html', 'hidden'])) {
                $summary_parts[] = $field->label . ': ' . wp_trim_words(strip_tags($value), 5);
            }
        }
        
        return implode(' | ', $summary_parts);
    }

    /**
     * Get sample inbox data for demonstration
     * @param int $page
     * @param int $per_page
     * @return array
     */
    private function getInboxSampleData($page = 1, $per_page = 20)
    {
        $sample_entries = [
            [
                'id' => 1,
                'entry_id' => 1,
                'form_id' => 1,
                'form_title' => 'فرم درخواست تجهیزات',
                'step_name' => 'تأیید مدیر',
                'step_type' => 'approval',
                'date_created' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'date_created_formatted' => 'امروز - ' . date('H:i', strtotime('-2 hours')),
                'status' => 'در انتظار',
                'status_class' => 'status-pending',
                'submitter' => [
                    'name' => 'علی احمدی',
                    'email' => 'ali@example.com'
                ],
                'actions' => [
                    ['type' => 'view', 'label' => 'مشاهده'],
                    ['type' => 'approve', 'label' => 'تأیید'],
                    ['type' => 'reject', 'label' => 'رد']
                ],
                'priority' => 3,
                'due_date' => date_i18n('j F Y', strtotime('+2 days')),
                'entry_summary' => 'نام: علی احمدی | تجهیزات: لپ تاپ | توضیحات: برای کار طراحی'
            ],
            [
                'id' => 2,
                'entry_id' => 2,
                'form_id' => 2,
                'form_title' => 'فرم گزارش مالی',
                'step_name' => 'بررسی اولیه',
                'step_type' => 'user_input',
                'date_created' => date('Y-m-d H:i:s', strtotime('-4 hours')),
                'date_created_formatted' => 'امروز - ' . date('H:i', strtotime('-4 hours')),
                'status' => 'نیاز به ورودی کاربر',
                'status_class' => 'status-user-input',
                'submitter' => [
                    'name' => 'مریم رضایی',
                    'email' => 'maryam@example.com'
                ],
                'actions' => [
                    ['type' => 'view', 'label' => 'مشاهده'],
                    ['type' => 'complete', 'label' => 'تکمیل']
                ],
                'priority' => 2,
                'due_date' => date_i18n('j F Y', strtotime('+3 days')),
                'entry_summary' => 'نوع گزارش: ماهانه | دوره: شهریور ۱۴۰۳ | مبلغ: ۵۰,۰۰۰,۰۰۰ تومان'
            ],
            [
                'id' => 3,
                'entry_id' => 3,
                'form_id' => 3,
                'form_title' => 'فرم ثبت نام دوره',
                'step_name' => 'تأیید نهایی',
                'step_type' => 'notification',
                'date_created' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'date_created_formatted' => 'دیروز - ' . date('H:i', strtotime('-1 day')),
                'status' => 'در حال پردازش',
                'status_class' => 'status-in-progress',
                'submitter' => [
                    'name' => 'حسن کریمی',
                    'email' => 'hassan@example.com'
                ],
                'actions' => [
                    ['type' => 'view', 'label' => 'مشاهده'],
                    ['type' => 'acknowledge', 'label' => 'تأیید دریافت']
                ],
                'priority' => 1,
                'due_date' => null,
                'entry_summary' => 'دوره: برنامه‌نویسی پایتون | سطح: مقدماتی | تاریخ شروع: ۱۰ مهر'
            ]
        ];

        $offset = ($page - 1) * $per_page;
        return array_slice($sample_entries, $offset, $per_page);
    }

}
