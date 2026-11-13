<?php

declare(strict_types=1);

namespace CnA\Contracts\Workflow;

interface WorkflowStepInterface
{
    /**
     * Get the step identifier.
     */
    public function getName(): string;

    /**
     * Execute the step logic. Returns updated workflow state/payload.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function execute(array $context): array;

    /**
     * Optional compensation logic if downstream steps fail.
     *
     * @param array<string, mixed> $context
     */
    public function compensate(array $context): void;
}
