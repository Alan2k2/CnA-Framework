<?php

declare(strict_types=1);

namespace CnA\Tenancy;

use CnA\Contracts\Tenancy\TenantInterface;
use CnA\Contracts\Tenancy\TenantManagerInterface;

class TenantManager implements TenantManagerInterface
{
    protected ?TenantInterface $activeTenant = null;

    /** @var array<string, TenantInterface> */
    protected array $tenantsById = [];

    /** @var array<string, TenantInterface> */
    protected array $tenantsByDomain = [];

    public function register(TenantInterface $tenant): void
    {
        $this->tenantsById[$tenant->getId()] = $tenant;
        if ($tenant->getDomain() !== null) {
            $this->tenantsByDomain[strtolower($tenant->getDomain())] = $tenant;
        }
    }

    public function setTenant(?TenantInterface $tenant): void
    {
        $this->activeTenant = $tenant;
    }

    public function getTenant(): ?TenantInterface
    {
        return $this->activeTenant;
    }

    public function hasTenant(): bool
    {
        return $this->activeTenant !== null;
    }

    public function resolve(string $identifier): ?TenantInterface
    {
        // 1. Direct ID match
        if (isset($this->tenantsById[$identifier])) {
            return $this->tenantsById[$identifier];
        }

        // 2. Domain / Host match
        $normalized = strtolower(trim($identifier));
        if (isset($this->tenantsByDomain[$normalized])) {
            return $this->tenantsByDomain[$normalized];
        }

        return null;
    }

    public function clear(): void
    {
        $this->activeTenant = null;
    }

    public function current(): ?TenantInterface
    {
        return $this->getTenant();
    }

    public function setCurrent(?TenantInterface $tenant): void
    {
        $this->setTenant($tenant);
    }

    public function get(string $id): ?TenantInterface
    {
        return $this->resolve($id);
    }

    public function has(string $id): bool
    {
        return isset($this->tenantsById[$id]);
    }

    public function all(): array
    {
        return $this->tenantsById;
    }
}
