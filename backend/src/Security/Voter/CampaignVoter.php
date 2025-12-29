<?php

namespace App\Security\Voter;

use App\Entity\Campaign;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CampaignVoter extends Voter
{
    public const VIEW = 'CAMPAIGN_VIEW';
    public const EDIT = 'CAMPAIGN_EDIT';
    public const DELETE = 'CAMPAIGN_DELETE';
    public const START = 'CAMPAIGN_START';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::START])
            && $subject instanceof Campaign;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Campaign $campaign */
        $campaign = $subject;

        // Super admin can do everything
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check if user belongs to the same organization
        if ($user->getOrganization() && $campaign->getOrganization()) {
            if ($user->getOrganization()->getId() !== $campaign->getOrganization()->getId()) {
                return false;
            }

            // Organization admin can manage all campaigns in their org
            if ($user->isOrgAdmin()) {
                return true;
            }
        }

        // Regular users can only access their own campaigns
        return $campaign->getUser()->getId() === $user->getId();
    }
}
