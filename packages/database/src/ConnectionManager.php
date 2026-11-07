<?php

declare(strict_types=1);

namespace CnA\Database;

use CnA\Contracts\Database\ConnectionInterface;
use CnA\Contracts\Database\ConnectionManagerInterface;
use CnA\Contracts\Database\DialectInterface;
use CnA\Contracts\Exceptions\DatabaseException;
use CnA\Database\Dialects\MysqlDialect;
use CnA\Database\Dialects\PostgresDialect;
use CnA\Database\Dialects\SqliteDialect;
use PDO;

class ConnectionManager implements ConnectionManagerInterface
{
    /** @var array<string, ConnectionInterface> */
    protected array $connections = [];

    public function __construct(
        protected array $config = [],
        protected string $defaultConnection = 'sqlite'
    ) {
    }

    public function connection(?string $name = null): ConnectionInterface
    {
        $name = $name ?? $this->defaultConnection;

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->makeConnection($name);
        }

        return $this->connections[$name];
    }

    public function getDefaultConnection(): string
    {
        return $this->defaultConnection;
    }

    public function setDefaultConnection(string $name): void
    {
        $this->defaultConnection = $name;
    }

    public function reconnect(?string $name = null): ConnectionInterface
    {
        $name = $name ?? $this->defaultConnection;
        $this->disconnect($name);
        return $this->connection($name);
    }

    public function disconnect(?string $name = null): void
    {
        $name = $name ?? $this->defaultConnection;
        unset($this->connections[$name]);
    }

    protected function makeConnection(string $name): ConnectionInterface
    {
        $config = $this->config['connections'][$name] ?? null;
        if ($config === null) {
            throw new DatabaseException("Database connection [{$name}] not configured.");
        }

        $driver = $config['driver'] ?? 'sqlite';
        $dialect = $this->createDialect($driver);

        $pdoResolver = function () use ($config, $driver) {
            return $this->createPdoInstance($driver, $config);
        };

        return new Connection($name, $pdoResolver, $dialect);
    }

    protected function createDialect(string $driver): DialectInterface
    {
        return match ($driver) {
            'sqlite' => new SqliteDialect(),
            'mysql' => new MysqlDialect(),
            'pgsql', 'postgres', 'postgresql' => new PostgresDialect(),
            default => throw new DatabaseException("Unsupported database driver [{$driver}]."),
        };
    }

    protected function createPdoInstance(string $driver, array $config): PDO
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return match ($driver) {
            'sqlite' => new PDO(
                'sqlite:' . ($config['database'] ?? ':memory:'),
                null,
                null,
                $options
            ),
            'mysql' => new PDO(
                "mysql:host={$config['host']};port=" . ($config['port'] ?? 3306) . ";dbname={$config['database']};charset=" . ($config['charset'] ?? 'utf8mb4'),
                $config['username'] ?? 'root',
                $config['password'] ?? '',
                $options
            ),
            'pgsql', 'postgres', 'postgresql' => new PDO(
                "pgsql:host={$config['host']};port=" . ($config['port'] ?? 5432) . ";dbname={$config['database']}",
                $config['username'] ?? 'postgres',
                $config['password'] ?? '',
                $options
            ),
            default => throw new DatabaseException("Unsupported driver [{$driver}]."),
        };
    }
}

