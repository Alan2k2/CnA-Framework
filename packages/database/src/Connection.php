<?php

declare(strict_types=1);

namespace CnA\Database;

use CnA\Contracts\Database\ConnectionInterface;
use CnA\Contracts\Database\DialectInterface;
use CnA\Contracts\Database\QueryBuilderInterface;
use CnA\Contracts\Exceptions\DatabaseException;
use CnA\QueryBuilder\QueryBuilder;
use PDO;
use PDOException;
use PDOStatement;

class Connection implements ConnectionInterface
{
    protected ?PDO $pdo = null;
    protected int $transactionLevel = 0;
    /** @var array<callable> */
    protected static array $queryListeners = [];

    /**
     * @param \Closure|PDO $pdoResolver
     */
    public function __construct(
        protected string $name,
        protected mixed $pdoResolver,
        protected DialectInterface $dialect
    ) {
    }

    public static function listen(callable $callback): void
    {
        static::$queryListeners[] = $callback;
    }

    public static function clearListeners(): void
    {
        static::$queryListeners = [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = is_callable($this->pdoResolver) ? ($this->pdoResolver)() : $this->pdoResolver;
        }
        return $this->pdo;
    }

    public function getDialect(): DialectInterface
    {
        return $this->dialect;
    }

    public function table(string $table): QueryBuilderInterface
    {
        return (new QueryBuilder($this))->from($table);
    }

    public function select(string $query, array $bindings = []): array
    {
        return $this->run($query, $bindings, function (PDOStatement $stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function selectOne(string $query, array $bindings = []): ?array
    {
        $records = $this->select($query, $bindings);
        return $records[0] ?? null;
    }

    public function insert(string $query, array $bindings = []): int|string
    {
        return $this->run($query, $bindings, function (PDOStatement $stmt) {
            return $this->getPdo()->lastInsertId();
        });
    }

    public function update(string $query, array $bindings = []): int
    {
        return $this->run($query, $bindings, function (PDOStatement $stmt) {
            return $stmt->rowCount();
        });
    }

    public function delete(string $query, array $bindings = []): int
    {
        return $this->update($query, $bindings);
    }

    public function statement(string $query, array $bindings = []): bool
    {
        return $this->run($query, $bindings, fn() => true);
    }

    public function beginTransaction(): void
    {
        $pdo = $this->getPdo();

        if ($this->transactionLevel === 0) {
            $pdo->beginTransaction();
        } elseif ($this->dialect->supportsSavepoints()) {
            $pdo->exec($this->dialect->createSavepointSql('trans_' . $this->transactionLevel));
        }

        $this->transactionLevel++;
    }

    public function commit(): void
    {
        if ($this->transactionLevel === 0) {
            return;
        }

        $this->transactionLevel--;

        if ($this->transactionLevel === 0) {
            $this->getPdo()->commit();
        } elseif ($this->dialect->supportsSavepoints()) {
            $this->getPdo()->exec($this->dialect->releaseSavepointSql('trans_' . $this->transactionLevel));
        }
    }

    public function rollBack(): void
    {
        if ($this->transactionLevel === 0) {
            return;
        }

        $this->transactionLevel--;

        if ($this->transactionLevel === 0) {
            $this->getPdo()->rollBack();
        } elseif ($this->dialect->supportsSavepoints()) {
            $this->getPdo()->exec($this->dialect->rollbackSavepointSql('trans_' . $this->transactionLevel));
        }
    }

    public function transactionLevel(): int
    {
        return $this->transactionLevel;
    }

    protected function run(string $query, array $bindings, callable $callback): mixed
    {
        $start = microtime(true);

        try {
            $stmt = $this->getPdo()->prepare($query);
            $this->bindValues($stmt, $bindings);
            $stmt->execute();
            $result = $callback($stmt);
        } catch (PDOException $e) {
            throw new DatabaseException("Database query error: {$e->getMessage()} [SQL: {$query}]", (int) $e->getCode(), $e);
        }

        $duration = microtime(true) - $start;
        $this->notifyQueryListeners($query, $bindings, $duration);

        return $result;
    }

    protected function bindValues(PDOStatement $stmt, array $bindings): void
    {
        foreach ($bindings as $key => $value) {
            $paramIndex = is_int($key) ? $key + 1 : $key;

            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };

            $stmt->bindValue($paramIndex, $value, $type);
        }
    }

    protected function notifyQueryListeners(string $sql, array $bindings, float $duration): void
    {
        if (empty(static::$queryListeners)) {
            return;
        }

        $event = [
            'sql' => $sql,
            'bindings' => $bindings,
            'duration' => $duration,
            'connection' => $this->name,
            'time' => microtime(true),
        ];

        foreach (static::$queryListeners as $listener) {
            $listener($event);
        }
    }
}

