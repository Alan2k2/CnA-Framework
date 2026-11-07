<?php

declare(strict_types=1);

namespace CnA\Ai\Tools;

use CnA\Contracts\Exceptions\AuthorizationException;

class ToolDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly array $parametersSchema,
        public readonly mixed $handler,
        public readonly ?string $permission = null,
        public readonly bool $requiresHumanConfirmation = false,
        public readonly int $timeoutSeconds = 30
    ) {
    }
}

class ToolRegistry
{
    /** @var array<string, ToolDefinition> */
    protected array $tools = [];

    /** @var array<array{tool: string, parameters: array, result: mixed, duration: float, user_id: ?string, timestamp: string}> */
    protected array $auditLog = [];

    public function register(ToolDefinition $tool): void
    {
        $this->tools[$tool->name] = $tool;
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function get(string $name): ?ToolDefinition
    {
        return $this->tools[$name] ?? null;
    }

    public function execute(string $name, array $parameters, ?object $user = null): mixed
    {
        $tool = $this->get($name);
        if ($tool === null) {
            throw new \InvalidArgumentException("Tool [{$name}] is not registered.");
        }

        // Permission check
        if ($tool->permission !== null) {
            $userHasPermission = false;
            if ($user !== null && method_exists($user, 'can')) {
                $userHasPermission = $user->can($tool->permission);
            }
            if (!$userHasPermission) {
                throw new AuthorizationException("User not authorized to execute tool [{$name}]. Requires permission: [{$tool->permission}].");
            }
        }

        if ($tool->requiresHumanConfirmation) {
            throw new \RuntimeException("Tool [{$name}] requires explicit human confirmation before execution.");
        }

        $start = microtime(true);
        $handler = $tool->handler;
        $result = is_callable($handler) ? $handler($parameters) : null;
        $duration = microtime(true) - $start;

        // Audit Trail
        $this->auditLog[] = [
            'tool' => $name,
            'parameters' => $parameters,
            'result' => $result,
            'duration' => $duration,
            'user_id' => $user?->id ?? null,
            'timestamp' => date('c'),
        ];

        return $result;
    }

    public function getAuditLog(): array
    {
        return $this->auditLog;
    }

    public function getSchemas(): array
    {
        $schemas = [];
        foreach ($this->tools as $name => $tool) {
            $schemas[] = [
                'name' => $name,
                'description' => $tool->description,
                'parameters' => $tool->parametersSchema,
            ];
        }
        return $schemas;
    }
}

