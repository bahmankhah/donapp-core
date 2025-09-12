<?php

namespace App\Services;

use Exception;

class UserRoleService
{
    /**
     * Initialize workflow-related user roles
     */
    public function initializeWorkflowRoles()
    {
        try {
            // Create school manager role
            $this->createSchoolManagerRole();

            // Create province manager role  
            $this->createProvinceManagerRole();

            return true;
        } catch (Exception $e) {
            error_log('UserRoleService Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create school manager role
     */
    private function createSchoolManagerRole()
    {
        // Remove existing role to recreate with updated capabilities
        \remove_role('school_manager');

        // Add school manager role with appropriate capabilities
        $result = \add_role('school_manager', 'مدیر مدرسه', [
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'publish_posts' => false,
            'upload_files' => false,

            // Gravity Forms/Flow capabilities
            'gravityforms_view_entries' => true,
            'gravityforms_edit_entries' => true,
            'gravityflow_workflow_detail_admin_actions' => true,
            'gravityflow_submit' => true,
            'gravityflow_status_view_all' => false, // Can only see own school's entries

            // Custom workflow capabilities
            'approve_school_submissions' => true,
            'view_school_workflow' => true,
            'manage_school_workflow' => true,
        ]);

        if ($result) {
            error_log('School Manager role created successfully');
        }
    }

    /**
     * Create province manager role  
     */
    private function createProvinceManagerRole()
    {
        // Remove existing role to recreate with updated capabilities
        \remove_role('province_manager');

        // Add province manager role with appropriate capabilities
        $result = \add_role('province_manager', 'مدیر استان', [
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'publish_posts' => false,
            'upload_files' => false,

            // Gravity Forms/Flow capabilities  
            'gravityforms_view_entries' => true,
            'gravityforms_edit_entries' => true,
            'gravityflow_workflow_detail_admin_actions' => true,
            'gravityflow_submit' => true,
            'gravityflow_status_view_all' => false, // Can only see own province's entries

            // Custom workflow capabilities
            'approve_province_submissions' => true,
            'view_province_workflow' => true,
            'manage_province_workflow' => true,
            'view_school_approvals' => true, // Can see what school managers approved
        ]);

        if ($result) {
            error_log('Province Manager role created successfully');
        }
    }

    /**
     * Assign school manager to location
     * @param int $user_id
     * @param string $province 
     * @param string $city
     * @param string $school
     * @return bool
     */
    public function assignSchoolManager($user_id, $province, $city, $school)
    {
        try {
            $user = \get_user_by('ID', $user_id);
            if (!$user) {
                return false;
            }

            // Set user role to school manager
            $user->set_role('school_manager');

            // Set location metadata
            \update_user_meta($user_id, 'manager_province', \sanitize_text_field($province));
            \update_user_meta($user_id, 'manager_city', \sanitize_text_field($city));
            \update_user_meta($user_id, 'manager_school', \sanitize_text_field($school));
            \update_user_meta($user_id, 'manager_type', 'school');

            // Set additional manager metadata
            \update_user_meta($user_id, 'workflow_assignment_date', \current_time('mysql'));
            \update_user_meta($user_id, 'manager_status', 'active');

            return true;
        } catch (Exception $e) {
            error_log('Failed to assign school manager: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Assign province manager to location
     * @param int $user_id
     * @param string $province
     * @return bool
     */
    public function assignProvinceManager($user_id, $province)
    {
        try {
            $user = \get_user_by('ID', $user_id);
            if (!$user) {
                return false;
            }

            // Set user role to province manager
            $user->set_role('province_manager');

            // Set location metadata
            \update_user_meta($user_id, 'manager_province', \sanitize_text_field($province));
            \update_user_meta($user_id, 'manager_type', 'province');

            // Set additional manager metadata
            \update_user_meta($user_id, 'workflow_assignment_date', \current_time('mysql'));
            \update_user_meta($user_id, 'manager_status', 'active');

            return true;
        } catch (Exception $e) {
            error_log('Failed to assign province manager: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get school managers by location
     * @param string $province
     * @param string $city
     * @param string $school  
     * @return array
     */
    public function getSchoolManagers($province = '', $city = '', $school = '')
    {
        $args = [
            'role' => 'school_manager',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'manager_status',
                    'value' => 'active',
                    'compare' => '='
                ]
            ]
        ];

        // Add location filters if provided
        if (!empty($province)) {
            $args['meta_query'][] = [
                'key' => 'manager_province',
                'value' => $province,
                'compare' => '='
            ];
        }

        if (!empty($city)) {
            $args['meta_query'][] = [
                'key' => 'manager_city',
                'value' => $city,
                'compare' => '='
            ];
        }

        if (!empty($school)) {
            $args['meta_query'][] = [
                'key' => 'manager_school',
                'value' => $school,
                'compare' => '='
            ];
        }

        return \get_users($args);
    }

    /**
     * Get province managers by location
     * @param string $province
     * @return array
     */
    public function getProvinceManagers($province = '')
    {
        $args = [
            'role' => 'province_manager',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'manager_status',
                    'value' => 'active',
                    'compare' => '='
                ]
            ]
        ];

        // Add province filter if provided
        if (!empty($province)) {
            $args['meta_query'][] = [
                'key' => 'manager_province',
                'value' => $province,
                'compare' => '='
            ];
        }

        return \get_users($args);
    }

    /**
     * Get manager's assigned location
     * @param int $user_id
     * @return array
     */
    public function getManagerLocation($user_id)
    {
        $location = [
            'province' => \get_user_meta($user_id, 'manager_province', true),
            'city' => \get_user_meta($user_id, 'manager_city', true),
            'school' => \get_user_meta($user_id, 'manager_school', true),
            'type' => \get_user_meta($user_id, 'manager_type', true),
            'status' => \get_user_meta($user_id, 'manager_status', true)
        ];

        return $location;
    }

    /**
     * Check if user can manage specific location
     * @param int $user_id
     * @param array $location_data
     * @return bool
     */
    public function canManageLocation($user_id, $location_data)
    {
        $user = \get_user_by('ID', $user_id);
        if (!$user) {
            return false;
        }

        $user_location = $this->getManagerLocation($user_id);

        // Admin can manage everything
        if (\user_can($user_id, 'manage_options')) {
            return true;
        }

        // Check school manager permissions
        if (\in_array('school_manager', $user->roles)) {
            return $user_location['province'] === $location_data['province'] &&
                $user_location['city'] === $location_data['city'] &&
                $user_location['school'] === $location_data['school'];
        }

        // Check province manager permissions  
        if (\in_array('province_manager', $user->roles)) {
            return $user_location['province'] === $location_data['province'];
        }

        return false;
    }

    /**
     * Deactivate manager
     * @param int $user_id
     * @return bool
     */
    public function deactivateManager($user_id)
    {
        try {
            \update_user_meta($user_id, 'manager_status', 'inactive');
            \update_user_meta($user_id, 'deactivation_date', \current_time('mysql'));
            return true;
        } catch (Exception $e) {
            error_log('Failed to deactivate manager: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all active managers with their location data
     * @return array
     */
    public function getAllActiveManagers()
    {
        $managers = [];

        // Get school managers
        $school_managers = $this->getSchoolManagers();
        foreach ($school_managers as $manager) {
            $location = $this->getManagerLocation($manager->ID);
            $managers[] = [
                'user' => $manager,
                'type' => 'school_manager',
                'location' => $location
            ];
        }

        // Get province managers
        $province_managers = $this->getProvinceManagers();
        foreach ($province_managers as $manager) {
            $location = $this->getManagerLocation($manager->ID);
            $managers[] = [
                'user' => $manager,
                'type' => 'province_manager',
                'location' => $location
            ];
        }

        return $managers;
    }

    /**
     * Create sample managers for testing (development only)
     */
    public function createSampleManagers()
    {
        if (!\defined('WP_DEBUG') || !\WP_DEBUG) {
            return false; // Only in development mode
        }

        try {
            // Create sample school manager
            $school_manager_data = [
                'user_login' => 'school_manager_tehran',
                'user_email' => 'school.manager@example.com',
                'user_pass' => 'temp_password_123',
                'display_name' => 'مدیر مدرسه شهید بهشتی',
                'first_name' => 'محمد',
                'last_name' => 'احمدی'
            ];

            $school_manager_id = \wp_insert_user($school_manager_data);
            if (!\is_wp_error($school_manager_id)) {
                $this->assignSchoolManager($school_manager_id, 'تهران', 'تهران', 'مدرسه شهید بهشتی');
            }

            // Create sample province manager
            $province_manager_data = [
                'user_login' => 'province_manager_tehran',
                'user_email' => 'province.manager@example.com',
                'user_pass' => 'temp_password_123',
                'display_name' => 'مدیر آموزش و پرورش تهران',
                'first_name' => 'علی',
                'last_name' => 'کریمی'
            ];

            $province_manager_id = \wp_insert_user($province_manager_data);
            if (!\is_wp_error($province_manager_id)) {
                $this->assignProvinceManager($province_manager_id, 'تهران');
            }

            return true;
        } catch (Exception $e) {
            error_log('Failed to create sample managers: ' . $e->getMessage());
            return false;
        }
    }
}
