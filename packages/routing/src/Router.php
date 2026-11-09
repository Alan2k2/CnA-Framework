<?php

declare(strict_types=1);

namespace CnA\Routing;

use CnA\Contracts\Container\ContainerInterface;
use CnA\Contracts\Exceptions\NotFoundException;
use CnA\Contracts\Http\RequestInterface;
use CnA\Contracts\Http\ResponseInterface;
use CnA\Contracts\Routing\RouteDefinitionInterface;
use CnA\Contracts\Routing\RouterInterface;
use CnA\Core\Pipeline;
use CnA\Http\JsonResponse;
use CnA\Http\Response;

class Router implements RouterInterface
{
    /** @var array<Route> */
    protected array $routes = [];

    /** @var array<string, Route> */
    protected array $namedRoutes = [];

    /** @var array<array{prefix: string, middleware: array}> */
    protected array $groupStack = [];

    /** @var array<string, string> */
    protected array $middlewareAliases = [];

    public function __construct(protected ?ContainerInterface $container = null)
    {
    }

    public function aliasMiddleware(string $alias, string $class): void
    {
        $this->middlewareAliases[$alias] = $class;
    }

    public function get(string $uri, callable|string|array $action): RouteDefinitionInterface
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    public function post(string $uri, callable|string|array $action): RouteDefinitionInterface
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    public function put(string $uri, callable|string|array $action): RouteDefinitionInterface
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }

    public function patch(string $uri, callable|string|array $action): RouteDefinitionInterface
    {
        return $this->addRoute(['PATCH'], $uri, $action);
    }

    public function delete(string $uri, callable|string|array $action): RouteDefinitionInterface
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }

    public function options(string $uri, callable|string|array $action): RouteDefinitionInterface
    {
        return $this->addRoute(['OPTIONS'], $uri, $action);
    }

    public function any(string $uri, callable|string|array $action): RouteDefinitionInterface
    {
        return $this->addRoute(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $action);
    }

    public function match(array $methods, string $uri, callable|string|array $action): RouteDefinitionInterface
    {
        return $this->addRoute($methods, $uri, $action);
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    public function dispatch(RequestInterface $request): ResponseInterface
    {
        $method = $request->method();
        $path = $request->path();

        $matchedRoute = null;
        foreach ($this->routes as $route) {
            if ($route->matches($method, $path)) {
                $matchedRoute = $route;
                break;
            }
        }

        if ($matchedRoute === null) {
            throw new NotFoundException("Route not found: {$method} {$path}", 404);
        }

        // Add parameters as request attributes
        foreach ($matchedRoute->getParameters() as $key => $val) {
            $request = $request->withAttribute($key, $val);
        }

        $middlewareClasses = array_map(
            fn($m) => $this->middlewareAliases[$m] ?? $m,
            $matchedRoute->getMiddleware()
        );

        $response = (new Pipeline($this->container))
            ->send($request)
            ->through($middlewareClasses)
            ->then(fn($req) => $this->runRoute($matchedRoute, $req));

        // RFC 9110 §9.3.2: HEAD responses must be identical to GET headers but contain an empty body
        if (strtoupper($method) === 'HEAD') {
            $response = $response->withBody('');
        }

        return $response;
    }

    public function route(string $name, array $parameters = []): string
    {
        $route = $this->namedRoutes[$name] ?? null;
        if ($route === null) {
            throw new \InvalidArgumentException("Route [{$name}] not found.");
        }

        $uri = $route->getUri();
        foreach ($parameters as $key => $value) {
            $uri = str_replace(["{{$key}}", "{{$key}?}"], (string) $value, $uri);
        }

        // Strip remaining optional parameters
        $uri = preg_replace('/\/\{[a-zA-Z_][a-zA-Z0-9_]*\?\}/', '', $uri);

        return $uri;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    protected function addRoute(array $methods, string $uri, callable|string|array $action): RouteDefinitionInterface
    {
        $prefix = $this->getCurrentPrefix();
        $fullUri = $prefix !== '' ? '/' . trim($prefix, '/') . '/' . ltrim($uri, '/') : $uri;

        $route = new Route($methods, $fullUri, $action);

        $groupMiddleware = $this->getCurrentMiddleware();
        if (!empty($groupMiddleware)) {
            $route->middleware($groupMiddleware);
        }

        $this->routes[] = $route;

        // Wrap to register route name if changed
        return new class($route, $this) implements RouteDefinitionInterface {
            public function __construct(private Route $route, private Router $router) {}

            public function name(string $name): static {
                $this->route->name($name);
                $this->router->registerNamedRoute($name, $this->route);
                return $this;
            }

            public function middleware(array|string $middleware): static {
                $this->route->middleware($middleware);
                return $this;
            }

            public function where(string|array $name, ?string $expression = null): static {
                $this->route->where($name, $expression);
                return $this;
            }

            public function getName(): ?string { return $this->route->getName(); }
            public function getMiddleware(): array { return $this->route->getMiddleware(); }
            public function getUri(): string { return $this->route->getUri(); }
            public function getMethods(): array { return $this->route->getMethods(); }
            public function getAction(): callable|string|array { return $this->route->getAction(); }
        };
    }

    public function registerNamedRoute(string $name, Route $route): void
    {
        $this->namedRoutes[$name] = $route;
    }

    protected function runRoute(Route $route, RequestInterface $request): ResponseInterface
    {
        $action = $route->getAction();
        $params = array_merge(['request' => $request], $route->getParameters());

        $result = null;
        if ($this->container !== null && method_exists($this->container, 'call')) {
            $result = $this->container->call($action, $params);
        } elseif (is_callable($action)) {
            $result = $action($request, ...array_values($route->getParameters()));
        } elseif (is_array($action) && count($action) === 2) {
            [$class, $method] = $action;
            $instance = is_string($class) ? new $class() : $class;
            $result = $instance->{$method}($request, ...array_values($route->getParameters()));
        }

        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if (is_array($result) || $result instanceof \JsonSerializable) {
            return new JsonResponse($result);
        }

        return new Response((string) $result);
    }

    protected function getCurrentPrefix(): string
    {
        $prefixes = array_column($this->groupStack, 'prefix');
        return implode('', array_map(fn($p) => '/' . trim($p, '/'), array_filter($prefixes)));
    }

    protected function getCurrentMiddleware(): array
    {
        $middlewares = [];
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $middlewares = array_merge($middlewares, (array) $group['middleware']);
            }
        }
        return $middlewares;
    }
}

