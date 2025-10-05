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
    private array $selectedRows = [];
    private int $totalEntriesCount = 0;
    private string $filename = '';

    /**
     * Set column totals data
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
     * Set selected rows for filtering
     * @param array $selectedRows
     * @return self
     */
    public function setSelectedRows(array $selectedRows): self
    {
        $this->selectedRows = $selectedRows;
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
                    'message' => 'هیچ داده‌ای برای صادرات یافت نشد',
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

            // Filter column totals by selected rows (if any selected)
            $dataToExport = $this->columnTotals;
            if (!empty($this->selectedRows)) {
                $dataToExport = array_filter($this->columnTotals, function($columnName) {
                    return in_array($columnName, $this->selectedRows);
                }, ARRAY_FILTER_USE_KEY);
            }

            foreach ($dataToExport as $columnName => $total) {
                // Skip grand total in main data section
                if ($columnName === 'جمع کل') {
                    continue;
                }
                
                $csvData[] = [
                    $columnName,
                    number_format($total, 2)
                ];
            }

            // Add summary row if there's a grand total
            if (isset($this->columnTotals['جمع کل'])) {
                $csvData[] = ['', '']; // Empty row separator
                
                // Calculate grand total for selected rows only if filters applied
                $grandTotal = $this->columnTotals['جمع کل'];
                if (!empty($this->selectedRows)) {
                    $grandTotal = 0;
                    foreach ($this->selectedRows as $selectedRow) {
                        if (isset($this->columnTotals[$selectedRow]) && $selectedRow !== 'جمع کل') {
                            $grandTotal += $this->columnTotals[$selectedRow];
                        }
                    }
                }
                
                $csvData[] = [
                    'مجموع کل امتیازات',
                    number_format($grandTotal, 2)
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
                'message' => 'خطا در تولید فایل CSV: ' . $e->getMessage(),
                'data' => null,
                'filename' => null
            ];
        }
    }
}
