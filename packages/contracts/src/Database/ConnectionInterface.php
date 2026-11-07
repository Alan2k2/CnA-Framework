<?php

declare(strict_types=1);

namespace CnA\Contracts\Database;

use PDO;
use PDOStatement;

interface ConnectionInterface
{
    public function getName(): string;
    public function getPdo(): PDO;
    public function getDialect(): DialectInterface;
    public function select(string $query, array $bindings = []): array;
    public function selectOne(string $query, array $bindings = []): ?array;
    public function insert(string $query, array $bindings = []): int|string;
    public function update(string $query, array $bindings = []): int;
    public function delete(string $query, array $bindings = []): int;
    public function statement(string $query, array $bindings = []): bool;
    public function table(string $table): QueryBuilderInterface;
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollBack(): void;
    public function transactionLevel(): int;
}

