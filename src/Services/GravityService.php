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
                        'date_created' => $entry['date_created'],
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

    private function isFormApprovedByUser($form,$entry, $user_id)
    {
        foreach ($form['fields'] as $field) {
            if($field->type == 'workflow_user'){
                $field_id = $field->id;
                $field_label = $field->label;
                $field_value = isset($entry[$field_id]) ? $entry[$field_id] : '';
                if ($field_value == $user_id) {
                    return true;
                }
                break;
            }
        }
        return false;
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
     * Check if entry is approved
     * @param array $entry
     * @return bool
     */
    private function isEntryApproved($entry)
    {
        // appLogger('GravityService: Checking approval status for entry ID: ' . $entry['id']);
        
        // Check different possible indicators for approved status
        if (isset($entry['workflow_final_status']) && $entry['workflow_final_status'] === 'approved') {
            // appLogger('GravityService: Entry ' . $entry['id'] . ' approved via workflow_final_status');
            return true;
        }

        if (isset($entry['gravityflow_status']) && $entry['gravityflow_status'] === 'approved') {
            // appLogger('GravityService: Entry ' . $entry['id'] . ' approved via gravityflow_status');
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
            // appLogger('GravityService: Entry ' . $entry_id . ' step status from meta: ' . $step_status);
        }

        if ($step_status === 'approved' || $step_status === 'complete') {
            // appLogger('GravityService: Entry ' . $entry['id'] . ' approved via step status: ' . $step_status);
            return true;
        }

        // appLogger('GravityService: Entry ' . $entry['id'] . ' is NOT approved. Available fields: ' . implode(', ', array_keys($entry)));
        return false;
    }

    /**
     * Get entry status
     * @param array $entry
     * @return string
     */
    private function getEntryStatus($entry)
    {
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
        // appLogger('GravityService: Checking access for user ID ' . $user_id . ' to entry ID ' . $entry['id']);
        
        // For this implementation, we'll check if the user created the entry
        // or if they are an admin. You can modify this logic based on your needs.
        if (current_user_can('manage_options')) {
            // appLogger('GravityService: User ' . $user_id . ' has admin access to entry ' . $entry['id']);
            return true;
        }

        // Check if user created this entry
        if (isset($entry['created_by']) && $entry['created_by'] == $user_id) {
            // appLogger('GravityService: User ' . $user_id . ' created entry ' . $entry['id']);
            return true;
        }

        // Additional checks can be added here based on your workflow requirements
        // For example, checking if user was assigned to approve this entry
        
        // appLogger('GravityService: User ' . $user_id . ' does NOT have access to entry ' . $entry['id'] . '. Entry created_by: ' . (isset($entry['created_by']) ? $entry['created_by'] : 'not set'));

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
    public function exportApprovedEntriesToCSV($user = null)
    {
        // Get all entries without pagination
        $all_entries_result = $this->getApprovedGravityFlowEntries(1, 1000, $user);
        appLogger(json_encode($all_entries_result));
        $entries = $all_entries_result['data'];

        if (empty($entries)) {
            return [
                'success' => false,
                'message' => 'هیچ ورودی تأیید شده‌ای یافت نشد.'
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
                date('Y/m/d H:i', strtotime($entry['date_created'])),
                'تأیید شده',
                $form_data
            ];
        }

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
}
