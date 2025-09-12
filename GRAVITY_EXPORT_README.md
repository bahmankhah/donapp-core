# PDF and Excel Export Implementation for Gravity Flow

This implementation adds PDF and Excel export functionality for both the Gravity Flow inbox page and individual entry pages in the Donapp Core plugin.

## Features Added

### 1. Backend Export Functionality (Admin Dashboard)
- **PDF Export**: Added PDF export capability for all approved Gravity Flow entries
- **Enhanced Excel Export**: Improved the existing Excel export functionality
- **Individual Entry Export**: Added PDF and Excel export for single entries with dropdown interface

### 2. Frontend Export Functionality (Public Pages)
- **Shortcodes**: Two new shortcodes for adding export buttons to any page
- **Flexible Styling**: Support for button and dropdown styles
- **Auto-detection**: Automatic detection of entry and form IDs from URL parameters

## New API Endpoints

### Gravity Flow Exports
```
GET /wp-json/dnp/v1/gravity/export-pdf?uid={user_id}
GET /wp-json/dnp/v1/gravity/entry/export-pdf?entry_id={entry_id}&form_id={form_id}
GET /wp-json/dnp/v1/gravity/entry/export-excel?entry_id={entry_id}&form_id={form_id}
```

## Shortcodes Usage

### 1. Inbox Page Export Buttons
Add this shortcode to the Gravity Flow inbox page (صندوق-ورودی-گردش-کار):

```php
[donap_gravity_export_buttons style="buttons" align="right" show_csv="true" show_excel="true" show_pdf="true"]
```

**Parameters:**
- `style`: `"buttons"` or `"dropdown"` (default: `"buttons"`)
- `align`: `"left"`, `"center"`, or `"right"` (default: `"left"`)
- `show_csv`: `"true"` or `"false"` (default: `"true"`)
- `show_excel`: `"true"` or `"false"` (default: `"true"`)
- `show_pdf`: `"true"` or `"false"` (default: `"true"`)

### 2. Single Entry Export Buttons
Add this shortcode to individual entry pages:

```php
[donap_gravity_single_export entry_id="76" form_id="13456" style="dropdown" show_pdf="true" show_excel="true"]
```

**Parameters:**
- `entry_id`: The entry ID (can be auto-detected from URL)
- `form_id`: The form ID (can be auto-detected from URL)
- `style`: `"buttons"` or `"dropdown"` (default: `"buttons"`)
- `show_pdf`: `"true"` or `"false"` (default: `"true"`)
- `show_excel`: `"true"` or `"false"` (default: `"true"`)
- `auto_detect`: `"true"` or `"false"` (default: `"true"`) - Auto-detect IDs from URL

## Implementation Details

### Files Modified/Added

1. **Controllers**
   - `src/Controllers/GravityController.php`: Added PDF export methods and single entry export functionality

2. **Services**
   - `src/Services/GravityService.php`: Added `getSingleEntryForExport()` method

3. **Utils**
   - `src/Utils/FileHelper.php`: Added PDF generation methods (`csv2Pdf`, `entry2Pdf`, `servePdfDownload`)

4. **Routes**
   - `src/Routes/RouteServiceProvider.php`: Added new export endpoints

5. **Providers**
   - `src/Providers/ShortcodeServiceProvider.php`: Added export button shortcodes

6. **Views**
   - `views/admin/gravity-flow.view.php`: Enhanced with export buttons and dropdown functionality
   - `views/shortcodes/gravity-export-buttons.view.php`: Frontend export buttons template
   - `views/shortcodes/gravity-single-export.view.php`: Single entry export template

### PDF Generation Approach

The PDF functionality uses HTML-based generation that:
- Creates properly formatted HTML with Persian/RTL support
- Uses Vazir font for Persian text
- Includes auto-print functionality for browser PDF generation
- Is responsive and print-optimized
- Can be easily extended to use dedicated PDF libraries (TCPDF, mPDF) in the future

### CSS and JavaScript

- **Responsive design**: Works on desktop and mobile
- **RTL support**: Proper right-to-left layout for Persian content
- **Interactive dropdowns**: JavaScript-powered export menus
- **Modern styling**: Clean, professional appearance matching WordPress admin

## Usage Examples

### For Inbox Page
```html
<!-- Add to the inbox page template -->
<div class="export-section">
    [donap_gravity_export_buttons style="dropdown" align="center"]
</div>
```

### For Single Entry Page
```html
<!-- Add to individual entry pages -->
<div class="entry-actions">
    [donap_gravity_single_export auto_detect="true" style="buttons"]
</div>
```

### Via PHP
```php
// In template files
echo do_shortcode('[donap_gravity_export_buttons]');

// For specific entries
echo do_shortcode('[donap_gravity_single_export entry_id="76" form_id="13456"]');
```

## Security Features

- User authentication required for all exports
- Nonce verification for admin exports
- Input sanitization for all parameters
- WordPress capability checks where appropriate

## Browser Compatibility

- Modern browsers with CSS3 and ES6 support
- Graceful degradation for older browsers
- Mobile-responsive design
- Print-optimized PDF generation

## Notes

- PDF exports open in new tab/window for printing
- Excel exports download directly
- All exports include Persian date/time stamps
- Export filenames include timestamps for uniqueness
- Supports both Gravity Forms and Gravity Flow workflows

## Future Enhancements

- Integration with dedicated PDF libraries (TCPDF/mPDF)
- Batch export functionality
- Custom PDF templates
- Email export functionality
- Scheduled exports