<?php

namespace App\Services;

use Exception;
use Kernel\DB;

class SessionScoresService
{
    protected $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get entries from GravityView/GravityForms with calculated scores
     * 
     * @param array $params Parameters including form_id, secret, pagination, etc.
     * @return array
     */
    public function getSessionScoresEntries($params = [])
    {
        $form_id = isset($params['form_id']) ? intval($params['form_id']) : null;
        $view_id = isset($params['view_id']) ? intval($params['view_id']) : null;
        
        // If form_id is not provided, try to get it from view_id
        if (!$form_id && $view_id) {
            $form_id = get_post_meta($view_id, '_gravityview_form_id', true);
            if (!$form_id) {
                error_log("SessionScoresService: Could not find form_id for view_id {$view_id}");
                $per_page = isset($params['per_page']) ? intval($params['per_page']) : 20;
                $page = isset($params['page']) ? intval($params['page']) : 1;
                return $this->getSampleData($per_page, $page);
            }
        }
        
        if (!$form_id) {
            error_log("SessionScoresService: No form_id or view_id provided");
            $per_page = isset($params['per_page']) ? intval($params['per_page']) : 20;
            $page = isset($params['page']) ? intval($params['page']) : 1;
            return $this->getSampleData($per_page, $page);
        }
        $per_page = isset($params['per_page']) ? intval($params['per_page']) : 20;
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $sort_by_sum = isset($params['sort_by_sum']) ? $params['sort_by_sum'] === 'true' : true;
        $sort_order = isset($params['sort_order']) ? strtoupper($params['sort_order']) : 'DESC';

        // Check if GravityForms is available
        if (!class_exists('GFForms') || !class_exists('GFAPI')) {
            return $this->getSampleData($per_page, $page);
        }

        try {
            // Get form data
            $form = \GFAPI::get_form($form_id);
            if (!$form) {
                throw new Exception("Form with ID {$form_id} not found");
            }

            // Get entries
            $search_criteria = [
                'status' => 'active'
            ];

            $sorting = [
                'key' => 'date_created',
                'direction' => 'DESC'
            ];

            $paging = [
                'offset' => 0,  // We'll handle pagination after processing
                'page_size' => 1000  // Get all entries for processing
            ];

            $entries = \GFAPI::get_entries($form_id, $search_criteria, $sorting, $paging);

            if (is_wp_error($entries)) {
                throw new Exception('Error retrieving entries: ' . $entries->get_error_message());
            }

            // Process entries and calculate scores
            $processed_entries = $this->processEntriesWithScores($entries, $form, $view_id);

            // Sort by sum if required
            if ($sort_by_sum) {
                usort($processed_entries, function($a, $b) use ($sort_order) {
                    $comparison = $a['sum_score'] <=> $b['sum_score'];
                    return $sort_order === 'DESC' ? -$comparison : $comparison;
                });
            }

            // Apply pagination
            $total_count = count($processed_entries);
            $offset = ($page - 1) * $per_page;
            $paginated_entries = array_slice($processed_entries, $offset, $per_page);

            return [
                'success' => true,
                'data' => $paginated_entries,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_items' => $total_count,
                    'total_pages' => ceil($total_count / $per_page)
                ],
                'form_title' => $form['title']
            ];

        } catch (Exception $e) {
            error_log('SessionScoresService Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Process entries and calculate scores based on the GravityView configuration
     */
    private function processEntriesWithScores($entries, $form, $view_id = null)
    {
        $processed = [];
        
        // Get visible fields from GravityView configuration
        $visible_fields = $this->getVisibleFieldsFromView($view_id, $form);
        
        // Filter summable fields
        $summable_fields = array_filter($visible_fields, function($field) {
            return $field['is_summable'];
        });

        foreach ($entries as $entry) {
            // Extract the required data
            $processed_entry = [
                'id' => $entry['id'],
                'date_created' => date_i18n('Y/m/d H:i', strtotime($entry['date_created'])),
                'form_id' => $entry['form_id'],
                'entry_data' => [],
                'visible_fields' => $visible_fields
            ];

            // Extract field values for all visible fields
            foreach ($visible_fields as $field_info) {
                $field_id = $field_info['field_id'];
                $field_label = $field_info['field_label'];
                $value = isset($entry[$field_id]) ? $entry[$field_id] : '';
                $processed_entry['entry_data'][$field_label] = $value;
            }

            // Calculate sum of scores for summable fields only
            $sum_score = 0;
            
            foreach ($summable_fields as $field_info) {
                $field_value = isset($entry[$field_info['field_id']]) ? $entry[$field_info['field_id']] : '';
                if (is_numeric($field_value)) {
                    $sum_score += floatval($field_value);
                }
            }
            
            $processed_entry['sum_score'] = $sum_score;
            $processed_entry['entry_data']['جمع امتیازها'] = $sum_score;
            $processed_entry['summable_fields'] = $summable_fields;

            $processed[] = $processed_entry;
        }

        return $processed;
    }

    /**
     * Map field labels to field IDs based on form structure
     */
    private function getFieldMapping($form)
    {
        $mapping = [];

        // Loop through form fields to create accurate mapping
        if (isset($form['fields']) && is_array($form['fields'])) {
            foreach ($form['fields'] as $field) {
                if (isset($field->label) && isset($field->id)) {
                    $mapping[$field->label] = (string)$field->id;
                }
            }
        }

        // Fallback mapping if form inspection fails
        if (empty($mapping)) {
            $mapping = [
                'نام پر کننده' => '1',
                'نقش' => '2',
                'نام مدرسه' => '3',
                'کد مدرسه' => '4',
                'نام مدیر' => '5',
                'بهسازی سالن' => '6',
                'جلسه والدین' => '7',
                'غنی سازی زنگ تفریح' => '8'
            ];
        }
        
        return $mapping;
    }

    /**
     * Get summable fields from GravityView configuration
     * Looks for fields that have footer calculations enabled
     */
    private function getSummableFieldsFromView($view_id, $form)
    {
        $summable_fields = [];

        if ($view_id && function_exists('get_post_meta')) {
            // Get GravityView directory fields configuration
            $directory_fields = get_post_meta($view_id, '_gravityview_directory_fields', true);
            
            if (is_array($directory_fields)) {
                // Loop through all zones (directory_table, single_table, etc.)
                foreach ($directory_fields as $zone => $zone_fields) {
                    if (is_array($zone_fields)) {
                        foreach ($zone_fields as $field_key => $field_config) {
                            // Check if this field has math/calculation settings enabled
                            if ($this->isFieldSummable($field_config)) {
                                $field_id = $field_config['id'] ?? '';
                                $field_label = $this->getFieldLabel($field_id, $form);
                                
                                if ($field_id && $field_label) {
                                    $summable_fields[] = [
                                        'field_id' => $field_id,
                                        'field_label' => $field_label,
                                        'field_config' => $field_config
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        // Fallback: if no GravityView config found, use numeric fields from form
        if (empty($summable_fields) && isset($form['fields'])) {
            foreach ($form['fields'] as $field) {
                if ($this->isNumericField($field)) {
                    $summable_fields[] = [
                        'field_id' => (string)$field->id,
                        'field_label' => $field->label,
                        'field_config' => []
                    ];
                }
            }
        }

        return $summable_fields;
    }

    /**
     * Get all visible fields from GravityView configuration
     */
    public function getVisibleFieldsFromView($view_id, $form)
    {
        $visible_fields = [];

        if ($view_id && function_exists('get_post_meta')) {
            // Get GravityView directory fields configuration
            $directory_fields = get_post_meta($view_id, '_gravityview_directory_fields', true);
            
            if (is_array($directory_fields)) {
                // Focus on directory_table zone for table view
                $table_fields = $directory_fields['directory_table'] ?? [];
                
                if (is_array($table_fields)) {
                    foreach ($table_fields as $field_key => $field_config) {
                        $field_id = $field_config['id'] ?? '';
                        $field_label = $this->getFieldLabel($field_id, $form);
                        
                        if ($field_id && $field_label) {
                            $visible_fields[] = [
                                'field_id' => $field_id,
                                'field_label' => $field_label,
                                'field_config' => $field_config,
                                'is_summable' => $this->isFieldSummable($field_config)
                            ];
                        }
                    }
                }
            }
        }

        // Fallback: if no GravityView config found, use all form fields
        if (empty($visible_fields) && isset($form['fields'])) {
            foreach ($form['fields'] as $field) {
                $visible_fields[] = [
                    'field_id' => (string)$field->id,
                    'field_label' => $field->label,
                    'field_config' => [],
                    'is_summable' => $this->isNumericField($field)
                ];
            }
        }

        return $visible_fields;
    }

    /**
     * Check if a field configuration indicates it should be included in footer calculations
     */
    private function isFieldSummable($field_config)
    {
        // Check various possible settings that indicate footer calculations
        if (isset($field_config['show_math'])) {
            return $field_config['show_math'] === '1' || $field_config['show_math'] === true;
        }

        if (isset($field_config['math'])) {
            return $field_config['math'] === '1' || $field_config['math'] === true;
        }

        if (isset($field_config['footer_calculation'])) {
            return $field_config['footer_calculation'] === '1' || $field_config['footer_calculation'] === true;
        }

        if (isset($field_config['add_to_footer'])) {
            return $field_config['add_to_footer'] === '1' || $field_config['add_to_footer'] === true;
        }

        // Check for any setting that might indicate calculations
        if (is_array($field_config)) {
            foreach ($field_config as $key => $value) {
                if (strpos(strtolower($key), 'math') !== false || 
                    strpos(strtolower($key), 'calc') !== false || 
                    strpos(strtolower($key), 'sum') !== false ||
                    strpos(strtolower($key), 'total') !== false) {
                    return $value === '1' || $value === true;
                }
            }
        }

        return false;
    }

    /**
     * Get field label by field ID from form
     */
    private function getFieldLabel($field_id, $form)
    {
        if (isset($form['fields'])) {
            foreach ($form['fields'] as $field) {
                if ((string)$field->id === (string)$field_id) {
                    return $field->label;
                }
            }
        }
        return '';
    }

    /**
     * Check if a field is numeric and suitable for calculations
     */
    private function isNumericField($field)
    {
        $numeric_types = ['number', 'calculation', 'product', 'quantity', 'price', 'total', 'select', 'radio'];
        return in_array($field->type, $numeric_types);
    }

    /**
     * Export selected entries to CSV
     */
    public function exportSelectedEntriesToCSV($entry_ids = [], $params = [])
    {
        try {
            $view_id = $params['view_id'] ?? null;
            
            // If no specific entries selected, export all
            if (empty($entry_ids)) {
                $entries_result = $this->getSessionScoresEntries([
                    'per_page' => 1000,  // Large number to get all
                    'page' => 1,
                    'view_id' => $view_id
                ]);
                $entries = $entries_result['data'];
            } else {
                // Get specific entries
                $entries = $this->getEntriesByIds($entry_ids, $view_id);
            }

            if (empty($entries)) {
                throw new Exception('No entries found for export');
            }

            // Prepare CSV data
            $csv_data = $this->prepareCsvData($entries);
            
            return [
                'success' => true,
                'data' => $csv_data,
                'filename' => 'session-scores-' . date('Y-m-d-H-i-s') . '.csv'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Prepare summary data for CSV export
     */
    public function prepareSummaryData($column_totals_result)
    {
        try {
            if (!$column_totals_result['success']) {
                throw new Exception('Column totals data not available');
            }

            $column_totals = $column_totals_result['data'];
            $total_entries_count = $column_totals_result['total_entries'];

            // Prepare CSV data for summary
            $csv_data = [];
            
            // Add headers
            $csv_data[] = ['نام ستون', 'مجموع', 'تعداد ورودی‌ها'];
            
            // Add column totals
            foreach ($column_totals as $field_label => $total) {
                $csv_data[] = [
                    $field_label,
                    number_format($total, 2),
                    $total_entries_count
                ];
            }

            return [
                'success' => true,
                'data' => $csv_data,
                'filename' => 'session-scores-summary-' . date('Y-m-d-H-i-s') . '.csv'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get specific entries by IDs
     */
    private function getEntriesByIds($entry_ids, $view_id = null)
    {
        $entries = [];
        
        foreach ($entry_ids as $entry_id) {
            $entry = \GFAPI::get_entry($entry_id);
            if (!is_wp_error($entry)) {
                // Get the form for processing
                $form = \GFAPI::get_form($entry['form_id']);
                if ($form) {
                    $processed = $this->processEntriesWithScores([$entry], $form, $view_id);
                    if (!empty($processed)) {
                        $entries[] = $processed[0];
                    }
                }
            }
        }

        return $entries;
    }

    /**
     * Calculate column totals for all entries (across all pages)
     */
    public function getColumnTotals($params = [])
    {
        try {
            // Get ALL entries without pagination
            $all_params = $params;
            $all_params['per_page'] = 1000; // Large number to get all entries
            $all_params['page'] = 1;
            
            $result = $this->getSessionScoresEntries($all_params);
            
            if (!$result['success'] || empty($result['data'])) {
                return [
                    'success' => false,
                    'message' => 'No data available for totals calculation'
                ];
            }
            
            $entries = $result['data'];
            $column_totals = [];
            
            // Get summable fields from the first entry
            if (!empty($entries)) {
                $summable_fields = $entries[0]['summable_fields'] ?? [];
                
                // Initialize totals for each summable field
                foreach ($summable_fields as $field_info) {
                    $field_label = $field_info['field_label'];
                    $column_totals[$field_label] = 0;
                }
                
                // Calculate totals across all entries
                foreach ($entries as $entry) {
                    foreach ($summable_fields as $field_info) {
                        $field_label = $field_info['field_label'];
                        $field_value = $entry['entry_data'][$field_label] ?? '';
                        
                        if (is_numeric($field_value)) {
                            $column_totals[$field_label] += floatval($field_value);
                        }
                    }
                }
                
                // Add grand total (sum of all sum_scores)
                $grand_total = 0;
                foreach ($entries as $entry) {
                    $grand_total += $entry['sum_score'] ?? 0;
                }
                $column_totals['جمع کل'] = $grand_total;
            }
            
            return [
                'success' => true,
                'data' => $column_totals,
                'total_entries' => count($entries)
            ];
            
        } catch (Exception $e) {
            error_log('SessionScoresService getColumnTotals Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Prepare CSV data from entries
     */
    private function prepareCsvData($entries)
    {
        $csv_data = [];
        
        if (empty($entries)) {
            return $csv_data;
        }

        // Get all field keys from the first entry to build dynamic headers
        $first_entry = $entries[0];
        $headers = ['شناسه', 'تاریخ ایجاد'];
        
        // Add headers for all entry data fields
        if (isset($first_entry['entry_data']) && is_array($first_entry['entry_data'])) {
            foreach ($first_entry['entry_data'] as $field_label => $value) {
                if ($field_label !== 'جمع امتیازها') { // We'll add this at the end
                    $headers[] = $field_label;
                }
            }
        }
        
        // Add sum column at the end
        $headers[] = 'جمع امتیازها';
        $csv_data[] = $headers;

        // Add data rows
        foreach ($entries as $entry) {
            $row = [$entry['id'], $entry['date_created']];
            
            // Add all field values in the same order as headers
            if (isset($entry['entry_data']) && is_array($entry['entry_data'])) {
                foreach ($first_entry['entry_data'] as $field_label => $value) {
                    if ($field_label !== 'جمع امتیازها') {
                        $row[] = $entry['entry_data'][$field_label] ?? '';
                    }
                }
            }
            
            // Add sum score at the end
            $row[] = $entry['sum_score'] ?? 0;
            $csv_data[] = $row;
        }

        return $csv_data;
    }

    /**
     * Sample data for when GravityForms is not available
     */
    private function getSampleData($per_page, $page)
    {
        // Define sample visible fields structure
        $visible_fields = [
            ['field_id' => '1', 'field_label' => 'نام پر کننده', 'field_config' => [], 'is_summable' => false],
            ['field_id' => '2', 'field_label' => 'نقش', 'field_config' => [], 'is_summable' => false],
            ['field_id' => '3', 'field_label' => 'نام مدرسه', 'field_config' => [], 'is_summable' => false],
            ['field_id' => '4', 'field_label' => 'کد مدرسه', 'field_config' => [], 'is_summable' => false],
            ['field_id' => '5', 'field_label' => 'نام مدیر', 'field_config' => [], 'is_summable' => false],
            ['field_id' => '6', 'field_label' => 'بهسازی سالن', 'field_config' => ['show_math' => '1'], 'is_summable' => true],
            ['field_id' => '7', 'field_label' => 'جلسه والدین', 'field_config' => ['show_math' => '1'], 'is_summable' => true],
            ['field_id' => '8', 'field_label' => 'غنی سازی زنگ تفریح', 'field_config' => ['show_math' => '1'], 'is_summable' => true]
        ];

        $summable_fields = array_filter($visible_fields, function($field) {
            return $field['is_summable'];
        });

        $sample_entries = [
            [
                'id' => 1,
                'date_created' => date('Y/m/d H:i'),
                'form_id' => 19809,
                'entry_data' => [
                    'نام پر کننده' => 'علی احمدی',
                    'نقش' => 'مدیر',
                    'نام مدرسه' => 'دبستان شهید بهشتی',
                    'کد مدرسه' => '12345',
                    'نام مدیر' => 'حسن رضایی',
                    'بهسازی سالن' => '85',
                    'جلسه والدین' => '90',
                    'غنی سازی زنگ تفریح' => '75',
                    'جمع امتیازها' => 250
                ],
                'sum_score' => 250,
                'visible_fields' => $visible_fields,
                'summable_fields' => $summable_fields
            ],
            [
                'id' => 2,
                'date_created' => date('Y/m/d H:i'),
                'form_id' => 19809,
                'entry_data' => [
                    'نام پر کننده' => 'فاطمه محمدی',
                    'نقش' => 'معاون',
                    'نام مدرسه' => 'دبیرستان فردوسی',
                    'کد مدرسه' => '67890',
                    'نام مدیر' => 'مریم کریمی',
                    'بهسازی سالن' => '70',
                    'جلسه والدین' => '80',
                    'غنی سازی زنگ تفریح' => '85',
                    'جمع امتیازها' => 235
                ],
                'sum_score' => 235,
                'visible_fields' => $visible_fields,
                'summable_fields' => $summable_fields
            ]
        ];

        // Apply pagination to sample data
        $total_count = count($sample_entries);
        $offset = ($page - 1) * $per_page;
        $paginated_entries = array_slice($sample_entries, $offset, $per_page);

        return [
            'success' => true,
            'data' => $paginated_entries,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_count,
                'total_pages' => ceil($total_count / $per_page)
            ],
            'form_title' => 'نمونه فرم امتیازدهی جلسات'
        ];
    }
}