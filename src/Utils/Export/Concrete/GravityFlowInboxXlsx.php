<?php

namespace App\Utils\Export\Concrete;

use App\Utils\Export\XlsxManager;
use App\Contracts\Export\SpreadsheetFile;

/**
 * Concrete implementation for exporting Gravity Flow inbox entries to XLSX
 */
class GravityFlowInboxXlsx extends XlsxManager implements SpreadsheetFile
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
     * Generate XLSX content for inbox entries
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

            // Prepare worksheet data
            $worksheets = [
                'صندوق ورودی' => $this->prepareInboxWorksheet(),
                'آمار' => $this->prepareStatsWorksheet()
            ];

            $this->filename = 'gravity-flow-inbox-' . date('Y-m-d-H-i-s') . '.xlsx';

            return [
                'success' => true,
                'message' => 'فایل XLSX با موفقیت تولید شد',
                'data' => $worksheets,
                'filename' => $this->filename
            ];

        } catch (\Exception $e) {
            error_log('GravityFlowInboxXlsx Generate Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در تولید XLSX: ' . $e->getMessage(),
                'data' => null,
                'filename' => null
            ];
        }
    }

    /**
     * Prepare main inbox worksheet
     * @return array
     */
    private function prepareInboxWorksheet(): array
    {
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
            'تعداد اقدامات موجود'
        ];

        $data = [$headers];

        foreach ($this->inboxEntries as $entry) {
            $data[] = [
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
                count($entry['actions'] ?? [])
            ];
        }

        return $data;
    }

    /**
     * Prepare statistics worksheet
     * @return array
     */
    private function prepareStatsWorksheet(): array
    {
        $stats = $this->calculateStats();

        return [
            ['آمار صندوق ورودی گردش کاری', ''],
            ['', ''],
            ['آمار کلی', 'تعداد'],
            ['کل موارد', $stats['total']],
            ['در انتظار', $stats['pending']],
            ['در حال پردازش', $stats['in_progress']],
            ['نیاز به ورودی کاربر', $stats['user_input']],
            ['', ''],
            ['آمار اولویت', 'تعداد'],
            ['اولویت بالا', $stats['priority_high']],
            ['اولویت متوسط', $stats['priority_medium']],
            ['اولویت عادی', $stats['priority_normal']],
            ['', ''],
            ['آمار فرم‌ها', 'تعداد'],
            ...$stats['forms_breakdown']
        ];
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
            'priority_high' => 0,
            'priority_medium' => 0,
            'priority_normal' => 0,
            'forms_breakdown' => []
        ];

        $forms = [];

        foreach ($this->inboxEntries as $entry) {
            // Status stats
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

            // Priority stats
            $priority = $entry['priority'] ?? 1;
            switch ($priority) {
                case 3:
                    $stats['priority_high']++;
                    break;
                case 2:
                    $stats['priority_medium']++;
                    break;
                case 1:
                default:
                    $stats['priority_normal']++;
                    break;
            }

            // Forms breakdown
            $form_title = $entry['form_title'] ?? 'نامشخص';
            $forms[$form_title] = ($forms[$form_title] ?? 0) + 1;
        }

        foreach ($forms as $form_title => $count) {
            $stats['forms_breakdown'][] = [$form_title, $count];
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
     * Get MIME type for XLSX files
     * @return string
     */
    public function getMimeType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }
}
