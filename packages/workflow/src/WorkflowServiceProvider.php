<?php

declare(strict_types=1);

namespace CnA\Workflow;

use CnA\Contracts\Workflow\WorkflowEngineInterface;
use CnA\Core\ServiceProvider;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WorkflowEngineInterface::class, function () {
            return new WorkflowEngine();
        });
        $this->app->alias(WorkflowEngineInterface::class, WorkflowEngine::class);
        $this->app->alias(WorkflowEngineInterface::class, 'workflow');
    }

    public function boot(): void
    {
    }
}

