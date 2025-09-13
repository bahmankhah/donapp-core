<?php

namespace App\Utils\Export\Concrete;

use App\Utils\Export\PdfManager;

/**
 * Concrete PDF implementation for Gravity Flow approved entries
 */
class GravityApprovedEntriesPdf extends PdfManager
{
    public function __construct()
    {
        $this->title = 'فرم‌های تأیید شده گرویتی فلو';
        $this->template = 'table';
    }

    public function setEntriesData(array $entries): self
    {
        $formatted_data = [];
        
        // Headers
        $formatted_data[] = [
            'شناسه ورودی',
            'عنوان فرم',
            'تاریخ ایجاد',
            'وضعیت',
            'اطلاعات فرم'
        ];
        
        // Data rows
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
                $entry['id'],
                $entry['form_title'],
                date('Y/m/d H:i', strtotime($entry['date_created'])),
                'تأیید شده',
                $form_data
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
