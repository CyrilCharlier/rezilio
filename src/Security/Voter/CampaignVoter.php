<?php

namespace App\Security\Voter;

use App\Entity\Campaign;
use App\Entity\User;
use App\Repository\UserSocietyRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;


class CampaignVoter extends Voter
{
    public const VIEW = 'CAMPAIGN_VIEW';
    public const DELETE = 'CAMPAIGN_DELETE';

    public function __construct(
        private readonly Security $security,
        private readonly UserSocietyRepository $userSocietyRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::VIEW,
            self::DELETE,
        ], true) && $subject instanceof Campaign;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote=null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        /** @var Campaign $campaign */
        $campaign = $subject;
        $society = $campaign->getSociety();

        if (!$society) {
            return false;
        }

        $userSociety = $this->userSocietyRepository->findOneBy([
            'user' => $user,
            'society' => $society,
        ]);

        if (!$userSociety) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => true,
            self::DELETE => true,
            default => false,
        };
    }
}
