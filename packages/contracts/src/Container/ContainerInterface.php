<?php

declare(strict_types=1);

namespace CnA\Contracts\Container;

interface ContainerInterface
{
    public function bind(string $abstract, callable|string|null $concrete = null): void;

    public function singleton(string $abstract, callable|string|null $concrete = null): void;

    public function scoped(string $abstract, callable|string|null $concrete = null): void;

    public function instance(string $abstract, object $instance): void;

    public function get(string $abstract): object;

    public function make(string $abstract, array $parameters = []): object;

    public function has(string $abstract): bool;

    public function tag(array|string $abstracts, array|string $tags): void;

    public function tagged(string $tag): iterable;
}

