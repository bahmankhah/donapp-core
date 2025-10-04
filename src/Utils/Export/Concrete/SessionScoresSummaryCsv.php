<?php

namespace App\Utils\Export\Concrete;

use App\Utils\Export\CsvManager;
use App\Contracts\Export\ExportableFile;

/**
 * Concrete implementation for exporting Session Scores Summary to CSV
 */
class SessionScoresSummaryCsv extends CsvManager implements ExportableFile
{
    private array $columnTotals = [];
    private int $totalEntriesCount = 0;
    private string $filename = '';

    /**
     * Set column totals data for summary export
     * @param array $columnTotals
     * @return self
     */
    public function setColumnTotalsData(array $columnTotals): self
    {
        $this->columnTotals = $columnTotals;
        return $this;
    }

    /**
     * Set total entries count
     * @param int $count
     * @return self
     */
    public function setTotalEntriesCount(int $count): self
    {
        $this->totalEntriesCount = $count;
        return $this;
    }

    /**
     * Generate CSV content for session scores summary
     * @return array
     */
    public function generate(): array
    {
        try {
            if (empty($this->columnTotals)) {
                return [
                    'success' => false,
                    'message' => 'هیچ داده‌ای برای صادرات خلاصه یافت نشد',
                    'data' => null,
                    'filename' => null
                ];
            }

            // Prepare CSV headers (exactly like the table)
            $headers = [
                'نام ستون',
                'مجموع'
            ];

            // Prepare CSV data
            $csvData = [$headers];

            foreach ($this->columnTotals as $columnName => $total) {
                $csvData[] = [
                    $columnName,
                    number_format($total, 2)
                ];
            }

            // Add summary row if there's a grand total
            if (isset($this->columnTotals['جمع کل'])) {
                $csvData[] = ['', '']; // Empty row separator
                $csvData[] = [
                    'مجموع کل امتیازها',
                    number_format($this->columnTotals['جمع کل'], 2)
                ];
            }

            $this->filename = 'session-scores-summary-' . date('Y-m-d-H-i-s') . '.csv';

            return [
                'success' => true,
                'message' => 'فایل CSV خلاصه امتیازات با موفقیت تولید شد',
                'data' => $csvData,
                'filename' => $this->filename
            ];

        } catch (\Exception $e) {
            error_log('SessionScoresSummaryCsv Generate Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در تولید CSV خلاصه: ' . $e->getMessage(),
                'data' => null,
                'filename' => null
            ];
        }
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
