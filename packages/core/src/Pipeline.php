<?php

declare(strict_types=1);

namespace CnA\Core;

use CnA\Contracts\Container\ContainerInterface;

class Pipeline
{
    /** @var array<mixed> */
    protected array $pipes = [];
    protected mixed $passable;
    protected string $method = 'process';

    public function __construct(protected ?ContainerInterface $container = null)
    {
    }

    public function send(mixed $passable): static
    {
        $this->passable = $passable;
        return $this;
    }

    public function through(array $pipes): static
    {
        $this->pipes = $pipes;
        return $this;
    }

    public function via(string $method): static
    {
        $this->method = $method;
        return $this;
    }

    public function then(callable $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            fn($passable) => $destination($passable)
        );

        return $pipeline($this->passable);
    }

    protected function carry(): \Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_callable($pipe)) {
                    return $pipe($passable, $stack);
                }

                if (is_string($pipe)) {
                    $pipe = $this->container !== null ? $this->container->get($pipe) : new $pipe();
                }

                if (method_exists($pipe, $this->method)) {
                    return $pipe->{$this->method}($passable, $stack);
                }

                if (is_callable([$pipe, '__invoke'])) {
                    return $pipe($passable, $stack);
                }

                throw new \InvalidArgumentException("Invalid pipe handler.");
            };
        };
    }
}

