<?php

namespace App\Repository;

use App\Entity\Campaign;
use App\Entity\MeasureReview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MeasureReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MeasureReview::class);
    }

    public function findKanbanCardsByCampaign(Campaign $campaign, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('mr')
            ->addSelect('mn', 'c')
            ->innerJoin('mr.campaign', 'campaign')
            ->innerJoin('mr.measure', 'mn')
            ->leftJoin('mn.category', 'c')
            ->andWhere('mr.campaign = :campaign')
            ->setParameter('campaign', $campaign)
            ->orderBy('c.ordre', 'ASC')
            ->addOrderBy('mn.ordre', 'ASC')
            ->addOrderBy('mn.code', 'ASC');

        if (!empty($filters['search'])) {
            $qb
                ->andWhere('mn.code LIKE :search OR mn.label LIKE :search OR c.name LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['category'])) {
            $qb
                ->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $filters['category']);
        }

        if (!empty($filters['status'])) {
            $qb
                ->andWhere('mr.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['applicability'])) {
            if ('ee' === $filters['applicability']) {
                $qb->andWhere('mn.appliesToEE = true');
            }

            if ('ei' === $filters['applicability']) {
                $qb->andWhere('mn.appliesToEI = true');
            }
        }

        return $qb->getQuery()->getResult();
    }

    public function findCategoriesByCampaign(Campaign $campaign): array
    {
        return $this->createQueryBuilder('mr')
            ->select('DISTINCT c.id AS id, c.name AS name')
            ->innerJoin('mr.measure', 'mn')
            ->innerJoin('mn.category', 'c')
            ->andWhere('mr.campaign = :campaign')
            ->setParameter('campaign', $campaign)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
