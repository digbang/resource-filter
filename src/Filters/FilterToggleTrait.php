<?php

namespace Digbang\ResourceFilter\Filters;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Filter\SQLFilter;

trait FilterToggleTrait
{
    private $filters;

    /**
     * @return EntityManager
     */
    abstract protected function getEntityManager();

    /**
     * @param array|string $names
     */
    private function disableFilters($names)
    {
        $names = (array) $names;

        $addedFilters = $this->getEntityManager()->getFilters();

        foreach ($names as $disabling) {
            try {
                $this->filters[$disabling] = $addedFilters->getFilter($disabling);
                $addedFilters->disable($disabling);
            } catch (\Exception $e) {
                //Do nothing
            }
        }
    }

    /**
     * @param array|string $names
     */
    private function enableFilters($names)
    {
        $names = (array) $names;

        $addedFilters = $this->getEntityManager()->getFilters();

        foreach ($names as $enabling) {
            try {
                $filter = $addedFilters->enable($enabling);

                /** @var SQLFilter $disabledFilter */
                if ($disabledFilter = $this->filters[$enabling]) {
                    $params = unserialize($disabledFilter->__toString());

                    foreach ($params as $name => $content) {
                        $filter->setParameter($name, $content['value'], $content['type']);
                    }
                }
            } catch (\Exception $e) {
                //Do nothing
            }
        }
    }
}
