<?php

namespace Digbang\ResourceFilter\Filters;

use Digbang\ResourceFilter\Associations\Association;
use Digbang\ResourceFilter\Associations\AssociationExaminer;
use Digbang\ResourceFilter\Resources\AccessListProvider;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Illuminate\Container\Container;
use Digbang\ResourceFilter\Resources\AggregatedResource;
use Digbang\ResourceFilter\Resources\AggregatorResource;
use Digbang\ResourceFilter\Resources\IndirectAggregatedResource;

class ResourceFilter extends SQLFilter
{
    public const FILTER_NAME = 'resource-filter';
    public const FILTER_USER_ID = 'user_id';
    public const FILTER_USER_TYPE = 'user_type';

    /** @var AssociationExaminer */
    private $associationExaminer;
    /** @var AccessListProvider */
    private $accessListProvider;
    /** @var string */
    private $resourceAggregator;
    /** @var string */
    private $userId;
    /** @var string */
    private $userType;
    /** @var string */
    private $pk;
    /** @var string */
    private $userResources;

    public function addFilterConstraint(ClassMetadata $targetEntityMetadata, $targetTableAlias)
    {
        if (
            ! $targetEntityMetadata->getReflectionClass()->implementsInterface(AggregatorResource::class) &&
            ! $targetEntityMetadata->getReflectionClass()->implementsInterface(AggregatedResource::class) &&
            ! $targetEntityMetadata->isInheritanceTypeJoined()
        ) {
            return '';
        }

        //Constructor is "final". Cannot extend to inverse this dependencies.
        $this->initializeDependencies();

        $this->cleanUserId();
        $this->cleanUserType();

        $this->pk = $this->buildPK($targetEntityMetadata, $targetTableAlias);

        $this->userResources = $this->buildUserResourcesQuery();

        if ($targetEntityMetadata->getReflectionClass()->implementsInterface(AggregatorResource::class)) {
            return $this->buildUserResourcesFilter();
        }

        if ($targetEntityMetadata->getReflectionClass()->implementsInterface(AggregatedResource::class)) {
            return $this->buildAggregatedResourceFilter($targetEntityMetadata);
        }
        /**
         * WARNING!!
         * This implementation is incomplete and possibly very very broken!;
         * So, this is forced to return empty, so doctrine will not filter anything
         */
        if ($targetEntityMetadata->isInheritanceTypeJoined()) {
            return '';
            //return $this->buildAggregatedResourceChildFilter($targetEntityMetadata);
        }

        return '';
    }

    protected function initializeDependencies(): void
    {
        $container = Container::getInstance();
        $config = $container->get('config');

        $this->associationExaminer = $container->make(AssociationExaminer::class);
        $this->resourceAggregator = $config->get('resource-filter.resources.aggregator');

        $accessListProviderClass = $config->get('resource-filter.resources.access-list-provider');
        if ($accessListProviderClass) {
            $this->accessListProvider = $container->make($accessListProviderClass);
        }
    }

    protected function buildUserResourcesFilter(): string
    {
        if ($this->userResources) {
            return "{$this->pk} IN ($this->userResources)";
        }

        return '';
    }

    protected function buildUserResourcesQuery(): string
    {
        if ($this->accessListProvider) {
            return $this->accessListProvider->buildAccessList($this->userId)->getQuery()->getSQL();
        }

        $association = $this->associationExaminer->associationResolver($this->resourceAggregator, $this->userType);
        if ($association) {
            if (! $association->isManyToMany()) {
                return "
                    SELECT
                        {$association->getLeftTablePK()}
                    FROM
                        {$association->getLeftTable()}
                        INNER JOIN {$association->getRightTable()} ON
                            {$association->getJoinRightTableColumn()} = {$association->getJoinLeftTableColumn()}
                    WHERE
                        {$association->getRightTablePK()} = {$this->pk}
                ";
            }

            if ($association->isManyToMany()) {
                return "
                    SELECT
                        {$association->getJoinLeftTableColumn()}
                    FROM
                        {$association->getJoinTable()}
                    WHERE
                        {$association->getJoinRightTableColumn()} = {$this->userId}
                ";
            }
        }

        return '';
    }

    protected function buildDirectAssociationQuery(string $targetEntity, string $toEntity): string
    {
        $association = $this->associationExaminer->associationResolver($toEntity, $targetEntity);

        if ($association) {
            // Dividing the relation type when making each query makes ugly code, but a better performing query.
            if (! $association->isManyToMany()) {
                return "EXISTS (
                    SELECT 
                        1 
                    FROM 
                        {$association->getLeftTable()}
                        INNER JOIN {$association->getRightTable()} ON 
                            {$association->getJoinRightTableColumn()} = {$association->getJoinLeftTableColumn()}
                    WHERE 
                        {$association->getRightTablePK()} = {$this->pk}
                        AND {$association->getLeftTablePK()} IN ($this->userResources)
                )";
            }

            if ($association->isManyToMany()) {
                return "EXISTS (
                    SELECT 
                        1 
                    FROM 
                        {$association->getJoinTable()}
                    WHERE 
                        {$association->getJoinRightTableColumn()} = {$this->pk}
                        AND {$association->getJoinLeftTableColumn()} IN ($this->userResources)
                )";
            }
        }

        return '';
    }

    protected function buildIndirectAssociationQuery(string $from, string $to): string
    {
        $associations = $this->collectIndirectAssociations($from, $to);

        /** @var Association $left */
        $left = array_first($associations);
        /** @var Association $right */
        $right = array_last($associations);

        $query = "EXISTS (SELECT 1 FROM {$left->getLeftTable()} ";

        foreach ($associations as $association) {
            if ($association->isManyToMany()) {
                $query .= " INNER JOIN {$association->getJoinTable()} ON 
                                {$association->getJoinLeftTableColumn()} = {$association->getLeftTablePK()}";

                $query .= " INNER JOIN {$association->getRightTable()} ON 
                                {$association->getRightTablePK()} = {$association->getJoinRightTableColumn()}";
            } else {
                $query .= " INNER JOIN {$association->getRightTable()} ON 
                                {$association->getJoinRightTableColumn()} = {$association->getJoinLeftTableColumn()}";
            }
        }

        $query .= " 
            WHERE 
                {$left->getLeftTablePK()} = {$this->pk} 
                AND {$right->getRightTablePK()} IN ($this->userResources))";

        return $query;
    }

    private function cleanUserId(): void
    {
        $this->userId = str_replace("'", '', $this->getParameter(static::FILTER_USER_ID));
    }

    private function cleanUserType(): void
    {
        $this->userType = str_replace("'", '', $this->getParameter(static::FILTER_USER_TYPE));
    }

    private function buildPK(ClassMetadata $targetEntityMetadata, string $targetTableAlias): string
    {
        $pk = $this->associationExaminer->getEntityPrimaryKey($targetEntityMetadata);

        return "$targetTableAlias.$pk";
    }

    private function buildAggregatedResourceFilter(ClassMetadata $targetEntityMetadata): string
    {
        return $this->buildAssociationFilter(
            $targetEntityMetadata->getReflectionClass()->getName(),
            $this->resourceAggregator,
            $targetEntityMetadata->getReflectionClass()->implementsInterface(IndirectAggregatedResource::class)
        );
    }

    /**
     * Don't really know if this is working correctly
     * We don't have a good example of this anymore to check.
     */
    private function buildAggregatedResourceChildFilter(ClassMetadata $targetEntityMetadata): string
    {
        $childMetadata = null;
        foreach ($targetEntityMetadata->subClasses as $child) {
            $childMetadata = $this->associationExaminer->getClassMetadata($child);
            if ($childMetadata->getReflectionClass()->implementsInterface(AggregatedResource::class)) {
                break;
            }
        }

        if ($childMetadata) {
            return $this->buildAggregatedResourceFilter($childMetadata);
        }

        return '';
    }

    private function buildAssociationFilter(string $from, string $toEntity, bool $isIndirect): string
    {
        //Indirect association. Will follow the path provided by the interface
        if ($isIndirect) {
            return $this->buildIndirectAssociationQuery($from, $toEntity);
        }

        //Direct association: Having or not a M:N intermediate table
        return $this->buildDirectAssociationQuery($from, $toEntity);
    }

    /**
     * @param string $from
     * @param string $to
     * @return Association[]|array
     */
    private function collectIndirectAssociations(string $from, string $to): array
    {
        /** @var IndirectAggregatedResource $from */
        $associationPath = array_merge(
            (array) $from,
            $from::getResourceAssociationPath(),
            (array) $to
        );

        $associations = [];
        $length = \count($associationPath);
        for ($i = 1; $i < $length; ++$i) {
            $associations[] = $this->associationExaminer->associationResolver($associationPath[$i - 1], $associationPath[$i]);
        }

        return $associations;
    }
}
