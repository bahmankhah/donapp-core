<?php

namespace App\Utils;

use Exception;
use ZipArchive;
use DOMDocument;

class FileHelper
{
    /**
     * Convert CSV data array to XLSX format and return as binary data
     * @param array $csv_data Array of arrays containing CSV data
     * @param string $sheet_name Name of the worksheet (optional)
     * @return array ['success' => bool, 'data' => string, 'filename' => string, 'message' => string]
     */
    public static function csv2Xlsx($csv_data, $sheet_name = 'Sheet1')
    {
        try {
            // Check if ZipArchive is available
            if (!class_exists('ZipArchive')) {
                return [
                    'success' => false,
                    'data' => null,
                    'filename' => null,
                    'message' => 'ZipArchive extension is not available'
                ];
            }

            // Create temporary file for the XLSX
            $temp_file = tempnam(sys_get_temp_dir(), 'xlsx_');
            // Create ZIP archive
            $zip = new ZipArchive();
            if ($zip->open($temp_file, ZipArchive::CREATE) !== TRUE) {
                return [
                    'success' => false,
                    'data' => null,
                    'filename' => null,
                    'message' => 'Cannot create ZIP archive'
                ];
            }

            // Generate and add all required XML files
            self::addContentTypesXml($zip);
            self::addRelsXml($zip);
            self::addWorkbookXml($zip, $sheet_name);
            self::addWorkbookRelsXml($zip);
            self::addWorksheetXml($zip, $csv_data);
            self::addSharedStringsXml($zip, $csv_data);

            // Close the ZIP
            $zip->close();

            // Read the generated file
            $xlsx_data = file_get_contents($temp_file);
            // Clean up temporary file
            unlink($temp_file);

            // Generate filename
            $filename = 'export-' . date('Y-m-d-H-i-s') . '.xlsx';

            return [
                'success' => true,
                'data' => $xlsx_data,
                'filename' => $filename,
                'message' => 'XLSX generated successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'filename' => null,
                'message' => 'Error generating XLSX: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Add [Content_Types].xml to the ZIP
     */
    private static function addContentTypesXml($zip)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $xml .= '<Default Extension="xml" ContentType="application/xml"/>';
        $xml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $xml .= '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $xml .= '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>';
        $xml .= '</Types>';
        $zip->addFromString('[Content_Types].xml', $xml);
    }

    /**
     * Add _rels/.rels to the ZIP
     */
    private static function addRelsXml($zip)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>';
        $xml .= '</Relationships>';
        $zip->addFromString('_rels/.rels', $xml);
    }

    /**
     * Add xl/workbook.xml to the ZIP
     */
    private static function addWorkbookXml($zip, $sheet_name)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheets>';
        $xml .= '<sheet name="' . htmlspecialchars($sheet_name, ENT_XML1, 'UTF-8') . '" sheetId="1" r:id="rId1"/>';
        $xml .= '</sheets>';
        $xml .= '</workbook>';
        $zip->addFromString('xl/workbook.xml', $xml);
    }

    /**
     * Add xl/_rels/workbook.xml.rels to the ZIP
     */
    private static function addWorkbookRelsXml($zip)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>';
        $xml .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';
        $xml .= '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $xml);
    }

    /**
     * Add xl/worksheets/sheet1.xml to the ZIP
     */
    private static function addWorksheetXml($zip, $csv_data)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheetData>';

        $row_num = 1;
        foreach ($csv_data as $row_index => $row) {
            $xml .= '<row r="' . $row_num . '">';
            $col_num = 1;
            foreach ($row as $cell_value) {
                $col_letter = self::getColumnLetter($col_num);
                $cell_ref = $col_letter . $row_num;

                // Determine if this is a header row (first row)
                $is_header = ($row_index === 0);

                // For simplicity, treat all values as inline strings
                $xml .= '<c r="' . $cell_ref . '" t="inlineStr"';
                if ($is_header) {
                    $xml .= ' s="1"'; // Apply header style
                }
                $xml .= '>';
                $xml .= '<is><t>' . htmlspecialchars($cell_value, ENT_XML1, 'UTF-8') . '</t></is>';
                $xml .= '</c>';

                $col_num++;
            }

            $xml .= '</row>';
            $row_num++;
        }

        $xml .= '</sheetData>';
        $xml .= '</worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
    }

    /**
     * Add xl/sharedStrings.xml to the ZIP (minimal implementation)
     */
    private static function addSharedStringsXml($zip, $csv_data)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="0" uniqueCount="0">';
        $xml .= '</sst>';
        $zip->addFromString('xl/sharedStrings.xml', $xml);
    }

    /**
     * Convert column number to Excel column letter (A, B, C, ..., AA, AB, etc.)
     */
    private static function getColumnLetter($col_num)
    {
        $letter = '';
        while ($col_num > 0) {
            $col_num--;
            $letter = chr(65 + ($col_num % 26)) . $letter;
            $col_num = intval($col_num / 26);
        }
        return $letter;
    }

    /**
     * Serve XLSX data as download
     * @param string $xlsx_data Binary XLSX data
     * @param string $filename Filename for download
     */
    public static function serveXlsxDownload($xlsx_data, $filename)
    {
        // Clean any output that might have been sent
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Set headers for XLSX download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . strlen($xlsx_data));

        // Output the XLSX data
        echo $xlsx_data;

        // Force output and exit cleanly
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        exit();
    }

    /**
     * Convert CSV data array to PDF format and return as binary data
     * @param array $csv_data Array of arrays containing CSV data
     * @param string $title Title for the PDF document
     * @return array ['success' => bool, 'data' => string, 'filename' => string, 'message' => string]
     */
    public static function csv2Pdf($csv_data, $title = 'Export Data')
    {
        try {
            // Simple HTML-based PDF generation using browser print functionality
            // This creates an HTML table that can be printed as PDF

            $html = self::generatePdfHtml($csv_data, $title);

            // For a more robust solution, you might want to use a PDF library like TCPDF or mPDF
            // For now, we'll use a simple HTML approach that can be printed as PDF

            return [
                'success' => true,
                'data' => $html,
                'filename' => 'export-' . date('Y-m-d-H-i-s') . '.pdf',
                'message' => 'PDF HTML generated successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'filename' => null,
                'message' => 'Error generating PDF: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Convert single entry data to PDF format
     * @param array $entry_data Entry data
     * @param string $title Title for the PDF document
     * @return array ['success' => bool, 'data' => string, 'filename' => string, 'message' => string]
     */
    public static function entry2Pdf($entry_data, $title = 'Entry Details')
    {
        try {
            $html = self::generateEntryPdfHtml($entry_data, $title);

            return [
                'success' => true,
                'data' => $html,
                'filename' => 'entry-' . date('Y-m-d-H-i-s') . '.pdf',
                'message' => 'Entry PDF HTML generated successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'filename' => null,
                'message' => 'Error generating entry PDF: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate HTML content for PDF from CSV data
     * @param array $csv_data CSV data array
     * @param string $title Document title
     * @return string HTML content
     */
    private static function generatePdfHtml($csv_data, $title)
    {
        $html = '<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Vazir:wght@300;400;500;600;700&display=swap");
        body {
            font-family: "Vazir", "Tahoma", sans-serif;
            margin: 20px;
            direction: rtl;
            background: white;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #0073aa;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header .date {
            color: #666;
            margin-top: 10px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }
        th {
            background-color: #0073aa;
            color: white;
            padding: 12px 8px;
            border: 1px solid #005177;
            font-weight: 600;
            text-align: center;
        }
        td {
            padding: 10px 8px;
            border: 1px solid #ddd;
            text-align: center;
            background-color: #fff;
        }
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tbody tr:hover {
            background-color: #f0f8ff;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        @media print {
            body { margin: 10px; }
            .header h1 { font-size: 20px; }
            table { font-size: 10px; }
            th, td { padding: 6px 4px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . htmlspecialchars($title) . '</h1>
        <div class="date">تاریخ تولید: ' . date('Y/m/d H:i:s') . '</div>
    </div>
    
    <table>';

        // Add table headers
        if (!empty($csv_data)) {
            $html .= '<thead><tr>';
            foreach ($csv_data[0] as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr></thead><tbody>';

            // Add table rows (skip first row as it's headers)
            for ($i = 1; $i < count($csv_data); $i++) {
                $html .= '<tr>';
                foreach ($csv_data[$i] as $cell) {
                    $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody>';
        }

        $html .= '</table>
    
    <div class="footer">
        <p>تولید شده توسط سیستم دناپ - ' . date('Y') . '</p>
    </div>
    
    <script>
        // Auto-print functionality for PDF generation
        window.addEventListener("load", function() {
            if (window.location.search.includes("auto_print=1")) {
                setTimeout(function() {
                    window.print();
                }, 1000);
            }
        });
    </script>
</body>
</html>';

        return $html;
    }

    /**
     * Generate HTML content for single entry PDF
     * @param array $entry_data Entry data
     * @param string $title Document title
     * @return string HTML content
     */
    private static function generateEntryPdfHtml($entry_data, $title)
    {
        $html = '<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Vazir:wght@300;400;500;600;700&display=swap");
        body {
            font-family: "Vazir", "Tahoma", sans-serif;
            margin: 20px;
            direction: rtl;
            background: white;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #0073aa;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header .date {
            color: #666;
            margin-top: 10px;
            font-size: 14px;
        }
        .entry-details {
            margin-top: 20px;
        }
        .field {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fafafa;
        }
        .field:nth-child(even) {
            background-color: #f0f8ff;
        }
        .field-label {
            font-weight: 600;
            color: #0073aa;
            min-width: 150px;
        }
        .field-value {
            flex: 1;
            text-align: left;
            padding-right: 20px;
            word-wrap: break-word;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        @media print {
            body { margin: 10px; }
            .header h1 { font-size: 20px; }
            .field { padding: 10px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . htmlspecialchars($title) . '</h1>
        <div class="date">تاریخ تولید: ' . date('Y/m/d H:i:s') . '</div>
    </div>
    
    <div class="entry-details">';

        // Add entry fields
        foreach ($entry_data as $field => $value) {
            $html .= '<div class="field">
                <div class="field-label">' . htmlspecialchars($field) . ':</div>
                <div class="field-value">' . htmlspecialchars($value) . '</div>
            </div>';
        }

        $html .= '</div>
    
    <div class="footer">
        <p>تولید شده توسط سیستم دناپ - ' . date('Y') . '</p>
    </div>
    
    <script>
        // Auto-print functionality for PDF generation
        window.addEventListener("load", function() {
            if (window.location.search.includes("auto_print=1")) {
                setTimeout(function() {
                    window.print();
                }, 1000);
            }
        });
    </script>
</body>
</html>';

        return $html;
    }

    /**
     * Serve PDF data as download (HTML format for printing)
     * @param string $html_data HTML content
     * @param string $filename Filename for download
     */
    public static function servePdfDownload($html_data, $filename)
    {
        // Clean any output that might have been sent
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Set headers for HTML display (user can print as PDF)
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: inline; filename="' . str_replace('.pdf', '.html', $filename) . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        // Add auto-print parameter to URL for automatic PDF generation
        $html_with_auto_print = str_replace(
            '</script>',
            'if (!window.location.search.includes("auto_print=1")) {
                window.location.search += (window.location.search ? "&" : "?") + "auto_print=1";
            }</script>',
            $html_data
        );

        // Output the HTML data
        echo $html_with_auto_print;

        // Force output and exit cleanly
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        exit();
    }
}
