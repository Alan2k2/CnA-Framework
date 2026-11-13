<?php

declare(strict_types=1);

namespace CnA\Contracts\Workflow;

interface WorkflowEngineInterface
{
    /**
     * Register a workflow definition.
     */
    public function register(WorkflowInterface $workflow): void;

    /**
     * Start a new workflow instance with initial input payload.
     *
     * @param string $workflowName
     * @param array<string, mixed> $input
     * @return string Execution ID
     */
    public function start(string $workflowName, array $input = []): string;

    /**
     * Get the state and execution history of a workflow run.
     *
     * @param string $executionId
     * @return array<string, mixed>|null
     */
    public function getExecution(string $executionId): ?array;

    /**
     * List all registered workflows.
     *
     * @return array<string, WorkflowInterface>
     */
    public function listWorkflows(): array;
}
