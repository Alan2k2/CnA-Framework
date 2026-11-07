<?php

declare(strict_types=1);

namespace CnA\Core\Providers;

use CnA\Contracts\Logging\LoggerInterface;
use CnA\Core\ServiceProvider;
use CnA\Logging\Logger;

class LoggingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoggerInterface::class, function () {
            $path = $this->app->storagePath('logs/cna.log');
            return new Logger($path);
        });

        $this->app->alias(LoggerInterface::class, Logger::class);
        $this->app->alias(LoggerInterface::class, 'logger');
    }
}

