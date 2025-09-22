<?php

namespace App\Utils\Export;

use App\Utils\Export\Concrete\GravityApprovedEntriesCsv;
use App\Utils\Export\Concrete\GravityApprovedEntriesXlsx;
use App\Utils\Export\Concrete\GravityApprovedEntriesPdf;
use App\Utils\Export\Concrete\GravitySingleEntryPdf;
use App\Utils\Export\Concrete\GravitySingleEntryXlsx;
use Exception;

/**
 * Factory class for creating export instances
 */
class ExportFactory
{
    /**
     * Create a Gravity Flow approved entries exporter
     * @param string $format csv|xlsx|pdf
     * @return GravityApprovedEntriesCsv|GravityApprovedEntriesXlsx|GravityApprovedEntriesPdf
     * @throws Exception
     */
    public static function createGravityApprovedEntriesExporter(string $format)
    {
        switch (strtolower($format)) {
            case 'csv':
                return new GravityApprovedEntriesCsv();
            case 'xlsx':
            case 'excel':
                return new GravityApprovedEntriesXlsx();
            case 'pdf':
                return new GravityApprovedEntriesPdf();
            default:
                throw new Exception('Unsupported export format: ' . $format);
        }
    }

    /**
     * Create a Gravity Flow single entry exporter
     * @param string $format pdf|xlsx|excel
     * @param int $entry_id
     * @return GravitySingleEntryPdf|GravitySingleEntryXlsx
     * @throws Exception
     */
    public static function createGravitySingleEntryExporter(string $format, int $entry_id)
    {
        switch (strtolower($format)) {
            case 'pdf':
                return new GravitySingleEntryPdf($entry_id);
            case 'xlsx':
            case 'excel':
                return new GravitySingleEntryXlsx($entry_id);
            default:
                throw new Exception('Unsupported single entry export format: ' . $format);
        }
    }

    /**
     * Create a generic CSV exporter
     * @return CsvManager
     */
    public static function createCsvExporter(): CsvManager
    {
        return new CsvManager();
    }

    /**
     * Create a generic XLSX exporter
     * @return XlsxManager
     */
    public static function createXlsxExporter(): XlsxManager
    {
        return new XlsxManager();
    }

    /**
     * Create a generic PDF exporter
     * @return PdfManager
     */
    public static function createPdfExporter(): PdfManager
    {
        return new PdfManager();
    }
}
