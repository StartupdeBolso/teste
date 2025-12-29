<?php

namespace App\Service;

use App\Entity\Campaign;
use App\Entity\Dispatch;
use App\Repository\CampaignRepository;
use App\Repository\ContactRepository;
use App\Repository\DispatchRepository;
use Doctrine\ORM\EntityManagerInterface;

class DispatchService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DispatchRepository $dispatchRepository,
        private CampaignRepository $campaignRepository,
        private ContactRepository $contactRepository,
        private N8nWebhookService $n8nWebhookService
    ) {
    }

    public function createDispatchesForCampaign(Campaign $campaign): int
    {
        // Get contacts from database
        $contacts = $this->contactRepository->findByCampaign($campaign);

        // Limit by dispatchLimit
        $contacts = array_slice($contacts, 0, $campaign->getDispatchLimit());

        $created = 0;
        foreach ($contacts as $contact) {
            $dispatch = new Dispatch();
            $dispatch->setCampaign($campaign);
            $dispatch->setContactData($contact->getData());
            $dispatch->setStatus('pending');

            $this->entityManager->persist($dispatch);
            $created++;
        }

        $this->entityManager->flush();

        return $created;
    }

    public function processPendingDispatches(int $batchSize = 10): array
    {
        $dispatches = $this->dispatchRepository->findPendingDispatches($batchSize);
        $results = [];
        $processedCampaigns = [];

        foreach ($dispatches as $dispatch) {
            $campaign = $dispatch->getCampaign();
            $campaignId = $campaign->getId();

            // Reset daily count if needed (only once per campaign per run)
            if (!isset($processedCampaigns[$campaignId])) {
                $campaign->resetDailyCountIfNeeded();
                $processedCampaigns[$campaignId] = true;
            }

            // Check if campaign has reached daily limit
            if ($campaign->getDailyLimit() > 0 && $campaign->getDispatchedToday() >= $campaign->getDailyLimit()) {
                $results[] = [
                    'dispatch_id' => $dispatch->getId(),
                    'status' => 'skipped',
                    'success' => false,
                    'message' => 'Daily limit reached for campaign',
                ];
                continue;
            }

            $result = $this->processDispatch($dispatch);
            $results[] = $result;
        }

        $this->entityManager->flush();

        return $results;
    }

    private function processDispatch(Dispatch $dispatch): array
    {
        $dispatch->setStatus('processing');
        $this->entityManager->flush();

        try {
            $result = $this->n8nWebhookService->sendDispatch(
                $dispatch->getContactData(),
                [
                    'campaign_id' => $dispatch->getCampaign()->getId(),
                    'dispatch_id' => $dispatch->getId(),
                ]
            );

            if ($result['success']) {
                $dispatch->setStatus('sent');
                $dispatch->setSentAt(new \DateTimeImmutable());
                $dispatch->setResponseData(json_encode($result['response'] ?? []));

                // Update campaign dispatched count
                $campaign = $dispatch->getCampaign();
                $campaign->setDispatchedCount($campaign->getDispatchedCount() + 1);

                // Update daily count
                $campaign->setDispatchedToday($campaign->getDispatchedToday() + 1);
                $campaign->setLastDispatchDate(new \DateTimeImmutable('today'));

                // Check if campaign is completed
                if ($campaign->getDispatchedCount() >= $campaign->getDispatchLimit()) {
                    $campaign->setStatus('completed');
                    $campaign->setCompletedAt(new \DateTimeImmutable());
                }
            } else {
                $dispatch->setStatus('failed');
                $dispatch->setErrorMessage($result['error'] ?? 'Unknown error');
                $dispatch->setRetryCount($dispatch->getRetryCount() + 1);
            }

            return [
                'dispatch_id' => $dispatch->getId(),
                'status' => $dispatch->getStatus(),
                'success' => $result['success'],
            ];
        } catch (\Exception $e) {
            $dispatch->setStatus('failed');
            $dispatch->setErrorMessage($e->getMessage());
            $dispatch->setRetryCount($dispatch->getRetryCount() + 1);

            return [
                'dispatch_id' => $dispatch->getId(),
                'status' => 'failed',
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function startCampaign(Campaign $campaign): void
    {
        if ($campaign->getStatus() !== 'draft') {
            throw new \RuntimeException('Only draft campaigns can be started');
        }

        $campaign->setStatus('active');
        $campaign->setStartedAt(new \DateTimeImmutable());

        // Create dispatches
        $this->createDispatchesForCampaign($campaign);

        $this->entityManager->flush();
    }

    public function pauseCampaign(Campaign $campaign): void
    {
        if ($campaign->getStatus() !== 'active') {
            throw new \RuntimeException('Only active campaigns can be paused');
        }

        $campaign->setStatus('paused');
        $this->entityManager->flush();
    }

    public function resumeCampaign(Campaign $campaign): void
    {
        if ($campaign->getStatus() !== 'paused') {
            throw new \RuntimeException('Only paused campaigns can be resumed');
        }

        $campaign->setStatus('active');
        $this->entityManager->flush();
    }

    public function getCampaignStats(Campaign $campaign): array
    {
        return [
            'total_contacts' => $campaign->getTotalContacts(),
            'dispatch_limit' => $campaign->getDispatchLimit(),
            'dispatched_count' => $campaign->getDispatchedCount(),
            'pending' => $this->dispatchRepository->countByStatus($campaign, 'pending'),
            'processing' => $this->dispatchRepository->countByStatus($campaign, 'processing'),
            'sent' => $this->dispatchRepository->countByStatus($campaign, 'sent'),
            'failed' => $this->dispatchRepository->countByStatus($campaign, 'failed'),
            'progress_percentage' => $campaign->getDispatchLimit() > 0
                ? round(($campaign->getDispatchedCount() / $campaign->getDispatchLimit()) * 100, 2)
                : 0,
        ];
    }
}
