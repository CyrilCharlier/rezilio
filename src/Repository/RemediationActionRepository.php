<?php

namespace App\Repository;

use App\Entity\RemediationAction;
use App\Enum\RemediationActionStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RemediationAction>
 */
class RemediationActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RemediationAction::class);
    }

    /**
     * @return RemediationAction[]
     */
    public function findForDashboard(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('ra')
            ->leftJoin('ra.measureReview', 'mr')
            ->leftJoin('mr.campaign', 'c')
            ->addSelect('mr', 'c')
            ->orderBy('ra.dueDate', 'ASC')
            ->addOrderBy('ra.updatedAt', 'DESC');

        if (!empty($filters['search'])) {
            $qb
                ->andWhere('ra.title LIKE :search OR ra.description LIKE :search OR c.name LIKE :search')
                ->setParameter('search', '%'.$filters['search'].'%');
        }

        if (!empty($filters['status'])) {
            $qb
                ->andWhere('ra.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $qb
                ->andWhere('ra.priority = :priority')
                ->setParameter('priority', $filters['priority']);
        }

        if (!empty($filters['campaign'])) {
            $qb
                ->andWhere('c.id = :campaign')
                ->setParameter('campaign', (int) $filters['campaign']);
        }

        return $qb->getQuery()->getResult();
    }

    public function countDashboardMetrics(array $filters = []): array
    {
        $actions = $this->findForDashboard($filters);

        $metrics = [
            'total' => 0,
            'draft' => 0,
            'open' => 0,
            'in_progress' => 0,
            'done' => 0,
            'cancelled' => 0,
            'overdue' => 0,
        ];

        $today = new \DateTimeImmutable('today');

        foreach ($actions as $action) {
            ++$metrics['total'];

            $status = $action->getStatus()?->value;
            if ($status && array_key_exists($status, $metrics)) {
                ++$metrics[$status];
            }

            $dueDate = $action->getDueDate();
            if (
                $dueDate instanceof \DateTimeInterface
                && $dueDate < $today
                && !in_array($action->getStatus(), [RemediationActionStatus::DONE, RemediationActionStatus::CANCELLED], true)
            ) {
                ++$metrics['overdue'];
            }
        }

        return $metrics;
    }

    /**
     * @param RemediationAction[] $actions
     */
    public function groupByStatus(array $actions): array
    {
        $groups = [
            RemediationActionStatus::DRAFT->value => [],
            RemediationActionStatus::OPEN->value => [],
            RemediationActionStatus::IN_PROGRESS->value => [],
            RemediationActionStatus::DONE->value => [],
            RemediationActionStatus::CANCELLED->value => [],
        ];

        foreach ($actions as $action) {
            $status = $action->getStatus()?->value ?? RemediationActionStatus::DRAFT->value;
            $groups[$status][] = $action;
        }

        return $groups;
    }
}
