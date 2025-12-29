<?php

namespace App\Service;

use Google\Client;
use Google\Service\Sheets;

class GoogleSheetsService
{
    private Client $client;
    private Sheets $sheetsService;

    public function __construct(private string $credentialsPath)
    {
        $this->initializeClient();
    }

    private function initializeClient(): void
    {
        $this->client = new Client();
        $this->client->setApplicationName('N8N Dispatch SaaS');
        $this->client->setScopes([Sheets::SPREADSHEETS_READONLY]);

        if (file_exists($this->credentialsPath)) {
            $this->client->setAuthConfig($this->credentialsPath);
        }

        $this->sheetsService = new Sheets($this->client);
    }

    public function getSheetData(string $spreadsheetId, string $range = 'A:Z'): array
    {
        try {
            $response = $this->sheetsService->spreadsheets_values->get($spreadsheetId, $range);
            return $response->getValues() ?? [];
        } catch (\Exception $e) {
            throw new \RuntimeException('Error fetching sheet data: ' . $e->getMessage());
        }
    }

    public function getSheetInfo(string $spreadsheetId): array
    {
        try {
            $spreadsheet = $this->sheetsService->spreadsheets->get($spreadsheetId);

            $sheets = [];
            foreach ($spreadsheet->getSheets() as $sheet) {
                $sheets[] = [
                    'id' => $sheet->getProperties()->getSheetId(),
                    'title' => $sheet->getProperties()->getTitle(),
                    'index' => $sheet->getProperties()->getIndex(),
                ];
            }

            return [
                'spreadsheetId' => $spreadsheet->getSpreadsheetId(),
                'title' => $spreadsheet->getProperties()->getTitle(),
                'sheets' => $sheets,
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException('Error fetching sheet info: ' . $e->getMessage());
        }
    }

    public function getContacts(string $spreadsheetId, string $sheetName, int $limit = null, int $offset = 0): array
    {
        $range = $sheetName . '!A:Z';
        $allData = $this->getSheetData($spreadsheetId, $range);

        if (empty($allData)) {
            return [];
        }

        // First row is headers
        $headers = array_shift($allData);

        // Apply offset and limit
        $slicedData = array_slice($allData, $offset, $limit);

        // Convert to associative array
        $contacts = [];
        foreach ($slicedData as $row) {
            $contact = [];
            foreach ($headers as $index => $header) {
                $contact[$header] = $row[$index] ?? '';
            }
            $contacts[] = $contact;
        }

        return $contacts;
    }

    public function countContacts(string $spreadsheetId, string $sheetName): int
    {
        $range = $sheetName . '!A:A';
        $data = $this->getSheetData($spreadsheetId, $range);

        // Subtract 1 for header row
        return max(0, count($data) - 1);
    }
}
