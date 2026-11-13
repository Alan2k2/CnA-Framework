<?php

declare(strict_types=1);

namespace CnA\Contracts\Tenancy;

interface TenantManagerInterface
{
    /**
     * Set the active tenant context for the current request/job scope.
     */
    public function setTenant(?TenantInterface $tenant): void;

    /**
     * Get the active tenant in scope, if any.
     */
    public function getTenant(): ?TenantInterface;

    /**
     * Check if a tenant is currently bound.
     */
    public function hasTenant(): bool;

    /**
     * Resolve a tenant from domain, token claim, or ID.
     */
    public function resolve(string $identifier): ?TenantInterface;

    /**
     * Clear the tenant context after request or background job completes.
     */
    public function clear(): void;
}

