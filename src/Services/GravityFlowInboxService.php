<?php

namespace App\Services;

use Exception;
use Kernel\Container;

class GravityFlowInboxService
{
    protected $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Hook into Gravity Flow inbox to add CSV export functionality
     */
    public function addInboxExportFunctionality()
    {
        // Hook into gravity flow shortcode output to add our export buttons
        add_filter('gravityflow_shortcode_output', [$this, 'addExportButtonsToInbox'], 10, 3);
        
        // Handle CSV export requests
        add_action('init', [$this, 'handleInboxExportRequests']);
        
        // Add custom CSS and JS for export buttons
        add_action('wp_enqueue_scripts', [$this, 'enqueueInboxExportAssets']);
    }

    /**
     * Add export buttons to Gravity Flow inbox shortcode
     */
    public function addExportButtonsToInbox($output, $atts, $content)
    {
        // Only add to inbox pages
        if (!isset($atts['page']) || $atts['page'] !== 'inbox') {
            return $output;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return $output;
        }

        // Add export button to the top of the inbox
        $export_nonce = wp_create_nonce('export_gravity_inbox');
        $export_button = $this->getInboxExportButton($export_nonce);
        
        // Insert export button before the table
        $output = $export_button . $output;
        
        // Add individual export buttons to table rows
        $output = $this->addIndividualExportButtons($output);
        
        return $output;
    }

    /**
     * Get the inbox-wide export button HTML
     */
    private function getInboxExportButton($nonce)
    {
        $current_url = $_SERVER['REQUEST_URI'];
        $export_url = add_query_arg([
            'export_inbox_csv' => '1',
            'inbox_nonce' => $nonce
        ], $current_url);

        return '
        <div class="donap-inbox-export-section" style="margin: 15px 0; text-align: right;">
            <a href="' . esc_url($export_url) . '" 
               class="button button-primary donap-inbox-export-btn">
                <span class="dashicons dashicons-download" style="margin-left: 5px;"></span>
                خروجی CSV کل صندوق ورودی
            </a>
        </div>';
    }

    /**
     * Add individual export buttons to each table row
     */
    private function addIndividualExportButtons($output)
    {
        // Use regex to find table rows and add export buttons
        $pattern = '/<tr[^>]*data-entry-id="(\d+)"[^>]*>(.*?)<\/tr>/s';
        
        $output = preg_replace_callback($pattern, function($matches) {
            $entry_id = $matches[1];
            $row_content = $matches[2];
            
            // Add export button to the last cell
            $export_nonce = wp_create_nonce('export_gravity_entry_' . $entry_id);
            $current_url = $_SERVER['REQUEST_URI'];
            $export_url = add_query_arg([
                'export_entry_csv' => $entry_id,
                'entry_nonce' => $export_nonce
            ], $current_url);
            
            $export_button = '<td style="text-align: center;">
                <a href="' . esc_url($export_url) . '" 
                   class="button button-small donap-entry-export-btn"
                   title="خروجی CSV این فرم">
                    <span class="dashicons dashicons-download"></span>
                </a>
            </td>';
            
            // If the row doesn't have the expected structure, add button at the end
            if (substr_count($row_content, '</td>') > 0) {
                $row_content .= $export_button;
            }
            
            return '<tr data-entry-id="' . $entry_id . '">' . $row_content . '</tr>';
        }, $output);
        
        // Also add header for the new column
        $output = preg_replace(
            '/(<thead[^>]*>.*?<tr[^>]*>.*?)(<\/tr>.*?<\/thead>)/s',
            '$1<th style="text-align: center;">خروجی</th>$2',
            $output
        );
        
        return $output;
    }

    /**
     * Handle export requests
     */
    public function handleInboxExportRequests()
    {
        // Handle full inbox export
        if (isset($_GET['export_inbox_csv']) && 
            wp_verify_nonce($_GET['inbox_nonce'], 'export_gravity_inbox')) {
            $this->exportInboxToCSV();
            exit;
        }
        
        // Handle individual entry export
        if (isset($_GET['export_entry_csv']) && 
            isset($_GET['entry_nonce'])) {
            $entry_id = intval($_GET['export_entry_csv']);
            if (wp_verify_nonce($_GET['entry_nonce'], 'export_gravity_entry_' . $entry_id)) {
                $this->exportSingleEntryToCSV($entry_id);
                exit;
            }
        }
    }

    /**
     * Export entire inbox to CSV
     */
    private function exportInboxToCSV()
    {
        if (!class_exists('Gravity_Flow_API')) {
            wp_die('Gravity Flow is not available.');
        }

        $current_user = wp_get_current_user();
        if (!$current_user || !$current_user->ID) {
            wp_die('You must be logged in to export data.');
        }

        // Get inbox entries using Gravity Flow API
        $inbox_entries = $this->getInboxEntries($current_user->ID);
        
        if (empty($inbox_entries)) {
            wp_die('No inbox entries found to export.');
        }

        // Prepare CSV data
        $csv_data = [];
        $csv_data[] = [
            'شناسه ورودی',
            'عنوان فرم', 
            'وضعیت جریان کار',
            'مرحله فعلی',
            'تاریخ ایجاد',
            'تاریخ آخرین به‌روزرسانی',
            'اطلاعات فرم'
        ];

        foreach ($inbox_entries as $entry_data) {
            $entry = $entry_data['entry'];
            $step = $entry_data['step'];
            $form = $entry_data['form'];
            
            // Get form data as string
            $form_data = $this->getFormDataString($entry, $form);
            
            $csv_data[] = [
                $entry['id'],
                $form['title'],
                $this->getWorkflowStatus($entry),
                $step ? $step->get_label() : 'نامشخص',
                date('Y/m/d H:i', strtotime($entry['date_created'])),
                date('Y/m/d H:i', strtotime($entry['date_updated'])),
                $form_data
            ];
        }

        $this->outputCSV($csv_data, 'gravity-flow-inbox-' . date('Y-m-d-H-i-s') . '.csv');
    }

    /**
     * Export single entry to CSV
     */
    private function exportSingleEntryToCSV($entry_id)
    {
        if (!class_exists('GFAPI')) {
            wp_die('Gravity Forms is not available.');
        }

        $entry = class_exists('GFAPI') ? \GFAPI::get_entry($entry_id) : null;
        if (is_wp_error($entry) || !$entry) {
            wp_die('Entry not found.');
        }

        $form = class_exists('GFAPI') ? \GFAPI::get_form($entry['form_id']) : null;
        if (is_wp_error($form) || !$form) {
            wp_die('Form not found.');
        }

        // Check if user has access to this entry
        if (!$this->userCanAccessEntry($entry, wp_get_current_user()->ID)) {
            wp_die('You do not have permission to export this entry.');
        }

        // Prepare CSV data with detailed form fields
        $csv_data = [];
        $csv_data[] = ['فیلد', 'مقدار'];
        
        // Add basic entry info
        $csv_data[] = ['شناسه ورودی', $entry['id']];
        $csv_data[] = ['عنوان فرم', $form['title']];
        $csv_data[] = ['تاریخ ایجاد', date('Y/m/d H:i', strtotime($entry['date_created']))];
        $csv_data[] = ['تاریخ به‌روزرسانی', date('Y/m/d H:i', strtotime($entry['date_updated']))];
        $csv_data[] = ['وضعیت', $this->getWorkflowStatus($entry)];
        $csv_data[] = ['', '']; // Empty row for separation
        
        // Add form fields
        foreach ($form['fields'] as $field) {
            $field_id = $field->id;
            $field_label = $field->label;
            $field_value = isset($entry[$field_id]) ? $entry[$field_id] : '';
            
            // Skip empty values and system fields
            if (empty($field_value) || in_array($field->type, ['page', 'section', 'html'])) {
                continue;
            }

            // Format value based on field type
            $formatted_value = $this->formatFieldValue($field_value, $field);
            
            $csv_data[] = [$field_label, $formatted_value];
        }

        $filename = 'gravity-entry-' . $entry_id . '-' . date('Y-m-d-H-i-s') . '.csv';
        $this->outputCSV($csv_data, $filename);
    }

    /**
     * Get inbox entries for current user
     */
    private function getInboxEntries($user_id)
    {
        if (!class_exists('Gravity_Flow_API')) {
            return [];
        }

        $inbox_entries = [];
        
        // Get all forms
        $forms = class_exists('GFAPI') ? \GFAPI::get_forms() : [];
        
        foreach ($forms as $form) {
            // Check if form has Gravity Flow
            if (!$this->formHasGravityFlow($form['id'])) {
                continue;
            }
            
            // Get pending entries for this user
            $entries = $this->getPendingEntriesForUser($form['id'], $user_id);
            
            foreach ($entries as $entry) {
                $step = $this->getCurrentStep($entry);
                
                $inbox_entries[] = [
                    'entry' => $entry,
                    'form' => $form,
                    'step' => $step
                ];
            }
        }
        
        return $inbox_entries;
    }

    /**
     * Check if form has Gravity Flow workflow
     */
    private function formHasGravityFlow($form_id)
    {
        $flow_settings = get_option('gravityflow_settings_' . $form_id, []);
        return !empty($flow_settings);
    }

    /**
     * Get pending entries for user
     */
    private function getPendingEntriesForUser($form_id, $user_id)
    {
        if (!class_exists('GFAPI')) {
            return [];
        }

        // Get entries that are pending for this user
        $search_criteria = [
            'status' => 'active',
            'field_filters' => [
                [
                    'key' => 'workflow_step_status',
                    'value' => 'pending'
                ]
            ]
        ];

        $entries = class_exists('GFAPI') ? \GFAPI::get_entries($form_id, $search_criteria) : [];
        
        // Filter entries where user is assigned
        $user_entries = [];
        foreach ($entries as $entry) {
            if ($this->isUserAssignedToEntry($entry, $user_id)) {
                $user_entries[] = $entry;
            }
        }
        
        return $user_entries;
    }

    /**
     * Check if user is assigned to entry
     */
    private function isUserAssignedToEntry($entry, $user_id)
    {
        // Check gravity flow step status
        $assigned_users = '';
        if (function_exists('gform_get_meta')) {
            $assigned_users = gform_get_meta($entry['id'], 'workflow_assigned_users');
        }
        
        if (is_array($assigned_users) && in_array($user_id, $assigned_users)) {
            return true;
        }
        
        // Also check if user has general access
        return current_user_can('manage_options') || $entry['created_by'] == $user_id;
    }

    /**
     * Get current workflow step
     */
    private function getCurrentStep($entry)
    {
        if (!class_exists('Gravity_Flow_API')) {
            return null;
        }
        
        $api = new \Gravity_Flow_API($entry['form_id']);
        return $api->get_current_step($entry);
    }

    /**
     * Get workflow status
     */
    private function getWorkflowStatus($entry)
    {
        if (isset($entry['workflow_final_status'])) {
            return $this->translateStatus($entry['workflow_final_status']);
        }
        
        if (isset($entry['workflow_step_status'])) {
            return $this->translateStatus($entry['workflow_step_status']);
        }
        
        return 'نامشخص';
    }

    /**
     * Translate status to Persian
     */
    private function translateStatus($status)
    {
        $translations = [
            'pending' => 'در انتظار',
            'approved' => 'تأیید شده',
            'rejected' => 'رد شده',
            'complete' => 'تکمیل شده',
            'cancelled' => 'لغو شده'
        ];
        
        return $translations[$status] ?? $status;
    }

    /**
     * Check if user can access entry
     */
    private function userCanAccessEntry($entry, $user_id)
    {
        // Admin can access all
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // User can access their own entries
        if ($entry['created_by'] == $user_id) {
            return true;
        }
        
        // Check if user is assigned to this entry
        return $this->isUserAssignedToEntry($entry, $user_id);
    }

    /**
     * Get form data as concatenated string
     */
    private function getFormDataString($entry, $form)
    {
        $data_parts = [];
        
        foreach ($form['fields'] as $field) {
            $field_id = $field->id;
            $field_value = isset($entry[$field_id]) ? $entry[$field_id] : '';
            
            if (!empty($field_value) && !in_array($field->type, ['page', 'section', 'html'])) {
                $formatted_value = $this->formatFieldValue($field_value, $field);
                $data_parts[] = $field->label . ': ' . strip_tags($formatted_value);
            }
        }
        
        return implode(' | ', $data_parts);
    }

    /**
     * Format field value based on type
     */
    private function formatFieldValue($value, $field)
    {
        switch ($field->type) {
            case 'fileupload':
                return !empty($value) ? basename($value) : '';
            case 'date':
                $timestamp = strtotime($value);
                return $timestamp ? date('Y/m/d', $timestamp) : $value;
            case 'phone':
            case 'email':
            case 'website':
                return $value;
            default:
                return strip_tags($value);
        }
    }

    /**
     * Output CSV file
     */
    private function outputCSV($csv_data, $filename)
    {
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
    }

    /**
     * Enqueue assets for inbox export functionality
     */
    public function enqueueInboxExportAssets()
    {
        // Only enqueue on pages that might have Gravity Flow shortcodes
        if (is_singular() || is_page()) {
            wp_add_inline_style('wp-admin', '
                .donap-inbox-export-section {
                    background: #f9f9f9;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    padding: 15px;
                    margin: 15px 0;
                }
                
                .donap-inbox-export-btn {
                    background: #2271b1 !important;
                    border-color: #2271b1 !important;
                    color: white !important;
                    text-decoration: none !important;
                }
                
                .donap-inbox-export-btn:hover {
                    background: #135e96 !important;
                    border-color: #135e96 !important;
                }
                
                .donap-entry-export-btn {
                    background: #50575e !important;
                    border-color: #50575e !important;
                    color: white !important;
                    text-decoration: none !important;
                    padding: 4px 8px !important;
                }
                
                .donap-entry-export-btn:hover {
                    background: #3c434a !important;
                    border-color: #3c434a !important;
                }
            ');
        }
    }
}
