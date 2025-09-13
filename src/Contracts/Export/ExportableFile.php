<?php

namespace App\Contracts\Export;

/**
 * Base interface for exportable files
 */
interface ExportableFile
{
    /**
     * Generate the file content
     * @return array ['success' => bool, 'data' => mixed, 'filename' => string, 'message' => string]
     */
    public function generate(): array;

    /**
     * Serve the file for download
     * @param mixed $data File data
     * @param string $filename Filename for download
     * @return void
     */
    public function serve($data, string $filename): void;

    /**
     * Get the MIME type for the file
     * @return string
     */
    public function getMimeType(): string;

    /**
     * Get file extension
     * @return string
     */
    public function getExtension(): string;
}
