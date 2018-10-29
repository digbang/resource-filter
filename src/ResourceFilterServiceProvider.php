<?php

namespace Digbang\ResourceFilter;

use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;

class ResourceFilterServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            $this->getConfigPath() => $this->getProjectConfigPath('resource-filter.php'),
        ], 'config');
    }

    public function register()
    {

    }

    /**
     * @return string
     */
    protected function getConfigPath(): string
    {
        return __DIR__ . '/../config/resource-filter.php';
    }

    private function getProjectConfigPath(string $path): string
    {
        return Container::getInstance()->make('path.config') . DIRECTORY_SEPARATOR . $path;
    }
}
