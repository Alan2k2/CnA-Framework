<?php

declare(strict_types=1);

namespace CnA\Contracts\Http;

interface RequestInterface
{
    public function method(): string;
    public function uri(): string;
    public function path(): string;
    public function query(?string $key = null, mixed $default = null): mixed;
    public function post(?string $key = null, mixed $default = null): mixed;
    public function input(?string $key = null, mixed $default = null): mixed;
    public function all(): array;
    public function header(string $key, ?string $default = null): ?string;
    public function headers(): array;
    public function cookie(string $key, ?string $default = null): ?string;
    public function cookies(): array;
    public function files(): array;
    public function file(string $key): mixed;
    public function bearerToken(): ?string;
    public function ip(): ?string;
    public function isSecure(): bool;
    public function isJson(): bool;
    public function wantsJson(): bool;
    public function correlationId(): string;
    public function setCorrelationId(string $id): void;
    public function withAttribute(string $name, mixed $value): static;
    public function getAttribute(string $name, mixed $default = null): mixed;
}

