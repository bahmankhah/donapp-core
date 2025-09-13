<?php

namespace App\Contracts\Export;

/**
 * Interface for PDF files
 */
interface PdfFile extends ExportableFile
{
    /**
     * Set the data to export
     * @param array $data
     * @return self
     */
    public function setData(array $data): self;

    /**
     * Set the document title
     * @param string $title
     * @return self
     */
    public function setTitle(string $title): self;

    /**
     * Set the template type (table, entry, custom)
     * @param string $template
     * @return self
     */
    public function setTemplate(string $template): self;

    /**
     * Generate HTML content for PDF
     * @return string
     */
    public function generateHtml(): string;

    /**
     * Get document title
     * @return string
     */
    public function getTitle(): string;

    /**
     * Get template type
     * @return string
     */
    public function getTemplate(): string;
}
