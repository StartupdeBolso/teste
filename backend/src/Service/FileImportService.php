<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

class FileImportService
{
    public function importFile(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            return $this->importCsv($filePath);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            return $this->importExcel($filePath);
        }

        throw new \InvalidArgumentException('Formato de arquivo não suportado. Use CSV, XLS ou XLSX.');
    }

    private function importCsv(string $filePath): array
    {
        $reader = new Csv();
        $reader->setInputEncoding('UTF-8');
        $reader->setDelimiter(',');
        $reader->setEnclosure('"');
        $reader->setSheetIndex(0);

        $spreadsheet = $reader->load($filePath);
        return $this->extractData($spreadsheet);
    }

    private function importExcel(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        return $this->extractData($spreadsheet);
    }

    private function extractData($spreadsheet): array
    {
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        if (empty($rows)) {
            return [];
        }

        // First row is headers
        $headers = array_shift($rows);
        $headers = array_map('trim', $headers);
        $headers = array_filter($headers); // Remove empty headers

        $contacts = [];
        foreach ($rows as $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $contact = [];
            foreach ($headers as $index => $header) {
                $contact[$header] = isset($row[$index]) ? trim($row[$index]) : '';
            }

            $contacts[] = $contact;
        }

        return $contacts;
    }

    public function validateFile(string $filePath): array
    {
        try {
            $contacts = $this->importFile($filePath);

            return [
                'valid' => true,
                'totalContacts' => count($contacts),
                'preview' => array_slice($contacts, 0, 5),
                'headers' => !empty($contacts) ? array_keys($contacts[0]) : []
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
