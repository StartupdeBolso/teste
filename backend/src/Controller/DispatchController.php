<?php

namespace App\Controller;

use App\Entity\Dispatch;
use App\Repository\CampaignRepository;
use App\Repository\DispatchRepository;
use App\Service\DispatchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dispatches')]
class DispatchController extends AbstractController
{
    public function __construct(
        private DispatchRepository $dispatchRepository,
        private CampaignRepository $campaignRepository,
        private DispatchService $dispatchService
    ) {
    }

    #[Route('/campaign/{campaignId}', name: 'api_dispatches_by_campaign', methods: ['GET'])]
    public function getByCampaign(int $campaignId): JsonResponse
    {
        $campaign = $this->campaignRepository->find($campaignId);

        if (!$campaign || $campaign->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Campaign not found'], 404);
        }

        $dispatches = $this->dispatchRepository->findByCampaign($campaign);

        return $this->json(array_map(function (Dispatch $dispatch) {
            return $this->serializeDispatch($dispatch);
        }, $dispatches));
    }

    #[Route('/process', name: 'api_dispatches_process', methods: ['POST'])]
    public function process(): JsonResponse
    {
        try {
            $results = $this->dispatchService->processPendingDispatches(10);

            return $this->json([
                'message' => 'Dispatches processed',
                'processed' => count($results),
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function serializeDispatch(Dispatch $dispatch): array
    {
        return [
            'id' => $dispatch->getId(),
            'campaignId' => $dispatch->getCampaign()->getId(),
            'contactData' => $dispatch->getContactData(),
            'status' => $dispatch->getStatus(),
            'responseData' => $dispatch->getResponseData(),
            'errorMessage' => $dispatch->getErrorMessage(),
            'retryCount' => $dispatch->getRetryCount(),
            'createdAt' => $dispatch->getCreatedAt()->format('c'),
            'sentAt' => $dispatch->getSentAt()?->format('c'),
        ];
    }
}
