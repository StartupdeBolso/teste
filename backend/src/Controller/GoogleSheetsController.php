<?php

namespace App\Controller;

use App\Service\GoogleSheetsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/google-sheets')]
class GoogleSheetsController extends AbstractController
{
    public function __construct(
        private GoogleSheetsService $googleSheetsService
    ) {
    }

    #[Route('/info', name: 'api_google_sheets_info', methods: ['POST'])]
    public function getInfo(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['spreadsheetId'])) {
            return $this->json(['error' => 'spreadsheetId is required'], 400);
        }

        try {
            $info = $this->googleSheetsService->getSheetInfo($data['spreadsheetId']);

            return $this->json($info);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/preview', name: 'api_google_sheets_preview', methods: ['POST'])]
    public function preview(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['spreadsheetId']) || !isset($data['sheetName'])) {
            return $this->json(['error' => 'spreadsheetId and sheetName are required'], 400);
        }

        try {
            $contacts = $this->googleSheetsService->getContacts(
                $data['spreadsheetId'],
                $data['sheetName'],
                10 // Preview only first 10 rows
            );

            $totalContacts = $this->googleSheetsService->countContacts(
                $data['spreadsheetId'],
                $data['sheetName']
            );

            return $this->json([
                'contacts' => $contacts,
                'totalContacts' => $totalContacts,
                'preview' => true,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
