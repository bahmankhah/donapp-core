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
        $form_id = isset($params['form_id']) ? intval($params['form_id']) : 19809;
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
            $processed_entries = $this->processEntriesWithScores($entries, $form);

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
     * Process entries and calculate scores based on the specified columns
     */
    private function processEntriesWithScores($entries, $form)
    {
        $processed = [];
        
        // Map field labels to field IDs (we'll need to identify these from the form structure)
        $field_mapping = $this->getFieldMapping($form);

        foreach ($entries as $entry) {
            // Extract the required data
            $processed_entry = [
                'id' => $entry['id'],
                'date_created' => date_i18n('Y/m/d H:i', strtotime($entry['date_created'])),
                'form_id' => $entry['form_id'],
                'entry_data' => []
            ];

            // Extract field values
            foreach ($field_mapping as $label => $field_id) {
                $value = isset($entry[$field_id]) ? $entry[$field_id] : '';
                $processed_entry['entry_data'][$label] = $value;
            }

            // Calculate sum of scores for the last three columns
            $score_fields = ['بهسازی سالن', 'جلسه والدین', 'غنی سازی زنگ تفریح'];
            $sum_score = 0;
            
            foreach ($score_fields as $field_label) {
                if (isset($processed_entry['entry_data'][$field_label])) {
                    $score = floatval($processed_entry['entry_data'][$field_label]);
                    $sum_score += $score;
                }
            }
            
            $processed_entry['sum_score'] = $sum_score;
            $processed_entry['entry_data']['جمع امتیازها'] = $sum_score;

            $processed[] = $processed_entry;
        }

        return $processed;
    }

    /**
     * Map field labels to field IDs based on form structure
     * This is a simplified version - in reality you'd inspect the form to find the exact field IDs
     */
    private function getFieldMapping($form)
    {
        $mapping = [
            'نام پر کننده' => '1',      // Adjust these field IDs based on actual form structure
            'نقش' => '2',
            'نام مدرسه' => '3',
            'کد مدرسه' => '4',
            'نام مدیر' => '5',
            'بهسازی سالن' => '6',
            'جلسه والدین' => '7',
            'غنی سازی زنگ تفریح' => '8'
        ];

        // In a real implementation, you'd loop through $form['fields'] 
        // and match labels to find the correct field IDs
        // This is a placeholder mapping
        
        return $mapping;
    }

    /**
     * Export selected entries to CSV
     */
    public function exportSelectedEntriesToCSV($entry_ids = [])
    {
        try {
            // If no specific entries selected, export all
            if (empty($entry_ids)) {
                $entries_result = $this->getSessionScoresEntries([
                    'per_page' => 1000,  // Large number to get all
                    'page' => 1
                ]);
                $entries = $entries_result['data'];
            } else {
                // Get specific entries
                $entries = $this->getEntriesByIds($entry_ids);
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
     * Get specific entries by IDs
     */
    private function getEntriesByIds($entry_ids)
    {
        $entries = [];
        
        foreach ($entry_ids as $entry_id) {
            $entry = \GFAPI::get_entry($entry_id);
            if (!is_wp_error($entry)) {
                // Get the form for processing
                $form = \GFAPI::get_form($entry['form_id']);
                if ($form) {
                    $processed = $this->processEntriesWithScores([$entry], $form);
                    if (!empty($processed)) {
                        $entries[] = $processed[0];
                    }
                }
            }
        }

        return $entries;
    }

    /**
     * Prepare CSV data from entries
     */
    private function prepareCsvData($entries)
    {
        $csv_data = [];
        
        // CSV headers
        $headers = [
            'شناسه',
            'تاریخ ایجاد',
            'نام پر کننده',
            'نقش',
            'نام مدرسه',
            'کد مدرسه',
            'نام مدیر',
            'بهسازی سالن',
            'جلسه والدین',
            'غنی سازی زنگ تفریح',
            'جمع امتیازها'
        ];
        
        $csv_data[] = $headers;

        // Add data rows
        foreach ($entries as $entry) {
            $row = [
                $entry['id'],
                $entry['date_created'],
                $entry['entry_data']['نام پر کننده'] ?? '',
                $entry['entry_data']['نقش'] ?? '',
                $entry['entry_data']['نام مدرسه'] ?? '',
                $entry['entry_data']['کد مدرسه'] ?? '',
                $entry['entry_data']['نام مدیر'] ?? '',
                $entry['entry_data']['بهسازی سالن'] ?? '',
                $entry['entry_data']['جلسه والدین'] ?? '',
                $entry['entry_data']['غنی سازی زنگ تفریح'] ?? '',
                $entry['sum_score']
            ];
            
            $csv_data[] = $row;
        }

        return $csv_data;
    }

    /**
     * Sample data for when GravityForms is not available
     */
    private function getSampleData($per_page, $page)
    {
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
                'sum_score' => 250
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
                'sum_score' => 235
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