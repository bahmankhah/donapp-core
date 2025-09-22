# Gravity Flow Inbox - Test Documentation

## Changes Made

### 1. Removed Authentication Requirements
- Removed nonce verification from bulk actions
- Removed user permission checks as requested
- Made bulk actions accessible without authentication

### 2. Enhanced Bulk Actions
- Added bulk action form with checkboxes
- Added "Select All" functionality
- Added visual feedback for selected items
- Added support for approve, reject, delete, and export actions

### 3. Individual Action Buttons
- Fixed approve and reject buttons
- Improved error handling with try/catch blocks
- Added proper logging for debugging
- Made buttons work with both GET and POST requests

### 4. Mobile Responsive Design
- Added responsive design for bulk action form
- Improved mobile table layout with checkboxes
- Enhanced small screen support

### 5. JavaScript Improvements
- Added bulk action JavaScript functionality
- Improved error handling and user feedback
- Added loading states for buttons
- Removed nonce dependency as requested

## How to Test

### 1. Basic Functionality
```
[donap_gravityflow_inbox show_stats="true" show_filters="true" show_pagination="true" mobile_responsive="true" show_export_buttons="true"]
```

### 2. Test Individual Actions
1. Click on individual "تأیید" (Approve) or "رد" (Reject) buttons
2. Actions should work without authentication
3. Check browser console for any errors

### 3. Test Bulk Actions
1. Check individual items or use "Select All"
2. Choose an action from the dropdown
3. Click "اعمال" (Apply)
4. Confirm the action in the popup

### 4. API Endpoints
- POST/GET: `/wp-json/dnp/v1/gravity/bulk-action`
- Parameters: `bulk_action`, `entry_ids[]` or `entry_id`

## API Response Format

### Success Response
```json
{
  "success": true,
  "data": {
    "message": "2 ورودی با موفقیت تأیید شدند",
    "results": [
      {"entry_id": 1, "status": "success"},
      {"entry_id": 2, "status": "success"}
    ],
    "success_count": 2,
    "error_count": 0
  }
}
```

### Error Response
```json
{
  "success": false,
  "data": {
    "message": "1 ورودی تأیید شدند، 1 ورودی با خطا مواجه شدند",
    "results": [
      {"entry_id": 1, "status": "success"},
      {"entry_id": 2, "status": "error", "message": "Entry not found"}
    ],
    "success_count": 1,
    "error_count": 1
  }
}
```

## Troubleshooting

### Common Issues
1. **Buttons not working**: Check browser console for JavaScript errors
2. **API returning errors**: Check WordPress error logs
3. **Bulk actions not appearing**: Ensure entries array is not empty

### Debug Information
All errors are logged to WordPress error log with the prefix "GravityController:"

### Browser Console Commands
```javascript
// Test bulk action API directly
fetch('/wp-json/dnp/v1/gravity/bulk-action', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'bulk_action=approve&entry_ids[]=1'
}).then(r => r.json()).then(console.log);
```
