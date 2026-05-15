<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\MeasureNode;
use App\Entity\Referential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MeasureNode>
 */
class MeasureNodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MeasureNode::class);
    }

    public function findRootNodesForReferential(Referential $referential): array
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.category', 'c')
            ->andWhere('m.parent IS NULL')
            ->andWhere('c.referential = :ref')
            ->setParameter('ref', $referential)
            ->orderBy('c.ordre', 'ASC')
            ->addOrderBy('m.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllForReferential(Referential $referential): array
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.category', 'c')
            ->andWhere('c.referential = :ref')
            ->setParameter('ref', $referential)
            ->orderBy('c.ordre', 'ASC')
            ->addOrderBy('m.parent', 'ASC')
            ->addOrderBy('m.ordre', 'ASC')
            ->addOrderBy('m.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllForCategory(Category $category): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.category = :category')
            ->setParameter('category', $category)
            ->orderBy('m.parent', 'ASC')
            ->addOrderBy('m.ordre', 'ASC')
            ->addOrderBy('m.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countLeafNodesByReferential(Referential $referential): int
    {
        return (int) $this->createQueryBuilder('mn')
            ->select('COUNT(mn.id)')
            ->innerJoin('mn.category', 'c')
            ->andWhere('c.referential = :referential')
            ->andWhere('mn.children IS EMPTY')
            ->setParameter('referential', $referential)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLeafNodesByReferential(Referential $referential): array
    {
        return $this->createQueryBuilder('mn')
            ->innerJoin('mn.category', 'c')
            ->andWhere('c.referential = :referential')
            ->andWhere('mn.children IS EMPTY')
            ->setParameter('referential', $referential)
            ->orderBy('c.ordre', 'ASC')
            ->addOrderBy('mn.ordre', 'ASC')
            ->addOrderBy('mn.code', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
