<?php

namespace App\Controller;

use App\Entity\Campaign;
use App\Repository\CampaignRepository;
use App\Service\DispatchService;
use App\Service\GoogleSheetsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/campaigns')]
class CampaignController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CampaignRepository $campaignRepository,
        private GoogleSheetsService $googleSheetsService,
        private DispatchService $dispatchService
    ) {
    }

    #[Route('', name: 'api_campaigns_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        $campaigns = $this->campaignRepository->findByUser($user);

        return $this->json(array_map(function (Campaign $campaign) {
            return $this->serializeCampaign($campaign);
        }, $campaigns));
    }

    #[Route('', name: 'api_campaigns_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || !isset($data['googleSheetId'])) {
            return $this->json(['error' => 'Missing required fields'], 400);
        }

        try {
            // Validate Google Sheet access and get info
            $sheetInfo = $this->googleSheetsService->getSheetInfo($data['googleSheetId']);
            $sheetName = $data['sheetName'] ?? $sheetInfo['sheets'][0]['title'];

            $totalContacts = $this->googleSheetsService->countContacts(
                $data['googleSheetId'],
                $sheetName
            );

            $campaign = new Campaign();
            $campaign->setUser($this->getUser());
            $campaign->setName($data['name']);
            $campaign->setDescription($data['description'] ?? null);
            $campaign->setGoogleSheetId($data['googleSheetId']);
            $campaign->setSheetName($sheetName);
            $campaign->setTotalContacts($totalContacts);
            $campaign->setDispatchLimit($data['dispatchLimit'] ?? $totalContacts);
            $campaign->setConfiguration($data['configuration'] ?? []);

            $this->entityManager->persist($campaign);
            $this->entityManager->flush();

            return $this->json($this->serializeCampaign($campaign), 201);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}', name: 'api_campaigns_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $campaign = $this->findCampaignOrFail($id);

        if (!$campaign) {
            return $this->json(['error' => 'Campaign not found'], 404);
        }

        $stats = $this->dispatchService->getCampaignStats($campaign);

        return $this->json([
            ...$this->serializeCampaign($campaign),
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}', name: 'api_campaigns_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $campaign = $this->findCampaignOrFail($id);

        if (!$campaign) {
            return $this->json(['error' => 'Campaign not found'], 404);
        }

        if ($campaign->getStatus() !== 'draft') {
            return $this->json(['error' => 'Only draft campaigns can be updated'], 400);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $campaign->setName($data['name']);
        }

        if (isset($data['description'])) {
            $campaign->setDescription($data['description']);
        }

        if (isset($data['dispatchLimit'])) {
            $campaign->setDispatchLimit($data['dispatchLimit']);
        }

        if (isset($data['configuration'])) {
            $campaign->setConfiguration($data['configuration']);
        }

        $this->entityManager->flush();

        return $this->json($this->serializeCampaign($campaign));
    }

    #[Route('/{id}', name: 'api_campaigns_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $campaign = $this->findCampaignOrFail($id);

        if (!$campaign) {
            return $this->json(['error' => 'Campaign not found'], 404);
        }

        if ($campaign->getStatus() === 'active') {
            return $this->json(['error' => 'Cannot delete active campaign'], 400);
        }

        $this->entityManager->remove($campaign);
        $this->entityManager->flush();

        return $this->json(['message' => 'Campaign deleted successfully']);
    }

    #[Route('/{id}/start', name: 'api_campaigns_start', methods: ['POST'])]
    public function start(int $id): JsonResponse
    {
        $campaign = $this->findCampaignOrFail($id);

        if (!$campaign) {
            return $this->json(['error' => 'Campaign not found'], 404);
        }

        try {
            $this->dispatchService->startCampaign($campaign);

            return $this->json([
                'message' => 'Campaign started successfully',
                'campaign' => $this->serializeCampaign($campaign),
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}/pause', name: 'api_campaigns_pause', methods: ['POST'])]
    public function pause(int $id): JsonResponse
    {
        $campaign = $this->findCampaignOrFail($id);

        if (!$campaign) {
            return $this->json(['error' => 'Campaign not found'], 404);
        }

        try {
            $this->dispatchService->pauseCampaign($campaign);

            return $this->json([
                'message' => 'Campaign paused successfully',
                'campaign' => $this->serializeCampaign($campaign),
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}/resume', name: 'api_campaigns_resume', methods: ['POST'])]
    public function resume(int $id): JsonResponse
    {
        $campaign = $this->findCampaignOrFail($id);

        if (!$campaign) {
            return $this->json(['error' => 'Campaign not found'], 404);
        }

        try {
            $this->dispatchService->resumeCampaign($campaign);

            return $this->json([
                'message' => 'Campaign resumed successfully',
                'campaign' => $this->serializeCampaign($campaign),
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    private function findCampaignOrFail(int $id): ?Campaign
    {
        $campaign = $this->campaignRepository->find($id);

        if ($campaign && $campaign->getUser() !== $this->getUser()) {
            return null;
        }

        return $campaign;
    }

    private function serializeCampaign(Campaign $campaign): array
    {
        return [
            'id' => $campaign->getId(),
            'name' => $campaign->getName(),
            'description' => $campaign->getDescription(),
            'googleSheetId' => $campaign->getGoogleSheetId(),
            'sheetName' => $campaign->getSheetName(),
            'totalContacts' => $campaign->getTotalContacts(),
            'dispatchLimit' => $campaign->getDispatchLimit(),
            'dispatchedCount' => $campaign->getDispatchedCount(),
            'status' => $campaign->getStatus(),
            'configuration' => $campaign->getConfiguration(),
            'createdAt' => $campaign->getCreatedAt()->format('c'),
            'startedAt' => $campaign->getStartedAt()?->format('c'),
            'completedAt' => $campaign->getCompletedAt()?->format('c'),
        ];
    }
}
