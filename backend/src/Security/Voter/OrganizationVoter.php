<?php

namespace App\Security\Voter;

use App\Entity\Organization;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OrganizationVoter extends Voter
{
    public const VIEW = 'ORGANIZATION_VIEW';
    public const EDIT = 'ORGANIZATION_EDIT';
    public const DELETE = 'ORGANIZATION_DELETE';
    public const MANAGE_USERS = 'ORGANIZATION_MANAGE_USERS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::MANAGE_USERS])
            && $subject instanceof Organization;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Organization $organization */
        $organization = $subject;

        // Super admin can do everything
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check if user belongs to this organization
        if (!$user->getOrganization() || $user->getOrganization()->getId() !== $organization->getId()) {
            return false;
        }

        // Organization admin can manage their organization
        if ($user->isOrgAdmin()) {
            // But cannot delete the organization (only super admin can)
            if ($attribute === self::DELETE) {
                return false;
            }
            return true;
        }

        // Regular users can only view their organization
        return $attribute === self::VIEW;
    }
}
