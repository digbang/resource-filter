<?php

namespace Digbang\ResourceFilter\Resources;

interface IndirectAggregatedResource extends AggregatedResource
{
    public static function getResourceAssociationPath(): array;
}
