<?php

namespace App\Http\Middleware;

use Digbang\ResourceFilter\Filters\ResourceFilter;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Pareto\Pago\Util\Resources\ResourceManager;

class ResourceFilterMiddleware
{
    /** @var ManagerRegistry */
    protected $registry;
    /** @var Config */
    private $config;

    public function __construct(ManagerRegistry $registry, Config $config)
    {
        $this->registry = $registry;
        $this->config = $config;
    }

    public function handle(Request $request, \Closure $next)
    {
        /** @var ResourceManager $user */
        $user = $request->getUser();

        if ($user instanceof ResourceManager) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->registry->getManager();
            $entityManager->getConfiguration()->addFilter(ResourceFilter::FILTER_NAME, ResourceFilter::class);

            /** @var ResourceFilter $filter */
            $filter = $entityManager->getFilters()->enable(ResourceFilter::FILTER_NAME);

            $filter->setParameter(ResourceFilter::FILTER_RESOURCE_AGGREGATOR, $this->config->get('resource-filter.resource-aggregator-class'), Type::STRING);
            $filter->setParameter(ResourceFilter::FILTER_USER_ID, $user->getId(), Type::INTEGER);
            $filter->setParameter(ResourceFilter::FILTER_USER_TYPE, \get_class($user), Type::STRING);
        }

        return $next($request);
    }
}