<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Migrations\Migrator;

class MigrateCommand extends Command
{
    protected string $name = 'migrate';
    protected string $description = 'Run the database migrations';

    public function handle(): int
    {
        $path = $this->app->basePath('database/migrations');
        $this->info("Running migrations from: {$path}");

        /** @var Migrator $migrator */
        $migrator = $this->app->has(Migrator::class) ? $this->app->get(Migrator::class) : new Migrator();

        $executed = $migrator->run($path);

        if (empty($executed)) {
            $this->comment("Nothing to migrate.");
            return Command::SUCCESS;
        }

        foreach ($executed as $m) {
            $this->line("<info>Migrated:</info> {$m}");
        }

        $this->info("All migrations completed successfully.");
        return Command::SUCCESS;
    }
}

