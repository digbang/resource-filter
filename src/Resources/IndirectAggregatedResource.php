<?php

namespace Pareto\Pago\Util\Resources;

interface IndirectAggregatedResource extends AggregatedResource
{
    public static function getResourceAssociationPath(): array;
}
