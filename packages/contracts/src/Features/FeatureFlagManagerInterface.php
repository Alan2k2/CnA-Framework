<?php

declare(strict_types=1);

namespace CnA\Contracts\Features;

interface FeatureFlagManagerInterface
{
    /**
     * Check if a feature flag is enabled for the current context/user/tenant.
     *
     * @param string $flag
     * @param array<string, mixed> $context
     * @return bool
     */
    public function isEnabled(string $flag, array $context = []): bool;

    /**
     * Set a feature flag state.
     *
     * @param string $flag
     * @param bool|int|callable $state Boolean value, rollout percentage (0-100), or callback
     */
    public function set(string $flag, mixed $state): void;

    /**
     * Get all active feature flags and their states.
     *
     * @return array<string, mixed>
     */
    public function all(): array;
}
