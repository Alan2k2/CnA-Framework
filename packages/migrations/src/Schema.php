<?php

declare(strict_types=1);

namespace CnA\Migrations;

use CnA\Contracts\Database\ConnectionInterface;
use CnA\Database\ConnectionManager;

class Schema
{
    public static function create(string $table, callable $callback, ?string $connection = null): void
    {
        $conn = static::getConnection($connection);
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $driver = $conn->getDialect()->getDriverName();
        $sql = $blueprint->toSql($driver);

        $conn->statement($sql);
    }

    public static function dropIfExists(string $table, ?string $connection = null): void
    {
        $conn = static::getConnection($connection);
        $quoted = $conn->getDialect()->quoteIdentifier($table);
        $conn->statement("DROP TABLE IF EXISTS {$quoted}");
    }

    protected static function getConnection(?string $name = null): ConnectionInterface
    {
        /** @var ConnectionManager $manager */
        $manager = app(ConnectionManager::class);
        return $manager->connection($name);
    }
}

