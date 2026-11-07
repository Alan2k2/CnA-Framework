<?php

declare(strict_types=1);

namespace CnA\QueryBuilder;

use CnA\Contracts\Database\ConnectionInterface;
use CnA\Contracts\Database\QueryBuilderInterface;

class QueryBuilder implements QueryBuilderInterface
{
    protected array $columns = ['*'];
    protected string $from = '';
    protected ?string $fromAlias = null;
    protected array $joins = [];
    protected array $wheres = [];
    protected array $orders = [];
    protected array $groups = [];
    protected array $havings = [];
    protected ?int $limitValue = null;
    protected ?int $offsetValue = null;
    protected array $bindings = [];

    public function __construct(protected ConnectionInterface $connection)
    {
    }

    public function select(array|string ...$columns): static
    {
        $cols = [];
        foreach ($columns as $col) {
            if (is_array($col)) {
                $cols = array_merge($cols, $col);
            } else {
                $cols[] = $col;
            }
        }
        $this->columns = !empty($cols) ? $cols : ['*'];
        return $this;
    }

    public function from(string $table, ?string $alias = null): static
    {
        $this->from = $table;
        $this->fromAlias = $alias;
        return $this;
    }

    public function table(string $table, ?string $alias = null): static
    {
        return $this->from($table, $alias);
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'inner'): static
    {
        $this->joins[] = [
            'type' => strtoupper($type),
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    public function where(string|callable $column, mixed $operator = null, mixed $value = null): static
    {
        return $this->addWhere('AND', $column, $operator, $value);
    }

    public function orWhere(string|callable $column, mixed $operator = null, mixed $value = null): static
    {
        return $this->addWhere('OR', $column, $operator, $value);
    }

    public function whereIn(string $column, array $values): static
    {
        $this->wheres[] = [
            'boolean' => 'AND',
            'type' => 'in',
            'column' => $column,
            'values' => array_values($values),
        ];
        $this->bindings = array_merge($this->bindings, array_values($values));
        return $this;
    }

    public function whereNotIn(string $column, array $values): static
    {
        $this->wheres[] = [
            'boolean' => 'AND',
            'type' => 'not_in',
            'column' => $column,
            'values' => array_values($values),
        ];
        $this->bindings = array_merge($this->bindings, array_values($values));
        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->wheres[] = [
            'boolean' => 'AND',
            'type' => 'null',
            'column' => $column,
        ];
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->wheres[] = [
            'boolean' => 'AND',
            'type' => 'not_null',
            'column' => $column,
        ];
        return $this;
    }

    public function whereBetween(string $column, array $values): static
    {
        $this->wheres[] = [
            'boolean' => 'AND',
            'type' => 'between',
            'column' => $column,
            'values' => [$values[0], $values[1]],
        ];
        $this->bindings[] = $values[0];
        $this->bindings[] = $values[1];
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC',
        ];
        return $this;
    }

    public function groupBy(string ...$groups): static
    {
        $this->groups = array_merge($this->groups, $groups);
        return $this;
    }

    public function having(string $column, string $operator, mixed $value): static
    {
        $this->havings[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];
        $this->bindings[] = $value;
        return $this;
    }

    public function limit(int $value): static
    {
        $this->limitValue = $value;
        return $this;
    }

    public function offset(int $value): static
    {
        $this->offsetValue = $value;
        return $this;
    }

    public function get(): array
    {
        return $this->connection->select($this->toSql(), $this->getBindings());
    }

    public function first(): ?array
    {
        $clone = clone $this;
        $clone->limit(1);
        return $clone->connection->selectOne($clone->toSql(), $clone->getBindings());
    }

    public function count(string $column = '*'): int
    {
        $clone = clone $this;
        $clone->columns = ["COUNT({$column}) as aggregate"];
        $result = $clone->connection->selectOne($clone->toSql(), $clone->getBindings());
        return (int) ($result['aggregate'] ?? 0);
    }

    public function sum(string $column): float|int
    {
        $clone = clone $this;
        $clone->columns = ["SUM({$column}) as aggregate"];
        $result = $clone->connection->selectOne($clone->toSql(), $clone->getBindings());
        return (float) ($result['aggregate'] ?? 0);
    }

    public function avg(string $column): float
    {
        $clone = clone $this;
        $clone->columns = ["AVG({$column}) as aggregate"];
        $result = $clone->connection->selectOne($clone->toSql(), $clone->getBindings());
        return (float) ($result['aggregate'] ?? 0);
    }

    public function min(string $column): mixed
    {
        $clone = clone $this;
        $clone->columns = ["MIN({$column}) as aggregate"];
        $result = $clone->connection->selectOne($clone->toSql(), $clone->getBindings());
        return $result['aggregate'] ?? null;
    }

    public function max(string $column): mixed
    {
        $clone = clone $this;
        $clone->columns = ["MAX({$column}) as aggregate"];
        $result = $clone->connection->selectOne($clone->toSql(), $clone->getBindings());
        return $result['aggregate'] ?? null;
    }

    public function insert(array $values): int|string
    {
        $dialect = $this->connection->getDialect();
        $quotedTable = $dialect->quoteIdentifier($this->from);

        $keys = array_keys($values);
        $quotedColumns = implode(', ', array_map([$dialect, 'quoteIdentifier'], $keys));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        $sql = "INSERT INTO {$quotedTable} ({$quotedColumns}) VALUES ({$placeholders})";
        return $this->connection->insert($sql, array_values($values));
    }

    public function update(array $values): int
    {
        $dialect = $this->connection->getDialect();
        $quotedTable = $dialect->quoteIdentifier($this->from);

        $sets = [];
        $updateBindings = [];
        foreach ($values as $col => $val) {
            $sets[] = "{$dialect->quoteIdentifier($col)} = ?";
            $updateBindings[] = $val;
        }

        $sql = "UPDATE {$quotedTable} SET " . implode(', ', $sets);

        $whereClause = $this->compileWheres();
        if ($whereClause !== '') {
            $sql .= " WHERE {$whereClause}";
        }

        $allBindings = array_merge($updateBindings, $this->bindings);
        return $this->connection->update($sql, $allBindings);
    }

    public function delete(): int
    {
        $dialect = $this->connection->getDialect();
        $quotedTable = $dialect->quoteIdentifier($this->from);

        $sql = "DELETE FROM {$quotedTable}";
        $whereClause = $this->compileWheres();
        if ($whereClause !== '') {
            $sql .= " WHERE {$whereClause}";
        }

        return $this->connection->delete($sql, $this->bindings);
    }

    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $total = $this->count();
        $offset = ($page - 1) * $perPage;

        $items = $this->limit($perPage)->offset($offset)->get();

        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    public function chunk(int $count, callable $callback): bool
    {
        $page = 1;
        do {
            $results = (clone $this)->limit($count)->offset(($page - 1) * $count)->get();
            $countResults = count($results);
            if ($countResults === 0) {
                break;
            }

            if ($callback($results, $page) === false) {
                return false;
            }

            unset($results);
            $page++;
        } while ($countResults === $count);

        return true;
    }

    public function toSql(): string
    {
        $dialect = $this->connection->getDialect();

        $cols = implode(', ', array_map(function ($c) use ($dialect) {
            return str_contains($c, '(') || str_contains($c, ' as ') || str_contains($c, ' AS ')
                ? $c
                : $dialect->quoteIdentifier($c);
        }, $this->columns));

        $fromTable = $dialect->quoteIdentifier($this->from);
        if ($this->fromAlias !== null) {
            $fromTable .= ' AS ' . $dialect->quoteIdentifier($this->fromAlias);
        }

        $sql = "SELECT {$cols} FROM {$fromTable}";

        foreach ($this->joins as $j) {
            $sql .= " {$j['type']} JOIN {$dialect->quoteIdentifier($j['table'])} ON {$dialect->quoteIdentifier($j['first'])} {$j['operator']} {$dialect->quoteIdentifier($j['second'])}";
        }

        $whereSql = $this->compileWheres();
        if ($whereSql !== '') {
            $sql .= " WHERE {$whereSql}";
        }

        if (!empty($this->groups)) {
            $sql .= " GROUP BY " . implode(', ', array_map([$dialect, 'quoteIdentifier'], $this->groups));
        }

        if (!empty($this->havings)) {
            $hParts = [];
            foreach ($this->havings as $h) {
                $hParts[] = "{$dialect->quoteIdentifier($h['column'])} {$h['operator']} ?";
            }
            $sql .= " HAVING " . implode(' AND ', $hParts);
        }

        if (!empty($this->orders)) {
            $orderParts = [];
            foreach ($this->orders as $o) {
                $orderParts[] = "{$dialect->quoteIdentifier($o['column'])} {$o['direction']}";
            }
            $sql .= " ORDER BY " . implode(', ', $orderParts);
        }

        $sql .= $dialect->compileLimitOffset($this->limitValue, $this->offsetValue);

        return $sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    protected function addWhere(string $boolean, string|callable $column, mixed $operator = null, mixed $value = null): static
    {
        if (is_callable($column)) {
            $subQuery = new static($this->connection);
            $column($subQuery);
            $this->wheres[] = [
                'boolean' => $boolean,
                'type' => 'nested',
                'query' => $subQuery,
            ];
            $this->bindings = array_merge($this->bindings, $subQuery->getBindings());
            return $this;
        }

        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'boolean' => $boolean,
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];
        $this->bindings[] = $value;

        return $this;
    }

    protected function compileWheres(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $dialect = $this->connection->getDialect();
        $parts = [];

        foreach ($this->wheres as $index => $where) {
            $prefix = $index === 0 ? '' : "{$where['boolean']} ";

            if ($where['type'] === 'basic') {
                $parts[] = "{$prefix}{$dialect->quoteIdentifier($where['column'])} {$where['operator']} ?";
            } elseif ($where['type'] === 'in') {
                $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                $parts[] = "{$prefix}{$dialect->quoteIdentifier($where['column'])} IN ({$placeholders})";
            } elseif ($where['type'] === 'not_in') {
                $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                $parts[] = "{$prefix}{$dialect->quoteIdentifier($where['column'])} NOT IN ({$placeholders})";
            } elseif ($where['type'] === 'null') {
                $parts[] = "{$prefix}{$dialect->quoteIdentifier($where['column'])} IS NULL";
            } elseif ($where['type'] === 'not_null') {
                $parts[] = "{$prefix}{$dialect->quoteIdentifier($where['column'])} IS NOT NULL";
            } elseif ($where['type'] === 'between') {
                $parts[] = "{$prefix}{$dialect->quoteIdentifier($where['column'])} BETWEEN ? AND ?";
            } elseif ($where['type'] === 'nested') {
                $nestedSql = $where['query']->compileWheres();
                $parts[] = "{$prefix}({$nestedSql})";
            }
        }

        return implode(' ', $parts);
    }
}

