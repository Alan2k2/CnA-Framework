<?php

declare(strict_types=1);

namespace CnA\Contracts\Routing;

interface RouteDefinitionInterface
{
    public function name(string $name): static;
    public function middleware(array|string $middleware): static;
    public function where(string|array $name, ?string $expression = null): static;
    public function getName(): ?string;
    public function getMiddleware(): array;
    public function getUri(): string;
    public function getMethods(): array;
    public function getAction(): callable|string|array;
}

