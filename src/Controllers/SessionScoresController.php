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
     * Handle summary table CSV export via AJAX
     */
    public function handleSummaryExport()
    {
        try {
            // Get selected column names from POST data
            $selected_columns = isset($_POST['selected_columns']) ? $_POST['selected_columns'] : [];
            $view_id = isset($_POST['view_id']) ? intval($_POST['view_id']) : null;
            $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : null;
            
            // Validate and sanitize column names
            $column_names = [];
            if (is_array($selected_columns)) {
                foreach ($selected_columns as $column) {
                    $column_names[] = sanitize_text_field($column);
                }
            }

            // Get the summary export data from service
            $export_result = $this->sessionScoresService->exportSummaryTableToCSV($column_names, [
                'view_id' => $view_id,
                'form_id' => $form_id
            ]);

            if (!$export_result['success']) {
                wp_send_json_error(['message' => $export_result['message']]);
                return;
            }

            // Use the CSV helper from ExportFactory
            $csvExporter = ExportFactory::createCsvExporter();
            
            // Set data for the CSV exporter
            $csvExporter->setData($export_result['data']);
            $csvExporter->setTitle('Column Totals Summary Export');
            
            // Generate the CSV
            $csvResult = $csvExporter->generate();
            
            if (!$csvResult['success']) {
                wp_send_json_error(['message' => 'Failed to generate CSV: ' . $csvResult['message']]);
                return;
            }

            // Serve the CSV file for download
            $csvExporter->serve($csvResult['data'], $csvResult['filename']);

        } catch (Exception $e) {
            error_log('SessionScoresController handleSummaryExport Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Summary export failed: ' . $e->getMessage()]);
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
}