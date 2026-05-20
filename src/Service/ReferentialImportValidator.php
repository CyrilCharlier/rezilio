<?php

namespace App\Service;

class ReferentialImportValidator
{
    public function validateImportPayload(array $data): void
    {
        if (!isset($data['schema_version']) || !is_string($data['schema_version'])) {
            throw new \InvalidArgumentException('Champ "schema_version" manquant ou invalide.');
        }

        if ($data['schema_version'] !== '1.0') {
            throw new \InvalidArgumentException(sprintf(
                'Version de schéma non supportée : "%s".',
                $data['schema_version']
            ));
        }

        if (!isset($data['exported_at']) || !is_string($data['exported_at'])) {
            throw new \InvalidArgumentException('Champ "exported_at" manquant ou invalide.');
        }

        if (!isset($data['referential']) || !is_array($data['referential'])) {
            throw new \InvalidArgumentException('Champ "referential" manquant ou invalide.');
        }

        $referential = $data['referential'];

        foreach (['code', 'label', 'categories', 'active'] as $field) {
            if (!array_key_exists($field, $referential)) {
                throw new \InvalidArgumentException(sprintf(
                    'Champ "referential.%s" manquant.',
                    $field
                ));
            }
        }

        if (!is_string($referential['code']) || '' === trim($referential['code'])) {
            throw new \InvalidArgumentException('Champ "referential.code" invalide.');
        }

        if (!is_string($referential['label']) || '' === trim($referential['label'])) {
            throw new \InvalidArgumentException('Champ "referential.label" invalide.');
        }

        if (!is_bool($referential['active'])) {
            throw new \InvalidArgumentException('Champ "referential.active" invalide.');
        }

        if (!is_array($referential['categories'])) {
            throw new \InvalidArgumentException('Champ "referential.categories" invalide.');
        }

        foreach ($referential['categories'] as $categoryIndex => $category) {
            if (!is_array($category)) {
                throw new \InvalidArgumentException(sprintf(
                    'Catégorie #%d invalide.',
                    $categoryIndex
                ));
            }

            foreach (['name', 'active', 'ordre', 'nodes'] as $field) {
                if (!array_key_exists($field, $category)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Champ "referential.categories[%d].%s" manquant.',
                        $categoryIndex,
                        $field
                    ));
                }
            }

            if (!is_string($category['name']) || '' === trim($category['name'])) {
                throw new \InvalidArgumentException(sprintf(
                    'Champ "referential.categories[%d].name" invalide.',
                    $categoryIndex
                ));
            }

            if (!is_int($category['ordre'])) {
                throw new \InvalidArgumentException(sprintf(
                    'Champ "referential.categories[%d].ordre" invalide.',
                    $categoryIndex
                ));
            }

            if (!is_bool($category['active'])) {
                throw new \InvalidArgumentException(sprintf(
                    'Champ "referential.categories[%d].active" invalide.',
                    $categoryIndex
                ));
            }

            if (!is_array($category['nodes'])) {
                throw new \InvalidArgumentException(sprintf(
                    'Champ "referential.categories[%d].nodes" invalide.',
                    $categoryIndex
                ));
            }

            $nodeIds = [];

            foreach ($category['nodes'] as $nodeIndex => $node) {
                if (!is_array($node)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Node #%d de la catégorie #%d invalide.',
                        $nodeIndex,
                        $categoryIndex
                    ));
                }

                foreach (['id', 'code', 'label', 'ordre', 'active', 'parent_id'] as $field) {
                    if (!array_key_exists($field, $node)) {
                        throw new \InvalidArgumentException(sprintf(
                            'Champ "referential.categories[%d].nodes[%d].%s" manquant.',
                            $categoryIndex,
                            $nodeIndex,
                            $field
                        ));
                    }
                }

                if (!is_int($node['id'])) {
                    throw new \InvalidArgumentException(sprintf(
                        'Champ "referential.categories[%d].nodes[%d].id" invalide.',
                        $categoryIndex,
                        $nodeIndex
                    ));
                }

                if (isset($nodeIds[$node['id']])) {
                    throw new \InvalidArgumentException(sprintf(
                        'ID node dupliqué dans la catégorie #%d : %d.',
                        $categoryIndex,
                        $node['id']
                    ));
                }

                $nodeIds[$node['id']] = true;

                if (!is_string($node['code']) || '' === trim($node['code'])) {
                    throw new \InvalidArgumentException(sprintf(
                        'Champ "referential.categories[%d].nodes[%d].code" invalide.',
                        $categoryIndex,
                        $nodeIndex
                    ));
                }

                if (!is_string($node['label']) || '' === trim($node['label'])) {
                    throw new \InvalidArgumentException(sprintf(
                        'Champ "referential.categories[%d].nodes[%d].label" invalide.',
                        $categoryIndex,
                        $nodeIndex
                    ));
                }

                if (!is_int($node['ordre'])) {
                    throw new \InvalidArgumentException(sprintf(
                        'Champ "referential.categories[%d].nodes[%d].ordre" invalide.',
                        $categoryIndex,
                        $nodeIndex
                    ));
                }

                if (!is_bool($node['active'])) {
                    throw new \InvalidArgumentException(sprintf(
                        'Champ "referential.categories[%d].nodes[%d].active" invalide.',
                        $categoryIndex,
                        $nodeIndex
                    ));
                }

                if (!is_null($node['parent_id']) && !is_int($node['parent_id'])) {
                    throw new \InvalidArgumentException(sprintf(
                        'Champ "referential.categories[%d].nodes[%d].parent_id" invalide.',
                        $categoryIndex,
                        $nodeIndex
                    ));
                }
            }

            foreach ($category['nodes'] as $nodeIndex => $node) {
                $parentId = $node['parent_id'];

                if (null === $parentId) {
                    continue;
                }

                if (!isset($nodeIds[$parentId])) {
                    throw new \InvalidArgumentException(sprintf(
                        'Le parent_id %d du node #%d dans la catégorie #%d ne référence aucun node de cette catégorie.',
                        $parentId,
                        $nodeIndex,
                        $categoryIndex
                    ));
                }

                if ($parentId === $node['id']) {
                    throw new \InvalidArgumentException(sprintf(
                        'Le node #%d dans la catégorie #%d ne peut pas être son propre parent.',
                        $nodeIndex,
                        $categoryIndex
                    ));
                }

            }

            $this->validateNoCyclesInCategory($category['nodes'], $categoryIndex);
        }
    }

    private function validateNoCyclesInCategory(array $nodes, int $categoryIndex): void
    {
        $parentMap = [];

        foreach ($nodes as $node) {
            $parentMap[$node['id']] = $node['parent_id'];
        }

        foreach ($parentMap as $nodeId => $parentId) {
            $visited = [];
            $currentId = $nodeId;

            while (null !== $currentId) {
                if (isset($visited[$currentId])) {
                    throw new \InvalidArgumentException(sprintf(
                        'Cycle détecté dans la catégorie #%d en suivant la chaîne des parents depuis le node ID %d.',
                        $categoryIndex,
                        $nodeId
                    ));
                }

                $visited[$currentId] = true;
                $currentId = $parentMap[$currentId] ?? null;
            }
        }
    }
}