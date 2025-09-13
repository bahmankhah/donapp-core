<?php

// Alternative usage examples for GravityController methods using ExportFactory

namespace App\Controllers;

use App\Services\GravityService;
use Exception;
use Kernel\Container;
use App\Utils\Export\ExportFactory;

class GravityControllerExample
{
    private GravityService $gravityService;

    public function __construct()
    {
        $this->gravityService = Container::resolve('GravityService');
    }

    /**
     * Export approved Gravity Flow entries to CSV using factory
     */
    public function exportCSV()
    {
        try {
            // Get all entries without pagination
            $all_entries_result = $this->gravityService->getApprovedGravityFlowEntries(1, 1000);
            $entries = $all_entries_result['data'];

            if (empty($entries)) {
                http_response_code(404);
                wp_die('هیچ داده‌ای برای صادرات یافت نشد.', 'داده یافت نشد', ['response' => 404]);
                return;
            }

            // Create exporter using factory
            $exporter = ExportFactory::createGravityApprovedEntriesExporter('csv');
            $result = $exporter->setEntriesData($entries)->generate();

            if (!$result['success']) {
                http_response_code(500);
                wp_die('خطا در تولید CSV: ' . $result['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            // Serve download
            $exporter->serve($result['data'], $result['filename']);
        } catch (Exception $e) {
            error_log('Gravity CSV Export Error: ' . $e->getMessage());
            http_response_code(500);
            wp_die('خطای داخلی سرور: ' . $e->getMessage(), 'خطای سرور', ['response' => 500]);
        }
    }

    /**
     * Export single entry to PDF using factory
     */
    public function exportSingleEntryPDF()
    {
        try {
            $entry_id = intval($_GET['entry_id'] ?? 0);
            $form_id = intval($_GET['form_id'] ?? 0);

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

            // Create exporter using factory
            $exporter = ExportFactory::createGravitySingleEntryExporter('pdf', $entry_id);
            $result = $exporter->setSingleEntryData($entry_result['data'])->generate();

            if (!$result['success']) {
                http_response_code(500);
                wp_die('خطا در تولید PDF: ' . $result['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            // Serve download
            $exporter->serve($result['data'], $result['filename']);
        } catch (Exception $e) {
            error_log('Single Entry PDF Export Error: ' . $e->getMessage());
            http_response_code(500);
            wp_die('خطای داخلی سرور: ' . $e->getMessage(), 'خطای سرور', ['response' => 500]);
        }
    }

    /**
     * Generic export method that can handle any format
     */
    public function exportData()
    {
        try {
            $format = sanitize_text_field($_GET['format'] ?? 'csv');
            $type = sanitize_text_field($_GET['type'] ?? 'approved_entries');

            // Get data based on type
            if ($type === 'approved_entries') {
                $all_entries_result = $this->gravityService->getApprovedGravityFlowEntries(1, 1000);
                $entries = $all_entries_result['data'];

                if (empty($entries)) {
                    http_response_code(404);
                    wp_die('هیچ داده‌ای برای صادرات یافت نشد.', 'داده یافت نشد', ['response' => 404]);
                    return;
                }

                // Create exporter using factory
                $exporter = ExportFactory::createGravityApprovedEntriesExporter($format);
                $result = $exporter->setEntriesData($entries)->generate();

            } elseif ($type === 'single_entry') {
                $entry_id = intval($_GET['entry_id'] ?? 0);
                $form_id = intval($_GET['form_id'] ?? 0);

                if (!$entry_id || !$form_id) {
                    http_response_code(400);
                    wp_die('شناسه ورودی یا فرم مشخص نشده است.', 'خطا', ['response' => 400]);
                    return;
                }

                $entry_result = $this->gravityService->getSingleEntryForExport($form_id, $entry_id);

                if (!$entry_result['success']) {
                    http_response_code(400);
                    wp_die('خطا در بازیابی ورودی: ' . $entry_result['message'], 'خطا در صادرات', ['response' => 400]);
                    return;
                }

                // Create exporter using factory
                $exporter = ExportFactory::createGravitySingleEntryExporter($format, $entry_id);
                $result = $exporter->setSingleEntryData($entry_result['data'])->generate();
            } else {
                http_response_code(400);
                wp_die('نوع صادرات نامشخص است.', 'خطا', ['response' => 400]);
                return;
            }

            if (!$result['success']) {
                http_response_code(500);
                wp_die('خطا در تولید فایل: ' . $result['message'], 'خطای سرور', ['response' => 500]);
                return;
            }

            // Serve download
            $exporter->serve($result['data'], $result['filename']);
        } catch (Exception $e) {
            error_log('Export Error: ' . $e->getMessage());
            http_response_code(500);
            wp_die('خطای داخلی سرور: ' . $e->getMessage(), 'خطای سرور', ['response' => 500]);
        }
    }
}
