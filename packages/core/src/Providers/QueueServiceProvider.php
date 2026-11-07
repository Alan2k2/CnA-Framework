<?php

declare(strict_types=1);

namespace CnA\Core\Providers;

use CnA\Contracts\Queue\QueueInterface;
use CnA\Core\ServiceProvider;
use CnA\Queue\QueueManager;

class QueueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QueueInterface::class, function () {
            $config = (array) config('queue', []);
            $default = (string) config('queue.default', 'sync');
            return new QueueManager($config, $this->app, $default);
        });

        $this->app->alias(QueueInterface::class, QueueManager::class);
        $this->app->alias(QueueInterface::class, 'queue');
    }
}

