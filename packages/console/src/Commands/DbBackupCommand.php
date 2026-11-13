<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;

class DbBackupCommand extends Command
{
    protected string $name = 'db:backup';
    protected string $description = 'Create a database backup snapshot';

    public function handle(): int
    {
        $backupDir = $this->app->storagePath('backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $config = $this->app->has('config') ? $this->app->make('config') : null;
        $defaultConn = $config?->get('database.default', 'sqlite') ?? 'sqlite';
        $connConfig = $config?->get("database.connections.{$defaultConn}", []);

        $timestamp = date('Y-m-d_His');
        $output = $this->option('output') ?: "{$backupDir}/db_backup_{$defaultConn}_{$timestamp}.sqlite";

        if ($defaultConn === 'sqlite') {
            $dbPath = $connConfig['database'] ?? $this->app->storagePath('database.sqlite');
            if (!file_exists($dbPath)) {
                // If database file does not exist, create empty backup or note
                file_put_contents($output, '');
                $this->info("Created empty SQLite snapshot at: {$output}");
                return Command::SUCCESS;
            }

            if (!copy($dbPath, $output)) {
                $this->error("Failed to copy database file from {$dbPath} to {$output}");
                return Command::FAILURE;
            }

            $size = round(filesize($output) / 1024, 2);
            $this->info("Database backup created successfully!");
            $this->info("File: {$output} ({$size} KB)");
            return Command::SUCCESS;
        }

        $this->warn("Native file snapshotting is optimized for SQLite. For MySQL/PgSQL, ensure mysqldump/pg_dump are available.");
        file_put_contents($output, "-- Backup for {$defaultConn} created at " . date('c'));
        $this->info("Created snapshot placeholder at: {$output}");
        return Command::SUCCESS;
    }
}

