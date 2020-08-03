<?php

namespace Digbang\ResourceFilter\Associations;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

class AssociationExaminer
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getEntityPrimaryKey(ClassMetadata $targetEntityMetadata): string
    {
        return array_first($targetEntityMetadata->getIdentifierColumnNames());
    }

    /**
     * @return array|Association[]
     */
    public function associationResolver(string $leftSide, string $rightSide): array
    {
        $leftSideMetadata = $this->getClassMetadata($leftSide);
        $rightSideMetadata = $this->getClassMetadata($rightSide);

        $associations = $leftSideMetadata->getAssociationsByTargetClass($rightSide);

        if (! $associations) {
            return [];
        }

        $leftTable = $leftSideMetadata->getTableName();
        $leftTableColumn = $this->getEntityPrimaryKey($leftSideMetadata);
        $rightTable = $rightSideMetadata->getTableName();
        $rightTableColumn = $this->getEntityPrimaryKey($rightSideMetadata);

        $compiled = [];
        foreach ($associations as $association) {
            $isInverted = false;
            if (! $association['isOwningSide']) {
                $association = array_first($rightSideMetadata->getAssociationsByTargetClass($leftSide));
                $isInverted = true;
            }

            $compiled[] = new Association($association['fieldName'], $leftTable, $leftTableColumn, $rightTable, $rightTableColumn, $association, $isInverted);
        }

        return $compiled;
    }

    public function getClassMetadata(string $targetEntity): ClassMetadata
    {
        return $this->entityManager->getClassMetadata($targetEntity);
    }
}
