# Enhanced Gravity Flow Inbox Implementation

This implementation adds an enhanced, mobile-responsive inbox table for Gravity Flow entries with advanced sorting, filtering, and bulk action capabilities in the Donapp Core plugin.

## Overview

The enhanced Gravity Flow inbox provides a comprehensive replacement for the standard `[gravityflow page="inbox"]` shortcode with improved functionality including:

- **Column Reorganization**: Optimized column order (Form Name, Status, Submitter, Send Time)
- **Mobile Responsiveness**: Full mobile optimization with collapsible columns
- **Sorting Capabilities**: Click-to-sort on all major columns
- **Bulk Actions**: Select all/individual entries for bulk operations
- **Advanced Filtering**: Filter by status, form, and search functionality
- **Enhanced Pagination**: Persian-localized pagination controls

## Features

### Column Structure
1. **نام فرم** (Form Name) - First column with entry title and mobile meta information
2. **وضعیت** (Status) - Color-coded status badges
3. **ارسال کننده** (Submitter) - User name and email information
4. **زمان ارسال** (Send Time) - Persian-formatted date and time
5. **عملیات** (Actions) - View, approve, reject, export actions

### Enhanced Functionality
- **Bulk Selection**: "Select All" checkbox with individual entry selection
- **Bulk Operations**: Approve, reject, delete, export multiple entries
- **Advanced Sorting**: Ascending/descending sort on all columns
- **Status Filtering**: Filter by pending, in progress, completed, rejected
- **Form Filtering**: Filter by specific forms
- **Search**: Search across submitter names and form content
- **Mobile Optimization**: Responsive design with collapsible columns

## Shortcode Usage

### Basic Usage
```
[donap_gravity_flow_inbox]
```

### Advanced Usage with Parameters
```
[donap_gravity_flow_inbox 
    per_page="20" 
    show_bulk_actions="true" 
    show_filters="true" 
    mobile_responsive="true" 
    show_pagination="true" 
    table_class="custom-gravity-table"
]
```

### Shortcode Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `per_page` | `20` | Number of entries per page |
| `show_bulk_actions` | `true` | Show bulk selection and actions |
| `show_filters` | `true` | Show filtering options |
| `mobile_responsive` | `true` | Enable mobile responsiveness |
| `show_pagination` | `true` | Show pagination controls |
| `table_class` | `donap-gravity-flow-table` | CSS class for the table |

## API Endpoints

### Bulk Actions API
```
POST /wp-json/dnp/v1/gravity/bulk-action
```

**Request Body:**
```json
{
    "bulk_action": "approve|reject|delete|export",
    "entry_ids": [1, 2, 3, 4],
    "_wpnonce": "security_nonce"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "message": "4 ورودی با موفقیت تأیید شدند",
        "results": [...],
        "success_count": 4,
        "error_count": 0
    }
}
```

## Database Integration

### Entry Status Management
The system manages entry workflow status through Gravity Forms meta fields:

- `workflow_final_status`: Current workflow status (pending, approved, rejected, etc.)
- `approved_by`: User ID who approved the entry
- `approved_at`: Timestamp of approval
- `rejected_by`: User ID who rejected the entry
- `rejected_at`: Timestamp of rejection
- `exported_at`: Timestamp when entry was exported
- `exported_by`: User ID who exported the entry

### Status Values
- `pending`: در انتظار بررسی
- `in_progress`: در حال بررسی  
- `completed`: تکمیل شده
- `rejected`: رد شده
- `approved`: تأیید شده

## File Structure

### Core Implementation
- `src/Providers/ShortcodeServiceProvider.php`: Shortcode registration and rendering logic
- `src/Services/GravityService.php`: Enhanced data retrieval with `getEnhancedGravityFlowEntries()`
- `src/Controllers/GravityController.php`: Bulk action handling with `handleBulkAction()`

### Frontend Views
- `views/shortcodes/gravity-flow-inbox.view.php`: Complete enhanced inbox template

### Route Configuration
- `src/Routes/RouteServiceProvider.php`: API endpoint registration for bulk actions

## User Interface Features

### Enhanced Table Design
- **RTL Support**: Full right-to-left layout for Persian content
- **Status Badges**: Color-coded visual status indicators
- **Sortable Headers**: Click headers to sort data
- **Row Actions**: Individual entry actions (view, approve, reject, export)

### Mobile Responsiveness
When `mobile_responsive="true"`:
- Table transforms to card-based layout on mobile devices
- Secondary columns collapse into main column metadata
- Bulk actions stack vertically
- Pagination adapts to smaller screens

### Bulk Actions Interface
- **Select All**: Master checkbox to select/deselect all visible entries
- **Individual Selection**: Checkboxes for each entry row
- **Action Dropdown**: Choose from available bulk operations
- **Confirmation Dialogs**: Security confirmations before bulk operations

### Filtering System
- **Status Filter**: Dropdown to filter by workflow status
- **Form Filter**: Dropdown to filter by specific forms
- **Search Box**: Text search across entry data
- **Filter Reset**: Clear all applied filters

### Pagination Controls
- **Persian Numerals**: Localized page numbers
- **Navigation Links**: First, previous, next, last page controls
- **Page Info**: "Showing X to Y of Z entries" in Persian
- **URL State**: Maintains filters and sorting in URL parameters

## Implementation Details

### Data Flow
1. **Shortcode Render**: `ShortcodeServiceProvider::renderGravityFlowInbox()`
2. **Data Retrieval**: `GravityService::getEnhancedGravityFlowEntries()`
3. **Template Render**: `gravity-flow-inbox.view.php`
4. **AJAX Actions**: `GravityController::handleBulkAction()`

### Security Features
- **Nonce Verification**: All AJAX requests protected with WordPress nonces
- **Permission Checking**: Capability-based access control
- **Input Sanitization**: All user inputs sanitized
- **SQL Injection Protection**: Using WordPress WPDB prepared statements

### Performance Optimizations
- **Pagination**: Efficient data loading with pagination
- **Caching Ready**: Structure supports object caching
- **Optimized Queries**: Minimal database queries for large datasets
- **Asset Loading**: CSS and JavaScript loaded only when needed

## Styling and Customization

### CSS Classes
- `.donap-gravity-flow-inbox-wrapper`: Main container
- `.gravity-flow-filters`: Filter section
- `.gravity-flow-bulk-actions`: Bulk actions bar
- `.donap-gravity-flow-table`: Main data table
- `.status-badge.status-{status}`: Status-specific styling
- `.mobile-responsive`: Mobile-responsive modifications

### Color Scheme
- **Pending**: `#f56e28` (Orange)
- **In Progress**: `#0073aa` (Blue)  
- **Completed/Approved**: `#46b450` (Green)
- **Rejected**: `#dc3232` (Red)

### Mobile Breakpoints
- `782px`: Tablet adjustments
- `480px`: Mobile phone optimizations

## JavaScript Functionality

### Interactive Features
- **Select All Toggle**: Master checkbox functionality
- **Individual Selection**: Indeterminate state handling
- **Bulk Action Confirmation**: User confirmation dialogs
- **Entry Actions**: Individual entry action handlers
- **Form Submission**: AJAX form handling with error management

### Event Handlers
- `select-all-entries`: Master checkbox change
- `entry-checkbox`: Individual checkbox change  
- `bulk-action-form`: Bulk action form submission
- `entry-view-action`: View entry details
- `entry-approve-action`: Approve single entry
- `entry-reject-action`: Reject single entry with notes
- `entry-export-action`: Export single entry

## Integration with Existing Systems

### Gravity Forms Compatibility
- Full integration with Gravity Forms API
- Compatible with all form field types
- Maintains existing entry data structure
- Supports custom form configurations

### Gravity Flow Integration
- Compatible with existing Gravity Flow workflows
- Respects workflow status and permissions
- Integrates with approval/rejection processes
- Maintains workflow history and audit trail

### WordPress Integration
- Uses WordPress user management
- Integrates with WordPress capabilities system
- Follows WordPress coding standards
- Compatible with WordPress multisite

## Error Handling

### Frontend Error Management
- User-friendly error messages in Persian
- Graceful degradation when plugins unavailable
- Form validation before submission
- AJAX error handling with user feedback

### Backend Error Handling
- Comprehensive exception handling
- Error logging for debugging
- Rollback capabilities for failed bulk operations
- Status reporting for partial failures

## Testing and Quality Assurance

### Test Scenarios
1. **Shortcode Rendering**: Test with various parameter combinations
2. **Bulk Actions**: Test all bulk operations with multiple entries
3. **Filtering**: Test all filter combinations and search functionality
4. **Sorting**: Test ascending/descending sort on all columns
5. **Mobile Responsiveness**: Test on various screen sizes
6. **Permission Handling**: Test with different user roles
7. **Error Scenarios**: Test with invalid data and network errors

### Sample Test Data
The system includes comprehensive sample data when Gravity Forms/Flow are not available:
- 5 sample entries with various statuses
- Multiple form types represented
- Different submitters and timestamps
- Various available actions per entry

## Troubleshooting

### Common Issues

**Shortcode not displaying:**
- Verify `ShortcodeServiceProvider` is registered in `AppServiceProvider`
- Check shortcode syntax: `[donap_gravity_flow_inbox]`
- Ensure user has appropriate permissions

**Bulk actions not working:**
- Verify nonce security token
- Check user capabilities (typically requires `manage_options`)
- Ensure entries are selected before submitting

**Mobile view not responsive:**
- Verify `mobile_responsive="true"` parameter
- Check CSS is loading correctly
- Test viewport meta tag in theme

**Filters not applying:**
- Check URL parameters are being passed correctly
- Verify `GravityService::getEnhancedGravityFlowEntries()` filter logic
- Ensure form data is available

### Debug Mode
Enable WordPress debug mode for detailed logging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Extension and Customization

### Adding Custom Columns
To add custom columns, modify:
1. `GravityService::getEnhancedGravityFlowEntries()` - Add data
2. `gravity-flow-inbox.view.php` - Add column headers and cells
3. CSS - Add responsive styles for new columns

### Custom Bulk Actions
To add new bulk actions:
1. Add action to `gravity-flow-inbox.view.php` dropdown
2. Implement handler in `GravityController::handleBulkAction()`
3. Add corresponding single entry method

### Custom Status Types
To add new status types:
1. Update `$status_labels` array in view template
2. Add corresponding colors in `$status_colors`
3. Update CSS for new status badge classes

### Integration with External Systems
The enhanced inbox can be extended to integrate with:
- External approval systems via API hooks
- Email notification services
- Document management systems
- Reporting and analytics platforms

## Performance Considerations

### Database Optimization
- Proper indexing on Gravity Forms entry tables
- Efficient pagination queries
- Optional result caching for frequently accessed data

### Frontend Performance
- Minified CSS and JavaScript in production
- Conditional asset loading
- Optimized mobile rendering
- Progressive enhancement approach

### Scalability
- Supports large numbers of entries through pagination
- Efficient sorting and filtering algorithms
- Memory-conscious data processing
- Optional background processing for bulk operations

## Security Best Practices

### Input Validation
- All user inputs sanitized using WordPress functions
- Type checking on all parameters
- Range validation on numerical inputs

### Permission Management
- Capability-based access control
- Entry-level permission checking
- Audit logging of all actions

### Data Protection
- Nonce protection on all forms
- CSRF protection on AJAX requests
- Sanitized output in all templates

## Future Enhancements

### Planned Features
- **Advanced Search**: Full-text search across all entry fields
- **Custom Views**: Save and manage custom filter/sort combinations  
- **Export Templates**: Customizable export formats and templates
- **Automated Actions**: Scheduled bulk operations and workflows
- **Analytics Dashboard**: Entry statistics and reporting
- **Mobile App Support**: REST API endpoints for mobile applications

### API Extensibility
The system provides hooks for developers:
- Custom bulk action handlers
- Filter and sort extensions  
- Custom column implementations
- Third-party system integrations

---

**Note**: This enhanced Gravity Flow inbox is specifically optimized for Persian/Farsi content with RTL layout support and Persian date formatting. All user interface elements and messages are localized for Persian language usage.