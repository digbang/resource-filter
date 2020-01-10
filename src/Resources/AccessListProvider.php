<?php

namespace Digbang\ResourceFilter\Resources;

use Doctrine\ORM\QueryBuilder;

interface AccessListProvider
{
    public function buildAccessList(): QueryBuilder;
}
