<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Migrations\Migrator;

class MigrateRollbackCommand extends Command
{
    protected string $name = 'migrate:rollback';
    protected string $description = 'Rollback the last database migration batch';

    public function handle(): int
    {
        $path = $this->app->basePath('database/migrations');
        /** @var Migrator $migrator */
        $migrator = $this->app->has(Migrator::class) ? $this->app->get(Migrator::class) : new Migrator();

        $rolledBack = $migrator->rollback($path);

        if (empty($rolledBack)) {
            $this->comment("Nothing to rollback.");
            return Command::SUCCESS;
        }

        foreach ($rolledBack as $m) {
            $this->line("<comment>Rolled back:</comment> {$m}");
        }

        $this->info("Rollback completed successfully.");
        return Command::SUCCESS;
    }
}

