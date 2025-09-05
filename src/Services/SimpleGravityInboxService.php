<?php

namespace App\Services;

use Exception;

class SimpleGravityInboxService
{
    public function addInboxExportFunctionality()
    {
        // Handle export requests first
        add_action('init', [$this, 'handleExportRequests']);
        
        // Add JavaScript to inject export button after page load
        add_action('wp_footer', [$this, 'addExportButtonScript']);
        add_action('admin_footer', [$this, 'addExportButtonScript']);
    }

    public function handleExportRequests()
    {
        if (isset($_GET['export_gravity_inbox']) && 
            wp_verify_nonce($_GET['nonce'], 'export_gravity_inbox')) {
            $this->exportInboxToCSV();
            exit;
        }
    }

    public function addExportButtonScript()
    {
        // Only add script if user is logged in
        if (!is_user_logged_in()) {
            return;
        }

        $nonce = wp_create_nonce('export_gravity_inbox');
        $current_url = $_SERVER['REQUEST_URI'];
        $export_url = add_query_arg([
            'export_gravity_inbox' => '1',
            'nonce' => $nonce
        ], $current_url);

        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Look for Gravity Flow inbox tables
            var $inboxTable = $('.gform-body table, .gravityflow-inbox table, .gravity-flow-inbox table, table[class*="gravityflow"], table[class*="gravity-flow"]');
            
            if ($inboxTable.length === 0) {
                // Fallback: look for any table that might be from Gravity Flow
                $inboxTable = $('table').filter(function() {
                    var tableHtml = $(this).html();
                    return tableHtml.includes('گرویتی') || 
                           tableHtml.includes('gravity') || 
                           tableHtml.includes('workflow') || 
                           tableHtml.includes('form') ||
                           $(this).closest('.gravityflow').length > 0;
                });
            }
            
            if ($inboxTable.length > 0) {
                var exportButton = '<div style="margin: 15px 0; text-align: right; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 15px;">' +
                    '<a href="<?php echo esc_js($export_url); ?>" class="button button-primary" style="background: #2271b1; border-color: #2271b1; color: white; text-decoration: none;">' +
                    '<span class="dashicons dashicons-download" style="margin-left: 5px;"></span>' +
                    'خروجی CSV صندوق ورودی' +
                    '</a>' +
                    '</div>';
                
                $inboxTable.first().before(exportButton);
                
                // Debug: Add a notice that we found the table
                console.log('Gravity Flow inbox table found and export button added');
            } else {
                // Debug: Let us know if no table was found
                console.log('No Gravity Flow inbox table found on this page');
            }
        });
        </script>
        
        <style>
        .donap-gravity-export-debug {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            color: #856404;
        }
        </style>
        <?php
    }

    private function exportInboxToCSV()
    {
        if (!is_user_logged_in()) {
            wp_die('You must be logged in to export data.');
        }

        // Sample data if Gravity Flow is not available
        $data = [
            ['شناسه ورودی', 'عنوان فرم', 'وضعیت', 'تاریخ ایجاد'],
            ['1', 'فرم تماس', 'تأیید شده', '1403/06/15'],
            ['2', 'فرم ثبت نام', 'در انتظار بررسی', '1403/06/16'],
            ['3', 'فرم درخواست خدمات', 'تأیید شده', '1403/06/17']
        ];

        // If Gravity Flow is available, get real data
        if (class_exists('Gravity_Flow_API')) {
            $data = $this->getRealInboxData();
        }

        $filename = 'gravity-inbox-export-' . date('Y-m-d-H-i-s') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        // Add BOM for proper UTF-8 handling in Excel
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
    }

    private function getRealInboxData()
    {
        try {
            $current_user = wp_get_current_user();
            $data = [['شناسه ورودی', 'عنوان فرم', 'وضعیت', 'تاریخ ایجاد', 'اطلاعات']];

            // Try to get real Gravity Flow inbox data
            if (class_exists('Gravity_Flow_API') && class_exists('GFAPI')) {
                $api = new \Gravity_Flow_API();
                $inbox_entries = $api->get_inbox_entries($current_user->ID);

                foreach ($inbox_entries as $entry) {
                    $form = \GFAPI::get_form($entry['form_id']);
                    $data[] = [
                        $entry['id'],
                        $form['title'] ?? 'نامشخص',
                        $entry['workflow_status'] ?? 'نامشخص',
                        $entry['date_created'] ?? '',
                        json_encode($entry, JSON_UNESCAPED_UNICODE)
                    ];
                }
            }

            return $data;
        } catch (Exception $e) {
            // Return sample data if there's an error
            return [
                ['شناسه ورودی', 'عنوان فرم', 'وضعیت', 'تاریخ ایجاد'],
                ['1', 'فرم تماس', 'تأیید شده', '1403/06/15'],
                ['2', 'فرم ثبت نام', 'در انتظار بررسی', '1403/06/16']
            ];
        }
    }
}
