<?php

declare(strict_types=1);

namespace CnA\Core\Providers;

use CnA\Contracts\Database\ConnectionInterface;
use CnA\Contracts\Database\ConnectionManagerInterface;
use CnA\Contracts\Database\TransactionManagerInterface;
use CnA\Core\ServiceProvider;
use CnA\Database\ConnectionManager;
use CnA\Database\TransactionManager;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConnectionManagerInterface::class, function () {
            $config = (array) config('database', []);
            $default = (string) config('database.default', 'sqlite');
            return new ConnectionManager($config, $default);
        });

        $this->app->alias(ConnectionManagerInterface::class, ConnectionManager::class);
        $this->app->alias(ConnectionManagerInterface::class, 'db');

        $this->app->bind(ConnectionInterface::class, function () {
            /** @var ConnectionManagerInterface $mgr */
            $mgr = $this->app->get(ConnectionManagerInterface::class);
            return $mgr->connection();
        });

        $this->app->bind(TransactionManagerInterface::class, function () {
            /** @var ConnectionInterface $conn */
            $conn = $this->app->get(ConnectionInterface::class);
            return new TransactionManager($conn);
        });
    }
}

