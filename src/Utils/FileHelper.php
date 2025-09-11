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
}
