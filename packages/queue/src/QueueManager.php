<?php

declare(strict_types=1);

namespace CnA\Queue;

use CnA\Contracts\Container\ContainerInterface;
use CnA\Contracts\Database\ConnectionManagerInterface;
use CnA\Contracts\Queue\JobInterface;
use CnA\Contracts\Queue\QueueInterface;
use CnA\Queue\Drivers\DatabaseQueue;
use CnA\Queue\Drivers\SyncQueue;

class QueueManager implements QueueInterface
{
    /** @var array<string, QueueInterface> */
    protected array $queues = [];

    public function __construct(
        protected array $config = [],
        protected ?ContainerInterface $container = null,
        protected string $default = 'sync'
    ) {
    }

    public function connection(?string $name = null): QueueInterface
    {
        $name = $name ?? $this->default;
        if (!isset($this->queues[$name])) {
            $this->queues[$name] = $this->resolve($name);
        }
        return $this->queues[$name];
    }

    public function push(object|string $job, mixed $data = '', ?string $queue = null): string|int
    {
        return $this->connection()->push($job, $data, $queue);
    }

    public function later(int $delay, object|string $job, mixed $data = '', ?string $queue = null): string|int
    {
        return $this->connection()->later($delay, $job, $data, $queue);
    }

    public function pop(?string $queue = null): ?JobInterface
    {
        return $this->connection()->pop($queue);
    }

    public function size(?string $queue = null): int
    {
        return $this->connection()->size($queue);
    }

    protected function resolve(string $name): QueueInterface
    {
        $driver = $this->config['connections'][$name]['driver'] ?? $name;

        return match ($driver) {
            'sync' => new SyncQueue($this->container),
            'database' => $this->createDatabaseDriver($this->config['connections'][$name] ?? []),
            default => new SyncQueue($this->container),
        };
    }

    protected function createDatabaseDriver(array $config): QueueInterface
    {
        /** @var ConnectionManagerInterface $dbManager */
        $dbManager = $this->container->get(ConnectionManagerInterface::class);
        $conn = $dbManager->connection($config['connection'] ?? null);
        return new DatabaseQueue($conn, $config['table'] ?? 'jobs', $this->container);
    }
}

