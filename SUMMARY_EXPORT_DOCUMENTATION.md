# Summary Table Export Feature

## Overview
Added export functionality to the existing summary table in the `donap_gravity_session_scores_table` shortcode. This allows users to export column totals in CSV format.

## How It Works

### Frontend
- An "اکسپورت خلاصه" (Export Summary) button is added to the summary table header
- Button appears alongside the summary table title
- Button is styled with a green gradient to distinguish it from main table export buttons

### Backend
- New AJAX action: `donap_export_summary_scores`
- New method: `SessionScoresController::handleSummaryExport()`
- New method: `SessionScoresService::prepareSummaryData()`

### Export Data Format
The exported CSV contains:
- Column 1: نام ستون (Column Name)
- Column 2: مجموع (Total)
- Column 3: تعداد ورودی‌ها (Entry Count)

## Usage

### Basic Usage
```php
[donap_gravity_session_scores_table 
    form_id="123" 
    view_id="456" 
    show_summary_table="true"]
```

### Key Parameters
- `show_summary_table="true"` - Must be enabled for export button to appear
- Must have summable fields configured in GravityView
- `view_id` is required to identify which columns to process

## Files Modified

### Backend Files
1. **SessionScoresServiceProvider.php**
   - Added AJAX action registration for summary export
   - Added new handler method `handle_ajax_summary_export()`

2. **SessionScoresController.php**
   - Added `handleSummaryExport()` method
   - Handles CSV generation for summary data

3. **SessionScoresService.php**
   - Added `prepareSummaryData()` method
   - Formats column totals for CSV export

### Frontend Files
4. **session-scores-table.view.php**
   - Added export button to summary section header
   - Added responsive CSS styles for new button
   - Added view_id to JavaScript for AJAX calls

5. **session-scores.js**
   - Added `exportSummary()` event handler
   - Added `performSummaryExport()` method
   - Uses existing AJAX patterns for consistency

## Implementation Approach

This implementation follows the **senior WordPress developer approach**:

✅ **Minimal Code Changes** - Only added necessary functionality without modifying existing structure
✅ **Consistent Patterns** - Uses same AJAX, export, and styling patterns as existing code  
✅ **No Breaking Changes** - All existing functionality remains intact
✅ **Follows Conventions** - Uses existing CSS classes, naming patterns, and file structure
✅ **Security** - Uses same nonce verification and data sanitization as main export
✅ **Responsive Design** - Export button works on mobile and desktop

## Technical Details

### Export Flow
1. User clicks "اکسپورت خلاصه" button
2. JavaScript creates form with AJAX action `donap_export_summary_scores`
3. Form submitted with view_id and nonce
4. `SessionScoresController::handleSummaryExport()` processes request
5. `SessionScoresService::prepareSummaryData()` formats data
6. `ExportFactory::createCsvExporter()` generates CSV
7. Browser downloads file: `session-scores-summary-YYYY-MM-DD-HH-MM-SS.csv`

### Security
- Uses existing nonce: `donap_export_scores`
- Validates and sanitizes all input parameters
- Same security model as main table export

### Error Handling
- Graceful degradation if no summary data available
- Error logging for debugging
- User-friendly error messages

## Testing

To test the functionality:
1. Create a shortcode with `show_summary_table="true"`
2. Ensure GravityView has summable fields configured
3. Verify summary table appears with export button
4. Click export button and verify CSV download
5. Check CSV contains proper column names and totals
