<?php

declare(strict_types=1);

namespace CnA\Contracts\Tenancy;

interface TenantInterface
{
    /**
     * Unique tenant identifier (e.g. UUID or slug).
     */
    public function getId(): string;

    /**
     * Tenant display name.
     */
    public function getName(): string;

    /**
     * Primary domain or custom hostname bound to this tenant.
     */
    public function getDomain(): ?string;

    /**
     * Database connection or schema name for isolated tenancy.
     */
    public function getDatabaseConnection(): ?string;
}
