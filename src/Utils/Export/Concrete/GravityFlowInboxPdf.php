<?php

namespace App\Utils\Export\Concrete;

use App\Utils\Export\PdfManager;
use App\Contracts\Export\PdfFile;

/**
 * Concrete implementation for exporting Gravity Flow inbox entries to PDF
 */
class GravityFlowInboxPdf extends PdfManager implements PdfFile
{
    private array $inboxEntries = [];
    private string $filename = '';

    /**
     * Set inbox entries data
     * @param array $entries
     * @return self
     */
    public function setInboxEntriesData(array $entries): self
    {
        $this->inboxEntries = $entries;
        return $this;
    }

    /**
     * Generate PDF content for inbox entries
     * @return array
     */
    public function generate(): array
    {
        try {
            if (empty($this->inboxEntries)) {
                return [
                    'success' => false,
                    'message' => 'هیچ داده‌ای برای صادرات یافت نشد',
                    'data' => null,
                    'filename' => null
                ];
            }

            $html = $this->generateHtmlContent();
            $this->filename = 'gravity-flow-inbox-' . date('Y-m-d-H-i-s') . '.pdf';

            return [
                'success' => true,
                'message' => 'فایل PDF با موفقیت تولید شد',
                'data' => $html,
                'filename' => $this->filename
            ];

        } catch (\Exception $e) {
            error_log('GravityFlowInboxPdf Generate Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در تولید PDF: ' . $e->getMessage(),
                'data' => null,
                'filename' => null
            ];
        }
    }

    /**
     * Generate HTML content for PDF
     * @return string
     */
    private function generateHtmlContent(): string
    {
        $stats = $this->calculateStats();
        $current_date = date('Y/m/d H:i');

        $html = '<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <title>گزارش صندوق ورودی گردش کاری</title>
    <style>
        @font-face {
            font-family: "Vazir";
            src: url("https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/Vazir.woff2") format("woff2");
        }
        
        body {
            font-family: "Vazir", Arial, sans-serif;
            direction: rtl;
            margin: 40px;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        .print-container {
            text-align: center;
            margin-top: 30px;
        }

        .print-button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 25px;
            font-size: 14px;
            border-radius: 6px;
            cursor: pointer;
        }

        .print-button:hover {
            background: #5a67d8;
        }

        @media print {
            .print-container {
                display: none;
            }
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #667eea;
            font-size: 24px;
            margin: 0;
        }
        
        .header .date {
            color: #666;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .stats-section {
            margin-bottom: 30px;
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .stats-title {
            font-size: 16px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 15px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 5px;
        }
        
        .stats-grid {
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        
        .stat-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            min-width: 100px;
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
            display: block;
        }
        
        .stat-label {
            font-size: 11px;
            color: #64748b;
            margin-top: 5px;
        }
        
        .entries-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }
        
        .entries-table th {
            background: #667eea;
            color: white;
            padding: 12px 8px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #5a67d8;
            font-size: 11px;
        }
        
        .entries-table td {
            padding: 10px 8px;
            border: 1px solid #e2e8f0;
            text-align: center;
            font-size: 10px;
            vertical-align: top;
        }
        
        .entries-table tr:nth-child(even) {
            background: #f8fafc;
        }
        
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            display: inline-block;
            min-width: 60px;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-in-progress {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-user-input {
            background: #f3e8ff;
            color: #7c3aed;
        }
        
        .priority-high {
            color: #ef4444;
            font-weight: bold;
        }
        
        .priority-medium {
            color: #f59e0b;
            font-weight: bold;
        }
        
        .priority-normal {
            color: #10b981;
        }
        
        .summary {
            max-width: 200px;
            word-wrap: break-word;
            font-size: 9px;
            line-height: 1.3;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>گزارش صندوق ورودی گردش کاری</h1>
        <div class="date">تاریخ تولید گزارش: ' . $current_date . '</div>
    </div>
    
    <div class="stats-section">
        <div class="stats-title">آمار کلی</div>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-number">' . $stats['total'] . '</span>
                <div class="stat-label">کل موارد</div>
            </div>
            <div class="stat-item">
                <span class="stat-number">' . $stats['pending'] . '</span>
                <div class="stat-label">در انتظار</div>
            </div>
            <div class="stat-item">
                <span class="stat-number">' . $stats['in_progress'] . '</span>
                <div class="stat-label">در حال پردازش</div>
            </div>
            <div class="stat-item">
                <span class="stat-number">' . $stats['user_input'] . '</span>
                <div class="stat-label">نیاز به ورودی</div>
            </div>
        </div>
    </div>';

        if (!empty($this->inboxEntries)) {
            $html .= '
    <table class="entries-table">
        <thead>
            <tr>
                <th style="width: 5%;">ردیف</th>
                <th style="width: 15%;">فرم</th>
                <th style="width: 12%;">مرحله</th>
                <th style="width: 12%;">ارسال‌کننده</th>
                <th style="width: 10%;">تاریخ</th>
                <th style="width: 10%;">وضعیت</th>
                <th style="width: 8%;">اولویت</th>
                <th style="width: 10%;">مهلت</th>
                <th style="width: 18%;">خلاصه</th>
            </tr>
        </thead>
        <tbody>';

            foreach ($this->inboxEntries as $index => $entry) {
                $priority_class = $this->getPriorityClass($entry['priority'] ?? 1);
                $status_class = $this->getStatusClass($entry['status'] ?? '');
                
                $html .= '
            <tr>
                <td>' . ($index + 1) . '</td>
                <td>
                    <strong>' . htmlspecialchars($entry['form_title'] ?? '') . '</strong>
                    <br><small>ID: ' . ($entry['form_id'] ?? '') . '</small>
                </td>
                <td>
                    ' . htmlspecialchars($entry['step_name'] ?? '') . '
                    <br><small>' . htmlspecialchars($entry['step_type'] ?? '') . '</small>
                </td>
                <td>
                    <strong>' . htmlspecialchars($entry['submitter']['name'] ?? '') . '</strong>
                    ' . (!empty($entry['submitter']['email']) ? '<br><small>' . htmlspecialchars($entry['submitter']['email']) . '</small>' : '') . '
                </td>
                <td>' . date('Y/m/d', strtotime($entry['date_created'] ?? 'now')) . '</td>
                <td>
                    <span class="status-badge ' . $status_class . '">' . htmlspecialchars($entry['status'] ?? '') . '</span>
                </td>
                <td class="' . $priority_class . '">' . $this->getPriorityLabel($entry['priority'] ?? 1) . '</td>
                <td>' . ($entry['due_date'] ?? '-') . '</td>
                <td class="summary">' . htmlspecialchars(wp_trim_words(strip_tags($entry['entry_summary'] ?? ''), 15)) . '</td>
            </tr>';
            }

            $html .= '
        </tbody>
    </table>';
        }

        $html .= '
    
    <div class="footer">
        این گزارش به صورت خودکار از سیستم مدیریت گردش کاری تولید شده است.<br>
        تعداد کل ورودی‌ها: ' . count($this->inboxEntries) . ' مورد
    </div>
    <div class="print-container">
    <button class="print-button" onclick="window.print()">چاپ گزارش</button>
</div>
</body>
</html>';

        return $html;
    }

    /**
     * Calculate statistics from inbox entries
     * @return array
     */
    private function calculateStats(): array
    {
        $stats = [
            'total' => count($this->inboxEntries),
            'pending' => 0,
            'in_progress' => 0,
            'user_input' => 0,
        ];

        foreach ($this->inboxEntries as $entry) {
            $status = $entry['status'] ?? '';
            switch ($status) {
                case 'در انتظار':
                    $stats['pending']++;
                    break;
                case 'در حال پردازش':
                    $stats['in_progress']++;
                    break;
                case 'نیاز به ورودی کاربر':
                    $stats['user_input']++;
                    break;
            }
        }

        return $stats;
    }

    /**
     * Get priority label
     * @param int $priority
     * @return string
     */
    private function getPriorityLabel(int $priority): string
    {
        $labels = [
            1 => 'عادی',
            2 => 'متوسط',
            3 => 'بالا'
        ];

        return $labels[$priority] ?? 'عادی';
    }

    /**
     * Get priority CSS class
     * @param int $priority
     * @return string
     */
    private function getPriorityClass(int $priority): string
    {
        $classes = [
            1 => 'priority-normal',
            2 => 'priority-medium',
            3 => 'priority-high'
        ];

        return $classes[$priority] ?? 'priority-normal';
    }

    /**
     * Get status CSS class
     * @param string $status
     * @return string
     */
    private function getStatusClass(string $status): string
    {
        $classes = [
            'در انتظار' => 'status-pending',
            'در حال پردازش' => 'status-in-progress',
            'نیاز به ورودی کاربر' => 'status-user-input'
        ];

        return $classes[$status] ?? 'status-pending';
    }

    /**
     * Get MIME type for PDF files
     * @return string
     */
    public function getMimeType(): string
    {
        return 'application/pdf';
    }
}
