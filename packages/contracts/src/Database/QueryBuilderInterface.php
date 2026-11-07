<?php

declare(strict_types=1);

namespace CnA\Contracts\Database;

interface QueryBuilderInterface
{
    public function select(array|string ...$columns): static;
    public function from(string $table, ?string $alias = null): static;
    public function table(string $table, ?string $alias = null): static;
    public function getFrom(): string;
    public function join(string $table, string $first, string $operator, string $second, string $type = 'inner'): static;
    public function leftJoin(string $table, string $first, string $operator, string $second): static;
    public function rightJoin(string $table, string $first, string $operator, string $second): static;
    public function where(string|callable $column, mixed $operator = null, mixed $value = null): static;
    public function orWhere(string|callable $column, mixed $operator = null, mixed $value = null): static;
    public function whereIn(string $column, array $values): static;
    public function whereNotIn(string $column, array $values): static;
    public function whereNull(string $column): static;
    public function whereNotNull(string $column): static;
    public function whereBetween(string $column, array $values): static;
    public function orderBy(string $column, string $direction = 'asc'): static;
    public function groupBy(string ...$groups): static;
    public function having(string $column, string $operator, mixed $value): static;
    public function limit(int $value): static;
    public function offset(int $value): static;
    public function get(): array;
    public function first(): ?array;
    public function count(string $column = '*'): int;
    public function sum(string $column): float|int;
    public function avg(string $column): float;
    public function min(string $column): mixed;
    public function max(string $column): mixed;
    public function insert(array $values): int|string;
    public function update(array $values): int;
    public function delete(): int;
    public function toSql(): string;
    public function getBindings(): array;
    public function paginate(int $perPage = 15, int $page = 1): array;
    public function chunk(int $count, callable $callback): bool;
}

