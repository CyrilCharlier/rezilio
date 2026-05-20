<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\MeasureNode;
use App\Entity\Referential;
use Doctrine\ORM\EntityManagerInterface;

class ReferentialImportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReferentialImportValidator $validator,
    ) {}

    public function importFromArray(array $data): Referential
    {
        $this->validator->validateImportPayload($data);

        return $this->entityManager->wrapInTransaction(function () use ($data) {
            $referentialData = $data['referential'];

            $referential = new Referential();
            $referential->setCode($referentialData['code']);
            $referential->setLabel($referentialData['label']);
            $referential->setDescription($referentialData['description'] ?? null);
            $referential->setVersion($referentialData['version'] ?? null);
            $referential->setActive($referentialData['active']);

            $this->entityManager->persist($referential);

            $nodeMap = [];

            foreach ($referentialData['categories'] as $categoryData) {
                $category = new Category();
                $category->setName($categoryData['name']);
                $category->setDescription($categoryData['description'] ?? null);
                $category->setActive($categoryData['active']);
                $category->setOrdre($categoryData['ordre']);
                $category->setReferential($referential);

                $this->entityManager->persist($category);

                foreach ($categoryData['nodes'] as $nodeData) {
                    $node = new MeasureNode();
                    $node->setCode($nodeData['code']);
                    $node->setLabel($nodeData['label']);
                    $node->setDescription($nodeData['description'] ?? null);
                    $node->setActive($nodeData['active']);
                    $node->setOrdre($nodeData['ordre']);
                    $node->setAppliesToEE($nodeData['appliesToEE'] ?? false);
                    $node->setAppliesToEI($nodeData['appliesToEI'] ?? false);
                    $node->setCategory($category);

                    $this->entityManager->persist($node);

                    $nodeMap[$nodeData['id']] = [
                        'entity' => $node,
                        'parent_id' => $nodeData['parent_id'],
                    ];
                }
            }

            foreach ($nodeMap as $oldId => $item) {
                $parentId = $item['parent_id'];

                if (null === $parentId) {
                    continue;
                }

                if (!isset($nodeMap[$parentId])) {
                    throw new \RuntimeException(sprintf(
                        'Parent introuvable pendant l’import pour le node source ID %d.',
                        $oldId
                    ));
                }

                $item['entity']->setParent($nodeMap[$parentId]['entity']);
            }

            $this->entityManager->flush();

            return $referential;
        });
    }
}