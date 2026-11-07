<?php

declare(strict_types=1);

namespace CnA\Queue\Drivers;

use CnA\Contracts\Container\ContainerInterface;
use CnA\Contracts\Database\ConnectionInterface;
use CnA\Contracts\Queue\JobInterface;
use CnA\Contracts\Queue\QueueInterface;
use CnA\Queue\Jobs\DatabaseJob;

class DatabaseQueue implements QueueInterface
{
    public function __construct(
        protected ConnectionInterface $connection,
        protected string $table = 'jobs',
        protected ?ContainerInterface $container = null
    ) {
    }

    public function push(object|string $job, mixed $data = '', ?string $queue = null): string|int
    {
        return $this->pushToDatabase($job, $data, $queue ?? 'default', 0);
    }

    public function later(int $delay, object|string $job, mixed $data = '', ?string $queue = null): string|int
    {
        return $this->pushToDatabase($job, $data, $queue ?? 'default', $delay);
    }

    public function pop(?string $queue = null): ?JobInterface
    {
        $queue = $queue ?? 'default';
        $now = time();

        $jobRecord = $this->connection->table($this->table)
            ->where('queue', $queue)
            ->whereNull('reserved_at')
            ->where('available_at', '<=', $now)
            ->orderBy('id', 'asc')
            ->first();

        if ($jobRecord === null) {
            return null;
        }

        // Reserve job
        $this->connection->table($this->table)->where('id', $jobRecord['id'])->update([
            'reserved_at' => $now,
            'attempts' => $jobRecord['attempts'] + 1,
        ]);

        $jobRecord['attempts'] = $jobRecord['attempts'] + 1;

        return new DatabaseJob($this->connection, $jobRecord, $this->table, $this->container);
    }

    public function size(?string $queue = null): int
    {
        return $this->connection->table($this->table)
            ->where('queue', $queue ?? 'default')
            ->count();
    }

    protected function pushToDatabase(object|string $job, mixed $data, string $queue, int $delay): string|int
    {
        $payload = json_encode([
            'job' => is_object($job) ? get_class($job) : $job,
            'data' => is_object($job) ? serialize($job) : serialize($data),
            'max_attempts' => 3,
        ], JSON_THROW_ON_ERROR);

        $now = time();

        return $this->connection->table($this->table)->insert([
            'queue' => $queue,
            'payload' => $payload,
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => $now + $delay,
            'created_at' => $now,
        ]);
    }
}

