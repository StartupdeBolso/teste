<?php

namespace App\Controller;

use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use App\Security\Voter\OrganizationVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/organizations')]
class OrganizationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrganizationRepository $organizationRepository
    ) {
    }

    #[Route('', name: 'api_organizations_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();

        // Super admin sees all organizations
        if ($user->isSuperAdmin()) {
            $organizations = $this->organizationRepository->findAll();
        } else {
            // Regular users only see their own organization
            $organizations = $user->getOrganization() ? [$user->getOrganization()] : [];
        }

        return $this->json(array_map(fn($org) => $this->serializeOrganization($org), $organizations));
    }

    #[Route('/{id}', name: 'api_organizations_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $organization = $this->organizationRepository->find($id);

        if (!$organization) {
            return $this->json(['error' => 'Organization not found'], 404);
        }

        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $organization);

        return $this->json($this->serializeOrganization($organization, true));
    }

    #[Route('', name: 'api_organizations_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Only super admins can create organizations
        if (!$this->getUser()->isSuperAdmin()) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return $this->json(['error' => 'Name is required'], 400);
        }

        $organization = new Organization();
        $organization->setName($data['name']);

        if (isset($data['webhookUrl'])) {
            $organization->setWebhookUrl($data['webhookUrl']);
        }

        if (isset($data['phoneNumber'])) {
            $organization->setPhoneNumber($data['phoneNumber']);
        }

        if (isset($data['settings']) && is_array($data['settings'])) {
            $organization->setSettings($data['settings']);
        }

        if (isset($data['isActive'])) {
            $organization->setIsActive($data['isActive']);
        }

        $this->entityManager->persist($organization);
        $this->entityManager->flush();

        return $this->json($this->serializeOrganization($organization, true), 201);
    }

    #[Route('/{id}', name: 'api_organizations_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $organization = $this->organizationRepository->find($id);

        if (!$organization) {
            return $this->json(['error' => 'Organization not found'], 404);
        }

        $this->denyAccessUnlessGranted(OrganizationVoter::EDIT, $organization);

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $organization->setName($data['name']);
        }

        if (isset($data['webhookUrl'])) {
            $organization->setWebhookUrl($data['webhookUrl']);
        }

        if (isset($data['phoneNumber'])) {
            $organization->setPhoneNumber($data['phoneNumber']);
        }

        if (isset($data['settings']) && is_array($data['settings'])) {
            $organization->setSettings($data['settings']);
        }

        // Only super admin can change isActive status
        if (isset($data['isActive']) && $this->getUser()->isSuperAdmin()) {
            $organization->setIsActive($data['isActive']);
        }

        $this->entityManager->flush();

        return $this->json($this->serializeOrganization($organization, true));
    }

    #[Route('/{id}', name: 'api_organizations_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $organization = $this->organizationRepository->find($id);

        if (!$organization) {
            return $this->json(['error' => 'Organization not found'], 404);
        }

        $this->denyAccessUnlessGranted(OrganizationVoter::DELETE, $organization);

        $this->entityManager->remove($organization);
        $this->entityManager->flush();

        return $this->json(['message' => 'Organization deleted successfully']);
    }

    #[Route('/{id}/stats', name: 'api_organizations_stats', methods: ['GET'])]
    public function stats(int $id): JsonResponse
    {
        $organization = $this->organizationRepository->find($id);

        if (!$organization) {
            return $this->json(['error' => 'Organization not found'], 404);
        }

        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $organization);

        $stats = [
            'total_users' => $organization->getUsers()->count(),
            'total_campaigns' => $organization->getCampaigns()->count(),
            'active_campaigns' => $organization->getCampaigns()->filter(fn($c) => $c->getStatus() === 'active')->count(),
            'total_contacts' => array_sum($organization->getCampaigns()->map(fn($c) => $c->getTotalContacts())->toArray()),
            'total_dispatched' => array_sum($organization->getCampaigns()->map(fn($c) => $c->getDispatchedCount())->toArray()),
        ];

        return $this->json($stats);
    }

    private function serializeOrganization(Organization $organization, bool $includeDetails = false): array
    {
        $data = [
            'id' => $organization->getId(),
            'name' => $organization->getName(),
            'isActive' => $organization->isActive(),
            'createdAt' => $organization->getCreatedAt()->format('c'),
        ];

        if ($includeDetails) {
            $data['webhookUrl'] = $organization->getWebhookUrl();
            $data['phoneNumber'] = $organization->getPhoneNumber();
            $data['settings'] = $organization->getSettings();
            $data['userCount'] = $organization->getUsers()->count();
            $data['campaignCount'] = $organization->getCampaigns()->count();
        }

        return $data;
    }
}
