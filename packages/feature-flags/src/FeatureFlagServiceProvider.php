<?php

declare(strict_types=1);

namespace CnA\Features;

use CnA\Contracts\Features\FeatureFlagManagerInterface;
use CnA\Core\ServiceProvider;

class FeatureFlagServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FeatureFlagManagerInterface::class, function () {
            $config = (array) $this->app->make('config')->get('features.flags', []);
            return new FeatureManager($config);
        });

        $this->app->alias(FeatureFlagManagerInterface::class, FeatureManager::class);
        $this->app->alias(FeatureFlagManagerInterface::class, 'features');
    }

    public function boot(): void
    {
    }
}

