<?php

namespace App\Utils\Export\Concrete;

use App\Utils\Export\XlsxManager;

/**
 * Concrete XLSX implementation for single Gravity Flow entry
 */
class GravitySingleEntryXlsx extends XlsxManager
{
    protected int $entry_id;

    public function __construct(int $entry_id = 0)
    {
        $this->entry_id = $entry_id;
        $this->title = 'جزئیات ورودی #' . $entry_id;
        
        // Schema for single entry
        $this->schema = [
            'field' => 'فیلد',
            'value' => 'مقدار'
        ];
    }

    public function setEntryId(int $entry_id): self
    {
        $this->entry_id = $entry_id;
        $this->title = 'جزئیات ورودی #' . $entry_id;
        return $this;
    }

    public function setSingleEntryData(array $entry_data): self
    {
        $formatted_data = [];
        
        // Convert entry data to field-value pairs
        foreach ($entry_data as $field => $value) {
            $formatted_data[] = [
                'field' => $field,
                'value' => $value
            ];
        }
        
        $this->data = $formatted_data;
        return $this;
    }

    protected function generateFilename(): string
    {
        return 'entry-' . $this->entry_id . '-' . date('Y-m-d-H-i-s') . '.' . $this->getExtension();
    }
}
