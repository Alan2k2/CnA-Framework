<?php

declare(strict_types=1);

namespace CnA\Migrations;

use CnA\Contracts\Database\ConnectionInterface;
use CnA\Database\ConnectionManager;

class Migrator
{
    protected string $table = 'migrations';

    public function __construct(protected ?ConnectionInterface $connection = null)
    {
    }

    public function getConnection(): ConnectionInterface
    {
        if ($this->connection === null) {
            /** @var ConnectionManager $manager */
            $manager = app(ConnectionManager::class);
            $this->connection = $manager->connection();
        }
        return $this->connection;
    }

    public function prepareTable(): void
    {
        $conn = $this->getConnection();
        $quoted = $conn->getDialect()->quoteIdentifier($this->table);

        $driver = $conn->getDialect()->getDriverName();
        $autoInc = match ($driver) {
            'sqlite' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'pgsql' => 'SERIAL PRIMARY KEY',
            default => 'INT AUTO_INCREMENT PRIMARY KEY',
        };

        $sql = "CREATE TABLE IF NOT EXISTS {$quoted} (
            \"id\" {$autoInc},
            \"migration\" VARCHAR(255) NOT NULL,
            \"batch\" INT NOT NULL
        )";

        $conn->statement($sql);
    }

    public function run(string $path): array
    {
        $this->prepareTable();
        $files = $this->getMigrationFiles($path);
        $ran = $this->getRan();

        $batch = $this->getNextBatchNumber();
        $executed = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $ran, true)) {
                continue;
            }

            $migration = require $file;
            if ($migration instanceof Migration) {
                $migration->up();
            }

            $this->getConnection()->table($this->table)->insert([
                'migration' => $name,
                'batch' => $batch,
            ]);

            $executed[] = $name;
        }

        return $executed;
    }

    public function rollback(string $path): array
    {
        $this->prepareTable();
        $lastBatch = $this->getLastBatchNumber();
        if ($lastBatch === 0) {
            return [];
        }

        $records = $this->getConnection()->table($this->table)
            ->where('batch', $lastBatch)
            ->orderBy('id', 'desc')
            ->get();

        $rolledBack = [];
        $files = $this->getMigrationFiles($path);
        $fileMap = [];
        foreach ($files as $f) {
            $fileMap[basename($f, '.php')] = $f;
        }

        foreach ($records as $record) {
            $name = $record['migration'];
            if (isset($fileMap[$name])) {
                $migration = require $fileMap[$name];
                if ($migration instanceof Migration) {
                    $migration->down();
                }
            }

            $this->getConnection()->table($this->table)->where('id', $record['id'])->delete();
            $rolledBack[] = $name;
        }

        return $rolledBack;
    }

    public function status(string $path): array
    {
        $this->prepareTable();
        $files = $this->getMigrationFiles($path);
        $ranRecords = $this->getConnection()->table($this->table)->get();
        $ranMap = [];
        foreach ($ranRecords as $r) {
            $ranMap[$r['migration']] = $r['batch'];
        }

        $status = [];
        foreach ($files as $f) {
            $name = basename($f, '.php');
            $status[] = [
                'migration' => $name,
                'ran' => isset($ranMap[$name]),
                'batch' => $ranMap[$name] ?? null,
            ];
        }

        return $status;
    }

    public function getRan(): array
    {
        $records = $this->getConnection()->table($this->table)->get();
        return array_column($records, 'migration');
    }

    protected function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    protected function getLastBatchNumber(): int
    {
        $max = $this->getConnection()->table($this->table)->max('batch');
        return (int) ($max ?? 0);
    }

    protected function getMigrationFiles(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }
        $files = glob(rtrim($path, '/\\') . '/*.php');
        if ($files === false) {
            return [];
        }
        sort($files);
        return $files;
    }
}

