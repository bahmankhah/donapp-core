<?php

namespace App\Utils\Export\Concrete;

use App\Utils\Export\CsvManager;
use App\Contracts\Export\ExportableFile;

/**
 * Concrete implementation for exporting Gravity Flow inbox entries to CSV
 */
class GravityFlowInboxCsv extends CsvManager implements ExportableFile
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
     * Generate CSV content for inbox entries
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

            // Prepare CSV headers
            $headers = [
                'شناسه ورودی',
                'شناسه فرم', 
                'عنوان فرم',
                'نام مرحله',
                'نوع مرحله',
                'ارسال‌کننده',
                'ایمیل ارسال‌کننده',
                'تاریخ ایجاد',
                'وضعیت',
                'اولویت',
                'مهلت',
                'خلاصه محتوا',
                'لینک مشاهده'
            ];

            // Prepare CSV data
            $csvData = [$headers];

            foreach ($this->inboxEntries as $entry) {
                $csvData[] = [
                    $entry['id'] ?? '',
                    $entry['form_id'] ?? '',
                    $entry['form_title'] ?? '',
                    $entry['step_name'] ?? '',
                    $entry['step_type'] ?? '',
                    $entry['submitter']['name'] ?? '',
                    $entry['submitter']['email'] ?? '',
                    date('Y/m/d H:i', strtotime($entry['date_created'] ?? 'now')),
                    $entry['status'] ?? '',
                    $this->getPriorityLabel($entry['priority'] ?? 1),
                    $entry['due_date'] ?? 'تعیین نشده',
                    strip_tags($entry['entry_summary'] ?? ''),
                    $entry['entry_url'] ?? ''
                ];
            }

            $this->filename = 'gravity-flow-inbox-' . date('Y-m-d-H-i-s') . '.csv';

            return [
                'success' => true,
                'message' => 'فایل CSV با موفقیت تولید شد',
                'data' => $csvData,
                'filename' => $this->filename
            ];

        } catch (\Exception $e) {
            error_log('GravityFlowInboxCsv Generate Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در تولید CSV: ' . $e->getMessage(),
                'data' => null,
                'filename' => null
            ];
        }
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
     * Get MIME type for CSV files
     * @return string
     */
    public function getMimeType(): string
    {
        return 'text/csv';
    }
}
