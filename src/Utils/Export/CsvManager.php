<?php

namespace App\Utils\Export;

use App\Contracts\Export\SpreadsheetFile;
use Exception;

/**
 * CSV file manager
 */
class CsvManager implements SpreadsheetFile
{
    protected array $schema = [];
    protected array $data = [];
    protected string $title = 'Export';

    public function setSchema(array $schema): SpreadsheetFile
    {
        $this->schema = $schema;
        return $this;
    }

    public function setData(array $data): SpreadsheetFile
    {
        $this->data = $data;
        return $this;
    }

    public function setTitle(string $title): SpreadsheetFile
    {
        $this->title = $title;
        return $this;
    }

    public function getSchema(): array
    {
        return $this->schema;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toTabular(): array
    {
        $tabular = [];
        
        // Add headers if schema is provided
        if (!empty($this->schema)) {
            $headers = array_keys($this->schema);
            $tabular[] = $headers;
        }

        // Add data rows
        foreach ($this->data as $row) {
            if (is_array($row)) {
                $tabular[] = array_values($row);
            }
        }

        return $tabular;
    }

    public function generate(): array
    {
        try {
            $tabular_data = $this->toTabular();
            
            if (empty($tabular_data)) {
                return [
                    'success' => false,
                    'data' => null,
                    'filename' => null,
                    'message' => 'No data to export'
                ];
            }

            $filename = $this->generateFilename();

            return [
                'success' => true,
                'data' => $tabular_data,
                'filename' => $filename,
                'message' => 'CSV generated successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'filename' => null,
                'message' => 'Error generating CSV: ' . $e->getMessage()
            ];
        }
    }

    public function serve($data, string $filename): void
    {
        // Clean any output that might have been sent
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        // Add BOM for proper UTF-8 handling in Excel
        echo "\xEF\xBB\xBF";

        // Output CSV data directly without buffering
        foreach ($data as $row) {
            $line = '';
            $first = true;
            foreach ($row as $field) {
                if (!$first) {
                    $line .= ',';
                }
                // Escape quotes and wrap in quotes if needed
                if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
                    $line .= '"' . str_replace('"', '""', $field) . '"';
                } else {
                    $line .= $field;
                }
                $first = false;
            }
            echo $line . "\n";
        }

        // Force output and exit cleanly
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        exit();
    }

    public function getMimeType(): string
    {
        return 'text/csv; charset=utf-8';
    }

    public function getExtension(): string
    {
        return 'csv';
    }

    protected function generateFilename(): string
    {
        return 'export-' . date('Y-m-d-H-i-s') . '.' . $this->getExtension();
    }
}
