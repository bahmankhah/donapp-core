# Automated Workflow System for Gravity Forms

This implementation adds a comprehensive automated workflow system for Gravity Forms submissions with school and province manager approval hierarchy in the Donapp Core plugin.

## Overview

The automated workflow system enables automatic task assignment and approval workflows based on geographic location data (province, city, school) extracted from Gravity Forms submissions. It creates a hierarchical approval system where submissions are routed to appropriate managers based on their location assignments.

## Features

- **Automatic Task Assignment**: Automatically creates workflow tasks based on form location fields (استان/شهر/نام مدرسه)
- **Manager Role System**: Custom WordPress roles for school managers and province managers
- **Approval Hierarchy**: School manager approval → Province manager approval → Complete workflow
- **Rejection Workflow**: Rejected submissions return to submitter for editing and resubmission
- **Admin Dashboard**: Comprehensive management interface with statistics and task monitoring
- **Real-time Updates**: AJAX-powered interface with live notifications and status updates
- **Complete Audit Trail**: Full logging of all workflow actions and state changes

## API Endpoints

### Workflow Management APIs

```
GET /wp-json/dnp/v1/workflow/dashboard
GET /wp-json/dnp/v1/workflow/my-tasks
POST /wp-json/dnp/v1/workflow/task-action
GET /wp-json/dnp/v1/workflow/history?entry_id={entry_id}
POST /wp-json/dnp/v1/workflow/test
```

## Admin Interface

### Workflow Automation Dashboard

Navigate to **Dashboard → Workflow Automation** to access the main management interface:

- **Statistics Overview**: Total workflows, pending approvals, completed workflows, today's activities
- **Recent Activity Feed**: Real-time view of all workflow actions
- **Pending Tasks Summary**: Quick overview of tasks awaiting action

### Manager Assignment Interface

Navigate to **Dashboard → Workflow Managers** to manage user roles and assignments:

- **Manager Assignment Form**: Assign users as school or province managers with location mapping
- **Active Managers List**: View and manage all current manager assignments
- **Role Management**: Create and modify manager roles with appropriate capabilities

### Personal Task Management

Managers can access their assigned tasks through the workflow interface:

- **My Tasks**: View all pending tasks assigned to the current manager
- **Approval Actions**: Approve or reject submissions with optional notes
- **Task History**: View complete workflow history for any submission

## Database Schema

### Workflow Log Table

The system creates a `wp_donap_workflow_log` table to track all workflow activities:

```sql
CREATE TABLE wp_donap_workflow_log (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  entry_id bigint(20) NOT NULL,
  form_id bigint(20) NOT NULL,
  user_id bigint(20) DEFAULT NULL,
  action varchar(50) NOT NULL,
  step_data text,
  details longtext,
  timestamp datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_entry_id (entry_id),
  KEY idx_form_id (form_id),
  KEY idx_user_id (user_id),
  KEY idx_action (action),
  KEY idx_timestamp (timestamp)
)
```

### Manager Location Assignments

Manager location data is stored in WordPress user meta:

- `school_manager_province`: Province assignment for school managers
- `school_manager_city`: City assignment for school managers  
- `school_manager_school`: School assignment for school managers
- `province_manager_province`: Province assignment for province managers

## Workflow Process

### 1. Form Submission
When a Gravity Form containing location fields is submitted:
- System automatically detects forms with location data (استان/شهر/نام مدرسه)
- Triggers workflow creation process via Gravity Forms hooks

### 2. Location Analysis
The system analyzes form fields to extract location information:
- **Province Field**: Fields containing "استان" in the label
- **City Field**: Fields containing "شهر" in the label
- **School Field**: Fields containing "مدرسه" in the label

### 3. Manager Identification
Based on extracted location data:
- Identifies the appropriate school manager for the specific school/city/province combination
- Falls back to province manager if no specific school manager is found
- Logs error if no appropriate manager is available

### 4. Task Creation
Creates workflow tasks in the system:
- Generates initial task for school manager approval
- Logs all task creation activities
- Sends notifications to assigned managers (if email notifications are enabled)

### 5. School Manager Review
School managers can:
- **Approve**: Moves submission to province manager for final approval
- **Reject**: Returns submission to original submitter with rejection notes

### 6. Province Manager Review (if approved by school manager)
Province managers can:
- **Approve**: Completes the workflow and marks submission as fully approved
- **Reject**: Returns submission to original submitter with rejection notes

### 7. Rejection Handling
When a submission is rejected at any level:
- Entry status is updated to allow submitter editing
- Rejection notes are logged in workflow history
- Upon resubmission, workflow restarts from school manager approval

## File Structure

### Core Services
- `src/Services/WorkflowService.php`: Main workflow automation logic
- `src/Services/UserRoleService.php`: Manager role and location assignment management

### Integration Layer
- `src/Providers/WorkflowServiceProvider.php`: WordPress hooks and admin interface integration
- `src/Controllers/WorkflowController.php`: API endpoints and request handling

### Admin Interface Views
- `views/admin/workflow-automation.view.php`: Main dashboard interface
- `views/admin/workflow-managers.view.php`: Manager assignment interface

### Frontend Assets
- `src/assets/admin/js/workflow-management.js`: JavaScript functionality for admin interface
- `src/assets/admin/css/workflow-management.css`: Persian RTL styles for workflow interface

## User Roles and Capabilities

### School Manager Role
- **Role Name**: `school_manager`
- **Capabilities**:
  - `read`
  - `approve_school_submissions`
  - `view_workflow_tasks`
  - `manage_school_workflows`

### Province Manager Role
- **Role Name**: `province_manager`
- **Capabilities**:
  - `read`
  - `approve_province_submissions`
  - `approve_school_submissions` (can handle school-level tasks if needed)
  - `view_workflow_tasks`
  - `manage_province_workflows`

## Configuration

### Automatic Role Creation
The system automatically creates manager roles during plugin activation or when the WorkflowServiceProvider is first loaded.

### Manager Assignment
Administrators can assign managers through the admin interface:

1. Navigate to **Dashboard → Workflow Managers**
2. Fill out the manager assignment form:
   - Select user to assign as manager
   - Choose manager type (school or province)
   - Select province assignment
   - For school managers: select city and specific school
3. Submit to create the assignment

### Form Integration
The system automatically detects and processes any Gravity Form containing location fields. No additional configuration is required for basic operation.

## Testing

### Test Workflow Creation
For development and testing purposes, the system includes a test workflow generator:

1. Navigate to the Workflow Automation dashboard
2. Click "Create Test Workflow" (available in WP_DEBUG mode)
3. System creates a sample workflow with test data
4. Use this to verify the approval process works correctly

### Manual Testing Checklist

1. **Manager Assignment**:
   - [ ] Create school manager with location assignment
   - [ ] Create province manager with province assignment
   - [ ] Verify managers can access their assigned areas only

2. **Form Submission**:
   - [ ] Submit form with location data (استان/شهر/نام مدرسه)
   - [ ] Verify task is created for appropriate school manager
   - [ ] Check workflow log entries are created

3. **Approval Process**:
   - [ ] School manager approves submission
   - [ ] Task automatically moves to province manager
   - [ ] Province manager approves submission
   - [ ] Workflow is marked as complete

4. **Rejection Process**:
   - [ ] School manager rejects submission with notes
   - [ ] Submission returns to submitter
   - [ ] Upon resubmission, workflow restarts

## Troubleshooting

### Common Issues

**No tasks appear for managers:**
- Verify manager location assignments match form location data exactly
- Check that user has the correct manager role assigned
- Ensure form fields contain the expected location keywords (استان/شهر/مدرسه)

**Workflow not triggered automatically:**
- Verify Gravity Forms hooks are properly registered
- Check WordPress admin error logs for any hook failures
- Confirm form contains the required location fields

**Permission denied errors:**
- Verify user has appropriate workflow capabilities
- Check that manager roles were created correctly
- Ensure user is assigned to correct location

### Debug Mode
Enable WordPress debug mode to see detailed workflow logging:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Workflow activities are logged to the WordPress debug log with the prefix "Workflow:".

## Extension Points

### Custom Location Field Detection
To customize how location fields are detected, modify the `extractLocationData()` method in `WorkflowService.php`:

```php
private function extractLocationData($entry, $form)
{
    // Custom field detection logic
    // Return array with 'province', 'city', 'school' keys
}
```

### Custom Approval Logic
To add custom approval rules, extend the `shouldCreateWorkflow()` method:

```php
private function shouldCreateWorkflow($entry, $form, $location_data)
{
    // Custom workflow creation logic
    // Return boolean
}
```

### Additional Manager Types
To add new manager types, extend the `UserRoleService` with additional role creation methods and update the capability system accordingly.

## Integration with Other Systems

### Email Notifications
The system is designed to integrate with WordPress email systems. Notification triggers are built into the workflow service but email sending is handled by WordPress hooks.

### External APIs
The workflow system can be extended to integrate with external approval systems by adding custom actions to the workflow completion process.

### Reporting Systems
All workflow data is stored in the database and can be accessed for reporting purposes through standard WordPress database queries or custom reporting interfaces.

## Security Considerations

### Permission Checking
- All API endpoints implement proper capability checking
- Managers can only access tasks for their assigned locations
- AJAX requests are protected with WordPress nonces

### Data Validation
- All user inputs are sanitized using WordPress functions
- Location data is validated before workflow creation
- SQL injection protection through WordPress WPDB prepared statements

### Access Control
- Manager assignments are restricted to administrators only
- Workflow data access is limited by user capabilities
- Sensitive workflow information is logged securely

## Performance Considerations

### Database Optimization
- Workflow log table includes proper indexes for query performance
- Location queries are optimized to reduce database load
- Automatic cleanup of old workflow logs can be implemented as needed

### Caching
- Manager location assignments are cached in user meta for fast access
- Workflow statistics can be cached for dashboard performance
- Consider implementing object caching for high-traffic sites

## Future Enhancements

### Planned Features
- Email notification system for task assignments
- Bulk approval/rejection capabilities
- Advanced workflow routing based on submission content
- Integration with external calendar systems for deadline management
- Mobile-responsive interface improvements

### API Extensibility
The system is designed to be easily extensible for future requirements:
- Additional manager role types
- Custom workflow steps
- Integration with third-party approval systems
- Advanced reporting and analytics capabilities

## Support and Maintenance

### Version History
- **v1.0**: Initial implementation with basic school/province approval hierarchy
- **v1.1**: Added admin interface and real-time updates (current)

### Maintenance Tasks
- Regular cleanup of old workflow logs
- Review and update manager assignments as organizational structure changes
- Monitor workflow performance and optimize as needed
- Keep manager role capabilities updated with WordPress security standards

---

**Note**: This workflow system is specifically designed for Persian/Farsi forms and includes RTL (Right-to-Left) interface support. All admin interfaces and user interactions are optimized for Persian language usage.