<?php

namespace App\Controller;

use App\Entity\Campaign;
use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/contacts')]
class ContactController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContactRepository $contactRepository
    ) {
    }

    #[Route('/campaign/{campaignId}', name: 'api_contacts_list', methods: ['GET'])]
    public function list(int $campaignId, Request $request): JsonResponse
    {
        $campaign = $this->entityManager->getRepository(Campaign::class)->find($campaignId);

        if (!$campaign || $campaign->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Campaign not found'], 404);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(10, (int) $request->query->get('limit', 50)));
        $offset = ($page - 1) * $limit;

        $contacts = $this->contactRepository->createQueryBuilder('c')
            ->where('c.campaign = :campaign')
            ->setParameter('campaign', $campaign)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();

        $total = $this->contactRepository->count(['campaign' => $campaign]);

        return $this->json([
            'contacts' => array_map(fn($c) => $this->serializeContact($c), $contacts),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit)
        ]);
    }

    #[Route('/{id}', name: 'api_contacts_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $contact = $this->contactRepository->find($id);

        if (!$contact || $contact->getCampaign()->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Contact not found'], 404);
        }

        if ($contact->getCampaign()->getStatus() !== 'draft') {
            return $this->json(['error' => 'Cannot edit contacts of active campaigns'], 400);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['data']) && is_array($data['data'])) {
            $contact->setData($data['data']);
            $this->entityManager->flush();
        }

        return $this->json($this->serializeContact($contact));
    }

    #[Route('/{id}', name: 'api_contacts_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $contact = $this->contactRepository->find($id);

        if (!$contact || $contact->getCampaign()->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Contact not found'], 404);
        }

        if ($contact->getCampaign()->getStatus() !== 'draft') {
            return $this->json(['error' => 'Cannot delete contacts of active campaigns'], 400);
        }

        $campaign = $contact->getCampaign();
        $this->entityManager->remove($contact);

        // Update campaign total
        $campaign->setTotalContacts($campaign->getTotalContacts() - 1);

        $this->entityManager->flush();

        return $this->json(['message' => 'Contact deleted successfully']);
    }

    private function serializeContact(Contact $contact): array
    {
        return [
            'id' => $contact->getId(),
            'data' => $contact->getData(),
            'createdAt' => $contact->getCreatedAt()->format('c')
        ];
    }
}
