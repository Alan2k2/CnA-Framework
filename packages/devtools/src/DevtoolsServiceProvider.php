<?php

declare(strict_types=1);

namespace CnA\Devtools;

use CnA\Contracts\Telemetry\TracerInterface;
use CnA\Core\ServiceProvider;

class DevtoolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TracerInterface::class, function () {
            return new Tracer();
        });

        $this->app->alias(TracerInterface::class, 'tracer');
    }
}
