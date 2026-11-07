<?php

declare(strict_types=1);

namespace CnA\Contracts\Database;

interface TransactionManagerInterface
{
    /**
     * Execute a callback within a transaction, handling savepoints and automatic rollback on failure.
     */
    public function run(callable $callback, int $attempts = 1): mixed;

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;
}

