<?php

namespace Digbang\ResourceFilter;

use Illuminate\Support\ServiceProvider;

class ResourceFilterServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publish();
    }

    public function register()
    {

    }

    private function publish(): void
    {
        $root = \realpath(\dirname(__DIR__));

        $this->publishes([
                "$root/config/resource-filter.php" => config_path('resource-filter.php'),
            ],
            'config'
        );
    }
}
