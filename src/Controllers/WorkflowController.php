<?php

namespace App\Controllers;

use App\Services\WorkflowService;
use App\Services\UserRoleService;
use Exception;
use Kernel\Container;

class WorkflowController
{
    private WorkflowService $workflowService;
    private UserRoleService $userRoleService;

    public function __construct()
    {
        $this->workflowService = Container::resolve('WorkflowService');
        $this->userRoleService = Container::resolve('UserRoleService');
    }

    /**
     * Get workflow dashboard data via API
     */
    public function getDashboardData()
    {
        try {
            if (!\current_user_can('manage_options')) {
                \wp_send_json_error(['message' => 'دسترسی مجاز نیست'], 403);
                return;
            }

            $stats = $this->getWorkflowStats();
            $recent_activities = $this->getRecentWorkflowActivities();
            $pending_tasks = $this->getPendingTasks();

            \wp_send_json_success([
                'stats' => $stats,
                'recent_activities' => $recent_activities,
                'pending_tasks' => $pending_tasks
            ]);
        } catch (Exception $e) {
            error_log('Workflow Dashboard API Error: ' . $e->getMessage());
            \wp_send_json_error(['message' => 'خطای داخلی سرور'], 500);
        }
    }

    /**
     * Handle manager task approval/rejection via AJAX
     */
    public function handleTaskAction()
    {
        try {
            // Verify nonce
            if (!\check_ajax_referer('workflow_task_action', 'nonce', false)) {
                \wp_send_json_error(['message' => 'خطای امنیتی'], 403);
                return;
            }

            // Check permissions
            if (
                !\current_user_can('approve_school_submissions') &&
                !\current_user_can('approve_province_submissions')
            ) {
                \wp_send_json_error(['message' => 'دسترسی مجاز نیست'], 403);
                return;
            }

            $entry_id = intval($_POST['entry_id'] ?? 0);
            $step_id = intval($_POST['step_id'] ?? 0);
            $action = \sanitize_text_field($_POST['task_action'] ?? '');
            $notes = \sanitize_textarea_field($_POST['notes'] ?? '');

            if (!$entry_id || !$step_id || !in_array($action, ['approve', 'reject'])) {
                \wp_send_json_error(['message' => 'پارامترهای نامعتبر'], 400);
                return;
            }

            // Verify user can handle this specific task
            if (!$this->canUserHandleTask(\get_current_user_id(), $entry_id, $step_id)) {
                \wp_send_json_error(['message' => 'شما مجاز به انجام این عملیات نیستید'], 403);
                return;
            }

            $step_data = [
                'step_id' => $step_id,
                'step_name' => \sanitize_text_field($_POST['step_name'] ?? ''),
                'step_order' => intval($_POST['step_order'] ?? 1)
            ];

            // Handle the action
            $result = $this->workflowService->handleStepCompletion($entry_id, $step_data, $action, $notes);

            if ($result) {
                $message = $action === 'approve' ? 'درخواست تأیید شد' : 'درخواست رد شد';
                \wp_send_json_success([
                    'message' => $message,
                    'entry_id' => $entry_id,
                    'action' => $action
                ]);
            } else {
                \wp_send_json_error(['message' => 'خطا در انجام عملیات'], 500);
            }
        } catch (Exception $e) {
            error_log('Workflow Task Action Error: ' . $e->getMessage());
            \wp_send_json_error(['message' => 'خطای داخلی سرور'], 500);
        }
    }

    /**
     * Get my pending tasks for current user
     */
    public function getMyTasks()
    {
        try {
            $user_id = \get_current_user_id();
            if (!$user_id) {
                \wp_send_json_error(['message' => 'کاربر وارد نشده'], 401);
                return;
            }

            $user = \get_user_by('ID', $user_id);
            $tasks = [];

            // Get tasks for school managers
            if (\in_array('school_manager', $user->roles)) {
                $tasks = $this->getTasksForSchoolManager($user_id);
            }
            // Get tasks for province managers
            elseif (\in_array('province_manager', $user->roles)) {
                $tasks = $this->getTasksForProvinceManager($user_id);
            }
            // Admins can see all pending tasks
            elseif (\current_user_can('manage_options')) {
                $tasks = $this->getAllPendingTasks();
            }

            \wp_send_json_success([
                'tasks' => $tasks,
                'user_role' => $this->getUserWorkflowRole($user),
                'total_count' => count($tasks)
            ]);
        } catch (Exception $e) {
            error_log('Get My Tasks Error: ' . $e->getMessage());
            \wp_send_json_error(['message' => 'خطای داخلی سرور'], 500);
        }
    }

    /**
     * Get workflow history for specific entry
     */
    public function getEntryWorkflowHistory()
    {
        try {
            if (!\current_user_can('gravityforms_view_entries')) {
                \wp_send_json_error(['message' => 'دسترسی مجاز نیست'], 403);
                return;
            }

            $entry_id = intval($_GET['entry_id'] ?? 0);
            if (!$entry_id) {
                \wp_send_json_error(['message' => 'شناسه ورودی نامعتبر'], 400);
                return;
            }

            $history = $this->workflowService->getWorkflowHistory($entry_id);

            \wp_send_json_success([
                'history' => $history,
                'entry_id' => $entry_id
            ]);
        } catch (Exception $e) {
            error_log('Get Entry Workflow History Error: ' . $e->getMessage());
            \wp_send_json_error(['message' => 'خطای داخلی سرور'], 500);
        }
    }

    /**
     * Create test workflow for development
     */
    public function createTestWorkflow()
    {
        if (!\defined('WP_DEBUG') || !\WP_DEBUG) {
            \wp_send_json_error(['message' => 'فقط در حالت توسعه'], 403);
            return;
        }

        try {
            // Create sample entry and form data
            $sample_entry = [
                'id' => 999,
                'form_id' => 1,
                'created_by' => \get_current_user_id(),
                'date_created' => \current_time('mysql'),
                '1' => 'تهران', // Province field
                '2' => 'تهران', // City field  
                '3' => 'مدرسه شهید بهشتی', // School field
                '4' => 'درخواست نمونه برای تست گردش کاری'
            ];

            $sample_form = [
                'id' => 1,
                'title' => 'فرم تست گردش کاری',
                'fields' => [
                    (object) ['id' => 1, 'label' => 'استان', 'type' => 'select'],
                    (object) ['id' => 2, 'label' => 'شهر', 'type' => 'select'],
                    (object) ['id' => 3, 'label' => 'نام مدرسه', 'type' => 'text'],
                    (object) ['id' => 4, 'label' => 'شرح درخواست', 'type' => 'textarea']
                ]
            ];

            // Process the test entry
            $result = $this->workflowService->processNewEntry($sample_entry, $sample_form);

            if ($result) {
                \wp_send_json_success([
                    'message' => 'گردش کاری تست با موفقیت ایجاد شد',
                    'entry_id' => $sample_entry['id']
                ]);
            } else {
                \wp_send_json_error(['message' => 'خطا در ایجاد گردش کاری تست']);
            }
        } catch (Exception $e) {
            error_log('Create Test Workflow Error: ' . $e->getMessage());
            \wp_send_json_error(['message' => 'خطای داخلی سرور: ' . $e->getMessage()]);
        }
    }

    /**
     * Get workflow statistics
     */
    private function getWorkflowStats()
    {
        global $wpdb;

        $log_table = $wpdb->prefix . 'donap_workflow_log';
        $today = \current_time('Y-m-d');
        $this_month = \current_time('Y-m');

        $stats = [
            'total_workflows' => 0,
            'pending_approvals' => 0,
            'completed_workflows' => 0,
            'today_activities' => 0,
            'this_month_workflows' => 0,
            'active_managers' => 0
        ];

        // Check if log table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$log_table'") != $log_table) {
            return $stats;
        }

        // Total workflows initiated
        $stats['total_workflows'] = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT entry_id) FROM $log_table WHERE action = 'step_created'"
        );

        // Pending approvals
        $stats['pending_approvals'] = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT entry_id) FROM $log_table 
             WHERE action = 'step_created' 
             AND entry_id NOT IN (SELECT entry_id FROM $log_table WHERE action = 'workflow_completed')"
        );

        // Completed workflows
        $stats['completed_workflows'] = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT entry_id) FROM $log_table WHERE action = 'workflow_completed'"
        );

        // Today's activities
        $stats['today_activities'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $log_table WHERE DATE(timestamp) = %s",
            $today
        ));

        // This month's workflows
        $stats['this_month_workflows'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT entry_id) FROM $log_table 
             WHERE action = 'step_created' AND DATE_FORMAT(timestamp, '%%Y-%%m') = %s",
            $this_month
        ));

        // Active managers
        $stats['active_managers'] = count($this->userRoleService->getAllActiveManagers());

        return $stats;
    }

    /**
     * Get recent workflow activities
     */
    private function getRecentWorkflowActivities($limit = 10)
    {
        global $wpdb;

        $log_table = $wpdb->prefix . 'donap_workflow_log';

        $activities = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $log_table 
             ORDER BY timestamp DESC 
             LIMIT %d",
            $limit
        ), \ARRAY_A);

        foreach ($activities as &$activity) {
            $activity['details'] = \json_decode($activity['details'], true);
            $activity['user'] = \get_user_by('ID', $activity['user_id']);
            $activity['formatted_time'] = \human_time_diff(strtotime($activity['timestamp']));
        }

        return $activities;
    }

    /**
     * Get pending tasks for current user or all
     */
    private function getPendingTasks($user_id = null)
    {
        global $wpdb;

        $log_table = $wpdb->prefix . 'donap_workflow_log';

        // Get entries with pending steps
        $pending_entries = $wpdb->get_results(
            "SELECT DISTINCT entry_id FROM $log_table 
             WHERE action = 'step_created' 
             AND entry_id NOT IN (SELECT entry_id FROM $log_table WHERE action IN ('step_approve', 'step_reject'))",
            \ARRAY_A
        );

        $tasks = [];
        foreach ($pending_entries as $entry_data) {
            $entry_id = $entry_data['entry_id'];

            // Get entry details if Gravity Forms is available
            if (class_exists('GFAPI')) {
                $entry = \GFAPI::get_entry($entry_id);
                $form = \GFAPI::get_form($entry['form_id']);

                if ($entry && $form) {
                    $tasks[] = [
                        'entry_id' => $entry_id,
                        'form_title' => $form['title'],
                        'entry_date' => $entry['date_created'],
                        'submitter' => \get_user_by('ID', $entry['created_by']),
                        'status' => 'pending'
                    ];
                }
            }
        }

        return $tasks;
    }

    /**
     * Check if user can handle specific task
     */
    private function canUserHandleTask($user_id, $entry_id, $step_id)
    {
        // Admins can handle any task
        if (\current_user_can('manage_options')) {
            return true;
        }

        // Get entry location data
        if (!class_exists('GFAPI')) {
            return false;
        }

        $entry = \GFAPI::get_entry($entry_id);
        $form = \GFAPI::get_form($entry['form_id']);

        if (!$entry || !$form) {
            return false;
        }

        // Extract location from entry
        $location_data = [];
        foreach ($form['fields'] as $field) {
            $field_id = $field->id;
            $field_label = $field->label ?? '';
            $field_value = $entry[$field_id] ?? '';

            if (strpos($field_label, 'استان') !== false) {
                $location_data['province'] = $field_value;
            } elseif (strpos($field_label, 'شهر') !== false) {
                $location_data['city'] = $field_value;
            } elseif (strpos($field_label, 'مدرسه') !== false) {
                $location_data['school'] = $field_value;
            }
        }

        // Check if user can manage this location
        return $this->userRoleService->canManageLocation($user_id, $location_data);
    }

    /**
     * Get tasks for school manager
     */
    private function getTasksForSchoolManager($user_id)
    {
        $manager_location = $this->userRoleService->getManagerLocation($user_id);

        // Get pending entries for this school
        return $this->getTasksByLocation($manager_location);
    }

    /**
     * Get tasks for province manager  
     */
    private function getTasksForProvinceManager($user_id)
    {
        $manager_location = $this->userRoleService->getManagerLocation($user_id);

        // Get pending entries for this province (approved by school managers)
        return $this->getTasksByLocation($manager_location);
    }

    /**
     * Get all pending tasks (for admins)
     */
    private function getAllPendingTasks()
    {
        return $this->getPendingTasks();
    }

    /**
     * Get tasks by location
     */
    private function getTasksByLocation($location)
    {
        // Implementation would filter pending tasks by location
        // This is a simplified version
        return $this->getPendingTasks();
    }

    /**
     * Get user workflow role
     */
    private function getUserWorkflowRole($user)
    {
        if (\in_array('school_manager', $user->roles)) {
            return 'school_manager';
        } elseif (\in_array('province_manager', $user->roles)) {
            return 'province_manager';
        } elseif (\current_user_can('manage_options')) {
            return 'admin';
        } else {
            return 'user';
        }
    }
}
