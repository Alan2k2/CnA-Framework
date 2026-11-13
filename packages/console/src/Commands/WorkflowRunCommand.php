<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Contracts\Workflow\WorkflowEngineInterface;

class WorkflowRunCommand extends Command
{
    protected string $name = 'workflow:run';
    protected string $description = 'Execute a registered workflow with optional context payload';

    public function handle(): int
    {
        $workflowName = $this->argument(0);
        if (empty($workflowName)) {
            $this->error('Workflow name is required. Usage: php cna workflow:run <name> [--context=JSON]');
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

        $context = [];
        $contextOpt = $this->option('context');
        if (!empty($contextOpt)) {
            $decoded = json_decode($contextOpt, true);
            if (is_array($decoded)) {
                $context = $decoded;
            }
        }

        $this->info("Executing workflow '{$workflowName}'...");
        $startTime = microtime(true);

        try {
            $result = $engine->run($workflowName, $context);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->info("Workflow completed in {$duration}ms.");
            $status = is_array($result) ? ($result['status'] ?? 'completed') : (method_exists($result, 'getState') ? $result->getState() : 'completed');
            $this->info("Final Status: " . $status);

            $contextData = is_array($result) ? ($result['context'] ?? []) : (method_exists($result, 'getContext') ? $result->getContext() : []);
            $rows = [];
            foreach ($contextData as $k => $v) {
                $valStr = is_scalar($v) ? (string) $v : json_encode($v);
                $rows[] = [$k, $valStr];
            }

            if (!empty($rows)) {
                $this->table(['Context Key', 'Value'], $rows);
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Workflow execution failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
