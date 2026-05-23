<?php

namespace App\Security\Voter;

use App\Entity\Society;
use App\Entity\User;
use App\Entity\UserSociety;
use App\Enum\SocietyRole;
use App\Repository\UserSocietyRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class SocietyVoter extends Voter
{
    public const VIEW = 'SOCIETY_VIEW';
    public const USE = 'SOCIETY_USE';
    public const MANAGE = 'SOCIETY_MANAGE';

    public function __construct(
        private readonly UserSocietyRepository $userSocietyRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::USE, self::MANAGE], true)
            && $subject instanceof Society;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote=null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        /** @var Society $society */
        $society = $subject;

        $userSociety = $this->userSocietyRepository->findOneBy([
            'user' => $user,
            'society' => $society,
        ]);

        if (!$userSociety instanceof UserSociety) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($userSociety),
            self::USE => $this->canUse($userSociety),
            self::MANAGE => $this->canManage($userSociety),
            default => false,
        };
    }

    private function canView(UserSociety $userSociety): bool
    {
        return \in_array($userSociety->getRole(), [
            SocietyRole::ADMIN,
            SocietyRole::MEMBER,
            SocietyRole::READER,
        ], true);
    }

    private function canUse(UserSociety $userSociety): bool
    {
        return \in_array($userSociety->getRole(), [
            SocietyRole::ADMIN,
            SocietyRole::MEMBER,
        ], true);
    }

    private function canManage(UserSociety $userSociety): bool
    {
        return $userSociety->getRole() === SocietyRole::ADMIN;
    }
}
