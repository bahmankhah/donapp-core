<?php

namespace App\Providers;

use Kernel\Container;
use App\Services\WorkflowService;
use App\Services\UserRoleService;

class WorkflowServiceProvider
{
    private WorkflowService $workflowService;
    private UserRoleService $userRoleService;

    public function register() {}

    public function boot()
    {
        $this->workflowService = Container::resolve('WorkflowService');
        $this->userRoleService = Container::resolve('UserRoleService');

        // Initialize user roles on activation
        add_action('init', [$this, 'initializeRoles']);

        // Hook into Gravity Forms entry submission
        add_action('gform_after_submission', [$this, 'handleFormSubmission'], 10, 2);

        // Hook into Gravity Flow step completion  
        add_action('gravityflow_workflow_complete', [$this, 'handleWorkflowComplete'], 10, 3);
        add_action('gravityflow_step_complete', [$this, 'handleStepComplete'], 10, 3);

        // Add admin menu for workflow management
        add_action('admin_menu', [$this, 'register_workflow_menu'], 25);

        // Add AJAX handlers for workflow actions
        add_action('wp_ajax_workflow_approve_step', [$this, 'handleAjaxApproval']);
        add_action('wp_ajax_workflow_reject_step', [$this, 'handleAjaxRejection']);
    }

    /**
     * Initialize workflow user roles
     */
    public function initializeRoles()
    {
        // Only run once during plugin activation or when roles don't exist
        if (!get_role('school_manager') || !get_role('province_manager')) {
            $this->userRoleService->initializeWorkflowRoles();
        }
    }

    /**
     * Handle form submission and trigger automated workflow
     * @param array $entry
     * @param array $form
     */
    public function handleFormSubmission($entry, $form)
    {
        // Only process forms that have workflow automation enabled
        if (!$this->shouldProcessAutomatically($form)) {
            return;
        }

        try {
            // Process the entry for automated workflow assignment
            $result = $this->workflowService->processNewEntry($entry, $form);

            if ($result) {
                error_log('Automated workflow initiated for entry ' . $entry['id']);
            } else {
                error_log('Failed to initiate automated workflow for entry ' . $entry['id']);
            }
        } catch (\Exception $e) {
            error_log('Workflow automation error: ' . $e->getMessage());
        }
    }

    /**
     * Check if form should be processed automatically
     * @param array $form
     * @return bool
     */
    private function shouldProcessAutomatically($form)
    {
        // Check if form has workflow automation enabled
        $automation_enabled = rgars($form, 'donap_workflow_automation', false);

        // Also check if form has required location fields
        $has_location_fields = $this->formHasLocationFields($form);

        return $automation_enabled || $has_location_fields;
    }

    /**
     * Check if form has required location fields
     * @param array $form
     * @return bool
     */
    private function formHasLocationFields($form)
    {
        if (!isset($form['fields']) || !is_array($form['fields'])) {
            return false;
        }

        $has_province = false;
        $has_city = false;
        $has_school = false;

        foreach ($form['fields'] as $field) {
            $field_label = $field->label ?? '';
            $admin_label = $field->adminLabel ?? '';

            // Check for province field
            if (
                strpos($field_label, 'استان') !== false ||
                strpos($field_label, 'province') !== false ||
                strpos($admin_label, 'province') !== false
            ) {
                $has_province = true;
            }

            // Check for city field
            if (
                strpos($field_label, 'شهر') !== false ||
                strpos($field_label, 'city') !== false ||
                strpos($admin_label, 'city') !== false
            ) {
                $has_city = true;
            }

            // Check for school field
            if (
                strpos($field_label, 'مدرسه') !== false ||
                strpos($field_label, 'school') !== false ||
                strpos($admin_label, 'school') !== false
            ) {
                $has_school = true;
            }
        }

        return $has_province && $has_city && $has_school;
    }

    /**
     * Handle workflow completion
     * @param array $entry
     * @param array $form
     * @param array $workflow_data
     */
    public function handleWorkflowComplete($entry, $form, $workflow_data)
    {
        error_log('Workflow completed for entry ' . $entry['id']);

        // You can add additional logic here for workflow completion
        // such as sending final notifications, updating other systems, etc.
    }

    /**
     * Handle individual step completion
     * @param int $entry_id
     * @param array $step_data
     * @param string $status
     */
    public function handleStepComplete($entry_id, $step_data, $status)
    {
        try {
            if ($status === 'approved') {
                $this->workflowService->handleStepCompletion($entry_id, $step_data, 'approve');
            } elseif ($status === 'rejected') {
                $this->workflowService->handleStepCompletion($entry_id, $step_data, 'reject');
            }
        } catch (\Exception $e) {
            error_log('Step completion handling error: ' . $e->getMessage());
        }
    }

    /**
     * Register workflow management menu
     */
    public function register_workflow_menu()
    {
        add_submenu_page(
            'donap-dashboard',
            'مدیریت گردش کاری',
            'گردش کاری خودکار',
            'manage_options',
            'donap-workflow-automation',
            [$this, 'workflow_automation_page']
        );

        // Add manager assignment submenu
        add_submenu_page(
            'donap-dashboard',
            'مدیران گردش کاری',
            'مدیران گردش کاری',
            'manage_options',
            'donap-workflow-managers',
            [$this, 'workflow_managers_page']
        );
    }

    /**
     * Workflow automation admin page
     */
    public function workflow_automation_page()
    {
        // Handle manager assignment form submission
        if (isset($_POST['assign_manager'])) {
            $this->handleManagerAssignment();
        }

        // Get all active managers
        $managers = $this->userRoleService->getAllActiveManagers();

        // Get workflow statistics
        $stats = $this->getWorkflowStats();

        echo view('admin/workflow-automation', [
            'managers' => $managers,
            'stats' => $stats,
            'nonce' => wp_create_nonce('workflow_management')
        ]);
    }

    /**
     * Workflow managers admin page
     */
    public function workflow_managers_page()
    {
        // Handle form submissions
        if (isset($_POST['create_sample_managers'])) {
            $this->userRoleService->createSampleManagers();
            echo '<div class="notice notice-success"><p>نمونه مدیران ایجاد شدند.</p></div>';
        }

        $managers = $this->userRoleService->getAllActiveManagers();

        echo view('admin/workflow-managers', [
            'managers' => $managers,
            'nonce' => wp_create_nonce('manager_assignment')
        ]);
    }

    /**
     * Handle manager assignment from admin form
     */
    private function handleManagerAssignment()
    {
        if (!wp_verify_nonce($_POST['workflow_nonce'], 'workflow_management')) {
            wp_die('Security check failed');
        }

        $user_id = intval($_POST['user_id']);
        $manager_type = sanitize_text_field($_POST['manager_type']);
        $province = sanitize_text_field($_POST['province']);
        $city = sanitize_text_field($_POST['city'] ?? '');
        $school = sanitize_text_field($_POST['school'] ?? '');

        if ($manager_type === 'school_manager') {
            $result = $this->userRoleService->assignSchoolManager($user_id, $province, $city, $school);
        } elseif ($manager_type === 'province_manager') {
            $result = $this->userRoleService->assignProvinceManager($user_id, $province);
        }

        if ($result) {
            echo '<div class="notice notice-success"><p>مدیر با موفقیت اختصاص یافت.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>خطا در اختصاص مدیر.</p></div>';
        }
    }

    /**
     * Get workflow statistics
     * @return array
     */
    private function getWorkflowStats()
    {
        global $wpdb;

        $stats = [
            'total_workflows' => 0,
            'pending_approvals' => 0,
            'completed_workflows' => 0,
            'active_managers' => 0
        ];

        // Get workflow log stats
        $log_table = $wpdb->prefix . 'donap_workflow_log';

        // Total workflows initiated
        $stats['total_workflows'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT entry_id) FROM $log_table WHERE action = 'step_created'"
        );

        // Pending approvals
        $stats['pending_approvals'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT entry_id) FROM $log_table WHERE action = 'step_created' 
             AND entry_id NOT IN (SELECT entry_id FROM $log_table WHERE action = 'workflow_completed')"
        );

        // Completed workflows
        $stats['completed_workflows'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT entry_id) FROM $log_table WHERE action = 'workflow_completed'"
        );

        // Active managers
        $stats['active_managers'] = count($this->userRoleService->getAllActiveManagers());

        return $stats;
    }

    /**
     * Handle AJAX approval action
     */
    public function handleAjaxApproval()
    {
        check_ajax_referer('workflow_action', 'nonce');

        if (!current_user_can('approve_school_submissions') && !current_user_can('approve_province_submissions')) {
            wp_die('Unauthorized', 403);
        }

        $entry_id = intval($_POST['entry_id']);
        $step_id = intval($_POST['step_id']);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $step_data = [
            'step_id' => $step_id,
            'step_name' => sanitize_text_field($_POST['step_name'] ?? ''),
            'step_order' => intval($_POST['step_order'] ?? 1)
        ];

        try {
            $this->workflowService->handleStepCompletion($entry_id, $step_data, 'approve', $notes);
            wp_send_json_success(['message' => 'تایید شد']);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'خطا: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle AJAX rejection action  
     */
    public function handleAjaxRejection()
    {
        check_ajax_referer('workflow_action', 'nonce');

        if (!current_user_can('approve_school_submissions') && !current_user_can('approve_province_submissions')) {
            wp_die('Unauthorized', 403);
        }

        $entry_id = intval($_POST['entry_id']);
        $step_id = intval($_POST['step_id']);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $step_data = [
            'step_id' => $step_id,
            'step_name' => sanitize_text_field($_POST['step_name'] ?? ''),
            'step_order' => intval($_POST['step_order'] ?? 1)
        ];

        try {
            $this->workflowService->handleStepCompletion($entry_id, $step_data, 'reject', $notes);
            wp_send_json_success(['message' => 'رد شد']);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'خطا: ' . $e->getMessage()]);
        }
    }
}
