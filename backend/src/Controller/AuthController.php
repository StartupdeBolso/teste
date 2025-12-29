<?php

namespace App\Controller;

use App\Entity\Organization;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
            return $this->json(['error' => 'Missing required fields'], 400);
        }

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingUser) {
            return $this->json(['error' => 'User already exists'], 400);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setName($data['name']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Handle organization
        $organization = null;
        $isOrgAdmin = false;

        if (isset($data['organizationId'])) {
            // Join existing organization
            $organization = $this->entityManager->getRepository(Organization::class)
                ->find($data['organizationId']);

            if (!$organization || !$organization->isActive()) {
                return $this->json(['error' => 'Invalid organization'], 400);
            }
        } elseif (isset($data['organizationName'])) {
            // Create new organization
            $organization = new Organization();
            $organization->setName($data['organizationName']);

            if (isset($data['webhookUrl'])) {
                $organization->setWebhookUrl($data['webhookUrl']);
            }

            if (isset($data['phoneNumber'])) {
                $organization->setPhoneNumber($data['phoneNumber']);
            }

            $this->entityManager->persist($organization);

            // First user of organization becomes admin
            $user->addRole('ROLE_ORG_ADMIN');
            $isOrgAdmin = true;
        }

        if ($organization) {
            $user->setOrganization($organization);
        }

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['error' => 'Validation failed', 'details' => (string) $errors], 400);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'organizationId' => $organization?->getId(),
                'organizationName' => $organization?->getName(),
                'isOrgAdmin' => $isOrgAdmin,
            ],
        ], 201);
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        $organization = $user->getOrganization();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'isSuperAdmin' => $user->isSuperAdmin(),
            'isOrgAdmin' => $user->isOrgAdmin(),
            'organization' => $organization ? [
                'id' => $organization->getId(),
                'name' => $organization->getName(),
                'webhookUrl' => $organization->getWebhookUrl(),
                'phoneNumber' => $organization->getPhoneNumber(),
                'isActive' => $organization->isActive(),
            ] : null,
            'createdAt' => $user->getCreatedAt()->format('c'),
        ]);
    }
}
