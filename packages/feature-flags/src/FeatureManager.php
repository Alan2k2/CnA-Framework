<?php

declare(strict_types=1);

namespace CnA\Features;

use CnA\Contracts\Features\FeatureFlagManagerInterface;
use Closure;

class FeatureManager implements FeatureFlagManagerInterface
{
    /** @var array<string, mixed> */
    protected array $flags = [];

    public function __construct(array $initialFlags = [])
    {
        $this->flags = $initialFlags;
    }

    public function set(string $flag, mixed $state): void
    {
        $this->flags[$flag] = $state;
    }

    public function isEnabled(string $flag, array $context = []): bool
    {
        if (!isset($this->flags[$flag])) {
            return false;
        }

        $val = $this->flags[$flag];

        // 1. Simple boolean
        if (is_bool($val)) {
            return $val;
        }

        // 2. Closure evaluation
        if ($val instanceof Closure) {
            return (bool) $val($context);
        }

        // 3. Percentage rollout (integer 0-100)
        if (is_int($val)) {
            $id = (string) ($context['user_id'] ?? $context['tenant_id'] ?? $context['ip'] ?? uniqid());
            $hash = crc32($flag . '_' . $id) % 100;
            return abs($hash) < $val;
        }

        return (bool) $val;
    }

    public function active(string $flag, array $context = []): bool
    {
        return $this->isEnabled($flag, $context);
    }

    public function has(string $flag): bool
    {
        return isset($this->flags[$flag]);
    }

    public function get(string $flag): mixed
    {
        return $this->flags[$flag] ?? null;
    }

    public function all(): array
    {
        return $this->flags;
    }
}
