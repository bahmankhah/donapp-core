<?php

namespace App\Utils\Export\Concrete;

use App\Utils\Export\PdfManager;
use App\Contracts\Export\PdfFile;

class GravityApprovedEntriesPdf extends PdfManager implements PdfFile
{
    private array $entries = [];
    private string $filename = '';

    /**
     * Set entries list from controller
     */
    public function setEntriesData(array $entries): self
    {
        $this->entries = $entries;
        return $this;
    }

    /**
     * Generate the PDF output
     */
    public function generate(): array
    {
        try {
            if (empty($this->entries)) {
                return [
                    'success' => false,
                    'message' => 'هیچ داده تایید شده‌ای یافت نشد.',
                    'data' => null,
                    'filename' => null
                ];
            }

            $html = $this->generateHtml();
            $this->filename = 'gravity-approved-entries-' . date('Y-m-d-H-i-s') . '.pdf';

            return [
                'success' => true,
                'message' => 'PDF با موفقیت تولید شد.',
                'data' => $html,
                'filename' => $this->filename
            ];

        } catch (\Exception $e) {
            error_log("GravityApprovedEntriesPdf Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در تولید PDF: ' . $e->getMessage(),
                'data' => null,
                'filename' => null
            ];
        }
    }

    /**
     * Build full printable HTML
     */
    public function generateHtml(): string
    {
        $stats = $this->calculateStats();
        $now = date_i18n('Y/m/d H:i', strtotime(date('Y/m/d H:i')));

        $html = '<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
<meta charset="UTF-8">
<title>گزارش فرم‌های تأیید شده</title>
<style>

@font-face {
    font-family: "Vazir";
    src: url("https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/Vazir.woff2") format("woff2");
}

body {
    font-family: "Vazir", Arial, sans-serif;
    direction: rtl;
    margin: 40px;
    line-height: 1.7;
    color: #333;
    font-size: 13px;
}

.print-container {
    text-align: center;
    margin-top: 25px;
}
.print-button {
    background: #10b981;
    color: #fff;
    padding: 10px 22px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 14px;
}
.print-button:hover {
    background: #059669;
}
@media print {
    .print-container { display: none; }
}

/* Header */
.header {
    text-align: center;
    border-bottom: 3px solid #10b981;
    padding-bottom: 15px;
    margin-bottom: 30px;
}
.header h1 {
    font-size: 22px;
    margin: 0;
    color: #10b981;
}
.header .date {
    color: #777;
    font-size: 14px;
    margin-top: 8px;
}

/* Stats */
.stats-box {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
}
.stats-title {
    color: #059669;
    font-size: 15px;
    font-weight: bold;
    margin-bottom: 10px;
}
.stats-grid {
    display: flex;
    justify-content: space-between;
}
.stat-item {
    text-align: center;
    padding: 10px;
    min-width: 100px;
}
.stat-number {
    color: #059669;
    font-size: 22px;
    font-weight: bold;
}
.stat-label {
    font-size: 11px;
    color: #555;
}

/* Table */
.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
.table th {
    background: #10b981;
    color: #fff;
    padding: 12px;
    font-size: 12px;
}
.table td {
    padding: 10px;
    font-size: 11px;
    border: 1px solid #e5e7eb;
}
.table tr:nth-child(even) {
    background: #f9fafb;
}

/* Status badge */
.status-approved {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
    padding: 4px 10px;
    border-radius: 14px;
    font-size: 10px;
    font-weight: bold;
    display: inline-block;
    min-width: 70px;
}

/* Summary */
.field-preview {
    font-size: 10px;
    line-height: 1.4;
}

/* Footer */
.footer {
    text-align: center;
    margin-top: 40px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
    color: #666;
    font-size: 11px;
}
</style>
</head>
<body>

<div class="header">
    <h1>گزارش فرم‌های تأیید شده</h1>
    <div class="date">تاریخ تولید گزارش: ' . $now . '</div>
</div>

<div class="stats-box">
    <div class="stats-title">آمار کلی</div>
    <div class="stats-grid">
        <div class="stat-item">
            <span class="stat-number">' . $stats['total'] . '</span>
            <div class="stat-label">تعداد کل</div>
        </div>

        <div class="stat-item">
            <span class="stat-number">' . $stats['forms_count'] . '</span>
            <div class="stat-label">فرم‌های مختلف</div>
        </div>

        <div class="stat-item">
            <span class="stat-number">' . $stats['fields_count'] . '</span>
            <div class="stat-label">میانگین تعداد فیلد</div>
        </div>
    </div>
</div>

<table class="table">
<thead>
<tr>
    <th style="width:8%;">شناسه</th>
    <th style="width:18%;">عنوان فرم</th>
    <th style="width:12%;">تاریخ ایجاد</th>
    <th style="width:10%;">وضعیت</th>
    <th style="width:52%;">خلاصه اطلاعات</th>
</tr>
</thead>
<tbody>';

foreach ($this->entries as $entry) {

    // Mini preview similar to your UI
    $preview = '';
    if (!empty($entry['entry_data'])) {
        $slice = array_slice($entry['entry_data'], 0, 2);
        foreach ($slice as $f) {
            $preview .= '<div><strong>' . htmlspecialchars($f['label']) . ':</strong> ' .
                        wp_kses_post($f['value']) . '</div>';
        }
        if (count($entry['entry_data']) > 2) {
            $preview .= '<small style="color:#6b7280">+' . (count($entry['entry_data']) - 2) . ' فیلد دیگر...</small>';
        }
    } else {
        $preview = '<em style="color:#999">بدون اطلاعات</em>';
    }

    $html .= '
    <tr>
        <td>' . $entry['id'] . '</td>
        <td>
            <strong>' . htmlspecialchars($entry['form_title']) . '</strong>
            <br><small style="color:#64748b;">فرم شماره: ' . $entry['form_id'] . '</small>
        </td>
        <td>' . date('Y/m/d H:i', strtotime($entry['date_created'])) . '</td>
        <td><span class="status-approved">تأیید شده</span></td>
        <td class="field-preview">' . $preview . '</td>
    </tr>';
}

$html .= '
</tbody>
</table>

<div class="footer">
این گزارش به صورت خودکار تولید شده است — تعداد کل: ' . count($this->entries) . ' فرم
</div>

<div class="print-container">
    <button class="print-button" onclick="window.print()">چاپ گزارش</button>
</div>

</body>
</html>';

        return $html;
    }

    /**
     * Statistics summary
     */
    private function calculateStats(): array
    {
        return [
            'total'       => count($this->entries),
            'forms_count' => count(array_unique(array_column($this->entries, 'form_id'))),
            'fields_count' => $this->avgFieldCount()
        ];
    }

    private function avgFieldCount(): int
    {
        if (empty($this->entries)) return 0;

        $total = 0; $count = 0;

        foreach ($this->entries as $e) {
            if (!empty($e['entry_data'])) {
                $total += count($e['entry_data']);
                $count++;
            }
        }
        return $count ? round($total / $count) : 0;
    }

    public function getMimeType(): string
    {
        return 'application/pdf';
    }
}
