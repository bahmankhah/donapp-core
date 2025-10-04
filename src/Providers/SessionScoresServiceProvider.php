<?php

namespace App\Providers;

use Kernel\Container;
use Exception;

class SessionScoresServiceProvider
{
    public function register()
    {
        // Register the SessionScoresService in the container
        Container::bind('SessionScoresService', function () {
            return new \App\Services\SessionScoresService();
        });
    }

    public function boot()
    {
        // Register the shortcode
        add_shortcode('donap_gravity_session_scores_table', [$this, 'render_session_scores_table']);
        
        // Register AJAX endpoints for CSV export
        add_action('wp_ajax_donap_export_selected_scores', [$this, 'handle_ajax_export']);
        add_action('wp_ajax_nopriv_donap_export_selected_scores', [$this, 'handle_ajax_export']);
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Render the session scores table shortcode
     */
    public function render_session_scores_table($atts)
    {
        $atts = shortcode_atts([
            'form_id' => '',    // Form ID - required parameter
            'view_id' => '',    // GravityView ID for detecting fields and form
            'secret' => '',     // Secret key for validation
            'per_page' => 20,
            'show_pagination' => 'true',
            'show_checkboxes' => 'true',
            'show_sum_column' => 'false',
            'show_summary_table' => 'true',  // Show column totals summary table
            'sort_by_sum' => 'true',
            'sort_order' => 'DESC'  // DESC for highest scores first
        ], $atts);

        // If view_id is provided but form_id is not, try to get form_id from GravityView
        if (!empty($atts['view_id']) && empty($atts['form_id'])) {
            $view_post = get_post($atts['view_id']);
            if ($view_post && $view_post->post_type === 'gravityview') {
                $form_id = get_post_meta($atts['view_id'], '_gravityview_form_id', true);
                if ($form_id) {
                    $atts['form_id'] = $form_id;
                }
            }
        }

        // Validate required parameters
        if (empty($atts['form_id'])) {
            return '<div class="error">خطا: شناسه فرم (form_id) یا شناسه نما (view_id) الزامی است</div>';
        }

        if (empty($atts['view_id'])) {
            return '<div class="error">خطا: شناسه نما (view_id) الزامی است برای تشخیص ستون‌ها</div>';
        }

        try {
            $controller = new \App\Controllers\SessionScoresController();
            return $controller->renderTable($atts);
        } catch (Exception $e) {
            error_log('SessionScores Shortcode Error: ' . $e->getMessage());
            return '<div class="error">خطا در نمایش جدول امتیازات جلسات</div>';
        }
    }

    /**
     * Handle AJAX export request
     */
    public function handle_ajax_export()
    {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'donap_export_scores')) {
            wp_die('Security check failed');
        }

        try {
            $controller = new \App\Controllers\SessionScoresController();
            $controller->handleExport();
        } catch (Exception $e) {
            error_log('SessionScores Export Error: ' . $e->getMessage());
            wp_die('Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Enqueue necessary scripts and styles
     */
    public function enqueue_assets()
    {
        // Only enqueue on pages that might have our shortcode
        if (is_singular() && has_shortcode(get_post()->post_content, 'donap_gravity_session_scores_table')) {
            wp_enqueue_script(
                'donap-session-scores',
                plugin_dir_url(__DIR__ . '/../../') . 'src/assets/js/session-scores.js',
                ['jquery'],
                '1.0.0',
                true
            );

            wp_enqueue_style(
                'donap-session-scores',
                plugin_dir_url(__DIR__ . '/../../') . 'src/assets/css/session-scores.css',
                [],
                '1.0.0'
            );

            // Localize script for AJAX
            wp_localize_script('donap-session-scores', 'donapSessionScores', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('donap_export_scores'),
                'strings' => [
                    'selectItems' => 'لطفا حداقل یک مورد را انتخاب کنید',
                    'exportError' => 'خطا در اکسپورت فایل',
                    'exportSuccess' => 'فایل با موفقیت دانلود شد'
                ]
            ]);
        }
    }
}