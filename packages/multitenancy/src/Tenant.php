<?php

declare(strict_types=1);

namespace CnA\Tenancy;

use CnA\Contracts\Tenancy\TenantInterface;

class Tenant implements TenantInterface
{
    /**
     * @param string $id Unique tenant identifier
     * @param string $name Organization/Tenant display name
     * @param string|null $domain Dedicated domain (e.g. acme.example.com)
     * @param string|null $databaseConnection Isolated DB connection name
     * @param array<string, mixed> $attributes Custom tenant metadata
     */
    public function __construct(
        protected string $id,
        protected string $name,
        string|array|null $domain = null,
        protected ?string $databaseConnection = null,
        protected array $attributes = []
    ) {
        if (is_array($domain)) {
            $this->attributes = $domain;
            $this->domain = null;
        } else {
            $this->domain = $domain;
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function getDatabaseConnection(): ?string
    {
        return $this->databaseConnection;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
