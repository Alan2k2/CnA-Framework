<?php

declare(strict_types=1);

namespace CnA\Ai\Cost;

use CnA\Contracts\Exceptions\SecurityException;

class CostTracker
{
    /** @var array<string, int> */
    protected array $tenantTokenUsage = [];

    /** @var array<string, int> */
    protected array $tenantQuotas = [];

    /** @var array<string> */
    protected array $modelAllowlist = [];

    public function __construct(array $modelAllowlist = [])
    {
        $this->modelAllowlist = $modelAllowlist;
    }

    public function setTenantQuota(string $tenantId, int $maxTokens): void
    {
        $this->tenantQuotas[$tenantId] = $maxTokens;
    }

    public function recordUsage(string $tenantId, string $model, int $tokens): void
    {
        if (!empty($this->modelAllowlist) && !in_array($model, $this->modelAllowlist, true)) {
            throw new SecurityException("Model [{$model}] is not in the approved AI model allowlist.");
        }

        $current = $this->tenantTokenUsage[$tenantId] ?? 0;
        $newTotal = $current + $tokens;

        if (isset($this->tenantQuotas[$tenantId]) && $newTotal > $this->tenantQuotas[$tenantId]) {
            throw new \RuntimeException("Tenant [{$tenantId}] has exceeded token quota ({$this->tenantQuotas[$tenantId]}). Current: {$newTotal}");
        }

        $this->tenantTokenUsage[$tenantId] = $newTotal;
    }

    public function getUsage(string $tenantId): int
    {
        return $this->tenantTokenUsage[$tenantId] ?? 0;
    }

    public function getAllUsage(): array
    {
        return $this->tenantTokenUsage;
    }
}

