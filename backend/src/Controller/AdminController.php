<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CampaignRepository;
use App\Repository\DispatchRepository;
use App\Repository\OrganizationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private OrganizationRepository $organizationRepository,
        private CampaignRepository $campaignRepository,
        private DispatchRepository $dispatchRepository
    ) {
    }

    /**
     * Check if current user is super admin before all actions
     */
    private function checkSuperAdmin(): void
    {
        if (!$this->getUser()->isSuperAdmin()) {
            throw $this->createAccessDeniedException('Only super admins can access this endpoint');
        }
    }

    #[Route('/dashboard', name: 'api_admin_dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        $this->checkSuperAdmin();

        $stats = [
            'total_organizations' => $this->organizationRepository->count([]),
            'active_organizations' => $this->organizationRepository->count(['isActive' => true]),
            'total_users' => $this->userRepository->count([]),
            'total_campaigns' => $this->campaignRepository->count([]),
            'active_campaigns' => $this->campaignRepository->count(['status' => 'active']),
            'total_dispatches' => $this->dispatchRepository->count([]),
            'sent_dispatches' => $this->dispatchRepository->count(['status' => 'sent']),
            'failed_dispatches' => $this->dispatchRepository->count(['status' => 'failed']),
            'pending_dispatches' => $this->dispatchRepository->count(['status' => 'pending']),
        ];

        return $this->json($stats);
    }

    #[Route('/users', name: 'api_admin_users_list', methods: ['GET'])]
    public function listUsers(Request $request): JsonResponse
    {
        $this->checkSuperAdmin();

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(10, (int) $request->query->get('limit', 50)));
        $offset = ($page - 1) * $limit;

        $organizationId = $request->query->get('organizationId');

        $qb = $this->userRepository->createQueryBuilder('u')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('u.createdAt', 'DESC');

        if ($organizationId) {
            $qb->where('u.organization = :orgId')
                ->setParameter('orgId', $organizationId);
        }

        $users = $qb->getQuery()->getResult();
        $total = $this->userRepository->count($organizationId ? ['organization' => $organizationId] : []);

        return $this->json([
            'users' => array_map(fn($u) => $this->serializeUser($u), $users),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit),
        ]);
    }

    #[Route('/users/{id}', name: 'api_admin_users_update', methods: ['PUT', 'PATCH'])]
    public function updateUser(int $id, Request $request): JsonResponse
    {
        $this->checkSuperAdmin();

        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $user->setName($data['name']);
        }

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        if (isset($data['organizationId'])) {
            $organization = $this->organizationRepository->find($data['organizationId']);
            if ($organization) {
                $user->setOrganization($organization);
            }
        }

        $this->entityManager->flush();

        return $this->json($this->serializeUser($user));
    }

    #[Route('/users/{id}', name: 'api_admin_users_delete', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        $this->checkSuperAdmin();

        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        // Prevent deleting yourself
        if ($user->getId() === $this->getUser()->getId()) {
            return $this->json(['error' => 'Cannot delete your own account'], 400);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'User deleted successfully']);
    }

    #[Route('/organizations/{id}/users', name: 'api_admin_org_users', methods: ['GET'])]
    public function getOrganizationUsers(int $id): JsonResponse
    {
        $this->checkSuperAdmin();

        $organization = $this->organizationRepository->find($id);

        if (!$organization) {
            return $this->json(['error' => 'Organization not found'], 404);
        }

        $users = $organization->getUsers()->toArray();

        return $this->json(array_map(fn($u) => $this->serializeUser($u), $users));
    }

    #[Route('/campaigns/all', name: 'api_admin_campaigns', methods: ['GET'])]
    public function listAllCampaigns(Request $request): JsonResponse
    {
        $this->checkSuperAdmin();

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(10, (int) $request->query->get('limit', 50)));
        $offset = ($page - 1) * $limit;

        $campaigns = $this->campaignRepository->createQueryBuilder('c')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $total = $this->campaignRepository->count([]);

        return $this->json([
            'campaigns' => array_map(fn($c) => [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'status' => $c->getStatus(),
                'organizationId' => $c->getOrganization()?->getId(),
                'organizationName' => $c->getOrganization()?->getName(),
                'userName' => $c->getUser()->getName(),
                'totalContacts' => $c->getTotalContacts(),
                'dispatchedCount' => $c->getDispatchedCount(),
                'createdAt' => $c->getCreatedAt()->format('c'),
            ], $campaigns),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit),
        ]);
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'organizationId' => $user->getOrganization()?->getId(),
            'organizationName' => $user->getOrganization()?->getName(),
            'createdAt' => $user->getCreatedAt()->format('c'),
        ];
    }
}
