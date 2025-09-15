<?php

namespace App\Controllers;

use Exception;

/**
 * Native Gravity Flow Actions Controller
 * Uses WordPress hooks and Gravity Flow's native processing
 */
class GravityFlowNativeController 
{
    /**
     * Handle entry actions using Gravity Flow's native approach
     */
    public function handleEntryAction()
    {
        try {
            // Verify request
            if (!isset($_POST['action'], $_POST['entry_id'])) {
                wp_send_json_error(['message' => 'پارامترهای ناقص'], 400);
                return;
            }

            $action = sanitize_text_field($_POST['action']);
            $entry_id = intval($_POST['entry_id']);
            $step_id = isset($_POST['step_id']) ? intval($_POST['step_id']) : 0;

            // Get entry
            if (!class_exists('GFAPI')) {
                wp_send_json_error(['message' => 'Gravity Forms در دسترس نیست'], 500);
                return;
            }

            $entry = \GFAPI::get_entry($entry_id);
            if (is_wp_error($entry)) {
                wp_send_json_error(['message' => 'ورودی یافت نشد'], 404);
                return;
            }

            // Process based on action type
            switch ($action) {
                case 'approve':
                    $result = $this->processApproval($entry, $step_id);
                    break;
                case 'reject':
                    $result = $this->processRejection($entry, $step_id);
                    break;
                case 'return':
                    $result = $this->processReturn($entry, $step_id);
                    break;
                default:
                    wp_send_json_error(['message' => 'عملیات نامعتبر'], 400);
                    return;
            }

            if ($result) {
                wp_send_json_success(['message' => 'عملیات با موفقیت انجام شد', 'entry_id' => $entry_id]);
            } else {
                wp_send_json_error(['message' => 'خطا در انجام عملیات'], 500);
            }

        } catch (Exception $e) {
            error_log('Native Gravity Flow Action Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'خطای داخلی سرور'], 500);
        }
    }

    /**
     * Process approval using native WordPress actions
     */
    private function processApproval($entry, $step_id)
    {
        $entry_id = $entry['id'];
        $form_id = $entry['form_id'];

        // Set current user context if needed
        $current_user = wp_get_current_user();
        if (!$current_user->ID) {
            wp_set_current_user(1); // Set to admin
        }

        // Trigger Gravity Flow approval action using native hooks
        do_action('gravityflow_step_complete', $entry_id, $form_id, $step_id, 'approved');
        
        // Alternative: Use direct form processing
        $_POST['gravityflow_submit'] = '1';
        $_POST['gravityflow_step_id'] = $step_id;
        $_POST['gravityflow_submit_approved'] = '1';
        $_POST['lid'] = $entry_id;
        
        // Process the form submission
        if (class_exists('Gravity_Flow') && class_exists('Gravity_Flow_Step_Approval')) {
            // Get the workflow step
            $step = gravity_flow()->get_step($step_id, $entry);
            if ($step && method_exists($step, 'process_step')) {
                return $step->process_step($entry, 'approved');
            }
        }

        return true;
    }

    /**
     * Process rejection using native WordPress actions  
     */
    private function processRejection($entry, $step_id)
    {
        $entry_id = $entry['id'];
        $form_id = $entry['form_id'];

        // Set current user context if needed
        $current_user = wp_get_current_user();
        if (!$current_user->ID) {
            wp_set_current_user(1); // Set to admin
        }

        // Trigger Gravity Flow rejection action using native hooks
        do_action('gravityflow_step_complete', $entry_id, $form_id, $step_id, 'rejected');
        
        // Alternative: Use direct form processing
        $_POST['gravityflow_submit'] = '1';
        $_POST['gravityflow_step_id'] = $step_id;
        $_POST['gravityflow_submit_rejected'] = '1';
        $_POST['lid'] = $entry_id;
        
        // Process the form submission
        if (class_exists('Gravity_Flow') && class_exists('Gravity_Flow_Step_Approval')) {
            // Get the workflow step
            $step = gravity_flow()->get_step($step_id, $entry);
            if ($step && method_exists($step, 'process_step')) {
                return $step->process_step($entry, 'rejected');
            }
        }

        return true;
    }

    /**
     * Process return to previous step
     */
    private function processReturn($entry, $step_id)
    {
        $entry_id = $entry['id'];
        $form_id = $entry['form_id'];

        // Set current user context if needed
        $current_user = wp_get_current_user();
        if (!$current_user->ID) {
            wp_set_current_user(1); // Set to admin
        }

        // Trigger return action
        do_action('gravityflow_step_return', $entry_id, $form_id, $step_id);
        
        return true;
    }

    /**
     * Get entry status and available actions
     */
    public function getEntryStatus()
    {
        try {
            $entry_id = intval($_GET['entry_id'] ?? 0);
            
            if (!$entry_id) {
                wp_send_json_error(['message' => 'شناسه ورودی ضروری است'], 400);
                return;
            }

            $entry = \GFAPI::get_entry($entry_id);
            if (is_wp_error($entry)) {
                wp_send_json_error(['message' => 'ورودی یافت نشد'], 404);
                return;
            }

            // Get current workflow status
            $status_info = $this->getWorkflowStatus($entry);
            
            wp_send_json_success($status_info);

        } catch (Exception $e) {
            error_log('Get Entry Status Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'خطای داخلی سرور'], 500);
        }
    }

    /**
     * Get comprehensive workflow status
     */
    private function getWorkflowStatus($entry)
    {
        $entry_id = $entry['id'];
        $form_id = $entry['form_id'];
        
        $status_info = [
            'entry_id' => $entry_id,
            'form_id' => $form_id,
            'current_status' => 'unknown',
            'current_step' => null,
            'available_actions' => [],
            'workflow_complete' => false
        ];

        // Get Gravity Flow status if available
        if (class_exists('Gravity_Flow_API')) {
            $api = new \Gravity_Flow_API($form_id);
            $status_info['current_status'] = $api->get_status($entry);
            
            $current_step = $api->get_current_step($entry);
            if ($current_step) {
                $status_info['current_step'] = [
                    'id' => $current_step->get_id(),
                    'name' => $current_step->get_name(),
                    'type' => $current_step->get_type()
                ];

                // Determine available actions based on step type and status
                if ($current_step->get_type() === 'approval') {
                    $status_info['available_actions'] = ['approve', 'reject'];
                } elseif ($current_step->get_type() === 'user_input') {
                    $status_info['available_actions'] = ['complete'];
                } elseif ($current_step->get_type() === 'notification') {
                    $status_info['available_actions'] = ['complete'];
                }
            } else {
                $status_info['workflow_complete'] = true;
            }
        }

        return $status_info;
    }
}
