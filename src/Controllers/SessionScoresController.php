<?php

namespace App\Controllers;

use Kernel\Container;
use Exception;
use App\Utils\Export\ExportFactory;

class SessionScoresController
{
    private $sessionScoresService;

    public function __construct()
    {
        $this->sessionScoresService = Container::resolve('SessionScoresService');
    }

    /**
     * Render the session scores table
     */
    public function renderTable($atts)
    {
        try {
            // Prepare parameters from shortcode attributes
            $params = [
                'form_id' => intval($atts['form_id']),
                'view_id' => isset($atts['view_id']) ? intval($atts['view_id']) : null,
                'per_page' => intval($atts['per_page']),
                'page' => isset($_GET['paged']) ? intval($_GET['paged']) : 1,
                'sort_by_sum' => $atts['sort_by_sum'] === 'true',
                'sort_order' => $atts['sort_order']
            ];

            // Get entries data
            $result = $this->sessionScoresService->getSessionScoresEntries($params);

            if (!$result['success']) {
                return '<div class="donap-error">خطا در دریافت اطلاعات: ' . esc_html($result['message']) . '</div>';
            }

            // Get visible fields from the first entry to build dynamic columns
            $visible_fields = [];
            $summable_fields = [];
            
            if (!empty($result['data'])) {
                $first_entry = $result['data'][0];
                if (isset($first_entry['visible_fields'])) {
                    $visible_fields = $first_entry['visible_fields'];
                }
                if (isset($first_entry['summable_fields'])) {
                    $summable_fields = $first_entry['summable_fields'];
                }
            }

            // Build columns array that the view expects
            $columns = ['checkbox' => ($atts['show_checkboxes'] === 'true')];
            foreach ($visible_fields as $field_info) {
                $field_label = $field_info['field_label'];
                $columns[$field_label] = true;
            }
            
            // Add sum column if enabled and there are summable fields
            if ($atts['show_sum_column'] === 'true' && !empty($summable_fields)) {
                $columns['جمع امتیازها'] = true;
            }

            // Get column totals for summary table (only if needed)
            $column_totals = [];
            $total_entries_count = 0;
            if ($atts['show_summary_table'] === 'true' && !empty($summable_fields)) {
                $column_totals_result = $this->sessionScoresService->getColumnTotals($params);
                $column_totals = $column_totals_result['success'] ? $column_totals_result['data'] : [];
                $total_entries_count = $column_totals_result['success'] ? $column_totals_result['total_entries'] : 0;
            }

            // Prepare data for view
            $view_data = [
                'entries' => $result['data'],
                'columns' => $columns,
                'pagination' => $result['pagination'],
                'form_title' => $result['form_title'] ?? 'جدول امتیازات جلسات',
                'atts' => $atts,
                'nonce' => wp_create_nonce('donap_export_scores'),
                'view_id' => $atts['view_id'] ?? '',
                'visible_fields' => $visible_fields,
                'summable_fields' => $summable_fields,
                'column_totals' => $column_totals,
                'total_entries_count' => $total_entries_count
            ];

            // Return the rendered view
            return view('shortcodes/session-scores-table', $view_data);

        } catch (Exception $e) {
            error_log('SessionScoresController renderTable Error: ' . $e->getMessage());
            return '<div class="donap-error">خطا در نمایش جدول</div>';
        }
    }

    /**
     * Handle CSV export via AJAX
     */
    public function handleExport()
    {
        try {
            // Get selected entry IDs from POST data
            $selected_ids = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
            $view_id = isset($_POST['view_id']) ? intval($_POST['view_id']) : null;
            
            // Validate and sanitize IDs
            $entry_ids = [];
            if (is_array($selected_ids)) {
                foreach ($selected_ids as $id) {
                    $entry_ids[] = intval($id);
                }
            }

            // Get the export data from service
            $export_result = $this->sessionScoresService->exportSelectedEntriesToCSV($entry_ids, ['view_id' => $view_id]);

            if (!$export_result['success']) {
                wp_send_json_error(['message' => $export_result['message']]);
                return;
            }

            // Use the CSV helper from ExportFactory
            $csvExporter = ExportFactory::createCsvExporter();
            
            // Set data for the CSV exporter
            $csvExporter->setData($export_result['data']);
            $csvExporter->setTitle('Session Scores Export');
            
            // Generate the CSV
            $csvResult = $csvExporter->generate();
            
            if (!$csvResult['success']) {
                wp_send_json_error(['message' => 'Failed to generate CSV: ' . $csvResult['message']]);
                return;
            }

            // Serve the CSV file for download
            $csvExporter->serve($csvResult['data'], $csvResult['filename']);

        } catch (Exception $e) {
            error_log('SessionScoresController handleExport Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Export failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Get entries for AJAX requests (for dynamic loading, filtering, etc.)
     */
    public function getEntriesAjax()
    {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'donap_session_scores')) {
                wp_send_json_error(['message' => 'Security check failed']);
                return;
            }

            $params = [
                'form_id' => intval($_POST['form_id']),
                'per_page' => intval($_POST['per_page']),
                'page' => intval($_POST['page']),
                'sort_by_sum' => $_POST['sort_by_sum'] === 'true',
                'sort_order' => sanitize_text_field($_POST['sort_order'])
            ];

            $result = $this->sessionScoresService->getSessionScoresEntries($params);

            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error(['message' => $result['message']]);
            }

        } catch (Exception $e) {
            error_log('SessionScoresController getEntriesAjax Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Request failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Export session scores to CSV
     * POST /wp-json/donapp/v1/session-scores/export
     */
    public function export()
    {
        try {
            // Get POST data
            $request_body = file_get_contents('php://input');
            $data = json_decode($request_body, true) ?: [];
            
            // Get parameters
            $view_id = isset($data['view_id']) ? intval($data['view_id']) : null;
            $form_id = isset($data['form_id']) ? intval($data['form_id']) : null;
            $rows = isset($data['rows']) ? $data['rows'] : [];
            $export_type = isset($data['type']) && $data['type'] === 'summary' ? 'summary' : 'entries';

            // Validate required parameters
            if (!$view_id && !$form_id) {
                wp_send_json_error([
                    'message' => 'view_id یا form_id الزامی است'
                ], 400);
                return;
            }

            if ($export_type === 'summary') {
                // Export summary (column totals)
                $this->exportSummary($view_id, $form_id);
            } else {
                // Export entries (selected or all)
                $this->exportEntries($view_id, $form_id, $rows);
            }

        } catch (Exception $e) {
            error_log('SessionScoresController Export Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'خطا در اکسپورت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export summary data (column totals)
     */
    private function exportSummary(?int $view_id, ?int $form_id): void
    {
        try {
            // Get column totals from service
            $params = array_filter([
                'view_id' => $view_id,
                'form_id' => $form_id
            ]);

            $column_totals_result = $this->sessionScoresService->getColumnTotals($params);

            if (!$column_totals_result['success']) {
                wp_send_json_error([
                    'message' => 'خطا در دریافت مجموع ستون‌ها: ' . $column_totals_result['message']
                ], 500);
                return;
            }

            // Create summary CSV exporter
            $csvExporter = ExportFactory::createSessionScoresSummaryExporter('csv');
            
            // Set data
            $csvExporter->setColumnTotalsData($column_totals_result['data']);
            $csvExporter->setTotalEntriesCount($column_totals_result['total_entries']);

            // Generate CSV
            $result = $csvExporter->generate();

            if (!$result['success']) {
                wp_send_json_error([
                    'message' => 'خطا در تولید CSV: ' . $result['message']
                ], 500);
                return;
            }

            // Serve the file
            $csvExporter->serve($result['data'], $result['filename']);

        } catch (Exception $e) {
            error_log('SessionScoresController exportSummary Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'خطا در اکسپورت خلاصه: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export entries data (selected or all)
     */
    private function exportEntries(?int $view_id, ?int $form_id, array $rows = []): void
    {
        try {
            $params = array_filter([
                'view_id' => $view_id,
                'form_id' => $form_id,
                'per_page' => 1000, // Large number to get all entries
                'page' => 1
            ]);

            // Get entries from service
            if (!empty($rows)) {
                // Get specific entries by IDs
                $entries_result = $this->sessionScoresService->getEntriesByIds($rows, $params);
            } else {
                // Get all entries
                $entries_result = $this->sessionScoresService->getSessionScoresEntries($params);
            }

            if (!$entries_result['success']) {
                wp_send_json_error([
                    'message' => 'خطا در دریافت اطلاعات: ' . $entries_result['message']
                ], 500);
                return;
            }

            $entries = $entries_result['data'];

            if (empty($entries)) {
                wp_send_json_error([
                    'message' => 'هیچ داده‌ای برای اکسپورت یافت نشد'
                ], 404);
                return;
            }

            // Prepare CSV data
            $csv_data = $this->prepareCsvData($entries);

            // Create generic CSV exporter
            $csvExporter = ExportFactory::createCsvExporter();
            
            // Set data directly as tabular format
            $csvExporter->setData($csv_data);
            $csvExporter->setTitle('Session Scores Export');

            // Generate CSV
            $result = $csvExporter->generate();

            if (!$result['success']) {
                wp_send_json_error([
                    'message' => 'خطا در تولید CSV: ' . $result['message']
                ], 500);
                return;
            }

            // Serve the file
            $filename = 'session-scores-' . date('Y-m-d-H-i-s') . '.csv';
            $csvExporter->serve($result['data'], $filename);

        } catch (Exception $e) {
            error_log('SessionScoresController exportEntries Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'خطا در اکسپورت جدول: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prepare CSV data from entries
     */
    private function prepareCsvData(array $entries): array
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
}