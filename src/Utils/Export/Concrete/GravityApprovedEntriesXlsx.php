<?php

namespace App\Utils\Export\Concrete;

use App\Utils\Export\XlsxManager;

/**
 * Concrete XLSX implementation for Gravity Flow approved entries
 */
class GravityApprovedEntriesXlsx extends XlsxManager
{
    public function __construct()
    {
        // Define the schema for gravity flow approved entries
        $this->schema = [
            'entry_id' => 'شناسه ورودی',
            'form_title' => 'عنوان فرم',
            'date_created' => 'تاریخ ایجاد',
            'status' => 'وضعیت',
            'form_data' => 'اطلاعات فرم'
        ];
        
        $this->title = 'فرم‌های تأیید شده';
    }

    public function setEntriesData(array $entries): self
    {
        $formatted_data = [];
        
        foreach ($entries as $entry) {
            $form_data = '';
            if (!empty($entry['entry_data'])) {
                $form_data_parts = [];
                foreach ($entry['entry_data'] as $field_data) {
                    $form_data_parts[] = $field_data['label'] . ': ' . strip_tags($field_data['value']);
                }
                $form_data = implode(' | ', $form_data_parts);
            }

            $formatted_data[] = [
                'entry_id' => $entry['id'],
                'form_title' => $entry['form_title'],
                'date_created' => date('Y/m/d H:i', strtotime($entry['date_created'])),
                'status' => 'تأیید شده',
                'form_data' => $form_data
            ];
        }
        
        $this->data = $formatted_data;
        return $this;
    }

    protected function generateFilename(): string
    {
        return 'gravity-flow-approved-entries-' . date('Y-m-d-H-i-s') . '.' . $this->getExtension();
    }
}
