<?php

declare(strict_types=1);

namespace CnA\Contracts\Queue;

interface JobInterface
{
    public function getId(): string|int;
    public function getName(): string;
    public function getPayload(): array;
    public function fire(): void;
    public function delete(): void;
    public function release(int $delay = 0): void;
    public function attempts(): int;
    public function maxAttempts(): int;
    public function failed(\Throwable $e): void;
}

