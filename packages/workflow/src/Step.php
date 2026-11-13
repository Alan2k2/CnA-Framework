<?php

declare(strict_types=1);

namespace CnA\Workflow;

use CnA\Contracts\Workflow\WorkflowStepInterface;
use Closure;

class Step implements WorkflowStepInterface
{
    /**
     * @param string $name
     * @param Closure $handler fn(array $context): array
     * @param Closure|null $compensator fn(array $context): void
     */
    public function __construct(
        protected string $name,
        protected Closure $handler,
        protected ?Closure $compensator = null
    ) {
    }

    public static function make(string $name, Closure $handler, ?Closure $compensator = null): static
    {
        return new static($name, $handler, $compensator);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function execute(array $context): array
    {
        $result = ($this->handler)($context);
        return is_array($result) ? $result : $context;
    }

    public function setCompensate(?Closure $compensator): static
    {
        $this->compensator = $compensator;
        return $this;
    }

    public function getCompensate(): ?Closure
    {
        return $this->compensator;
    }

    public function compensate(array $context): void
    {
        if ($this->compensator !== null) {
            ($this->compensator)($context);
        }
    }
}
