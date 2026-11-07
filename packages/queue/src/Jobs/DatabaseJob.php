<?php

declare(strict_types=1);

namespace CnA\Queue\Jobs;

use CnA\Contracts\Container\ContainerInterface;
use CnA\Contracts\Database\ConnectionInterface;
use CnA\Contracts\Queue\JobInterface;

class DatabaseJob implements JobInterface
{
    public function __construct(
        protected ConnectionInterface $connection,
        protected array $record,
        protected string $table = 'jobs',
        protected ?ContainerInterface $container = null
    ) {
    }

    public function getId(): string|int
    {
        return $this->record['id'];
    }

    public function getName(): string
    {
        $payload = $this->getPayload();
        return $payload['job'] ?? 'UnknownJob';
    }

    public function getPayload(): array
    {
        return json_decode($this->record['payload'], true) ?? [];
    }

    public function fire(): void
    {
        $payload = $this->getPayload();
        $command = unserialize($payload['data'] ?? '');

        if (is_object($command) && method_exists($command, 'handle')) {
            if ($this->container !== null && method_exists($this->container, 'call')) {
                $this->container->call([$command, 'handle']);
            } else {
                $command->handle();
            }
        }
    }

    public function delete(): void
    {
        $this->connection->table($this->table)->where('id', $this->getId())->delete();
    }

    public function release(int $delay = 0): void
    {
        $this->connection->table($this->table)->where('id', $this->getId())->update([
            'reserved_at' => null,
            'available_at' => time() + $delay,
        ]);
    }

    public function attempts(): int
    {
        return (int) $this->record['attempts'];
    }

    public function maxAttempts(): int
    {
        $payload = $this->getPayload();
        return (int) ($payload['max_attempts'] ?? 3);
    }

    public function failed(\Throwable $e): void
    {
        // Store into failed_jobs table
        try {
            $this->connection->table('failed_jobs')->insert([
                'connection' => $this->connection->getName(),
                'queue' => $this->record['queue'],
                'payload' => $this->record['payload'],
                'exception' => $e->getMessage() . "\n" . $e->getTraceAsString(),
                'failed_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // Ignore if failed_jobs table does not exist
        }

        $this->delete();
    }
}

