<?php

namespace App\Repository;

use App\Entity\Evidence;
use App\Entity\MeasureReview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evidence>
 */
class EvidenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evidence::class);
    }

    /**
     * @return Evidence[]
     */
    public function findByMeasureReview(MeasureReview $measureReview): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.measureReview = :measureReview')
            ->setParameter('measureReview', $measureReview)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByMeasureReview(MeasureReview $measureReview): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.measureReview = :measureReview')
            ->setParameter('measureReview', $measureReview)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
