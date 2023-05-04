<?php

namespace Digbang\ResourceFilter\Middlewares;

use Digbang\ResourceFilter\Filters\ResourceFilter;
use Digbang\ResourceFilter\Resources\ResourceManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;

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
        $user = $request->user();

        $userType = \get_class($user);
        if ($user instanceof \Doctrine\ORM\Proxy\Proxy) {
            $userType = \Doctrine\Common\Util\ClassUtils::getClass($user);
        }

        if ($this->shouldApplyFilter($user)) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->registry->getManager();
            $entityManager->getConfiguration()->addFilter(ResourceFilter::FILTER_NAME, ResourceFilter::class);

            /** @var ResourceFilter $filter */
            $filter = $entityManager->getFilters()->enable(ResourceFilter::FILTER_NAME);

            $filter->setParameter(ResourceFilter::FILTER_USER_ID, $user->getId(), Type::INTEGER);
            $filter->setParameter(ResourceFilter::FILTER_USER_TYPE, $userType, Type::STRING);
        }

        return $next($request);
    }

    protected function shouldApplyFilter($user)
    {
        return
            $user instanceof ResourceManager &&
            ! \in_array($user->getId(), $this->config->get('resource-filter.users.always-allow'))
        ;
    }
}
