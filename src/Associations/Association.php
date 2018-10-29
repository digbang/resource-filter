<?php

namespace Digbang\ResourceFilter\Associations;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

class Association
{
    /** @var int */
    private $type;
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

    public function __construct(string $leftTable, string $leftTablePK, string $rightTable, string $rightTablePK, array $association, bool $isInverted = false)
    {
        $this->leftTable = $leftTable;
        $this->leftTablePK = $leftTablePK;
        $this->rightTable = $rightTable;
        $this->rightTablePK = $rightTablePK;

        $this->deconstructAssociation($association, $isInverted);
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getLeftTable(): string
    {
        return $this->leftTable;
    }

    /**
     * @return string
     */
    public function getLeftTablePK(): string
    {
        return "$this->leftTablePK.$this->leftTablePK";
    }

    /**
     * @return string
     */
    public function getRightTable(): string
    {
        return $this->rightTable;
    }

    /**
     * @return string
     */
    public function getRightTablePK(): string
    {
        return "$this->rightTable.$this->rightTablePK";
    }

    /**
     * @return string
     */
    public function getJoinTable(): ?string
    {
        return $this->joinTable;
    }

    /**
     * @return string
     */
    public function getJoinLeftTableColumn(): string
    {
        $table = $this->isManyToMany() ? $this->joinTable : $this->leftTable;

        return "$table.$this->joinLeftTableColumn";
    }

    /**
     * @return string
     */
    public function getJoinRightTableColumn(): string
    {
        $table = $this->isManyToMany() ? $this->joinTable : $this->rightTable;

        return "$table.$this->joinRightTableColumn";
    }

    /**
     * @param int|array $type
     * @return bool
     */
    public function is($type): bool
    {
        $type = (array) $type;

        return \in_array($this->type, $type, true);
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

        $this->joinLeftTableColumn = $association['joinColumns'][0]['name'];
        $this->joinRightTableColumn = $association['joinColumns'][0]['referencedColumnName'];
        if ($this->isManyToMany()) {
            $this->joinTable = $association['joinTable']['name'];
            $this->joinLeftTableColumn = $association['joinTable']['joinColumns'][0]['name'];
            $this->joinRightTableColumn = $association['joinTable']['inverseJoinColumns'][0]['name'];
        }

        if ($isInverted) {
            list($this->joinLeftTableColumn, $this->joinRightTableColumn) = [$this->joinRightTableColumn, $this->joinLeftTableColumn];
        }
    }
}
