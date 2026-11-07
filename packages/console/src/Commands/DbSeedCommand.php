<?php

declare(strict_types=1);

namespace CnA\Console\Commands;

use CnA\Console\Command;
use Database\Seeders\DatabaseSeeder;

class DbSeedCommand extends Command
{
    protected string $name = 'db:seed';
    protected string $description = 'Seed the database with records';

    public function handle(): int
    {
        $class = $this->argument(0, DatabaseSeeder::class);

        $seederFile = $this->app->basePath('database/seeders/' . basename(str_replace('\\', '/', $class)) . '.php');
        if (file_exists($seederFile)) {
            require_once $seederFile;
        }

        if (!class_exists($class)) {
            $this->error("Seeder class [{$class}] does not exist.");
            return Command::INVALID;
        }

        $this->info("Running seeder [{$class}]...");

        $seeder = new $class($this->app);
        $seeder->run();

        $this->info("Database seeded successfully.");
        return Command::SUCCESS;
    }
}

