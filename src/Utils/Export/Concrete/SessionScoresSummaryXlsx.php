<?php

namespace App\Utils\Export\Concrete;

use App\Utils\Export\XlsxManager;
use App\Contracts\Export\SpreadsheetFile;

/**
 * Concrete implementation for exporting Session Scores Summary to XLSX
 */
class SessionScoresSummaryXlsx extends XlsxManager implements SpreadsheetFile
{
    private array $columnTotals = [];
    private int $totalEntriesCount = 0;
    private string $filename = '';
    private array $selectedRows = [];

    /**
     * Constructor - Set up default schema and title
     */
    public function __construct()
    {
        // Define the schema for session scores summary (exactly like the table)
        $this->schema = [
            'column_name' => 'نام ستون',
            'total_score' => 'مجموع'
        ];
        
        $this->title = 'خلاصه امتیازات جلسات';
    }

    /**
     * Set column totals data for summary export
     * @param array $columnTotals
     * @return self
     */
    public function setColumnTotalsData(array $columnTotals): self
    {
        $this->columnTotals = $columnTotals;
        $this->prepareFormattedData();
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
        $this->prepareFormattedData();
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
        $this->prepareFormattedData();
        return $this;
    }

    /**
     * Prepare formatted data for XLSX export
     * @return void
     */
    private function prepareFormattedData(): void
    {
        if (empty($this->columnTotals)) {
            return;
        }

        $formatted_data = [];
        
        foreach ($this->columnTotals as $columnName => $total) {
            // Skip grand total for individual columns section
            if ($columnName === 'جمع کل') {
                continue;
            }

            // Filter by selected rows if any selected
            if (!empty($this->selectedRows) && !in_array($columnName, $this->selectedRows)) {
                continue;
            }
            
            $formatted_data[] = [
                'column_name' => $columnName,
                'total_score' => number_format($total, 2)
            ];
        }

        // Add summary row if there's a grand total
        if (isset($this->columnTotals['جمع کل'])) {
            // Add empty row separator
            $formatted_data[] = [
                'column_name' => '',
                'total_score' => ''
            ];

            // Calculate grand total for selected rows only if filters applied
            $grandTotal = $this->columnTotals['جمع کل'];
            if (!empty($this->selectedRows)) {
                $grandTotal = 0;
                foreach ($this->selectedRows as $selectedRow) {
                    if (isset($this->columnTotals[$selectedRow])) {
                        $grandTotal += $this->columnTotals[$selectedRow];
                    }
                }
            }

            // Add grand total row
            $formatted_data[] = [
                'column_name' => 'مجموع کل امتیازها',
                'total_score' => number_format($grandTotal, 2)
            ];
        }
        
        $this->data = $formatted_data;
    }

    /**
     * Generate XLSX content for session scores summary
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

            // Ensure data is prepared
            $this->prepareFormattedData();

            // Use parent generate method
            $result = parent::generate();

            if ($result['success']) {
                $result['filename'] = $this->generateFilename();
                $result['message'] = 'فایل XLSX خلاصه امتیازات با موفقیت تولید شد';
            }

            return $result;

        } catch (\Exception $e) {
            error_log('SessionScoresSummaryXlsx Generate Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در تولید XLSX خلاصه: ' . $e->getMessage(),
                'data' => null,
                'filename' => null
            ];
        }
    }

    /**
     * Generate filename for the export
     * @return string
     */
    protected function generateFilename(): string
    {
        return 'session-scores-summary-' . date('Y-m-d-H-i-s') . '.' . $this->getExtension();
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
