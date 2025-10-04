# Session Scores Export Implementation

## Overview
Created a complete export system for the Session Scores table following your established export architecture pattern.

## Files Created/Modified

### 1. New Concrete Export Classes
- **`src/Utils/Export/Concrete/SessionScoresSummaryCsv.php`** - CSV exporter for summary data
- **`src/Utils/Export/Concrete/SessionScoresSummaryXlsx.php`** - XLSX exporter for summary data

### 2. Updated Export Factory
- **`src/Utils/Export/ExportFactory.php`** - Added `createSessionScoresSummaryExporter()` method

### 3. Enhanced Controller
- **`src/Controllers/SessionScoresController.php`** - Added export methods:
  - `export()` - Main export handler
  - `exportSummary()` - Summary export
  - `exportEntries()` - Selected/all entries export
  - `prepareCsvData()` - CSV data preparation

### 4. Updated Service
- **`src/Services/SessionScoresService.php`** - Added public `getEntriesByIds()` method

### 5. New Route
- **`src/Routes/RouteServiceProvider.php`** - Added POST route: `/wp-json/donapp/v1/session-scores/export`

### 6. Frontend Updates
- **`views/shortcodes/session-scores-table.view.php`** - Added export button and styling
- **`src/assets/js/session-scores.js`** - Added `exportSummary()` JavaScript function

## API Endpoint

### Route
```
POST /wp-json/donapp/v1/session-scores/export
```

### Request Body
```json
{
    "type": "summary",     // "summary" or "entries"
    "view_id": 123,        // Required: GravityView ID
    "form_id": 456,        // Optional: Form ID
    "rows": [1, 2, 3]      // Optional: Specific entry IDs (for entries type)
}
```

### Response
- **Success**: Downloads CSV file directly
- **Error**: JSON error response

## Export Types

### 1. Summary Export (`type: "summary"`)
- Exports column totals summary
- Uses `SessionScoresSummaryCsv` concrete class
- Contains: Column Name, Total Score, Entry Count, Average Score

### 2. Entries Export (`type: "entries"`)
- Exports individual entries
- If `rows` provided: exports selected entries
- If `rows` empty: exports all entries
- Uses generic `CsvManager` class

## Usage

### Frontend Button
The summary export button appears in the summary table section:
```html
<button type="button" id="donap-export-summary" class="donap-btn donap-btn-success">
    📊 اکسپورت خلاصه
</button>
```

### JavaScript Call
```javascript
// Automatic call when button is clicked
// Uses fetch API to call /wp-json/donapp/v1/session-scores/export
// Downloads file automatically on success
```

## Features

✅ **Follows Established Pattern** - Uses your existing export architecture  
✅ **No Authentication Required** - Simple wp-json route calling  
✅ **Flexible Data Selection** - Summary or specific/all entries  
✅ **Responsive Design** - Works on mobile and desktop  
✅ **Error Handling** - Graceful error messages and logging  
✅ **Loading States** - Button shows loading during export  
✅ **Automatic Download** - Files download automatically  
✅ **Persian Support** - RTL design and Persian labels

## Testing

To test the implementation:

1. **Summary Export**: Visit page with `[donap_gravity_session_scores_table show_summary_table="true"]`
2. **Click Export Button**: Click "اکسپورت خلاصه" in summary table
3. **Verify Download**: CSV file should download with column totals
4. **Check Content**: CSV should contain column names, totals, counts, and averages

## Error Handling

- Validates required parameters (view_id or form_id)
- Handles missing data gracefully
- Logs errors for debugging
- Shows user-friendly error messages
- Automatic button state reset

## Next Steps

The implementation is complete and ready for testing. The export system integrates seamlessly with your existing architecture and follows all established patterns.
