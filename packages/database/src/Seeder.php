<?php

declare(strict_types=1);

namespace CnA\Database;

use CnA\Contracts\Container\ContainerInterface;

abstract class Seeder
{
    public function __construct(protected ?ContainerInterface $container = null)
    {
    }

    abstract public function run(): void;

    public function call(string|array $seeders): void
    {
        foreach ((array) $seeders as $seeder) {
            $instance = $this->container !== null && $this->container->has($seeder)
                ? $this->container->get($seeder)
                : new $seeder($this->container);

            if (method_exists($instance, 'run')) {
                $instance->run();
            }
        }
    }
}

