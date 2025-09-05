<?php

namespace App\Providers;

use Kernel\Container;
use Exception;

class GravityServiceProvider
{
    private $main_menu_slug = 'donap-dashboard';
    private $capability = 'manage_options';

    public function register() {}

    public function boot()
    {
        add_action('admin_menu', [$this, 'register_gravity_menu'], 20);
        
        // Initialize inbox export functionality
        $this->initializeInboxExport();
    }

    /**
     * Initialize Gravity Flow inbox export functionality
     */
    private function initializeInboxExport()
    {
        // Only initialize on frontend or when shortcodes might be processed
        if (!is_admin()) {
            $inboxService = Container::resolve('GravityFlowInboxService');
            $inboxService->addInboxExportFunctionality();
        }
    }

    /**
     * Register Gravity Flow submenu under Donap dashboard
     */
    public function register_gravity_menu()
    {
        // Add submenu under the main Donap menu
        add_submenu_page(
            $this->main_menu_slug,
            'فرم‌های تأیید شده گرویتی فلو',          // Page title
            'فرم‌های گرویتی فلو',                   // Menu title
            $this->capability,                      // Capability
            'donap-gravity-flow',                   // Menu slug
            [$this, 'gravity_flow_page']           // Function
        );
    }

    /**
     * Gravity Flow page content
     */
    public function gravity_flow_page()
    {
        $gravityService = Container::resolve('GravityService');
        
        // Handle CSV export
        if (isset($_GET['export_csv']) && wp_verify_nonce($_GET['gravity_nonce'], 'export_gravity_csv')) {
            $this->handle_csv_export($gravityService);
            return;
        }
        
        // Get pagination parameters
        $page = max(1, intval($_GET['paged'] ?? 1));
        $per_page = 20;
        
        // Get filters from request
        $filters = [
            'form_filter' => $_GET['form_filter'] ?? '',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? ''
        ];
        
        $entries_result = $gravityService->getApprovedGravityFlowEntries($page, $per_page);
        $stats = $gravityService->getApprovedEntriesStats();
        
        $error_message = '';
        if (!class_exists('GFForms') || !class_exists('Gravity_Flow')) {
            $error_message = 'توجه: افزونه‌های Gravity Forms و Gravity Flow نصب نیستند. داده‌های نمایش داده شده نمونه هستند.';
        }
        
        $data = [
            'entries' => $entries_result['data'],
            'pagination' => $entries_result['pagination'],
            'stats' => $stats,
            'current_filters' => $filters,
            'export_nonce' => wp_create_nonce('export_gravity_csv'),
            'warning_message' => $error_message
        ];
        
        echo view('admin/gravity-flow', $data);
    }

    /**
     * Handle CSV export
     */
    private function handle_csv_export($gravityService)
    {
        $export_result = $gravityService->exportApprovedEntriesToCSV();
        
        if (!$export_result['success']) {
            wp_die($export_result['message']);
            return;
        }
        
        $csv_data = $export_result['data'];
        $filename = $export_result['filename'];
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        // Add BOM for proper UTF-8 handling in Excel
        echo "\xEF\xBB\xBF";
        
        // Output CSV data
        $output = fopen('php://output', 'w');
        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        
        exit;
    }
}
