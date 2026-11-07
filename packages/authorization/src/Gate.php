<?php

declare(strict_types=1);

namespace CnA\Authorization;

use CnA\Contracts\Container\ContainerInterface;
use CnA\Contracts\Exceptions\AuthorizationException;

class Gate
{
    /** @var array<string, callable> */
    protected array $abilities = [];

    /** @var array<string, string> */
    protected array $policies = [];

    public function __construct(protected ?ContainerInterface $container = null)
    {
    }

    public function define(string $ability, callable $callback): static
    {
        $this->abilities[$ability] = $callback;
        return $this;
    }

    public function policy(string $class, string $policy): static
    {
        $this->policies[$class] = $policy;
        return $this;
    }

    public function allows(string $ability, mixed ...$arguments): bool
    {
        // 1. Check direct ability closure
        if (isset($this->abilities[$ability])) {
            return (bool) ($this->abilities[$ability])(...$arguments);
        }

        // 2. Check class-based policy if argument is an object
        $target = $arguments[0] ?? null;
        if (is_object($target)) {
            $class = get_class($target);
            if (isset($this->policies[$class])) {
                $policyClass = $this->policies[$class];
                $policyInstance = $this->container !== null
                    ? $this->container->get($policyClass)
                    : new $policyClass();

                if (method_exists($policyInstance, $ability)) {
                    return (bool) $policyInstance->{$ability}(...$arguments);
                }
            }
        }

        // Deny by default
        return false;
    }

    public function denies(string $ability, mixed ...$arguments): bool
    {
        return !$this->allows($ability, ...$arguments);
    }

    public function authorize(string $ability, mixed ...$arguments): bool
    {
        if ($this->denies($ability, ...$arguments)) {
            throw new AuthorizationException("This action is unauthorized: [{$ability}]");
        }
        return true;
    }
}

