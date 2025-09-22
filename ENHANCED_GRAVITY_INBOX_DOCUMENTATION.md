# Enhanced Gravity Flow Inbox Shortcode

## Overview
The enhanced Gravity Flow Inbox shortcode now includes integrated beautiful export buttons and improved functionality using the Gravity Flow API.

## Shortcode Usage

```php
[gravity_flow_inbox 
    mobile_responsive="true" 
    show_bulk_actions="true" 
    show_filters="true" 
    show_pagination="true" 
    show_export_buttons="true"
    table_class="donap-gravity-flow-table"
]
```

## Parameters

- `mobile_responsive` (true/false) - Enable mobile responsive design
- `show_bulk_actions` (true/false) - Show bulk action controls
- `show_filters` (true/false) - Show filtering options
- `show_pagination` (true/false) - Show pagination controls
- `show_export_buttons` (true/false) - Show integrated export buttons
- `table_class` (string) - CSS class for the table

## Features

### 🎨 Beautiful Export Integration
- **Header Export Buttons**: Prominent export dropdown in the inbox header
- **Individual Entry Export**: Per-row export options for PDF and Excel
- **Modern Design**: Gradient backgrounds, hover effects, and animations
- **Loading States**: Visual feedback during export operations

### 📊 Export Options
- **CSV Export**: For spreadsheet applications
- **Excel Export**: Formatted Excel files with proper styling
- **PDF Export**: Print-ready documents

### 🔧 Enhanced Functionality
- **Gravity Flow API Integration**: Uses official API methods
- **Workflow Operations**: Restart, cancel, and step management
- **Timeline Tracking**: Complete audit trails
- **Real-time Status**: Accurate workflow states

### 📱 Responsive Design
- **Mobile Optimized**: Card-based layout on mobile devices
- **Touch Friendly**: Large tap targets for mobile users
- **Adaptive Export**: Export dropdowns adjust to screen size

## Export URLs (Available Routes)

### Inbox Export Routes
- `GET /gravity/inbox/export-csv` - Export all inbox entries as CSV
- `GET /gravity/inbox/export-xlsx` - Export all inbox entries as Excel
- `GET /gravity/inbox/export-pdf` - Export all inbox entries as PDF

### Single Entry Export Routes
- `GET /gravity/entry/export-pdf` - Export single entry as PDF
- `GET /gravity/entry/export-excel` - Export single entry as Excel

### Workflow Management Routes
- `POST /gravity/workflow/restart` - Restart workflow for entry
- `POST /gravity/workflow/cancel` - Cancel workflow for entry
- `POST /gravity/workflow/send-to-step` - Send entry to specific step
- `GET /gravity/workflow/steps` - Get workflow steps for form
- `GET /gravity/entry/timeline` - Get entry timeline

## Styling

The shortcode includes comprehensive CSS styling with:
- **Modern Design System**: Consistent colors, typography, and spacing
- **Hover Effects**: Smooth transitions and visual feedback
- **Status Badges**: Color-coded workflow states
- **Loading Animations**: Spinner animations during operations
- **Mobile Responsive**: Adaptive layout for all screen sizes

## JavaScript Features

- **Export Dropdown Toggle**: Smooth dropdown animations
- **Loading States**: Visual feedback during exports
- **Bulk Actions**: Select all/none functionality
- **Entry Actions**: Individual entry operations
- **Mobile Optimization**: Touch-friendly interactions

## Integration with Gravity Flow API

The enhanced shortcode leverages the Gravity Flow API for:
- **Inbox Management**: `Gravity_Flow_API::get_inbox_entries()`
- **Status Tracking**: `$api->get_status($entry)`
- **Timeline Data**: `$api->get_timeline($entry)`
- **Workflow Operations**: `$api->restart_workflow()`, `$api->cancel_workflow()`
- **Step Management**: `$api->send_to_step()`, `$api->get_steps()`

## Security Features

- **Nonce Verification**: CSRF protection for all operations
- **Permission Checks**: User capability validation
- **URL Validation**: Secure export URLs with user ID parameters
- **Input Sanitization**: All user inputs are properly sanitized

## Performance Optimizations

- **API-based Queries**: Uses optimized Gravity Flow API methods
- **Pagination Support**: Handles large datasets efficiently
- **Lazy Loading**: Export operations don't block UI
- **Caching**: Leverages Gravity Flow's built-in caching

## Browser Support

- **Modern Browsers**: Chrome, Firefox, Safari, Edge
- **Mobile Browsers**: iOS Safari, Chrome Mobile, Samsung Internet
- **Graceful Degradation**: Works without JavaScript for basic functionality

## Accessibility

- **ARIA Labels**: Screen reader support
- **Keyboard Navigation**: Full keyboard accessibility
- **High Contrast**: Supports high contrast mode
- **Focus Management**: Clear focus indicators

This enhanced inbox provides a complete workflow management solution with beautiful, user-friendly export capabilities integrated directly into the interface.
