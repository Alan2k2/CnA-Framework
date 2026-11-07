<?php

declare(strict_types=1);

namespace Tests\Unit;

use CnA\Database\Connection;
use CnA\Database\Dialects\SqliteDialect;
use CnA\QueryBuilder\QueryBuilder;
use CnA\Testing\TestCase;

class QueryBuilderTest extends TestCase
{
    public function testCompilesBasicSelect(): void
    {
        $conn = new Connection('sqlite', fn() => new \PDO('sqlite::memory:'), new SqliteDialect());
        $builder = new QueryBuilder($conn);

        $sql = $builder->select('id', 'name')->from('users')->where('id', '=', 5)->toSql();

        $this->assertEquals('SELECT "id", "name" FROM "users" WHERE "id" = ?', $sql);
        $this->assertEquals([5], $builder->getBindings());
    }

    public function testCompilesComplexWheresAndJoins(): void
    {
        $conn = new Connection('sqlite', fn() => new \PDO('sqlite::memory:'), new SqliteDialect());
        $builder = new QueryBuilder($conn);

        $sql = $builder->from('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->where('orders.status', 'completed')
            ->whereIn('orders.user_id', [1, 2, 3])
            ->orderBy('orders.id', 'desc')
            ->limit(10)
            ->offset(20)
            ->toSql();

        $this->assertTrue(str_contains($sql, 'INNER JOIN "users" ON "orders"."user_id" = "users"."id"'));
        $this->assertTrue(str_contains($sql, 'WHERE "orders"."status" = ? AND "orders"."user_id" IN (?, ?, ?)'));
        $this->assertTrue(str_contains($sql, 'ORDER BY "orders"."id" DESC'));
        $this->assertTrue(str_contains($sql, 'LIMIT 10 OFFSET 20'));
    }
}

