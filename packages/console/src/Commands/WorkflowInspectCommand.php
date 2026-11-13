<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Contracts\Workflow\WorkflowEngineInterface;

class WorkflowInspectCommand extends Command
{
    protected string $name = 'workflow:inspect';
    protected string $description = 'Inspect a workflow steps, states, conditions, and compensation logic';

    public function handle(): int
    {
        $workflowName = $this->argument(0);
        if (empty($workflowName)) {
            $this->error('Workflow name is required. Usage: php cna workflow:inspect <name>');
            return Command::INVALID;
        }

        if (!$this->app->has(WorkflowEngineInterface::class) && !$this->app->has('workflow')) {
            $this->error('Workflow engine is not registered.');
            return Command::FAILURE;
        }

        /** @var WorkflowEngineInterface $engine */
        $engine = $this->app->make(WorkflowEngineInterface::class);
        $workflow = $engine->get($workflowName);

        if ($workflow === null) {
            $this->error("Workflow '{$workflowName}' not found.");
            return Command::FAILURE;
        }

        $this->info("Workflow: {$workflow->getName()}");
        $this->info("Initial State: {$workflow->getState()}");

        $steps = $workflow->getSteps();
        $this->info("Total Steps: " . count($steps));

        $rows = [];
        $index = 1;
        foreach ($steps as $step) {
            $rows[] = [
                (string) $index++,
                $step->getName(),
                $step->getCompensate() !== null ? 'Yes' : 'No',
            ];
        }

        $this->table(['#', 'Step Name', 'Has Compensation (Saga)'], $rows);
        return Command::SUCCESS;
    }
}

