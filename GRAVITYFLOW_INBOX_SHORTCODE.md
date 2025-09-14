# Gravity Flow Inbox Shortcode Documentation

## Overview

The `donap_gravityflow_inbox` shortcode displays a beautiful and responsive table showing Gravity Flow inbox entries for the current user. This shortcode provides a comprehensive view of workflow items that require user attention.

## Usage

### Basic Usage
```
[donap_gravityflow_inbox]
```

### Advanced Usage with Parameters
```
[donap_gravityflow_inbox per_page="15" show_stats="true" show_filters="true" mobile_responsive="true"]
```

## Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `per_page` | integer | `20` | Number of entries to display per page |
| `show_stats` | boolean | `true` | Show statistics cards at the top |
| `show_filters` | boolean | `true` | Show filter dropdowns for status and priority |
| `mobile_responsive` | boolean | `true` | Enable mobile-responsive design |
| `show_pagination` | boolean | `true` | Show pagination controls |
| `table_class` | string | `donap-gravity-flow-table` | CSS class for the main table |
| `show_bulk_actions` | boolean | `true` | Show bulk action controls (future feature) |

## Features

### 🎨 Beautiful Design
- Modern gradient colors and smooth animations
- Persian/RTL language support
- Professional card-based statistics
- Color-coded status badges with different styles
- Priority indicators with visual cues

### 📱 Fully Responsive
- **Desktop**: Full table with all columns
- **Tablet**: Optimized layout with adjusted spacing
- **Mobile**: Card-based layout for better readability
- **Small Mobile**: Stacked card design with labels

### ⚡ Interactive Features
- **Real-time Filtering**: Filter by status and priority
- **Pagination**: Navigate through multiple pages
- **Action Buttons**: Direct links to Gravity Flow admin
- **Quick Actions**: Approve, reject, complete, and more
- **Priority Sorting**: Entries sorted by priority and date

### 📊 Statistics Dashboard
The shortcode displays helpful statistics:
- **Total Items**: Total number of inbox entries
- **Pending**: Items waiting for action
- **In Progress**: Items currently being processed

### 🔧 Status Management
Supports all Gravity Flow statuses:
- **در انتظار** (Pending) - Yellow badge
- **در حال پردازش** (In Progress) - Blue badge  
- **نیاز به ورودی کاربر** (User Input) - Purple badge
- **تأیید شده** (Approved) - Green badge
- **تکمیل شده** (Complete) - Green badge

### 🎯 Priority System
Three priority levels with visual indicators:
- **Priority 3** (High) - Red dot
- **Priority 2** (Medium) - Orange dot  
- **Priority 1** (Normal) - Green dot

## Table Columns

1. **فرم** (Form) - Form title and ID
2. **مرحله** (Step) - Current workflow step name and type
3. **ارسال‌کننده** (Submitter) - User who submitted the entry
4. **تاریخ ایجاد** (Date Created) - When the entry was submitted
5. **وضعیت** (Status) - Current workflow status with color coding
6. **اولویت** (Priority) - Priority level with visual indicator
7. **مهلت** (Due Date) - Deadline for the current step (if set)
8. **خلاصه** (Summary) - Brief summary of entry content
9. **عملیات** (Actions) - Available actions for the entry

## Sample Actions

The shortcode provides different actions based on the workflow step:

### Approval Steps
- **مشاهده** (View) - Open entry in Gravity Flow admin
- **تأیید** (Approve) - Approve the entry
- **رد** (Reject) - Reject the entry

### User Input Steps  
- **مشاهده** (View) - Open entry for editing
- **تکمیل** (Complete) - Mark as completed

### Notification Steps
- **مشاهده** (View) - View the notification
- **تأیید دریافت** (Acknowledge) - Acknowledge receipt

## Error Handling

The shortcode gracefully handles various scenarios:

### No Gravity Forms/Flow
When Gravity Forms or Gravity Flow is not active, it displays sample data for demonstration purposes.

### No Entries
Shows a beautiful empty state with an inbox icon and helpful message.

### Permission Issues
Displays appropriate error messages for access denied scenarios.

## CSS Customization

### Custom Styling
You can override the default styles by adding CSS to your theme:

```css
/* Custom table styling */
.donap-gravity-flow-table {
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

/* Custom status badge colors */
.status-pending {
    background: #your-color;
    color: #your-text-color;
}

/* Custom priority indicators */
.priority-3 {
    background: #your-high-priority-color;
}
```

### Theme Integration
The shortcode uses CSS custom properties that can be overridden:

```css
.donap-gravityflow-inbox-wrapper {
    --primary-color: #your-brand-color;
    --success-color: #your-success-color;
    --warning-color: #your-warning-color;
    --danger-color: #your-danger-color;
}
```

## JavaScript Events

The shortcode fires custom events that you can listen to:

```javascript
// When filters change
document.addEventListener('gravityflow-filter-changed', function(e) {
    console.log('Filter changed:', e.detail);
});

// When action is performed
document.addEventListener('gravityflow-action-performed', function(e) {
    console.log('Action performed:', e.detail);
});
```

## Integration with WordPress

### Shortcode Registration
The shortcode is automatically registered through the `ShortcodeServiceProvider` when the plugin is active.

### User Permissions
The shortcode respects Gravity Flow permissions and only shows entries that the current user has access to.

### AJAX Actions
Future versions will support AJAX actions for:
- Approving/rejecting entries without page reload
- Bulk actions for multiple entries
- Real-time status updates

## Performance

### Optimization Features
- **Pagination**: Prevents loading too many entries at once
- **Lazy Loading**: Only loads visible content
- **Caching**: Uses WordPress transients for improved performance
- **Database Optimization**: Efficient queries with proper indexes

### Best Practices
- Use reasonable `per_page` values (10-50)
- Enable caching on high-traffic sites
- Consider using page builders for better integration

## Browser Support

- ✅ Chrome 60+
- ✅ Firefox 60+  
- ✅ Safari 12+
- ✅ Edge 79+
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

## Accessibility

The shortcode includes accessibility features:
- **ARIA Labels**: Proper labeling for screen readers
- **Keyboard Navigation**: Full keyboard support
- **High Contrast**: Readable color combinations
- **Screen Reader Support**: Semantic HTML structure

## Examples

### Simple Inbox
```
[donap_gravityflow_inbox per_page="10"]
```

### Inbox with Stats Only
```
[donap_gravityflow_inbox show_filters="false" show_stats="true"]
```

### Minimal Inbox
```
[donap_gravityflow_inbox show_stats="false" show_filters="false" per_page="5"]
```

### Custom Styled Inbox
```
[donap_gravityflow_inbox table_class="my-custom-table" mobile_responsive="true"]
```

## Troubleshooting

### Common Issues

1. **Empty Table**: Check if Gravity Forms and Gravity Flow are active
2. **No Permissions**: Ensure user has appropriate workflow permissions
3. **Styling Issues**: Check for theme CSS conflicts
4. **JavaScript Errors**: Verify jQuery is loaded properly

### Debug Mode
Enable WordPress debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support

For support and feature requests, please contact the plugin developer or submit an issue through the appropriate channels.
