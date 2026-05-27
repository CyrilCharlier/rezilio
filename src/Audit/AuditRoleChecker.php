<?php

namespace App\Audit;

use DH\Auditor\Security\RoleCheckerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class RoleChecker implements RoleCheckerInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function __invoke(string $entity, string $scope): bool
    {
        $user = $this->security->getUser();

        if (null === $user) {
            return false;
        }

        return $this->security->isGranted('ROLE_SUPER_ADMIN')
            || $this->security->isGranted('ROLE_ADMIN');
    }
}
