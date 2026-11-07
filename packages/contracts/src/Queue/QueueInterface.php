<?php

declare(strict_types=1);

namespace CnA\Contracts\Queue;

interface QueueInterface
{
    public function push(object|string $job, mixed $data = '', ?string $queue = null): string|int;
    public function later(int $delay, object|string $job, mixed $data = '', ?string $queue = null): string|int;
    public function pop(?string $queue = null): ?JobInterface;
    public function size(?string $queue = null): int;
}

