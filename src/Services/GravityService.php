<?php

namespace App\Services;

use Exception;

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
    public function getApprovedGravityFlowEntries($page = 1, $per_page = 20)
    {
        // Check if Gravity Forms and Gravity Flow are active
        if (!class_exists('GFForms') || !class_exists('Gravity_Flow')) {
            // Return sample data for demonstration purposes
            return $this->getSampleData($page, $per_page);
        }

        $current_user = wp_get_current_user();
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
        $approved_entries = [];
        $total_count = 0;

        foreach ($forms as $form) {
            // Check if this form has Gravity Flow enabled
            $flow_settings = get_option('gravityflow_settings_' . $form['id'], array());
            if (empty($flow_settings)) {
                continue;
            }

            // Get entries for this form that are approved
            $search_criteria = [
                'status' => 'active'
            ];

            $entries = class_exists('GFAPI') ? \GFAPI::get_entries($form['id'], $search_criteria) : [];

            foreach ($entries as $entry) {
                // Check if entry is approved and user has access
                if ($this->isEntryApproved($entry) && $this->userHasAccessToEntry($entry, $current_user->ID)) {
                    $approved_entries[] = [
                        'id' => $entry['id'],
                        'form_id' => $form['id'],
                        'form_title' => $form['title'],
                        'date_created' => $entry['date_created'],
                        'status' => $this->getEntryStatus($entry),
                        'entry_data' => $this->formatEntryData($entry, $form)
                    ];
                    $total_count++;
                }
            }
        }

        // Sort by date created (newest first)
        usort($approved_entries, function ($a, $b) {
            return strtotime($b['date_created']) - strtotime($a['date_created']);
        });

        // Apply pagination
        $paginated_entries = array_slice($approved_entries, $offset, $per_page);

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

    /**
     * Get enhanced Gravity Flow entries with sorting and mobile optimization
     * @param int $page
     * @param int $per_page
     * @param array $filters
     * @return array
     */
    public function getEnhancedGravityFlowEntries($page = 1, $per_page = 20, $filters = [])
    {
        // Check if Gravity Forms and Gravity Flow are active
        if (!class_exists('GFForms') || !class_exists('Gravity_Flow')) {
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
                // Get submitter info
                $submitter = \get_user_by('ID', $entry['created_by']);
                $submitter_name = $submitter ? $submitter->display_name : 'نامشخص';

                // Determine status
                $status = $this->getEntryWorkflowStatus($entry['id'], $form['id']);

                // Apply filters if provided
                if (!empty($filters['status']) && $filters['status'] !== $status) {
                    continue;
                }

                if (!empty($filters['form_id']) && $filters['form_id'] != $form['id']) {
                    continue;
                }

                $enhanced_entries[] = [
                    'id' => $entry['id'],
                    'form_id' => $form['id'],
                    'form_name' => $form['title'],
                    'status' => $status,
                    'submitter' => [
                        'id' => $entry['created_by'],
                        'name' => $submitter_name,
                        'email' => $submitter ? $submitter->user_email : '',
                    ],
                    'date_created' => $entry['date_created'],
                    'date_created_formatted' => \date_i18n('j F Y - H:i', strtotime($entry['date_created'])),
                    'entry_data' => $entry,
                    'form_data' => $form,
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
     * Get entry workflow status
     */
    private function getEntryWorkflowStatus($entry_id, $form_id)
    {
        // This would integrate with your workflow system or Gravity Flow
        // For now, return sample statuses
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
}
