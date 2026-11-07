<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use CnA\Migrations\Migrator;

class DbStatusCommand extends Command
{
    protected string $name = 'db:status';
    protected string $description = 'Show the status of each migration';

    public function handle(): int
    {
        $path = $this->app->basePath('database/migrations');
        /** @var Migrator $migrator */
        $migrator = $this->app->has(Migrator::class) ? $this->app->get(Migrator::class) : new Migrator();

        $status = $migrator->status($path);

        if (empty($status)) {
            $this->comment("No migrations found.");
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($status as $s) {
            $ran = $s['ran'] ? "\033[32mRan\033[0m" : "\033[33mPending\033[0m";
            $batch = $s['batch'] !== null ? (string) $s['batch'] : '-';
            $rows[] = [$s['migration'], $ran, $batch];
        }

        $this->table(['Migration', 'Status', 'Batch'], $rows);
        return Command::SUCCESS;
    }
}

