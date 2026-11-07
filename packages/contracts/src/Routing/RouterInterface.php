<?php

declare(strict_types=1);

namespace CnA\Contracts\Routing;

use CnA\Contracts\Http\RequestInterface;
use CnA\Contracts\Http\ResponseInterface;

interface RouterInterface
{
    public function get(string $uri, callable|string|array $action): RouteDefinitionInterface;
    public function post(string $uri, callable|string|array $action): RouteDefinitionInterface;
    public function put(string $uri, callable|string|array $action): RouteDefinitionInterface;
    public function patch(string $uri, callable|string|array $action): RouteDefinitionInterface;
    public function delete(string $uri, callable|string|array $action): RouteDefinitionInterface;
    public function options(string $uri, callable|string|array $action): RouteDefinitionInterface;
    public function match(array $methods, string $uri, callable|string|array $action): RouteDefinitionInterface;
    public function group(array $attributes, callable $callback): void;
    public function dispatch(RequestInterface $request): ResponseInterface;
    public function route(string $name, array $parameters = []): string;
}

