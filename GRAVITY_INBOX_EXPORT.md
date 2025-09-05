# Gravity Flow Inbox CSV Export Integration

## Overview
This implementation adds CSV export functionality to Gravity Flow's existing `[gravityflow page="inbox"]` shortcode. It seamlessly integrates with the native Gravity Flow inbox without requiring any changes to existing code.

## How It Works

### 🔧 **Technical Implementation**

1. **Hook Integration**: Uses WordPress filters to modify Gravity Flow's shortcode output
2. **Seamless Addition**: Adds export buttons without breaking existing functionality
3. **Security**: Uses WordPress nonces for secure CSV export operations
4. **User Permissions**: Respects Gravity Flow's permission system

### 📋 **Features Added**

#### 1. **Full Inbox Export Button**
- Adds a "خروجی CSV کل صندوق ورودی" button at the top of the inbox table
- Exports all entries currently visible in the user's inbox
- Includes workflow status, current step, and form data

#### 2. **Individual Entry Export Buttons**
- Adds a CSV export button to each row in the inbox table
- Exports detailed information for a single form entry
- Shows all form fields with their values

#### 3. **Automatic Integration**
- No changes needed to existing `[gravityflow page="inbox"]` shortcodes
- Works automatically when the service is active
- Maintains all existing Gravity Flow functionality

## Usage

### 🚀 **Basic Setup**
The integration is automatic once the service is registered. Simply use the existing Gravity Flow shortcode:

```
[gravityflow page="inbox"]
```

The export buttons will automatically appear when:
- User is logged in
- Gravity Flow is active
- User has inbox entries

### 📊 **Export Options**

#### **Full Inbox Export**
- **Button**: "خروجی CSV کل صندوق ورودی"
- **Location**: Top of the inbox table
- **Content**: All inbox entries with summary information
- **Filename**: `gravity-flow-inbox-YYYY-MM-DD-HH-MM-SS.csv`

#### **Individual Entry Export**
- **Button**: Download icon in each table row
- **Location**: New "خروجی" column in the table
- **Content**: Detailed form data for single entry
- **Filename**: `gravity-entry-{ID}-YYYY-MM-DD-HH-MM-SS.csv`

## CSV Export Content

### 📋 **Full Inbox Export Columns**
1. **شناسه ورودی** - Entry ID
2. **عنوان فرم** - Form Title
3. **وضعیت جریان کار** - Workflow Status
4. **مرحله فعلی** - Current Step
5. **تاریخ ایجاد** - Creation Date
6. **تاریخ آخرین به‌روزرسانی** - Last Update Date
7. **اطلاعات فرم** - Form Data Summary

### 📝 **Individual Entry Export Content**
- **Basic Information**: Entry ID, Form Title, Dates, Status
- **All Form Fields**: Each field as a separate row with label and value
- **Formatted Values**: Proper formatting for dates, files, etc.
- **Persian Labels**: All headers and labels in Persian

## Security Features

### 🔒 **Access Control**
- **User Authentication**: Must be logged in
- **Permission Checking**: Uses Gravity Flow's existing permission system
- **Entry Access**: Only entries the user can view in their inbox
- **Nonce Protection**: WordPress nonces prevent CSRF attacks

### ✅ **Permission Logic**
Users can export entries if they:
1. Are assigned to the workflow step
2. Created the original entry
3. Have admin privileges (`manage_options`)
4. Have specific Gravity Flow permissions

## File Structure

### 📁 **New Files Created**
```
src/Services/GravityFlowInboxService.php    # Main service class
```

### 🔄 **Modified Files**
```
src/Providers/AppServiceProvider.php        # Service registration
src/Providers/GravityServiceProvider.php    # Service initialization
```

## Technical Details

### 🎯 **WordPress Hooks Used**
- `gravityflow_shortcode_output` - Modify shortcode output
- `init` - Handle export requests
- `wp_enqueue_scripts` - Add CSS styling

### 🎨 **Styling**
- Inline CSS for export buttons
- WordPress admin-style button design
- Responsive and accessible design
- RTL-friendly layout

### 🔧 **API Integration**
- **Gravity Flow API**: For workflow information
- **Gravity Forms API**: For form and entry data
- **WordPress Functions**: For permissions and security

## Error Handling

### ⚠️ **Graceful Degradation**
- Checks for Gravity Flow availability
- Handles missing Gravity Forms
- Provides user-friendly error messages
- Falls back gracefully if APIs unavailable

### 🛡️ **Security Validation**
- Nonce verification for all exports
- User permission checks
- Entry access validation
- Sanitized output and file handling

## Usage Examples

### 📄 **In a Page/Post**
```
[gravityflow page="inbox"]
```
Export buttons will automatically appear.

### 🎯 **In Template Files**
```php
echo do_shortcode('[gravityflow page="inbox"]');
```

### 📱 **With Other Attributes**
```
[gravityflow page="inbox" user_roles="editor,author"]
```
Export functionality works with all existing Gravity Flow shortcode attributes.

## Customization

### 🎨 **Styling Customization**
Override the default styles by adding CSS:

```css
.donap-inbox-export-section {
    /* Custom export section styling */
}

.donap-inbox-export-btn {
    /* Custom export button styling */
}
```

### ⚙️ **Functionality Extension**
The service can be extended to:
- Add more export formats (Excel, PDF)
- Include additional workflow metadata
- Implement bulk operations
- Add email export functionality

## Compatibility

### ✅ **WordPress Compatibility**
- WordPress 5.0+
- Multisite compatible
- Theme independent

### ✅ **Plugin Compatibility**
- Gravity Forms 2.0+
- Gravity Flow 1.0+
- Works with other Gravity add-ons

### ✅ **Browser Compatibility**
- All modern browsers
- Mobile responsive
- Accessibility compliant

## Troubleshooting

### ❓ **Common Issues**

**Export buttons not showing**
- Check if user is logged in
- Verify Gravity Flow is active
- Ensure user has inbox entries

**CSV download fails**
- Check file permissions
- Verify nonce is valid
- Ensure no output before headers

**Empty CSV files**
- Check user permissions on entries
- Verify Gravity Flow data exists
- Check for PHP errors in logs

This integration provides a seamless way to add powerful CSV export capabilities to Gravity Flow's inbox without disrupting existing functionality.
