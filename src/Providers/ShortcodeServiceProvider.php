<?php

namespace App\Providers;

use Kernel\Container;
use Kernel\Facades\Wordpress;
use Exception;

class ShortcodeServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        Wordpress::shortcode('donap_wallet_topup', function () {
            if (!\is_user_logged_in()) {
                return '<p>برای شارژ کیف پول ابتدا وارد شوید.</p>';
            }
            return view('pages/wallet-topup');
        });

        // Enhanced Gravity Flow Inbox Shortcode
        Wordpress::shortcode('donap_gravityflow_inbox', function ($atts) {
            $atts = \shortcode_atts([
                'per_page' => 20,
                'show_bulk_actions' => 'true',
                'show_filters' => 'true',
                'mobile_responsive' => 'true',
                'show_pagination' => 'true',
                'table_class' => 'donap-gravity-flow-table',
                'show_stats' => 'true',
                'show_export_buttons' => 'true'
            ], $atts);

            return $this->renderGravityFlowInbox($atts);
        });

        // Export buttons shortcode (existing)
        Wordpress::shortcode('donap_gravity_export_buttons', function ($atts) {
            $atts = \shortcode_atts([
                'style' => 'buttons',
                'align' => 'right',
                'show_csv' => 'true',
                'show_excel' => 'true',
                'show_pdf' => 'true'
            ], $atts);

            // Convert string values to boolean for template
            $view_data = [
                'style' => $atts['style'],
                'align' => $atts['align'],
                'show_csv' => $atts['show_csv'] === 'true',
                'show_excel' => $atts['show_excel'] === 'true',
                'show_pdf' => $atts['show_pdf'] === 'true',
                'user_id' => \get_current_user_id()
            ];

            return view('shortcodes/gravity-export-buttons', $view_data);
        });

        // Single entry export shortcode (existing)
        Wordpress::shortcode('donap_gravity_single_export', function ($atts) {
            $atts = \shortcode_atts([
                'entry_id' => '',
                'form_id' => '',
                'style' => 'dropdown',
                'show_pdf' => 'true',
                'show_excel' => 'true',
                'auto_detect' => 'false'
            ], $atts);

            // Convert string values to boolean for template
            $view_data = [
                'entry_id' => $atts['entry_id'],
                'form_id' => $atts['form_id'],
                'style' => $atts['style'],
                'show_pdf' => $atts['show_pdf'] === 'true',
                'show_excel' => $atts['show_excel'] === 'true',
                'auto_detect' => $atts['auto_detect'] === 'true'
            ];

            return view('shortcodes/gravity-single-export', $view_data);
        });

        // Gravity Flow Inbox Export Buttons Shortcode
        Wordpress::shortcode('donap_gravityflow_inbox_export', function ($atts) {
            $atts = \shortcode_atts([
                'style' => 'buttons',
                'align' => 'right',
                'show_csv' => 'true',
                'show_excel' => 'true',
                'show_pdf' => 'true',
                'button_text' => 'صادرات صندوق ورودی',
                'user_id' => get_current_user_id()
            ], $atts);

            return $this->renderInboxExportButtons($atts);
        });

        Wordpress::shortcode('donap_gravity_flow_approved_table', function ($atts) {
            return $this->render_gravity_flow_approved_shortcode($atts);
        });
    }

    /**
     * Render the Gravity Flow table shortcode
     */
    private function render_gravity_flow_approved_shortcode($atts)
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
     * Render enhanced Gravity Flow inbox table
     */
    private function renderGravityFlowInbox($atts)
    {
        try {
            $gravityService = \Kernel\Container::resolve('GravityService');

            // Get current page from query params
            $current_page = max(1, intval($_GET['gf_page'] ?? 1));
            $per_page = intval($atts['per_page']);

            // Get inbox entries data using the new method
            $result = $gravityService->getGravityFlowInboxPage($current_page, $per_page);

            if (!$result['success']) {
                return '<div class="donap-error-message"><i class="fas fa-exclamation-triangle"></i> ' . esc_html($result['message']) . '</div>';
            }

            // Prepare export URLs
            $current_user_id = \get_current_user_id();

            // Prepare view data
            $view_data = [
                'entries' => $result['data'],
                'pagination' => $result['pagination'],
                'stats' => $result['stats'] ?? [],
                'attributes' => $atts,
                'current_page' => $current_page,
                'success' => $result['success'],
                'bulk_action_url' => \rest_url('dnp/v1/gravity/bulk-action'),
                'inbox_csv_url' => \rest_url('dnp/v1/gravity/inbox/export-csv?uid=' . $current_user_id),
                'inbox_excel_url' => \rest_url('dnp/v1/gravity/inbox/export-xlsx?uid=' . $current_user_id),
                'inbox_pdf_url' => \rest_url('dnp/v1/gravity/inbox/export-pdf?uid=' . $current_user_id)
            ];

            return view('shortcodes/gravityflow-inbox', $view_data);
        } catch (Exception $e) {
            error_log('Gravity Flow Inbox Shortcode Error: ' . $e->getMessage());
            return '<div class="donap-error-message"><i class="fas fa-exclamation-triangle"></i> خطا در بارگیری صندوق ورودی: ' . esc_html($e->getMessage()) . '</div>';
        }
    }

    /**
     * Render Gravity Flow inbox export buttons
     */
    private function renderInboxExportButtons($atts)
    {
        try {
            // Convert string values to boolean for template
            $view_data = [
                'style' => $atts['style'],
                'align' => $atts['align'],
                'show_csv' => $atts['show_csv'] === 'true',
                'show_excel' => $atts['show_excel'] === 'true',
                'show_pdf' => $atts['show_pdf'] === 'true',
                'button_text' => $atts['button_text'],
                'user_id' => intval($atts['user_id'])
            ];

            return view('shortcodes/gravityflow-inbox-export-buttons', $view_data);
        } catch (Exception $e) {
            error_log('Inbox Export Buttons Shortcode Error: ' . $e->getMessage());
            return '<div class="donap-error-message"><i class="fas fa-exclamation-triangle"></i> خطا در بارگیری دکمه‌های صادرات: ' . esc_html($e->getMessage()) . '</div>';
        }
    }

}
