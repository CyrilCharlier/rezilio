<?php

namespace App\Repository;

use App\Entity\Campaign;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Campaign>
 */
class CampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Campaign::class);
    }

    public function findAccessibleForUser(User $user, ?string $search): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.society', 's')
            ->addSelect('s')
            ->orderBy('c.startDate', 'DESC');

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $qb->getQuery()->getResult();
        }

        $qb
            ->innerJoin('s.userSocieties', 'us')
            ->andWhere('us.user = :user')
            ->setParameter('user', $user);
            if ($search) {
                $qb
                ->innerJoin('c.referential', 'r')
                ->andWhere('c.name LIKE :search OR s.name LIKE :search OR r.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
            }

        return $qb->getQuery()->getResult();
    }
}
