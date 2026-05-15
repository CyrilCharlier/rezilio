<?php

namespace App\Repository;

use App\Entity\Campaign;
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

    public function findByFilters(?string $search, ?int $societyId): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.society', 's')->addSelect('s')
            ->leftJoin('c.referential', 'r')->addSelect('r')
            ->orderBy('c.startDate', 'DESC');

        if ($search) {
            $qb
                ->andWhere('c.name LIKE :search OR s.name LIKE :search OR r.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($societyId) {
            $qb
                ->andWhere('s.id = :societyId')
                ->setParameter('societyId', $societyId);
        }

        return $qb->getQuery()->getResult();
    }
}
