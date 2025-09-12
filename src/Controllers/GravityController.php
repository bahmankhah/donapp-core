<?php

namespace App\Controllers;

use App\Services\GravityService;
use Exception;
use Kernel\Container;
use App\Utils\FileHelper;

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
            if (!$uid) {
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
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Set headers for CSV download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            // Remove Content-Length header as it can cause issues with dynamic content

            // Add BOM for proper UTF-8 handling in Excel
            echo "\xEF\xBB\xBF";

            // Output CSV data directly without buffering
            foreach ($csv_data as $row) {
                // Convert each row to CSV format and output immediately
                $line = '';
                $first = true;
                foreach ($row as $field) {
                    if (!$first) {
                        $line .= ',';
                    }
                    // Escape quotes and wrap in quotes if needed
                    if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
                        $line .= '"' . str_replace('"', '""', $field) . '"';
                    } else {
                        $line .= $field;
                    }
                    $first = false;
                }
                echo $line . "\n";
            }

            // Force output and exit cleanly
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            exit();
        } catch (Exception $e) {
            error_log('Gravity CSV Export Error: ' . $e->getMessage());
            http_response_code(500);
            wp_die('خطای داخلی سرور: ' . $e->getMessage(), 'خطای سرور', ['response' => 500]);
        }
    }

    /**
     * Export approved Gravity Flow entries to XLSX
     */
    public function exportXLSX()
    {
        try {
            $uid = $_GET['uid'];
            if (!$uid) {
                http_response_code(404);
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
                wp_die('خطا در تولید XLSX: ' . $export_result['message'], 'خطا در صادرات', ['response' => 400]);
                return;
            }

            $csv_data = $export_result['data'];

            // Check if we have data
            if (empty($csv_data) || count($csv_data) <= 1) {
                http_response_code(404);
                wp_die('هیچ داده‌ای برای صادرات یافت نشد.', 'داده یافت نشد', ['response' => 404]);
                return;
            }

            // Convert CSV data to XLSX using FileHelper
            $xlsx_result = FileHelper::csv2Xlsx($csv_data, 'فرم‌های تأیید شده');

            if (!$xlsx_result['success']) {
                http_response_code(500);
                wp_die('خطا در تولید XLSX: ' . $xlsx_result['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            // Serve XLSX download
            FileHelper::serveXlsxDownload($xlsx_result['data'], $xlsx_result['filename']);
        } catch (Exception $e) {
            error_log('Gravity XLSX Export Error: ' . $e->getMessage());
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
     * Export approved Gravity Flow entries to PDF (all entries)
     */
    public function exportPDF()
    {
        try {
            $uid = $_GET['uid'];
            if (!$uid) {
                http_response_code(404);
                wp_die('کاربر یافت نشد.', 'خطا', ['response' => 404]);
                return;
            }

            $user = get_user_by('ID', $uid);
            if (!$user) {
                http_response_code(404);
                wp_die('کاربر یافت نشد.', 'خطا', ['response' => 404]);
                return;
            }

            // Get export data from service (CSV method doesn't take user parameter)
            $export_result = $this->gravityService->exportApprovedEntriesToCSV();

            if (!$export_result['success']) {
                http_response_code(400);
                wp_die('خطا در تولید PDF: ' . $export_result['message'], 'خطا در صادرات', ['response' => 400]);
                return;
            }

            $csv_data = $export_result['data'];
            $filename = str_replace('.csv', '.pdf', $export_result['filename']);

            // Check if we have data
            if (empty($csv_data) || count($csv_data) <= 1) {
                http_response_code(404);
                wp_die('هیچ داده‌ای برای صادرات یافت نشد.', 'داده یافت نشد', ['response' => 404]);
                return;
            }

            // Convert CSV data to PDF using FileHelper
            $pdf_result = FileHelper::csv2Pdf($csv_data, 'فرم‌های تأیید شده گرویتی فلو');

            if (!$pdf_result['success']) {
                http_response_code(500);
                wp_die('خطا در تولید PDF: ' . $pdf_result['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            // Serve PDF download
            FileHelper::servePdfDownload($pdf_result['data'], $filename);
        } catch (Exception $e) {
            error_log('Gravity PDF Export Error: ' . $e->getMessage());
            http_response_code(500);
            wp_die('خطای داخلی سرور: ' . $e->getMessage(), 'خطای سرور', ['response' => 500]);
        }
    }

    /**
     * Export single Gravity Flow entry to PDF
     */
    public function exportSingleEntryPDF()
    {
        try {
            $entry_id = $_GET['entry_id'] ?? null;
            $form_id = $_GET['form_id'] ?? null;

            if (!$entry_id || !$form_id) {
                http_response_code(400);
                wp_die('شناسه ورودی یا فرم مشخص نشده است.', 'خطا', ['response' => 400]);
                return;
            }

            // Get single entry data
            $entry_result = $this->gravityService->getSingleEntryForExport($form_id, $entry_id);

            if (!$entry_result['success']) {
                http_response_code(400);
                wp_die('خطا در بازیابی ورودی: ' . $entry_result['message'], 'خطا در صادرات', ['response' => 400]);
                return;
            }

            $entry_data = $entry_result['data'];
            $filename = 'entry-' . $entry_id . '-' . date('Y-m-d-H-i-s') . '.pdf';

            // Convert entry to PDF
            $pdf_result = FileHelper::entry2Pdf($entry_data, 'جزئیات ورودی #' . $entry_id);

            if (!$pdf_result['success']) {
                http_response_code(500);
                wp_die('خطا در تولید PDF: ' . $pdf_result['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            // Serve PDF download
            FileHelper::servePdfDownload($pdf_result['data'], $filename);
        } catch (Exception $e) {
            error_log('Single Entry PDF Export Error: ' . $e->getMessage());
            http_response_code(500);
            wp_die('خطای داخلی سرور: ' . $e->getMessage(), 'خطای سرور', ['response' => 500]);
        }
    }

    /**
     * Export single Gravity Flow entry to Excel
     */
    public function exportSingleEntryExcel()
    {
        try {
            $entry_id = $_GET['entry_id'] ?? null;
            $form_id = $_GET['form_id'] ?? null;

            if (!$entry_id || !$form_id) {
                http_response_code(400);
                wp_die('شناسه ورودی یا فرم مشخص نشده است.', 'خطا', ['response' => 400]);
                return;
            }

            // Get single entry data
            $entry_result = $this->gravityService->getSingleEntryForExport($form_id, $entry_id);

            if (!$entry_result['success']) {
                http_response_code(400);
                wp_die('خطا در بازیابی ورودی: ' . $entry_result['message'], 'خطا در صادرات', ['response' => 400]);
                return;
            }

            $entry_data = $entry_result['data'];
            $filename = 'entry-' . $entry_id . '-' . date('Y-m-d-H-i-s') . '.xlsx';

            // Convert entry to Excel format (convert to CSV-like array first)
            $csv_data = $this->entryToCsvFormat($entry_data);

            $xlsx_result = FileHelper::csv2Xlsx($csv_data, 'جزئیات ورودی #' . $entry_id);

            if (!$xlsx_result['success']) {
                http_response_code(500);
                wp_die('خطا در تولید Excel: ' . $xlsx_result['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            // Serve Excel download
            FileHelper::serveXlsxDownload($xlsx_result['data'], $filename);
        } catch (Exception $e) {
            error_log('Single Entry Excel Export Error: ' . $e->getMessage());
            http_response_code(500);
            wp_die('خطای داخلی سرور: ' . $e->getMessage(), 'خطای سرور', ['response' => 500]);
        }
    }

    /**
     * Helper method to convert single entry to CSV format for Excel export
     */
    private function entryToCsvFormat($entry_data)
    {
        $csv_data = [];

        // Headers
        $csv_data[] = ['فیلد', 'مقدار'];

        // Add entry data
        foreach ($entry_data as $field => $value) {
            $csv_data[] = [$field, $value];
        }

        return $csv_data;
    }

    /**
     * Handle bulk actions for enhanced Gravity Flow inbox
     */
    public function handleBulkAction()
    {
        try {
            // Verify nonce
            if (!\check_ajax_referer('gravity_flow_bulk_action', '_wpnonce', false)) {
                \wp_send_json_error(['message' => 'خطای امنیتی'], 403);
                return;
            }

            // Check permissions
            if (!\current_user_can('manage_options')) {
                \wp_send_json_error(['message' => 'دسترسی مجاز نیست'], 403);
                return;
            }

            $bulk_action = \sanitize_text_field($_POST['bulk_action'] ?? '');
            $entry_ids = array_map('intval', $_POST['entry_ids'] ?? []);

            if (empty($bulk_action) || empty($entry_ids)) {
                \wp_send_json_error(['message' => 'پارامترهای نامعتبر'], 400);
                return;
            }

            $results = [];
            $success_count = 0;
            $error_count = 0;

            foreach ($entry_ids as $entry_id) {
                try {
                    switch ($bulk_action) {
                        case 'approve':
                            $result = $this->approveSingleEntry($entry_id);
                            break;

                        case 'reject':
                            $result = $this->rejectSingleEntry($entry_id);
                            break;

                        case 'delete':
                            $result = $this->deleteSingleEntry($entry_id);
                            break;

                        case 'export':
                            $result = $this->exportSingleEntry($entry_id);
                            break;

                        default:
                            throw new Exception('عملیات نامشخص');
                    }

                    if ($result) {
                        $success_count++;
                        $results[] = ['entry_id' => $entry_id, 'status' => 'success'];
                    } else {
                        $error_count++;
                        $results[] = ['entry_id' => $entry_id, 'status' => 'error', 'message' => 'خطا در انجام عملیات'];
                    }
                } catch (Exception $e) {
                    $error_count++;
                    $results[] = [
                        'entry_id' => $entry_id,
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                }
            }

            // Prepare response message
            $action_names = [
                'approve' => 'تأیید',
                'reject' => 'رد',
                'delete' => 'حذف',
                'export' => 'صادرات'
            ];

            $action_name = $action_names[$bulk_action] ?? 'پردازش';

            if ($error_count === 0) {
                $message = sprintf('%s ورودی با موفقیت %s شدند', $success_count, $action_name);
                \wp_send_json_success([
                    'message' => $message,
                    'results' => $results,
                    'success_count' => $success_count,
                    'error_count' => $error_count
                ]);
            } else {
                $message = sprintf(
                    '%s ورودی %s شدند، %s ورودی با خطا مواجه شدند',
                    $success_count,
                    $action_name,
                    $error_count
                );
                \wp_send_json_error([
                    'message' => $message,
                    'results' => $results,
                    'success_count' => $success_count,
                    'error_count' => $error_count
                ], 207); // 207 Multi-Status
            }
        } catch (Exception $e) {
            error_log('Bulk Action Error: ' . $e->getMessage());
            \wp_send_json_error(['message' => 'خطای داخلی سرور'], 500);
        }
    }

    /**
     * Approve single entry
     */
    private function approveSingleEntry($entry_id)
    {
        if (!class_exists('GFAPI')) {
            return false;
        }

        $entry = \GFAPI::get_entry($entry_id);
        if (is_wp_error($entry)) {
            return false;
        }

        // Update entry meta or use Gravity Flow API to approve
        // This is a simplified implementation
        \gform_update_meta($entry_id, 'workflow_final_status', 'approved');
        \gform_update_meta($entry_id, 'approved_by', \get_current_user_id());
        \gform_update_meta($entry_id, 'approved_at', \current_time('mysql'));

        return true;
    }

    /**
     * Reject single entry
     */
    private function rejectSingleEntry($entry_id)
    {
        if (!class_exists('GFAPI')) {
            return false;
        }

        $entry = \GFAPI::get_entry($entry_id);
        if (is_wp_error($entry)) {
            return false;
        }

        // Update entry meta or use Gravity Flow API to reject
        \gform_update_meta($entry_id, 'workflow_final_status', 'rejected');
        \gform_update_meta($entry_id, 'rejected_by', \get_current_user_id());
        \gform_update_meta($entry_id, 'rejected_at', \current_time('mysql'));

        return true;
    }

    /**
     * Delete single entry
     */
    private function deleteSingleEntry($entry_id)
    {
        if (!class_exists('GFAPI')) {
            return false;
        }

        $result = \GFAPI::delete_entry($entry_id);
        return !is_wp_error($result);
    }

    /**
     * Export single entry (returns download URL)
     */
    private function exportSingleEntry($entry_id)
    {
        // This would typically generate an export file and return URL
        // For now, just mark as exported
        \gform_update_meta($entry_id, 'exported_at', \current_time('mysql'));
        \gform_update_meta($entry_id, 'exported_by', \get_current_user_id());

        return true;
    }
}
