<?php

namespace App\Controller;

use App\Entity\Campaign;
use App\Entity\Contact;
use App\Service\FileImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/files')]
class FileUploadController extends AbstractController
{
    public function __construct(
        private FileImportService $fileImportService,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/upload/{campaignId}', name: 'api_files_upload', methods: ['POST'])]
    public function upload(int $campaignId, Request $request): JsonResponse
    {
        $campaign = $this->entityManager->getRepository(Campaign::class)->find($campaignId);

        if (!$campaign || $campaign->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Campaign not found'], 404);
        }

        if ($campaign->getStatus() !== 'draft') {
            return $this->json(['error' => 'Only draft campaigns can have files uploaded'], 400);
        }

        /** @var UploadedFile $file */
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        $allowedExtensions = ['csv', 'xlsx', 'xls'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions)) {
            return $this->json(['error' => 'File type not supported. Use CSV, XLS or XLSX'], 400);
        }

        try {
            // Save file temporarily
            $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = uniqid() . '.' . $extension;
            $file->move($uploadDir, $fileName);
            $filePath = $uploadDir . '/' . $fileName;

            // Import contacts
            $contacts = $this->fileImportService->importFile($filePath);

            if (empty($contacts)) {
                unlink($filePath);
                return $this->json(['error' => 'No contacts found in file'], 400);
            }

            // Delete old contacts
            foreach ($campaign->getContacts() as $oldContact) {
                $this->entityManager->remove($oldContact);
            }

            // Save new contacts
            foreach ($contacts as $contactData) {
                $contact = new Contact();
                $contact->setCampaign($campaign);
                $contact->setData($contactData);
                $this->entityManager->persist($contact);
            }

            $campaign->setFileName($file->getClientOriginalName());
            $campaign->setTotalContacts(count($contacts));

            // Set dispatch limit if not set or if less than total
            if (!$campaign->getDispatchLimit() || $campaign->getDispatchLimit() > count($contacts)) {
                $campaign->setDispatchLimit(count($contacts));
            }

            // Set daily limit if not set (default to 100 per day)
            if (!$campaign->getDailyLimit()) {
                $campaign->setDailyLimit(min(100, count($contacts)));
            }

            $this->entityManager->flush();

            // Delete uploaded file
            unlink($filePath);

            return $this->json([
                'message' => 'File uploaded successfully',
                'totalContacts' => count($contacts),
                'preview' => array_slice($contacts, 0, 5),
                'headers' => !empty($contacts) ? array_keys($contacts[0]) : []
            ]);
        } catch (\Exception $e) {
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }

            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/preview/{campaignId}', name: 'api_files_preview', methods: ['GET'])]
    public function preview(int $campaignId): JsonResponse
    {
        $campaign = $this->entityManager->getRepository(Campaign::class)->find($campaignId);

        if (!$campaign || $campaign->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Campaign not found'], 404);
        }

        $contacts = $campaign->getContacts()->slice(0, 10);
        $contactsArray = array_map(fn($c) => $c->getData(), $contacts);

        return $this->json([
            'totalContacts' => $campaign->getTotalContacts(),
            'preview' => $contactsArray,
            'fileName' => $campaign->getFileName(),
            'headers' => !empty($contactsArray) ? array_keys($contactsArray[0]) : []
        ]);
    }
}
