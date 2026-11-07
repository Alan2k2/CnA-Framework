<?php

declare(strict_types=1);

namespace CnA\Database\Dialects;

use CnA\Contracts\Database\DialectInterface;

class MysqlDialect implements DialectInterface
{
    public function getDriverName(): string
    {
        return 'mysql';
    }

    public function quoteIdentifier(string $identifier): string
    {
        if ($identifier === '*') {
            return '*';
        }
        if (str_contains($identifier, '.')) {
            return implode('.', array_map([$this, 'quoteIdentifier'], explode('.', $identifier)));
        }
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    public function compileLimitOffset(?int $limit, ?int $offset): string
    {
        $sql = '';
        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }
        if ($offset !== null) {
            if ($limit === null) {
                $sql .= " LIMIT 18446744073709551615";
            }
            $sql .= " OFFSET {$offset}";
        }
        return $sql;
    }

    public function compileUpsert(string $table, array $values, array $uniqueKeys, array $updateColumns): string
    {
        $quotedTable = $this->quoteIdentifier($table);
        $cols = implode(', ', array_map([$this, 'quoteIdentifier'], array_keys($values)));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        $updates = [];
        foreach ($updateColumns as $col) {
            $qCol = $this->quoteIdentifier($col);
            $updates[] = "{$qCol} = VALUES({$qCol})";
        }
        $updateSql = implode(', ', $updates);

        return "INSERT INTO {$quotedTable} ({$cols}) VALUES ({$placeholders}) ON DUPLICATE KEY UPDATE {$updateSql}";
    }

    public function supportsSavepoints(): bool
    {
        return true;
    }

    public function createSavepointSql(string $name): string
    {
        return "SAVEPOINT {$name}";
    }

    public function releaseSavepointSql(string $name): string
    {
        return "RELEASE SAVEPOINT {$name}";
    }

    public function rollbackSavepointSql(string $name): string
    {
        return "ROLLBACK TO SAVEPOINT {$name}";
    }
}

