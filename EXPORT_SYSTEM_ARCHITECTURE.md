# Export System Architecture

This document describes the refactored export system that provides a clean, modular, and extensible architecture for exporting data in various formats.

## Overview

The old `FileHelper` class has been refactored into a comprehensive export system with clear separation of concerns:

- **Contracts**: Define interfaces for different export types
- **Managers**: Handle the actual file generation logic  
- **Concrete Implementations**: Specific exporters for different data types

## Architecture

### Contracts (`src/Contracts/Export/`)

1. **`ExportableFile`**: Base interface for all exportable files
   - `generate()`: Generate the file content
   - `serve()`: Serve the file for download
   - `getMimeType()`: Get MIME type
   - `getExtension()`: Get file extension

2. **`SpreadsheetFile`**: Interface for spreadsheet files (CSV/Excel)
   - Extends `ExportableFile`
   - `setSchema()`: Define data structure
   - `setData()`: Set the data to export
   - `toTabular()`: Convert to tabular format

3. **`PdfFile`**: Interface for PDF files
   - Extends `ExportableFile`
   - `setTemplate()`: Set template type (table/entry)
   - `generateHtml()`: Generate HTML for PDF conversion

### Managers (`src/Utils/Export/`)

1. **`CsvManager`**: Handles CSV file generation
   - Implements `SpreadsheetFile`
   - UTF-8 BOM support for Excel compatibility
   - Proper CSV escaping

2. **`XlsxManager`**: Handles XLSX file generation
   - Implements `SpreadsheetFile`
   - Creates proper XLSX structure using ZipArchive
   - Generates all required XML files

3. **`PdfManager`**: Handles PDF file generation
   - Implements `PdfFile`
   - Generates HTML for browser printing
   - Supports table and entry templates

### Concrete Implementations (`src/Utils/Export/Concrete/`)

#### For Gravity Flow Approved Entries:
1. **`GravityApprovedEntriesCsv`**: CSV export for approved entries
2. **`GravityApprovedEntriesXlsx`**: XLSX export for approved entries  
3. **`GravityApprovedEntriesPdf`**: PDF export for approved entries

#### For Single Gravity Flow Entries:
1. **`GravitySingleEntryPdf`**: PDF export for single entry
2. **`GravitySingleEntryXlsx`**: XLSX export for single entry

## Usage Examples

### CSV Export
```php
use App\Utils\Export\Concrete\GravityApprovedEntriesCsv;

$csvExporter = new GravityApprovedEntriesCsv();
$result = $csvExporter->setEntriesData($entries)->generate();

if ($result['success']) {
    $csvExporter->serve($result['data'], $result['filename']);
}
```

### XLSX Export
```php
use App\Utils\Export\Concrete\GravityApprovedEntriesXlsx;

$xlsxExporter = new GravityApprovedEntriesXlsx();
$result = $xlsxExporter->setEntriesData($entries)->generate();

if ($result['success']) {
    $xlsxExporter->serve($result['data'], $result['filename']);
}
```

### PDF Export
```php
use App\Utils\Export\Concrete\GravityApprovedEntriesPdf;

$pdfExporter = new GravityApprovedEntriesPdf();
$result = $pdfExporter->setEntriesData($entries)->generate();

if ($result['success']) {
    $pdfExporter->serve($result['data'], $result['filename']);
}
```

### Single Entry Export
```php
use App\Utils\Export\Concrete\GravitySingleEntryPdf;

$pdfExporter = new GravitySingleEntryPdf($entry_id);
$result = $pdfExporter->setSingleEntryData($entry_data)->generate();

if ($result['success']) {
    $pdfExporter->serve($result['data'], $result['filename']);
}
```

## Benefits

1. **Separation of Concerns**: Each class has a single responsibility
2. **Extensibility**: Easy to add new export formats or data types
3. **Reusability**: Managers can be used for different data types
4. **Type Safety**: Clear interfaces define expected behavior
5. **Testability**: Each component can be tested independently

## Creating New Exporters

### For New Data Types:
1. Create concrete classes extending the appropriate manager
2. Define the schema in the constructor
3. Implement data formatting methods

### For New Export Formats:
1. Create a new manager implementing `ExportableFile`
2. Define format-specific interface if needed
3. Implement the generation and serving logic

## Migration from FileHelper

The old `FileHelper` class methods have been replaced:

| Old Method | New Implementation |
|------------|-------------------|
| `FileHelper::csv2Xlsx()` | `XlsxManager->generate()` |
| `FileHelper::csv2Pdf()` | `PdfManager->generate()` |  
| `FileHelper::entry2Pdf()` | `GravitySingleEntryPdf->generate()` |
| `FileHelper::serveXlsxDownload()` | `XlsxManager->serve()` |
| `FileHelper::servePdfDownload()` | `PdfManager->serve()` |

## Controller Changes

The `GravityController` has been updated to use the new export system:

- `exportCSV()`: Uses `GravityApprovedEntriesCsv`
- `exportXLSX()`: Uses `GravityApprovedEntriesXlsx`  
- `exportPDF()`: Uses `GravityApprovedEntriesPdf`
- `exportSingleEntryPDF()`: Uses `GravitySingleEntryPdf`
- `exportSingleEntryExcel()`: Uses `GravitySingleEntryXlsx`

## Service Changes

Added `getSingleEntryForExport()` method to `GravityService` for retrieving and formatting single entry data for export.

## Future Enhancements

1. **Caching**: Add file caching for large exports
2. **Background Processing**: Queue large exports for background processing
3. **Templates**: Allow custom PDF templates
4. **Compression**: Add ZIP export for multiple files
5. **Cloud Storage**: Direct export to cloud storage services
