<?php

namespace App\Providers;

use Kernel\Container;
use Kernel\Facades\Wordpress;

class ShortcodeServiceProvider
{
    public function register() {}

    public function boot()
    {
        Wordpress::shortcode('donap_wallet_topup', function () {
            if (!is_user_logged_in()) {
                return '<p>برای شارژ کیف پول ابتدا وارد شوید.</p>';
            }
            return view('pages/wallet-topup');
        });

        Wordpress::shortcode('donap_gravity_flow_table', function ($atts) {
            return $this->render_gravity_flow_shortcode($atts);
        });
    }

    /**
     * Render the Gravity Flow table shortcode
     */
    private function render_gravity_flow_shortcode($atts)
    {
        // Parse shortcode attributes
        $atts = shortcode_atts([
            'per_page' => 10,
            'show_filters' => 'true',
            'show_export' => 'true',
            'show_stats' => 'true',
            'user_only' => 'false' // If true, only show current user's entries
        ], $atts);

        // Check if user is logged in (required for Gravity Flow functionality)
        if (!is_user_logged_in()) {
            return '<div class="donap-gravity-notice"><p>برای مشاهده فرم‌های گرویتی فلو باید وارد شوید.</p></div>';
        }

        // Handle CSV export
        if (isset($_GET['export_gravity_csv']) && wp_verify_nonce($_GET['gravity_nonce'], 'export_gravity_csv_shortcode')) {
            $this->handle_shortcode_csv_export();
            return; // Will exit after export
        }

        $gravityService = Container::resolve('GravityService');
        
        // Get pagination parameters
        $page = max(1, intval($_GET['paged'] ?? 1));
        $per_page = intval($atts['per_page']);
        
        // Get filters from request
        $filters = [
            'form_filter' => $_GET['form_filter'] ?? '',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? ''
        ];
        
        // Get entries and stats
        $entries_result = $gravityService->getApprovedGravityFlowEntries($page, $per_page);
        $stats = ($atts['show_stats'] === 'true') ? $gravityService->getApprovedEntriesStats() : [];
        
        // Check for plugin availability
        $warning_message = '';
        if (!class_exists('GFForms') || !class_exists('Gravity_Flow')) {
            $warning_message = 'توجه: افزونه‌های Gravity Forms و Gravity Flow نصب نیستند. داده‌های نمایش داده شده نمونه هستند.';
        }
        
        $data = [
            'entries' => $entries_result['data'],
            'pagination' => $entries_result['pagination'],
            'stats' => $stats,
            'current_filters' => $filters,
            'export_nonce' => wp_create_nonce('export_gravity_csv_shortcode'),
            'warning_message' => $warning_message,
            'show_filters' => $atts['show_filters'] === 'true',
            'show_export' => $atts['show_export'] === 'true',
            'show_stats' => $atts['show_stats'] === 'true',
            'is_shortcode' => true,
            'base_url' => get_permalink() // For pagination links
        ];
        
        return view('shortcodes/gravity-flow', $data);
    }

    /**
     * Handle CSV export for shortcode
     */
    private function handle_shortcode_csv_export()
    {
        $gravityService = Container::resolve('GravityService');
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
