<?php

declare(strict_types=1);

namespace CnA\Core\Providers;

use CnA\Core\ServiceProvider;
use CnA\Monitoring\QueryObserver;
use CnA\Orm\Model;

class MonitoringServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QueryObserver::class, function () {
            $observer = new QueryObserver();
            // Configure query budget from config if present
            $budget = config('database.query_budget');
            if ($budget !== null) {
                $observer->setQueryBudget((int) $budget);
            }
            return $observer;
        });

        $this->app->alias(QueryObserver::class, 'db.observer');
    }

    public function boot(): void
    {
        // Enforce strict lazy-loading prevention in local and testing environments (Section 8 of blueprint)
        if ($this->app->isLocal()) {
            Model::preventLazyLoading(true);
        }
    }
}

