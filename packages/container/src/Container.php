<?php

declare(strict_types=1);

namespace CnA\Container;

use CnA\Contracts\Container\ContainerInterface;
use CnA\Contracts\Exceptions\ContainerException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

class Container implements ContainerInterface
{
    protected static ?ContainerInterface $instance = null;

    /** @var array<string, array{concrete: mixed, shared: bool, scoped: bool}> */
    protected array $bindings = [];

    /** @var array<string, object> */
    protected array $instances = [];

    /** @var array<string, object> */
    protected array $scopedInstances = [];

    /** @var array<string, array<string>> */
    protected array $tags = [];

    /** @var array<string, string> */
    protected array $aliases = [];

    /** @var array<string, bool> */
    protected array $buildStack = [];

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public static function setInstance(?ContainerInterface $container): ?ContainerInterface
    {
        return static::$instance = $container;
    }

    public function bind(string $abstract, callable|string|null $concrete = null): void
    {
        $this->dropInstances($abstract);
        $concrete = $concrete ?? $abstract;
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => false,
            'scoped' => false,
        ];
    }

    public function singleton(string $abstract, callable|string|null $concrete = null): void
    {
        $this->dropInstances($abstract);
        $concrete = $concrete ?? $abstract;
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => true,
            'scoped' => false,
        ];
    }

    public function scoped(string $abstract, callable|string|null $concrete = null): void
    {
        $this->dropInstances($abstract);
        $concrete = $concrete ?? $abstract;
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => false,
            'scoped' => true,
        ];
    }

    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function has(string $abstract): bool
    {
        $abstract = $this->getAlias($abstract);
        return isset($this->bindings[$abstract])
            || isset($this->instances[$abstract])
            || isset($this->scopedInstances[$abstract])
            || class_exists($abstract);
    }

    public function get(string $abstract): object
    {
        return $this->make($abstract);
    }

    public function make(string $abstract, array $parameters = []): object
    {
        $abstract = $this->getAlias($abstract);

        // Return singleton instance if already resolved
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Return scoped instance if already resolved in this scope
        if (isset($this->scopedInstances[$abstract])) {
            return $this->scopedInstances[$abstract];
        }

        // Circular dependency check
        if (isset($this->buildStack[$abstract])) {
            $cycle = implode(' -> ', array_keys($this->buildStack)) . ' -> ' . $abstract;
            throw new ContainerException("Circular dependency detected while resolving: {$cycle}");
        }

        $this->buildStack[$abstract] = true;

        try {
            $concrete = $this->getConcrete($abstract);

            $object = $this->build($concrete, $parameters);

            if ($this->isShared($abstract)) {
                $this->instances[$abstract] = $object;
            } elseif ($this->isScoped($abstract)) {
                $this->scopedInstances[$abstract] = $object;
            }

            return $object;
        } finally {
            unset($this->buildStack[$abstract]);
        }
    }

    public function build(mixed $concrete, array $parameters = []): object
    {
        if ($concrete instanceof \Closure || is_callable($concrete)) {
            return $concrete($this, $parameters);
        }

        if (!is_string($concrete)) {
            throw new ContainerException("Target is not instantiable.");
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new ContainerException("Target class [{$concrete}] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new ContainerException("Target [{$concrete}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();
        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters(), $parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    public function call(callable|array|string $callback, array $parameters = []): mixed
    {
        if (is_string($callback) && str_contains($callback, '@')) {
            [$class, $method] = explode('@', $callback, 2);
            $callback = [$this->make($class), $method];
        } elseif (is_string($callback) && class_exists($callback)) {
            $callback = [$this->make($callback), '__invoke'];
        } elseif (is_array($callback) && is_string($callback[0])) {
            $callback[0] = $this->make($callback[0]);
        }

        if (is_array($callback)) {
            $reflector = new ReflectionMethod($callback[0], $callback[1]);
            $dependencies = $this->resolveDependencies($reflector->getParameters(), $parameters);
            return $reflector->invokeArgs($callback[0], $dependencies);
        }

        $reflector = new ReflectionFunction($callback(...));
        $dependencies = $this->resolveDependencies($reflector->getParameters(), $parameters);
        return $reflector->invokeArgs($dependencies);
    }

    public function tag(array|string $abstracts, array|string $tags): void
    {
        $tags = (array) $tags;
        $abstracts = (array) $abstracts;

        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }
            foreach ($abstracts as $abstract) {
                $this->tags[$tag][] = $abstract;
            }
        }
    }

    public function tagged(string $tag): iterable
    {
        if (!isset($this->tags[$tag])) {
            return [];
        }

        $results = [];
        foreach ($this->tags[$tag] as $abstract) {
            $results[] = $this->make($abstract);
        }
        return $results;
    }

    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    public function forgetScopedInstances(): void
    {
        $this->scopedInstances = [];
    }

    protected function getAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    protected function getConcrete(string $abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }
        return $abstract;
    }

    protected function isShared(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) && $this->bindings[$abstract]['shared'];
    }

    protected function isScoped(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) && $this->bindings[$abstract]['scoped'];
    }

    protected function dropInstances(string $abstract): void
    {
        unset($this->instances[$abstract], $this->scopedInstances[$abstract]);
    }

    /**
     * @param array<ReflectionParameter> $dependencies
     * @param array<string, mixed> $parameters
     * @return array<mixed>
     */
    protected function resolveDependencies(array $dependencies, array $parameters = []): array
    {
        $results = [];

        foreach ($dependencies as $parameter) {
            $name = $parameter->getName();

            // Override provided explicitly
            if (array_key_exists($name, $parameters)) {
                $results[] = $parameters[$name];
                continue;
            }

            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();
                try {
                    $results[] = $this->make($typeName);
                    continue;
                } catch (\Throwable $e) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $results[] = $parameter->getDefaultValue();
                        continue;
                    }
                    if ($parameter->allowsNull()) {
                        $results[] = null;
                        continue;
                    }
                    throw $e;
                }
            }

            if ($parameter->isDefaultValueAvailable()) {
                $results[] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->allowsNull()) {
                $results[] = null;
                continue;
            }

            throw new ContainerException("Unresolvable dependency [{$parameter}] in class [{$parameter->getDeclaringClass()?->getName()}]");
        }

        return $results;
    }
}

