<?php

namespace App\Utils\Export\Concrete;

use App\Utils\Export\PdfManager;

/**
 * Concrete PDF implementation for single Gravity Flow entry
 */
class GravitySingleEntryPdf extends PdfManager
{
    protected int $entry_id;

    public function __construct(int $entry_id = 0)
    {
        $this->entry_id = $entry_id;
        $this->title = 'جزئیات ورودی #' . $entry_id;
        $this->template = 'entry';
    }

    public function setEntryId(int $entry_id): self
    {
        $this->entry_id = $entry_id;
        $this->title = 'جزئیات ورودی #' . $entry_id;
        return $this;
    }

    public function setSingleEntryData(array $entry_data): self
    {
        $this->data = $entry_data;
        return $this;
    }

    protected function generateFilename(): string
    {
        return 'entry-' . $this->entry_id . '-' . date('Y-m-d-H-i-s') . '.' . $this->getExtension();
    }
}
