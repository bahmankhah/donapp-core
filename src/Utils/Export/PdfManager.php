<?php

namespace App\Utils\Export;

use App\Contracts\Export\PdfFile;
use Exception;

/**
 * PDF file manager
 */
class PdfManager implements PdfFile
{
    protected array $data = [];
    protected string $title = 'Export';
    protected string $template = 'table';

    public function setData(array $data): PdfFile
    {
        $this->data = $data;
        return $this;
    }

    public function setTitle(string $title): PdfFile
    {
        $this->title = $title;
        return $this;
    }

    public function setTemplate(string $template): PdfFile
    {
        $this->template = $template;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function generate(): array
    {
        try {
            if (empty($this->data)) {
                return [
                    'success' => false,
                    'data' => null,
                    'filename' => null,
                    'message' => 'No data to export'
                ];
            }

            $html = $this->generateHtml();
            $filename = $this->generateFilename();

            return [
                'success' => true,
                'data' => $html,
                'filename' => $filename,
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

    public function generateHtml(): string
    {
        switch ($this->template) {
            case 'entry':
                return $this->generateEntryHtml();
            case 'table':
            default:
                return $this->generateTableHtml();
        }
    }

    public function serve($pdfBinary, string $filename): void
    {
        // Clean output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfBinary));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: public');

        echo $pdfBinary;

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        exit;
    }

    public function getMimeType(): string
    {
        return 'application/pdf';
    }

    public function getExtension(): string
    {
        return 'pdf';
    }

    protected function generateFilename(): string
    {
        return 'export-' . date('Y-m-d-H-i-s') . '.' . $this->getExtension();
    }

    /**
     * Generate HTML content for table-style PDF
     */
    private function generateTableHtml(): string
    {
        $html = '<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($this->title) . '</title>
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
        <h1>' . htmlspecialchars($this->title) . '</h1>
        <div class="date">تاریخ تولید: ' . date('Y/m/d H:i:s') . '</div>
    </div>
    
    <table>';

        // Add table content
        if (!empty($this->data)) {
            $html .= '<thead><tr>';
            // Assume first row is headers
            foreach ($this->data[0] as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr></thead><tbody>';

            // Add table rows (skip first row as it's headers)
            for ($i = 1; $i < count($this->data); $i++) {
                $html .= '<tr>';
                foreach ($this->data[$i] as $cell) {
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
     */
    private function generateEntryHtml(): string
    {
        $html = '<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($this->title) . '</title>
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
        <h1>' . htmlspecialchars($this->title) . '</h1>
        <div class="date">تاریخ تولید: ' . date('Y/m/d H:i:s') . '</div>
    </div>
    
    <div class="entry-details">';

        // Add entry fields
        foreach ($this->data as $field => $value) {
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
}
