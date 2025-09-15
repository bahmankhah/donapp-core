<?php

namespace App\Controllers;

use App\Services\GravityService;
use Exception;
use Kernel\Container;
use App\Utils\Export\Concrete\GravityApprovedEntriesCsv;
use App\Utils\Export\Concrete\GravityApprovedEntriesXlsx;
use App\Utils\Export\Concrete\GravityApprovedEntriesPdf;
use App\Utils\Export\Concrete\GravitySingleEntryPdf;
use App\Utils\Export\Concrete\GravitySingleEntryXlsx;
use App\Utils\Export\Concrete\GravityFlowInboxCsv;
use App\Utils\Export\Concrete\GravityFlowInboxXlsx;
use App\Utils\Export\Concrete\GravityFlowInboxPdf;

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
    public function exportApprovedEntriesCSV()
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

            // Get all entries without pagination
            $all_entries_result = $this->gravityService->getApprovedGravityFlowEntries(1, 1000, $user);
            $entries = $all_entries_result['data'];

            if (empty($entries)) {
                http_response_code(404);
                wp_die('هیچ داده‌ای برای صادرات یافت نشد.', 'داده یافت نشد', ['response' => 404]);
                return;
            }

            // Create CSV exporter and generate file
            $csvExporter = new GravityApprovedEntriesCsv();
            $result = $csvExporter->setEntriesData($entries)->generate();

            if (!$result['success']) {
                http_response_code(500);
                wp_die('خطا در تولید CSV: ' . $result['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            // Serve CSV download
            $csvExporter->serve($result['data'], $result['filename']);
        } catch (Exception $e) {
            error_log('Gravity CSV Export Error: ' . $e->getMessage());
            http_response_code(500);
            wp_die('خطای داخلی سرور: ' . $e->getMessage(), 'خطای سرور', ['response' => 500]);
        }
    }

    /**
     * Export approved Gravity Flow entries to XLSX
     */
    public function exportApprovedEntriesXLSX()
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

            // Get all entries without pagination
            $all_entries_result = $this->gravityService->getApprovedGravityFlowEntries(1, 1000, $user);
            $entries = $all_entries_result['data'];

            if (empty($entries)) {
                http_response_code(404);
                wp_die('هیچ داده‌ای برای صادرات یافت نشد.', 'داده یافت نشد', ['response' => 404]);
                return;
            }

            // Create XLSX exporter and generate file
            $xlsxExporter = new GravityApprovedEntriesXlsx();
            $result = $xlsxExporter->setEntriesData($entries)->generate();

            if (!$result['success']) {
                http_response_code(500);
                wp_die('خطا در تولید XLSX: ' . $result['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            // Serve XLSX download
            $xlsxExporter->serve($result['data'], $result['filename']);
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

            // Get all entries without pagination
            $all_entries_result = $this->gravityService->getApprovedGravityFlowEntries(1, 1000);
            $entries = $all_entries_result['data'];

            if (empty($entries)) {
                http_response_code(404);
                wp_die('هیچ داده‌ای برای صادرات یافت نشد.', 'داده یافت نشد', ['response' => 404]);
                return;
            }

            // Create PDF exporter and generate file
            $pdfExporter = new GravityApprovedEntriesPdf();
            $result = $pdfExporter->setEntriesData($entries)->generate();

            if (!$result['success']) {
                http_response_code(500);
                wp_die('خطا در تولید PDF: ' . $result['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            // Serve PDF download
            $pdfExporter->serve($result['data'], $result['filename']);
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
                wp_die('شناسه ورودی یا فرم مشخص نشده است. لطفاً از طریق دکمه‌های صادرات در صفحه ورودی اقدام کنید.', 'خطا در پارامترها', ['response' => 400]);
                return;
            }

            // Validate entry_id and form_id are numeric
            if (!is_numeric($entry_id) || !is_numeric($form_id)) {
                http_response_code(400);
                wp_die('شناسه ورودی یا فرم معتبر نیست.', 'خطا در پارامترها', ['response' => 400]);
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

            // Create PDF exporter and generate file
            $pdfExporter = new GravitySingleEntryPdf(intval($entry_id));
            $result = $pdfExporter->setSingleEntryData($entry_data)->generate();

            if (!$result['success']) {
                http_response_code(500);
                wp_die('خطا در تولید PDF: ' . $result['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            // Serve PDF download
            $pdfExporter->serve($result['data'], $result['filename']);
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
                wp_die('شناسه ورودی یا فرم مشخص نشده است. لطفاً از طریق دکمه‌های صادرات در صفحه ورودی اقدام کنید.', 'خطا در پارامترها', ['response' => 400]);
                return;
            }

            // Validate entry_id and form_id are numeric
            if (!is_numeric($entry_id) || !is_numeric($form_id)) {
                http_response_code(400);
                wp_die('شناسه ورودی یا فرم معتبر نیست.', 'خطا در پارامترها', ['response' => 400]);
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

            // Create XLSX exporter and generate file
            $xlsxExporter = new GravitySingleEntryXlsx(intval($entry_id));
            $result = $xlsxExporter->setSingleEntryData($entry_data)->generate();

            if (!$result['success']) {
                http_response_code(500);
                wp_die('خطا در تولید Excel: ' . $result['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            // Serve Excel download
            $xlsxExporter->serve($result['data'], $result['filename']);
        } catch (Exception $e) {
            error_log('Single Entry Excel Export Error: ' . $e->getMessage());
            http_response_code(500);
            wp_die('خطای داخلی سرور: ' . $e->getMessage(), 'خطای سرور', ['response' => 500]);
        }
    }

    /**
     * Handle bulk actions for enhanced Gravity Flow inbox
     */
    public function handleBulkAction()
    {
        try {
            // Handle both POST and GET requests for flexibility
            $request_method = $_SERVER['REQUEST_METHOD'] ?? 'POST';
            $request_data = $request_method === 'GET' ? $_GET : $_POST;
            
            error_log("GravityController: Bulk action request received via $request_method");
            error_log("GravityController: Request data: " . print_r($request_data, true));
            
            $bulk_action = \sanitize_text_field($request_data['bulk_action'] ?? '');
            $entry_ids = [];
            
            // Handle different input formats
            if (isset($request_data['entry_ids']) && is_array($request_data['entry_ids'])) {
                $entry_ids = array_map('intval', $request_data['entry_ids']);
            } elseif (isset($request_data['entry_id'])) {
                $entry_ids = [intval($request_data['entry_id'])];
            }

            error_log("GravityController: Parsed bulk_action: '$bulk_action', entry_ids: " . implode(', ', $entry_ids));

            if (empty($bulk_action) || empty($entry_ids)) {
                error_log("GravityController: Invalid parameters - bulk_action: '$bulk_action', entry_ids count: " . count($entry_ids));
                \wp_send_json_error(['message' => 'پارامترهای نامعتبر'], 400);
                return;
            }

            $results = [];
            $success_count = 0;
            $error_count = 0;

            foreach ($entry_ids as $entry_id) {
                appLogger("GravityController: Processing bulk action '$bulk_action' for entry ID: $entry_id");
                
                try {
                    switch ($bulk_action) {
                        case 'approve':
                            $result = $this->approveSingleEntry($entry_id);
                            appLogger("GravityController: Approve result for entry $entry_id: " . ($result ? 'success' : 'failed'));
                            break;

                        case 'reject':
                            $result = $this->rejectSingleEntry($entry_id);
                            appLogger("GravityController: Reject result for entry $entry_id: " . ($result ? 'success' : 'failed'));
                            break;

                        case 'delete':
                            $result = $this->deleteSingleEntry($entry_id);
                            appLogger("GravityController: Delete result for entry $entry_id: " . ($result ? 'success' : 'failed'));
                            break;

                        case 'export':
                            $result = $this->exportSingleEntry($entry_id);
                            appLogger("GravityController: Export result for entry $entry_id: " . ($result ? 'success' : 'failed'));
                            break;

                        default:
                            appLogger("GravityController: Unknown bulk action: $bulk_action");
                            throw new Exception('عملیات نامشخص');
                    }

                    if ($result) {
                        $success_count++;
                        $results[] = ['entry_id' => $entry_id, 'status' => 'success'];
                        appLogger("GravityController: Successfully processed entry $entry_id");
                    } else {
                        $error_count++;
                        $results[] = ['entry_id' => $entry_id, 'status' => 'error', 'message' => 'خطا در انجام عملیات'];
                        appLogger("GravityController: Failed to process entry $entry_id");
                    }
                } catch (Exception $e) {
                    $error_count++;
                    $error_message = $e->getMessage();
                    appLogger("GravityController: Exception for entry $entry_id: $error_message");
                    $results[] = [
                        'entry_id' => $entry_id,
                        'status' => 'error',
                        'message' => $error_message
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

            error_log("GravityController: Bulk action completed - success: $success_count, errors: $error_count");

            if ($error_count === 0) {
                $message = sprintf('%s ورودی با موفقیت %s شدند', $success_count, $action_name);
                error_log("GravityController: Sending success response: $message");
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
                error_log("GravityController: Sending partial success response: $message");
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
     * Simple and direct Gravity Flow approval using native form submission
     */
    private function approveSingleEntry($entry_id)
    {
        try {
            appLogger("GravityController: Starting SIMPLE approval process for entry ID: $entry_id");

            if (!class_exists('GFAPI') || !function_exists('gravity_flow')) {
                appLogger("GravityController: GFAPI or Gravity Flow not available");
                return false;
            }

            $entry = \GFAPI::get_entry($entry_id);
            if (is_wp_error($entry) || !$entry) {
                appLogger("GravityController: Entry not found: $entry_id");
                return false;
            }

            $form_id = $entry['form_id'];
            appLogger("GravityController: Processing entry $entry_id from form $form_id");

            // Get current step
            $api = new \Gravity_Flow_API($form_id);
            $current_step = $api->get_current_step($entry);
            
            if (!$current_step) {
                appLogger("GravityController: No current step - workflow may be complete");
                return false;
            }

            $step_id = $current_step->get_id();
            appLogger("GravityController: Current step ID: $step_id");

            // Set user context
            $current_user = \wp_get_current_user();
            if (!$current_user->ID) {
                \wp_set_current_user(1);
                appLogger("GravityController: Set admin user context");
            }

            // Method 1: Use Gravity Flow's native form submission simulation
            $this->simulateGravityFlowApproval($entry_id, $step_id);
            
            // Method 2: Direct step processing if available
            if (method_exists($current_step, 'process_step')) {
                $_POST['gravityflow_submit'] = 'approved';
                $_POST['lid'] = $entry_id;
                $result = $current_step->process_step($entry);
                appLogger("GravityController: Direct step processing result: " . ($result ? 'success' : 'failed'));
            }

            // Method 3: Use WordPress action hooks
            \do_action('gravityflow_workflow_complete', $entry_id, $form_id, $step_id, 'approved');
            
            // Update metadata as backup
            \gform_update_meta($entry_id, 'workflow_final_status', 'approved');
            \gform_update_meta($entry_id, 'approved_by', \get_current_user_id());
            \gform_update_meta($entry_id, 'approved_at', \current_time('mysql'));
            
            appLogger("GravityController: Multiple approval methods executed for entry $entry_id");
            return true;

        } catch (Exception $e) {
            appLogger("GravityController: Exception in simple approval: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Simulate Gravity Flow approval form submission
     */
    private function simulateGravityFlowApproval($entry_id, $step_id)
    {
        appLogger("GravityController: Simulating Gravity Flow form submission for entry $entry_id, step $step_id");
        
        // Set up $_POST variables that Gravity Flow expects
        $_POST['gravityflow_submit'] = 'approved';
        $_POST['gravityflow_step_id'] = $step_id;
        $_POST['lid'] = $entry_id;
        $_POST['gravityflow_submit_approved'] = 'Approve';
        $_POST['gravityflow_note'] = 'Entry approved via API';
        
        // Set required WordPress globals
        global $current_user;
        if (!$current_user || !$current_user->ID) {
            $current_user = \get_user_by('id', 1); // Use admin user
        }
        
        appLogger("GravityController: Form submission variables set for Gravity Flow processing");
        
        return true;
    }

    /**
     * Simple and direct Gravity Flow rejection using native form submission
     */
    private function rejectSingleEntry($entry_id)
    {
        try {
            appLogger("GravityController: Starting SIMPLE rejection process for entry ID: $entry_id");

            if (!class_exists('GFAPI') || !function_exists('gravity_flow')) {
                appLogger("GravityController: GFAPI or Gravity Flow not available");
                return false;
            }

            $entry = \GFAPI::get_entry($entry_id);
            if (is_wp_error($entry) || !$entry) {
                appLogger("GravityController: Entry not found: $entry_id");
                return false;
            }

            $form_id = $entry['form_id'];
            appLogger("GravityController: Processing entry $entry_id from form $form_id");

            // Get current step
            $api = new \Gravity_Flow_API($form_id);
            $current_step = $api->get_current_step($entry);
            
            if (!$current_step) {
                appLogger("GravityController: No current step - workflow may be complete");
                return false;
            }

            $step_id = $current_step->get_id();
            appLogger("GravityController: Current step ID: $step_id");

            // Set user context
            $current_user = \wp_get_current_user();
            if (!$current_user->ID) {
                \wp_set_current_user(1);
                appLogger("GravityController: Set admin user context");
            }

            // Method 1: Use Gravity Flow's native form submission simulation
            $this->simulateGravityFlowRejection($entry_id, $step_id);
            
            // Method 2: Direct step processing if available
            if (method_exists($current_step, 'process_step')) {
                $_POST['gravityflow_submit'] = 'rejected';
                $_POST['lid'] = $entry_id;
                $result = $current_step->process_step($entry);
                appLogger("GravityController: Direct step processing result: " . ($result ? 'success' : 'failed'));
            }

            // Method 3: Use WordPress action hooks
            \do_action('gravityflow_workflow_complete', $entry_id, $form_id, $step_id, 'rejected');
            
            // Update metadata as backup
            \gform_update_meta($entry_id, 'workflow_final_status', 'rejected');
            \gform_update_meta($entry_id, 'rejected_by', \get_current_user_id());
            \gform_update_meta($entry_id, 'rejected_at', \current_time('mysql'));
            
            appLogger("GravityController: Multiple rejection methods executed for entry $entry_id");
            return true;

        } catch (Exception $e) {
            appLogger("GravityController: Exception in simple rejection: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Simulate Gravity Flow rejection form submission
     */
    private function simulateGravityFlowRejection($entry_id, $step_id)
    {
        appLogger("GravityController: Simulating Gravity Flow rejection for entry $entry_id, step $step_id");
        
        // Set up $_POST variables that Gravity Flow expects
        $_POST['gravityflow_submit'] = 'rejected';
        $_POST['gravityflow_step_id'] = $step_id;
        $_POST['lid'] = $entry_id;
        $_POST['gravityflow_submit_rejected'] = 'Reject';
        $_POST['gravityflow_note'] = 'Entry rejected via API';
        
        // Set required WordPress globals
        global $current_user;
        if (!$current_user || !$current_user->ID) {
            $current_user = \get_user_by('id', 1); // Use admin user
        }
        
        appLogger("GravityController: Rejection form submission variables set");
        
        return true;
    }

    /**
     * Delete single entry
     */
    private function deleteSingleEntry($entry_id)
    {
        try {
            if (!class_exists('GFAPI')) {
                error_log("GravityController: GFAPI not available for deleting entry ID: $entry_id");
                return false;
            }

            $result = \GFAPI::delete_entry($entry_id);
            if (is_wp_error($result)) {
                error_log("GravityController: Failed to delete entry ID: $entry_id - " . $result->get_error_message());
                return false;
            }

            return true;
        } catch (Exception $e) {
            error_log("GravityController: Error deleting entry ID $entry_id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Export single entry (returns download URL) using Gravity Flow API
     */
    private function exportSingleEntry($entry_id)
    {
        try {
            if (!class_exists('GFAPI') || !class_exists('Gravity_Flow_API')) {
                error_log("GravityController: GFAPI or Gravity_Flow_API not available for exporting entry ID: $entry_id");
                return false;
            }

            $entry = \GFAPI::get_entry($entry_id);
            if (is_wp_error($entry)) {
                error_log("GravityController: Failed to get entry ID for export: $entry_id - " . $entry->get_error_message());
                return false;
            }

            if (!$entry || !isset($entry['form_id'])) {
                error_log("GravityController: Invalid entry data for export ID: $entry_id");
                return false;
            }

            // Initialize Gravity Flow API for this form
            $gravity_flow_api = new \Gravity_Flow_API($entry['form_id']);

            // Get current user info safely
            $current_user = \wp_get_current_user();
            $user_name = $current_user && $current_user->ID ? $current_user->display_name : 'سیستم';

            // Add timeline note about export
            $gravity_flow_api->add_timeline_note($entry_id, 'ورودی توسط ' . $user_name . ' صادر شد.');
            $gravity_flow_api->log_activity('entry', 'exported', $entry['form_id'], $entry_id);

            // Mark as exported
            if (function_exists('gform_update_meta')) {
                \gform_update_meta($entry_id, 'exported_at', \current_time('mysql'));
                \gform_update_meta($entry_id, 'exported_by', \get_current_user_id());
            }

            return true;
        } catch (Exception $e) {
            error_log("GravityController: Error exporting entry ID $entry_id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Restart workflow for entry using Gravity Flow API
     */
    public function restartWorkflow()
    {
        try {
            // Verify nonce
            if (!\check_ajax_referer('gravity_flow_restart_workflow', '_wpnonce', false)) {
                \wp_send_json_error(['message' => 'خطای امنیتی'], 403);
                return;
            }

            // Check permissions
            if (!\current_user_can('manage_options')) {
                \wp_send_json_error(['message' => 'دسترسی مجاز نیست'], 403);
                return;
            }

            $entry_id = intval($_POST['entry_id'] ?? 0);
            $form_id = intval($_POST['form_id'] ?? 0);

            if (!$entry_id || !$form_id) {
                \wp_send_json_error(['message' => 'پارامترهای نامعتبر'], 400);
                return;
            }

            if (!class_exists('GFAPI') || !class_exists('Gravity_Flow_API')) {
                \wp_send_json_error(['message' => 'Gravity Flow API در دسترس نیست'], 500);
                return;
            }

            $entry = \GFAPI::get_entry($entry_id);
            if (is_wp_error($entry) || $entry['form_id'] != $form_id) {
                \wp_send_json_error(['message' => 'ورودی یافت نشد'], 404);
                return;
            }

            // Initialize Gravity Flow API
            $gravity_flow_api = new \Gravity_Flow_API($form_id);

            // Restart the workflow
            $gravity_flow_api->restart_workflow($entry);

            \wp_send_json_success([
                'message' => 'گردش کار با موفقیت مجدداً راه‌اندازی شد',
                'entry_id' => $entry_id
            ]);

        } catch (Exception $e) {
            error_log('Restart Workflow Error: ' . $e->getMessage());
            \wp_send_json_error(['message' => 'خطای داخلی سرور'], 500);
        }
    }

    /**
     * Cancel workflow for entry using Gravity Flow API
     */
    public function cancelWorkflow()
    {
        try {
            // Verify nonce
            if (!\check_ajax_referer('gravity_flow_cancel_workflow', '_wpnonce', false)) {
                \wp_send_json_error(['message' => 'خطای امنیتی'], 403);
                return;
            }

            // Check permissions
            if (!\current_user_can('manage_options')) {
                \wp_send_json_error(['message' => 'دسترسی مجاز نیست'], 403);
                return;
            }

            $entry_id = intval($_POST['entry_id'] ?? 0);
            $form_id = intval($_POST['form_id'] ?? 0);

            if (!$entry_id || !$form_id) {
                \wp_send_json_error(['message' => 'پارامترهای نامعتبر'], 400);
                return;
            }

            if (!class_exists('GFAPI') || !class_exists('Gravity_Flow_API')) {
                \wp_send_json_error(['message' => 'Gravity Flow API در دسترس نیست'], 500);
                return;
            }

            $entry = \GFAPI::get_entry($entry_id);
            if (is_wp_error($entry) || $entry['form_id'] != $form_id) {
                \wp_send_json_error(['message' => 'ورودی یافت نشد'], 404);
                return;
            }

            // Initialize Gravity Flow API
            $gravity_flow_api = new \Gravity_Flow_API($form_id);

            // Cancel the workflow
            $result = $gravity_flow_api->cancel_workflow($entry);

            if ($result) {
                \wp_send_json_success([
                    'message' => 'گردش کار با موفقیت لغو شد',
                    'entry_id' => $entry_id
                ]);
            } else {
                \wp_send_json_error(['message' => 'عدم موفقیت در لغو گردش کار'], 500);
            }

        } catch (Exception $e) {
            error_log('Cancel Workflow Error: ' . $e->getMessage());
            \wp_send_json_error(['message' => 'خطای داخلی سرور'], 500);
        }
    }

    /**
     * Send entry to specific step using Gravity Flow API
     */
    public function sendToStep()
    {
        try {
            // Verify nonce
            if (!\check_ajax_referer('gravity_flow_send_to_step', '_wpnonce', false)) {
                \wp_send_json_error(['message' => 'خطای امنیتی'], 403);
                return;
            }

            // Check permissions
            if (!\current_user_can('manage_options')) {
                \wp_send_json_error(['message' => 'دسترسی مجاز نیست'], 403);
                return;
            }

            $entry_id = intval($_POST['entry_id'] ?? 0);
            $form_id = intval($_POST['form_id'] ?? 0);
            $step_id = intval($_POST['step_id'] ?? 0);

            if (!$entry_id || !$form_id || !$step_id) {
                \wp_send_json_error(['message' => 'پارامترهای نامعتبر'], 400);
                return;
            }

            if (!class_exists('GFAPI') || !class_exists('Gravity_Flow_API')) {
                \wp_send_json_error(['message' => 'Gravity Flow API در دسترس نیست'], 500);
                return;
            }

            $entry = \GFAPI::get_entry($entry_id);
            if (is_wp_error($entry) || $entry['form_id'] != $form_id) {
                \wp_send_json_error(['message' => 'ورودی یافت نشد'], 404);
                return;
            }

            // Initialize Gravity Flow API
            $gravity_flow_api = new \Gravity_Flow_API($form_id);

            // Get the target step to validate it exists
            $target_step = $gravity_flow_api->get_step($step_id, $entry);
            if (!$target_step) {
                \wp_send_json_error(['message' => 'مرحله مقصد یافت نشد'], 404);
                return;
            }

            // Send to step
            $gravity_flow_api->send_to_step($entry, $step_id);

            \wp_send_json_success([
                'message' => 'ورودی با موفقیت به مرحله ' . $target_step->get_name() . ' ارسال شد',
                'entry_id' => $entry_id,
                'step_name' => $target_step->get_name()
            ]);

        } catch (Exception $e) {
            error_log('Send To Step Error: ' . $e->getMessage());
            \wp_send_json_error(['message' => 'خطای داخلی سرور'], 500);
        }
    }

    /**
     * Export Gravity Flow inbox entries to CSV
     */
    public function exportInboxCSV()
    {
        try {
            $uid = $_GET['uid'] ?? null;
            if (!$uid) {
                http_response_code(400);
                wp_die('کاربر مشخص نشده است.', 'خطا', ['response' => 400]);
                return;
            }

            $user = get_user_by('ID', $uid);
            if (!$user) {
                http_response_code(404);
                wp_die('کاربر یافت نشد.', 'خطا', ['response' => 404]);
                return;
            }

            // Get all inbox entries without pagination
            $result = $this->gravityService->getGravityFlowInboxPage(1, 1000, $user);
            if (!$result['success']) {
                http_response_code(500);
                wp_die('خطا در دریافت داده‌ها: ' . $result['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            $entries = $result['data'];
            if (empty($entries)) {
                http_response_code(404);
                wp_die('هیچ داده‌ای برای صادرات یافت نشد.', 'داده یافت نشد', ['response' => 404]);
                return;
            }

            // Create CSV exporter and generate file
            $csvExporter = new GravityFlowInboxCsv();
            $exportResult = $csvExporter->setInboxEntriesData($entries)->generate();

            if (!$exportResult['success']) {
                http_response_code(500);
                wp_die('خطا در تولید CSV: ' . $exportResult['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            // Serve CSV download
            $csvExporter->serve($exportResult['data'], $exportResult['filename']);
        } catch (Exception $e) {
            error_log('Gravity Inbox CSV Export Error: ' . $e->getMessage());
            http_response_code(500);
            wp_die('خطای داخلی سرور: ' . $e->getMessage(), 'خطای سرور', ['response' => 500]);
        }
    }

    /**
     * Export Gravity Flow inbox entries to XLSX
     */
    public function exportInboxXLSX()
    {
        try {
            $uid = $_GET['uid'] ?? null;
            if (!$uid) {
                http_response_code(400);
                wp_die('کاربر مشخص نشده است.', 'خطا', ['response' => 400]);
                return;
            }

            $user = get_user_by('ID', $uid);
            if (!$user) {
                http_response_code(404);
                wp_die('کاربر یافت نشد.', 'خطا', ['response' => 404]);
                return;
            }

            // Get all inbox entries without pagination
            $result = $this->gravityService->getGravityFlowInboxPage(1, 1000, $user);

            if (!$result['success']) {
                http_response_code(500);
                wp_die('خطا در دریافت داده‌ها: ' . $result['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            $entries = $result['data'];

            if (empty($entries)) {
                http_response_code(404);
                wp_die('هیچ داده‌ای برای صادرات یافت نشد.', 'داده یافت نشد', ['response' => 404]);
                return;
            }

            // Create XLSX exporter and generate file
            $xlsxExporter = new GravityFlowInboxXlsx();
            $exportResult = $xlsxExporter->setInboxEntriesData($entries)->generate();

            if (!$exportResult['success']) {
                http_response_code(500);
                wp_die('خطا در تولید XLSX: ' . $exportResult['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            // Serve XLSX download
            $xlsxExporter->serve($exportResult['data'], $exportResult['filename']);
        } catch (Exception $e) {
            error_log('Gravity Inbox XLSX Export Error: ' . $e->getMessage());
            http_response_code(500);
            wp_die('خطای داخلی سرور: ' . $e->getMessage(), 'خطای سرور', ['response' => 500]);
        }
    }

    /**
     * Export Gravity Flow inbox entries to PDF
     */
    public function exportInboxPDF()
    {
        try {
            $uid = $_GET['uid'] ?? null;
            if (!$uid) {
                http_response_code(400);
                wp_die('کاربر مشخص نشده است.', 'خطا', ['response' => 400]);
                return;
            }

            $user = get_user_by('ID', $uid);
            if (!$user) {
                http_response_code(404);
                wp_die('کاربر یافت نشد.', 'خطا', ['response' => 404]);
                return;
            }

            // Get all inbox entries without pagination
            $result = $this->gravityService->getGravityFlowInboxPage(1, 1000, $user);

            if (!$result['success']) {
                http_response_code(500);
                wp_die('خطا در دریافت داده‌ها: ' . $result['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            $entries = $result['data'];

            if (empty($entries)) {
                http_response_code(404);
                wp_die('هیچ داده‌ای برای صادرات یافت نشد.', 'داده یافت نشد', ['response' => 404]);
                return;
            }

            // Create PDF exporter and generate file
            $pdfExporter = new GravityFlowInboxPdf();
            $exportResult = $pdfExporter->setInboxEntriesData($entries)->generate();

            if (!$exportResult['success']) {
                http_response_code(500);
                wp_die('خطا در تولید PDF: ' . $exportResult['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            // Serve PDF download
            $pdfExporter->serve($exportResult['data'], $exportResult['filename']);
        } catch (Exception $e) {
            error_log('Gravity Inbox PDF Export Error: ' . $e->getMessage());
            http_response_code(500);
            wp_die('خطای داخلی سرور: ' . $e->getMessage(), 'خطای سرور', ['response' => 500]);
        }
    }

    /**
     * Get workflow steps for a form using Gravity Flow API
     */
    public function getWorkflowSteps()
    {
        try {
            // Check permissions
            if (!\current_user_can('manage_options')) {
                \wp_send_json_error(['message' => 'دسترسی مجاز نیست'], 403);
                return;
            }

            $form_id = intval($_GET['form_id'] ?? 0);

            if (!$form_id) {
                \wp_send_json_error(['message' => 'شناسه فرم مشخص نشده است'], 400);
                return;
            }

            if (!class_exists('GFAPI') || !class_exists('Gravity_Flow_API')) {
                \wp_send_json_error(['message' => 'Gravity Flow API در دسترس نیست'], 500);
                return;
            }

            // Initialize Gravity Flow API
            $gravity_flow_api = new \Gravity_Flow_API($form_id);

            // Get all steps for this form
            $steps = $gravity_flow_api->get_steps();
            $formatted_steps = [];

            foreach ($steps as $step) {
                $formatted_steps[] = [
                    'id' => $step->get_id(),
                    'name' => $step->get_name(),
                    'type' => $step->get_type(),
                    'description' => $step->get_setting('description', ''),
                    'is_active' => $step->is_active(),
                    'step_type_display' => $this->getStepTypeDisplay($step->get_type())
                ];
            }

            \wp_send_json_success([
                'steps' => $formatted_steps,
                'form_id' => $form_id
            ]);

        } catch (Exception $e) {
            error_log('Get Workflow Steps Error: ' . $e->getMessage());
            \wp_send_json_error(['message' => 'خطای داخلی سرور'], 500);
        }
    }

    /**
     * Get display name for step type
     * @param string $step_type
     * @return string
     */
    private function getStepTypeDisplay($step_type)
    {
        $step_types = [
            'approval' => 'تأیید',
            'user_input' => 'ورودی کاربر',
            'notification' => 'اطلاع‌رسانی',
            'webhook' => 'وب‌هوک',
            'email' => 'ایمیل',
            'conditional' => 'شرطی',
            'discussion' => 'بحث',
            'schedule' => 'زمان‌بندی'
        ];

        return $step_types[$step_type] ?? $step_type;
    }

    /**
     * Get entry timeline using Gravity Flow API
     */
    public function getEntryTimeline()
    {
        try {
            // Check permissions
            if (!\current_user_can('manage_options')) {
                \wp_send_json_error(['message' => 'دسترسی مجاز نیست'], 403);
                return;
            }

            $entry_id = intval($_GET['entry_id'] ?? 0);
            $form_id = intval($_GET['form_id'] ?? 0);

            if (!$entry_id || !$form_id) {
                \wp_send_json_error(['message' => 'پارامترهای نامعتبر'], 400);
                return;
            }

            if (!class_exists('GFAPI') || !class_exists('Gravity_Flow_API')) {
                \wp_send_json_error(['message' => 'Gravity Flow API در دسترس نیست'], 500);
                return;
            }

            $entry = \GFAPI::get_entry($entry_id);
            if (is_wp_error($entry) || $entry['form_id'] != $form_id) {
                \wp_send_json_error(['message' => 'ورودی یافت نشد'], 404);
                return;
            }

            // Initialize Gravity Flow API
            $gravity_flow_api = new \Gravity_Flow_API($form_id);

            // Get timeline
            $timeline = $gravity_flow_api->get_timeline($entry);

            // Get current status
            $current_status = $gravity_flow_api->get_status($entry);
            $current_step = $gravity_flow_api->get_current_step($entry);

            \wp_send_json_success([
                'timeline' => $timeline,
                'current_status' => $current_status,
                'current_step' => $current_step ? [
                    'id' => $current_step->get_id(),
                    'name' => $current_step->get_name(),
                    'type' => $current_step->get_type()
                ] : null,
                'entry_id' => $entry_id
            ]);

        } catch (Exception $e) {
            error_log('Get Entry Timeline Error: ' . $e->getMessage());
            \wp_send_json_error(['message' => 'خطای داخلی سرور'], 500);
        }
    }

    /**
     * Debug workflow status for entry (can be called via API for troubleshooting)
     */
    public function debugWorkflowStatus()
    {
        try {
            $entry_id = intval($_GET['entry_id'] ?? $_POST['entry_id'] ?? 0);
            
            if (!$entry_id) {
                wp_send_json_error(['message' => 'Entry ID is required']);
                return;
            }

            appLogger("GravityController: Debug workflow status for entry ID: $entry_id");

            if (!class_exists('GFAPI') || !class_exists('Gravity_Flow_API')) {
                wp_send_json_error(['message' => 'Gravity Forms/Flow not available']);
                return;
            }

            $entry = \GFAPI::get_entry($entry_id);
            if (is_wp_error($entry)) {
                wp_send_json_error(['message' => 'Entry not found: ' . $entry->get_error_message()]);
                return;
            }

            $gravity_flow_api = new \Gravity_Flow_API($entry['form_id']);
            $current_step = $gravity_flow_api->get_current_step($entry);
            
            $debug_info = [
                'entry_id' => $entry_id,
                'form_id' => $entry['form_id'],
                'current_step' => $current_step ? [
                    'id' => $current_step->get_id(),
                    'name' => $current_step->get_name(),
                    'type' => $current_step->get_type(),
                    'status' => method_exists($current_step, 'get_status') ? $current_step->get_status() : 'unknown'
                ] : null,
                'workflow_final_status' => function_exists('gform_get_meta') ? \gform_get_meta($entry_id, 'workflow_final_status') : 'unknown',
                'entry_status' => $entry['status'] ?? 'unknown',
                'available_actions' => []
            ];

            if ($current_step) {
                // Check available actions
                if (method_exists($current_step, 'can_approve')) {
                    $debug_info['available_actions']['can_approve'] = $current_step->can_approve();
                }
                if (method_exists($current_step, 'can_reject')) {
                    $debug_info['available_actions']['can_reject'] = $current_step->can_reject();
                }
            }

            appLogger("GravityController: Debug info for entry $entry_id: " . json_encode($debug_info));
            
            wp_send_json_success(['debug_info' => $debug_info]);
            
        } catch (Exception $e) {
            appLogger("GravityController: Exception in debugWorkflowStatus: " . $e->getMessage());
            wp_send_json_error(['message' => 'Debug error: ' . $e->getMessage()]);
        }
    }

}