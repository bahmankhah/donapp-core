<?php

namespace App\Contracts\Export;

/**
 * Interface for spreadsheet files (CSV/Excel)
 */
interface SpreadsheetFile extends ExportableFile
{
    /**
     * Set the schema (headers and data structure)
     * @param array $schema
     * @return self
     */
    public function setSchema(array $schema): self;

    /**
     * Set the data to export
     * @param array $data
     * @return self
     */
    public function setData(array $data): self;

    /**
     * Set the sheet name/title
     * @param string $title
     * @return self
     */
    public function setTitle(string $title): self;

    /**
     * Get the schema
     * @return array
     */
    public function getSchema(): array;

    /**
     * Get the data
     * @return array
     */
    public function getData(): array;

    /**
     * Convert data to tabular format
     * @return array
     */
    public function toTabular(): array;
}
