# Summary Table Export Feature

## Overview
The `donap_gravity_session_scores_table` shortcode now includes export functionality for the **Column Totals Summary Table**.

## New Features Added

### 1. **Summary Table with Checkboxes**
- Each row in the summary table now has a checkbox
- Users can select specific columns to export
- "Select All" functionality for summary table

### 2. **Export Buttons**
- **Export Selected Columns**: Export only the selected summary rows
- **Export All Summary**: Export the entire summary table
- **Select All Columns**: Select/deselect all summary table rows

### 3. **Export Data Format**
The exported CSV includes:
- Column Name (نام ستون)
- Total Value (مجموع) 
- Number of Entries (تعداد ورودی‌ها)
- Average (میانگین)
- Export Date (تاریخ اکسپورت)
- Summary row with grand totals

### 4. **Technical Implementation**

#### Backend Components:
- **SessionScoresService**: Added `exportSummaryTableToCSV()` method
- **SessionScoresController**: Added `handleSummaryExport()` method  
- **SessionScoresServiceProvider**: Added AJAX action `donap_export_summary_table`

#### Frontend Components:
- **JavaScript**: Added `SummaryTableExport` object with full interaction handling
- **CSS**: Added responsive styling for summary export controls
- **HTML**: Added checkboxes and export buttons to summary table

### 5. **Security Features**
- Nonce verification for all AJAX requests
- Data sanitization for column names
- Proper escaping of output data

### 6. **Usage Example**

```php
[donap_gravity_session_scores_table 
    form_id="1" 
    view_id="123"
    show_summary_table="true"
    show_checkboxes="true"
]
```

### 7. **Export Flow**

1. User selects summary table rows using checkboxes
2. Clicks export button (Selected or All)
3. JavaScript collects selected column names
4. AJAX request sent to `donap_export_summary_table` action
5. Backend calculates totals and formats CSV data
6. CSV file generated using ExportFactory
7. File automatically downloads to user's browser

### 8. **File Structure**

```
Summary Export CSV Format:
- Header: نام ستون | مجموع | تعداد ورودی‌ها | میانگین | تاریخ اکسپورت
- Data: One row per selected column with totals and statistics
- Footer: Grand total row for all selected columns
```

This enhancement provides comprehensive export capabilities for both individual entry data and aggregate column statistics, giving users complete flexibility in data analysis and reporting.
