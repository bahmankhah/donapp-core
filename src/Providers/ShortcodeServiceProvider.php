<?php

namespace App\Providers;

use Kernel\Facades\Wordpress;
use Exception;

class ShortcodeServiceProvider
{
    public function register() {}

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

            // Prepare view data
            $view_data = [
                'entries' => $result['data'],
                'pagination' => $result['pagination'],
                'stats' => $result['stats'] ?? [],
                'attributes' => $atts,
                'current_page' => $current_page,
                'nonce' => \wp_create_nonce('gravity_flow_inbox_action'),
                'success' => $result['success']
            ];

            return view('shortcodes/gravity-flow-inbox', $view_data);
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
