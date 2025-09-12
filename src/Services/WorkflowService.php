<?php

namespace App\Services;

use Exception;

class WorkflowService
{
    protected $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Process new form entry and assign workflow tasks automatically
     * @param array $entry Gravity Forms entry data
     * @param array $form Gravity Forms form data
     * @return bool
     */
    public function processNewEntry($entry, $form)
    {
        try {
            // Extract location fields from entry
            $location_data = $this->extractLocationData($entry, $form);

            if (!$location_data['province'] || !$location_data['city'] || !$location_data['school']) {
                error_log('WorkflowService: Missing required location fields for entry ' . $entry['id']);
                return false;
            }

            // Create initial workflow tasks
            $this->createInitialWorkflowTasks($entry, $form, $location_data);

            return true;
        } catch (Exception $e) {
            error_log('WorkflowService Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract location data from form entry
     * @param array $entry
     * @param array $form
     * @return array
     */
    private function extractLocationData($entry, $form)
    {
        $location_data = [
            'province' => null,
            'city' => null,
            'school' => null
        ];

        if (!isset($form['fields']) || !is_array($form['fields'])) {
            return $location_data;
        }

        foreach ($form['fields'] as $field) {
            $field_id = $field->id;
            $field_label = $field->label ?? '';
            $field_value = $entry[$field_id] ?? '';

            // Check for province field (استان)
            if (
                strpos($field_label, 'استان') !== false ||
                strpos($field_label, 'province') !== false ||
                strpos($field->adminLabel ?? '', 'province') !== false
            ) {
                $location_data['province'] = $field_value;
            }

            // Check for city field (شهر)
            elseif (
                strpos($field_label, 'شهر') !== false ||
                strpos($field_label, 'city') !== false ||
                strpos($field->adminLabel ?? '', 'city') !== false
            ) {
                $location_data['city'] = $field_value;
            }

            // Check for school name field (نام مدرسه)
            elseif (
                strpos($field_label, 'مدرسه') !== false ||
                strpos($field_label, 'school') !== false ||
                strpos($field->adminLabel ?? '', 'school') !== false
            ) {
                $location_data['school'] = $field_value;
            }
        }

        return $location_data;
    }

    /**
     * Create initial workflow tasks for new entry
     * @param array $entry
     * @param array $form
     * @param array $location_data
     */
    private function createInitialWorkflowTasks($entry, $form, $location_data)
    {
        // Find school manager
        $school_manager = $this->findSchoolManager($location_data);

        if ($school_manager) {
            $this->createWorkflowStep($entry, $form, [
                'step_type' => 'approval',
                'step_name' => 'School Manager Approval',
                'assignee_type' => 'school_manager',
                'assignee_id' => $school_manager->ID,
                'step_order' => 1,
                'status' => 'pending',
                'location_data' => $location_data,
                'instructions' => 'Please review and approve this submission from your school.'
            ]);
        }

        // Find province manager for next step
        $province_manager = $this->findProvinceManager($location_data);

        if ($province_manager) {
            $this->createWorkflowStep($entry, $form, [
                'step_type' => 'approval',
                'step_name' => 'Province Manager Approval',
                'assignee_type' => 'province_manager',
                'assignee_id' => $province_manager->ID,
                'step_order' => 2,
                'status' => 'inactive', // Will be activated when school manager approves
                'location_data' => $location_data,
                'instructions' => 'Please review and provide final approval for this submission.'
            ]);
        }
    }

    /**
     * Find school manager based on location data
     * @param array $location_data
     * @return \WP_User|null
     */
    private function findSchoolManager($location_data)
    {
        // Search for users with school manager role and matching location
        $args = [
            'role' => 'school_manager',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'manager_province',
                    'value' => $location_data['province'],
                    'compare' => '='
                ],
                [
                    'key' => 'manager_city',
                    'value' => $location_data['city'],
                    'compare' => '='
                ],
                [
                    'key' => 'manager_school',
                    'value' => $location_data['school'],
                    'compare' => '='
                ]
            ]
        ];

        $users = \get_users($args);
        return !empty($users) ? $users[0] : null;
    }

    /**
     * Find province manager based on location data
     * @param array $location_data
     * @return \WP_User|null
     */
    private function findProvinceManager($location_data)
    {
        // Search for users with province manager role and matching province
        $args = [
            'role' => 'province_manager',
            'meta_query' => [
                [
                    'key' => 'manager_province',
                    'value' => $location_data['province'],
                    'compare' => '='
                ]
            ]
        ];

        $users = \get_users($args);
        return !empty($users) ? $users[0] : null;
    }

    /**
     * Create workflow step in Gravity Flow
     * @param array $entry
     * @param array $form
     * @param array $step_config
     */
    private function createWorkflowStep($entry, $form, $step_config)
    {
        // Check if Gravity Flow is available
        if (!class_exists('Gravity_Flow')) {
            error_log('Gravity Flow is not available for creating workflow steps');
            return false;
        }

        try {
            // Get Gravity Flow API
            $flow_api = \gravity_flow();

            // Create step configuration
            $step_data = [
                'form_id' => $form['id'],
                'step_name' => $step_config['step_name'],
                'step_type' => $step_config['step_type'],
                'assignee_type' => $step_config['assignee_type'],
                'assignees' => [$step_config['assignee_id']],
                'step_order' => $step_config['step_order'],
                'status' => $step_config['status'],
                'instructions' => $step_config['instructions'],
                'auto_created' => true,
                'location_context' => $step_config['location_data']
            ];

            // Add to workflow
            $step_id = $flow_api->add_step($step_data);

            if ($step_id) {
                // Log successful step creation
                $this->logWorkflowActivity($entry['id'], 'step_created', [
                    'step_id' => $step_id,
                    'step_name' => $step_config['step_name'],
                    'assignee_id' => $step_config['assignee_id'],
                    'assignee_type' => $step_config['assignee_type']
                ]);

                return $step_id;
            }
        } catch (Exception $e) {
            error_log('Failed to create workflow step: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Handle workflow step completion
     * @param int $entry_id
     * @param array $step_data
     * @param string $action ('approve' or 'reject')
     * @param string $notes
     */
    public function handleStepCompletion($entry_id, $step_data, $action, $notes = '')
    {
        try {
            if ($action === 'approve') {
                $this->handleStepApproval($entry_id, $step_data, $notes);
            } elseif ($action === 'reject') {
                $this->handleStepRejection($entry_id, $step_data, $notes);
            }

            // Log the action
            $this->logWorkflowActivity($entry_id, 'step_' . $action, [
                'step_id' => $step_data['step_id'],
                'step_name' => $step_data['step_name'],
                'assignee_id' => \get_current_user_id(),
                'notes' => $notes
            ]);
        } catch (Exception $e) {
            error_log('WorkflowService handleStepCompletion error: ' . $e->getMessage());
        }
    }

    /**
     * Handle step approval - activate next step or complete workflow
     * @param int $entry_id
     * @param array $step_data
     * @param string $notes
     */
    private function handleStepApproval($entry_id, $step_data, $notes)
    {
        // Mark current step as approved
        $this->updateStepStatus($step_data['step_id'], 'approved', $notes);

        // Find next step in workflow
        $next_step = $this->findNextWorkflowStep($entry_id, $step_data['step_order']);

        if ($next_step) {
            // Activate next step
            $this->updateStepStatus($next_step['step_id'], 'pending');

            // Send notification to next assignee
            $this->sendWorkflowNotification($entry_id, $next_step, 'assignment');
        } else {
            // No next step - complete the workflow
            $this->completeWorkflow($entry_id);
        }
    }

    /**
     * Handle step rejection - send back to submitter for editing
     * @param int $entry_id
     * @param array $step_data
     * @param string $notes
     */
    private function handleStepRejection($entry_id, $step_data, $notes)
    {
        // Mark current step as rejected
        $this->updateStepStatus($step_data['step_id'], 'rejected', $notes);

        // Create edit task for original submitter
        $entry = \GFAPI::get_entry($entry_id);
        $form = \GFAPI::get_form($entry['form_id']);

        if ($entry && $form) {
            $submitter_id = $entry['created_by'];

            $this->createWorkflowStep($entry, $form, [
                'step_type' => 'user_input',
                'step_name' => 'Edit Submission',
                'assignee_type' => 'submitter',
                'assignee_id' => $submitter_id,
                'step_order' => $step_data['step_order'] - 0.5, // Insert before current step
                'status' => 'pending',
                'location_data' => $step_data['location_context'] ?? [],
                'instructions' => 'Please edit your submission based on the feedback: ' . $notes
            ]);

            // Send notification to submitter
            $this->sendWorkflowNotification($entry_id, [
                'assignee_id' => $submitter_id,
                'step_name' => 'Edit Required'
            ], 'edit_request', $notes);
        }
    }

    /**
     * Update workflow step status
     * @param int $step_id
     * @param string $status
     * @param string $notes
     */
    private function updateStepStatus($step_id, $status, $notes = '')
    {
        if (!class_exists('Gravity_Flow')) {
            return false;
        }

        try {
            $flow_api = \gravity_flow();
            return $flow_api->update_step_status($step_id, $status, $notes);
        } catch (Exception $e) {
            error_log('Failed to update step status: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find next workflow step
     * @param int $entry_id
     * @param int $current_step_order
     * @return array|null
     */
    private function findNextWorkflowStep($entry_id, $current_step_order)
    {
        // Query workflow steps for this entry ordered by step_order
        $steps = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}gravityflow_steps 
             WHERE entry_id = %d AND step_order > %d AND status = 'inactive'
             ORDER BY step_order ASC LIMIT 1",
            $entry_id,
            $current_step_order
        ), \ARRAY_A);

        return !empty($steps) ? $steps[0] : null;
    }

    /**
     * Complete workflow
     * @param int $entry_id
     */
    private function completeWorkflow($entry_id)
    {
        try {
            // Update entry status to approved
            \GFAPI::update_entry_property($entry_id, 'status', 'approved');

            // Update workflow final status
            \gform_update_meta($entry_id, 'workflow_final_status', 'approved');
            \gform_update_meta($entry_id, 'workflow_completion_date', \current_time('mysql'));

            // Log workflow completion
            $this->logWorkflowActivity($entry_id, 'workflow_completed', [
                'completion_date' => \current_time('mysql')
            ]);

            // Send completion notification to submitter
            $entry = \GFAPI::get_entry($entry_id);
            if ($entry) {
                $this->sendWorkflowNotification($entry_id, [
                    'assignee_id' => $entry['created_by'],
                    'step_name' => 'Approval Complete'
                ], 'completion');
            }
        } catch (Exception $e) {
            error_log('Failed to complete workflow: ' . $e->getMessage());
        }
    }

    /**
     * Send workflow notifications
     * @param int $entry_id
     * @param array $step_data
     * @param string $type ('assignment', 'edit_request', 'completion')
     * @param string $additional_message
     */
    private function sendWorkflowNotification($entry_id, $step_data, $type, $additional_message = '')
    {
        $user = \get_user_by('ID', $step_data['assignee_id']);
        if (!$user) {
            return false;
        }

        $entry = \GFAPI::get_entry($entry_id);
        $form = \GFAPI::get_form($entry['form_id']);

        if (!$entry || !$form) {
            return false;
        }

        // Prepare notification content
        $subject = '';
        $message = '';

        switch ($type) {
            case 'assignment':
                $subject = 'تکلیف گردش کاری جدید - ' . $form['title'];
                $message = sprintf(
                    'سلام %s،\n\nیک تکلیف گردش کاری جدید برای شما اختصاص یافته است.\n\nمرحله: %s\nفرم: %s\nشماره ورودی: %s\n\nلطفاً به بخش مدیریت مراجعه کنید.\n\n%s',
                    $user->display_name,
                    $step_data['step_name'],
                    $form['title'],
                    $entry_id,
                    $additional_message
                );
                break;

            case 'edit_request':
                $subject = 'درخواست ویرایش فرم - ' . $form['title'];
                $message = sprintf(
                    'سلام %s،\n\nفرم ارسالی شما نیاز به ویرایش دارد.\n\nفرم: %s\nشماره ورودی: %s\n\nبازخورد: %s\n\nلطفاً فرم را ویرایش کنید.',
                    $user->display_name,
                    $form['title'],
                    $entry_id,
                    $additional_message
                );
                break;

            case 'completion':
                $subject = 'تأیید نهایی فرم - ' . $form['title'];
                $message = sprintf(
                    'سلام %s،\n\nفرم شما با موفقیت تأیید شد.\n\nفرم: %s\nشماره ورودی: %s\n\nمتشکریم.',
                    $user->display_name,
                    $form['title'],
                    $entry_id
                );
                break;
        }

        // Send email notification
        if ($subject && $message) {
            return \wp_mail($user->user_email, $subject, $message);
        }

        return false;
    }

    /**
     * Log workflow activity
     * @param int $entry_id
     * @param string $action
     * @param array $details
     */
    private function logWorkflowActivity($entry_id, $action, $details = [])
    {
        $log_data = [
            'entry_id' => $entry_id,
            'action' => $action,
            'user_id' => \get_current_user_id(),
            'timestamp' => \current_time('mysql'),
            'details' => \wp_json_encode($details)
        ];

        // Store in custom log table (create if needed)
        $this->createWorkflowLogTable();

        $this->wpdb->insert(
            $this->wpdb->prefix . 'donap_workflow_log',
            $log_data,
            ['%d', '%s', '%d', '%s', '%s']
        );
    }

    /**
     * Create workflow log table if it doesn't exist
     */
    private function createWorkflowLogTable()
    {
        $table_name = $this->wpdb->prefix . 'donap_workflow_log';

        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            entry_id int(11) NOT NULL,
            action varchar(50) NOT NULL,
            user_id int(11) NOT NULL,
            timestamp datetime NOT NULL,
            details longtext,
            PRIMARY KEY (id),
            KEY entry_id (entry_id),
            KEY user_id (user_id),
            KEY action (action)
        ) $charset_collate;";

        require_once(\ABSPATH . 'wp-admin/includes/upgrade.php');
        \dbDelta($sql);
    }

    /**
     * Get workflow history for an entry
     * @param int $entry_id
     * @return array
     */
    public function getWorkflowHistory($entry_id)
    {
        $table_name = $this->wpdb->prefix . 'donap_workflow_log';

        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM $table_name WHERE entry_id = %d ORDER BY timestamp ASC",
            $entry_id
        ), \ARRAY_A);

        foreach ($results as &$result) {
            $result['details'] = \json_decode($result['details'], true);
            $result['user'] = \get_user_by('ID', $result['user_id']);
        }

        return $results;
    }
}
