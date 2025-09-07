<?php

namespace App\Controllers;

use App\Services\GravityService;
use Exception;
use Kernel\Container;

class GravityController
{
    private GravityService $gravityService;

    public function __construct()
    {
        $this->gravityService = Container::resolve('GravityService');
    }

    /**
     * Export approved Gravity Flow entries to CSV
     */
    public function exportCSV()
    {
        try {
            // Check user permissions
            // if (!current_user_can('manage_options')) {
            //     http_response_code(403);
            //     wp_die('شما اجازه دسترسی به این بخش را ندارید.', 'خطای دسترسی', ['response' => 403]);
            //     return;
            // }

            // Verify nonce for security
            // if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'export_gravity_csv')) {
            //     http_response_code(403);
            //     wp_die('خطای امنیتی: نانس نامعتبر است.', 'خطای امنیتی', ['response' => 403]);
            //     return;
            // }

            $uid = $_GET['uid'];
            if(!$uid) {
                http_response_code(403);
                wp_die('کاربر یافت نشد.', 'خطا', ['response' => 404]);
                return;
            }

            $user = get_user_by('ID', $uid);
            if (!$user) {
                http_response_code(404);
                wp_die('کاربر یافت نشد.', 'خطا', ['response' => 404]);
                return;
            }

            // Get export data from service
            $export_result = $this->gravityService->exportApprovedEntriesToCSV($user);

            if (!$export_result['success']) {
                http_response_code(400);
                wp_die('خطا در تولید CSV: ' . $export_result['message'], 'خطا در صادرات', ['response' => 400]);
                return;
            }

            $csv_data = $export_result['data'];
            $filename = $export_result['filename'];

            // Check if we have data
            if (empty($csv_data) || count($csv_data) <= 1) {
                http_response_code(404);
                wp_die('هیچ داده‌ای برای صادرات یافت نشد.', 'داده یافت نشد', ['response' => 404]);
                return;
            }

            // Clean any output that might have been sent
            if (ob_get_level()) {
                ob_end_clean();
            }

            // Set headers for CSV download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . $this->calculateCSVSize($csv_data));

            // Add BOM for proper UTF-8 handling in Excel
            echo "\xEF\xBB\xBF";

            // Output CSV data
            $output = fopen('php://output', 'w');
            foreach ($csv_data as $row) {
                fputcsv($output, $row);
            }
            fclose($output);

            exit;

        } catch (Exception $e) {
            error_log('Gravity CSV Export Error: ' . $e->getMessage());
            http_response_code(500);
            wp_die('خطای داخلی سرور: ' . $e->getMessage(), 'خطای سرور', ['response' => 500]);
        }
    }

    /**
     * Get approved entries as JSON (for AJAX requests)
     */
    public function getApprovedEntries()
    {
        try {
            // Check user permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'دسترسی مجاز نیست'], 403);
                return;
            }

            // Get pagination parameters
            $page = max(1, intval($_GET['page'] ?? 1));
            $per_page = max(1, min(100, intval($_GET['per_page'] ?? 20)));

            // Get filters
            $filters = [
                'form_filter' => sanitize_text_field($_GET['form_filter'] ?? ''),
                'start_date' => sanitize_text_field($_GET['start_date'] ?? ''),
                'end_date' => sanitize_text_field($_GET['end_date'] ?? '')
            ];

            // Get entries from service
            $result = $this->gravityService->getApprovedGravityFlowEntries($page, $per_page);

            wp_send_json_success([
                'entries' => $result['data'],
                'pagination' => $result['pagination'],
                'filters_applied' => $filters
            ]);

        } catch (Exception $e) {
            error_log('Gravity Entries API Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'خطای داخلی سرور'], 500);
        }
    }

    /**
     * Calculate the approximate size of CSV data for Content-Length header
     */
    private function calculateCSVSize($csv_data)
    {
        $size = 0;
        foreach ($csv_data as $row) {
            $size += strlen(implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row))) + 2; // +2 for \r\n
        }
        return $size + 3; // +3 for BOM
    }
}
