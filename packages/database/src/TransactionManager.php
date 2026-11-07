<?php

declare(strict_types=1);

namespace CnA\Database;

use CnA\Contracts\Database\ConnectionInterface;
use CnA\Contracts\Database\TransactionManagerInterface;

class TransactionManager implements TransactionManagerInterface
{
    public function __construct(protected ConnectionInterface $connection)
    {
    }

    public function run(callable $callback, int $attempts = 1): mixed
    {
        for ($currentAttempt = 1; $currentAttempt <= $attempts; $currentAttempt++) {
            $this->beginTransaction();

            try {
                $result = $callback($this);
                $this->commit();
                return $result;
            } catch (\Throwable $e) {
                $this->rollBack();

                if ($currentAttempt >= $attempts || !$this->isDeadlock($e)) {
                    throw $e;
                }

                usleep(50000 * $currentAttempt);
            }
        }

        throw new \RuntimeException("Transaction failed after {$attempts} attempts.");
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    protected function isDeadlock(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        return str_contains($message, 'deadlock')
            || str_contains($message, 'lock wait timeout')
            || str_contains($message, 'database is locked');
    }
}

