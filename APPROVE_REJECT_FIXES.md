# Gravity Flow Inbox - Approve/Reject Button Fixes & Debugging

## Issues Fixed

### 1. **API Returns 200 but Status Doesn't Change**

The main issue was that the approve/reject methods weren't properly handling the Gravity Flow step approval process. The previous implementation was too generic and didn't account for different step types and approval mechanisms.

### 2. **Enhanced Approval Process** 

**New `approveSingleEntry()` method:**
- ✅ Checks for step-specific approval methods (`approve()`, `process_action()`)
- ✅ Updates step status to 'complete' 
- ✅ Updates workflow final status and metadata
- ✅ Processes workflow to move to next step
- ✅ Comprehensive logging with `appLogger`
- ✅ Verification of final status after approval

### 3. **Enhanced Rejection Process**

**New `rejectSingleEntry()` method:**
- ✅ Checks for step-specific rejection methods (`reject()`, `process_action()`) 
- ✅ Updates step status to 'rejected'
- ✅ Updates workflow final status and metadata
- ✅ Processes workflow to handle rejection
- ✅ Comprehensive logging with `appLogger`
- ✅ Verification of final status after rejection

### 4. **Comprehensive Debugging**

**Added `appLogger()` throughout:**
- Entry ID and form ID logging
- Current step information (ID, type, name)
- User information processing the action
- Step-specific method availability checks
- Metadata update confirmations
- Workflow processing results
- Final status verification

### 5. **New Debug Endpoint**

**Added `/wp-json/dnp/v1/gravity/debug-workflow?entry_id=X`:**
- Returns complete workflow status information
- Shows current step details
- Displays available actions
- Helps troubleshoot approval issues

## Key Improvements Made

### 1. **Step-Specific Processing**
```php
// Check if step has specific approval methods
if (method_exists($current_step, 'approve')) {
    $result = $current_step->approve();
} elseif (method_exists($current_step, 'process_action')) {
    $result = $current_step->process_action('approved');
}

// Update step status
if (method_exists($current_step, 'update_step_status')) {
    $current_step->update_step_status('complete');
}
```

### 2. **Enhanced Metadata Updates**
```php
// Update both workflow and step-specific metadata
gform_update_meta($entry_id, 'workflow_final_status', 'approved');
gform_update_meta($entry_id, 'workflow_step_status_' . $step_id, 'complete');
gform_update_meta($entry_id, 'approved_by', $user_id);
gform_update_meta($entry_id, 'approved_at', current_time('mysql'));
```

### 3. **Comprehensive Logging**
```php
appLogger("GravityController: Starting approval process for entry ID: $entry_id");
appLogger("GravityController: Current step ID: $step_id, Type: $step_type");
appLogger("GravityController: Step approve method result: " . ($result ? 'success' : 'failed'));
appLogger("GravityController: Final workflow status after approval: $final_status");
```

## How to Test

### 1. **Check Logs**
Logs are written to: `/wp-content/plugins/donapp-core/logs/donapp-errors.log`

### 2. **Use Debug Endpoint**
```bash
curl "https://yoursite.com/wp-json/dnp/v1/gravity/debug-workflow?entry_id=123"
```

### 3. **Test Approve/Reject Buttons**
- Click approve/reject buttons in the shortcode table
- Check the logs for detailed processing information
- Verify status changes in Gravity Flow admin

## Debugging Checklist

When approve/reject buttons don't work:

1. **Check the logs** - Look for appLogger entries
2. **Verify entry exists** - Use debug endpoint
3. **Check current step** - Is there an active step?
4. **Verify step type** - Is it an approval step?
5. **Check user permissions** - Does user have approval rights?
6. **Verify API classes** - Are GFAPI and Gravity_Flow_API loaded?

## Log Output Example

```
[2025-09-16 10:30:15] GravityController: Starting approval process for entry ID: 123
[2025-09-16 10:30:15] GravityController: Entry found - Form ID: 1, Entry ID: 123
[2025-09-16 10:30:15] GravityController: Current step ID: 5, Type: approval
[2025-09-16 10:30:15] GravityController: Processing approval by user: Admin (ID: 1)
[2025-09-16 10:30:15] GravityController: Using step-specific approval method
[2025-09-16 10:30:15] GravityController: Step approve method result: success
[2025-09-16 10:30:15] GravityController: Step status updated to complete
[2025-09-16 10:30:15] GravityController: All metadata updated for entry 123
[2025-09-16 10:30:15] GravityController: Workflow processed for entry 123
[2025-09-16 10:30:15] GravityController: Final workflow status after approval: approved
```

## API Endpoints

### 1. **Bulk Actions**
- **URL**: `POST /wp-json/dnp/v1/gravity/bulk-action`
- **Parameters**: `bulk_action`, `entry_ids[]`

### 2. **Debug Workflow**
- **URL**: `GET /wp-json/dnp/v1/gravity/debug-workflow`
- **Parameters**: `entry_id`

## Files Modified

1. `src/Controllers/GravityController.php` - Enhanced approval/rejection methods
2. `src/Routes/RouteServiceProvider.php` - Added debug route

The implementation now properly handles Gravity Flow's step-based approval system and provides comprehensive debugging information to troubleshoot any issues.
