<?php

declare(strict_types=1);

namespace CnA\Contracts\Database;

interface DialectInterface
{
    public function getDriverName(): string;
    public function quoteIdentifier(string $identifier): string;
    public function compileLimitOffset(?int $limit, ?int $offset): string;
    public function compileUpsert(string $table, array $values, array $uniqueKeys, array $updateColumns): string;
    public function supportsSavepoints(): bool;
    public function createSavepointSql(string $name): string;
    public function releaseSavepointSql(string $name): string;
    public function rollbackSavepointSql(string $name): string;
}

