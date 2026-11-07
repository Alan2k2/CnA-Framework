<?php

declare(strict_types=1);

namespace CnA\Core\Providers;

use CnA\Cache\CacheManager;
use CnA\Contracts\Cache\CacheInterface;
use CnA\Core\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheInterface::class, function () {
            $config = (array) config('cache', []);
            $default = (string) config('cache.default', 'array');
            return new CacheManager($config, $default);
        });

        $this->app->alias(CacheInterface::class, CacheManager::class);
        $this->app->alias(CacheInterface::class, 'cache');
    }
}

