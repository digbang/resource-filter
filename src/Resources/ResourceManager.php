<?php

namespace Pareto\Pago\Util\Resources;

interface ResourceManager
{
    /** @return int|string */
    public function getId();

    /** @return AggregatedResource[] */
    public function getAggregatedResources(): array;
}
