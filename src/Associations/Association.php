<?php

namespace Digbang\ResourceFilter\Associations;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

class Association
{
    /** @var int */
    private $type;
    /** @var string */
    private $associationName;
    /** @var string */
    private $leftTable;
    /** @var string */
    private $leftTablePK;
    /** @var string */
    private $rightTable;
    /** @var string */
    private $rightTablePK;
    /** @var string */
    private $joinTable;
    /** @var string */
    private $joinLeftTableColumn;
    /** @var string */
    private $joinRightTableColumn;

    public function __construct(string $associationName, string $leftTable, string $leftTablePK, string $rightTable, string $rightTablePK, array $association, bool $isInverted = false)
    {
        $this->associationName = $associationName;
        $this->leftTable = $leftTable;
        $this->leftTablePK = $leftTablePK;
        $this->rightTable = $rightTable;
        $this->rightTablePK = $rightTablePK;

        $this->deconstructAssociation($association, $isInverted);
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getAssociationName(): string
    {
        return $this->associationName;
    }

    public function getLeftTable(): string
    {
        return $this->leftTable;
    }

    public function getLeftTablePK(): string
    {
        return "$this->leftTable.$this->leftTablePK";
    }

    public function getRightTable(): string
    {
        return $this->rightTable;
    }

    public function getRightTablePK(): string
    {
        return "$this->rightTable.$this->rightTablePK";
    }

    public function getJoinTable(): ?string
    {
        return $this->joinTable;
    }

    public function getJoinLeftTableColumn(): string
    {
        $table = $this->isManyToMany() ? $this->joinTable : $this->leftTable;

        return "$table.$this->joinLeftTableColumn";
    }

    public function getJoinRightTableColumn(): string
    {
        $table = $this->isManyToMany() ? $this->joinTable : $this->rightTable;

        return "$table.$this->joinRightTableColumn";
    }

    /**
     * @param int|array $type
     */
    public function is($type): bool
    {
        $type = (array) $type;

        return in_array($this->type, $type, true);
    }

    public function isManyToMany(): bool
    {
        return $this->is(ClassMetadataInfo::MANY_TO_MANY);
    }

    public function isOneToMany(): bool
    {
        return $this->is(ClassMetadataInfo::ONE_TO_MANY);
    }

    public function isManyToOne(): bool
    {
        return $this->is(ClassMetadataInfo::MANY_TO_ONE);
    }

    private function deconstructAssociation(array $association, bool $isInverted): void
    {
        $this->type = $association['type'];

        if ($this->isManyToMany()) {
            $this->joinTable = $association['joinTable']['name'];
            $this->joinLeftTableColumn = $association['joinTable']['joinColumns'][0]['name'];
            $this->joinRightTableColumn = $association['joinTable']['inverseJoinColumns'][0]['name'];
        } else {
            $this->joinLeftTableColumn = $association['joinColumns'][0]['name'];
            $this->joinRightTableColumn = $association['joinColumns'][0]['referencedColumnName'];
        }

        if ($isInverted) {
            list($this->joinLeftTableColumn, $this->joinRightTableColumn) = [$this->joinRightTableColumn, $this->joinLeftTableColumn];
        }
    }
}
