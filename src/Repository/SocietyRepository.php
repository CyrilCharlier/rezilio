<?php

namespace App\Repository;

use App\Entity\Society;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Society>
 */
class SocietyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Society::class);
    }

    public function createAccessibleForUserQueryBuilder(?User $user): QueryBuilder
    {
        $qb = $this->createQueryBuilder('s')
            ->orderBy('s.name', 'ASC');

        if (!$user instanceof User) {
            return $qb->andWhere('1 = 0');
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $qb;
        }

        return $qb
            ->innerJoin('s.userSocieties', 'us')
            ->andWhere('us.user = :user')
            ->setParameter('user', $user);
    }
}
