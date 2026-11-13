<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Contracts\Workflow\WorkflowEngineInterface;

class WorkflowListCommand extends Command
{
    protected string $name = 'workflow:list';
    protected string $description = 'List all registered workflow definitions';

    public function handle(): int
    {
        if (!$this->app->has(WorkflowEngineInterface::class) && !$this->app->has('workflow')) {
            $this->warn('Workflow engine is not registered in the application container.');
            return Command::FAILURE;
        }

        /** @var WorkflowEngineInterface $engine */
        $engine = $this->app->make(WorkflowEngineInterface::class);
        $workflows = $engine->all();

        if (empty($workflows)) {
            $this->info('No workflows registered.');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($workflows as $name => $workflow) {
            $steps = $workflow->getSteps();
            $rows[] = [
                $name,
                (string) count($steps),
                $workflow->getState(),
            ];
        }

        $this->table(['Workflow Name', 'Steps', 'Initial State'], $rows);
        return Command::SUCCESS;
    }
}

