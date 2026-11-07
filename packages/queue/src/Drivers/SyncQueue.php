<?php

declare(strict_types=1);

namespace CnA\Queue\Drivers;

use CnA\Contracts\Container\ContainerInterface;
use CnA\Contracts\Queue\JobInterface;
use CnA\Contracts\Queue\QueueInterface;

class SyncQueue implements QueueInterface
{
    public function __construct(protected ?ContainerInterface $container = null)
    {
    }

    public function push(object|string $job, mixed $data = '', ?string $queue = null): string|int
    {
        $this->resolveAndFire($job, $data);
        return 'sync-' . uniqid();
    }

    public function later(int $delay, object|string $job, mixed $data = '', ?string $queue = null): string|int
    {
        return $this->push($job, $data, $queue);
    }

    public function pop(?string $queue = null): ?JobInterface
    {
        return null;
    }

    public function size(?string $queue = null): int
    {
        return 0;
    }

    protected function resolveAndFire(object|string $job, mixed $data): void
    {
        if (is_object($job)) {
            if (method_exists($job, 'handle')) {
                if ($this->container !== null && method_exists($this->container, 'call')) {
                    $this->container->call([$job, 'handle']);
                } else {
                    $job->handle();
                }
            }
        } elseif (is_string($job) && class_exists($job)) {
            $instance = $this->container !== null ? $this->container->get($job) : new $job();
            if (method_exists($instance, 'handle')) {
                if ($this->container !== null && method_exists($this->container, 'call')) {
                    $this->container->call([$instance, 'handle'], ['data' => $data]);
                } else {
                    $instance->handle($data);
                }
            }
        }
    }
}

