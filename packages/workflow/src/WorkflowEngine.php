<?php

declare(strict_types=1);

namespace CnA\Workflow;

use CnA\Contracts\Workflow\WorkflowEngineInterface;
use CnA\Contracts\Workflow\WorkflowInterface;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class WorkflowEngine implements WorkflowEngineInterface
{
    /** @var array<string, WorkflowInterface> */
    protected array $workflows = [];

    /** @var array<string, array<string, mixed>> */
    protected array $executions = [];

    public function register(WorkflowInterface $workflow): void
    {
        $this->workflows[$workflow->getName()] = $workflow;
    }

    public function start(string $workflowName, array $input = []): string
    {
        if (!isset($this->workflows[$workflowName])) {
            throw new InvalidArgumentException("Workflow [{$workflowName}] is not registered.");
        }

        $workflow = $this->workflows[$workflowName];
        $executionId = 'wf_' . bin2hex(random_bytes(8));

        $context = $input;
        $history = [];
        $executedSteps = [];

        $this->executions[$executionId] = [
            'id' => $executionId,
            'workflow' => $workflowName,
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s'),
            'completed_at' => null,
            'context' => $context,
            'history' => &$history,
            'error' => null,
        ];

        try {
            foreach ($workflow->getSteps() as $step) {
                $stepName = $step->getName();
                $stepStart = microtime(true);

                $context = $step->execute($context);
                $executedSteps[] = $step;

                $durationMs = round((microtime(true) - $stepStart) * 1000, 2);
                $history[] = [
                    'step' => $stepName,
                    'status' => 'completed',
                    'duration_ms' => $durationMs,
                    'timestamp' => date('Y-m-d H:i:s'),
                ];
            }

            $this->executions[$executionId]['status'] = 'completed';
            $this->executions[$executionId]['completed_at'] = date('Y-m-d H:i:s');
            $this->executions[$executionId]['context'] = $context;
        } catch (Throwable $e) {
            $this->executions[$executionId]['status'] = 'failed';
            $this->executions[$executionId]['error'] = $e->getMessage();

            // Run compensation in reverse order for completed steps
            foreach (array_reverse($executedSteps) as $executedStep) {
                try {
                    $executedStep->compensate($context);
                } catch (Throwable) {
                    // Suppress compensation error to preserve original failure
                }
            }

            throw new RuntimeException("Workflow [{$workflowName}] failed at step: " . $e->getMessage(), 0, $e);
        }

        return $executionId;
    }

    public function getExecution(string $executionId): ?array
    {
        return $this->executions[$executionId] ?? null;
    }

    public function get(string $name): ?WorkflowInterface
    {
        return $this->workflows[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->workflows[$name]);
    }

    public function all(): array
    {
        return $this->workflows;
    }

    public function run(string $workflowName, array $input = []): array
    {
        $executionId = $this->start($workflowName, $input);
        return $this->getExecution($executionId) ?? [];
    }

    public function listWorkflows(): array
    {
        return $this->workflows;
    }

    public function listExecutions(): array
    {
        return $this->executions;
    }
}
