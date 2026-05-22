<?php

namespace App\Service;

use App\Repository\ReferentialRepository;

class ReferentialExportService
{
    public function __construct(
        private ReferentialRepository $referentialRepository,
    ) {}

    public function exportToArray(int $referentialId): array
    {
        $referential = $this->referentialRepository->find($referentialId);
        if (!$referential) {
            throw new \InvalidArgumentException('Référentiel introuvable');
        }

        $data = [
            'schema_version'     => '1.0',
            'exported_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'referential' => [
                'id'         => $referential->getId(),
                'code'       => $referential->getCode(),
                'label'      => $referential->getLabel(),
                'description'=> $referential->getDescription(),
                'version'    => $referential->getVersion(),
                'active'     => $referential->isActive(),
                'categories' => [],
            ],
        ];

        foreach ($referential->getCategories() as $category) {
            $catData = [
                'id'            => $category->getId(),
                'name'          => $category->getName(),
                'description'   => $category->getDescription(),
                'active'        => $category->isActive(),
                'ordre'         => $category->getOrdre(),
                'nodes'         => [],
            ];

            foreach ($category->getNodes() as $node) {
                $catData['nodes'][] = [
                    'id'            => $node->getId(),
                    'code'          => $node->getCode(),
                    'label'         => $node->getLabel(),
                    'description'   => $node->getDescription(),
                    'active'        => $node->isActive(),
                    'ordre'         => $node->getOrdre(),
                    'appliesToEE'   => $node->isAppliesToEE(),
                    'appliesToEI'   => $node->isAppliesToEI(),
                    'parent_id'     => $node->getParent()?->getId(),
                ];
            }

            $data['referential']['categories'][] = $catData;
        }

        return $data;
    }
}
