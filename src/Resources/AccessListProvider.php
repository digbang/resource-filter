<?php

namespace Digbang\ResourceFilter\Resources;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

interface AccessListProvider
{
    public function buildAccessList(ClassMetadata $targetEntityMetadata, $userId): QueryBuilder;
}
