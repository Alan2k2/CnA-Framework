<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class DbRestoreCommand extends Command
{
    protected string $name = 'db:restore';
    protected string $description = 'Restore a database snapshot from backup file';

    public function handle(): int
    {
        $file = $this->argument(0);
        if (empty($file)) {
            $this->error('Backup file path is required. Usage: php cna db:restore <path/to/file>');
            return Command::INVALID;
        }

        if (!file_exists($file)) {
            $this->error("Backup file not found: {$file}");
            return Command::FAILURE;
        }

        $config = $this->app->has('config') ? $this->app->make('config') : null;
        $defaultConn = $config?->get('database.default', 'sqlite') ?? 'sqlite';
        $connConfig = $config?->get("database.connections.{$defaultConn}", []);

        if ($defaultConn === 'sqlite') {
            $targetPath = $connConfig['database'] ?? $this->app->storagePath('database.sqlite');
            if (!copy($file, $targetPath)) {
                $this->error("Failed to restore backup to {$targetPath}");
                return Command::FAILURE;
            }

            $this->info("Database restored successfully from: {$file}");
            $this->info("Target: {$targetPath}");
            return Command::SUCCESS;
        }

        $this->warn("Restore command currently supports SQLite databases directly.");
        return Command::SUCCESS;
    }
}
