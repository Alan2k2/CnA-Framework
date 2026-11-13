<?php

declare(strict_types=1);

namespace CnA\Contracts\Workflow;

interface WorkflowInterface
{
    /**
     * Get the unique workflow definition name.
     */
    public function getName(): string;

    /**
     * Define the sequence of steps and state transitions for the workflow.
     *
     * @return array<int, WorkflowStepInterface>
     */
    public function getSteps(): array;
}

