<?php

declare(strict_types=1);

namespace CnA\Workflow;

use CnA\Contracts\Workflow\WorkflowInterface;
use CnA\Contracts\Workflow\WorkflowStepInterface;
use Closure;

class Workflow implements WorkflowInterface
{
    /** @var array<int, WorkflowStepInterface> */
    protected array $steps = [];

    public function __construct(protected string $name)
    {
    }

    public static function define(string $name): static
    {
        return new static($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addStep(WorkflowStepInterface|string $step, ?Closure $handler = null, ?Closure $compensate = null): static
    {
        if (is_string($step) && $handler !== null) {
            $this->steps[] = new Step($step, $handler, $compensate);
        } elseif ($step instanceof WorkflowStepInterface) {
            $this->steps[] = $step;
        }
        return $this;
    }

    public function getSteps(): array
    {
        return $this->steps;
    }
}

