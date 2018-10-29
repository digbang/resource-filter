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

    public function associationResolver(string $leftSide, string $rightSide): ?Association
    {
        $leftSideMetadata = $this->getClassMetadata($leftSide);
        $rightSideMetadata = $this->getClassMetadata($rightSide);
        $association = array_first($leftSideMetadata->getAssociationsByTargetClass($rightSide));

        if (! $association) {
            return null;
        }

        $leftTable = $leftSideMetadata->getTableName();
        $leftTableColumn = $this->getEntityPrimaryKey($leftSideMetadata);
        $rightTable = $rightSideMetadata->getTableName();
        $rightTableColumn = $this->getEntityPrimaryKey($rightSideMetadata);

        $isInverted = false;
        if (! $association['isOwningSide']) {
            $association = array_first($rightSideMetadata->getAssociationsByTargetClass($leftSide));
            $isInverted = true;
        }

        return new Association($leftTable, $leftTableColumn, $rightTable, $rightTableColumn, $association, $isInverted);
    }

    public function getClassMetadata(string $targetEntity): ClassMetadata
    {
        return $this->entityManager->getClassMetadata($targetEntity);
    }
}
