<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Migrations\Migrator;

class MigrateRefreshCommand extends Command
{
    protected string $name = 'migrate:refresh';
    protected string $description = 'Rollback all migrations and run them again';

    public function handle(): int
    {
        $path = $this->app->basePath('database/migrations');
        $migrator = $this->app->has(Migrator::class) ? $this->app->get(Migrator::class) : new Migrator();

        $this->info("Rolling back all database migrations...");

        while (!empty($migrator->rollback($path))) {
            // Keep rolling back until all batches are cleared
        }

        $this->info("Re-running all migrations...");
        $executed = $migrator->run($path);

        foreach ($executed as $m) {
            $this->line("<info>Migrated:</info> {$m}");
        }

        if ($this->hasOption('seed')) {
            $this->info("Seeding database after refresh...");
            $seedCmd = new DbSeedCommand($this->app);
            $seedCmd->run([], []);
        }

        $this->info("Database refresh complete.");
        return Command::SUCCESS;
    }
}

