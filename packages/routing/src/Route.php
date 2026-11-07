<?php

declare(strict_types=1);

namespace CnA\Routing;

use CnA\Contracts\Routing\RouteDefinitionInterface;

class Route implements RouteDefinitionInterface
{
    /** @var array<string> */
    protected array $methods;
    protected string $uri;
    /** @var callable|string|array */
    protected mixed $action;
    protected ?string $name = null;
    /** @var array<string> */
    protected array $middleware = [];
    /** @var array<string, string> */
    protected array $wheres = [];
    /** @var array<string, string> */
    protected array $parameters = [];

    public function __construct(array|string $methods, string $uri, callable|string|array $action)
    {
        $this->methods = array_map('strtoupper', (array) $methods);
        $this->uri = '/' . trim($uri, '/');
        $this->action = $action;
    }

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function middleware(array|string $middleware): static
    {
        $this->middleware = array_merge($this->middleware, (array) $middleware);
        return $this;
    }

    public function where(string|array $name, ?string $expression = null): static
    {
        if (is_array($name)) {
            $this->wheres = array_merge($this->wheres, $name);
        } elseif ($expression !== null) {
            $this->wheres[$name] = $expression;
        }
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getAction(): callable|string|array
    {
        return $this->action;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function matches(string $method, string $path): bool
    {
        if (!in_array(strtoupper($method), $this->methods, true) && !in_array('ANY', $this->methods, true)) {
            return false;
        }

        $path = '/' . trim($path, '/');
        $pattern = $this->compilePattern();

        if (preg_match($pattern, $path, $matches)) {
            $this->parameters = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $this->parameters[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }

    protected function compilePattern(): string
    {
        // Replace {param} or {param?} with regex
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)(\??)\}/', function ($matches) {
            $param = $matches[1];
            $optional = $matches[2] === '?';
            $regex = $this->wheres[$param] ?? '[^/]+';

            if ($optional) {
                return "(?:/(?P<{$param}>{$regex}))?";
            }
            return "(?P<{$param}>{$regex})";
        }, $this->uri);

        return '#^' . $pattern . '$#u';
    }
}

